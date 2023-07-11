<?php


namespace app\services\product\sku;


use app\dao\product\sku\StoreProductVirtualDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;

class StoreProductVirtualServices extends BaseServices
{
    public function __construct(StoreProductVirtualDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 规格中获取卡密列表
     * @param $unique
     * @param $product_id
     * @return array
     */
    public function getArr($unique, $product_id)
    {
        $res = $this->dao->getColumn(['attr_unique' => $unique, 'product_id' => $product_id], 'card_no,card_pwd');
        $data = [];
        foreach ($res as $item) {
            $data[] = ['key' => $item['card_no'], 'value' => $item['card_pwd']];
        }
        return $data;
    }

    /**
     * 获取订单发送卡密列表
     * @param array $where
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderCardList(array $where, int $limit = 1)
    {
        return $this->dao->getList($where, '*', 0, $limit);
    }

    /**
     * 保存商品规格（虚拟卡密信息）
     * @param int $id
     * @param array $valueGroup
     * @param int $store_id
     * @return bool
     */
    public function saveProductVirtual(int $id, array $valueGroup, int $store_id = 0)
    {
        foreach ($valueGroup as &$item) {
            if (isset($item['product_type']) && $item['product_type'] == 1 && isset($item['virtual_list']) && count($item['virtual_list'])) {
                $this->dao->delete(['store_id' => $store_id, 'product_id' => $id, 'attr_unique' => $item['unique'], 'uid' => 0]);
                $data = [];
                foreach ($item['virtual_list'] as &$items) {
                    if (!$this->dao->count(['product_id' => $id, 'store_id' => $store_id, 'card_no' => $items['key'], 'card_pwd' => $items['value']])) {
                        $data = [
                            'product_id' => $id,
                            'attr_unique' => $item['unique'],
                            'card_no' => $items['key'],
                            'card_pwd' => $items['value'],
                            'card_unique' => md5($item['unique'] . ',' . $items['key'] . ',' . $items['value'])
                        ];
                        $this->dao->save($data);
                    }
                }
            }
        }
        return true;
    }
}
