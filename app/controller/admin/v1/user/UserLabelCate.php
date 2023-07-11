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
declare (strict_types=1);

namespace app\controller\admin\v1\user;

use app\controller\admin\AuthController;
use app\services\user\label\UserLabelCateServices;
use app\services\user\label\UserLabelServices;
use app\validate\admin\user\UserLabeCateValidata;
use think\facade\App;
use app\Request;

/**
 * Class UserLabelCate
 * @package app\controller\admin\v1\user
 */
class UserLabelCate extends AuthController
{
    /**
     * UserLabelCate constructor.
     * @param App $app
     * @param UserLabelCateServices $services
     */
    public function __construct(App $app, UserLabelCateServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
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
        $where['type'] = 1;
        $where['group'] = 0;
        return $this->success($this->services->getLabelList($where));
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

        $this->validate($data, UserLabeCateValidata::class);

        if ($this->services->count(['name' => $data['name']])) {
            return $this->fail('分类已经存在，请勿重复添加');
        }
        $data['group'] = 0;
        $data['add_time'] = time();
        if ($this->services->save($data)) {
            $this->services->deleteCateCache();
            return $this->success('保存分类成功');
        } else {
            return $this->fail('保存分类失败');
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
            return $this->fail('缺少标签分类id');
        }
        $info = $this->services->get($id);
        if (!$info) {
            return $this->fail('获取标签分类失败');
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
            ['sort', 0],
        ]);

        $this->validate($data, UserLabeCateValidata::class);

        if ($this->services->update($id, $data)) {
            $this->services->deleteCateCache();
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
        /** @var UserLabelServices $userLable */
        $userLable = app()->make(UserLabelServices::class);
        if ($userLable->count(['label_cate' => $id])) {
            return $this->fail('该分类下有标签数据');
        }
        if ($info->delete()) {
            $this->services->deleteCateCache();
            return $this->success('删除成功');
        } else {
            return $this->fail('删除失败');
        }
    }

    /**
     * 获取用户标签分类全部
     * @return mixed
     */
    public function getAll()
    {
        return $this->success($this->services->getLabelCateAll());
    }
}
