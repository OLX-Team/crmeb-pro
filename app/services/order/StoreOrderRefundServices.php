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

use app\dao\order\StoreOrderRefundDao;
use app\jobs\notice\SmsAdminJob;
use app\jobs\template\RoutineTemplateJob;
use app\jobs\template\WechatTemplateJob;
use app\services\activity\discounts\StoreDiscountsServices;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\combination\StorePinkServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\BaseServices;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\message\service\StoreServiceServices;
use app\services\other\ExpressServices;
use app\services\pay\PayServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\product\StoreProductServices;
use app\services\order\StoreOrderPromotionsServices;
use app\services\store\SystemStoreServices;
use app\services\user\UserBillServices;
use app\services\user\UserBrokerageServices;
use app\services\user\UserMoneyServices;
use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use crmeb\services\AliPayService;
use crmeb\services\CacheService;
use crmeb\services\FormBuilder as Form;
use crmeb\services\wechat\Payment;
use think\exception\ValidateException;

/**
 * 订单退款
 * Class StoreOrderRefundServices
 * @package app\services\order
 * @method getUserRefundPriceList(array $time, string $timeType, string $str, string $field = 'add_time', array $with = []) 获取退款金额按照时间分组
 * @method count(array $where)  读取数据条数
 * @method update(array $where, array $data)  更新数据
 * @method get($id)  查询一条数据
 * @method getOne(array $where, ?string $field = '*', array $with = []) 根据条件获取一条数据
 */
class StoreOrderRefundServices extends BaseServices
{
    /**
     * 订单services
     * @var StoreOrderServices
     */
    protected $storeOrderServices;

    /**
     * 构造方法
     * StoreOrderRefundServices constructor.
     * @param StoreOrderRefundDao $dao
     * @param StoreOrderServices $storeOrderServices
     */
    public function __construct(StoreOrderRefundDao $dao, StoreOrderServices $storeOrderServices)
    {
        $this->dao = $dao;
        $this->storeOrderServices = $storeOrderServices;
    }

