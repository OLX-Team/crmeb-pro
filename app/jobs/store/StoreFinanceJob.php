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

namespace app\jobs\store;


use app\services\store\finance\StoreFinanceFlowServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 门店资金流水记录
 * Class StoreFinanceJob
 * @package app\jobs
 */
class StoreFinanceJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 门店流水
     * @param array $order
     * @param int $type
     * @param int $price
     * @return bool
     */
    public function doJob(array $order, int $type, $price = 0)
    {
        try {
            /** @var StoreFinanceFlowServices $storeFinanceFlowServices */
            $storeFinanceFlowServices = app()->make(StoreFinanceFlowServices::class);
            $storeFinanceFlowServices->setFinance($order, $type, $price);
        } catch (\Throwable $e) {
            Log::error('记录流水失败:' . $e->getMessage());
        }
        return true;
    }
}
