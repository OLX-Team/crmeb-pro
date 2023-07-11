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

namespace app\common\controller;


use app\jobs\BatchHandleJob;
use app\Request;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\order\store\WriteOffOrderServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderDeliveryServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderStatusServices;
use app\services\order\StoreOrderTakeServices;
use app\services\order\StoreOrderWriteOffServices;
use app\services\order\StoreOrderPromotionsServices;
use app\services\pay\OrderOfflineServices;
use app\services\other\queue\QueueServices;
use app\services\serve\ServeServices;
use app\services\other\ExpressServices;
use app\services\store\SystemStoreServices;
use app\services\user\UserServices;
use app\validate\admin\order\StoreOrderValidate;
use crmeb\services\SystemConfigService;
use crmeb\traits\MacroTrait;

/**
 * Trait Order
 * @package app\common\controller
 * @property StoreOrderServices $services
 */
trait Order
{
    use MacroTrait;

    /**
     * 获取订单类型数量
     * @param Request $request
     * @return mixed
     */
    public function chart(Request $request)
    {
		$where = $request->getMore([
            ['status', ''],
            ['real_name', ''],
            ['data', '', '', 'time'],
            ['type', ''],
            ['pay_type', ''],
            ['field_key', ''],
        ]);
        $where['type'] = trim($where['type']);
        $where['is_system_del'] = 0;
        if (!in_array($where['status'], [-1, -2, -3])) {
            $where['pid'] = [0, -1];
        }
        $where['store_id'] = 0;
        $where['type'] = trim($where['type'], ' ');
        $data = $this->services->orderCount($where);
        return app('json')->success($data);
    }

    /**
     * 获取订单列表
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function lst(Request $request)
    {
        $where = $request->getMore([
            ['status', ''],
            ['real_name', ''],
            ['is_del', ''],
            ['data', '', '', 'time'],
            ['type', ''],
            ['pay_type', ''],
            ['order', ''],
            ['field_key', ''],
        ]);
        $where['type'] = trim($where['type']);
        $where['is_system_del'] = 0;
        if (!in_array($where['status'], [-1, -2, -3])) {
            $where['pid'] = [0, -1];
        }
        $where['store_id'] = 0;
        $where['type'] = trim($where['type'], ' ');
        return app('json')->success($this->services->getOrderList($where, ['*'], ['split' => function ($query) {
            $query->field('id,pid');
        }, 'pink', 'invoice']));
    }

    /**
     * 获取订单拆分子订单列表
     * @return mixed
     */
    public function split_order(Request $request, $id)
    {
		[$status] = $request->getMore([
            ['status', -1]
        ], true);
        if (!$id) {
            return app('json')->fail('缺少订单ID');
        }
		$where = ['pid' => $id, 'is_system_del' => 0];
		if (!$this->services->count($where)) {
			$where = ['id' => $id, 'is_system_del' => 0];
		}
        return app('json')->success($this->services->getSplitOrderList($where, ['*'], ['split', 'pink', 'invoice']));
    }

