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
namespace app\controller\store\system;


use think\facade\App;
use app\controller\store\AuthController;
use app\services\store\SystemStoreStaffServices;


/**
 * Class StoreAdmin
 * @package app\controller\store\system
 */
class StoreAdmin extends AuthController
{

    /**
     * SystemRole constructor.
     * @param App $app
     * @param SystemStoreStaffServices $services
     */
    public function __construct(App $app, SystemStoreStaffServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }


    /**
     * 显示管理员资源列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['name', ''],
            ['roles', ''],
            ['is_del', 0],
            ['status', '']
        ]);
        $where['level'] = $this->storeStaffInfo['level'] + 1;
        $where['store_id'] = $this->storeId;
        $where['is_admin'] = 1;
        return app('json')->success($this->services->getStoreAdminList($where));
    }

    /**
     * 创建表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create()
    {
        return app('json')->success($this->services->createStoreAdminForm((int)$this->storeId, $this->storeStaffInfo['level'] + 1));
    }

    /**
     * 保存管理员
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['account', ''],
            ['avatar', ''],
            ['phone', ''],
            ['conf_pwd', ''],
            ['pwd', ''],
            ['staff_name', ''],
            ['roles', []],
            ['status', 0],
        ]);

        $this->validate($data, \app\validate\store\StoreAdminValidata::class);

        $data['level'] = $this->storeStaffInfo['level'] + 1;
        if ($data['conf_pwd'] != $data['pwd']) {
            return app('json')->fail('两次输入的密码不相同');
        }
        if (!check_phone($data['phone'])) {
            return app('json')->fail('请输入正确的手机号');
        }
        if ($this->services->count(['store_id' => $this->storeId, 'account' => $data['account'], 'is_del' => 0])) {
            return app('json')->fail('该账号已经存在');
        }
        if ($this->services->count(['store_id' => $this->storeId, 'phone' => $data['phone'], 'is_del' => 0])) {
            return app('json')->fail('该手机号已经存在');
        }
        $data['store_id'] = $this->storeId;
        $data['is_admin'] = 1;
		$data['is_cashier'] = 1;
        unset($data['conf_pwd']);

        $data['pwd'] = $this->services->passwordHash($data['pwd']);
        $data['add_time'] = time();
        $data['roles'] = implode(',', $data['roles']);
        if ($this->services->save($data)) {
            return app('json')->success('添加成功');
        } else {
            app('json')->fail('添加失败');
        }

    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if (!$id) {
            return $this->fail('管理员信息读取失败');
        }
        return app('json')->success($this->services->updateStoreAdminForm((int)$id, $this->storeStaffInfo['level'] + 1));
    }

    /**
     * 修改管理员信息
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['account', ''],
            ['avatar', ''],
            ['phone', ''],
            ['conf_pwd', ''],
            ['pwd', ''],
            ['staff_name', ''],
            ['roles', []],
            ['status', 0],
        ]);

        $this->validate($data, \app\validate\store\StoreAdminValidata::class, 'update');
        if (!check_phone($data['phone'])) {
            return app('json')->fail('请输入正确的手机号');
        }
        $storeAdmin = $this->services->get(['store_id' => $this->storeId, 'account' => $data['account'], 'is_del' => 0]);
        if ($storeAdmin && $storeAdmin['id'] != $id) {
            return app('json')->fail('该账号已经存在');
        }
        $storeAdmin = $this->services->get(['store_id' => $this->storeId, 'phone' => $data['phone'], 'is_del' => 0]);
        if ($storeAdmin && $storeAdmin['id'] != $id) {
            return app('json')->fail('该手机号已经存在');
        }
        if ($data['pwd']) {
            if (!$data['conf_pwd']) {
                return $this->fail('请输入确认密码');
            }
            if ($data['pwd'] != $data['conf_pwd']) {
                return $this->fail('两次输入的密码不一致');
            }
            $data['pwd'] = $this->services->passwordHash($data['pwd']);
        } else {
            unset($data['pwd']);
        }
        unset($data['conf_pwd']);
        if ($this->services->update((int)$id, $data)) {
            return app('json')->success('修改成功');
        } else {
            return $this->fail('修改失败');
        }
    }

    /**
     * 删除管理员
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$id) return $this->fail('删除失败，缺少参数');
        if ($this->services->update((int)$id, ['is_del' => 1, 'status' => 0]))
            return app('json')->success('删除成功！');
        else
            return $this->fail('删除失败');
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function set_status($id, $status)
    {
        $this->services->update((int)$id, ['status' => $status]);
        return app('json')->success($status == 0 ? '关闭成功' : '开启成功');
    }

    /**
     * 获取当前登陆门店管理员的信息
     * @return mixed
     */
    public function info()
    {
        return app('json')->success($this->storeStaffInfo);
    }
}
