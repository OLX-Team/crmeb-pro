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


use app\services\product\product\StoreProductStockRecordServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 库存记录
 * Class ProductStockRecordJob
 * @package app\jobs\product
 */
class ProductStockRecordJob extends BaseJobs
{
    use QueueTrait;

    public function doJob(int $id, array $valueGroup)
    {
        try {
            /** @var StoreProductStockRecordServices $storeProductStockRecordServices */
            $storeProductStockRecordServices = app()->make(StoreProductStockRecordServices::class);
            //保存库存记录
            $storeProductStockRecordServices->saveRecord($id, $valueGroup);
        } catch (\Throwable $e) {
            Log::error('写入商品库存记录发生错误,错误原因:' . $e->getMessage());
        }
        return true;
    }
}
