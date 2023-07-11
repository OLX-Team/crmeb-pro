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

use crmeb\interfaces\ListenerInterface;

/**
 * 订单拒绝申请退款事件
 * Class PriceRevision
 * @package app\listener\order
 */
class RefuseRefund implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        [$orderInfo] = $event;
        //消息推送
        event('notice.notice', [['orderInfo' => $orderInfo], 'send_order_refund_no_status']);



    }
}
