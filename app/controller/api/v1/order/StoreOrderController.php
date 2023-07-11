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

use app\Request;
use app\services\pay\PayServices;
use app\services\other\ExpressServices;
use app\services\system\admin\SystemAdminServices;
use app\services\user\UserAddressServices;
use app\services\user\UserInvoiceServices;
use app\services\user\UserServices;
use app\services\activity\{discounts\StoreDiscountsServices,
    lottery\LuckLotteryServices,
    bargain\StoreBargainServices,
    combination\StorePinkServices
};
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\order\{OtherOrderServices,
    StoreCartServices,
    StoreOrderCartInfoServices,
    StoreOrderComputedServices,
    StoreOrderCreateServices,
    StoreOrderEconomizeServices,
    StoreOrderRefundServices,
    StoreOrderServices,
    StoreOrderStatusServices,
    StoreOrderSuccessServices,
    StoreOrderTakeServices,
    StoreOrderPromotionsServices
};
use app\services\pay\OrderPayServices;
use app\services\pay\YuePayServices;
use app\services\product\product\StoreProductReplyServices;
use app\services\product\shipping\ShippingTemplatesServices;
use app\services\store\SystemStoreServices;
use crmeb\services\CacheService;

/**
 * 订单控制器
 * Class StoreOrderController
 * @package app\api\controller\order
 */
class StoreOrderController
{

    /**
     * @var StoreOrderServices
     */
    protected $services;

    /**
     * @var int[]
     */
    protected $getChennel = [
        'weixin' => 0,
        'routine' => 1,
        'weixinh5' => 2,
        'pc' => 3,
        'app' => 4
    ];

    /**
     * 地址信息
     * @var string[]
     */
    protected $addressInfo = [
        'id' => 0,
        'real_name' => '',
        'phone' => '',
        'province' => '',
        'city' => '',
        'district' => '',
        'street' => '',
        'detail' => '',
        'longitude' => '',
        'latitude' => ''
    ];

    /**
     * StoreOrderController constructor.
     * @param StoreOrderServices $services
     */
    public function __construct(StoreOrderServices $services)
    {
        $this->services = $services;
    }

