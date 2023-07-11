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


use app\dao\other\queue\QueueDao;
use app\jobs\BatchHandleJob;
use app\services\BaseServices;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\order\StoreCartServices;
use app\services\order\StoreOrderDeliveryServices;
use app\services\order\StoreOrderServices;
use app\services\product\category\StoreCategoryServices;
use app\services\product\product\StoreProductCateServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductRuleServices;
use app\services\user\group\UserGroupServices;
use app\services\user\label\UserLabelRelationServices;
use app\services\user\label\UserLabelServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use think\exception\ValidateException;
use think\facade\Log;


/**
 * 队列
 * Class QueueServices
 * @package app\services\other\queue
 */
class QueueServices extends BaseServices
{
    /**
     * 任务类型名称
     * @var string[]
     */
    public $queue_type_name = [
        1 => "批量发放用户优惠券",
        2 => "批量设置用户分组",
        3 => "批量设置用户标签",
        4 => "批量下架商品",
        5 => "批量删除商品规格",
        6 => "批量删除订单",
        7 => "批量手动发货",
        8 => "批量打印电子面单",
        9 => "批量配送",
        10 => "批量虚拟发货",
    ];

    /**
     * 任务redis缓存key
     * @var string[]
     */
    public $queue_redis_key = [
        1 => "DrivingSendCoupon-ADMIN",
        2 => "DrivingUserGroup-ADMIN",
        3 => "DrivingUserLabel-ADMIN",
        4 => "DrivingProductUnshow-ADMIN",
        5 => "DrivingProductRule-ADMIN",
        6 => "DrivingOrderDel-ADMIN",
        7 => 3,
        8 => 4,
        9 => 5,
        10 => 6,
    ];

    /**
     * 状态
     * @var string[]
     */
    protected $status_name = [
        0 => '未处理',
        1 => '正在处理',
        2 => '完成',
        3 => '失败'
    ];

