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
declare (strict_types=1);

namespace app\services\order;

use app\dao\order\StoreCartDao;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\activity\discounts\StoreDiscountsProductsServices;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\promotions\StorePromotionsServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\BaseServices;
use app\services\other\CityAreaServices;
use app\services\product\branch\StoreBranchProductAttrValueServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\product\shipping\ShippingTemplatesNoDeliveryServices;
use app\services\user\level\SystemUserLevelServices;
use app\services\user\member\MemberCardServices;
use app\services\user\UserServices;
use app\jobs\product\ProductLogJob;
use crmeb\services\CacheService;
use crmeb\traits\OptionTrait;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;

/**
 *
 * Class StoreCartServices
 * @package app\services\order
 * @method updateCartStatus($cartIds) 修改购物车状态
 * @method getUserCartNum(int $uid, string $type, int $numType) 购物车数量
 * @method deleteCartStatus(array $cartIds) 修改购物车状态
 * @method productIdByCartNum(array $ids, int $uid, int $staff_id = 0, int $touristUid = 0, int $storeId = 0)  根据商品id获取购物车数量
 * @method getCartList(array $where, ?int $page = 0, ?int $limit = 0, ?array $with = []) 获取用户购物车
 * @method getSum($where, $field) 求和
 * @method getProductTrend($time, $timeType, $str) 购物车趋势
 * @method getHangOrder(int $storeId, int $staffId, string $search = '', int $page = 0, int $limit = 10) 获取挂单数据
 */
class StoreCartServices extends BaseServices
{

    use OptionTrait, ServicesTrait;

    //库存字段比对
    const STOCK_FIELD = 'sum_stock';
	//购物车最大数量
	protected $maxCartNum = 100;

