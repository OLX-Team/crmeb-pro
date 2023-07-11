<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\services\store;


use app\dao\store\DeliveryServiceDao;
use app\services\BaseServices;
use app\services\order\store\BranchOrderServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder;
use think\exception\ValidateException;


/**
 * 配送
 * Class DeliveryServiceServices
 * @package app\services\store
 * @method update($id, array $data, ?string $key = null) 修改数据
 */
class DeliveryServiceServices extends BaseServices
{
    /**
     * 创建form表单
     * @var Form
     */
    protected $builder;

    /**
     * 构造方法
     * DeliveryServiceServices constructor.
     * @param DeliveryServiceDao $dao
     * @param FormBuilder $builder
     */
    public function __construct(DeliveryServiceDao $dao, FormBuilder $builder)
    {
        $this->dao = $dao;
        $this->builder = $builder;
    }

    /**
     * 获取配送员详情
     * @param int $id
     * @param string $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDeliveryInfo(int $id, string $field = '*')
    {
        $info = $this->dao->getOne(['id' => $id, 'is_del' => 0], $field);
        if (!$info) {
            throw new ValidateException('配送员不存在');
        }
        return $info;
    }

    /**
     * 更具uid获取配送员信息
     * @param int $uid
     * @param string $field
     * @return array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDeliveryInfoByUid(int $uid, int $store_id = 0, string $field = '*')
    {
        $where = ['uid' => $uid, 'is_del' => 0, 'status' => 1];
        if ($store_id) $where['store_id'] = $store_id;
        $info = $this->dao->getOne($where, $field);
        if (!$info) {
            throw new ValidateException('配送员不存在');
        }
        return $info;
    }

    /**
     * 获取配送员所在门店id
     * @param int $uid
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getDeliveryStoreIds(int $uid, string $field, string $key = '')
    {
        return $this->dao->getColumn(['uid' => $uid, 'is_del' => 0, 'status' => 1], $field, $key);
    }

    /**
     * 获取单个配送员统计信息
     * @param int $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deliveryDetail(int $id)
    {
        $deliveryInfo = $this->getDeliveryInfo($id);
        if (!$deliveryInfo) {
            throw new AdminException('配送员不存在');
        }
        $where = ['store_id' => $deliveryInfo['store_id'], 'delivery_uid' => $deliveryInfo['uid']];
        /** @var BranchOrderServices $orderServices */
        $orderServices = app()->make(BranchOrderServices::class);
        $order_where = ['paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0];

        $field = ['id', 'type', 'order_id', 'real_name', 'total_num', 'total_price', 'pay_price', 'FROM_UNIXTIME(pay_time,"%Y-%m-%d") as pay_time', 'paid', 'pay_type', 'activity_id', 'pink_id'];
        $list = $orderServices->getStoreOrderList($where + $order_where, $field, [], true);
        $data = ['id' => $id];
        $data['info'] = $deliveryInfo;
        $data['list'] = $list;
        $data['data'] = [
            [
                'title' => '待配送订单数',
                'value' => $orderServices->count($where + $order_where + ['status' => 2]),
                'key' => '单',
            ],
            [
                'title' => '已配送订单数',
                'value' => $orderServices->count($where + $order_where + ['status' => 3]),
                'key' => '单',
            ],
            [
                'title' => '待配送订单金额',
                'value' => $orderServices->sum($where + $order_where + ['status' => 2], 'pay_price', true),
                'key' => '元',
            ],
            [
                'title' => '已配送订单',
                'value' => $orderServices->sum($where + $order_where + ['status' => 3], 'pay_price', true),
                'key' => '元',
            ]
        ];
        return $data;
    }

    /**
     * 获取门店配送员订单统计
     * @param int $uid
     * @param int $store_id
     * @param string $time
     * @return array
     */
    public function getStoreData(int $uid, int $store_id, string $time = 'today')
    {
        $this->getDeliveryInfoByUid($uid, $store_id);
        $data = [];
        $where = ['delivery_uid' => $uid, 'time' => $time, 'paid' => 1, 'is_del' => 0, 'is_system_del' => 0, 'refund_status' => [0, 3]];
        if ($store_id) {
            $where['store_id'] = $store_id;
        }
        /** @var BranchOrderServices $order */
        $order = app()->make(BranchOrderServices::class);
        $where['status'] = 2;
        $data['unsend'] = $order->count($where);
        $where['status'] = 9;
        $data['send'] = $order->count($where);
        $data['send_price'] = $order->sum($where, 'pay_price', true);
        return $data;
    }

