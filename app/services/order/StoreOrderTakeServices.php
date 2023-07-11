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

namespace app\services\order;


use app\dao\order\StoreOrderDao;
use app\jobs\order\AutoTakeOrderJob;
use app\jobs\notice\SmsAdminJob;
use app\services\BaseServices;
use app\services\message\service\StoreServiceServices;
use app\services\message\sms\SmsSendServices;
use app\services\user\member\MemberCardServices;
use app\services\user\UserBillServices;
use app\services\user\UserBrokerageServices;
use app\services\user\level\UserLevelServices;
use app\services\user\UserServices;
use think\exception\ValidateException;
use think\facade\Log;

/**
 * 订单收货
 * Class StoreOrderTakeServices
 * @package app\services\order
 * @method get(int $id, ?array $field = []) 获取一条
 */
class StoreOrderTakeServices extends BaseServices
{
    /**
     * 构造方法
     * StoreOrderTakeServices constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 用户订单收货
     * @param $uni
     * @param $uid
     * @return bool
     */
    public function takeOrder(string $uni, int $uid)
    {
        $order = $this->dao->getUserOrderDetail($uni, $uid);
        if (!$order) {
            throw new ValidateException('订单不存在!');
        }
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $order = $orderServices->tidyOrder($order);
        if ($order['_status']['_type'] != 2) {
            throw new ValidateException('订单状态错误!');
        }
        //存在拆分发货 需要分开收货
        if ($this->dao->count(['pid' => $order['id']])) {
            throw new ValidateException('拆分发货，请去订单详情中包裹确认收货');
        }
        $order->status = 2;
        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        $res = $order->save() && $statusService->save([
                'oid' => $order['id'],
                'change_type' => 'user_take_delivery',
                'change_message' => '用户已收货',
                'change_time' => time()
            ]);
        $res = $res && $this->storeProductOrderUserTakeDelivery($order);
        if (!$res) {
            throw new ValidateException('收货失败');
        }
        //核销订单 修改订单商品核销状态
        if ($order['shipping_type'] == 2 || (in_array($order['shipping_type'], [1, 3]) && $order['delivery_type'] == 'send')) {
            //修改原来订单商品信息
            $cartData['is_writeoff'] = 1;
            $cartData['surplus_num'] = 0;
            /** @var StoreOrderCartInfoServices $cartInfoServices */
            $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
            $cartInfoServices->update(['oid' => $order['id']], $cartData);
        }
        return $order;
    }

