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
use app\model\activity\promotions\StorePromotions;
use think\Model;

/**
 * 订单优惠活动记录Model
 * Class SoreOrderPromotions
 * @package app\model\order
 */
class StoreOrderPromotions extends BaseModel
{
    use ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_order_promotions';


    /**
     * 一对一关联优惠活动
     * @return \think\model\relation\hasMany
     */
    public function promotions()
    {
        return $this->hasOne(StorePromotions::class, 'id', 'promotions_id');
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
    public function searchUidIdAttr($query, $value)
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
     * 优惠活动ID搜索器
     * @param Model $query
     * @param $value
     */
    public function searchPromotionsIdAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('promotions_id', $value);
            } else {
                $query->where('promotions_id', $value);
            }
        }
    }

}
