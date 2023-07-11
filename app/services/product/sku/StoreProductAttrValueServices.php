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


use app\dao\product\sku\StoreProductAttrValueDao;
use app\jobs\product\ProductStockValueTips;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\discounts\StoreDiscountsServices;
use app\services\activity\integral\StoreIntegralServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\BaseServices;
use app\services\product\branch\StoreBranchProductAttrValueServices;
use app\services\product\product\StoreProductStockRecordServices;
use app\webscoket\SocketPush;
use crmeb\exceptions\AdminException;
use app\services\product\product\StoreProductServices;
use crmeb\services\CacheService;
use think\exception\ValidateException;

/**
 * Class StoreProductAttrValueService
 * @package app\services\product\sku
 * @method getProductAttrValue(array $where)
 * @method value(array $where, string $field = '') 获取单个键值的数据
 * @method decStockIncSales(array $where, int $num, string $stock = 'stock', string $sales = 'sales') 减库存加销量
 * @method count(array $where) 获取指定条件下的数量
 * @method storeBranchAttr(array $where) 门店规格
 * @method getProductStockByValues($ids) 根据规格信息获取商品库存
 * @method getAttrByBarCode(string $bar_code) 根据条形码获取一条商品规格信息
 * @method uniqueByField(string $unique, int $type = 0, string $field = '*', array $with = []) 根据unique获取一条规格数据
 */
