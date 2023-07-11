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
namespace app\controller\store\staff;

use app\services\order\store\BranchOrderServices;
use app\services\store\finance\StoreFinanceFlowServices;
use app\services\store\StoreUserServices;
use app\services\store\SystemStoreServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use think\facade\App;
use app\controller\store\AuthController;
use app\services\store\SystemStoreStaffServices;

/**
 * 店员
 * Class SystemStoreStaff
 * @package app\controller\store\staff
 */
class StoreStaff extends AuthController
{
    protected $level = null;

    /**
     * 构造方法
     * SystemStoreStaff constructor.
     * @param App $app
     * @param SystemStoreStaffServices $services
     */
    public function __construct(App $app, SystemStoreStaffServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
        $this->level = $this->storeStaffInfo['level'] ?? 0;
    }

    /**
     * 获取店员列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['keyword', ''],
            ['field_key', ''],
        ]);
        if ($where['field_key'] == 'all') $where['field_key'] = '';
        $where['store_id'] = $this->storeId;
        if ($this->level) {
            $where['level'] = $this->level + 1;
        }
        $where['is_admin'] = 0;
        $where['is_del'] = 0;
        return app('json')->success($this->services->getStoreStaffList($where));
    }

    /**
     * 获取店员select
     * @param SystemStoreStaffServices $services
     * @return mixed
     */
    public function getStaffSelect()
    {
        $where['store_id'] = $this->storeId;
        $where['is_del'] = 0;
        $where['status'] = 1;
        return app('json')->success($this->services->getSelectList($where));
    }

    /**
     * 获取某个店员信息
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        if (!$id) {
            return app('json')->fail('缺少店员id');
        }
        return app('json')->success($this->services->read((int)$id));
    }

    /**
     * 店员
     * @param $id
     * @return mixed
     */
    public function staffDetail($id)
    {
        $data = $this->request->getMore([
            ['type', ''],
        ]);
        $id = (int)$id;
        if ($data['type'] == '' || !$id) return $this->fail('缺少参数');
        return $this->success($this->services->staffDetail($id, $data['type']));
    }

