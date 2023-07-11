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

namespace app\services\other;


use app\services\activity\discounts\StoreDiscountsServices;
use app\services\activity\integral\StoreIntegralServices;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\BaseServices;
use app\services\product\sku\StoreProductAttrValueServices;
use crmeb\services\CacheService;
use crmeb\traits\ServicesTrait;


/**
 * Class HotDataServices
 * @package app\services\product\product
 */
class HotDataServices extends BaseServices
{
    use ServicesTrait;

    public function hot(int $type = 0)
    {
        /** @var StoreProductAttrValueServices $productAttrValueServices */
        $productAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        switch ($type) {
            case 0://普通商品
                break;
            case 1://秒杀商品
                /** @var StoreSeckillServices $seckillServices */
                $seckillServices = app()->make(StoreSeckillServices::class);
                $products = $seckillServices->getListByTime(0);
                if ($products) {
                    $products = array_column($products, 'id');
                    $attrValue = $productAttrValueServices->getSkuArray([['product_id', 'IN', $products], ['type', '=', 1], ['quota', '>', 0]], 'unique,quota,quota_show');
                    if ($attrValue) {
                        foreach ($attrValue as $item) {
                            CacheService::setStock($item['unique'], (int)$item['quota'], 1);
                        }
                    }
                }
                break;
            case 2://砍价商品
                /** @var StoreBargainServices $bargainServices */
                $bargainServices = app()->make(StoreBargainServices::class);
                $products = $bargainServices->bargainList();
                if ($products) {
                    $products = array_column($products, 'id');
                    $attrValue = $productAttrValueServices->getSkuArray([['product_id', 'IN', $products], ['type', '=', 2], ['quota', '>', 0]], 'unique,quota,quota_show');
                    if ($attrValue) {
                        foreach ($attrValue as $item) {
                            CacheService::setStock($item['unique'], (int)$item['quota'], 2);
                        }
                    }
                }
                break;
            case 3://拼团商品
                /** @var StoreCombinationServices $combinationServices */
                $combinationServices = app()->make(StoreCombinationServices::class);
                $products = $combinationServices->combinationList(['is_del' => 0, 'is_show' => 1, 'pinkIngTime' => true, 'storeProductId' => true]);
                if ($products) {
                    $products = array_column($products, 'id');
                    $attrValue = $productAttrValueServices->getSkuArray([['product_id', 'IN', $products], ['type', '=', 3], ['quota', '>', 0]], 'unique,quota,quota_show');
                    if ($attrValue) {
                        foreach ($attrValue as $item) {
                            CacheService::setStock($item['unique'], (int)$item['quota'], 3);
                        }
                    }
                }
                break;
            case 4://积分商品
                /** @var StoreIntegralServices $integralServices */
                $integralServices = app()->make(StoreIntegralServices::class);
                $products = $integralServices->getColumn(['is_del' => 0], 'id');
                if ($products) {
                    $attrValue = $productAttrValueServices->getSkuArray([['product_id', 'IN', $products], ['type', '=', 4], ['quota', '>', 0]], 'unique,quota,quota_show');
                    if ($attrValue) {
                        foreach ($attrValue as $item) {
                            CacheService::setStock($item['unique'], (int)$item['quota'], 4);
                        }
                    }
                }
                break;
            case 5://套餐
                /** @var StoreDiscountsServices $discountsServices */
                $discountsServices = app()->make(StoreDiscountsServices::class);
                $products = $discountsServices->getColumn([['is_del', '=', 0], ['is_limit', '>', 0]], 'id,limit_num');
                if ($products) {
                    foreach ($products as $item) {
                        CacheService::setStock(md5($item['id']), (int)$item['limit_num'], 5);
                    }
                }
                break;
        }
    }
}