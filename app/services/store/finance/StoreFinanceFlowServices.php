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

namespace app\services\store\finance;


use app\dao\store\finance\StoreFinanceFlowDao;
use app\dao\store\StoreUserDao;
use app\services\BaseServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderServices;
use app\services\pay\PayServices;

/**
 * 门店流水
 * Class StoreExtractServices
 * @package app\services\store\finance
 */
class StoreFinanceFlowServices extends BaseServices
{
    /**
     * 支付类型
     * @var string[]
     */
    public $pay_type = ['weixin' => '微信支付', 'yue' => '余额支付', 'offline' => '线下支付', 'alipay' => '支付宝支付', 'cash' => '现金支付', 'automatic' => '自动转账', 'store' => '微信支付'];

    /**
     * 交易类型
     * @var string[]
     */
    public $type = [1 => '支付订单', 2 => '支付订单', 3 => '订单手续费', 4 => '退款订单', 5 => '充值返点', 6 => '付费会员返点', 7 => '充值订单', 8 => '付费订单', 9 => '收银订单', 10 => '核销订单', 11 => '分配订单'];

    /**
     * 构造方法
     * StoreUser constructor.
     * @param StoreUserDao $dao
     */
    public function __construct(StoreFinanceFlowDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 显示资源列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, '*', $page, $limit, ['user', 'systemStoreStaff']);
        foreach ($list as &$item) {
            $item['type_name'] = isset($this->type[$item['type']]) ? $this->type[$item['type']] : '其他类型';
            $item['pay_type_name'] = isset($this->pay_type[$item['pay_type']]) ? $this->pay_type[$item['pay_type']] : '其他方式';
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
            $item['trade_time'] = $item['trade_time'] ? date('Y-m-d H:i:s', $item['trade_time']) : $item['add_time'];
            $item['user_nickname'] = $item['user_nickname'] ?: '游客';
        }
        $count = $this->dao->getCount($where);
        return compact('list', 'count');
    }

    /**
     * 门店账单
     * @param $where
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFundRecord($where)
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_del'] = 0;
        $data = $this->dao->getFundRecord($where, $page, $limit);
        $i = 1;
        foreach ($data['list'] as &$item) {
            $item['id'] = $i;
            $i++;
            $item['entry_num'] = bcsub($item['income_num'], $item['exp_num'], 2);
            switch ($where['timeType']) {
                case "day" :
                    $item['title'] = "日账单";
                    $item['add_time'] = date('Y-m-d', $item['add_time']);
                    break;
                case "week" :
                    $item['title'] = "周账单";
                    $item['add_time'] = '第' . $item['day'] . '周(' . date('m', $item['add_time']) . '月)';
                    break;
                case "month" :
                    $item['title'] = "月账单";
                    $item['add_time'] = date('Y-m', $item['add_time']);
                    break;
            }
        }
        return $data;
    }

    /**
     * 店员交易统计头部数据
     * @param $where
     * @return mixed
     */
    public function getStatisticsHeader($where)
    {
        $data = [];
        $data['legend'] = '业绩统计';
        $color = ['#2EC479', '#7F7AE5', '#FFA21B', '#46A3FF', '#FF6046', '#5cadff', '#b37feb', '#19be6b', '#ff9900'];
        $data['series'] = [];
        $list = $this->dao->getStatisticsHeader($where, 'staff_id', 'pay_price');
        $lists = [];
        $i = 0;
        foreach ($list as $item) {
            $data['series'][$i] = $item['total_number'] ?? 0;
            $data['xAxis'][$i] = $item['staff_name'] ?? '';
            if ($i < 5) {
                $lists[$i] = $item;
            } else {
                $lists[5]['staff_name'] = '其他';
                $lists[5]['total_number'] = (float)bcadd((string)($lists[5]['total_number'] ?? 0), (string)$item['total_number'], 2);
            }
            $i++;
        }
        foreach ($lists as $key => &$item) {
            $data['bing_data'][$key]['itemStyle']['color'] = $color[$key];
            $data['bing_data'][$key]['name'] = $item['staff_name'] ?? '';
            $data['bing_data'][$key]['value'] = $item['total_number'] ?? 0;
            $data['bing_xdata'][$key] = $item['staff_name'] ?? '';
        }
        $data['yAxis']['maxnum'] = $data['series'] ? max($data['series']) : 0;
        return $data;
    }

    /**
     * 获取一段时间订单统计数量、金额
     * @param $where
     * @param $time
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTypeHeader($where, $time)
    {
        [$start, $end, $timeType, $xAxis] = $time;
        $order = $this->dao->orderAddTimeList($where, [$start, $end], $timeType, '*', 'pay_price');
        $price = array_column($order, 'price', 'day');
        $count = array_column($order, 'count', 'day');
        $datas = $series = [];
        foreach ($xAxis as $key) {
            $datas['销售业绩金额'][] = isset($price[$key]) ? floatval($price[$key]) : 0;
            $datas['销售业绩单数'][] = isset($count[$key]) ? floatval($count[$key]) : 0;
        }
        foreach ($datas as $key => $item) {
            $series[] = [
                'name' => $key,
                'data' => $item,
                'type' => 'line',
                'smooth' => 'true',
                'yAxisIndex' => 1,
            ];
        }
        $data['order']['xAxis'] = $xAxis;
        $data['order']['series'] = $series;

        $color = ['#2EC479', '#7F7AE5', '#FFA21B', '#46A3FF', '#FF6046', '#5cadff', '#b37feb', '#19be6b', '#ff9900'];
        $data['bing']['series'] = [];
        $list = $this->dao->getStatisticsHeader($where, 'type', 'pay_price');
        foreach ($list as $key => &$item) {
            $item['type_name'] = isset($this->type[$item['type']]) ? $this->type[$item['type']] : '其他类型';
            $data['bing']['bing_data'][$key]['itemStyle']['color'] = $color[$key];
            $data['bing']['bing_data'][$key]['name'] = $item['type_name'];
            $data['bing']['bing_data'][$key]['value'] = $item['total_number'];
            $data['bing']['bing_xdata'][$key] = $item['type_name'];
            $data['bing']['series'][$key] = $item['total_number'];
            $data['bing']['xAxis'][$key] = $item['type_name'];
        }
        $data['bing']['yAxis']['maxnum'] = $data['bing']['series'] ? max($data['bing']['series']) : 0;
        return $data;
    }

    /**
     * 获取百分比
     * @param $num
     * @return string|null
     */
    public function getPercent($num)
    {
        return bcdiv($num, '100', 4);
    }

    /**
     * 写入流水账单
     * @param $order
     * @param int $type
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setFinance($order, $type = 1, $price = 0)
    {
        /** @var StoreOrderServices $storeOrderServices */
        $storeOrderServices = app()->make(StoreOrderServices::class);
        switch ($type) {
            case 1 ://商品订单
                if ($order['store_id'] > 0) {
                    //门店订单
                    //2.1修改门店流水按照下单支付金额计算，
//                    $total_price = $storeOrderCartInfoServices->getCartInfoPrice($order['id'], $order['cart_id']);
                    $total_price = $order['pay_price'];
                    //商品总价+支付邮费
//                    $total_price = bcadd($total_price, $order['pay_postage'], 2);
                    $append = [
                        'pay_price' => $total_price,
                        'total_price' => $total_price,
                        'rate' => 1
                    ];
                    //支付订单
                    $this->savaData($order, $order['pay_price'], 1, 1, 1);
                    //现金支付增加
                    if ($order['pay_type'] == PayServices::CASH_PAY) {
                        //交易订单记录
                        $this->savaData($order, $total_price, 0, 2, 1, $append);
                    }
                    //门店订单
                    $this->savaData($order, $total_price, 1, 2, 1, $append);
                    if ($order['shipping_type'] == 2) {
                        //核销订单费率
                        $store_writeoff_order_rate = sys_config('store_writeoff_order_rate');
                        $total_price = bcmul($total_price, $this->getPercent($store_writeoff_order_rate), 2);
                        $append['rate'] = $store_writeoff_order_rate;
                        $type = 10;
                    } else if ($order['shipping_type'] == 4) {
                        //收银订单费率
                        $store_cashier_order_rate = sys_config('store_cashier_order_rate');
                        $total_price = bcmul($total_price, $this->getPercent($store_cashier_order_rate), 2);
                        $append['rate'] = $store_cashier_order_rate;
                        $type = 9;
                    } else {
                        //分配订单费率
                        $store_self_order_rate = sys_config('store_self_order_rate');
                        $total_price = bcmul($total_price, $this->getPercent($store_self_order_rate), 2);
                        $append['rate'] = $store_self_order_rate;
                        $type = 11;
                    }
                    //交易订单记录
                    $this->savaData($order, $total_price, 1, $type, 2, $append);
                    $this->savaData($order, $total_price, 0, 3, 1, $append);
                } else {
                    $orderList = $storeOrderServices->getSonOrder($order['id'], '*', 1);
                    if ($orderList) {
                        foreach ($orderList as $order) {
//                            $total_price = $storeOrderCartInfoServices->getCartInfoPrice($order['id'], $order['cart_id']);
                            $total_price = $order['pay_price'];
                            //商品总价+支付邮费
//                            $total_price = bcadd($total_price, $order['pay_postage']);
                            $append = [
                                'pay_price' => $total_price,
                                'total_price' => $total_price,
                                'rate' => 1
                            ];
                            //支付订单
                            $this->savaData($order, $order['pay_price'], 1, 1, 1);
                            //门店订单
                            $this->savaData($order, $total_price, 1, 2, 1, $append);
                            if ($order['shipping_type'] == 2) {
                                //核销订单费率
                                $store_writeoff_order_rate = sys_config('store_writeoff_order_rate');
                                $total_price = bcmul($total_price, $this->getPercent($store_writeoff_order_rate), 2);
                                $append['rate'] = $store_writeoff_order_rate;
                                $type = 10;
                            } else if ($order['shipping_type'] == 4) {
                                //收银订单费率
                                $store_cashier_order_rate = sys_config('store_cashier_order_rate');
                                $total_price = bcmul($total_price, $this->getPercent($store_cashier_order_rate), 2);
                                $append['rate'] = $store_cashier_order_rate;
                                $type = 9;
                            } else {
                                //分配订单费率
                                $store_self_order_rate = sys_config('store_self_order_rate');
                                $total_price = bcmul($total_price, $this->getPercent($store_self_order_rate), 2);
                                $append['rate'] = $store_self_order_rate;
                                $type = 11;
                            }
                            //交易订单记录
                            $this->savaData($order, $total_price, 1, $type, 2);
                            $this->savaData($order, $total_price, 0, 3, 1, $append);
                        }
                    }
                }
                break;
            case 2://充值订单
                //充值订单返点
                $store_recharge_order_rate = sys_config('store_recharge_order_rate');
                $order['pay_type'] = $order['recharge_type'];
                $append = [
                    'pay_price' => $order['price'],
                    'total_price' => $order['price'],
                    'rate' => $store_recharge_order_rate
                ];
                //订单账单
                $this->savaData($order, $order['price'], 1, 7, 2, $append);
                //返点
                $pay_price = bcmul($order['price'], $this->getPercent($store_recharge_order_rate), 2);
                $this->savaData($order, $pay_price, 1, 5, 1, $append);
                break;
            case 3://付费会员订单
                //购买付费会员返点
                $store_svip_order_rate = sys_config('store_svip_order_rate');
                $append = [
                    'pay_price' => $order['pay_price'],
                    'total_price' => $order['pay_price'],
                    'rate' => $store_svip_order_rate
                ];
                //订单账单
                $this->savaData($order, $order['pay_price'], 1, 8, 2, $append);
                //返点
                $pay_price = bcmul($order['pay_price'], $this->getPercent($store_svip_order_rate), 2);
                $this->savaData($order, $pay_price, 1, 6, 1, $append);
                break;
            case 4://退款
                if ($order['shipping_type'] == 2) {
                    //核销订单费率
                    $store_writeoff_order_rate = sys_config('store_writeoff_order_rate');
                    $total_price = bcmul($price, $this->getPercent($store_writeoff_order_rate), 2);
                    $append['rate'] = $store_writeoff_order_rate;
                } else if ($order['shipping_type'] == 4) {
                    //收银订单费率
                    $store_cashier_order_rate = sys_config('store_cashier_order_rate');
                    $total_price = bcmul($price, $this->getPercent($store_cashier_order_rate), 2);
                    $append['rate'] = $store_cashier_order_rate;
                } else {
                    //分配订单费率
                    $store_self_order_rate = sys_config('store_self_order_rate');
                    $total_price = bcmul($price, $this->getPercent($store_self_order_rate), 2);
                    $append['rate'] = $store_self_order_rate;
                }
                //退款
                $this->savaData($order, $price, 0, 4, 1, $append);
                $this->savaData($order, $total_price, 1, 3, 1, $append);
                break;
            case 5://充值退款
                $store_recharge_order_rate = sys_config('store_recharge_order_rate');
                $order['pay_type'] = $order['recharge_type'];
                $append = [
                    'pay_price' => $order['price'],
                    'total_price' => $order['price'],
                    'rate' => $store_recharge_order_rate
                ];
                //订单账单
                $this->savaData($order, $price, 0, 4, 2, $append);
                //返点扣除
                $pay_price = bcmul($price, $this->getPercent($store_recharge_order_rate), 2);
                $this->savaData($order, $pay_price, 0, 5, 1, $append);
                break;
        }
    }

    /**
     * 写入数据
     * @param $order
     * @param $number
     * @param $pm
     * @param $type
     * @param $trade_type
     * @param array $append
     * @throws \Exception
     */
    public function savaData($order, $number, $pm, $type, $trade_type, array $append = [])
    {
        /** @var StoreOrderCreateServices $storeOrderCreateServices */
        $storeOrderCreateServices = app()->make(StoreOrderCreateServices::class);
        $order_id = $storeOrderCreateServices->getNewOrderId('ls');
        $data = [
            'store_id' => $order['store_id'] ?? '',
            'uid' => $order['uid'] ?? 0,
            'staff_id' => $order['staff_id'] ?? 0,
            'order_id' => $order_id,
            'link_id' => $order['order_id'] ?? '',
            'pay_type' => $order['pay_type'] ?? '',
            'trade_time' => $order['pay_time'] ?? '',
            'pm' => $pm,
            'number' => $trade_type == 1 ? ($number ?: 0) : 0,
            'type' => $type,
            'trade_type' => $trade_type,
            'add_time' => time()
        ];
        $data = array_merge($data, $append);
        $this->dao->save($data);
    }

    /**
     * 关联门店店员
     * @param $link_id
     * @param int $staff_id
     * @return mixed
     */
    public function setStaff($link_id, int $staff_id)
    {
        return $this->dao->update(['link_id' => $link_id], ['staff_id' => $staff_id]);
    }

    /**
     * 可提现金额
     * @param array $where
     * @return int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSumFinance(array $where, array $whereData)
    {
        $field = 'sum(if(pm = 1,number,0)) as income_num,sum(if(pm = 0,number,0)) as exp_num';
        $data = $this->dao->getList($whereData, $field);
        if (!$data) return 0;
        $income_num = isset($data[0]['income_num']) ? $data[0]['income_num'] : 0;
        $exp_num = isset($data[0]['exp_num']) ? $data[0]['exp_num'] : 0;
        $number = bcsub($income_num, $exp_num, 2);
        //已提现金额
        /** @var StoreExtractServices $storeExtractServices */
        $storeExtractServices = app()->make(StoreExtractServices::class);
        $where['not_status'] = -1;
        $extract_price = $storeExtractServices->dao->getExtractMoneyByWhere($where, 'extract_price');
        $price_not = bcsub((string)$number, (string)$extract_price, 2);
        return $price_not;
    }
}