    /**
     * 核销码核销
     * @param Request $request
     * @param StoreOrderWriteOffServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function write_order(Request $request, StoreOrderWriteOffServices $services)
    {
        [$code, $confirm] = $request->getMore([
            ['code', ''],
            ['confirm', 0]
        ], true);
        if (!$code) return app('json')->fail('Lack of write-off code');
        $orderInfo = $services->writeOffOrder($code, (int)$confirm);
        if ($confirm == 0) {
            return app('json')->success('验证成功', $orderInfo);
        }
        return app('json')->success('Write off successfully');
    }

    /**
     * 订单号核销
     * @param StoreOrderWriteOffServices $services
     * @param $order_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function write_update(StoreOrderWriteOffServices $services, $order_id)
    {
        $orderInfo = $this->services->getOne(['order_id' => $order_id, 'is_del' => 0]);
        if ($orderInfo->shipping_type != 2 && $orderInfo->delivery_type != 'send') {
            return app('json')->fail('核销订单未查到!');
        } else {
            if (!$orderInfo->verify_code) {
                return app('json')->fail('Lack of write-off code');
            }
            $orderInfo = $services->writeOffOrder($orderInfo->verify_code, 1);
            if ($orderInfo) {
                return app('json')->success('Write off successfully');
            } else {
                return app('json')->fail('核销失败!');
            }
        }
    }

    /**
     * 修改支付金额等
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function edit($id)
    {
        if (!$id) return app('json')->fail('Data does not exist!');
        return app('json')->success($this->services->updateForm($id));
    }

    /**
     * 修改订单
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        if (!$id) return app('json')->fail('Missing order ID');
        $data = $this->request->postMore([
            ['order_id', ''],
            ['total_price', 0],
            ['total_postage', 0],
            ['pay_price', 0],
            ['pay_postage', 0],
            ['gain_integral', 0],
        ]);

        $this->validate($data, StoreOrderValidate::class);

        if ($data['total_price'] < 0) return app('json')->fail('Please enter the total price');
        if ($data['pay_price'] < 0) return app('json')->fail('Please enter the actual payment amount');

        $this->services->updateOrder((int)$id, $data);
        return app('json')->success('Modified success');
    }

    /**
     * 获取快递公司
     * @param Request $request
     * @param ExpressServices $services
     * @return mixed
     */
    public function express(Request $request, ExpressServices $services)
    {
        [$status] = $request->getMore([
            ['status', ''],
        ], true);
        if ($status != '' && $status != 'undefined') $data['status'] = (int)$status;
        $data['is_show'] = 1;
        return app('json')->success($services->express($data));
    }

    /**
     * 批量删除用户已经删除的订单
     * @param Request $request
     * @return mixed
     */
    public function del_orders(Request $request)
    {
        [$ids, $all, $where] = $request->postMore([
            ['ids', []],
            ['all', 0],
            ['where', []],
        ], true);
        if (!count($ids) && $all == 0) return app('json')->fail('请选择需要删除的订单');
        if ($this->services->getOrderIdsCount($ids) && $all == 0) return app('json')->fail('您选择的的订单存在用户未删除的订单');
        if ($all == 0 && $this->services->batchUpdate($ids, ['is_system_del' => 1])) return app('json')->success('删除成功');
        if ($all == 1) $ids = [];
        $type = 6;// 订单删除
        $where['status'] = -4;
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $queueService->setQueueData($where, 'id', $ids, $type);
        //加入队列
        BatchHandleJob::dispatch([false, $type]);
        return app('json')->success('后台程序已执行批量删除任务!');

    }

    /**
     * 删除订单
     * @param $id
     * @return mixed
     */
    public function del($id)
    {
        if (!$id || !($orderInfo = $this->services->get($id)))
            return app('json')->fail('订单不存在');
        if (!$orderInfo->is_del)
            return app('json')->fail('订单用户未删除无法删除');
        $orderInfo->is_system_del = 1;
        if ($orderInfo->save())
            return app('json')->success('SUCCESS');
        else
            return app('json')->fail('ERROR');
    }

