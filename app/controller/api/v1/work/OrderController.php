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

namespace app\controller\api\v1\work;


use app\Request;
use app\services\activity\combination\StorePinkServices;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\message\service\StoreServiceRecordServices;
use app\services\order\StoreOrderEconomizeServices;
use app\services\order\StoreOrderPromotionsServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderStatusServices;
use app\services\product\product\StoreProductServices;
use app\services\store\SystemStoreServices;
use app\services\user\UserInvoiceServices;
use app\services\user\UserServices;

/**
 * 订单
 * Class OrderController
 * @package app\controller\api\v1\work
 */
class OrderController extends BaseWorkController
{

    /**
     * OrderController constructor.
     * @param StoreOrderServices $services
     */
    public function __construct(StoreOrderServices $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * 获取订单列表
     * @param Request $request
     * @param StoreServiceRecordServices $services
     * @param StoreOrderRefundServices $storeOrderRefundServices
     * @return mixed
     */
    public function getUserOrderList(Request $request, StoreServiceRecordServices $services, StoreOrderRefundServices $storeOrderRefundServices)
    {
        $where = $request->getMore([
            ['type', '', '', 'status'],
            ['search', '', '', 'real_name'],
        ]);
        $uid = $this->clientInfo['uid'] ?? 0;
        if (!$uid) {
            return $this->success(['list' => [], 'count' => 0]);
        }
        $where['uid'] = $uid;
        $where['is_del'] = 0;
        $where['is_system_del'] = 0;
        $where['refund_type'] = [0, 1, 3, 6];
        if ($where['status'] == -1) {
            $list = $storeOrderRefundServices->refundList(['uid' => $where['uid'], 'real_name' => $where['real_name'], 'refund_type' => [1, 2, 4, 5]])['list'] ?? [];
        } else {
            $list = $this->service->getOrderApiList($where + ['pid' => 0], ['*'], ['pink', 'invoice']);
        }
        return $this->success($list);
    }


    /**
     * 订单详情
     * @param StoreOrderEconomizeServices $services
     * @param StoreOrderPromotionsServices $storeOrderPromotiosServices
     * @param $id 订单id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderInfo(StoreOrderEconomizeServices $services, StoreOrderPromotionsServices $storeOrderPromotiosServices, $id)
    {
        if (!$id || !($orderInfo = $this->service->get($id))) {
            return $this->fail('订单不存在');
        }
        $order = $orderInfo->toArray();
        $order['split'] = [];
        //是否开启门店自提
        $store_self_mention = sys_config('store_self_mention');
        //关闭门店自提后 订单隐藏门店信息
        if ($store_self_mention == 0) $order['shipping_type'] = 1;
        if ($order['verify_code']) {
            $verify_code = $order['verify_code'];
            $verify[] = substr($verify_code, 0, 4);
            $verify[] = substr($verify_code, 4, 4);
            $verify[] = substr($verify_code, 8);
            $order['_verify_code'] = implode(' ', $verify);
        }
        //收银台订单 用户无信息 手机号
        if ($order['shipping_type'] == 4 && $order['uid'] && !$order['real_name']) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->getUserCacheInfo((int)$order['uid']);
            $order['real_name'] = $userInfo['nickname'];
            $order['user_phone'] = $userInfo['phone'];
        }
        $order['add_time_y'] = date('Y-m-d', $order['add_time']);
        $order['add_time_h'] = date('H:i:s', $order['add_time']);
        $order['system_store'] = false;
        if (!$order['store_id'] && $order['shipping_type'] == 2) {
            $order['store_id'] = $this->service->value(['pid' => $order['id']], 'store_id');
        }
        if ($order['store_id']) {
            /** @var SystemStoreServices $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $order['system_store'] = $storeServices->getStoreDispose($order['store_id']);
        }
        $order['mapKey'] = sys_config('tengxun_map_key');
        $order['yue_pay_status'] = (int)sys_config('balance_func_status') && (int)sys_config('yue_pay_status') == 1 ? (int)1 : (int)2;//余额支付 1 开启 2 关闭
        $order['pay_weixin_open'] = (int)sys_config('pay_weixin_open') ?? 0;//微信支付 1 开启 0 关闭
        $order['ali_pay_status'] = sys_config('ali_pay_status') ? true : false;//支付包支付 1 开启 0 关闭

        $orderData = $this->service->tidyOrder($order, true, true);
        //核算优惠金额
        $vipTruePrice = 0;
        $refund_num = 0;
        foreach ($orderData['cartInfo'] ?? [] as $key => &$cart) {
            $vipTruePrice = bcadd((string)$vipTruePrice, (string)$cart['vip_sum_truePrice'], 2);
            $refund_num = bcadd((string)$refund_num, (string)$cart['refund_num'], 0);
        }
        $orderData['vip_true_price'] = $vipTruePrice;
        $orderData['total_price'] = floatval(bcsub((string)$orderData['total_price'], (string)$vipTruePrice, 2));
        //优惠活动优惠详情
        $orderData['promotions_detail'] = $storeOrderPromotiosServices->getOrderPromotionsDetail((int)$order['id']);
        //同步查询订单商品为查询到 查询缓存信息
        if (!$orderData['cartInfo']) {
            $cartGroup = $this->service->getCacheOrderInfo((int)$order['uid'], $order['unique']);
            $orderData['cartInfo'] = $cartGroup['cartInfo'] ?? [];
        }

        $economize = $services->get(['order_id' => $order['order_id']], ['postage_price', 'member_price']);
        if ($economize) {
            $orderData['postage_price'] = $economize['postage_price'];
            $orderData['member_price'] = $economize['member_price'];
        } else {
            $orderData['postage_price'] = 0;
            $orderData['member_price'] = 0;
        }
        $orderData['routine_contact_type'] = sys_config('routine_contact_type', 0);
        /** @var UserInvoiceServices $userInvoice */
        $userInvoice = app()->make(UserInvoiceServices::class);
        $invoice_func = $userInvoice->invoiceFuncStatus();
        $orderData['invoice_func'] = $invoice_func['invoice_func'];
        $orderData['special_invoice'] = $invoice_func['special_invoice'];
        $orderData['refund_cartInfo'] = [];
        $orderData['refund_total_num'] = $orderData['total_num'];
        $orderData['refund_pay_price'] = $orderData['pay_price'];
        $orderData['is_apply_refund'] = $refund_num >= $orderData['total_num'] ? false : true;
        $orderData['is_batch_refund'] = count($orderData['cartInfo']) > 1 ? true : false;
        $orderData['pinkStatus'] = null;
        if ($orderData['type'] == 3) {
            /** @var StorePinkServices $pinkService */
            $pinkService = app()->make(StorePinkServices::class);
            $orderData['pinkStatus'] = $pinkService->value(['order_id' => $orderData['order_id']], 'status');
        }

        /** @var StoreOrderStatusServices $statusServices */
        $statusServices = app()->make(StoreOrderStatusServices::class);
        $log = $statusServices->getColumn(['oid' => $order['id']], 'change_time', 'change_type');
        if (isset($log['delivery'])) {
            $delivery = date('Y-m-d', $log['delivery']);
        } elseif (isset($log['delivery_goods'])) {
            $delivery = date('Y-m-d', $log['delivery_goods']);
        } elseif (isset($log['delivery_fictitious'])) {
            $delivery = date('Y-m-d', $log['delivery_fictitious']);
        } else {
            $delivery = '';
        }
        $orderData['order_log'] = [
            'create' => isset($log['cache_key_create_order']) ? date('Y-m-d', $log['cache_key_create_order']) : '',
            'pay' => isset($log['pay_success']) ? date('Y-m-d', $log['pay_success']) : '',
            'delivery' => $delivery,
            'take' => isset($log['take_delivery']) ? date('Y-m-d', $log['take_delivery']) : '',
            'complete' => isset($log['check_order_over']) ? date('Y-m-d', $log['check_order_over']) : '',
        ];
        if ($orderData['give_coupon']) {
            $couponIds = is_string($orderData['give_coupon']) ? explode(',', $orderData['give_coupon']) : $orderData['give_coupon'];
            /** @var StoreCouponIssueServices $couponIssueService */
            $couponIssueService = app()->make(StoreCouponIssueServices::class);
            $orderData['give_coupon'] = $couponIssueService->getColumn([['id', 'IN', $couponIds]], 'id,coupon_title');
        }
        $orderInfo = $orderData;
        /** @var UserServices $services */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->get($orderInfo['uid']);
        if (!$userInfo) {
            return $this->fail('用户信息不存在');
        }
        $userInfo = $userInfo->hidden(['pwd', 'add_ip', 'last_ip', 'login_type']);
        $userInfo['spread_name'] = '';
        if ($userInfo['spread_uid']) {
            $userInfo['spread_name'] = $userServices->value(['uid' => $userInfo['spread_uid']], 'nickname');
        }
        return $this->success(compact('orderInfo', 'userInfo'));
    }

}
