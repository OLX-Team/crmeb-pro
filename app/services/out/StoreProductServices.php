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

namespace app\services\out;

use app\dao\product\product\StoreProductDao;
use app\Request;
use app\services\BaseServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use think\exception\ValidateException;
use app\services\product\product\StoreProductServices as StoreProductsServices;

/**
 * Class StoreProductService
 * @package app\services\product\product
 * @method getOne(array $where) 获取一条数据
 * @method update(int $id, array $data) 获取一条数据
 * @method delete($id, ?string $key = null) 删除数据
 * @method value($where, ?string $field = null) 获取字段
 * @method incStockDecSales(array $where, int $num, string $stock = 'stock', string $sales = 'sales') 加库存减销量
 * @method count(array $where) 获取指定条件下的数量
 * @method getColumn(array $where, string $field, string $key = '') 获取某个字段数组
 * @method getSearchList(array $where, int $page = 0, int $limit = 0, ?array $field = ['*']) 获取列表
 * @method get(int $id, array $field) 获取一条数据
 * @method getCid(int $page, int $limit) 获取一级分类ID
 */
class StoreProductServices extends BaseServices
{
    public function __construct(StoreProductDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取商品详情
     * @param Request $request
     * @param int $id
     * @param int $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function productDetail(Request $request, $spu)
    {
        $storeInfo = $this->dao->getOne(['spu' => $spu], 'id,store_name,is_show,cate_id,keyword,unit_name,store_info,image,video_link,custom_form', ['description']);
        if (!$storeInfo) {
            throw new ValidateException('商品不存在');
        } else {
            $storeInfo = $storeInfo->toArray();
        }
        $siteUrl = sys_config('site_url');
        $storeInfo['image'] = set_file_url($storeInfo['image'], $siteUrl);

        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        [$productAttr, $productValue] = $storeProductAttrServices->getProductAttrDetail($storeInfo['id'], 0, 0, 0, $storeInfo);
        $storeInfo['productValue'] = $productValue;
        $storeInfo = get_thumb_water($storeInfo, 'big', ['image', 'slider_image']);
        $storeInfoNew = get_thumb_water($storeInfo, 'small', ['image']);
        $storeInfo['small_image'] = $storeInfoNew['image'];

        //浏览记录
//        ProductLogJob::dispatch(['visit', ['uid' => $uid, 'product_id' => $id]]);
        return $storeInfo;
    }

    /**
     * @param Request $request
     * @param $spu
     * @param $is_show
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setShow(Request $request, $spu, $is_show)
    {
        $storeInfo = $this->dao->getOne(['spu' => $spu], 'id');
        if (!$storeInfo) {
            throw new ValidateException('商品不存在');
        } else {
            $storeInfo = $storeInfo->toArray();
        }
        /** @var StoreProductsServices $storeProductsService */
        $storeProductsService = app()->make(StoreProductsServices::class);
        $storeProductsService->setShow([$storeInfo['id']], $is_show);
        return true;
    }

    /**
     * 修改库存
     * @param $data
     * @param $spu
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setStock($data, $spu)
    {
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $storeInfo = $this->dao->getOne(['spu' => $spu], 'id');
        if (!$storeInfo)
            throw new ValidateException('商品不存在');
        foreach ($data as $item) {
            if (isset($item['unique']) && $item['stock']) {
                $attr = $storeProductAttrValueServices->getOne(['unique' => $item['unique'], 'type' => 0, 'product_id' => $storeInfo['id']]);
                if ($attr) {
                    $attr->stock = $item['stock'];
                    $attr->save();
                }
            }
        }
        $stock = $storeProductAttrValueServices->pidBuStock($storeInfo['id']);
        $storeInfo->stock = $stock ?? 0;
        $storeInfo->save();
        return true;
    }
}
