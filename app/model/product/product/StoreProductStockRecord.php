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

namespace app\model\product\product;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 库存记录
 * Class StoreProductStockRecord
 * @package app\model\product\product
 */
class StoreProductStockRecord extends BaseModel
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
    protected $name = 'store_product_stock_record';

    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'add_time';

    /**
     * 添加时间修改器
     * @return int
     */
    public function setAddTimeAttr()
    {
        return time();
    }

    /**
     * 一对一关联
     * 商品记录关联商品名称
     * @return \think\model\relation\HasOne
     */
    public function storeName()
    {
        return $this->hasOne(StoreProduct::class, 'id', 'product_id')->bind([
            'store_name',
            'image',
            'product_price' => 'price'
        ]);
    }

    /**
     * 门店ID搜索器
     * @param $query
     * @param $value
     */
    public function searchStoreIdAttr($query, $value)
    {
        if ($value != '') $query->where('store_id', $value);
    }


    /**
     * 商品ID搜索器
     * @param $query
     * @param $value
     */
    public function searchProductIdAttr($query, $value)
    {
        if ($value != '') $query->where('product_id', $value);
    }

    /**
     * unique搜索器
     * @param $query
     * @param $value
     */
    public function searchUniqueAttr($query, $value)
    {
        if ($value != '') $query->where('unique', $value);
    }

    /**
     * pm搜索器
     * @param $query
     * @param $value
     */
    public function searchPmAttr($query, $value)
    {
        if ($value != '') $query->where('pm', $value);
    }
}
