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

namespace app\services\order\cashier;


use app\jobs\activity\StorePromotionsJob;use app\jobs\user\MicroPayOrderJob;
use app\services\BaseServices;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\activity\promotions\StorePromotionsServices;
use app\services\order\StoreCartServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderComputedServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderSuccessServices;
use app\services\pay\PayServices;
use app\services\pay\YuePayServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\category\StoreCategoryServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\store\SystemStoreStaffServices;
use app\services\user\UserAddressServices;
use app\services\user\UserServices;
use crmeb\services\CacheService;
use crmeb\traits\OptionTrait;
use think\exception\ValidateException;

/**
 * 收银台订单
 * Class CashierOrderServices
 * @package app\services\order\cashier
 */
class CashierOrderServices extends BaseServices
{

    use OptionTrait;

    //余额支付
    const YUE_PAY = 1;
    //线上支付
    const ONE_LINE_PAY = 2;
    //现金支付
    const CASH_PAY = 3;




    /**
     * 计算某个门店中收银台的金额
     * @param int $uid
     * @param int $storeId
     * @param array $cartIds
     * @param bool $integral
     * @param bool $coupon
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function computeOrder(int $uid, int $storeId, array $cartIds, bool $integral = false, bool $coupon = false, array $userInfo = [], int $coupon_id = 0, bool $new = false)
    {
        if (!$userInfo && $uid) {
            /** @var UserServices $userService */
            $userService = app()->make(UserServices::class);
            $userInfo = $userService->getUserInfo($uid);
            if (!$userInfo) {
                throw new ValidateException('用户不存在');
            }
            $userInfo = $userInfo->toArray();
        }
        /** @var StoreCartServices $cartService */
        $cartServices = app()->make(StoreCartServices::class);
        //获取购物车信息
        $cartGroup = $cartServices->getUserProductCartListV1($uid, $cartIds, $new, [], 4, $storeId, $coupon_id);
        $cartInfo = array_merge($cartGroup['valid'], $cartGroup['giveCartList']);
        if (!$cartInfo) {
            throw new ValidateException('购物车暂无货物！');
        }
        $promotionsDetail = [];
        $promotions = $cartGroup['promotions'] ?? [];
        if ($promotions) {
            foreach ($promotions as $key => $value) {
                if (isset($value['details']['sum_promotions_price']) && $value['details']['sum_promotions_price']) {
                    $promotionsDetail[] = ['id' => $value['id'], 'name' => $value['name'], 'title' => $value['title'], 'desc' => $value['desc'], 'promotions_price' => $value['details']['sum_promotions_price'], 'promotions_type' => $value['promotions_type']];
                }
            }
            if ($promotionsDetail) {
                $typeArr = array_column($promotionsDetail, 'promotions_type');
                array_multisort($typeArr, SORT_ASC, $promotionsDetail);
            }
        }

        /** @var StoreOrderComputedServices $computeOrderService */
        $computeOrderService = app()->make(StoreOrderComputedServices::class);
        $sumPrice = $computeOrderService->getOrderSumPrice($cartInfo, 'sum_price');//获取订单原总金额
        $totalPrice = $computeOrderService->getOrderSumPrice($cartInfo, 'truePrice');//获取订单svip、用户等级优惠之后总金额
        $costPrice = $computeOrderService->getOrderSumPrice($cartInfo, 'costPrice');//获取订单成本价
        $vipPrice = $computeOrderService->getOrderSumPrice($cartInfo, 'vip_truePrice');//获取订单会员优惠金额
        $promotionsPrice = $computeOrderService->getOrderSumPrice($cartInfo, 'promotions_true_price');//优惠活动优惠
        $payPrice = (float)$totalPrice;
        $couponPrice = floatval($cartGroup['couponPrice'] ?? 0);
        if ($couponPrice < $payPrice) {
            $payPrice = (float)bcsub((string)$payPrice, (string)$couponPrice, 2);
        } else {
            $couponPrice = $payPrice;
            $payPrice = 0;
        }
        $SurplusIntegral = $usedIntegral = 0;
        $deductionPrice = '0';
        //自动使用优惠券
        // if ($coupon) {
        //     [
        //         $payPrice,
        //         $couponPrice
        //     ] = $this->authUseCoupon($uid, $cartInfo, $payPrice, $coupon_id);
        // }
        //使用积分
        if ($userInfo && $integral) {
            [
                $payPrice,
                $deductionPrice,
                $usedIntegral,
                $SurplusIntegral
            ] = $computeOrderService->useIntegral(true, $userInfo, $payPrice, [
                'offlinePostage' => sys_config('offline_postage'),
                'integralRatio' => sys_config('integral_ratio')
            ]);
        }

        return [
            'payPrice' => floatval($payPrice),//支付金额
            'vipPrice' => floatval($vipPrice),//会员优惠金额
            'totalPrice' => floatval($totalPrice),//会员优惠后订单金额
            'costPrice' => floatval($costPrice),//成本金额
            'sumPrice' => floatval($sumPrice),//订单总金额
            'couponPrice' => $couponPrice,//优惠券金额
            'promotionsPrice' => floatval($promotionsPrice),//优惠活动金额
            'promotionsDetail' => $promotionsDetail,//优惠
            'deductionPrice' => floatval($deductionPrice),//积分抵扣多少钱
            'surplusIntegral' => $SurplusIntegral,//抵扣了多少积分
            'usedIntegral' => $usedIntegral,//使用了多少积分
            'cartInfo' => $cartInfo,//购物列表
            'cartGroup' => $cartGroup//计算结果
        ];
    }

    /**
     * 收银台用户优惠券
     * @param int $uid
     * @param int $storeId
     * @param array $cartIds
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponList(int $uid, int $storeId, array $cartIds)
    {
        /** @var StoreCartServices $cartService */
        $cartService = app()->make(StoreCartServices::class);
        /** @var StoreOrderComputedServices $computeOrderService */
        $computeOrderService = app()->make(StoreOrderComputedServices::class);
        $cart = $cartService->getUserCartList($uid, 1, $cartIds, $storeId, 0, 4);
        $cartInfo = $cart['valid'];
        if (!$cartInfo) {
            throw new ValidateException('购物车暂无货物！');
        }
        /** @var StoreCouponissueServices $couponIssueServices */
        $couponIssueServices = app()->make(StoreCouponissueServices::class);
        return $couponIssueServices->getCanUseCoupon($uid, $cartInfo, $cart['promotions'] ?? [], false);
    }

    /**
     * 自用使用优惠力度最大的优惠券
     * @param int $uid
     * @param array $cartInfo
     * @param string $payPrice
     * @return array
     */
    public function authUseCoupon(int $uid, array $cartInfo, string $payPrice, int $couponId)
    {
        //使用优惠劵
        $couponPrice = 0;
        if ($uid && $couponId && $cartInfo) {
            /** @var StoreCouponUserServices $couponServices */
            $couponServices = app()->make(StoreCouponUserServices::class);
            $couponInfo = $couponServices->getOne([['id', '=', $couponId], ['uid', '=', $uid], ['is_fail', '=', 0], ['status', '=', 0], ['start_time', '<=', time()], ['end_time', '>=', time()]], '*', ['issue']);
            if (!$couponInfo) {
                throw new ValidateException('选择的优惠劵无效!');
            }
            $type = $couponInfo['applicable_type'] ?? 0;
            $flag = false;
            $price = 0;
            $count = 0;
            switch ($type) {
                case 0:
                case 3:
                    foreach ($cartInfo as $cart) {
                        $price = bcadd($price, bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 2), 2);
                        $count++;
                    }
                    break;
                case 1://品类券
                    /** @var StoreCategoryServices $storeCategoryServices */
                    $storeCategoryServices = app()->make(StoreCategoryServices::class);
                    $cateGorys = $storeCategoryServices->getAllById((int)$couponInfo['category_id']);
                    if ($cateGorys) {
                        $cateIds = array_column($cateGorys, 'id');
                        foreach ($cartInfo as $cart) {
                            if (isset($cart['productInfo']['cate_id']) && array_intersect(explode(',', $cart['productInfo']['cate_id']), $cateIds)) {
                                $price = bcadd($price, bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 2), 2);
                                $count++;
                            }
                        }
                    }
                    break;
                case 2:
                    foreach ($cartInfo as $cart) {
                        if (isset($cart['product_id']) && in_array($cart['product_id'], explode(',', $couponInfo['product_id']))) {
                            $price = bcadd($price, bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 2), 2);
                            $count++;
                        }
                    }
                    break;
            }
            if ($count && $couponInfo['use_min_price'] <= $price) {
                $flag = true;
            }
            if (!$flag) {
                throw new ValidateException('不满足优惠劵的使用条件!');
            }
            //满减券
            if ($couponInfo['coupon_type'] == 1) {
                $couponPrice = $couponInfo['coupon_price'];
            } else {
                if ($couponInfo['coupon_price'] <= 0) {//0折
                    $couponPrice = $price;
                } else if ($couponInfo['coupon_price'] >= 100) {
                    $couponPrice = 0;
                } else {
                    $truePrice = (float)bcmul((string)$price, bcdiv((string)$couponInfo['coupon_price'], '100', 2), 2);
                    $couponPrice = (float)bcsub((string)$price, (string)$truePrice, 2);
                }
            }
            if ($couponPrice < $payPrice) {
                $payPrice = (float)bcsub((string)$payPrice, (string)$couponPrice, 2);
            } else {
                $couponPrice = $payPrice;
                $payPrice = 0;
            }
        }
        return [$payPrice, $couponPrice];
    }

    /**
     * 二位数组冒泡排序
     * @param array $arr
     * @param string $key
     * @return array
     */
    protected function mpSort(array $arr, string $key)
    {
        for ($i = 0; $i < count($arr); $i++) {
            for ($j = $i; $j < count($arr); $j++) {
                if ($arr[$i][$key] > $arr[$j][$key]) {
                    $temp = $arr[$i];
                    $arr[$i] = $arr[$j];
                    $arr[$j] = $temp;
                }
            }
        }
        return $arr;
    }

    /**
     * 自动解析扫描二维码
     * @param string $barCode
     * @param int $storeId
     * @param int $uid
     * @param int $staff_id
     * @param int $touristUid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAnalysisCode(string $barCode, int $storeId, int $uid, int $staff_id, int $touristUid = 0)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $userInfo = $userService->get(['bar_code' => $barCode], ['uid', 'avatar', 'nickname', 'now_money', 'integral']);
        if ($userInfo) {
            return ['userInfo' => $userInfo->toArray()];
        } else {
            /** @var SystemStoreStaffServices $staffServices */
            $staffServices = app()->make(SystemStoreStaffServices::class);
            $staffServices->getStaffInfo($staff_id);

            /** @var StoreProductAttrValueServices $storeProductAttrService */
            $storeProductAttrService = app()->make(StoreProductAttrValueServices::class);
            $attProductInfo = $storeProductAttrService->getAttrByBarCode((string)$barCode);
            if (!$attProductInfo) {
                throw new ValidateException('没有扫描到商品');
            }
            /** @var StoreProductServices $productService */
            $productService = app()->make(StoreProductServices::class);
            $productInfo = $productService->get(['is_show' => 1, 'is_del' => 0, 'id' => $attProductInfo->product_id], [
                'image', 'store_name', 'store_info', 'bar_code', 'price', 'id as product_id', 'id'
            ]);
            if (!$productInfo) {
                throw new ValidateException('商品未查到');
            }
            /** @var StoreBranchProductServices $storeProductService */
            $storeProductService = app()->make(StoreBranchProductServices::class);
            if (!$storeProductService->count(['store_id' => $storeId, 'product_id' => $attProductInfo->product_id])) {
                throw new ValidateException('该商品在此门店不存在');
            }
            /** @var StoreProductAttrValueServices $valueService */
            $valueService = app()->make(StoreProductAttrValueServices::class);
            $valueInfo = $valueService->getOne(['unique' => $attProductInfo->unique]);
            if (!$valueInfo) {
                throw new ValidateException('商品属性不存在');
            }
            $productInfo = $productInfo->toArray();
            $productInfo['attr_value'] = [
                'ot_price' => $valueInfo->ot_price,
                'price' => $valueInfo->price,
                'sales' => $valueInfo->sales,
                'vip_price' => $valueInfo->vip_price,
                'stock' => $attProductInfo->stock,
            ];
            if ($uid || $touristUid) {
                /** @var StoreCartServices $cartService */
                $cartService = app()->make(StoreCartServices::class);
                $cartService->setItem('store_id', $storeId);
                $cartService->setItem('tourist_uid', $touristUid);
                $cartId = $cartService->addCashierCart($uid, $attProductInfo->product_id, 1, $attProductInfo->unique, $staff_id);
                $cartService->reset();
                if (!$cartId) {
                    throw new ValidateException('自动添加购物车失败');
                }
            } else {
                $cartId = 0;
            }
            return ['productInfo' => $productInfo, 'cartId' => $cartId];
        }
    }

    /**
     * 生成订单
     * @param int $uid
     * @param int $storeId
     * @param int $staffId
     * @param array $cartIds
     * @param string $payType
     * @param bool $integral
     * @param bool $coupon
     * @param string $remarks
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createOrder(int $uid, array $userInfo, array $computeData, int $storeId, int $staffId, array $cartIds, string $payType, bool $integral = false, bool $coupon = false, string $remarks = '', string $changePrice = '0', bool $isPrice = false, int $coupon_id = 0, int $seckillId = 0)
    {
        /** @var SystemStoreStaffServices $staffService */
        $staffService = app()->make(SystemStoreStaffServices::class);
        if (!$staffInfo = $staffService->getOne(['store_id' => $storeId, 'id' => $staffId, 'is_del' => 0, 'status' => 1])) {
            throw new ValidateException('您选择的店员不存在');
        }
        $addressInfo = [];
        //兼容门店虚拟用户下单
        $field = ['real_name', 'phone', 'province', 'city', 'district', 'street', 'detail'];
        if ($uid) {
            /** @var UserAddressServices $addreService */
            $addreService = app()->make(UserAddressServices::class);
            $addressInfo = $addreService->getUserDefaultAddress($uid, implode(',', $field));
            if ($addressInfo) {
                $addressInfo = $addressInfo->toArray();
            }
        }
        if (!$addressInfo) {
            foreach ($field as $key) {
                $addressInfo[$key] = '';
            }
        }
        $cartGroup = $computeData['cartGroup'] ?? [];
        $cartInfo = $computeData['cartInfo'];
        $totalPrice = $computeData['totalPrice'];
        $couponId = $coupon_id;
        $couponPrice = $computeData['couponPrice'] ?? '0.00';
        $useIntegral = $computeData['usedIntegral'];
        $gainIntegral = $totalNum = 0;
        $priceData = [
            'coupon_id' => $couponId,
            'coupon_price' => $couponPrice,
            'usedIntegral' => $useIntegral,
            'deduction_price' => $computeData['deductionPrice'] ?? '0.00',
            'promotions_price' => $computeData['promotionsPrice'],
            'pay_postage' => 0,
            'pay_price' => $computeData['payPrice']
        ];

        $promotions_give = [
            'give_integral' => $cartGroup['give_integral'] ?? 0,
            'give_coupon' => $cartGroup['giveCoupon'] ?? [],
            'give_product' => $cartGroup['giveProduct'] ?? [],
            'promotions' => $cartGroup['promotions'] ?? []
        ];

        $type = 0;
        foreach ($cartInfo as $cart) {
            $totalNum += $cart['cart_num'];
            $cartInfoGainIntegral = isset($cart['productInfo']['give_integral']) ? bcmul((string)$cart['cart_num'], (string)$cart['productInfo']['give_integral'], 0) : 0;
            $gainIntegral = bcadd((string)$gainIntegral, (string)$cartInfoGainIntegral, 0);

            if ($seckillId) {
                if (!isset($cart['product_attr_unique']) || !$cart['product_attr_unique']) continue;
                $type = $cart['type'];
                if (in_array($type, [1, 2, 3]) &&
                    (
                        !CacheService::checkStock($cart['product_attr_unique'], (int)$cart['cart_num'], $type) ||
                        !CacheService::popStock($cart['product_attr_unique'], (int)$cart['cart_num'], $type)
                    )
                ) {
                    throw new ValidateException('您购买的商品库存已不足' . $cart['cart_num'] . $cart['productInfo']['unit_name']);
                }
            }
        }

        /** @var StoreOrderCreateServices $orderServices */
        $orderServices = app()->make(StoreOrderCreateServices::class);
        $key = md5(json_encode($cartIds) . uniqid() . time());

        $orderInfo = [
            'uid' => $uid,
            'type' => 0,
            'order_id' => $orderServices->getNewOrderId(),
            'real_name' => $addressInfo['real_name'] ? $addressInfo['real_name'] : $userInfo['nickname'] ?? '',
            'user_phone' => $addressInfo['phone'] ? $addressInfo['phone'] : $userInfo['phone'] ?? '',
            'user_address' => $addressInfo['province'] . ' ' . $addressInfo['city'] . ' ' . $addressInfo['district'] . ' ' . $addressInfo['street'] . ' ' . $addressInfo['detail'],
            'cart_id' => $cartIds,
            'clerk_id' => $staffInfo['uid'],
            'store_id' => $storeId,
            'staff_id' => $staffId,
            'total_num' => $totalNum,
            'total_price' => $computeData['sumPrice'] ?? $totalPrice,
            'total_postage' => 0,
            'coupon_id' => $couponId,
            'coupon_price' => $couponPrice,
            'promotions_price' => $priceData['promotions_price'],
            'pay_price' => $isPrice ? $changePrice : $computeData['payPrice'],
            'pay_postage' => 0,
            'deduction_price' => $computeData['deductionPrice'],
            'paid' => 0,
            'pay_type' => $payType == self::YUE_PAY ? 'yue' : '',
            'use_integral' => $useIntegral,
            'gain_integral' => $gainIntegral,
            'mark' => htmlspecialchars($remarks),
            'activity_id' => $seckillId,
            'pink_id' => 0,
            'cost' => $computeData['costPrice'],
            'is_channel' => 2,
            'add_time' => time(),
            'unique' => $key,
            'shipping_type' => 4,
            'channel_type' => $userInfo['user_type'] ?? '',
            'province' => '',
            'spread_uid' => 0,
            'spread_two_uid' => 0,
            'promotions_give' => json_encode($promotions_give),
            'give_integral' => $promotions_give['give_integral'] ?? 0,
            'give_coupon' => implode(',', $promotions_give['give_coupon'] ?? []),
        ];
        /** @var StoreOrderCartInfoServices $cartServices */
        $cartServices = app()->make(StoreOrderCartInfoServices::class);
        //$order = $this->transaction(function () use ($key, $storeId, $cartInfo, $type, $seckillId, $computeData, $orderInfo, $cartServices, $orderServices, $couponId, $userInfo, $useIntegral, $promotions_give, $payType) {
            $order = $orderServices->save($orderInfo);
            if (!$order) {
                throw new ValidateException('订单生成失败');
            }
            //使用优惠券
            if ($couponId) {
                /** @var StoreCouponUserServices $couponServices */
                $couponServices = app()->make(StoreCouponUserServices::class);
                $res1 = $couponServices->useCoupon($couponId);
                if (!$res1) {
                    throw new ValidateException('使用优惠劵失败!');
                }
            }
            //积分抵扣
            $orderServices->deductIntegral($userInfo, $useIntegral, [
                'SurplusIntegral' => $computeData['surplusIntegral'],
                'usedIntegral' => $computeData['usedIntegral'],
                'deduction_price' => $computeData['deductionPrice'],
            ], (int)($userInfo['uid'] ?? 0), $key);
            //修改门店库存
            $orderServices->decGoodsStock($cartInfo, $type, $seckillId, $storeId);
            //保存购物车商品信息
            $cartServices->setCartInfo($order['id'], $cartInfo, $userInfo['uid'] ?? 0, $promotions_give['promotions'] ?? []);
            $order = $order->toArray();
