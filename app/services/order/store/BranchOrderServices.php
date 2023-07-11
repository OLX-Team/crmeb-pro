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

namespace app\services\order\store;


use app\dao\order\StoreOrderDao;
use app\services\BaseServices;
use app\services\order\OtherOrderServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderStatusServices;
use app\services\store\StoreUserServices;
use app\services\user\UserCardServices;
use app\services\user\UserRechargeServices;
use think\exception\ValidateException;

/**
 * Class StoreOrderWapServices
 * @package app\services\order
 * @method getOne(array $where, ?string $field = '*') 获取一条数据
 * @method count(array $where = []): int 读取数据条数
 * @method sum(array $where, string $field, bool $search = false) 求和
 */
class BranchOrderServices extends BaseServices
{
    /**
     * StoreOrderWapServices constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取订单数量
     * @param int $store_id
     * @param int $staff_id
     * @return array
     */
    public function getOrderData(int $store_id, int $staff_id = 0)
    {
        $where = ['pid' => 0, 'store_id' => $store_id, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0];
        $data['order_count'] = (string)$this->dao->count($where);
        $where = $where + ['paid' => 1];
        $data['sum_price'] = (string)$this->dao->sum($where, 'pay_price', true);

        $countWhere = ['store_id' => $store_id];
        if ($staff_id) {
            $countWhere['staff_id'] = $staff_id;
        }
        $pid_where = ['pid' => 0];
        $not_pid_where = ['not_pid' => 1];
        $data['unpaid_count'] = (string)$this->dao->count(['status' => 0] + $countWhere + $pid_where);
        $data['unshipped_count'] = (string)$this->dao->count(['status' => 1] + $countWhere + $pid_where);
        $data['unwriteoff_count'] = (string)$this->dao->count(['status' => 5] + $countWhere + $pid_where);
        $data['received_count'] = (string)$this->dao->count(['status' => 2] + $countWhere + $pid_where);
        $data['evaluated_count'] = (string)$this->dao->count(['status' => 3] + $countWhere + $pid_where);
        $data['complete_count'] = (string)$this->dao->count(['status' => 4] + $countWhere + $pid_where);
        /** @var StoreOrderRefundServices $storeOrderRefundServices */
        $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);
        $refund_where = ['store_id' => $store_id, 'is_cancel' => 0];
        $data['refunding_count'] = (string)$storeOrderRefundServices->count($refund_where + ['refund_type' => [1, 2, 4, 5]]);
        $data['refunded_count'] = (string)$storeOrderRefundServices->count($refund_where + ['refund_type' => [3, 6]]);
        $data['refund_count'] = (string)$storeOrderRefundServices->count($refund_where);
        return $data;
    }

    /**
     * 订单统计详情列表
     * @param int $store_id
     * @param int $staff_id
     * @param int $type
     * @param array $time
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function time(int $store_id, int $staff_id = 0, int $type = 1, array $time = [])
    {
        if (!$time) {
            return [[], []];
        }
        [$start, $stop, $front, $front_stop] = $time;
        $order_where = ['pid' => 0, 'is_del' => 0, 'paid' => 1, 'store_id' => $store_id];
        if ($type != 3) {
            $order_where['refund_status'] = [0, 3];
        }
        if ($staff_id) $order_where['staff_id'] = $staff_id;
        switch ($type) {
            case 1://配送
            case 2://配送
                $order_where['shipping_type'] = 3;
                break;
            case 3://退款
                $order_where['status'] = -3;
                break;
            case 4://收银订单
                $order_where['shipping_type'] = 4;
                break;
            case 5://核销
                $order_where['shipping_type'] = 2;
                break;
        }
        if ($type == 2) {//数量
            $frontPrice = $this->dao->count($order_where + ['time' => [$front, $front_stop]]);
            $nowPrice = $this->dao->count($order_where + ['time' => [$start, $stop]]);
        } else {//金额
            $frontPrice = $this->dao->sum($order_where + ['time' => [$front, $front_stop]], 'pay_price', true);
            $nowPrice = $this->dao->sum($order_where + ['time' => [$start, $stop]], 'pay_price', true);
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getOrderList($order_where + ['time' => [$start, $stop]], ['id', 'order_id', 'uid', 'spread_uid', 'pay_price', 'add_time'], $page, $limit);
        foreach ($list as &$item) {
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
        }
        return [[$nowPrice, $frontPrice], $list];
    }

    /**
     * 订单每月统计数据(按天分组)
     * @param array $where
     * @param array|string[] $field
     * @return array
     */
    public function getOrderDataPriceCount(array $where, array $field = ['sum(pay_price) as price', 'count(id) as count', 'FROM_UNIXTIME(add_time, \'%m-%d\') as time'])
    {
        [$page, $limit] = $this->getPageValue();
        $order_where = ['is_del' => 0, 'is_system_del' => 0, 'paid' => 1, 'refund_status' => [0, 3]];
        $where = array_merge($where, $order_where);
        return $this->dao->getOrderDataPriceCount($where, $field, $page, $limit);
    }

    /**
     * 获取订单列表
     * @param array $where
     * @param array $with
     * @param false $is_count
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreOrderList(array $where, array $field = ['*'], array $with = [], $is_count = false)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getOrderList($where, $field, $page, $limit, $with, 'id desc');
        if ($is_count) {
            $count = $this->dao->count($where);
            return compact('list', 'count');
        }
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $list = $orderServices->tidyOrderList($list);
        foreach ($list as &$item) {
            $refund_num = array_sum(array_column($item['refund'], 'refund_num'));
            $cart_num = 0;
            foreach ($item['_info'] as $items) {
				if (isset($items['cart_info']['is_gift']) && $items['cart_info']['is_gift']) continue;
                $cart_num += $items['cart_info']['cart_num'];
            }
            $item['is_all_refund'] = $refund_num == $cart_num ? true : false;
        }
        return $list;
    }

    /**
     * 取消订单
     * @param $id
     * @param int $store_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cancelOrder($id, int $store_id = 0)
    {
        $where = ['id' => $id, 'is_del' => 0, 'store_id' => $store_id];

        $order = $this->dao->getOne($where);
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
        return true;
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
     * 门店首页头部统计
     * @param int $store_id
     * @param $time
     * @return array
     */
    public function homeStatics(int $store_id, $time)
    {
        $data = [];
        $where = ['time' => $time];
        if ($store_id) $where['store_id'] = $store_id;

        $order_where = ['paid' => 1, 'pid' => 0, 'is_system_del' => 0, 'refund_status' => [0, 3]];
        //门店营收
        $data['store_income'] = $this->dao->sum($order_where + $where, 'pay_price', true);
        //消耗余额
        $data['store_use_yue'] = $this->dao->sum(['pay_type' => 'yue'] + $order_where + $where, 'pay_price', true);
        //收银订单
        $data['cashier_order_price'] = $this->dao->sum(['shipping_type' => 4] + $order_where + $where, 'pay_price', true);
        //分配订单
        $data['store_order_price'] = $this->dao->sum(['shipping_type' => 3] + $order_where + $where, 'pay_price', true);
        //核销订单
        $data['store_writeoff_order_price'] = $this->dao->sum(['shipping_type' => 2] + $order_where + $where, 'pay_price', true);
        /** @var StoreUserServices $storeUserServices */
        $storeUserServices = app()->make(StoreUserServices::class);
        $data['store_user_count'] = $storeUserServices->count($where);
        //门店成交用户数
        $data['store_pay_user_count'] = count(array_unique($this->dao->getColumn($order_where + $where, 'uid', '', true)));
        /** @var OtherOrderServices $vipOrderServices */
        $vipOrderServices = app()->make(OtherOrderServices::class);
        $data['vip_price'] = $vipOrderServices->sum(['paid' => 1, 'type' => [0, 1, 2, 4]] + $where, 'pay_price', true);
        /** @var UserRechargeServices $userRecharge */
        $userRecharge = app()->make(UserRechargeServices::class);
        $data['recharge_price'] = $userRecharge->sum(['paid' => 1] + $where, 'price', true);
        /** @var UserCardServices $userCard */
        $userCard = app()->make(UserCardServices::class);
        $data['card_count'] = $userCard->count($where + ['is_submit' => 1]);
        return $data;
    }

    /**
     * 门店首页运营统计
     * @param int $store_id
     * @param array $time
     * @return array
     */
    public function operateChart(int $store_id, array $time)
    {
        [$start, $end, $timeType, $xAxis] = $time;
        $where = [];
        if ($store_id == -1) {
            $where[] = ['store_id', '>', 0];
        } else {
            $where['store_id'] = $store_id;
        }
        $order = $this->dao->orderAddTimeList($where, [$start, $end], $timeType);
        /** @var StoreUserServices $storeUserServices */
        $storeUserServices = app()->make(StoreUserServices::class);
        $storeUser = $storeUserServices->userTimeList($where, [$start, $end], $timeType);

        $order = array_column($order, 'price', 'day');
        $storeUser = array_column($storeUser, 'count', 'day');

        $data = $series = [];
        foreach ($xAxis as $key) {
            $data['门店收款'][] = isset($order[$key]) ? floatval($order[$key]) : 0;
            $data['新增用户数'][] = isset($storeUser[$key]) ? floatval($storeUser[$key]) : 0;
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
     * 首页交易统计
     * @param int $store_id
     * @param array $time
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderChart(int $store_id, array $time)
    {
        $chartdata = [];
        $where = ['time' => $time, 'pid' => 0];
        if ($store_id) $where['store_id'] = $store_id;

        $order_where = ['paid' => 1, 'pid' => 0, 'is_system_del' => 0, 'refund_status' => [0, 3]];

        $list = $this->dao->getOrderList($where + $order_where, ['id', 'order_id', 'uid', 'pay_price', 'pay_time'], 0, 10);
        $chartdata['order_list'] = $list;

        $chartdata['bing_xdata'] = ['收银订单', '充值订单', '分配订单', '核销订单', '付费会员订单'];
        $color = ['#2EC479', '#7F7AE5', '#FFA21B', '#46A3FF', '#FF6046'];
        //收银订单
        $pay[] = $this->dao->sum(['type' => 6] + $order_where + $where, 'pay_price', true);
        /** @var UserRechargeServices $userRecharge */
        $userRecharge = app()->make(UserRechargeServices::class);
        $pay[] = $userRecharge->sum(['paid' => 1] + $where, 'price', true);
        //分配订单
        $pay[] = $this->dao->sum(['type' => 7] + $order_where + $where, 'pay_price', true);
        //核销订单
        $pay[] = $this->dao->sum(['type' => 5] + $order_where + $where, 'pay_price', true);

        /** @var OtherOrderServices $vipOrderServices */
        $vipOrderServices = app()->make(OtherOrderServices::class);
        $pay[] = $vipOrderServices->sum(['paid' => 1, 'type' => [0, 1, 2, 4]] + $where, 'pay_price', true);
        foreach ($pay as $key => $item) {
            $bing_data[] = ['name' => $chartdata['bing_xdata'][$key], 'value' => $pay[$key], 'itemStyle' => ['color' => $color[$key]]];
        }
        $chartdata['bing_data'] = $bing_data;
        return $chartdata;
    }

}
