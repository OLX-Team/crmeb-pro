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

namespace app\services\other;


use app\dao\other\CityAreaDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use crmeb\services\FormBuilder as Form;

/**
 * 城市数据（街道）
 * Class CityAreaServices
 * @package app\services\other
 */
class CityAreaServices extends BaseServices
{

    /**
     * 城市类型
     * @var string[]
     */
    public $type = [
        '1' => 'province',
        '2' => 'city',
        '3' => 'area',
        '4' => 'street'
    ];

    /**
     * CityAreaServices constructor.
     * @param CityAreaDao $dao
     */
    public function __construct(CityAreaDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取某一个城市id相关上级所有ids
     * @param int $id
     * @return array|int[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRelationCityIds(int $id)
    {
        $cityInfo = $this->dao->get($id);
        $ids = [];
        if ($cityInfo) {
            $ids = explode('/', trim($cityInfo['path'], '/'));
        }
        return array_merge([$id], $ids);
    }

    /**
     * @param int $id
     * @param int $expire
     * @return bool|mixed|null
     */
    public function getRelationCityIdsCache(int $id, int $expire = 1800)
    {
        return CacheService::redisHandler('apiCity')->remember('city_ids_' . $id, function () use ($id) {
            $cityInfo = $this->dao->get($id);
            $ids = [];
            if ($cityInfo) {
                $ids = explode('/', trim($cityInfo['path'], '/'));
            }
            return array_merge([$id], $ids);
        }, $expire);
    }


    /**
     * @return bool|mixed|null
     */
    public function getCityTreeList(int $pid = 0)
    {
        $parent_name = '中国';
        if ($pid) {
            $city = $this->dao->get($pid);
            $parent_name = $city ? $city['name'] : '';
        }
        $cityList = $this->dao->getCityList(['parent_id' => $pid], 'id as value,id,name as label,parent_id as pid,level', ['children']);
        foreach ($cityList as &$item) {
            $item['parent_name'] = $parent_name;
            if (isset($item['children']) && $item['children']) {
                $item['children'] = [];
                $item['loading'] = false;
                $item['_loading'] = false;
            } else {
                unset($item['children']);
            }
        }
        return $cityList;
    }

    /**
     * 添加城市数据表单
     * @param int $parentId
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createCityForm(int $parentId)
    {
        $info = [];
        if ($parentId) {
            $info = $this->dao->get($parentId);
        }
        $field[] = Form::hidden('level', $info['level'] ?? 0);
        $field[] = Form::hidden('parent_id', $info['id'] ?? 0);
        $field[] = Form::input('parent_name', '父类名称', $info['name'] ?? '中国')->disabled(true);
        $field[] = Form::input('name', '名称')->required('请填写城市名称');
        return create_form('添加城市', $field, $this->url('/setting/city/save'));
    }

    /**
     * 添加城市数据创建
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function updateCityForm(int $id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new AdminException('需改的数据不存在');
        }
        if ($info['parent_id']) {
            $city = $this->dao->get($info['parent_id']);
            $info['parent_name'] = $city['name'];
        }
        $info = $info->toArray();
        $field[] = Form::hidden('id', $info['id']);
        $field[] = Form::hidden('level', $info['level']);
        $field[] = Form::hidden('parent_id', $info['parent_id']);
        $field[] = Form::input('parent_name', '父类名称', $info['parent_name'] ?? '中国')->disabled(true);
        $field[] = Form::input('name', '名称', $info['name'])->required('请填写城市名称');
        return create_form('修改城市', $field, $this->url('/setting/city/save'));
    }
}
