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
use app\services\system\SystemMenusServices;
use app\services\system\SystemRoleServices;
use think\facade\App;

/**
 * 菜单权限
 * Class SystemMenus
 * @package app\controller\store\system
 */
class SystemMenus extends AuthController
{
    /**
     * SystemMenus constructor.
     * @param App $app
     * @param SystemMenusServices $services
     */
    public function __construct(App $app, SystemMenusServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
        $this->request->filter(['addslashes', 'trim']);
    }

    /**
     * 菜单展示列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['is_show', ''],
            ['keyword', ''],
        ]);
        $where['type'] = 2;
//        $where['auth_type'] = 1;
        return app('json')->success($this->services->getList($where));
    }

    /**
     * 获取子权限
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sonMenusList($role_id = 0, $id = 0)
    {
        if (!$id) {
            app('json')->fail('请选择上级菜单');
        }
        $rules = [];
        if ($role_id) {
            /** @var SystemRoleServices $systemRoleServices */
            $systemRoleServices = app()->make(SystemRoleServices::class);
            $role = $systemRoleServices->get((int)$role_id);
            $rules = $role && $role['rules'] ? explode(',', $role['rules']) : [];
        }
        $where['type'] = 2;
        $where['auth_type'] = 2;
        $where['pid'] = $id;
        $menus = $this->services->getList($where);
        $checked_rules = [];
        foreach ($menus as $item) {
            if ($rules && in_array($item['id'] ?? 0, $rules)) {
                $checked_rules [] = $item['id'];
            }
        }
        return app('json')->success(compact('menus', 'checked_rules'));
    }
}
