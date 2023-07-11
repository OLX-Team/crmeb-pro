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

namespace app\model\product\branch;


use app\model\product\product\StoreProduct;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 门店商品
 * Class StoreBranchProduct
 * @package app\model\product\branch
 */
class StoreBranchProduct extends BaseModel
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
    protected $name = 'store_branch_product';

    /**
     * @return \think\model\relation\HasOne
     */
    public function product()
    {
        return $this->hasOne(StoreProduct::class, 'id', 'product_id');
    }

    /**
     * 库存搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchStockAttr($query, $value)
    {
        $query->where('stock', $value);
    }

    /**
     * is_del搜索器
     * @param $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        $query->where('is_del', $value);
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
     * is_show搜索器
     * @param $query
     * @param $value
     */
    public function searchIsShowAttr($query, $value)
    {
        if ($value !== '') $query->where('is_show', $value);
    }

    /**
     * store_id
     * @param $query
     * @param $value
     */
    public function searchStoreIdAttr($query, $value)
    {
        if ($value !== '') $query->where('store_id', $value);
    }

    /**
     * 商品数量条件搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchTypeAttr($query, $value, $data)
    {
        switch ((int)$value) {
            case 1:
                $query->where(['is_del' => 0])->where('is_show', 1);
                break;
            case 2:
                $query->where(['is_del' => 0])->where('is_show', 0);
                break;
            case 3:
                $query->where(['is_del' => 0]);
                break;
            case 4:
                $query->where(['is_del' => 0])->where(function ($query) {
                    $query->whereIn('id', function ($query) {
                        $query->name('store_branch_product_attr_value')->where('stock', 0)->where('type', 0)->field('product_id')->select();
                    })->whereOr('stock', 0);
                });
                break;
            case 5:
                if (isset($data['store_stock']) && $data['store_stock']) {
                    $store_stock = $data['store_stock'];
                    $query->where(['is_del' => 0])->where('stock', '<=', $store_stock)->where('stock', '>', 0);
                } else {
                    $query->where(['is_del' => 0])->where('stock', '>', 0);
                }
                break;
            case 6:
                $query->where(['is_del' => 1]);
                break;
            case 7:
                $query->where(function ($q) {
                    $q->where(['is_del' => 1])->whereOr('is_show', 0);
                });
                break;
        };
    }

}

