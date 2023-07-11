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
namespace app\listener\order;

use app\jobs\store\StoreFinanceJob;
use app\jobs\system\CapitalFlowJob;
use app\services\order\StoreOrderInvoiceServices;
use app\services\user\UserServices;
use crmeb\interfaces\ListenerInterface;

/**
 * 订单退款事件
 * Class PriceRevision
 * @package app\listener\order
 */
class Refund implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        [$data, $order] = $event;

        //修改开票数据退款状态
        $orderInvoiceServices = app()->make(StoreOrderInvoiceServices::class);
        $orderInvoiceServices->update(['order_id' => $order['id']], ['is_refund' => 1]);

        //门店退款流水
        StoreFinanceJob::dispatch([$order, 4, $data['refund_price'] ?? 0.00]);

        //退款写入资金流水
        if ($order['pay_type'] == 'wechat' || $order['pay_type'] == 'alipay') {
            $userInfo = app()->make(UserServices::class)->get($order['uid']);
            //记录资金流水队列
            CapitalFlowJob::dispatch([[
                'order_id' => $order['order_id'],
                'store_id' => $order['store_id'],
                'uid' => $order['uid'],
                'nickname' => $userInfo['nickname'],
                'phone' => $userInfo['phone'],
                'price' => $data['refund_price'],
                'pay_type' => $order['pay_type'],
                'add_time' => time(),
            ], 'refund']);
        }

        //订单退款消息推送
        event('notice.notice', [['data' => $data, 'order' => $order], 'order_refund']);
    }
}
