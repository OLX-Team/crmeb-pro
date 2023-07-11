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

namespace app\services\order\cashier;


use app\dao\order\StoreHangOrderDao;
use app\services\BaseServices;
use app\services\order\StoreCartServices;
use app\services\order\StoreOrderServices;
use crmeb\traits\ServicesTrait;

/**
 * Class StoreHangOrderServices
 * @package app\services\order\cashier
 */
class StoreHangOrderServices extends BaseServices
{

    use ServicesTrait;

    /**
     * StoreHangOrderServices constructor.
     * @param StoreHangOrderDao $dao
     */
    public function __construct(StoreHangOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取挂单10个，历史下单20个
     * @param int $storeId
     * @param int $staffId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHangOrder(int $storeId, int $staffId)
    {
        /** @var StoreCartServices $service */
        $service = app()->make(StoreCartServices::class);
        $data = $service->getHangOrder($storeId, $staffId)->limit(10)->order('add_time desc')->select()->toArray();
        foreach ($data as &$item) {
            $item['is_check'] = 0;
        }
        $uid = $data ? array_column($data, 'uid') : [];
        /** @var StoreOrderServices $orderService */
        $orderService = app()->make(StoreOrderServices::class);
        $user = $orderService->getOrderHistoryList($storeId, $staffId, $uid, 20);
        foreach ($user as &$item) {
            $item['is_check'] = 1;
        }
        [$usec, $sec] = explode(" ", microtime());
        $msec = round($usec * 1000);
        $tourist = [[
            'is_check' => 1,
            'tourist_uid' => $msec,
            'uid' => 0,
            'nickname' => '',
            'avatar' => '',
            'staff_id' => $staffId,
            'store_id' => $storeId,
            'cart_id' => '',
            'add_time' => time()
        ]];
        return array_merge($data, $tourist, $user);
    }

    /**
     * 获取挂单区列表
     * @param int $storeId
     * @param int $staffId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHangOrderList(int $storeId, int $staffId, string $search)
    {
        [$page, $limit] = $this->getPageValue();
        /** @var StoreCartServices $service */
        $service = app()->make(StoreCartServices::class);
        $data = $service->getHangOrder($storeId, $staffId, $search, $page, $limit)->order('add_time desc')->select()->toArray();
        /** @var CashierOrderServices $make */
        $make = app()->make(CashierOrderServices::class);
        foreach ($data as &$item) {
            $item['id'] = $item['cart_id'];
            $item['_add_time'] = date('Y.m.d H:i:s', $item['add_time']);
            try {
                $computeOrder = $make->computeOrder($item['uid'], $storeId, explode(',', $item['cart_id']) ?: []);
                $item['price'] = $computeOrder['payPrice'];
            } catch (\Throwable $e) {
                $item['price'] = 0;
            }
        }
        $count = $service->getHangOrder($storeId, $staffId, $search)->count();
        return compact('data', 'count');
    }

    /**
     * 保存挂单
     * @param int $uid
     * @param array $cartIds
     * @param string $price
     * @param int $storeId
     * @param int $staffId
     * @param string $touristUid
     * @return \crmeb\basic\BaseModel|\think\Model
     */
    public function saveHang(int $uid, array $cartIds, string $price, int $storeId, int $staffId, string $touristUid = '')
    {
        $cartIds = implode(',', $cartIds);
        if ($uid && $this->dao->count(['uid' => $uid, 'store_id' => $storeId, 'staff_id' => $staffId])) {
            $this->dao->delete(['uid' => $uid, 'store_id' => $storeId, 'staff_id' => $staffId]);
        } else if ($touristUid && $this->dao->count(['tourist_uid' => $touristUid, 'store_id' => $storeId, 'staff_id' => $staffId])) {
            $this->dao->delete(['tourist_uid' => $touristUid, 'store_id' => $storeId, 'staff_id' => $staffId]);
        }
        return $this->dao->save([
            'uid' => $uid,
            'cart_ids' => $cartIds,
            'store_id' => $storeId,
            'staff_id' => $staffId,
            'tourist_uid' => $touristUid,
            'price' => $price
        ]);
    }
}
