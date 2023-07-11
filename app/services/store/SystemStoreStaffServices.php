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
namespace app\services\store;

use app\dao\store\SystemStoreStaffDao;
use app\services\BaseServices;
use app\services\order\OtherOrderServices;
use app\services\order\store\BranchOrderServices;
use app\services\system\SystemRoleServices;
use app\services\user\UserCardServices;
use app\services\user\UserRechargeServices;
use app\services\user\UserSpreadServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder;
use think\exception\ValidateException;

/**
 * 门店店员
 * Class SystemStoreStaffServices
 * @package app\services\system\store
 * @method count(array $where = []) 获取指定条件下的count
 * @method update($id, array $data, ?string $key = null) 修改数据
 * @method save(array $data) 保存数据
 * @method delete(int $id, ?string $key = null) 删除数据
 */
class SystemStoreStaffServices extends BaseServices
{
    /**
     * @var FormBuilder
     */
    protected $builder;

    /**
     * 构造方法
     * SystemStoreStaffServices constructor.
     * @param SystemStoreStaffDao $dao
     */
    public function __construct(SystemStoreStaffDao $dao, FormBuilder $builder)
    {
        $this->dao = $dao;
        $this->builder = $builder;
    }

    /**
     * 获取低于等级的店员名称和id
     * @param string $field
     * @param int $level
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrdAdmin(string $field = 'real_name,id', int $storeId = 0, int $level = 0)
    {
        return $this->dao->getWhere()->when('store_id', $storeId)->where('level', '>=', $level)->field($field)->select()->toArray();
    }

    /**
     * 获取店员详情
     * @param int $id
     * @param string $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStaffInfo(int $id, string $field = '*')
    {
        $info = $this->dao->getOne(['id' => $id, 'is_del' => 0], $field);
        if (!$info) {
            throw new ValidateException('店员不存在');
        }
        return $info;
    }

    /**
     * 根据uid获取门店店员信息
     * @param int $uid
     * @param int $store_id
     * @param string $field
     * @return array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStaffInfoByUid(int $uid, int $store_id = 0, string $field = '*')
    {
        $where = ['uid' => $uid, 'is_del' => 0, 'status' => 1];
        if ($store_id) $where['store_id'] = $store_id;
        $info = $this->dao->getOne($where, $field);
        if (!$info) {
            throw new ValidateException('店员不存在');
        }
        return $info;
    }

    /**
     * 获取门店｜店员统计
     * @param int $store_id
     * @param int $staff_id
     * @param string $time
     * @return array
     */
    public function getStoreData(int $uid, int $store_id, int $staff_id = 0, string $time = 'today')
    {
        $where = ['store_id' => $store_id, 'time' => $time];
        if ($staff_id) {
            $where['staff_id'] = $staff_id;
        }
        $data = [];
        $order_where = ['pid' => 0, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0];
        /** @var BranchOrderServices $orderServices */
        $orderServices = app()->make(BranchOrderServices::class);
        $data['send_price'] = $orderServices->sum($where + $order_where + ['type' => 7], 'pay_price', true);
        $data['send_count'] = $orderServices->count($where + $order_where + ['type' => 7]);
        $data['refund_price'] = $orderServices->sum($where + ['status' => -3], 'pay_price', true);
        $data['refund_count'] = $orderServices->count($where + ['status' => -3]);
        $data['cashier_price'] = $orderServices->sum($where + $order_where + ['type' => 6], 'pay_price', true);
        $data['writeoff_price'] = $orderServices->sum($where + $order_where + ['type' => 5], 'pay_price', true);
        /** @var OtherOrderServices $otherOrder */
        $otherOrder = app()->make(OtherOrderServices::class);
        $data['svip_price'] = $otherOrder->sum($where + ['paid' => 1, 'type' => [0, 1, 2, 4]], 'pay_price', true);
        /** @var UserRechargeServices $userRecharge */
        $userRecharge = app()->make(UserRechargeServices::class);
        $data['recharge_price'] = $userRecharge->getWhereSumField($where + ['paid' => 1], 'price');
        /** @var UserSpreadServices $userSpread */
        $userSpread = app()->make(UserSpreadServices::class);
        $data['spread_count'] = $userSpread->count($where + ['timeKey' => 'spread_time']);
        /** @var UserCardServices $userCard */
        $userCard = app()->make(UserCardServices::class);
        $data['card_count'] = $userCard->count($where + ['is_submit' => 1]);
        return $data;
    }

