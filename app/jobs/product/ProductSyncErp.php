<?php

namespace app\jobs\product;

use app\jobs\store\SynchStocksJob;
use app\services\product\branch\StoreBranchProductAttrValueServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\store\SystemStoreServices;
use crmeb\basic\BaseJobs;
use crmeb\exceptions\AdminException;
use crmeb\services\erp\Erp as erpServices;
use crmeb\traits\QueueTrait;
use think\facade\Log;

class ProductSyncErp extends BaseJobs
{
    use QueueTrait;

    /**
     * 同步商品到erp
     * @param $id
     * @return mixed
     */
    public function upProductToErp($id)
    {
        try {
            /** @var StoreProductServices $productServices */
            $productServices = app()->make(StoreProductServices::class);
            // 获取商品信息
            $productInfo = $productServices->getInfo($id)['productInfo'];
            $data = [];
            foreach ($productInfo['attrs'] as $item) {
                if ($item['pic'] && strstr($item['pic'], 'http') === false) {
                    $siteUrl = sys_config('site_url');
                    $item['pic'] = $siteUrl . $item['pic'];
                }

                $data[] = [
                    'i_id' => $productInfo['code'],
                    'sku_id' => $item['code'],
                    'name' => $productInfo['store_name'],
                    'properties_value' => str_replace(',', ' ', $item['values']),
                    's_price' => $item['price'],
                    'pic' => $item['pic'],
                    'c_price' => $item['cost'],
                    'market_price' => $item['ot_price'],
                ];
            }

            (new erpServices())->serviceDriver('product')->updateProduct($data);
        } catch (\Exception $e) {
            Log::error('商品上传失败, 原因: ' . $e->getMessage());
        }
        return true;
    }

    /**
     * 上传店铺商品
     * @param $id
     * @param $shop
     * @return bool
     */
    public function upBranchProductToErp($id, $shop)
    {
        try {
            /** @var StoreProductServices $productServices */
            $productServices = app()->make(StoreProductServices::class);
            // 获取商品信息
            $productInfo = $productServices->getInfo($id)['productInfo'];
            $data = [];
            foreach ($productInfo['attrs'] as $item) {
                $data[] = [
                    'i_id' => $productInfo['code'],
                    'sku_id' => $item['code'],
                    'shop_i_id' => $shop['erp_shop_id'] . $productInfo['code'],
                    'shop_sku_id' => $shop['erp_shop_id'] . $item['code'],
                    'name' => $productInfo['store_name'],
                    'properties_value' => str_replace(',', ' ', $item['values']),
                    'shop_id' => $shop['erp_shop_id'],
                ];
            }

            (new erpServices())->serviceDriver('product')->updateShopProduct($data);
        } catch (\Exception $e) {
            Log::error('店铺商品上传失败, 原因: ' . $e->getMessage());
        }
        return true;
    }

    /**
     * 添加商品同步到门店中
     * @param $id
     * @param $shop
     */
    public function productToBranch($id, $shop)
    {
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        // 获取商品信息
        $productInfo = $productServices->getInfo($id)['productInfo'];
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $skuArray = $storeProductAttrValueServices->getSkuArray(['product_id' => $id, 'type' => 0], 'unique', 'code');
        $data = [
            'product_id' => $id,
            'image' => $productInfo['image'],
            'store_name' => $productInfo['store_name'],
            'store_info' => $productInfo['store_info'],
            'keyword' => $productInfo['keyword'],
            'bar_code' => $productInfo['bar_code'],
            'cate_id' => $productInfo['cate_id'],
            'store_id' => $shop['id'],
            'sales' => 0,
            'stock' => 0,
            'sort' => 0,
            'label_id' => $productInfo['label_id'],
            'is_show' => 0,
            'add_time' => time(),
            'is_del' => 0,
            'code' => $productInfo['code'],
        ];
        $attrs = [];
        foreach ($productInfo['attrs'] as $item) {
            if (empty($item['code']) || !array_key_exists($item['code'], $skuArray)) {
                continue;
            }
            $attrs[] = [
                'product_id' => $id,
                'store_id' => $shop['id'],
                'unique' => $skuArray[$item['code']],
                'sales' => 0,
                'stock' => 0,
                'type' => 0,
                'bar_code' => $item['bar_code'],
                'code' => $item['code'],
            ];
        }

        /** @var StoreBranchProductAttrValueServices $branchProductAttrServices */
        $branchProductAttrServices = app()->make(StoreBranchProductAttrValueServices::class);

        $branchProductAttrServices->transaction(function () use ($id, $data, $attrs, $shop, $branchProductAttrServices) {

            /** @var StoreBranchProductServices $branchProductServices */
            $branchProductServices = app()->make(StoreBranchProductServices::class);

            // 判断门店是否有商品
            $branchProduct = $branchProductServices->getOne(['product_id' => $id, 'store_id' => $shop['id']]);
            if (empty($branchProduct)) {
                $branchProductServices->save($data);
                $branchProductAttrServices->saveAll($attrs);
            } else {
                $branchProductAttr = $branchProductAttrServices->getColumn(['product_id' => $id, 'store_id' => $shop['id']], '*', 'code');
                if (!empty($branchProductAttr)) {
                    foreach ($attrs as $key => $attr) {
                        if (isset($branchProductAttr[$attr['code']])) {
                            unset($attrs[$key]);
                        }
                    }
                    if (!empty($attrs)) {
                        $branchProductAttrServices->saveAll($attrs);
                    }
                }
            }
        });

        return true;
    }

