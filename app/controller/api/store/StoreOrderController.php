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
namespace app\controller\api\store;

use app\Request;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\activity\combination\StorePinkServices;
use app\services\activity\promotions\StorePromotionsServices;
use app\services\order\StoreOrderPromotionsServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderDeliveryServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\order\store\BranchOrderServices;
use app\services\order\store\WriteOffOrderServices;
use app\services\pay\OrderOfflineServices;
use app\services\serve\ServeServices;
use app\services\store\SystemStoreStaffServices;
use app\services\user\UserRechargeServices;
use app\services\user\UserServices;
use app\services\other\ExpressServices;

/**
 * 门店订单类
 * Class StoreOrderController
 * @package app\controller\api\store
 */
class StoreOrderController
{
    /**
     * @var BranchOrderServices
     */
    protected $services;

    /**
     * @var int
     */
    protected $uid;
    /**
     * 门店店员信息
     * @var array
     */
    protected $staffInfo;
    /**
     * 门店id
     * @var int|mixed
     */
    protected $store_id;

    /**
     * 门店店员ID
     * @var int|mixed
     */
    protected $staff_id;


    /**
     * StoreOrderController constructor.
     * @param BranchOrderServices $services
     */
    public function __construct(BranchOrderServices $services, Request $request)
    {
        $this->services = $services;
        $this->uid = (int)$request->uid();
    }

    protected function getStaffInfo()
    {
        /** @var SystemStoreStaffServices $staffServices */
        $staffServices = app()->make(SystemStoreStaffServices::class);
        $this->staffInfo = $staffServices->getStaffInfoByUid($this->uid)->toArray();
        $this->store_id = (int)$this->staffInfo['store_id'] ?? 0;
        $this->staff_id = (int)$this->staffInfo['id'] ?? 0;
    }

    /**
     * 订单数据统计
     * @param Request $request
     * @return mixed
     */
    public function statistics()
    {
        $this->getStaffInfo();
        $store_id = $this->store_id;
        $data = $this->services->getOrderData($store_id);
        return app('json')->successful($data);
    }

