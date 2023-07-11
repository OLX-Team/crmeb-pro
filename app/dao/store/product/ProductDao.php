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

namespace app\dao\store\product;

use app\dao\BaseDao;
use app\model\store\product\Product;

/**
 * 商品dao
 * Class DeliveryServiceDao
 * @package app\dao\store
 */
class ProductDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return Product::class;
    }

    /**
     * 收银台搜索
     * @param array $where
     * @return \crmeb\basic\BaseModel
     */
    public function getSerach(array $where)
    {
        return $this->getModel()->when(isset($where['store_name']) && $where['store_name'], function ($query) use ($where) {
            if (isset($where['field_key']) && $where['field_key'] && in_array($where['field_key'], ['product_id', 'store_name', 'bar_code'])) {
                $query->where($where['field_key'], $where['store_name']);
            } else {
                $query->where('store_name|bar_code|keyword', 'LIKE', '%' . $where['store_name'] . '%');
            }
        })->when(isset($where['cate_id']) && $where['cate_id'], function ($query) use ($where) {
            $query->whereIn('product_id', function ($query) use ($where) {
                $query->name('store_product_cate')->whereIn('cate_id', function ($query) use ($where) {
                    $query->name('store_category')->where('pid', $where['cate_id'])->field('id')->select();
                })->field('product_id')->select();
            });
        })->when(isset($where['store_id']), function ($query) use ($where) {
            $query->where('store_id', $where['store_id']);
        });
    }


}
