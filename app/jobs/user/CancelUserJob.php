<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\jobs\user;

use app\services\activity\integral\StoreIntegralOrderServices;
use app\services\message\service\StoreServiceServices;
use app\services\order\OtherOrderServices;
use app\services\order\StoreCartServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\store\DeliveryServiceServices;
use app\services\store\SystemStoreStaffServices;
use app\services\user\UserAddressServices;
use app\services\user\UserFriendsServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 注销用户队列
 */
class CancelUserJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 用户注销队列
     * @param $uid
     * @return bool
     */
    public function doJob($uid)
    {
        try {
            /** @var SystemStoreStaffServices $storeStaffServices */
            $storeStaffServices = app()->make(SystemStoreStaffServices::class);
            /** @var DeliveryServiceServices $deliveryServices */
            $deliveryServices = app()->make(DeliveryServiceServices::class);
            /** @var OtherOrderServices $otherOrderServices */
            $otherOrderServices = app()->make(OtherOrderServices::class);
            /** @var StoreCartServices $storeCartServices */
            $storeCartServices = app()->make(StoreCartServices::class);
            /** @var StoreOrderServices $storeOrderServices */
            $storeOrderServices = app()->make(StoreOrderServices::class);
            /** @var StoreOrderRefundServices $storeOrderRefundServices */
            $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);
            /** @var UserAddressServices $addressServices */
            $addressServices = app()->make(UserAddressServices::class);
            /** @var StoreIntegralOrderServices $integralOrderServices */
            $integralOrderServices = app()->make(StoreIntegralOrderServices::class);
            /** @var StoreServiceServices $storeServiceServices */
            $storeServiceServices = app()->make(StoreServiceServices::class);

            /** @var UserFriendsServices $userService */
            $userService = app()->make(UserFriendsServices::class);
            //删除好友关系记录
            $userService->delete(['uid' => $uid]);

            // 删除核销员，删除配送员，删除客服
            $storeStaffServices->update(['uid' => $uid], ['is_del' => 1]);
            $deliveryServices->update(['uid' => $uid], ['is_del' => 1]);
            $storeServiceServices->update(['uid' => $uid], ['is_del' => 1]);

            // 删除订单，删除退款单，删除其他订单，删除积分订单，删除购物车
            $otherOrderServices->update(['uid' => $uid], ['is_del' => 1]);
            $storeOrderServices->update(['uid' => $uid], ['is_del' => 1]);
            $storeOrderRefundServices->update(['uid' => $uid], ['is_del' => 1]);
            $integralOrderServices->update(['uid' => $uid], ['is_del' => 1]);
            $storeCartServices->delete(['uid' => $uid]);

            // 删除地址
            $addressServices->update(['uid' => $uid], ['is_del' => 1]);

        } catch (\Throwable $e) {
            Log::error('注销用户失败,失败原因:' . $e->getMessage());
        }
        return true;
    }
}
