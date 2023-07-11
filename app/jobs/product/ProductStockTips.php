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


use app\services\product\product\StoreProductServices;
use app\webscoket\SocketPush;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 商品库存警戒提示
 * Class ProductStockTips
 * @package app\jobs\product
 */
class ProductStockTips extends BaseJobs
{
    use QueueTrait;


    public function doJob($productId)
    {
        /** @var StoreProductServices $make */
        $make = app()->make(StoreProductServices::class);
        $stock = $make->value(['id' => $productId], 'stock');
        $store_stock = sys_config('store_stock') ?? 0;//库存预警界限
        if ($store_stock >= $stock) {
            try {
                SocketPush::admin()->type('STORE_STOCK')->data(['id' => $productId])->push();
            } catch (\Exception $e) {
            }
        }
        return true;
    }

}
