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
namespace app\controller\cashier;

use app\common\controller\Order as CommonOrder;
use app\Request;
use app\services\order\OtherOrderServices;
use app\services\activity\discounts\StoreDiscountsServices;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\cashier\OrderServices;
use app\services\order\cashier\CashierOrderServices;
use app\services\order\cashier\StoreHangOrderServices;
use app\services\order\store\WriteOffOrderServices;
use app\services\order\StoreCartServices;
use app\services\order\StoreOrderDeliveryServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\pay\PayServices;
use app\services\store\DeliveryServiceServices;
use app\services\user\UserServices;
use app\services\user\UserRechargeServices;
use crmeb\services\AliPayService;
use crmeb\services\CacheService;
use crmeb\services\wechat\Payment;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\App;

/**
 * 收银台订单控制器
 */
class Order extends AuthController
{
    use CommonOrder;

    /**
     * StoreOrder constructor.
     * @param App $app
     * @param StoreOrderServices $service
     */
    public function __construct(App $app, StoreOrderServices $service)
    {
        parent::__construct($app);
        $this->services = $service;
    }

    /**
     * 获取收银订单用户
     * @param OrderServices $services
     * @param $storeId
     * @param $cashierId
     * @return mixed
     */
    public function getUserList(OrderServices $services, $cashierId)
    {
        $data = $services->getOrderUserList($this->storeId);
        return $this->success($data);
    }

    /**
 	* 获取门店订单列表
	* @param Request $request
	* @param StoreOrderServices $services
	* @param UserRechargeServices $rechargeServices
	* @param OtherOrderServices $otherOrderServices
	* @param $orderType
	* @return mixed
	* @throws DataNotFoundException
	* @throws ModelNotFoundException
	* @throws \think\db\exception\DbException
	 */
    public function getOrderList(Request $request, StoreOrderServices $services, UserRechargeServices $rechargeServices, OtherOrderServices $otherOrderServices, $orderType = 1)
    {
        if (!$orderType) $orderType = 1;
        $where = $request->postMore([
            ['type', ''],
            ['status', ''],
            ['time', ''],
            ['staff_id', ''],
            ['keyword', '', '', 'real_name']
        ]);
		if ($where['time'] && is_array($where['time']) && count($where['time']) == 2) {
			[$start, $end] = $where['time'];
			if (strtotime($start) > strtotime($end)){
				return $this->fail('开始时间不能大于结束时间，请重新选择时间');
			}
		}
        $where['store_id'] = $this->storeId;
        if (!$where['real_name'] && !in_array($where['status'], [-1, -2, -3])) {
            $where['pid'] = 0;
        }
        switch ($orderType) {
            case 1:
            case 5:
                $where['is_system_del'] = 0;
                $data = $services->getOrderList(
                    $where,
                    ['*'],
                    [
                        'user',
                        'split' => function ($query) {
                            $query->field('id,pid');
                        },
                        'pink',
                        'invoice',
                        'storeStaff'
                    ],
                    true
                );
                $list = $data['data'] ?? [];
                if ($list) {
                    /** @var StoreCouponIssueServices $couponIssueService */
                    $couponIssueService = app()->make(StoreCouponIssueServices::class);
                    foreach ($list as $key => &$item) {
                        if ($item['give_coupon']) {
                            $couponIds = is_string($item['give_coupon']) ? explode(',', $item['give_coupon']) : $item['give_coupon'];
                            
                            $item['give_coupon'] = $couponIssueService->getColumn([['id', 'IN', $couponIds]], 'id,coupon_title');
                        }
                    }
                }
                $count = $data['count'] ?? 0;
                return $this->success(compact('list', 'count'));
            case 2:
                return $this->success($rechargeServices->getRechargeList($where, '*', 0, ['staff', 'user']));
            case 3:
                $where['paid'] = 1;
                return $this->success($otherOrderServices->getMemberRecord($where));
        }
        return $this->success(['list' => [], 'count' => 0]);
    }

