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

use app\dao\order\StoreOrderPromotionsDao;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\BaseServices;
use crmeb\services\CacheService;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;

/**
 * Class StoreOrderPromotionsServices
 * @package app\services\order
 */
class StoreOrderPromotionsServices extends BaseServices
{

    use ServicesTrait;

    /**
     * StoreOrderPromotionsServices constructor.
     * @param StoreOrderPromotionsDao $dao
     */
    public function __construct(StoreOrderPromotionsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存订单优惠详情
     * @param $oid
     * @param array $cartInfo
     * @param $uid
     * @return int
     */
    public function setPromotionsDetail(int $uid, int $oid, array $cartList, array $promotionsList)
    {
        $group = [];
        if ($promotionsList) {
			$time = time();
            foreach ($cartList as $key => $cart) {
                foreach ($promotionsList as $promotions) {
                    $details = $promotions['details'] ?? [];
                    $unique = $cart['product_attr_unique'] ?? '';
                    if ($details && isset($details[$unique]['promotions_true_price'])) {
                        $group[] = [
                            'oid' => $oid,
                            'uid' => $uid,
                            'product_id' => $cart['productInfo']['id'],
                            'promotions_id' => $promotions['id'],
                            'promotions_price' => bcmul((string)($details[$unique]['promotions_true_price'] ?? 0), (string)$cart['cart_num'], 2),
                            'add_time' => $time
                        ];
                    }
                }
            }
        }
        if ($group) {
            return $this->dao->saveAll($group);
        }
        return true;
    }

    /**
     * 获取订单商品实际参与优惠活动 以及优惠金额
     * @param int $oid
     * @return array
     */ 
    public function getOrderPromotionsDetail(int $oid) 
    {
        $result = $this->dao->getPromotionsDetailList(['oid' => $oid], '*,sum(`promotions_price`) as promotions_price', ['promotions' => function($query) {
            $query->field('id,name,title,desc')->bind([
                'promotions_type' => 'promotions_type',
                'name' => 'name', 
                'title' => 'title',
                'desc' => 'desc'
            ]);
        }], 'promotions_id');
        if ($result) {
            $typeArr = array_column($result, 'promotions_type');
            array_multisort($typeArr, SORT_ASC, $result);
        }
        return $result;
    }

    /**
     * 拆分订单同步拆分优惠活动记录
     * @param int $oid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function splitOrderPromotions(int $oid)
    {
        /** @var StoreOrderServices $storeOrderServices */
        $storeOrderServices = app()->make(StoreOrderServices::class);
        $orderInfo = $storeOrderServices->getOne(['id' => $oid, 'is_del' => 0]);
        if (!$orderInfo) {
            throw new ValidateException('订单不存在');
        }
        $promotions_give = [];
        $promotions = [];
        if (isset($orderInfo['promotions_give']) && $orderInfo['promotions_give']) {
            $promotions_give = is_string($orderInfo['promotions_give']) ? json_decode($promotions_give, true) : $orderInfo['promotions_give'];
        }
        $promotions = $promotions_give['promotions'] ?? [];
        $pid = $orderInfo['pid'] > 0 ? $orderInfo['pid'] : $orderInfo['id'];
        //查询优惠记录
        $orderPromotions = $this->dao->getPromotionsDetailList(['order_id' => $oid]);
        //查询子订单
        $spliteOrder = $storeOrderServices->getColumn(['pid' => $pid, 'is_system_del' => 0], 'id,order_id');
        if ($spliteOrder && $orderPromotions && $promotions) {
            /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
            $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
            $data_all = $data = [];
            $data['uid'] = $orderInfo['uid'];
			$data['add_time'] = time();
            foreach ($spliteOrder as $order) {
                $cartInfo = $storeOrderCartInfoServices->getColumn(['oid' => $order['id']], 'id,product_id,cart_num,cart_info');
                if (!$cartInfo) {
                    continue;
                }
                $data['oid'] = $order['id'];
                foreach ($cartInfo as $key => $cart) {
                    $data['product_id'] = $cart['product_id'];
                    $_info = is_string($cart['cart_info']) ? json_decode($cart['cart_info'], true) : $cart['cart_info'];
                    $unique = $_info['product_attr_unique'] ?? '';
                    foreach ($promotions as $key => $info) {
                       if (isset($info['details'][$unique])) {
                           $data['promotions_id'] = $info['id'];
                           $data['promotions_price'] = floatval(bcmul((string)($info['details'][$unique]['promotions_true_price'] ?? 0), (string)$cart['cart_num'], 2));
                           $data_all[] = $data;
                       }
                    }
                }
            }
            if ($data_all) {
                $this->transaction(function () use ($data_all, $spliteOrder) {
                    $this->dao->delete(['oid' => array_column($spliteOrder, 'id')]);
                    $this->dao->saveAll($data_all);
                    
                });
            }
        }
        return true;
    }


    /**
     * 申请退款订单优惠详情处理
     * @param int $oid
     * @param array $cartInfo
     * @return bool
     */ 
    public function applyRefundOrderPromotions(int $oid, array $cartInfo = [])
    {
        /** @var StoreOrderServices $storeOrderServices */
        $storeOrderServices = app()->make(StoreOrderServices::class);
        $orderInfo = $storeOrderServices->getOne(['id' => $oid, 'is_del' => 0]);
        if (!$orderInfo) {
            return [];
        }
        $promotions_give = [];
        $promotions = [];
        if (isset($orderInfo['promotions_give']) && $orderInfo['promotions_give']) {
            $promotions_give = is_string($orderInfo['promotions_give']) ? json_decode($promotions_give, true) : $orderInfo['promotions_give'];
        }
        $promotions = $promotions_give['promotions'] ?? [];
        if (!$cartInfo || !$promotions) {
            return [];
        }
        $data_all = [];
        foreach ($promotions as $key => $info) {
            $data = [];
            foreach ($cartInfo as $key => $cart) {
               if (isset($info['details'][$cart['product_id']])) {
                   $data['promotions_id'] = $info['id'];
                   $data['promotions_type'] = $info['promotions_type'];
                   $data['name'] = $info['name'];
                   $data['title'] = $info['title'];
                   $data['desc'] = $info['desc'];
                   $data['promotions_price'] = bcadd((string)($data['promotions_price'] ?? 0), (string)bcmul((string)($info['details'][$cart['product_id']]['promotions_true_price'] ?? 0), (string)$cart['cart_num'], 2), 2);
               }
            }
            if ($data) {
                $data_all[] = $data;
            }
        }
        if ($data_all) {
            $typeArr = array_column($data_all, 'promotions_type');
            array_multisort($typeArr, SORT_ASC, $data_all);
        }
        return $data_all;
    }

}
