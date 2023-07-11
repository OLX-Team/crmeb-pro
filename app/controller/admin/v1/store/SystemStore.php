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
namespace app\controller\admin\v1\store;

use app\services\order\StoreOrderServices;
use app\services\store\LoginServices;
use app\services\store\SystemStoreStaffServices;
use think\facade\App;
use app\controller\admin\AuthController;
use app\services\store\SystemStoreServices;

/**
 * 门店管理控制器
 * Class SystemStore
 * @package app\controller\admin\v1\store
 */
class SystemStore extends AuthController
{
    /**
     * 构造方法
     * SystemStore constructor.
     * @param App $app
     * @param SystemStoreServices $services
     */
    public function __construct(App $app, SystemStoreServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 获取门店列表1
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            [['keywords', 's'], ''],
            [['type', 'd'], 0],
            ['id', 0, '', 'order_id'],
        ]);
        if ($where['type'] == 'all') $where['type'] = '';
        $where['is_del'] = 0;
        return $this->success($this->services->getStoreList($where, ['id', 'name', 'phone', 'address', 'detailed_address', 'image', 'is_show', 'day_time', 'day_start', 'day_end']));
    }

    /**
     * 获取门店头部
     * @return mixed
     */
    public function get_header()
    {
        $count = $this->services->getStoreData();
        return $this->success(compact('count'));
    }

    /**
     * 门店设置
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_info()
    {
        [$id] = $this->request->getMore([
            [['id', 'd'], 0],
        ], true);
        $info = $this->services->getStoreDispose($id);
        return $this->success(compact('info'));
    }

    /**
     * 位置选择
     * @return mixed
     */
    public function select_address()
    {
        $key = sys_config('tengxun_map_key');
        if (!$key) return $this->fail('提示：前往设置->系统设置->物流配置 配置腾讯地图KEY');
        return $this->success(compact('key'));
    }

    /**
     * 设置单个门店是否显示
     * @param string $is_show
     * @param string $id
     * @return json
     */
    public function set_show($is_show = '', $id = '')
    {
        ($is_show == '' || $id == '') && $this->fail('缺少参数');
        $res = $this->services->update((int)$id, ['is_show' => (int)$is_show]);
        if ($res) {
            /** @var SystemStoreStaffServices $storeStaffServices */
            $storeStaffServices = app()->make(SystemStoreStaffServices::class);
            if ($is_show) {
                $storeStaffServices->update(['store_id' => $id, 'is_del' => 0, 'status' => 0], ['status' => 1]);
            } else {
                $storeStaffServices->update(['store_id' => $id, 'is_del' => 0, 'status' => 1], ['status' => 0]);
            }
            event('store.status', [$id, $is_show]);
            return $this->success('设置成功');
        } else {
            return $this->fail('设置失败');
        }
    }

    /**
     * 获取重置账号密码表单
     * @param $id
     * @return mixed
     */
    public function resetAdminForm($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        return $this->success($this->services->storeAdminAccountForm($id));
    }

    /**
     * 重置门店超级管理员账号密码
     * @param SystemStoreStaffServices $staffServices
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function resetAdmin(SystemStoreStaffServices $staffServices, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $data = $this->request->postMore([
            ['staff_id', 0],
            ['account', ''],
            ['password', ''],
            ['true_password', ''],
        ]);
        $storeInfo = $this->services->getStoreInfo((int)$id);
        if (!$data['password']) {
            return $this->fail('请输入密码');
        }
        if (!$data['true_password']) {
            return $this->fail('请输入确认密码');
        }
        if ($data['password'] != $data['true_password']) {
            return $this->fail('两次输入的密码不正确');
        }
        $staff_data['account'] = $data['account'];
        $staff_data['store_id'] = $storeInfo['id'];
        $staff_data['level'] = 0;
        $staff_data['is_admin'] = 1;
        $staff_data['verify_status'] = 1;
        $staff_data['is_manager'] = 1;
        $staff_data['is_cashier'] = 1;
        $staff_data['add_time'] = time();
        $staff_data['pwd'] = $this->services->passwordHash($data['password']);
        $staffInfo = $staffServices->getOne(['account' => $data['account'], 'is_del' => 0]);
        if ($data['staff_id'] && $staffServices->getCount(['id' => $data['staff_id'], 'is_del' => 0])) {
            if ($staffInfo && $staffInfo['id'] != $data['staff_id']) {
                return $this->fail('该账号已存在');
            }
            if (!$staffServices->update($data['staff_id'], $staff_data)) {
                throw new AdminException('创建门店管理员失败！');
            }
        } else {
            if ($staffInfo) {
                return $this->fail('该账号已存在');
            }
            if (!$staffServices->save($staff_data)) {
                throw new AdminException('创建门店管理员失败！');
            }
        }
        return $this->success('操作成功!');
    }

    /**
     * 保存修改门店信息
     * param int $id
     * */
    public function save($id = 0)
    {
        $data = $this->request->postMore([
            ['erp_shop_id', 0],
            ['name', ''],
            ['introduction', ''],
            ['is_show', 1],
            ['is_store', 1],
            ['image', ''],
            ['phone', ''],
            ['address', ''],
            ['detailed_address', ''],
            ['province', ''],
            ['city', ''],
            ['area', ''],
            ['street', ''],
            ['longitude', ''],
            ['latitude', ''],
            ['day_time', []],
            ['valid_range', 0],
            ['store_account', ''],
            ['store_password', '']
        ]);
        $this->validate($data, \app\validate\admin\merchant\SystemStoreValidate::class, 'save');
        if (!!sys_config('erp_open') && !$data['erp_shop_id']) {
            return $this->fail('开启ERP时,店铺编号必须填写');
        }
        if (!isset($data['longitude']) || !isset($data['latitude'])) {
            return $this->fail('请选择门店位置');
        }
        if (!check_phone($data['phone'])) {
            return $this->fail('请输入正确的手机号');
        }
        if (!$id && (!$data['store_account'] || !$data['store_password'])) {
            return $this->fail('请填写门店管理员账号密码');
        }
        if ($data['is_show'] && (!$data['day_time'] || count($data['day_time']) != 2)) {
            return $this->fail('请选择门店营业时间');
        }
		if (!floatval(trim($data['valid_range']))) {
			return $this->fail('请输入有效的配送范围');
		}
        if ($data['day_time'] && count($data['day_time']) == 2) {
            [$data['day_start'], $data['day_end']] = $data['day_time'];
            $data['day_time'] = implode(' - ', $data['day_time']);
        }
        if ($data['image'] && strstr($data['image'], 'http') === false) {
            $site_url = sys_config('site_url');
            $data['image'] = $site_url . $data['image'];
        }
        $data['address'] = str_replace([' ', '/', '\\'], '', $data['address']);
        $data['detailed_address'] = str_replace([' ', '/', '\\'], '', $data['detailed_address']);
        $staff_data = [
            'staff_name' => $data['name'],
            'avatar' => $data['image'],
            'phone' => $data['phone'],
            'account' => $data['store_account'],
            'pwd' => $data['store_password']
        ];
        $data['valid_range'] = bcmul($data['valid_range'], '1000', 0);
        unset($data['store_account'], $data['store_password']);
        [$id, $is_new] = $this->services->saveStore((int)$id, $data, $staff_data);
        event('store.create', [$data, $id, $is_new]);
        return $this->success('操作成功!');
    }

    /**
     * 删除恢复门店
     * @param $id
     */
    public function delete($id)
    {
        if (!$id) return $this->fail('数据不存在');
        $storeInfo = $this->services->get($id);
        if (!$storeInfo) {
            return $this->fail('数据不存在');
        }
        /** @var SystemStoreStaffServices $storeStaffServices */
        $storeStaffServices = app()->make(SystemStoreStaffServices::class);
        if ($storeInfo->is_del == 1) {
            $storeInfo->is_del = 0;
            if (!$storeInfo->save()) {
                return $this->fail('恢复失败,请稍候再试!');
            } else {
                $storeStaffServices->update(['store_id' => $id, 'is_del' => 1], ['is_del' => 0]);
                return $this->success('恢复门店成功!');
            }
        } else {
            /** @var StoreOrderServices $storeOrderServices */
            $storeOrderServices = app()->make(StoreOrderServices::class);
            $orderCount = $storeOrderServices->count(['store_id' => $id, 'status' => 0]);
            if (!$orderCount) {
                $orderCount = $storeOrderServices->count(['store_id' => $id, 'status' => 1]);
                if (!$orderCount) {
                    $orderCount = $storeOrderServices->count(['store_id' => $id, 'status' => 5]);
                }
            }
            if ($orderCount) {
                return $this->fail('删除失败,该门店还有待处理订单');
            }
            $storeInfo->is_del = 1;
            if (!$storeInfo->save()) {
                return $this->fail('删除失败,请稍候再试!');
            } else {
                $storeStaffServices->update(['store_id' => $id, 'is_del' => 0], ['is_del' => 1]);
                event('store.delete', [$id]);
                return $this->success('删除门店成功!');
            }
        }
    }

    /**
     * 门店登录
     * @param SystemStoreStaffServices $staffServices
     * @param LoginServices $services
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function storeLogin(SystemStoreStaffServices $staffServices, LoginServices $services, $id)
    {
        $storeInfo = $this->services->get($id);
        if (!$storeInfo) {
            return $this->fail('登录的门店不存在');
        }
        $staffAdmin = $staffServices->getOne(['store_id' => $id, 'level' => 0, 'is_admin' => 1]);
        if (!$staffAdmin) {
            return $this->fail('门店超级管理员异常');
        }
        return $this->success($services->login($staffAdmin['account'], '', 'store'));
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function getErpShop()
    {
        return $this->success($this->services->erpShopList());
    }
}
