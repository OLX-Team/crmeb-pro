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

namespace app\jobs\activity;

use app\services\user\UserServices;
use app\services\user\UserBillServices;
use app\services\user\label\UserLabelRelationServices;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\activity\promotions\StorePromotionsAuxiliaryServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 营销：优惠活动
 * Class StorePromotionsJob
 * @package app\jobs\activity
 */
class StorePromotionsJob extends BaseJobs
{

    use QueueTrait;

    /**
     * 赠送
     * @param $orderInfo
     * @return bool
     */
    public function give($orderInfo)
    {
        $uid = (int)$orderInfo['uid'];
        $promotions_give = [];
        if (isset($orderInfo['promotions_give']) && $orderInfo['promotions_give']) {
            $promotions_give = is_string($orderInfo['promotions_give']) ? json_decode($promotions_give, true) : $orderInfo['promotions_give'];
        }
        $give_integral = $promotions_give['give_integral'] ?? 0;
        if ($give_integral) {
            try {
                /** @var UserServices $userServices */
                $userServices = app()->make(UserServices::class);
                $userInfo = $userServices->getUserInfo($uid);
                /** @var UserBillServices $userBillServices */
                $userBillServices = app()->make(UserBillServices::class);
                $balance = bcadd((string)$userInfo['integral'], (string)$give_integral, 0);
                $userServices->update(['uid' => $userInfo['uid']], ['integral' => $balance]);
                $userBillServices->income('order_promotions_give_integral', $uid, $give_integral, $balance, $orderInfo['id']);
            } catch (\Throwable $e) {
                Log::error('优惠活动下单赠送积分失败,失败原因:' . $e->getMessage());
            }
            
        }
        $give_coupon = $promotions_give['give_coupon'] ?? [];
        $this->giveCoupon($uid, $give_coupon);
        return true;
    }

    /**
     * 赠送优惠券
     * @param int $uid
     * @param array $give_coupon
     * @return bool
     */
    public function giveCoupon(int $uid, array $give_coupon)
    {
        if ($give_coupon) {
            try {
                /** @var StoreCouponIssueServices $storeCoupon */
                $storeCoupon = app()->make(StoreCouponIssueServices::class);
                $storeCoupon->orderPayGiveCoupon($uid, $give_coupon);
            } catch (\Throwable $e) {
                Log::error('优惠活动下单赠送优惠券失败,失败原因:' . $e->getMessage());
            }
        }
        return true;
    }

    /**
     * 扣除优惠活动赠品限量
     * @param array $promotions_give
     * @param bool $isDec
     * @rerunt bool
     */ 
    public function changeGiveLimit(array $promotions_give, bool $isDec = true)
    {
        if ($promotions_give){
            try {
                $promotionsArr = $promotions_give['promotions'] ?? [];
                if ($promotionsArr) {
                    /** @var StorePromotionsAuxiliaryServices $storePromotionsAuxiliaryServices */
                    $storePromotionsAuxiliaryServices = app()->make(StorePromotionsAuxiliaryServices::class);
                    $giveCoupon = $promotions_give['give_coupon'] ?? [];
                    $getPromotionsId = function($id, $key) use ($promotionsArr) {
                        $pid = 0;
                        foreach($promotionsArr as $promotions) {
                            $k = $key == 'coupon_id' ? 'giveCoupon' : 'giveProducts';
                            $arr = $promotions[$k] ?? [];
                            $ids = [];
                            if ($arr) $ids = array_column($arr, $key);
                            if ($ids && in_array($id, $ids)) $pid = $promotions['id'] ?? $promotionsArr['id'] ?? 0;
                        }
                        return $pid;
                    };
                    if ($giveCoupon) {
                        foreach ($giveCoupon as  $coupon_id) {
                            $promotions_id = $getPromotionsId($coupon_id, 'coupon_id');
                            if ($promotions_id) $storePromotionsAuxiliaryServices->updateLimit([$promotions_id], 2, (int)$coupon_id, $isDec);
                        }
                    }
                    $giveProduct = $promotions_give['give_product'] ?? [];
                    if ($giveProduct) {
                        foreach ($giveProduct as  $give) {
                            $promotions_id = (int)$give['promotions_id'] ?? 0;
                            $product_id = (int)$give['product_id'] ?? 0;
                            $unique = $give['unique'] ?? '';
                            $cart_num = (int)$give['cart_num'] ?? 1;
                            if ($promotions_id && $product_id && $unique) $storePromotionsAuxiliaryServices->updateLimit([$promotions_id], 3, (int)$product_id, $isDec, $unique, $cart_num);
                        }
                    }
                }
                
            } catch (\Throwable $e) {
                Log::error('订单创建优惠活动赠品限量扣除失败,失败原因:' . $e->getMessage());
            }
            
        }
        return true;
    }


    /**
     * 设置用户购买的标签
     * @param $orderInfo
     */
    public function setUserLabel($orderInfo)
    {
        try {
            $promotions_give = [];
            if (isset($orderInfo['promotions_give']) && $orderInfo['promotions_give']) {
                $promotions_give = is_string($orderInfo['promotions_give']) ? json_decode($promotions_give, true) : $orderInfo['promotions_give'];
            }
            $promotions = $promotions_give['promotions'] ?? [];
            if (!$promotions) {
                return true;
            }
            $labelIds = [];
            foreach ($promotions as $key => $value) {
                $label_id = is_string($value['label_id']) ? explode(',', $value['label_id']) : $value['label_id'];
                $labelIds = array_merge($labelIds, $label_id);
            }
            if (!$labelIds) {
                return true;
            }
            $labelIds = array_unique($labelIds);
            /** @var UserLabelRelationServices $labelServices */
            $labelServices = app()->make(UserLabelRelationServices::class);
            $where = [
                ['label_id', 'in', $labelIds],
                ['uid', '=', $orderInfo['uid']],
                ['store_id', '=', $orderInfo['store_id'] ?? 0]
            ];
            $data = [];
            $userLabel = $labelServices->getColumn($where, 'label_id');
            foreach ($labelIds as $item) {
                if (!in_array($item, $userLabel)) {
                    $data[] = ['uid' => $orderInfo['uid'], 'label_id' => $item, 'store_id' => $orderInfo['store_id'] ?? 0];
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

}
