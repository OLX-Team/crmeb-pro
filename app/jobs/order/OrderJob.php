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

use app\services\activity\discounts\StoreDiscountsServices;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\message\service\StoreServiceServices;
use app\services\message\sms\SmsSendServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderEconomizeServices;
use app\services\order\StoreOrderServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\product\StoreProductServices;
use app\services\user\member\MemberCardServices;
use app\services\user\label\UserLabelRelationServices;
use app\services\user\level\UserLevelServices;
use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use app\webscoket\SocketPush;
use crmeb\basic\BaseJobs;
use crmeb\services\wechat\Messages;
use crmeb\services\wechat\OfficialAccount;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 订单消息队列
 * Class OrderJob
 * @package app\jobs
 */
class OrderJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 执行订单支付成功发送消息
     * @param $order
     * @return bool
     */
    public function otherTake($order)
    {
        //检测会员等级
        if ($order['uid']) {
            try {
                /** @var UserLevelServices $levelServices */
                $levelServices = app()->make(UserLevelServices::class);
                $levelServices->detection((int)$order['uid']);
            } catch (\Throwable $e) {
                Log::error('会员等级升级失败,失败原因:' . $e->getMessage());
            }
        }

        if ($order['store_id']) {
            //向门店后台发送新订单消息
            try {
                SocketPush::store()->to($order['store_id'])->type('NEW_ORDER')->data(['order_id' => $order['order_id']])->push();
            } catch (\Throwable $e) {
                Log::error('向后台发送新订单消息失败,失败原因:' . $e->getMessage());
            }
        } else {
            //向后台发送新订单消息
            try {
                SocketPush::admin()->type('NEW_ORDER')->data(['order_id' => $order['order_id']])->push();
            } catch (\Throwable $e) {
                Log::error('向后台发送新订单消息失败,失败原因:' . $e->getMessage());
            }
        }

        return true;
    }

    /**
     * 设置用户购买次数和检测时候成为推广人
     * @param $order
     */
    public function setUserPayCountAndPromoter($order)
    {
        try {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->get($order['uid']);
            if ($userInfo) {
                $userInfo->pay_count = $userInfo->pay_count + 1;
                if (!$userInfo->is_promoter) {
                    /** @var StoreOrderServices $orderServices */
                    $orderServices = app()->make(StoreOrderServices::class);
                    $price = $orderServices->sum(['pid' => 0, 'paid' => 1, 'refund_status' => 0, 'uid' => $userInfo['uid']], 'pay_price');
                    $status = is_brokerage_statu($price);
                    if ($status) {
                        $userInfo->is_promoter = 1;
                    }
                }
                $userInfo->save();
            }
        } catch (\Throwable $e) {
            Log::error('更新用户订单数失败,失败原因:' . $e->getMessage());
        }
        return true;
    }

    /**
     * 设置用户购买的标签
     * @param $order
     */
    public function setUserLabel($order)
    {
        try {
            /** @var StoreOrderCartInfoServices $cartInfoServices */
            $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
            $cartInfos = $cartInfoServices->getCartColunm(['oid' => $order['id']], 'cart_info');
            $cartInfo = [];
            foreach ($cartInfos as $cart) {
                $cartInfo[] = is_string($cart) ? json_decode($cart, true) : $cart;
            }
            $productIds = array_unique(array_column($cartInfo, 'product_id'));
            if (isset($order['store_id']) && $order['store_id']) {
                /** @var StoreBranchProductServices $branchProductServices */
                $branchProductServices = app()->make(StoreBranchProductServices::class);
                $label = $branchProductServices->getColumn([['product_id', 'in', $productIds], ['store_id', '=', $order['store_id']]], 'label_id');
            } else {
                /** @var StoreProductServices $productServices */
                $productServices = app()->make(StoreProductServices::class);
                $label = $productServices->getColumn([['id', 'in', $productIds]], 'label_id');
            }

            $labelIds = [];
            if ($label) {
                $labelIds = explode(',', implode(',', $label));
            }
            if ($order['type'] == 5 && $order['activity_id']) {
                /** @var StoreDiscountsServices $storeDiscountsServices */
                $storeDiscountsServices = app()->make(StoreDiscountsServices::class);
                $discounts_label = $storeDiscountsServices->value(['id' => $order['activity_id']], 'link_ids');
                if ($discounts_label) {
                    $labelIds = array_merge($labelIds, explode(',', $discounts_label));
                }
            }
            if (!$labelIds) {
                return true;
            }
            $labelIds = array_unique($labelIds);
            /** @var UserLabelRelationServices $labelServices */
            $labelServices = app()->make(UserLabelRelationServices::class);
            $where = [
                ['label_id', 'in', $labelIds],
                ['uid', '=', $order['uid']],
                ['store_id', '=', $order['store_id'] ?? 0]
            ];
            $data = [];
            $userLabel = $labelServices->getColumn($where, 'label_id');
            foreach ($labelIds as $item) {
                if (!in_array($item, $userLabel)) {
                    $data[] = ['uid' => $order['uid'], 'label_id' => $item, 'store_id' => $order['store_id'] ?? 0];
                }
            }
            $re = true;
            if ($data) {
                $re = $labelServices->saveAll($data);
            }
        } catch (\Throwable $e) {
            Log::error('用户标签添加失败,失败原因:' . $e->getMessage());
        }
        return $re;
    }

    /**
     * 发送模板消息和客服消息
     * @param $order
     * @return bool
     */
    public function sendServicesAndTemplate($order)
    {
        try {
            if (in_array($order['is_channel'], [1, 2])) {//小程序发送模板消息
                //订单支付成功后给客服发送客服消息
                $this->sendOrderPaySuccessCustomerService($order, 0);
            } else {//公众号发送模板消息
                //订单支付成功后给客服发送客服消息
                $this->sendOrderPaySuccessCustomerService($order, 1);
            }
        } catch (\Exception $e) {
            Log::error('发送客服消息,短信消息失败,失败原因:' . $e->getMessage());
        }
        return true;
    }

    /**
     * 订单支付成功后给客服发送客服消息
     * @param $order
     * @param int $type 1 公众号 0 小程序
     * @return string
     */
    public function sendOrderPaySuccessCustomerService($order, $type = 0)
    {
        /** @var StoreServiceServices $services */
        $services = app()->make(StoreServiceServices::class);
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $serviceOrderNotice = $services->getStoreServiceOrderNotice();
        if (count($serviceOrderNotice)) {
            /** @var StoreProductServices $services */
            $services = app()->make(StoreProductServices::class);
            /** @var StoreSeckillServices $seckillServices */
            $seckillServices = app()->make(StoreSeckillServices::class);
            /** @var StoreCombinationServices $pinkServices */
            $pinkServices = app()->make(StoreCombinationServices::class);
            /** @var StoreBargainServices $bargainServices */
            $bargainServices = app()->make(StoreBargainServices::class);
            /** @var StoreOrderCartInfoServices $cartInfoServices */
            $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
            /** @var SmsSendServices $smsServices */
//            $smsServices = app()->make(SmsSendServices::class);
//            $switch = sys_config('admin_pay_success_switch') ? true : false;

            $description = '';
            $image = sys_config('site_logo');
            switch ($order['type']) {
                case 1://秒杀
                    $description .= '秒杀商品：' . $seckillServices->value(['id' => $order['activity_id']], 'title');
                    $image = $seckillServices->value(['id' => $order['activity_id']], 'image');
                    break;
                case 2://砍价
                    $description .= '砍价商品：' . $bargainServices->value(['id' => $order['activity_id']], 'title');
                    $image = $bargainServices->value(['id' => $order['activity_id']], 'image');
                    break;
                case 3://拼团
                    $description .= '拼团商品：' . $pinkServices->value(['id' => $order['activity_id']], 'title');
                    $image = $pinkServices->value(['id' => $order['activity_id']], 'image');
                    break;
                default:
                    $productIds = $cartInfoServices->getCartIdsProduct((int)$order['id'], $order['cart_id']);
                    $storeProduct = $services->getProductArray([['id', 'in', $productIds]], 'image,store_name', 'id');
                    if (count($storeProduct)) {
                        foreach ($storeProduct as $value) {
                            $description .= $value['store_name'] . '  ';
                            $image = $value['image'];
                        }
                    }
                    break;
            }
            foreach ($serviceOrderNotice as $key => $item) {
                $userInfo = $wechatUserServices->getOne(['uid' => $item['uid'], 'user_type' => 'wechat']);
                if ($userInfo) {
                    $userInfo = $userInfo->toArray();
                    if ($userInfo['subscribe'] && $userInfo['openid']) {
                        if ($item['customer']) {
                            // 统计管理开启  推送图文消息
                            $head = '订单提醒 订单号：' . $order['order_id'];
                            $url = sys_config('site_url') . '/pages/admin/orderDetail/index?id=' . $order['order_id'];

                            $message = Messages::newsMessage($head, $description, $url, $image);
                            try {
                                OfficialAccount::staffService()->message($message)->to($userInfo['openid'])->send();
                            } catch (\Exception $e) {
                                Log::error($userInfo['nickname'] . '发送失败' . $e->getMessage());
                            }
                        } else {
                            // 推送文字消息
                            $head = "客服提醒：亲,您有一个新订单 \r\n订单单号:{$order['order_id']}\r\n支付金额：￥{$order['pay_price']}\r\n备注信息：{$order['mark']}\r\n订单来源：小程序";
                            if ($type) $head = "客服提醒：亲,您有一个新订单 \r\n订单单号:{$order['order_id']}\r\n支付金额：￥{$order['pay_price']}\r\n备注信息：{$order['mark']}\r\n订单来源：公众号";
                            try {
                                OfficialAccount::staffService()->message($head)->to($userInfo['openid'])->send();
                            } catch (\Exception $e) {
                                Log::error($userInfo['nickname'] . '发送失败' . $e->getMessage());
                            }
                        }
                    }
                }

            }
        }
        return true;
    }

    /**
     *  支付成功短信提醒
     * @param string $order_id
     */
    public function mssageSendPaySuccess($order)
    {
        $switch = sys_config('lower_order_switch') ? true : false;
        //模板变量
        $pay_price = $order['pay_price'];
        $order_id = $order['order_id'];
        /** @var SmsSendServices $smsServices */
        $smsServices = app()->make(SmsSendServices::class);
        $smsServices->send($switch, $order['user_phone'], compact('order_id', 'pay_price'), 'PAY_SUCCESS_CODE');
    }

    /**
     * 计算节约金额
     * @param $order
     */
    public function setEconomizeMoney($order)
    {
        try {
            /** @var UserServices $userService */
            $userService = app()->make(UserServices::class);
            /** @var StoreOrderCartInfoServices $cartInfoService */
            $cartInfoService = app()->make(StoreOrderCartInfoServices::class);
            /** @var StoreCouponUserServices $couponService */
            $couponService = app()->make(StoreCouponUserServices::class);
            /** @var StoreOrderEconomizeServices $economizeService */
            $economizeService = app()->make(StoreOrderEconomizeServices::class);
            /** @var MemberCardServices $memberCardService */
            $memberCardService = app()->make(MemberCardServices::class);
            $getOne = $economizeService->getOne(['order_id' => $order['order_id']]);
            if ($getOne) return false;
            //看是否是会员
            $userInfo = $userService->getUserInfo($order['uid']);
            if ($userInfo && $userInfo['is_money_level'] > 0) {
                $save = [];
                $save['order_type'] = 1;
                $save['add_time'] = time();
                $save['pay_price'] = $order['pay_price'];
                $save['order_id'] = $order['order_id'];
                $save['uid'] = $order['uid'];
                //计算商品节约金额
                $isOpenVipPrice = $memberCardService->isOpenMemberCardCache('vip_price');
                if ($isOpenVipPrice) {
                    $cartInfo = $cartInfoService->getOrderCartInfo($order['id']);
                    $memberPrice = 0.00;
                    if ($cartInfo) {
                        foreach ($cartInfo as $k => $item) {
                            foreach ($item as $value) {
                                if (isset($value['price_type']) && $value['price_type'] == 'member') $memberPrice = bcadd((string)$memberPrice, (string)bcmul((string)$value['vip_truePrice'], (string)$value['cart_num'] ?: '1', 2), 2);
                            }
                        }
                    }
                    $save['member_price'] = $memberPrice;
                }
                //计算邮费节约金额
                $isOpenExpress = $memberCardService->isOpenMemberCardCache('express');
                if ($isOpenExpress) {
                    $expressTotalMoney = bcdiv($order['total_postage'], bcdiv($isOpenExpress, 100, 2), 2);
                    $save['postage_price'] = bcsub($expressTotalMoney, $order['total_postage'], 2);
                }
                //计算会员券节省金额
                if ($order['coupon_id'] && $order['coupon_price']) {
                    $coupon = $couponService->getOne(['id' => $order['coupon_id']], '*', ['issue']);
                    //是会员券
                    if ($coupon && $coupon['receive_type'] == 4) {
                        $save['coupon_price'] = $order['coupon_price'];
                    }
                }
                return $economizeService->addEconomize($save);
            }
        } catch (\Throwable $e) {
            Log::error('计算节省金额,失败原因:' . $e->getMessage());
        }
        return true;
    }
}
