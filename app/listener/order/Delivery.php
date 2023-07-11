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


use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderStatusServices;
use crmeb\interfaces\ListenerInterface;
use think\facade\Log;


/**
 * 订单发货事件
 * Class Delivery
 * @package app\listener\order
 */
class Delivery implements ListenerInterface
{
    public function handle($event): void
    {
        [$orderInfo, $storeName, $data, $type] = $event;
        switch ($type) {
            case 1://快递发货
                //用户推送消息事件
                event('notice.notice', [['orderInfo' => $orderInfo, 'storeName' => $storeName, 'data' => $data], 'order_postage_success']);
                break;
            case 2://配送
                //用户推送消息事件
                event('notice.notice', [['orderInfo' => $orderInfo, 'storeName' => $storeName, 'data' => $data], 'order_deliver_success']);
                break;
            case 3://虚拟发货
                break;
            case 4://门店收银订单自动收货
                break;
            default:
                Log::error('不支持的发货类型');
        }
		//发子订单 修改主订单发货状态
		if ($orderInfo['pid']) {
			$id = (int)$orderInfo['pid'];
			/** @var StoreOrderServices $order */
			$order = app()->make(StoreOrderServices::class);
			//默认部分发货
            $delivery_data = ['delivery_type' => 'split', 'status' => 4];
            $status_data = ['oid' => $id, 'change_time' => time()];
            //检测原订单商品是否 全部拆分发货完成  改原订单状态
            if (!$order->count(['pid' => $id, 'status' => 1])) {//发货完成
                $delivery_data['status'] = 1;
                $status_data['change_type'] = 'delivery_split';
                $status_data['change_message'] = '已拆分发货';
            } else {
                $status_data['change_type'] = 'delivery_part_split';
                $status_data['change_message'] = '已拆分部分发货';
            }
            //改变原订单状态
            $order->update($id, $delivery_data);
            /** @var StoreOrderStatusServices $services */
            $services = app()->make(StoreOrderStatusServices::class);
            //记录原订单状态
            $services->save($status_data);
		}

    }


}