    public function __construct(QueueDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取任务列表
     * @param array $where
     */
    public function getList(array $where = [])
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $page, $limit);
        if ($list) {
            foreach ($list as &$v) {
                $v['finish_time'] = $v['finish_time'] ? date('Y-m-d H:i:s', $v['finish_time']) : "";
                $v['first_time'] = $v['first_time'] ? date('Y-m-d H:i:s', $v['first_time']) : "";
                $v['again_time'] = $v['again_time'] ? date('Y-m-d H:i:s', $v['again_time']) : "";
                $v['status_cn'] = $this->status_name[$v['status']] ?? '';
                $v['is_show_log'] = false;
                if (in_array($v['type'], [7, 8, 9, 10])) {
                    $v['is_show_log'] = true;
                    $v['is_error_button'] = $v['status'] == 2 ? true : false;
                }
                $v['type_cn'] = $this->queue_type_name[$v['type']] ?? '';
                $v['cache_type'] = $this->queue_redis_key[$v['type']] ?? 0;
                $v['success_num'] = bcsub($v['total_num'], $v['surplus_num'], 0);
                //是否显示停止按钮
                $v['is_stop_button'] = $v['status'] == 1 ? true : false;
                $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 将要执行的任务数据存入表中
     * @param array $where
     * @param string $field
     * @param array $data
     * @param int $type
     * @return mixed
     */
    public function setQueueData(array $where = [], $field = "*", array $data = [], int $type = 1, $other = false)
    {
        if (!$type) throw new ValidateException('缺少执行任务类型');
        $queue_redis_keys = $this->queue_redis_key;
        $redisKey = $queue_redis_keys[$type] ?? '';
        $queue_type_name = $this->queue_type_name;
        $queueName = $queue_type_name[$type] ?? '';
        if (!$redisKey || !$queueName) {
            throw new ValidateException('缺少队列缓存KEY，或者不存在此类型队列');
        }
        //检查同类型其他任务
        $this->checkTypeQueue($redisKey);

        $source = 'admin';
        if (in_array($type, [1, 2, 3, 4, 5, 6])) {
            $queueDataNum = $this->setRedisData($redisKey, $type, $data, $where, $field);
            if (!$queueDataNum) {
                throw new ValidateException('需要执行的批量数据为空');
            }
            if (!$id = $this->dao->addQueueList($queueName, $queueDataNum, $type, $redisKey, $source)) {
                throw new ValidateException('添加队列失败');
            }
        } else {
            if ($type == 7) {
                $ids = array_column($data, 0);
            } else {
                $ids = $data;
            }
            /** @var StoreOrderServices $orderService */
            $orderService = app()->make(StoreOrderServices::class);
            $oids = $orderService->getOrderDumpData($where, $ids, $field);
            $order_ids = [];
            if ($oids) {
                //过滤拼团未完成订单
                foreach ($oids as $order) {
                    if (isset($order['pinkStatus']) && $order['pinkStatus'] != 2) {
                        continue;
                    }
                    $order_ids[] = $order['id'];
                }
            }
            if (!$order_ids) {
                throw new ValidateException('暂无需要发货订单');
            }
            if (!$id = $this->dao->addQueueList($queueName, count($ids), $type, $redisKey, $source)) {
                throw new ValidateException('添加队列失败');
            }
            /** @var QueueAuxiliaryServices $auxiliaryService */
            $auxiliaryService = app()->make(QueueAuxiliaryServices::class);
            $auxiliaryService->saveQueueOrderData($id, $order_ids, $data, $type, $redisKey);
        }

        return $id;
    }

    /**
     * 队列数据放入redis集合
     * @param $redisKey
     * @param $type
     * @param array $dataIds
     * @param array $where
     * @param string $filed
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setRedisData($redisKey, $type, array $dataIds, array $where, $filed = "*")
    {
        if (!$redisKey || !$type) return 0;
        /** @var CacheService $redis */
        $redis = app()->make(CacheService::class);
        if ($dataIds) {
            foreach ($dataIds as $v) {
                $redis->sAdd($redisKey, $v);
            }
        } else {
            if ($where) {
                foreach ($where as $k => $v) {
                    if (!$v) unset($where[$k]);
                }
            }
            switch ($type) {
                case 1://批量发放优惠券
                case 2://批量设置用户分组
                case 3://批量设置用户标签
                    /** @var UserServices $userService */
                    $userService = app()->make(UserServices::class);
                    $dataInfo = $userService->getUserInfoList($where, $filed);
                    if ($dataInfo) {
                        foreach ($dataInfo as $k => $v) {
                            $redis->sAdd($redisKey, $v['uid']);
                        }
                    }
                    break;
                case 4://批量上下架商品
                    $cateIds = [];
                    if (isset($where['cate_id']) && $where['cate_id']) {
                        /** @var StoreCategoryServices $storeCategory */
                        $storeCategory = app()->make(StoreCategoryServices::class);
                        $cateIds = $storeCategory->getColumn(['pid' => $where['cate_id']], 'id');
                    }
                    if ($cateIds) {
                        $cateIds[] = $where['cate_id'];
                        $where['cate_id'] = $cateIds;
                    }
                    /** @var StoreProductServices $productService */
                    $productService = app()->make(StoreProductServices::class);
                    $dataInfo = $productService->getProductListByWhere($where, $filed);
                    if ($dataInfo) {
                        foreach ($dataInfo as $k => $v) {
                            $redis->sAdd($redisKey, $v['id']);
                        }
                    }
                    break;
                case 5://批量删除商品规格
                    /** @var StoreProductRuleServices $productRuleService */
                    $productRuleService = app()->make(StoreProductRuleServices::class);
                    $dataInfo = $productRuleService->getProductRuleList($where, $filed);
                    if ($dataInfo) {
                        foreach ($dataInfo as $k => $v) {
                            $redis->sAdd($redisKey, $v['id']);
                        }
                    }
                    break;
                case 6://批量删除用户已删除订单
                    /** @var StoreOrderServices $orderService */
                    $orderService = app()->make(StoreOrderServices::class);
                    $dataInfo = $orderService->getOrderListByWhere($where, $filed);
                    if ($dataInfo) {
                        foreach ($dataInfo as $k => $v) {
                            $redis->sAdd($redisKey, $v['id']);
                        }
                    }
                    break;
                default:
                    return 0;
                    break;
            }

        }
        return $redis->sCard($redisKey);
    }

    /**
     * 获取队列redis中存的数据集合
     * @param string $redisKey
     * @param array $queueInfo
     * @return array
     */
    public function getQueueRedisdata($queueInfo, string $redisKey = '')
    {
        if (!$queueInfo) return [$redisKey, []];
        if (!$redisKey) {
            $redisKey = $queueInfo['execute_key'] ?? '';
        }
        if (!$redisKey) {
            return [$redisKey, []];
        }
        /** @var CacheService $redis */
        $redis = app()->make(CacheService::class);
        return [$redisKey, $redis->sMembers($redisKey)];
    }

    /**
     * 批量发送优惠券
     * @param $coupon
     * @param $type
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendCoupon($coupon, $type)
    {
        if (!$type || !$coupon) return false;
        $queueInfo = $this->dao->getQueueOne(['type' => $type, 'status' => 0]);
        if (!$queueInfo) {
            return false;
        }
        //把队列需要执行的入参数据存起来，以便队列执行失败后接着执行，同时队列状态改为正在执行状态。
        $this->dao->setQueueDoing($coupon, $queueInfo['id']);

        [$redisKey, $uids] = $this->getQueueRedisdata($queueInfo);
        if ($uids) {
            $chunkUids = array_chunk($uids, 100, true);
            /** @var StoreCouponIssueServices $issueService */
            $issueService = app()->make(StoreCouponIssueServices::class);
            foreach ($chunkUids as $v) {
                $issueService->setCoupon($coupon, $v, $redisKey, $queueInfo);
            }
        }
        //发完后将队列置为完成
        $this->setQueueSuccess($queueInfo['id'], $queueInfo['type']);
        return true;
    }

    /**
     * 批量设置用户分组
     * @param $groupId
     * @param $type
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setUserGroup($groupId, $type)
    {
        if (!$groupId || !$type) return false;
        $queueInfo = $this->dao->getQueueOne(['type' => $type, 'status' => 0]);
        if (!$queueInfo) {
            return false;
        }
        /** @var UserGroupServices $userGroup */
        $userGroup = app()->make(UserGroupServices::class);
        if (!$userGroup->getGroup($groupId)) {
            return false;
        }
        //把队列需要执行的入参数据存起来，以便队列执行失败后接着执行，同时队列状态改为正在执行状态。
        $this->dao->setQueueDoing($groupId, $queueInfo['id']);

        [$redisKey, $uids] = $this->getQueueRedisdata($queueInfo);
        if ($uids) {
            $chunkUids = array_chunk($uids, 1000, true);
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            foreach ($chunkUids as $v) {
                //执行分组
                if (!$userServices->setUserGroup($v, $groupId)) {
                    $this->setQueueFail($queueInfo['id'], $redisKey);
                } else {
                    $this->doSuccessSremRedis($v, $redisKey, $type);
                }
            }
        }
        //发完后将队列置为完成
        $this->setQueueSuccess($queueInfo['id'], $queueInfo['type']);
        return true;
    }

    /**
     * 批量设置用户标签
     * @param $labelId
     * @param $type
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setUserLabel($labelId, $type, $other = [])
    {
        if (!$labelId || !$type) return false;
        $queueInfo = $this->dao->getQueueOne(['type' => $type, 'status' => 0]);
        if (!$queueInfo) {
            return false;
        }
        /** @var UserLabelServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelServices::class);
        $count = $userLabelServices->getCount([['id', 'IN', $labelId]]);
        if ($count != count($labelId)) {
            return false;
        }
        //把队列需要执行的入参数据存起来，以便队列执行失败后接着执行，同时队列状态改为正在执行状态。
        $this->dao->setQueueDoing($labelId, $queueInfo['id']);

        [$redisKey, $uids] = $this->getQueueRedisdata($queueInfo);
        if ($uids) {
            $chunkUids = array_chunk($uids, 1000, true);
            /** @var UserLabelRelationServices $services */
            $services = app()->make(UserLabelRelationServices::class);
            $store_id = $other['store_id'] ?? 0;
            foreach ($chunkUids as $v) {
                if (!$services->setUserLable($v, $labelId, $store_id)) {
                    $this->setQueueFail($queueInfo['id'], $redisKey);
                } else {
                    $this->doSuccessSremRedis($v, $redisKey, $type);
                }
            }
        }
        //发完后将队列置为完成
        $this->setQueueSuccess($queueInfo['id'], $queueInfo['type']);
        return true;
    }

    /**
     * 商品批量上下架
     * @param string $upORdown
     * @param $type
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setProductShow($upORdown = "up", $type = 1)
    {
        if (!$type) return false;
        $queueInfo = $this->dao->getQueueOne(['type' => $type, 'status' => 0]);
        if (!$queueInfo) {
            return false;
        }
        //把队列需要执行的入参数据存起来，以便队列执行失败后接着执行，同时队列状态改为正在执行状态。
        $this->dao->setQueueDoing($upORdown, $queueInfo['id']);

        [$redisKey, $pids] = $this->getQueueRedisdata($queueInfo);
        if ($pids) {
            $chunkPids = array_chunk($pids, 1000, true);
            $isShow = 0;
            if ($upORdown == 'up') $isShow = 1;
            /** @var StoreProductServices $storeproductServices */
            $storeproductServices = app()->make(StoreProductServices::class);
            /** @var StoreProductCateServices $storeProductCateServices */
            $storeProductCateServices = app()->make(StoreProductCateServices::class);
            /** @var StoreCartServices $cartService */
            $cartService = app()->make(StoreCartServices::class);
            $update = ['is_show' => $isShow];
            if ($isShow) {//手动上架 清空定时下架状态
                $update['auto_off_time'] = 0;
            }
            foreach ($chunkPids as $v) {
                $res = $storeproductServices->batchUpdate($v, $update);

                if ($isShow == 0) {
                    $storeProductCateServices->batchUpdate($v, ['status' => $isShow], 'product_id');
                    //购物车
                    $cartService->batchUpdate($v, ['status' => 1], 'product_id');
                }
                //下架检测是否有参与活动商品
                try {
                    $is_activity = $storeproductServices->checkActivity($v);
                } catch (\Throwable $e) {
                    $is_activity = false;
                }
                if ($isShow == 0 || $is_activity) {
                    //改变购物车中状态
                    $storeProductCateServices->batchUpdate($v, ['status' => $isShow], 'product_id');
                }
                if (!$res) {
                    $this->setQueueFail($queueInfo['id'], $redisKey);
                } else {
                    $this->doSuccessSremRedis($v, $redisKey, $type);
                }
            }
        }
        //发完后将队列置为完成
        $this->setQueueSuccess($queueInfo['id'], $queueInfo['type']);
        return true;
    }

    /**
     * 批量队列删除商品规格
     * @param $type
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delProductRule($type)
    {
        if (!$type) return false;
        $queueInfo = $this->dao->getQueueOne(['type' => $type, 'status' => 0]);
        if (!$queueInfo) {
            return false;
        }
        //把队列需要执行的入参数据存起来，以便队列执行失败后接着执行，同时队列状态改为正在执行状态。
        $this->dao->setQueueDoing('', $queueInfo['id']);

        [$redisKey, $pids] = $this->getQueueRedisdata($queueInfo);
        if ($pids) {
            $chunkPids = array_chunk($pids, 1000, true);
            /** @var StoreProductRuleServices $storeProductRuleservices */
            $storeProductRuleservices = app()->make(StoreProductRuleServices::class);
            foreach ($chunkPids as $v) {
                $res = $storeProductRuleservices->del(implode(',', $v));
                if ($res) {
                    $this->doSuccessSremRedis($v, $redisKey, $queueInfo['type']);
                } else {
                    $this->addQueueFail($queueInfo['id'], $redisKey);
                }
            }
        }
        //发完后将队列置为完成
        $this->setQueueSuccess($queueInfo['id'], $queueInfo['type']);
        return true;
    }

    /**
     * 批量队列删除订单
     * @param $type
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delOrder($type)
    {
        if (!$type) return false;
        $queueInfo = $this->dao->getQueueOne(['type' => $type, 'status' => 0]);
        if (!$queueInfo) {
            return false;
        }
        //把队列需要执行的入参数据存起来，以便队列执行失败后接着执行，同时队列状态改为正在执行状态。
        $this->dao->setQueueDoing('', $queueInfo['id']);

        [$redisKey, $pids] = $this->getQueueRedisdata($queueInfo);
        if ($pids) {
            $chunkPids = array_chunk($pids, 1000, true);
            /** @var StoreOrderServices $storeOrderServices */
            $storeOrderServices = app()->make(StoreOrderServices::class);
            foreach ($chunkPids as $v) {
                $res = $storeOrderServices->batchUpdateOrder($v, ['is_system_del' => 1]);
                if ($res) {
                    $this->doSuccessSremRedis($v, $redisKey, $type);
                } else {
                    $this->setQueueFail($queueInfo['id'], $redisKey);
                }
            }
        }
        //发完后将队列置为完成
        $this->setQueueSuccess($queueInfo['id'], $queueInfo['type']);
        return true;
    }

    /**
     * 队列批量发货
     * @param $oid
     * @param array $deliveryData
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderDelivery($oid, array $deliveryData)
    {
        if (!$oid) return false;
        if (!isset($deliveryData['queueType'])) {
            return false;
        }
        /** @var QueueAuxiliaryServices $auxiliaryService */
        $auxiliaryService = app()->make(QueueAuxiliaryServices::class);
        //看是否能查到任务数据
        $auxiliaryInfo = $auxiliaryService->getOrderCacheOne(['binding_id' => $deliveryData['queueId'], 'relation_id' => $oid, 'type' => $deliveryData['cacheType']]);
        if (!$auxiliaryInfo || !$auxiliaryInfo['other']) {
            return false;
        }
        $deliveryInfo = json_decode($auxiliaryInfo['other'], true);
        if ($deliveryData['queueType'] == 7) {
            if (!$deliveryInfo['delivery_name'] || !$deliveryInfo['delivery_code'] || !$deliveryInfo['delivery_id']) {
                return false;
            }
            $deliveryData['express_record_type'] = 1;
            $deliveryData['delivery_name'] = $deliveryInfo['delivery_name'];
            $deliveryData['delivery_id'] = $deliveryInfo['delivery_id'];
            $deliveryData['delivery_code'] = $deliveryInfo['delivery_code'];
        }

        try {
            /** @var StoreOrderDeliveryServices $storeOrderDelivery */
            $storeOrderDelivery = app()->make(StoreOrderDeliveryServices::class);
            //发货
            $storeOrderDelivery->delivery($oid, $deliveryData);
        } catch (\Throwable $e) {
            Log::error('队列发货失败发货，order_id：' . $oid . ',原因：' . $e->getMessage());
        }

        //更改队列子集数据
        $this->doSuccessSremRedis(['order_id' => $oid], $deliveryData['queueId'], $deliveryData['queueType'], ['phone_message' => 1, 'status' => 1]);
        //队列置为完成
        return $this->setQueueSuccess($deliveryData['queueId'], $deliveryData['queueType']);
    }

    /**
     * 添加任务前校验同类型任务状态
     * @param $type
     * @param array $queueInfo
     * @param false $is_again
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkTypeQueue($type, array $queueInfo = [], bool $is_again = false)
    {
        if (!$type) return false;
        if (!$queueInfo) {
            $queueInfo = $this->dao->getQueueOne(['type' => $type, 'status' => [0, 1]]);
        }
        if (!$queueInfo) {
            return false;
        }
        $num = 0;
        if (in_array($type, [7, 8, 9, 10])) {
            /** @var QueueAuxiliaryServices $auxiliaryService */
            $auxiliaryService = app()->make(QueueAuxiliaryServices::class);
            $num = $auxiliaryService->count(['binding_id' => $queueInfo['id'], 'status' => 0]);
        } else {
            if ($queueInfo['execute_key']) {
                /** @var CacheService $redis */
                $redis = app()->make(CacheService::class);
                $num = $redis->sCard($queueInfo['execute_key']);
            }
        }
        if ($num) {
            if (!$is_again) {
                if ($queueInfo['status'] == 0) {
                    throw new AdminException('上次批量任务尚未执行，请前往任务列表手动执行');
                }
                if ($queueInfo['status'] == 1) {
                    throw new AdminException('有正在执行中的任务，请耐心等待，若长时间无反应，前往任务列表修复异常数据，再手动执行');
                }
            }
        } else {
            $this->delWrongQueue(0, $type);
            return false;
        }
        return true;
    }

    /**
     * 修复异常任务
     * @param $queueInfo
     * @return bool|mixed
     */
    public function repairWrongQueue($queueInfo)
    {
        if (!$queueInfo) throw new AdminException('任务不存在');
        try {
            switch ($queueInfo['type']) {
                case 1://批量发放优惠券
                case 2://批量设置用户分组
                case 3://批量设置用户标签
                case 4://批量上下架商品
                case 5://批量删除商品规格
                case 6://批量删除用户已删除订单
                    if (!$queueInfo['execute_key']) {
                        throw new AdminException('缓存key缺失，请清除数据');
                    }
                    /** @var CacheService $redis */
                    $redis = app()->make(CacheService::class);
                    $cacheNum = $redis->sCard($queueInfo['execute_key']);
                    if ($cacheNum != $queueInfo['surplus_num']) {
                        return $this->dao->update(['id' => $queueInfo['id']], ['surplus_num' => $cacheNum]);
                    }
                    break;
                case 7://手动发货
                case 8://电子面单发货
                case 9://批量配送
                case 10://批量虚拟发货
                    /** @var QueueAuxiliaryServices $auxiliaryService */
                    $auxiliaryService = app()->make(QueueAuxiliaryServices::class);
                    $cacheType = $this->queue_redis_key[$queueInfo['type']] ?? '';

                    $cacheFailAndNoNum = $auxiliaryService->getCountOrder(['binding_id' => $queueInfo['id'], 'type' => $cacheType, 'status' => [0, 2]]);
                    $cacheTotalNum = $auxiliaryService->getCountOrder(['binding_id' => $queueInfo['id'], 'type' => $cacheType, 'status' => [0, 1, 2]]);
                    //如果任务已经执行完毕，但是记录却存在未执行数据，要进行修复，让其重新执行
                    if ($cacheFailAndNoNum && $queueInfo['status'] == 2) return $this->dao->update(['id' => $queueInfo['id']], ['status' => 3, 'surplus_num' => $cacheFailAndNoNum, 'total_num' => $cacheTotalNum]);
                    //如果执行失败，记录全部执行成功，那么进行修复
                    if (!$cacheFailAndNoNum && $queueInfo['status'] != 2) return $this->dao->update(['id' => $queueInfo['id']], ['status' => 2, 'surplus_num' => 0, 'total_num' => $cacheTotalNum]);
            }
            return true;
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * 队列再次执行
     * @param $queueId
     * @param $type
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function againDoQueue($queueId, $type)
    {
        $queueInfo = $this->getQueueOne(['id' => $queueId, 'type' => $type]);
        if (!$queueInfo) {
            throw new AdminException('队列任务不存在');
        }
        if (!$queueInfo['queue_in_value']) {
            throw new AdminException('队列关键数据缺失，请清除此任务及异常数据');
        }
        if ($queueInfo['status'] == 2) {
            throw new AdminException('队列已完成');
        }
        if ($queueInfo['status'] == 3) {
            throw new AdminException('队列异常，请清除队列重新加入');
        }
        if ($queueInfo['status'] == 4) {
            throw new AdminException('队列已删除');
        }
        //检测当前队列
        if (!$this->checkTypeQueue($type, $queueInfo, true)) {
            throw new AdminException('任务已清除，无需再次执行');
        }
        //先进行数据修复
        $this->repairWrongQueue($queueInfo);

        if (in_array($type, [7, 8, 9, 10])) {
            $queueInValue = json_decode($queueInfo['queue_in_value'], true);
            /** @var StoreOrderServices $storeOrderService */
            $storeOrderService = app()->make(StoreOrderServices::class);
            $storeOrderService->adminQueueOrderDo($queueInValue, true);
        } else {
            $queueInValue = $queueInfo['queue_in_value'];
            if ($type == 1) {
                $queueInValue = json_decode($queueInfo['queue_in_value'], true);
            }
            //加入队列
            BatchHandleJob::dispatch([$queueInValue, $type]);
        }

        return true;
    }

    /**
     * 任务执行失败，修改队列状态
     * @param $queueId
     * @param string $redisKey
     * @return mixed
     */
    public function setQueueFail($queueId, $redisKey = '')
    {
        if ($redisKey) {
            /** @var CacheService $cacheService */
            $cacheService = app()->make(CacheService::class);
            $surplusNum = $cacheService->sCard($redisKey);
        } else {
            /** @var QueueAuxiliaryServices $auxiliaryService */
            $auxiliaryService = app()->make(QueueAuxiliaryServices::class);
            $surplusNum = $auxiliaryService->count(['binding_id' => $queueId, 'status' => 0]);
        }
        return $this->dao->update(['id' => $queueId], ['status' => 3, 'surplus_num' => $surplusNum]);
    }

    /**
     * 将执行成功数据移除redis集合
     * @param array $data
     * @param $redisKey
     * @return bool
     */
    public function doSuccessSremRedis(array $data, $redisKey, $type, array $otherData = [])
    {
        if (!$data || !$redisKey || !$type) return true;
        if (in_array($type, [7, 8, 9, 10])) {
            $where['relation_id'] = $data['order_id'];
            $where['binding_id'] = $redisKey;
            /** @var QueueAuxiliaryServices $auxiliaryService */
            $auxiliaryService = app()->make(QueueAuxiliaryServices::class);
            $getOne = $auxiliaryService->getOrderCacheOne($where);
            if (!$getOne) return false;
            $other = json_decode($getOne['other'], true);
            if (isset($otherData['delivery_status'])) $other['delivery_status'] = $otherData['delivery_status'];
            if (isset($otherData['wx_message'])) $other['wx_message'] = $otherData['wx_message'];
            if (isset($otherData['phone_message'])) $other['phone_message'] = $otherData['phone_message'];
            if (isset($otherData['error_info'])) $other['error_info'] = $otherData['error_info'];
            $updateData['status'] = isset($otherData['status']) ? $otherData['status'] : 0;
            $updateData['other'] = json_encode($other);
            $updateData['update_time'] = time();
            $auxiliaryService->updateOrderStatus($where, $updateData);
        } else {//在redis缓存集合的从集合删除
            /** @var CacheService $redis */
            $redis = app()->make(CacheService::class);
            foreach ($data as $k => $v) {
                $redis->sRem($redisKey, $v);
            }
        }
        return true;
    }

    /**
     * 任务执行成功
     * @param $queueId
     * @param $type
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setQueueSuccess($queueId, $type)
    {
        if (!$queueId || !$type) return false;
        $queueInfo = $this->dao->get($queueId);
        if (!$queueInfo) return false;
        $res = true;
        if (in_array($type, [7, 8, 9, 10])) {
            $res = false;
            if ($queueInfo['surplus_num'] > 0) {
                $this->dao->bcDec($queueId, 'surplus_num', 1);
            }
            //看是否全部执行成功
            $queueInfo = $this->dao->get($queueId);
            if ($queueInfo['surplus_num'] == 0) {
                $res = true;
            }
        }
        if ($res) {
            $update = [
                'status' => 2,
                'finish_time' => time(),
                'surplus_num' => 0
            ];
            return $this->dao->update(['id' => $queueId], $update);
        }
        return true;

    }

    /**
     * 清除异常队列
     * @param $queueId
     * @param $type
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delWrongQueue($queueId, $type, $is_del = true)
    {
        if (!$type) return false;
        if ($queueId) {
            $queueInfo = $this->dao->getQueueOne(['id' => $queueId, 'type' => $type]);
        } else {
            $queueInfo = $this->dao->getQueueOne(['type' => $type]);
        }
        if (!$queueInfo) {
            return true;
        }
        try {
            $data = ['status' => 3];
            if ($is_del) {
                if (in_array($type, [7, 8, 9, 10])) {
                    /** @var QueueAuxiliaryServices $auxiliaryService */
                    $auxiliaryService = app()->make(QueueAuxiliaryServices::class);
                    $auxiliaryService->batchUpdate(['binding_id' => $queueInfo['id']], ['status' => 3]);
                } else {
                    if ($queueInfo['execute_key']) {
                        /** @var CacheService $redis */
                        $redis = app()->make(CacheService::class);
                        $redis->del($queueInfo['execute_key']);
                    }
                }
                $data = ['is_del' => 1, 'status' => 4];
            }
            $this->dao->update(['id' => $queueInfo['id']], $data);
        } catch (\Throwable $e) {
            Log::error('清除异常队列失败，原因' . $e->getMessage());
        }
        return true;

    }
}
