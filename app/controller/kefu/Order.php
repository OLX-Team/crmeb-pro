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

namespace app\controller\kefu;


use app\Request;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\activity\combination\StorePinkServices;
use app\services\order\StoreOrderWriteOffServices;
use think\facade\App;
use app\services\store\DeliveryServiceServices;
use app\services\order\StoreOrderPromotionsServices;
use app\services\product\product\StoreProductServices;
use app\services\serve\ServeServices;
use app\services\other\ExpressServices;
use app\services\user\UserServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderDeliveryServices;
use app\services\store\SystemStoreServices;
use app\validate\admin\order\StoreOrderValidate;
use app\services\message\service\StoreServiceRecordServices;
use \app\common\controller\Order as CommonOrder;

/**
 * Class Order
 * @package app\kefuapi\controller
 */
class Order extends AuthController
{
    use CommonOrder;

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
     * 获取订单列表
     * @param Request $request
     * @param $uid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserOrderList(Request $request, StoreServiceRecordServices $services, StoreOrderRefundServices $storeOrderRefundServices, $uid)
    {
        $where = $request->getMore([
            ['type', '', '', 'status'],
            ['search', '', '', 'real_name'],
        ]);
        $where['uid'] = $uid;
        $where['is_del'] = 0;
        $where['is_system_del'] = 0;
        $where['refund_type'] = [0, 1, 3, 6];
        if (!$services->count(['to_uid' => $uid])) {
            return $this->fail('用户uid不再当前聊天用户范围内');
        }
        if ($where['status'] == -1) {
            $list = $storeOrderRefundServices->refundList(['uid' => $where['uid'], 'real_name' => $where['real_name'], 'refund_type' => [1, 2, 4, 5]])['list'] ?? [];
        } else {
            $where['store_id'] = 0;
            $list = $this->services->getOrderApiList($where + ['pid' => 0], ['*'], ['pink', 'invoice']);
        }
        return $this->success($list);
    }

