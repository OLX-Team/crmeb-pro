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

namespace app\services\product\sku;


use app\dao\product\sku\StoreProductAttrDao;
use app\services\BaseServices;
use app\services\order\StoreCartServices;
use app\services\product\product\StoreProductServices;
use crmeb\exceptions\AdminException;
use crmeb\traits\OptionTrait;

/**
 * Class StoreProductAttrService
 * @package app\services\product\sku
 */
class StoreProductAttrServices extends BaseServices
{

    use OptionTrait;

    /**
     * StoreProductAttrServices constructor.
     * @param StoreProductAttrDao $dao
     */
    public function __construct(StoreProductAttrDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 生成规格唯一值
     * @param int $id
     * @param string $sku
     * @return false|string
     */
    public function createAttrUnique(int $id, string $sku)
    {
        return substr(md5($id . $sku . uniqid(true)), 12, 8);
    }

    /**
     * 根据根据前端规格数据获取sku
     * @param array $value
     * @param string
     */ 
    public function getSku(array $value)
    {   
        $sku = '';
        if ($value) {
            $detail = [];
            $count = count($value['detail'] ?? []);
            for ($i=1; $i<=$count ; $i++) { 
                $detail[] = trim($value['value' . $i]);
            }
            $sku = implode(',', $detail);
        }
        return $sku;
    }

    /**
     * 添加商品属性数据判断
     * @param array $attrList
     * @param array $valueList
     * @param int $productId
     * @param int $type
     * @param int $is_vip
     * @return array
     */
    public function validateProductAttr(array $attrList, array $valueList, int $productId, int $type = 0, int $is_vip = 0)
    {
        $result = ['attr' => $attrList, 'value' => $valueList];
        $attrValueList = [];
        $attrNameList = [];
        foreach ($attrList as $index => $attr) {
            if (!isset($attr['value'])) {
                throw new AdminException('请输入规则名称!');
            }
            $attr['value'] = trim($attr['value']);
            if (!isset($attr['value'])) {
                throw new AdminException('请输入规则名称!!');
            }
            if (!isset($attr['detail']) || !count($attr['detail'])) {
                throw new AdminException('请输入属性名称!');
            }
            foreach ($attr['detail'] as $k => $attrValue) {
                $attrValue = trim($attrValue);
                if (empty($attrValue)) {
                    throw new AdminException('请输入正确的属性');
                }
                $attr['detail'][$k] = $attrValue;
                $attrValueList[] = $attrValue;
                $attr['detail'][$k] = $attrValue;
            }
            $attrNameList[] = $attr['value'];
            $attrList[$index] = $attr;
        }
        $attrCount = count($attrList);
        foreach ($valueList as $index => $value) {
            if (!isset($value['detail']) || count($value['detail']) != $attrCount) {
                throw new AdminException('请填写正确的商品信息');
            }
            if (!isset($value['price']) || !is_numeric($value['price']) || floatval($value['price']) != $value['price']) {
                throw new AdminException('请填写正确的商品价格');
            }
            if (!isset($value['stock']) || !is_numeric($value['stock']) || intval($value['stock']) != $value['stock']) {
                throw new AdminException('请填写正确的商品库存');
            }
            if (!isset($value['cost']) || !is_numeric($value['cost']) || floatval($value['cost']) != $value['cost']) {
                throw new AdminException('请填写正确的商品成本价格');
            }
            if (!isset($value['pic']) || empty($value['pic'])) {
                throw new AdminException('请上传商品规格图片');
            }
            if ($is_vip && (!isset($value['vip_price']) || !$value['vip_price'])) {
                throw new AdminException('会员价格不能为0');
            }
            foreach ($value['detail'] as $attrName => $attrValue) {
                //如果attrName 存在空格 则这个规格key 会出现两次
                unset($valueList[$index]['detail'][$attrName]);
                $attrName = trim($attrName);
                $attrValue = trim($attrValue);
                if (!in_array($attrName, $attrNameList, true)) {
                    throw new AdminException($attrName . '规则不存在');
                }
                if (!in_array($attrValue, $attrValueList, true)) {
                    throw new AdminException($attrName . '属性不存在');
                }
                if (empty($attrName)) {
                    throw new AdminException('请输入正确的属性');
                }
                $valueList[$index]['detail'][$attrName] = $attrValue;
            }
        }
        $attrGroup = [];
        $valueGroup = [];
        foreach ($attrList as $k => $value) {
            $attrGroup[] = [
                'product_id' => $productId,
                'attr_name' => $value['value'],
                'attr_values' => $value['detail'],
                'type' => $type
            ];
        }
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $skuArray = $storeProductAttrValueServices->getSkuArray(['product_id' => $productId, 'type' => $type], 'unique', 'suk');
        foreach ($valueList as $k => $value) {
//            sort($value['detail'], SORT_STRING);
            $sku = implode(',', $value['detail']);
            $valueGroup[$sku] = [
                'product_id' => $productId,
                'suk' => $this->getSku($value),
                'price' => $value['price'],
                'cost' => $value['cost'],
                'ot_price' => $value['ot_price'],
                'stock' => $value['stock'],
                'unique' => $skuArray[$sku] ?? $this->createAttrUnique($productId, $sku),
                'image' => $value['pic'],
                'bar_code' => $value['bar_code'] ?? '',
                'weight' => $value['weight'] ?? 0,
                'volume' => $value['volume'] ?? 0,
                'brokerage' => $value['brokerage'] ?? 0,
                'brokerage_two' => $value['brokerage_two'] ?? 0,
                'type' => $type,
                'quota' => $value['quota'] ?? 0,
                'quota_show' => $value['quota'] ?? 0,
                'vip_price' => $value['vip_price'] ?? 0,
                'code' => $value['code'] ?? '',
                'product_type' => $value['product_type'] ?? 0,
                'virtual_list' => $value['virtual_list'] ?? [],
                'disk_info' => $value['disk_info'] ?? '',
            ];
        }
        if (!count($attrGroup) || !count($valueGroup)) {
            throw new AdminException('请设置至少一个属性!');
        }
        return compact('result', 'attrGroup', 'valueGroup');
    }

    /**
     * 保存商品规格
     * @param array $data
     * @param int $id
     * @param int $type
     * @return bool|mixed|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveProductAttr(array $data, int $id, int $type = 0)
    {
        $this->setAttr($data['attrGroup'], $id, $type);
        /** @var StoreProductAttrResultServices $storeProductAttrResultServices */
        $storeProductAttrResultServices = app()->make(StoreProductAttrResultServices::class);
        $storeProductAttrResultServices->setResult($data['result'], $id, $type);
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);

