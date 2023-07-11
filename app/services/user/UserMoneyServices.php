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

use app\dao\user\UserMoneyDao;
use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use think\Exception;
use think\exception\ValidateException;
use crmeb\services\CacheService;
use think\facade\Log;

/**
 * 用户余额
 * Class UserMoneyServices
 * @package app\services\user
 * @method takeUpdate(int $uid, int $id) 修改收货状态
 * @method sum(array $where, string $field) 求和
 * @method count(array $where) 求条数
 * @method getTotalSum(array $where) 计算某个条件下订单内商品总数
 * @method getBillSum(array $where) 获取某个条件总数
 */
class UserMoneyServices extends BaseServices
{

    /**
     * 用户记录模板
     * @var array[]
     */
    protected $incomeData = [
        'pay_product' => [
            'title' => '余额支付购买商品',
            'type' => 'pay_product',
            'mark' => '余额支付{%num%}元购买商品',
            'status' => 1,
            'pm' => 0
        ],
        'pay_product_refund' => [
            'title' => '商品退款',
            'type' => 'pay_product_refund',
            'mark' => '订单余额退款{%num%}元',
            'status' => 1,
            'pm' => 1
        ],
        'system_add' => [
            'title' => '系统增加余额',
            'type' => 'system_add',
            'mark' => '系统增加{%num%}余额',
            'status' => 1,
            'pm' => 1
        ],
        'system_sub' => [
            'title' => '系统减少余额',
            'type' => 'system_sub',
            'mark' => '系统扣除了{%num%}余额',
            'status' => 1,
            'pm' => 0
        ],
        'user_recharge' => [
            'title' => '用户充值余额',
            'type' => 'recharge',
            'mark' => '成功充值余额{%price%}元,赠送{%give_price%}元',
            'status' => 1,
            'pm' => 1
        ],
        'user_recharge_refund' => [
            'title' => '用户充值退款',
            'type' => 'recharge_refund',
            'mark' => '退款扣除用户余额{%num%}元',
            'status' => 1,
            'pm' => 0
        ],
        'brokerage_to_nowMoney' => [
            'title' => '佣金提现到余额',
            'type' => 'extract',
            'mark' => '佣金提现到余额{%num%}元',
            'status' => 1,
            'pm' => 1
        ],
        'lottery_use_money' => [
            'title' => '参与抽奖使用余额',
            'type' => 'lottery_use',
            'mark' => '参与抽奖使用{%num%}余额',
            'status' => 1,
            'pm' => 0
        ],
        'lottery_give_money' => [
            'title' => '抽奖中奖赠送余额',
            'type' => 'lottery_add',
            'mark' => '抽奖中奖赠送{%num%}余额',
            'status' => 1,
            'pm' => 1
        ],
    ];

    /**
     * UserMoneyServices constructor.
     * @param UserMoneyDao $dao
     */
    public function __construct(UserMoneyDao $dao)
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
    public function getRecordCount(int $uid, $category = 'now_money', $type = [], $time = '', $pm = false)
    {

        $where = [];
        $where['uid'] = $uid;
        $where['category'] = $category;
        $where['status'] = 1;

        if (is_string($type) && strlen(trim($type))) {
            $where['type'] = explode(',', $type);
        }
        if ($time) {
            $where['time'] = $time;
        }
        if ($pm) {
            $where['pm'] = 0;
        }
        return $this->dao->getBillSumColumn($where);
    }

