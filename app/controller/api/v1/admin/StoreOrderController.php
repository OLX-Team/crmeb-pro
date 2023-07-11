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
namespace app\controller\api\v1\admin;

use app\Request;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\activity\combination\StorePinkServices;
use app\services\activity\promotions\StorePromotionsServices;
use app\services\order\StoreOrderPromotionsServices;
use app\services\order\store\WriteOffOrderServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderCreateServices;
use app\services\store\DeliveryServiceServices;
use app\services\order\StoreOrderDeliveryServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderWapServices;
use app\services\pay\OrderOfflineServices;
use app\services\serve\ServeServices;
use app\services\user\UserServices;
use app\services\other\ExpressServices;
use crmeb\services\SystemConfigService;

/**
 * 订单类
 * Class StoreOrderController
 * @package app\api\controller\admin\order
 */
class StoreOrderController
{
    /**
     * @var StoreOrderWapServices
     */
    protected $services;

    /**
     * StoreOrderController constructor.
     * @param StoreOrderWapServices $services
     */
    public function __construct(StoreOrderWapServices $services)
    {
        $this->services = $services;
    }

    /**
     * 获取erp开关
     * @return mixed
     */
    public function getErpConfig()
    {
        return app('json')->success(['open_erp' => !!sys_config('erp_open')]);
    }

    /**
     *  订单数据统计
     * @param Request $request
     * @return mixed
     */
    public function statistics(StoreOrderServices $services)
    {
        $store_id = 0;
        $dataCount = $services->getOrderData(0, $store_id);
        $dataPrice = $this->services->getOrderTimeData($store_id);
        $data = array_merge($dataCount, $dataPrice);
        return app('json')->successful($data);
    }

    /**
     * 订单每月统计数据
     * @param Request $request
     * @return mixed
     */
    public function data(Request $request)
    {
        [$start, $stop] = $request->getMore([
            ['start', strtotime(date('Y-m'))],
            ['stop', time()],
        ], true);
        return app('json')->successful($this->services->getOrderDataPriceCount(['time' => [$start, $stop]]));
    }

