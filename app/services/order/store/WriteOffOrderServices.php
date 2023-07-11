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
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderStatusServices;
use app\services\order\StoreOrderTakeServices;
use app\services\activity\integral\StoreIntegralOrderServices;
use app\services\activity\integral\StoreIntegralOrderStatusServices;
use app\services\activity\combination\StorePinkServices;
use app\services\BaseServices;
use app\services\store\finance\StoreFinanceFlowServices;
use app\services\store\SystemStoreStaffServices;
use app\services\store\DeliveryServiceServices;
use app\services\user\UserServices;
use think\exception\ValidateException;

/**
 * 核销订单
 * Class StoreOrderWriteOffServices
 * @package app\sservices\order
 */
class WriteOffOrderServices extends BaseServices
{

    /**
     * 构造方法
     * StoreOrderWriteOffServices constructor.
     * @param StoreOrderDao $dao
     */
    public function __construct(StoreOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 验证核销订单权限
     * @param int $uid
     * @param array $orderInfo
     * @param int $auth
     * @param int $staff_id
     * @return bool
     */
    public function checkAuth(int $uid, array $orderInfo, int $auth = 1, int $staff_id = 0)
    {
        if ((!$uid && !$staff_id) || !$orderInfo) {
            throw new ValidateException('订单不存在');
        }
        $store_id = $orderInfo['store_id'] ?? 0;
        $info = $this->checkUserAuth($uid, $auth, $store_id, $staff_id);
        switch ($auth) {
            case 1://门店
                if ($orderInfo['shipping_type'] == 2 && $info && isset($info['store_id']) && $info['store_id'] == $store_id) {
                    $isAuth = true;
                } else {
                    $isAuth = false;
                }
                break;
            case 2://配送员
                if (in_array($orderInfo['shipping_type'], [1, 3]) && $info && $orderInfo['delivery_type'] == 'send' && $orderInfo['delivery_uid'] == $uid) {
                    $isAuth = true;
                } else {
                    $isAuth = false;
                }
                break;
        }
        if (!$isAuth) {
            throw new ValidateException('您无权限核销此订单，请联系管理员');
        }
        return true;
    }

    /**
     * 验证核销权限
     * @param int $uid
     * @param int $auth
     * @param int $store_id
     * @param int $staff_id
     * @return array|\think\Model
     */
    public function checkUserAuth(int $uid, int $auth = 1, int $store_id = 0, int $staff_id = 0)
    {
        if (!$uid && !$staff_id) {
            throw new ValidateException('用户不存在');
        }
        $isAuth = false;
        $info = [];
        switch ($auth) {
            case 1://门店
                //验证店员
                /** @var SystemStoreStaffServices $storeStaffServices */
                $storeStaffServices = app()->make(SystemStoreStaffServices::class);
                try {
                    if ($staff_id) {
                        $info = $storeStaffServices->getStaffInfo($staff_id);
                    } else {
                        $info = $storeStaffServices->getStaffInfoByUid($uid, $store_id);
                    }
                } catch (\Throwable $e) {

                }
                if ($info && $info['verify_status'] == 1) {
                    $isAuth = true;
                }
                break;
            case 2://配送员
                /** @var DeliveryServiceServices $deliverServiceServices */
                $deliverServiceServices = app()->make(DeliveryServiceServices::class);
                try {
                    $info = $deliverServiceServices->getDeliveryInfoByUid($uid, $store_id);
                } catch (\Throwable $e) {

                }

                if ($info) {
                    $isAuth = true;
                }
                break;
        }
        if (!$isAuth) {
            throw new ValidateException('您无权限核销，请联系管理员');
        }
        return $info;
    }

    /**
     * 用户码获取待核销订单列表
     * @param int $uid
     * @param string $code
     * @param int $auth
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userUnWriteoffOrder(int $uid, string $code, int $auth = 1)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getOne(['bar_code' => $code]);
        if (!$userInfo) {
            throw new ValidateException('该用户不存在');
        }
        $info = $this->checkUserAuth($uid, $auth);
        $unWriteoffOrder = [];
        if ($info && isset($info['store_id'])) {
            if ($auth == 1) {//店员
                $where = ['store_id' => $info['store_id']];
            } else {//配送员
                $where = ['delivery_uid' => $info['uid']];
            }
            $unWriteoffOrder = $this->dao->getUnWirteOffList(['uid' => $userInfo['uid']] + $where, ['id']);
        }
        $data = [];
        if ($unWriteoffOrder) {
            foreach ($unWriteoffOrder as $item) {
                try {
                    $orderInfo = $this->writeoffOrderInfo($uid, '', $auth, $item['id']);
                } catch (\Throwable $e) {//无权限或其他异常不返回订单信息
                    $orderInfo = [];
                }
                if ($orderInfo) $data[] = $orderInfo;
            }
        }
        return $data;
    }

    /**
     * 获取核销订单信息
     * @param int $uid
     * @param string $code
     * @param int $auth
     * @param int $oid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function writeoffOrderInfo(int $uid, string $code = '', int $auth = 1, int $oid = 0, int $staff_id = 0)
    {
        if ($oid) {
            //订单
            $orderInfo = $this->dao->getOne(['id' => $oid, 'is_del' => 0], '*', ['user', 'pink']);
            $order_type = 'order';
            if (!$orderInfo) {
                //积分兑换订单
                /** @var StoreIntegralOrderServices $storeIntegralOrderServices */
                $storeIntegralOrderServices = app()->make(StoreIntegralOrderServices::class);
                $orderInfo = $storeIntegralOrderServices->getOne(['id' => $oid]);
                $order_type = 'integral';
            }
        } else {
            //订单
            $orderInfo = $this->dao->getOne(['verify_code' => $code, 'is_del' => 0], '*', ['user', 'pink']);
            $order_type = 'order';
            if (!$orderInfo) {
                //积分兑换订单
                /** @var StoreIntegralOrderServices $storeIntegralOrderServices */
                $storeIntegralOrderServices = app()->make(StoreIntegralOrderServices::class);
                $orderInfo = $storeIntegralOrderServices->getOne(['verify_code' => $code]);
                $order_type = 'integral';
            }
        }

        if (!$orderInfo) {
            throw new ValidateException('Write off order does not exist');
        }
		if ($order_type == 'order' && !$orderInfo['paid']) {
			throw new ValidateException('订单还未完成支付');
		}
		if ($order_type == 'order' && $orderInfo['refund_status'] != 0) {
			throw new ValidateException('该订单状态暂不支持核销');
		}

        $orderInfo['order_type'] = $order_type;
        $orderInfo = $orderInfo->toArray();
        //验证权限
        $this->checkAuth($uid, $orderInfo, $auth, $staff_id);
        return $orderInfo;
    }

    /**
 	* 获取订单商品信息
	* @param int $uid
	* @param int $id
	* @param int $auth
	* @param int $staff_id
	* @param bool $isCasher
	* @return array|\think\Model|null
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	 */
    public function getOrderCartInfo(int $uid, int $id, int $auth = 1, int $staff_id = 0, bool $isCasher = false)
    {
		if ($isCasher) {//获取订单信息 暂时不验证权限
			$orderInfo = $this->dao->getOne(['id' => $id], '*', ['user', 'pink']);
			if (!$orderInfo) {
				throw new ValidateException('Write off order does not exist');
			}
			$orderInfo = $orderInfo->toArray();
		} else {
			$orderInfo = $this->writeoffOrderInfo($uid, '', $auth, $id, $staff_id);
		}
        $writeoff_count = 0;
        /** @var StoreOrderCartInfoServices $cartInfoServices */
        $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        $cartInfo = $cartInfoServices->getCartColunm(['oid' => $orderInfo['id']], 'id,cart_id,cart_num,surplus_num,is_writeoff,cart_info,product_type,is_support_refund,is_gift');
        foreach ($cartInfo as &$item) {
            $_info = is_string($item['cart_info']) ? json_decode($item['cart_info'], true) : $item['cart_info'];
            if (!isset($_info['productInfo'])) $_info['productInfo'] = [];
            //缩略图处理
            if (isset($_info['productInfo']['attrInfo'])) {
                $_info['productInfo']['attrInfo'] = get_thumb_water($_info['productInfo']['attrInfo']);
            }
            $_info['productInfo'] = get_thumb_water($_info['productInfo']);
            $item['cart_info'] = $_info;
            if ($item['cart_num'] > $item['surplus_num']) $writeoff_count = bcadd((string)$writeoff_count, (string)bcsub((string)$item['cart_num'], (string)$item['surplus_num']));
            unset($_info);
        }
        $orderInfo['cart_count'] = count($cartInfo);
        $orderInfo['writeoff_count'] = $writeoff_count;
        $orderInfo['cart_info'] = $cartInfo;
        return $orderInfo;
    }

    /**
     * 核销订单
     * @param int $uid
     * @param array $orderInfo
     * @param array $cartIds
     * @param int $auth
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function writeoffOrder(int $uid, array $orderInfo, array $cartIds = [], int $auth = 1, int $staff_id = 0)
    {
        if (!$orderInfo) {
            throw new ValidateException('订单不存在');
        }
        if (!$orderInfo['verify_code'] || ($orderInfo['shipping_type'] != 2 && $orderInfo['delivery_type'] != 'send')) {
            throw new ValidateException('此订单不能被核销');
        }
        /** @var StoreOrderRefundServices $storeOrderRefundServices */
        $storeOrderRefundServices = app()->make(StoreOrderRefundServices::class);
        if ($storeOrderRefundServices->count(['store_order_id' => $orderInfo['id'], 'refund_type' => [1, 2, 4, 5], 'is_cancel' => 0, 'is_del' => 0])) {
            throw new ValidateException('订单有售后申请请先处理');
        }
        if (isset($orderInfo['pinkStatus']) && $orderInfo['pinkStatus'] != 2) {
            throw new ValidateException('拼团未完成暂不能发货!');
        }

        //默认正常订单
        $orderInfo['order_type'] = $orderInfo['order_type'] ?? 'order';
        if ($orderInfo['order_type'] == 'order') {
            /** @var StoreOrderCartInfoServices $cartInfoServices */
            $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
            if ($orderInfo['status'] == 2 && !$cartInfoServices->count(['oid' => $orderInfo['id'], 'is_writeoff' => 0])) {
                throw new ValidateException('订单已核销');
            }
            $store_id = $orderInfo['store_id'];
            if ($orderInfo['type'] == 3 && $orderInfo['activity_id'] && $orderInfo['pink_id']) {
                /** @var StorePinkServices $services */
                $services = app()->make(StorePinkServices::class);
                $res = $services->getCount([['id', '=', $orderInfo['pink_id']], ['status', '<>', 2]]);
                if ($res) throw new ValidateException('Failed to write off the group order');
            }

            $cartInfo = [];
            if ($cartIds) {//商城存在部分核销
                $ids = array_unique(array_column($cartIds, 'cart_id'));
                //订单下原商品信息
                $cartInfo = $cartInfoServices->getCartColunm(['oid' => $orderInfo['id'], 'cart_id' => $ids, 'is_writeoff' => 0], 'cart_id,cart_num,surplus_num,product_id', 'cart_id');
                if (count($ids) != count($cartInfo)) {
                    throw new ValidateException('订单中有商品已核销');
                }
            }

            $data = ['clerk_id' => $uid];
            $cartData = ['writeoff_time' => time()];
            if ($auth == 1) {//店员
                /** @var SystemStoreStaffServices $storeStaffServices */
                $storeStaffServices = app()->make(SystemStoreStaffServices::class);
                if ($uid) {//商城前端
                    $staffInfo = $storeStaffServices->getStaffInfoByUid($uid, $store_id);
                } else {//门店后台
                    $staffInfo = $storeStaffServices->getStaffInfo($staff_id);
                    if ($store_id != $staffInfo['store_id']) {
                        throw new ValidateException('订单不存在');
                    }
                    if ($staffInfo['verify_status'] != 1) {
                        throw new ValidateException('您暂无核销权限');
                    }
                    $data['clerk_id'] = $staffInfo['uid'];
                }
                $data['staff_id'] = $staffInfo['id'] ?? 0;
                $cartData['staff_id'] = $staffInfo['id'] ?? 0;
            } else if ($auth == 2) {//配送员
                /** @var DeliveryServiceServices $deliverServiceServices */
                $deliverServiceServices = app()->make(DeliveryServiceServices::class);
                $deliveryInfo = $deliverServiceServices->getDeliveryInfoByUid($uid, $store_id);
                $cartData['delivery_id'] = $deliveryInfo['id'] ?? 0;
            }
            /** @var StoreOrderStatusServices $statusServices */
            $statusServices = app()->make(StoreOrderStatusServices::class);
            $this->transaction(function () use ($orderInfo, $staff_id, $data, $cartIds, $cartInfoServices, $statusServices, $cartData, $auth, $cartInfo) {
                if ($cartInfo) {//选择商品、件数核销
                    foreach ($cartIds as $cart) {
                        $surplus_num = $cartInfo[$cart['cart_id']]['surplus_num'] ?? 0;
                        if (!isset($cartInfo[$cart['cart_id']]) || !$surplus_num) continue;
                        if ($cart['cart_num'] >= $surplus_num) {//拆分完成
                            $cartData['surplus_num'] = 0;
                            $cartData['is_writeoff'] = 1;
                        } else {//拆分部分数量
                            $cartData['surplus_num'] = bcsub((string)$surplus_num, $cart['cart_num'], 0);
                            $cartData['is_writeoff'] = 0;
                        }
                        //修改原来订单商品信息
                        $cartInfoServices->update(['oid' => $orderInfo['id'], 'cart_id' => $cart['cart_id']], $cartData);
                    }
                } else {//整单核销
                    //修改原来订单商品信息
                    $cartData['is_writeoff'] = 1;
                    $cartData['surplus_num'] = 0;
                    $cartInfoServices->update(['oid' => $orderInfo['id']], $cartData);
                }
                if (!$cartInfoServices->count(['oid' => (int)$orderInfo['id'], 'is_writeoff' => 0])) {//全部核销
                    $data['status'] = 2;
                    /** @var StoreOrderTakeServices $storeOrdeTask */
                    $storeOrdeTask = app()->make(StoreOrderTakeServices::class);
                    $re = $storeOrdeTask->storeProductOrderUserTakeDelivery($orderInfo);
                    if (!$re) {
                        throw new ValidateException('Write off failure');
                    }
                } else {//部分核销
                    $data['status'] = 5;
                }
                if (!$this->dao->update($orderInfo['id'], $data)) {
                    throw new ValidateException('Write off failure');
                }
                if ($data['status'] == 5) {
                    $message = [];
                    foreach ($cartInfo as $item) {
                        foreach ($cartIds as $value) {
                            if ($value['cart_id'] === $item['cart_id']) {
                                $message[] = '商品id:' . $item['product_id'] . ',核销数量:' . $value['cart_num'];
                            }
                        }
                    }
                    $statusServices->save([
                        'oid' => $orderInfo['id'],
                        'change_type' => 'writeoff',
                        'change_message' => '订单部分核销，核销成员:' . $staff_id . ',核销商品:' . implode(' ', $message),
                        'change_time' => time()
                    ]);
                } else {
                    $statusServices->save([
                        'oid' => $orderInfo['id'],
                        'change_type' => 'writeoff',
                        'change_message' => '订单核销已完成',
                        'change_time' => time()
                    ]);
                }
                if ($auth == 1 && $data['staff_id']) {
                    //流水关联店员
                    /** @var StoreFinanceFlowServices $storeFinanceFlow */
                    $storeFinanceFlow = app()->make(StoreFinanceFlowServices::class);
                    $storeFinanceFlow->setStaff($orderInfo['order_id'], $data['staff_id']);
                }
            });
        } else {//积分订单
            if ($orderInfo['status'] == 3) {
                throw new ValidateException('订单已核销');
            }
            $data = ['status' => 3];
            /** @var StoreIntegralOrderServices $storeIntegralOrderServices */
            $storeIntegralOrderServices = app()->make(StoreIntegralOrderServices::class);
            if (!$storeIntegralOrderServices->update($orderInfo['id'], $data)) {
                throw new ValidateException('Write off failure');
            }
            //增加收货订单状态
            /** @var StoreIntegralOrderStatusServices $statusService */
            $statusService = app()->make(StoreIntegralOrderStatusServices::class);
            $statusService->save([
                'oid' => $orderInfo['id'],
                'change_type' => 'take_delivery',
                'change_message' => '已收货',
                'change_time' => time()
            ]);
        }
        return $orderInfo;
    }

}
