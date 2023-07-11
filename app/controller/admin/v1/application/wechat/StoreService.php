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

use app\controller\admin\AuthController;
use app\services\kefu\LoginServices;
use app\services\message\service\StoreServiceLogServices;
use app\services\message\service\StoreServiceServices;
use app\services\user\UserServices;
use app\services\user\UserWechatuserServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use think\facade\App;

/**
 * 客服管理
 * Class StoreService
 * @package app\admin\controller\store
 */
class StoreService extends AuthController
{
    /**
     * StoreService constructor.
     * @param App $app
     * @param StoreServiceServices $services
     */
    public function __construct(App $app, StoreServiceServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        return $this->success($this->services->getServiceList([]));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create(UserWechatuserServices $services)
    {
        $where = $this->request->getMore([
            ['nickname', ''],
            ['data', '', '', 'time'],
            ['type', '', '', 'user_type'],
        ]);
        [$list, $count] = $services->getWhereUserList($where, 'u.nickname,u.uid,u.avatar as headimgurl,u.user_type,w.subscribe,w.province,w.country,w.city,w.sex');
        foreach ($list as &$item) {
            $item['subscribe'] = is_null($item['subscribe']) ? 0 : $item['subscribe'];
            $item['country'] = is_null($item['country']) ? '' : $item['country'];
            $item['province'] = is_null($item['province']) ? '' : $item['province'];
            $item['city'] = is_null($item['city']) ? '' : $item['city'];
            $item['sex'] = is_null($item['sex']) ? 0 : $item['sex'];
        }
        return $this->success(compact('list', 'count'));
    }

    /**
     * 添加客服表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function add()
    {
        return $this->success($this->services->create());
    }

    /*
     * 保存新建的资源
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['image', ''],
            ['uid', 0],
            ['avatar', ''],
            ['customer', ''],
            ['notify', ''],
            ['phone', ''],
            ['account', ''],
            ['password', ''],
            ['true_password', ''],
            ['phone', ''],
            ['nickname', ''],
            ['status', 1],
            ['account_status', 1],
        ]);
        if ($data['image'] == '') return $this->fail('请选择用户');
        $data['uid'] = $data['image']['uid'];
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $userInfo = $userService->get($data['uid']);
        if ($data['phone'] == '') {
            if (!$userInfo['phone']) {
                throw new AdminException('该用户没有绑定手机号，请手动填写');
            } else {
                $data['phone'] = $userInfo['phone'];
            }
        } else {
            if (!check_phone($data['phone'])) {
                throw new AdminException('请输入正确的手机号');
            }
        }
        if ($data['nickname'] == '') $data['nickname'] = $userInfo['nickname'];
        $data['avatar'] = $data['image']['image'];
        if ($this->services->count(['uid' => $data['uid']])) {
            return $this->fail('客服已存在!');
        }
        unset($data['image']);
        $data['add_time'] = time();
        if (!$data['account']) {
            return $this->fail('请输入账号');
        }
        if (!preg_match('/^[a-zA-Z0-9]{4,30}$/', $data['account'])) {
            return $this->fail('账号必须为数字或者字母的组合4-30位');
        }
        if (!$data['password']) {
            return $this->fail('请输入密码');
        }
        if (!preg_match('/^[0-9a-z_$]{6,20}$/i', $data['password'])) {
            return $this->fail('密码必须为数字或者字母的组合6-20位');
        }
        if ($this->services->count(['phone' => $data['phone']])) {
            return $this->fail('该手机号的客服已存在!');
        }
        if ($this->services->count(['account' => $data['account']])) {
            return $this->fail('该客服账号已存在!');
        }
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        $res = $this->services->save($data);
        if ($res) {
            return $this->success('客服添加成功');
        } else {
            return $this->fail('客服添加失败，请稍后再试');
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
        return $this->success($this->services->edit((int)$id));
    }

    /**
     * 保存新建的资源
     *
     * @param \think\Request $request
     * @return \think\Response
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['avatar', ''],
            ['nickname', ''],
            ['account', ''],
            ['phone', ''],
            ['status', 1],
            ['account_status', 1],
            ['notify', 1],
            ['customer', 1],
            ['password', ''],
            ['true_password', ''],
        ]);
        $customer = $this->services->get((int)$id);
        if (!$customer) {
            return $this->fail('数据不存在');
        }
        if ($data["nickname"] == '') {
            return $this->fail("客服名称不能为空！");
        }
        if (!check_phone($data['phone'])) {
            return $this->fail('请输入正确的手机号');
        }
        if ($customer['phone'] != $data['phone'] && $this->services->count(['phone' => $data['phone']])) {
            return $this->fail('该手机号的客服已存在!');
        }
        if ($data['password']) {
            if (!preg_match('/^[0-9a-z_$]{6,16}$/i', $data['password'])) {
                return $this->fail('密码必须为数字或者字母的组合');
            }
            if (!$data['true_password']) {
                return $this->fail('请输入确认密码');
            }
            if ($data['password'] != $data['true_password']) {
                return $this->fail('两次输入的密码不正确');
            }
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            unset($data['password']);
        }
        $this->services->update($id, $data);
        return $this->success('修改成功!');
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$this->services->delete($id))
            return $this->fail('删除失败,请稍候再试!');
        else
            return $this->success('删除成功!');
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function set_status(UserServices $services, $id, $status)
    {
        if ($status == '' || $id == 0) return $this->fail('参数错误');
        $info = $this->services->get($id, ['account_status', 'uid']);
        if (!$services->count(['uid' => $info['uid']])) {
            $info->account_status = 1;
            $info->save();
            return $this->fail('用户不存在，客服将强制禁止登录');
        }
        $info->account_status = $status;
        $info->save();
        return $this->success($status == 0 ? '隐藏成功' : '显示成功');
    }

    /**
     * 聊天记录
     *
     * @return \think\Response
     */
    public function chat_user($id)
    {
        $uid = $this->services->value(['id' => $id], 'uid');
        if (!$uid) {
            return $this->fail('数据不存在!');
        }
        return $this->success($this->services->getChatUser((int)$uid));
    }


    /**
     * 聊天记录
     * @param StoreServiceLogServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function chat_list(StoreServiceLogServices $services)
    {
        $data = $this->request->getMore([
            ['uid', 0],
            ['to_uid', 0],
            ['id', 0]
        ]);
        if ($data['uid']) {
            CacheService::set('admin_chat_list' . $this->adminId, $data);
        }
        $data = CacheService::get('admin_chat_list' . $this->adminId);
        if ($data['uid']) {
            $where = [
                'chat' => [$data['uid'], $data['to_uid']],
            ];
        } else {
            $where = [];
        }
        $list = $services->getChatLogList($where);
        return $this->success($list);
    }

    /**
     * 客服登录
     * @param LoginServices $services
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function keufLogin(LoginServices $services, $id)
    {
        $serviceInfo = $services->get($id);
        if (!$serviceInfo) {
            return $this->fail('登录的客服不存在');
        }
        if (!$serviceInfo->account || !$serviceInfo->password) {
            return $this->fail('请先填写客服账号和密码再尝试进入客服平台');
        }
        if (!$serviceInfo->account_status || !$serviceInfo->status) {
            return $this->fail('客服帐号已被禁用');
        }
        return $this->success($services->authLogin($serviceInfo->account));
    }

}
