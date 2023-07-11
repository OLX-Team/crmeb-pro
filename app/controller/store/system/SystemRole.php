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

use app\controller\store\AuthController;
use app\services\store\SystemStoreStaffServices;
use app\services\system\SystemRoleServices;
use app\services\system\SystemMenusServices;
use think\facade\App;

/**
 * Class SystemRole
 * @package app\controller\store\system
 */
class SystemRole extends AuthController
{
    /**
     * 管理员｜员工
     * @var null
     */
    protected $storeStaffServices = null;

    /**
     * SystemRole constructor.
     * @param App $app
     * @param SystemRoleServices $services
     */
    public function __construct(App $app, SystemRoleServices $services, SystemStoreStaffServices $storeStaffServices)
    {
        parent::__construct($app);
        $this->services = $services;
        $this->storeStaffServices = $storeStaffServices;
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['role_name', ''],
        ]);
        $where['type'] = $this->type;
        $where['store_id'] = $this->storeId;
        $where['level'] = $this->storeStaffInfo['level'] + 1;
        $result = $this->services->getRoleList($where);
        if (isset($result['list']) && $list = $result['list']) {
            foreach ($list as &$item) {
                $item['count'] = $this->storeStaffServices->count(['is_del' => 0, 'roles' => $item['id'], 'is_admin' => 1]);
            }
            $result['list'] = $list;
        }
        return app('json')->success($result);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create(SystemMenusServices $services)
    {
        $menus = $services->getmenus($this->storeStaffInfo['level'] == 0 ? [] : $this->storeStaffInfo['roles']);
        return app('json')->success(compact('menus'));
    }

    /**
     * 保存新建的资源
     *
     * @return \think\Response
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            'role_name',
            ['status', 0],
            ['checked_menus', [], '', 'rules']
        ]);
        if (!$data['role_name']) return app('json')->fail('请输入身份名称');
        if (!is_array($data['rules']) || !count($data['rules']))
            return app('json')->fail('请选择最少一个权限');
        $data['rules'] = implode(',', $data['rules']);
        $data['type'] = $this->type;
        $data['store_id'] = $this->storeId;
        if ($id) {
            $id = (int)$id;
            $role = $this->services->get($id);
            if (!$role) return app('json')->fail('角色不存在!');
            if (!$this->services->update($id, $data)) return app('json')->fail('修改失败!');
            //更改角色下店员状态
            $this->services->setStaffStatus((int)$role['store_id'], $id, $data['status']);
            \crmeb\services\CacheService::clear();
            return app('json')->success('修改成功!');
        } else {
            $data['level'] = $this->storeStaffInfo['level'] + 1;
            if (!$this->services->save($data)) return app('json')->fail('添加身份失败!');
            \crmeb\services\CacheService::clear();
            return app('json')->success('添加身份成功!');
        }
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit(SystemMenusServices $services, $id)
    {
        $role = $this->services->get($id);
        if (!$role) {
            return app('json')->fail('修改的角色不存在');
        }
        $role = $role->toArray();
        if ($role['rules']) {
            $role['rules'] = is_string($role['rules']) ? explode(',', $role['rules']) : $role['rules'];
            foreach ($role['rules'] as &$item) {
                $item = (int)$item;
            }
        }
        /** @var SystemMenusServices $systemMenusServices */
        $systemMenusServices = app()->make(SystemMenusServices::class);
        return app('json')->success(['role' => $role, 'menus' => $systemMenusServices->getList(['type' => 2])]);
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $role = $this->services->get($id);
        if (!$role) {
            return app('json')->fail('没有查到此身份');
        }
		if ($this->storeStaffServices->count(['is_del' => 0, 'roles' => $id, 'is_admin' => 1])){
			return app('json')->fail('该角色存在管理员，请先删除管理员');
		}
        if (!$this->services->delete($id))
            return app('json')->fail('删除失败,请稍候再试!');
        else {
            //更改角色下店员状态
            $this->services->setStaffStatus((int)$role['store_id'], (int)$id, 0);
            \crmeb\services\CacheService::clear();
            return app('json')->success('删除成功!');
        }
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function set_status($id, $status)
    {
        if (!$id) {
            return app('json')->fail('缺少参数');
        }
        $role = $this->services->get($id);
        if (!$role) {
            return app('json')->fail('没有查到此身份');
        }
        $role->status = $status;
        if ($role->save()) {
            //更改角色下店员状态
            $this->services->setStaffStatus((int)$role['store_id'], (int)$id, $status);
            \crmeb\services\CacheService::clear();
            return app('json')->success('修改成功');
        } else {
            return app('json')->fail('修改失败');
        }
    }
}
