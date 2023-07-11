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
namespace app\controller\admin\v1\product\specs;

use app\controller\admin\AuthController;
use app\services\product\specs\StoreProductSpecsServices;
use app\services\product\specs\StoreProductSpecsTemplateServices;
use think\facade\App;
use app\Request;

/**
 * 商品参数
 * Class StoreProductSpecs
 * @package app\controller\admin\v1\product\specs
 */
class StoreProductSpecs extends AuthController
{
    /**
     * 参数模版services
     * @var StoreProductSpecsTemplateServices
     */
    protected $templateServices;

    /**
     * StoreProductSpecs constructor.
     * @param App $app
     * @param StoreProductSpecsServices $services
     * @param StoreProductSpecsTemplateServices $templateServices
     */
    public function __construct(App $app, StoreProductSpecsServices $services, StoreProductSpecsTemplateServices $templateServices)
    {
        parent::__construct($app);
        $this->services = $services;
        $this->templateServices = $templateServices;
    }


    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index(Request $request)
    {
        $where = $request->postMore([
            ['name', '']
        ]);
        return $this->success($this->templateServices->getProductSpecsTemplateList($where));
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function allSpecs(Request $request)
    {
        return $this->success($this->templateServices->getCateList(['type' => 1, 'store_id' => 0, 'group' => 3], 0, 0, ['id', 'name'], ['specs' => function($query) {
            $query->where('status', 1);
        }]));
    }

    /**
     * 获取参数模版信息
     * @param $id
     * @return mixed
     */
    public function getInfo($id)
    {
        if (!$id) return $this->fail('缺少参数ID');
        $temp = $this->templateServices->get(['id' => $id, 'group' => 3], ['*'], ['specs']);
        if (!$temp) {
            return $this->fail('参数模版不存在');
        }
        return $this->success($temp->toArray());
    }


    /**
     * 添加、编辑商品参数模版
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function save(Request $request, $id)
    {
        $data = $request->postMore([
            ['name', ''],
            ['specs', []],
            ['sort', ''],
        ]);
        if (!$data['name']) {
            return $this->fail('请输入参数模版名称');
        }
        $specs = $data['specs'];
        unset($data['specs']);
        $temp = $this->templateServices->getOne(['name' => $data['name'], 'group' => 3, 'store_id' => 0]);
        if ($id) {
            if ($temp && $id != $temp['id']) {
                return $this->fail('参数模版已经存在');
            }
            $this->templateServices->transaction(function () use ($id, $data, $specs) {
                if (!$this->templateServices->update($id, $data)) {
                    return $this->success('编辑失败');
                }
                if (!$this->services->updateData($id, $specs)) {
                    return $this->success('编辑失败');
                }
            });
            return $this->success('编辑成功');
        } else {
            if ($temp) {
                return $this->fail('参数模版已经存在');
            }
            $data['type'] = 1;
            $data['store_id'] = 0;
            $data['group'] = 3;
            $data['add_time'] = time();
            $this->templateServices->transaction(function () use ($id, $data, $specs) {
                if (!$res = $this->templateServices->save($data)) {
                    return $this->success('保存失败');
                }
                if (!$this->services->saveData((int)$res->id, $specs)) {
                    return $this->success('保存失败');
                }
            });
            return $this->success('保存成功');
        }
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$id || !($this->templateServices->count(['id' => $id]))) {
            return $this->fail('删除的数据不存在');
        }
        $this->templateServices->delete($id);
        $this->services->delete(['temp_id' => $id]);
        return $this->success('删除成功');
    }
}
