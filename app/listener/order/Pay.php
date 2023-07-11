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


use app\jobs\agent\AgentJob;
use app\jobs\order\OrderCreateAfterJob;
use app\jobs\order\OrderDeliveryJob;
use app\jobs\order\OrderJob;
use app\jobs\order\OrderSendCardJob;
use app\jobs\order\OrderSyncJob;
use app\jobs\order\OrderTakeJob;
use app\jobs\pink\PinkJob;
use app\jobs\product\ProductCouponJob;
use app\jobs\product\ProductLogJob;
use app\jobs\order\SpliteStoreOrderJob;
use app\jobs\activity\StorePromotionsJob;
use app\jobs\system\CapitalFlowJob;
use app\services\order\StoreOrderInvoiceServices;
use app\services\order\StoreOrderStatusServices;
use crmeb\interfaces\ListenerInterface;

/**
 * 订单支付事件
 * Class Pay
 * @package app\listener\order
 */
class Pay implements ListenerInterface
{
    public function handle($event): void
    {
        [$orderInfo, $userInfo] = $event;

        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        $statusService->save([
            'oid' => $orderInfo['id'],
            'change_type' => 'pay_success',
            'change_message' => '用户付款成功',
            'change_time' => time()
        ]);
        //创建拼团
        if ($orderInfo['type'] == 3 && $orderInfo['activity_id'] && !$orderInfo['refund_status']) {
            PinkJob::dispatchDo('createPink', [$orderInfo]);
        }
        //门店自动分配订单
        SpliteStoreOrderJob::dispatch([$orderInfo]);
        //门店虚拟用户
        if ($orderInfo['uid']) {
            //赠送商品关联优惠卷
            ProductCouponJob::dispatch([$orderInfo]);
            //修改开票数据支付状态
            $orderInvoiceServices = app()->make(StoreOrderInvoiceServices::class);
            $orderInvoiceServices->update(['order_id' => $orderInfo['id']], ['is_pay' => 1]);
            //支付成功后计算商品节省金额
            OrderJob::dispatchDo('setEconomizeMoney', [$orderInfo]);
            //支付成功处理自己、上级分销等级升级
            AgentJob::dispatch([(int)$orderInfo['uid']]);
            //支付成功后更新用户支付订单数量
            OrderJob::dispatchDo('setUserPayCountAndPromoter', [$orderInfo]);
            //发送卡密
            if ($orderInfo['product_type']) {
                OrderSendCardJob::dispatch([$orderInfo]);
            }
            //优惠活动赠送优惠卷
            StorePromotionsJob::dispatchDo('give', [$orderInfo]);
            //优惠活动关联用户标签设置
            StorePromotionsJob::dispatchDo('setUserLabel', [$orderInfo]);
        }
		if ($orderInfo['shipping_type'] == 4) {
            //订单发货
            OrderDeliveryJob::dispatch([$orderInfo, [], 4]);
            //订单收货
            OrderTakeJob::dispatchSece(60, [$orderInfo]);
			//清理购物车
			$cartIds = [];
			if (isset($orderInfo['cart_id']) && $orderInfo['cart_id']) {
				$cartIds = is_string($orderInfo['cart_id']) ? json_decode($orderInfo['cart_id'], true) : $orderInfo['cart_id'];
			}
        	OrderCreateAfterJob::dispatchDo('delCartAndUpdateAddres', [$orderInfo, ['cartIds' => $cartIds, 'delCart' => true]]);
        }
        //支付成功后其他事件处理
        OrderJob::dispatchDo('otherTake', [$orderInfo]);
        //支付成功后向管理员发送模板消息
        OrderJob::dispatchDo('sendServicesAndTemplate', [$orderInfo]);
        //支付记录
        ProductLogJob::dispatch(['pay', ['uid' => $orderInfo['uid'], 'order_id' => $orderInfo['id']]]);
        //记录资金流水队列
        if (in_array($orderInfo['pay_type'], ['weixin', 'alipay', 'offline'])) {
            CapitalFlowJob::dispatch([[
                'order_id' => $orderInfo['order_id'],
                'store_id' => $orderInfo['store_id'] ?? 0,
                'uid' => $orderInfo['uid'] ?? 0,
                'nickname' => $userInfo['nickname'] ?? '游客' . time(),
                'phone' => $userInfo['phone'] ?? '',
                'price' => $orderInfo['pay_price'],
                'pay_type' => $orderInfo['pay_type'],
                'add_time' => time(),
            ], 'order']);
        }
        //支付成功给客服发送消息
        event('notice.notice', [$orderInfo, 'admin_pay_success_code']);
        // 同步订单
        if (sys_config('erp_open')) {
            OrderSyncJob::dispatchDo('syncOrder', [(int)$orderInfo['id']]);
        }
    }
}
