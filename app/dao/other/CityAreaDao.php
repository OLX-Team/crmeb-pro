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

namespace app\dao\other;


use app\dao\BaseDao;
use app\model\other\CityArea;

/**
 * Class CityAreaDao
 * @package app\dao\other
 */
class CityAreaDao extends BaseDao
{

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return CityArea::class;
    }

    public function search(array $where = [])
    {
        return parent::search($where)->when(isset($where['pid']) && $where['pid'] !== '', function ($query) use ($where) {
            $query->where('parent_id', $where['pid']);
        })->when(isset($where['address']) && $where['address'] !== '', function ($query) use ($where) {
            $address = explode('/', trim($where['address'], '/'));
			if (isset($address[0]) && isset($address[1]) && $address[0] == $address[1]) {//直辖市：北京市北京市朝阳区
				array_shift($address);
			}
            $p = array_shift($address);
            if (mb_strlen($p) - 1 === mb_strpos($p, '市')) {
                $p = mb_substr($p, 0, -1);
            } elseif (mb_strlen($p) - 1 === mb_strpos($p, '省')) {
                $p = mb_substr($p, 0, -1);
            } elseif (mb_strlen($p) - 3 === mb_strpos($p, '自治区')) {
                $p = mb_substr($p, 0, -3);
            }
            $pcity = $this->getModel()->where('name', $p)->value('id');
            $path = ['', $pcity];
            $street = $p;
            foreach ($address as $item) {
                $pcity = $this->getModel()->whereLike('path', implode('/', $path) . '/%')->where('name', $item)->value('id');
                if (!$pcity) {
				    break;
				}
				$path[] = $pcity;
				$street = $item;
            }
			array_pop($path);
            $query->whereLike('path', implode('/', $path) . '/%')->where('name', $street);
        });

    }

    /**
     * 搜索某个地址
     * @param array $where
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function searchCity(array $where)
    {
        return $this->search($where)->order('id DESC')->find();
    }

    /**
     * 获取地址
     * @param array $where
     * @param string $field
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCityList(array $where, string $field = '*', array $with = [])
    {
        return $this->getModel()->where($where)->field($field)->with($with)->order('id asc')->select()->toArray();
    }

    /**
     * 删除上级城市和当前城市id
     * @param int $cityId
     * @return bool
     * @throws \Exception
     */
    public function deleteCity(int $cityId)
    {
        return $this->getModel()->where('id', $cityId)->whereOr('parent_id', $cityId)->delete();
    }
}
