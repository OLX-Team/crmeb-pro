<?php


namespace app\jobs\store;

use app\services\product\branch\StoreBranchProductAttrValueServices;
use app\services\product\branch\StoreBranchProductServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 门店同步库存队列
 * Class SynchStocksJob
 * @package app\jobs\store
 */
class SynchStocksJob extends BaseJobs
{
    use QueueTrait;

    public function doJob($ids, $storeId)
    {
        try {
            /** @var StoreBranchProductServices $services */
            $services = app()->make(StoreBranchProductServices::class);
            $services->synchStocks($ids, (int)$storeId);
        } catch (\Throwable $e) {

        }
        return true;
    }
}
