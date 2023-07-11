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

namespace app\dao\product\shipping;


use app\dao\BaseDao;
use app\model\product\shipping\ShippingTemplatesNoDelivery;

/**
 * 不送达
 * Class ShippingTemplatesNoDeliveryDao
 * @package app\dao\shipping
 */
class ShippingTemplatesNoDeliveryDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return ShippingTemplatesNoDelivery::class;
    }

    /**
     * 获取运费模板列表并按照指定字段进行分组
     * @param array $where
     * @param string $group
     * @param string $field
     * @param string $key
     * @return mixed
     */
    public function getShippingGroupArray(array $where, string $group, string $field, string $key)
    {
        return $this->search($where)->group($group)->column($field, $key);
    }

    /**
     * 获取列表
     * @param array $where
     * @param array|string[] $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getShippingList(array $where, array $field = ['*'])
    {
        return $this->search($where)->field($field)->select()->toArray();
    }

    /**
     * 获取运费模板列表
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getShippingArray(array $where, string $field, string $key)
    {
        return $this->search($where)->column($field, $key);
    }

    /**
     * 是否不送达
     * @param $tempId
     * @param $cityid
     * @param string $field
     * @param string $key
     * @return array
     */
    public function isNoDelivery($tempId, $cityid, string $field = 'temp_id', string $key = '')
    {
        return $this->getModel()
            ->when($cityid, function ($query) use ($cityid) {
                if (is_array($cityid)) {
                    $query->whereIn('city_id', $cityid);
                } else {
                    $query->where('city_id', $cityid);
                }
            })->when($tempId, function ($query) use ($tempId) {
                if (is_array($tempId)) {
                    $query->whereIn('temp_id', $tempId);
                } else {
                    $query->where('temp_id', $tempId);
                }
            })->column($field, $key);
    }

}
