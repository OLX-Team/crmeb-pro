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
use app\model\product\shipping\ShippingTemplatesFree;

/**
 * 包邮
 * Class ShippingTemplatesFreeDao
 * @package app\dao\product\shipping
 */
class ShippingTemplatesFreeDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return ShippingTemplatesFree::class;
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
     * 是否可以满足包邮
     * @param $tempId
     * @param $cityid
     * @param $number
     * @param $price
     * @param $type
     * @return int
     */
    public function isFree($tempId, $cityid, $number, $price, $type = 0)
    {
        return $this->getModel()->where('temp_id', $tempId)
            ->where('city_id', $cityid)
            ->when($type, function ($query) use ($type, $number) {
                if ($type == 1) {//数量
                    $query->where('number', '<=', $number);
                } else {//重量、体积
                    $query->where('number', '>=', $number);
                }
            })->where('price', '<=', $price)->count();
    }

    /**
     * 是否包邮模版数据列表
     * @param $tempId
     * @param $cityid
     * @param int $price
     * @param string $field
     * @param string $key
     * @return array
     */
    public function isFreeList($tempId, $cityid, $price = 0, string $field = '*', string $key = '')
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
            })->when($price, function ($query) use ($price) {
                $query->where('price', '<=', $price);
            })->column($field, $key);
    }

}
