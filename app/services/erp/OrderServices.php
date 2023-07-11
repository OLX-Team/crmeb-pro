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
declare (strict_types=1);

namespace app\services\erp;

use app\jobs\order\OrderSyncJob;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderDeliveryServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use crmeb\exceptions\AdminException;
use crmeb\services\erp\Erp as erpServices;
use think\facade\Log;

/**
 * Class OrderServices
 * @package app\services\erp
 */
class OrderServices
{
    protected $services;

    /**
     * OrderServices constructor.
     * @param erpServices $services
     */
    public function __construct(erpServices $services)
    {
        $this->services = $services;
    }

    /**
     * 订单上传
     * @param int $oid
     * @return bool
     * @throws \Exception
     */
    public function upload(int $oid): bool
    {
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $order = $orderServices->get($oid, ['*'], ['store']);

        if ($order['id'] < 1) {
            throw new AdminException('订单信息不能为空！');
        }

        // 过滤已退款订单
        if ($order['refund_status'] == 2) {
            return true;
        }

        $biz = [
            'shop_id' => $this->getShopId($order->store->erp_shop_id ?? 0), // 店铺编号
            'so_id' => $this->getErpOrderId($order['order_id'], $order['erp_order_id']), // 线上订单号, 长度 <= 50
            'shop_status' => 'WAIT_SELLER_SEND_GOODS',   // 订单状态 待发货
            'buyer_message' => $order['mark'],           // 买家留言
            'shop_buyer_id' => (string)$order['uid'],    // 买家帐号
            'receiver_name' => $order['real_name'],      // 收件人
            'receiver_mobile' => $order['user_phone'],   // 联系手机
            'pay_amount' => (float)$order['pay_price'],  // 应付金额，保留两位小数，单位元）
            'freight' => (float)$order['freight_price'], // 运费
            'order_date' => date('Y-m-d H:i:s', $order['add_time']), // 订单日期
            'shop_modified' => date('Y-m-d H:i:s', $order['add_time']), // 订单修改日期
            'items' => $this->getItems($oid),
            'pay' => [
                'outer_pay_id' => 'xxx', // 外部支付单号，最大50
                'pay_date' => date('Y-m-d H:i:s', $order['pay_time']),     // 支付日期
                'payment' => $order['pay_type'],        // 支付方式，最大20
                'seller_account' => 'seller',           // 卖家支付账号，最大 50
                'buyer_account' => 'buyer',             // 买家支付账号，最大 200
                'amount' => (float)$order['pay_price']  // 支付总额
            ]
        ];

        $biz = array_merge($biz, $this->getAddress($order['user_address']));

        $num = 3;
        for ($i = $num; $i >= 0; $i--) {
            if ($i <= 0) {
                Log::error(['msg' => 'ERP订单上传失败,调用均为异常', 'oid' => $oid]);
                return false;
            }

            try {
                $result = $this->services->serviceDriver('order')->ordersUpload([$biz]);
                if ($result['datas'][0]['issuccess'] && $order['erp_order_id'] != $biz['so_id']) {
                    $order->save(['erp_id' => $result['datas'][0]['o_id'], 'erp_order_id' => $biz['so_id']]);
                }
                break;
            } catch (\Exception $e) {
                usleep(1000 * 50);
                Log::error('ERP订单上传失败,原因:' . $e->getMessage());
            }
        }
        return true;
    }

    /**
     * 收货地址
     * @param string $userAddress
     * @return array
     */
    public function getAddress(string $userAddress): array
    {
        $receiver_state = $receiver_city = $receiver_district = $receiver_address = '';
        if (!empty($userAddress)) {
            $address = explode(' ', $userAddress);
            $receiver_state = $address[0] ?? '';     // 收货省份
            $receiver_city = $address[1] ?? '';      // 收货市
            $receiver_district = $address[2] ?? '';  // 收货区
            $receiver_address = $address[3] ?? '';   // 收货街道
            $receiver_address .= $address[4] ?? '';  // 街道
        }

        return compact('receiver_state', 'receiver_city', 'receiver_district', 'receiver_address');
    }

