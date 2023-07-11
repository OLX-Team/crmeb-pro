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

namespace app\dao\other\queue;


use app\dao\BaseDao;
use app\model\other\queue\QueueAuxiliary;

/**
 * 队列辅助
 * Class QueueAuxiliaryDao
 * @package app\dao\other\queue
 */
class QueueAuxiliaryDao extends BaseDao
{

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return QueueAuxiliary::class;
    }

    /**
     * 添加订单缓存记录
     * @param array $data
     * @return int|string
     */
    public function saveOrderCacheLog(array $data)
    {
        return $this->getModel()->insertGetId($data);
    }

    /**
     * 获取发货缓存数据列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderExpreList(array $where, int $page = 0, int $limit = 0)
    {
        return $this->search($where)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order('add_time asc')->select()->toArray();
    }

    /**
     * 查询单条订单缓存数据
     * @param array $where
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderCacheOne(array $where)
    {
        return $this->search($where)->find();
    }

    /**
     * 获取发货记录
     * @param array $where
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deliveryLogList(array $where, int $page = 0, int $limit = 0, string $order = '')
    {
        foreach ($where as $k => $v) {
            if ($v == "") unset($where[$k]);
        }
        return $this->search($where)
            ->order(($order ? $order . ' ,' : '') . 'add_time desc')
            ->page($page, $limit)->select()->toArray();
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
        return $this->search(['binding_id' => $bindingId, 'type' => $type])->select()->toArray();
    }
}
