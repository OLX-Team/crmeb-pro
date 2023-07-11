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

namespace app\jobs\pink;


use app\services\activity\combination\StorePinkServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 拼团失败
 * Class PinkJob
 * @package app\jobs
 */
class PinkJob extends BaseJobs
{
    use QueueTrait;

    public function doJob($pinkId)
    {
        try {
            /** @var StorePinkServices $pinkService */
            $pinkService = app()->make(StorePinkServices::class);
            $people = $pinkService->value(['id' => $pinkId], 'people');
            $count = $pinkService->count(['k_id' => $pinkId, 'is_refund' => 0]) + 1;
            $orderIds = $pinkService->getColumn([['id|k_id', '=', $pinkId]], 'order_id_key', 'uid');
            if ($people > $count) {
                $refundData = [
                    'refund_reason' => '拼团时间超时',
                    'refund_explain' => '拼团时间超时',
                    'refund_img' => json_encode([]),
                ];
                /** @var StoreOrderServices $orderService */
                $orderService = app()->make(StoreOrderServices::class);
                /** @var StoreOrderRefundServices $orderRefundService */
                $orderRefundService = app()->make(StoreOrderRefundServices::class);
                $refundeOrder = $orderRefundService->getColumn([
                    ['store_order_id', 'IN', $orderIds],
                    ['refund_type', 'in', [1, 2, 4, 5]],
                    ['is_cancel', '=', 0],
                    ['is_del', '=', 0]
                ], 'id,store_order_id', 'store_order_id');
                foreach ($orderIds as $key => $item) {
                    if (in_array($item, $refundeOrder)) {
                        continue;
                    }
                    $order = $orderService->get($item);
                    try {
                        $orderRefundService->applyRefund((int)$order['id'], (int)$order['uid'], $order, [], 1, (float)$order['pay_price'], $refundData);
                    } catch (\Throwable $e) {

                    }
                    $pinkService->update([['id|k_id', '=', $pinkId]], ['status' => 3]);
                    $pinkService->orderPinkAfterNo($key, $pinkId, false, $order['is_channel']);
                }
            }
        } catch (\Throwable $e) {
            Log::error('拼团超时处理失败，原因：' . $e->getMessage());
        }
        return true;
    }

    /**
     * 创建拼团
     * @param $orderInfo
     * @return bool
     */
    public function createPink($orderInfo)
    {
        if (!$orderInfo) {
            return true;
        }
        try {
            /** @var StorePinkServices $pinkServices */
            $pinkServices = app()->make(StorePinkServices::class);
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);
            $resPink = $pinkServices->createPink($orderServices->tidyOrder($orderInfo, true));//创建拼团
        } catch (\Throwable $e) {
            Log::error('创建拼团失败失败，原因：' . $e->getMessage());
        }
        return true;
    }
}