    /**
     * 获取收银台挂单列表
     * @param Request $request
     * @param StoreHangOrderServices $services
     * @param int $cashierId
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function getHangList(Request $request, StoreHangOrderServices $services, $cashierId = 0)
    {
        $search = $request->get('keyword', '');
        $data = $services->getHangOrderList((int)$this->storeId, 0, $search);
        $data['list'] = $data['data'];
        unset($data['data']);
        return $this->success($data);
    }

    /**
     * 收银台退款订单列表
     * @param Request $request
     * @param StoreOrderRefundServices $service
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function getRefundList(Request $request, StoreOrderRefundServices $service)
    {
        $where = $request->getMore([
            ['keyword', '', '', 'order_id'],
            ['time', ''],
            ['refund_type', 0]
        ]);
        $where['store_id'] = $this->storeId;
        return $this->success($service->refundList($where));
    }

    /**
     * 收银台核销订单
     * @param Request $request
     * @param StoreOrderServices $services
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function getVerifyList(Request $request, StoreOrderServices $services)
    {
        $where = $request->postMore([
            ['status', ''],
            ['time', ''],
            ['staff_id', ''],
            ['keyword', '', '', 'real_name']
        ]);
		if ($where['time'] && is_array($where['time']) && count($where['time']) == 2) {
			[$start, $end] = $where['time'];
			if (strtotime($start) > strtotime($end)){
				return $this->fail('开始时间不能大于结束时间，请重新选择时间');
			}
		}
        $where['is_system_del'] = 0;
        $where['type'] = 5;
        $where['store_id'] = $this->storeId;
        if (!$where['real_name'] && !in_array($where['status'], [-1, -2, -3])) {
            $where['pid'] = 0;
        }
        $result = $services->getOrderList($where, ['*'], ['split' => function ($query) {
            $query->field('id,pid');
        }, 'pink', 'invoice', 'storeStaff'], true);
        if ($result['data']) {
            /** @var StoreCouponIssueServices $couponIssueService */
            $couponIssueService = app()->make(StoreCouponIssueServices::class);
            foreach ($result['data'] as $key => &$item) {
                if ($item['give_coupon']) {
                    $couponIds = is_string($item['give_coupon']) ? explode(',', $item['give_coupon']) : $item['give_coupon'];
                    
                    $item['give_coupon'] = $couponIssueService->getColumn([['id', 'IN', $couponIds]], 'id,coupon_title');
                }
            }
        }
        return $this->success($result);
    }

    /**
     * 退款订单详情
     * @param StoreOrderRefundServices $service
     * @param UserServices $userServices
     * @param $id
     * @return mixed
     */
    public function refundInfo(StoreOrderRefundServices $service, UserServices $userServices, $id)
    {
        $order = $service->refundDetail($id);
        $data['orderInfo'] = $order;
        $userInfo = ['spread_uid' => '', 'spread_name' => '无'];
        if ($order['uid']) {
            $userInfo = $userServices->get((int)$order['uid']);
            if (!$userInfo) return $this->fail('用户信息不存在');
            $userInfo = $userInfo->hidden(['pwd', 'add_ip', 'last_ip', 'login_type']);
            $userInfo = $userInfo->toArray();
            $userInfo['spread_name'] = '无';
            if ($order['spread_uid']) {
                $spreadName = $userServices->value(['uid' => $order['spread_uid']], 'nickname');
                if ($spreadName) {
                    $userInfo['spread_name'] = $order['uid'] == $order['spread_uid'] ? $spreadName . '(自购)' : $spreadName;
                    $userInfo['spread_uid'] = $order['spread_uid'];
                } else {
                    $userInfo['spread_uid'] = '';
                }
            } else {
                $userInfo['spread_uid'] = '';
            }
        }
        $data['userInfo'] = $userInfo;
        return $this->success('ok', $data);
    }

    /**
     * 加入购物车
     * @param Request $request
     * @param StoreCartServices $services
     * @param $uid
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function addCart(Request $request, StoreCartServices $services, $uid)
    {
        $where = $request->postMore([
            ['productId', 0],//普通商品编号
            [['cartNum', 'd'], 1], //购物车数量
            ['uniqueId', ''],//属性唯一值
            ['staff_id', ''],//店员ID
            ['secKillId', 0],//秒杀ID
            ['new', 1],//1直接购买,0=加入购物车
            ['tourist_uid', ''],//虚拟用户uid
            [['secKillId', 'd'], 0],//秒杀商品编号
        ]);

        $new = !!$where['new'];

        if (!$where['productId']) {
            return app('json')->fail('参数错误');
        }
        //真实用户存在，虚拟用户uid为空
        if ($uid) {
            $where['tourist_uid'] = '';
        }
        if (!$uid && !$where['tourist_uid']) {
            return $this->fail('缺少用户UID');
        }
        $services->setItem('store_id', $this->storeId)
            ->setItem('tourist_uid', $where['tourist_uid'])
            ->setItem('staff_id', $where['staff_id']);

        $activityId = $type = 0;

        if ($where['secKillId']) {
            $type = 1;
            $activityId = $where['secKillId'];
        }

        $res = $services->setCart($uid, (int)$where['productId'], (int)$where['cartNum'], $where['uniqueId'], $type, $new, (int)$activityId);

        $services->reset();
        return $this->success(['cartId' => $res]);
    }

    /**
     * 收银台更改购物车数量
     * @param Request $request
     * @param StoreCartServices $services
     * @param $uid
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function numCart(Request $request, StoreCartServices $services, $uid)
    {
        $where = $request->postMore([
            ['id', 0],//购物车编号
            ['number', 0],//购物数量
        ]);
        if (!$where['id'] || !$where['number'] || !is_numeric($where['id']) || !is_numeric($where['number'])) {
            return $this->fail('参数错误!');
        }
        if ($services->changeCashierCartNum((int)$where['id'], (int)$where['number'], $uid, $this->storeId)) {
            return $this->success('修改成功');
        } else {
            return $this->fail('修改失败');
        }
    }

    /**
     * 收银台删除购物车信息
     * @param Request $request
     * @param StoreCartServices $services
     * @param $uid
     * @return mixed
     */
    public function delCart(Request $request, StoreCartServices $services, $uid)
    {
        $where = $request->postMore([
            ['ids', []],//购物车编号
        ]);
        if (!count($where['ids'])) {
            return $this->fail('参数错误!');
        }
        if ($services->removeUserCart((int)$uid, $where['ids'])) {
            return $this->success('删除成功');
        } else {
            return $this->fail('清除失败！');
        }
    }

    /**
     * 收银台重选商品规格
     * @param Request $request
     * @param StoreCartServices $services
     * @return mixed
     */
    public function changeCart(Request $request, StoreCartServices $services)
    {
        [$cart_id, $product_id, $unique] = $request->postMore([
            ['cart_id', 0],
            ['product_id', 0],
            ['unique', '']
        ], true);
        $services->modifyCashierCart($this->storeId, (int)$cart_id, (int)$product_id, $unique);
        return $this->success('重选成功');
    }

    /**
     * 获取购物车数据
     * @param Request $request
     * @param StoreCartServices $services
     * @param $uid
     * @param $cashierId
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCartList(Request $request, StoreCartServices $services, $uid, $cashierId)
    {
        $cartIds = $request->get('cart_ids', '');
        $touristUid = $request->get('tourist_uid', '');
        $cartIds = $cartIds ? explode(',', $cartIds) : [];
        if (!$touristUid && !$uid) {
            return $this->fail('缺少用户信息');
        }
        $result = $services->getUserCartList((int)$uid, -1, $cartIds, $this->storeId, 0, 4, (int)$touristUid);
        $result['valid'] = $services->getReturnCartList($result['valid'] ?? [], $result['promotions'] ?? []);
        unset($result['promotions']);
        return $this->success($result);
    }

    /**
     * 收银台计算订单金额
     * @param Request $request
     * @param CashierOrderServices $services
     * @param $uid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderCompute(Request $request, CashierOrderServices $services, $uid)
    {
        [$integral, $coupon, $cartIds, $coupon_id, $new] = $request->postMore([
            ['integral', 0],
            ['coupon', 0],
            ['cart_id', []],
            ['coupon_id', 0],
            ['new', 0]
        ], true);
        if (!$cartIds) {
            return $this->fail('缺少购物车ID');
        }
        return $this->success($services->computeOrder((int)$uid, $this->storeId, $cartIds, !!$integral, !!$coupon, [], $coupon_id, !!$new));
    }


    /**
     * 生成订单
     * @param CashierOrderServices $services
     * @param $uid
     * @return mixed
     */
    public function createOrder(CashierOrderServices $services, $uid)
    {
        [$integral, $coupon, $cartIds, $payType, $remarks, $staffId, $changePrice, $isPrice, $userCode, $coupon_id, $authCode, $touristUid, $seckillId, $new] = $this->request->postMore([
            ['integral', 0],
            ['coupon', 0],
            ['cart_id', []],
            ['pay_type', ''],
            ['remarks', ''],
            ['staff_id', 0],
            ['change_price', 0],
            ['is_price', 0],
            ['userCode', ''],
            ['coupon_id', 0],
            ['auth_code', ''],
            ['tourist_uid', ''],
            ['seckill_id', 0],
            ['new', 0]
        ], true);

        if (!$staffId) {
            $staffId = $this->request->cashierId();
        }

        if (!$cartIds) {
            return $this->fail('缺少购物车ID');
        }
        if (!in_array($payType, ['yue', 'cash']) && $authCode) {
            if (Payment::isWechatAuthCode($authCode)) {
                $payType = PayServices::WEIXIN_PAY;
            } else if (AliPayService::isAliPayAuthCode($authCode)) {
                $payType = PayServices::ALIAPY_PAY;
            } else {
                return $this->fail('未知,付款二维码');
            }
        }

        $userInfo = [];
        if ($uid) {
            /** @var UserServices $userService */
            $userService = app()->make(UserServices::class);
            $userInfo = $userService->getUserInfo($uid);
            if (!$userInfo) {
                return $this->fail('用户不存在');
            }
            $userInfo = $userInfo->toArray();
        }

        $computeData = $services->computeOrder($uid, $this->storeId, $cartIds, $integral, $coupon, $userInfo, $coupon_id, !!$new);
        $cartInfo = $computeData['cartInfo'];

        try {
             $res = $services->transaction(function () use ($services, $userInfo, $computeData, $authCode, $uid, $staffId, $cartIds, $payType, $integral, $coupon, $remarks, $changePrice, $isPrice, $userCode, $coupon_id, $seckillId) {
                 $orderInfo = $services->createOrder((int)$uid, $userInfo, $computeData, $this->storeId, (int)$staffId, $cartIds, $payType, !!$integral, !!$coupon, $remarks, $changePrice, !!$isPrice, $coupon_id, $seckillId);
                 if (in_array($payType, [PayServices::YUE_PAY, PayServices::CASH_PAY, PayServices::ALIAPY_PAY, PayServices::WEIXIN_PAY])) {
                     $res = $services->paySuccess($orderInfo['order_id'], $payType, $userCode, $authCode);
                     $res['order_id'] = $orderInfo['order_id'];
                     return $res;
                 } else {
                     return ['status' => 'ORDER_CREATE', 'order_id' => $orderInfo['order_id']];
                 }
             });
             return app('json')->success($res);
        } catch (\Throwable $e) {
            //回退库存
            if ($seckillId) {
                foreach ($cartInfo as $item) {
                    if (!isset($item['product_attr_unique']) || !$item['product_attr_unique']) continue;
                    $type = $item['type'];
                    if (in_array($type, [1, 2, 3])) CacheService::setStock($item['product_attr_unique'], (int)$item['cart_num'], $type, false);
                }
            }

            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 订单支付
     * @param CashierOrderServices $services
     * @param $orderId
     * @return mixed
     */
    public function payOrder(CashierOrderServices $services, $orderId)
    {
        if (!$orderId) {
            return $this->fail('缺少订单号');
        }
        $payType = $this->request->post('payType', 'yue');

        $userCode = $this->request->post('userCode', '');
        $authCode = $this->request->post('auth_code', '');
        if ($payType == PayServices::YUE_PAY && !$userCode) {
            return $this->fail('缺少用户余额支付CODE');
        }
        if (!in_array($payType, ['yue', 'cash']) && $authCode) {
            if (Payment::isWechatAuthCode($authCode)) {
                $payType = PayServices::WEIXIN_PAY;
            } else if (AliPayService::isAliPayAuthCode($authCode)) {
                $payType = PayServices::ALIAPY_PAY;
            } else {
                return $this->fail('未知,付款二维码');
            }
        }
        $res = $services->paySuccess($orderId, $payType, $userCode, $authCode);
        $res['order_id'] = $orderId;
        return $this->success($res);
    }

    /**
     * 订单核销订单数据
     * @param Request $request
     * @param WriteOffOrderServices $writeOffOrderServices
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function verifyCartInfo(Request $request, WriteOffOrderServices $writeOffOrderServices)
    {
        [$oid] = $request->postMore([
            ['oid', '']
        ], true);
        return $this->success($writeOffOrderServices->getOrderCartInfo(0, (int)$oid, 1, (int)$this->cashierId, true));
    }

    /**
     * 订单核销
     * @param Request $request
     * @param StoreOrderServices $services
     * @param WriteOffOrderServices $writeOffOrderServices
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function writeOff(Request $request, StoreOrderServices $services, WriteOffOrderServices $writeOffOrderServices, $id)
    {
        if (!$id) {
            return $this->fail('核销订单未查到!');
        }
        [$cart_ids] = $request->postMore([
            ['cart_ids', []]
        ], true);
        if ($cart_ids) {
            foreach ($cart_ids as $cart) {
                if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num'] || $cart['cart_num'] <= 0) {
                    return $this->fail('请重新选择发货商品，或发货件数');
                }
            }
        }
		$orderInfo = $writeOffOrderServices->getOrderCartInfo(0, (int)$id, 1, (int)$this->cashierId);
        $writeOffOrderServices->writeoffOrder(0, $orderInfo, $cart_ids, 1, (int)$this->cashierId);
        return $this->success('核销成功');
    }

    /**
     * 订单可用的优惠券列表
     * @param Request $request
     * @param CashierOrderServices $services
     * @param $uid
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function couponList(Request $request, CashierOrderServices $services, $uid)
    {
        [$cartIds] = $request->postMore([
            ['cart_id', []],
        ], true);
        if (!$uid) return $this->success([]);
        return $this->success($services->getCouponList((int)$uid, $this->storeId, $cartIds));
    }

    /**
     * 领取优惠券
     *
     * @param Request $request
     * @return mixed
     */
    public function couponReceive(Request $request, StoreCouponIssueServices $storeCouponIssueServices, UserServices $userServices, $uid)
    {
        list($couponId) = $request->getMore([
            ['couponId', 0]
        ], true);
        if (!$uid || !$couponId || !is_numeric($couponId)) return app('json')->fail('参数错误!');

        $userInfo = $userServices->getUserInfo($uid);
        if (!$userInfo) {
            return app('json')->fail('请选择用户');
        }
        $coupon = $storeCouponIssueServices->issueUserCoupon($couponId, $userInfo);
        if ($coupon) {
            $coupon = $coupon->toArray();
            return app('json')->success('领取成功', $coupon);
        }
        return app('json')->fail('领取失`败');
    }

    /**
     * 收银台删除挂单
     * @param Request $request
     * @param StoreCartServices $services
     * @return mixed
     */
    public function deleteHangOrder(Request $request, StoreCartServices $services)
    {
        $id = $request->get('id');
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $id = explode(',', $id) ?: [];
        if ($services->search(['id' => $id])->delete()) {
            return $this->success('删除成功');
        } else {
            return $this->fail('删除失败');
        }
    }

    /**
     * 收银台售后订单退款
     * @param Request $request
     * @param StoreOrderServices $services
     * @param StoreOrderRefundServices $refundService
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function agreeRefund(Request $request, StoreOrderServices $services, StoreOrderRefundServices $refundService, $id)
    {
        $data = $request->postMore([
            ['refund_price', 0],
            ['type', 1]
        ]);
        if (!$id) {
            return $this->fail('Data does not exist!');
        }
        $orderRefund = $refundService->get($id);
        if (!$orderRefund) {
            return $this->fail('Data does not exist!');
        }
        if ($orderRefund['is_cancel'] == 1) {
            return $this->fail('用户已取消申请');
        }
        $order = $services->get((int)$orderRefund['store_order_id']);
        if (!$order) {
            return $this->fail('Data does not exist!');
        }
        if (!in_array($orderRefund['refund_type'], [1, 2, 5])) {
            return $this->fail('售后订单状态不支持该操作');
        }

        if ($data['type'] == 1) {
            $data['refund_type'] = 6;
        } else if ($data['type'] == 2) {
            $data['refund_type'] = 3;
        }
        $data['refunded_time'] = time();
        $type = $data['type'];
        //拒绝退款
        if ($type == 2) {
            $refundService->refuseRefund((int)$orderRefund['id'], $data, $orderRefund);
            return $this->success('修改退款状态成功!');
        } else {
            //0元退款
            if ($orderRefund['refund_price'] == 0) {
                $refund_price = 0;
            } else {
                if (!$data['refund_price']) {
                    return $this->fail('请输入退款金额');
                }
                if ($orderRefund['refund_price'] == $orderRefund['refunded_price']) {
                    return $this->fail('已退完支付金额!不能再退款了');
                }
                $refund_price = $data['refund_price'];

                $data['refunded_price'] = bcadd((string)$data['refund_price'], (string)$orderRefund['refunded_price'], 2);
                $bj = bccomp((string)$orderRefund['refund_price'], (string)$data['refunded_price'], 2);
                if ($bj < 0) {
                    return $this->fail('退款金额大于支付金额，请修改退款金额');
                }
            }
            unset($data['type']);
            $refund_data['pay_price'] = $order['pay_price'];
            $refund_data['refund_price'] = $refund_price;

            //修改订单退款状态
            unset($data['refund_price']);
            if ($refundService->agreeRefund($id, $refund_data)) {
                //退款处理
                $refundService->update($id, $data);
                return $this->success('退款成功');
            } else {
                $refundService->storeProductOrderRefundYFasle((int)$id, $refund_price);
                return $this->fail('退款失败');
            }
        }
    }

    /**
     * 收银台获取配送员
     * @param DeliveryServiceServices $services
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function getDeliveryList(DeliveryServiceServices $services)
    {
        return $this->success($services->getDeliveryList(2, $this->storeId));
    }

    /**
     * 面单默认配置信息
     * @return mixed
     */
    public function getSheetInfo()
    {
        return $this->success([
            'express_temp_id' => store_config($this->storeId, 'store_config_export_temp_id'),
            'id' => store_config($this->storeId, 'store_config_export_id'),
            'to_name' => store_config($this->storeId, 'store_config_export_to_name'),
            'to_tel' => store_config($this->storeId, 'store_config_export_to_tel'),
            'to_add' => store_config($this->storeId, 'store_config_export_to_address'),
            'export_open' => (int)store_config($this->storeId, 'store_config_export_open') ? true : false
        ]);
    }

    /**
     * 收银台订单发货
     * @param Request $request
     * @param StoreOrderDeliveryServices $services
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function updateDelivery(Request $request, StoreOrderDeliveryServices $services, $id)
    {
        $data = $request->postMore([
            ['type', 1],
            ['delivery_name', ''],//快递公司名称
            ['delivery_id', ''],//快递单号
            ['delivery_code', ''],//快递公司编码

            ['express_record_type', 2],//发货记录类型
            ['express_temp_id', ""],//电子面单模板
            ['to_name', ''],//寄件人姓名
            ['to_tel', ''],//寄件人电话
            ['to_addr', ''],//寄件人地址

            ['sh_delivery_name', ''],//送货人姓名
            ['sh_delivery_id', ''],//送货人电话
            ['sh_delivery_uid', ''],//送货人ID

            ['fictitious_content', ''],//虚拟发货内容

            ['cart_ids', []]
        ]);
        if (!$id) {
            return $this->fail('缺少发货ID');
        }
        if (!$data['cart_ids']) {
            $res = $services->delivery((int)$id, $data, $this->cashierId);
            return $this->success('发货成功', $res);
        }
        foreach ($data['cart_ids'] as $cart) {
            if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num']) {
                return $this->fail('请重新选择发货商品，或发货件数');
            }
        }
        $res = $services->splitDelivery((int)$id, $data, $this->cashierId);
        return $this->success('发货成功', $res);
    }
}