    /**
     * 获取配送员列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getServiceList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getServiceList($where, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取配送员列表
     * @param int $type
     * @param int $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDeliveryList(int $type = 1, int $store_id = 0)
    {
        [$page, $limit] = $this->getPageValue();
        $where = ['status' => 1, 'is_del' => 0, 'type' => $type, 'store_id' => $store_id];
        $list = $this->dao->getServiceList($where, $page, $limit);
        foreach ($list as &$item) {
            if (!$item['nickname']) $item['nickname'] = $item['wx_name'];
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 创建配送员表单
     * @param array $formData
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createServiceForm(array $formData = [])
    {
        if ($formData) {
            $field[] = $this->builder->frameImage('avatar', '配送员头像', $this->url('admin/widget.images/index', ['fodder' => 'avatar'], true), $formData['avatar'] ?? '')->icon('ios-add')->width('960px')->height('505px')->modal(['footer-hide' => true]);
//            $field[] = $this->builder->frameImage('avatar', '用户头像', $this->url('admin/widget.images/index', array('fodder' => 'avatar')))->icon('ios-add')->width('50%')->height('396px');
        } else {
            $field[] = $this->builder->frameImage('image', '商城用户', $this->url('admin/system.user/list', ['fodder' => 'image'], true))->icon('ios-add')->width('960px')->height('550px')->modal(['footer-hide' => true])->Props(['srcKey' => 'image']);
            $field[] = $this->builder->hidden('uid', 0);
            $field[] = $this->builder->hidden('avatar', '');
        }
        $field[] = $this->builder->input('nickname', '配送员名称', $formData['nickname'] ?? '')->required('请填写配送员名称')->col(24);
        $field[] = $this->builder->input('phone', '手机号码', $formData['phone'] ?? '')->required('请填写电话')->col(24)->maxlength(11);
        $field[] = $this->builder->radio('status', '配送员状态', $formData['status'] ?? 1)->options([['value' => 1, 'label' => '显示'], ['value' => 0, 'label' => '隐藏']]);
        return $field;
    }

    /**
     * 创建配送员获取表单
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create()
    {
        return create_form('添加配送员', $this->createServiceForm(), $this->url('/order/delivery/save'), 'POST');
    }

    /**
     * 编辑获取表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function edit(int $id)
    {
        $serviceInfo = $this->dao->get($id);
        if (!$serviceInfo) {
            throw new AdminException('数据不存在!');
        }
        return create_form('编辑配送员', $this->createServiceForm($serviceInfo->toArray()), $this->url('/order/delivery/update/' . $id), 'PUT');
    }

    /**
     * 获取某人的聊天记录用户列表
     * @param int $uid
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChatUser(int $uid)
    {
        /** @var StoreServiceLogServices $serviceLog */
        $serviceLog = app()->make(StoreServiceLogServices::class);
        /** @var UserServices $serviceUser */
        $serviceUser = app()->make(UserServices::class);
        $uids = $serviceLog->getChatUserIds($uid);
        if (!$uids) {
            return [];
        }
        return $serviceUser->getUserList(['uid' => $uids], 'nickname,uid,avatar as headimgurl');
    }

    /**
     * 检查用户是否是配送员
     * @param int $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkoutIsService(int $uid)
    {
        return $this->dao->count(['uid' => $uid, 'status' => 1, 'is_del' => 0]) ? true : false;
    }

    /**
     * 添加门店配送员
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createStoreDeliveryForm()
    {
        $field[] = $this->builder->frameImage('image', '商城用户', $this->url('store/system.User/list', ['fodder' => 'image'], true))->icon('ios-add')->width('960px')->height('430px')->modal(['footer-hide' => true])->Props(['srcKey' => 'image']);
        $field[] = $this->builder->hidden('uid', 0);
        $field[] = $this->builder->hidden('avatar', '');
        $field[] = $this->builder->input('nickname', '配送员名称')->required('请填写配送员名称')->col(24);
        $field[] = $this->builder->input('phone', '手机号码')->required('请填写电话')->col(24)->maxlength(11);
        $field[] = $this->builder->radio('status', '配送员状态', 1)->options([['value' => 1, 'label' => '显示'], ['value' => 0, 'label' => '隐藏']]);
        return create_form('添加门店配送员', $field, $this->url('/staff/delivery'));
    }

    /**
     * 编辑门店配送员
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function updateStoreDeliveryForm($id, $formData)
    {
        $field[] = $this->builder->input('nickname', '配送员名称', $formData['nickname'] ?? '')->required('请填写配送员名称')->col(24);
        $field[] = $this->builder->frameImage('avatar', '配送员头像', $this->url('store/widget.images/index', ['fodder' => 'avatar'], true), $formData['avatar'] ?? '')->icon('ios-add')->width('960px')->height('505px')->modal(['footer-hide' => true]);
        $field[] = $this->builder->input('phone', '手机号码', $formData['phone'] ?? '')->required('请填写电话')->col(24)->maxlength(11);
        $field[] = $this->builder->radio('status', '配送员状态', $formData['status'] ?? 1)->options([['value' => 1, 'label' => '显示'], ['value' => 0, 'label' => '隐藏']]);
        return create_form('编辑门店配送员', $field, $this->url('/staff/delivery/' . $id), 'put');
    }

    /**
     * 获取配送员select（Uid）
     * @param array $where
     * @return mixed
     */
    public function getSelectList($where = [])
    {
        $list = $this->dao->getSelectList($where);
        $menus = [];
        foreach ($list as $menu) {
            $menus[] = ['value' => $menu['uid'], 'label' => $menu['nickname']];
        }
        return $menus;
    }
}
