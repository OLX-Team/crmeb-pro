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
use app\services\user\UserServices;
use app\services\activity\discounts\StoreDiscountsServices;
use app\services\activity\promotions\StorePromotionsServices;
use app\services\activity\promotions\StorePromotionsAuxiliaryServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\product\product\StoreProductServices;
use app\services\product\branch\StoreBranchProductServices;
use think\facade\App;

/**
 * 收银台优惠活动
 */
class Promotions extends AuthController
{
    protected $services;

    /**
     * StoreOrder constructor.
     * @param App $app
     * @param StorePromotionsServices $service
     */
    public function __construct(App $app, StorePromotionsServices $service)
    {
        parent::__construct($app);
        $this->services = $service;
    }

    /**
     * 获取活动商品数量信息
     * @return mixed
     */
    public function promotionsCount(StoreSeckillServices $seckillServices, StoreDiscountsServices $discountsServices, $uid)
    {
        $typeArr = [1 => 'time_discount', 2 => 'n_piece_n_discount', 3 => 'full_discount', 4 => 'full_give'];
        $where[] = [];
        $where['type'] = 1;
        $where['store_id'] = 0;
        $where['pid'] = 0;
        $where['is_del'] = 0;
        $where['status'] = 1;
        $where['promotionsTime'] = true;
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        /** @var StoreBranchProductServices $branchProductServices */
        $branchProductServices = app()->make(StoreBranchProductServices::class);
        $storeProducts = $branchProductServices->getBranchProductList(['store_id' => $this->storeId, 'type' => 7], 'product_id');
        $not_ids = [];
        if ($storeProducts) {
            $not_ids = array_column($storeProducts, 'product_id');
        }
        $storeProducts = $branchProductServices->getBranchProductList(['store_id' => $this->storeId, 'type' => 1], 'product_id');
                $ids = [];
        if ($storeProducts) {
            $ids = array_column($storeProducts, 'product_id');
        }
        $result = [];
        foreach ($typeArr as $type => $key) {
            $where['promotions_type'] = $type;
            $product_where = [];
            $product_where['ids'] = $ids;
            $product_where['not_ids'] = $not_ids;
            //门店不展示卡密商品
            $product_where['product_type'] = [0, 2];
            $product_where['is_show'] = 1;
            $product_where['is_del'] = 0;
            //存在一个全部商品折扣优惠活动 直接返回商品
            if ($ids && !$this->services->count($where + ['product_partake_type' => 1])) {
                //正选并集
                $mergeIds = function ($promotions) {
                    $data = [];
                    foreach ($promotions as $item) {
                        $productIds = is_string($item['product_id']) ? explode(',', $item['product_id']) : $item['product_id'];
                        $data = array_merge($data, $productIds);
                    }
                    return $data;
                };
                $promotions = $this->services->getList($where + ['product_partake_type' => 2], 'id,product_id');
                $pIds = $promotions ? $mergeIds($promotions) : [];
                $product_where['ids'] = $pIds ? array_intersect($ids, $pIds) : $ids;
                $notPromotions = $this->services->getList($where + ['product_partake_type' => 3], 'id,product_id');
                //反选交集
                /** @var StorePromotionsAuxiliaryServices $auxiliaryService */
                $auxiliaryService = app()->make(StorePromotionsAuxiliaryServices::class); 
                $intersectIds = function ($promotions) use ($auxiliaryService) {
                    $data = [];
                    foreach ($promotions as $item) {
                        $productIds = is_string($item['product_id']) ? explode(',', $item['product_id']) : $item['product_id'];
                        $productIds = $auxiliaryService->getColumn(['promotions_id' => $item['id'], 'type' => 1, 'is_all' => 1, 'product_id' => $productIds], 'product_id', '', true);
                    if(!$productIds) {
                        continue;
                    }
                        if ($data) {
                            $data = array_intersect($data, $productIds);
                        } else {
                            $data = $productIds;
                        }
                    }
                    return $data;
                };
                $product_where['not_ids'] = array_merge($product_where['not_ids'] ?? [], $notPromotions ? $intersectIds($notPromotions) : []);
            }
            $count = 0;
            if ($product_where['ids'] && $this->services->count($where)) {
                $product_where['is_vip_product'] = -1;
                $product_where['is_presale_product'] = 0;
                if ($uid) {
                    /** @var UserServices $user */
                    $user = app()->make(UserServices::class);
                    $userInfo = $user->getUserCacheInfo((int)$uid);
                    $is_vip = $userInfo['is_money_level'] ?? 0;
                    $product_where['is_vip_product'] = $is_vip ? -1 : 0;
                }
                $count = $productServices->getCount($product_where);
            }
            $result[$key] = ['type' => $type, 'count' => $count];
        }
        $result['seckill'] = ['type' => '5', 'count' => $seckillServices->getTimeCount($seckillServices->getSeckillTime(), $ids, $not_ids)];
        $result['discount'] = ['type' => '6', 'count' => $discountsServices->getDiscountsCount()];
        return $this->success($result);
    }

    /**
     * 收银台获取活动商品列表
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function activityList(Request $request, $type, $uid)
    {
        $data = [];
        /** @var StoreBranchProductServices $branchProductServices */
        $branchProductServices = app()->make(StoreBranchProductServices::class);
        $storeProducts = $branchProductServices->getBranchProductList(['store_id' => $this->storeId, 'type' => 7], 'product_id');
        $not_ids = [];
        if ($storeProducts) {
            $not_ids = array_column($storeProducts, 'product_id');
        }
        $storeProducts = $branchProductServices->getBranchProductList(['store_id' => $this->storeId, 'type' => 1], 'product_id');
        $ids = [];
        if ($storeProducts) {
            $ids = array_column($storeProducts, 'product_id');
        }
        $data['list'] = [];
        $data['count'] = 0;
        switch ($type) {
            case 1:
            case 2:
            case 3:
            case 4:
                [$staff_id, $promotions_id, $tourist_uid, $store_name] = $request->getMore([
                    ['staff_id', ''],
                    ['promotions_id', 0],
                    ['tourist_uid', ''],//虚拟用户uid
                    ['store_name', '']
                ], true);
                $promotions_id = $request->param('promotions_id', 0);
                $this->services->setItem('store_id', $this->storeId)
                            ->setItem('tourist_uid', $tourist_uid)
                            ->setItem('staff_id', $staff_id)
                            ->setItem('ids', $ids)
                            ->setItem('not_ids', $not_ids)
                            ->setItem('store_name', $store_name);
                $data = $this->services->getTypeList((int)$type, (int)$uid, (int)$promotions_id);
                $this->services->reset();
                break;
            case 5:
                /** @var StoreSeckillServices $seckillServices */
                $seckillServices = app()->make(StoreSeckillServices::class);
                $timeId = (int)$seckillServices->getSeckillTime();
                $data['list'] = $seckillServices->getListByTime($timeId, $ids, true);
                $data['count'] = $seckillServices->getTimeCount($timeId, $ids);
                break;
            case 6:
                $where['is_del'] = 0;
                $where['status'] = 1;
                $where['is_time'] = 1;
                /** @var StoreDiscountsServices $discountsServices */
                $discountsServices = app()->make(StoreDiscountsServices::class);
                $data = $discountsServices->getList($where);
                break;
        }
        return $this->success($data);
    }
}
