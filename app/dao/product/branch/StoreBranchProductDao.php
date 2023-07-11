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

namespace app\dao\product\branch;


use app\dao\BaseDao;
use app\model\product\branch\StoreBranchProduct;

/**
 * Class StoreBranchProductDao
 * @package app\dao\product\branch
 */
class StoreBranchProductDao extends BaseDao
{

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return StoreBranchProduct::class;
    }

    /**
     * 收银台搜索
     * @param array $where
     * @return \crmeb\basic\BaseModel
     */
    public function getSerach(array $where)
    {
        return $this->search($where)->when(isset($where['store_name']) && $where['store_name'], function ($query) use ($where) {
            if (isset($where['field_key']) && $where['field_key'] && in_array($where['field_key'], ['product_id', 'store_name', 'bar_code'])) {
                $query->where($where['field_key'], $where['store_name']);
            } else {
                $query->where('product_id|store_name|bar_code|keyword', 'LIKE', '%' . $where['store_name'] . '%');
            }
        })->when(isset($where['ids']) && $where['ids'], function ($query) use ($where) {
            $query->whereIn('product_id', $where['ids']);
        })->when(isset($where['cate_id']) && $where['cate_id'], function ($query) use ($where) {
            $query->whereIn('product_id', function ($query) use ($where) {
                $query->name('store_product_cate')->whereIn('cate_id', function ($query) use ($where) {
                    $query->name('store_category')->where('pid', $where['cate_id'])->field('id')->select();
                })->field('product_id')->select();
            });
        });
    }

    /**
     * 订单搜索列表
     * @param array $where
     * @param array $field
     * @param int $page
     * @param int $limit
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBranchProductList(array $where, string $field = '*', int $page = 0, int $limit = 0, array $with = [], string $order = 'sort desc,stock desc,id desc')
    {
        return $this->search($where)->field($field)->when($with, function($query) use($with) {
            $query->with($with);
        })->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order($order)->select()->toArray();
    }


    /**
     *更新单个商品
     * @param array $where
     * @param array $data
     * @return \crmeb\basic\BaseModel
     */
    public function updateOnline(array $where, array $data)
    {
        return $this->getModel()->where($where)->update($data);
    }
}