    /**
     * 获取erp指定订单商品详情
     * @param int $oid
     * @return array
     */
    public function getItems(int $oid): array
    {
        /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
        $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $cartInfo = $storeOrderCartInfoServices->getOrderCartInfo($oid);

        $items = [];
        foreach ($cartInfo as $cart) {
            $attrInfo = $cart['cart_info']['productInfo']['attrInfo'];
            $items[] = [
                'sku_id' => $attrInfo['code'],
                'shop_sku_id' => $attrInfo['code'],
                'base_price' => (float)bcdiv((string)$cart['cart_info']['truePrice'], (string)$cart['cart_info']['cart_num'], 2),
                'amount' => (float)$cart['cart_info']['truePrice'],
                'qty' => $cart['cart_info']['cart_num'],
                'pic' => $cart['cart_info']['productInfo']['image'],
                'name' => $cart['cart_info']['productInfo']['store_name'],
                'properties_value' => $attrInfo['suk'],
                'outer_oi_id' => $cart['cart_info']['product_attr_unique'],
            ];
        }
        return $items;
    }

    /**
     * 取消回调
     * @param array $cancel
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function cancelCallback(array $cancel): bool
    {
        try {
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);
            $orders = explode('_', $cancel['so_id']);
            $cancel['order_id'] = $orders[0] ?? '';
            $order = $orderServices->getOne(['order_id' => $cancel['order_id']], '*');
            if (!$order) {
                throw new AdminException('订单信息不能为空！');
            }

            $order = is_object($order) ? $order->toArray() : $order;
            $ids = [$order['erp_order_id']];

            // 获取子订单号
            $orders = $orderServices->getColumn(['pid' => $order['id']], 'erp_order_id', 'id');
            if ($orders) {
                $ids = array_values(array_unique(array_filter(array_merge($ids, $orders))));
            }

            $result = $this->services->serviceDriver('order')->ordersSingleQuery(['so_ids' => $ids]);
            // 取消申请退货
            $this->checkRefundApply((int)$order['id']);

            // 部分退货
            if (count($result['orders']) > 1) {
                $this->applyPartRefund($cancel, $order, $result['orders'], $orderServices);
            } else {
                $this->applyRefund($cancel, $order, $orderServices);
            }
        } catch (\Exception $e) {
            Log::error(['msg' => '订单取消失败,原因:' . $e->getMessage(), 'data' => $cancel]);
        }
        return true;
    }

    /**
     * 订单申请退款
     * @param array $cancel
     * @param array $order
     * @param StoreOrderServices $orderServices
     * @return void
     */
    protected function applyRefund(array $cancel, array $order, StoreOrderServices $orderServices)
    {
        try {
            $data['refund_reason_wap_explain'] = $cancel['remark'];
            $data['order_id'] = $cancel['order_id'];

            /** @var StoreOrderRefundServices $storeOrderRefundServices */
            $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);

            if ($storeOrderRefundServices->count(['store_order_id' => $order['id'], 'refund_type' => [0, 1, 2, 4, 5], 'is_cancel' => 0, 'is_del' => 1])) {
                throw new AdminException('请先处理售后申请！');
            }
            //0元退款
            if ($order['pay_price'] == 0 && in_array($order['refund_status'], [0, 1])) {
                $refund_price = 0;
            } else {
                if ($order['pay_price'] == $order['refund_price']) {
                    throw new AdminException('已退完支付金额!不能再退款了！');
                }
                $refund_price = bcsub((string)$order['pay_price'], $order['refund_price'], 2);
            }
            $data['refund_status'] = 2;
            $data['refund_type'] = 6;

            $refund_data['pay_price'] = $order['pay_price'];
            $refund_data['refund_price'] = $refund_price;
            if ($order['refund_price'] > 0) {
                mt_srand();
                $refund_data['refund_id'] = $order['order_id'] . rand(100, 999);
            }
            //主动退款清楚原本退款单
            $storeOrderRefundServices->delete(['store_order_id' => $order['id']]);

            /** @var StoreOrderCreateServices $service */
            $storeOrderCreateServices = app()->make(StoreOrderCreateServices::class);

            //生成退款订单
            $refundOrderData['uid'] = $order['uid'];
            $refundOrderData['store_id'] = $order['store_id'];
            $refundOrderData['store_order_id'] = $order['id'];
            $refundOrderData['refund_num'] = $order['total_num'];
            $refundOrderData['refund_type'] = $data['refund_type'];
            $refundOrderData['refund_price'] = $order['pay_price'];
            $refundOrderData['refunded_price'] = $refund_price;
            $refundOrderData['refund_reason'] = $cancel['remark'];
            $refundOrderData['order_id'] = $storeOrderCreateServices->getNewOrderId('');
            $refundOrderData['refunded_time'] = time();
            $refundOrderData['add_time'] = time();

            /** @var StoreOrderCartInfoServices $orderInfoServices */
            $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
            $cartInfos = $storeOrderCartInfoServices->getCartColunm(['oid' => $order['id']], 'id,cart_id,cart_num,cart_info');
            foreach ($cartInfos as &$cartInfo) {
                $cartInfo['cart_info'] = is_string($cartInfo['cart_info']) ? json_decode($cartInfo['cart_info'], true) : $cartInfo['cart_info'];
            }
            $refundOrderData['cart_info'] = json_encode(array_column($cartInfos, 'cart_info'));
            $res = $storeOrderRefundServices->save($refundOrderData);

            //修改订单退款状态
            if ($storeOrderRefundServices->agreeRefund((int)$res->id, $refund_data)) {
                $orderServices->update($order['id'], $data);
                $erpOrderId = [];
                if ($order['pid'] < 0) {
                    $childOrder = $orderServices->getOne([['pid', '=', $order['id']], ['status', '=', 0], ['refund_status', '=', 0]], 'id, pid, status, refund_status, erp_order_id');
                    if ($childOrder) {
                        $erpOrderId[] = $childOrder['erp_order_id'];
                    }
                }

                OrderSyncJob::dispatchDo('reorderOrder', [$order['erp_order_id'], $erpOrderId, 0]);
            } else {
                $storeOrderRefundServices->storeProductOrderRefundYFasle((int)$order['id'], $refund_price);
            }
        } catch (\Exception $e) {
            Log::error('订单申请退款失败,原因:' . $e->getMessage());
        }
    }

    /**
     * 部分退款申请
     * @param array $cancel
     * @param array $order
     * @param array $orders
     * @param StoreOrderServices $orderServices
     * @return bool|void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    protected function applyPartRefund(array $cancel, array $order, array $orders, StoreOrderServices $orderServices)
    {
        try {
            $cartWhere = ['oid' => $order['id']];

            // 获取拆单信息
            if ($order['pid'] < 0) {
                $oIds = $orderServices->Value([['pid', '=', $order['id']], ['refund_status', '<', 2], ['status', '=', 0]], 'GROUP_CONCAT(id)');
                if (!empty($oIds)) {
                    $oIds = array_filter(explode(',', $oIds));
                }
                $cartWhere = ['oid' => $oIds, 'split_status' => [0, 1]];
            }

            /** @var StoreOrderCartInfoServices $orderInfoServices */
            $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);

            $cartInfos = $storeOrderCartInfoServices->getCartColunm($cartWhere, 'id,oid,cart_id,cart_num,split_surplus_num,cart_info');
            foreach ($cartInfos as &$cartInfo) {
                $cartInfo['cart_info'] = is_string($cartInfo['cart_info']) ? json_decode($cartInfo['cart_info'], true) : $cartInfo['cart_info'];
            }

            // 过滤已退商品
            $cancelled = $this->filter($orders, $order['id']);
            if (empty($cancelled)) {
                return true;
            }

            $cartOid = 0;
            $cartIds = [];
            foreach ($cartInfos as $cart) {
                $num = $cancelled[$cart['cart_info']['productInfo']['attrInfo']['code']] ?? 0;
                if ($num > 0) {
                    $allow = $num <= $cart['split_surplus_num'] ? $num : $num - $cart['split_surplus_num'];
                    $cartId = (string)$cart['cart_id'];
                    if (isset($cartIds[$cartId])) {
                        $cartIds[$cartId]['cart_num'] += $allow;
                    } else {
                        $cartIds[$cartId] = ['cart_id' => $cartId, 'cart_num' => $allow, 'oid' => $cart['oid']];
                        $cartOid = $cart['oid'];
                    }
                }
            }

            if ($order['pid'] < 0) {
                $oid = $orderServices->Value([['pid', '=', $order['id']], ['refund_status', '<', 2], ['status', '=', 0]], 'id');
                if ($oid < 1) {
                    return true;
                }
                $order = $orderServices->getOne(['id' => $oid], '*');
                if (!$order) {
                    throw new AdminException('订单信息不能为空！');
                }
            }

            /** @var StoreOrderRefundServices $storeOrderRefundServices */
            $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);

            $refundData = ['refund_reason' => $cancel['remark'], 'refund_explain' => 'ERP取消订单', 'refund_img' => json_encode([])];
            $refundId = $storeOrderRefundServices->applyRefund($cartOid, $order['uid'], $order, array_values($cartIds), 1, 0.00, $refundData, 1, false);
            if ($refundId) {
                $refund = $storeOrderRefundServices->get($refundId);
                // 立即退款
                $this->receivedCallback(['outer_as_id' => $refund['order_id']], 1);
            }
        } catch (\Exception $e) {
            Log::error('订单部分退款申请失败,原因:' . $e->getMessage());
        }
    }

    /**
     * 订单发货
     * @param array $data
     * @return bool
     */
    public function deliverCallback(array $data): bool
    {
        try {
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);

            /** @var StoreOrderDeliveryServices $orderDeliveryServices */
            $orderDeliveryServices = app()->make(StoreOrderDeliveryServices::class);
            $order = $orderServices->getOne(['erp_id' => $data['o_id'], 'status' => 0], '*');
            $deliver = [
                "type" => 1, // 订单统一使用快递配送
                "delivery_name" => $data['logistics_company'], // 快递公司名称
                "delivery_id" => $data['l_id'],                // 快递公司单号
                "delivery_code" => $data['lc_id'],             // 快递公司编码
                "express_record_type" => "1", // 快递
                'express_temp_id' => '',
                'expressTemp' => [],
                "to_name" => '',
                "to_tel" => '',
                "to_addr" => '',
                "sh_delivery_name" => "",
                "sh_delivery_id" => "",
                "sh_delivery_uid" => "",
                "fictitious_content" => "",
                "export_open" => true,
                'erp_id' => (int)$data['o_id'] // 内部订单编号
            ];
            // 单个订单发货
            if (!empty($order)) {
                // 取消申请退货
                $this->checkRefundApply($order['id']);
                $other = ["to_name" => $order['real_name'], "to_tel" => $order['user_phone'], "to_addr" => $order['user_address']];
                $orderDeliveryServices->delivery($order['id'], array_merge($deliver, $other));
                return true;
            }

            // 根据订单ID查找为空则订单异常
            $order = $orderServices->getOne(['erp_order_id' => $data['so_id']], '*');
            if (!$order) {
                throw new AdminException('订单信息获取异常，不能进行自动发货！');
            }

            // 拆单发货
            /** @var StoreOrderCartInfoServices $cartInfoServices */
            $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
            $list = $cartInfoServices->getSplitCartList($order['id']);

            if ($order['pid'] < 0 && empty($list)) {
                $oIds = $orderServices->Value([['pid', '=', $order['id']], ['status', '=', 0], ['refund_status', '<', 2]], 'GROUP_CONCAT(id)');
                if ($oIds) {
                    $oIds = array_filter(explode(',', $oIds));
                    // 获取拆单信息进行匹配是否拆单
                    $cartWhere = ['oid' => $oIds, 'split_status' => [0, 1]];
                    $list = $cartInfoServices->getCartColunm($cartWhere, 'id,oid,cart_id,cart_num,split_surplus_num,cart_info');
                    foreach ($list as &$cartInfo) {
                        $cartInfo['cart_info'] = is_string($cartInfo['cart_info']) ? json_decode($cartInfo['cart_info'], true) : $cartInfo['cart_info'];
                    }
                }
            }

            // 获取发货数据
            [$deliver['cart_ids'], $oid] = $this->generateCart($data['items'], $list);
            if (empty($deliver['cart_ids'])) {
                return true;
            }

            // 取消申请退货
            $this->checkRefundApply($oid);

            $other = ["to_name" => $order['real_name'], "to_tel" => $order['user_phone'], "to_addr" => $order['user_address']];
            $orderDeliveryServices->splitDelivery($oid, array_merge($deliver, $other));
        } catch (\Exception $e) {
            Log::error('发货回调失败, 原因:' . $e->getMessage());
        }
        return true;
    }

    /**
     * 订单拆分后的数据
     * @param array $items
     * @param array $list
     * @return array
     * @throws Exception
     */
    public function generateCart(array $items, array $list): array
    {
        $carts = [];
        $oid = 0;
        foreach ($items as $item) {
            foreach ($list as $cart) {
                if ($item['sku_id'] == $cart['cart_info']['productInfo']['attrInfo']['code']) {
                    $carts[] = ['cart_id' => $cart['cart_id'], 'cart_num' => (int)$item['qty']];
                    $oid = $cart['oid'];
                }
            }
        }

        return [$carts, $oid];
    }

    /**
     * 售后单上传
     * @param array $refundIds
     * @return bool
     */
    public function refundOrderUpload(array $refundIds, string $shopStatus = 'WAIT_SELLER_CONFIRM_GOODS')
    {
        try {
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);

            /** @var StoreOrderRefundServices $storeOrderRefundServices */
            $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);

            $list = [];
            foreach ($refundIds as $refundId) {
                $refund = $storeOrderRefundServices->getOne(['id' => $refundId], '*', ['order']);
                if ($refund->order['pid'] > 0) {
                    $order = $orderServices->get($refund->order['pid']);
                    if (!$order) {
                        throw new AdminException('父级订单信息不能为空！');
                    }
                    $soId = $order->erp_order_id;
                } else {
                    $soId = $refund->order->erp_order_id;
                }

                $refundData = [
                    'shop_id' => $this->getShopId($refund->order->store->erp_shop_id ?? 0), // 店铺编号
                    'outer_as_id' => $refund['order_id'],   // 退货退款单号，平台唯一
                    'so_id' => $soId,                       // 平台订单号
                    'type' => '普通退货',                    // 售后类型
                    'shop_status' => $shopStatus,           // WAIT_SELLER_CONFIRM_GOODS:等待卖家确认收货  CLOSED:退款关闭
                    'good_status' => 'BUYER_RETURNED_GOODS', // 买家已退货
                    'question_type' => null,
                    'remark' => $refund['refund_reason'],    // 问题类型
                    'total_amount' => (float)$refund->order['pay_price'], // 原单据总金额
                    'refund' => (float)$refund['refund_price'],   // 卖家应退金额
                    'payment' => 0.00                             // 买家应补偿金额
                ];

                $items = [];
                foreach ($refund['cart_info'] as $cart) {
                    $items[] = [
                        'sku_id' => $cart['productInfo']['attrInfo']['code'], // 商家商品编码
                        'qty' => (int)$cart['cart_num'], // 退货数量
                        'amount' => bcadd(bcmul((string)($cart['truePrice'] ?? 0), (string)$cart['cart_num'], 4), (string)($cart['postage_price'] ?? 0), 2), // SKU退款金额
                        'type' => '退货',
                        'pic' => $cart['productInfo']['attrInfo']['image']
                    ];
                }
                $refundData['items'] = $items;
                $list[] = $refundData;
            }

            $result = $this->services->serviceDriver('order')->afterSaleUpload($list);
            if ($result['datas'][0]['issuccess'] != true) {
                Log::error('退货单:' . implode(',', $refundIds) . ' 发送失败，原因:' . $result['datas'][0]['msg']);
            }
        } catch (\Exception $e) {
            Log::error(['msg' => '售后单上传失败，原因:' . $e->getMessage(), 'data' => ['refundIds' => $refundIds, 'shopStatus' => $shopStatus]]);
        }
    }

    /**
     * 获取店铺编号
     * @param int $erpShopId
     * @return int
     */
    public function getShopId(int $erpShopId): int
    {
        if ($erpShopId < 1) {
            $erpShopId = sys_config('jst_default_shopid');
        }

        return (int)$erpShopId;
    }

    /**
     * ERP订单取消
     * @param string $orderId
     * @param array $erpOrderId
     * @return void
     */
    public function cancelOrder(string $orderId, array $erpOrderId = [])
    {
        $erpOrderId[] = $orderId;
        $result = $this->services->serviceDriver('order')->ordersSingleQuery(['so_ids' => array_filter($erpOrderId), 'status' => 'WaitConfirm']);
        if (!$erpIds = array_column($result['orders'], 'o_id')) {
            Log::error(['msg' => 'ERP订单取消失败,原因:未找到待发货订单', 'data' => [$orderId, $erpOrderId]]);
            return false;
        }

        $cancelData = ['o_ids' => $erpIds, 'cancel_type' => '用户申请退货, 重新生成订单'];
        $num = 3;
        for ($i = $num; $i >= 0; $i--) {
            if ($i <= 0) {
                Log::error(['msg' => 'ERP订单取消失败,调用均为异常', 'data' => [$orderId, $erpOrderId]]);
                return false;
            }

            try {
                $this->services->serviceDriver('order')->orderByOIdCancel($cancelData);
                return true;
            } catch (\Exception $e) {
                usleep(1000 * 50);
                Log::error('ERP订单取消失败,原因:' . $e->getMessage());
            }
        }
        return false;
    }

    /**
     * 售后收货回调
     * @param array $refund
     * @param int $type
     * @param bool $isUpload
     * @return void
     */
    public function receivedCallback(array $refund, int $type = 1, bool $isUpload = false)
    {
        try {
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);

            /** @var StoreOrderRefundServices $orderRefundServices */
            $orderRefundServices = app()->make(StoreOrderRefundServices::class);

            $data = [
                'refund_type' => 6, // 已退款
                'refunded_time' => time()
            ];

            if ($type == 1) {
                $data['refund_status'] = 2;
                $data['refund_type'] = 6;
            } else if ($type == 2) {
                $data['refund_status'] = 0;
                $data['refund_type'] = 3;
            }

            $orderRefund = $orderRefundServices->getOne(['order_id' => $refund['outer_as_id']]);
            if (!$orderRefund) {
                throw new AdminException('Data does not exist!');
            }

            $id = (int)$orderRefund['id'];

            if ($type == 2) {
                $refundData = [
                    'refuse_reason' => 'ERP订单发货取消',
                    'refund_type' => 3,
                    'refunded_time' => time()
                ];
                $orderRefundServices->refuseRefund($id, $refundData, $orderRefund);
            } else {
                if ($orderRefund['is_cancel'] == 1) {
                    throw new AdminException('用户已取消申请!');
                }

                $order = $orderServices->get((int)$orderRefund['store_order_id']);
                if (!$order) {
                    throw new AdminException('Data does not exist!');
                }

                if (!in_array($orderRefund['refund_type'], [1, 2, 5])) {
                    throw new AdminException('售后订单状态不支持该操作');
                }

                // 0元退款
                if ($orderRefund['refund_price'] == 0) {
                    $refund_price = 0;
                } else {
                    $refund_price = $orderRefund['refund_price'];
                    if ($orderRefund['refund_price'] == $orderRefund['refunded_price']) {
                        throw new AdminException('已退完支付金额!不能再退款了');
                    }

                    $data['refunded_price'] = bcadd($refund_price, $orderRefund['refunded_price'], 2);
                    $bj = bccomp((string)$orderRefund['refund_price'], $data['refunded_price'], 2);
                    if ($bj < 0) {
                        throw new AdminException('退款金额大于支付金额，请修改退款金额');
                    }
                }
                $refund_data['pay_price'] = $order['pay_price'];
                $refund_data['refund_price'] = $refund_price;
                if ($order['refund_price'] > 0) {
                    mt_srand();
                    $refund_data['refund_id'] = $order['order_id'] . rand(100, 999);
                }
                // 修改订单退款状态
                unset($data['refund_price']);
                if ($orderRefundServices->agreeRefund($id, $refund_data)) {
                    $orderRefundServices->update($id, $data);
                } else {
                    $orderRefundServices->storeProductOrderRefundYFasle($id, $refund_price);
                    throw new AdminException('退货单退款失败');
                }
                // 重新上传订单
                if ($type && $isUpload) {
                    $orders = explode('_', $refund['so_id']);
                    $order = $orderServices->getOne(['order_id' => $orders[0] ?? ''], 'id, pid, status, refund_status, erp_order_id');
                    if (!$order) {
                        throw new AdminException('订单不存在');
                    }

                    $erpOrderId = [];
                    $oid = (int)$order['id'];
                    $status = (int)$order['status'];
                    if ($order['pid'] < 0) {
                        $childOrder = $orderServices->getOne([['pid', '=', $order['id']], ['status', '=', 0], ['refund_status', '=', 0]], 'id, pid, status, refund_status, erp_order_id');
                        $refundChild = $orderServices->getColumn([['pid', '=', $order['id']], ['status', '=', 0], ['refund_status', '=', 2]], 'erp_order_id');
                        if (!empty($refundChild)) {
                            $erpOrderId = array_unique($refundChild);
                        }
                        if ($childOrder) {
                            $oid = (int)$childOrder['id'];
                            $erpOrderId[] = $childOrder['erp_order_id'];
                            $status = (int)$childOrder['status'];
                        }
                    }

                    if ($oid > 0) {
                        !in_array($status, [2,4]) && OrderSyncJob::dispatchDo('reorderOrder', [$order['erp_order_id'], $erpOrderId, $oid]);
                    } else {
                        Log::error(['msg' => '售后收货回调没有需要取消的订单', 'data' => $refund]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error(['msg' => '售后收货回调失败,原因:' . $e->getMessage(), 'data' => ['refund' => $refund, 'type' => $type, 'isUpload' => $isUpload]]);
        }
    }

    /**
     * 过滤ERP不可退订单
     * @param array $orders
     * @param int $oid
     * @return array
     */
    protected function filter(array $orders, int $oid): array
    {
        $cancelled = $waitConfirm = [];
        foreach ($orders as $cartOrder) {
            if (in_array($cartOrder['status'], ['WaitConfirm', 'Sent'])) {
                foreach ($cartOrder['items'] as $item) {
                    if (!isset($waitConfirm[$item['sku_id']])) {
                        $waitConfirm[$item['sku_id']] = $item['qty'];
                    } else {
                        $waitConfirm[$item['sku_id']] += $item['qty'];
                    }
                }
            }
        }

        /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
        $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $cartInfo = $storeOrderCartInfoServices->getOrderCartInfo($oid);

        foreach ($cartInfo as $cart) {
            $attrInfo = $cart['cart_info']['productInfo']['attrInfo'];
            $cancelled[$attrInfo['code']] = $cart['cart_info']['cart_num'];
            if (isset($waitConfirm[$attrInfo['code']])) {
                $cancelled[$attrInfo['code']] -= $waitConfirm[$attrInfo['code']];
                if ($cancelled[$attrInfo['code']] < 1) {
                    unset($cancelled[$attrInfo['code']]);
                }
            }
        }

        $refundData = $this->getOrderRefund($oid);
        foreach ($refundData as $key => $item) {
            $num = $cancelled[$key] ?? 0;
            if ($num > 0) {
                $cancelled[$key] -= $item;
                if ($cancelled[$key] < 1) {
                    unset($cancelled[$key]);
                }
            }
        }
        return $cancelled;
    }

    /**
     * 累计订单已退数量
     * @param int $oid
     * @return array
     */
    public function getOrderRefund(int $oid): array
    {
        $attrData = [];
        $refunds = $this->orderRefundList($oid);
        foreach ($refunds as $refund) {
            foreach ($refund['cart_info'] as $item) {
                if (!isset($attrData[$item['productInfo']['attrInfo']['code']])) {
                    $attrData[$item['productInfo']['attrInfo']['code']] = $item['cart_num'];
                } else {
                    $attrData[$item['productInfo']['attrInfo']['code']] += $item['cart_num'];
                }
            }
        }
        return $attrData;
    }

    /**
     * 存在申请退货单则关闭
     * @param int $oid
     * @return bool
     */
    public function checkRefundApply(int $oid): bool
    {
        $refunds = $this->orderRefundList($oid, 1);

        if (!empty($refunds)) {
            $refundIds = [];
            foreach ($refunds as $refund) {
                $refundIds[] = $refund['id'];
                $this->receivedCallback(['outer_as_id' => $refund['order_id']], 2);
            }
            // 关闭 ERP 退货单
            $this->refundOrderUpload($refundIds, 'CLOSED');
        }
        return true;
    }

    /**
     * 订单退货单相关信息
     * @param int $id
     * @param int $refundType
     * @return array
     */
    public function orderRefundList(int $id, int $refundType = 4): array
    {
        /** @var StoreOrderRefundServices $storeOrderRefundServices */
        $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);
        $refunds = $storeOrderRefundServices->getRefundList(['store_order_id' => $id, 'refundTypes' => $refundType], 'id, store_order_id, store_id, order_id, uid, cart_info');

        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $ids = $orderServices->Value(['pid' => $id], 'GROUP_CONCAT(id)');
        if ($ids) {
            $ids = array_filter(explode(',', $ids));
            $list = $storeOrderRefundServices->getRefundList(['store_order_id' => $ids, 'refundTypes' => $refundType], 'id, store_order_id, store_id, order_id, uid, cart_info');
            $refunds = array_merge($refunds, $list);
        }
        return $refunds;
    }

    /**
     * ERP订单号
     * @return string $orderId
     * @return string $erpOrderId
     */
    public function getErpOrderId(string $orderId, string $erpOrderId = ''): string
    {
        if (!empty($erpOrderId)) {
            $arr = explode('_', $erpOrderId);
            if (count($arr) == 1) {
                return $erpOrderId . '_1';
            }

            $num = end($arr);
            return $arr[0] . '_' . ++$num;
        }
        return $orderId;
    }
}