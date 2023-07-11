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


namespace app\model\activity\discounts;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 优惠套餐
 * Class StoreDiscounts
 * @package app\model\activity\discounts
 */
class StoreDiscounts extends BaseModel
{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_discounts';

    use ModelTrait;

    /**
     * 套餐商品关联
     * @return \think\model\relation\HasMany
     */
    public function products()
    {
        return $this->hasMany(StoreDiscountsProducts::class, 'discount_id', 'id');
    }

    /**
     * 类型搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value != '') $query->where('type', $value);
    }

    /**
     * 名称搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchTitleAttr($query, $value)
    {
        if ($value != '') $query->where('title', 'like', '%' . $value . '%');
    }

    /**
     * 状态搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') $query->where('status', $value);
    }

    /**
     * 是否删除搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '') $query->where('is_del', $value);
    }

    /**
     * 商品id搜索器
     * @param Model $query
     * @param $value
     */
    public function searchProductIdsAttr($query, $value)
    {
        if ($value != '') $query->whereFindInSet('product_ids', $value);
    }
}