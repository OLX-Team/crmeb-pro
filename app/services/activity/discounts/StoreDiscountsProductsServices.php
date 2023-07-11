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

namespace app\services\activity\discounts;

use app\dao\activity\discounts\StoreDiscountsProductsDao;
use app\services\BaseServices;
use app\services\product\sku\StoreProductAttrResultServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use think\exception\ValidateException;


/**
 * 优惠套餐商品
 * Class StoreDiscountsProductsServices
 * @package app\services\activity\discounts
 * @method save($data)
 * @method update($where, $data)
 */
class StoreDiscountsProductsServices extends BaseServices
{
    public function __construct(StoreDiscountsProductsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 删除规格和优惠商品
     * @param $discount_id
     * @return mixed
     */
    public function del($discount_id)
    {
        /** @var StoreProductAttrResultServices $storeProductAttrResultServices */
        $storeProductAttrResultServices = app()->make(StoreProductAttrResultServices::class);
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);

        $ids = $this->dao->getColumn(['discount_id' => $discount_id], 'id');

        foreach ($ids as $id) {
            $storeProductAttrServices->del($id, 5);
            $storeProductAttrResultServices->del($id, 5);
            $storeProductAttrValueServices->del($id, 5);
        }
        return $this->dao->delete($discount_id, 'discount_id');
    }

    /**
     * 下单｜加入购物车验证套餐商品库存
     * @param int $uid
     * @param int $discount_product_id
     * @param int $cartNum
     * @param string $unique
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkDiscountsStock(int $uid, int $discount_product_id, int $cartNum = 1, string $unique = '')
    {
        $discountProductInfo = $this->dao->getDiscountProductInfo($discount_product_id, '*,title as store_name', ['product']);
        if (!$discountProductInfo) {
            throw new ValidateException('该商品已下架或删除');
        }
        $discountProductInfo['temp_id'] = $discountProductInfo['p_temp_id'] ?? 0;
        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);
        $attrInfo = $attrValueServices->getOne(['product_id' => $discount_product_id, 'unique' => $unique, 'type' => 5]);
        if (!$attrInfo || $attrInfo['product_id'] != $discount_product_id) {
            throw new ValidateException('请选择有效的商品属性');
        }
        return [$attrInfo, $unique, $discountProductInfo];
    }
}