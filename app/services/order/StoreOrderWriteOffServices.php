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
use app\services\activity\integral\StoreIntegralOrderServices;
use app\services\activity\integral\StoreIntegralOrderStatusServices;
use app\services\activity\combination\StorePinkServices;
use app\services\BaseServices;
use app\services\store\SystemStoreStaffServices;
use app\services\user\UserServices;
use think\exception\ValidateException;

/**
 * 核销订单
 * Class StoreOrderWriteOffServices
 * @package app\sservices\order
 */
class StoreOrderWriteOffServices extends BaseServices
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
     * 订单核销
     * @param string $code
     * @param int $confirm
     * @param int $uid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function writeOffOrder(string $code, int $confirm, int $uid = 0)
    {
        //订单
        $orderInfo = $this->dao->getOne(['verify_code' => $code, 'paid' => 1, 'refund_status' => 0, 'is_del' => 0], '*', ['pink']);
        $order_type = 'order';
        if (!$orderInfo) {
            //积分兑换订单
            /** @var StoreIntegralOrderServices $storeIntegralOrderServices */
            $storeIntegralOrderServices = app()->make(StoreIntegralOrderServices::class);
            $orderInfo = $storeIntegralOrderServices->getOne(['verify_code' => $code]);
            $order_type = 'integral';
        }
        if (!$orderInfo) {
            throw new ValidateException('Write off order does not exist');
        }
        if (!$orderInfo['verify_code'] || ($orderInfo->shipping_type != 2 && $orderInfo->delivery_type != 'send')) {
            throw new ValidateException('此订单不能被核销');
        }
        if ($uid) {
            $isAuth = true;
            switch ($orderInfo['shipping_type']) {
                case 1://配送订单
                    /** @var DeliveryServiceServices $deliverServiceServices */
                    $deliverServiceServices = app()->make(DeliveryServiceServices::class);
                    $isAuth = $deliverServiceServices->getCount(['uid' => $uid, 'status' => 1]) > 0 ? true : false;
                    break;
                case 2://自提订单
                    /** @var SystemStoreStaffServices $storeStaffServices */
                    $storeStaffServices = app()->make(SystemStoreStaffServices::class);
                    $isAuth = $storeStaffServices->getCount(['uid' => $uid, 'verify_status' => 1, 'status' => 1]) > 0 ? true : false;
                    break;
            }
            if (!$isAuth) {
                throw new ValidateException('您无权限核销此订单，请联系管理员');
            }
        }
        $orderInfo['order_type'] = $order_type;
        if ($order_type == 'order') {
            if ($orderInfo->status == 2) {
                throw new ValidateException('订单已核销');
            }
            if (isset($orderInfo['pinkStatus']) && $orderInfo['pinkStatus'] != 2) {
                throw new ValidateException('拼团未完成暂不能发货!');
            }
            /** @var StoreOrderCartInfoServices $orderCartInfo */
            $orderCartInfo = app()->make(StoreOrderCartInfoServices::class);
            $cartInfo = $orderCartInfo->getOne([
                ['cart_id', '=', $orderInfo['cart_id'][0]]
            ], 'cart_info');
            if ($cartInfo) $orderInfo['image'] = $cartInfo['cart_info']['productInfo']['image'];
            if ($orderInfo->shipping_type == 2) {
                if ($orderInfo->status > 0) {
                    throw new ValidateException('Order written off');
                }
            }
            if ($orderInfo['type'] == 3 && $orderInfo['activity_id'] && $orderInfo['pink_id']) {
                /** @var StorePinkServices $services */
                $services = app()->make(StorePinkServices::class);
                $res = $services->getCount([['id', '=', $orderInfo->pink_id], ['status', '<>', 2]]);
                if ($res) throw new ValidateException('Failed to write off the group order');
            }
            if ($confirm == 0) {
                /** @var UserServices $services */
                $services = app()->make(UserServices::class);
                $orderInfo['nickname'] = $services->value(['uid' => $orderInfo['uid']], 'nickname');
                return $orderInfo->toArray();
            }
            $orderInfo->status = 2;
            if ($uid) {
                if ($orderInfo->shipping_type == 2) {
                    $orderInfo->clerk_id = $uid;
                }
            }
            if ($orderInfo->save()) {
                /** @var StoreOrderTakeServices $storeOrdeTask */
                $storeOrdeTask = app()->make(StoreOrderTakeServices::class);
                $re = $storeOrdeTask->storeProductOrderUserTakeDelivery($orderInfo);
                if (!$re) {
                    throw new ValidateException('Write off failure');
                }
                //修改订单商品信息
                $cartData = ['writeoff_time' => time()];
                $cartData['is_writeoff'] = 1;
                $cartData['surplus_num'] = 0;
                $orderCartInfo->update(['oid' => $orderInfo['id']], $cartData);
                return $orderInfo->toArray();
            } else {
                throw new ValidateException('Write off failure');
            }
        } else {
            if ($orderInfo['status'] == 3) {
                throw new ValidateException('订单已核销');
            }
            if ($confirm == 0) {
                /** @var UserServices $services */
                $services = app()->make(UserServices::class);
                $orderInfo['nickname'] = $services->value(['uid' => $orderInfo['uid']], 'nickname');
                return $orderInfo->toArray();
            }
            if (!$storeIntegralOrderServices->update($orderInfo['id'], ['status' => 3])) {
                throw new ValidateException('Write off failure');
            } else {
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
            return $orderInfo->toArray();
        }

    }
}
