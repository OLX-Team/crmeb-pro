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
declare (strict_types=1);

namespace app\services\user;

use app\dao\user\UserBrokerageDao;
use app\services\BaseServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderServices;
use think\Exception;
use think\exception\ValidateException;
use crmeb\services\CacheService;
use think\facade\Log;

/**
 * 用户佣金
 * Class UserBrokerageServices
 * @package app\services\user
 * @method takeUpdate(int $uid, int $id) 修改收货状态
 * @method sum(array $where, string $field) 求和
 * @method count(array $where) 求条数
 * @method getTotalSum(array $where) 计算某个条件下订单内商品总数
 * @method getBillSum(array $where) 获取某个条件总数
 * @method getUserFrozenPrice(int $uid) 获取某个用户冻结的佣金
 */
class UserBrokerageServices extends BaseServices
{

    /**
     * 用户记录模板
     * @var array[]
     */
    protected $incomeData = [
        'get_self_brokerage' => [
            'title' => '获得自购订单佣金',
            'type' => 'self_brokerage',
            'mark' => '您成功消费{%pay_price%}元,奖励自购佣金{%number%}',
            'status' => 1,
            'pm' => 1
        ],
        'get_brokerage' => [
            'title' => '获得下级推广订单佣金',
            'type' => 'one_brokerage',
            'mark' => '{%nickname%}成功消费{%pay_price%}元,奖励推广佣金{%number%}',
            'status' => 1,
            'pm' => 1
        ],
        'get_two_brokerage' => [
            'title' => '获得推广订单佣金',
            'type' => 'two_brokerage',
            'mark' => '二级推广人{%nickname%}成功消费{%pay_price%}元,奖励推广佣金{%number%}',
            'status' => 1,
            'pm' => 1
        ],
        'get_user_brokerage' => [
            'title' => '获得推广用户佣金',
            'type' => 'brokerage_user',
            'mark' => '成功推广用户：{%nickname%},奖励推广佣金{%number%}',
            'status' => 1,
            'pm' => 1
        ],
        'extract' => [
            'title' => '佣金提现',
            'type' => 'extract',
            'mark' => '{%mark%},佣金提现{%number%}元',
            'status' => 1,
            'pm' => 0
        ],
        'extract_fail' => [
            'title' => '提现失败',
            'type' => 'extract_fail',
            'mark' => '提现失败,退回佣金{%num%}元',
            'status' => 1,
            'pm' => 1
        ],
        'brokerage_to_nowMoney' => [
            'title' => '佣金提现到余额',
            'type' => 'extract_money',
            'mark' => '佣金提现到余额{%num%}元',
            'status' => 1,
            'pm' => 0
        ],
        'brokerage_refund' => [
            'title' => '退款退佣金',
            'type' => 'refund',
            'mark' => '订单退款扣除佣金{%num%}元',
            'status' => 1,
            'pm' => 0
        ],
    ];

