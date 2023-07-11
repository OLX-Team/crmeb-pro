<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
namespace app\services\cashier;

use app\services\BaseServices;
use app\services\order\StoreCartServices;
use app\services\order\StoreOrderServices;

/**
 * 收银台订单和挂单Services
 */
class OrderServices extends BaseServices
{
    /**
     * 获取收银订单用户
     * @param int $storeId
     * @param int $cashierId
     * @return array
     */
    public function getOrderUserList(int $storeId, int $cashierId = 0)
    {
        /** @var StoreCartServices $cartServices */
        $cartServices = app()->make(StoreCartServices::class);
        $list1 = $cartServices->getHangOrder($storeId, $cashierId)->order('add_time desc')->select()->toArray();
        foreach ($list1 as &$item) {
            $item['is_check'] = 0;
        }
        $uids = [];
        if ($list1){
            $uids = array_unique(array_column($list1, 'uid'));
        }
		$list2 = [];
//        /** @var StoreOrderServices $orderService */
//        $orderService = app()->make(StoreOrderServices::class);
//        $list2 = $orderService->getOrderHistoryList($storeId, $cashierId, $uids);
//        foreach ($list2 as &$item) {
//            $item['is_check'] = 1;
//        }
        [$microsecond, $sec] = explode(" ", microtime());
        $touristUid = round($microsecond * 10000);
        $list0 = [[
            'is_check' => 1,
            'tourist_uid' => $touristUid,
            'uid' => 0,
            'nickname' => '',
            'avatar' => '',
            'staff_id' => $cashierId,
            'store_id' => $storeId,
            'cart_id' => '',
            'add_time' => time()
        ]];
        return array_merge($list0, $list1, $list2);
    }
}
