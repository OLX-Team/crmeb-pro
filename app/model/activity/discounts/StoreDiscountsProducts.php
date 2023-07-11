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

use app\model\product\product\StoreProduct;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 优惠套餐商品
 * Class StoreDiscountsProducts
 * @package app\model\activity\discounts
 */
class StoreDiscountsProducts extends BaseModel
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
    protected $name = 'store_discounts_products';

    use ModelTrait;

    /**
     * 一对一关联商品表
     * @return \think\model\relation\HasOne
     */
    public function product()
    {
        return $this->hasOne(StoreProduct::class, 'id', 'product_id')->field(['id', 'freight', 'postage', 'temp_id'])->bind([
            'freight' => 'freight',
            'postage' => 'postage',
            'p_temp_id' => 'temp_id'
        ]);
    }

    /**
     * 状态搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchDiscountIdAttr($query, $value)
    {
        if ($value != '') $query->where('discount_id', $value);
    }

}