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
use crmeb\interfaces\ListenerInterface;

/**
 * 用户充值事件
 * Class Recharge
 * @package app\listener\user
 */
class Recharge implements ListenerInterface
{
    /**
     * 用户充值事件
     * @param $event
     */
    public function handle($event): void
    {
        [$order, $now_money] = $event;

        CapitalFlowJob::dispatch([[
            'order_id' => $order['order_id'],
            'store_id' => $order['store_id'] ?? 0,
            'uid' => $order['uid'],
            'nickname' => $order['nickname'],
            'phone' => $order['phone'],
            'price' => $order['price'],
            'pay_type' => $order['recharge_type'] ?? 'weixin',
            'add_time' => time(),
        ], 'recharge']);
        //提醒推送
        event('notice.notice', [['order' => $order, 'now_money' => $now_money], 'recharge_success']);

    }
}
