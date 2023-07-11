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

namespace app\model\activity\promotions;


use app\model\activity\coupon\StoreCouponIssue;
use app\model\product\product\StoreProduct;
use app\model\product\sku\StoreProductAttrValue;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 优惠活动辅助表
 */
class StorePromotionsAuxiliary extends BaseModel
{
    use ModelTrait;

    /**
     * @var string
     */
    protected $key = 'id';

    /**
     * @var string
     */
    protected $name = 'store_promotions_auxiliary';

    /**
     * 商品规格
     * @param $value
     * @return array|mixed
     */
    protected function getUniqueAttr($value, $data)
    {
        if ($value) {
            return is_string($value) && (!isset($data['type']) || $data['type'] != 3) ? explode(',', $value) : $value;
        }
        return [];
    }

    /**
     * 关联优惠券
     * @return \think\model\relation\HasOne
     */
    public function coupon()
    {
        return $this->hasOne(StoreCouponIssue::class, 'id', 'coupon_id');
    }

    /**
     * 关联商品
     * @return \think\model\relation\HasOne
     */
    public function productInfo()
    {
        return $this->hasOne(StoreProduct::class, 'id', 'product_id');
    }

    /**
     * 赠送sku一对一
     * @return \think\model\relation\HasMany
     */
    public function giveAttrValue()
    {
        return $this->hasOne(StoreProductAttrValue::class, 'unique', 'unique')->where('type', 0);
    }

    /**
     * sku一对多
     * @return \think\model\relation\HasMany
     */
    public function attrValue()
    {
        return $this->hasMany(StoreProductAttrValue::class, 'product_id', 'product_id')->where('type', 0);
    }


    /**
     * type搜索器
     * @param Model $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value) $query->where('type', $value);
    }

    /**
     * promotions_id搜索器
     * @param $query
     * @param $value
     */
    public function searchPromotionsIdAttr($query, $value)
    {
        if (is_array($value)) {
            if ($value) $query->whereIn('promotions_id', $value);
        } else {
            if ($value !== '') $query->where('promotions_id', $value);
        }
    }

    /**
     * coupon_id搜索器
     * @param $query
     * @param $value
     */
    public function searchCouponIdAttr($query, $value)
    {
        if (is_array($value)) {
            if ($value) $query->whereIn('coupon_id', $value);
        } else {
            if ($value !== '') $query->where('coupon_id', $value);
        }
    }


    /**
     * product_id搜索器
     * @param $query
     * @param $value
     */
    public function searchProductIdAttr($query, $value)
    {
        if (is_array($value)) {
            if ($value) $query->whereIn('product_id', $value);
        } else {
            if ($value !== '') $query->where('product_id', $value);
        }
    }

    /**
     * is_all搜索器
     * @param $query
     * @param $value
     */
    public function searchIsAllAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_all', $value);
        }
    }

}
