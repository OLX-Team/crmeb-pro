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
use app\services\order\StoreCartServices;

/**
 * 加入购物车事件
 * Class AddCart
 * @package app\listener\order
 */
class AddCart implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        [$uid, $tourist_uid, $store_id, $staff_id] = $event;
		//控制购物车数量
		if ($uid || $tourist_uid) {
			/** @var StoreCartServices $cartServices */
        	$cartServices = app()->make(StoreCartServices::class);
			$cartServices->controlCartNum((int)$uid, (int)$tourist_uid, (int)$store_id, (int)$staff_id);
		}
    }
}