        $valueGroup = $data['valueGroup'] ?? [];
        $updateSuks = array_column($valueGroup, 'suk');
        $oldSuks = [];
        $oldAttrValue = $storeProductAttrValueServices->getSkuArray(['product_id' => $id, 'type' => $type], '*', 'suk');
        if ($oldAttrValue) $oldSuks = array_column($oldAttrValue, 'suk');
        $delSuks = array_merge(array_diff($oldSuks, $updateSuks));
        $dataAll = [];
        $res1 = $res2 = $res3 = true;
        foreach ($valueGroup as $item) {
            if ($oldSuks && in_array($item['suk'], $oldSuks) && isset($oldAttrValue[$item['suk']])) {
                $attrId = $oldAttrValue[$item['suk']]['id'];
                unset($item['suk'], $item['unique']);
                $item['virtual_list'] =json_encode($item['virtual_list']);
                $res1 = $res1 && $storeProductAttrValueServices->update($attrId, $item);
            } else {
                $dataAll[] = $item;
            }
        }
        if ($delSuks) {
            $res2 = $storeProductAttrValueServices->del($id, $type, $delSuks);
        }
        if ($dataAll) {
            $res3 = $storeProductAttrValueServices->saveAll($dataAll);
        }
        if ($res1 && $res2 && $res3) {
            $unique = array_column($valueGroup, 'unique');
            $storeProductAttrValueServices->updateSumStock($unique ?? []);
            return $valueGroup;
        } else {
            throw new AdminException('商品规格信息保存失败');
        }

    }


    /**
     * 获取商品规格
     * @param array $where
     * @return array
     */
    public function getProductAttr(array $where)
    {
        return $this->dao->getProductAttr($where);
    }

    /**
     * 获取商品规格详情
     * @param int $id
     * @param int $uid
     * @param int $cartNum //是否查询购物车数量
     * @param int $type //活动类型 attr_value表
     * @param int $productId
     * @param array $productInfo
     * @param int $discount //限时折扣
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductAttrDetail(int $id, int $uid, int $cartNum = 0, int $type = 0, int $productId = 0, array $productInfo = [], int $discount = -1)
    {
        $attrDetail = $this->dao->getProductAttr(['product_id' => $id, 'type' => $type]);
        /** @var StoreProductAttrValueServices $storeProductAttrValueService */
        $storeProductAttrValueService = app()->make(StoreProductAttrValueServices::class);
        $_values = $storeProductAttrValueService->getProductAttrValue(['product_id' => $id, 'type' => $type]);
        if ($productId == 0) $productId = $id;
        /** @var StoreProductServices $storeProductService */
        $storeProductService = app()->make(StoreProductServices::class);
        if (!$productInfo) {
            $productInfo = $storeProductService->get($productId, ['is_vip']);
        }
        $vip_price = true;
        if (!$storeProductService->vipIsOpen(!!$productInfo['is_vip'])) $vip_price = false;

        $cartNumList = [];
        $activityAttr = [];
        if ($cartNum) {
            /** @var StoreCartServices $storeCartService */
            $storeCartService = app()->make(StoreCartServices::class);
            //真实用户
            if ($uid) {
                $cartNumList = $storeCartService->getUserCartNums(array_column($_values, 'unique'), $id, $uid);
            } else {
                //虚拟用户
                $touristUid = $this->getItem('touristUid');
                if ($touristUid) {
                    $cartNumList = $storeCartService->getUserCartNums(array_column($_values, 'unique'), $id, $touristUid, 'tourist_uid');
                }
            }
        }
        $values = [];
        $field = $type ? 'stock,price' : 'stock';
        $storeProducts = $storeProductAttrValueService->getSkuArray(['product_id' => $productId, 'type' => 0], $field, 'suk');
        foreach ($_values as $value) {
            if ($cartNum) {
                $value['cart_num'] = $uid || $touristUid ? ($cartNumList[$value['unique']] ?? 0) : 0;
            }
            if (!$vip_price) $value['vip_price'] = 0;
            $value['product_stock'] = $storeProducts[$value['suk']]['stock'] ?? 0;
            if ($discount != -1) $value['price'] = bcmul((string)$value['price'], (string)bcdiv((string)$discount, '100', 2), 2);
            if ($type) {
                $value['product_price'] = $storeProducts[$value['suk']]['price'] ?? 0;
                $attrs = explode(',', $value['suk']);
                $count = count($attrs);
                for ($i = 0; $i < $count; $i++) {
                    $activityAttr[$i][] = $attrs[$i];
                }
            }
            $values[$value['suk']] = $value;
        }
        foreach ($attrDetail as $k => $v) {
            $attr = $v['attr_values'];
            //活动商品只展示参与活动sku
            if ($type && $activityAttr && $a = array_merge(array_intersect($v['attr_values'], $activityAttr[$k]))) {
                $attrDetail[$k]['attr_values'] = $a;
                $attr = $a;
            }
            foreach ($attr as $kk => $vv) {
                $attrDetail[$k]['attr_value'][$kk]['attr'] = $vv;
                $attrDetail[$k]['attr_value'][$kk]['check'] = false;
            }
        }
        return [$attrDetail, $values];
    }

    /**
     * 删除一条数据
     * @param int $id
     * @param int $type
     */
    public function del(int $id, int $type)
    {
        $this->dao->del($id, $type);
    }


    /**
     * 设置规格
     * @param array $data
     * @param int $id
     * @param int $type
     * @return bool
     * @throws \Exception
     */
    public function setAttr(array $data, int $id, int $type)
    {
        if ($data) {
            $this->dao->del($id, $type);
            $res = $this->dao->saveAll($data);
            if (!$res) throw new AdminException('规格保存失败');
        }
        return true;
    }
}