    /**
     * 订单确认
     * @param Request $request
     * @param ShippingTemplatesServices $services
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function confirm(Request $request, ShippingTemplatesServices $services)
    {
        if (!$services->get(1, ['id'])) {
            return app('json')->fail('默认模板未配置，无法下单');
        }
        [$cartId, $new, $addressId, $shipping_type, $storeId, $couponId] = $request->postMore([
            'cartId',
            'new',
            ['addressId', 0],
            ['shipping_type', 1],
            ['store_id', 0],
            ['couponId', 0]
        ], true);
        if (!is_string($cartId) || !$cartId) {
            return app('json')->fail('请提交购买的商品');
        }
        $user = $request->user()->toArray();
        return app('json')->successful($this->services->getOrderConfirmData($user, $cartId, !!$new, (int)$addressId, (int)$shipping_type, (int)$storeId, (int)$couponId));
    }

    /**
     * 计算订单金额
     * @param Request $request
     * @param $key
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function computedOrder(Request $request, StoreOrderComputedServices $computedServices, $key)
    {
        if (!$key) return app('json')->fail('参数错误!');
        $uid = $request->uid();
        if ($checkOrder = $this->services->getOne(['order_id|unique' => $key, 'uid' => $uid, 'is_del' => 0], 'id,order_id'))
            return app('json')->status('extend_order', '订单已生成', ['orderId' => $checkOrder['order_id'], 'key' => $key]);
        list($addressId, $couponId, $payType, $useIntegral, $mark, $combinationId, $pinkId, $seckill_id, $bargainId, $shipping_type) = $request->postMore([
            'addressId', 'couponId', ['payType', 'yue'], ['useIntegral', 0], 'mark', ['combinationId', 0], ['pinkId', 0], ['seckill_id', 0], ['bargainId', ''],
            ['shipping_type', 1],
        ], true);
        $payType = strtolower($payType);
        $cartGroup = $this->services->getCacheOrderInfo($uid, $key);
        if (!$cartGroup) return app('json')->fail('订单已过期,请刷新当前页面!');
        $priceGroup = $computedServices->setParamData([
            'combinationId' => $combinationId,
            'pinkId' => $pinkId,
            'seckill_id' => $seckill_id,
            'bargainId' => $bargainId,
        ])->computedOrder($request->uid(), $request->user()->toArray(), $cartGroup, (int)$addressId, $payType, !!$useIntegral, (int)$couponId, (int)$shipping_type);

        if ($priceGroup)
            return app('json')->status('NONE', 'ok', $priceGroup);
        else
            return app('json')->fail('计算失败');
    }

    /**
     * 订单创建
     * @param Request $request
     * @param StoreOrderCreateServices $createServices
     * @param $key
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create(Request $request, StoreOrderCreateServices $createServices, $key)
    {
        if (!$key) return app('json')->fail('参数错误!');
        $uid = (int)$request->uid();
        if ($checkOrder = $this->services->getOne(['unique' => $key, 'uid' => $uid, 'is_del' => 0], 'id,order_id'))
            return app('json')->status('extend_order', '订单已创建，请点击查看完成支付', ['orderId' => $checkOrder['order_id'], 'key' => $key]);
        [$addressId, $couponId, $payType, $useIntegral, $mark, $combinationId, $pinkId, $seckill_id, $bargainId, $from, $shipping_type, $real_name, $phone, $storeId, $news, $invoice_id, $quitUrl, $discountId, $customForm] = $request->postMore([
            [['addressId', 'd'], 0],
            [['couponId', 'd'], 0],
            ['payType', ''],
            ['useIntegral', 0],
            ['mark', ''],
            [['combinationId', 'd'], 0],
            [['pinkId', 'd'], 0],
            [['seckill_id', 'd'], 0],
            [['bargainId', 'd'], ''],
            ['from', 'weixin'],
            [['shipping_type', 'd'], 1],
            ['real_name', ''],
            ['phone', ''],
            [['store_id', 'd'], 0],
            ['new', 0],
            [['invoice_id', 'd'], 0],
            ['quitUrl', ''],
            [['discountId', 'd'], 0],
            ['custom_form', []],
        ], true);
        $cartGroup = $this->services->getCacheOrderInfo($uid, $key);
        if (!$cartGroup) {
            return app('json')->fail('请不重复提交或订单已过期,请刷新当前页面!');
        }
        $cartInfo = $cartGroup['cartInfo'];
        if (!$cartInfo) {
            return app('json')->fail('订单已过期或提交的商品不在送达区域,请刷新当前页面或重新选择商品下单!');
        }
        $payType = strtolower($payType);
        if ($shipping_type == 1) {
            $cartInfo = $cartGroup['cartInfo'];
            $product_type = $cartInfo[0]['productInfo']['product_type'] ?? 0;
            //普通商品 验证地址
            if ($product_type == 0 && !$addressId) {
                return app('json')->fail('请选择收货地址!');
            }
            $addressInfo = ($cartGroup['addr'] ?? []) ?: $this->addressInfo;
            if ($addressId && (!$addressInfo || !isset($addressInfo['id']) || $addressInfo['id'] != $addressId)) {
                /** @var UserAddressServices $addressServices */
                $addressServices = app()->make(UserAddressServices::class);
                if (!$addressInfo = $addressServices->getOne(['uid' => $uid, 'id' => $addressId, 'is_del' => 0]))
                    return app('json')->fail('地址选择有误!');
                $addressInfo = $addressInfo->toArray();
            }
        } else {
            if (!$real_name || !$phone) {
                return app('json')->fail('请填写姓名和电话');
            }
            $addressInfo = $this->addressInfo;
            $addressInfo['real_name'] = $real_name;
            $addressInfo['phone'] = $phone;
        }
        //下单前砍价验证
        if ($bargainId) {
            /** @var StoreBargainServices $bargainServices */
            $bargainServices = app()->make(StoreBargainServices::class);
            $bargainServices->checkBargainUser((int)$bargainId, $uid);
        }
        //下单前发票验证
        if ($invoice_id) {
            /** @var UserInvoiceServices $userInvoiceServices */
            $userInvoiceServices = app()->make(UserInvoiceServices::class);
            $userInvoiceServices->checkInvoice((int)$invoice_id, $uid);
        }
        if ($pinkId) {
            $pinkId = (int)$pinkId;
            /** @var StorePinkServices $pinkServices */
            $pinkServices = app()->make(StorePinkServices::class);
            if ($pinkServices->isPink($pinkId, $uid))
                return app('json')->status('ORDER_EXIST', '订单生成失败，你已经在该团内不能再参加了', ['orderId' => $this->services->getStoreIdPink($pinkId, $uid)]);
            if ($this->services->getIsOrderPink($pinkId, $uid))
                return app('json')->status('ORDER_EXIST', '订单生成失败，你已经参加该团了，请先支付订单', ['orderId' => $this->services->getStoreIdPink($pinkId, $uid)]);
            if (!CacheService::checkStock(md5($pinkId), 1, 3) || !CacheService::popStock(md5($pinkId), 1, 3)) {
                return app('json')->fail('该团人员已满');
            }
        }
        if ($from != 'pc') {
            if (!$this->services->checkPaytype($payType)) {
                return app('json')->fail('暂不支持该支付方式，请刷新页面或者联系管理员');
            }
        }
        $isChannel = $this->getChennel[$from] ?? ($request->isApp() ? 4 : 1);
        if ($seckill_id || $combinationId || $discountId || $bargainId) {
            //套餐限量库
            if ($discountId) {
                /** @var StoreDiscountsServices $discountService */
                $discountService = app()->make(StoreDiscountsServices::class);
                $discounts = $discountService->get((int)$discountId);
                if (!$discounts) {
                    return app('json')->fail('套餐商品未找到！');
                }
                //套餐限量
                if ($discounts['is_limit'] && !CacheService::popStock(md5($discountId), 1, 5)) {
                    return app('json')->fail('您购买的套餐不足');
                }
            }
            foreach ($cartInfo as $item) {
                if (!isset($item['product_attr_unique']) || !$item['product_attr_unique']) continue;
                $type = $item['type'];
                if (in_array($type, [1, 2, 3]) && (!CacheService::checkStock($item['product_attr_unique'], (int)$item['cart_num'], $type) || !CacheService::popStock($item['product_attr_unique'], (int)$item['cart_num'], $type))) {
                    return app('json')->fail('您购买的商品库存已不足' . $item['cart_num'] . $item['productInfo']['unit_name']);
                }
            }
        }
        try {
            $msg = '';
            $order = $createServices->createOrder($uid, $key, $cartGroup, (int)$addressId, $payType, $addressInfo, $request->user()->toArray(), !!$useIntegral, $couponId, $mark, $pinkId, $isChannel, $shipping_type, $storeId, !!$news, $customForm, (int)$invoice_id, $from);
        } catch (\Throwable $e) {
            $order = false;
            $msg = $e->getMessage();
            \think\facade\Log::error('订单生成失败，原因：' . $msg . $e->getFile() . $e->getLine());
        }
        if ($order === false) {
            if ($seckill_id || $combinationId || $discountId || $bargainId) {
                //回退套餐限量库
                if ($discountId) CacheService::setStock(md5($discountId), 1, 5, false);
                foreach ($cartInfo as $item) {
                    if (!isset($item['product_attr_unique']) || !$item['product_attr_unique']) continue;
                    $type = $item['type'];
                    if (in_array($type, [1, 2, 3])) CacheService::setStock($item['product_attr_unique'], (int)$item['cart_num'], $type, false);
                }
            }
            return app('json')->fail($msg ?: '订单生成失败');
        }
        $orderId = $order['order_id'];
        if ($orderId) {
            $orderInfo = $order->toArray();
            if (!$orderInfo || !isset($orderInfo['paid'])) {
                return app('json')->fail('支付订单不存在!');
            }
            $info = compact('orderId', 'key');
            switch ($payType) {
                case PayServices::WEIXIN_PAY:
                    if ($orderInfo['paid']) return app('json')->fail('支付已支付!');
                    //支付金额为0
                    if (bcsub((string)$orderInfo['pay_price'], '0', 2) <= 0) {
                        //创建订单jspay支付
                        /** @var StoreOrderSuccessServices $success */
                        $success = app()->make(StoreOrderSuccessServices::class);
                        $payPriceStatus = $success->zeroYuanPayment($orderInfo, $uid, PayServices::WEIXIN_PAY);
                        if ($payPriceStatus)//0元支付成功
                            return app('json')->status('success', '微信支付成功', $info);
                        else
                            return app('json')->status('pay_error');
                    } else {
                        /** @var OrderPayServices $payServices */
                        $payServices = app()->make(OrderPayServices::class);
                        if (!$from && $request->isApp()) {
                            $from = 'weixin';
                        }
                        $info['jsConfig'] = $payServices->orderPay($orderInfo, $from);
                        if ($from == 'weixinh5') {
                            return app('json')->status('wechat_h5_pay', '订单创建成功', $info);
                        } else {
                            return app('json')->status('wechat_pay', '订单创建成功', $info);
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
                    [$url, $param] = explode('?', $quitUrl);
                    if ($param) {
                        $quitUrl = $quitUrl . '&order_id=' . $orderInfo['order_id'];
                    } else {
                        $quitUrl = $url . '?order_id=' . $orderInfo['order_id'];
                    }
                    //支付金额为0
                    if (bcsub((string)$orderInfo['pay_price'], '0', 2) <= 0) {
                        //创建订单jspay支付
                        /** @var StoreOrderSuccessServices $success */
                        $success = app()->make(StoreOrderSuccessServices::class);
                        $payPriceStatus = $success->zeroYuanPayment($orderInfo, $uid, PayServices::ALIAPY_PAY);
                        if ($payPriceStatus)//0元支付成功
                            return app('json')->status('success', '支付宝支付成功', $info);
                        else
                            return app('json')->status('pay_error');
                    } else {
                        /** @var OrderPayServices $payServices */
                        $payServices = app()->make(OrderPayServices::class);
                        $info['jsConfig'] = $payServices->alipayOrder($orderInfo, $quitUrl, $from == 'routine');
                        $payKey = md5($orderInfo['order_id']);
                        CacheService::set($payKey, ['order_id' => $orderInfo['order_id'], 'other_pay_type' => false], 300);
                        $info['pay_key'] = $payKey;
                        return app('json')->status(PayServices::ALIAPY_PAY . '_pay', '订单创建成功', $info);
                    }
                    break;
                default:
                    return app('json')->status('success', '订单创建成功', $info);
                    break;
            }
        } else return app('json')->fail('订单生成失败!');
    }

    /**
     * 订单 再次下单
     * @param Request $request
     * @return mixed
     */
    public function again(Request $request, StoreCartServices $services)
    {
        list($uni) = $request->postMore([
            ['uni', ''],
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $order = $this->services->getUserOrderDetail($uni, (int)$request->uid());
        if (!$order) return app('json')->fail('订单不存在!');
        if (in_array($order['type'], [1, 2, 3, 5])) {
            $msg = '';
            switch ($order['type']) {
                case 1://秒杀
                    $msg = '秒杀';
                    break;
                case 2://砍价
                    $msg = '砍价';
                    break;
                case 3://拼团
                    $msg = '拼团';
                    break;
                case 5://套餐
                    $msg = '套餐';
                    break;
            }
            return app('json')->fail($msg . '商品不能再来一单，请在' . $msg . '商品内自行下单!');
        }
        $order = $this->services->tidyOrder($order, true);
        $cateId = [];
        foreach ($order['cartInfo'] as $v) {
            if ($v['type'] == 0) $cateId[] = $services->setCart($request->uid(), (int)$v['product_id'], (int)$v['cart_num'], isset($v['productInfo']['attrInfo']['unique']) ? $v['productInfo']['attrInfo']['unique'] : '');
        }
        if (!$cateId) return app('json')->fail('再来一单失败，请重新下单!');
        return app('json')->successful('ok', ['cateId' => implode(',', $cateId)]);
    }


    /**
     * 订单支付
     * @param Request $request
     * @param StorePinkServices $services
     * @param OrderPayServices $payServices
     * @param YuePayServices $yuePayServices
     * @return mixed
     */
    public function pay(Request $request, StorePinkServices $services, OrderPayServices $payServices, YuePayServices $yuePayServices)
    {
        [$uni, $paytype, $from, $quitUrl] = $request->postMore([
            ['uni', ''],
            ['paytype', 'weixin'],
            ['from', 'weixin'],
            ['quitUrl', '']
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $order = $this->services->getUserOrderDetail($uni, (int)$request->uid());
        if (!$order)
            return app('json')->fail('订单不存在!');
        if ($order['paid'])
            return app('json')->fail('该订单已支付!');
        if ($order['pink_id'] && $services->isPinkStatus($order['pink_id'])) {
            return app('json')->fail('该订单已失效!');
        }

//        $isChannel = $this->getChennel[$from] ?? ($request->isApp() ? 4 : 1);
        //缓存不存在 ｜｜ 切换另一端支付
//        if (!CacheService::get('pay_' . $order['order_id']) || $isChannel != $order['is_channel']) {
//            mt_srand();
//            switch ($from) {
//                case 'weixin':
//                    if (in_array($order->is_channel, [1, 2, 3, 4])) {//0
//                        $order['order_id'] = mt_rand(100, 999) . '_' . $order['order_id'];
//                    }
//                    break;
//                case 'weixinh5':
//                    if (in_array($order->is_channel, [0, 1, 3, 4])) {
//                        $order['order_id'] = mt_rand(100, 999) . '_' . $order['order_id'];
//                    }
//                    break;
//                case 'routine':
//                    if (in_array($order->is_channel, [0, 2, 3, 4])) {
//                        $order['order_id'] = mt_rand(100, 999) . '_' . $order['order_id'];
//                    }
//                    break;
//                case 'pc':
//                case 'aliapy':
//                    $order['order_id'] = mt_rand(100, 999) . '_' . $order['order_id'];
//                    break;
//            }
//        }

        //只要重新支付就更新订单号
        if (in_array($paytype, [PayServices::ALIAPY_PAY, PayServices::WEIXIN_PAY])) {
            mt_srand();
            $order['order_id'] = mt_rand(100, 999) . '_' . $order['order_id'];
        }

        $order['pay_type'] = $paytype; //重新支付选择支付方式
        switch ($order['pay_type']) {
            case PayServices::WEIXIN_PAY:
                $jsConfig = $payServices->orderPay($order->toArray(), $from);
                if ($from == 'weixinh5') {
                    return app('json')->status('wechat_h5_pay', ['jsConfig' => $jsConfig, 'order_id' => $order['order_id']]);
                } elseif ($from == 'weixin' || $from == 'routine') {
                    return app('json')->status('wechat_pay', ['jsConfig' => $jsConfig, 'order_id' => $order['order_id']]);
                } elseif ($from == 'pc') {
                    return app('json')->status('wechat_pc_pay', ['jsConfig' => $jsConfig, 'order_id' => $order['order_id']]);
                }
                break;
            case PayServices::ALIAPY_PAY:
                if (!$quitUrl && $from != 'routine') {
                    return app('json')->fail('请传入支付宝支付回调URL');
                }
                $isCode = $from == 'routine' || $from == 'pc';
                $jsConfig = $payServices->alipayOrder($order->toArray(), $quitUrl, $isCode);
                if ($isCode && !($jsConfig->invalid ?? false)) $jsConfig->invalid = time() + 60;
                $payKey = md5($order['order_id']);
                CacheService::set($payKey, ['order_id' => $order['order_id'], 'other_pay_type' => false], 300);
                return app('json')->status(PayServices::ALIAPY_PAY . '_pay', '订单创建成功', ['jsConfig' => $jsConfig, 'order_id' => $order['order_id'], 'pay_key' => $payKey]);
                break;
            case PayServices::YUE_PAY:
                $pay = $yuePayServices->yueOrderPay($order->toArray(), $request->uid());
                if ($pay['status'] === true)
                    return app('json')->status('success', '余额支付成功');
                else {
                    if (is_array($pay))
                        return app('json')->status($pay['status'], $pay['msg']);
                    else
                        return app('json')->status('pay_error', $pay);
                }
                break;
            case PayServices::OFFLINE_PAY:
                if ($this->services->setOrderTypePayOffline($order['order_id']))
                    return app('json')->status('success', '订单创建成功');
                else
                    return app('json')->status('success', '支付失败');
                break;
        }
        return app('json')->fail('支付方式错误');
    }

    /**
     * 支付宝单独支付
     * @param OrderPayServices $payServices
     * @param OtherOrderServices $services
     * @param string $key
     * @param string $quitUrl
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function aliPay(OrderPayServices $payServices, OtherOrderServices $services, string $key, string $quitUrl)
    {
        if (!$key || !($orderCache = CacheService::get($key))) {
            return app('json')->fail('该订单无法支付');
        }
        if (!isset($orderCache['order_id'])) {
            return app('json')->fail('该订单无法支付');
        }
        $payType = isset($orderCache['other_pay_type']) && $orderCache['other_pay_type'] == true;
        if ($payType) {
            $orderInfo = $services->getOne(['order_id' => $orderCache['order_id'], 'is_del' => 0, 'paid' => 0]);
        } else {
            $orderInfo = $this->services->get(['order_id' => $orderCache['order_id'], 'paid' => 0, 'is_del' => 0]);
        }

        if (!$orderInfo) {
            return app('json')->fail('订单支付状态有误，无法进行支付');
        }
        if (!$quitUrl) {
            return app('json')->fail('请传入支付宝支付回调URL');
        }
        $payInfo = $payServices->alipayOrder($orderInfo->toArray(), $quitUrl);
        return app('json')->success(['pay_content' => $payInfo]);
    }

    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        $where = $request->getMore([
            ['type', '', '', 'status'],
            ['search', '', '', 'real_name'],
            ['refund_type', '', '', 'refundTypes']
        ]);

        $where['uid'] = $request->uid();
        $where['is_del'] = 0;
        $where['is_system_del'] = 0;
        if ($where['status'] === '') {
            $where['pid'] = 0;
        } elseif (in_array($where['status'], [-1, -2, -3])) {
            $where['not_pid'] = 1;
        } elseif (in_array($where['status'], [0, 1, 2, 3, 4])) {
            $where['pid'] = 0;
        }
        $list = $this->services->getOrderApiList($where);
        return app('json')->successful($list);
    }

    /**
     * 订单详情
     * @param Request $request
     * @param StoreOrderEconomizeServices $services
     * @param StoreOrderPromotionsServices $storeOrderPromotiosServices
     * @param $uni
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail(Request $request, StoreOrderEconomizeServices $services, StoreOrderPromotionsServices $storeOrderPromotiosServices, $uni)
    {
        if (!strlen(trim($uni))) return app('json')->fail('参数错误');
        $order = $this->services->getUserOrderDetail($uni, (int)$request->uid(), ['invoice']);
        if (!$order) return app('json')->fail('订单不存在');
        if ($order['pid'] == -1) return app('json')->fail('订单已被拆分为多个订单，请查看订单列表');
        $order = $order->toArray();
        $order['split'] = [];
        //是否开启门店自提
        $store_self_mention = sys_config('store_self_mention');
        //关闭门店自提后 订单隐藏门店信息
        if ($store_self_mention == 0) $order['shipping_type'] = 1;
        if ($order['verify_code']) {
            $verify_code = $order['verify_code'];
            $verify[] = substr($verify_code, 0, 4);
            $verify[] = substr($verify_code, 4, 4);
            $verify[] = substr($verify_code, 8);
            $order['_verify_code'] = implode(' ', $verify);
        }
        //收银台订单 用户无信息 手机号
        if ($order['shipping_type'] == 4 && $order['uid'] && !$order['real_name']) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->getUserCacheInfo((int)$order['uid']);
            $order['real_name'] = $userInfo['nickname'];
            $order['user_phone'] = $userInfo['phone'];
        }
        $order['add_time_y'] = date('Y-m-d', $order['add_time']);
        $order['add_time_h'] = date('H:i:s', $order['add_time']);
        $order['system_store'] = false;
        if (!$order['store_id'] && $order['shipping_type'] == 2) {
            $order['store_id'] = $this->services->value(['pid' => $order['id']], 'store_id');
        }
        if ($order['store_id']) {
            /** @var SystemStoreServices $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $order['system_store'] = $storeServices->getStoreDispose($order['store_id']);
        }
        $order['mapKey'] = sys_config('tengxun_map_key');
        $order['yue_pay_status'] = (int)sys_config('balance_func_status') && (int)sys_config('yue_pay_status') == 1 ? (int)1 : (int)2;//余额支付 1 开启 2 关闭
        $order['pay_weixin_open'] = (int)sys_config('pay_weixin_open') ?? 0;//微信支付 1 开启 0 关闭
        $order['ali_pay_status'] = sys_config('ali_pay_status') ? true : false;//支付包支付 1 开启 0 关闭

        $orderData = $this->services->tidyOrder($order, true, true);
        //核算优惠金额
        $vipTruePrice = 0;
        $refund_num = 0;
        foreach ($orderData['cartInfo'] ?? [] as $key => &$cart) {
            $vipTruePrice = bcadd((string)$vipTruePrice, (string)$cart['vip_sum_truePrice'], 2);
            $refund_num = bcadd((string)$refund_num, (string)$cart['refund_num'], 0);
        }
        $orderData['vip_true_price'] = $vipTruePrice;
        $orderData['total_price'] = floatval(bcsub((string)$orderData['total_price'], (string)$vipTruePrice, 2));
        //优惠活动优惠详情
        $orderData['promotions_detail'] = $storeOrderPromotiosServices->getOrderPromotionsDetail((int)$order['id']);
        //同步查询订单商品为查询到 查询缓存信息
        if (!$orderData['cartInfo']) {
            $cartGroup = $this->services->getCacheOrderInfo((int)$order['uid'], $order['unique']);
            $orderData['cartInfo'] = $cartGroup['cartInfo'] ?? [];
        }

        $economize = $services->get(['order_id' => $order['order_id']], ['postage_price', 'member_price']);
        if ($economize) {
            $orderData['postage_price'] = $economize['postage_price'];
            $orderData['member_price'] = $economize['member_price'];
        } else {
            $orderData['postage_price'] = 0;
            $orderData['member_price'] = 0;
        }
        $orderData['routine_contact_type'] = sys_config('routine_contact_type', 0);
        /** @var UserInvoiceServices $userInvoice */
        $userInvoice = app()->make(UserInvoiceServices::class);
        $invoice_func = $userInvoice->invoiceFuncStatus();
        $orderData['invoice_func'] = $invoice_func['invoice_func'];
        $orderData['special_invoice'] = $invoice_func['special_invoice'];
        $orderData['refund_cartInfo'] = [];
        $orderData['refund_total_num'] = $orderData['total_num'];
        $orderData['refund_pay_price'] = $orderData['pay_price'];
        $orderData['is_apply_refund'] = $refund_num >= $orderData['total_num'] ? false : true;
        $orderData['is_batch_refund'] = count($orderData['cartInfo']) > 1 ? true : false;
        $orderData['pinkStatus'] = null;
        if ($orderData['type'] == 3) {
            /** @var StorePinkServices $pinkService */
            $pinkService = app()->make(StorePinkServices::class);
            $orderData['pinkStatus'] = $pinkService->value(['order_id' => $orderData['order_id']], 'status');
        }

        /** @var StoreOrderStatusServices $statusServices */
        $statusServices = app()->make(StoreOrderStatusServices::class);
        $log = $statusServices->getColumn(['oid' => $order['id']], 'change_time', 'change_type');
        if (isset($log['delivery'])) {
            $delivery = date('Y-m-d', $log['delivery']);
        } elseif (isset($log['delivery_goods'])) {
            $delivery = date('Y-m-d', $log['delivery_goods']);
        } elseif (isset($log['delivery_fictitious'])) {
            $delivery = date('Y-m-d', $log['delivery_fictitious']);
        } else {
            $delivery = '';
        }
        $orderData['order_log'] = [
            'create' => isset($log['cache_key_create_order']) ? date('Y-m-d', $log['cache_key_create_order']) : '',
            'pay' => isset($log['pay_success']) ? date('Y-m-d', $log['pay_success']) : '',
            'delivery' => $delivery,
            'take' => isset($log['take_delivery']) ? date('Y-m-d', $log['take_delivery']) : '',
            'complete' => isset($log['check_order_over']) ? date('Y-m-d', $log['check_order_over']) : '',
        ];
        if ($orderData['give_coupon']) {
            $couponIds = is_string($orderData['give_coupon']) ? explode(',', $orderData['give_coupon']) : $orderData['give_coupon'];
            /** @var StoreCouponIssueServices $couponIssueService */
            $couponIssueService = app()->make(StoreCouponIssueServices::class);
            $orderData['give_coupon'] = $couponIssueService->getColumn([['id', 'IN', $couponIds]], 'id,coupon_title');
        }
        return app('json')->successful('ok', $orderData);
    }

    /**
     * 订单删除
     * @param Request $request
     * @return mixed
     */
    public function del(Request $request)
    {
        [$uni] = $request->postMore([
            ['uni', ''],
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $res = $this->services->removeOrder($uni, (int)$request->uid());
        if ($res) {
            return app('json')->successful();
        } else {
            return app('json')->fail('删除失败');
        }
    }

    /**
     * 订单收货
     * @param Request $request
     * @return mixed
     */
    public function take(Request $request, StoreOrderTakeServices $services, StoreCouponIssueServices $issueServices)
    {
        list($uni) = $request->postMore([
            ['uni', ''],
        ], true);
        if (!$uni) return app('json')->fail('参数错误!');
        $order = $services->takeOrder($uni, (int)$request->uid());
        if ($order) {
            return app('json')->successful('收货成功');
        } else
            return app('json')->fail('收货失败');
    }


    /**
     * 订单 查看物流
     * @param Request $request
     * @param StoreOrderCartInfoServices $services
     * @param ExpressServices $expressServices
     * @param $uni
     * @param string $type
     * @return mixed
     */
    public function express(Request $request, StoreOrderCartInfoServices $services, ExpressServices $expressServices, $uni, $type = '')
    {
        if ($type == 'refund') {
            /** @var StoreOrderRefundServices $refundService */
            $refundService = app()->make(StoreOrderRefundServices::class);
            $order = $refundService->refundDetail($uni);
            $express = $order['refund_express'];
            $cacheName = $uni . $express;
            $orderInfo = [];
            $info = [];
            $cartNew = [];
            foreach ($order['cart_info'] as $k => $cart) {
                $cartNew['cart_num'] = $cart['cart_num'];
                $cartNew['truePrice'] = $cart['truePrice'];
                $cartNew['productInfo']['image'] = $cart['productInfo']['image'];
                $cartNew['productInfo']['store_name'] = $cart['productInfo']['store_name'];
                $cartNew['productInfo']['unit_name'] = $cart['productInfo']['unit_name'] ?? '';
                array_push($info, $cartNew);
                unset($cart);
            }
            $orderInfo['cartInfo'] = $info;
            $orderInfo['delivery_id'] = $express;
            $orderInfo['delivery_name'] = $order['refund_express_name'];
            $orderInfo['delivery_code'] = '';
            $orderInfo['user_address'] = $order['user_address'];
            $orderInfo['user_mark'] = $order['refund_explain'];
        } else {
            if (!$uni || !($order = $this->services->getUserOrderDetail($uni, $request->uid()))) return app('json')->fail('查询订单不存在!');
            if ($type != 'refund' && ($order['delivery_type'] != 'express' || !$order['delivery_id'])) return app('json')->fail('该订单不存在快递单号!');
            $express = $type == 'refund' ? $order['refund_express'] : $order['delivery_id'];
            $cacheName = $uni . $express;
            $orderInfo = [];
            $cartInfo = $services->getCartColunm(['oid' => $order['id']], 'cart_info', 'unique');
            $info = [];
            $cartNew = [];
            foreach ($cartInfo as $k => $cart) {
                $cart = json_decode($cart, true);
                $cartNew['cart_num'] = $cart['cart_num'];
                $cartNew['truePrice'] = $cart['truePrice'];
                $cartNew['productInfo']['image'] = $cart['productInfo']['image'];
                $cartNew['productInfo']['store_name'] = $cart['productInfo']['store_name'];
                $cartNew['productInfo']['unit_name'] = $cart['productInfo']['unit_name'] ?? '';
                array_push($info, $cartNew);
                unset($cart);
            }
            $orderInfo['delivery_id'] = $express;
            $orderInfo['delivery_name'] = $type == 'refund' ? '用户退回' : $order['delivery_name'];;
            $orderInfo['delivery_code'] = $type == 'refund' ? '' : $order['delivery_code'];
            $orderInfo['delivery_type'] = $order['delivery_type'];
            $orderInfo['user_address'] = $order['user_address'];
            $orderInfo['user_mark'] = $order['mark'];
            $orderInfo['cartInfo'] = $info;
        }
        return app('json')->successful([
            'order' => $orderInfo,
            'express' => [
                'result' => ['list' => $expressServices->query($cacheName, $orderInfo['delivery_id'], $orderInfo['delivery_code'])
                ]
            ]
        ]);
    }


    /**
     * 订单评价
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function comment(Request $request, StoreOrderCartInfoServices $cartInfoServices, StoreProductReplyServices $replyServices)
    {

        $group = $request->postMore([
            ['unique', ''], ['comment', ''], ['pics', ''], ['product_score', 5], ['service_score', 5]
        ]);
        $unique = $group['unique'];
        unset($group['unique']);
        if (!$unique) return app('json')->fail('参数错误!');
        $cartInfo = $cartInfoServices->getOne(['unique' => $unique]);
        $uid = $request->uid();
        $user_info = $request->user();
        $group['nickname'] = $user_info['nickname'];
        $group['avatar'] = $user_info['avatar'];
        if (!$cartInfo) return app('json')->fail('评价商品不存在!');
        $order = $this->services->getOne(['id' => $cartInfo['oid']], 'uid,store_id');
        if ($uid != $order['uid']) return app('json')->fail('评价商品不存在!');
        if ($replyServices->be(['oid' => $cartInfo['oid'], 'unique' => $unique]))
            return app('json')->fail('该商品已评价!');
        $group['comment'] = htmlspecialchars(trim($group['comment']));
        if ($group['product_score'] < 1) return app('json')->fail('请为商品评分');
        else if ($group['service_score'] < 1) return app('json')->fail('请为商家服务评分');
        if (isset($cartInfo['cart_info']['activity_id']) && $cartInfo['cart_info']['activity_id']) $productId = $cartInfo['cart_info']['product_id'];
        else $productId = $cartInfo['product_id'];
        if ($group['pics']) $group['pics'] = json_encode(is_array($group['pics']) ? $group['pics'] : explode(',', $group['pics']));
        $group = array_merge($group, [
            'uid' => $uid,
            'oid' => $cartInfo['oid'],
            'store_id' => $order['store_id'],
            'unique' => $unique,
            'product_id' => $productId,
            'add_time' => time(),
            'reply_type' => 'product'
        ]);

        $res = $replyServices->save($group);
        if (!$res) {
            return app('json')->fail('评价失败!');
        } else {
            //订单评价成功事件
            event('order.comment', [$group, $order]);
        }
        try {
            $this->services->checkOrderOver($replyServices, $cartInfoServices->getCartColunm(['oid' => $cartInfo['oid'], 'is_gift' => 0], 'unique', ''), $cartInfo['oid']);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
        //缓存抽奖次数
        /** @var LuckLotteryServices $luckLotteryServices */
        $luckLotteryServices = app()->make(LuckLotteryServices::class);
        $luckLotteryServices->setCacheLotteryNum((int)$order['uid'], 'comment');

        /** @var SystemAdminServices $systemAdmin */
        $systemAdmin = app()->make(SystemAdminServices::class);
        $systemAdmin->adminNewPush();

        $lottery = $luckLotteryServices->getFactorLottery(4);
        if (!$lottery) {
            return app('json')->successful(['to_lottery' => false]);
        }
        $lottery = $lottery->toArray();
        try {
            $luckLotteryServices->checkoutUserAuth($uid, (int)$lottery['id'], [], $lottery);
            $lottery_num = $luckLotteryServices->getLotteryNum($uid, (int)$lottery['id'], [], $lottery);
            if ($lottery_num > 0) return app('json')->successful(['to_lottery' => true]);
        } catch (\Exception $e) {
            return app('json')->successful(['to_lottery' => false]);
        }
    }

    /**
     * 订单统计数据
     * @param Request $request
     * @return mixed
     */
    public function data(Request $request)
    {
        return app('json')->successful($this->services->getOrderData((int)$request->uid()));
    }

    /**
     * 订单退款理由
     * @return mixed
     */
    public function refund_reason()
    {
        $reason = sys_config('stor_reason') ?: [];//退款理由
        $reason = str_replace("\r\n", "\n", $reason);//防止不兼容
        $reason = explode("\n", $reason);
        return app('json')->successful($reason);
    }

    /**
     * 获取退货商品列表
     * @param StoreOrderCartInfoServices $services
     * @param $id
     * @return mixed
     */
    public function refundCartInfo(Request $request, StoreOrderCartInfoServices $services, $id)
    {
        if (!$id) {
            return app('json')->fail('缺少发货ID');
        }
        [$cart_ids] = $request->postMore([
            ['cart_ids', []]
        ], true);
        $list = $services->getRefundCartList((int)$id);
        if ($cart_ids) {
            foreach ($cart_ids as $cart) {
                if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num'] || $cart['cart_num'] <= 0) {
                    return app('json')->fail('请重新选择退款商品，或件数');
                }
            }
            $cart_ids = array_combine(array_column($cart_ids, 'cart_id'), $cart_ids);
            foreach ($list as &$item) {
                if (isset($cart_ids[$item['cart_id']]['cart_num'])) $item['cart_num'] = $cart_ids[$item['cart_id']]['cart_num'];
            }
        }
        return app('json')->success($list);
    }

    /**
     * 获取退货商品列表
     * @param StoreOrderCartInfoServices $services
     * @param $id
     * @return mixed
     */
    public function refundCartInfoList(Request $request)
    {
        [$cart_ids, $id] = $request->postMore([
            ['cart_ids', []],
            ['id', 0],
        ], true);
        if (!$id) {
            return app('json')->fail('缺少发货ID');
        }
        return app('json')->success($this->services->refundCartInfoList((array)$cart_ids, (int)$id));
    }

    /**
     * 用户申请退款
     * @param Request $request
     * @return mixed
     */
    public function applyRefund(Request $request, StoreOrderRefundServices $services, StoreOrderServices $storeOrderServices, $id)
    {
        if (!$id) {
            return app('json')->fail('缺少参数!');
        }
        $data = $request->postMore([
            ['text', ''],
            ['refund_reason_wap_img', ''],
            ['refund_reason_wap_explain', ''],
            ['refund_type', 1],
            ['refund_price', 0.00],
            ['cart_ids', []]
        ]);
        if ($data['text'] == '') return app('json')->fail('参数错误!');
        if ($data['cart_ids']) {
            foreach ($data['cart_ids'] as $cart) {
                if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num']) {
                    return app('json')->fail('请重新选择退款商品，或件数');
                }
            }
        }

        $order = $storeOrderServices->get($id);
        $uid = (int)$request->uid();
        if (!$order || $uid != $order['uid']) {
            return app('json')->fail('订单不存在!');
        }
        if (!$order['paid']) {
            return app('json')->fail('请先完成支付!');
        }
        // 开启 ERP 发货后不能申请退货
        if (sys_config('erp_open') && $storeOrderServices->getDeliverNum($id, $order)) {
            return app('json')->fail('订单已发货, 不能申请退货!');
        }

        $refundData = [
            'refund_reason' => $data['text'],
            'refund_explain' => $data['refund_reason_wap_explain'],
            'refund_img' => json_encode($data['refund_reason_wap_img'] != '' ? explode(',', $data['refund_reason_wap_img']) : []),
        ];
        $res = $services->applyRefund((int)$id, $uid, $order, $data['cart_ids'], (int)$data['refund_type'], (float)$data['refund_price'], $refundData);
        if ($res)
            return app('json')->successful('提交申请成功');
        else
            return app('json')->fail('提交失败');
    }

    /**
     * 用户申请退款
     * @param Request $request
     * @return mixed
     */
    public function refund_verify(Request $request, StoreOrderRefundServices $services)
    {
        $data = $request->postMore([
            ['text', ''],
            ['refund_reason_wap_img', ''],
            ['refund_reason_wap_explain', ''],
            ['uni', ''],
            ['refund_type', 1],
            ['refund_price', 0.00],
            ['cart_ids', []]
        ]);
        $uni = $data['uni'];
        unset($data['uni']);
        if (!$uni || $data['text'] == '') return app('json')->fail('参数错误!');

        $refundData = [
            'refund_reason' => $data['text'],
            'refund_explain' => $data['refund_reason_wap_explain'],
            'refund_img' => json_encode($data['refund_reason_wap_img'] != '' ? explode(',', $data['refund_reason_wap_img']) : []),
        ];
        $order = $this->services->getUserOrderDetail($uni, (int)$request->uid());
        if (!$order) {
            return app('json')->fail('订单不存在!');
        }
        if (!$order['paid']) {
            return app('json')->fail('请先完成支付!');
        }
        $uid = (int)$request->uid();
        $res = $services->applyRefund((int)$order['id'], $uid, $order, $data['cart_ids'], (int)$data['refund_type'], (float)$data['refund_price'], $refundData);
        if ($res)
            return app('json')->successful('提交申请成功');
        else
            return app('json')->fail('提交失败');
    }

    /**
     * 用户退货提交快递单号
     * @param Request $request
     * @param StoreOrderRefundServices $services
     * @return mixed
     */
    public function refund_express(Request $request, StoreOrderRefundServices $services)
    {
        $data = $request->postMore([
            ['id', ''],
            ['refund_express', ''],
            ['refund_phone', ''],
            ['refund_express_name', ''],
            ['refund_img', '', '', 'refund_goods_img'],
            ['refund_explain', '', '', 'refund_goods_explain'],
        ]);
        if ($data['id'] == '') return app('json')->fail('参数错误!');
        $data['refund_goods_img'] = json_encode($data['refund_goods_img'] != '' ? explode(',', $data['refund_goods_img']) : []);
        $res = $services->editRefundExpress($data);
        if ($res)
            return app('json')->successful('提交成功');
        else
            return app('json')->fail('提交失败');
    }

    /**
     * 订单取消   未支付的订单回退积分,回退优惠券,回退库存
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancel(Request $request)
    {
        list($id) = $request->postMore([['id', 0]], true);
        if (!$id) return app('json')->fail('参数错误');
        if ($this->services->cancelOrder($id, (int)$request->uid()))
            return app('json')->successful('取消订单成功');
        return app('json')->fail('取消订单失败');
    }


    /**
     * 订单商品信息
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function product(Request $request, StoreOrderCartInfoServices $services)
    {
        list($unique) = $request->postMore([['unique', '']], true);
        if (!$unique || !($cartInfo = $services->getOne(['unique' => $unique]))) return app('json')->fail('评价商品不存在!');
        $cartInfo = $cartInfo->toArray();
        $cartProduct = [];
        $cartProduct['cart_num'] = $cartInfo['cart_info']['cart_num'];
        $cartProduct['productInfo']['image'] = get_thumb_water($cartInfo['cart_info']['productInfo']['image'] ?? '');
        $cartProduct['productInfo']['price'] = $cartInfo['cart_info']['productInfo']['price'] ?? 0;
        $cartProduct['productInfo']['store_name'] = $cartInfo['cart_info']['productInfo']['store_name'] ?? '';
        if (isset($cartInfo['cart_info']['productInfo']['attrInfo'])) {
            $cartProduct['productInfo']['attrInfo']['product_id'] = $cartInfo['cart_info']['productInfo']['attrInfo']['product_id'] ?? '';
            $cartProduct['productInfo']['attrInfo']['suk'] = $cartInfo['cart_info']['productInfo']['attrInfo']['suk'] ?? '';
            $cartProduct['productInfo']['attrInfo']['price'] = $cartInfo['cart_info']['productInfo']['attrInfo']['price'] ?? '';
            $cartProduct['productInfo']['attrInfo']['image'] = get_thumb_water($cartInfo['cart_info']['productInfo']['attrInfo']['image'] ?? '');
        }
        $cartProduct['product_id'] = $cartInfo['cart_info']['product_id'] ?? 0;
        $cartProduct['type'] = $cartInfo['cart_info']['type'] ?? 0;
        $cartProduct['activity_id'] = $cartInfo['cart_info']['activity_id'] ?? 0;
        $cartProduct['order_id'] = $this->services->value(['id' => $cartInfo['oid']], 'order_id');
        return app('json')->successful($cartProduct);
    }

    /**
     * 门店线上支付订单详情
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function payCashierOrder(Request $request)
    {
        list($store_id) = $request->postMore([['store_id', '']], true);
        $uid = $request->uid();
        return app('json')->successful($this->services->payCashierOrder((int)$store_id, (int)$uid));
    }

}
