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
 * 订单评价事件
 * Class PriceRevision
 * @package app\listener\order
 */
class Comment implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        [$data, $order] = $event;
        //订单评价消息推送
        // event('notice.notice', [['data' => $data, 'order' => $order], 'order_refund']);

    }
}
