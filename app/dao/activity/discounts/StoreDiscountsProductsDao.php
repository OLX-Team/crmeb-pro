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

namespace app\dao\activity\discounts;

use app\dao\BaseDao;
use app\model\activity\discounts\StoreDiscountsProducts;

/**
 * 优惠套餐商品
 * Class StoreDiscountsProductsDao
 * @package app\dao\activity\discounts
 */
class StoreDiscountsProductsDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreDiscountsProducts::class;
    }

    /**
     * 获取商品列表
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where)
    {
        return $this->search($where)->select()->toArray();
    }

    /**
     * 获取套餐商品
     * @param int $id
     * @param string $field
     * @param array $with
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiscountProductInfo(int $id, string $field, array $with = [])
    {
        return $this->getModel()->where('id', $id)->when($with, function ($query) use ($with) {
            $query->with($with);
        })->field($field)->find();
    }
}