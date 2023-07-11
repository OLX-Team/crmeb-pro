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


use app\model\user\User;
use app\model\order\StoreOrder;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * Class StoreOrderRefund
 * @package app\model\order
 */
class StoreOrderRefund extends BaseModel
{
    use ModelTrait;

    protected $pk = 'id';

    protected $name = 'store_order_refund';

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
     * 图片获取器
     * @param $value
     * @return array|mixed
     */
    public function getRefundImgAttr($value)
    {
        return is_string($value) ? json_decode($value, true) ?? [] : [];
    }

    /**
     * 图片获取器
     * @param $value
     * @return array|mixed
     */
    public function getRefundGoodsImgAttr($value)
    {
        return is_string($value) ? json_decode($value, true) ?? [] : [];
    }

    /**
     * 一对一关联订单表
     * @return StoreOrderRefund|\think\model\relation\HasOne
     */
    public function order()
    {
        return $this->hasOne(StoreOrder::class, 'id', 'store_order_id');
    }

    /**
     * 一对一关联用户表
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid', false)->field(['uid', 'avatar', 'nickname', 'phone', 'now_money', 'integral', 'delete_time'])->bind([
            'avatar' => 'avatar',
            'nickname' => 'nickname',
            'phone' => 'phone',
            'now_money' => 'now_money',
            'integral' => 'integral',
            'delete_time' => 'delete_time',
        ]);
    }

    /**
     * 订单ID搜索器
     * @param $query
     * @param $value
     */
    public function searchStoreOrderIdAttr($query, $value)
    {
        if ($value !== '') {
            if (is_array($value)) {
                $query->whereIn('store_order_id', $value);
            } else {
                $query->where('store_order_id', $value);
            }
        }
    }

    /**
     * 门店ID
     * @param $query
     * @param $value
     */
    public function searchStoreIdAttr($query, $value)
    {
        if ($value !== '') {
            if ($value == -1) {//所有门店
                $query->where('store_id', '>', 0);
            } else {
                $query->where('store_id', $value);
            }
        }
    }


    /**
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        if ($value !== '' && !is_null($value)) {
            if (is_array($value)) {
                $query->whereIn('uid', $value);
            } else {
                $query->where('uid', $value);
            }
        }
    }

    /**
     * is_cancel
     * @param Model $query
     * @param $value
     */
    public function searchIsCancelAttr($query, $value)
    {
        if ($value !== '' && !is_null($value)) $query->where('is_cancel', $value);
    }

    /**
     * is_del搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '' && !is_null($value)) $query->where('is_del', $value);
    }
}