    /**
     * 订单确认收货
     * @param $order
     * @return bool
     */
    public function storeProductOrderUserTakeDelivery($order, bool $isTran = true)
    {
        $res = true;
        //获取购物车内的商品标题
        /** @var StoreOrderCartInfoServices $orderInfoServices */
        $orderInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $storeName = $orderInfoServices->getCarIdByProductTitle((int)$order['id'], $order['cart_id']);
        $storeTitle = substrUTf8($storeName, 20, 'UTF-8', '');
        if ($order['uid']) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->get((int)$order['uid']);
            $res = $this->transaction(function () use ($order, $userInfo, $storeTitle) {
                //赠送积分
                $res1 = $this->gainUserIntegral($order, $userInfo, $storeTitle);
                //返佣
                $res2 = $this->backOrderBrokerage($order, $userInfo);
                //经验
                $res3 = $this->gainUserExp($order, $userInfo);
                if (!($res1 && $res2 && $res3)) {
                    throw new ValidateException('收货失败!');
                }
                return true;
            }, $isTran);
        }
        if ($res) {
            //订单收货事件
            event('order.take', [$order, $storeTitle]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 赠送积分
     * @param $order
     * @param $userInfo
     * @param $storeTitle
     * @return bool
     */
    public function gainUserIntegral($order, $userInfo, $storeTitle)
    {
        $res2 = true;
        if (!$userInfo) {
            return true;
        }
        // 营销产品送积分
        if (!isset($order['type']) || in_array($order['type'], [1, 2, 3, 5])) {
            return true;
        }
        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
        if ($order['gain_integral'] > 0) {
            $balance = $userInfo['integral'];
            $res2 = false != $userBillServices->income('pay_give_integral', $order['uid'], $order['gain_integral'], $balance, $order['id']);
        }
        $order_integral = 0;
        $res3 = true;
        $order_give_integral = sys_config('order_give_integral');
        if ($order['pay_price'] && $order_give_integral) {
            //会员消费返积分翻倍
            if ($userInfo['is_money_level'] > 0) {
                //看是否开启消费返积分翻倍奖励
                /** @var MemberCardServices $memberCardService */
                $memberCardService = app()->make(MemberCardServices::class);
                $integral_rule_number = $memberCardService->isOpenMemberCardCache('integral');
                if ($integral_rule_number) {
                    $order_integral = bcmul((string)$order['pay_price'], (string)$integral_rule_number, 2);
                }
            }
            $order_integral = bcmul((string)$order_give_integral, (string)($order_integral ? $order_integral : $order['pay_price']), 0);

            $balance = bcadd((string)$userInfo['integral'], (string)$order['gain_integral'], 0);
            $res3 = false != $userBillServices->income('order_give_integral', $order['uid'], $order_integral, $balance, $order['id']);
        }
        $give_integral = $order_integral + $order['gain_integral'];
        if ($give_integral > 0) {
            $integral = $userInfo['integral'] + $give_integral;
            $userInfo->integral = $integral;
            $res1 = false != $userInfo->save();
            $res = $res1 && $res2 && $res3;
            //发送消息
            event('notice.notice', [['order' => $order, 'storeTitle' => $storeTitle, 'give_integral' => $give_integral, 'integral' => $integral], 'integral_accout']);
            return $res;
        }
        return true;
    }

    /**
     * 一级返佣
     * @param $orderInfo
     * @param $userInfo
     * @return bool
     */
    public function backOrderBrokerage($orderInfo, $userInfo)
    {
        // 当前订单｜用户不存在  直接返回
        if (!$orderInfo || !$userInfo) {
            return true;
        }
        //商城分销功能是否开启 0关闭1开启
        if (!sys_config('brokerage_func_status')) return true;

        // 营销产品不返佣金
        if (!isset($orderInfo['type']) || in_array($orderInfo['type'], [1, 2, 3, 5])) {
            return true;
        }
        //绑定失效
        if (isset($orderInfo['spread_uid']) && $orderInfo['spread_uid'] == -1) {
            return true;
        }
        //是否开启自购返佣
        $isSelfBrokerage = sys_config('is_self_brokerage', 0);
        if (!isset($orderInfo['spread_uid']) || !$orderInfo['spread_uid']) {//兼容之前订单表没有spread_uid情况
            //没开启自购返佣 没有上级 或者 当用用户上级时自己  直接返回
            if (!$isSelfBrokerage && (!$userInfo['spread_uid'] || $userInfo['spread_uid'] == $orderInfo['uid'])) {
                return true;
            }
            $one_spread_uid = $isSelfBrokerage ? $userInfo['uid'] : $userInfo['spread_uid'];
        } else {
            $one_spread_uid = $orderInfo['spread_uid'];
        }
        $one_spread_uid = (int)$one_spread_uid;
        //冻结时间
        $broken_time = intval(sys_config('extract_time'));
        $frozen_time = time() + $broken_time * 86400;

        //检测是否是分销员
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if (!$userServices->checkUserPromoter($one_spread_uid)) {//一级不是分销员 直接二级返佣
            return $this->backOrderBrokerageTwo($orderInfo, $userInfo, $isSelfBrokerage, $frozen_time);
        }
        //订单中取出
        $brokeragePrice = $orderInfo['one_brokerage'] ?? 0;
        // 返佣金额小于等于0 直接返回不返佣金
        if ($brokeragePrice <= 0) {
            return true;
        }
        // 获取上级推广员信息
        $spreadPrice = $userServices->value(['uid' => $one_spread_uid], 'brokerage_price');
        // 上级推广员返佣之后的金额
        $balance = bcadd($spreadPrice, $brokeragePrice, 2);
        // 添加佣金记录
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        //自购返佣 ｜｜ 上级
        $type = $one_spread_uid == $orderInfo['uid'] ? 'get_self_brokerage' : 'get_brokerage';
        $res1 = $userBrokerageServices->income($type, $one_spread_uid, [
            'nickname' => $userInfo['nickname'],
            'pay_price' => floatval($orderInfo['pay_price']),
            'number' => floatval($brokeragePrice),
            'frozen_time' => $frozen_time
        ], $balance, $orderInfo['id']);
        // 添加用户佣金
        $res2 = $userServices->bcInc($one_spread_uid, 'brokerage_price', $brokeragePrice, 'uid');
        //给上级发送获得佣金的模板消息
        $this->sendBackOrderBrokerage($orderInfo, $one_spread_uid, $brokeragePrice);
        // 一级返佣成功 跳转二级返佣
        $res = $res1 && $res2 && $this->backOrderBrokerageTwo($orderInfo, $userInfo, $isSelfBrokerage, $frozen_time);
        return $res;
    }


    /**
     * 二级推广返佣
     * @param $orderInfo
     * @param $userInfo
     * @param int $isSelfbrokerage
     * @param int $frozenTime
     * @return bool
     */
    public function backOrderBrokerageTwo($orderInfo, $userInfo, $isSelfbrokerage = 0, $frozenTime = 0)
    {
        //绑定失效
        if (isset($orderInfo['spread_two_uid']) && $orderInfo['spread_two_uid'] == -1) {
            return true;
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if (isset($orderInfo['spread_two_uid']) && $orderInfo['spread_two_uid']) {
            $spread_two_uid = $orderInfo['spread_two_uid'];
        } else {
            // 获取上推广人
            $userInfoTwo = $userServices->get((int)$userInfo['spread_uid']);
            // 订单｜上级推广人不存在   直接返回
            if (!$orderInfo || !$userInfoTwo) {
                return true;
            }
            //没开启自购返佣 或者 上推广人没有上级  或者 当用用户上上级时自己  直接返回
            if (!$isSelfbrokerage && (!$userInfoTwo['spread_uid'] || $userInfoTwo['spread_uid'] == $orderInfo['uid'])) {
                return true;
            }
            $spread_two_uid = $isSelfbrokerage ? $userInfoTwo['uid'] : $userInfoTwo['spread_uid'];
        }
        $spread_two_uid = (int)$spread_two_uid;
        // 获取后台分销类型  1 指定分销 2 人人分销
        if (!$userServices->checkUserPromoter($spread_two_uid)) {
            return true;
        }
        //订单中取出
        $brokeragePrice = $orderInfo['two_brokerage'] ?? 0;
        // 返佣金额小于等于0 直接返回不返佣金
        if ($brokeragePrice <= 0) {
            return true;
        }
        // 获取上上级推广员信息
        $spreadPrice = $userServices->value(['uid' => $spread_two_uid], 'brokerage_price');
        // 获取上上级推广员返佣之后余额
        $balance = bcadd($spreadPrice, $brokeragePrice, 2);
        // 添加佣金记录
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        $res1 = $userBrokerageServices->income('get_two_brokerage', $spread_two_uid, [
            'nickname' => $userInfo['nickname'],
            'pay_price' => floatval($orderInfo['pay_price']),
            'number' => floatval($brokeragePrice),
            'frozen_time' => $frozenTime
        ], $balance, $orderInfo['id']);
        // 添加用户佣金
        $res2 = $userServices->bcInc($spread_two_uid, 'brokerage_price', $brokeragePrice, 'uid');
        //给上级发送获得佣金的模板消息
        $this->sendBackOrderBrokerage($orderInfo, $spread_two_uid, $brokeragePrice);
        return $res1 && $res2;
    }

    /**
     * 佣金到账发送模板消息
     * @param $orderInfo
     * @param $spread_uid
     * @param $brokeragePrice
     */
    public function sendBackOrderBrokerage($orderInfo, $spread_uid, $brokeragePrice, string $type = 'order')
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userType = $userServices->value(['uid' => $spread_uid], 'user_type');
        if ($type == 'order') {
            /** @var StoreOrderCartInfoServices $storeOrderCartInfoService */
            $storeOrderCartInfoService = app()->make(StoreOrderCartInfoServices::class);
            $cartInfo = $storeOrderCartInfoService->getOrderCartInfo($orderInfo['id']);
            if ($cartInfo) {
                $cartInfo = array_column($cartInfo, 'cart_info');
                $goodsPrice = 0;
                $goodsName = "";
                foreach ($cartInfo as $k => $v) {
                    $goodsName .= $v['productInfo']['store_name'];
                    $price = $v['productInfo']['attrInfo']['price'] ?? $v['productInfo']['price'] ?? 0;
                    $goodsPrice = bcadd((string)$goodsPrice, (string)$price, 2);
                }
            }
        } else {
            $goodsName = '推广用户获取佣金';
            $goodsPrice = $brokeragePrice;
        }
        //提醒推送
        event('notice.notice', [['spread_uid' => $spread_uid, 'userType' => $userType, 'brokeragePrice' => $brokeragePrice, 'goodsName' => $goodsName, 'goodsPrice' => $goodsPrice, 'add_time' => $orderInfo['add_time'] ?? time()], 'order_brokerage']);
        return true;
    }


    /**
     * 发送短信
     * @param $order
     * @param $storeTitle
     */
    public function smsSend($order, $storeTitle)
    {
        /** @var SmsSendServices $smsServices */
        $smsServices = app()->make(SmsSendServices::class);
        $switch = sys_config('confirm_take_over_switch') ? true : false;
        //模板变量
        $store_name = $storeTitle;
        $order_id = $order['order_id'];
        $smsServices->send($switch, $order['user_phone'], compact('store_name', 'order_id'), 'TAKE_DELIVERY_CODE');
    }

    /**
     * 发送确认收货管理员短信
     * @param $order
     */
    public function smsSendTake($order)
    {
        $switch = sys_config('admin_confirm_take_over_switch') ? true : false;
        /** @var StoreServiceServices $services */
        $services = app()->make(StoreServiceServices::class);
        $adminList = $services->getStoreServiceOrderNotice();
        SmsAdminJob::dispatchDo('sendAdminConfirmTakeOver', [$switch, $adminList, $order]);
        return true;
    }

    /**
     * 赠送经验
     * @param $order
     * @param $userInfo
     * @return bool
     */
    public function gainUserExp($order, $userInfo)
    {
        if (!$userInfo) {
            return true;
        }
        //用户等级是否开启
        if (!sys_config('member_func_status', 1)) {
            return true;
        }
        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
        $order_exp = 0;
        $res3 = true;
        $order_give_exp = sys_config('order_give_exp');
        if ($order['pay_price'] && $order_give_exp) {
            $order_exp = bcmul($order_give_exp, (string)$order['pay_price'], 2);
            $res3 = false != $userBillServices->income('order_give_exp', $order['uid'], $order_exp, $userInfo['exp'], $order['id']);
        }
        $res = true;
        if ($order_exp > 0) {
            $exp = $userInfo['exp'] + $order_exp;
            $userInfo->exp = $exp;
            $res1 = false != $userInfo->save();
            $res = $res1 && $res3;
        }
        /** @var UserLevelServices $levelServices */
        $levelServices = app()->make(UserLevelServices::class);
        $levelServices->detection((int)$order['uid']);
        return $res;
    }

    /**
     * 加入队列
     * @param array $where
     * @param int $count
     * @param int $maxLimit
     * @return bool
     */
    public function batchJoinJobs(array $where, int $count, int $maxLimit)
    {
        $page = ceil($maxLimit / $count);
        for ($i = 1; $i <= $page; $i++) {
            AutoTakeOrderJob::dispatch([$where, $i, $maxLimit]);
        }
        return true;
    }

    /**
     * 执行自动收货
     * @param array $where
     * @param int $page
     * @param int $maxLimit
     * @return bool
     */
    public function runAutoTakeOrder(array $where, int $page = 0, int $maxLimit = 0)
    {
        /** @var StoreOrderStoreOrderStatusServices $service */
        $service = app()->make(StoreOrderStoreOrderStatusServices::class);
        $orderList = $service->getTakeOrderIds($where, $page, $maxLimit);
        foreach ($orderList as $order) {
            if ($order['status'] == 2) {
                continue;
            }
            if ($order['paid'] == 1 && $order['status'] == 1) {
                $data['status'] = 2;
            } else if ($order['pay_type'] == 'offline') {
                $data['status'] = 2;
            } else {
                continue;
            }
            try {
                /** @var StoreOrderStatusServices $statusService */
                $statusService = app()->make(StoreOrderStatusServices::class);
                $res = $this->dao->update($order['id'], $data) && $statusService->save([
                        'oid' => $order['id'],
                        'change_type' => 'take_delivery',
                        'change_message' => '已收货[自动收货]',
                        'change_time' => time()
                    ]);
                $res = $res && $this->storeProductOrderUserTakeDelivery($order);
                if (!$res) {
                    throw new ValidateException('订单号' . $order['order_id'] . '自动收货失败');
                }
            } catch (\Throwable $e) {
                Log::error('自动收货失败,失败原因：' . $e->getMessage());
            }
        }
        return true;
    }

    /**
     * 自动收货
     * @return bool
     */
    public function autoTakeOrder()
    {
        //7天前时间戳
        $systemDeliveryTime = (int)sys_config('system_delivery_time', 0);
        //0为取消自动收货功能
        if ($systemDeliveryTime == 0) {
            return true;
        }
        $sevenDay = strtotime(date('Y-m-d H:i:s', strtotime('-' . $systemDeliveryTime . ' day')));
        /** @var StoreOrderStoreOrderStatusServices $service */
        $service = app()->make(StoreOrderStoreOrderStatusServices::class);
        $where = [
            'change_time' => $sevenDay,
            'is_del' => 0,
            'paid' => 1,
            'status' => 1,
            'change_type' => ['delivery_goods', 'delivery_fictitious', 'delivery']
        ];
        $maxLimit = 100;
        $count = $service->getTakeOrderCount($where);
        if ($count > $maxLimit) {
            return $this->batchJoinJobs($where, $count, $maxLimit);
        }
        return $this->runAutoTakeOrder($where);
    }
}
