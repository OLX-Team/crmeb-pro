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


use app\services\order\StoreOrderStatusServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * Class OrderStatusJob
 * @package app\jobs\order
 */
class OrderStatusJob extends BaseJobs
{

    use QueueTrait;

    public function doJob($orderId, $group, $totalPrice, $payPrice)
    {
        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        $statusService->save([
            'oid' => $orderId,
            'change_type' => 'cache_key_create_order',
            'change_message' => '订单生成',
            'change_time' => time()
        ]);
        if (isset($group['changePrice']) && $group['changePrice'] > 0) {
            $totalPrice = $group['priceData']['pay_price'] ?? $totalPrice;
            $statusService->save([
                'oid' => $orderId,
                'change_type' => 'order_edit',
                'change_time' => time(),
                'change_message' => '商品总价为：' . $totalPrice . ' 修改实际支付金额为：' . $payPrice
            ]);
        }

        return true;
    }
}
