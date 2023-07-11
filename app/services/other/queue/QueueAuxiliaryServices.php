<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\services\other\queue;


use app\dao\other\queue\QueueAuxiliaryDao;
use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use think\exception\ValidateException;

/**
 * 队列辅助
 * Class QueueAuxiliaryServices
 * @package app\services\other\queue
 */
class QueueAuxiliaryServices extends BaseServices
{
    public static $_status = [
        0 => "未执行",
        1 => "成功",
        2 => "失败",
        3 => '删除'
    ];

    /**
     * QueueAuxiliaryServices constructor.
     * @param QueueAuxiliaryDao $dao
     */
    public function __construct(QueueAuxiliaryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 添加队列数据
     * @param $queueId
     * @param $ids
     * @param $data
     * @param $type
     * @param $rediskey
     * @return bool
     */
    public function saveQueueOrderData($queueId, $ids, $data, $type, $rediskey)
    {
        if (!$ids) {
            throw new ValidateException('缺少数据');
        }
        $data_all = [];
        $save = ['binding_id' => $queueId, 'type' => $rediskey, 'status' => 0, 'add_time' => time()];
        if ($type == 7) {//批量发货读取数据表格
            foreach ($data as $k => $v) {
                if ($v[0] && $v[1] && $v[2] && $v[3] && $v[4] && in_array($v[0], $ids)) {
                    $save['relation_id'] = $v[0];
                    $save['other'] = json_encode(['id' => $v[0], 'order_id' => $v[1], 'delivery_name' => $v[2], 'delivery_code' => $v[3], 'delivery_id' => $v[4], 'delivery_status' => 0, 'wx_message' => 0, 'phone_message' => 0, 'error_info' => ""]);
                    $data_all[] = $save;
                }
            }
        } else {
            foreach ($ids as $k => $v) {
                $save['relation_id'] = $v;
                $save['other'] = json_encode(['id' => $v, 'delivery_status' => 0, 'wx_message' => 0, 'phone_message' => 0, 'error_info' => ""]);
                $data_all[] = $save;
            }
        }
        if ($data_all) {
            if (!$this->dao->saveAll($data_all)) {
                throw new ValidateException('添加队列数据失败');
            }
        }
        return true;
    }

    /**
     * 获取发货缓存数据列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderExpreList(array $where, int $limit = 0)
    {
        if (!$where) throw new ValidateException("获取发货缓存数据条件缺失");
        if ($limit) {
            [$page] = $this->getPageValue();
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        return $this->dao->getOrderExpreList($where, $page, $limit);
    }

    /**
     * 修改订单缓存数据
     * @param array $where
     * @param array $data
     * @return mixed
     */
    public function updateOrderStatus(array $where, array $data)
    {
        if (!$where) throw new ValidateException("数据条件缺失");
        return $this->dao->update($where, $data);
    }

    /**
     * 根据条件统计缓存数据
     * @param array $where
     * @return int
     */
    public function getCountOrder(array $where)
    {
        return $this->dao->count($where);
    }

    /**
     * 查看订单缓存数据的各种状态
     * @param $orderId
     * @param $queueId
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderOtherSataus($orderId, $queueId, $cacheType)
    {
        if (!$orderId || !$queueId) return false;
        $where['relation_id'] = $orderId;
        $where['binding_id'] = $queueId;
        $where['type'] = $cacheType;
        $where['status'] = 1;
        $getOne = $this->dao->getOrderCacheOne($where);
        if ($getOne) return true;
        return false;
    }

    /**
     * 获取发货记录
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deliveryLogList(array $where = [])
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->deliveryLogList($where, $page, $limit);
        $list = $this->doBatchDeliveryData($list);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 下载发货记录
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getExportData(array $where, int $limit = 0)
    {
        if (!$where) throw new ValidateException("数据条件缺失");
        $list = $this->getOrderExpreList($where, $limit);
        $list = $this->doBatchDeliveryData($list);
        return $list;
    }

    /**
     * 批量发货记录数据
     * @param $list
     * @return mixed
     */
    public function doBatchDeliveryData($list)
    {
        if ($list) {
            /** @var StoreOrderServices $storeOrderService */
            $storeOrderService = app()->make(StoreOrderServices::class);
            /** @var QueueServices $queueService */
            $queueService = app()->make(QueueServices::class);
            $type = array_flip($queueService->queue_redis_key);

            foreach ($list as &$v) {
                $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['status_cn'] = self::$_status[$v['status']];
                $v['error'] = $v['status'] == 1 ? '无' : '队列异常';
                $orderInfo = $storeOrderService->getOne(['id' => $v['relation_id']]);
                $v['order_id'] = $orderInfo['order_id'];
                if (in_array($type[$v['type']], [7, 8, 9])) {
                    $v['delivery_name'] = $orderInfo ? $orderInfo['delivery_name'] : "";
                    $v['delivery_id'] = $orderInfo ? $orderInfo['delivery_id'] : "";
                }
                if ($type[$v['type']] == 10) {
                    $v['fictitious_content'] = $orderInfo ? $orderInfo['fictitious_content'] : "";
                }
            }
        }
        return $list;
    }

    /**
     * 获取某个队列的数据缓存
     * @param $bindingId
     * @param $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCacheOidList($bindingId, $type)
    {
        return $this->dao->getCacheOidList($bindingId, $type);
    }
}
