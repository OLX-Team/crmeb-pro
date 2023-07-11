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
namespace app\controller\admin\v1\setting;

use app\controller\admin\AuthController;
use app\services\other\CityAreaServices;
use think\facade\App;
use crmeb\services\CacheService;


/**
 * 城市数据
 * Class CityArea
 * @package app\controller\admin\v1\setting
 */
class CityArea extends AuthController
{
    /**
     * 构造方法
     * CityArea constructor.
     * @param App $app
     * @param CityAreaServices $services
     */
    public function __construct(App $app, CityAreaServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 城市列表
     * @return string
     * @throws \Exception
     */
    public function index()
    {
        [$parent_id] = $this->request->getMore([
            [['parent_id', 'd'], 0]
        ], true);
        return $this->success($this->services->getCityTreeList((int)$parent_id));
    }

    /**
     * 添加城市
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function add()
    {
        [$parentId] = $this->request->getMore([
            [['parent_id', 'd'], 0]
        ], true);
        return $this->success($this->services->createCityForm((int)$parentId));
    }

    /**
     * 保存
     */
    public function save()
    {
        $data = $this->request->postMore([
            [['id', 'd'], 0],
            [['name', 's'], ''],
            [['level', 'd'], 0],
            [['parent_id', 'd'], 0],
        ]);
        if ($data['id'] == 0) {
			if ($data['parent_id']) {
				$parent = $this->services->get($data['parent_id']);
				if ($parent) {
					$data['path'] = $parent['path'] . $parent['id'] .'/';
				}
			} else {
				$data['path'] = '/';
			}
            unset($data['id']);
            $data['level'] = $data['level'] + 1;
            $data['type'] = $this->services->type[$data['level']] ?? '';
            $data['create_time'] = date('Y-m-d H:i:s');
            $this->services->save($data);
            $this->services->bcInc($data['parent_id'], 'snum', 1);
            return $this->success('添加城市成功!');
        } else {
            unset($data['level']);
            unset($data['parent_id']);
            $this->services->update($data['id'], $data);
            return $this->success('修改城市成功!');
        }
    }

    /**
     * 修改城市
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit()
    {
        [$id] = $this->request->getMore([
            [['id', 'd'], 0]
        ], true);
        return $this->success($this->services->updateCityForm((int)$id));
    }

    /**
     * 删除城市
     * @throws \Exception
     */
    public function delete()
    {
        [$id] = $this->request->getMore([
            [['city_id', 'd'], 0]
        ], true);
        $city = $this->services->get((int)$id);
        if ($city) {
            $this->services->deleteCity($id);
            if ($city['parent_id']) $this->services->bcDec($city['parent_id'], 'snum', 1);
        }
        return $this->success('删除成功!');
    }

    /**
     * 清除城市缓存
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function clean_cache()
    {
        CacheService::delete('CITY_LIST');
        return $this->success('清除成功!');
    }
}
