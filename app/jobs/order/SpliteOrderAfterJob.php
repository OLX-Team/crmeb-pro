<?php


namespace app\jobs\order;


use app\services\order\StoreOrderStatusServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 拆分订单后置队列
 * Class SpliteOrderAfterJob
 * @package app\jobs\order
 */
class SpliteOrderAfterJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 门店分配订单
     * @param $orderInfo
     * @return bool
     */
    public function doJob($oid, $orderData)
    {
        if (!$oid || !$orderData || count($orderData) != 2) {
            return true;
        }
        [$orderInfo, $otherOrder] = $orderData;
        try {
            /** @var StoreOrderStatusServices $statusService */
            $statusService = app()->make(StoreOrderStatusServices::class);
            $statusData = $statusService->getColumn(['oid' => $oid], '*');
            if (!$statusData) {
                return true;
            }
            $ids = [];
            if ($orderInfo['id'] != $oid) {
                $ids[] = $orderInfo['id'];
            }
            if ($otherOrder['id'] != $oid) {
                $ids[] = $otherOrder['id'];
            }
            if ($ids) {
                $allData = [];
                foreach ($ids as $id) {
                    foreach ($statusData as $data) {
                        $data['oid'] = $id;
                        $allData[] = $data;
                    }
                }
                if ($allData) {
                    $statusService->saveAll($allData);
                }
            }

        } catch (\Throwable $e) {
            Log::error('处理拆分订单记录失败，原因：' . $e->getMessage());
        }
        return true;
    }


}