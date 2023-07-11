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
namespace app\controller\admin\v1\product;

use app\controller\admin\AuthController;
use app\services\product\product\StoreProductUnitServices;
use think\facade\App;
use app\Request;

/**
 * 商品单位
 * Class StoreProductUnit
 * @package app\controller\admin\v1\product
 */
class StoreProductUnit extends AuthController
{
    /**
     * StoreProductUnit constructor.
     * @param App $app
     * @param StoreProductUnitServices $services
     */
    public function __construct(App $app, StoreProductUnitServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 获取所有商品单位
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllUnit()
    {
        $where = [];
        $where['store_id'] = 0;
        $where['status'] = 1;
        $where['is_del'] = 0;

        return $this->success($this->services->getAllUnitList($where, 'id,name'));
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
        $where['store_id'] = 0;
        $where['status'] = 1;
        $where['is_del'] = 0;
        return $this->success($this->services->getUnitList($where));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        return $this->success($this->services->createForm());
    }

    /**
     * 保存新建的资源
     *
     * @param \app\Request $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data = $request->postMore([
            ['name', ''],
            ['sort', 0]
        ]);

        validate(\app\validate\admin\product\StoreProductUnitValidate::class)->scene('get')->check(['name' => $data['name']]);

        if ($this->services->getCount(['name' => $data['name'], 'is_del' => 0])) {
            return $this->fail('单位已经存在，请勿重复添加');
        }
        $data['add_time'] = time();
        if ($this->services->save($data)) {
            return $this->success('保存成功');
        } else {
            return $this->fail('保存失败');
        }
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        if (!$id) {
            return $this->fail('缺少ID');
        }
        $info = $this->services->get($id);
        if (!$info) {
            return $this->fail('获取商品单位失败');
        }
        return $this->success($info->toArray());
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        return $this->success($this->services->updateForm((int)$id));
    }

    /**
     * 保存更新的资源
     *
     * @param \app\Request $request
     * @param int $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->postMore([
            ['name', ''],
            ['sort', 0]
        ]);
        
        validate(\app\validate\admin\product\StoreProductUnitValidate::class)->scene('get')->check(['name' => $data['name']]);

        $unit = $this->services->getOne(['name' => $data['name'], 'is_del' => 0]);
        if ($unit && $unit['id'] != $id) {
            return $this->fail('单位名称已经存在');
        }
        if ($this->services->update($id, $data)) {
            return $this->success('修改成功');
        } else {
            return $this->fail('修改失败');
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
        if (!$id || !($info = $this->services->get($id))) {
            return $this->fail('删除的数据不存在');
        }
        if ($info && $info['is_del'] == 0) {
            $this->services->update($id, ['is_del' => 1]);
        }
        return $this->success('删除成功');
    }
}
