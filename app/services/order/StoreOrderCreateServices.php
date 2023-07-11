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


use app\jobs\store\StoreCartJob;
use app\jobs\activity\StorePromotionsJob;
use app\services\activity\discounts\StoreDiscountsServices;
use app\services\agent\AgentLevelServices;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\other\CityAreaServices;
use app\services\pay\PayServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\category\StoreCategoryServices;
use app\services\product\shipping\ShippingTemplatesFreeServices;
use app\services\product\shipping\ShippingTemplatesRegionServices;
use app\services\product\shipping\ShippingTemplatesServices;
use app\services\wechat\WechatUserServices;
use app\services\BaseServices;
use crmeb\services\CacheService;
use app\dao\order\StoreOrderDao;
use app\services\user\UserServices;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;
use app\services\user\UserBillServices;
use app\services\user\UserAddressServices;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\store\SystemStoreServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\product\product\StoreProductServices;
use function Swoole\Coroutine\batch;
use think\facade\Cache;
use think\facade\Log;

/**
 * 订单创建
 * Class StoreOrderCreateServices
 * @package app\services\order
 */
class StoreOrderCreateServices extends BaseServices
{
    use ServicesTrait;

    /**
     * StoreOrderCreateServices constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 使用雪花算法生成订单ID
     * @return string
     * @throws \Exception
     */
    public function getNewOrderId(string $prefix = 'wx')
    {
        $snowflake = new \Godruoyi\Snowflake\Snowflake();
        $is_callable = function ($currentTime) {
//            if (is_win()) {
            $redis = Cache::store('redis');
            $swooleSequenceResolver = new \Godruoyi\Snowflake\RedisSequenceResolver($redis->handler());
            return $swooleSequenceResolver->sequence($currentTime);
//            } else {
//                $swooleSequenceResolver = new \Godruoyi\Snowflake\SwooleSequenceResolver();
//                return $swooleSequenceResolver->sequence($currentTime);
//            }
        };
        //32位
        if (PHP_INT_SIZE == 4) {
            $id = abs($snowflake->setSequenceResolver($is_callable)->id());
        } else {
            $id = $snowflake->setStartTimeStamp(strtotime('2020-06-05') * 1000)->setSequenceResolver($is_callable)->id();
        }
        return $prefix . $id;
    }

    /**
     * 核销订单生成核销码
     * @return false|string
     */
    public function getStoreCode()
    {
        mt_srand();
        list($msec, $sec) = explode(' ', microtime());
        $num = time() + mt_rand(10, 999999) . '' . substr($msec, 2, 3);//生成随机数
        if (strlen($num) < 12)
            $num = str_pad((string)$num, 12, 0, STR_PAD_RIGHT);
        else
            $num = substr($num, 0, 12);
        if ($this->dao->count(['verify_code' => $num])) {
            return $this->getStoreCode();
        }
        return $num;
    }

    /**
     * 创建订单
     * @param int $uid
     * @param string $key
     * @param array $cartGroup
     * @param int $addressId
     * @param string $payType
     * @param array $addressInfo
     * @param array $userInfo
     * @param bool $useIntegral
     * @param int $couponId
     * @param string $mark
     * @param int $pinkId
     * @param int $isChannel
     * @param int $shippingType
     * @param int $storeId
     * @param false $news
     * @param array $customForm
     * @param int $invoice_id
     * @param string $from
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createOrder(int $uid, string $key, array $cartGroup, int $addressId, string $payType, array $addressInfo, array $userInfo = [], bool $useIntegral = false, $couponId = 0, $mark = '', $pinkId = 0, $isChannel = 0, $shippingType = 1, $storeId = 0, $news = false, $customForm = [], int $invoice_id = 0, string $from = '')
    {
        /** @var StoreOrderComputedServices $computedServices */
        $computedServices = app()->make(StoreOrderComputedServices::class);
        $priceData = $computedServices->computedOrder($uid, $userInfo, $cartGroup, $addressId, $payType, $useIntegral, $couponId, $shippingType);
        $cartInfo = $cartGroup['cartInfo'];
        $priceGroup = $cartGroup['priceGroup'];
        $cartIds = [];
        $totalNum = 0;
        $gainIntegral = 0;
        foreach ($cartInfo as $cart) {
            $cartIds[] = $cart['id'];
            $totalNum += $cart['cart_num'];
            $cartInfoGainIntegral = isset($cart['productInfo']['give_integral']) ? bcmul((string)$cart['cart_num'], (string)$cart['productInfo']['give_integral'], 0) : 0;
            $gainIntegral = bcadd((string)$gainIntegral, (string)$cartInfoGainIntegral, 0);
        }
        $deduction = $cartGroup['deduction'];
        $other = $cartGroup['other'];
        $promotions_give = [
            'give_integral' => $other['give_integral'] ?? 0,
            'give_coupon' => $other['give_coupon'] ?? [],
            'give_product' => $other['give_product'] ?? [],
            'promotions' => $other['promotions'] ?? []
        ];
        $type = (int)$deduction['type'] ?? 0;
        $activity_id = (int)$deduction['activity_id'] ?? 0;
        $product_type = (int)$deduction['product_type'] ?? 0;
        if (in_array($type, [1, 2, 3, 5])) {
            $couponId = 0;
            if ($type != 5) $useIntegral = false;
            $systemPayType = PayServices::PAY_TYPE;
            unset($systemPayType['offline']);
            if ($from != 'pc' && !array_key_exists($payType, $systemPayType)) {
                throw new ValidateException('营销商品不能使用线下支付!');
            }
        }
        //$shipping_type = 1 快递发货 $shipping_type = 2 门店自提
        $storeSelfMention = sys_config('store_self_mention') ?? 0;
        if (!$storeSelfMention) $shippingType = 1;