    /**
     * 订单每月统计数据
     * @param Request $request
     * @return mixed
     */
    public function data(Request $request)
    {
        [$is_manager, $time, $type] = $request->getMore([
            ['is_manager', 0],
            ['data', '', '', 'time'],
            ['type', 4],
        ], true);
        $this->getStaffInfo();
        if (!$is_manager || !$this->staffInfo['is_manager']) {
            $is_manager = 0;
        }

        $store_id = $this->store_id;
        $staff_id = $is_manager ? 0 : $this->staff_id;
        $where = ['pid' => 0, 'store_id' => $store_id, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0, 'time' => $time];
        if ($staff_id) {
            $where['staff_id'] = $staff_id;
        }
        $data = [];
        switch ($type) {
            case 1://配送
                $data = $this->services->getOrderDataPriceCount($where + ['type' => 7]);
                break;
            case 2://收银
                $data = $this->services->getOrderDataPriceCount($where + ['type' => 6]);
                break;
            case 3://核销
                $data = $this->services->getOrderDataPriceCount($where + ['type' => 5]);
                break;
            case 4://充值
                /** @var UserRechargeServices $userRechargeServices */
                $userRechargeServices = app()->make(UserRechargeServices::class);
                $data = $userRechargeServices->getDataPriceCount($store_id, $staff_id, $time);
                break;
            default:
                return app('json')->fail('没有此类型');
        }
        return app('json')->success($data);
    }

    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        $where = $request->getMore([
            ['is_manager', 0],
            ['status', ''],
            ['is_del', 0],
            ['data', '', '', 'time'],
            ['type', ''],
            ['field_key', ''],
            ['field_value', ''],
        ]);
        $this->getStaffInfo();
        $is_manager = $where['is_manager'];
        unset($where['is_manager']);
        $where['store_id'] = $this->store_id;
        if (!$is_manager && !$this->staffInfo['is_manager'] && !$this->staffInfo['order_status']) {
            $where['staff_id'] = $this->staff_id;
        }
        $where['is_system_del'] = 0;
        if (!in_array($where['status'], [-1, -2, -3])) {
            $where['pid'] = 0;
        }
        return app('json')->successful($this->services->getStoreOrderList($where, ['*'], ['pink']));
    }

    /**
     * 订单详情
     * @param Request $request
     * @param StoreOrderServices $services
     * @param UserServices $userServices
     * @param $id
     * @return mixed
     */
    public function detail(Request $request, StoreOrderServices $services, UserServices $userServices, StoreOrderPromotionsServices $storeOrderPromotiosServices, $id)
    {
        $order = $this->services->getOne(['id' => $id], '*', ['store', 'refund' => function ($query) {
            $query->field('id,store_order_id,refund_num');
        }]);
        if (!$order) return app('json')->fail('订单不存在');
        $order = $order->toArray();
        $order['split'] = null;
        $orderInfo = $services->tidyOrder($order, true);
        //核算优惠金额
        $vipTruePrice = 0;
        foreach ($orderInfo['cartInfo'] ?? [] as $key => $cart) {
            $vipTruePrice = bcadd((string)$vipTruePrice, (string)$cart['vip_sum_truePrice'], 2);
        }
        $orderInfo['total_price'] = floatval(bcsub((string)$orderInfo['total_price'], (string)$vipTruePrice, 2));
        //优惠活动优惠详情
        $orderInfo['promotions_detail'] = $storeOrderPromotiosServices->getOrderPromotionsDetail((int)$order['id']);
        if ($orderInfo['give_coupon']) {
            $couponIds = is_string($orderInfo['give_coupon']) ? explode(',', $orderInfo['give_coupon']) : $orderInfo['give_coupon'];
            /** @var StoreCouponIssueServices $couponIssueService */
            $couponIssueService = app()->make(StoreCouponIssueServices::class);
            $orderInfo['give_coupon'] = $couponIssueService->getColumn([['id', 'IN', $couponIds]], 'id,coupon_title');
        }
        $orderInfo['cartInfo'] = array_merge($orderInfo['cartInfo']);
        $orderInfo['vip_true_price'] = $vipTruePrice;
        $orderInfo['pinkStatus'] = null;
        if ($orderInfo['type'] == 3) {
            /** @var StorePinkServices $pinkService */
            $pinkService = app()->make(StorePinkServices::class);
            $orderInfo['pinkStatus'] = $pinkService->value(['order_id' => $orderInfo['order_id']], 'status');
        }
        $nickname = $userServices->value(['uid' => $order['uid']], 'nickname');
        $orderInfo['nickname'] = $nickname;
        return app('json')->successful('ok', $orderInfo);
    }

    /**
     * 订单发货获取订单信息
     * @param Request $request
     * @param $orderId
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deliveryInfo(UserServices $userServices, $orderId)
    {
        $order = $this->services->getOne(['order_id' => $orderId], 'store_id,real_name,user_phone,user_address,order_id,uid,status,paid,id');
        if (!$order) return app('json')->fail('订单不存在');
        if ($order['paid']) {
            $order['nickname'] = $userServices->value(['uid' => $order['uid']], 'nickname');
            $order = $order->hidden(['uid', 'status', 'paid'])->toArray();
            $store_id = (int)$order['store_id'];
            $order['config_export_open'] = store_config($store_id, 'store_config_export_open');
            $export = [];
            if ($order['config_export_open']) {
                $export = [
                    'express_temp_id' => store_config($store_id, 'store_config_export_temp_id'),
                    'to_name' => store_config($store_id, 'store_config_export_to_name'),
                    'id' => store_config($store_id, 'store_config_export_id'),
                    'to_tel' => store_config($store_id, 'store_config_export_to_tel'),
                    'to_add' => store_config($store_id, 'store_config_export_to_address')
                ];
            }
            $order['export'] = $export;
            return app('json')->successful('ok', $order);
        }
        return app('json')->fail('状态错误');
    }

    /**
     * 获取面单信息
     * @param ServeServices $services
     * @return mixed
     */
    public function getExportTemp(Request $request, ServeServices $services)
    {
        [$com] = $request->getMore([
            ['com', ''],
        ], true);
        return app('json')->success($services->express()->temp($com));
    }

    /**
     * 物流公司
     * @param ExpressServices $services
     * @return mixed
     */
    public function getExportAll(ExpressServices $services)
    {
        return app('json')->success($services->expressList());
    }

    /**
     * 订单发货
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delivery(Request $request, StoreOrderDeliveryServices $services, $id)
    {
        $data = $request->postMore([
            ['type', 1],
            ['delivery_name', ''],//快递公司名称
            ['delivery_id', ''],//快递单号
            ['delivery_code', ''],//快递公司编码
            ['delivery_type', ''],//快递类型

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
        $services->delivery((int)$id, $data);
        return app('json')->successful('发货成功!');
    }

    /**
     * 订单拆单发送货
     * @param $id 订单id
     * @return mixed
     */
    public function split_delivery(StoreOrderDeliveryServices $services, Request $request, $id)
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
     * @param $id
     * @param StoreOrderCartInfoServices $services
     * @return mixed
     */
    public function split_cart_info($id, StoreOrderCartInfoServices $services)
    {
        if (!$id) {
            return app('json')->fail('缺少发货ID');
        }
        return app('json')->success($services->getSplitCartList((int)$id));
    }

    /**
     * 订单改价
     * @param Request $request
     * @param StoreOrderServices $services
     * @return mixed
     * @throws \Exception
     */
    public function price(Request $request, StoreOrderServices $services)
    {
        [$order_id, $price] = $request->postMore([
            ['order_id', ''],
            ['price', '']
        ], true);
        $order = $this->services->getOne(['order_id' => $order_id], 'id,user_phone,id,paid,pay_price,order_id,total_price,total_postage,pay_postage,gain_integral');
        if (!$order) return app('json')->fail('订单不存在');
        if ($order['paid']) {
            return app('json')->fail('订单已支付');
        }
        if ($price === '') return app('json')->fail('请填写实际支付金额');
        if ($price < 0) return app('json')->fail('实际支付金额不能小于0元');
        if ($order['pay_price'] == $price) return app('json')->successful('改价成功');
        $services->updateOrder($order['id'], ['total_price' => $order['total_price'], 'pay_price' => $price]);
        return app('json')->successful('改价成功');
    }

    /**
     * 订单备注
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function remark(Request $request)
    {
        [$order_id, $remark] = $request->postMore([
            ['order_id', ''],
            ['remark', '']
        ], true);
        $this->getStaffInfo();
        $order = $this->services->getOne(['order_id' => $order_id, 'store_id' => $this->store_id], 'id,remark');
        if (!$order) return app('json')->fail('订单不存在');
        if (!strlen(trim($remark))) return app('json')->fail('请填写备注内容');
        $order->remark = $remark;
        if (!$order->save())
            return app('json')->fail('备注失败');
        return app('json')->successful('备注成功');
    }


    /**
     * 订单支付
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function offline(Request $request, OrderOfflineServices $services)
    {
        [$order_id] = $request->postMore([['order_id', '']], true);
        $this->getStaffInfo();
        $orderInfo = $this->services->getOne(['order_id' => $order_id, 'store_id' => $this->store_id], 'id');
        if (!$orderInfo) return app('json')->fail('参数错误');
        $id = $orderInfo->id;
        $services->orderOffline((int)$id);
        return app('json')->successful('确认成功!');
    }

    /**
     * 订单退款
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refund(Request $request, StoreOrderRefundServices $services)
    {
        list($orderId, $price, $type) = $request->postMore([
            ['order_id', ''],
            ['price', '0'],
            ['type', 1],
        ], true);
        if (!strlen(trim($orderId))) return app('json')->fail('参数错误');
        $this->getStaffInfo();
        $orderRefund = $services->getOne(['order_id' => $orderId, 'store_id' => $this->store_id]);
        if (!$orderRefund) {
            return app('json')->fail('数据不存在!');
        }
        if ($orderRefund['is_cancel'] == 1) {
            return app('json')->fail('用户已取消申请');
        }
        $orderInfo = $this->services->get((int)$orderRefund['store_order_id']);
        if (!$orderInfo) {
            return app('json')->fail('数据不存在');
        }
        if (!in_array($orderRefund['refund_type'], [1, 2, 5])) {
            return app('json')->fail('售后订单状态不支持该操作');
        }
        if ($type == 1) {
            $data['refund_type'] = 6;
        } else if ($type == 2) {
            $data['refund_type'] = 3;
        } else {
            return app('json')->fail('退款修改状态错误');
        }
        $data['refunded_time'] = time();
        //拒绝退款
        if ($type == 2) {
            $services->refuseRefund((int)$orderRefund['id'], $data, $orderRefund);
            return app('json')->successful('修改退款状态成功!');
        } else {
            if ($orderRefund['refund_price'] == $orderRefund['refunded_price']) return app('json')->fail('已退完支付金额!不能再退款了');
            if (!$price) {
                return app('json')->fail('请输入退款金额');
            }
            $data['refunded_price'] = bcadd($price, $orderRefund['refunded_price'], 2);
            $bj = bccomp((float)$orderRefund['refund_price'], (float)$data['refunded_price'], 2);
            if ($bj < 0) {
                return app('json')->fail('退款金额大于支付金额，请修改退款金额');
            }
            $refundData['pay_price'] = $orderInfo['pay_price'];
            $refundData['refund_price'] = $price;
            if ($orderInfo['refund_price'] > 0) {
                mt_srand();
                $refundData['refund_id'] = $orderInfo['order_id'] . rand(100, 999);
            }
            //修改订单退款状态
            if ($services->agreeRefund($orderRefund['id'], $refundData)) {
                $services->update((int)$orderRefund['id'], $data);
                return app('json')->success('退款成功');
            } else {
                $services->storeProductOrderRefundYFasle((int)$orderInfo['id'], $price);
                return app('json')->fail('退款失败');
            }
        }
    }

    /**
     * 商家同意退货退款
     * @return mixed
     */
    public function agreeRefund(Request $request, StoreOrderRefundServices $services)
    {
        [$id] = $request->getMore([
            ['id', '']
        ], true);
        $services->agreeRefundProdcut((int)$id);
        return app('json')->success('操作成功');
    }

    /**
     * 订单取消   未支付的订单回退积分,回退优惠券,回退库存
     * @param Request $request
     * @param StoreOrderServices $orderServices
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cancel(Request $request, $id)
    {
        if (!$id) return app('json')->fail('参数错误');
        $this->getStaffInfo();
        if ($this->services->cancelOrder($id, $this->store_id))
            return app('json')->successful('取消订单成功');
        return app('json')->fail('取消订单失败');
    }

    /**
     * 订单删除
     * @param Request $request
     * @return mixed
     */
    public function del(Request $request, $id)
    {
        if (!$id) return app('json')->fail('参数错误!');
        $orderInfo = $this->services->get($id);
        $this->getStaffInfo();
        if (!$orderInfo || $orderInfo['store_id'] != $this->store_id)
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
     * 扫码获取核销订单列表信息
     * @param Request $request
     * @param WriteOffOrderServices $writeOffOrderServices
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function writeoffOrderinfo(Request $request, WriteOffOrderServices $writeOffOrderServices, StoreOrderCartInfoServices $orderCartInfo, $type)
    {
        [$verifyCode, $codeType] = $request->postMore([
            ['verify_code', ''],
            ['code_type', 1]
        ], true);
        $uid = (int)$request->uid();
        if (!$verifyCode || $verifyCode == 'undefined') return app('json')->fail('Lack of write-off code');
        if (strlen($verifyCode) == 12) {//核销码
            $orderInfo = $writeOffOrderServices->writeoffOrderInfo($uid, $verifyCode, $type);
            if ($orderInfo) {
                $data = [$orderInfo];
            }
        } else {//找用户
            $data = $writeOffOrderServices->userUnWriteoffOrder($uid, $verifyCode, $type);
        }
        $res = [];
        if ($data) {
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);
            $res = $orderServices->tidyOrderList($data, false);
            foreach ($res as &$orderInfo) {
                $orderInfo['image'] = '';
                if (isset($orderInfo['cart_id'][0])) {
                    $cartInfo = $orderCartInfo->getOne(['oid' => $orderInfo['id'], 'cart_id' => $orderInfo['cart_id'][0]], 'cart_info');
                    if ($cartInfo) $orderInfo['image'] = $cartInfo['cart_info']['productInfo']['image'] ?? '';
                }
            }
        }
        return app('json')->success($res);
    }

    /**
     * 扫码获取核销订单列表信息
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function orderCartInfo(Request $request, WriteOffOrderServices $writeOffOrderServices, $type)
    {
        $uid = (int)$request->uid();
        [$oid] = $request->postMore([
            ['oid', '']
        ], true);
        return app('json')->success($writeOffOrderServices->getOrderCartInfo($uid, $oid, $type));
    }

    /**
     * 核销订单
     * @param Request $request
     * @param WriteOffOrderServices $writeOffOrderServices
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function wirteoff(Request $request, WriteOffOrderServices $writeOffOrderServices, $type)
    {
        [$oid, $cart_ids] = $request->postMore([
            ['oid', ''],
            ['cart_ids', []]
        ], true);
        if (!$oid || !$cart_ids) {
            return app('json')->fail('请选择要核销的订单商品');
        }
        foreach ($cart_ids as $cart) {
            if (!isset($cart['cart_id']) || !$cart['cart_id'] || !isset($cart['cart_num']) || !$cart['cart_num'] || $cart['cart_num'] <= 0) {
                return app('json')->fail('请重新选择发货商品，或发货件数');
            }
        }
        $uid = (int)$request->uid();
        $orderInfo = $writeOffOrderServices->writeoffOrderInfo($uid, '', (int)$type, $oid);
        return app('json')->success('核销成功', $writeOffOrderServices->writeoffOrder($uid, $orderInfo, $cart_ids, $type));
    }

    /**
     * @param Request $request
     * @param StoreOrderRefundServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function refundList(Request $request, StoreOrderRefundServices $services)
    {
        $where = $request->getMore([
            ['order_id', ''],
            ['time', ''],
            ['refund_type', 0],
        ]);
        $this->getStaffInfo();
        $store_id = $this->store_id;
        $where['store_id'] = $store_id;
        $data = $services->refundList($where)['list'];
        return app('json')->success($data);
    }

    /**
     * 门店退款列表
     * @param StoreOrderRefundServices $services
     * @param $id
     * @return mixed
     */
    public function refundDetail(StoreOrderRefundServices $services, $id)
    {
        $uni = $services->value(['id' => $id], 'order_id');
        $data = $services->refundDetail($uni);
        return app('json')->successful('ok', $data);
    }

    /**
     * 修改备注
     * @param $id
     * @return mixed
     */
    public function refundRemark(StoreOrderRefundServices $services, Request $request)
    {
        [$remark, $order_id] = $request->postMore([
            ['remark', ''],
            ['order_id', ''],
        ], true);
        if (!$remark)
            return app('json')->fail('请输入要备注的内容');
        if (!$order_id)
            return app('json')->fail('缺少参数');

        if (!$order = $services->get(['order_id' => $order_id])) {
            return app('json')->fail('修改的订单不存在!');
        }
        $order->remark = $remark;
        if ($order->save()) {
            return app('json')->success('备注成功');
        } else
            return app('json')->fail('备注失败');
    }
}
