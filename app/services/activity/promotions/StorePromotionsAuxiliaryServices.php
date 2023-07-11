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

namespace app\services\activity\promotions;


use app\dao\activity\promotions\StorePromotionsAuxiliaryDao;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\BaseServices;

/**
 * 优惠活动辅助表
 * Class StorePromotionsAuxiliaryServices
 * @package app\services\activity\promotions
 */
class StorePromotionsAuxiliaryServices extends BaseServices
{

    /**
     * @param StorePromotionsAuxiliaryDao $dao
     */
    public function __construct(StorePromotionsAuxiliaryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
    * 优惠活动关联保存
    * @param int $promotionsId
    * @param int $type
    * @param array $productData
    * @param array $couponData
    * @param array $giveProductData
    * @return bool
     */
    public function savePromotionsRelation(int $promotionsId, int $type, array $productData, array $couponData, array $giveProductData)
    {
        $this->dao->delete(['promotions_id' => $promotionsId]);
        $this->savePromotionsProducts($promotionsId, $type, $productData);
        $this->savePromotionsGiveCoupon($promotionsId, $couponData);
        $this->savePromotionsGiveProducts($promotionsId, $giveProductData);
        return true;
    }

    /**
     * 设置活动关联商品
     * @param int $promotionsId
     * @param int $type
     * @param array $productIds
     * @return bool
     */
    public function savePromotionsProducts(int $promotionsId, int $type, array $productData)
    {
        if ($productData) {
            $data = [];
            $productIds = array_column($productData, 'product_id');
            $productData = array_combine($productIds, $productData);
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            foreach ($productIds as $productId) {
                $unique = $productData[$productId]['unique'] ?? [];
                $skuCount = $skuValueServices->count(['product_id' => $productId, 'type' => 0]);
                $data[] = [
                    'type' => 1,
                    'product_partake_type' => $type,
                    'promotions_id' => $promotionsId,
                    'product_id' => $productId,
                    'is_all' => count($unique) >= $skuCount ? 1 : 0,
                    'unique' => implode(',', $unique)
                ];
            }
            $this->dao->saveAll($data);
        }
        return true;
    }

    /**
    * 设置活动关联赠送优惠券
    * @param int $promotionsId
    * @param array $couponData
    * @return bool
     */
    public function savePromotionsGiveCoupon(int $promotionsId, array $couponData)
    {
        if ($couponData) {
            $data = [];
            $couponIds = array_column($couponData, 'give_coupon_id');
            $couponData = array_combine($couponIds, $couponData);
            foreach ($couponIds as $couponId) {
                $data[] = [
                    'type' => 2,
                    'promotions_id' => $promotionsId,
                    'coupon_id' => $couponId,
                    'limit_num' => $couponData[$couponId]['give_coupon_num'] ?? 0,
                    'surplus_num' => $couponData[$couponId]['give_coupon_num'] ?? 0
                ];
            }
            $this->dao->saveAll($data);
        }
        return true;
    }

    /**
     * 设置活动关联赠送商品
     * @param int $promotionsId
     * @param array $giveProductData
     * @return bool
     */
    public function savePromotionsGiveProducts(int $promotionsId, array $giveProductData)
    {
        if ($giveProductData) {
            $data = [];
            foreach ($giveProductData as $product) {
                $data[] = [
                    'type' => 3,
                    'promotions_id' => $promotionsId,
                    'product_id' => $product['give_product_id'],
                    'limit_num' => $product['give_product_num'] ?? 0,
                    'surplus_num' => $product['give_product_num'] ?? 0,
                    'unique' => $product['unique'] ?? ''
                ];
            }
            $this->dao->saveAll($data);
        }
        return true;
    }


    /**
     * 优惠活动关联赠品限量处理
     * @param array $promotions_id
     * @param int $type
     * @param int $id
     * @param bool $is_dec
     * @param string $unique
     * @param int $num
     * @return bool
     */ 
    public function updateLimit(array $promotions_id, int $type, int $id, bool $is_dec = true, string $unique = '', int $num = 1)
    {
        if (!$promotions_id) return true; 
        $where = ['promotions_id' => $promotions_id, 'type' => $type];
        if ($type == 2) {
            $where['coupon_id'] = $id;        
        } else {
            $where['product_id'] = $id;
            $where['unique'] = $unique;
        }
        $info = $this->dao->get($where);
        if ($info) {
            if ($is_dec) {
                if ($info['surplus_num'] < $num) {
                    $surplus_num = 0;
                } else {
                    $surplus_num = bcsub((string)$info['surplus_num'], (string)$num, 0);
                }
            } else {
                $surplus_num = bcadd((string)$info['surplus_num'], (string)$num, 0);
            }
            
            $this->dao->update($info['id'], ['surplus_num' => $surplus_num]);
        }
        return true;
    }
}