    /**
     * 获取资金列表
     * @param array $where
     * @param string $field
     * @param int $limit
     * @return array
     */
    public function getMoneyList(array $where, string $field = '*', int $limit = 0)
    {
        $where_data = [];
        if (isset($where['uid']) && $where['uid'] != '') {
            $where_data['uid'] = $where['uid'];
        }
        if ($where['start_time'] != '' && $where['end_time'] != '') {
            $where_data['time'] = str_replace('-', '/', $where['start_time']) . ' - ' . str_replace('-', '/', $where['end_time']);
        }
        if (isset($where['category']) && $where['category'] != '') {
            $where_data['category'] = $where['category'];
        }
        if (isset($where['type']) && $where['type'] != '') {
            $where_data['type'] = $where['type'];
        }
        $where_data['not_category'] = ['integral', 'exp', 'share'];
        $where_data['not_type'] = ['gain', 'system_sub', 'deduction', 'sign'];
        if (isset($where['nickname']) && $where['nickname'] != '') {
            $where_data['like'] = $where['nickname'];
        }
        if (isset($where['excel']) && $where['excel'] != '') {
            $where_data['excel'] = $where['excel'];
        } else {
            $where_data['excel'] = 0;
        }
        if ($limit) {
            [$page] = $this->getPageValue();
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        $data = $this->dao->getList($where_data, $field, $page, $limit, [
            'user' => function ($query) {
                $query->field('uid,nickname');
            }]);
        foreach ($data as &$item) {
            $item['nickname'] = $item['user']['nickname'] ?? '';
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
            unset($item['user']);
        }
        $count = $this->dao->count($where_data);
        return compact('data', 'count');
    }

    /**
     * 用户|所有资金变动列表
     * @param int $uid
     * @param array $where_time
     * @param string $field
     * @return array
     */
    public function getUserMoneyList(int $uid = 0, $where_time = [], string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $where = [];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        foreach ($list as &$item) {
            $value = array_filter($this->incomeData, function ($value) use ($item) {
                if ($item['type'] == $value['type']) {
                    return $item['title'];
                }
            });
            $item['type_title'] = $value[$item['type']]['title'] ?? '未知类型';
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
        }
        return compact('list', 'count');
    }

    /**
     * 获取用户的充值总数
     * @param int $uid
     * @return float
     */
    public function getRechargeSum(int $uid = 0, $time = [])
    {
        $where = ['uid' => $uid, 'pm' => 1, 'status' => 1];
        if ($time) $where['add_time'] = $time;
        return $this->dao->sum($where, 'number', true);
    }

    /**
     * 用户|所有充值列表
     * @param int $uid
     * @param string $field
     * @return array
     */
    public function getRechargeList(int $uid = 0, $where_time = [], string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $where = ['category' => 'now_money', 'type' => 'recharge'];
        if ($uid) $where['uid'] = $uid;
        if ($where_time) $where['add_time'] = $where_time;
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
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
     * @return bool|mixed|null
     */
    public function bill_type()
    {
        return CacheService::get('user_money_type_list', function () {
            return ['list' => $this->dao->getMoneyType([])];
        }, 600);
    }

    /**
     * 用户余额记录列表
     * @param int $uid
     * @param int $type
     * @param array $data
     * @return array
     */
    public function userMoneyList(int $uid, int $type, array $data = [])
    {
        $where = [];
        $where['uid'] = $uid;
        switch ($type) {
            case 1:
                $where['pm'] = 0;
                break;
            case 2:
                $where['pm'] = 1;
                break;
            case 0:
            default:
                break;
        }
        [$page, $limit] = $this->getPageValue();
        if ((isset($data['start']) && $data['start']) || (isset($data['stop']) && $data['stop'])) {
            $where['time'] = [$data['start'], $data['stop']];
        }
        $list = $this->dao->getList($where, '*', $page, $limit);
        $count = $this->dao->count($where);
        $times = [];
        if ($list) {
            foreach ($list as &$item) {
                $item['time_key'] = $item['time'] = $item['add_time'] ? date('Y-m', (int)$item['add_time']) : '';
                $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i', (int)$item['add_time']) : '';
            }
            $times = array_merge(array_unique(array_column($list, 'time_key')));
        }

        return ['count' => $count, 'list' => $list, 'time' => $times];
    }

    /**
     * 根据查询用户充值金额
     * @param array $where
     * @param string $rechargeSumField
     * @param string $selectType
     * @param string $group
     * @return float|mixed
     */
    public function getRechargeMoneyByWhere(array $where, string $rechargeSumField, string $selectType, string $group = "")
    {
        switch ($selectType) {
            case "sum" :
                return $this->dao->getWhereSumField($where, $rechargeSumField);
            case "group" :
                return $this->dao->getGroupField($where, $rechargeSumField, $group);
        }
    }

}