//            return $order;
//        });
        $news = false;
        $addressId = $type = $activity_id = 0;
        //订单创建事件
        if (in_array($payType, [PayServices::ALIAPY_PAY, PayServices::WEIXIN_PAY, PayServices::YUE_PAY])) {
            $delCart = false;
        } else {
            $delCart = true;
        }
		//扣除优惠活动赠品限量
        StorePromotionsJob::dispatchDo('changeGiveLimit', [$promotions_give]);
        event('order.create', [$order, $userInfo, compact('cartInfo', 'priceData', 'addressId', 'cartIds', 'news', 'delCart', 'changePrice'), compact('type', 'activity_id'), 0]);
        return $order;
    }

    /**
     * 收银台支付
     * @param string $orderId
     * @param string $payType
     * @param string $userCode
     * @param string $authCode
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function paySuccess(string $orderId, string $payType, string $userCode, string $authCode = '')
    {
        /** @var StoreOrderSuccessServices $orderService */
        $orderService = app()->make(StoreOrderSuccessServices::class);
        $orderInfo = $orderService->get(['order_id' => $orderId]);
        if (!$orderInfo) {
            throw new ValidateException('没有查询到订单信息');
        }
        if ($orderInfo->paid) {
            throw new ValidateException('订单已支付');
        }
        if ($orderInfo->is_del) {
            throw new ValidateException('订单已取消');
        }
        switch ($payType) {
            case PayServices::YUE_PAY://余额支付
                if (!$orderInfo['uid']) {
                    throw new ValidateException('余额支付用户信息不存在无法支付');
                }
                if (!$userCode) {
                    throw new ValidateException('缺少扫码支付参数');
                }
                /** @var UserServices $userService */
                $userService = app()->make(UserServices::class);
                $userInfo = $userService->getUserInfo($orderInfo->uid, ['uid', 'rand_code']);
                //读取缓存用户code
                $rand_code = CacheService::get('user_rand_code' . $orderInfo->uid);
                CacheService::delete('user_rand_code' . $orderInfo->uid);
                if (!$userInfo) {
                    throw new ValidateException('余额支付用户不存在');
                }
                if ($rand_code != $userCode) {
                    throw new ValidateException('二维码已使用，请重新扫码');
                }
                /** @var YuePayServices $payService */
                $payService = app()->make(YuePayServices::class);
                $pay = $payService->yueOrderPay($orderInfo->toArray(), $orderInfo->uid);
                if ($pay['status'] === true)
                    return ['status' => 'SUCCESS'];
                else if ($pay['status'] === 'pay_deficiency') {
                    throw new ValidateException($pay['msg']);
                } else {
                    return ['status' => 'ERROR', 'message' => is_array($pay) ? $pay['msg'] ?? '余额支付失败' : $pay];
                }
            case PayServices::WEIXIN_PAY://微信支付
            case PayServices::ALIAPY_PAY://支付宝支付
                if (!$authCode) {
                    throw new ValidateException('缺少支付付款二维码CODE');
                }

                $pay = new PayServices();
                $site_name = sys_config('site_name');
                /** @var StoreOrderCartInfoServices $orderInfoServices */
                $orderInfoServices = app()->make(StoreOrderCartInfoServices::class);
                $body = $orderInfoServices->getCarIdByProductTitle((int)$orderInfo['id'], $orderInfo['cart_id']);
                $body = substrUTf8($site_name . '--' . $body, 30);
                try {
                    //扫码支付
                    $response = $pay->setAuthCode($authCode)->pay($payType, '', $orderInfo->order_id, $orderInfo->pay_price, 'product', $body);
                } catch (\Throwable $e) {
                    return ['status' => 'ERROR', 'message' => '支付失败'];
                }
                //支付成功paid返回1
                if ($response['paid']) {
                    if (!$orderService->paySuccess($orderInfo->toArray(), $payType, ['trade_no' => $response['payInfo']['transaction_id'] ?? ''])) {
                        return ['status' => 'ERROR', 'message' => '支付失败'];
                    }
                    //支付成功刪除購物車
                    /** @var StoreCartServices $cartServices */
                    $cartServices = app()->make(StoreCartServices::class);
                    $cartServices->deleteCartStatus($orderInfo['cart_id'] ?? []);
                    return ['status' => 'SUCCESS'];
                } else {
                    if ($payType === PayServices::WEIXIN_PAY) {
                        if (isset($response['payInfo']['err_code']) && in_array($response['payInfo']['err_code'], ['AUTH_CODE_INVALID', 'NOTENOUGH'])) {
                            return ['status' => 'ERROR', 'message' => '支付失败'];
                        }
                        //微信付款码支付需要同步更改状态
                        $secs = 5;
                        if (isset($order_info['payInfo']['err_code']) && $order_info['payInfo']['err_code'] === 'USERPAYING') {
                            $secs = 10;
                        }
                        //放入队列执行
                        MicroPayOrderJob::dispatchSece($secs, [$orderInfo['order_id'], 0]);
                    }
                    return ['status' => 'PAY_ING', 'message' => $response['message']];
                }
                break;
            case PayServices::CASH_PAY://收银台现金支付
                if (!$orderService->paySuccess($orderInfo->toArray(), $payType)) {
                    return ['status' => 'ERROR', 'message' => '支付失败'];
                } else {
                    return ['status' => 'SUCCESS'];
                }
                break;
            default:
                throw new ValidateException('暂无支付方式，无法支付');
        }
    }


}