class StoreProductAttrValueServices extends BaseServices
{
    /**
     * StoreProductAttrValueServices constructor.
     * @param StoreProductAttrValueDao $dao
     */
    public function __construct(StoreProductAttrValueDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取单规格规格
     * @param array $where
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne(array $where)
    {
        return $this->dao->getOne($where);
    }

    /**
     * 根据活动商品unique查看原商品unique
     * @param string $unique
     * @param int $activity_id
     * @param int $type
     * @param array|string[] $field
     * @return array|mixed|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUniqueByActivityUnique(string $unique, int $activity_id, int $type = 1, array $field = ['unique'])
    {
        if ($type == 0) return $unique;
        $attrValue = $this->dao->get(['unique' => $unique, 'product_id' => $activity_id, 'type' => $type], ['id', 'suk', 'product_id']);
        if (!$attrValue) {
            return '';
        }
        switch ($type) {
            case 1://秒杀
                /** @var StoreSeckillServices $activityServices */
                $activityServices = app()->make(StoreSeckillServices::class);
                break;
            case 2://砍价
                /** @var StoreBargainServices $activityServices */
                $activityServices = app()->make(StoreBargainServices::class);
                break;
            case 3://拼团
                /** @var StoreCombinationServices $activityServices */
                $activityServices = app()->make(StoreCombinationServices::class);
                break;
            case 4://积分
                /** @var StoreIntegralServices $activityServices */
                $activityServices = app()->make(StoreIntegralServices::class);
                break;
            case 5://套餐
                /** @var StoreDiscountsServices $activityServices */
                $activityServices = app()->make(StoreDiscountsServices::class);
                break;
            default:
                /** @var StoreProductServices $activityServices */
                $activityServices = app()->make(StoreProductServices::class);
                break;

        }
        $product_id = $activityServices->value(['id' => $activity_id], 'product_id');
        if (!$product_id) {
            return '';
        }
        if (count($field) == 1) {
            return $this->dao->value(['suk' => $attrValue['suk'], 'product_id' => $product_id, 'type' => 0], $field[0] ?? 'unique');
        } else {
            return $this->dao->get(['suk' => $attrValue['suk'], 'product_id' => $product_id, 'type' => 0], $field);
        }

    }

    /**
     * 删除一条数据
     * @param int $id
     * @param int $type
     * @param array $suk
     * @return bool
     */
    public function del(int $id, int $type, array $suk = [])
    {
        return $this->dao->del($id, $type, $suk);
    }

    /**
     * 批量保存
     * @param array $data
     */
    public function saveAll(array $data)
    {
        $res = $this->dao->saveAll($data);
        if (!$res) throw new AdminException('规格保存失败');
        return $res;
    }

    /**
     * 获取sku
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getSkuArray(array $where, string $field = 'unique,bar_code,cost,price,ot_price,stock,image as pic,weight,volume,brokerage,brokerage_two,quota,product_id,code', string $key = 'suk')
    {
        return $this->dao->getColumn($where, $field, $key);
    }

    /**
     * 交易排行榜
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function purchaseRanking()
    {
        $dlist = $this->dao->attrValue();
        /** @var StoreProductServices $proServices */
        $proServices = app()->make(StoreProductServices::class);
        $slist = $proServices->getProductLimit(['is_del' => 0], $limit = 20, 'id as product_id,store_name,sales * price as val');
        $data = array_merge($dlist, $slist);
        $last_names = array_column($data, 'val');
        array_multisort($last_names, SORT_DESC, $data);
        $list = array_splice($data, 0, 20);
        return $list;
    }

    /**
     * 获取商品的属性数量
     * @param $product_id
     * @param $unique
     * @param $type
     * @return int
     */
    public function getAttrvalueCount($product_id, $unique, $type)
    {
        return $this->dao->count(['product_id' => $product_id, 'unique' => $unique, 'type' => $type]);
    }

    /**
     * 获取唯一值下的库存
     * @param string $unique
     * @return int
     */
    public function uniqueByStock(string $unique)
    {
        if (!$unique) return 0;
        return $this->dao->uniqueByStock($unique);
    }

    /**
     * 减销量,加库存
     * @param $productId
     * @param $unique
     * @param $num
     * @param int $type
     * @return mixed
     */
    public function decProductAttrStock($productId, $unique, $num, $type = 0)
    {
        $res = $this->dao->decStockIncSales([
            'product_id' => $productId,
            'unique' => $unique,
            'type' => $type
        ], $num);
        if ($res) {
            $this->workSendStock($productId, $unique, $type);
        }
        return $res;
    }

    /**
     * 减少销量增加库存
     * @param $productId
     * @param $unique
     * @param $num
     * @return bool
     */
    public function incProductAttrStock(int $productId, string $unique, int $num, int $type = 0)
    {
        return $this->dao->incStockDecSales(['unique' => $unique, 'product_id' => $productId, 'type' => $type], $num);
    }

    /**
     * 库存预警消息提醒
     * @param int $productId
     * @param string $unique
     * @param int $type
     */
    public function workSendStock(int $productId, string $unique, int $type)
    {
        ProductStockValueTips::dispatch([$productId, $unique, $type]);
    }

    /**
     * 获取秒杀库存
     * @param int $productId
     * @param string $unique
     * @param bool $isNew
     * @return array|mixed|\think\Model|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSeckillAttrStock(int $productId, string $unique, bool $isNew = false)
    {
        $key = md5('seclkill_attr_stock_' . $productId . '_' . $unique);
        $stock = CacheService::redisHandler()->get($key);
        if (!$stock || $isNew) {
            $stock = $this->dao->getOne(['product_id' => $productId, 'unique' => $unique, 'type' => 1], 'suk,quota');
            if ($stock) {
                CacheService::redisHandler()->set($key, $stock, 60);
            }
        }
        return $stock;
    }

    /**
     * @param $product_id
     * @param string $suk
     * @param string $unique
     * @param bool $is_new
     * @return int|mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getProductAttrStock(int $productId, string $suk = '', string $unique = '', $isNew = false)
    {
        if (!$suk && !$unique) return 0;
        $key = md5('product_attr_stock_' . $productId . '_' . $suk . '_' . $unique);
        $stock = CacheService::redisHandler()->get($key);
        if (!$stock || $isNew) {
            $where = ['product_id' => $productId, 'type' => 0];
            if ($suk) {
                $where['suk'] = $suk;
            }
            if ($unique) {
                $where['unique'] = $unique;
            }
            $stock = $this->dao->value($where, 'stock');
            CacheService::redisHandler()->set($key, $stock, 60);
        }
        return $stock;
    }

    /**
     * 根据商品id获取对应规格库存
     * @param int $productId
     * @param int $type
     * @return float
     */
    public function pidBuStock(int $productId, int $type = 0)
    {
        return $this->dao->pidBuStock($productId, $type);
    }

    /**
     * 更新sum_stock
     * @param array $uniques
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateSumStock(array $uniques)
    {
        /** @var StoreBranchProductAttrValueServices $storeValueService */
        $storeValueService = app()->make(StoreBranchProductAttrValueServices::class);
        $stockSumData = $storeValueService->getProductAttrValueStockSum($uniques ?? []);
        $this->dao->getList(['unique' => $uniques])->map(function ($item) use ($stockSumData) {
            if (isset($stockSumData[$item->unique])) {
                $data['sum_stock'] = $item->stock + $stockSumData[$item->unique];
            } else {
                $data['sum_stock'] = $item->stock;
            }
            $this->dao->update(['product_id' => $item['product_id'], 'unique' => $item['unique'], 'type' => $item['type']], $data);
        });
    }

    /**
     * 批量快速修改商品规格库存
     * @param int $id
     * @param array $data
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveProductAttrsStock(int $id, array $data)
    {
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        $product = $productServices->get($id);
        if (!$product) {
            throw new ValidateException('商品不存在');
        }
        $attrs = $this->dao->getProductAttrValue(['product_id' => $id, 'type' => 0]);
        if ($attrs) $attrs = array_combine(array_column($attrs, 'unique'), $attrs);
        $dataAll = $update = [];
        $stock = 0;
        $time = time();
        foreach ($data as $attr) {
            if (!isset($attrs[$attr['unique']])) continue;
            if ($attr['pm']) {
                $stock = bcadd((string)$stock, (string)$attr['stock'], 0);
                $update['stock'] = bcadd((string)$attrs[$attr['unique']]['stock'], (string)$attr['stock'], 0);
                $update['sum_stock'] = bcadd((string)$attrs[$attr['unique']]['sum_stock'], (string)$attr['stock'], 0);
            } else {
                $stock = bcsub((string)$stock, (string)$attr['stock'], 0);
                $update['stock'] = bcsub((string)$attrs[$attr['unique']]['stock'], (string)$attr['stock'], 0);
                $update['sum_stock'] = bcsub((string)$attrs[$attr['unique']]['sum_stock'], (string)$attr['stock'], 0);
            }
            $update['stock'] = $update['stock'] > 0 ? $update['stock'] : 0;
            $this->dao->update(['id' => $attrs[$attr['unique']]['id']], $update);

            $dataAll[] = [
                'product_id' => $id,
                'unique' => $attr['unique'],
                'cost_price' => $attrs[$attr['unique']]['cost'] ?? 0,
                'number' => $attr['stock'],
                'pm' => $attr['pm'] ? 1 : 0,
                'add_time' => $time,
            ];
        }
        $product_stock = $stock ? bcadd((string)$product['stock'], (string)$stock, 0) : bcsub((string)$product['stock'], (string)$stock, 0);
        $product_stock = $product_stock > 0 ? $product_stock : 0;
        //修改商品库存
        $productServices->update($id, ['stock' => $product_stock]);
        //添加库存记录$product_stock
        if ($dataAll) {
            /** @var StoreProductStockRecordServices $storeProductStockRecordServces */
            $storeProductStockRecordServces = app()->make(StoreProductStockRecordServices::class);
            $storeProductStockRecordServces->saveAll($dataAll);
        }
        return $product_stock;
    }

}
