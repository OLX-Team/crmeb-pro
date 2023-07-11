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

namespace app\controller\out;


use app\Request;
use app\services\order\StoreCartServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderInvoiceServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderSuccessServices;
use app\services\order\StoreOrderTakeServices;
use app\services\user\UserAddressServices;
use think\facade\App;
use app\services\product\product\StoreProductServices;
use app\services\user\UserServices;
use app\services\order\StoreOrderServices;


/**
 * Class Order
 * @package app\kefuapi\controller
 */
class Order
{

    /**
     * Order constructor.
     * @param App $app
     * @param StoreOrderServices $services
     */
    public function __construct(StoreOrderServices $services)
    {
        $this->services = $services;
    }


    /**
     * 获取订单列表
     * @return mixed
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
        $where['shipping_type'] = 1;
        $where['is_system_del'] = 0;
        if (!$where['real_name'] && !in_array($where['status'], [-1, -2, -3])) {
            $where['pid'] = 0;
        }
        return app('json')->success($this->services->getOrderList($where, ['*'], ['split' => function ($query) {
            $query->field('id,pid');
        }, 'pink', 'invoice']));
    }

    /**
     * 根据订单id获取订单状态
     * @param $oid
     * @return mixed
     */
    public function get_status($oid)
    {
        if (!$oid) return app('json')->fail('参数错误');
        return app('json')->success($this->services->outGetStatus($oid));
    }

    /**
     * 根据订单id查询收货方式
     * @param $oid
     * @return mixed
     */
    public function get_shipping_type($oid)
    {
        if (!$oid) return app('json')->fail('参数错误');
        return app('json')->success($this->services->outGetShippingType($oid));
    }

    /**
     * 根据订单id查询配送信息
     * @param $oid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delivery_type($oid)
    {
        if (!$oid) return app('json')->fail('参数错误');
        return app('json')->success($this->services->OutDeliveryType($oid));
    }

    /**
     * 收货
     * @param $oid
     * @param StoreOrderTakeServices $services
     * @return mixed
     */
    public function take_delivery($oid, StoreOrderTakeServices $services)
    {

        if (!$oid) return app('json')->fail('缺少参数');
        $order = $this->services->get(['order_id' => $oid]);
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

        if (!$this->services->update($order['id'], $data)) {
            return app('json')->fail('收货失败,请稍候再试!');
        } else {
            $services->storeProductOrderUserTakeDelivery($order);
            return app('json')->success('收货成功');
        }
    }

    /**
     * 根据订单id获取发票信息
     * @param $oid
     * @param StoreOrderInvoiceServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function invoice($oid, StoreOrderInvoiceServices $services)
    {
        if (!$oid) return app('json')->fail('缺少参数');
        $id = $this->services->value(['order_id' => $oid], 'id');
        return app('json')->success($services->getOneInvoice((int)$id));
    }

    /**
     * 获取订单详情
     * @param $oid
     * @return mixed
     */
    public function detail($oid)
    {
        if (!$oid || !($orderInfo = $this->services->get(['order_id' => $oid]))) {
            return $this->app('json')('订单不存在');
        }
        $orderInfo = $this->services->tidyOrder($orderInfo->toArray(), true, true);
        //核算优惠金额
        $vipTruePrice = 0;
        foreach ($orderInfo['cartInfo'] ?? [] as $cart) {
            $vipTruePrice = bcadd((string)$vipTruePrice, (string)$cart['vip_sum_truePrice'], 2);
        }
        $orderInfo['vip_true_price'] = $vipTruePrice;
        $orderInfo['total_price'] = bcadd((string)$orderInfo['total_price'], (string)$orderInfo['vip_true_price'], 2);
        return app('json')->success(compact('orderInfo'));
    }

    /**
     * 修改订单
     * @param $id
     * @return mixed
     */
    public function update(Request $request, $oid)
    {
        if (!$oid) return app('json')->fail('缺少参数');
        $order = $this->services->get(['order_id' => $oid]);
        $data = $request->postMore([
            ['pay_price', 0],
        ]);
//        $this->validate($data, StoreOrderValidate::class);
        if ($data['pay_price'] < 0) return app('json')->fail('请输入实际支付金额');
        $data['total_price'] = $order['total_price'];
        $data['order_id'] = $order['order_id'];
        $this->services->updateOrder((int)$order['id'], $data);
        return app('json')->success('修改成功');
    }

