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

namespace app\controller\admin\v1\application\wechat;


use app\Request;
use think\facade\App;
use app\controller\admin\AuthController;
use app\services\message\service\StoreServiceSpeechcraftCateServices;

/**
 * Class StoreServiceSpeechcraftCate
 * @package app\controller\admin\v1\application\wechat
 */
class StoreServiceSpeechcraftCate extends AuthController
{

    /**
     * StoreServiceSpeechcraftCate constructor.
     * @param App $app
     * @param StoreServiceSpeechcraftCateServices $services
     */
    public function __construct(App $app, StoreServiceSpeechcraftCateServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 获取列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['name', '']
        ]);
        $where['owner_id'] = 0;
        $where['type'] = 1;
        $where['group'] = 1;
        return $this->success($this->services->getCateList($where));
    }

    /**
     * 获取创建表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create()
    {
        return $this->success($this->services->createForm());
    }

    /**
     * 保存数据
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['sort', 0],
        ]);

        if (!$data['name']) {
            return $this->fail('请输入分类名称');
        }

        if ($this->services->count(['name' => $data['name'], 'group' => 1])) {
            return $this->fail('分类已存在');
        }

        $data['add_time'] = time();
        $data['group'] = 1;

        $this->services->save($data);
        return $this->success('添加成功');
    }

    /**
     * 获取修改表单
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit($id)
    {
        return $this->success($this->services->editForm((int)$id));
    }

    /**
     * 修改保存
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function update(Request $request, $id)
    {
        $data = $request->postMore([
            ['name', ''],
            [['sort', 'd'], 0],
        ]);
        if (!$data['name']) {
            return $this->fail('请输入分类名称');
        }

        $cateInfo = $this->services->get($id);
        if (!$cateInfo) {
            return $this->fail('修改的分类不存在');
        }
        $old = $this->services->getOne(['name' => $data['name'], 'group' => 1]);
        if ($old && $old['id'] != $id) {
            return $this->fail('分类已存在');
        }
        $cateInfo->name = $data['name'];
        $cateInfo->sort = $data['sort'];
        $cateInfo->save();
        return $this->success('修改成功');
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $cateInfo = $this->services->get($id);
        if (!$cateInfo) {
            return $this->fail('删除的分类不存在');
        }
        $cateInfo->delete();
        return $this->success('删除成功');
    }
}
