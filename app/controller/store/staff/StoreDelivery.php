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
use app\services\order\StoreOrderServices;
use think\facade\App;
use app\controller\store\AuthController;
use app\services\store\DeliveryServiceServices;
use app\services\user\UserWechatuserServices;


/**
 * 配送员
 * Class StoreDelivery
 * @package app\controller\store\staff
 */
class StoreDelivery extends AuthController
{

    /**
     * DeliveryService constructor.
     * @param App $app
     * @param DeliveryServiceServices $services
     */
    public function __construct(App $app, DeliveryServiceServices $services)
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
        $where = $this->request->getMore([
            ['field_key', ''],
            ['keyword', '']
        ]);
        $where['type'] = $this->type;
        $where['store_id'] = $this->storeId;
        $where['is_del'] = 0;
        return app('json')->success($this->services->getServiceList($where));
    }

    /**
     * 配送员新增表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create()
    {
        return app('json')->success($this->services->createStoreDeliveryForm());
    }


    /**
     * 配送员
     * @param $id
     * @return mixed
     */
    public function deliveryDetail($id)
    {
        $id = (int)$id;
        if (!$id) return $this->fail('缺少参数');
        return $this->success($this->services->deliveryDetail($id));
    }


    /**
     * 配送员修改表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function edit()
    {
        [$id] = $this->request->getMore([
            [['id', 'd'], 0],
        ], true);
        $storeDelivery = $this->services->getOne(['id' => $id, 'is_del' => 0]);
        if (!$storeDelivery) {
            return app('json')->fail('配送员不存在');
        }
        return app('json')->success($this->services->updateStoreDeliveryForm($id, $storeDelivery));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function userList(UserWechatuserServices $services)
    {
        $where = $this->request->getMore([
            ['nickname', ''],
            ['data', '', '', 'time'],
            ['type', '', '', 'user_type'],
        ]);
        [$list, $count] = $services->getWhereUserList($where, 'u.nickname,u.uid,u.avatar as headimgurl,w.subscribe,w.province,w.country,w.city,w.sex');
        return app('json')->success(compact('list', 'count'));
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
            ['phone', ''],
            ['nickname', ''],
            ['status', 1],
        ]);
        if ($data['image'] == '') return $this->fail('请选择用户');
        $data['uid'] = $data['image']['uid'];
        if (!$data['nickname']) {
            return app()->fail('请输入配送员名称');
        }
        if (!check_phone($data['phone'])) {
            return app('json')->fail('请输入正确的手机号!');
        }
        if ($this->services->count(['store_id' => $this->storeId, 'phone' => $data['phone'], 'is_del' => 0])) {
            return app('json')->fail('同一个手机号的配送员只能添加一个!');
        }
        $data['avatar'] = $data['image']['image'];
        if ($this->services->count(['uid' => $data['uid'], 'store_id' => $this->storeId, 'is_del' => 0])) {
            return $this->fail('配送员已存在!');
        }
        unset($data['image']);
        $data['type'] = $this->type;
        $data['store_id'] = $this->storeId;
        unset($data['image']);
        $data['add_time'] = time();
        $res = $this->services->save($data);
        if ($res) {
            return app('json')->success('配送员添加成功');
        } else {
            return app('json')->fail('配送员添加失败，请稍后再试');
        }
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
            ['phone', ''],
            ['status', 1],
        ]);
        $delivery = $this->services->get((int)$id);
        if (!$delivery) {
            return app('json')->fail('数据不存在');
        }
        if ($data["nickname"] == '') {
            return app('json')->fail("配送员名称不能为空！");
        }
        if (!$data['phone']) {
            return app('json')->fail("手机号不能为空！");
        }
        if (!check_phone($data['phone'])) {
            return app('json')->fail('请输入正确的手机号!');
        }
        if ($delivery['phone'] != $data['phone'] && $this->services->count(['store_id' => $this->storeId, 'phone' => $data['phone'], 'is_del' => 0])) {
            return app('json')->fail('同一个手机号的配送员只能添加一个!');
        }
        $this->services->update($id, $data);
        return app('json')->success('修改成功!');
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$this->services->update($id, ['is_del' => 1]))
            return app('json')->fail('删除失败,请稍候再试!');
        else
            return app('json')->success('删除成功!');
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function set_status($id, $status)
    {
        if ($status == '' || $id == 0) return app('json')->fail('参数错误');
        $this->services->update($id, ['status' => $status]);
        return app('json')->success($status == 0 ? '关闭成功' : '开启成功');
    }

    /**
     * 获取配送员select
     * @return mixed
     */
    public function getDeliverySelect()
    {
        $where['store_id'] = $this->storeId;
        $where['is_del'] = 0;
        $where['status'] = 1;
        return app('json')->success($this->services->getSelectList($where));
    }

    /**
     * 获取所有配送员列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_delivery_list()
    {
        $data = $this->services->getDeliveryList();
        return $this->success($data);
    }

    /**
     * 获取配送员订单统计列表
     * @param StoreOrderServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function statistics(StoreOrderServices $services, BranchOrderServices $orderServices)
    {
        $where = $this->request->getMore([
            ['delivery_uid', 0],
            ['data', '', '', 'time'],
        ]);
        $where['store_id'] = $this->storeId;
        $where['time'] = $orderServices->timeHandle($where['time']);
        return app('json')->success($services->getDeliveryStatistics($where));
    }

    /**
     * 取配送员订单统计头部图表数据
     * @param StoreOrderServices $services
     * @return mixed
     */
    public function statisticsHeader(StoreOrderServices $services, BranchOrderServices $orderServices)
    {
        [$delivery_uid, $time] = $this->request->getMore([
            ['delivery_uid', 0],
            ['data', '', '', 'time']
        ], true);
        $time = $orderServices->timeHandle($time, true);
        $store_id = $this->storeId;
        return app('json')->success($services->getStatisticsHeader($store_id, $delivery_uid, $time));
    }
}
