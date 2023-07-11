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

use app\services\erp\OrderServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * Class OrderSyncJob
 * @package app\jobs\order
 */
class OrderSyncJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 同步订单
     * @param int $oid
     * @return bool
     */
    public function syncOrder(int $oid): bool
    {
        try{
            /** @var OrderServices $orderServices */
            $orderServices = app()->make(OrderServices::class);
            $orderServices->upload($oid);
        } catch (\Exception $e) {
            Log::error(['msg' => '订单信息同步失败，原因：' . $e->getMessage(), 'data' => ['oid' => $oid]]);
        }
        return true;
    }

    /**
     * 更新订单信息
     * @param string $orderId
     * @param string $erpOrderId
     * @param int $oid
     * @return bool
     */
    public function reorderOrder(string $orderId, array $erpOrderId = [], int $oid = 0): bool
    {
        try{
            /** @var OrderServices $orderServices */
            $orderServices = app()->make(OrderServices::class);
            $cancelRes = $orderServices->cancelOrder($orderId, $erpOrderId);
            if ($cancelRes && $oid > 0) {
                $orderServices->upload($oid);
            }
        } catch (\Exception $e) {
            Log::error(['msg' => '订单信息更新失败，原因：' . $e->getMessage(), 'data' => ['orderId' => $orderId, 'erpOrderId' => $erpOrderId, 'oid' => $oid]]);
        }
        return true;
    }

    /**
     * 回调取消
     * @param array $data
     * @return bool
     */
    public function refundOrder(array $data): bool
    {
        try{
            /** @var OrderServices $orderServices */
            $orderServices = app()->make(OrderServices::class);
            $orderServices->refundOrder($data);
        } catch (\Exception $e) {
            Log::error('回调订单取消同步失败，原因：' . $e->getMessage());
        }
        return true;
    }

    /**
     * 同步退货单
     * @param int $refundId
     * @return bool
     */
    public function refundOrderUpload(int $refundId): bool
    {
        try {
            /** @var OrderServices $orderServices */
            $orderServices = app()->make(OrderServices::class);
            $orderServices->refundOrderUpload([$refundId]);
        } catch (\Exception $e) {
            Log::error('同步退货单信息失败，原因：' . $e->getMessage());
        }
        return true;
    }
}