    /**
     * 判断是否是有权限核销的店员
     * @param $uid
     * @return int
     */
    public function verifyStatus($uid)
    {
        return $this->dao->getOne(['uid' => $uid, 'status' => 1, 'is_del' => 0, 'verify_status' => 1]) ? true : false;
    }

    /**
     * 获取店员列表
     * @param array $where
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreStaffList(array $where, array $with = [])
    {
        $with = array_merge($with, [
            'workMember' => function ($query) {
                $query->field(['uid', 'name', 'position', 'qr_code', 'external_position']);
            }
        ]);
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getStoreStaffList($where, '*', $page, $limit, $with);
        if ($list) {
            /** @var SystemRoleServices $service */
            $service = app()->make(SystemRoleServices::class);
            $allRole = $service->getRoleArray(['type' => 2, 'store_id' => $where['store_id'], 'status' => 1]);
            foreach ($list as &$item) {
                if ($item['roles']) {
                    $roles = [];
                    foreach ($item['roles'] as $id) {
                        if (isset($allRole[$id])) $roles[] = $allRole[$id];
                    }
                    if ($roles) {
                        $item['roles'] = implode(',', $roles);
                    } else {
                        $item['roles'] = '';
                    }
                } else {
                    $item['roles'] = '';
                }
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 不查询总数
     * @param array $where
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreStaff(array $where, array $with = [])
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getStoreStaffList($where, '*', $page, $limit, $with);
        foreach ($list as $key => $item) {
            unset($list[$key]['pwd']);
        }
        return $list;
    }

    /**
     * 店员详情
     * @param int $id
     * @return array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function read(int $id)
    {
        $staffInfo = $this->getStaffInfo($id);
        $info = [
            'id' => $id,
            'headerList' => $this->getHeaderList($id, $staffInfo),
            'ps_info' => $staffInfo
        ];
        return $info;
    }

    /**
     * 获取单个店员统计信息
     * @param $id 用户id
     * @return mixed
     */
    public function staffDetail(int $id, string $type)
    {
        $staffInfo = $this->getStaffInfo($id);
        if (!$staffInfo) {
            throw new AdminException('店员不存在');
        }
        $where = ['store_id' => $staffInfo['store_id'], 'staff_id' => $staffInfo['id']];
        $data = [];
        switch ($type) {
            case 'cashier_order':
                /** @var BranchOrderServices $orderServices */
                $orderServices = app()->make(BranchOrderServices::class);
                $where = array_merge($where, ['pid' => 0, 'type' => 6, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0]);
                $field = ['uid', 'order_id', 'real_name', 'total_num', 'total_price', 'pay_price', 'FROM_UNIXTIME(pay_time,"%Y-%m-%d") as pay_time', 'paid', 'pay_type', 'type', 'activity_id', 'pink_id'];
                $data = $orderServices->getStoreOrderList($where, $field, [], true);
                break;
            case 'self_order':
                /** @var BranchOrderServices $orderServices */
                $orderServices = app()->make(BranchOrderServices::class);
                $where = array_merge($where, ['pid' => 0, 'type' => 7, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0]);
                $field = ['uid', 'order_id', 'real_name', 'total_num', 'total_price', 'pay_price', 'FROM_UNIXTIME(pay_time,"%Y-%m-%d") as pay_time', 'paid', 'pay_type', 'type', 'activity_id', 'pink_id'];
                $data = $orderServices->getStoreOrderList($where, $field, [], true);
                break;
            case 'writeoff_order':
                /** @var BranchOrderServices $orderServices */
                $orderServices = app()->make(BranchOrderServices::class);
                $where = array_merge($where, ['pid' => 0, 'type' => 5, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0]);
                $field = ['uid', 'order_id', 'real_name', 'total_num', 'total_price', 'pay_price', 'FROM_UNIXTIME(pay_time,"%Y-%m-%d") as pay_time', 'paid', 'pay_type', 'type', 'activity_id', 'pink_id'];
                $data = $orderServices->getStoreOrderList($where, $field, [], true);
                break;
            case 'recharge':
                /** @var UserRechargeServices $userRechargeServices */
                $userRechargeServices = app()->make(UserRechargeServices::class);
                $data = $userRechargeServices->getRechargeList($where + ['paid' => 1]);
                break;
            case 'spread':
                /** @var UserSpreadServices $userSpreadServices */
                $userSpreadServices = app()->make(UserSpreadServices::class);
                $data = $userSpreadServices->getSpreadList($where);
                break;
            case 'card':
                /** @var UserCardServices $userCardServices */
                $userCardServices = app()->make(UserCardServices::class);
                $data = $userCardServices->getCardList($where + ['is_submit' => 1]);
                break;
            case 'svip':
                /** @var OtherOrderServices $otherOrderServices */
                $otherOrderServices = app()->make(OtherOrderServices::class);
                $data = $otherOrderServices->getMemberRecord($where);
                break;
            default:
                throw new AdminException('type参数错误');
        }
        return $data;
    }

