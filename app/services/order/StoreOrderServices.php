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
namespace app\services\order;

use app\dao\order\StoreOrderDao;
use app\jobs\BatchHandleJob;
use app\jobs\activity\StorePromotionsJob;
use app\jobs\order\AutoOrderUnpaidCancelJob;
use app\jobs\order\SpliteStoreOrderJob;
use app\services\activity\combination\StorePinkServices;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\activity\promotions\StorePromotionsServices;
use app\services\BaseServices;
use app\services\product\branch\StoreBranchProductAttrValueServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\product\StoreProductServices;
use app\services\other\queue\QueueAuxiliaryServices;
use app\services\other\queue\QueueServices;
use app\services\pay\PayServices;
use app\services\product\product\StoreProductLogServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\store\finance\StoreFinanceFlowServices;
use app\services\store\SystemStoreServices;
use app\services\user\UserInvoiceServices;
use app\services\user\UserServices;
use app\services\product\product\StoreProductReplyServices;
use app\services\user\UserAddressServices;
use app\services\user\level\UserLevelServices;
use app\services\wechat\WechatUserServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use crmeb\services\FileService;
use crmeb\services\FormBuilder as Form;
use crmeb\services\printer\Printer;
use crmeb\services\SystemConfigService;
use crmeb\traits\ServicesTrait;
use crmeb\utils\Arr;
use think\exception\ValidateException;
use think\facade\Log;

/**
 * Class StoreOrderServices
 * @package app\services\order
 * @method getOrderIdsCount(array $ids) 获取订单id下没有删除的订单数量
 * @method StoreOrderDao getUserOrderDetail(string $key, int $uid, array $with = []) 获取订单详情
 * @method chartTimePrice($start, $stop) 获取当前时间到指定时间的支付金额 管理员
 * @method chartTimeNumber($start, $stop) 获取当前时间到指定时间的支付订单数 管理员
 * @method together(array $where, string $field, string $together = 'sum') 聚合查询
 * @method getBuyCount($uid, $type, $activity_id) 获取用户已购买此活动商品的个数
 * @method getDistinctCount(array $where, $field, ?bool $search = true)
 * @method getTrendData($time, $type, $timeType, $str) 用户趋势
 * @method getRegion($time, $channelType) 地域统计
 * @method getProductTrend($time, $timeType, $field, $str) 商品趋势
 * @method getSonOrder(int $id, string $field = '*', int $type = 1) 获取拆单之后的子订单
 * @method getOrderDumpData(array $where, array $ids = [], $filed = "*") 获取批量打印电子面单数据
 * @method getOrderHistoryList(int $storeId, int $staffId, array $uid, int $limit = 20) 获取门店下的购买历史记录
 * @method newOrderId($status, int $store_id = 0) 获取新的未通知订单
 */
class StoreOrderServices extends BaseServices
{
    use ServicesTrait;

    /**
     * 发货类型
     * @var string[]
     */
    public $deliveryType = ['send' => '商家配送', 'express' => '快递配送', 'fictitious' => '虚拟发货', 'delivery_part_split' => '拆分部分发货', 'delivery_split' => '拆分发货完成'];

    /**
     * StoreOrderProductServices constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取门店订单统计
     * @param int $storeId
     * @return array
     */
    public function getStoreOrderHeader(int $storeId)
    {
        return [
            'cashier' => $this->dao->count(['pid' => 0, 'type' => 6, 'is_system_del' => 0, 'store_id' => $storeId]),
            'delivery' => $this->dao->count(['pid' => 0, 'type' => 7, 'is_system_del' => 0, 'store_id' => $storeId]),
            'writeoff' => $this->dao->count(['pid' => 0, 'type' => 5, 'is_system_del' => 0, 'store_id' => $storeId]),
        ];
    }