    /**
     * 订单列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        $where = $request->getMore([
            ['status', ''],
            ['is_del', 0],
            ['data', '', '', 'time'],
            ['type', ''],
            ['field_key', ''],
            ['field_value', ''],
        ]);
        $where['is_system_del'] = 0;
        if (!in_array($where['status'], [-1, -2, -3])) {
            $where['pid'] = 0;
        }
        $where['store_id'] = 0;
        return app('json')->successful($this->services->getWapAdminOrderList($where, ['split' => function ($query) {
            $query->field('id,pid');
        }, 'pink', 'invoice']));
    }

    /**
     * 订单详情
     * @param Request $request
     * @param $orderId
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail(Request $request, StoreOrderServices $services, UserServices $userServices, StoreOrderPromotionsServices $storeOrderPromotiosServices, $orderId)
    {
        if (!strlen(trim($orderId))) return app('json')->fail('参数错误');
        $order = $this->services->getOne(['order_id' => $orderId], '*', ['invoice', 'refund' => function ($query) {
            $query->field('id,store_order_id,refund_num');
        }]);
        if (!$order) return app('json')->fail('订单不存在');
        $order = $order->toArray();
        $order['split'] = [];
        $orderInfo = $services->tidyOrder($order, true);
        //核算优惠金额
        $vipTruePrice = 0;
        foreach ($orderInfo['cartInfo'] ?? [] as $key => &$cart) {
            $vipTruePrice = bcadd((string)$vipTruePrice, (string)$cart['vip_sum_truePrice'], 2);
        }
        $orderInfo['vip_true_price'] = $vipTruePrice;
        $orderInfo['total_price'] = floatval(bcsub((string)$orderInfo['total_price'], (string)$vipTruePrice, 2));
        //优惠活动优惠详情
        $orderInfo['promotions_detail'] = $storeOrderPromotiosServices->getOrderPromotionsDetail((int)$order['id']);
        if ($orderInfo['give_coupon']) {
            $couponIds = is_string($orderInfo['give_coupon']) ? explode(',', $orderInfo['give_coupon']) : $orderInfo['give_coupon'];
            /** @var StoreCouponIssueServices $couponIssueService */
            $couponIssueService = app()->make(StoreCouponIssueServices::class);
            $orderInfo['give_coupon'] = $couponIssueService->getColumn([['id', 'IN', $couponIds]], 'id,coupon_title');
        }
        $orderInfo['pinkStatus'] = null;
        if ($orderInfo['type'] == 3) {
            /** @var StorePinkServices $pinkService */
            $pinkService = app()->make(StorePinkServices::class);
            $orderInfo['pinkStatus'] = $pinkService->value(['order_id' => $orderInfo['order_id']], 'status');
        }
        $nickname = $userServices->value(['uid' => $orderInfo['uid']], 'nickname');
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
    public function delivery_gain(UserServices $userServices, $orderId)
    {
        $order = $this->services->getOne(['order_id' => $orderId], 'real_name,user_phone,user_address,order_id,uid,status,paid,id');
        if (!$order) return app('json')->fail('订单不存在');
        if ($order['paid']) {
            $order['nickname'] = $userServices->value(['uid' => $order['uid']], 'nickname');
            $order = $order->hidden(['uid', 'status', 'paid'])->toArray();
            $order['config_export_open'] = sys_config('config_export_open');
            return app('json')->successful('ok', $order);
        }
        return app('json')->fail('状态错误');
    }

    /**
     * 订单发货
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delivery_keep(Request $request, StoreOrderDeliveryServices $services, $id)
    {
        $data = $request->postMore([
            ['type', 1],
            ['delivery_name', ''],//快递公司id
            ['delivery_id', ''],//快递单号
            ['delivery_code', ''],//快递公司编码
            ['delivery_type', ''],//快递公司名称

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
        $order = $this->services->getOne(['order_id' => $order_id], 'id,remark');
        if (!$order) return app('json')->fail('订单不存在');
        if (!strlen(trim($remark))) return app('json')->fail('请填写备注内容');
        $order->remark = $remark;
        if (!$order->save())
            return app('json')->fail('备注失败');
        return app('json')->successful('备注成功');
    }

    /**
     * 订单交易额/订单数量时间统计
     * @param Request $request
     * @return bool
     */
    public function time(Request $request)
    {
        list($start, $stop, $type) = $request->getMore([
            ['start', strtotime(date('Y-m'))],
            ['stop', time()],
            ['type', 1]
        ], true);
        [$start, $stop, $front] = getFrontTime((int)$start, (int)$stop);
        $front_stop = (int)bcsub((string)$start, '1');

        /** @var StoreOrderServices $orderService */
        $orderService = app()->make(StoreOrderServices::class);
        $order_where = ['pid' => 0, 'paid' => 1, 'refund_status' => [0, 3], 'store_id' => 0, 'is_del' => 0, 'is_system_del' => 0];
        if ($type == 1) {//销售额
            $frontPrice = $orderService->sum($order_where + ['time' => [$front, $front_stop]], 'pay_price', true);
            $afterPrice = $orderService->sum($order_where + ['time' => [$start, $stop]], 'pay_price', true);
            $chartInfo = $orderService->chartTimePrice($start, $stop);
            $data['chart'] = $chartInfo;//营业额图表数据
            $data['time'] = $afterPrice;//时间区间营业额
            $increase = (float)bcsub((string)$afterPrice, (string)$frontPrice, 2); //同比上个时间区间增长营业额
            $growthRate = abs($increase);
            if ($growthRate == 0) $data['growth_rate'] = 0;
            else if ($frontPrice == 0) $data['growth_rate'] = (int)bcmul($growthRate, 100, 0);
            else $data['growth_rate'] = (int)bcmul((string)bcdiv((string)$growthRate, (string)$frontPrice, 2), '100', 0);//时间区间增长率
            $data['increase_time'] = abs($increase); //同比上个时间区间增长营业额
            $data['increase_time_status'] = $increase >= 0 ? 1 : 2; //同比上个时间区间增长营业额增长 1 减少 2
        } else {//订单数
            $frontNumber = $orderService->count($order_where + ['time' => [$front, $front_stop]]);
            $afterNumber = $orderService->count($order_where + ['time' => [$start, $stop]]);
            $chartInfo = $orderService->chartTimeNumber($start, $stop);
            $data['chart'] = $chartInfo;//订单数图表数据
            $data['time'] = $afterNumber;//时间区间订单数
            $increase = $afterNumber - $frontNumber; //同比上个时间区间增长订单数
            $growthRate = abs($increase);
            if ($growthRate == 0) $data['growth_rate'] = 0;
            else if ($frontNumber == 0) $data['growth_rate'] = (int)bcmul($growthRate, 100, 0);
            else $data['growth_rate'] = (int)bcmul((string)bcdiv((string)$growthRate, (string)$frontNumber, 2), '100', 0);//时间区间增长率
            $data['increase_time'] = abs($increase); //同比上个时间区间增长营业额
            $data['increase_time_status'] = $increase >= 0 ? 1 : 2; //同比上个时间区间增长营业额增长 1 减少 2
        }
        return app('json')->successful($data);
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
        [$orderId] = $request->postMore([['order_id', '']], true);
        $orderInfo = $this->services->getOne(['order_id' => $orderId], 'id');
        if (!$orderInfo) return app('json')->fail('参数错误');
        $id = $orderInfo->id;
        $services->orderOffline((int)$id);
        return app('json')->successful('修改成功!');

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
    public function refund(Request $request, StoreOrderRefundServices $services, StoreOrderServices $orderServices, StoreOrderCartInfoServices $storeOrderCartInfoServices, StoreOrderCreateServices $storeOrderCreateServices)
    {
        list($orderId, $price, $type) = $request->postMore([
            ['order_id', ''],
            ['price', '0'],
            ['type', 1],
        ], true);
        if (!strlen(trim($orderId))) return app('json')->fail('参数错误');
        //退款订单详情
        $orderRefund = $services->getOne(['order_id' => $orderId]);
        $is_admin = 0;
        if (!$orderRefund) {
            //主动退款主订单详情
            $orderRefund = $orderRefund ?: $orderServices->getOne(['order_id' => $orderId]);
            $is_admin = 1;
            if ($services->count(['store_order_id' => $orderRefund['id'], 'refund_type' => [0, 1, 2, 4, 5], 'is_cancel' => 0, 'is_del' => 0])) {
                return app('json')->fail('请先处理售后申请');
            }
        }
        if (!$is_admin) {
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
                if ($orderRefund['refund_price'] == $orderInfo['refunded_price']) return app('json')->fail('已退完支付金额!不能再退款了');
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


                //修改订单退款状态
                if ($services->agreeRefund((int)$orderRefund['id'], $refundData)) {
                    $services->update((int)$orderRefund['id'], $data);
                    return app('json')->success('退款成功');
                } else {
                    $services->storeProductOrderRefundYFasle((int)$orderInfo['id'], $price);
                    return app('json')->fail('退款失败');
                }
            }
        } else {
            $order = $orderRefund;
            $data['refund_price'] = $price;
            $data['type'] = $type;
            $id = $order['id'];
            //0元退款
            if ($order['pay_price'] == 0 && in_array($order['refund_status'], [0, 1])) {
                $refund_price = 0;
            } else {
                if ($order['pay_price'] == $order['refund_price']) {
                    return app('json')->fail('已退完支付金额!不能再退款了');
                }
                if (!$data['refund_price']) {
                    return app('json')->fail('请输入退款金额');
                }
                $refund_price = $data['refund_price'];
                $data['refund_price'] = bcadd($data['refund_price'], $order['refund_price'], 2);
                $bj = bccomp((string)$order['pay_price'], (string)$data['refund_price'], 2);
                if ($bj < 0) {
                    return app('json')->fail('退款金额大于支付金额，请修改退款金额');
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
                if ($services->agreeRefund((int)$res->id, $refund_data)) {
                    $this->services->update($id, $data);
                    return app('json')->success('退款成功');
                } else {
                    $services->storeProductOrderRefundYFasle((int)$id, $refund_price);
                    return app('json')->fail('退款失败');
                }
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
     * 扫码核销
     * @param Request $request
     * @param WriteOffOrderServices $writeOffOrderServices
     * @param StoreOrderCartInfoServices $orderCartInfo
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function order_verific(Request $request, WriteOffOrderServices $writeOffOrderServices, StoreOrderCartInfoServices $orderCartInfo)
    {
        [$verifyCode, $isConfirm] = $request->postMore([
            ['verify_code', ''],
            ['is_confirm', 0]
        ], true);
        if (!$verifyCode) return app('json')->fail('Lack of write-off code');
        $uid = $request->uid();
        $type = 0;
        try {//店员
            $type = 1;
            $orderInfo = $writeOffOrderServices->writeoffOrderInfo($uid, $verifyCode, $type);
        } catch (\Throwable $e) {
            $orderInfo = [];
        }
        if (!$orderInfo) {
            try {
                $type = 2;
                $orderInfo = $writeOffOrderServices->writeoffOrderInfo($uid, $verifyCode, $type);
            } catch (\Throwable $e) {
                $orderInfo = [];
            }
        }
        if (!$orderInfo || !$type) {
            return app('json')->fail('您暂无核销权限');
        }
        if ($isConfirm == 0) {
            $cartInfo = $orderCartInfo->getOne(['oid' => $orderInfo['id'], 'cart_id' => $orderInfo['cart_id'][0]], 'cart_info');
            if ($cartInfo) $orderInfo['image'] = $cartInfo['cart_info']['productInfo']['image'] ?? '';
            return app('json')->success($orderInfo);
        }
        $writeOffOrderServices->writeoffOrder($uid, $orderInfo, [], $type);
        return app('json')->success('Write off successfully');
    }


    /**
     * 获取所有配送员列表
     * @param DeliveryServiceServices $services
     * @return mixed
     */
    public function getDeliveryAll(DeliveryServiceServices $services)
    {
        $list = $services->getDeliveryList();
        return app('json')->success($list['list']);
    }

    /**
     * 获取配置信息
     * @return mixed
     */
    public function getDeliveryInfo()
    {
        $data = SystemConfigService::more(['config_export_temp_id', 'config_export_to_name', 'config_export_id', 'config_export_to_tel', 'config_export_to_address']);
        return app('json')->success([
            'express_temp_id' => $data['config_export_temp_id'] ?? '',
            'to_name' => $data['config_export_to_name'] ?? '',
            'id' => $data['config_export_id'] ?? '',
            'to_tel' => $data['config_export_to_tel'] ?? '',
            'to_add' => $data['config_export_to_address'] ?? ''
        ]);
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
     * 移动端订单管理退款列表
     * @param Request $request
     * @param StoreOrderRefundServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function refundOrderList(Request $request, StoreOrderRefundServices $services)
    {
        $where = $request->getMore([
            ['order_id', ''],
            ['time', ''],
            ['refund_type', 0],
        ]);
        $data = $services->refundList($where)['list'];
        return app('json')->success($data);
    }

    /**
     * 订单详情
     * @param Request $request
     * @param $uni
     * @return mixed
     */
    public function refundOrderDetail(StoreOrderRefundServices $services, $uni)
    {
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
