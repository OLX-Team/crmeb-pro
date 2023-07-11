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

namespace app\services\product\branch;


use app\dao\product\branch\StoreBranchProductAttrValueDao;
use app\services\BaseServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\product\product\StoreProductStockRecordServices;
use app\webscoket\SocketPush;
use crmeb\exceptions\AdminException;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;

/**
 * Class StoreBranchProductAttrValueServices
 * @package app\services\product\branch
 * @method getProductStockByValues(array $productIds, int $storeId) 根据商品信息获取商品库存
 */
class StoreBranchProductAttrValueServices extends BaseServices
{

    use ServicesTrait;

    /**
     * StoreBranchProductAttrValueServices constructor.
     * @param StoreBranchProductAttrValueDao $dao
     */
    public function __construct(StoreBranchProductAttrValueDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param string $unique
     * @param int $storeId
     * @return int|mixed
     */
    public function uniqueByStock(string $unique, int $storeId)
    {
        if (!$unique) return 0;
        return $this->dao->uniqueByStock($unique, $storeId);
    }

    /**
     * 更新
     * @param int $id
     * @param array $data
     * @param int $store_id
     */
    public function updataAll(int $id, array $data, int $store_id)
    {
        /** @var StoreBranchProductServices $productServices */
        $productServices = app()->make(StoreBranchProductServices::class);
        $where = [];
        $where['product_id'] = $id;
        $where['store_id'] = $store_id;
        $where['type'] = 0;

        $this->transaction(function () use ($id, $store_id, $where, $data, $productServices) {
            $attrArr = [];
            $stock = 0;
            $this->dao->delete($where);
            foreach ($data['attrs'] as $key => $item) {
                $attrArr[$key]['product_id'] = $id;
                $attrArr[$key]['store_id'] = $store_id;
                $attrArr[$key]['unique'] = $item['unique'] ?? '';
                $attrArr[$key]['stock'] = intval($item['stock']) ?? 0;
                $attrArr[$key]['bar_code'] = $item['bar_code'] ?? 0;
                $attrArr[$key]['type'] = 0;
                $stock += (int)($item['stock'] ?? 0);
            }
            $res1 = $this->dao->saveAll($attrArr);
            $productServices->saveStoreProduct($id, $store_id, $stock, $data);
            $unique = array_column($data['attrs'], 'unique');
            /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
            $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
            $storeProductAttrValueServices->updateSumStock($unique);
            //记录入出库
            /** @var StoreProductStockRecordServices $storeProductStockRecordServces */
            $storeProductStockRecordServces = app()->make(StoreProductStockRecordServices::class);
            $storeProductStockRecordServces->saveRecord($id, $attrArr, 0, $store_id);
            if (!$res1) {
                throw new AdminException('添加失败！');
            }
        });
    }

    /**
     * 减销量,加库存
     * @param $productId
     * @param $unique
     * @param $num
     * @param int $type
     * @return mixed
     */
    public function incProductAttrStock(int $storeId, int $productId, string $unique, int $num, int $type = 0)
    {
        $res = $this->dao->incStockDecSales([
            'product_id' => $productId,
            'unique' => $unique,
            'type' => $type,
            'store_id' => $storeId
        ], $num);
        return $res;
    }

    /**
     * 减销量,加库存
     * @param $productId
     * @param $unique
     * @param $num
     * @param int $type
     * @return mixed
     */
    public function decProductAttrStock(int $storeId, int $productId, string $unique, int $num, int $type = 0)
    {
        $res = $this->dao->decStockIncSales([
            'product_id' => $productId,
            'unique' => $unique,
            'type' => $type,
            'store_id' => $storeId
        ], $num);
        if ($res) {
            $this->workSendStock($storeId, $productId, $unique, $type);
        }
        return $res;
    }

    /**
     * 库存预警消息提醒
     * @param int $productId
     * @param string $unique
     * @param int $type
     */
    public function workSendStock(int $storeId, int $productId, string $unique, int $type)
    {
        $stock = $this->dao->value([
            'product_id' => $productId,
            'unique' => $unique,
            'type' => $type,
            'store_id' => $storeId
        ], 'stock');
        $store_stock = sys_config('store_stock') ?? 0;//库存预警界限
        if ($store_stock >= $stock) {
            try {
                SocketPush::store()->type('STORE_STOCK')->data(['id' => $productId])->to($storeId)->push();
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * 获取某个门店商品下的库存
     * @param int $storeId
     * @param int $productId
     * @return array
     */
    public function getProductAttrUnique(int $storeId, int $productId)
    {
        return $this->dao->getColumn(['store_id' => $storeId, 'product_id' => $productId], 'stock', 'unique');
    }

    /**
     * 获取某个sku下的商品库存总和
     * @param string $unique
     * @return array
     */
    public function getProductAttrValueStockSum(array $unique)
    {
        return $this->dao->getColumn([['unique', 'in', $unique]], 'sum(stock) as sum_stock', 'unique');
    }

    /**
     * 获取门店商品规格信息
     * @param int $id
     * @param int $store_id
     * @param int $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreProductAttr(int $id, int $store_id, int $type = 0)
    {
        /** @var StoreProductAttrValueServices $productAttrValueServices */
        $productAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $productAttrs = $productAttrValueServices->getProductAttrValue(['product_id' => $id, 'type' => $type]);
        $storeData = $this->dao->getProductAttrValue(['store_id' => $store_id, 'product_id' => $id, 'type' => $type]);
        if ($storeData) $storeData = array_combine(array_column($storeData, 'unique'), $storeData);
        foreach ($productAttrs as &$attr) {
            $attr['stock'] = isset($storeData[$attr['unique']]) ? $storeData[$attr['unique']]['stock'] : 0;
        }
        return $productAttrs;
    }

    /**
     * 批量快速修改商品规格库存
     * @param int $id
     * @param int $store_id
     * @param array $data
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveProductAttrsStock(int $id, int $store_id, array $data)
    {

        $dataAll = $update = [];
        $stock = 0;
        $time = time();
        /** @var StoreBranchProductServices $branchProductServices */
        $branchProductServices = app()->make(StoreBranchProductServices::class);
        $attrs = $this->dao->getProductAttrValue(['store_id' => $store_id, 'product_id' => $id, 'type' => 0]);
        if (!$attrs) {//门店还未编辑商品，生成商品规格
            /** @var StoreProductAttrValueServices $productAttrValueServices */
            $productAttrValueServices = app()->make(StoreProductAttrValueServices::class);
            $productAttrs = $productAttrValueServices->getProductAttrValue(['product_id' => $id, 'type' => 0]);
            $data = array_combine(array_column($data, 'unique'), $data);
            $stock = 0;
            foreach ($productAttrs as $key => $item) {
                $attrs[$key]['product_id'] = $id;
                $attrs[$key]['store_id'] = $store_id;
                $attrs[$key]['unique'] = $item['unique'] ?? '';
                $attrs[$key]['stock'] = $data[$item['unique']]['stock'] ?? 0;
                $attrs[$key]['bar_code'] = $item['bar_code'] ?? 0;
                $attrs[$key]['type'] = 0;
                if (isset($data[$item['unique']]['pm']) && $data[$item['unique']]['pm']) {
                    $stock = bcadd((string)$stock, (string)($data[$item['unique']]['stock']), 0);
                } else {
                    $stock = bcsub((string)$stock, (string)($data[$item['unique']]['stock']), 0);
                }

                if (isset($data[$item['unique']]) && $data[$item['unique']]['stock']) $dataAll[] = [
                    'store_id' => $store_id,
                    'product_id' => $id,
                    'unique' => $item['unique'],
                    'cost_price' => $item['cost'] ?? 0,
                    'number' => isset($data[$item['unique']]['pm']) && $data[$item['unique']]['pm'] ? ($data[$item['stock']]['stock'] ?? '') : 0,
                    'pm' => 1,
                    'add_time' => $time,
                ];
            }
            $this->dao->saveAll($attrs);
        } else {
            $product = $branchProductServices->get(['store_id' => $store_id, 'product_id' => $id]);
            $attrs = array_combine(array_column($attrs, 'unique'), $attrs);
            foreach ($data as $attr) {
                if (!isset($attrs[$attr['unique']]) || !$attr['stock']) continue;
                if ($attr['pm']) {
                    $stock = bcadd((string)$stock, (string)$attr['stock'], 0);
                    $update['stock'] = bcadd((string)$attrs[$attr['unique']]['stock'], (string)$attr['stock'], 0);
                } else {
                    $stock = bcsub((string)$stock, (string)$attr['stock'], 0);
                    $update['stock'] = bcsub((string)$attrs[$attr['unique']]['stock'], (string)$attr['stock'], 0);
                }
                $update['stock'] = $update['stock'] > 0 ? $update['stock'] : 0;
                $this->dao->update(['id' => $attrs[$attr['unique']]['id']], $update);

                $dataAll[] = [
                    'store_id' => $store_id,
                    'product_id' => $id,
                    'unique' => $attr['unique'],
                    'cost_price' => $attrs[$attr['unique']]['cost'] ?? 0,
                    'number' => $attr['stock'],
                    'pm' => $attr['pm'] ? 1 : 0,
                    'add_time' => $time,
                ];
            }
            $stock = $stock ? bcadd((string)($product['stock'] ?? 0), (string)$stock, 0) : bcsub((string)($product['stock'] ?? 0), (string)$stock, 0);
        }
        $stock = $stock > 0 ? $stock : 0;
        //修改、添加商品
        $branchProductServices->saveStoreProduct($id, $store_id, $stock);
        //添加库存记录
        if ($dataAll) {
            /** @var StoreProductStockRecordServices $storeProductStockRecordServces */
            $storeProductStockRecordServces = app()->make(StoreProductStockRecordServices::class);
            $storeProductStockRecordServces->saveAll($dataAll);
        }
        return $stock;
    }
}
