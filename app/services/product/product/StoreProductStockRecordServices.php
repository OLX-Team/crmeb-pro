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

namespace app\services\product\product;


use app\dao\product\product\StoreProductStockRecordDao;
use app\services\BaseServices;
use app\services\product\branch\StoreBranchProductAttrValueServices;
use app\services\product\sku\StoreProductAttrValueServices;

/**
 * 商品库存记录
 * Class StoreProductStockRecordServices
 * @package app\services\product\product
 */
class StoreProductStockRecordServices extends BaseServices
{
    /**
     * StoreProductStockRecordServices constructor.
     * @param StoreProductStockRecordDao $dao
     */
    public function __construct(StoreProductStockRecordDao $dao)
    {
        $this->dao = $dao;
    }


    /**
     * 保存入库、出库记录
     * @param int $id
     * @param array $attrs
     * @param int $type
     * @param int $store_id
     * @return bool
     */
    public function saveRecord(int $id, array $attrs, int $type = 0, int $store_id = 0)
    {
        if (!$attrs) {
            return false;
        }
        try {
            if ($store_id) {
                /** @var StoreBranchProductAttrValueServices $storeBranchProductAttrValueServices */
                $storeBranchProductAttrValueServices = app()->make(StoreBranchProductAttrValueServices::class);
                $attrValues = $storeBranchProductAttrValueServices->getStoreProductAttr($id, $store_id, $type);
            } else {
                /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
                $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
                //原来规格数据
                $attrValues = $storeProductAttrValueServices->getProductAttrValue(['product_id' => $id, 'type' => $type]);
            }
            if ($attrValues) $attrValues = array_combine(array_column($attrValues, 'unique'), $attrValues);
            $time = time();
            $dataAll = $data = [];
            foreach ($attrs as $attr) {
                $data = [
                    'store_id' => $store_id,
                    'product_id' => $id,
                    'unique' => $attr['unique'],
                    'cost_price' => $attrValues[$attr['unique']]['cost'] ?? 0,
                    'add_time' => $time,
                ];
                if (!isset($attrValues[$attr['unique']]) || $attr['stock'] > $attrValues[$attr['unique']]['stock']) {
                    $data['pm'] = 1;
                    $data['number'] = !isset($attrValues[$attr['unique']]) ? $attr['stock'] : bcsub((string)$attr['stock'], (string)$attrValues[$attr['unique']]['stock'], 2);
                } else {
                    $data['pm'] = 0;
                    $data['number'] = !isset($attrValues[$attr['unique']]) ? $attr['stock'] : bcsub((string)$attrValues[$attr['unique']]['stock'], (string)$attr['stock'], 2);
                }
                if ($data['number']) $dataAll[] = $data;
            }
            if ($dataAll) {
                $this->dao->saveAll($dataAll);
            }
        } catch (\Throwable $e) {

        }
        return true;
    }

}