    /**
     * 退款订单列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function refundList(array $where)
    {
        $where['is_cancel'] = 0;
        $where['store_id'] = isset($where['store_id']) ? $where['store_id'] : 0;
        if (isset($where['time']) && $where['time'] != '') {
			$where['time'] = is_string($where['time']) ? explode('-', $where['time']) : $where['time'];
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getRefundList($where, '*', ['user'], $page, $limit);
        $count = $this->dao->count($where);
        if ($list) {
            foreach ($list as &$item) {
                $item['refund'] = [];
                $item['is_all_refund'] = 1;
                $item['paid'] = 1;
                $item['add_time'] = isset($item['add_time']) ? date('Y-m-d H:i', (int)$item['add_time']) : '';
                $item['cartInfo'] = $item['cart_info'];
                if (in_array($item['refund_type'], [1, 2, 4, 5])) {
                    $item['refund_status'] = 1;
                } elseif ($item['refund_type'] == 6) {
                    $item['refund_status'] = 2;
                } elseif ($item['refund_type'] == 3) {
                    $item['refund_status'] = 3;
                }
                foreach ($item['cart_info'] as $items) {
                    $item['_info'][]['cart_info'] = $items;
                }
                $item['total_num'] = $item['refund_num'];
                $item['pay_price'] = $item['refund_price'];
                $item['pay_postage'] = floatval($this->getOrderSumPrice($item['cart_info'], 'postage_price', false));
                $item['status_name'] = [
                    'pic' => [],
                    'status_name' => ''
                ];
                unset($item['cart_info']);
                if (in_array($item['refund_type'], [1, 2, 4, 5])) {
                    $_type = -1;
                    $_title = '申请退款中';
                    if ($item['refund_type'] == 1) {
                        $item['status_name']['status_name'] = '仅退款';
                    } elseif ($item['refund_type'] == 2) {
                        $item['status_name']['status_name'] = '退货退款';
                    } elseif ($item['refund_type'] == 4) {
                        $item['status_name']['status_name'] = '等待用户退货';
                    } elseif ($item['refund_type'] == 5) {
                        $item['status_name']['status_name'] = '商家待收货';
                    }
                } elseif ($item['refund_type'] == 3) {
                    $_type = -3;
                    $_title = '拒绝退款';
                    $item['status_name']['status_name'] = '拒绝退款';
                } else {
                    $_type = -2;
                    $_title = '已退款';
                    $item['status_name']['status_name'] = '已退款';
                }
                $item['_status'] = [
                    '_type' => $_type,
                    '_title' => $_title,
                ];
            }
        }
        $data['list'] = $list;
        $data['count'] = $count;
        $del_where = ['store_id' => $where['store_id'], 'is_cancel' => 0];
        $data['num'] = [
            0 => ['name' => '全部', 'num' => $this->dao->count($del_where)],
            1 => ['name' => '仅退款', 'num' => $this->dao->count($del_where + ['refund_type' => 1])],
            2 => ['name' => '退货退款', 'num' => $this->dao->count($del_where + ['refund_type' => 2])],
            3 => ['name' => '拒绝退款', 'num' => $this->dao->count($del_where + ['refund_type' => 3])],
            4 => ['name' => '商品待退货', 'num' => $this->dao->count($del_where + ['refund_type' => 4])],
            5 => ['name' => '退货待收货', 'num' => $this->dao->count($del_where + ['refund_type' => 5])],
            6 => ['name' => '已退款', 'num' => $this->dao->count($del_where + ['refund_type' => 6])]
        ];
        return $data;
    }

    /**
     * 前端订单列表
     * @param array $where
     * @param array|string[] $field
     * @param array $with
     * @return mixed
     */
    public function getRefundOrderList(array $where, string $field = '*', array $with = [])
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_cancel'] = 0;
        $where['is_del'] = 0;
        $data = $this->dao->getRefundList($where, $field, $with, $page, $limit);
        foreach ($data as &$item) {
            $item['add_time'] = isset($item['add_time']) ? date('Y-m-d H:i', (int)$item['add_time']) : '';
            $item['cartInfo'] = $item['cart_info'];
            unset($item['cart_info']);
        }
        return $data;
    }

    /**
     * 订单申请退款
     * @param int $id
     * @param int $uid
     * @param array $order
     * @param array $cart_ids
     * @param int $refundType
     * @param float $refundPrice
     * @param array $refundData
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function applyRefund(int $id, int $uid, $order = [], array $cart_ids = [], int $refundType = 0, float $refundPrice = 0.00, array $refundData = [], int $origin = 0, bool $isSync = true)
    {
        if (!$order) {
            $order = $this->storeOrderServices->get($id);
        }
        if (!$order) {
            throw new ValidateException('支付订单不存在!');
        }
        if (!sys_config('erp_open')) {
            $is_now = $this->dao->getCount([
                ['store_order_id', '=', $id],
                ['refund_type', 'in', [1, 2, 4, 5]],
                ['is_cancel', '=', 0],
                ['is_del', '=', 0]
            ]);
            if ($is_now) throw new ValidateException('退款处理中，请联系商家');
        }

        $refund_num = $order['total_num'];
        $refund_price = $order['pay_price'];
        /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
        $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        //退部分
        $cartInfo = [];
        $cartInfos = $storeOrderCartInfoServices->getCartColunm(['oid' => $id], 'id,cart_id,product_type,is_support_refund,cart_num,refund_num,cart_info');
        if ($cart_ids) {
            $cartInfo = array_combine(array_column($cartInfos, 'cart_id'), $cartInfos);
            $refund_num = 0;
            foreach ($cart_ids as $cart) {
                if (!isset($cartInfo[$cart['cart_id']])) throw new ValidateException('该订单中商品不存在，请重新选择!');
                if (!$cartInfo[$cart['cart_id']]['is_support_refund'] && $origin == 0) {
                    throw new ValidateException('该订单中有商品不支持退款，请联系管理员');
                }
                if ($cart['cart_num'] + $cartInfo[$cart['cart_id']]['refund_num'] > $cartInfo[$cart['cart_id']]['cart_num']) {
                    throw new ValidateException('超出订单中商品数量，请重新选择!');
                }
                $refund_num = bcadd((string)$refund_num, (string)$cart['cart_num'], 0);
            }
            //总共申请多少件
            $total_num = array_sum(array_column($cart_ids, 'cart_num'));
            if ($total_num < $order['total_num']) {
                $total_price = 0;
                foreach ($cartInfos as $cart) {
                    $_info = is_string($cart['cart_info']) ? json_decode($cart['cart_info'], true) : $cart['cart_info'];
                    $total_price = bcadd((string)$total_price, bcmul((string)($_info['truePrice'] ?? 0), (string)$cart['cart_num'], 4), 2);
                }
                //订单实际支付金额
                $order_pay_price = bcadd((string)$total_price, (string)$order['pay_postage'], 2);

                /** @var StoreOrderSplitServices $storeOrderSpliteServices */
                $storeOrderSpliteServices = app()->make(StoreOrderSplitServices::class);
                $cartInfos = $storeOrderSpliteServices->getSplitOrderCartInfo($id, $cart_ids, $order);
                $total_price = $pay_postage = 0;
                foreach ($cartInfos as $cart) {
                    $_info = is_string($cart['cart_info']) ? json_decode($cart['cart_info'], true) : $cart['cart_info'];
                    $total_price = bcadd((string)$total_price, bcmul((string)($_info['truePrice'] ?? 0), (string)$cart['cart_num'], 4), 2);
                    $pay_postage = bcadd((string)$pay_postage, (string)($_info['postage_price'] ?? 0), 2);
                }
                //实际退款金额
                $refund_pay_price = bcadd((string)$total_price, (string)$pay_postage, 2);

                if ($order_pay_price != $order['pay_price']) {//有改价 且是拆分
                    $refund_price = bcmul((string)bcdiv((string)$order['pay_price'], (string)$order_pay_price, 4), (string)$refund_pay_price, 2);
                } else {
                    $refund_price = $refund_pay_price;
                }
            }
        } else {//整单退款
            foreach ($cartInfos as $cart) {
                if (!$cart['is_support_refund']) {
                    throw new ValidateException('该订单中有商品不支持退款，请联系管理员');
                }
                if ($cart['refund_num'] > 0) {
                    throw new ValidateException('超出订单中商品数量，请重新选择!');
                }
            }
        }
        foreach ($cartInfos as &$cart) {
            $cart['cart_info'] = is_string($cart['cart_info']) ? json_decode($cart['cart_info'], true) : $cart['cart_info'];
        }
        $refundData['uid'] = $uid;
        $refundData['store_id'] = $order['store_id'];
        $refundData['store_order_id'] = $id;
        $refundData['refund_num'] = $refund_num;
        $refundData['refund_type'] = $refundType;
        $refundData['refund_price'] = $refund_price;
        $refundData['order_id'] = app()->make(StoreOrderCreateServices::class)->getNewOrderId('');
        $refundData['add_time'] = time();
        $refundData['cart_info'] = json_encode(array_column($cartInfos, 'cart_info'));
        $refundId = $this->transaction(function () use ($id, $order, $cart_ids, $refundData, $storeOrderCartInfoServices, $cartInfo, $cartInfos) {
            /** @var StoreOrderStatusServices $statusService */
            $statusService = app()->make(StoreOrderStatusServices::class);
            $res1 = false !== $statusService->save([
                    'oid' => $order['id'],
                    'change_type' => 'apply_refund',
                    'change_message' => '用户申请退款，原因：' . $refundData['refund_reason'],
                    'change_time' => time()
                ]);
            $res2 = true;
            //添加退款数据
            /** @var StoreOrderRefundServices $storeOrderRefundServices */
            $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);
            $res3 = $storeOrderRefundServices->save($refundData);
            if (!$res3) {
                throw new ValidateException('添加退款申请失败');
            }
            $res4 = true;
            if ($cart_ids) {
                //修改订单商品退款信息
                foreach ($cart_ids as $cart) {
                    $res4 = $res4 && $storeOrderCartInfoServices->update(['oid' => $id, 'cart_id' => $cart['cart_id']], ['refund_num' => (($cartInfo[$cart['cart_id']]['refund_num'] ?? 0) + $cart['cart_num'])]);
                }
            } else {//整单退款
                //修改原订单状态
//                $res2 = false !== $this->storeOrderServices->update(['id' => $order['id']], ['refund_status' => 1]);
                foreach ($cartInfos as $cart) {
                    $res4 = $res4 && $storeOrderCartInfoServices->update(['oid' => $id, 'cart_id' => $cart['cart_id']], ['refund_num' => $cart['cart_num']]);
                }
            }
            if ($res1 && $res2 && $res3 && $res4) {
                return (int)$res3->id;
            } else {
                return false;
            }
        });
        $storeOrderCartInfoServices->clearOrderCartInfo($order['id']);
        //申请退款事件
        event('order.applyRefund', [$order, $refundId, $isSync]);
        return $refundId;
    }

    /**
     * 拒绝退款
     * @param int $id
     * @param array $data
     * @param array $orderRefundInfo
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function refuseRefund(int $id, array $data, $orderRefundInfo = [])
    {
        if (!$orderRefundInfo) {
            $orderRefundInfo = $this->dao->get(['id' => $id, 'is_cancel' => 0]);
        }
        if (!$orderRefundInfo) {
            throw new ValidateException('售后订单不存在');
        }
        $this->transaction(function () use ($id, $orderRefundInfo, $data) {
            //处理售后订单
            if (isset($data['refund_price'])) unset($data['refund_price']);
            $this->dao->update($id, $data);
            //处理订单
            $oid = (int)$orderRefundInfo['store_order_id'];
            $this->storeOrderServices->update($oid, ['refund_status' => 0, 'refund_type' => 3]);
            //处理订单商品cart_info
            $this->cancelOrderRefundCartInfo($id, $oid, $orderRefundInfo);
            //记录
            /** @var StoreOrderStatusServices $statusService */
            $statusService = app()->make(StoreOrderStatusServices::class);
            $statusService->save([
                'oid' => $id,
                'change_type' => 'refund_n',
                'change_message' => '不退款原因:' . ($data['refund_reason'] ?? ''),
                'change_time' => time()
            ]);
        });
        $orderInfo = $this->storeOrderServices->get((int)$orderRefundInfo['store_order_id']);
        //订单拒绝退款事件
        event('order.refuseRefund', [$orderInfo]);
        return true;
    }

    /**
     * 取消申请、后台拒绝处理cart_info refund_num数据
     * @param int $id
     * @param int $oid
     * @param array $orderRefundInfo
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cancelOrderRefundCartInfo(int $id, int $oid, $orderRefundInfo = [])
    {
        if (!$orderRefundInfo) {
            $orderRefundInfo = $this->dao->get(['id' => $id, 'is_cancel' => 0]);
        }
        if (!$orderRefundInfo) {
            throw new ValidateException('售后订单不存在');
        }
        $cart_ids = array_column($orderRefundInfo['cart_info'], 'id');
        /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
        $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $cartInfos = $storeOrderCartInfoServices->getColumn([['oid', '=', $oid], ['cart_id', 'in', $cart_ids]], 'cart_id,refund_num', 'cart_id');
        foreach ($orderRefundInfo['cart_info'] as $cart) {
            $cart_refund_num = $cartInfos[$cart['id']]['refund_num'] ?? 0;
            if ($cart['cart_num'] >= $cart_refund_num) {
                $refund_num = 0;
            } else {
                $refund_num = bcsub((string)$cart_refund_num, (string)$cart['cart_num'], 0);
            }
            $storeOrderCartInfoServices->update(['oid' => $oid, 'cart_id' => $cart['id']], ['refund_num' => $refund_num]);
        }
        $storeOrderCartInfoServices->clearOrderCartInfo($oid);
        return true;
    }

    /**
     * 商家同意退货退款，等待客户退货
     * @param int $id
     * @return bool
     */
    public function agreeRefundProdcut(int $id)
    {
        $res = $this->dao->update(['id' => $id], ['refund_type' => 4]);
        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        $statusService->save([
            'oid' => $id,
            'change_type' => 'refund_express',
            'change_message' => '等待用户退货',
            'change_time' => time()
        ]);
        if ($res) return true;
        throw new ValidateException('操作失败');
    }

    /**
     * 同意退款：拆分退款单、退积分、佣金等
     * @param int $id
     * @param array $refundData
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function agreeRefund(int $id, array $refundData)
    {
        $order = $this->transaction(function () use ($id, $refundData) {
            //退款拆分
            $order = $this->agreeSplitRefundOrder($id);
            //回退积分和优惠卷
            if (!$this->integralAndCouponBack($order)) {
                throw new ValidateException('回退积分和优惠卷失败');
            }
            //退拼团
            if ($order['pid'] == 0 && $order['type'] == 3) {
                /** @var StorePinkServices $pinkServices */
                $pinkServices = app()->make(StorePinkServices::class);
                if (!$pinkServices->setRefundPink($order)) {
                    throw new ValidateException('拼团修改失败!');
                }
            }
            //退佣金
            /** @var UserBrokerageServices $userBrokerageServices */
            $userBrokerageServices = app()->make(UserBrokerageServices::class);
            if (!$userBrokerageServices->orderRefundBrokerageBack($order)) {
                throw new ValidateException('回退佣金失败');
            }
            //回退库存
            if ($order['status'] == 0) {
                /** @var StoreOrderStatusServices $services */
                $services = app()->make(StoreOrderStatusServices::class);
                if (!$services->count(['oid' => $order['id'], 'change_type' => 'refund_price'])) {
                    $this->regressionStock($order);
                }
            }
            //退金额
            if ($refundData['refund_price'] > 0) {
                if (!isset($refundData['refund_id']) || !$refundData['refund_id']) {
                    mt_srand();
                    $refundData['refund_id'] = $order['order_id'] . rand(100, 999);
                }
                if ($order['pid'] > 0) {//子订单
                    $refundOrder = $this->storeOrderServices->get((int)$order['pid']);
                    $refundData['pay_price'] = $refundOrder['pay_price'];
                } else {
                    $refundOrder = $order;
                }
                switch ($refundOrder['pay_type']) {
                    case PayServices::WEIXIN_PAY:
                        $no = $refundOrder['order_id'];
                        if ($refundOrder['trade_no'] && $refundOrder['trade_no'] != $refundOrder['order_id']) {
                            $no = $refundOrder['trade_no'];
                            $refundData['type'] = 'trade_no';
                        }
                        if ($refundOrder['is_channel'] == 1) {
                            //小程序退款
                            Payment::instance()->setAccessEnd(Payment::MINI)->payOrderRefund($no, $refundData);//小程序
                        } else {
                            //微信公众号退款
                            Payment::instance()->setAccessEnd(Payment::WEB)->payOrderRefund($no, $refundData);//公众号
                        }
                        break;
                    case PayServices::YUE_PAY:
                        //余额退款
                        if (!$this->yueRefund($refundOrder, $refundData)) {
                            throw new ValidateException('余额退款失败');
                        }
                        break;
                    case PayServices::ALIAPY_PAY:
                        mt_srand();
                        $refund_id = $refundData['refund_id'] ?? $refundOrder['order_id'] . rand(100, 999);
                        //支付宝退款
                        AliPayService::instance()->refund(strpos($refundOrder['trade_no'], '_') !== false ? $refundOrder['trade_no'] : $refundOrder['order_id'], floatval($refundData['refund_price']), $refund_id);
                        break;
                }
            }
            //订单记录
            /** @var StoreOrderStatusServices $statusService */
            $statusService = app()->make(StoreOrderStatusServices::class);
            $statusService->save([
                'oid' => $order['id'],
                'change_type' => 'refund_price',
                'change_message' => '退款给用户：' . $refundData['refund_price'] . '元',
                'change_time' => time()
            ]);
            return $order;
        });
        //订单同意退款事件
        event('order.refund', [$refundData, $order, 'order_refund']);
        return true;
    }

    /**
     * 处理退款 拆分订单
     * @param int $id
     * @param array $orderRefundInfo
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function agreeSplitRefundOrder(int $id, $orderRefundInfo = [])
    {
        if (!$orderRefundInfo) {
            $orderRefundInfo = $this->dao->get($id);
        }
        if (!$orderRefundInfo) {
            throw new ValidateException('数据不存在');
        }
        $cart_ids = [];
        if ($orderRefundInfo['cart_info']) {
            foreach ($orderRefundInfo['cart_info'] as $cart) {
                $cart_ids[] = [
                    'cart_id' => $cart['id'],
                    'cart_num' => $cart['cart_num'],
                ];
            }
        }
        return $this->transaction(function () use ($orderRefundInfo, $cart_ids) {
            /** @var StoreOrderSplitServices $storeOrderSplitServices */
            $storeOrderSplitServices = app()->make(StoreOrderSplitServices::class);
            $oid = (int)$orderRefundInfo['store_order_id'];
            $splitResult = $storeOrderSplitServices->equalSplit($oid, $cart_ids, [], 0, true);
            $orderInfo = [];
            if ($splitResult) {//拆分发货
                [$orderInfo, $otherOrder] = $splitResult;
            }
            if ($orderInfo) {
                /** @var StoreOrderServices $storeOrderServices */
                $storeOrderServices = app()->make(StoreOrderServices::class);
                //原订单退款状态清空
                $storeOrderServices->update($oid, ['refund_status' => 0, 'refund_type' => 0]);
                //修改新生成拆分退款订单状态
                $storeOrderServices->update($orderInfo['id'], ['refund_status' => 2, 'refund_type' => 6]);
                //修改售后订单 关联退款订单
                $this->dao->update($orderRefundInfo['id'], ['store_order_id' => $orderInfo['id']]);
                if ($oid != $otherOrder['id']) {//拆分生成新订单了
                    //修改原订单还在申请的退款单
                    $this->dao->update(['store_order_id' => $oid], ['store_order_id' => $otherOrder['id']]);
                }
            } else {//整单退款
                /** @var StoreOrderServices $storeOrderServices */
                $storeOrderServices = app()->make(StoreOrderServices::class);
                $storeOrderServices->update($oid, ['refund_status' => 2, 'refund_type' => 6]);
                //修改订单商品申请退款数量
                /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
                $storeOrderCartInfoServices = app()->make(StoreOrderCreateServices::class);
                $storeOrderCartInfoServices->update(['oid' => $oid], ['refund_num' => 0]);
                $orderInfo = $storeOrderServices->get($oid);
            }
            return $orderInfo;
        });
    }

    /**
     * 订单退款表单
     * @param int $id
     * @param string $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function refundOrderForm(int $id, string $type = 'refund')
    {
        if ($type == 'refund') {//售后订单
            $orderRefund = $this->dao->get($id);
            if (!$orderRefund) {
                throw new ValidateException('未查到订单');
            }
            $order = $this->storeOrderServices->get((int)$orderRefund['store_order_id']);
            if (!$order) {
                throw new ValidateException('未查到订单');
            }
            if (!$order['paid']) {
                throw new ValidateException('未支付无法退款');
            }
            if ($orderRefund['refund_price'] > 0 && in_array($orderRefund['refund_type'], [1, 5])) {
                if ($orderRefund['refund_price'] <= $orderRefund['refunded_price']) {
                    throw new ValidateException('订单已退款');
                }
            }
            $f[] = Form::input('order_id', '退款单号', $orderRefund->getData('order_id'))->disabled(true);
            $f[] = Form::number('refund_price', '退款金额', (float)bcsub((string)$orderRefund->getData('refund_price'), (string)$orderRefund->getData('refunded_price'), 2))->precision(2)->min(0)->required('请输入退款金额');
            return create_form('退款处理', $f, $this->url('/refund/refund/' . $id), 'PUT');
        } else {//订单主动退款
            $order = $this->storeOrderServices->get((int)$id);
            if (!$order) {
                throw new ValidateException('未查到订单');
            }
            if (!$order['paid']) {
                throw new ValidateException('未支付无法退款');
            }
            if ($order['pay_price'] > 0 && in_array($order['refund_status'], [0, 1])) {
                if ($order['pay_price'] <= $order['refund_price']) {
                    throw new ValidateException('订单已退款');
                }
            }
            $f[] = Form::input('order_id', '退款单号', $order->getData('order_id'))->disabled(true);
            $f[] = Form::number('refund_price', '退款金额', (float)bcsub((string)$order->getData('pay_price'), (string)$order->getData('refund_price'), 2))->precision(2)->required('请输入退款金额');
            return create_form('退款处理', $f, $this->url('/order/refund/' . $id), 'PUT');
        }
    }

    /**
     * 订单退款处理
     * @param int $type
     * @param $order
     * @param array $refundData
     * @return mixed
     */
    public function payOrderRefund(int $type, $order, array $refundData)
    {
        return $this->transaction(function () use ($type, $order, $refundData) {

            //回退积分和优惠卷
            if (!$this->integralAndCouponBack($order)) {
                throw new ValidateException('回退积分和优惠卷失败');
            }
            //退拼团
            if ($type == 1) {
                /** @var StorePinkServices $pinkServices */
                $pinkServices = app()->make(StorePinkServices::class);
                if (!$pinkServices->setRefundPink($order)) {
                    throw new ValidateException('拼团修改失败!');
                }
            }
            //退佣金
            /** @var UserBrokerageServices $userBrokerageServices */
            $userBrokerageServices = app()->make(UserBrokerageServices::class);
            if (!$userBrokerageServices->orderRefundBrokerageBack($order)) {
                throw new ValidateException('回退佣金失败');
            }
            //回退库存
            if ($order['status'] == 0) {
                /** @var StoreOrderStatusServices $services */
                $services = app()->make(StoreOrderStatusServices::class);
                if (!$services->count(['oid' => $order['id'], 'change_type' => 'refund_price'])) {
                    $this->regressionStock($order);
                }
            }
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $usermoney = $userServices->value(['uid' => $order['uid']], 'now_money');
            //退金额
            if ($refundData['refund_price'] > 0) {
                if (!isset($refundData['refund_id']) || !$refundData['refund_id']) {
                    mt_srand();
                    $refundData['refund_id'] = $order['order_id'] . rand(100, 999);
                }
                if ($order['pid'] > 0) {//子订单
                    $refundOrder = $this->storeOrderServices->get((int)$order['pid']);
                    $refundData['pay_price'] = $refundOrder['pay_price'];
                } else {
                    $refundOrder = $order;
                }
                switch ($refundOrder['pay_type']) {
                    case PayServices::WEIXIN_PAY:
                        $no = $refundOrder['order_id'];
                        if ($refundOrder['trade_no']) {
                            $no = $refundOrder['trade_no'];
                            $refundData['type'] = 'trade_no';
                        }
                        if ($refundOrder['is_channel'] == 1) {
                            //小程序退款
                            Payment::instance()->setAccessEnd(Payment::MINI)->payOrderRefund($no, $refundData);//小程序
                        } else {
                            //微信公众号退款
                            Payment::instance()->setAccessEnd(Payment::WEB)->payOrderRefund($no, $refundData);//公众号
                        }
                        break;
                    case PayServices::YUE_PAY:
                        //余额退款
                        if (!$this->yueRefund($refundOrder, $refundData)) {
                            throw new ValidateException('余额退款失败');
                        }
                        break;
                    case PayServices::ALIAPY_PAY:
                        mt_srand();
                        $refund_id = $refundData['refund_id'] ?? $refundOrder['order_id'] . rand(100, 999);
                        //支付宝退款
                        AliPayService::instance()->refund(strpos($refundOrder['trade_no'], '_') !== false ? $refundOrder['trade_no'] : $refundOrder['order_id'], floatval($refundData['refund_price']), $refund_id);
                        break;
                }
            }
        });
    }

    /**
     * 余额退款
     * @param $order
     * @param array $refundData
     * @return bool
     */
    public function yueRefund($order, array $refundData)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $usermoney = $userServices->value(['uid' => $order['uid']], 'now_money');
        $res = $userServices->bcInc($order['uid'], 'now_money', $refundData['refund_price'], 'uid');
        /** @var UserMoneyServices $userMoneyServices */
        $userMoneyServices = app()->make(UserMoneyServices::class);
        return $res && $userMoneyServices->income('pay_product_refund', $order['uid'], $refundData['refund_price'], bcadd((string)$usermoney, (string)$refundData['refund_price'], 2), $order['id']);
    }

    /**
     * 回退积分和优惠卷
     * @param $order
     * @return bool
     */
    public function integralAndCouponBack($order)
    {
        $res = true;
        //回退优惠卷 拆分子订单不退优惠券
        if (!$order['pid'] && $order['coupon_id'] && $order['coupon_price']) {
            /** @var StoreCouponUserServices $coumonUserServices */
            $coumonUserServices = app()->make(StoreCouponUserServices::class);
            $res = $res && $coumonUserServices->recoverCoupon((int)$order['coupon_id']);
        }
        //回退积分
        $order = $this->regressionIntegral($order);
        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        $statusService->save([
            'oid' => $order['id'],
            'change_type' => 'integral_back',
            'change_message' => '商品退积分',
            'change_time' => time()
        ]);
        return $res && $order->save();
    }

    /**
     * 回退使用积分和赠送积分
     * @param $order
     * @return bool
     */
    public function regressionIntegral($order)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->get($order['uid'], ['integral']);
        if (!$userInfo) {
            $order->back_integral = $order->use_integral;
            return $order;
        }
        $integral = $userInfo['integral'];
        if ($order['status'] == -2 || $order['is_del']) {
            return $order;
        }
        $res1 = $res2 = $res3 = $res4 = true;
        //订单赠送积分
        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
        $where = [
            'uid' => $order['uid'],
            'category' => 'integral',
            'type' => 'gain',
            'link_id' => $order['id']
        ];
        $give_integral = $userBillServices->sum($where, 'number');
        if ((int)$order['refund_status'] != 2 && $order['back_integral'] >= $order['use_integral']) {
            return $order;
        }
        //子订单退款 再次查询主订单
        if (!$give_integral && $order['pid']) {
            $where['link_id'] = $order['pid'];
            $give_integral = $userBillServices->sum($where, 'number');
            if ($give_integral) {
                $p_order = $this->storeOrderServices->get($order['pid']);
                $give_integral = bcsub((string)$give_integral, (string)bcdiv((string)$order['pay_price'], (string)$p_order['pay_price'], 4), 0);
            }
        }
        if ($give_integral) {
            //判断订单是否已经回退积分
            $count = $userBillServices->count(['category' => 'integral', 'type' => 'deduction', 'link_id' => $order['id']]);
            if (!$count) {
                $res1 = $userServices->bcDec($order['uid'], 'integral', $give_integral, null, 0, false);
                //记录赠送积分收回
                $res2 = $userBillServices->income('integral_refund', $order['uid'], $give_integral, $integral, $order['id']);
            }
        }
        //返还下单使用积分
        $use_integral = $order['use_integral'];
        if ($use_integral > 0) {
            $res3 = $userServices->bcInc($order['uid'], 'integral', $use_integral, null, 0);
            //记录下单使用积分还回
            $res4 = $userBillServices->income('pay_product_integral_back', $order['uid'], $use_integral, $integral, $order['id']);
        }
        if (!($res1 && $res2 && $res3 && $res4)) {
            throw new ValidateException('回退积分增加失败');
        }
        if ($use_integral > $give_integral) {
            $order->back_integral = bcsub($use_integral, $give_integral, 2);
        }
        return $order;
    }

    /**
     * 回退库存
     * @param $order
     * @return bool
     */
    public function regressionStock($order)
    {
        if ($order['status'] == -2 || $order['is_del']) return true;
        $res5 = true;
        /** @var StoreOrderCartInfoServices $cartServices */
        $cartServices = app()->make(StoreOrderCartInfoServices::class);
        /** @var StoreProductServices $services */
        $services = app()->make(StoreProductServices::class);
        /** @var StoreSeckillServices $seckillServices */
        $seckillServices = app()->make(StoreSeckillServices::class);
        /** @var StoreCombinationServices $pinkServices */
        $pinkServices = app()->make(StoreCombinationServices::class);
        /** @var StoreBargainServices $bargainServices */
        $bargainServices = app()->make(StoreBargainServices::class);
        /** @var StoreDiscountsServices $discountServices */
        $discountServices = app()->make(StoreDiscountsServices::class);
        /** @var StoreBranchProductServices $storeBranchService */
        $storeBranchService = app()->make(StoreBranchProductServices::class);
        $activity_id = (int)$order['activity_id'];
        $store_id = (int)$order['store_id'] ?? 0;
        $cartInfo = $cartServices->getCartInfoList(['cart_id' => $order['cart_id']], ['cart_info']);
        foreach ($cartInfo as $cart) {
            $cart['cart_info'] = is_array($cart['cart_info']) ? $cart['cart_info'] : json_decode($cart['cart_info'], true);
            //增库存减销量
            $unique = isset($cart['cart_info']['productInfo']['attrInfo']) ? $cart['cart_info']['productInfo']['attrInfo']['unique'] : '';
            $cart_num = (int)$cart['cart_info']['cart_num'];
            $product_id = (int)$cart['cart_info']['productInfo']['id'];
            switch ($order['type']) {
                case 0://普通
                case 6://预售
                    if ($store_id) {
                        $res5 = $res5 && $storeBranchService->updataDecStock($cart_num, $product_id, $store_id, $unique, false);
                    } else {
                        $res5 = $res5 && $services->incProductStock($cart_num, $product_id, $unique);
                    }
                    break;
                case 1://秒杀
                    $res5 = $res5 && $seckillServices->incSeckillStock($cart_num, $activity_id, $unique, $store_id);
                    break;
                case 2://砍价
                    $res5 = $res5 && $bargainServices->incBargainStock($cart_num, $activity_id, $unique, $store_id);
                    break;
                case 3://拼团
                    $res5 = $res5 && $pinkServices->incCombinationStock($cart_num, $activity_id, $unique, $store_id);
                    break;
                case 5://套餐
                    CacheService::setStock(md5($activity_id), 1, 5, false);
                    $res5 = $res5 && $discountServices->incDiscountStock($cart_num, $activity_id, (int)($cart['cart_info']['discount_product_id'] ?? 0), (int)($cart['cart_info']['product_id'] ?? 0), $unique, $store_id);
                    break;
                default:
                    if ($store_id) {
                        $res5 = $res5 && $storeBranchService->updataDecStock($cart_num, $product_id, $store_id, $unique, false);
                    } else {
                        $res5 = $res5 && $services->incProductStock($cart_num, $product_id, $unique);
                    }
                    break;
            }
            if (in_array($order['type'], [1, 2, 3])) CacheService::setStock($unique, $cart_num, (int)$order['type'], false);
        }
        if ($order['type'] == 5) {
            //改变套餐限量
            $res5 = $res5 && $discountServices->changeDiscountLimit($activity_id, false);
        }
        return $res5;
    }

    /**
     * 同意退款退款失败写入订单记录
     * @param int $id
     * @param $refund_price
     */
    public function storeProductOrderRefundYFasle(int $id, $refund_price)
    {
        /** @var StoreOrderStatusServices $statusService */
        $statusService = app()->make(StoreOrderStatusServices::class);
        $statusService->save([
            'oid' => $id,
            'change_type' => 'refund_price',
            'change_message' => '退款给用户：' . $refund_price . '元失败',
            'change_time' => time()
        ]);
    }

    /**
     * 不退款表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function noRefundForm(int $id)
    {
        $orderRefund = $this->dao->get($id);
        if (!$orderRefund) {
            throw new ValidateException('未查到订单');
        }
        $order = $this->storeOrderServices->get((int)$orderRefund['store_order_id']);
        if (!$order) {
            throw new ValidateException('未查到订单');
        }
        $f[] = Form::input('order_id', '不退款单号', $order->getData('order_id'))->disabled(true);
        $f[] = Form::input('refund_reason', '不退款原因')->type('textarea')->required('请填写不退款原因');
        return create_form('不退款原因', $f, $this->url('order/no_refund/' . $id), 'PUT');
    }

    /**
     * 退积分表单创建
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function refundIntegralForm(int $id)
    {
        if (!$orderInfo = $this->storeOrderServices->get($id))
            throw new ValidateException('订单不存在');
        if ($orderInfo->use_integral < 0 || $orderInfo->use_integral == $orderInfo->back_integral)
            throw new ValidateException('积分已退或者积分为零无法再退');
        if (!$orderInfo->paid)
            throw new ValidateException('未支付无法退积分');
        $f[] = Form::input('order_id', '退款单号', $orderInfo->getData('order_id'))->disabled(1);
        $f[] = Form::number('use_integral', '使用的积分', (float)$orderInfo->getData('use_integral'))->min(0)->disabled(1);
        $f[] = Form::number('use_integrals', '已退积分', (float)$orderInfo->getData('back_integral'))->min(0)->disabled(1);
        $f[] = Form::number('back_integral', '可退积分', (float)bcsub($orderInfo->getData('use_integral'), $orderInfo->getData('back_integral')))->min(0)->precision(0)->required('请输入可退积分');
        return create_form('退积分', $f, $this->url('/order/refund_integral/' . $id), 'PUT');
    }

    /**
     * 单独退积分处理
     * @param $orderInfo
     * @param $back_integral
     */
    public function refundIntegral($orderInfo, $back_integral)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $integral = $userServices->value(['uid' => $orderInfo['uid']], 'integral');
        return $this->transaction(function () use ($userServices, $orderInfo, $back_integral, $integral) {
            $res1 = $userServices->bcInc($orderInfo['uid'], 'integral', $back_integral, 'uid');
            /** @var UserBillServices $userBillServices */
            $userBillServices = app()->make(UserBillServices::class);
            $res2 = $userBillServices->income('pay_product_integral_back', $orderInfo['uid'], $back_integral, $integral + $back_integral, $orderInfo['id']);
            /** @var StoreOrderStatusServices $statusService */
            $statusService = app()->make(StoreOrderStatusServices::class);
            $res3 = $statusService->save([
                'oid' => $orderInfo['id'],
                'change_type' => 'integral_back',
                'change_message' => '商品退积分:' . $back_integral,
                'change_time' => time()
            ]);
            $res4 = $orderInfo->save();
            $res = $res1 && $res2 && $res3 && $res4;
            if (!$res) {
                throw new ValidateException('订单退积分失败');
            }
            return true;
        });
    }

    /**
     * 用户发起退款管理员短信提醒
     * 用户退款中模板消息
     * @param string $order_id
     */
    public function sendAdminRefund($order)
    {
        $switch = sys_config('admin_refund_switch') ? true : false;
        /** @var StoreServiceServices $services */
        $services = app()->make(StoreServiceServices::class);
        $adminList = $services->getStoreServiceOrderNotice();
        SmsAdminJob::dispatchDo('sendAdminRefund', [$switch, $adminList, $order]);
        /** @var WechatUserServices $wechatServices */
        $wechatServices = app()->make(WechatUserServices::class);
        if ($order['is_channel'] == 1) {
            //小程序
            $openid = $wechatServices->uidToOpenid($order['uid'], 'routine');
            if ($openid) RoutineTemplateJob::dispatchDo('sendOrderRefundStatus', [$openid, $order]);
        } else {
            $openid = $wechatServices->uidToOpenid($order['uid'], 'wechat');
            if ($openid) WechatTemplateJob::dispatchDo('sendOrderApplyRefund', [$openid, $order]);
        }
        return true;
    }

    /**
     * 写入退款快递单号
     * @param $order
     * @param $express
     * @return bool
     */
    public function editRefundExpress($data)
    {
        $id = (int)$data['id'];
        $refundOrder = $this->dao->get($id);
        if (!$refundOrder) {
            throw new ValidateException('退款订单不存在');
        }
        $this->transaction(function () use ($id, $refundOrder, $data) {
            $data['refund_type'] = 5;
            /** @var StoreOrderStatusServices $statusService */
            $statusService = app()->make(StoreOrderStatusServices::class);
            $res1 = false !== $statusService->save([
                    'oid' => $refundOrder['store_order_id'],
                    'change_type' => 'refund_express',
                    'change_message' => '用户已退货，快递单号：' . $data['refund_express'],
                    'change_time' => time()
                ]);
            $res2 = false !== $this->dao->update(['id' => $id], $data);
            $res = $res1 && $res2;
            if (!$res)
                throw new ValidateException('提交失败!');
        });
        return true;
    }

    /**
     * 退款订单详情
     * @param $uni
     * @return mixed
     */
    public function refundDetail($uni)
    {
        if (!strlen(trim($uni))) throw new ValidateException('参数错误');
        $order = $this->dao->get(['id|order_id' => $uni], ['*']);
        if (!$order) throw new ValidateException('订单不存在');
        $order = $order->toArray();
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $orderInfo = $orderServices->get($order['store_order_id'], ['*'], ['invoice', 'virtual']);
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getUserWithTrashedInfo($order['uid']);
        $order['mapKey'] = sys_config('tengxun_map_key');
        $order['yue_pay_status'] = (int)sys_config('balance_func_status') && (int)sys_config('yue_pay_status') == 1 ? (int)1 : (int)2;//余额支付 1 开启 2 关闭
        $order['pay_weixin_open'] = (int)sys_config('pay_weixin_open') ?? 0;//微信支付 1 开启 0 关闭
        $order['ali_pay_status'] = sys_config('ali_pay_status') ? true : false;//支付包支付 1 开启 0 关闭
        $orderData = $order;
        $orderData['refunded_price'] = floatval($orderData['refunded_price']) ?: $orderData['refund_price'];
        $orderData['store_order_sn'] = $orderInfo['order_id'];
        $orderData['product_type'] = $orderInfo['product_type'];
        $orderData['cartInfo'] = $orderData['cart_info'];
        $orderData['invoice'] = $orderInfo['invoice'];
        $orderData['virtual'] = $orderInfo['virtual'];
        $orderData['virtual_info'] = $orderInfo['virtual_info'];
        $orderData['custom_form'] = is_string($orderInfo['custom_form']) ? json_decode($orderInfo['custom_form'], true) : $orderInfo['custom_form'];
        $cateData = [];
        if (isset($orderData['cartInfo']) && $orderData['cartInfo']) {
            $productId = array_column($orderData['cartInfo'], 'product_id');
            /** @var StoreProductServices $productServices */
            $productServices = app()->make(StoreProductServices::class);
            $cateData = $productServices->productIdByProductCateName($productId);
        }
        //核算优惠金额
        $vipTruePrice = 0;
        $total_price = 0;
        $promotionsPrice = 0;
        foreach ($orderData['cartInfo'] ?? [] as $key => &$cart) {
            if (!isset($cart['sum_true_price'])) $cart['sum_true_price'] = bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 2);
            $cart['vip_sum_truePrice'] = bcmul($cart['vip_truePrice'], $cart['cart_num'] ? $cart['cart_num'] : 1, 2);
            $vipTruePrice = bcadd((string)$vipTruePrice, (string)$cart['vip_sum_truePrice'], 2);
            if (isset($order['split']) && $order['split']) {
                $orderData['cartInfo'][$key]['cart_num'] = $cart['surplus_num'];
                if (!$cart['surplus_num']) unset($orderData['cartInfo'][$key]);
            }
            $total_price = bcadd($total_price, bcmul((string)$cart['sum_price'], (string)$cart['cart_num'], 2), 2);
            $orderData['cartInfo'][$key]['class_name'] = $cateData[$cart['product_id']] ?? '';
            $promotionsPrice = bcadd($promotionsPrice, bcmul((string)($cart['promotions_true_price'] ?? 0), (string)$cart['cart_num'], 2), 2);
        }
        //优惠活动优惠详情
        /** @var StoreOrderPromotionsServices $storeOrderPromotiosServices */
        $storeOrderPromotiosServices = app()->make(StoreOrderPromotionsServices::class);
        if ($orderData['refund_type'] == 6) {
            $orderData['promotions_detail'] = $storeOrderPromotiosServices->getOrderPromotionsDetail((int)$orderData['store_order_id']);
        } else {
            $orderData['promotions_detail'] = $storeOrderPromotiosServices->applyRefundOrderPromotions((int)$orderData['store_order_id'], $orderData['cartInfo']);
        }
        if (!$orderData['promotions_detail'] && $promotionsPrice) {
            $orderData['promotions_detail'][] = [
                'name' => '优惠活动',
                'title' => '优惠活动',
                'promotions_price' => $promotionsPrice,
            ];
        }
        $orderData['use_integral'] = $this->getOrderSumPrice($orderData['cartInfo'], 'use_integral', false);
        $orderData['integral_price'] = $this->getOrderSumPrice($orderData['cartInfo'], 'integral_price', false);
        $orderData['coupon_id'] = $orderInfo['coupon_id'];
        $orderData['coupon_price'] = $this->getOrderSumPrice($orderData['cartInfo'], 'coupon_price', false);
        $orderData['deduction_price'] = $this->getOrderSumPrice($orderData['cartInfo'], 'integral_price', false);
        $orderData['vip_true_price'] = $vipTruePrice;
        $orderData['postage_price'] = 0;
        $orderData['pay_postage'] = $this->getOrderSumPrice($orderData['cart_info'], 'postage_price', false);
        $orderData['member_price'] = 0;
        $orderData['routine_contact_type'] = sys_config('routine_contact_type', 0);
        switch ($orderInfo['pay_type']) {
            case PayServices::WEIXIN_PAY:
                $pay_type_name = '微信支付';
                break;
            case PayServices::YUE_PAY:
                $pay_type_name = '余额支付';
                break;
            case PayServices::OFFLINE_PAY:
                $pay_type_name = '线下支付';
                break;
            case PayServices::ALIAPY_PAY:
                $pay_type_name = '支付宝支付';
                break;
            case PayServices::CASH_PAY:
                $pay_type_name = '现金支付';
                break;
            default:
                $pay_type_name = '其他支付';
                break;
        }
        $orderData['_add_time'] = date('Y-m-d H:i:s', $orderData['add_time']);
        $orderData['add_time_y'] = date('Y-m-d', $orderData['add_time']);
        $orderData['add_time_h'] = date('H:i:s', $orderData['add_time']);
        if (in_array($orderData['refund_type'], [1, 2, 4, 5])) {
            $_type = -1;
            $_msg = '商家审核中,请耐心等待';
            $_title = '申请退款中';
        } elseif ($orderData['refund_type'] == 3) {
            $_type = -3;
            $_title = '拒绝退款';
            $_msg = '商家拒绝退款，请联系商家';
        } else {
            $_type = -2;
            $_title = '已退款';
            $_msg = '已为您退款,感谢您的支持';
        }
        if ($orderData['store_id']) {
            /** @var SystemStoreServices $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            $storeInfo = $storeServices->get($orderData['store_id']);
            $refund_name = $storeInfo['name'];
            $refund_phone = $storeInfo['phone'];
            $refund_address = $storeInfo['detailed_address'];
        } else {
            $refund_name = sys_config('refund_name', '');
            $refund_phone = sys_config('refund_phone', '');
            $refund_address = sys_config('refund_address', '');
        }
        $orderData['_status'] = [
            '_type' => $_type,
            '_title' => $_title,
            '_msg' => $_msg ?? '',
            '_payType' => $pay_type_name,
            'refund_name' => $refund_name,
            'refund_phone' => $refund_phone,
            'refund_address' => $refund_address,
        ];
        $orderData['real_name'] = $orderInfo['real_name'];
        $orderData['user_phone'] = $orderInfo['user_phone'];
        $orderData['user_address'] = $orderInfo['user_address'];
        $orderData['_pay_time'] = $orderInfo['pay_time'] ? date('Y-m-d H:i:s', $orderInfo['pay_time']) : '';
        $orderData['_add_time'] = $orderInfo['add_time'] ? date('Y-m-d H:i:s', $orderInfo['add_time']) : '';
        $orderData['_refund_time'] = $orderData['add_time'] ? date('Y-m-d H:i:s', $orderData['add_time']) : '';
        $orderData['nickname'] = $userInfo['nickname'] ?? '';
        $orderData['total_num'] = $orderData['refund_num'];
        $orderData['pay_price'] = $orderData['refund_price'];
        $orderData['refund_status'] = in_array($orderData['refund_type'], [1, 2, 4, 5]) ? 1 : 2;
        $orderData['total_price'] = floatval(bcsub((string)$total_price, (string)$vipTruePrice, 2));
        $orderData['paid'] = 1;
        $orderData['mark'] = $orderInfo['mark'];
        $orderData['express_list'] = $orderData['refund_type'] == 4 ? app()->make(ExpressServices::class)->expressList(['is_show' => 1]) : [];
        $orderData['spread_uid'] = $orderInfo['spread_uid'] ?? 0;
        return $orderData;
    }

    /**
     * 获取某个字段总金额
     * @param $cartInfo
     * @param string $key
     * @param bool $is_unit
     * @return int|string
     */
    public function getOrderSumPrice($cartInfo, $key = 'truePrice', $is_unit = true)
    {
        $SumPrice = 0;
        foreach ($cartInfo as $cart) {
            if (isset($cart['cart_info'])) $cart = $cart['cart_info'];
            if ($is_unit) {
                $SumPrice = bcadd($SumPrice, bcmul($cart['cart_num'] ?? 1, $cart[$key] ?? 0, 2), 2);
            } else {
                $SumPrice = bcadd($SumPrice, $cart[$key] ?? 0, 2);
            }
        }
        return $SumPrice;
    }
}
