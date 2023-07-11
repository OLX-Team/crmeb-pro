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
namespace app\controller\api\store;

use app\Request;
use app\services\order\store\BranchOrderServices;
use app\services\store\DeliveryServiceServices;
use app\services\store\SystemStoreServices;
use app\services\store\SystemStoreStaffServices;
use app\services\user\UserServices;


/**
 * 配送员
 * Class StoreDeliveryController
 * @package app\controller\store\staff
 */
class StoreDeliveryController
{
    /**
     * @var DeliveryServiceServices
     */
    protected $services;

    /**
     * StoreDeliveryController constructor.
     * @param DeliveryServiceServices $services
     */
    public function __construct(DeliveryServiceServices $services)
    {
        $this->services = $services;
    }

    /**
     * 获取配送员信息
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function info(Request $request, SystemStoreServices $storeServices, UserServices $userServices)
    {
        $uid = (int)$request->uid();
        $deliveryInfo = $this->services->getDeliveryInfoByUid($uid)->toArray();
        $store_ids = $this->services->getDeliveryStoreIds($uid, 'store_id');
        $store_info = [];
        if ($store_ids) {
            $store_info = $storeServices->getColumn([['id', 'in', $store_ids], ['is_show', '=', 1], ['is_del', '=', 0]], 'id,name');
        }
        $deliveryInfo['store_info'] = $store_info;
        $deliveryInfo['user_nickname'] = $userServices->value(['uid' => $uid], 'nickname');
        return app('json')->success($deliveryInfo);
    }

    /**
     * 获取所有配送员列表
     * @param DeliveryServiceServices $services
     * @return mixed
     */
    public function getDeliveryAll(Request $request, SystemStoreStaffServices $staffServices)
    {
        $uid = (int)$request->uid();
        $staffInfo = $staffServices->getStaffInfoByUid($uid);
        $list = $this->services->getDeliveryList(2, $staffInfo['store_id']);
        return app('json')->success($list['list']);
    }


    /**
     * 配送员数据统计
     * @param Request $request
     * @return mixed
     */
    public function statistics(Request $request)
    {
        [$store_id, $time] = $request->getMore([
            ['store_id', 0],
            ['data', '', '', 'time'],
        ], true);
        $uid = (int)$request->uid();
        $data = $this->services->getStoreData($uid, $store_id, $time);
        return app('json')->successful($data);
    }

    /**
     * 配送统计列表数据
     * @param Request $request
     * @return mixed
     */
    public function data(Request $request, BranchOrderServices $orderServices)
    {
        [$store_id, $time] = $request->getMore([
            ['store_id', 0],
            ['data', '', '', 'time'],
        ], true);
        $uid = (int)$request->uid();
        $this->services->getDeliveryInfoByUid($uid, $store_id);
        $where = ['delivery_uid' => $uid, 'time' => $time];
        if ($store_id) {
            $where['store_id'] = $store_id;
        }
        $data = $orderServices->getOrderDataPriceCount($where);
        return app('json')->successful($data);
    }


    /**
     * 配送订单列表
     * @param Request $request
     * @return mixed
     */
    public function orderList(Request $request, BranchOrderServices $orderServices)
    {
        [$type] = $request->getMore([
            ['type', 1]
        ], true);
        $uid = (int)$request->uid();
        $where = ['delivery_uid' => $uid, 'status' => 9, 'is_del' => 0, 'is_system_del' => 0, 'paid' => 1, 'refund_status' => [0, 3]];
        if ($type == 1) {
            $where['status'] = 2;
        }
        $list = $orderServices->getStoreOrderList($where, ['*'], ['pink', 'store' => function ($query) {
            $query->field('id,name,address,detailed_address');
        }]);
        $data = [];
        $where['status'] = 2;
        $data['unsend'] = $orderServices->count($where);
        $where['status'] = 9;
        $data['send'] = $orderServices->count($where);
        return app('json')->successful(compact('data', 'list'));
    }


}
