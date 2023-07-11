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

namespace app\jobs\order;


use app\services\order\StoreOrderDeliveryServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 订单发货
 * Class OrderDeliveryJob
 * @package app\jobs
 */
class OrderDeliveryJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 确认收货
     * @param $orderInfo
     * @param $data
     * @param $type
     * @return bool
     */
    public function doJob($orderInfo, $data, $type)
    {
        try {
            /** @var StoreOrderDeliveryServices $storeOrderDeliveryServices */
            $storeOrderDeliveryServices = app()->make(StoreOrderDeliveryServices::class);
            $data['type'] = $type;
            $storeOrderDeliveryServices->doDelivery((int)$orderInfo['id'], $orderInfo, $data);
        } catch (\Throwable $e) {
            Log::error('收银台订单自动发货失败，原因：' . $e->getMessage());
        }
        return true;
    }


}
