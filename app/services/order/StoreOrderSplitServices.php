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

use app\jobs\order\SpliteOrderAfterJob;
use app\services\BaseServices;
use app\dao\order\StoreOrderDao;
use app\services\order\StoreOrderServices;
use app\services\store\SystemStoreServices;
use think\exception\ValidateException;

/**
 * 订单拆分
 * Class StoreOrderSplitServices
 * @package app\services\order
 */
class StoreOrderSplitServices extends BaseServices
{

    /**
     * 需要清空恢复默认数据字段
     * @var string[]
     */
    protected $order_data = ['id', 'refund_status', 'refund_type', 'refund_express', 'refund_reason_wap_img', 'refund_reason_wap_explain', 'refund_reason_time', 'refund_reason_wap', 'refund_reason', 'refund_price'];

    /**
     * 构造方法
     * StoreOrderRefundServices constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 检测要拆分数组
     * @param int $id
     * @param array $cart_ids
     * @param array $orderInfo
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkCartNum(int $id, array $cart_ids, $orderInfo = [])
    {
        if (!$cart_ids) {
            return false;
        }
        if (!$orderInfo) {
            $orderInfo = $this->dao->get($id, ['*']);
        }
        $total_num = array_sum(array_column($cart_ids, 'cart_num'));
        //商品和原订单一致，不拆分
        if ($total_num >= $orderInfo['total_num']) {
            return false;
        }
        return true;
    }

    /**
     * 平行拆分订单
     * @param int $id
     * @param array $cart_ids
     * @param array $orderInfo
     * @param int $store_id
     * @param bool $is_refund
     * @param int $erp_id
     * @return false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function equalSplit(int $id, array $cart_ids, $orderInfo = [], int $store_id = 0, bool $is_refund = false, int $erp_id = 0)
    {
        if (!$cart_ids) {
            return false;
        }
        if (!$orderInfo) {
            $orderInfo = $this->dao->get($id, ['*']);
        }
        if (!$orderInfo) {
            throw new ValidateException('订单未能查到,不能拆分订单!');
        }
        if ($orderInfo['pid'] == -1) {
            throw new ValidateException('已拆分完毕，请返回列表重新操作');
        }
        if (!$this->checkCartNum($id, $cart_ids, $orderInfo)) {
            return false;
        }
        $orderInfo = is_object($orderInfo) ? $orderInfo->toArray() : $orderInfo;
        /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
        $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        //订单下原商品信息
        $cartInfo = $storeOrderCartInfoServices->getCartColunm(['oid' => $id, 'split_status' => [0, 1]], 'is_gift,cart_num,split_surplus_num', 'cart_id');
        $new_cart_ids = array_combine(array_column($cart_ids, 'cart_id'), $cart_ids);
        $other_cart_ids = $other = [];

        foreach ($cartInfo as $cart_id => $cart) {
            if (!isset($new_cart_ids[$cart_id]) && $cart['split_surplus_num']) {//无拆分
                $other = ['cart_id' => $cart_id, 'cart_num' => $cart['split_surplus_num']];
            } else if ($new_cart_ids[$cart_id]['cart_num'] < $cart['split_surplus_num']) {
                $other = ['cart_id' => $cart_id, 'cart_num' => bcsub((string)$cart['split_surplus_num'], (string)$new_cart_ids[$cart_id]['cart_num'], 0)];
            } else {
                continue;
            }
            $other_cart_ids[] = $other;
        }
        $data = [$cart_ids, $other_cart_ids];
		$split_cart_ids = $cart_ids;
		if ($other_cart_ids && $orderInfo['status'] == 0 && $is_refund) {
			//拆分剩余 都是赠品
			$is_gift = true;
			foreach ($other_cart_ids as $cart) {
				if (!isset($cartInfo[$cart['cart_id']]) || !$cartInfo[$cart['cart_id']]['is_gift']) {
					$is_gift = false;
					break;
				}
			}
			if ($is_gift) {//未发货 申请退款 整单退
				return [];
			}
		}
		
        /** @var StoreOrderServices $storeOrderServices */
        $storeOrderServices = app()->make(StoreOrderServices::class);
        $orderInfo = $storeOrderServices->tidyOrder($orderInfo, true, true);
        //核算优惠金额
        $vipTruePrice = 0;
        $refund_num = 0;
        $all_true_price = 0;
        foreach ($orderInfo['cartInfo'] ?? [] as $key => &$cart) {
            $vipTruePrice = bcadd((string)$vipTruePrice, (string)$cart['vip_sum_truePrice'], 2);
            $refund_num = bcadd((string)$refund_num, (string)$cart['refund_num'], 0);
            $all_true_price = bcadd((string)$all_true_price, bcmul((string)($cart['truePrice'] ?? 0), (string)$cart['cart_num'], 4), 2);
        }
        $orderInfo['vip_true_price'] = $vipTruePrice;
        $orderInfo['total_price'] = floatval(bcsub((string)$orderInfo['total_price'], (string)$vipTruePrice, 2));

