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
use app\services\BaseServices;

/**
 * Class StoreOrderWapServices
 * @package app\services\order
 * @method getOne(array $where, ?string $field = '*') 获取一条数据
 */
class StoreOrderWapServices extends BaseServices
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
     * 获取 今日 昨日 本月 订单金额
     * @return mixed
     */
    public function getOrderTimeData(int $store_id = 0)
    {
        $where = ['pid' => 0, 'timeKey' => 'add_time', 'paid' => 1, 'is_del' => 0, 'is_system_del' => 0, 'refund_status' => [0, 3], 'store_id' => $store_id];
        //今日成交额
        $data['todayPrice'] = $this->dao->together($where + ['time' => 'today'], 'pay_price');
        //今日订单数
        $data['todayCount'] = $this->dao->count($where + ['time' => 'today']);
        //昨日成交额
        $data['proPrice'] = $this->dao->together($where + ['time' => 'yesterday'], 'pay_price');
        //昨日订单数
        $data['proCount'] = $this->dao->count($where + ['time' => 'yesterday']);
        //本月成交额
        $data['monthPrice'] = $this->dao->together($where + ['time' => 'month'], 'pay_price');
        //本月订单数
        $data['monthCount'] = $this->dao->count($where + ['time' => 'month']);
        return $data;
    }

    /**
     * 订单每月统计数据
     * @param array $where
     * @param int $store_id
     * @return array
     */
    public function getOrderDataPriceCount(array $where = [], int $store_id = 0)
    {
        [$page, $limit] = $this->getPageValue();
        return $this->dao->getOrderDataPriceCount($where + ['pid' => 0, 'is_del' => 0, 'paid' => 1, 'refund_status' => [0, 3], 'is_system_del' => 0, 'store_id' => $store_id], ['sum(pay_price) as price', 'count(id) as count', 'FROM_UNIXTIME(add_time, \'%m-%d\') as time'], $page, $limit);
    }

    /**
     * 获取手机端订单管理
     * @param array $where
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWapAdminOrderList(array $where, array $with = [])
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getOrderList($where, ['*'], $page, $limit, $with);
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

}
