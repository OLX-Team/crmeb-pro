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

namespace app\services\message\notice;

use app\jobs\template\TemplateJob;
use app\services\message\NoticeService;
use app\services\wechat\WechatUserServices;
use think\facade\Log;


/**
 * 小程序模板消息消息队列
 * Class RoutineTemplateJob
 * @package crmeb\jobs
 */
class RoutineTemplateListService extends NoticeService
{

    /**
     * 判断是否开启权限
     * @var bool
     */
    private $isopend = true;

    /**
     * 是否开启权限
     * @param string $mark
     * @return $this
     */
    public function isOpen(string $mark)
    {
        $this->isopend = $this->notceinfo['is_routine'] === 1 ? true : false;
        return $this;

    }

    /**
     * 根据UID获取openid
     * @param int $uid
     * @return mixed
     */
    public function getOpenidByUid(int $uid)
    {
        /** @var WechatUserServices $wechatServices */
        $wechatServices = app()->make(WechatUserServices::class);
        return $wechatServices->uidToOpenid($uid, 'routine');
    }

    /**
     * 发送模板消息
     * @param string $tempCode 模板消息常量名称
     * @param $uid uid
     * @param array $data 模板内容
     * @param string $link 跳转链接
     * @param string|null $color 文字颜色
     * @return bool|mixed
     */
    public function sendTemplate(string $tempCode, int $uid, array $data, string $link = null, string $color = null)
    {
        try {
            $this->isopend = $this->notceinfo['is_routine'] === 1 ? true : false;
            if ($this->isopend && $uid) {
                $openid = $this->getOpenidByUid($uid);
                //放入队列执行
                TemplateJob::dispatchDo('doJob', ['subscribe', $openid, $tempCode, $data, $link, $color]);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return true;
        }
    }

    /**
     * 确认收货
     * @param $uid
     * @param $order
     * @param $title
     * @return bool|mixed
     */
    public function sendOrderTakeOver($uid, $order, $title)
    {
        return $this->sendTemplate('ORDER_TAKE', $uid, [
            'thing1' => $order['order_id'],
            'thing2' => $title,
            'date5' => date('Y-m-d H:i:s', time()),
        ], '/pages/users/order_details/index?order_id=' . $order['order_id']);
    }

    /**
     * 发货
     * @param $uid
     * @param $order
     * @param $storeTitle
     * @param array $data
     * @param int $isGive 0 = 同城配送， 1 = 快递发货
     * @return bool|mixed
     */
    public function sendOrderPostage($uid, $order, $storeTitle, array $data, int $isGive = 0)
    {
        if ($isGive) {//快递发货
            return $this->sendTemplate('ORDER_DELIVER_SUCCESS', $uid, [
                'character_string2' => $data['delivery_id'] ?? '',
                'thing1' => $data['delivery_name'] ?? '',
                'time3' => date('Y-m-d H:i:s', time()),
                'thing5' => $storeTitle,
            ], '/pages/users/order_details/index?order_id=' . $order['order_id']);
        } else {//同城配送
            return $this->sendTemplate('ORDER_POSTAGE_SUCCESS', $uid, [
                'thing8' => $storeTitle,
                'character_string1' => $order['order_id'],
                'name4' => $data['delivery_name'] ?? '',
                'phone_number10' => $data['delivery_id'] ?? ''
            ], '/pages/users/order_details/index?order_id=' . $order['order_id']);
        }
    }

    /**
     * 充值金额退款
     * @param $uid
     * @param $UserRecharge
     * @param $now_money
     * @return bool|mixed
     */
    public function sendRechargeSuccess($uid, $UserRecharge, $now_money)
    {
        return $this->sendTemplate('RECHARGE_SUCCESS', $uid, [
            'character_string1' => $UserRecharge['order_id'],
            'amount3' => $UserRecharge['price'],
            'amount4' => $now_money,
            'date5' => date('Y-m-d H:i:s', time()),
        ], '/pages/user_bill/index?type=2');
    }

    /**
     * 订单退款成功发送消息
     * @param string $openid
     * @param array $order
     * @return bool
     */
    public function sendOrderRefundSuccess($openid, $order, $storeTitle)
    {
        return $this->sendTemplate('ORDER_REFUND', $openid, [
            'thing1' => '已成功退款',
            'thing2' => $storeTitle,
            'amount3' => $order['pay_price'],
            'character_string6' => $order['order_id']
        ], '/pages/users/order_details/index?order_id=' . $order['order_id'] . '&isReturen=1');
    }

    /**
     * 订单退款失败
     * @param $uid
     * @param $order
     * @param $storeTitle
     * @return bool|mixed
     */
    public function sendOrderRefundFail($uid, $order, $storeTitle)
    {
        return $this->sendTemplate('ORDER_REFUND', $uid, [
            'thing1' => '退款失败',
            'thing2' => $storeTitle,
            'amount3' => $order['pay_price'],
            'character_string6' => $order['order_id']
        ], '/pages/users/order_details/index?order_id=' . $order['order_id'] . '&isReturen=1');
    }

    /**
     * 用户申请退款给管理员发送消息
     * @param $uid
     * @param $order
     * @return bool|mixed
     */
    public function sendOrderRefundStatus($uid, $order)
    {
        $data['character_string4'] = $order['order_id'];
        $data['date5'] = date('Y-m-d H:i:s', time());
        $data['amount2'] = $order['pay_price'];
        $data['phrase7'] = '申请退款中';
        $data['thing8'] = '请及时处理';
        return $this->sendTemplate('ORDER_REFUND_STATUS', $uid, $data);
    }

    /**
     * 砍价成功通知
     * @param $uid
     * @param array $bargain
     * @param array $bargainUser
     * @param int $bargainUserId
     * @return bool|mixed
     */
    public function sendBargainSuccess($uid, $bargain = [], $bargainUser = [], $bargainUserId = 0)
    {
        $data['thing1'] = $bargain['title'];
        $data['amount2'] = $bargain['min_price'];
        $data['thing3'] = '恭喜您，已经砍到最低价了';
        return $this->sendTemplate('BARGAIN_SUCCESS', $uid, $data, '/pages/activity/user_goods_bargain_list/index?id=' . $bargain['id'] . '&bargain=' . $bargainUserId);
    }

    /**
     * 订单支付成功发送模板消息
     * @param $uid
     * @param $pay_price
     * @param $orderId
     * @return bool|mixed
     */
    public function sendOrderSuccess($uid, $pay_price, $orderId)
    {
        if ($orderId == '') return true;
        $data['character_string1'] = $orderId;
        $data['amount2'] = $pay_price . '元';
        $data['date3'] = date('Y-m-d H:i:s', time());
        return $this->sendTemplate('ORDER_PAY_SUCCESS', $uid, $data, '/pages/users/order_details/index?order_id=' . $orderId);
    }

    /**
     * 会员订单支付成功发送消息
     * @param $uid
     * @param $pay_price
     * @param $orderId
     * @return bool|mixed
     */
    public function sendMemberOrderSuccess($uid, $pay_price, $orderId)
    {
        if ($orderId == '') return true;
        $data['character_string1'] = $orderId;
        $data['amount2'] = $pay_price . '元';
        $data['date3'] = date('Y-m-d H:i:s', time());
        return $this->sendTemplate('ORDER_PAY_SUCCESS', $uid, $data, '/pages/annex/vip_paid/index');
    }

    /**
     * 提现失败
     * @param $uid
     * @param $msg
     * @param $extract_number
     * @param $nickname
     * @return bool|mixed
     */
    public function sendExtractFail($uid, $msg, $extract_number, $nickname)
    {
        return $this->sendTemplate('USER_EXTRACT', $uid, [
            'thing1' => '提现失败：' . $msg,
            'amount2' => $extract_number . '元',
            'thing3' => $nickname,
            'date4' => date('Y-m-d H:i:s', time())
        ], '/pages/users/user_spread_money/index?type=2');
    }

    /**
     * 提现成功
     * @param $uid
     * @param $extract_number
     * @param $nickname
     * @return bool|mixed
     */
    public function sendExtractSuccess($uid, $extract_number, $nickname)
    {
        return $this->sendTemplate('USER_EXTRACT', $uid, [
            'thing1' => '提现成功',
            'amount2' => $extract_number . '元',
            'thing3' => $nickname,
            'date4' => date('Y-m-d H:i:s', time())
        ], '/pages/users/user_spread_money/index?type=2');
    }

    /**
     * 拼团成功通知
     * @param $uid
     * @param $pinkTitle
     * @param $nickname
     * @param $pinkTime
     * @param $count
     * @param string $link
     * @return bool|mixed
     */
    public function sendPinkSuccess($uid, $pinkTitle, $nickname, $pinkTime, $count, string $link = '')
    {
        return $this->sendTemplate('PINK_TRUE', $uid, [
            'thing1' => $pinkTitle,
            'name3' => $nickname,
            'date5' => date('Y-m-d H:i:s', $pinkTime),
            'number2' => $count
        ], $link);
    }

    /**
     * 拼团状态通知
     * @param $uid
     * @param $pinkTitle
     * @param $count
     * @param $remarks
     * @param $link
     * @return bool|mixed
     */
    public function sendPinkFail($uid, $pinkTitle, $count, $remarks, $link)
    {
        return $this->sendTemplate('PINK_STATUS', $uid, [
            'thing2' => $pinkTitle,
            'thing1' => $count,
            'thing3' => $remarks
        ], $link);
    }

    /**
     * 赠送积分消息提醒
     * @param $uid
     * @param $order
     * @param $storeTitle
     * @param $gainIntegral
     * @param $integral
     * @return bool|mixed
     */
    public function sendUserIntegral($uid, $order, $storeTitle, $gainIntegral, $integral)
    {
        if (!$order || !$uid) return true;
        if (is_string($order['cart_id']))
            $order['cart_id'] = json_decode($order['cart_id'], true);
        return $this->sendTemplate('INTEGRAL_ACCOUT', $uid, [
            'character_string2' => $order['order_id'],
            'thing3' => $storeTitle,
            'amount4' => $order['pay_price'],
            'number5' => $gainIntegral,
            'number6' => $integral
        ], '/pages/users/user_integral/index');
    }


    /**
     * 获得推广佣金发送提醒
     * @param string $uid
     * @param string $brokeragePrice
     * @param string $goods_name
     * @return bool|mixed
     */
    public function sendOrderBrokerageSuccess(string $uid, string $brokeragePrice, string $goods_name)
    {
        return $this->sendTemplate('ORDER_BROKERAGE', $uid, [
            'thing2' => $goods_name,
            'amount4' => $brokeragePrice . '元',
            'time1' => date('Y-m-d H:i:s', time())
        ], '/pages/users/user_spread_user/index');
    }

    /**
     * 绑定推广关系发送消息提醒
     * @param string $uid
     * @param string $userName
     * @return bool|mixed
     */
    public function sendBindSpreadUidSuccess(string $uid, string $userName)
    {
        return $this->sendTemplate('BIND_SPREAD_UID', $uid, [
            'name3' => $userName . "加入您的团队",
            'date4' => date('Y-m-d H:i:s', time())
        ], '/pages/users/user_spread_user/index');
    }
}