    /**
     * UserBrokerageServices constructor.
     * @param UserBrokerageDao $dao
     */
    public function __construct(UserBrokerageDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     *  获取用户记录总和
     * @param $uid
     * @param string $category
     * @param array $type
     * @return mixed
     */
    public function getRecordCount(int $uid, $time = '', $pm = false)
    {
        $where = [];
        $where['uid'] = $uid;
        $where['status'] = 1;
        if ($time) {
            $where['time'] = $time;
        }
        if ($pm) {
            $where['pm'] = 0;
        }
        return $this->dao->getBrokerageSumColumn($where);
    }

    /**
     * 计算佣金
     * @param array $where
     * @param int $time
     * @return mixed
     */
    public function getUsersBokerageSum(array $where, $time = 0)
    {
        $where_data = [
            'status' => 1,
            'pm' => $where['pm'] ?? '',
            'uid' => $where['uid'] ?? '',
            'time' => $where['time'] ?? 0
        ];
        if ($time) $where_data['time'] = $time;
        return $this->dao->getBrokerageSumColumn($where_data);
    }

    /**
     * 某个用户佣金总和
     * @param int $uid
     * @param array|string[] $type
     * @param string $time
     * @return float
     */
    public function getUserBillBrokerageSum(int $uid, array $type = ['self_brokerage', 'one_brokerage', 'two_brokerage', 'brokerage_user'], $time = '')
    {
        $where = ['uid' => $uid];
        if ($type) $where['type'] = $type;
        if ($time) $where['time'] = $time;
        return $this->dao->getBrokerageSum($where);
    }


    /**
     * 获取用户|所有佣金总数
     * @param int $uid
     * @param array $where_time
     * @return float
     */
    public function getBrokerageCount(int $uid = 0, $where_time = [])
    {
        $where = ['category' => 'now_money', 'type' => ['system_add', 'pay_product', 'extract', 'pay_product_refund', 'system_sub'], 'pm' => 1, 'status' => 1];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        return $this->dao->getBillCount($where);
    }

    /**
     * 写入用户记录
     * @param string $type 写入类型
     * @param int $uid
     * @param int|string|array $number
     * @param int|string $balance
     * @param int $link_id
     * @return bool|mixed
     */
    public function income(string $type, int $uid, $number, $balance, $link_id)
    {
        $data = $this->incomeData[$type] ?? null;
        if (!$data) {
            return true;
        }
        $data['uid'] = $uid;
        $data['balance'] = $balance ?? 0;
        $data['link_id'] = $link_id;
        if (is_array($number)) {
            $key = array_keys($number);
            $key = array_map(function ($item) {
                return '{%' . $item . '%}';
            }, $key);
            $value = array_values($number);
            $data['number'] = $number['number'] ?? 0;
            $data['frozen_time'] = $number['frozen_time'] ?? 0;
            $data['mark'] = str_replace($key, $value, $data['mark']);
        } else {
            $data['number'] = $number;
            $data['mark'] = str_replace(['{%num%}'], $number, $data['mark']);
        }
        $data['add_time'] = time();
		if ((float)$data['number']) {
			return $this->dao->save($data);
		}
        return true;
    }


    /**
     * 资金类型
     */
    public function bill_type()
    {
        return CacheService::get('user_brokerage_type_list', function () {
            return ['list' => $this->dao->getBrokerageType([])];
        }, 600);
    }

    /**
     * 获取资金列表
     * @param array $where
     * @param string $field
     * @param int $limit
     * @return array
     */
    public function getBrokerageList(array $where, string $field = '*', int $limit = 0)
    {
        $where_data = [];
        if (isset($where['uid']) && $where['uid'] != '') {
            $where_data['uid'] = $where['uid'];
        }
        if ($where['start_time'] != '' && $where['end_time'] != '') {
            $where_data['time'] = str_replace('-', '/', $where['start_time']) . ' - ' . str_replace('-', '/', $where['end_time']);
        }
        if (isset($where['type']) && $where['type'] != '') {
            $where_data['type'] = $where['type'];
        }
        if (isset($where['nickname']) && $where['nickname'] != '') {
            $where_data['like'] = $where['nickname'];
        }
        if ($limit) {
            [$page] = $this->getPageValue();
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        $data = $this->dao->getBrokerageList($where_data, $field, $page, $limit);
        foreach ($data as &$item) {
            $item['nickname'] = $item['user']['nickname'] ?? '';
            $item['_add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
            unset($item['user']);
        }
        $count = $this->dao->count($where_data);
        return compact('data', 'count');
    }

    /**
     * 获取佣金列表
     * @param array $where
     * @param int $limit
     * @return array
     */
    public function getCommissionList(array $where, int $limit = 0)
    {
        $where_data = [];
        $where_data['time'] = $where['time'];
        if (isset($where['nickname']) && $where['nickname']) {
            $where_data[] = ['u.account|u.nickname|u.uid|u.phone', 'LIKE', "%$where[nickname]%"];
        }
        if (isset($where['price_max']) && isset($where['price_min'])) {
            if ($where['price_max'] != '' && $where['price_min'] != '') {
                $where_data[] = ['u.brokerage_price', 'between', [$where['price_min'], $where['price_max']]];
            } elseif ($where['price_min'] != '' && $where['price_max'] == '') {
                $where_data[] = ['u.brokerage_price', '>=', $where['price_min']];
            } elseif ($where['price_min'] == '' && $where['price_max'] != '') {
                $where_data[] = ['u.brokerage_price', '<=', $where['price_max']];
            }
        }
        $order_string = '';
        $order_arr = ['asc', 'desc'];
        if (isset($where['sum_number']) && in_array($where['sum_number'], $order_arr)) {
            $order_string .= ',income ' . $where['sum_number'];
        }
        if (isset($where['brokerage_price']) && in_array($where['brokerage_price'], $order_arr)) {
            $order_string .= ',u.brokerage_price ' . $where['brokerage_price'];
        }
        if ($order_string) {
            $order_string = trim($order_string, ',');
        }
        /** @var UserUserBrokerageServices $userUserBrokerage */
        $userUserBrokerage = app()->make(UserUserBrokerageServices::class);
        [$count, $list] = $userUserBrokerage->getBrokerageList($where_data, 'b.type,b.pm,sum(IF(b.pm = 1, b.number, 0)) as income,sum(IF(b.pm = 0, b.number, 0)) as pay,u.nickname,u.phone,u.uid,u.now_money,u.brokerage_price,u.delete_time,b.add_time as time', $order_string, $limit);
        $uids = array_unique(array_column($list, 'uid'));
        /** @var UserExtractServices $userExtract */
        $userExtract = app()->make(UserExtractServices::class);
        $extractSumList = $userExtract->getUsersSumList($uids);
        foreach ($list as &$item) {
//            $item['sum_number'] = $item['income'] > $item['pay'] ? bcsub($item['income'], $item['pay'], 2) : 0;
            $item['nickname'] = $item['nickname'] . "|" . ($item['phone'] ? $item['phone'] . "|" : '') . $item['uid'];
            $item['extract_price'] = $extractSumList[$item['uid']] ?? 0;
            $item['sum_number'] = bcadd((string)$item['extract_price'], (string)$item['brokerage_price'], 2);
            $item['time'] = $item['time'] ? date('Y-m-d H:i:s', $item['time']) : '';
        }
        return compact('count', 'list');
    }

    /**
     * 用户佣金详情
     * @param int $uid
     * @return array
     */
    public function user_info(int $uid)
    {
        /** @var UserServices $user */
        $user = app()->make(UserServices::class);
        $user_info = $user->getUserWithTrashedInfo($uid, 'nickname,spread_uid,now_money,brokerage_price,add_time');
        if (!$user_info) {
            throw new ValidateException('您查看的用户信息不存在!');
        }
        $user_info = $user_info->toArray();
        /** @var UserExtractServices $userExtract */
        $userExtract = app()->make(UserExtractServices::class);
        $extract_price = $userExtract->getUserExtract($uid);
        $user_info['number'] = bcadd((string)$extract_price, (string)$user_info['brokerage_price'], 2);
        $user_info['add_time'] = date('Y-m-d H:i:s', $user_info['add_time']);
        $user_info['spread_name'] = $user_info['spread_uid'] ? $user->getUserInfo((int)$user_info['spread_uid'], 'nickname', true)['nickname'] ?? '' : '';
        return compact('user_info');
    }


    /**
     * 退佣金
     * @param $order
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderRefundBrokerageBack($order)
    {
        $id = (int)$order['id'];
        $where = [
            'uid' => [$order['spread_uid'], $order['spread_two_uid']],
            'type' => ['self_brokerage', 'one_brokerage', 'two_brokerage'],
            'link_id' => $id,
            'pm' => 1
        ];
        $brokerageList = $this->dao->getUserBrokerageList($where);
        //子订单
        if (!$brokerageList && $order['pid']) {
            $where['link_id'] = $order['pid'];
            $p_brokerageList = $this->dao->getUserBrokerageList($where);
            //主订单已分佣 子订单按订单拆分后计算结果回退
            if ($p_brokerageList) {
                $brokerageList = [
                    ['uid' => $order['spread_uid'], 'number' => $order['one_brokerage']],
                    ['uid' => $order['spread_two_uid'], 'number' => $order['two_brokerage']],
                ];
            }
        }
        $res = true;
        if ($brokerageList) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $brokerages = $userServices->getColumn([['uid', 'in', array_column($brokerageList, 'uid')]], 'brokerage_price', 'uid');
            $brokerageData = [];

            foreach ($brokerageList as $item) {
                if (!$item['uid']) continue;
                $usermoney = $brokerages[$item['uid']] ?? 0;
                if ($item['number'] > $usermoney) {
                    $item['number'] = $usermoney;
                }
                $res = $res && $userServices->bcDec($item['uid'], 'brokerage_price', (string)$item['number'], 'uid');
                $brokerageData[] = [
                    'title' => '退款退佣金',
                    'uid' => $item['uid'],
                    'pm' => 0,
                    'add_time' => time(),
                    'type' => 'refund',
                    'number' => $item['number'],
                    'link_id' => $id,
                    'balance' => bcsub((string)$usermoney, (string)$item['number'], 2),
                    'mark' => '订单退款扣除佣金' . floatval($item['number']) . '元'
                ];
            }
            if ($brokerageData) {
                $res = $res && $this->dao->saveAll($brokerageData);
            }
            //修改佣金冻结时间
            $this->dao->update($where, ['frozen_time' => 0]);
        }
        return $res;
    }

    /**
     * 佣金排行
     * @param string $time
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function brokerageRankList(string $time = 'week')
    {
        $where = [];
        if ($time) {
            $where['time'] = $time;
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->brokerageRankList($where, $page, $limit);
        foreach ($list as $key => &$item) {
            if (!isset($item['user']) || !$item['user'] || $item['brokerage_price'] <= 0) {
                unset($list[$key]);
                continue;
            }
            $item['nickname'] = $item['user']['nickname'] ?? '';
            $item['avatar'] = $item['user']['avatar'] ?? '';
            if ($item['brokerage_price'] == '0.00' || $item['brokerage_price'] == 0 || !$item['brokerage_price']) {
                unset($list[$key]);
            }
            unset($item['user']);
        }
        return array_merge($list);
    }

    /**
     * 获取用户排名
     * @param int $uid
     * @param string $time
     */
    public function getUserBrokerageRank(int $uid, string $time = 'week')
    {
        $where = [];
        if ($time) {
            $where['time'] = $time;
        }
        $list = $this->dao->brokerageRankList($where);
        foreach ($list as $key => &$item) {
            if (!isset($item['user']) || !$item['user'] || $item['brokerage_price'] <= 0) {
                unset($list[$key]);
            }
        }
        $position_tmp_one = array_column($list, 'uid');
        $position_tmp_two = array_column($list, 'brokerage_price', 'uid');
        if (!in_array($uid, $position_tmp_one)) {
            $position = 0;
        } else {
            if ($position_tmp_two[$uid] == 0.00) {
                $position = 0;
            } else {
                $position = array_search($uid, $position_tmp_one) + 1;
            }
        }
        return $position;
    }


    /**
     * 推广数据    昨天的佣金   累计提现金额  当前佣金
     * @param int $uid
     * @return mixed
     */
    public function commission(int $uid)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if (!$userServices->userExist($uid)) {
            throw new ValidateException('数据不存在');
        }
        /** @var UserExtractServices $userExtract */
        $userExtract = app()->make(UserExtractServices::class);
        $data = [];
        $data['uid'] = $uid;
        $data['pm'] = 1;
        $data['commissionSum'] = $this->getUsersBokerageSum($data);
        $data['pm'] = 0;
        $data['commissionRefund'] = $this->getUsersBokerageSum($data);
        $data['commissionCount'] = $data['commissionSum'] > $data['commissionRefund'] ? bcsub((string)$data['commissionSum'], (string)$data['commissionRefund'], 2) : 0.00;
        $data['lastDayCount'] = $this->getUsersBokerageSum($data, 'yesterday');//昨天的佣金
        $data['extractCount'] = $userExtract->getUserExtract($uid);//累计提现金额

        return $data;
    }

    /**
     * 前端佣金排行页面数据
     * @param int $uid
     * @param $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function brokerage_rank(int $uid, $type)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        if (!$userService->userExist($uid)) {
            throw new ValidateException('数据不存在');
        }
        return [
            'rank' => $this->brokerageRankList($type),
            'position' => $this->getUserBrokerageRank($uid, $type)
        ];
    }

    /**
     * 推广 佣金/提现 总和
     * @param int $uid
     * @param $type 3 佣金  4 提现
     * @return mixed
     */
    public function spread_count(int $uid, $type)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        if (!$userService->userExist($uid)) {
            throw new ValidateException('数据不存在');
        }
        $count = 0;
        if ($type == 3) {
            $where = [
                'uid' => $uid,
                'status' => 1,
                'pm' => 1
            ];
            $count1 = $this->dao->getBrokerageSumColumn($where);
            $where['pm'] = 0;
            $count2 = $this->dao->getBrokerageSumColumn($where);
            $count = $count1 - $count2;
        } else if ($type == 4) {
            /** @var UserExtractServices $userExtract */
            $userExtract = app()->make(UserExtractServices::class);
            $count = $userExtract->getUserExtract($uid);//累计提现
        }
        return $count ? $count : 0;
    }

    /**
     * 推广订单
     * @param Request $request
     * @return mixed
     */
    public function spread_order(int $uid, array $data)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        if (!$userService->userExist($uid)) {
            throw new ValidateException('数据不存在');
        }
        $result = ['list' => [], 'time' => [], 'count' => 0];
        /** @var StoreOrderServices $storeOrderServices */
        $storeOrderServices = app()->make(StoreOrderServices::class);
        [$page, $limit] = $this->getPageValue();
        $time_data = [];
        $where = ['type' => 0, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0, 'spread_or_uid' => $uid];
        if ($data['start'] || $data['stop']) {
            $where['time'] = [$data['start'], $data['stop']];
        }
        $where['real_name'] = $data['keyword'];
        $list = $storeOrderServices->getList($where, ['id,order_id,uid,add_time,spread_uid,status,spread_two_uid,one_brokerage,two_brokerage,pay_price,cart_id'], $page, $limit, ['brokerage' => function ($query) use ($uid) {
            $query->where('uid', $uid);
        }]);
        $result['count'] = $storeOrderServices->count($where);
        if ($list) {
            /** @var StoreOrderCartInfoServices $cartInfoServices */
            $cartInfoServices = app()->make(StoreOrderCartInfoServices::class);
            $uids = array_unique(array_column($list, 'uid'));
            $userInfos = $userService->getColumn([['uid', 'in', $uids]], 'uid,avatar,nickname', 'uid');
            foreach ($list as &$item) {
                $item['store_name'] = $cartInfoServices->getCarIdByProductTitle((int)$item['id'], $item['cart_id']);
                $item['avatar'] = $userInfos[$item['uid']]['avatar'] ?? '';
                $item['nickname'] = $userInfos[$item['uid']]['nickname'] ?? '';
                $item['number'] = $item['spread_uid'] == $uid ? $item['one_brokerage'] : $item['two_brokerage'];
                $item['time_key'] = $item['add_time'] ? date('Y-m', $item['add_time']) : '';
                $item['type'] = in_array($item['status'], [2, 3]) ? 'brokerage' : 'number';
				$brokerage = $item['brokerage'];
				$time = $brokerage['add_time'] ?? $item['add_time'];
				$item['time'] = $time ? date('Y-m-d H:i', $time) : '';
                $item['is_frozen'] = $brokerage && $brokerage['frozen_time'] > time() ? 1 : 0;
				unset($item['brokerage']);
            }
            $times = array_unique(array_column($list, 'time_key'));
            $time_data = [];
            $i = 0;
            foreach ($times as $time) {
                $time_data[$i]['time'] = $time;
                $time_data[$i]['count'] = $storeOrderServices->getMonthCount($where, $time);
                $time_data[$i]['sumPrice'] = $storeOrderServices->getMonthMoneyCount($where, $time, 'pay_price');
                $i++;
            }
        }
        $result['list'] = $list;
        $result['time'] = $time_data;

        $priceWhere = ['paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0];
        if ($data['start'] || $data['stop']) {
            $priceWhere['time'] = [$data['start'], $data['stop']];
        }
        //条件获取一级佣金总和
        $sum_price_one = $storeOrderServices->sum($priceWhere + ['spread_uid' => $uid], 'one_brokerage', true);
        $sum_price_two = $storeOrderServices->sum($priceWhere + ['spread_two_uid' => $uid], 'two_brokerage', true);
        $result['sum_brokerage'] = bcadd((string)$sum_price_one, (string)$sum_price_two, 2);


        return $result;
    }

    /**
     * 用户佣金记录v2
     * @param int $uid
     * @return array
     */
    public function userBrokerageList(int $uid, $data = [])
    {
        $where = [];
        $where['uid'] = $uid;
        if ($data['start'] || $data['stop']) {
            $where['time'] = [$data['start'], $data['stop']];
        }
        $where['like'] = $data['keyword'];
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, '*', $page, $limit);
        $times = [];
        if ($list) {
            foreach ($list as &$item) {
                $item['time_key'] = $item['add_time'] ? date('Y-m', (int)$item['add_time']) : '';
                $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i', (int)$item['add_time']) : '';
            }
            $times = array_merge(array_unique(array_column($list, 'time_key')));
        }
        $income = $this->dao->sum($where + ['pm' => 1, 'not_type' => ['extract_fail']], 'number', true);
        $expend = $this->dao->sum($where + ['pm' => 0], 'number', true);
        return ['list' => $list, 'time' => $times, 'income' => $income, 'expend' => $expend];
    }

    /**
     * 用户提现记录v2
     * @param int $uid
     * @param array $data
     * @return array
     */
    public function userExtractList(int $uid, array $data)
    {
        $where = [];
        $where['uid'] = $uid;
        $where['type'] = ['extract', 'extract_money', 'extract_fail'];
        if ((isset($data['start']) && $data['start']) || (isset($data['stop']) && $data['stop'])) {
            $where['time'] = [$data['start'], $data['stop']];
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, '*', $page, $limit);
        $times = [];
        if ($list) {
            foreach ($list as &$item) {
                $item['time_key'] = $item['add_time'] ? date('Y-m', (int)$item['add_time']) : '';
                $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i', (int)$item['add_time']) : '';
            }
            $times = array_merge(array_unique(array_column($list, 'time_key')));
        }
        return ['list' => $list, 'time' => $times];
    }
}