    /**
     * 购物车 添加
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cart_add(Request $request, StoreCartServices $services, StoreProductServices $productServices)
    {
        $where = $request->postMore([
            ['spu', ''],//普通商品编号
            [['cartNum', 'd'], 1], //购物车数量
            ['uniqueId', ''],//属性唯一值
            ['uid', 0]
        ]);
        $where['productId'] = $productServices->value(['spu' => $where['spu']], 'id');
        if (!$where['productId']) {
            return app('json')->fail('参数错误');
        }
        $uid = $where['uid'];
        $res = $services->setCart($uid, (int)$where['productId'], (int)$where['cartNum'], $where['uniqueId'], 0, true);
        if (!$res) return app('json')->fail('添加失败');
        else  return app('json')->successful('ok', ['cartId' => $res]);
    }

    /**
     * 获取商品运费
     * @param Request $request
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function postage(Request $request)
    {
        [$cartId, $uid, $addressId] = $request->getMore([
            'cartId',
            'uid', //用户id
            ['addressId', 0],//地址id
        ], true);
        return app('json')->successful($this->services->outGetPostage($cartId, (int)$uid, (int)$addressId));
    }

    /**
     * 订单确认
     * @param Request $request
     * @param UserServices $userServices
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function confirm(Request $request, UserServices $userServices)
    {
        [$cartId, $addressId, $uid] = $request->postMore([
            'cartId',
            ['addressId', 0],
            ['uid', 0]
        ], true);
        if (!is_string($cartId) || !$cartId) {
            return app('json')->fail('请提交购买的商品');
        }
        $user = $userServices->get($uid);
        return app('json')->successful($this->services->getOrderConfirmData($user, $cartId, true, (int)$addressId));
    }

    /**
     * 售后列表
     * @param Request $request
     * @param StoreOrderRefundServices $storeOrderRefundServices
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function refund_list(Request $request, StoreOrderRefundServices $storeOrderRefundServices)
    {
        $where = $request->getMore([
            ['order_id', ''],
            ['time', '', '', 'refund_reason_time'],
            ['refund_type', 0],
            ['page', 0],
            ['limit', 0]
        ]);
        return app('json')->success($storeOrderRefundServices->refundList($where));
    }

    /**
     * 订单创建
     * @param Request $request
     * @param StoreOrderCreateServices $createServices
     * @param $key
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create(Request $request, StoreOrderCreateServices $createServices, $key)
    {

        [$addressId, $couponId, $payType, $useIntegral, $mark, $combinationId, $pinkId, $seckill_id, $bargainId, $from, $shipping_type, $real_name, $phone, $storeId, $news, $invoice_id, $quitUrl, $discountId, $uid, $paid] = $request->postMore([
            [['addressId', 'd'], 0],
            [['couponId', 'd'], 0],
            ['payType', ''],
            ['useIntegral', 0],
            ['mark', ''],
            [['combinationId', 'd'], 0],
            [['pinkId', 'd'], 0],
            [['seckill_id', 'd'], 0],
            [['bargainId', 'd'], ''],
            ['from', 'weixin'],
            [['shipping_type', 'd'], 1],
            ['real_name', ''],
            ['phone', ''],
            [['store_id', 'd'], 0],
            ['new', 0],
            [['invoice_id', 'd'], 0],
            ['quitUrl', ''],
            [['discountId', 'd'], 0],
            ['uid', 0],
            ['paid', 0]
        ], true);
        if (!$key) return app('json')->fail('参数错误!');
        if ($this->services->getOne(['unique' => $key, 'uid' => $uid, 'is_del' => 0]))
            return app('json')->status('extend_order', '订单已创建，请点击查看完成支付', ['orderId' => $key, 'key' => $key]);
        $cartGroup = $this->services->getCacheOrderInfo($uid, $key);
        if (!$cartGroup) {
            return app('json')->fail('请不重复提交或订单已过期,请刷新当前页面!');
        }
        $cartInfo = $cartGroup['cartInfo'];
        if (!$cartInfo) {
            return app('json')->fail('订单已过期或提交的商品不在送达区域,请刷新当前页面或重新选择商品下单!');
        }
        if ($discountId && isset($cartGroup['invalidCartInfo']) && $cartGroup['invalidCartInfo']) {
            return app('json')->fail('套餐中有商品已失效或者不在送达区域');
        }
        $payType = strtolower($payType);
        if ($shipping_type == 1) {
            if (!$addressId) {
                return app('json')->fail('请选择收货地址!');
            }
            $addressInfo = $cartGroup['addr'] ?? [];
            if (!$addressInfo || $addressInfo['id'] != $addressId) {
                /** @var UserAddressServices $addressServices */
                $addressServices = app()->make(UserAddressServices::class);
                if (!$addressInfo = $addressServices->getOne(['uid' => $uid, 'id' => $addressId, 'is_del' => 0]))
                    return app('json')->fail('地址选择有误!');
                $addressInfo = $addressInfo->toArray();
            }
        } else {
            if ((!$real_name || !$phone)) {
                return app('json')->fail('请填写姓名和电话');
            }
            $addressInfo['real_name'] = $real_name;
            $addressInfo['phone'] = $phone;
            $addressInfo['province'] = '';
            $addressInfo['city'] = '';
            $addressInfo['district'] = '';
            $addressInfo['detail'] = '';
        }
        if (!$this->services->checkPaytype($payType)) {
            return app('json')->fail('暂不支持该支付方式，请刷新页面或者联系管理员');
        }
        $isChannel = $this->getChennel[$from] ?? ($request->isApp() ? 0 : 1);

        try {
            $order = $createServices->createOrder($uid, $key, $cartGroup, (int)$addressId, $payType, $addressInfo, $request->user()->toArray(), !!$useIntegral, $couponId, $mark, $pinkId, $isChannel, $shipping_type, $storeId, !!$news, [], (int)$invoice_id);
        } catch (\Throwable $e) {
            $order = false;
            \think\facade\Log::error('订单生成失败，原因：' . $e->getMessage());
        }
        $orderId = $order['order_id'];
        if ($orderId) {
            $orderInfo = $this->services->getOne(['order_id' => $orderId]);
            if (!$orderInfo || !isset($orderInfo['paid'])) {
                return app('json')->fail('支付订单不存在!');
            }
            $orderInfo = $orderInfo->toArray();
            $info = compact('orderId', 'key');
            if ($paid == 1) {
                /** @var StoreOrderSuccessServices $orderServices */
                $orderServices = app()->make(StoreOrderSuccessServices::class);
                $orderServices->paySuccess($orderInfo, $payType);
                return app('json')->status('success', '微信支付成功');
            } else {
                return app('json')->status('success', '订单创建成功');
            }
        } else return app('json')->fail('订单生成失败!');
    }

}
