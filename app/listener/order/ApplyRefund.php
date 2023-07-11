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

use app\jobs\order\OrderSyncJob;
use app\webscoket\SocketPush;
use crmeb\interfaces\ListenerInterface;

/**
 * 订单申请退款事件
 * Class ApplyRefund
 * @package app\listener\order
 */
class ApplyRefund implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        [$order, $refundId, $sync] = $event;

        if ($order['store_id']) {
            //向门店后台发送退款订单消息
            try {
                SocketPush::store()->to($order['store_id'])->data(['order_id' => $order['order_id']])->type('NEW_REFUND_ORDER')->push();
            } catch (\Exception $e) {
            }
        } else {
            //向后台发送退款订单消息
            try {
                SocketPush::admin()->data(['order_id' => $order['order_id']])->type('NEW_REFUND_ORDER')->push();
            } catch (\Exception $e) {
            }
        }

        //退款消息推送
        event('notice.notice', [['order' => $order], 'send_order_apply_refund']);

        //ERP功能开启 同步退款单
        if (sys_config('erp_open') && $sync) {
            OrderSyncJob::dispatchDo('refundOrderUpload', [$refundId]);
        }
    }
}
