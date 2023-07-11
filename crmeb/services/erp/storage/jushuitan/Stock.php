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
namespace crmeb\services\erp\storage\jushuitan;

use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrValueServices;
use crmeb\exceptions\AdminException;
use crmeb\services\erp\AccessToken;
use crmeb\services\erp\storage\Jushuitan;

class Stock
{
    /**
     * token句柄
     * @var AccessToken
     */
    protected $accessToken;

    /*** @var Jushuitan */
    protected $jushuitan;

    /**
     * @param AccessToken $accessToken
     * @param Jushuitan $jushuitan
     */
    public function __construct(AccessToken $accessToken, Jushuitan $jushuitan)
    {
        $this->accessToken = $accessToken;
        $this->jushuitan = $jushuitan;
    }

    /**
     * 同步商品库存
     * @param array $ids
     * @return void
     * @throws \Exception
     */
    public function syncStock(string $ids = '')
    {
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);

        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);

        //查询ids下的所有规格对应的sku
        $ids = array_unique(array_map('intval', explode(',', $ids)));
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
            $skuMap = $skuMap + $this->getSkuStockByCode($code);
        }

        // 拼装规格数据
        if (!empty($skuMap)) {
            foreach ($skuMap as $key => $item) {
                if ($id = array_search($key, $list)) {
                    $skuData[] = ['id' => $id, 'stock' => $item, 'sum_stock' => $item];
                }
            }
        }

        // 同步库存 TODO:待添加至队列
        $storeProductServices->transaction(function () use ($ids, $skuData, $skuMap, $storeProductAttrValueServices, $storeProductServices) {
            // 同步规格库存
            $storeProductAttrValueServices->saveAll($skuData);
            // 同步商品库存
            $productData = $storeProductAttrValueServices->getProductStockByValues($ids);
            $storeProductServices->saveAll($productData);

            //同步门店库存
            foreach ($skuMap as $item) {

            }
        });

        return true;
    }

    /**
     * 库存查询
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function inventoryQuery(string $codeStr): array
    {
        $url = $this->accessToken->getApiUrl("/open/inventory/query");

        //业务参数
        $biz = [];

        $biz["sku_ids"] = $codeStr;

        //拼装请求参数
        $params = $this->getParams($biz);

        //请求平台接口
        $request = $this->postRequest($url, $params);
        return $request["data"];
    }

    /**
     * 获取erp库存
     * @param array $code
     * @return array
     * @throws \Exception
     */
    public function getSkuStockByCode(array $code = []): array
    {
        $skuMap = [];
        $codeStr = implode(',', $code);
        $result = $this->inventoryQuery($codeStr);
        if (!empty($result['inventorys'])) {
            foreach ($result['inventorys'] as $inventory) {
                $skuMap[$inventory['sku_id']] = $inventory['qty'];
            }
        }
        return $skuMap;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->jushuitan, $name], $arguments);
    }

}