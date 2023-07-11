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

namespace app\jobs\product;


use app\services\product\sku\StoreProductAttrValueServices;
use app\webscoket\SocketPush;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 库存提醒
 * Class ProductStockValueTips
 * @package app\jobs\product
 */
class ProductStockValueTips extends BaseJobs
{
    use QueueTrait;

    public function doJob($productId, $unique, $type)
    {
        /** @var StoreProductAttrValueServices $make */
        $make = app()->make(StoreProductAttrValueServices::class);
        $stock = $make->value([
            'product_id' => $productId,
            'unique' => $unique,
            'type' => $type
        ], 'stock');
        $store_stock = sys_config('store_stock') ?? 0;//库存预警界限
        if ($store_stock >= $stock) {
            try {
                SocketPush::admin()->data(['id' => $productId])->push();
            } catch (\Exception $e) {
            }
        }
        return true;
    }
}
