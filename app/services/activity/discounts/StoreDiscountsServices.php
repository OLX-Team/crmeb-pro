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

use app\dao\activity\discounts\StoreDiscountsDao;
use app\services\BaseServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrResultServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\user\label\UserLabelServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;

/**
 * 优惠套餐
 * Class StoreDiscountsServices
 * @package app\services\activity\discounts
 * @method value($where, $field)
 * @method get($id)
 * @method getDiscountsCount()
 */
class StoreDiscountsServices extends BaseServices
{
    public function __construct(StoreDiscountsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存数据
     * @param $data
     * @return mixed
     */
    public function saveDiscounts($data)
    {
        if ($data['freight'] == 2 && !$data['postage']) {
            throw new AdminException('请设置运费金额');
        }
        if ($data['freight'] == 3 && !$data['temp_id']) {
            throw new AdminException('请选择运费模版');
        }
        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        /** @var StoreDiscountsProductsServices $storeDiscountsProductsServices */
        $storeDiscountsProductsServices = app()->make(StoreDiscountsProductsServices::class);
        return $this->transaction(function () use ($data, $storeProductAttrServices, $storeDiscountsProductsServices) {
            //添加优惠套餐
            $discountsData['title'] = $data['title'];
            $discountsData['image'] = $data['image'];
            $discountsData['type'] = $data['type'];
            $discountsData['is_limit'] = $data['is_limit'];
            $discountsData['limit_num'] = $data['is_limit'] ? $data['limit_num'] : 0;
            $discountsData['link_ids'] = implode(',', $data['link_ids']);
            $discountsData['is_time'] = $data['is_time'];
            $discountsData['start_time'] = $data['is_time'] ? strtotime($data['time'][0]) : 0;
            $discountsData['stop_time'] = $data['is_time'] ? strtotime($data['time'][1]) + 86399 : 0;
            $discountsData['sort'] = $data['sort'];
            $discountsData['add_time'] = time();
            $discountsData['free_shipping'] = $data['free_shipping'];
            $discountsData['status'] = $data['status'];
            $discountsData['freight'] = $data['freight'];
            $discountsData['postage'] = $data['postage'];
            $discountsData['custom_form'] = json_encode($data['custom_form']);
            $product_ids = [];
            foreach ($data['products'] as $product) {
                if ($product['type']) {
                    $product_ids = [];
                    $product_ids[] = $product['product_id'];
                    break;
                } else {
                    $product_ids[] = $product['product_id'];
                }
            }
            $discountsData['product_ids'] = implode(',', $product_ids);
            if ($data['id']) {
                unset($discountsData['add_time']);
                $this->dao->update($data['id'], $discountsData);
                $storeDiscountsProductsServices->del($data['id']);
                $discountsId = $data['id'];
            } else {
                $discountsId = $this->dao->save($discountsData)->id;
            }
            if (!$discountsId) throw new AdminException('添加失败');
            if ($discountsData['is_limit']) CacheService::setStock(md5($discountsId), (int)$discountsData['limit_num'], 5);
            //添加优惠套餐内商品
            /** @var StoreProductServices $productService */
            $productService = app()->make(StoreProductServices::class);
            foreach ($data['products'] as $item) {
				$stock = $productService->value(['id' => $item['product_id'], 'is_del' => 0, 'is_show' => 1], 'stock') ?? 0;
				if ((int)$stock <= 0) {
					throw new AdminException('商品库存不足，请选择其他商品');
				}
                $productData = [];
                $productData['discount_id'] = $discountsId;
                $productData['product_id'] = $item['product_id'];
                $productData['product_type'] = $item['product_type'] ?? 0;
                $productData['title'] = $item['store_name'];
                $productData['image'] = $item['image'];
                $productData['type'] = $item['type'];
                $productData['temp_id'] = $item['temp_id'];
                $discountsProducts = $storeDiscountsProductsServices->save($productData);
                $skuList = $storeProductAttrServices->validateProductAttr($item['items'], $item['attr'], (int)$discountsProducts->id, 5);
                $valueGroup = $storeProductAttrServices->saveProductAttr($skuList, (int)$discountsProducts->id, 5);
                if (!$discountsProducts || !$valueGroup) throw new AdminException('添加失败');
            }
            return true;
        });
    }

    /**
     * 获取列表
     * @param array $where
     * @return array
     */
    public function getList($where = [])
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, ['products'], $page, $limit);
        $count = $this->dao->count($where + ['is_del' => 0]);
        $time = time();
        foreach ($list as &$item) {
            if (!$this->checkDiscount($item, 0) || ($item['stop_time'] && $item['stop_time'] < $time)) {
                $item['status'] = 0;
                $this->dao->update(['id' => $item['id']], ['status' => 0]);
            }
            $item['start_time'] = $item['start_time'] ? date('Y-m-d', $item['start_time']) : 0;
            $item['stop_time'] = $item['stop_time'] ? date('Y-m-d', $item['stop_time']) : 0;
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
        }
        return compact('list', 'count');
    }

    /**
     * 获取详情
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfo($id)
    {
        $discounts = $this->dao->get($id);
        if ($discounts) {
            $discounts = $discounts->toArray();
        } else {
            throw new AdminException('套餐商品未找到！');
        }
        /** @var StoreDiscountsProductsServices $discountsProducts */
        $discountsProducts = app()->make(StoreDiscountsProductsServices::class);
        /** @var UserLabelServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelServices::class);
        $discounts['products'] = $discountsProducts->dao->getList(['discount_id' => $discounts['id']]);
        foreach ($discounts['products'] as &$item) {
            /** @var StoreProductAttrResultServices $storeProductAttrResultServices */
            $storeProductAttrResultServices = app()->make(StoreProductAttrResultServices::class);
            $discountsResult = $storeProductAttrResultServices->value(['product_id' => $item['id'], 'type' => 5], 'result');
            $item['items'] = json_decode($discountsResult, true)['attr'];
            $item['attr'] = $this->getattr($item['items'], $item['id'], 5);
            $item['store_name'] = $item['title'];
        }
        if ($discounts['start_time']) {
            $discounts['time'] = [date('Y-m-d', $discounts['start_time']), date('Y-m-d', $discounts['stop_time'])];
        } else {
            $discounts['time'] = [];
        }
        $link_ids = explode(',', $discounts['link_ids']);
        $discounts['link_ids'] = $userLabelServices->getLabelList(['ids' => $link_ids], ['id', 'label_name']);
        return $discounts;
    }

    /**
     * 获取规格
     * @param $attr
     * @param $id
     * @param $type
     * @return array
     */
    public function getattr($attr, $id, $type)
    {
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $value = attr_format($attr)[1];
        $valueNew = [];
        $count = 0;
        foreach ($value as $key => $item) {
            $detail = $item['detail'];
            $suk = implode(',', $item['detail']);
            $sukValue = $storeProductAttrValueServices->getSkuArray(['product_id' => $id, 'type' => $type, 'suk' => $suk], 'bar_code,code,cost,price,ot_price,stock,image as pic,weight,volume,brokerage,brokerage_two,quota,quota_show', 'suk');
            if (count($sukValue)) {
                $valueNew[$count]['value'] = '';
                foreach (array_values($detail) as $k => $v) {
                    $valueNew[$count]['value' . ($k + 1)] = $v;
                    $valueNew[$count]['value'] .= $valueNew[$count]['value'] == '' ? $v : '，' . $v;
                }
                $valueNew[$count]['detail'] = $detail;
                $valueNew[$count]['pic'] = $sukValue[$suk]['pic'] ?? '';
                $valueNew[$count]['price'] = $sukValue[$suk]['price'] ? floatval($sukValue[$suk]['price']) : 0;
                $valueNew[$count]['cost'] = $sukValue[$suk]['cost'] ? floatval($sukValue[$suk]['cost']) : 0;
                $valueNew[$count]['ot_price'] = isset($sukValue[$suk]['ot_price']) ? floatval($sukValue[$suk]['ot_price']) : 0;
                $valueNew[$count]['stock'] = $sukValue[$suk]['stock'] ? intval($sukValue[$suk]['stock']) : 0;
//                $valueNew[$count]['quota'] = $sukValue[$suk]['quota'] ? intval($sukValue[$suk]['quota']) : 0;
                $valueNew[$count]['quota'] = isset($sukValue[$suk]['quota_show']) && $sukValue[$suk]['quota_show'] ? intval($sukValue[$suk]['quota_show']) : 0;
                $valueNew[$count]['bar_code'] = $sukValue[$suk]['bar_code'] ?? '';
                $valueNew[$count]['code'] = $sukValue[$suk]['code'] ?? '';
                $valueNew[$count]['weight'] = $sukValue[$suk]['weight'] ? floatval($sukValue[$suk]['weight']) : 0;
                $valueNew[$count]['volume'] = $sukValue[$suk]['volume'] ? floatval($sukValue[$suk]['volume']) : 0;
                $valueNew[$count]['brokerage'] = $sukValue[$suk]['brokerage'] ? floatval($sukValue[$suk]['brokerage']) : 0;
                $valueNew[$count]['brokerage_two'] = $sukValue[$suk]['brokerage_two'] ? floatval($sukValue[$suk]['brokerage_two']) : 0;
                $count++;
            }
        }
        return $valueNew;
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function setStatus($id, $status)
    {
        $discounts = $this->dao->get($id, ['*'], ['products']);
        if ($discounts) {
            $discounts = $discounts->toArray();
        } else {
            throw new AdminException('套餐商品未找到！');
        }
        //上架
        if ($status) {
            if ($discounts['stop_time'] && $discounts['stop_time'] < time()) {
                throw new AdminException('套餐活动已结束');
            }
            if (!$this->checkDiscount($discounts, 0)) {
                throw new AdminException('套餐内商品已下架或者库存不足');
            }
        }
        return $this->dao->update($id, ['status' => $status]);
    }

    /**
     * 删除商品
     * @param $id
     * @return mixed
     */
    public function del($id)
    {
        return $this->dao->update($id, ['is_del' => 1]);
    }

    /**
     * 获取优惠套餐列表
     * @param $product_id
     * @param $uid
     * @param $type
     * @return array
     */
    public function getDiscounts($product_id, $uid, $type = 0)
    {
        $list = $this->dao->getDiscounts($product_id);
        foreach ($list as $key => &$discounts) {
            $discounts = $this->checkDiscount($discounts, $uid);
            if (!$discounts) unset($list[$key]);
        }
        return $list;
    }

    /**
     * 购买｜退款处理套餐限量
     * @param int $discountId
     * @param bool $is_dec
     * @return bool
     */
    public function changeDiscountLimit(int $discountId, bool $is_dec = true)
    {
        $is_limit = $this->dao->value(['id' => $discountId], 'is_limit');
        $res = true;
        //开启限量
        if ($is_limit) {
            if ($is_dec) $res = $res && $this->dao->decLimitNum($discountId);
            else $res = $res && $this->dao->incLimitNum($discountId);
        }
        return $res;
    }

    /**
     * 优惠套餐库存减少
     * @param int $num
     * @param int $discountId
     * @param int $discount_product_id
     * @param int $product_id
     * @param string $unique
     * @param int $store_id
     * @return bool
     */
    public function decDiscountStock(int $num, int $discountId, int $discount_product_id, int $product_id, string $unique, int $store_id = 0)
    {
        if (!$discountId || !$discount_product_id || !$product_id) return false;
        $res = true;
        $productUnique = '';
        if ($unique) {
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            //减去套餐商品sku的库存增加销量
//            $res = false !== $skuValueServices->decProductAttrStock($discount_product_id, $unique, $num, 5);
            //减掉普通商品sku的库存加销量
            $suk = $skuValueServices->value(['unique' => $unique, 'product_id' => $discount_product_id, 'type' => 5], 'suk');
            $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $product_id, 'type' => 0], 'unique');
//            if ($productUnique) {
//                $res = $res && $skuValueServices->decProductAttrStock($product_id, $productUnique, $num);
//            }
        }
        if (!$store_id) {
            /** @var StoreProductServices $services */
            $services = app()->make(StoreProductServices::class);
            //减掉普通商品的库存加销量
            $res = $res && $services->decProductStock($num, $product_id, $productUnique);
        } else {
            /** @var StoreBranchProductServices $storeBranchService */
            $storeBranchService = app()->make(StoreBranchProductServices::class);
            //修改门店库存
            $storeBranchService->updataDecStock($num, $product_id, $store_id, $productUnique);
        }
        return $res;
    }

    /**
     * 加库存减销量
     * @param int $num
     * @param int $discountId 套餐id
     * @param int $discount_product_id 套餐商品id
     * @param int $product_id 原商品ID
     * @param string $unique
     * @return bool
     */
    public function incDiscountStock(int $num, int $discountId, int $discount_product_id, int $product_id, string $unique, int $store_id = 0)
    {
        if (!$discountId || !$discount_product_id || !$product_id) return false;
        $res = true;
        $productUnique = '';
        if ($unique) {
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            //增加套餐商品的sku库存,减去销量
//            $res = false !== $skuValueServices->incProductAttrStock($discount_product_id, $unique, $num, 5);
            //增加当前普通商品sku的库存,减去销量
            $suk = $skuValueServices->value(['unique' => $unique, 'product_id' => $discount_product_id, 'type' => 5], 'suk');
            $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $product_id, 'type' => 0], 'unique');
