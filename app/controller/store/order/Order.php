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

namespace app\controller\store\order;


use app\controller\store\AuthController;
use app\Request;
use app\services\other\export\ExportServices;
use app\services\order\OtherOrderServices;
use app\services\order\store\WriteOffOrderServices;
use app\services\order\StoreOrderDeliveryServices;
use app\services\order\StoreOrderServices;
use app\services\store\DeliveryServiceServices;
use app\services\user\UserRechargeServices;
use think\facade\App;
use \app\common\controller\Order as CommonOrder;

/**
 * Class Order
 * @package app\controller\store\order
 * @property Request $request
 */
class Order extends AuthController
{

    use CommonOrder;

    /**
     * @var StoreOrderServices
     */
    protected $services;

    /**
     * Order constructor.
     * @param App $app
     * @param StoreOrderServices $services
     */
    public function __construct(App $app, StoreOrderServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 订单列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['type', ''],
            ['status', ''],
            ['time', ''],
            ['staff_id', ''],
            ['real_name', '']
        ]);
        $where['is_system_del'] = 0;
        $where['store_id'] = $this->storeId;
        if (!$where['real_name'] && !in_array($where['status'], [-1, -2, -3])) {
            $where['pid'] = 0;
        }
        return $this->success($this->services->getOrderList($where, ['*'], ['split' => function ($query) {
            $query->field('id,pid');
        }, 'pink', 'invoice', 'storeStaff']));
    }

    /**
     * 获取订单类型数量
     * @return mixed
     */
    public function chart()
    {
        $where = $this->request->getMore([
            ['data', '', '', 'time'],
            [['type', 'd'], 0],
        ]);
        $where['store_id'] = $this->storeId;
        $data = $this->services->orderStoreCount($where);
        return $this->success($data);
    }

    /**
     * 获取头部统计数据
     * @param UserRechargeServices $services
     * @param OtherOrderServices $orderServices
     * @return mixed
     */
    public function header(UserRechargeServices $services, OtherOrderServices $orderServices)
    {
        $data = $this->services->getStoreOrderHeader($this->storeId);
        $data['recharg'] = $services->getRechargeCount($this->storeId);
        $data['vip'] = $orderServices->getvipOrderCount($this->storeId);
        return $this->success($data);
    }

    /**
     * 获取配置信息
     * @return mixed
     */
    public function getDeliveryInfo()
    {
        return $this->success([
            'express_temp_id' => store_config($this->storeId, 'store_config_export_temp_id'),
            'id' => store_config($this->storeId, 'store_config_export_id'),
            'to_name' => store_config($this->storeId, 'store_config_export_to_name'),
            'to_tel' => store_config($this->storeId, 'store_config_export_to_tel'),
            'to_add' => store_config($this->storeId, 'store_config_export_to_address'),
            'export_open' => (int)store_config($this->storeId, 'store_config_export_open') ? true : false
        ]);
    }

    /**
     * 订单导出
     * @param UserRechargeServices $services
     * @param ExportServices $exportServices
     * @param OtherOrderServices $otherOrderServices
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function export(UserRechargeServices $services, ExportServices $exportServices, OtherOrderServices $otherOrderServices, $type)
    {
        switch ((int)$type) {
            case 1:
                $where_tmp = $this->request->postMore([
                    ['status', ''],
                    ['real_name', ''],
                    ['time', ''],
                    ['type', ''],
                    ['orderType', ''],
                    ['ids', '']
                ]);
                $orderType = $where_tmp['orderType'];
                unset($where_tmp['orderType']);
                $where['status'] = $where_tmp['status'];
                $where['real_name'] = $where_tmp['real_name'];
                $where['time'] = $where_tmp['time'];
                $where['type'] = $orderType;
                $with = [];
                if ($where_tmp['ids']) {
                    $where['id'] = explode(',', $where_tmp['ids']);
                }
                $where['is_system_del'] = 0;
                if ($orderType == 7 || $orderType == 5) $where['pid'] = 0;
                $where['store_id'] = $this->storeId;
                $data = $this->services->getExportList($where, $with, $exportServices->limit);
                return $this->success($exportServices->storeOrder($data, ''));
            case 2:
                $where = $this->request->postMore([
                    ['data', ''],
                    ['paid', 1],
                    ['nickname', ''],
                    ['excel', '1'],
                    ['staff_id', ''],
                ]);
                $where['store_id'] = $this->storeId;
                $data = $services->getRechargeList($where, '*', $exportServices->limit);
                return $this->success($exportServices->userRecharge($data['list'] ?? []));
            case 3:
                $where = $this->request->postMore([
                    ['name', ""],
                    ['add_time', ""],
                    ['member_type', ""],
                    ['pay_type', ""],
                    ['staff_id', ''],
                ]);
                $where['store_id'] = $this->storeId;
                $data = $otherOrderServices->getMemberRecord($where, $exportServices->limit);
                return $this->success($exportServices->vipOrder($data['list'] ?? []));
            default:
                return $this->fail('导出类型错误');
        }
    }

    /**
     * @param DeliveryServiceServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDeliveryList(DeliveryServiceServices $services)
    {
        return $this->success($services->getDeliveryList(2, $this->storeId));
    }

    /**
     * 获取核销订单商品列表
     * @param Request $request
     * @param WriteOffOrderServices $writeOffOrderServices
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderCartInfo(Request $request, WriteOffOrderServices $writeOffOrderServices)
    {
        [$oid] = $request->postMore([
            ['oid', '']
        ], true);
        return app('json')->success($writeOffOrderServices->getOrderCartInfo(0, (int)$oid, 1, (int)$this->storeStaffId));
    }

    /**
     * 核销订单
     * @param Request $request
     * @param WriteOffOrderServices $writeOffOrderServices
     * @param $order_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function wirteoff(Request $request, WriteOffOrderServices $writeOffOrderServices, $order_id)
    {
        $orderInfo = $this->services->getOne(['order_id' => $order_id, 'is_del' => 0], '*', ['pink']);
        if (!$orderInfo) {
            return $this->fail('核销订单未查到!');
        }
        [$cart_ids] = $request->postMore([
            ['cart_ids', []]
        ], true);
        if ($cart_ids) {
            foreach ($cart_ids as $cart) {
                if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num'] || $cart['cart_num'] <= 0) {
                    return $this->fail('请重新选择发货商品，或发货件数');
                }
            }
        }
        return app('json')->success('核销成功', $writeOffOrderServices->writeoffOrder(0, $orderInfo->toArray(), $cart_ids, 1, (int)$this->storeStaffId));
    }

    /**
     * 订单发送货
     * @param $id 订单id
     * @return mixed
     */
    public function update_delivery($id, StoreOrderDeliveryServices $services)
    {
        $data = $this->request->postMore([
            ['type', 1],
            ['delivery_name', ''],//快递公司名称
            ['delivery_id', ''],//快递单号
            ['delivery_code', ''],//快递公司编码

            ['express_record_type', 2],//发货记录类型
            ['express_temp_id', ""],//电子面单模板
            ['to_name', ''],//寄件人姓名
            ['to_tel', ''],//寄件人电话
            ['to_addr', ''],//寄件人地址

            ['sh_delivery_name', ''],//送货人姓名
            ['sh_delivery_id', ''],//送货人电话
            ['sh_delivery_uid', ''],//送货人ID

            ['fictitious_content', '']//虚拟发货内容
        ]);
        if (!$id) {
            return $this->fail('缺少发货ID');
        }
        $services->delivery((int)$id, $data, (int)$this->storeStaffId);
        return $this->success('SUCCESS');
    }

    /**
     * 订单拆单发送货
     * @param $id 订单id
     * @return mixed
     */
    public function split_delivery($id, StoreOrderDeliveryServices $services)
    {
        $data = $this->request->postMore([
            ['type', 1],
            ['delivery_name', ''],//快递公司名称
            ['delivery_id', ''],//快递单号
            ['delivery_code', ''],//快递公司编码

            ['express_record_type', 2],//发货记录类型
            ['express_temp_id', ""],//电子面单模板
            ['to_name', ''],//寄件人姓名
            ['to_tel', ''],//寄件人电话
            ['to_addr', ''],//寄件人地址

            ['sh_delivery_name', ''],//送货人姓名
            ['sh_delivery_id', ''],//送货人电话
            ['sh_delivery_uid', ''],//送货人ID

            ['fictitious_content', ''],//虚拟发货内容

            ['cart_ids', []]
        ]);
        if (!$id) {
            return $this->fail('缺少发货ID');
        }
        if (!$data['cart_ids']) {
            return $this->fail('请选择发货商品');
        }
        foreach ($data['cart_ids'] as $cart) {
            if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num']) {
                return $this->fail('请重新选择发货商品，或发货件数');
            }
        }
        $services->splitDelivery((int)$id, $data, (int)$this->storeStaffId);
        return $this->success('SUCCESS');
    }
}