    /**
     * 订单发货
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delivery_keep(StoreOrderDeliveryServices $services, $id)
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
        $services->delivery((int)$id, $data);
        return $this->success('发货成功!');
    }

    /**
     * 修改支付金额等
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function edit($id)
    {
        if (!$id) {
            return $this->fail('Data does not exist!');
        }
        return $this->success($this->services->updateForm($id));
    }

    /**
     * 修改订单
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        if (!$id) {
            return $this->fail('Missing order ID');
        }
        $data = $this->request->postMore([
            ['order_id', ''],
            ['total_price', 0],
            ['total_postage', 0],
            ['pay_price', 0],
            ['pay_postage', 0],
            ['gain_integral', 0],
        ]);

        validate(StoreOrderValidate::class)->check($data);

        if ($data['total_price'] < 0) {
            return $this->fail('Please enter the total price');
        }
        if ($data['pay_price'] < 0) {
            return $this->fail('Please enter the actual payment amount');
        }

        $this->services->updateOrder((int)$id, $data);
        return $this->success('Modified success');
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
        $order = $this->services->getOne(['order_id' => $order_id], 'id,remark');
        /** @var StoreOrderRefundServices $refundServices */
        $refundServices = app()->make(StoreOrderRefundServices::class);
        $order = $order ?: $refundServices->get(['order_id' => $order_id]);
        if (!$order) {
            return $this->fail('订单不存在');
        }
        if (!strlen(trim($remark))) {
            return $this->fail('请填写备注内容');
        }
        $order->remark = $remark;
        if (!$order->save()) {
            return $this->fail('备注失败');
        }
        return $this->success('备注成功');

    }

    /**
     * 退款表单生成
     * @param $id 订单id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function refundForm(StoreOrderRefundServices $services, $id)
    {
        if (!$id) {
            return $this->fail('Data does not exist!');
        }
        return $this->success($services->refundOrderForm((int)$id));
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
    public function refundOld(Request $request, StoreOrderRefundServices $services)
    {
        [$orderId, $price, $type] = $request->postMore([
            ['order_id', ''],
            ['price', '0'],
            ['type', 1],
        ], true);
        if (!strlen(trim($orderId))) return $this->fail('参数错误');
        $orderRefund = $services->getOne(['order_id' => $orderId]);
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
            return $this->success('修改退款状态成功!');
        } else {
            if ($orderRefund['refund_price'] == $orderRefund['refunded_price']) return $this->fail('已退完支付金额!不能再退款了');
            if (!$price) {
                return $this->fail('请输入退款金额');
            }
            $data['refunded_price'] = bcadd($price, $orderRefund['refunded_price'], 2);
            $bj = bccomp((float)$orderRefund['refund_price'], (float)$data['refunded_price'], 2);
            if ($bj < 0) {
                return $this->fail('退款金额大于支付金额，请修改退款金额');
            }
            $refundData['pay_price'] = $orderInfo['pay_price'];
            $refundData['refund_price'] = $price;

            //修改订单退款状态
            if ($services->agreeRefund((int)$orderRefund['id'], $refundData)) {
                $services->update((int)$orderRefund['id'], $data);
                return $this->success('退款成功');
            } else {
                $services->storeProductOrderRefundYFasle((int)$orderInfo['id'], $price);
                return $this->fail('退款失败');
            }
        }
    }

    /**
     * 订单详情
     * @param $id 订单id
     * @return mixed
     */
    public function orderInfo(StoreProductServices $productServices, StoreOrderPromotionsServices $storeOrderPromotiosServices, $id)
    {
        if (!$id || !($orderInfo = $this->services->get($id))) {
            return $this->fail('订单不存在');
        }
        /** @var UserServices $services */
        $services = app()->make(UserServices::class);
        $userInfo = $services->get($orderInfo['uid']);
        if (!$userInfo) {
            return $this->fail('用户信息不存在');
        }
        $userInfo = $userInfo->hidden(['pwd', 'add_ip', 'last_ip', 'login_type']);
        $userInfo['spread_name'] = '';
        if ($userInfo['spread_uid'])
            $userInfo['spread_name'] = $services->value(['uid' => $userInfo['spread_uid']], 'nickname');
        $orderInfo = $this->services->tidyOrder($orderInfo->toArray(), true);
        /** @var StorePinkServices $pinkService */
        $pinkService = app()->make(StorePinkServices::class);
        $orderInfo['pinkStatus'] = $pinkService->value(['order_id' => $orderInfo['order_id']], 'status');
        $productId = array_column($orderInfo['cartInfo'], 'product_id');
        $cateData = $productServices->productIdByProductCateName($productId);
        foreach ($orderInfo['cartInfo'] as &$item) {
            $item['class_name'] = $cateData[$item['product_id']] ?? '';
        }
        if ($orderInfo['store_id'] && $orderInfo['shipping_type'] == 2) {
            /** @var  $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $orderInfo['_store_name'] = $storeServices->value(['id' => $orderInfo['store_id']], 'name');
        } else {
            $orderInfo['_store_name'] = '';
        }
        //核算优惠金额
        $vipTruePrice = 0;
        foreach ($orderInfo['cartInfo'] ?? [] as $cart) {
            $vipTruePrice = bcadd((string)$vipTruePrice, (string)$cart['vip_sum_truePrice'], 2);
        }
        $orderInfo['vip_true_price'] = $vipTruePrice;
        $orderInfo['total_price'] = bcadd((string)$orderInfo['total_price'], (string)$orderInfo['vip_true_price'], 2);
        //优惠活动优惠详情
        $orderInfo['promotions_detail'] = $storeOrderPromotiosServices->getOrderPromotionsDetail((int)$orderInfo['id']);
        if ($orderInfo['give_coupon']) {
            $couponIds = is_string($orderInfo['give_coupon']) ? explode(',', $orderInfo['give_coupon']) : $orderInfo['give_coupon'];
            /** @var StoreCouponIssueServices $couponIssueService */
            $couponIssueService = app()->make(StoreCouponIssueServices::class);
            $orderInfo['give_coupon'] = $couponIssueService->getColumn([['id', 'IN', $couponIds]], 'id,coupon_title');
        }
        $userInfo = $userInfo->toArray();
        return $this->success(compact('orderInfo', 'userInfo'));
    }

    /**
     * 获取物流
     * @param ExpressServices $services
     * @return mixed
     */
    public function export(ExpressServices $services)
    {
        [$status] = $this->request->getMore([
            ['status', ''],
        ], true);
        if ($status != '') $data['status'] = $status;
        $data['is_show'] = 1;
        return $this->success($services->express($data));
    }

    /**
     *
     * 获取面单信息
     * @param string $com
     * @return mixed
     */
    public function getExportTemp(ServeServices $services)
    {
        [$com] = $this->request->getMore([
            ['com', ''],
        ], true);
        return $this->success($services->express()->temp($com));
    }

    /**
     * 获取所有配送员列表
     * @param DeliveryServiceServices $services
     * @return mixed
     */
    public function getDeliveryAll(DeliveryServiceServices $services)
    {
        $list = $services->getDeliveryList();
        return $this->success($list['list']);
    }

    /**
     * 获取配置信息
     * @return mixed
     */
    public function getDeliveryInfo()
    {
        return $this->success([
            'express_temp_id' => sys_config('config_export_temp_id'),
            'to_name' => sys_config('config_export_to_name'),
            'id' => sys_config('config_export_id'),
            'to_tel' => sys_config('config_export_to_tel'),
            'to_add' => sys_config('config_export_to_address')
        ]);
    }

    /**
     * 门店核销
     * @param Request $request
     */
    public function order_verific(StoreOrderWriteOffServices $services, $id)
    {

        $orderInfo = $this->services->get(['id' => $id], ['verify_code', 'uid']);
        if (!$orderInfo) {
            return $this->fail('核销订单未查到');
        }
        if (!$orderInfo->verify_code) {
            return $this->fail('Lack of write-off code');
        }
        $services->writeOffOrder($orderInfo->verify_code, 1, $orderInfo->uid);
        return $this->success('Write off successfully');
    }


    /**
     * 退款订单详情
     * @param StoreOrderRefundServices $services
     * @param UserServices $userServices
     * @param $id
     * @return mixed
     */
    public function refundDetail(StoreOrderRefundServices $services, UserServices $userServices, $id)
    {
        $order = $services->get(['id' => $id], ['id', 'order_id', 'uid']);
        $uni = $order['order_id'];
        $data['orderInfo'] = $services->refundDetail($uni);
        $userInfo = $userServices->get((int)$order['uid']);
        if (!$userInfo) return app('json')->fail('用户信息不存在');
        $userInfo = $userInfo->hidden(['pwd', 'add_ip', 'last_ip', 'login_type']);
        $data['userInfo'] = $userInfo;
        return app('json')->success($data);
    }
}
