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

namespace app\jobs\order;


use app\services\message\SystemMessageServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderStatusServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\product\sku\StoreProductVirtualServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 支付成功自动发送卡密
 * Class OrderSendCardJob
 * @package app\jobs\order
 */
class OrderSendCardJob extends BaseJobs
{
    use QueueTrait;

    /**
     * @param $orderInfo
     * @return bool
     */
    public function doJob($orderInfo)
    {
        if (!$orderInfo) {
            return true;
        }
        //待发货状态
        if ($orderInfo['status'] != 0) {
            return true;
        }
        /** @var StoreOrderServices $orderService */
        $orderService = app()->make(StoreOrderServices::class);
        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        /** @var StoreOrderCartInfoServices $services */
        $services = app()->make(StoreOrderCartInfoServices::class);
        $orderInfo['cart_info'] = $services->getOrderCartInfo((int)$orderInfo['id']);
        /** @var SystemMessageServices $SystemMessageServices */
        $SystemMessageServices = app()->make(SystemMessageServices::class);
        try {

            switch ($orderInfo['product_type']) {
                case 1:
                    $title = $content = $disk_info = $virtual_info = '';
                    $disk_info = $orderInfo['cart_info'][$orderInfo['cart_id'][0] ?? 0]['cart_info']['productInfo']['attrInfo']['disk_info'] ?? '';
                    $unique = $orderInfo['cart_info'][$orderInfo['cart_id'][0]]['cart_info']['productInfo']['attrInfo']['unique'];
                    //活动订单共用原商品规格卡密
                    if (in_array($orderInfo['type'], [1, 2, 3, 5]) && $orderInfo['activity_id']) {
                        /** @var StoreProductAttrValueServices $skuValueServices */
                        $skuValueServices = app()->make(StoreProductAttrValueServices::class);
                        $attrValue = $skuValueServices->getUniqueByActivityUnique($unique, (int)$orderInfo['activity_id'], (int)$orderInfo['type'], ['unique', 'disk_info']);
                        if ($attrValue) {
                            $disk_info = $attrValue['disk_info'] ?? '';
                            $unique = $attrValue['unique'] ?? '';
                        }
                    }
                    if ($disk_info) {
                        $title = '虚拟密钥发放';
                        $content = '您购买的密钥商品已支付成功，支付金额' . $orderInfo['pay_price'] . '元，订单号：' . $orderInfo['order_id'] . '，密钥：' . $disk_info . '，感谢您的光临！';
                        $virtual_info = '密钥自动发放：' . $disk_info;
//                        $remark = '密钥自动发放：' . $disk_info;
                    } else {
                        /** @var StoreProductVirtualServices $virtualService */
                        $virtualService = app()->make(StoreProductVirtualServices::class);
                        $cardList = $virtualService->getOrderCardList(['store_id' => $orderInfo['store_id'], 'attr_unique' => $unique, 'uid' => 0], (int)$orderInfo['total_num']);
                        $title = '虚拟卡密发放';
                        $virtual_info = '卡密已自动发放，';
//                        $remark = '卡密已自动发放';
                        if ($cardList) {
                            $content = '您购买的卡密商品已支付成功，支付金额' . $orderInfo['pay_price'] . '元，订单号：' . $orderInfo['order_id'] . ',';
                            $update = [];
                            $update['order_id'] = $orderInfo['order_id'];
                            $update['uid'] = $orderInfo['uid'];
                            foreach ($cardList as $virtual) {
                                $virtualService->update($virtual['id'], $update);
                                $content .= '卡号：' . $virtual['card_no'] . '；密码：' . $virtual['card_pwd'] . "\n";
                                $virtual_info .= '卡号：' . $virtual['card_no'] . '；密码：' . $virtual['card_pwd'] . ';';
//                                $remark .= '，卡号：' . $virtual['card_no'] . '；密码：' . $virtual['card_pwd'] . ';';
                            }
                            $content .= '，感谢您的光临！';
                        }
                    }
                    //修改订单虚拟备注
                    $orderService->update(['id' => $orderInfo['id']], ['status' => 1, 'delivery_type' => 'fictitious', 'virtual_info' => $virtual_info]);
                    //发送站内行
                    $SystemMessageServices->systemSend($orderInfo['uid'], [
                        'mark' => 'virtual_info',
                        'title' => $title,
                        'content' => $content
                    ]);
                    $statusService->save([
                        'oid' => $orderInfo['id'],
                        'change_type' => 'delivery_fictitious',
                        'change_message' => '卡密自动发货',
                        'change_time' => time()
                    ]);
                    break;
            }
        } catch (\Throwable $e) {
            Log::error('订单虚拟商品自动发放失败，原因：' . $e->getMessage());
        }
        return true;
    }

}