        //订单实际支付金额
        $order_pay_price = bcadd((string)$all_true_price, (string)$orderInfo['pay_postage'], 2);
        //有改价
        $change_price = $order_pay_price != $orderInfo['pay_price'];

        $order = $this->transaction(function () use ($id, $data, $orderInfo, $store_id, $storeOrderCartInfoServices, $split_cart_ids, $is_refund, $change_price, $order_pay_price, $erp_id) {
            $order = [];
            $i = 0;
            foreach ($data as $key => $cart_ids) {
                if ($key == 0 && $erp_id > 0) {
                    $orderInfo['erpId'] = $erp_id;
                }
                //生成新订单、处理cart_info
                [$new_id, $cart_data] = $this->splitV2($id, $cart_ids, $orderInfo, $i, $store_id, $is_refund, $split_cart_ids);
                //处理订单
                $this->splitComputeOrder((int)$new_id, $cart_data, $orderInfo, (float)($change_price ? $order_pay_price : 0), (float)$orderInfo['pay_price'], (float)($order[0]['pay_price'] ?? 0));
                $order[] = $this->dao->get($new_id);
                $i++;
            }
            //标记主订单拆分完成
            if (!$orderInfo['pid']) $this->dao->update($id, ['pid' => -1]);

            //处理申请开票记录
            /** @var StoreOrderInvoiceServices $storeOrderInvoiceServics */
            $storeOrderInvoiceServics = app()->make(StoreOrderInvoiceServices::class);
            $storeOrderInvoiceServics->splitOrderInvoice((int)$id);
            //处理订单优惠活动记录
            /** @var StoreOrderPromotionsServices $storeOrderPromotionsServics */
            $storeOrderPromotionsServics = app()->make(StoreOrderPromotionsServices::class);
            $storeOrderPromotionsServics->splitOrderPromotions((int)$id);
            return $order;
        });
        //拆分完成后置队列 处理订单状态记录
        SpliteOrderAfterJob::dispatch([$id, $order]);
        return $order;
    }

    /**
     * 订单拆分
     * @param int $id
     * @param array $cart_ids
     * @param array $orderInfo
     * @param int $i
     * @param int $store_id
     * @param bool $is_refund
     * @param array $append
     * @return array|false
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function splitV2(int $id, array $cart_ids, $orderInfo = [], int $i = 0, int $store_id = 0, bool $is_refund = false, array $append = [])
    {
        $ids = array_unique(array_column($cart_ids, 'cart_id'));
        if (!$cart_ids || !$ids) {
            return false;
        }
        if (!$orderInfo) {
            $orderInfo = $this->dao->get($id, ['*']);
        }
        if (!$orderInfo) {
            throw new ValidateException('订单未能查到,不能拆分订单!');
        }
        /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
        $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        //订单下原商品信息
        $cartInfo = $storeOrderCartInfoServices->getCartColunm(['oid' => $id, 'cart_id' => $ids], '*', 'cart_id');
        if (!$cartInfo || count($cartInfo) != count($ids)) {
            return false;
        }
        if ($append) $append = array_combine(array_column($append, 'cart_id'), $append);

        $is_not_split = $i && $orderInfo['pid'];

        if ($is_not_split) {//保留原订单 不重新生成（只修改订单信息、订单商品信息）
            $new_id = $id;
            //删除原订单下商品信息
            $storeOrderCartInfoServices->delete(['oid' => $id]);
            //删除缓存
            $storeOrderCartInfoServices->clearOrderCartInfo($id);
        } else {
            /** @var StoreOrderCreateServices $storeOrderCreateServices */
            $storeOrderCreateServices = app()->make(StoreOrderCreateServices::class);
            $orderInfo = is_object($orderInfo) ? $orderInfo->toArray() : $orderInfo;

            $pid = $orderInfo['pid'] ?: $orderInfo['id'];
            foreach ($this->order_data as $field) {
                unset($orderInfo[$field]);
            }
            //核销码重新生成
            if ($orderInfo['verify_code']) {
                $orderInfo['verify_code'] = $storeOrderCreateServices->getStoreCode();
            }
            $order_data = $orderInfo;
            $order_data['promotions_give'] = is_array($order_data['promotions_give']) ? json_encode($order_data['promotions_give']) : $order_data['promotions_give'];
            $order_data['give_coupon'] = is_array($order_data['give_coupon']) ? implode(',', $order_data['give_coupon']) : $order_data['give_coupon'];
            //$pid 平行拆分 0：子订单拆分
            $order_data['pid'] = $pid ?: $id;
			$parentOrder = $this->dao->get($order_data['pid']);
			$oldOrderId = $parentOrder['order_id'];
			$childCont = $this->dao->count(['pid' => $parentOrder['id']]);
            if ($store_id) {//门店分配拆分
                /** @var SystemStoreServices $storeServices */
                $storeServices = app()->make(SystemStoreServices::class);
                if (!$storeInfo = $storeServices->getStoreInfo($store_id)) {
                    throw new ValidateException('门店不存在');
                }
                $order_data['store_id'] = $store_id;
                $order_data['order_id'] = $storeOrderCreateServices->getNewOrderId();
                if ($order_data['shipping_type'] == 1) {
                    $order_data['shipping_type'] = 3;
                } else {//自提订单
                    $order_data['verify_code'] = $storeOrderCreateServices->getStoreCode();
                }
                $status_data = [
                    'change_type' => 'store_split_create_order',
                    'change_message' => '门店分配拆分生成订单',
                    'change_time' => time()
                ];
            } else {//发货拆分
//                $order_data['order_id'] = $storeOrderCreateServices->getNewOrderId();
				$order_data['order_id'] = $oldOrderId . '_' . ($childCont + 1);
                $status_data = [
                    'change_type' => 'split_create_order',
                    'change_message' => $is_refund ? '售后退款拆分生成订单' : '发货拆分生成订单',
                    'change_time' => time()
                ];
            }
            $order_data['cart_id'] = [];
            $order_data['unique'] = $storeOrderCreateServices->getNewOrderId('');
            $order_data['add_time'] = time();

            $new_order = $this->dao->save($order_data);
            if (!$new_order) {
                throw new ValidateException('生成新订单失败');
            }
            $new_id = (int)$new_order->id;
            /** @var StoreOrderStatusServices $statusService */
            $statusService = app()->make(StoreOrderStatusServices::class);
            $status_data['oid'] = $new_id;
            $statusService->save($status_data);
        }
        //生成订单商品信息 保存
        $cart_data = $cart_data_all = $update_data = [];
        foreach ($cart_ids as $cart) {
            $split_surplus_num = $cartInfo[$cart['cart_id']]['split_surplus_num'] ?? 0;
            if (!isset($cartInfo[$cart['cart_id']]) || (!$is_not_split && !$split_surplus_num)) continue;
            $_info = is_string($cartInfo[$cart['cart_id']]['cart_info']) ? json_decode($cartInfo[$cart['cart_id']]['cart_info'], true) : $cartInfo[$cart['cart_id']]['cart_info'];
            $cart_data = $cartInfo[$cart['cart_id']];
            $cart_data['oid'] = $new_id;
            unset($cart_data['id']);
            $cart_data['split_surplus_num'] = $cart['cart_num'];
            $split_refund_num = $append[$cart['cart_id']]['cart_num'] ?? 0;
            if (!$is_not_split) {//新增cart_info
                $cart_data['cart_id'] = $storeOrderCreateServices->getNewOrderId('');
                $cart_data['product_id'] = $_info['product_id'];
                $cart_data['old_cart_id'] = $cart['cart_id'];
                $cart_data['unique'] = md5($cart_data['cart_id'] . '_' . $cart_data['oid']);
                $cart_data['surplus_num'] = $cart['cart_num'];
            } else {//保留

            }
            $refund_num = 0;
            //拆出订单
            if (!$i) {
                //无核销 ｜｜ 有核销剩余数量大于拆分数量
                $surplus_num = !$cart_data['writeoff_time'] || $cart_data['surplus_num'] >= $cart['cart_num'] ? $cart['cart_num'] : bcsub((string)$cart['cart_num'], (string)$cart_data['surplus_num'], 0);
                if ($is_refund) $refund_num = $cart['cart_num'];
            } else {//修改原订单
                //无核销 ｜｜ 核销数量大于拆分数量
                if (!$cart_data['writeoff_time']) {
                    $surplus_num = $cart['cart_num'];
                } else {
                    $writoff_numm = bcsub((string)$cart_data['cart_num'], (string)$cart_data['surplus_num'], 0);
                    $surplus_num = $writoff_numm >= $cart['cart_num'] ? 0 : bcsub((string)$cart['cart_num'], (string)$writoff_numm, 0);
                }
                if ($is_refund) $refund_num = $split_refund_num >= $cart_data['refund_num'] ? 0 : bcsub((string)$cart_data['refund_num'], (string)$split_refund_num, 0);
            }
            $cart_data['cart_num'] = $cart['cart_num'];
            $cart_data['refund_num'] = $refund_num > 0 ? $refund_num : 0;
            $cart_data['surplus_num'] = $surplus_num > 0 ? $surplus_num : 0;
            if (!$cart_data['surplus_num']) {//核销完毕
                $cart_data['is_writeoff'] = 1;
            }
            if ($cart_data['surplus_num'] == $cart_data['cart_num']) {//未核销
                $cart_data['is_writeoff'] = 0;
                $cart_data['writeoff_time'] = 0;
            }
            if ($cart['cart_num'] >= $split_surplus_num) {//拆分完成
                $cart_data['cart_num'] = $split_surplus_num;
                $update_data['split_status'] = 2;
                $update_data['split_surplus_num'] = 0;
            } else {//拆分部分数量
                $update_data['split_surplus_num'] = bcsub((string)$split_surplus_num, $cart['cart_num'], 0);
                $update_data['split_status'] = $update_data['split_surplus_num'] > 0 ? 1 : 2;
            }


            $_info = $this->slpitComputeOrderCart($cart_data['cart_num'], $_info, $i, !!($cart_data['is_gift'] ?? 0));
            $_info['id'] = $cart_data['cart_id'];
            $cart_data['cart_info'] = json_encode($_info);

            //修改原来订单商品信息
            if (!$is_not_split && false === $storeOrderCartInfoServices->update(['oid' => $id, 'cart_id' => $cart['cart_id']], $update_data)) {
                throw new ValidateException('修改原来订单商品拆分状态失败，请稍候重试');
            }
            $cart_data_all[] = $cart_data;
            unset($cartInfo[$cart['cart_id']]);
        }

        if (!$storeOrderCartInfoServices->saveAll($cart_data_all)) {
            throw new ValidateException('新增拆分订单商品信息失败');
        }
        return [$new_id, $cart_data_all];
