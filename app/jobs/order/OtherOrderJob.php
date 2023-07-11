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

use app\services\message\sms\SmsSendServices;
use app\services\order\OtherOrderServices;
use app\services\order\StoreOrderEconomizeServices;
use app\services\user\member\MemberCardServices;
use app\services\user\UserBillServices;
use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 订单消息队列
 * Class OtherOrderJob
 * @package app\jobs
 */
class OtherOrderJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 执行订单支付成功发送消息
     * @param $order
     * @return bool
     */
    public function doJob($order)
    {
        //更新用户支付订单数量
        try {
            $this->setUserPayCountAndPromoter($order);
        } catch (\Throwable $e) {
            Log::error('更新用户订单数失败,失败原因:' . $e->getMessage());
        }

        // 计算用户节省金额
        try {
            $this->setEconomizeMoney($order);
        } catch (\Throwable $e) {
            Log::error('计算节省金额,失败原因:' . $e->getMessage());
        }
        try {
            $this->sendMemberIntegral($order);
        } catch (\Throwable $e) {
            Log::error('消费积分返还失败,失败原因:' . $e->getMessage());
        }

        return true;
    }

    /**
     * 设置用户购买次数和检测时候成为推广人
     * @param $order
     */
    public function setUserPayCountAndPromoter($order)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->get($order['uid']);
        if ($userInfo) {
            if (!$userInfo->is_promoter) {
                /** @var OtherOrderServices $orderServices */
                $orderServices = app()->make(OtherOrderServices::class);
                $price = $orderServices->sum(['paid' => 1, 'uid' => $userInfo['uid']], 'pay_price');
                $status = is_brokerage_statu($price);
                if ($status) {
                    $userInfo->is_promoter = 1;
                }
            }
            $userInfo->save();
        }
    }


    /**
     * 发送模板消息和客服消息
     * @param $order
     * @return bool
     */
    public function sendServicesAndTemplate($order)
    {
        try {
            /** @var WechatUserServices $wechatUserServices */
            $wechatUserServices = app()->make(WechatUserServices::class);
            if ($order['is_channel'] == "wechat") {//公众号发送模板消息
                $openid = $wechatUserServices->uidToOpenid($order['uid'], 'wechat');
                if (!$openid) {
                    return true;
                }
                $wechatTemplate = new WechatTemplateJob();
                $wechatTemplate->sendOrderPaySuccess($openid, $order);
            } else if ($order['is_channel'] == "routine") {//小程序发送模板消息
                $openid = $wechatUserServices->uidToOpenid($order['uid'], 'routine');
                if (!$openid) {
                    return true;
                }
                $tempJob = new RoutineTemplateJob();
                $tempJob->sendMemberOrderSuccess($openid, $order['pay_price'], $order['order_id']);
            }
        } catch (\Exception $e) {
        }

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
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $userPhone = $userService->value(['uid' => $order['uid']], 'phone');
        if ($userPhone) {
            $smsServices->send($switch, $userPhone, compact('order_id', 'pay_price'), 'PAY_SUCCESS_CODE');
        }

    }

    /**
     * 购买会员赠送优惠券
     * @param $uid
     */
    public function setCoupon($uid)
    {
//       /** @var StoreCouponIssueServices $couponService */
//        $couponService = app()->make(StoreCouponIssueServices::class);
//        $couponInfo = $couponService->sendMemberCoupon($uid);
//        if (!$couponInfo)  Log::error('优惠券发送失败,失败原因:没发现会员优惠券或者优惠券数量为0');
    }

    /**
     * 线下付款奖励积分
     * @param $order
     * @return bool
     */
    public function sendMemberIntegral($order)
    {
        //只有线下付款才奖励
        if ($order['type'] == 3) {
            $order_give_integral = sys_config('order_give_integral');
            $order_integral = bcmul($order_give_integral, (string)$order['pay_price'], 0);
            /** @var UserServices $userService */
            $userService = app()->make(UserServices::class);
            $userInfo = $userService->getUserInfo($order['uid']);
            if (!$userInfo) return false;
            if ($userInfo['is_money_level'] > 0) {
                //看是否开启消费返积分翻倍奖励
                /** @var MemberCardServices $memberCardService */
                $memberCardService = app()->make(MemberCardServices::class);
                $integral_rule_number = $memberCardService->isOpenMemberCardCache('integral');
                if ($integral_rule_number) {
                    $order_integral = bcadd($order_integral, $integral_rule_number, 2);
                }
            }
            if ($order_integral > 0) {
                $integral = bcadd(abs($userInfo['integral']), abs($order_integral), 2);
                $userService->update(['uid' => $order['uid']], ['integral' => $integral]);
                /** @var UserBillServices $userBillServices */
                $userBillServices = app()->make(UserBillServices::class);
                $userBillServices->income('order_give_integral', $order['uid'], $order_integral, $userInfo['integral'], $order['id']);
            }
        }
    }

    /**
     * @param $order
     */
    public function setEconomizeMoney($order)
    {
        //只有线下付款才计算节省
        if ($order['type'] == 3) {
            /** @var StoreOrderEconomizeServices $economizeService */
            $economizeService = app()->make(StoreOrderEconomizeServices::class);
            /** @var MemberCardServices $memberRightService */
            $memberRightService = app()->make(MemberCardServices::class);
            $isOpenOfflin = $memberRightService->isOpenMemberCardCache('offline');
            if ($isOpenOfflin) {
                $save = [
                    'uid' => $order['uid'],
                    'order_id' => $order['order_id'],
                    'order_type' => 2,
                    'pay_price' => $order['pay_price'],
                    'offline_price' => bcsub($order['money'], $order['pay_price'], 2)
                ];
                $economizeService->addEconomize($save);
            }

        }
    }

}
