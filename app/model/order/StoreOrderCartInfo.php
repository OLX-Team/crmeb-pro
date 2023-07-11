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

namespace app\model\order;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 *  订单记录Model
 * Class StoreOrderCartInfo
 * @package app\model\order
 */
class StoreOrderCartInfo extends BaseModel
{
    use ModelTrait;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_order_cart_info';

    /**
     * 购物车信息获取器
     * @param $value
     * @return array|mixed
     */
    public function getCartInfoAttr($value)
    {
        return is_string($value) ? json_decode($value, true) ?? [] : [];
    }

    /**
     * 订单ID搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchOidAttr($query, $value, $data)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('oid', $value);
            } else {
                $query->where('oid', $value);
            }
        }
    }

    /**
     * UID搜索器
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('uid', $value);
            } else {
                $query->where('uid', $value);
            }
        }
    }

    /**
     * product_id搜索器
     * @param Model $query
     * @param $value
     */
    public function searchProductIdAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('product_id', $value);
            } else {
                $query->where('product_id', $value);
            }
        }
    }

    /**
     * 优惠活动ID搜索器
     * @param Model $query
     * @param $value
     */
    public function searchPromotionsIdAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->where(function($q) use ($value) {
                    foreach ($value as $key => $v) {
                        $q->whereOr(function ($c) use ($v) { 
                            $c->whereFindInSet('promotions_id', $v);
                        });
                    }
                });
            } else {
                $query->whereFindInSet('promotions_id', $value);
            }
        }
    }

    /**
     * 购物车ID搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchCartIdAttr($query, $value, $data)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('cart_id', $value);
            } else {
                $query->where('cart_id', $value);
            }
        }
    }

    /**
     * 原购物车ID搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchOldCartIdAttr($query, $value, $data)
    {
        if (is_array($value)) {
            $query->whereIn('old_cart_id', $value);
        } else {
            $query->where('old_cart_id', $value);
        }
    }

    /**
     *  拆分状态搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchSplitStatusAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('split_status', $value);
        } else {
            if (in_array($value, [0, 1, 2])) {
                $query->where('split_status', $value);
            }
        }
    }

    /**
     * 是否赠送搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsGiftAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_writeoff', $value);
        }
    }

    /**
     * 是否核销搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchIsWriteoffAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_writeoff', $value);
        }
    }
}
