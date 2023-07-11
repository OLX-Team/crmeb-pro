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

namespace app\controller\admin\v1\work;


use app\controller\admin\AuthController;
use app\Request;
use think\facade\App;
use app\services\work\WorkChannelCategoryServices;

/**
 * 渠道二维码分类
 * Class ClientCate
 * @package app\controller\admin\v1\work
 */
class ChannelCate extends AuthController
{

    /**
     * ClientCate constructor.
     * @param App $app
     * @param WorkChannelCategoryServices $services
     */
    public function __construct(App $app, WorkChannelCategoryServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 获取列表
     * @return mixed
     */
    public function index()
    {
        return $this->success($this->services->getCateAll());
    }

    /**
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create()
    {
        return $this->success($this->services->createForm());
    }

    /**
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit($id)
    {
        return $this->success($this->services->updateForm((int)$id));
    }

    /**
     * 保存数据
     * @param Request $request
     * @return mixed
     */
    public function save(Request $request)
    {
        $data = $request->postMore([
            ['name', ''],
            ['sort', 0]
        ]);

        if (!$data['name']) {
            return $this->fail('请输入分类名称');
        }
        if ($this->services->count(['nowName' => $data['name'], 'group' => WorkChannelCategoryServices::TYPE])) {
            return $this->fail('分类名称已存在');
        }

        $data['group'] = WorkChannelCategoryServices::TYPE;

        if ($this->services->save($data)) {
            return $this->success('添加成功');
        } else {
            return $this->fail('添加失败');
        }
    }

    /**
     * 修改分类
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function update(Request $request, $id)
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['sort', 0]
        ]);


        if (!$data['name']) {
            return $this->fail('请输入分类名称');
        }

        if ($this->services->count(['notId' => $id, 'nowName' => $data['name'], 'group' => WorkChannelCategoryServices::TYPE])) {
            return $this->fail('分类名称已存在');
        }

        if ($this->services->update($id, $data)) {
            return $this->success('修改成功');
        } else {
            return $this->fail('修改失败');
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        if ($this->services->delete($id)) {
            return $this->success('删除成功');
        } else {
            return $this->fail('删除失败');
        }
    }
}

