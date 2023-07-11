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

use app\jobs\activity\StorePromotionsJob;
use app\services\order\StoreOrderInvoiceServices;
use crmeb\interfaces\ListenerInterface;

/**
 * 订单取消删除事件
 * Class Cancel
 * @package app\listener\order
 */
class Cancel implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
		[$order] = $event;
		//回退优惠活动赠品限量
        StorePromotionsJob::dispatchDo('changeGiveLimit', [is_string($order['promotions_give']) ? json_decode($order['promotions_give'], true) : $order['promotions_give'], false]);

		//同步删除开票记录
		/** @var StoreOrderInvoiceServices $storeOrderInvoiceServices */
		$storeOrderInvoiceServices = app()->make(StoreOrderInvoiceServices::class);
		$storeOrderInvoiceServices->update(['order_id' => $order['id']], ['is_del' => 1]);
    }
}
