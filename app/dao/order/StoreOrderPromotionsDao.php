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

namespace app\dao\order;


use app\dao\BaseDao;
use app\model\order\StoreOrderPromotions;

/**
 * 订单详情
 * Class StoreOrderPtomotionsDao
 * @package app\dao\order
 * @method saveAll(array $data)
 */
class StoreOrderPromotionsDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreOrderPromotions::class;
    }


    /**
     * 获取购物车详情列表
     * @param array $where
     * @param string $field
     * @param array $with
     * @param string $group
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPromotionsDetailList(array $where, string $field = '*', array $with = [], string $group = '')
    {
        return $this->search($where)->field($field)->when($with, function($query) use($with) {
            $query->with($with);
        })->when($group, function($query) use($group) {
            $query->group($group);
        })->select()->toArray();
    }

}