        $userAddress = $addressInfo['province'] . ' ' . $addressInfo['city'] . ' ' . $addressInfo['district'] . ' ' . $addressInfo['street'] . ' ' . $addressInfo['detail'];
        $userLocation = $addressInfo['longitude'] . ' ' . $addressInfo['latitude'];

        $orderInfo = [
            'uid' => $uid,
            'type' => $type,
            'order_id' => $this->getNewOrderId(),
            'real_name' => $addressInfo['real_name'],
            'user_phone' => $addressInfo['phone'],
            'user_address' => $userAddress,
            'user_location' => $userLocation,
            'cart_id' => $cartIds,
            'total_num' => $totalNum,
            'total_price' => $priceGroup['sumPrice'] ?? $priceGroup['totalPrice'],
            'total_postage' => $priceData['total_postage'] ?? $priceGroup['storePostage'],
            'coupon_id' => $couponId,
            'coupon_price' => $priceData['coupon_price'],
            'promotions_price' => $priceData['promotions_price'],
            'pay_price' => $priceData['pay_price'],
            'pay_postage' => $priceData['pay_postage'],
            'deduction_price' => $priceData['deduction_price'],
            'paid' => 0,
            'pay_type' => $payType,
            'use_integral' => $priceData['usedIntegral'],
            'gain_integral' => $gainIntegral,
            'mark' => htmlspecialchars($mark),
            'product_type' => $product_type,
            'activity_id' => $activity_id,
            'pink_id' => $pinkId,
            'cost' => $priceGroup['costPrice'],
            'is_channel' => $isChannel,
            'add_time' => time(),
            'unique' => $key,
            'shipping_type' => $shippingType,
            'channel_type' => $userInfo['user_type'],
            'province' => '',
            'spread_uid' => 0,
            'spread_two_uid' => 0,
            'custom_form' => json_encode($customForm),
            'promotions_give' => json_encode($promotions_give),
            'give_integral' => $promotions_give['give_integral'] ?? 0,
            'give_coupon' => implode(',', $promotions_give['give_coupon'] ?? []),
        ];
        if ($userInfo['user_type'] == 'wechat' || $userInfo['user_type'] == 'routine') {
            /** @var WechatUserServices $wechatServices */
            $wechatServices = app()->make(WechatUserServices::class);
            $orderInfo['province'] = $wechatServices->value(['uid' => $uid, 'user_type' => $userInfo['user_type']], 'province') ?: '';
        }
        if ($shippingType == 2) {
            $orderInfo['verify_code'] = $this->getStoreCode();
            /** @var SystemStoreServices $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $orderInfo['store_id'] = $storeServices->getStoreDispose($storeId, 'id');
            if (!$orderInfo['store_id']) {
                throw new ValidateException('暂无门店无法选择门店自提');
            }
        }

        $priceData['coupon_id'] = $couponId;
        $order = $this->transaction(function () use ($cartIds, $couponId, $orderInfo, $cartInfo, $key, $userInfo, $useIntegral, $priceData, $type, $activity_id, $uid, $addressId) {
            //创建订单
            $results = batch([
                'order' => function () use ($orderInfo) {
                    return $this->dao->save($orderInfo);
                },
                'coupon' => function () use ($couponId) {
                    $res1 = true;
                    if ($couponId) {
                        /** @var StoreCouponUserServices $couponServices */
                        $couponServices = app()->make(StoreCouponUserServices::class);
                        $res1 = $couponServices->useCoupon($couponId);
                    }
                    return $res1;
                },
                'deductIntegral' => function () use ($userInfo, $useIntegral, $priceData, $key) {
                    try {
                        $this->deductIntegral($userInfo, $useIntegral, $priceData, (int)$userInfo['uid'], $key);
                        return null;
                    } catch (\Throwable $e) {
                        return $e->getMessage();
                    }
                },
                'decGoodsStock' => function () use ($orderInfo, $cartInfo, $type, $activity_id) {
                    try {
                        //扣库存
                        $this->decGoodsStock($cartInfo, $type, $activity_id, $orderInfo['store_id'] ?? 0);
                        return null;
                    } catch (\Throwable $e) {
                        return $e->getMessage();
                    }
                }
            ]);

            if (!$results['order']) {
                throw new ValidateException('订单生成失败!');
            }
            $order = $results['order'];
            if (!$results['coupon']) {
                throw new ValidateException('使用优惠劵失败!');
            }

            if ($results['deductIntegral'] && is_string($results['deductIntegral'])) {
                throw new ValidateException($results['deductIntegral']);
            }

            if ($results['decGoodsStock'] && is_string($results['decGoodsStock'])) {
                throw new ValidateException($results['decGoodsStock']);
            }
            return $order;
        });
        //保存购物车商品信息
        StoreCartJob::dispatch([$order['id'], $cartInfo, $uid, $promotions_give['promotions'] ?? []]);
        //扣除优惠活动赠品限量
        StorePromotionsJob::dispatchDo('changeGiveLimit', [$promotions_give]);
        //订单创建事件
        event('order.create', [$order, $userInfo, compact('cartInfo', 'priceData', 'addressId', 'cartIds', 'news'), compact('type', 'activity_id'), $invoice_id]);
        return $order;
    }


    /**
     * 抵扣积分
     * @param array $userInfo
     * @param bool $useIntegral
     * @param array $priceData
     * @param int $uid
     * @param string $key
     */
    public function deductIntegral(array $userInfo, bool $useIntegral, array $priceData, int $uid, string $key)
    {
        $res2 = true;
        if ($userInfo && $useIntegral && $userInfo['integral'] > 0) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            if (!$priceData['SurplusIntegral']) {
                $res2 = false !== $userServices->update($uid, ['integral' => 0]);
            } else {
                $res2 = false !== $userServices->bcDec($userInfo['uid'], 'integral', $priceData['usedIntegral'], 'uid');
            }
            /** @var UserBillServices $userBillServices */
            $userBillServices = app()->make(UserBillServices::class);
            $res3 = $userBillServices->income('deduction', $uid, [
                'number' => $priceData['usedIntegral'],
                'deductionPrice' => $priceData['deduction_price']
            ], $userInfo['integral'], $key);

            $res2 = $res2 && false != $res3;
        }
        if (!$res2) {
            throw new ValidateException('使用积分抵扣失败!');
        }
    }

    /**
     * 扣库存
     * @param array $cartInfo
     * @param int $type
     * @param int $activity_id
     * @param int $store_id
     */
    public function decGoodsStock(array $cartInfo, int $type, int $activity_id, int $store_id = 0)
    {
        $res5 = true;
        /** @var StoreProductServices $services */
        $services = app()->make(StoreProductServices::class);
        /** @var StoreSeckillServices $seckillServices */
        $seckillServices = app()->make(StoreSeckillServices::class);
        /** @var StoreCombinationServices $pinkServices */
        $pinkServices = app()->make(StoreCombinationServices::class);
        /** @var StoreBargainServices $bargainServices */
        $bargainServices = app()->make(StoreBargainServices::class);
        /** @var StoreDiscountsServices $discountServices */
        $discountServices = app()->make(StoreDiscountsServices::class);
        /** @var StoreBranchProductServices $storeBranchService */
        $storeBranchService = app()->make(StoreBranchProductServices::class);
        try {
            foreach ($cartInfo as $cart) {
                $unique = isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '';
                //减库存加销量
                switch ($type) {
                    case 0://普通
                    case 6://预售
                        if ($store_id) {
                            $res5 = $res5 && $storeBranchService->updataDecStock((int)$cart['cart_num'], (int)$cart['productInfo']['id'], $store_id, $unique);
                        } else {
                            $res5 = $res5 && $services->decProductStock((int)$cart['cart_num'], (int)$cart['productInfo']['id'], $unique);
                        }
                        break;
                    case 1://秒杀
                        $res5 = $res5 && $seckillServices->decSeckillStock((int)$cart['cart_num'], $activity_id, $unique, $store_id);
                        break;
                    case 2://砍价
                        $res5 = $res5 && $bargainServices->decBargainStock((int)$cart['cart_num'], $activity_id, $unique, $store_id);
                        break;
                    case 3://拼团
                        $res5 = $res5 && $pinkServices->decCombinationStock((int)$cart['cart_num'], $activity_id, $unique, $store_id);
                        break;
                    case 5://套餐
                        $res5 = $res5 && $discountServices->decDiscountStock((int)$cart['cart_num'], $activity_id, (int)($cart['discount_product_id'] ?? 0), (int)($cart['product_id'] ?? 0), $unique, $store_id);
                        break;
                    default:
                        if ($store_id) {
                            $res5 = $res5 && $storeBranchService->updataDecStock((int)$cart['cart_num'], (int)$cart['productInfo']['id'], $store_id, $unique);
                        } else {
                            $res5 = $res5 && $services->decProductStock((int)$cart['cart_num'], (int)$cart['productInfo']['id'], $unique);
                        }
                        break;

                }
            }
            if ($type == 5 && $activity_id) {
                //改变套餐限量
                $res5 = $res5 && $discountServices->changeDiscountLimit($activity_id);
            }
            if (!$res5) {
                throw new ValidateException('库存不足!');
            }
        } catch (\Throwable $e) {
            throw new ValidateException('库存不足!');
        }
    }

    /**
     * 订单创建后的后置事件
     * @param UserAddressServices $addressServices
     * @param $order
     * @param array $group
     */
    public function orderCreateAfter($order, array $group)
    {
        /** @var UserAddressServices $addressServices */
        $addressServices = app()->make(UserAddressServices::class);
        //设置用户默认地址
        if ($order['uid'] && isset($group['addressId']) && $group['addressId'] && !$addressServices->be(['is_default' => 1, 'uid' => $order['uid']])) {
            $addressServices->setDefaultAddress($group['addressId'], $order['uid']);
        }
        //删除购物车
        if (isset($group['news']) && $group['news']) {
            array_map(function ($key) {
                CacheService::redisHandler()->delete($key);
            }, $group['cartIds']);
        } else {
            if (!isset($group['delCart']) || (isset($group['delCart']) && $group['delCart'] !== false)) {
                /** @var StoreCartServices $cartServices */
                $cartServices = app()->make(StoreCartServices::class);
                $cartServices->deleteCartStatus($group['cartIds'] ?? []);
            }
        }
    }

    /**
     * 计算订单每个商品真实付款价格
     * @param array $orderInfo
     * @param array $cartInfo
     * @param array $priceData
     * @param $addressId
     * @param int $uid
     * @param $userInfo
     * @return array
     */
    public function computeOrderProductTruePrice($orderInfo, array $cartInfo, array $priceData, $addressId, int $uid, $userInfo)
    {
        //统一放入默认数据
        foreach ($cartInfo as &$cart) {
            $cart['use_integral'] = 0;
            $cart['integral_price'] = 0.00;
            // $cart['coupon_price'] = 0.00;
        }
        try {
            $promotionsGice =  isset($orderInfo['promotions_give']) ? (is_string($orderInfo['promotions_give']) ? json_decode($orderInfo['promotions_give'], true) : $orderInfo['promotions_give']) : [];
            $promotions = [];
            if (isset($promotionsGice['promotions']) && $promotionsGice['promotions']) {
                $promotions = $promotionsGice['promotions'];
            }
            [$cartInfo, $spread_ids] = $this->computeOrderProductBrokerage($uid, $cartInfo, $userInfo);
            //$cartInfo = $this->computeOrderProductCoupon($cartInfo, $priceData, $promotions);
            $cartInfo = $this->computeOrderProductIntegral($cartInfo, $priceData);
//            $cartInfo = $this->computeOrderProductPostage($cartInfo, $priceData, $addressId);
        } catch (\Throwable $e) {
            Log::error('订单商品结算失败,File：' . $e->getFile() . ',Line：' . $e->getLine() . ',Message：' . $e->getMessage());
            throw new ValidateException('订单商品结算失败');
        }
        //truePice实际支付单价（存在）
        //几件商品总体优惠 以及积分抵扣金额
        foreach ($cartInfo as &$cart) {
            $coupon_price = $cart['coupon_price'] ?? 0;
            $integral_price = $cart['integral_price'] ?? 0;
            $cart['sum_true_price'] = bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 2);
            if ($coupon_price) {
                $cart['sum_true_price'] = bcsub((string)$cart['sum_true_price'], (string)$coupon_price, 2);
                $uni_coupon_price = (string)bcdiv((string)$coupon_price, (string)$cart['cart_num'], 4);
                $cart['truePrice'] = $cart['truePrice'] > $uni_coupon_price ? bcsub((string)$cart['truePrice'], $uni_coupon_price, 2) : 0;
            }
            if ($integral_price) {
                $cart['sum_true_price'] = bcsub((string)$cart['sum_true_price'], (string)$integral_price, 2);
                $uni_integral_price = (string)bcdiv((string)$integral_price, (string)$cart['cart_num'], 4);
                $cart['truePrice'] = $cart['truePrice'] > $uni_integral_price ? bcsub((string)$cart['truePrice'], $uni_integral_price, 2) : 0;
            }
        }
        return [$cartInfo, $spread_ids];
    }

    /**
     * 计算每个商品实际支付运费
     * @param array $cartInfo
     * @param array $priceData
     * @return array
     */
    public function computeOrderProductPostage(array $cartInfo, array $priceData, $addressId)
    {
        $storePostage = $priceData['pay_postage'] ?? 0;
        if ($storePostage) {
            /** @var UserAddressServices $addressServices */
            $addressServices = app()->make(UserAddressServices::class);
            $addr = $addressServices->getAdderssCache($addressId);
            if ($addr) {
                //按照运费模板计算每个运费模板下商品的件数/重量/体积以及总金额 按照首重倒序排列
                $cityId = $addr['city_id'] ?? 0;
                $ids = [];
                if ($cityId) {
                    /** @var CityAreaServices $cityAreaServices */
                    $cityAreaServices = app()->make(CityAreaServices::class);
                    $ids = $cityAreaServices->getRelationCityIds($cityId);
                }
                $cityIds = array_merge([0], $ids);

                $tempIds[] = 1;
                foreach ($cartInfo as $key_c => $item_c) {
                    $tempIds[] = $item_c['productInfo']['temp_id'];
                }
                $tempIds = array_unique($tempIds);
                /** @var ShippingTemplatesServices $shippServices */
                $shippServices = app()->make(ShippingTemplatesServices::class);
                $temp = $shippServices->getShippingColumn(['id' => $tempIds], 'type,appoint', 'id');
                /** @var ShippingTemplatesRegionServices $regionServices */
                $regionServices = app()->make(ShippingTemplatesRegionServices::class);
                $regions = $regionServices->getTempRegionList($tempIds, $cityIds, 'temp_id,first,first_price,continue,continue_price', 'temp_id');
                $temp_num = [];
                foreach ($cartInfo as $cart) {
                    $tempId = $cart['productInfo']['temp_id'] ?? 1;
                    $type = isset($temp[$tempId]['type']) ? $temp[$tempId]['type'] : $temp[1]['type'];
                    if ($type == 1) {
                        $num = $cart['cart_num'];
                    } elseif ($type == 2) {
                        $num = $cart['cart_num'] * $cart['productInfo']['attrInfo']['weight'];
                    } else {
                        $num = $cart['cart_num'] * $cart['productInfo']['attrInfo']['volume'];
                    }
                    $region = isset($regions[$tempId]) ? $regions[$tempId] : $regions[1];
                    if (!isset($temp_num[$tempId])) {
                        $temp_num[$tempId] = [
                            'cart_id' => [$cart['id']],
                            'number' => $num,
                            'type' => $type,
                            'price' => bcmul($cart['cart_num'], $cart['truePrice'], 2),
                            'first' => $region['first'],
                            'first_price' => $region['first_price'],
                            'continue' => $region['continue'],
                            'continue_price' => $region['continue_price'],
                            'temp_id' => $tempId
                        ];
                    } else {
                        $temp_num[$tempId]['cart_id'][] = $cart['id'];
                        $temp_num[$tempId]['number'] += $num;
                        $temp_num[$tempId]['price'] += bcmul($cart['cart_num'], $cart['truePrice'], 2);
                    }
                }
                $cartInfo = array_combine(array_column($cartInfo, 'id'), $cartInfo);
                /** @var ShippingTemplatesFreeServices $freeServices */
                $freeServices = app()->make(ShippingTemplatesFreeServices::class);
                $freeList = $freeServices->isFreeList($tempIds, $cityIds, 0, 'temp_id,number,price', 'temp_id');
                if ($freeList) {
                    foreach ($temp_num as $k => $v) {
                        if (isset($temp[$v['temp_id']]['appoint']) && $temp[$v['temp_id']]['appoint'] && isset($freeList[$v['temp_id']])) {
                            $free = $freeList[$v['temp_id']];
                            $condition = $v['type'] == 1 ? $free['number'] <= $v['number'] : $free['number'] >= $v['number'];
                            if ($free['price'] <= $v['price'] && $condition) {
                                //免运费
                                foreach ($v['cart_id'] as $c_id) {
                                    if (isset($cartInfo[$c_id])) $cartInfo[$c_id]['postage_price'] = 0.00;
                                }
                            }
                        }
                    }
                }
                $count = 0;
                $compute_price = 0.00;
                $total_price = 0;
                $postage_price = 0.00;
                foreach ($cartInfo as $cart) {
                    if (isset($cart['postage_price'])) {//免运费
                        continue;
                    }
                    if (isset($cart['is_gift']) && $cart['is_gift'] == 1) {
                        continue;
                    }
                    $total_price = bcadd((string)$total_price, (string)bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 4), 2);
                    $count++;
                }
                foreach ($cartInfo as &$cart) {
                    if (isset($cart['postage_price'])) {//免运费
                        continue;
                    }
                    if (isset($cart['is_gift']) && $cart['is_gift'] == 1) {
                        continue;
                    }
                    if ($count > 1) {
                        $postage_price = bcmul((string)bcdiv((string)bcmul((string)$cart['cart_num'], (string)$cart['truePrice'], 4), (string)$total_price, 4), (string)$storePostage, 2);
                        $compute_price = bcadd((string)$compute_price, (string)$postage_price, 2);
                    } else {
                        $postage_price = bcsub((string)$storePostage, $compute_price, 2);
                    }
                    $cart['postage_price'] = $postage_price;
                    $count--;
                }
                $cartInfo = array_merge($cartInfo);
            }
        }
        return $cartInfo;
    }

    /**
     * 计算订单商品积分实际抵扣金额
     * @param array $cartInfo
     * @param array $priceData
     * @return array
     */
    public function computeOrderProductIntegral(array $cartInfo, array $priceData)
    {
        $usedIntegral = $priceData['usedIntegral'] ?? 0;
        $deduction_price = $priceData['deduction_price'] ?? 0;
        if ($deduction_price) {
            $count = 0;
            $total_price = 0.00;
            $compute_price = 0.00;
            $integral_price = 0.00;
            $use_integral = 0;
            $compute_integral = 0;
            foreach ($cartInfo as $cart) {
                if (isset($cart['is_gift']) && $cart['is_gift'] == 1) {
                    continue;
                }
                $total_price = bcadd((string)$total_price, (string)bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 4), 2);
                $count++;
            }
            foreach ($cartInfo as &$cart) {
                if (isset($cart['is_gift']) && $cart['is_gift'] == 1) {
                    continue;
                }
                if ($count > 1) {
                    $integral_price = bcmul((string)bcdiv((string)bcmul((string)$cart['cart_num'], (string)$cart['truePrice'], 4), (string)$total_price, 4), (string)$deduction_price, 2);
                    $compute_price = bcadd((string)$compute_price, (string)$integral_price, 2);
                    $use_integral = bcmul((string)bcdiv((string)bcmul((string)$cart['cart_num'], (string)$cart['truePrice'], 4), (string)$total_price, 4), (string)$usedIntegral, 0);
                    $compute_integral = bcadd((string)$compute_integral, $use_integral, 0);
                } else {
                    $integral_price = bcsub((string)$deduction_price, $compute_price, 2);
                    $use_integral = bcsub((string)$usedIntegral, $compute_integral, 0);
                }
                $count--;
                $cart['integral_price'] = $integral_price;
                $cart['use_integral'] = $use_integral;
            }
        }
        return $cartInfo;
    }

    /**
     * 计算订单商品优惠券实际抵扣金额
     * @param array $cartInfo
     * @param array $priceData
     * @return array
     */
    public function computeOrderProductCoupon(array $cartInfo, array $priceData, array $promotions = [])
    {
        if ($priceData['coupon_id'] && $priceData['coupon_price'] ?? 0) {
            $count = 0;
            $total_price = 0.00;
            $compute_price = 0.00;
            $coupon_price = 0.00;
            /** @var StoreCouponUserServices $couponServices */
            $couponServices = app()->make(StoreCouponUserServices::class);
            $couponInfo = $couponServices->getOne(['id' => $priceData['coupon_id']], '*', ['issue']);
            if ($couponInfo) {
                $promotionsList = [];
                if($promotions){
                    $promotionsList = array_combine(array_column($promotions, 'id'), $promotions);
                }
                $isOverlay = function($cart) use ($promotionsList) {
                    if (isset($cart['promotions_id']) && $cart['promotions_id']) {
                        foreach ($cart['promotions_id'] as $key => $promotions_id) {
                            $promotions = $promotionsList[$promotions_id] ?? [];
                            if ($promotions && $promotions['promotions_type'] != 4){
                                $overlay = is_string($promotions['overlay']) ? explode(',', $promotions['overlay']) : $promotions['overlay'];
                                if (!in_array(5, $overlay)) {
                                    return false;
                                }
                            }
                        }
                    }
                    return true;
                };
                $type = $couponInfo['applicable_type'] ?? 0;
                $counpon_id = $couponInfo['id'];
                switch ($type) {
                    case 0:
                    case 3:
                        foreach ($cartInfo as $cart) {
                            if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1))  continue;
                            $total_price = bcadd((string)$total_price, (string)bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 4), 2);
                            $count++;
                        }
                        foreach ($cartInfo as &$cart) {
                            if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1))  continue;
                            if ($count > 1) {
                                $coupon_price = bcmul((string)bcdiv((string)bcmul((string)$cart['cart_num'], (string)$cart['truePrice'], 4), (string)$total_price, 4), (string)$priceData['coupon_price'], 2);
                                $compute_price = bcadd((string)$compute_price, (string)$coupon_price, 2);
                            } else {
                                $coupon_price = bcsub((string)$priceData['coupon_price'], $compute_price, 2);
                            }
                            $cart['coupon_price'] = $coupon_price;
                            $cart['coupon_id'] = $counpon_id;
                            $count--;
                        }
                        break;
                    case 1://品类券
                        /** @var StoreCategoryServices $storeCategoryServices */
                        $storeCategoryServices = app()->make(StoreCategoryServices::class);
                        $cateGorys = $storeCategoryServices->getAllById((int)$couponInfo['category_id']);
                        if ($cateGorys) {
                            $cateIds = array_column($cateGorys, 'id');
                            foreach ($cartInfo as $cart) {
                                if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1))  continue;
                                if (isset($cart['productInfo']['cate_id']) && array_intersect(explode(',', $cart['productInfo']['cate_id']), $cateIds)) {
                                    $total_price = bcadd((string)$total_price, (string)bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 4), 2);
                                    $count++;
                                }
                            }
                            foreach ($cartInfo as &$cart) {
                                if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1))  continue;
                                $cart['coupon_id'] = 0;
                                $cart['coupon_price'] = 0;
                                if (isset($cart['productInfo']['cate_id']) && array_intersect(explode(',', $cart['productInfo']['cate_id']), $cateIds)) {
                                    if ($count > 1) {
                                        $coupon_price = bcmul((string)bcdiv((string)bcmul((string)$cart['cart_num'], (string)$cart['truePrice'], 4), (string)$total_price, 4), (string)$priceData['coupon_price'], 2);
                                        $compute_price = bcadd((string)$compute_price, (string)$coupon_price, 2);
                                    } else {
                                        $coupon_price = bcsub((string)$priceData['coupon_price'], $compute_price, 2);
                                    }
                                    $cart['coupon_id'] = $counpon_id;
                                    $cart['coupon_price'] = $coupon_price;
                                    $count--;
                                }
                            }
                        }
                        break;
                    case 2://商品劵
                        foreach ($cartInfo as $cart) {
                            if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1))  continue;
                            if (isset($cart['product_id']) && in_array($cart['product_id'], explode(',', $couponInfo['product_id']))) {
                                $total_price = bcadd((string)$total_price, (string)bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 4), 2);
                                $count++;
                            }
                        }
                        foreach ($cartInfo as &$cart) {
                            if (!$isOverlay($cart) || (isset($cart['is_gift']) && $cart['is_gift'] == 1))  continue;
                            $cart['coupon_id'] = 0;
                            $cart['coupon_price'] = 0;
                            if (isset($cart['product_id']) && in_array($cart['product_id'], explode(',', $couponInfo['product_id']))) {
                                if ($count > 1) {
                                    $coupon_price = bcmul((string)bcdiv((string)bcmul((string)$cart['cart_num'], (string)$cart['truePrice'], 4), (string)$total_price, 4), (string)$priceData['coupon_price'], 2);
                                    $compute_price = bcadd((string)$compute_price, (string)$coupon_price, 2);
                                } else {
                                    $coupon_price = bcsub((string)$priceData['coupon_price'], $compute_price, 2);
                                }
                                $cart['coupon_id'] = $counpon_id;
                                $cart['coupon_price'] = $coupon_price;
                                $count--;
                            }
                        }
                        break;
                }
            }
        }
        return $cartInfo;
    }

    /**
     * 计算实际佣金
     * @param int $uid
     * @param array $cartInfo
     * @param $userInfo
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function computeOrderProductBrokerage(int $uid, array $cartInfo, $userInfo)
    {
        //获取后台一级返佣比例
        $storeBrokerageRatio = sys_config('store_brokerage_ratio');
        //获取二级返佣比例
        $storeBrokerageTwo = sys_config('store_brokerage_two');
        /** @var AgentLevelServices $agentLevelServices */
        $agentLevelServices = app()->make(AgentLevelServices::class);
        [$one_brokerage_up, $two_brokerage_up, $spread_uid, $spread_two_uid] = $agentLevelServices->getAgentLevelBrokerage($uid, $userInfo);
        foreach ($cartInfo as &$cart) {
            $oneBrokerage = '0';//一级返佣金额
            $twoBrokerage = '0';//二级返佣金额
            $cartNum = (string)$cart['cart_num'] ?? '0';
            if (isset($cart['productInfo']) && isset($cart['is_gift']) && $cart['is_gift'] == 0) {
                $productInfo = $cart['productInfo'];
                //指定返佣金额
                if (isset($productInfo['is_sub']) && $productInfo['is_sub'] == 1) {
                    $oneBrokerage = bcmul((string)($productInfo['attrInfo']['brokerage'] ?? '0'), $cartNum, 2);
                    $twoBrokerage = bcmul((string)($productInfo['attrInfo']['brokerage_two'] ?? '0'), $cartNum, 2);
                } else {
                    //比例返佣
                    if (isset($productInfo['attrInfo'])) {
                        $price = bcmul((string)($productInfo['attrInfo']['price'] ?? '0'), $cartNum, 4);
                    } else {
                        $price = bcmul((string)($productInfo['price'] ?? '0'), $cartNum, 4);
                    }
                    if ($price) {
                        //一级返佣比例 小于等于零时直接返回 不返佣
                        if ($storeBrokerageRatio > 0) {
                            //计算获取一级返佣比例
                            $brokerageRatio = bcdiv($storeBrokerageRatio, 100, 4);
                            $oneBrokerage = bcmul((string)$price, (string)$brokerageRatio, 2);
                        }
                        //二级返佣比例小于等于0 直接返回
                        if ($storeBrokerageTwo > 0) {
                            //计算获取二级返佣比例
                            $brokerageTwo = bcdiv($storeBrokerageTwo, 100, 4);
                            $twoBrokerage = bcmul((string)$price, (string)$brokerageTwo, 2);
                        }
                    }
                }
            }
            //分销等级上浮佣金
            if ($one_brokerage_up) $oneBrokerage = bcadd((string)$oneBrokerage, (string)bcmul((string)$oneBrokerage, (string)bcdiv((string)$one_brokerage_up, '100', 2), 4), 2);
            if ($two_brokerage_up) $twoBrokerage = bcadd((string)$twoBrokerage, (string)bcmul((string)$twoBrokerage, (string)bcdiv((string)$two_brokerage_up, '100', 2), 4), 2);

            $cart['one_brokerage'] = $oneBrokerage;
            $cart['two_brokerage'] = $twoBrokerage;
        }
        return [$cartInfo, [$spread_uid, $spread_two_uid]];
    }
}