    /**
     * 订单发送货
     * @param Request $request
     * @param StoreOrderDeliveryServices $services
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function update_delivery(Request $request, StoreOrderDeliveryServices $services, $id)
    {
        $data = $request->postMore([
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
            return app('json')->fail('缺少发货ID');
        }
        $services->delivery((int)$id, $data);
        return app('json')->success('SUCCESS');
    }

    /**
     * 订单拆单发送货
     * @param Request $request
     * @param StoreOrderDeliveryServices $services
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function split_delivery(Request $request, StoreOrderDeliveryServices $services, $id)
    {
        $data = $request->postMore([
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
            return app('json')->fail('缺少发货ID');
        }
        if (!$data['cart_ids']) {
            return app('json')->fail('请选择发货商品');
        }
        foreach ($data['cart_ids'] as $cart) {
            if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num']) {
                return app('json')->fail('请重新选择发货商品，或发货件数');
            }
        }
        $services->splitDelivery((int)$id, $data);
        return app('json')->success('SUCCESS');
    }

    /**
     * 获取订单可拆分发货商品列表
     * @param StoreOrderCartInfoServices $services
     * @param $id
     * @return mixed
     */
    public function split_cart_info(StoreOrderCartInfoServices $services, $id)
    {
        if (!$id) {
            return app('json')->fail('缺少发货ID');
        }
        return app('json')->success($services->getSplitCartList((int)$id));
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
            return app('json')->fail('核销订单未查到!');
        }
        [$cart_ids] = $request->postMore([
            ['cart_ids', []]
        ], true);
        if ($cart_ids) {
            foreach ($cart_ids as $cart) {
                if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num']) {
                    return app('json')->fail('请重新选择发货商品，或发货件数');
                }
            }
        }
        return app('json')->success('核销成功', $writeOffOrderServices->writeoffOrder(0, $orderInfo->toArray(), $cart_ids, 1, (int)$this->storeStaffId));
    }

    /**
     * 确认收货
     * @param StoreOrderTakeServices $services
     * @param $id
     * @return mixed
     */
    public function take_delivery(StoreOrderTakeServices $services, $id)
    {
        if (!$id) return app('json')->fail('缺少参数');
        $order = $this->services->get($id);
        if (!$order)
            return app('json')->fail('Data does not exist!');
        if ($order['status'] == 2)
            return app('json')->fail('不能重复收货!');
        if ($order['paid'] == 1 && $order['status'] == 1)
            $data['status'] = 2;
        else if ($order['pay_type'] == 'offline')
            $data['status'] = 2;
        else
            return app('json')->fail('请先发货或者送货!');

        if (!$this->services->update($id, $data)) {
            return app('json')->fail('收货失败,请稍候再试!');
        } else {
            $services->storeProductOrderUserTakeDelivery($order);
            return app('json')->success('收货成功');
        }
    }


    /**
     * 获取配置信息
     * @return mixed
     */
    public function getDeliveryInfo()
    {
        $data = SystemConfigService::more(['config_export_temp_id', 'config_export_to_name', 'config_export_id', 'config_export_to_tel', 'config_export_to_address', 'config_export_open']);
        return app('json')->success([
            'express_temp_id' => $data['config_export_temp_id'] ?? '',
            'id' => $data['config_export_id'] ?? '',
            'to_name' => $data['config_export_to_name'] ?? '',
            'to_tel' => $data['config_export_to_tel'] ?? '',
            'to_add' => $data['config_export_to_address'] ?? '',
            'export_open' => (int)($data['config_export_open'] ?? 0) ? true : false
        ]);
    }

    /**
     * 订单主动退款表单生成
     * @param StoreOrderRefundServices $services
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function refund(StoreOrderRefundServices $services, $id)
    {
        if (!$id) {
            return app('json')->fail('Data does not exist!');
        }
        return app('json')->success($services->refundOrderForm((int)$id, 'order'));
    }

    /**
     * 订单主动退款
     * @param Request $request
     * @param StoreOrderRefundServices $services
     * @param $id
     * @return mixed
     */
    public function update_refund(Request $request, StoreOrderRefundServices $services, StoreOrderCreateServices $storeOrderCreateServices, StoreOrderCartInfoServices $storeOrderCartInfoServices, $id)
    {
        $data = $request->postMore([
            ['refund_price', 0],
            ['type', 1]
        ]);
        if (!$id) {
            return $this->fail('Data does not exist!');
        }
        $order = $this->services->get($id);
        if (!$order) {
            return $this->fail('Data does not exist!');
        }
        if ($services->count(['store_order_id' => $id, 'refund_type' => [0, 1, 2, 4, 5], 'is_cancel' => 0, 'is_del' => 1])) {
            return $this->fail('请先处理售后申请');
        }
        //0元退款
        if ($order['pay_price'] == 0 && in_array($order['refund_status'], [0, 1])) {
            $refund_price = 0;
        } else {
            if ($order['pay_price'] == $order['refund_price']) {
                return $this->fail('已退完支付金额!不能再退款了');
            }
            if (!$data['refund_price']) {
                return $this->fail('请输入退款金额');
            }
            $refund_price = $data['refund_price'];
            $data['refund_price'] = bcadd($data['refund_price'], $order['refund_price'], 2);
            $bj = bccomp((string)$order['pay_price'], (string)$data['refund_price'], 2);
            if ($bj < 0) {
                return $this->fail('退款金额大于支付金额，请修改退款金额');
            }
        }
        if ($data['type'] == 1) {
            $data['refund_status'] = 2;
            $data['refund_type'] = 6;
        } else if ($data['type'] == 2) {
            $data['refund_status'] = 0;
            $data['refund_type'] = 3;
        }
        $type = $data['type'];
        //拒绝退款
        if ($type == 2) {
            $this->services->update((int)$order['id'], ['refund_status' => 0, 'refund_type' => 3]);
            return app('json')->successful('修改退款状态成功!');
        } else {
            unset($data['type']);
            $refund_data['pay_price'] = $order['pay_price'];
            $refund_data['refund_price'] = $refund_price;
            if ($order['refund_price'] > 0) {
                mt_srand();
                $refund_data['refund_id'] = $order['order_id'] . rand(100, 999);
            }
            //主动退款清楚原本退款单
            $services->delete(['store_order_id' => $id]);
            //生成退款订单
            $refundOrderData['uid'] = $order['uid'];
            $refundOrderData['store_id'] = $order['store_id'];
            $refundOrderData['store_order_id'] = $id;
            $refundOrderData['refund_num'] = $order['total_num'];
            $refundOrderData['refund_type'] = $data['refund_type'];
            $refundOrderData['refund_price'] = $order['pay_price'];
            $refundOrderData['refunded_price'] = $refund_price;
            $refundOrderData['refund_reason'] = '管理员手动退款';
            $refundOrderData['order_id'] = $storeOrderCreateServices->getNewOrderId('');
            $refundOrderData['refunded_time'] = time();
            $refundOrderData['add_time'] = time();
            $cartInfos = $storeOrderCartInfoServices->getCartColunm(['oid' => $id], 'id,cart_id,cart_num,cart_info');
            foreach ($cartInfos as &$cartInfo) {
                $cartInfo['cart_info'] = is_string($cartInfo['cart_info']) ? json_decode($cartInfo['cart_info'], true) : $cartInfo['cart_info'];
            }
            $refundOrderData['cart_info'] = json_encode(array_column($cartInfos, 'cart_info'));
            $res = $services->save($refundOrderData);

            //修改订单退款状态
            if ($services->agreeRefund($res->id, $refund_data)) {
                $this->services->update($id, $data);
                return app('json')->success('退款成功');
            } else {
                $services->storeProductOrderRefundYFasle((int)$id, $refund_price);
                return app('json')->fail('退款失败');
            }
        }
    }

    /**
     * 订单详情
     * @param $id
     * @return mixed
     */
    public function order_info($id)
    {
        if (!$id || !($orderInfo = $this->services->get($id, ['*'], ['invoice', 'virtual', 'pink', 'refund']))) {
            return app('json')->fail('订单不存在');
        }
        $userInfo = ['spread_uid' => '', 'spread_name' => '无'];
        if ($orderInfo['uid']) {
            /** @var UserServices $services */
            $services = app()->make(UserServices::class);
            $userInfo = $services->getUserWithTrashedInfo($orderInfo['uid']);
            if (!$userInfo) return app('json')->fail('用户信息不存在');
            $userInfo = $userInfo->hidden(['pwd', 'add_ip', 'last_ip', 'login_type']);
            $userInfo = $userInfo->toArray();
            $userInfo['spread_name'] = '无';
            if ($orderInfo['spread_uid']) {
                $spreadName = $services->value(['uid' => $orderInfo['spread_uid']], 'nickname');
                if ($spreadName) {
                    $userInfo['spread_name'] = $orderInfo['uid'] == $orderInfo['spread_uid'] ? $spreadName . '(自购)' : $spreadName;
                    $userInfo['spread_uid'] = $orderInfo['spread_uid'];
                } else {
                    $userInfo['spread_uid'] = '';
                }
            } else {
                $userInfo['spread_uid'] = '';
            }
        }
        $orderInfo = is_object($orderInfo) ? $orderInfo->toArray() : $orderInfo;
        $orderInfo = $this->services->tidyOrder($orderInfo, true, true);
        $_status = $orderInfo['_status'];
        [$pink_name, $color] = $this->services->tidyOrderType($orderInfo);
        $orderInfo['pink_name'] = $pink_name;
        $orderInfo['store_order_sn'] = $orderInfo['pid'] ? $this->services->value(['id' => $orderInfo['pid']], 'order_id') : '';
        //核算优惠金额
        $vipTruePrice = 0;
        foreach ($orderInfo['cartInfo'] ?? [] as $cart) {
            $vipTruePrice = bcadd((string)$vipTruePrice, (string)$cart['vip_sum_truePrice'], 2);
        }
        $orderInfo['vip_true_price'] = $vipTruePrice;
        $orderInfo['total_price'] = floatval(bcsub((string)$orderInfo['total_price'], (string)$vipTruePrice, 2));
        //优惠活动优惠详情
        /** @var StoreOrderPromotionsServices $storeOrderPromotiosServices */
        $storeOrderPromotiosServices = app()->make(StoreOrderPromotionsServices::class);
        $orderInfo['promotions_detail'] = $storeOrderPromotiosServices->getOrderPromotionsDetail((int)$orderInfo['id']);
        if ($orderInfo['give_coupon']) {
            $couponIds = is_string($orderInfo['give_coupon']) ? explode(',', $orderInfo['give_coupon']) : $orderInfo['give_coupon'];
            /** @var StoreCouponIssueServices $couponIssueService */
            $couponIssueService = app()->make(StoreCouponIssueServices::class);
            $orderInfo['give_coupon'] = $couponIssueService->getColumn([['id', 'IN', $couponIds]], 'id,coupon_title');
        }
        $orderInfo['_store_name'] = '';
        if ($orderInfo['store_id'] && in_array($orderInfo['shipping_type'], [2, 4])) {
            /** @var  $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $orderInfo['_store_name'] = $storeServices->value(['id' => $orderInfo['store_id']], 'name');
        }

        $orderInfo = $this->services->tidyOrderList([$orderInfo])[0];
        $orderInfo['_status_new'] = $orderInfo['_status'];
        $orderInfo['_status'] = $_status;
        $refund_num = array_sum(array_column($orderInfo['refund'], 'refund_num'));
        $cart_num = 0;
        foreach ($orderInfo['_info'] as $items) {
			if (isset($items['cart_info']['is_gift']) && $items['cart_info']['is_gift']) continue;
            $cart_num += $items['cart_info']['cart_num'];
        }
        $orderInfo['is_all_refund'] = $refund_num == $cart_num ? true : false;
        return app('json')->success(compact('orderInfo', 'userInfo'));
    }

    /**
     * 查询物流信息
     * @param ExpressServices $services
     * @param $id
     * @return mixed
     */
    public function get_express(ExpressServices $services, $id)
    {
        if (!$id || !($orderInfo = $this->services->get($id)))
            return app('json')->fail('订单不存在');
        if ($orderInfo['delivery_type'] != 'express' || !$orderInfo['delivery_id'])
            return app('json')->fail('该订单不存在快递单号');

        $cacheName = $orderInfo['order_id'] . $orderInfo['delivery_id'];

        $data['delivery_name'] = $orderInfo['delivery_name'];
        $data['delivery_id'] = $orderInfo['delivery_id'];
        $data['result'] = $services->query($cacheName, $orderInfo['delivery_id'], $orderInfo['delivery_code'] ?? null);
        return app('json')->success($data);
    }


    /**
     * 获取修改配送信息表单结构
     * @param StoreOrderDeliveryServices $services
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function distribution(StoreOrderDeliveryServices $services, $id)
    {
        if (!$id) {
            return app('json')->fail('订单不存在');
        }
        return app('json')->success($services->distributionForm((int)$id));
    }

    /**
     * 修改配送信息
     * @param StoreOrderDeliveryServices $services
     * @param ExpressServices $expressServices
     * @param $id
     * @return mixed
     */
    public function update_distribution(StoreOrderDeliveryServices $services, ExpressServices $expressServices, $id)
    {
        $data = $this->request->postMore([['delivery_name', ''], ['delivery_id', '']]);
        if (!$id) return app('json')->fail('Data does not exist!');
        $express = $expressServices->getOne(['name' => $data['delivery_name']], 'id,name,code');
        if (!$express) {
            return app('json')->fail('Data does not exist!');
        }
        $data['delivery_code'] = $express['code'];
        $services->updateDistribution($id, $data);
        return app('json')->success('Modified success');
    }

    /**
     * 不退款表单结构
     * @param StoreOrderRefundServices $services
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function no_refund(StoreOrderRefundServices $services, $id)
    {
        if (!$id) return app('json')->fail('Data does not exist!');
        return app('json')->success($services->noRefundForm((int)$id));
    }

    /**
     * 订单不退款
     * @param StoreOrderRefundServices $services
     * @param $id
     * @return mixed
     */
    public function update_un_refund(StoreOrderRefundServices $services, $id)
    {
        if (!$id || !($orderRefundInfo = $services->get($id)))
            return app('json')->fail('订单不存在');
        [$refund_reason] = $this->request->postMore([['refund_reason', '']], true);
        if (!$refund_reason) {
            return app('json')->fail('请输入不退款原因');
        }
        $refundData = [
            'refuse_reason' => $refund_reason,
            'refund_type' => 3,
            'refunded_time' => time()
        ];
        //拒绝退款处理
        $services->refuseRefund((int)$id, $refundData, $orderRefundInfo);

        return app('json')->success('Modified success');
    }

    /**
     * 线下支付
     * @param OrderOfflineServices $services
     * @param $id
     * @return mixed
     */
    public function pay_offline(OrderOfflineServices $services, $id)
    {
        if (!$id) return app('json')->fail('缺少参数');
        $res = $services->orderOffline((int)$id);
        if ($res) {
            return app('json')->success('Modified success');
        } else {
            return app('json')->fail('Modification failed');
        }
    }

    /**
     * 退积分表单获取
     * @param StoreOrderRefundServices $services
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function refund_integral(StoreOrderRefundServices $services, $id)
    {
        if (!$id)
            return app('json')->fail('订单不存在');
        return app('json')->success($services->refundIntegralForm((int)$id));
    }

    /**
     * 退积分保存
     * @param $id
     * @return mixed
     */
    public function update_refund_integral(StoreOrderRefundServices $services, $id)
    {
        [$back_integral] = $this->request->postMore([['back_integral', 0]], true);
        if (!$id || !($orderInfo = $this->services->get($id))) {
            return app('json')->fail('订单不存在');
        }
        if ($orderInfo->is_del) {
            return app('json')->fail('订单已删除无法退积分');
        }
        if ($back_integral <= 0) {
            return app('json')->fail('请输入积分');
        }
        if ($orderInfo['use_integral'] == $orderInfo['back_integral']) {
            return app('json')->fail('已退完积分!不能再积分了');
        }

        $data['back_integral'] = bcadd((string)$back_integral, (string)$orderInfo['back_integral'], 2);
        $bj = bccomp((string)$orderInfo['use_integral'], (string)$data['back_integral'], 2);
        if ($bj < 0) {
            return app('json')->fail('退积分大于支付积分，请修改退积分');
        }
        //积分退款处理
        $orderInfo->back_integral = $data['back_integral'];
        if ($services->refundIntegral($orderInfo, $back_integral)) {
            return app('json')->success('退积分成功');
        } else {
            return app('json')->fail('退积分失败');
        }
    }

    /**
     * 修改备注
     * @param $id
     * @return mixed
     */
    public function remark($id)
    {
        $data = $this->request->postMore([['remark', '']]);
        if (!$data['remark'])
            return app('json')->fail('请输入要备注的内容');
        if (!$id)
            return app('json')->fail('缺少参数');

        if (!$order = $this->services->get($id)) {
            return app('json')->fail('修改的订单不存在!');
        }
        $order->remark = $data['remark'];
        if ($order->save()) {
            return app('json')->success('备注成功');
        } else
            return app('json')->fail('备注失败');
    }

    /**
     * 获取订单状态列表并分页
     * @param $id
     * @return mixed
     */
    public function status(StoreOrderStatusServices $services, $id)
    {
        if (!$id) return app('json')->fail('缺少参数');
        return app('json')->success($services->getStatusList(['oid' => $id])['list']);
    }

    /**
     * 易联云打印机打印
     * @param $id
     * @return mixed
     */
    public function order_print($id)
    {
        if (!$id) return app('json')->fail('缺少参数');
        $order = $this->services->get($id);
        if (!$order) {
            return app('json')->fail('订单没有查到,无法打印!');
        }
        $res = $this->services->orderPrint($order, $order->cart_id);
        if ($res) {
            return app('json')->success('打印成功');
        } else {
            return app('json')->fail('打印失败');
        }
    }

    /**
     * 电子面单模板
     * @param $com
     * @return mixed
     */
    public function expr_temp(ServeServices $services, $com)
    {
        if (!$com) {
            return app('json')->fail('快递公司编号缺失');
        }
        $list = $services->express()->temp($com);
        return app('json')->success($list);
    }

    /**
     * 获取模板
     * @param ServeServices $services
     * @return mixed
     */
    public function express_temp(ServeServices $services)
    {
        $data = $this->request->getMore([['com', '']]);
        $tpd = $services->express()->temp($data['com']);
        return app('json')->success($tpd['data']);
    }

    /**
     * 订单发货后打印电子面单
     * @param $orderId
     * @param StoreOrderDeliveryServices $storeOrderDeliveryServices
     * @return mixed
     */
    public function order_dump($order_id, StoreOrderDeliveryServices $storeOrderDeliveryServices)
    {
        return app('json')->success($storeOrderDeliveryServices->orderDump($order_id));
    }

    /**
     * 手动批量发货
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function hand_batch_delivery()
    {
        $data = $this->request->getMore([
            ['file', ""]
        ]);
        if (!$data['file']) return app('json')->fail('请上传文件');
        $file = public_path() . substr($data['file'], 1);
        $type = 7;//手动批量发货
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $expreData = $this->services->readExpreExcel($file, 2);
        $queueId = $queueService->setQueueData([], false, $expreData, $type);
        $data['queueType'] = $type;
        $data['cacheType'] = 3;
        $data['type'] = 1;
        $data['queueId'] = $queueId ? $queueId : 0;
        $this->services->adminQueueOrderDo($data);
        return app('json')->success('后台程序已执行批量发货任务!');
    }

    /**
     * 批量手动以外发货
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function other_batch_delivery()
    {
        $data = $this->request->postMore([
            ['where', []],
            ['ids', []],
            ['express_record_type', 1],
            ['type', 1],
            ['delivery_name', ''],//快递公司名称
            ['delivery_id', ''],//快递单号
            ['delivery_code', ''],//快递公司编码
            ['all', 0],//发货记录类型
            ['express_temp_id', ""],//电子面单模板
            ['to_name', ''],//寄件人姓名
            ['to_tel', ''],//寄件人电话
            ['to_addr', ''],//寄件人地址

            ['sh_delivery_name', ''],//送货人姓名
            ['sh_delivery_id', ''],//送货人电话
            ['sh_delivery_uid', ''],//送货人ID

            ['fictitious_content', '']//虚拟发货内容
        ]);
        if ($data['all'] == 0 && empty($data['ids'])) return app('json')->fail('请选择需要发货的订单');
        if ($data['express_record_type'] == 2 && !sys_config('config_export_open', 0)) return app('json')->fail('请先在系统设置中打开单子面单打印开关');
        if ($data['all'] == 1) $data['ids'] = [];
        if ($data['type'] == 1) {//批量打印电子面单
            $data['queueType'] = 8;
            $data['cacheType'] = 4;
        }
        if ($data['type'] == 2) {//批量送货
            $data['queueType'] = 9;
            $data['cacheType'] = 5;
        }
        if ($data['type'] == 3) {//批量虚拟
            $data['queueType'] = 10;
            $data['cacheType'] = 6;
        }
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $queueId = $queueService->setQueueData($data['where'], 'id', $data['ids'], $data['queueType']);
        $data['queueId'] = $queueId ? $queueId : 0;
        /** @var StoreOrderDeliveryServices $deliveryService */
        $this->services->adminQueueOrderDo($data);
        return app('json')->success('后台程序已执行批量发货任务');
    }

}