    /**
     * 获取列表
     * @param array $where
     * @param array $field
     * @param array $with
     * @param bool $abridge
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderList(array $where, array $field = ['*'], array $with = [], bool $abridge = false)
    {
        [$page, $limit] = $this->getPageValue();
        $data = $this->dao->getOrderList($where, $field, $page, $limit, $with);
        $count = $this->dao->count($where);
        $stat = [];
        $batch_url = "file/upload/1";
        if ($data) {
            $data = $this->tidyOrderList($data, true, $abridge);
            foreach ($data as &$item) {
                $refund_num = array_sum(array_column($item['refund'], 'refund_num'));
                $cart_num = 0;
                foreach ($item['_info'] as $items) {
					if (isset($items['cart_info']['is_gift']) && $items['cart_info']['is_gift']) continue;
                    $cart_num += $items['cart_info']['cart_num'];
                }
                $item['is_all_refund'] = $refund_num == $cart_num;
            }
        }
        return compact('data', 'count', 'stat', 'batch_url');
    }

    /**
     * 获取列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSplitOrderList(array $where, array $field = ['*'], array $with = [])
    {
        $data = $this->dao->getOrderList($where, $field, 0, 0, $with);
        if ($data) {
            $data = $this->tidyOrderList($data);
        }
        return $data;
    }

    /**
     * 前端订单列表
     * @param array $where
     * @param array|string[] $field
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderApiList(array $where, array $field = ['*'], array $with = [])
    {
        [$page, $limit] = $this->getPageValue();
        if (isset($where['status']) && $where['status'] === '') {
            $data = $this->dao->getOrderList($where, $field, $page, $limit, $with, 'id DESC');
        } else {
            $data = $this->dao->getOrderList($where, $field, $page, $limit, $with);
        }
        foreach ($data as &$item) {
            $item = $this->tidyOrder($item, true);
			$cart_num = 0;
            foreach ($item['cartInfo'] ?: [] as $key => $product) {
                if (isset($item['_status']['_type']) && $item['_status']['_type'] == 3) {
                    $item['cartInfo'][$key]['add_time'] = isset($product['add_time']) ? date('Y-m-d H:i', (int)$product['add_time']) : '时间错误';
                }
                $item['cartInfo'][$key]['productInfo']['price'] = $product['truePrice'] ?? 0;

				if (isset($product['is_gift']) && $product['is_gift']) continue;
				$cart_num += $product['cart_num'];
            }
            if (count($item['refund'])) {
                $refund_num = array_sum(array_column($item['refund'], 'refund_num'));
                $item['is_all_refund'] = $refund_num == $cart_num ? true : false;
            } else {
                $item['is_all_refund'] = false;
            }
        }
        return $data;
    }

    /**
     * 获取订单数量
     * @param int $uid
     * @return mixed
     */
    public function getOrderData(int $uid = 0, $store_id = '')
    {
        $where = ['pid' => 0, 'uid' => $uid, 'is_del' => 0, 'is_system_del' => 0];
        if ($store_id !== '') $where['store_id'] = $store_id;
        $data['order_count'] = (string)$this->dao->count($where);
        $where = $where + ['paid' => 1];
        $data['sum_price'] = (string)$this->dao->sum($where, 'pay_price', true);
        $countWhere = $store_id !== '' ? ['pid' => 0, 'store_id' => $store_id] : ['pid' => 0];
        if ($uid) {
            $countWhere['uid'] = $uid;
        }
        $pid_where = ['pid' => 0];
        $not_pid_where = ['not_pid' => 1];
        $data['unpaid_count'] = (string)$this->dao->count(['status' => 0] + $countWhere + $pid_where);
        $data['unshipped_count'] = (string)$this->dao->count(['status' => 1] + $countWhere + $pid_where);
        $data['received_count'] = (string)$this->dao->count(['status' => 2] + $countWhere + $pid_where);
        $data['evaluated_count'] = (string)$this->dao->count(['status' => 3] + $countWhere + $pid_where);
        $data['unwritoff_count'] = (string)$this->dao->count(['status' => 5] + $countWhere);
        $data['complete_count'] = (string)$this->dao->count(['status' => 4] + $countWhere + $pid_where);
        /** @var StoreOrderRefundServices $storeOrderRefundServices */
        $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);
        $refund_where = ['is_cancel' => 0];
        if ($uid) $refund_where['uid'] = $uid;
        if ($store_id !== '') $refund_where['store_id'] = $store_id;
        $data['refunding_count'] = (string)$storeOrderRefundServices->count($refund_where + ['refund_type' => [1, 2, 4, 5]]);
        $data['refunded_count'] = (string)$storeOrderRefundServices->count($refund_where + ['refund_type' => [3, 6]]);
        $data['refund_count'] = (string)bcadd($data['refunding_count'], $data['refunded_count'], 0);
        $data['yue_pay_status'] = (int)sys_config('balance_func_status') && (int)sys_config('yue_pay_status') == 1 ? (int)1 : (int)2;//余额支付 1 开启 2 关闭
        $data['pay_weixin_open'] = (int)sys_config('pay_weixin_open') ?? 0;//微信支付 1 开启 0 关闭
        $data['ali_pay_status'] = sys_config('ali_pay_status') ? true : false;//支付包支付 1 开启 0 关闭
        return $data;
    }

    /**
     * 订单详情数据格式化
     * @param $order
     * @param bool $detail 是否需要订单商品详情
     * @param bool $isPic 是否需要订单状态图片
     * @return mixed
     */
    public function tidyOrder($order, bool $detail = false, bool $isPic = false)
    {
        if ($detail == true && isset($order['id'])) {
            /** @var StoreOrderCartInfoServices $cartServices */
            $cartServices = app()->make(StoreOrderCartInfoServices::class);
            $cartInfos = $cartServices->getCartColunm(['oid' => $order['id']], 'cart_num,is_writeoff,surplus_num,cart_info,refund_num,product_type,is_support_refund,is_gift,promotions_id', 'unique');
            $info = [];
            /** @var StoreProductReplyServices $replyServices */
            $replyServices = app()->make(StoreProductReplyServices::class);
            foreach ($cartInfos as $k => $cartInfo) {
                $cart = json_decode($cartInfo['cart_info'], true);
                $cart['cart_num'] = $cartInfo['cart_num'];
                $cart['is_writeoff'] = $cartInfo['is_writeoff'];
                $cart['surplus_num'] = $cartInfo['surplus_num'];
                $cart['refund_num'] = $cartInfo['refund_num'];
                $cart['product_type'] = $cartInfo['product_type'];
                $cart['is_support_refund'] = $cartInfo['is_support_refund'];
                $cart['is_gift'] = $cartInfo['is_gift'];
                $cart['promotions_id'] = $cartInfo['promotions_id'];
                $cart['unique'] = $k;
                //新增是否评价字段
                $cart['is_reply'] = $replyServices->count(['unique' => $k]);
                if (isset($cart['productInfo']['attrInfo'])) {
                    $cart['productInfo']['attrInfo'] = get_thumb_water($cart['productInfo']['attrInfo']);
                }
                $cart['productInfo'] = get_thumb_water($cart['productInfo']);
                //一种商品买多件  计算总优惠
                $cart['vip_sum_truePrice'] = bcmul($cart['vip_truePrice'], $cart['cart_num'] ? $cart['cart_num'] : 1, 2);
                $cart['is_valid'] = 1;
                array_push($info, $cart);
                unset($cart);
            }
            $order['cartInfo'] = $info;
        }
        /** @var StoreOrderStatusServices $statusServices */
        $statusServices = app()->make(StoreOrderStatusServices::class);
        $status = [];
        $storeInfo = [];
        if ($order['store_id']) {
            $storeServices = app()->make(SystemStoreServices::class);
            $storeInfo = $storeServices->get((int)$order['store_id']);
        }
        //系统预设取消订单时间段
        $keyValue = ['order_cancel_time', 'order_activity_time', 'order_bargain_time', 'order_seckill_time', 'order_pink_time'];
        //获取配置
        $systemValue = SystemConfigService::more($keyValue);
        //格式化数据
        $systemValue = Arr::setValeTime($keyValue, is_array($systemValue) ? $systemValue : []);
        switch ($order['type'] ?? 0) {
            case 1://秒杀
                $secs = $systemValue['order_seckill_time'] ? $systemValue['order_seckill_time'] : $systemValue['order_activity_time'];
                break;
            case 2://砍价
                $secs = $systemValue['order_bargain_time'] ? $systemValue['order_bargain_time'] : $systemValue['order_activity_time'];
                break;
            case 3://拼团
                $secs = $systemValue['order_pink_time'] ? $systemValue['order_pink_time'] : $systemValue['order_activity_time'];
                break;
            default:
                $secs = $systemValue['order_cancel_time'];
                break;
        }
        $order['stop_time'] = $secs * 3600 + $order['add_time'];

        if (!$order['paid'] && $order['pay_type'] == 'offline' && !$order['status'] >= 2) {
            $status['_type'] = 9;
            $status['_title'] = '线下付款,未支付';
            $status['_msg'] = '等待商家处理,请耐心等待';
            $status['_class'] = 'nobuy';
        } else if (!$order['paid']) {
            $status['_type'] = 0;
            $status['_title'] = '未支付';
            $status['_msg'] = '请在' . date('m-d H:i:s', $order['stop_time']) . '前完成支付!';
            $status['_class'] = 'nobuy';
        } else if ($order['refund_status'] == 2) {
            $status['_type'] = -2;
            $status['_title'] = '已退款';
            $status['_msg'] = '已为您退款,感谢您的支持';
            $status['_class'] = 'state-sqtk';
        } else if ($order['status'] == 4) {
            if ($order['delivery_type'] == 'send') {// 送货
                $status['_type'] = 2;
                $status['_title'] = '待收货';
                $status['_msg'] = date('m月d日H时i分', $statusServices->value(['oid' => $order['id'], 'change_type' => 'delivery'], 'change_time')) . '服务商已送货';
                $status['_class'] = 'state-ysh';
            } elseif ($order['delivery_type'] == 'express') {//  发货
                $status['_type'] = 2;
                $status['_title'] = '待收货';
                $status['_msg'] = date('m月d日H时i分', $statusServices->value(['oid' => $order['id'], 'change_type' => 'delivery_goods'], 'change_time')) . '服务商已发货';
                $status['_class'] = 'state-ysh';
            } elseif ($order['delivery_type'] == 'split') {//拆分发货
                $status['_type'] = 2;
                $status['_title'] = '待收货';
                $status['_msg'] = date('m月d日H时i分', $statusServices->value(['oid' => $order['id'], 'change_type' => 'delivery_part_split'], 'change_time')) . '服务商已拆分多个包裹发货';
                $status['_class'] = 'state-ysh';
            } else {
                $status['_type'] = 2;
                $status['_title'] = '待收货';
                $status['_msg'] = date('m月d日H时i分', $statusServices->value(['oid' => $order['id'], 'change_type' => 'delivery_fictitious'], 'change_time')) . '服务商已虚拟发货';
                $status['_class'] = 'state-ysh';
            }
        } elseif ($order['status'] == 5) {
            if ($order['shipping_type'] == 2) {
                $status['_type'] = 5;
                $status['_title'] = '部分核销';
                $status['_msg'] = '部分核销,请继续进行核销';
                $status['_class'] = 'state-nfh';
            } else {
                $status['_type'] = 2;
                $status['_title'] = '待收货';
                $status['_msg'] = '部分核销收货,请继续进行核销';
                $status['_class'] = 'state-ysh';
            }
        } else if ($order['refund_status'] == 1) {
            if (in_array($order['refund_type'], [0, 1, 2])) {
                $status['_type'] = -1;
                $status['_title'] = '申请退款中';
                $status['_msg'] = '商家审核中,请耐心等待';
                $status['_class'] = 'state-sqtk';
            } elseif ($order['refund_type'] == 4) {
                $status['_type'] = -1;
                $status['_title'] = '申请退款中';
                $status['_msg'] = '商家同意退款,请填写退货订单号';
                $status['_class'] = 'state-sqtk';
                if ($order['shipping_type'] == 1 || !$storeInfo) {//平台
                    $status['refund_name'] = sys_config('refund_name', '');
                    $status['refund_phone'] = sys_config('refund_phone', '');
                    $status['refund_address'] = sys_config('refund_address', '');
                } else {
                    $status['refund_name'] = $storeInfo['name'];
                    $status['refund_phone'] = $storeInfo['phone'];
                    $status['refund_address'] = $storeInfo['detailed_address'];
                }
            } elseif ($order['refund_type'] == 5) {
                $status['_type'] = -1;
                $status['_title'] = '申请退款中';
                $status['_msg'] = '等待商家收货';
                $status['_class'] = 'state-sqtk';
                if ($order['shipping_type'] == 1 || !$storeInfo) {//平台
                    $status['refund_name'] = sys_config('refund_name', '');
                    $status['refund_phone'] = sys_config('refund_phone', '');
                    $status['refund_address'] = sys_config('refund_address', '');
                } else {
                    $status['refund_name'] = $storeInfo['name'];
                    $status['refund_phone'] = $storeInfo['phone'];
                    $status['refund_address'] = $storeInfo['detailed_address'];
                }
            }
        } else if ($order['refund_status'] == 3) {
            $status['_type'] = -1;
            $status['_title'] = '部分退款（子订单）';
            $status['_msg'] = '拆分发货，部分退款';
            $status['_class'] = 'state-sqtk';
        } else if ($order['refund_status'] == 4) {
            $status['_type'] = -1;
            $status['_title'] = '子订单已全部申请退款中';
            $status['_msg'] = '拆分发货，全部退款';
            $status['_class'] = 'state-sqtk';
        } else if (!$order['status']) {
            if ($order['pink_id']) {
                /** @var StorePinkServices $pinkServices */
                $pinkServices = app()->make(StorePinkServices::class);
                if ($pinkServices->getCount(['id' => $order['pink_id'], 'status' => 1])) {
                    $status['_type'] = 1;
                    $status['_title'] = '拼团中';
                    $status['_msg'] = '等待其他人参加拼团';
                    $status['_class'] = 'state-nfh';
                } else {
                    $status['_type'] = 1;
                    $status['_title'] = '未发货';
                    $status['_msg'] = '商家未发货,请耐心等待';
                    $status['_class'] = 'state-nfh';
                }
            } else {
                if (in_array($order['shipping_type'], [1, 3])) {
                    $status['_type'] = 1;
                    $status['_title'] = '未发货';
                    $status['_msg'] = '商家未发货,请耐心等待';
                    $status['_class'] = 'state-nfh';
                } else {
                    $status['_type'] = 5;
                    $status['_title'] = '待核销';
                    $status['_msg'] = '待核销,请到核销点进行核销';
                    $status['_class'] = 'state-nfh';
                }
            }
        } else if ($order['status'] == 1) {
            if ($order['delivery_type'] == 'send') {// 配送
                $status['_type'] = 2;
                $status['_title'] = '待收货';
                $status['_msg'] = date('m月d日H时i分', $statusServices->value(['oid' => $order['id'], 'change_type' => 'delivery'], 'change_time')) . '服务商已发货';
                $status['_class'] = 'state-ysh';
            } elseif ($order['delivery_type'] == 'express') {//  发货
                $status['_type'] = 2;
                $status['_title'] = '待收货';
                $status['_msg'] = date('m月d日H时i分', $statusServices->value(['oid' => $order['id'], 'change_type' => 'delivery_goods'], 'change_time')) . '服务商已发货';
                $status['_class'] = 'state-ysh';
            } elseif ($order['delivery_type'] == 'split') {//拆分发货
                $status['_type'] = 2;
                $status['_title'] = '待收货';
                $status['_msg'] = date('m月d日H时i分', $statusServices->value(['oid' => $order['id'], 'change_type' => 'delivery_split'], 'change_time')) . '服务商已拆分多个包裹发货';
                $status['_class'] = 'state-ysh';
            } else {
                $status['_type'] = 2;
                $status['_title'] = '待收货';
                $status['_msg'] = date('m月d日H时i分', $statusServices->value(['oid' => $order['id'], 'change_type' => 'delivery_fictitious'], 'change_time')) . '服务商已虚拟发货';
                $status['_class'] = 'state-ysh';
            }
        } else if ($order['status'] == 2) {
            $status['_type'] = 3;
            $status['_title'] = '待评价';
            $status['_msg'] = '已收货,快去评价一下吧';
            $status['_class'] = 'state-ypj';
        } else if ($order['status'] == 3) {
            $status['_type'] = 4;
            $status['_title'] = '交易完成';
            $status['_msg'] = '交易完成,感谢您的支持';
            $status['_class'] = 'state-ytk';
        }
        if (isset($order['pay_type']))
            $status['_payType'] = ($status['_type'] ?? 0) == 0 ? '' : PayServices::PAY_TYPE[$order['pay_type']] ?? '其他方式';
        if (isset($order['delivery_type']))
            $status['_deliveryType'] = isset($this->deliveryType[$order['delivery_type']]) ? $this->deliveryType[$order['delivery_type']] : '其他方式';
        $order['_status'] = $status;
        $order['_pay_time'] = isset($order['pay_time']) && $order['pay_time'] != null ? date('Y-m-d H:i:s', $order['pay_time']) : '';
        $order['_add_time'] = isset($order['add_time']) ? (strstr((string)$order['add_time'], '-') === false ? date('Y-m-d H:i:s', $order['add_time']) : $order['add_time']) : '';

        $order['status_pic'] = '';
        //获取商品状态图片
        if ($isPic) {
            try {
                $order_details_images = sys_data('order_details_images') ?: [];
                $order_details_images = array_combine(array_column($order_details_images, 'order_status'), $order_details_images);
                $order['status_pic'] = $order_details_images[$order['_status']['_type']]['pic'] ?? '';
            } catch (\Throwable $e) {
            }
        }
        $order['offlinePayStatus'] = (int)sys_config('offline_pay_status') ?? 2;
        //自购返佣
        if ($order['uid'] == $order['spread_uid']) {
            $order['spread_nickname'] = isset($order['spread_nickname']) ? $order['spread_nickname'] . '(自购)' : '';
        }
        $order['longitude'] = $order['latitude'] = '';
        //处理地址定位
        if (isset($order['user_location']) && $order['user_location']) {
            [$longitude, $latitude] = explode(' ', $order['user_location']);
            $order['longitude'] = $longitude;
            $order['latitude'] = $latitude;
        }
        return $order;
    }

    /**
     * 整理订单类型
     * @param $order
     * @param bool $abridge
     * @return string[]
     */
    public function tidyOrderType($order, bool $abridge = false)
    {
        $pink_name = $color = '';
        if ($order && isset($order['type'])) {
            switch ($order['type']) {
                case 0://普通订单
                    if ($order['shipping_type'] == 1) {
                        $pink_name = $abridge ? '普通' : '[普通订单]';
                        $color = '#895612';
                    } else if ($order['shipping_type'] == 2) {
                        $pink_name = $abridge ? '核销' : '[核销订单]';
                        $color = '#8956E8';
                    } else if ($order['shipping_type'] == 3) {
                        $pink_name = $abridge ? '分配' : '[分配订单]';
                        $color = '#FFA21B';
                    } else if ($order['shipping_type'] == 4) {
                        $pink_name = $abridge ? '收银' : '[收银订单]';
                        $color = '#2EC479';
                    }
                    break;
                case 1://秒杀
                    $pink_name = $abridge ? '秒杀' : '[秒杀订单]';
                    $color = '#32c5e9';
                    break;
                case 2://砍价
                    $pink_name = $abridge ? '砍价' : '[砍价订单]';
                    $color = '#12c5e9';
                    break;
                case 3://拼团
                    if (isset($order['pinkStatus'])) {
                        switch ($order['pinkStatus']) {
                            case 1:
                                $pink_name = $abridge ? '拼团' : '[拼团订单]正在进行中';
                                $color = '#f00';
                                break;
                            case 2:
                                $pink_name = $abridge ? '拼团' : '[拼团订单]已完成';
                                $color = '#00f';
                                break;
                            case 3:
                                $pink_name = $abridge ? '拼团' : '[拼团订单]未完成';
                                $color = '#f0f';
                                break;
                            default:
                                $pink_name = $abridge ? '拼团' : '[拼团订单]历史订单';
                                $color = '#457856';
                                break;
                        }
                    } else {
                        $pink_name = $abridge ? '拼团' : '[拼团订单]历史订单';
                        $color = '#457856';
                    }
                    break;
                case 5://套餐
                    $pink_name = $abridge ? '优惠' : '[优惠套餐]';
                    $color = '#12c5e9';
                    break;
                case 6://预售
                    $pink_name = $abridge ? '预售' : '[预售订单]';
                    $color = '#12c5e9';
                    break;
            }
        }
        return [$pink_name, $color];
    }

    /**
     * 数据转换
     * @param array $data
     * @param bool $is_cart_info
     * @return array|null
     */
    public function tidyOrderList(array $data, bool $is_cart_info = true, bool $abridge = false)
    {
        if (!$data) {
            return $data;
        }
        /** @var StoreOrderCartInfoServices $services */
        $services = app()->make(StoreOrderCartInfoServices::class);
        foreach ($data as &$item) {
            if ($is_cart_info) $item['_info'] = $services->getOrderCartInfo((int)$item['id']);
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
            $item['_refund_time'] = isset($item['refund_reason_time']) && $item['refund_reason_time'] ? date('Y-m-d H:i:s', $item['refund_reason_time']) : '';
            $item['_pay_time'] = isset($item['pay_time']) && $item['pay_time'] ? date('Y-m-d H:i:s', $item['pay_time']) : '';
            [$pink_name, $color] = $this->tidyOrderType($item, $abridge);
            $item['pink_name'] = $pink_name;
            $item['color'] = $color;
            if ($item['paid'] == 1) {
                switch ($item['pay_type']) {
                    case PayServices::WEIXIN_PAY:
                        $item['pay_type_name'] = '微信支付';
                        break;
                    case PayServices::YUE_PAY:
                        $item['pay_type_name'] = '余额支付';
                        break;
                    case PayServices::OFFLINE_PAY:
                        $item['pay_type_name'] = '线下支付';
                        break;
                    case PayServices::ALIAPY_PAY:
                        $item['pay_type_name'] = '支付宝支付';
                        break;
                    case PayServices::CASH_PAY:
                        $item['pay_type_name'] = '现金支付';
                        break;
                    default:
                        $item['pay_type_name'] = '其他支付';
                        break;
                }
            } else {
                switch ($item['pay_type']) {
                    default:
                        $item['pay_type_name'] = '未支付';
                        break;
                    case 'offline':
                        $item['pay_type_name'] = '线下支付';
                        $item['pay_type_info'] = 1;
                        break;
                }
            }
            $status_name = ['status_name' => '', 'pics' => []];
            if ($item['paid'] == 0 && $item['status'] == 0) {
                $status_name['status_name'] = '未支付';
                $item['_status'] = 1;//未支付
            } else if ($item['paid'] == 1 && $item['status'] == 4 && in_array($item['shipping_type'], [1, 3]) && $item['refund_status'] == 0) {
                $status_name['status_name'] = '部分发货';
                $item['_status'] = 8;//已支付 部分发货
            } else if ($item['paid'] == 1 && $item['refund_status'] == 2) {
                $status_name['status_name'] = '已退款';
                $item['_status'] = 7;//已支付 已退款
            } else if ($item['paid'] == 1 && $item['status'] == 5 && $item['shipping_type'] == 2 && $item['refund_status'] == 0) {
                $status_name['status_name'] = '部分核销';
                $item['_status'] = 12;//已支付 部分核销
            } else if ($item['paid'] == 1 && $item['refund_status'] == 1) {
                $item['_status'] = 3;//已支付 申请退款中
                $refundReasonTime = $item['refund_reason_time'] ? date('Y-m-d H:i', $item['refund_reason_time']) : '';
                $refundReasonWapImg = json_decode($item['refund_reason_wap_img'], true);
                $refundReasonWapImg = $refundReasonWapImg ? $refundReasonWapImg : [];
                $img = [];
                if (count($refundReasonWapImg)) {
                    foreach ($refundReasonWapImg as $itemImg) {
                        if (strlen(trim($itemImg)))
                            $img[] = $itemImg;
                    }
                }
                $status_name['status_name'] = <<<HTML
<b style="color:#f124c7">申请退款</b><br/>
<span>退款原因：{$item['refund_reason_wap']}</span><br/>
<span>备注说明：{$item['refund_reason_wap_explain']}</span><br/>
<span>退款时间：{$refundReasonTime}</span><br/>
<span>退款凭证：</span>
HTML;
                $status_name['pics'] = $img;
            } else if ($item['paid'] == 1 && $item['refund_status'] == 4) {
                $item['_status'] = 10;//拆单发货 已全部申请退款
                $status_name['status_name'] = <<<HTML
<b style="color:#f124c7">退款中</b><br/>
HTML;
            } else if ($item['paid'] == 1 && $item['status'] == 0 && in_array($item['shipping_type'], [1, 3]) && $item['refund_status'] == 0) {
                $status_name['status_name'] = '未发货';
                $item['_status'] = 2;//已支付 未发货
            } else if ($item['paid'] == 1 && in_array($item['status'], [0, 1]) && $item['shipping_type'] == 2 && $item['refund_status'] == 0) {
                $status_name['status_name'] = '未核销';
                $item['_status'] = 11;//已支付 待核销
            } else if ($item['paid'] == 1 && in_array($item['status'], [1, 5]) && in_array($item['shipping_type'], [1, 3]) && $item['refund_status'] == 0) {
                $status_name['status_name'] = '待收货';
                $item['_status'] = 4;//已支付 待收货
            } else if ($item['paid'] == 1 && $item['status'] == 2 && $item['refund_status'] == 0) {
                $status_name['status_name'] = '待评价';
                $item['_status'] = 5;//已支付 待评价
            } else if ($item['paid'] == 1 && $item['status'] == 3 && $item['refund_status'] == 0) {
                $status_name['status_name'] = '已完成';
                $item['_status'] = 6;//已支付 已完成
            } else if ($item['paid'] == 1 && $item['refund_status'] == 3) {
                $item['_status'] = 9;//拆单发货 部分申请退款
                $status_name['status_name'] = <<<HTML
<b style="color:#f124c7">部分退款</b><br/>
HTML;
            }
            $item['status_name'] = $status_name;
            if ($item['store_id'] == 0 && $item['clerk_id'] == 0 && !isset($item['clerk_name'])) {
                $item['clerk_name'] = '总平台';
            }
            //根据核销员更改store_name
            if ($item['clerk_id'] && isset($item['staff_store_id']) && $item['staff_store_id']) {
                /** @var SystemStoreServices $store */
                $store = app()->make(SystemStoreServices::class);
                $storeOne = $store->value(['id' => $item['staff_store_id']], 'name');
                if ($storeOne) $item['store_name'] = $storeOne;
            }
            //自购返佣
            if ($item['uid'] == $item['spread_uid']) {
                $item['spread_nickname'] = isset($item['spread_nickname']) ? $item['spread_nickname'] . '(自购)' : '';
            }
        }
        return $data;
    }

    /**
     * 处理订单金额
     * @param $where
     * @return array
     */
    public function getOrderPrice($where)
    {
//        $where['pid'] = 0;//子订单不统计
        $whereData = [];
        $price['today_count_sum'] = 0; //今日订单总数
        $price['count_sum'] = 0; //订单总数
        $price['pay_price'] = 0;//支付金额
        $price['today_pay_price'] = 0;//今日支付金额
        if ($where['status'] == '') {
            $whereData['paid'] = 1;
            $whereData['refund_status'] = [0, 3];
        }
        $not_pid = $where;
        unset($not_pid['pid']);
        $not_pid['not_pid'] = 1;
        $sumNumber = $this->dao->search($where + $whereData)->field([
            'count(id) as count_sum',
        ])->find();
        $price['count_sum'] = $sumNumber && $sumNumber['count_sum'] ? $sumNumber['count_sum'] : 0;
        $sumNumber = $this->dao->search($whereData + $where)->field([
            'sum(pay_price) as sum_pay_price',
        ])->find();
        $price['pay_price'] = $sumNumber && $sumNumber['sum_pay_price'] ? $sumNumber['sum_pay_price'] : 0;
        $where['time'] = 'today';
        $not_pid['time'] = 'today';
        $sumNumber = $this->dao->search($where + $whereData)->field([
            'count(id) as today_count_sum',
        ])->find();
        $price['today_count_sum'] = $sumNumber && $sumNumber['today_count_sum'] ? $sumNumber['today_count_sum'] : 0;
        $sumNumber = $this->dao->search($whereData + $where + ['paid' => 1])->field([
            'sum(pay_price) as today_pay_price',
        ])->find();
        $price['today_pay_price'] = $sumNumber && $sumNumber['today_pay_price'] ? $sumNumber['today_pay_price'] : 0;
        return $price;
    }

    /**
     * 获取订单列表页面统计数据
     * @param $where
     * @return array
     */
    public function getBadge($where)
    {
        $price = $this->getOrderPrice($where);
        return [
            [
                'name' => '订单数量',
                'field' => '件',
                'count' => $price['count_sum'],
                'className' => 'md-basket',
                'col' => 6
            ],
            [
                'name' => '订单金额',
                'field' => '元',
                'count' => $price['pay_price'],
                'className' => 'md-pricetags',
                'col' => 6
            ],
            [
                'name' => '今日订单数量',
                'field' => '件',
                'count' => $price['today_count_sum'],
                'className' => 'ios-chatbubbles',
                'col' => 6
            ],
            [
                'name' => '今日支付金额',
                'field' => '元',
                'count' => $price['today_pay_price'],
                'className' => 'ios-cash',
                'col' => 6
            ],
        ];
    }

    /**
     *
     * @param array $where
     * @return mixed
     */
    public function orderStoreCount(array $where)
    {
        $defaultWhere = ['time' => $where['time'], 'store_id' => $where['store_id'] ?? 0];
        if (isset($where['store_id'])) {
            $defaultWhere['store_id'] = $where['store_id'];
        }
        //全部订单
        $data['all'] = (string)$this->dao->count(['pid' => 0, 'is_system_del' => 0] + $defaultWhere);
        //普通订单
        $data['general'] = (string)$this->dao->count(['pid' => 0, 'type' => 0, 'is_system_del' => 0] + $defaultWhere);
        //拼团订单
        $data['pink'] = (string)$this->dao->count(['pid' => 0, 'type' => 3, 'is_system_del' => 0] + $defaultWhere);
        //秒杀订单
        $data['seckill'] = (string)$this->dao->count(['pid' => 0, 'type' => 1, 'is_system_del' => 0] + $defaultWhere);
        //砍价订单
        $data['bargain'] = (string)$this->dao->count(['pid' => 0, 'type' => 2, 'is_system_del' => 0] + $defaultWhere);
        //收银订单
        $data['cashier'] = (string)$this->dao->count(['pid' => 0, 'type' => 6, 'is_system_del' => 0] + $defaultWhere);
        $data['write_off'] = (string)$this->dao->count(['pid' => 0, 'type' => 5, 'is_system_del' => 0] + $defaultWhere);
        $data['delivery'] = (string)$this->dao->count(['pid' => 0, 'type' => 7, 'is_system_del' => 0] + $defaultWhere);
        //预售订单
        $data['presale'] = (string)$this->dao->count(['pid' => 0, 'type' => 8, 'is_system_del' => 0] + $defaultWhere);

        switch ($where['type']) {
            case 0:
                $data['statusAll'] = $data['general'];
                break;
            case 1:
                $data['statusAll'] = $data['seckill'];
                break;
            case 2:
                $data['statusAll'] = $data['bargain'];
                break;
            case 3:
                $data['statusAll'] = $data['pink'];
                break;
            case 4:
                break;
            case 5:
                $data['statusAll'] = $data['write_off'];
                break;
            case 6:
                $data['statusAll'] = $data['cashier'];
                break;
            case 7:
                $data['statusAll'] = $data['delivery'];
                break;
            case 8:
                $data['statusAll'] = $data['presale'];
                break;
            default:
                $data['statusAll'] = $data['all'];
        }
        $count_where = ['pid' => 0, 'is_system_del' => 0, 'type' => $where['type']] + $defaultWhere;
        //未支付
        $data['unpaid'] = (string)$this->dao->count($count_where + ['status' => 0]);
        //未发货
        $data['unshipped'] = (string)$this->dao->count($count_where + ['status' => 1]);
        //部分发货
        $data['partshipped'] = (string)$this->dao->count($count_where + ['status' => 7]);
        //待收货
        $data['untake'] = (string)$this->dao->count($count_where + ['status' => 2]);
        //待核销
        $data['write_off'] = (string)$this->dao->count($count_where + ['status' => 5]);
        //已核销
        $data['write_offed'] = (string)$this->dao->count($count_where + ['status' => 6]);
        //待评价
        $data['unevaluate'] = (string)$this->dao->count($count_where + ['status' => 3]);
        //交易完成
        $data['complete'] = (string)$this->dao->count($count_where + ['status' => 4]);
        //退款中
//        $data['refunding'] = (string)$this->dao->count(['status' => -1, 'time' => $where['time'], 'is_system_del' => 0, 'type' => $where['type']]);
        //已退款
//        $data['refund'] = (string)$this->dao->count(['status' => -2, 'time' => $where['time'], 'is_system_del' => 0, 'type' => $where['type']]);
        //删除订单
        $data['del'] = (string)$this->dao->count($count_where + ['status' => -4]);
        return $data;
    }

    /**
     *
     * @param array $where
     * @return mixed
     */
    public function orderCount(array $where)
    {
        $default_where = ['pid' => [0, -1], 'time' => $where['time'], 'store_id' => $where['store_id'] ?? 0, 'status' => $where['status'], 'pay_type' => $where['pay_type'], 'field_key' => $where['field_key'], 'real_name' => $where['real_name']];
        //全部订单
        $data['all'] = (string)$this->dao->count(['is_system_del' => 0] + $default_where);
        //普通订单
        $data['general'] = (string)$this->dao->count(['type' => 0, 'is_system_del' => 0,] + $default_where);
        //拼团订单
        $data['pink'] = (string)$this->dao->count(['type' => 3, 'is_system_del' => 0] + $default_where);
        //秒杀订单
        $data['seckill'] = (string)$this->dao->count(['type' => 1, 'is_system_del' => 0] + $default_where);
        //砍价订单
        $data['bargain'] = (string)$this->dao->count(['type' => 2, 'is_system_del' => 0] + $default_where);
        //预售订单
        $data['presale'] = (string)$this->dao->count(['type' => 8, 'is_system_del' => 0] + $default_where);

        $data['statusAll'] = $data['all'];
        if (trim($where['type'], ' ') !== '') {
            switch ($where['type']) {
                case 0:
                    $data['statusAll'] = $data['general'];
                    break;
                case 1:
                    $data['statusAll'] = $data['seckill'];
                    break;
                case 2:
                    $data['statusAll'] = $data['bargain'];
                    break;
                case 3:
                    $data['statusAll'] = $data['pink'];
                    break;
                case 4:
                    break;
                case 8:
                    $data['statusAll'] = $data['presale'];
                    break;
                default:
                    $data['statusAll'] = $data['all'];
            }
        }
        $count_where = ['is_system_del' => 0, 'type' => $where['type']] + $default_where;
        //未支付
        $data['unpaid'] = (string)$this->dao->count($count_where + ['status' => 0]);
        //未发货
        $data['unshipped'] = (string)$this->dao->count($count_where + ['status' => 1, 'shipping_type' => 1]);
        //部分发货
        $data['partshipped'] = (string)$this->dao->count($count_where + ['status' => 7, 'shipping_type' => 1]);
        //待收货
        $data['untake'] = (string)$this->dao->count($count_where + ['status' => 2, 'shipping_type' => 1]);
        //待核销
        $data['write_off'] = (string)$this->dao->count($count_where + ['status' => 5]);
        //已核销
        $data['write_offed'] = (string)$this->dao->count($count_where + ['status' => 6]);
        //待评价
        $data['unevaluate'] = (string)$this->dao->count($count_where + ['status' => 3]);
        //交易完成
        $data['complete'] = (string)$this->dao->count($count_where + ['status' => 4]);
        //退款中
//        $data['refunding'] = (string)$this->dao->count(['status' => -1, 'time' => $where['time'], 'is_system_del' => 0, 'type' => $where['type']]);
        //已退款
//        $data['refund'] = (string)$this->dao->count(['status' => -2, 'time' => $where['time'], 'is_system_del' => 0, 'type' => $where['type']]);
        //删除订单
        $data['del'] = (string)$this->dao->count($count_where + ['status' => -4]);
        return $data;
    }

    /**
     * 创建修改订单表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function updateForm(int $id)
    {
        $product = $this->dao->get($id);
        if (!$product) {
            throw new ValidateException('Data does not exist!');
        }
        $f = [];
        $f[] = Form::input('order_id', '订单编号', $product->getData('order_id'))->disabled(true);
        $f[] = Form::number('total_price', '商品总价', (float)$product->getData('total_price'))->min(0)->disabled(true);
        $f[] = Form::number('total_postage', '原始邮费', (float)$product->getData('total_postage'))->min(0)->disabled(true);
        $f[] = Form::number('pay_postage', '实际支付邮费', (float)$product->getData('pay_postage') ?: 0)->disabled(true);
        $f[] = Form::number('pay_price', '实际支付金额', (float)$product->getData('pay_price'))->min(0)->precision(2);
        $f[] = Form::number('gain_integral', '赠送积分', (float)$product->getData('gain_integral') ?: 0)->precision(0);
        return create_form('修改订单', $f, $this->url('/order/update/' . $id), 'PUT');
    }

    /**
     * 修改订单
     * @param int $id
     * @param array $data
     * @return mixed
     * @throws \Exception
     */
    public function updateOrder(int $id, array $data)
    {
        $order = $this->dao->getOne(['id' => $id, 'is_del' => 0]);
        if (!$order) {
            throw new ValidateException('订单不存在或已删除');
        }
        if ($order['paid']) {
            throw new ValidateException('订单已支付');
        }
        //订单改价后不在更改订单号
//        /** @var StoreOrderCreateServices $createServices */
//        $createServices = app()->make(StoreOrderCreateServices::class);
//        if ($order->pay_price != $data['pay_price']) {
//            $data['order_id'] = $createServices->getNewOrderId();
//        }
        /** @var StoreOrderStatusServices $services */
        $services = app()->make(StoreOrderStatusServices::class);
        $this->transaction(function () use ($id, $order, $data, $services) {
            $res = $this->dao->update($id, $data);
            $res = $res && $services->save([
                    'oid' => $id,
                    'change_type' => 'order_edit',
                    'change_time' => time(),
                    'change_message' => '商品总价为：' . $order['pay_price'] . ' 修改实际支付金额为：' . $data['pay_price']
                ]);
            if ($res) {
                return true;
            } else {
                throw new ValidateException('Modification failed');
            }
        });
        //新订单号
        // $order['order_id'] = $data['order_id'];
        //改价提醒
        event('order.price', [$order, $data['pay_price']]);
//        event('notice.notice', [['order' => $order, 'pay_price' => $data['pay_price']], 'price_revision']);
//        if ($data['pay_price'] != $order['pay_price']) {
//
//            $switch = sys_config('price_revision_switch') ? true : false;
//            if ($switch) {
//                /** @var SmsSendServices $smsServices */
//                $smsServices = app()->make(SmsSendServices::class);
//                $smsServices->send($switch, $order['user_phone'], ['order_id' => $order['order_id'], 'pay_price' => $data['pay_price']], 'PRICE_REVISION_CODE');
//            }
//        }
        return true;
    }

    /**
     * 订单图表
     * @param $cycle
     * @return array
     */
    public function orderCharts($cycle)
    {
        $datalist = [];
        $where = ['store_id' => 0];
        $series1 = ['normal' => ['color' => [
            'x' => 0, 'y' => 0, 'x2' => 0, 'y2' => 1,
            'colorStops' => [
                [
                    'offset' => 0,
                    'color' => '#69cdff'
                ],
                [
                    'offset' => 0.5,
                    'color' => '#3eb3f7'
                ],
                [
                    'offset' => 1,
                    'color' => '#1495eb'
                ]
            ]
        ]]
        ];
        $series2 = ['normal' => ['color' => [
            'x' => 0, 'y' => 0, 'x2' => 0, 'y2' => 1,
            'colorStops' => [
                [
                    'offset' => 0,
                    'color' => '#6fdeab'
                ],
                [
                    'offset' => 0.5,
                    'color' => '#44d693'
                ],
                [
                    'offset' => 1,
                    'color' => '#2cc981'
                ]
            ]
        ]]
        ];
        $chartdata = [];
        $data = [];//临时
        $chartdata['yAxis']['maxnum'] = 0;//最大值数量
        $chartdata['yAxis']['maxprice'] = 0;//最大值金额
        switch ($cycle) {
            case 'thirtyday':
                //上期
                $datebefor = date('Y-m-d 00:00:00', strtotime('-59 day'));
                $dateafter = date('Y-m-d 23:59:59', strtotime('-29 day'));
                //当前
                $now_datebefor = date('Y-m-d 00:00:00', strtotime('-29 day'));
                $now_dateafter = date('Y-m-d 23:59:59');
                for ($i = -29; $i <= 0; $i++) {
                    $datalist[date('m-d', strtotime($i . ' day'))] = date('m-d', strtotime($i . ' day'));
                }
                $order_list = $this->dao->orderAddTimeList($where, [$now_datebefor, $now_dateafter], 'day');
                if (empty($order_list)) return ['yAxis' => [], 'legend' => [], 'xAxis' => [], 'serise' => [], 'pre_cycle' => [], 'cycle' => []];
                $order_list = array_combine(array_column($order_list, 'day'), $order_list);
                $cycle_list = [];
                foreach ($datalist as $dk => $dd) {
                    if (isset($order_list[$dk]) && !empty($order_list[$dd])) {
                        $cycle_list[$dd] = $order_list[$dd];
                    } else {
                        $cycle_list[$dd] = ['count' => 0, 'day' => $dd, 'price' => ''];
                    }
                }
                foreach ($cycle_list as $k => $v) {
                    $data['day'][] = $v['day'];
                    $data['count'][] = $v['count'];
                    $data['price'][] = round($v['price'], 2);
                    if ($chartdata['yAxis']['maxnum'] < $v['count'])
                        $chartdata['yAxis']['maxnum'] = $v['count'];//日最大订单数
                    if ($chartdata['yAxis']['maxprice'] < $v['price'])
                        $chartdata['yAxis']['maxprice'] = $v['price'];//日最大金额
                }
                $chartdata['legend'] = ['订单金额', '订单数'];//分类
                $chartdata['xAxis'] = $data['day'];//X轴值
                $chartdata['series'][] = ['name' => $chartdata['legend'][0], 'type' => 'bar', 'itemStyle' => $series1, 'data' => $data['price']];//分类1值
                $chartdata['series'][] = ['name' => $chartdata['legend'][1], 'type' => 'line', 'itemStyle' => $series2, 'data' => $data['count'], 'yAxisIndex' => 1];//分类2值
                break;
            case 'week':
                $weekarray = [['周日'], ['周一'], ['周二'], ['周三'], ['周四'], ['周五'], ['周六']];
                $datebefor = date('Y-m-d 00:00:00', strtotime('-1 week Monday'));
                $dateafter = date('Y-m-d 23:59:59', strtotime('-1 week Sunday'));
                $order_list = $this->dao->orderAddTimeList($where, [$datebefor, $dateafter], 'week');
                //数据查询重新处理
                $new_order_list = array_combine(array_column($order_list, 'day'), $order_list);
                $now_datebefor = date('Y-m-d 00:00:00', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
                $now_dateafter = date('Y-m-d 23:59:59', strtotime("+1 day"));
                $now_order_list = $this->dao->orderAddTimeList($where, [$now_datebefor, $now_dateafter], 'week');
                //数据查询重新处理 key 变为当前值
                $new_now_order_list = array_combine(array_column($now_order_list, 'day'), $now_order_list);
                foreach ($weekarray as $dk => $dd) {
                    if (isset($new_order_list[$dk]) && !empty($new_order_list[$dk])) {
                        $weekarray[$dk]['pre'] = $new_order_list[$dk];
                    } else {
                        $weekarray[$dk]['pre'] = ['count' => 0, 'day' => $weekarray[$dk][0], 'price' => '0'];
                    }
                    if (isset($new_now_order_list[$dk]) && !empty($new_now_order_list[$dk])) {
                        $weekarray[$dk]['now'] = $new_now_order_list[$dk];
                    } else {
                        $weekarray[$dk]['now'] = ['count' => 0, 'day' => $weekarray[$dk][0], 'price' => '0'];
                    }
                }
                foreach ($weekarray as $k => $v) {
                    $data['day'][] = $v[0];
                    $data['pre']['count'][] = $v['pre']['count'];
                    $data['pre']['price'][] = round($v['pre']['price'], 2);
                    $data['now']['count'][] = $v['now']['count'];
                    $data['now']['price'][] = round($v['now']['price'], 2);
                    if ($chartdata['yAxis']['maxnum'] < $v['pre']['count'] || $chartdata['yAxis']['maxnum'] < $v['now']['count']) {
                        $chartdata['yAxis']['maxnum'] = $v['pre']['count'] > $v['now']['count'] ? $v['pre']['count'] : $v['now']['count'];//日最大订单数
                    }
                    if ($chartdata['yAxis']['maxprice'] < $v['pre']['price'] || $chartdata['yAxis']['maxprice'] < $v['now']['price']) {
                        $chartdata['yAxis']['maxprice'] = $v['pre']['price'] > $v['now']['price'] ? $v['pre']['price'] : $v['now']['price'];//日最大金额
                    }
                }
                $chartdata['legend'] = ['上周金额', '本周金额', '上周订单数', '本周订单数'];//分类
                $chartdata['xAxis'] = $data['day'];//X轴值
                $chartdata['series'][] = ['name' => $chartdata['legend'][0], 'type' => 'bar', 'itemStyle' => $series1, 'data' => $data['pre']['price']];//分类1值
                $chartdata['series'][] = ['name' => $chartdata['legend'][1], 'type' => 'bar', 'itemStyle' => $series1, 'data' => $data['now']['price']];//分类1值
                $chartdata['series'][] = ['name' => $chartdata['legend'][2], 'type' => 'line', 'itemStyle' => $series2, 'data' => $data['pre']['count'], 'yAxisIndex' => 1];//分类2值
                $chartdata['series'][] = ['name' => $chartdata['legend'][3], 'type' => 'line', 'itemStyle' => $series2, 'data' => $data['now']['count'], 'yAxisIndex' => 1];//分类2值
                break;
            case 'month':
                $weekarray = ['01' => ['1'], '02' => ['2'], '03' => ['3'], '04' => ['4'], '05' => ['5'], '06' => ['6'], '07' => ['7'], '08' => ['8'], '09' => ['9'], '10' => ['10'], '11' => ['11'], '12' => ['12'], '13' => ['13'], '14' => ['14'], '15' => ['15'], '16' => ['16'], '17' => ['17'], '18' => ['18'], '19' => ['19'], '20' => ['20'], '21' => ['21'], '22' => ['22'], '23' => ['23'], '24' => ['24'], '25' => ['25'], '26' => ['26'], '27' => ['27'], '28' => ['28'], '29' => ['29'], '30' => ['30'], '31' => ['31']];
                $datebefor = date('Y-m-01 00:00:00', strtotime('-1 month'));
                $dateafter = date('Y-m-d 23:59:59', strtotime(date('Y-m-01')));
                $order_list = $this->dao->orderAddTimeList($where, [$datebefor, $dateafter], "month");
                //数据查询重新处理
                $new_order_list = array_combine(array_column($order_list, 'day'), $order_list);
                $now_datebefor = date('Y-m-01 00:00:00');
                $now_dateafter = date('Y-m-d', strtotime("+1 day"));
                $now_order_list = $this->dao->orderAddTimeList($where, [$now_datebefor, $now_dateafter], "month");
                //数据查询重新处理 key 变为当前值
                $new_now_order_list = array_combine(array_column($now_order_list, 'day'), $now_order_list);
                foreach ($weekarray as $dk => $dd) {
                    if (isset($new_order_list[$dk]) && !empty($new_order_list[$dk])) {
                        $weekarray[$dk]['pre'] = $new_order_list[$dk];
                    } else {
                        $weekarray[$dk]['pre'] = ['count' => 0, 'day' => $weekarray[$dk][0], 'price' => '0'];
                    }
                    if (isset($new_now_order_list[$dk]) && !empty($new_now_order_list[$dk])) {
                        $weekarray[$dk]['now'] = $new_now_order_list[$dk];
                    } else {
                        $weekarray[$dk]['now'] = ['count' => 0, 'day' => $weekarray[$dk][0], 'price' => '0'];
                    }
                }
                foreach ($weekarray as $k => $v) {
                    $data['day'][] = $v[0];
                    $data['pre']['count'][] = $v['pre']['count'];
                    $data['pre']['price'][] = round($v['pre']['price'], 2);
                    $data['now']['count'][] = $v['now']['count'];
                    $data['now']['price'][] = round($v['now']['price'], 2);
                    if ($chartdata['yAxis']['maxnum'] < $v['pre']['count'] || $chartdata['yAxis']['maxnum'] < $v['now']['count']) {
                        $chartdata['yAxis']['maxnum'] = $v['pre']['count'] > $v['now']['count'] ? $v['pre']['count'] : $v['now']['count'];//日最大订单数
                    }
                    if ($chartdata['yAxis']['maxprice'] < $v['pre']['price'] || $chartdata['yAxis']['maxprice'] < $v['now']['price']) {
                        $chartdata['yAxis']['maxprice'] = $v['pre']['price'] > $v['now']['price'] ? $v['pre']['price'] : $v['now']['price'];//日最大金额
                    }
                }
                $chartdata['legend'] = ['上月金额', '本月金额', '上月订单数', '本月订单数'];//分类
                $chartdata['xAxis'] = $data['day'];//X轴值
                $chartdata['series'][] = ['name' => $chartdata['legend'][0], 'type' => 'bar', 'itemStyle' => $series1, 'data' => $data['pre']['price']];//分类1值
                $chartdata['series'][] = ['name' => $chartdata['legend'][1], 'type' => 'bar', 'itemStyle' => $series1, 'data' => $data['now']['price']];//分类1值
                $chartdata['series'][] = ['name' => $chartdata['legend'][2], 'type' => 'line', 'itemStyle' => $series2, 'data' => $data['pre']['count'], 'yAxisIndex' => 1];//分类2值
                $chartdata['series'][] = ['name' => $chartdata['legend'][3], 'type' => 'line', 'itemStyle' => $series2, 'data' => $data['now']['count'], 'yAxisIndex' => 1];//分类2值
                break;
            case 'year':
                $weekarray = ['01' => ['一月'], '02' => ['二月'], '03' => ['三月'], '04' => ['四月'], '05' => ['五月'], '06' => ['六月'], '07' => ['七月'], '08' => ['八月'], '09' => ['九月'], '10' => ['十月'], '11' => ['十一月'], '12' => ['十二月']];
                $datebefor = date('Y-01-01 00:00:00', strtotime('-1 year'));
                $dateafter = date('Y-12-31 23:59:59', strtotime('-1 year'));
                $order_list = $this->dao->orderAddTimeList($where, [$datebefor, $dateafter], 'year');
                //数据查询重新处理
                $new_order_list = array_combine(array_column($order_list, 'day'), $order_list);
                $now_datebefor = date('Y-01-01 00:00:00');
                $now_dateafter = date('Y-12-31 23:59:59');
                $now_order_list = $this->dao->orderAddTimeList($where, [$now_datebefor, $now_dateafter], 'year');
                //数据查询重新处理 key 变为当前值
                $new_now_order_list = array_combine(array_column($now_order_list, 'day'), $now_order_list);
                $y = date('Y');
                foreach ($weekarray as $dk => $dd) {
                    $order_dk = $y . '-' . $dk;
                    if (isset($new_order_list[$order_dk]) && !empty($new_order_list[$order_dk])) {
                        $weekarray[$dk]['pre'] = $new_order_list[$order_dk];
                    } else {
                        $weekarray[$dk]['pre'] = ['count' => 0, 'day' => $weekarray[$dk][0], 'price' => '0'];
                    }
                    if (isset($new_now_order_list[$order_dk]) && !empty($new_now_order_list[$order_dk])) {
                        $weekarray[$dk]['now'] = $new_now_order_list[$order_dk];
                    } else {
                        $weekarray[$dk]['now'] = ['count' => 0, 'day' => $weekarray[$dk][0], 'price' => '0'];
                    }
                }
                foreach ($weekarray as $k => $v) {
                    $data['day'][] = $v[0];
                    $data['pre']['count'][] = $v['pre']['count'];
                    $data['pre']['price'][] = round($v['pre']['price'], 2);
                    $data['now']['count'][] = $v['now']['count'];
                    $data['now']['price'][] = round($v['now']['price'], 2);
                    if ($chartdata['yAxis']['maxnum'] < $v['pre']['count'] || $chartdata['yAxis']['maxnum'] < $v['now']['count']) {
                        $chartdata['yAxis']['maxnum'] = $v['pre']['count'] > $v['now']['count'] ? $v['pre']['count'] : $v['now']['count'];//日最大订单数
                    }
                    if ($chartdata['yAxis']['maxprice'] < $v['pre']['price'] || $chartdata['yAxis']['maxprice'] < $v['now']['price']) {
                        $chartdata['yAxis']['maxprice'] = $v['pre']['price'] > $v['now']['price'] ? $v['pre']['price'] : $v['now']['price'];//日最大金额
                    }
                }
                $chartdata['legend'] = ['去年金额', '今年金额', '去年订单数', '今年订单数'];//分类
                $chartdata['xAxis'] = $data['day'];//X轴值
                $chartdata['series'][] = ['name' => $chartdata['legend'][0], 'type' => 'bar', 'itemStyle' => $series1, 'data' => $data['pre']['price']];//分类1值
                $chartdata['series'][] = ['name' => $chartdata['legend'][1], 'type' => 'bar', 'itemStyle' => $series1, 'data' => $data['now']['price']];//分类1值
                $chartdata['series'][] = ['name' => $chartdata['legend'][2], 'type' => 'line', 'itemStyle' => $series2, 'data' => $data['pre']['count'], 'yAxisIndex' => 1];//分类2值
                $chartdata['series'][] = ['name' => $chartdata['legend'][3], 'type' => 'line', 'itemStyle' => $series2, 'data' => $data['now']['count'], 'yAxisIndex' => 1];//分类2值
                break;
            default:
                break;
        }
        //统计总数上期
        $pre_total = $this->dao->preTotalFind($where, [$datebefor, $dateafter]);
        if ($pre_total) {
            $chartdata['pre_cycle']['count'] = [
                'data' => $pre_total['count'] ?: 0
            ];
            $chartdata['pre_cycle']['price'] = [
                'data' => $pre_total['price'] ?: 0
            ];
        }
        //统计总数
        $total = $this->dao->preTotalFind($where, [$now_datebefor, $now_dateafter]);
        if ($total) {
            $cha_count = intval($pre_total['count']) - intval($total['count']);
            $pre_total['count'] = $pre_total['count'] == 0 ? 1 : $pre_total['count'];
            $chartdata['cycle']['count'] = [
                'data' => $total['count'] ?: 0,
                'percent' => round((abs($cha_count) / intval($pre_total['count']) * 100), 2),
                'is_plus' => $cha_count > 0 ? -1 : ($cha_count == 0 ? 0 : 1)
            ];
            $cha_price = round($pre_total['price'], 2) - round($total['price'], 2);
            $pre_total['price'] = $pre_total['price'] == 0 ? 1 : $pre_total['price'];
            $chartdata['cycle']['price'] = [
                'data' => $total['price'] ?: 0,
                'percent' => round(abs($cha_price) / $pre_total['price'] * 100, 2),
                'is_plus' => $cha_price > 0 ? -1 : ($cha_price == 0 ? 0 : 1)
            ];
        }
        return $chartdata;
    }

    /**
     * 获取订单数量
     * @param int $store_id
     * @param int $type
     * @return int
     */
    public function storeOrderCount(int $store_id = 0, int $type = -1)
    {
        return $this->dao->storeOrderCount($store_id, $type);
    }

    /**
     * 首页头部统计
     * @return array
     */
    public function homeStatics()
    {
        /** @var UserServices $uSercice */
        $uSercice = app()->make(UserServices::class);
        /** @var StoreProductLogServices $productLogServices */
        $productLogServices = app()->make(StoreProductLogServices::class);
        // 销售额
        //今日销售额
        $today_sales = $this->dao->totalSales('today');
        //昨日销售额
        $yesterday_sales = $this->dao->totalSales('yesterday');
        //日同比
        $sales_today_ratio = $this->countRate($today_sales, $yesterday_sales);
        //周销售额
//        //本周
//        $this_week_sales = $this->dao->totalSales('week');
//        //上周
//        $last_week_sales = $this->dao->totalSales('last week');
//        //周同比
//        $sales_week_ratio = $this->countRate($this_week_sales, $last_week_sales);
        //总销售额
        $total_sales = $this->dao->totalSales('month');
        $sales = [
            'today' => $today_sales,
            'yesterday' => $yesterday_sales,
            'today_ratio' => $sales_today_ratio,
//            'week' => $this_week_sales,
//            'last_week' => $last_week_sales,
//            'week_ratio' => $sales_week_ratio,
            'total' => $total_sales . '元',
            'date' => '今日'
        ];
        //用户访问量
        //今日访问量
        $today_visits = $productLogServices->count(['time' => 'today', 'type' => 'visit']);
        //昨日访问量
        $yesterday_visits = $productLogServices->count(['time' => 'yesterday', 'type' => 'visit']);
        //日同比
        $visits_today_ratio = $this->countRate($today_visits, $yesterday_visits);
//        //本周访问量
//        $this_week_visits = $productLogServices->count(['time' => 'week', 'type' => 'visit']);
//        //上周访问量
//        $last_week_visits = $productLogServices->count(['time' => 'last week', 'type' => 'visit']);
//        //周同比
//        $visits_week_ratio = $this->countRate($this_week_visits, $last_week_visits);
        //总访问量
        $total_visits = $productLogServices->count(['time' => 'month', 'type' => 'visit']);
        $visits = [
            'today' => $today_visits,
            'yesterday' => $yesterday_visits,
            'today_ratio' => $visits_today_ratio,
//            'week' => $this_week_visits,
//            'last_week' => $last_week_visits,
//            'week_ratio' => $visits_week_ratio,
            'total' => $total_visits . 'Pv',
            'date' => '今日'
        ];
        // 订单量
        //今日订单量
        $today_order = $this->dao->totalOrderCount('today');
        //昨日订单量
        $yesterday_order = $this->dao->totalOrderCount('yesterday');
        //订单日同比
        $order_today_ratio = $this->countRate($today_order, $yesterday_order);
//        //本周订单量
//        $this_week_order = $this->dao->totalOrderCount('week');
//        //上周订单量
//        $last_week_order = $this->dao->totalOrderCount('last week');
//        //订单周同比
//        $order_week_ratio = $this->countRate($this_week_order, $last_week_order);
        //总订单量
        $total_order = $this->dao->totalOrderCount('month');
        $order = [
            'today' => $today_order,
            'yesterday' => $yesterday_order,
            'today_ratio' => $order_today_ratio,
//            'week' => $this_week_order,
//            'last_week' => $last_week_order,
//            'week_ratio' => $order_week_ratio,
            'total' => $total_order . '单',
            'date' => '今日'
        ];
        // 用户
        //今日新增用户
        $today_user = $uSercice->totalUserCount('today');
        //昨日新增用户
        $yesterday_user = $uSercice->totalUserCount('yesterday');
        //新增用户日同比
        $user_today_ratio = $this->countRate($today_user, $yesterday_user);
//        //本周新增用户
//        $this_week_user = $uSercice->totalUserCount('week');
//        //上周新增用户
//        $last_week_user = $uSercice->totalUserCount('last week');
//        //新增用户周同比
//        $user_week_ratio = $this->countRate($this_week_user, $last_week_user);
        //所有用户
        $total_user = $uSercice->totalUserCount(['time' => 'month']);
        $user = [
            'today' => $today_user,
            'yesterday' => $yesterday_user,
            'today_ratio' => $user_today_ratio,
//            'week' => $this_week_user,
//            'last_week' => $last_week_user,
//            'week_ratio' => $user_week_ratio,
            'total' => $total_user . '人',
            'date' => '今日'
        ];
        $info = array_values(compact('sales', 'visits', 'order', 'user'));
        $info[0]['title'] = '销售额';
        $info[1]['title'] = '用户访问量';
        $info[2]['title'] = '订单量';
        $info[3]['title'] = '新增用户';
        $info[0]['total_name'] = '本月销售额';
        $info[1]['total_name'] = '本月访问量';
        $info[2]['total_name'] = '本月订单量';
        $info[3]['total_name'] = '本月新增用户';
        return $info;
    }

    /**
     * 打印订单
     * @param $order
     * @param array $cartId
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderPrint($order, array $cartId)
    {
        /** @var StoreOrderCartInfoServices $cartServices */
        $cartServices = app()->make(StoreOrderCartInfoServices::class);
        $product = $cartServices->getCartInfoPrintProduct((int)$order['id'], $cartId);
        if (!$product) {
            throw new ValidateException('订单商品获取失败,无法打印!');
        }
        if ($order['store_id']) {
			$switch = store_config($order['store_id'], 'store_pay_success_printing_switch') ? true : false;
            $data = [
                'clientId' => store_config((int)$order['store_id'], 'store_printing_client_id', ''),
                'apiKey' => store_config((int)$order['store_id'], 'store_printing_api_key', ''),
                'partner' => store_config((int)$order['store_id'], 'store_develop_id', ''),
                'terminal' => store_config((int)$order['store_id'], 'store_terminal_number', '')
            ];
        } else {
			$switch = sys_config('pay_success_printing_switch') ? true : false;
            $data = [
                'clientId' => sys_config('printing_client_id', ''),
                'apiKey' => sys_config('printing_api_key', ''),
                'partner' => sys_config('develop_id', ''),
                'terminal' => sys_config('terminal_number', '')
            ];
        }
		if (!$switch) {
			throw new ValidateException('请先开启小票打印');
		}
		if (!$data['clientId'] || !$data['apiKey'] || !$data['partner'] || !$data['terminal']) {
			throw new ValidateException('请先配置小票打印开发者');
		}
		$printer = new Printer('yi_lian_yun', $data);
		$res = $printer->setPrinterContent([
			'name' => sys_config('site_name'),
			'orderInfo' => is_object($order) ? $order->toArray() : $order,
			'product' => $product
		])->startPrinter();
		if (!$res) {
			throw new ValidateException($printer->getError());
		}
		return $res;
    }

    /**
     * 获取订单确认数据
     * @param array $user
     * @param $cartId
     * @param bool $new
     * @param int $addressId
     * @param int $shipping_type
     * @param int $store_id
     * @param int $coupon_id
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderConfirmData(array $user, $cartId, bool $new, int $addressId, int $shipping_type = 1, int $store_id = 0, int $coupon_id = 0)
    {
        $addr = $data = [];
        $uid = (int)$user['uid'];
        /** @var UserAddressServices $addressServices */
        $addressServices = app()->make(UserAddressServices::class);
        if ($addressId) {
            $addr = $addressServices->getAdderssCache($addressId);
        }
        //没传地址id或地址已删除未找到 ||获取默认地址
        if (!$addr) {
            $addr = $addressServices->getUserDefaultAddressCache($uid);
        }
        $data['upgrade_addr'] = 0;
        if ($addr) {
            $addr = is_object($addr) ? $addr->toArray() : $addr;
            if (isset($addr['upgrade']) && $addr['upgrade'] == 0) {
                $data['upgrade_addr'] = 1;
            }
        } else {
            $addr = [];
        }
        if ($store_id) {
            /** @var SystemStoreServices $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $storeServices->getStoreInfo($store_id);
        }
        /** @var StoreCartServices $cartServices */
        $cartServices = app()->make(StoreCartServices::class);
        //获取购物车信息
        $cartGroup = $cartServices->getUserProductCartListV1($uid, $cartId, $new, $addr, $shipping_type, $store_id, $coupon_id);
        $storeFreePostage = floatval(sys_config('store_free_postage')) ?: 0;//满额包邮金额
        $data['storeFreePostage'] = $storeFreePostage;
        $validCartInfo = $cartGroup['valid'];
        $giveCartList = $cartGroup['giveCartList'] ?? [];
        /** @var StoreOrderComputedServices $computedServices */
        $computedServices = app()->make(StoreOrderComputedServices::class);
        $priceGroup = $computedServices->getOrderPriceGroup($validCartInfo, $addr, $user, $storeFreePostage);
        $priceGroup['couponPrice'] = $cartGroup['couponPrice'] ?? 0;
        $validCartInfo = array_merge($priceGroup['cartInfo'] ?? $validCartInfo, $giveCartList);
        $other = [
            'offlinePostage' => sys_config('offline_postage'),
            'integralRatio' => sys_config('integral_ratio'),
            'give_integral' => $cartGroup['giveIntegral'] ?? 0,
            'give_coupon' => $cartGroup['giveCoupon'] ?? [],
            'give_product' => $cartGroup['giveProduct'],
            'promotions' => $cartGroup['promotions']
        ];
        $deduction = $cartGroup['deduction'];
        $data['product_type'] = $deduction['product_type'] ?? 0;
        $data['valid_count'] = count($validCartInfo);
        $data['addressInfo'] = $addr;
        $data['type'] = $deduction['type'] ?? 0;
        $data['activity_id'] = $deduction['activity_id'] ?? 0;
        $data['seckill_id'] = $deduction['type'] == 1 ? $deduction['activity_id'] : 0;
        $data['bargain_id'] = $deduction['type'] == 2 ? $deduction['activity_id'] : 0;
        $data['combination_id'] = $deduction['type'] == 3 ? $deduction['activity_id'] : 0;
        $data['discount_id'] = $deduction['type'] == 5 ? $deduction['activity_id'] : 0;
        $data['deduction'] = in_array($deduction['product_type'], [1, 2]) || $deduction['activity_id'] > 0;
        $data['cartInfo'] = array_merge($cartGroup['cartInfo'], $giveCartList);
        // $data['giveCartInfo'] = $giveCartList;
        $data['custom_form'] = [];
        if (isset($cartGroup['cartInfo'][0]['productInfo']['custom_form']) && $cartGroup['cartInfo'][0]['productInfo']['custom_form']) {
            $data['custom_form'] = is_string($cartGroup['cartInfo'][0]['productInfo']['custom_form']) ? json_decode($cartGroup['cartInfo'][0]['productInfo']['custom_form'], true) : $cartGroup['cartInfo'][0]['productInfo']['custom_form'];
        }
        $data['give_integral'] = $other['give_integral'];
        $data['give_coupon'] = [];
        if ($other['give_coupon']) {
            /** @var StoreCouponIssueServices $couponIssueService */
            $couponIssueService = app()->make(StoreCouponIssueServices::class);
            $data['give_coupon'] = $couponIssueService->getColumn([['id', 'IN', $other['give_coupon']]], 'id,coupon_title');
        }
        $data['priceGroup'] = $priceGroup;
        $data['orderKey'] = $this->cacheOrderInfo($uid, $validCartInfo, $priceGroup, $other, $addr, $cartGroup['invalid'] ?? [], $deduction);
        $data['offlinePostage'] = $other['offlinePostage'];
        if (isset($user['pwd'])) unset($user['pwd']);
        $user['vip'] = isset($priceGroup['vipPrice']) && $priceGroup['vipPrice'] > 0 ? true : false;
        $user['vip_id'] = 0;
        $user['discount'] = 0;
        //用户等级是否开启
        if (sys_config('member_func_status', 1)) {
            /** @var UserLevelServices $levelServices */
            $levelServices = app()->make(UserLevelServices::class);
            $userLevel = $levelServices->getUerLevelInfoByUid($uid);
            if ($user['vip'] || $userLevel) {
                $user['vip'] = true;
                $user['vip_id'] = $userLevel['id'] ?? 0;
                $user['discount'] = $userLevel['discount'] ?? 0;
            }
        }
        $user['record_pone'] = !isset($user['record_pone']) || !$user['record_pone'] ? '' : $user['record_pone'];
        $data['userInfo'] = $user;
        $data['integralRatio'] = $other['integralRatio'];
        $data['offline_pay_status'] = (int)sys_config('offline_pay_status') ?? (int)2;
        $data['yue_pay_status'] = (int)sys_config('balance_func_status') && (int)sys_config('yue_pay_status') == 1 ? (int)1 : (int)2;//余额支付 1 开启 2 关闭
        $data['pay_weixin_open'] = (int)sys_config('pay_weixin_open') ?? 0;//微信支付 1 开启 0 关闭
        $data['store_self_mention'] = (int)sys_config('store_self_mention') ?? 0;//门店自提是否开启
        /** @var SystemStoreServices $systemStoreServices */
        $systemStoreServices = app()->make(SystemStoreServices::class);
        $store_count = $systemStoreServices->count(['type' => 0]);
        $data['store_self_mention'] = $data['store_self_mention'] && $store_count;
        $data['ali_pay_status'] = sys_config('ali_pay_status') ? true : false;//支付包支付 1 开启 0 关闭
        $data['system_store'] = [];//门店信息
        /** @var UserInvoiceServices $userInvoice */
        $userInvoice = app()->make(UserInvoiceServices::class);
        $invoice_func = $userInvoice->invoiceFuncStatus();
        $data['invoice_func'] = $invoice_func['invoice_func'];
        $data['special_invoice'] = $invoice_func['special_invoice'];
        return $data;
    }

    /**
     * 缓存订单信息
     * @param int $uid
     * @param array $cartInfo
     * @param array $priceGroup
     * @param array $other
     * @param array $addr
     * @param array $invalidCartInfo
     * @param array $deduction
     * @param int $cacheTime
     * @return string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function cacheOrderInfo(int $uid, array $cartInfo, array $priceGroup, array $other = [], array $addr = [], array $invalidCartInfo = [], array $deduction = [], int $cacheTime = 600)
    {
        /** @var StoreOrderCreateServices $storeOrderCreateService */
        $storeOrderCreateService = app()->make(StoreOrderCreateServices::class);
        $key = md5($storeOrderCreateService->getNewOrderId((string)$uid) . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8));
        CacheService::redisHandler()->set('user_order_' . $uid . $key, compact('cartInfo', 'priceGroup', 'other', 'addr', 'invalidCartInfo', 'deduction'), $cacheTime);
        return $key;
    }

    /**
     * 获取订单缓存信息
     * @param int $uid
     * @param string $key
     * @return |null
     */
    public function getCacheOrderInfo(int $uid, string $key)
    {
        $cacheName = 'user_order_' . $uid . $key;
        if (!CacheService::redisHandler()->has($cacheName)) return null;
        return CacheService::redisHandler()->get($cacheName);
    }

    /**
     * 获取用户购买活动产品的次数
     * @param $uid
     * @param $seckill_id
     * @return int
     */
    public function activityProductCount(array $where)
    {
        return $this->dao->count($where);
    }

    /**
     * 获取拼团的订单id
     * @param int $pid
     * @param int $uid
     * @return mixed
     */
    public function getStoreIdPink(int $pid, int $uid)
    {
        return $this->dao->value(['uid' => $uid, 'pink_id' => $pid, 'is_del' => 0], 'order_id');
    }

    /**
     * 判断当前订单中是否有拼团
     * @param int $pid
     * @param int $uid
     * @return int
     */
    public function getIsOrderPink($pid = 0, $uid = 0)
    {
        return $this->dao->count(['uid' => $uid, 'pink_id' => $pid, 'refund_status' => 0, 'is_del' => 0]);
    }

    /**
     * 判断支付方式是否开启
     * @param $payType
     * @return bool
     */
    public function checkPaytype(string $payType)
    {
        $res = false;
        switch ($payType) {
            case PayServices::WEIXIN_PAY:
                $res = sys_config('pay_weixin_open') ? true : false;
                break;
            case PayServices::YUE_PAY:
                $res = sys_config('balance_func_status') && sys_config('yue_pay_status') == 1 ? true : false;
                break;
            case 'offline':
                $res = sys_config('offline_pay_status') == 1 ? true : false;
                break;
            case PayServices::ALIAPY_PAY:
                $res = sys_config('ali_pay_status') == 1 ? true : false;
                break;
        }
        return $res;
    }

    /**
     * 修改支付方式为线下支付
     * @param string $orderId
     * @return bool
     */
    public function setOrderTypePayOffline(string $orderId)
    {
        return $this->dao->update($orderId, ['pay_type' => 'offline'], 'order_id');
    }

    /**
     * 删除订单
     * @param $uni
     * @param $uid
     * @return bool
     */
    public function removeOrder(string $uni, int $uid)
    {
        $order = $this->getUserOrderDetail($uni, $uid);
        if (!$order) {
            throw new ValidateException('订单不存在!');
        }
        $order = $this->tidyOrder($order);
        if ($order['_status']['_type'] != 0 && $order['_status']['_type'] != -2 && $order['_status']['_type'] != 4)
            throw new ValidateException('该订单无法删除!');
        $order->is_del = 1;
        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        $res = $statusService->save([
            'oid' => $order['id'],
            'change_type' => 'remove_order',
            'change_message' => '删除订单',
            'change_time' => time()
        ]);
        if ($order->save() && $res) {
            //未支付和已退款的状态下才可以退积分退库存退优惠券
            if ($order['_status']['_type'] == 0 || $order['_status']['_type'] == -2) {
                /** @var StoreOrderRefundServices $refundServices */
                $refundServices = app()->make(StoreOrderRefundServices::class);
                $this->transaction(function () use ($order, $refundServices) {
                    //回退积分和优惠卷
                    $res = $refundServices->integralAndCouponBack($order);
                    //回退库存
                    $res = $res && $refundServices->regressionStock($order);
                    if (!$res) {
                        throw new ValidateException('取消订单失败!');
                    }
                });
            }
            return true;
        } else
            throw new ValidateException('订单删除失败!');
    }

    /**
     * 取消订单
     * @param $order_id
     * @param int $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cancelOrder($order_id, int $uid)
    {
        $order = $this->dao->getOne(['order_id' => $order_id, 'uid' => $uid, 'is_del' => 0]);
        if (!$order) {
            throw new ValidateException('没有查到此订单');
        }
        if ($order->paid) {
            throw new ValidateException('订单已经支付无法取消');
        }
        /** @var StoreOrderRefundServices $refundServices */
        $refundServices = app()->make(StoreOrderRefundServices::class);
        $this->transaction(function () use ($refundServices, $order) {
            //回退积分和优惠卷
            $res = $refundServices->integralAndCouponBack($order);
            //回退库存和销量
            $res = $res && $refundServices->regressionStock($order);
            $order->is_del = 1;
            if (!($res && $order->save())) {
                throw new ValidateException('取消订单失败');
            }
        });
		//订单取消事件
		event('order.cancel', [$order]);
        return true;
    }

    /**
     * 判断订单完成
     * @param StoreProductReplyServices $replyServices
     * @param array $uniqueList
     * @param $oid
     * @return mixed
     */
    public function checkOrderOver($replyServices, array $uniqueList, $oid)
    {
        //订单商品全部评价完成
        $replyServices->count(['unique' => $uniqueList, 'oid' => $oid]);
        if ($replyServices->count(['unique' => $uniqueList, 'oid' => $oid]) == count($uniqueList)) {
            $res = $this->dao->update($oid, ['status' => '3']);
            if (!$res) throw new ValidateException('评价后置操作失败!');
            /** @var StoreOrderStatusServices $statusService */
            $statusService = app()->make(StoreOrderStatusServices::class);
            $statusService->save([
                'oid' => $oid,
                'change_type' => 'check_order_over',
                'change_message' => '用户评价',
                'change_time' => time()
            ]);
        }
    }

    /**
     * 某个用户订单
     * @param int $uid
     * @param UserServices $userServices
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserOrderList(int $uid)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserWithTrashedInfo($uid);
        if (!$user) {
            throw  new ValidateException('数据不存在');
        }
        [$page, $limit] = $this->getPageValue();
        $where = ['uid' => $uid, 'pid' => [0, -1], 'paid' => 1, 'is_del' => 0, 'is_system_del' => 0];
        $list = $this->dao->getStairOrderList($where, 'order_id,real_name,total_num,total_price,pay_price,FROM_UNIXTIME(pay_time,"%Y-%m-%d") as pay_time,paid,pay_type,type,activity_id,activity_append', $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取推广订单列表
     * @param int $uid
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserStairOrderList(int $uid, $where)
    {
        $where_data = [];
        if (isset($where['type'])) {
            switch ((int)$where['type']) {
                case 1:
                    $where_data['spread_uid'] = $uid;
                    break;
                case 2:
                    $where_data['spread_two_uid'] = $uid;
                    break;
                default:
                    $where_data['spread_or_uid'] = $uid;
                    break;
            }
        }
        if (isset($where['data']) && $where['data']) {
            $where_data['time'] = $where['data'];
        }
        if (isset($where['order_id']) && $where['order_id']) {
            $where_data['order_id'] = $where['order_id'];
        }
        //推广订单只显示支付过并且未退款的订单
        $where_data['pid'] = 0;
        $where_data['paid'] = 1;
        $where_data['refund_status'] = 0;
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getStairOrderList($where_data, '*', $page, $limit);
        $count = $this->dao->count($where_data);
        return compact('list', 'count');
    }

    /**
     * 订单导出
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getExportList(array $where, array $with = [], int $limit = 0)
    {
        if ($limit) {
            [$page] = $this->getPageValue();
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        $list = $this->dao->search($where)->with($with)->page($page, $limit)->select()->toArray();
        if ($list) {
            /** @var WechatUserServices $wechatUserService */
            $wechatUserService = app()->make(WechatUserServices::class);
            $userSex = $wechatUserService->getColumn([['uid', 'IN', array_unique(array_column($list, 'uid'))]], 'uid,sex', 'uid');
            foreach ($list as &$item) {
                /** @var StoreOrderCartInfoServices $orderCart */
                $orderCart = app()->make(StoreOrderCartInfoServices::class);
                $_info = $orderCart->getCartColunm(['oid' => $item['id']], 'cart_info', 'unique');
                foreach ($_info as $k => $v) {
                    $cart_info = is_string($v) ? json_decode($v, true) : $v;
                    if (!isset($cart_info['productInfo'])) $cart_info['productInfo'] = [];
                    $_info[$k] = $cart_info;
                    unset($cart_info);
                }
                $item['_info'] = $_info;
                $item['sex'] = $userSex[$item['uid']]['sex'] ?? '';
                switch ($item['type']) {
                    case 0://普通订单
                        if ($item['shipping_type'] == 1) {
                            $item['pink_name'] = '[普通订单]';
                            $item['color'] = '#895612';
                        } else if ($item['shipping_type'] == 2) {
                            $item['pink_name'] = '[核销订单]';
                            $item['color'] = '#8956E8';
                        }
                        break;
                    case 1://秒杀
                        $item['pink_name'] = '[秒杀订单]';
                        $item['color'] = '#32c5e9';
                        break;
                    case 2://砍价
                        $item['pink_name'] = '[砍价订单]';
                        $item['color'] = '#12c5e9';
                        break;
                    case 3://拼团
                        /** @var StorePinkServices $pinkService */
                        $pinkService = app()->make(StorePinkServices::class);
                        $pinkStatus = $pinkService->value(['order_id_key' => $item['id']], 'status');
                        switch ($pinkStatus) {
                            case 1:
                                $item['pink_name'] = '[拼团订单]正在进行中';
                                $item['color'] = '#f00';
                                break;
                            case 2:
                                $item['pink_name'] = '[拼团订单]已完成';
                                $item['color'] = '#00f';
                                break;
                            case 3:
                                $item['pink_name'] = '[拼团订单]未完成';
                                $item['color'] = '#f0f';
                                break;
                            default:
                                $item['pink_name'] = '[拼团订单]历史订单';
                                $item['color'] = '#457856';
                                break;
                        }
                        break;
                    case 5://套餐
                        $item['pink_name'] = '[优惠套餐]';
                        $item['color'] = '#12c5e9';
                        break;
                }
            }
        }
        return $list;
    }

    /**
     * 自动取消订单
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function runOrderUnpaidCancel(int $page = 0, int $limit = 0)
    {
        $list = $this->dao->getOrderUnPaid(0, $page, $limit)->field(['*'])->select();
        if (!$list) {
            return true;
        }
        //系统预设取消订单时间段
        $keyValue = ['order_cancel_time', 'order_activity_time', 'order_bargain_time', 'order_seckill_time', 'order_pink_time'];
        //获取配置
        $systemValue = SystemConfigService::more($keyValue);
        //格式化数据
        $systemValue = Arr::setValeTime($keyValue, is_array($systemValue) ? $systemValue : []);
        /** @var StoreOrderRefundServices $refundServices */
        $refundServices = app()->make(StoreOrderRefundServices::class);
        foreach ($list as $order) {
            switch ($order['type']) {
                case 1://秒杀
                    $secs = $systemValue['order_seckill_time'] ? $systemValue['order_seckill_time'] : $systemValue['order_activity_time'];
                    break;
                case 2://砍价
                    $secs = $systemValue['order_bargain_time'] ? $systemValue['order_bargain_time'] : $systemValue['order_activity_time'];
                    break;
                case 3://拼团
                    $secs = $systemValue['order_pink_time'] ? $systemValue['order_pink_time'] : $systemValue['order_activity_time'];
                    break;
                default:
                    $secs = $systemValue['order_cancel_time'];
            }
            if ($secs == 0) continue;
            if (($order['add_time'] + bcmul($secs, '3600', 0)) < time()) {
                try {
                    $this->transaction(function () use ($order, $refundServices) {
                        //回退积分和优惠卷
                        $res = $refundServices->integralAndCouponBack($order);
                        //回退库存和销量
                        $res = $res && $refundServices->regressionStock($order);
                        //修改订单状态
                        $res = $res && $this->dao->update($order['id'], ['is_del' => 1, 'mark' => '订单未支付已超过系统预设时间']);
                        if (!$res) {
                            throw new ValidateException('订单号' . $order['order_id'] . '自动取消订单失败');
                        }
                        return true;
                    });
                	//订单取消事件
					event('order.cancel', [$order]);
				} catch (\Throwable $e) {
                    Log::error('自动取消订单失败,失败原因:' . $e->getMessage(), $e->getTrace());
                }
            }
        }
        return true;
    }

    /**
     * 批量加入对接
     * @param int $count
     * @param int $limit
     */
    public function batchJoinJob(int $count, int $limit)
    {
        $pages = ceil($limit / $count);
        for ($i = 1; $i <= $pages; $i++) {
            AutoOrderUnpaidCancelJob::dispatch([$i, $limit]);
        }
        return true;
    }

    /**
     * 自动取消订单
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderUnpaidCancel()
    {
        $count = $this->dao->getOrderUnPaid()->count();
        $maxLimit = 100;
        if ($count > $maxLimit) {
            return $this->batchJoinJob($count, $maxLimit);
        }
        return $this->runOrderUnpaidCancel();
    }

    /**
     * 根据时间获取当天或昨天订单营业额
     * @param array $where
     * @return float|int
     */
    public function getOrderMoneyByWhere(array $where, string $sum_field, string $selectType, string $group = "")
    {

        switch ($selectType) {
            case "sum" :
                return $this->dao->getDayTotalMoney($where, $sum_field);
            case "group" :
                return $this->dao->getDayGroupMoney($where, $sum_field, $group);
        }
    }

    /**
     * 统计时间段订单数
     * @param array $where
     * @param string $sum_field
     */
    public function getOrderCountByWhere(array $where)
    {
        return $this->dao->getDayOrderCount($where);
    }

    /**
     * 分组统计时间段订单数
     * @param $where
     * @return mixed
     */
    public function getOrderGroupCountByWhere($where)
    {
        return $this->dao->getOrderGroupCount($where);
    }

    /**
     * 时间段支付订单人数
     * @param $where
     * @return mixed
     */
    public function getPayOrderPeopleByWhere($where)
    {
        return $this->dao->getPayOrderPeople($where);
    }

    /**
     * 时间段分组统计支付订单人数
     * @param $where
     * @return mixed
     */
    public function getPayOrderGroupPeopleByWhere($where)
    {
        return $this->dao->getPayOrderGroupPeople($where);
    }

    /**
     * @param array $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOrderListByWhere(array $where, $field)
    {
        return $this->dao->getOrderListByWhere($where, $field);
    }

    /**
     * 批量更新数据
     * @param array $ids
     * @param array $data
     * @param string|null $key
     * @return BaseModel
     */
    public function orderDel(array $ids, $redisKey, $queueId)
    {
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $res = $this->dao->batchUpdateOrder($ids, ['is_system_del' => 1]);
        if ($res) {
            $queueService->doSuccessSremRedis($ids, $redisKey, $queueId['type']);
        } else {
            $queueService->addQueueFail($queueId['id'], $redisKey);
            throw new AdminException('删除失败');
        }
    }

    /**
     * 获取发货excel文件数据
     * @param string $file
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function readExpreExcel(string $file, $row = 2)
    {
        if (!$file) throw new AdminException('请上传发货数据表');
        /** @var FileService $readExcelService */
        $readExcelService = app()->make(FileService::class);
        $exprData = $readExcelService->readExcel($file, $row);
        if (!$exprData) throw new AdminException('发货数据为空');
        return $exprData;
    }

    /**
     * 队列发货
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function adminQueueOrderDo(array $data, bool $is_again = false)
    {
        if (!$data) return false;
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        /** @var QueueAuxiliaryServices $auxiliaryService */
        $auxiliaryService = app()->make(QueueAuxiliaryServices::class);
        $queueWhere['type'] = $data['queueType'];
        $queueWhere['status'] = 0;
        if (isset($data['queueId']) && $data['queueId']) $queueWhere['id'] = $data['queueId'];
        $queueInfo = $queueService->getQueueOne($queueWhere);
        $ids = $auxiliaryService->getCacheOidList($queueInfo['id'], $data['cacheType']);
        $data['ids'] = array_column($ids, 'relation_id');
        $data['queueId'] = $queueInfo['id'];
        //if ($queueInfo['status'] == 2) throw new ValidateException('任务已完成');
        //把队列需要执行的入参数据存起来，以便队列执行失败后接着执行，同时队列状态改为正在执行状态。
        $queueService->setQueueDoing($data, $queueInfo['id'], $is_again);
        $oids = $auxiliaryService->getOrderExpreList(['binding_id' => $queueInfo['id'], 'type' => $data['cacheType'], 'status' => [0, 2]]);
        $oids = $oids ? array_column($oids, 'relation_id') : [];
        // $chunkPids = array_chunk($oids, 1000, true);
        $data['queueId'] = $queueInfo['id'];
        foreach ($oids as $v) {
            //加入队列
            BatchHandleJob::dispatch([$v, $data['queueType'], $data]);
        }
        return true;
    }

    /**
     * 对外接口获取订单状态
     * @param int $oid
     */
    public function outGetStatus(string $oid)
    {
        $order = $this->dao->getOne(['order_id' => $oid]);
        if (!$order['paid'] && $order['pay_type'] == 'offline' && !$order['status'] >= 2) {
            $status_name = '线下支付';
        } else if (!$order['paid']) {
            $status_name = '未支付';
        } else if ($order['status'] == 0 && $order['refund_status'] == 0) {
            $status_name = '待发货';
        } else if ($order['refund_status'] == 1) {
            $status_name = '申请退款中';
        } else if ($order['refund_status'] == 2) {
            $status_name = '已退款';
        } else if ($order['refund_status'] == 3) {
            $status_name = '部分退款（子订单）';
        } else if ($order['refund_status'] == 4) {
            $status_name = '子订单已全部申请退款中';
        } else if (!$order['status']) {
            $status_name = '未发货';
        } else if ($order['status'] == 1) {
            $status_name = '待收货';
        } else if ($order['status'] == 2) {
            $status_name = '待评价';
        } else if ($order['status'] == 3) {
            $status_name = '交易完成';
        }
        $data = [];
        $data['status_name'] = $status_name;
        $data['status'] = $order['status'];
        $data['paid'] = $order['paid'];
        $data['pay_type'] = $order['pay_type'];
        $data['refund_status'] = $order['refund_status'];
        return $data;
    }

    /**
     * 对外接口根据订单id查询收货方式
     * @param string $oid
     * @return array
     */
    public function outGetShippingType(string $oid)
    {
        $shipping_type = $this->dao->value(['order_id' => $oid], 'shipping_type');
        $shipping_type = $shipping_type == 1 ? '商家配送' : '到店自提';
        return compact('shipping_type');
    }

    /**
     * 对外接口根据订单id查询配送信息
     * @param string $oid
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function OutDeliveryType(string $oid)
    {
        $info = $this->dao->getOne(['order_id' => $oid], 'order_id,delivery_type,delivery_name,delivery_id');
        return $info ? $info->toArray() : [];
    }

    /**
     * 对外接口获取运费
     * @param int $cartId
     * @param int $uid
     * @param int $addressId
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function outGetPostage($cartId, int $uid, int $addressId, int $couponId = 0)
    {
        $addr = [];
        /** @var UserAddressServices $addressServices */
        $addressServices = app()->make(UserAddressServices::class);
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->get($uid);
        if ($addressId) {
            $addr = $addressServices->getAdderssCache($addressId);
        }
        //没传地址id或地址已删除未找到 ||获取默认地址
        if (!$addr) {
            $addr = $addressServices->getUserDefaultAddressCache($uid);
        }

        /** @var StoreCartServices $cartServices */
        $cartServices = app()->make(StoreCartServices::class);
        $cartGroup = $cartServices->getUserProductCartListV1($uid, $cartId, true, $addr);
        $storeFreePostage = floatval(sys_config('store_free_postage')) ?: 0;//满额包邮金额
        $validCartInfo = $cartGroup['valid'];
        /** @var StoreOrderComputedServices $computedServices */
        $computedServices = app()->make(StoreOrderComputedServices::class);
        $priceGroup = $computedServices->getOrderPriceGroup($validCartInfo, $addr, $user, $storeFreePostage);
        $postage = $priceGroup['storePostage'] ?? 0;
        return compact('postage');
    }

    /**
     * 获取配送员订单统计列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDeliveryStatistics(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_del'] = 0;
        $where['paid'] = 1;
        $where['is_system_del'] = 0;
        $where['delivery_type'] = 'send';
        $where['refund_status'] = [0, 3];
        $list = $this->dao->getList((array)$where, ['*'], (int)$page, (int)$limit, ['user']);
        if ($list) {
            $list = $this->tidyOrderList($list, false);
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 配送员订单统计
     * @param $store_id
     * @param $delivery_uid
     * @param $time
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStatisticsHeader($store_id, $delivery_uid, $time)
    {
        $where['is_del'] = 0;
        $where['paid'] = 1;
        $where['is_system_del'] = 0;
        $where['delivery_type'] = 'send';
        $where['store_id'] = $store_id;
        $where['refund_status'] = [0, 3];
        if ($delivery_uid) {
            $where['delivery_uid'] = $delivery_uid;
        }
        [$start, $end, $timeType, $xAxis] = $time;
        $order = $this->dao->orderAddTimeList($where, [$start, $end], $timeType, false);
        $price = array_column($order, 'price', 'day');
        $count = array_column($order, 'count', 'day');
        $data = $series = [];
        foreach ($xAxis as $key) {
            $data['配送订单金额'][] = isset($price[$key]) ? floatval($price[$key]) : 0;
            $data['配送单数'][] = isset($count[$key]) ? floatval($count[$key]) : 0;
        }
        foreach ($data as $key => $item) {
            $series[] = [
                'name' => $key,
                'data' => $item,
                'type' => 'line',
                'smooth' => 'true',
                'yAxisIndex' => 1,
            ];
        }
        return compact('xAxis', 'series');
    }

    /**
     * 门店线上支付订单详情
     * @param int $store
     * @param int $uid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function payCashierOrder(int $store, int $uid)
    {
        $order = $this->dao->payCashierOrder($store, $uid);
        if (!$order) throw new ValidateException('订单不存在');
        $order = $order->toArray();
        $order = $this->tidyOrder($order, true);
        $order['yue_pay_status'] = (int)sys_config('balance_func_status') && (int)sys_config('yue_pay_status') == 1 ? (int)1 : (int)2;//余额支付 1 开启 2 关闭
        $order['pay_weixin_open'] = (int)sys_config('pay_weixin_open') ?? 0;//微信支付 1 开启 0 关闭
        $order['ali_pay_status'] = sys_config('ali_pay_status') ? true : false;//支付包支付 1 开启 0 关闭
        return $order;
    }

    /**
     * 订单分配｜重新分配给你门店
     * @param int $id
     * @param int $store_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function shareOrder(int $id, int $store_id)
    {
        $orderInfo = $this->dao->get((int)$id);
        if (!$orderInfo) {
            throw new ValidateException('订单不存在');
        }
        //卡密商品
        if ($orderInfo['product_type'] == 1) {
            throw new ValidateException('订单中卡密商品门店暂不支持');
        }
        /** @var SystemStoreServices $storeServices */
        $storeServices = app()->make(SystemStoreServices::class);
        $storeInfo = $storeServices->getStoreInfo($store_id);
        if ($orderInfo['status'] != 0) {
            throw new ValidateException('订单已发货');
        }
        /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
        $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $cart_info = $storeOrderCartInfoServices->getSplitCartList($id, 'cart_info');
        if (!$cart_info) {
            throw new ValidateException('订单已发货或');
        }
        $productIds = array_unique(array_column($cart_info, 'product_id'));
        /** @var StoreBranchProductServices $branchProductServics */
        $branchProductServics = app()->make(StoreBranchProductServices::class);
        $productCount = count($productIds);
        //商品没下架
        if ($productCount != $branchProductServics->getCount([['product_id', 'IN', $productIds], ['is_show', '=', 1], ['is_del', '=', 0], ['store_id', '=', $store_id]])) {
            throw new ValidateException('该门店商品未上架或未设置库存');
        }
        $attrUniques = [];
        $attrUniquesArr = [];
        /** @var StoreProductAttrValueServices $skuValueServices */
        $skuValueServices = app()->make(StoreProductAttrValueServices::class);
        foreach ($cart_info as $cart) {
            if (isset($cart['productInfo']['store_delivery']) && !$cart['productInfo']['store_delivery']) {//有商品不支持门店配送
                throw new ValidateException('有商品不支持门店配送');
            }
            $type = $cart['type'];
            switch ($type) {
                case 0:
                case 6:
                    $attrUniques[] = $cart['product_attr_unique'];
                    $attrUniquesArr[$cart['product_attr_unique']] = $cart['product_attr_unique'];
                    break;
                case 1:
                case 2:
                case 3:
                case 5:
                    $suk = $skuValueServices->value(['unique' => $cart['product_attr_unique'], 'product_id' => $cart['activity_id'], 'type' => $type], 'suk');
                    $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $cart['product_id'], 'type' => 0], 'unique');
                    $attrUniquesArr[$cart['product_attr_unique']] = $productUnique;
                    $attrUniques[] = $productUnique;
                    break;
            }
        }
        /** @var StoreBranchProductAttrValueServices $storeValueService */
        $storeValueService = app()->make(StoreBranchProductAttrValueServices::class);
        $allStock = $storeValueService->getColumn([['unique', 'in', $attrUniques], ['store_id', '=', $store_id]], 'stock', 'unique');
        if (!$allStock) {
            throw new ValidateException('该门店商品库存不足');
        }
        foreach ($cart_info as $item) {
            if ($item['cart_num'] > ($allStock[$attrUniquesArr[$item['product_attr_unique']]] ?? 0)) {
                throw new ValidateException('该门店商品库存不足');
            }
        }
        $res = $this->transaction(function () use ($id, $store_id, $orderInfo, $storeInfo, $cart_info, $branchProductServics) {

            if ($orderInfo['store_id'] > 0) {//重新分配门店
                //返还原来门店库存
                $res = $branchProductServics->regressionBranchProductStock($orderInfo, $cart_info, -1, 0);
            } else {
                //返还平台库存
                $res = $branchProductServics->regressionBranchProductStock($orderInfo, $cart_info, 0, -1);
            }
            //扣门店库存
            $res = $branchProductServics->regressionBranchProductStock($orderInfo, $cart_info, -1, 1, $store_id);
            $res = $res && $this->dao->update($id, ['store_id' => $storeInfo['id'], 'shipping_type' => $orderInfo['shipping_type'] == 1 ? 3 : $orderInfo['shipping_type']]);
            return $res;
        });
        $orderInfo['store_id'] = $storeInfo['id'];
        //删除之前的账单记录
        /** @var StoreFinanceFlowServices $storeFinanceFlowServices */
        $storeFinanceFlowServices = app()->make(StoreFinanceFlowServices::class);
        $storeFinanceFlowServices->update(['link_id' => $orderInfo['order_id']], ['is_del' => 1]);
        //分配后置方法
        SpliteStoreOrderJob::dispatchDo('splitAfter', [$orderInfo, true]);
        return $res;
    }

    /**
     * 获取退货商品列表
     * @param array $cart_ids
     * @param int $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function refundCartInfoList(array $cart_ids = [], int $id = 0)
    {
        $orderInfo = $this->dao->get($id);
        if (!$orderInfo) {
            throw new ValidateException('订单不存在');
        }
        $orderInfo = $this->tidyOrder($orderInfo, true);
        $cartInfo = $orderInfo['cartInfo'] ?? [];
        $data = [];
        if ($cart_ids) {
            foreach ($cart_ids as $cart) {
                if (!isset($cart['cart_id']) || !$cart['cart_id']) {
                    throw new ValidateException('请重新选择退款商品，或件数');
                }
            }
            $cart_ids = array_combine(array_column($cart_ids, 'cart_id'), $cart_ids);
            $i = 0;
            foreach ($cartInfo as $item) {
                if (isset($cart_ids[$item['id']])) {
                    $data['cartInfo'][$i] = $item;
                    if (isset($cart_ids[$item['id']]['cart_num'])) $data['cartInfo'][$i]['cart_num'] = $cart_ids[$item['id']]['cart_num'];
                    $i++;
                }
            }
        }
        $data['_status'] = $orderInfo['_status'] ?? [];
        $data['cartInfo'] = $data['cartInfo'] ?? $cartInfo;
        return $data;
    }

    /**
     * 拆单的发货订单数量
     * @param int $id
     * @param $order
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDeliverNum(int $id, $order): int
    {
        if (!$order) {
            $order = $this->get($id);
        }
        $ids = $id;
        $pid = (int)$order['pid'];
        if ($pid > 0) {
            $ids = $this->Value([['pid', '=', $pid], ['status', '=', 1]], 'GROUP_CONCAT(id)');
            if (!empty($ids)) {
                $ids = array_map('intval', array_filter(explode(',', $ids)));
            }
        }
        return $this->getCount(['id' => $ids, 'status' => 1]);
    }
}
