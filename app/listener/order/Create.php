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


use app\jobs\activity\StoreBargainJob;
use app\jobs\order\CreateInvoiceJob;
use app\jobs\order\OrderCreateAfterJob;
use app\jobs\order\OrderStatusJob;
use app\jobs\product\ProductLogJob;
use app\jobs\order\UnpaidOrderCancelJob;
use app\jobs\order\UnpaidOrderSend;
use app\jobs\store\StoreUserJob;
use app\jobs\user\UserUpdateJob;
use crmeb\interfaces\ListenerInterface;
use crmeb\services\SystemConfigService;
use crmeb\utils\Arr;

/**
 * 订单创建事件
 * Class Create
 * @package app\listener\order
 */
class Create implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        [$orderInfo, $userInfo, $group, $activity, $invoice_id] = $event;
        $uid = (int)($userInfo['uid'] ?? 0);
        $orderId = (int)$orderInfo['id'];

        //计算订单实际金额
        OrderCreateAfterJob::dispatchDo('compute', [$userInfo, $orderInfo, $group, $activity]);

        //记录自提人电话和姓名
        if ($uid && ($userInfo['real_name'] != $orderInfo['real_name'] || $userInfo['record_phone'] != $orderInfo['user_phone'])) {
            UserUpdateJob::dispatchDo('updateRealName', [$uid, $orderInfo['real_name'], $orderInfo['user_phone']]);
        }
        //创建发票信息
        if ($invoice_id) {
            CreateInvoiceJob::dispatch([$uid, $orderId, (int)$invoice_id]);
        }
        //下单成功修改砍价状态
        if ($activity['type'] == 2 && $activity['activity_id']) {
            StoreBargainJob::dispatchDo('setBargainUserStatus', [$uid, (int)$activity['activity_id']]);
        }

        //设置默认地址，清理购物车
        OrderCreateAfterJob::dispatchDo('delCartAndUpdateAddres', [$orderInfo, $group]);
        //清理订单确认生成缓存
        OrderCreateAfterJob::dispatchDo('delOrderCache', [$uid, $orderInfo['unique']], 120);

        //写入订单记录表
        OrderStatusJob::dispatch([$orderId, $group, $orderInfo['total_price'], $orderInfo['pay_price']]);
        //记录门店用户
        if ($uid && $orderInfo['store_id']) {
            StoreUserJob::dispatch([$uid, $orderInfo['store_id']]);
        }
        //下单记录
        ProductLogJob::dispatch(['order', ['uid' => $uid, 'order_id' => $orderId]]);


        //订单自动取消
        $this->pushJob($orderId, (int)$activity['type']);
    }

    /**
     * 订单自动取消加入延迟消息队列
     * @param int $orderId
     * @param int $type
     * @return mixed
     */
    public function pushJob(int $orderId, int $type)
    {
        //系统预设取消订单时间段
        $keyValue = ['order_cancel_time', 'order_activity_time', 'order_bargain_time', 'order_seckill_time', 'order_pink_time'];
        //获取配置
        $systemValue = SystemConfigService::more($keyValue);
        //格式化数据
        $systemValue = Arr::setValeTime($keyValue, is_array($systemValue) ? $systemValue : []);
        switch ($type) {
            case 1://秒杀
                $secs = $systemValue['order_seckill_time'] ? $systemValue['order_seckill_time'] : $systemValue['order_activity_time'];
                break;
            case 2://砍价
                $secs = $systemValue['order_bargain_time'] ? $systemValue['order_bargain_time'] : $systemValue['order_activity_time'];
                break;
            case 3://拼团
                $secs = $systemValue['order_pink_time'] ? $systemValue['order_pink_time'] : $systemValue['order_activity_time'];
                break;
            default:
                $secs = $systemValue['order_cancel_time'];
                break;
        }
        //未支付10分钟后发送短信
        UnpaidOrderSend::dispatchSece(600, [$orderId]);

        //未支付根据系统设置事件取消订单
        UnpaidOrderCancelJob::dispatchSece((int)($secs * 3600), [$orderId]);
    }
}