//            if ($productUnique) {
//                $res = $res && $skuValueServices->incProductAttrStock($product_id, $productUnique, $num);
//            }
        }
        if (!$store_id) {
            /** @var StoreProductServices $services */
            $services = app()->make(StoreProductServices::class);
            //增加普通商品库存
            $res = $res && $services->incProductStock($num, $product_id, $productUnique);
        } else {
            /** @var StoreBranchProductServices $storeBranchService */
            $storeBranchService = app()->make(StoreBranchProductServices::class);
            //修改门店库存
            $storeBranchService->updataDecStock($num, $product_id, $store_id, $productUnique, false);
        }
        return $res;
    }

    /**
     * 判断优惠套餐显示
     * @param $discount
     * @param $uid
     * @return false
     */
    public function checkDiscount($discount, $uid)
    {
        $discount = is_object($discount) ? $discount->toArray() : $discount;
        if ($discount['is_limit'] && $discount['limit_num'] <= 0) return false;
        if (isset($discount['products']) && $discount['products']) {
            /** @var StoreProductServices $productService */
            $productService = app()->make(StoreProductServices::class);
            /** @var StoreProductAttrServices $storeProductAttrServices */
            $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
            $minPrice = 0;
            foreach ($discount['products'] as $key => &$item) {
                $stock = $productService->value(['id' => $item['product_id'], 'is_del' => 0, 'is_show' => 1], 'stock') ?? 0;
                [$productAttr, $productValue] = $storeProductAttrServices->getProductAttrDetail($item['id'], $uid, 0, 5, $item['product_id']);
                if ($discount['type']) {
                    if ($item['type']) {
                        if ($stock == 0) {
                            return false;
                        } else {
                            if (!array_sum(array_column($productValue, 'product_stock'))) return false;
                        }
                    } else {
                        if (!array_sum(array_column($productValue, 'product_stock'))) unset($discount['products'][$key]);
                    }
                } else {
                    if ($stock == 0) {
                        return false;
                    } else {
                        if (!array_sum(array_column($productValue, 'product_stock'))) return false;
                    }
                }
                $item['productAttr'] = $productAttr;
                $item['productValue'] = $productValue;
                $minPrice += min(array_column($productValue, 'price'));
            }
            if (!count($discount['products'])) return false;
            $discount['min_price'] = $minPrice;
            $discount['products'] = array_merge($discount['products']);
        }
        return $discount;
    }
}
