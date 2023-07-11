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
 * 订单改价
 * Class PriceRevision
 * @package app\listener\order
 */
class PriceRevision implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        [$order, $pay_price] = $event;
        //消息推送
        event('notice.notice', [['order' => $order, 'pay_price' => $pay_price], 'price_revision']);


    }
}
