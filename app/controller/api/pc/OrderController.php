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
namespace app\controller\api\pc;

use app\Request;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\pc\OrderServices;

class OrderController
{
    protected $services;

    public function __construct(OrderServices $services)
    {
        $this->services = $services;
    }

    /**
     * 轮询订单状态
     * @param Request $request
     * @return mixed
     */
    public function checkOrderStatus(Request $request)
    {
        list($order_id, $end_time) = $request->getMore([
            ['order_id', ''],
            ['end_time', ''],
        ], true);
        $data['status'] = $this->services->checkOrderStatus((string)$order_id);
        $time = $end_time - time();
        $data['time'] = $time > 0 ? $time : 0;
        return app('json')->successful($data);
    }

    /**
     * 获取订单列表
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderList(Request $request)
    {
        $where = $request->getMore([
            ['type', '', '', 'status'],
            ['search', '', '', 'real_name'],
        ]);
        $where['uid'] = $request->uid();
        $where['is_del'] = 0;
        $where['is_system_del'] = 0;
        if (!in_array($where['status'], [-1, -2, -3])) $where['pid'] = 0;
        return app('json')->successful($this->services->getOrderList($where));
    }

    /**
     * 获取退货商品列表
     * @param StoreOrderCartInfoServices $services
     * @param $id
     * @return mixed
     */
    public function refundCartInfoList(Request $request, StoreOrderServices $services)
    {
        [$cart_ids, $id] = $request->postMore([
            ['cart_ids', ''],
            ['id', 0],
        ], true);
        if (!$id) {
            return app('json')->fail('缺少发货ID');
        }
        $cart_id = [];
        if ($cart_ids) $cart_id[] = ['cart_id' => $cart_ids];
        return app('json')->success($services->refundCartInfoList((array)$cart_id, (int)$id));
    }

    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function refundList(Request $request, StoreOrderRefundServices $services)
    {
        $where = $request->getMore([
            ['refund_type', '', '', 'refundTypes']
        ]);
        $where['uid'] = $request->uid();
        $where['is_cancel'] = 0;
        $data['list'] = $services->getRefundOrderList($where);
        $data['count'] = $services->count($where);
        return app('json')->successful($data);
    }
}