    /**
     * StoreCartServices constructor.
     * @param StoreCartDao $dao
     */
    public function __construct(StoreCartDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取某个用户下的购物车数量
     * @param array $unique
     * @param int $productId
     * @param int $uid
     * @param string $userKey
     * @return array
     */
    public function getUserCartNums(array $unique, int $productId, int $uid, string $userKey = 'uid')
    {
        $where['is_pay'] = 0;
        $where['is_del'] = 0;
        $where['is_new'] = 0;
        $where['product_id'] = $productId;
        $where[$userKey] = $uid;
        return $this->dao->getUserCartNums($where, $unique);
    }

    /**
     * 获取用户下的购物车列表
     * @param int $uid
     * @param $cartIds
     * @param bool $new
     * @param array $addr
     * @param int $shipping_type
     * @param int $store_id
     * @param int $coupon_id
     * @param bool $isCart
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserProductCartListV1(int $uid, $cartIds, bool $new, array $addr = [], int $shipping_type = 1, int $store_id = 0, int $coupon_id = 0, bool $isCart = false)
    {
        if ($new) {
            $cartIds = $cartIds && is_string($cartIds) ? explode(',', $cartIds) : (is_array($cartIds) ? $cartIds : []);
            $cartInfo = [];
            if ($cartIds) {
                foreach ($cartIds as $key) {
                    $info = CacheService::redisHandler()->get($key);
                    if ($info) {
                        $cartInfo[] = $info;
                    }
                }
            }
        } else {
            $cartInfo = $this->dao->getCartList(['uid' => $uid, 'status' => 1, 'id' => $cartIds], 0, 0, ['productInfo', 'attrInfo']);
        }
        if (!$cartInfo) {
            throw new ValidateException('获取购物车信息失败');
        }
        [$cartInfo, $valid, $invalid] = $this->handleCartList($uid, $cartInfo, $addr, $shipping_type, $store_id);
        $type = array_unique(array_column($cartInfo, 'type'));
        $product_type = array_unique(array_column($cartInfo, 'product_type'));
        $activity_id = array_unique(array_column($cartInfo, 'activity_id'));
        $deduction = ['product_type' => $product_type[0] ?? 0, 'type' => $type[0] ?? 0, 'activity_id' => $activity_id[0] ?? 0];
        $promotions = $giveCoupon = $giveCartList = $useCoupon = $giveProduct = [];
        $giveIntegral = $couponPrice = 0;
        if (!$deduction['activity_id']) {
            /** @var StorePromotionsServices $storePromotionsServices */
            $storePromotionsServices = app()->make(StorePromotionsServices::class);
            //计算相关优惠活动
            [$valid, $couponPrice, $useCoupon, $promotions, $giveIntegral, $giveCoupon, $giveCartList] = $storePromotionsServices->computedPromotions($uid, $valid, $store_id, $coupon_id, $isCart);
            if ($giveCartList) {
                foreach ($giveCartList as $key => $give) {
                    $giveProduct[] = [
                        'promotions_id' => $give['promotions_id'][0] ?? 0,
                        'product_id' => $give['product_id'] ?? 0,
                        'unique' => $give['product_attr_unique'] ?? '',
                        'cart_num' => $give['cart_num'] ?? 1,
                    ];
                }
            }
        }
        return compact('cartInfo', 'valid', 'invalid', 'deduction', 'couponPrice', 'useCoupon', 'promotions', 'giveCartList', 'giveIntegral', 'giveCoupon', 'giveProduct');
    }

    /**
     * 验证库存
     * @param int $uid
     * @param int $productId
     * @param int $cartNum
     * @param string $unique
     * @param int $type
     * @param int $activity_id
     * @param int $discount_product_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkProductStock(int $uid, int $productId, int $cartNum, string $unique = '', int $type = 0, int $activity_id = 0, $discount_product_id = 0)
    {
        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);
        switch ($type) {
            case 0://普通
                if ($unique == '') {
                    $unique = $attrValueServices->value(['product_id' => $productId, 'type' => 0], 'unique');
                }
                /** @var StoreProductServices $productServices */
                $productServices = app()->make(StoreProductServices::class);
                $productInfo = $productServices->isValidProduct($productId);
                if (!$productInfo) {
                    throw new ValidateException('该商品已下架或删除');
                }
                if ($productInfo['is_vip_product']) {
                    /** @var UserServices $userServices */
                    $userServices = app()->make(UserServices::class);
                    $is_vip = $userServices->value(['uid' => $uid], 'is_money_level');
                    if (!$is_vip) {
                        throw new ValidateException('该商品为付费会员专享商品');
                    }
                }
                //预售商品
                if ($productInfo['is_presale_product']) {
                    if ($productInfo['presale_start_time'] > time()) throw new ValidateException('预售活动未开始');
                    if ($productInfo['presale_end_time'] < time()) throw new ValidateException('预售活动已结束');
                }

                $attrInfo = $attrValueServices->getOne(['unique' => $unique, 'type' => 0]);
                if (!$unique || !$attrInfo || $attrInfo['product_id'] != $productId) {
                    throw new ValidateException('请选择有效的商品属性');
                }
                $nowStock = $attrInfo['stock'];//现有平台库存
                if ($cartNum > $nowStock) {
                    throw new ValidateException('该商品库存不足' . $cartNum);
                }
                $stockNum = $this->dao->value(['product_id' => $productId, 'product_attr_unique' => $unique, 'uid' => $uid, 'status' => 1, 'store_id' => 0], 'cart_num') ?: 0;
                if ($nowStock < ($cartNum + $stockNum)) {
                    $surplusStock = $nowStock - $cartNum;//剩余库存
                    if ($surplusStock < $stockNum) {
                        $this->dao->update(['product_id' => $productId, 'product_attr_unique' => $unique, 'uid' => $uid, 'status' => 1, 'store_id' => 0], ['cart_num' => $surplusStock]);
                    }
                }
                break;
            case 1://秒杀
                /** @var StoreSeckillServices $seckillService */
                $seckillService = app()->make(StoreSeckillServices::class);
                [$attrInfo, $unique, $productInfo] = $seckillService->checkSeckillStock($uid, $activity_id, $cartNum, $unique);
                break;
            case 2://砍价
                /** @var StoreBargainServices $bargainService */
                $bargainService = app()->make(StoreBargainServices::class);
                [$attrInfo, $unique, $productInfo, $bargainUserInfo] = $bargainService->checkBargainStock($uid, $activity_id, $cartNum, $unique);
                break;
            case 3://拼团
                /** @var StoreCombinationServices $combinationService */
                $combinationService = app()->make(StoreCombinationServices::class);
                [$attrInfo, $unique, $productInfo] = $combinationService->checkCombinationStock($uid, $activity_id, $cartNum, $unique);
                break;
            case 5://套餐
                /** @var StoreDiscountsProductsServices $discountProduct */
                $discountProduct = app()->make(StoreDiscountsProductsServices::class);
                [$attrInfo, $unique, $productInfo] = $discountProduct->checkDiscountsStock($uid, $discount_product_id, $cartNum, $unique);
                break;
            default:
                throw new ValidateException('请刷新后重试');
                break;
        }
        if ($type) {
            //根商品规格库存
            $product_stock = $attrValueServices->value(['product_id' => $productInfo['product_id'], 'suk' => $attrInfo['suk'], 'type' => 0], 'stock');
            if ($product_stock < $cartNum) {
                throw new ValidateException('商品库存不足' . $cartNum);
            }
            if ($type != 5 && !CacheService::checkStock($unique, (int)$cartNum, $type)) {
                throw new ValidateException('商品库存不足' . $cartNum . ',无法购买请选择其他商品!');
            }
        }
        return [$attrInfo, $unique, $bargainUserInfo['bargain_price_min'] ?? 0, $cartNum, $productInfo];
    }

    /**
     * 添加购物车
     * @param int $uid 用户UID
     * @param int $product_id 商品ID
     * @param int $cart_num 商品数量
     * @param string $product_attr_unique 商品SKU
     * @param int $type 添加购物车类型
     * @param bool $new true = 立即购买，false = 加入购物车
     * @param int $activity_id 活动ID
     * @param int $discount_product_id 套餐商品ID
     * @return mixed|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setCart(int $uid, int $product_id, int $cart_num = 1, string $product_attr_unique = '', int $type = 0, bool $new = true, int $activity_id = 0, int $discount_product_id = 0)
    {
        if ($cart_num < 1) $cart_num = 1;
        //检测库存限量
        $store_id = $this->getItem('store_id', 0);
        $staff_id = $this->getItem('staff_id', 0);
        $tourist_uid = $this->getItem('tourist_uid', '');
        if ($store_id) {
            //收银台检测
            [$attrInfo, $product_attr_unique, $bargainPriceMin, $cart_num, $productInfo] = $this->checkCashierProductStock(
                $store_id,
                $uid,
                $product_id,
                $cart_num,
                $product_attr_unique,
                $type,
                $activity_id,
                $discount_product_id
            );
        } else {
            [$attrInfo, $product_attr_unique, $bargainPriceMin, $cart_num, $productInfo] = $this->checkProductStock(
                $uid,
                $product_id,
                $cart_num,
                $product_attr_unique,
                $type, $activity_id,
                $discount_product_id
            );
        }
        $product_type = $productInfo['product_type'];
        if ($new) {
            /** @var StoreOrderCreateServices $storeOrderCreateService */
            $storeOrderCreateService = app()->make(StoreOrderCreateServices::class);
            $key = $storeOrderCreateService->getNewOrderId((string)$uid);
            //普通订单 && 商品是预售商品 订单类型改为预售订单
            if ($type == 0 && $productInfo['is_presale_product']) {
                $type = 6;
            }
            $info['id'] = $key;
            $info['type'] = $type;
            $info['store_id'] = $store_id;
            $info['tourist_uid'] = $tourist_uid;
            $info['product_type'] = $product_type;
            $info['activity_id'] = $activity_id;
            $info['discount_product_id'] = $discount_product_id;
            $info['product_id'] = $product_id;
            $info['product_attr_unique'] = $product_attr_unique;
            $info['cart_num'] = $cart_num;
            $info['productInfo'] = $productInfo ? $productInfo->toArray() : [];
            $info['productInfo']['attrInfo'] = $attrInfo->toArray();
            $info['attrInfo'] = $attrInfo->toArray();
            $info['sum_price'] = $info['productInfo']['attrInfo']['price'] ?? $info['productInfo']['price'] ?? 0;
            //砍价
            if ($type == 2 && $activity_id) {
                $info['truePrice'] = $bargainPriceMin;
                $info['productInfo']['attrInfo']['price'] = $bargainPriceMin;
            } else {
                $info['truePrice'] = $info['productInfo']['attrInfo']['price'] ?? $info['productInfo']['price'] ?? 0;
            }
            //活动商品不参与会员价
            if ($type > 0 && $activity_id) {
                $info['truePrice'] = $info['productInfo']['attrInfo']['price'] ?? 0;
                $info['vip_truePrice'] = 0;
            }
            $info['trueStock'] = $info['productInfo']['attrInfo']['stock'] ?? 0;
            $info['costPrice'] = $info['productInfo']['attrInfo']['cost'] ?? 0;
            try {
                CacheService::redisHandler()->set($key, $info, 3600);
            } catch (\Throwable $e) {
                throw new ValidateException($e->getMessage());
            }
            return $key;
        } else {//加入购物车记录
            ProductLogJob::dispatch(['cart', ['uid' => $uid, 'product_id' => $product_id, 'cart_num' => $cart_num]]);
            $cart = $this->dao->getOne(['type' => $type, 'uid' => $uid, 'tourist_uid' => $tourist_uid, 'product_id' => $product_id, 'product_attr_unique' => $product_attr_unique, 'is_del' => 0, 'is_new' => 0, 'is_pay' => 0, 'status' => 1, 'store_id' => $store_id, 'staff_id' => $staff_id]);
            if ($cart) {
                $cart->cart_num = $cart_num + $cart->cart_num;
                $cart->add_time = time();
                $cart->save();
                return $cart->id;
            } else {
                $add_time = time();
				$id = $this->dao->save(compact('uid', 'tourist_uid', 'store_id', 'staff_id','product_id', 'product_type', 'cart_num', 'product_attr_unique', 'type', 'activity_id', 'add_time'))->id;
                event('cart.add', [$uid, $tourist_uid, $store_id, $staff_id]);
				return $id;
            }

        }
    }

    /**
     * 移除购物车商品
     * @param int $uid
     * @param array $ids
     * @return StoreCartDao|bool
     */
    public function removeUserCart(int $uid, array $ids)
    {
        return $this->dao->removeUserCart($uid, $ids) !== false;
    }

    /**
     * 购物车 修改商品数量
     * @param $id
     * @param $number
     * @param $uid
     * @return bool|\crmeb\basic\BaseModel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeUserCartNum($id, $number, $uid)
    {
        if (!$id || !$number) return false;
        $where = ['uid' => $uid, 'id' => $id];
        $carInfo = $this->dao->getOne($where, 'product_id,type,activity_id,product_attr_unique,cart_num');
        /** @var StoreProductServices $StoreProduct */
        $StoreProduct = app()->make(StoreProductServices::class);
        $stock = $StoreProduct->getProductStock($carInfo->product_id, $carInfo->product_attr_unique);
        if (!$stock) throw new ValidateException('暂无库存');
        if (!$number) throw new ValidateException('库存错误');
        if ($stock < $number) throw new ValidateException('库存不足' . $number);
        if ($carInfo->cart_num == $number) return true;
        return $this->dao->changeUserCartNum(['uid' => $uid, 'id' => $id], (int)$number);
    }

    /**
     * 修改购物车状态
     * @param int $productId
     * @param int $status 0 商品下架
     */
    public function changeStatus(int $productId, $status = 0)
    {
        $this->dao->update($productId, ['status' => $status], 'product_id');
    }

    /**
     * 获取购物车列表
     * @param int $uid
     * @param int $status
     * @param string $cartIds
     * @param int $storeId
     * @param int $staff_id
     * @param int $shipping_type
     * @param int $numType
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCartList(int $uid, int $status, array $cartIds = [], int $storeId = 0, int $staff_id = 0, int $shipping_type = 1, int $touristUid = 0, int $numType = 0)
    {
        // [$page, $limit] = $this->getPageValue();
        $where = ['uid' => $uid, 'store_id' => $storeId, 'tourist_uid' => $touristUid, 'cart_ids' => $cartIds];
        //有店员就证明在收银台中
        if ($staff_id) {
            $where['staff_id'] = $staff_id;
        }
        if ($status != -1) $where = array_merge($where, ['status' => $status]);

        $list = $this->dao->getCartList($where, 0, 0, ['productInfo', 'attrInfo']);

        $count = $promotionsPrice = $coupon_price = 0;
        $cartList = $valid = $promotions = $coupon = $invalid = $type = $activity_id = [];
        if ($list) {
            [$list, $valid, $invalid] = $this->handleCartList($uid, $list, [], $shipping_type, $storeId);
            $activity_id = array_unique(array_column($list, 'activity_id'));
            $type = array_unique(array_column($list, 'type'));

            if (!($activity_id[0] ?? 0)) {
                /** @var StorePromotionsServices $storePromotionsServices */
                $storePromotionsServices = app()->make(StorePromotionsServices::class);
                //计算相关优惠活动
                [$valid, $coupon_price, $coupon, $promotions, $giveIntegral, $giveCoupon, $giveCartList] = $storePromotionsServices->computedPromotions($uid, $valid, $storeId, 0, true);
            }
            $cartList = array_merge($valid, $giveCartList);
            if ($numType) {
                $count = count($valid);
            } else {
                $count = array_sum(array_column($valid, 'cart_num'));
            }
            foreach ($cartList as $key => $cart) {
                if (isset($cart['promotions_true_price']) && isset($cart['price_type']) && $cart['price_type'] == 'promotions') {
                    $promotionsPrice = bcadd((string)$promotionsPrice, (string)bcmul((string)$cart['promotions_true_price'], (string)$cart['cart_num'], 2), 2);
                }
            }
        }
        $deduction = ['type' => $type[0] ?? 0, 'activity_id' => $activity_id[0] ?? 0];
        $deduction['promotions_price'] = $promotionsPrice;
        $deduction['coupon_price'] = $coupon_price;

        return ['promotions' => $promotions, 'coupon' => $coupon, 'valid' => $valid, 'invalid' => $invalid, 'deduction' => $deduction, 'count' => $count];
    }

    /**
     * 购物车重选
     * @param int $cart_id
     * @param int $product_id
     * @param string $unique
     */
    public function modifyCart(int $cart_id, int $product_id, string $unique)
    {
        /** @var StoreProductAttrValueServices $attrService */
        $attrService = app()->make(StoreProductAttrValueServices::class);
        $stock = $attrService->value(['product_id' => $product_id, 'unique' => $unique, 'type' => 0], 'stock');
        if ($stock > 0) {
            $this->dao->update($cart_id, ['product_attr_unique' => $unique, 'cart_num' => 1]);
        } else {
            throw new ValidateException('选择的规格库存不足');
        }
    }

    /**
     * 重选购物车
     * @param $id
     * @param $uid
     * @param $productId
     * @param $unique
     * @param $num
     * @param int $store_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function resetCart($id, $uid, $productId, $unique, $num, int $store_id = 0)
    {
        $res = $this->dao->getOne(['uid' => $uid, 'product_id' => $productId, 'product_attr_unique' => $unique, 'store_id' => $store_id]);
        if ($res) {
            $res->cart_num = $res->cart_num + $num;
            $res->save();
            if ($res['id'] != $id) $this->dao->delete($id);
        } else {
            $this->dao->update($id, ['product_attr_unique' => $unique, 'cart_num' => $num]);
        }
    }

    /**
     * 首页加入购物车
     * @param $uid
     * @param $productId
     * @param $num
     * @param $unique
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setCartNum($uid, $productId, $num, $unique, $type)
    {
        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);

        if ($unique == '') {
            $unique = $attrValueServices->value(['product_id' => $productId, 'type' => 0], 'unique');
        }
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        $productInfo = $productServices->isValidProduct((int)$productId);
        if (!$productInfo) {
            throw new ValidateException('该商品已下架或删除');
        }
        if (!($unique && $attrValueServices->getAttrvalueCount($productId, $unique, 0))) {
            throw new ValidateException('请选择有效的商品属性');
        }
        $stock = $productServices->getProductStock((int)$productId, $unique);
        if ($stock < $num) {
            throw new ValidateException('该商品库存不足' . $num);
        }

        $cart = $this->dao->getOne(['uid' => $uid, 'product_id' => $productId, 'product_attr_unique' => $unique, 'store_id' => 0]);
        if ($cart) {
            if ($type == -1) {
                $cart->cart_num = $num;
            } elseif ($type == 0) {
                $cart->cart_num = $cart->cart_num - $num;
            } elseif ($type == 1) {
                if ($cart->cart_num >= $stock) {
                    throw new ValidateException('该商品库存只有' . $stock);
                }
                $new_cart_num = $cart->cart_num + $num;
                if ($new_cart_num > $stock) {
                    $new_cart_num = $stock;
                }
                $cart->cart_num = $new_cart_num;
            }
            if ($cart->cart_num === 0) {
                return $this->dao->delete($cart->id);
            } else {
                $cart->add_time = time();
                $cart->save();
                return $cart->id;
            }
        } else {
            $data = [
                'uid' => $uid,
                'product_id' => $productId,
                'product_type' => $productInfo['product_type'],
                'cart_num' => $num,
                'product_attr_unique' => $unique,
                'type' => 0,
                'add_time' => time()
            ];
			$id = $this->dao->save($data)->id;
			event('cart.add', [$uid, 0, 0, 0]);
            return $id;
        }
    }

    /**
     * 获取用户购物车数量  ids 统计金额
     * @param int $uid
     * @param string $numType
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCartCount(int $uid, string $numType)
    {
        $count = 0;
        $ids = [];
        $sum_price = 0;
        $cartList = $this->dao->getUserCartList(['uid' => $uid, 'store_id' => 0], 'id,cart_num');
        if ($cartList) {
            $ids = array_column($cartList, 'id');
            if ($numType) {
                $count = count($cartList);
            } else {
                $count = array_sum(array_column($cartList, 'cart_num'));
            }
        }
        return compact('count', 'ids', 'sum_price');
    }

    /**
     * 处理购物车数据
     * @param int $uid
     * @param array $cartList
     * @param array $addr
     * @param int $shipping_type
     * @param int $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handleCartList(int $uid, array $cartList, array $addr = [], int $shipping_type = 1, int $store_id = 0)
    {
        if (!$cartList) {
            return [$cartList, [], [], [], 0, [], []];
        }
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        /** @var MemberCardServices $memberCardService */
        $memberCardService = app()->make(MemberCardServices::class);
        $vipStatus = $memberCardService->isOpenMemberCardCache('vip_price', false);
        $tempIds = [];
        $userInfo = [];
        $discount = 100;
        $productIds = $allStock = $attrUniquesArr = [];
        if ($uid) {
            /** @var UserServices $user */
            $user = app()->make(UserServices::class);
            $userInfo = $user->getUserCacheInfo($uid);
            //用户等级是否开启
            if (sys_config('member_func_status', 1)) {
                /** @var SystemUserLevelServices $systemLevel */
                $systemLevel = app()->make(SystemUserLevelServices::class);
                $discount = $systemLevel->getDiscount($uid, (int)$userInfo['level']);
            }
        }
        //不送达运费模板
        if ($shipping_type == 1 && $addr) {
            $cityId = (int)($addr['city_id'] ?? 0);
            if ($cityId) {
                /** @var CityAreaServices $cityAreaServices */
                $cityAreaServices = app()->make(CityAreaServices::class);
                $cityIds = $cityAreaServices->getRelationCityIds($cityId);
                foreach ($cartList as $item) {
                    $tempIds[] = $item['productInfo']['temp_id'];
                }
                /** @var ShippingTemplatesNoDeliveryServices $noDeliveryServices */
                $noDeliveryServices = app()->make(ShippingTemplatesNoDeliveryServices::class);
                $tempIds = $noDeliveryServices->isNoDelivery(array_unique($tempIds), $cityIds);
            }
        } elseif (($shipping_type == 2 || $shipping_type == 4) && $store_id) {
            $productIds = array_unique(array_column($cartList, 'product_id'));

            /** @var StoreBranchProductServices $branchProductServics */
            $branchProductServics = app()->make(StoreBranchProductServices::class);
            $productIds = $branchProductServics->getColumn([['product_id', 'IN', $productIds], ['is_show', '=', 1], ['is_del', '=', 0], ['store_id', '=', $store_id]], 'product_id');
            $attrUniques = [];
            $attrUniquesArr = [];
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            foreach ($cartList as $cart) {
                switch ($cart['type']) {
                    case 0:
                    case 6:
                        $attrUniques[] = $cart['product_attr_unique'];
                        $attrUniquesArr[$cart['product_attr_unique']] = $cart['product_attr_unique'];
                        break;
                    case 1:
                    case 2:
                    case 3:
                    case 5:
                        $suk = $skuValueServices->value(['unique' => $cart['product_attr_unique'], 'product_id' => $cart['activity_id'], 'type' => $cart['type']], 'suk');
                        $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $cart['product_id'], 'type' => 0], 'unique');
                        $attrUniquesArr[$cart['product_attr_unique']] = $productUnique;
                        $attrUniques[] = $productUnique;
                        break;
                }
            }
            /** @var StoreBranchProductAttrValueServices $storeValueService */
            $storeValueService = app()->make(StoreBranchProductAttrValueServices::class);
            $allStock = $storeValueService->getColumn([['unique', 'in', $attrUniques], ['store_id', '=', $store_id]], 'stock', 'unique');
        }

        $valid = $invalid = [];
        foreach ($cartList as &$item) {
            $item['is_gift'] = 0;
            $item['productInfo']['express_delivery'] = false;
            $item['productInfo']['store_mention'] = false;
            $item['productInfo']['store_delivery'] = false;
            if (isset($item['productInfo']['delivery_type'])) {
                $item['productInfo']['delivery_type'] = is_string($item['productInfo']['delivery_type']) ? explode(',', $item['productInfo']['delivery_type']) : $item['productInfo']['delivery_type'];
                if (in_array(1, $item['productInfo']['delivery_type'])) {
                    $item['productInfo']['express_delivery'] = true;
                }
                if (in_array(2, $item['productInfo']['delivery_type'])) {
                    $item['productInfo']['store_mention'] = true;
                }
                if (in_array(3, $item['productInfo']['delivery_type'])) {
                    $item['productInfo']['store_delivery'] = true;
                }
            }
            if (isset($item['attrInfo']) && $item['attrInfo'] && (!isset($item['productInfo']['attrInfo']) || !$item['productInfo']['attrInfo'])) {
                $item['productInfo']['attrInfo'] = $item['attrInfo'] ?? [];
            }
            $item['attrStatus'] = isset($item['productInfo']['attrInfo']['stock']) && $item['productInfo']['attrInfo']['stock'] ? true : false;
            $item['productInfo']['attrInfo']['image'] = $item['productInfo']['attrInfo']['image'] ?? $item['productInfo']['image'] ?? '';
            $item['productInfo']['attrInfo']['suk'] = $item['productInfo']['attrInfo']['suk'] ?? '已失效';
            if (isset($item['productInfo']['attrInfo'])) {
                $item['productInfo']['attrInfo'] = get_thumb_water($item['productInfo']['attrInfo']);
            }
            $item['productInfo'] = get_thumb_water($item['productInfo']);
            $productInfo = $item['productInfo'];
            $item['vip_truePrice'] = 0;

            if (isset($productInfo['attrInfo']['product_id']) && $item['product_attr_unique']) {
                $item['costPrice'] = $productInfo['attrInfo']['cost'] ?? 0;
                $item['trueStock'] = $productInfo['attrInfo']['stock'] ?? 0;
                $item['truePrice'] = $productInfo['attrInfo']['price'] ?? 0;
                $item['sum_price'] = $productInfo['attrInfo']['price'] ?? 0;
                if (!$item['type'] || !$item['activity_id']) {
                    [$truePrice, $vip_truePrice, $type] = $productServices->setLevelPrice($productInfo['attrInfo']['price'] ?? 0, $uid, $userInfo, $vipStatus, $discount, $productInfo['attrInfo']['vip_price'] ?? 0, $productInfo['is_vip'] ?? 0, true);
                    $item['truePrice'] = $truePrice;
                    $item['vip_truePrice'] = $vip_truePrice;
                    $item['price_type'] = $type;
                }
            } else {
                $item['costPrice'] = $item['productInfo']['cost'] ?? 0;
                $item['trueStock'] = $item['productInfo']['stock'] ?? 0;
                $item['truePrice'] = $item['productInfo']['price'] ?? 0;
                $item['sum_price'] = $item['productInfo']['price'] ?? 0;
                if (!$item['type'] || !$item['activity_id']) {
                    [$truePrice, $vip_truePrice, $type] = $productServices->setLevelPrice($item['productInfo']['price'] ?? 0, $uid, $userInfo, $vipStatus, $discount, $item['productInfo']['vip_price'] ?? 0, $item['productInfo']['is_vip'] ?? 0, true);
                    $item['truePrice'] = $truePrice;
                    $item['vip_truePrice'] = $vip_truePrice;
                    $item['price_type'] = $type;
                }
            }
            if (isset($item['status']) && $item['status'] == 0) {
                $item['is_valid'] = 0;
                $invalid[] = $item;
            } else {
                switch ($shipping_type) {
                    case 1:
                        //不送达
                        if (in_array($item['productInfo']['temp_id'], $tempIds)) {
                            $item['is_valid'] = 0;
                            $invalid[] = $item;
                        } else {
                            $item['is_valid'] = 1;
                            $valid[] = $item;
                        }
                        break;
                    case 2:
                        //不支持到店自提
                        if (isset($item['productInfo']['delivery_type']) && $item['productInfo']['delivery_type'] && !in_array(2, $item['productInfo']['delivery_type'])) {
                            $item['is_valid'] = 0;
                            $invalid[] = $item;
                        } elseif ($item['productInfo']['product_type'] == 1) {
                            $item['is_valid'] = 0;
                            $invalid[] = $item;
                        } else {
                            if (!in_array(isset($item['productInfo']['product_id']) ? $item['productInfo']['product_id'] : $item['productInfo']['id'], $productIds) || $item['cart_num'] > ($allStock[$attrUniquesArr[$item['product_attr_unique']]] ?? 0)) {
                                $item['is_valid'] = 0;
                                $invalid[] = $item;
                            } else {
                                $item['is_valid'] = 1;
                                $valid[] = $item;
                            }
                        }
                        break;
                    case 4:
                        //无库存｜｜下架
                        if (!in_array(isset($item['productInfo']['product_id']) ? $item['productInfo']['product_id'] : $item['productInfo']['id'], $productIds) || $item['cart_num'] > ($allStock[$attrUniquesArr[$item['product_attr_unique']]] ?? 0)) {
                            $item['is_valid'] = 0;
                            $invalid[] = $item;
                        } else {
                            $item['is_valid'] = 1;
                            $valid[] = $item;
                        }
                        break;
                }
            }
            unset($item['attrInfo']);
        }
        return [$cartList, $valid, $invalid];
    }

    /**
     * 检测库存
     * @param int $productId
     * @param int $cartNum
     * @param string $unique
     * @return mixed
     */
    public function checkCashierProductStock(int $store_id, int $uid, int $productId, int $cartNum, string $unique = '', int $type = 0, int $activity_id = 0, $discount_product_id = 0)
    {
        switch ($type) {
            case 0:
                /** @var StoreProductAttrValueServices $productAttrValueServices */
                $productAttrValueServices = app()->make(StoreProductAttrValueServices::class);
                if ($unique == '') {
                    $unique = $productAttrValueServices->value(['product_id' => $productId, 'type' => 0], 'unique');
                }
                /** @var StoreProductServices $productServices */
                $productServices = app()->make(StoreProductServices::class);
                $productInfo = $productServices->isValidProduct($productId);
                if (!$productInfo) {
                    throw new ValidateException('该商品已下架或删除');
                }
                /** @var StoreBranchProductServices $branchProductServices */
                $branchProductServices = app()->make(StoreBranchProductServices::class);
                $branchProductInfo = $branchProductServices->isValidProduct($productId, $store_id);
                if (!$branchProductInfo) {
                    throw new ValidateException('门店该商品已下架或删除');
                }
                /** @var StoreBranchProductAttrValueServices $attrValueServices */
                $attrValueServices = app()->make(StoreBranchProductAttrValueServices::class);
                $attrInfo = $attrValueServices->getOne(['store_id' => $store_id, 'product_id' => $productId,'unique' => $unique, 'type' => 0]);
                if (!$unique || !$attrInfo || $attrInfo['product_id'] != $productId) {
                    throw new ValidateException('请选择有效的商品属性');
                }
                $nowStock = $attrInfo['stock'];//现有库存
                if ($cartNum > $nowStock) {
                    throw new ValidateException('该商品库存不足' . $cartNum);
                }
                $attrInfo = $productAttrValueServices->getOne(['product_id' => $productId,'unique' => $unique, 'type' => 0]);
                break;
            case 1://秒杀
                /** @var StoreSeckillServices $seckillService */
                $seckillService = app()->make(StoreSeckillServices::class);
                [$attrInfo, $unique, $productInfo] = $seckillService->checkSeckillStock($uid, $activity_id, $cartNum, $unique);
                break;
        }

        if ($type === 1 && !CacheService::checkStock($unique, (int)$cartNum, $type)) {
            throw new ValidateException('商品库存不足' . $cartNum . ',无法购买请选择其他商品!');
        }

        return [$attrInfo, $unique, 0, $cartNum, $productInfo];
    }

    /**
     * 门店给用户加入购物车
     * @param int $uid
     * @param int $productId
     * @param int $cartNum
     * @param string $unique
     * @param int $staff_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addCashierCart(int $uid, int $productId, int $cartNum, string $unique, int $staff_id)
    {
        $store_id = $this->getItem('store_id', 0);
        $tourist_uid = $this->getItem('tourist_uid', '');
        if (!$store_id) {
            throw new ValidateException('缺少门店ID');
        }

        [$nowStock, $unique, $bargainPriceMin, $cart_num, $productInfo] = $this->checkCashierProductStock($store_id, $uid, $productId, $cartNum, $unique);

        ProductLogJob::dispatch(['cart', ['uid' => $uid, 'product_id' => $productId, 'cart_num' => $cartNum]]);
        $cart = $this->dao->getOne([
            'uid' => $uid,
            'product_id' => $productId,
            'product_attr_unique' => $unique,
            'store_id' => $store_id,
            'staff_id' => $staff_id,
            'tourist_uid' => $tourist_uid,
            'is_del' => 0,
            'is_new' => 0,
            'is_pay' => 0,
            'status' => 1
        ]);
        if ($cart) {
            if ($nowStock < ($cartNum + $cart['cart_num'])) {
                $cartNum = $nowStock - $cartNum;//剩余库存
            }
            if ($cartNum == 0) throw new ValidateException('库存不足');
            $cart->cart_num = $cartNum + $cart->cart_num;
            $cart->add_time = time();
            $cart->save();
            return $cart->id;
        } else {
            $add_time = time();
            $data = compact('uid', 'store_id', 'add_time', 'tourist_uid');
            $data['type'] = 0;
            $data['product_id'] = $productId;
            $data['product_type'] = $productInfo['product_type'];
            $data['cart_num'] = $cartNum;
            $data['product_attr_unique'] = $unique;
            $data['store_id'] = $store_id;
            $data['staff_id'] = $staff_id;
			$id = $this->dao->save($data)->id;
			event('cart.add', [$uid, $tourist_uid, $store_id, $staff_id]);
            return $id;
        }
    }

    /**
     * @param int $id
     * @param int $number
     * @param int $uid
     * @param int $storeId
     * @return bool|\crmeb\basic\BaseModel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function changeCashierCartNum(int $id, int $number, int $uid, int $storeId)
    {
        if (!$id || !$number) return false;
        $where = ['uid' => $uid, 'id' => $id, 'store_id' => $storeId];
        $carInfo = $this->dao->getOne($where, 'product_id,product_attr_unique,cart_num');
        /** @var StoreBranchProductServices $storeProduct */
        $storeProduct = app()->make(StoreBranchProductServices::class);
        $stock = $storeProduct->getProductStock($carInfo->product_id, $storeId, $carInfo->product_attr_unique);
        if (!$stock) throw new ValidateException('暂无库存');
        if (!$number) throw new ValidateException('库存错误');
        if ($stock < $number) throw new ValidateException('库存不足' . $number);
        if ($carInfo->cart_num == $number) return true;
        $this->checkCashierProductStock($storeId, $uid, (int)$carInfo->product_id, $number, $carInfo->product_attr_unique);
        return $this->dao->changeUserCartNum(['uid' => $uid, 'id' => $id], (int)$number);
    }

    /**
     * 购物车重选
     * @param int $cart_id
     * @param int $product_id
     * @param string $unique
     */
    public function modifyCashierCart(int $storeId, int $cart_id, int $product_id, string $unique)
    {
        /** @var StoreBranchProductAttrValueServices $attrService */
        $attrService = app()->make(StoreBranchProductAttrValueServices::class);
        $stock = $attrService->value(['store_id' => $storeId, 'product_id' => $product_id, 'unique' => $unique, 'type' => 0], 'stock');
        if ($stock > 0) {
            $this->dao->update($cart_id, ['product_attr_unique' => $unique, 'cart_num' => 1]);
        } else {
            throw new ValidateException('选择的规格库存不足');
        }
    }

    /**
     * 批量加入购物车
     * @param array $cart
     * @param int $storeId
     * @param int $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function batchAddCart(array $cart, int $storeId, int $uid)
    {
        $this->setItem('store_id', $storeId);
        $cartIds = [];
        foreach ($cart as $item) {
            if (!isset($item['productId'])) {
                throw new ValidateException('缺少商品ID');
            }
            if (!isset($item['cartNum'])) {
                throw new ValidateException('缺少购买商品数量');
            }
            if (!isset($item['uniqueId'])) {
                throw new ValidateException('缺少唯一值');
            }
            $cartIds[] = $this->addCashierCart($uid, (int)$item['productId'], (int)$item['cartNum'], $item['uniqueId']);
        }
        $this->reset();
        return $cartIds;
    }

    /**
     * 组合前端购物车需要的数据结构
     * @param array $cartList
     * @param array $protmoions
     * @return array
     */
    public function getReturnCartList(array $cartList, array $promotions)
    {
        $result = [];
        if ($cartList) {
            if($promotions) $promotions = array_combine(array_column($promotions, 'id'), $promotions);
            $i = 0;
            foreach ($cartList as $key => $cart) {
                $data = ['promotions' => [], 'pids' => [],'cart' => []];
                if ($result && isset($cart['promotions_id']) && $cart['promotions_id']) {
                    $isTure = false;
                    foreach ($result as $key => &$res) {
                        if (array_intersect($res['pids'], $cart['promotions_id'])) {
                            $res['pids'] = array_unique(array_merge($res['pids'], $cart['promotions_id'] ?? []));
                            $res['cart'][] = $cart;
                            $isTure = true;
                            break;
                        }
                    }
                    if (!$isTure) {
                        $data['cart'][] = $cart;
                        $data['pids'] = array_unique($cart['promotions_id'] ?? []);
                        $result[$i] = $data;
                        $i++;
                    }
                } else {
                    $data['cart'][] = $cart;
                    $data['pids'] = array_unique($cart['promotions_id'] ?? []);
                    $result[$i] = $data;
                    $i++;
                }
            }

            foreach ($result as $key => &$item) {
                if ($item['pids']) {
                    foreach ($item['pids'] as $key => $id) {
                        $item['promotions'][] = $promotions[$id] ?? [];
                    }
                }
            }
        }
        return $result;
    }


	/**
 	*
	* @param int $uid
	* @param int $tourist_uid
	* @param int $store_id
	* @param int $staff_id
	* @return bool
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	 */
    public function controlCartNum(int $uid, int $tourist_uid = 0, int $store_id = 0, int $staff_id = 0)
    {
		$maxCartNum = $this->maxCartNum;
        $where = [
            'is_del' => 0,
            'is_new' => 0,
            'is_pay' => 0,
            'status' => 1
        ];
		if ($uid) $where['uid'] = $uid;
		if ($tourist_uid) $where['tourist_uid'] = $tourist_uid;
		if ($store_id) $where['store_id'] = $store_id;
		if ($staff_id) $where['staff_id'] = $staff_id;
		try {
			$count = $this->dao->count($where);
			if ($count >= $maxCartNum) {//删除一个最早加入购物车商品
				$one = $this->dao->search($where)->order('id asc')->find();
				if ($one) {
					$this->dao->delete($one['id']);
				}
			}
		} catch (\Throwable $e) {
			\think\facade\Log::error('自动控制购物车数量，删除最早加入商品失败：'. $e->getMessage());
		}
		return  true;
	}
}
