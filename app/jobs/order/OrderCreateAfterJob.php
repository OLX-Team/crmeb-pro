<?php


namespace app\jobs\order;


use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderComputedServices;
use app\services\user\UserServices;
use crmeb\basic\BaseJobs;
use crmeb\services\CacheService;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 订单创建
 * Class OrderCreateAfterJob
 * @package app\jobs
 */
class OrderCreateAfterJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 清理订单确认生成缓存
     * @param int $uid
     * @param string $unique
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function delOrderCache(int $uid, string $unique)
    {
        CacheService::redisHandler()->delete('user_order_' . $uid . $unique);
        return true;
    }

    /**
     * 删除购物车和更新用户收货地址
     * @return bool
     */
    public function delCartAndUpdateAddres($orderInfo, $group)
    {
        try {
            /** @var StoreOrderCreateServices $orderCreate */
            $orderCreate = app()->make(StoreOrderCreateServices::class);
            $orderCreate->orderCreateAfter($orderInfo, $group);
        } catch (\Throwable $e) {
            Log::error('更新用户信息和删除购物车失败,失败原因:' . $e->getMessage());
        }
        return true;
    }

    /**
     * 订单后置处理
     * @param $userInfo
     * @param $orderInfo
     * @param $data
     * @param $activity
     * @return bool
     */
    public function compute($userInfo, $orderInfo, $data, $activity)
    {
        if (!$userInfo || !$orderInfo || $userInfo['uid'] != $orderInfo['uid']) {
            return true;
        }
        $uid = (int)$orderInfo['uid'];
        $orderId = (int)$orderInfo['id'];
        try {
            $spread_uid = $spread_two_uid = 0;
            $cartInfo = $data['cartInfo'] ?? [];
            $priceData = $data['priceData'] ?? [];
            $addressId = $data['addressId'] ?? 0;
            /** @var StoreOrderCreateServices $createService */
            $createService = app()->make(StoreOrderCreateServices::class);
            $spread_ids = [];
            if ($cartInfo && $priceData) {
                /** @var StoreOrderCartInfoServices $cartServices */
                $cartServices = app()->make(StoreOrderCartInfoServices::class);
                [$cartInfo, $spread_ids] = $createService->computeOrderProductTruePrice($orderInfo, $cartInfo, $priceData, $addressId, $uid, $userInfo);
                $cartServices->updateCartInfo($orderId, $cartInfo);
            }
            $orderData = [];
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            if ($spread_ids) {
                [$spread_uid, $spread_two_uid] = $spread_ids;
                $orderData['spread_uid'] = $spread_uid;
                $orderData['spread_two_uid'] = $spread_two_uid;
            } else {
                $spread_uid = $userServices->getSpreadUid($uid);
                if ($spread_uid) {
                    $orderData['spread_uid'] = $spread_uid;
                }
                if ($spread_uid > 0) {
                    $spread_two_uid = $userServices->getSpreadUid($spread_uid, [], false);
                    if ($spread_two_uid) {
                        $orderData['spread_two_uid'] = $spread_two_uid;
                    }
                }
            }
            if ($cartInfo && (isset($activity['type']) && $activity['type'] == 0)) {
                /** @var StoreOrderComputedServices $orderComputed */
                $orderComputed = app()->make(StoreOrderComputedServices::class);
                if ($userServices->checkUserPromoter($spread_uid)) $orderData['one_brokerage'] = $orderComputed->getOrderSumPrice($cartInfo, 'one_brokerage', false);
                if ($userServices->checkUserPromoter($spread_two_uid)) $orderData['two_brokerage'] = $orderComputed->getOrderSumPrice($cartInfo, 'two_brokerage', false);
            }
            if ($orderData) $createService->update(['id' => $orderId], $orderData);
        } catch (\Throwable $e) {
            Log::error('计算订单实际优惠、积分、邮费、佣金失败，原因：' . $e->getMessage());
        }

        return true;
    }
}