    /**
     * 店员新增表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create()
    {
        return app('json')->success($this->services->createStoreStaffForm((int)$this->storeId, $this->storeStaffInfo['level'] + 1));
    }

    /**
     * 店员修改表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function edit()
    {
        [$id] = $this->request->getMore([
            [['id', 'd'], 0],
        ], true);
        return app('json')->success($this->services->updateStoreStaffForm($id, $this->storeStaffInfo['level'] + 1));
    }

    /**
     * 保存店员信息
     */
    public function save($id = 0)
    {
        $data = $this->request->postMore([
            ['image', ''],
            ['account', ''],
            ['uid', 0],
            ['avatar', ''],
            ['staff_name', ''],
            ['roles', []],
            ['phone', ''],
            ['verify_status', 1],
            ['order_status', 1],
            ['is_manager', 0],
            ['is_cashier', 0],
            ['status', 1],
            ['conf_pwd', ''],
            ['pwd', ''],
            ['notify', 0]
        ]);
        $data['store_id'] = $this->storeId;
        $data['is_admin'] = 0;
        if ($data['image'] == '') return $this->fail('请选择用户');
        $data['uid'] = $data['image']['uid'];
        if ($data['staff_name'] == '') {
            return app('json')->fail('请填店员名称');
        }
        if ($data['phone'] == '') {
            return app('json')->fail('请填写店员电话');
        }
        if (!check_phone($data['phone'])) {
            return app('json')->fail('请输入正确的手机号');
        }
        if ($data['conf_pwd'] != $data['pwd']) {
            return app('json')->fail('两次输入的密码不相同');
        }
        if ($this->services->count(['account' => $data['account'], 'is_del' => 0])) {
            return app('json')->fail('该员工账号已经存在');
        }
        if ($this->services->count(['phone' => $data['phone'], 'is_del' => 0])) {
            return app('json')->fail('该手机号已经存在');
        }
        $data['avatar'] = $data['image']['image'];
        $staff = $this->services->get(['uid' => $data['uid'], 'is_del' => 0]);
        if ($staff) {
            /** @var SystemStoreServices $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $store = $storeServices->get($staff['store_id']);
            return $this->fail($staff['id'] == $this->storeId ? '该用户已存在!' : '该用户已在（' . ($store['name'] ?? '') . '）门店存在!');
        }
        unset($data['image']);
        unset($data['conf_pwd'], $data['image']);
        $data['level'] = $this->storeStaffInfo['level'] + 1;
        $data['pwd'] = $this->services->passwordHash($data['pwd']);
        $data['add_time'] = time();
        $res = $this->services->save($data);
        if ($res) {
            return app('json')->success('添加成功');
        } else {
            return app('json')->fail('添加失败，请稍后再试');
        }
    }

    /**
     * 保存店员信息
     */
    public function update($id = 0)
    {
        $data = $this->request->postMore([
            ['account', ''],
            ['avatar', ''],
            ['staff_name', ''],
            ['phone', ''],
            ['roles', []],
            ['verify_status', 1],
            ['order_status', 1],
            ['is_manager', 0],
            ['is_cashier', 0],
            ['status', 1],
            ['conf_pwd', ''],
            ['pwd', ''],
            ['notify', 0]
        ]);
        $data['store_id'] = $this->storeId;
        $data['is_admin'] = 0;
        if ($data['staff_name'] == '') {
            return app('json')->fail('请填店员名称');
        }
        if ($data['phone'] == '') {
            return app('json')->fail('请填写店员电话');
        }
        if (!check_phone($data['phone'])) {
            return app('json')->fail('请输入正确的手机号');
        }
        $staff = $this->services->get(['store_id' => $this->storeId, 'account' => $data['account'], 'is_del' => 0]);
        if ($staff && $staff['id'] != $id) {
            return app('json')->fail('该员工账号已经存在');
        }
        $staff = $this->services->get(['store_id' => $this->storeId, 'phone' => $data['phone'], 'is_del' => 0]);
        if ($staff && $staff['id'] != $id) {
            return app('json')->fail('该手机号已经存在');
        }
        if ($data['pwd']) {
            if (!$data['conf_pwd']) {
                return app('json')->fail('请输入确认密码');
            }
            if ($data['pwd'] != $data['conf_pwd']) {
                return app('json')->fail('两次输入的密码不一致');
            }
            $data['pwd'] = $this->services->passwordHash($data['pwd']);
        } else {
            unset($data['pwd']);
        }
        unset($data['conf_pwd']);
        $res = $this->services->update($id, $data);
        if ($res) {
            return app('json')->success('编辑成功');
        } else {
            return app('json')->fail('编辑失败');
        }
    }

    /**
     * 设置单个店员是否开启
     * @param string $is_show
     * @param string $id
     * @return mixed
     */
    public function set_show($is_show = '', $id = '')
    {
        if ($is_show == '' || $id == '') {
            $this->fail('缺少参数');
        }
        $res = $this->services->update($id, ['status' => (int)$is_show]);
        if ($res) {
            return app('json')->success($is_show == 1 ? '开启成功' : '关闭成功');
        } else {
            return app('json')->fail($is_show == 1 ? '开启失败' : '关闭失败');
        }
    }

    /**
     * 修改当前登陆店员信息
     * @return mixed
     */
    public function updateStaffPwd()
    {
        $data = $this->request->postMore([
            ['real_name', ''],
            ['pwd', ''],
            ['new_pwd', ''],
            ['conf_pwd', ''],
            ['avatar', ''],
        ]);
        if (!preg_match('/^(?![^a-zA-Z]+$)(?!\D+$).{6,}$/', $data['new_pwd'])) {
            return $this->fail('设置的密码过于简单(不小于六位包含数字字母)');
        }
        if ($this->services->updateStaffPwd($this->storeStaffId, $data))
            return $this->success('修改成功');
        else
            return $this->fail('修改失败');
    }

    /**
     * 删除店员
     * @param $id
     */
    public function delete($id)
    {
        if (!$id) return app('json')->fail('数据不存在');
        if (!$this->services->update($id, ['is_del' => 1]))
            return app('json')->fail('删除失败,请稍候再试!');
        else
            return app('json')->success('删除成功!');
    }

    /**
     * 店员绑定uid
     * @param UserServices $userServices
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function bandingUser(UserServices $userServices, StoreUserServices $storeUserServices)
    {
        [$uid, $staff_id] = $this->request->postMore([
            ['uid', 0],
            ['staff_id', ''],
        ], true);
        if (!$uid || !$staff_id) {
            return app('json')->fail('缺少参数');
        }
        if (!$userServices->count(['uid' => $uid])) {
            return app('json')->fail('用户不存在');
        }
        $staffInfo = $this->services->getStaffInfo((int)$staff_id);
        if (!$staffInfo) {
            return app('json')->fail('店员不存在');
        }
        $res = $this->services->transaction(function () use ($storeUserServices, $staffInfo, $uid, $staff_id) {
            //清空该门店uid绑定其他店员
            $re = $this->services->update(['store_id' => $staffInfo['store_id'], 'uid' => $uid, 'is_del' => 0], ['uid' => 0]);
            $re = $re && $this->services->update($staff_id, ['uid' => $uid]);
            //写入门店用户
            return $re && $storeUserServices->setStoreUser((int)$uid, (int)$this->storeId);
        });
        if (!$res) {
            return app('json')->fail('设置失败');
        }
        return app('json')->success('修改成功');
    }

    /**
     * 店员交易统计
     * @param StoreFinanceFlowServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function statistics(StoreFinanceFlowServices $services, BranchOrderServices $orderServices)
    {
        $where = $this->request->getMore([
            ['staff_id', -1],
            ['data', '', '', 'time'],
            ['type', 0]
        ]);
        $where['staff_id'] = $where['staff_id'] ?: -1;
        $where['store_id'] = $this->storeId;
        $where['is_del'] = 0;
        $where['trade_type'] = 2;
        $where['type'] = $where['type'] ?: [7, 8, 9, 10, 11];
        $where['time'] = $orderServices->timeHandle($where['time']);
        $where['is_del'] = 0;
        return app('json')->success($services->getList($where));
    }

    /**
     * 店员交易统计头部数据
     * @return mixed
     */
    public function statisticsHeader(StoreFinanceFlowServices $services, BranchOrderServices $orderServices)
    {
        $where = $this->request->getMore([
            ['staff_id', -1],
            ['data', '', '', 'time'],
            ['type', 0]
//            ['group', '']
        ]);
        $where['staff_id'] = $where['staff_id'] ?: -1;
        $where['store_id'] = $this->storeId;
        $where['trade_type'] = 2;
        $where['type'] = $where['type'] ?: [7, 8, 9, 10, 11];
        $where['is_del'] = 0;
        if ($where['staff_id'] == -1) {
            $where['time'] = $orderServices->timeHandle($where['time']);
            $data = $services->getStatisticsHeader($where);
        } else {
            $time = $orderServices->timeHandle($where['time'], true);
            $data = $services->getTypeHeader($where, $time);
        }
        return app('json')->success($data);
    }

    /**
     * 获取登录店员详情
     * @return mixed
     */
    public function info()
    {
        return app('json')->success($this->storeStaffInfo);
    }

    /**
     * 登录收银台
     * @param \app\services\cashier\LoginServices $services
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loginCashier(\app\services\cashier\LoginServices $services, $id)
    {
        $storeStaffInfo = $services->get($id);
        if (!$storeStaffInfo) {
            return app('json')->fail('账号不存在!');
        }
        if ($storeStaffInfo->is_del) {
            return app('json')->fail('账号不存在');
        }

        return app('json')->success($services->getLoginResult($id, 'cashier', $storeStaffInfo));
    }
}
