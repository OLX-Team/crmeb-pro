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

namespace app\dao\product\branch;


use app\dao\BaseDao;
use app\model\product\branch\StoreBranchProductAttrValue;

/**
 * Class StoreBranchProductAttrValueDao
 * @package app\dao\product\branch
 */
class StoreBranchProductAttrValueDao extends BaseDao
{


    /**
     * @return string
     */
    protected function setModel(): string
    {
        return StoreBranchProductAttrValue::class;
    }

    /**
     * 获取属性库存
     * @param string $unique
     * @param int $storeId
     * @return int|mixed
     */
    public function uniqueByStock(string $unique, int $storeId)
    {
        return $this->search(['unique' => $unique, 'store_id' => $storeId])->value('stock') ?: 0;
    }

    /**
     * 根据条件获取规格数据列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductAttrValue(array $where)
    {
        return $this->search($where)->order('id asc')->select()->toArray();
    }

    /**
     * 根据规格信息获取商品库存
     * @param array $ids
     * @return array|\think\Model|null
     */
    public function getProductStockByValues(array $productIds, int $storeId)
    {
       try {
           return $this->getModel()->where([['product_id', 'in', $productIds], ['store_id', '=', $storeId], ['type', '=', 0]])
               ->field('`product_id`, SUM(`stock`) AS `stock`')->group("product_id")->select()->toArray();
       } catch (\Exception $e) {
           \think\facade\Log::error([$e->getFile() .'__' . $e->getLine() .'__' . $e->getMessage(),'file' => 'Exception']);
       }
    }

    /**
     * 保存数据
     * @param array $data
     * @return mixed|\think\Collection
     * @throws \Exception
     */
    public function saveAll(array $data)
    {
        return $this->getModel()->saveAll($data);
    }
}
