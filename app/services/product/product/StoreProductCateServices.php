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

namespace app\services\product\product;


use app\dao\product\product\StoreProductCateDao;
use app\services\BaseServices;
use app\services\product\category\StoreCategoryServices;

/**
 * Class StoreProductCateService
 * @package app\services\product\product
 * @method productIdByCateId(array $productId) 根据商品id获取分类id
 * @method cateIdByProduct(array $cate_id) 根据分类获取商品id
 */
class StoreProductCateServices extends BaseServices
{
    public function __construct(StoreProductCateDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存商品分类关联
     * @param int $id
     * @param array $cate_id
     * @param int $is_show
     * @return bool
     */
    public function saveCate(int $id, array $cate_id, int $is_show = 0)
    {
        $cateData = [];
        $time = time();
        /** @var StoreCategoryServices $storeCategoryServices */
        $storeCategoryServices = app()->make(StoreCategoryServices::class);
        $cateGory = $storeCategoryServices->getColumn([['id', 'IN', $cate_id]], 'id,pid', 'id');
        foreach ($cate_id as $cid) {
            if ($cid && isset($cateGory[$cid]['pid'])) {
                $cateData[] = ['product_id' => $id, 'cate_id' => $cid, 'cate_pid' => $cateGory[$cid]['pid'], 'status' => $is_show, 'add_time' => $time];
            }
        }
        $this->change($id, $cateData);
        return true;
    }

    /**
     * 商品添加修改商品分类关联
     * @param $id
     * @param $cateData
     */
    public function change($id, $cateData)
    {
        $this->dao->delete(['product_id' => $id]);
        $this->dao->saveAll($cateData);
    }


}