    /**
     * 店员详情头部信息
     * @param int $id
     * @param array $staffInfo
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHeaderList(int $id, $staffInfo = [])
    {
        if (!$staffInfo) {
            $staffInfo = $this->dao->get($id);
        }
        $where = ['store_id' => $staffInfo['store_id'], 'staff_id' => $staffInfo['id']];
        /** @var BranchOrderServices $orderServices */
        $orderServices = app()->make(BranchOrderServices::class);
        $cashier_order = $orderServices->sum($where + ['pid' => 0, 'type' => 6, 'paid' => 1, 'refund_status' => 0, 'is_del' => 0, 'is_system_del' => 0], 'pay_price', true);
        $writeoff_order = $orderServices->sum($where + ['pid' => 0, 'type' => 5, 'paid' => 1, 'refund_status' => 0, 'is_del' => 0, 'is_system_del' => 0], 'pay_price', true);
        $self_order = $orderServices->sum($where + ['pid' => 0, 'type' => 7, 'paid' => 1, 'refund_status' => 0, 'is_del' => 0, 'is_system_del' => 0], 'pay_price', true);
        /** @var UserRechargeServices $userRechargeServices */
        $userRechargeServices = app()->make(UserRechargeServices::class);
        $recharge = $userRechargeServices->sum($where + ['paid' => 1], 'price', true);
        /** @var UserSpreadServices $userSpreadServices */
        $userSpreadServices = app()->make(UserSpreadServices::class);
        $spread = $userSpreadServices->count($where);
        /** @var UserCardServices $userCardServices */
        $userCardServices = app()->make(UserCardServices::class);
        $card = $userCardServices->count($where + ['is_submit' => 1]);
        /** @var OtherOrderServices $otherOrderServices */
        $otherOrderServices = app()->make(OtherOrderServices::class);
        $svip = $otherOrderServices->sum($where, 'pay_price', true);
        return [
            [
                'title' => '收银订单',
                'value' => $cashier_order,
                'key' => '元',
            ],
            [
                'title' => '核销订单',
                'value' => $writeoff_order,
                'key' => '元',
            ],
            [
                'title' => '配送订单',
                'value' => $self_order,
                'key' => '元',
            ],
            [
                'title' => '充值订单',
                'value' => $recharge,
                'key' => '元',
            ],
            [
                'title' => '付费会员',
                'value' => $svip,
                'key' => '元',
            ],
            [
                'title' => '推广用户数',
                'value' => $spread,
                'key' => '人',
            ],
            [
                'title' => '激活会员卡',
                'value' => $card,
                'key' => '张',
            ]
        ];
    }

    /**
     * 获取select选择框中的门店列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreSelectFormData()
    {
        /** @var SystemStoreServices $service */
        $service = app()->make(SystemStoreServices::class);
        $menus = [];
        foreach ($service->getStore() as $menu) {
            $menus[] = ['value' => $menu['id'], 'label' => $menu['name']];
        }
        return $menus;
    }

    /**
     * 获取核销员表单
     * @param array $formData
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createStaffForm(array $formData = [])
    {
        if ($formData) {
            $field[] = $this->builder->frameImage('image', '更换头像', $this->url('admin/widget.images/index', array('fodder' => 'image'), true), $formData['avatar'] ?? '')->icon('ios-add')->width('960px')->height('505px')->modal(['footer-hide' => true]);
        } else {
            $field[] = $this->builder->frameImage('image', '商城用户', $this->url('admin/system.User/list', ['fodder' => 'image'], true))->icon('ios-add')->width('960px')->height('550px')->modal(['footer-hide' => true])->Props(['srcKey' => 'image']);
        }
        $field[] = $this->builder->hidden('uid', $formData['uid'] ?? 0);
        $field[] = $this->builder->hidden('avatar', $formData['avatar'] ?? '');
        $field[] = $this->builder->select('store_id', '所属提货点', ($formData['store_id'] ?? 0))->setOptions($this->getStoreSelectFormData())->filterable(true);
        $field[] = $this->builder->input('staff_name', '核销员名称', $formData['staff_name'] ?? '')->col(24)->required();
        $field[] = $this->builder->input('phone', '手机号码', $formData['phone'] ?? '')->col(24)->required();
        $field[] = $this->builder->radio('verify_status', '核销开关', $formData['verify_status'] ?? 1)->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        $field[] = $this->builder->radio('status', '状态', $formData['status'] ?? 1)->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        return $field;
    }

    /**
     * 添加核销员表单
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createForm()
    {
        return create_form('添加核销员', $this->createStaffForm(), $this->url('/merchant/store_staff/save/0'));
    }

    /**
     * 编辑核销员form表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateForm(int $id)
    {
        $storeStaff = $this->dao->get($id);
        if (!$storeStaff) {
            throw new AdminException('没有查到信息,无法修改');
        }
        return create_form('修改核销员', $this->createStaffForm($storeStaff->toArray()), $this->url('/merchant/store_staff/save/' . $id));
    }

    /**
     * 获取门店店员
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreAdminList($where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getStoreAdminList($where, $page, $limit);
        /** @var SystemRoleServices $service */
        $service = app()->make(SystemRoleServices::class);
        $allRole = $service->getRoleArray(['type' => 2, 'store_id' => $where['store_id']]);
        foreach ($list as &$item) {
            if ($item['roles']) {
                $roles = [];
                foreach ($item['roles'] as $id) {
                    if (isset($allRole[$id])) $roles[] = $allRole[$id];
                }
                if ($roles) {
                    $item['roles'] = implode(',', $roles);
                } else {
                    $item['roles'] = '';
                }
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 添加门店管理员
     * @param int $store_id
     * @param $level
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createStoreAdminForm(int $store_id, $level)
    {
        $field[] = $this->builder->input('staff_name', '管理员名称')->col(24)->required();
        $field[] = $this->builder->frameImage('avatar', '管理员头像', $this->url('store/widget.images/index', ['fodder' => 'avatar'], true))->icon('ios-add')->width('960px')->height('505px')->modal(['footer-hide' => true]);
        $field[] = $this->builder->input('account', '管理员账号')->maxlength(35)->required('请填写管理员账号');
        $field[] = $this->builder->input('phone', '手机号码')->col(24)->required();
        $field[] = $this->builder->input('pwd', '管理员密码')->type('password')->required('请填写管理员密码');
        $field[] = $this->builder->input('conf_pwd', '确认密码')->type('password')->required('请输入确认密码');
        /** @var SystemRoleServices $service */
        $service = app()->make(SystemRoleServices::class);
        $options = $service->getRoleFormSelect($level, 2, $store_id);
        $roles = [];
        $field[] = $this->builder->select('roles', '管理员身份', $roles)->setOptions(FormBuilder::setOptions($options))->multiple(true)->required('请选择管理员身份');
        $field[] = $this->builder->radio('status', '状态', 1)->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        return create_form('添加门店管理员', $field, $this->url('/system/admin'));
    }

    /**
     * 修改门店管理员
     * @param $id
     * @param $level
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateStoreAdminForm($id, $level)
    {
        $adminInfo = $this->dao->get($id);
        if (!$adminInfo) {
            throw new AdminException('门店管理员不存在!');
        }
        if ($adminInfo->is_del) {
            throw new AdminException('门店管理员已经删除');
        }
        $adminInfo = $adminInfo->toArray();
        $field[] = $this->builder->input('staff_name', '门店管理员名称', $adminInfo['staff_name'])->col(24)->required('请填写门店管理员名称');
        $field[] = $this->builder->frameImage('avatar', '管理员头像', $this->url('store/widget.images/index', ['fodder' => 'avatar'], true), $adminInfo['avatar'] ?? '')->icon('ios-add')->width('960px')->height('505px')->modal(['footer-hide' => true]);
        $field[] = $this->builder->input('account', '门店管理员账号', $adminInfo['account'])->maxlength(35)->required('请填写门店管理员账号');
        $field[] = $this->builder->input('phone', '手机号码', $adminInfo['phone'])->col(24)->required();
        $field[] = $this->builder->input('pwd', '门店管理员密码')->placeholder('不更改密码请留空')->type('password');
        $field[] = $this->builder->input('conf_pwd', '确认密码')->placeholder('不更改密码请留空')->type('password');
        /** @var SystemRoleServices $service */
        $service = app()->make(SystemRoleServices::class);
        $options = $service->getRoleFormSelect($level, 2, (int)$adminInfo['store_id']);
        $roles = [];
        if ($adminInfo && isset($adminInfo['roles']) && $adminInfo['roles']) {
            foreach ($adminInfo['roles'] as $role) {
                $roles[] = (int)$role;
            }
        }
        $field[] = $this->builder->select('roles', '管理员身份', $roles)->setOptions(FormBuilder::setOptions($options))->multiple(true)->required('请选择门店管理员身份');
        $field[] = $this->builder->radio('status', '状态', (int)$adminInfo['status'])->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        return create_form('修改门店管理员', $field, $this->url('/system/admin/' . $id), 'put');
    }

    /**
     * 添加门店店员
     * @param int $store_id
     * @param $level
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createStoreStaffForm(int $store_id, $level)
    {
        $field[] = $this->builder->input('staff_name', '店员名称')->col(24)->required('请输入门店店员名称');
//        $field[] = $this->builder->frameImage('avatar', '店员头像', $this->url('store/widget.images/index', ['fodder' => 'avatar'], true))->icon('ios-add')->width('960px')->height('430px');
        $field[] = $this->builder->frameImage('image', '商城用户', $this->url('store/system.User/list', ['fodder' => 'image'], true))->icon('ios-add')->width('960px')->height('450px')->modal(['footer-hide' => true])->Props(['srcKey' => 'image']);
        $field[] = $this->builder->hidden('uid', 0);
        $field[] = $this->builder->hidden('avatar', '');
        $field[] = $this->builder->input('account', '店员账号')->maxlength(35)->required('请填写门店店员账号');
        $field[] = $this->builder->input('pwd', '店员密码')->type('password')->required('请填写门店店员密码');
        $field[] = $this->builder->input('conf_pwd', '确认密码')->type('password')->required('请输入确认密码');
        $field[] = $this->builder->input('phone', '手机号码')->col(24)->required('请输入手机号');
        /** @var SystemRoleServices $service */
        $service = app()->make(SystemRoleServices::class);
        $options = $service->getRoleFormSelect($level, 2, $store_id);
        $roles = [];
        $field[] = $this->builder->select('roles', '店员身份', $roles)->setOptions(FormBuilder::setOptions($options))->multiple(true)->required('请选择店员身份');
        $field[] = $this->builder->radio('is_manager', '是否是店长', 0)->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        $field[] = $this->builder->radio('order_status', '订单管理', 1)->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        $field[] = $this->builder->radio('verify_status', '核销开关', 1)->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        $field[] = $this->builder->radio('is_cashier', '是否是收银员', 1)->options([
            ['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']
        ]);
        $field[] = $this->builder->radio('notify', '通知开关', 0)->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        $field[] = $this->builder->radio('status', '状态', 1)->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        return create_form('添加门店店员', $field, $this->url('/staff/staff'));
    }

    /**
     * 编辑门店店员
     * @param $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateStoreStaffForm($id, $level)
    {
        $staffInfo = $this->dao->get($id);
        if (!$staffInfo) {
            throw new AdminException('门店店员不存在!');
        }
        if ($staffInfo->is_del) {
            throw new AdminException('门店店员已经删除');
        }
        $field[] = $this->builder->input('staff_name', '店员名称', $staffInfo['staff_name'])->col(24)->required('请填写门店店员名称');
        $field[] = $this->builder->frameImage('avatar', '店员头像', $this->url('store/widget.images/index', ['fodder' => 'avatar'], true), $staffInfo['avatar'] ?? '')->icon('ios-add')->width('960px')->height('505px')->modal(['footer-hide' => true]);
        $field[] = $this->builder->input('account', '店员账号', $staffInfo['account'])->maxlength(35)->required('请填写门店店员账号');
        $field[] = $this->builder->input('pwd', '店员密码')->placeholder('不更改密码请留空')->type('password');
        $field[] = $this->builder->input('conf_pwd', '确认密码')->placeholder('不更改密码请留空')->type('password');
        $field[] = $this->builder->input('phone', '手机号码', $staffInfo['phone'])->col(24)->required('请输入手机号');
        /** @var SystemRoleServices $service */
        $service = app()->make(SystemRoleServices::class);
        $options = $service->getRoleFormSelect($level, 2, (int)$staffInfo['store_id']);
        $roles = [];
        if ($staffInfo && isset($staffInfo['roles']) && $staffInfo['roles']) {
            foreach ($staffInfo['roles'] as $role) {
                $roles[] = (int)$role;
            }
        }
        $field[] = $this->builder->select('roles', '店员身份', $roles)->setOptions(FormBuilder::setOptions($options))->multiple(true)->required('请选择店员身份');
        $field[] = $this->builder->radio('is_manager', '是否是店长', (int)$staffInfo['is_manager'])->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        $field[] = $this->builder->radio('order_status', '订单管理', (int)$staffInfo['order_status'])->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        $field[] = $this->builder->radio('verify_status', '核销开关', (int)$staffInfo['verify_status'])->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        $field[] = $this->builder->radio('is_cashier', '是否是收银员', (int)$staffInfo['is_cashier'])->options([
            ['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']
        ]);
        $field[] = $this->builder->radio('notify', '通知开关', (int)$staffInfo['notify'])->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        $field[] = $this->builder->radio('status', '状态', (int)$staffInfo['status'])->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        return create_form('编辑门店店员', $field, $this->url('/staff/staff/' . $id), 'put');
    }

    /**
     * 获取店员select
     * @param array $where
     * @return mixed
     */
    public function getSelectList($where = [])
    {
        $list = $this->dao->getSelectList($where);
        $menus = [];
        foreach ($list as $menu) {
            $menus[] = ['value' => $menu['id'], 'label' => $menu['staff_name'] ?? ''];
        }
        return $menus;
    }

    /**
     * 首页店员统计
     * @param int $store_id
     * @param array $time
     * @return array
     */
    public function staffChart(int $store_id, array $time)
    {
        $list = $this->dao->getStoreStaffList(['store_id' => $store_id, 'is_del' => 0], 'id,uid,avatar,staff_name');
        if ($list) {
            /** @var UserSpreadServices $userSpreadServices */
            $userSpreadServices = app()->make(UserSpreadServices::class);
            /** @var BranchOrderServices $orderServices */
            $orderServices = app()->make(BranchOrderServices::class);
            /** @var OtherOrderServices $otherOrderServices */
            $otherOrderServices = app()->make(OtherOrderServices::class);
            $where = ['store_id' => $store_id, 'time' => $time];
            $order_where = ['paid' => 1, 'pid' => 0, 'is_del' => 0, 'is_system_del' => 0, 'refund_status' => [0, 3]];
            $staffIds = array_unique(array_column($list, 'id'));
            $otherStaff = $otherOrderServices->preStaffTotal($where + ['staff_id' => $staffIds, 'paid' => 1, 'type' => [0, 1, 2, 4]], 'distinct(`uid`)');
            $otherStaff = array_combine(array_column($otherStaff, 'staff_id'), $otherStaff);
            foreach ($list as &$item) {
                $staff_where = ['staff_id' => $item['id']];
                $spread_uid = $userSpreadServices->getColumn($where + ['timeKey' => 'spread_time'] + $staff_where, 'uid', '', true);
                $item['spread_count'] = count($spread_uid);
                $item['speread_order_price'] = 0;
                if ($spread_uid) {
                    $item['speread_order_price'] = $orderServices->sum($where + $order_where + ['uid' => $spread_uid], 'pay_price', true);
                }
                $item['vip_count'] = $otherStaff[$item['id']]['count'] ?? 0;
                $item['vip_price'] = $otherStaff[$item['id']]['price'] ?? 0;
                unset($spread);
            }
        }
        return $list;
    }

    /**
     * 修改当前店员信息
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateStaffPwd(int $id, array $data)
    {
        $staffInfo = $this->dao->get($id);
        if (!$staffInfo)
            throw new AdminException('店员信息未查到');
        if ($staffInfo->is_del) {
            throw new AdminException('店员已经删除');
        }
        if ($data['real_name']) {
            $staffInfo->staff_name = $data['real_name'];
        }
        if ($data['avatar']) {
            $staffInfo->avatar = $data['avatar'];
        }
        if ($data['pwd']) {
            if (!password_verify($data['pwd'], $staffInfo['pwd']))
                throw new AdminException('原始密码错误');
            if (!$data['new_pwd'])
                throw new AdminException('请输入新密码');
            if (!$data['conf_pwd'])
                throw new AdminException('请输入确认密码');
            if ($data['new_pwd'] != $data['conf_pwd'])
                throw new AdminException('两次输入的密码不一致');
            $staffInfo->pwd = $this->passwordHash($data['new_pwd']);
        }
        if ($staffInfo->save())
            return true;
        else
            return false;
    }

    /**
     * 获取门店接收通知店员
     * @param int $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNotifyStoreStaffList(int $store_id)
    {
        $where = [
            'store_id' => $store_id,
            'status' => 1,
            'is_del' => 0,
            'notify' => 1
        ];
        $list = $this->dao->getStoreStaffList($where);
        return $list;
    }

    /**
     * 获取所有门店有用的手机号
     * @return array
     */
    public function getPhoneAll()
    {
        $phone = array_merge(array_unique($this->dao->getColumn([
            ['is_del', '=', 0],
            ['status', '=', 1],
        ], 'phone')));

        return $phone ?: [0];
    }
}
