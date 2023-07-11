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

namespace app\services\pay;


use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderSuccessServices;
use think\exception\ValidateException;

/**
 * 线下支付
 * Class OrderOfflineServices
 * @package app\services\pay
 */
class OrderOfflineServices extends BaseServices
{

    /**
     * 线下支付
     * @param int $id
     * @return mixed
     */
    public function orderOffline(int $id)
    {
        /** @var StoreOrderServices $orderSerives */
        $orderSerives = app()->make(StoreOrderServices::class);
        $orderInfo = $orderSerives->get($id);
        if (!$orderInfo) {
            throw new ValidateException('订单不存在');
        }

        if ($orderInfo->paid) {
            throw new ValidateException('订单已支付');
        }
        /** @var StoreOrderSuccessServices $storeOrderSuccessServices */
        $storeOrderSuccessServices = app()->make(StoreOrderSuccessServices::class);
        $storeOrderSuccessServices->paySuccess($orderInfo->toArray(), PayServices::OFFLINE_PAY);
        return true;
    }
}
