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

namespace app\controller\api\v1\order;


use app\services\order\OtherOrderServices;
use app\services\pay\OrderPayServices;
use app\services\pay\PayServices;
use app\services\pay\YuePayServices;
use app\services\user\member\MemberCardServices;
use app\services\user\UserServices;
use crmeb\services\CacheService;
use crmeb\services\SystemConfigService;
use app\Request;

/**
 * Class OtherOrderController
 * @package app\api\controller\v1\order
 */
class OtherOrderController
{
    /**
     * @var OtherOrderServices
     */
    protected $services;

    /**
     * OtherOrderController constructor.
     * @param OtherOrderServices $services
     */
    public function __construct(OtherOrderServices $services)
    {
        $this->services = $services;
    }

    /**
     * 计算会员线下付款金额
     * @param Request $request
     * @return mixed
     */
    public function computed_offline_pay_price(Request $request)
    {
        list($pay_price) = $request->getMore([['pay_price', 0]], true);
        if (!$pay_price || !is_numeric($pay_price)) return app('json')->fail('请输入付款金额');
        $uid = $request->uid();
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $user_info = $userService->get($uid, ['is_money_level', 'is_ever_level', 'overdue_time']);
        //会员线下享受折扣
        if ($user_info->is_money_level > 0 && $user_info->overdue_time > time()) {
            //看是否开启线下享受折扣
            /** @var MemberCardServices $memberCardService */
            $memberCardService = app()->make(MemberCardServices::class);
            $offline_rule_number = $memberCardService->isOpenMemberCardCache('offline');
            if ($offline_rule_number) {
                $pay_price = bcmul($pay_price, bcdiv($offline_rule_number, '100', 2), 2);
            } else {
                $pay_price = 0;
            }
        } else {
            $pay_price = 0;
        }
        return app('json')->successful(['pay_price' => $pay_price]);

    }

    /**
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create(Request $request)
    {
        $uid = (int)$request->uid();
        /** @var OtherOrderServices $OtherOrderServices */
        $OtherOrderServices = app()->make(OtherOrderServices::class);
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        [$payType, $type, $from, $memberType, $price, $money, $quitUrl] = $request->postMore([
            ['pay_type', 'yue'],
            ['type', 0],
            ['from', 'weixin'],
            ['member_type', ''],
            ['price', 0.00],
            ['money', 0.00],
            ['quitUrl', ''],
        ], true);
        $payType = strtolower($payType);
        if (in_array($type, [1, 2])) {
            /** @var MemberCardServices $memberCardService */
            $memberCardService = app()->make(MemberCardServices::class);
            $isOpenMember = $memberCardService->isOpenMemberCardCache();
            if (!$isOpenMember) return app('json')->fail('付费会员功能暂未开启!');
        }
        $channelType = $request->user('user_type');
        $order = $OtherOrderServices->createOrder($uid, $channelType, $memberType, $price, $payType, $type, $money);
        if ($order === false) return app('json')->fail('支付数据生成失败');
        $order_id = $order['order_id'];
        $orderInfo = $order->toArray();
        $info = compact('order_id');
        if ($order_id) {
            switch ($payType) {
                case PayServices::WEIXIN_PAY:
                    if ($orderInfo['paid']) return app('json')->fail('已支付!');
                    //支付金额为0
                    if (bcsub((string)$orderInfo['pay_price'], '0', 2) <= 0) {
                        //创建订单jspay支付
                        $payPriceStatus = $OtherOrderServices->zeroYuanPayment($orderInfo);
                        if ($payPriceStatus)//0元支付成功
                            return app('json')->status('success', '微信支付成功', $info);
                        else
                            return app('json')->status('pay_error');
                    } else {
                        /** @var OrderPayServices $payServices */
                        $payServices = app()->make(OrderPayServices::class);
                        $info['jsConfig'] = $payServices->orderPay($orderInfo, $from);
                        if ($from == 'weixinh5') {
                            return app('json')->status('wechat_h5_pay', '前往支付', $info);
                        } else {
                            return app('json')->status('wechat_pay', '前往支付', $info);
                        }
                    }
                    break;
                case PayServices::YUE_PAY:
                    /** @var YuePayServices $yueServices */
                    $yueServices = app()->make(YuePayServices::class);
                    $pay = $yueServices->yueOrderPay($orderInfo, $uid);
                    if ($pay['status'] === true)
                        return app('json')->status('success', '余额支付成功', $info);
                    else {
                        if (is_array($pay))
                            return app('json')->status($pay['status'], $pay['msg'], $info);
                        else
                            return app('json')->status('pay_error', $pay);
                    }
                    break;
                case PayServices::ALIAPY_PAY:
                    if (!$quitUrl && ($request->isH5() || $request->isWechat())) {
                        return app('json')->status('pay_error', '请传入支付宝支付回调URL', $info);
                    }
                    //支付金额为0
                    if (bcsub((string)$orderInfo['pay_price'], '0', 2) <= 0) {
                        //创建订单jspay支付
                        $payPriceStatus = $OtherOrderServices->zeroYuanPayment($orderInfo);
                        if ($payPriceStatus)//0元支付成功
                            return app('json')->status('success', '支付宝支付成功', $info);
                        else
                            return app('json')->status('pay_error');
                    } else {
                        /** @var OrderPayServices $payServices */
                        $payServices = app()->make(OrderPayServices::class);
                        $info['jsConfig'] = $payServices->alipayOrder($orderInfo, $quitUrl, $request->isRoutine());
                        $payKey = md5($orderInfo['order_id']);
                        CacheService::set($payKey, ['order_id' => $orderInfo['order_id'], 'other_pay_type' => true], 300);
                        $info['pay_key'] = $payKey;
                        return app('json')->status(PayServices::ALIAPY_PAY . '_pay', '前往支付', $info);
                    }
                    break;
                case PayServices::OFFLINE_PAY:
                    return app('json')->status('success', '前往支付', $info);
                    break;
                default:
                    return app('json')->fail('未知支付方式');
            }
        } else return app('json')->fail('订单生成失败!');
    }

    /**
     * 线下支付方式
     * @return mixed
     */
    public function pay_type(Request $request)
    {
        $payType = SystemConfigService::more(['ali_pay_status', 'pay_weixin_open', 'site_name', 'balance_func_status', 'yue_pay_status']);
        $payType['now_money'] = $request->user('now_money');
        $payType['offline_pay_status'] = true;
        $payType['yue_pay_status'] = (int)($payType['balance_func_status'] ?? 0) && (int)($payType['yue_pay_status'] ?? 0) == 1 ? 1 : 0;//余额支付 1 开启 2 关闭
        unset($payType['balance_func_status'], $payType['yue_pay_status']);
        return app('json')->successful($payType);
    }
}
