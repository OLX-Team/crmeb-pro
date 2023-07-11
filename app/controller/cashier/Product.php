<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
namespace app\controller\cashier;

use app\Request;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\category\StoreCategoryServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\product\sku\StoreProductAttrServices;

/**
 * 收银台商品控制器
 */
class Product extends AuthController
{
    /**
     * 获取商品一级分类
     * @param StoreCategoryServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOneCategory(StoreCategoryServices $services)
    {
        return $this->success($services->getOneCategory());
    }

    /**
     * 收银台商品列表
     * @param Request $request
     * @param StoreBranchProductServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductList(Request $request, StoreBranchProductServices $services)
    {
        $where = $request->getMore([
            ['store_name', ''],
            ['cate_id', 0, '', 'cid'],
            ['field_key', ''],
            ['staff_id', ''],
            ['uid', 0],
            ['tourist_uid', ''],//虚拟用户uid
        ]);
        $where['store_id'] = $this->storeId;
        $where['field_key'] = $where['field_key'] == 'all' ? '' : $where['field_key'];
        $where['field_key'] = $where['field_key'] == 'id' ? 'product_id' : $where['field_key'];
        $staff_id = (int)$where['staff_id'];
        $tourist_uid = (int)$where['tourist_uid'];
        $uid = (int)$where['uid'];
        unset($where['staff_id'], $where['uid'], $where['tourist_uid']);
        $where['is_del'] = 0;
        $where['is_show'] = 1;
        return $this->success($services->getCashierProductListV1($where, $uid, 0, $tourist_uid));
    }

    /**
     * 获取收银台商品详情
     * @param Request $request
     * @param StoreBranchProductServices $services
     * @param int $id
     * @param int $uid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductInfo(Request $request, StoreBranchProductServices $services, $id = 0, $uid = 0)
    {
        if (!$id) {
            return $this->fail('缺少商品id');
        }
        $touristUid = $request->get('tourist_uid');
        return $this->success($services->getProductDetail($this->storeId, (int)$id, (int)$uid, (int)$touristUid));
    }

    /**
     * 获取商品属性
     * @param Request $request
     * @return mixed
     */
    public function getProductAttr(Request $request)
    {
        [$id, $cartNum, $uid] = $request->getMore([
            ['id', 0],
            ['type', 0],
            ['uid', 0]
        ], true);
        if (!$id) return app('json')->fail('参数错误');
        /** @var StoreSeckillServices $seckillServices */
        $seckillServices = app()->make(StoreSeckillServices::class);
        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        $storeInfo = $seckillServices->getOne(['id' => $id]);
        $data['storeInfo'] = $storeInfo ? $storeInfo->toArray() : [];
        $data['storeInfo']['store_name'] = $data['storeInfo']['title'] ?? '';
        [$data['productAttr'], $data['productValue']] = $storeProductAttrServices->getProductAttrDetail((int)$id, (int)$uid, (int)$cartNum, 1, (int)$storeInfo['product_id']);
        return app('json')->successful($data);
    }

}
