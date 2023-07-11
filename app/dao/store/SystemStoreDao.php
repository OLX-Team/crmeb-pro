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
namespace app\dao\store;

use app\dao\BaseDao;
use app\model\store\SystemStore;

/**
 * 门店dao
 * Class SystemStoreDao
 * @package app\dao\system\store
 */
class SystemStoreDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return SystemStore::class;
    }

    /**
     * 经纬度排序计算
     * @param string $latitude
     * @param string $longitude
     * @return string
     */
    public function distance(string $latitude, string $longitude, bool $type = false)
    {
        if ($type) {
            return "(round(6367000 * 2 * asin(sqrt(pow(sin(((latitude * pi()) / 180 - ({$latitude} * pi()) / 180) / 2), 2) + cos(({$latitude} * pi()) / 180) * cos((latitude * pi()) / 180) * pow(sin(((longitude * pi()) / 180 - ({$longitude} * pi()) / 180) / 2), 2)))))";
        } else {
            return "(round(6367000 * 2 * asin(sqrt(pow(sin(((latitude * pi()) / 180 - ({$latitude} * pi()) / 180) / 2), 2) + cos(({$latitude} * pi()) / 180) * cos((latitude * pi()) / 180) * pow(sin(((longitude * pi()) / 180 - ({$longitude} * pi()) / 180) / 2), 2))))) AS distance";
        }
    }

    /**
     * 获取
     * @param array $where
     * @param array $field
     * @param int $page
     * @param int $limit
     * @param string $latitude
     * @param string $longitude
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreList(array $where, array $field, int $page = 0, int $limit = 0, string $latitude = '', string $longitude = '', int $order = 0)
    {
        return $this->search($where)->when(isset($where['ids']) && $where['ids'], function ($query) use ($where) {
			$query->whereIn('id', $where['ids']);
        })->when($longitude && $longitude, function ($query) use ($longitude, $latitude, $order) {
            $query->field(['*', $this->distance($latitude, $longitude)]);
        })->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->when(isset($order), function ($query) use ($order) {
            if ($order == 1) {
                $query->order('distance ASC');
            } else {
                $query->order('id desc');
            }
        })->field($field)->select()->toArray();
    }

    /**
     * 获取有效门店
     * @param array $where
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    public function getValidSerch(array $where = [])
    {
        $validWhere = [
            'is_show' => 1,
            'is_del' => 0,
        ];
        return $this->search($where)->where($validWhere);
    }

    /**
     * 获取最近距离距离内的一个门店
     * @param string $latitude
     * @param string $longitude
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDistanceShortStore(string $latitude = '', string $longitude = '')
    {
        return $this->getValidSerch()->when($longitude && $longitude, function ($query) use ($longitude, $latitude) {
            $query->field(['*', $this->distance($latitude, $longitude)])->order('distance ASC');
        })->order('id desc')->find();
    }

    /**
     * 距离排序符合配送范围门店
     * @param string $latitude
     * @param string $longitude
     * @param string $field
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDistanceShortStoreList(string $latitude = '', string $longitude = '', string $field = '*', int $limit = 0)
    {
        return $this->getValidSerch()->field($field)->when($longitude && $longitude, function ($query) use ($longitude, $latitude, $field) {
            $query->field([$field, $this->distance($latitude, $longitude)])->where('valid_range', 'EXP', '>' . $this->distance($latitude, $longitude, true))->order('distance ASC');
        })->when($limit, function ($query) use ($limit) {
            $query->limit($limit);
        })->order('id desc')->select()->toArray();
    }

    /**
     * 根据地址区、街道信息获取门店列表
     * @param string $addressInfo
     * @param string $field
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreByAddressInfo(string $addressInfo = '', string $field = '*', int $limit = 0)
    {
        return $this->getValidSerch()->field($field)->when($addressInfo, function ($query) use ($addressInfo) {
            $query->whereLike('address', '%' . $addressInfo . '%');
        })->when($limit, function ($query) use ($limit) {
            $query->limit($limit);
        })->order('id desc')->select()->toArray();
    }

    /**
     * 获取门店不分页
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStore(array $where)
    {
        return $this->search($where)->order('add_time DESC')->field(['id', 'name'])->select()->toArray();
    }

    /**
     * 获取ERP店铺
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getErpStore(array $where, array $field = ['id','name','erp_shop_id'])
    {
        return $this->search(['type' => 0])->where($where)->field($field)->select()->toArray();
    }
}