    /**
     * 同步商品
     * @param $spuArr
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function productFromErp($spuArr)
    {
        $result = (new erpServices())->serviceDriver('product')->syncProduct([$spuArr]);
        $productList = $result['datas'];
        $productInfo = [];
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        /** @var StoreProductAttrServices $productAttrServices */
        $productAttrServices = app()->make(StoreProductAttrServices::class);

        /** @var SystemStoreServices $systemStoreServices */
        $systemStoreServices = app()->make(SystemStoreServices::class);
        $systemStoreList = $systemStoreServices->getErpStore([['erp_shop_id', '>', 0]]);
        foreach ($productList as $item) {
            $productInfo = [
                'image' => $item['pic'],
                'slider_image' => json_encode([$item['pic']]),
                'store_name' => $item['name'],
                'store_info' => $item['name'],
                'cate_id' => 0,
                'price' => $item['s_price'],
                'ot_price' => $item['market_price'],
                'delivery_type' => '1,2,3',
                'freight' => 1,
                'is_show' => 0,
                'add_time' => time(),
                'cost' => $item['c_price'],
                'ficti' => 0,
                'spec_type' => 1,
                'code' => $item['i_id'],
            ];
            $detail = $details = $value = [];
            foreach ($item['skus'] as $items) {
                $detail[] = $items['properties_value'];
                $details[] = [
                    'name' => $items['properties_value'],
                    'select' => false
                ];
                $value[] = [
                    'bar_code' => '',
                    'brokerage' => 0,
                    'brokerage_two' => 0,
                    'code' => $items['sku_id'],
                    'cost' => $items['cost_price'],
                    'detail' => ['规格' => $items['properties_value']],
                    'ot_price' => $items['market_price'],
                    'pic' => $items['pic'],
                    'price' => $items['sale_price'],
                    'select' => true,
                    'value1' => $items['properties_value'],
                    'values' => $items['properties_value'],
                    'vip_price' => 0,
                    'volume' => 0,
                    'weight' => 0,
                    'stock' => 0,
                ];
            }
            $pid = $productServices->value(['code' => $item['i_id']], 'id');
            if (!$pid) {
                $pid = $productServices->ErpProductSave($productInfo);
            }
            $attr = [[
                'value' => '规格',
                'detail' => $detail,
                'details' => $details,
            ]];
            $skuList = $productAttrServices->validateProductAttr($attr, $value, $pid);
            $productAttrServices->saveProductAttr($skuList, $pid);

            // 同步商品至erp门店
            if (!empty($systemStoreList)) {
                foreach ($systemStoreList as $store) {
                    ProductSyncErp::dispatchDo('productToBranch', [$pid, $store]);
                }
            }
        }
        return true;
    }

    /**
     * 同步商品库存
     * @param array $ids
     * @return bool
     */
    public function stockFromErp(array $ids)
    {
        try {
            /** @var StoreProductServices $storeProductServices */
            $storeProductServices = app()->make(StoreProductServices::class);

            /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
            $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);

            //查询ids下的所有规格对应的sku
            $list = $storeProductAttrValueServices->getSkuArray(['product_id' => $ids, 'type' => 0], 'code', 'id');

            $values = array_filter(array_values($list));
            if (empty($values)) {
                throw new AdminException('没有符合同步库存的商品');
            }

            $skuData = $skuMap = [];

            $basic = 20; // 单次查询数量最多20
            $num = count($values);
            $rate = ceil($num / $basic);
            for ($i = 0; $i < $rate; $i++) {
                $code = array_slice($values, $i * $basic, $basic);
                $codeStr = implode(',', $code);
                $result = (new erpServices())->serviceDriver('product')->syncStock($codeStr);
                if (!empty($result['inventorys'])) {
                    foreach ($result['inventorys'] as $inventory) {
                        $skuMap[$inventory['sku_id']] = $inventory['qty'] - $inventory['order_lock'];
                    }
                }
            }

            // 拼装规格数据
            if (!empty($skuMap)) {
                foreach ($skuMap as $key => $item) {
                    if ($id = array_search($key, $list)) {
                        $skuData[] = ['id' => $id, 'stock' => $item, 'sum_stock' => $item];
                    }
                }
            }

            // 同步库存
            $storeProductServices->transaction(function () use ($ids, $skuData, $storeProductAttrValueServices, $storeProductServices) {
                // 同步规格库存
                $storeProductAttrValueServices->saveAll($skuData);
                // 同步商品库存
                $productData = $storeProductAttrValueServices->getProductStockByValues($ids);
                $storeProductServices->saveAll($productData);
            });

            /** @var SystemStoreServices $systemStoreServices */
            $systemStoreServices = app()->make(SystemStoreServices::class);
            $systemStoreList = $systemStoreServices->getErpStore([['erp_shop_id', '>', 0]]);

            // 同步门店商品库存
            if (!empty($systemStoreList)) {
                foreach ($systemStoreList as $store) {
                    ProductSyncErp::dispatchDo('syncBranchProductStock', [$ids[0], $skuMap, $store['id']]);
                }
            }
        } catch (\Exception $e) {
            Log::error('库存获取失败, 原因: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * 同步门店商品库存
     * @param int $productId
     * @param array $data
     * @param int $storeId
     */
    public function syncBranchProductStock(int $productId, array $data, int $storeId)
    {
        /** @var StoreBranchProductAttrValueServices $branchProductAttrServices */
        $branchProductAttrServices = app()->make(StoreBranchProductAttrValueServices::class);
        $branchProductAttrServices->transaction(function () use ($productId, $storeId, $data, $branchProductAttrServices) {
            $list = $branchProductAttrServices->getColumn(['store_id' => $storeId, 'product_id' => $productId, 'code' => array_keys($data)], '*', 'id');
            if (!empty($list)) {
                foreach ($list as $item) {
                    $branchProductAttrServices->update($item['id'], ['stock' => $data[$item['code']]]);
                }
            }

            $stock = (int)$branchProductAttrServices->sum(['product_id' => $productId, 'store_id' => $storeId], 'stock');

            /** @var StoreBranchProductServices $branchProductServices */
            $branchProductServices = app()->make(StoreBranchProductServices::class);
            $branchProductId = $branchProductServices->value(['product_id' => $productId, 'store_id' => $storeId], 'id');
            if ($branchProductId > 0) {
                $branchProductServices->update($branchProductId, ['stock' => $stock]);
            }
        });
        return true;
    }

    /**
     * 同步商品至新增门店
     * @param int $shopId
     */
    public function syncProductToBranch(int $shopId)
    {
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        $list = $productServices->getColumn(['is_del' => 0, 'is_show' => 1], 'id', 'id');

        if (!empty($list)) {
            foreach ($list as $item) {
                ProductSyncErp::dispatchDo('productToBranch', [$item, ['id' => $shopId]]);
            }
        }
        return true;
    }

    /**
     * 更新商品库存
     * @param array $list
     * @return bool
     */
    public function updatePlatformStock(array $list): bool
    {
        try {
            /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
            $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);

            $data = array_column($list, 'qty', 'sku_id');
            $shopData = array_keys(array_column($list, 'shop_id', 'shop_id'));
            $erpShopId = $shopData[0] ?? 0;
            if ($erpShopId < 1) {
                return true;
            }

            // 更新平台商品库存
            $defaultShopId = (int)sys_config('jst_default_shopid');
            if ($defaultShopId == $erpShopId) {
                $this->updateStoreProductValueStock($data);
            }

            $attrs = $storeProductAttrValueServices->getColumn(['code' => array_keys($data)], 'id, product_id, suk, stock, sum_stock', 'code');
            $productIds = array_unique(array_column($attrs, 'product_id'));

            /** @var SystemStoreServices $systemStoreServices */
            $systemStoreServices = app()->make(SystemStoreServices::class);
            $systemStoreList = $systemStoreServices->getErpStore([['erp_shop_id', '=', $erpShopId]]);

            // 同步门店商品规格库存
            foreach ($systemStoreList as $store) {
                ProductSyncErp::dispatchDo('updateStoreAttrStockByCode', [$data, $store['id']]);
            }

            // 同步门店商品库存
            foreach ($systemStoreList as $store) {
                ProductSyncErp::dispatchDo('updateStoreProductStock', [$productIds, $store['id']]);
            }
        } catch (\Exception $e) {
            Log::error('更新门店库存失败, 原因: ' . $e->getMessage());
        }
        return true;
    }

    /**
     * 更新门店规格库存
     * @param array $data
     * @param int $storeId
     * @return bool
     */
    public function updateStoreAttrStockByCode(array $data, int $storeId): bool
    {
        try {
            /** @var StoreBranchProductAttrValueServices $branchProductAttrServices */
            $branchProductAttrServices = app()->make(StoreBranchProductAttrValueServices::class);
            $list = $branchProductAttrServices->getColumn(['store_id' => $storeId, 'code' => array_keys($data)], 'code', 'id');
            $skuData = [];

            // 同步规格库存
            foreach ($data as $key => $item) {
                if ($id = array_search($key, $list)) {
                    $skuData[] = ['id' => $id, 'stock' => $item];
                }
            }
            // 同步规格库存
            $branchProductAttrServices->saveAll($skuData);
        } catch (\Exception $e) {
            Log::error('门店: ' . $storeId . ' 规格库存更新失败, 原因: ' . $e->getMessage());
        }
        return true;
    }

    /**
     * 更新门店商品库存
     * @param array $productIds
     * @param int $storeId
     * @return bool
     */
    public function updateStoreProductStock(array $productIds, int $storeId): bool
    {
        try {
            /** @var StoreBranchProductAttrValueServices $branchProductAttrServices */
            $branchProductAttrServices = app()->make(StoreBranchProductAttrValueServices::class);

            /** @var StoreBranchProductServices $branchProductServices */
            $branchProductServices = app()->make(StoreBranchProductServices::class);

            $productData = $branchProductAttrServices->getProductStockByValues($productIds, $storeId);
            foreach ($productData as $product) {
                $branchProductServices->updateOnline(['product_id' => $product['product_id'], 'store_id' => $storeId], ['stock' => $product['stock']]);
            }
        } catch (\Exception $e) {
            Log::error('门店: ' . $storeId . ' 商品库存更新失败, 原因: ' . $e->getMessage());
        }
        return true;
    }

    /**
     * 更新平台商品库存
     * @param array $data
     * @return bool
     */
    public function updateStoreProductValueStock(array $data): bool
    {
        try {
            /** @var StoreProductServices $storeProductServices */
            $storeProductServices = app()->make(StoreProductServices::class);

            /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
            $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);

            $skuList = $storeProductAttrValueServices->getSkuArray(['code' => array_keys($data), 'type' => 0], 'id, product_id,code', 'id');
            if (empty($skuList)) {
                throw new AdminException('没有符合同步库存的商品');
            }

            $ids = array_unique(array_column($skuList, 'product_id'));

            $skuData = [];
            foreach ($skuList as $key => $sku) {
                if (array_key_exists($sku['code'], $data)) {
                    $skuData[] = ['id' => $key, 'stock' => $data[$sku['code']]];
                }
            }

            if (empty($skuData)) {
                return true;
            }

            // 同步库存
            $storeProductServices->transaction(function () use ($ids, $skuData, $storeProductAttrValueServices, $storeProductServices) {
                // 同步规格库存
                $storeProductAttrValueServices->saveAll($skuData);
                // 同步商品库存
                $productData = $storeProductAttrValueServices->getProductStockByValues($ids);
                $storeProductServices->saveAll($productData);
            });
        } catch (\Exception $e) {
            Log::error('平台商品库存更新失败, 原因: ' . $e->getMessage());
        }
        return true;
    }
}