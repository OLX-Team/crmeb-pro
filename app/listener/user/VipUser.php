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

namespace app\listener\user;

use app\jobs\system\CapitalFlowJob;
use app\services\user\UserServices;
use crmeb\interfaces\ListenerInterface;
use app\jobs\order\OtherOrderJob;

/**
 * 购买付费会员
 * Class Register
 * @package app\listener\user
 */
class VipUser implements ListenerInterface
{
    /**
     * 购买付费会员
     * @param $event
     */
    public function handle($event): void
    {
        [$orderInfo] = $event;
        //支付成功后发送消息
        OtherOrderJob::dispatch([$orderInfo]);

        //记录资金流水队列
        if ($orderInfo['pay_type'] != 'yue') {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->get($orderInfo['uid']);
            CapitalFlowJob::dispatch([[
                'order_id' => $orderInfo['order_id'],
                'store_id' => 0,
                'uid' => $orderInfo['uid'],
                'nickname' => $userInfo['nickname'],
                'phone' => $userInfo['phone'],
                'price' => $orderInfo['pay_price'],
                'pay_type' => $orderInfo['pay_type'],
                'add_time' => time(),
            ], 'pay_member']);
        }

        //用户推送消息事件
        event('notice.notice', [$orderInfo, 'order_pay_success']);
        //支付成功给客服发送消息
        event('notice.notice', [$orderInfo, 'admin_pay_success_code']);

    }
}