//        $split_order_info = $this->dao->get($new_id);
//        $this->splitComputeOrder($new_id, $cart_data_all, $split_order_info);
//        return $split_order_info;
    }

    /**
     * 获取整理后的订单商品信息
     * @param int $id
     * @param array $cart_ids
     * @param array $orderInfo
     * @return array|false
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSplitOrderCartInfo(int $id, array $cart_ids, $orderInfo = [])
    {
        $ids = array_unique(array_column($cart_ids, 'cart_id'));
        if (!$cart_ids || !$ids) {
            return false;
        }
        if (!$orderInfo) {
            $orderInfo = $this->dao->get($id, ['*']);
        }
        if (!$orderInfo) {
            throw new ValidateException('订单未能查到,不能拆分订单!');
        }
        /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
        $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $cartInfo = $storeOrderCartInfoServices->getCartColunm(['oid' => $id, 'cart_id' => $ids], '*', 'cart_id');
        $cart_data_all = [];
        foreach ($cart_ids as $cart) {
            $split_surplus_num = $cartInfo[$cart['cart_id']]['split_surplus_num'] ?? 0;
            if (!isset($cartInfo[$cart['cart_id']]) || !$split_surplus_num) continue;
            $_info = is_string($cartInfo[$cart['cart_id']]['cart_info']) ? json_decode($cartInfo[$cart['cart_id']]['cart_info'], true) : $cartInfo[$cart['cart_id']]['cart_info'];
            $cart_data = $cartInfo[$cart['cart_id']];
            $cart_data['oid'] = $id;
            $cart_data['product_id'] = $_info['product_id'];
            $cart_data['old_cart_id'] = $cart['cart_id'];
            $cart_data['cart_num'] = $cart['cart_num'];
            $cart_data['surplus_num'] = $cart['cart_num'];
            $cart_data['split_surplus_num'] = $cart['cart_num'];

            $_info = $this->slpitComputeOrderCart($cart_data['cart_num'], $_info);
            $_info['id'] = $cart_data['cart_id'];
            $cart_data['cart_info'] = $_info;
            $cart_data_all[] = $cart_data;
            unset($cartInfo[$cart['cart_id']]);
        }
        return $cart_data_all;
    }

    /**
     * 重新计算新订单中价格等信息
     * @param int $id 订单号
     * @param array $cart_info_data 重新计算过订单商品信息
     * @param array $orderInfo 订单信息
     * @param float $order_pay_price 订单原来支付金额 0:没改价继续计算 >0 存在改价 需要计算新订单实际支付金额
     * @param float $pay_price 订单实际支付金额
     * @param float $pre_pay_price 上一个订单实际支付金额
     * @return bool
     */
    public function splitComputeOrder(int $id, array $cart_info_data, $orderInfo = [], float $order_pay_price = 0.00, float $pay_price = 0.00, float $pre_pay_price = 0.00)
    {
        $order_update['cart_id'] = array_column($cart_info_data, 'cart_id');
        $order_update['total_num'] = array_sum(array_column($cart_info_data, 'cart_num'));
        $total_price = $true_total_price = $coupon_price = $deduction_price = $use_integral = $pay_postage = $gainIntegral = $one_brokerage = $two_brokerage = $promotions_price = 0;
        $unwriteoff = $wrrteoffed = 0;
        foreach ($cart_info_data as $cart) {
            $_info = json_decode($cart['cart_info'], true);
            if (isset($cart['writeoff_time']) && !$cart['writeoff_time']) {
                $unwriteoff++;
            }
            if (isset($cart['is_writeoff']) && $cart['is_writeoff']) {
                $wrrteoffed++;
            }
            //赠品跳过
            if (isset($cart['is_gift']) && $cart['is_gift']) {
                continue;
            }
            $true_total_price = bcadd((string)$true_total_price, (string)($_info['sum_true_price'] ?? 0), 2);
            $total_price = bcadd((string)$total_price, (string)bcmul((string)$cart['cart_num'] ?? '1', (string)$_info['sum_price'] ?? '0', 2), 2);
            $deduction_price = bcadd((string)$deduction_price, (string)($_info['integral_price'] ?? 0), 2);
            $coupon_price = bcadd((string)$coupon_price, (string)($_info['coupon_price'] ?? 0), 2);
            $use_integral = bcadd((string)$use_integral, (string)($_info['use_integral'] ?? 0), 0);
            $pay_postage = bcadd((string)$pay_postage, (string)($_info['postage_price'] ?? 0), 2);
            $cartInfoGainIntegral = bcmul((string)$cart['cart_num'], (string)($_info['productInfo']['give_integral'] ?? '0'), 0);
            $gainIntegral = bcadd((string)$gainIntegral, (string)$cartInfoGainIntegral, 0);
            $one_brokerage = bcadd((string)$one_brokerage, (string)$_info['one_brokerage'], 2);
            $two_brokerage = bcadd((string)$two_brokerage, (string)$_info['two_brokerage'], 2);
            $promotions_price = bcadd((string)$promotions_price, (string)bcmul((string)$cart['cart_num'], (string)($_info['promotions_true_price'] ?? 0), 2), 2);
        }

        $order_update['coupon_id'] = array_unique(array_column($cart_info_data, 'coupon_id'));
        $order_update['pay_price'] = bcadd((string)$true_total_price, (string)$pay_postage, 2);
        //有订单原来支付金额 改价订单
        if ($order_pay_price) {
            if ($pre_pay_price) {//上一个已经计算 这里减法
                $order_update['pay_price'] = bcsub((string)$pay_price, (string)$pre_pay_price, 2);
            } else {//按比例计算实际支付金额
                $order_update['pay_price'] = bcmul((string)bcdiv((string)$pay_price, (string)$order_pay_price, 4), (string)$order_update['pay_price'], 2);
            }
        }
        if (!$pre_pay_price) {//最后一个订单保留赠送 积分 优惠券信息
            $order_update['give_integral'] = 0;
            $order_update['give_coupon'] = '';
        }
        $order_update['pay_price'] = $order_update['pay_price'] < 0 ? 0 : $order_update['pay_price'];
        $order_update['total_price'] = $total_price;
        $order_update['deduction_price'] = $deduction_price;
        $order_update['coupon_price'] = $coupon_price;
        $order_update['use_integral'] = $use_integral;
        $order_update['gain_integral'] = $gainIntegral;
        $order_update['pay_postage'] = $pay_postage;
        $order_update['one_brokerage'] = $one_brokerage;
        $order_update['two_brokerage'] = $two_brokerage;
        $order_update['promotions_price'] = $promotions_price;

        if ($orderInfo['status'] == 5) {//部分核销拆单
            if ($unwriteoff == $order_update['total_num']) {//未核销
                $order_update['status'] = 0;
            } elseif ($wrrteoffed == $order_update['total_num']) {//全部核销
                $order_update['status'] = 2;
            }
        }
        if (false === $this->dao->update($id, $order_update, 'id')) {
            throw new ValidateException('保存新订单商品信息失败');
        }
        return true;
    }

    /**
     * 部分发货重新计算订单商品：实际金额、优惠、积分等金额
     * @param int $cart_num
     * @param array $cart_info
     * @param int $i
     * @param bool $is_gift
     * @return array
     */
    public function slpitComputeOrderCart(int $cart_num, array $cart_info, int $i = 0, bool $is_gift = false)
    {
        if (!$cart_num || !$cart_info) return [];
        $new_cart_info = $cart_info;
        if ($cart_num < $cart_info['cart_num']) {
            $compute_arr = ['coupon_price', 'integral_price', 'postage_price', 'use_integral', 'one_brokerage', 'two_brokerage', 'sum_true_price'];
            foreach ($compute_arr as $field) {
                if (!isset($cart_info[$field]) || !$cart_info[$field] || $is_gift) {
                    $new_cart_info[$field] = 0;
                    continue;
                }
                $scale = 2;
                if ($field == 'use_integral') $scale = 0;

                if (!$i) {//拆出
                    $new_cart_info[$field] = bcmul((string)$cart_num, bcdiv((string)$cart_info[$field], (string)$cart_info['cart_num'], 4), $scale);
                } else {
                    $field_number = bcmul((string)bcsub((string)$cart_info['cart_num'], (string)$cart_num, 0), bcdiv((string)$cart_info[$field], (string)$cart_info['cart_num'], 4), $scale);
                    $new_cart_info[$field] = bcsub((string)$cart_info[$field], (string)$field_number, $scale);
                }
            }
        }
        $new_cart_info['cart_num'] = $cart_num;
        return $new_cart_info;
    }
}
