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

use app\services\BaseServices;
use app\dao\user\UserSpreadDao;
use app\services\order\store\BranchOrderServices;
use app\services\store\StoreUserServices;
use app\services\store\SystemStoreStaffServices;
use app\services\user\level\SystemUserLevelServices;

/**
 * Class UserSpreadServices
 * @package app\services\user
 * @method getList(array $where, string $field = '*', int $page = 0, $limit = 0)
 */
class UserSpreadServices extends BaseServices
{
    /**
     * UserSpreadServices constructor.
     * @param UserSpreadDao $dao
     */
    public function __construct(UserSpreadDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 记录推广关系
     * @param int $uid
     * @param int $spread_uid
     * @param int $spread_time
     * @param int $admin_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setSpread(int $uid, int $spread_uid, int $spread_time = 0, int $admin_id = 0)
    {
        if (!$uid || !$spread_uid) return false;
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if (!$userServices->userExist($uid)) {
            return false;
        }

        if (!$userServices->userExist($spread_uid)) {
            return false;
        }
        $data = ['uid' => $uid, 'spread_uid' => $spread_uid, 'spread_time' => $spread_time ?: time(), 'admin_id' => $admin_id];
        try {
            /** @var SystemStoreStaffServices $storeStaffServices */
            $storeStaffServices = app()->make(SystemStoreStaffServices::class);
            $staffInfo = $storeStaffServices->getStaffInfoByUid($spread_uid);
        } catch (\Throwable $e) {
            $staffInfo = [];
        }
        if ($staffInfo) {
            $data['store_id'] = $staffInfo['store_id'];
            $data['staff_id'] = $staffInfo['id'];
        }
        if ($this->dao->save($data)) {
            if ($staffInfo) {
                //记录门店用户
                /** @var StoreUserServices $storeUserServices */
                $storeUserServices = app()->make(StoreUserServices::class);
                $storeUserServices->setStoreUser($uid, (int)$staffInfo['store_id']);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * 查询推广用户uids
     * @param int $uid
     * @param int $type 1:一级2：二级 0：所有
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSpreadUids(int $uid, int $type = 0, array $where = [])
    {
        if (!$uid) return [];
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if (!$userServices->userExist($uid)) {
            return [];
        }
        if ($where && isset($where['time'])) {
            $where['timeKey'] = 'spread_time';
        }
        $where['spread_uid'] = $uid;
        $spread_one = $this->dao->getSpreadUids($where);
        if ($type == 1) {
            return $spread_one;
        }
        $where['spread_uid'] = $spread_one;
        $spread_two = $this->dao->getSpreadUids($where);
        if ($type == 2) {
            return $spread_two;
        }
        return array_unique(array_merge($spread_one, $spread_two));
    }

    /**
     * 门店推广统计详情列表
     * @param int $store_id
     * @param int $staff_id
     * @param array $time
     * @return array|array[]
     */
    public function time(int $store_id, int $staff_id, array $time = [])
    {
        if (!$time) {
            return [[], []];
        }
        [$start, $stop, $front, $front_stop] = $time;
        $where = ['store_id' => $store_id];
        if ($staff_id) {
            $where['staff_id'] = $staff_id;
        }
        $frontPrice = $this->dao->count($where + ['time' => [$front, $front_stop], 'timeKey' => 'spread_time']);
        $nowPrice = $this->dao->count($where + ['time' => [$start, $stop], 'timeKey' => 'spread_time']);
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where + ['time' => [$start, $stop], 'timeKey' => 'spread_time'], '*', ['user'], $page, $limit);
        /** @var BranchOrderServices $order */
        $order = app()->make(BranchOrderServices::class);
        $order_where = ['time' => $time, 'is_del' => 0, 'is_system_del' => 0, 'paid' => 1, 'pid' => 0, 'refund_status' => [0, 3]];
        foreach ($list as &$item) {
            $item['spread_time'] = $item['spread_time'] ? date('Y-m-d H:i:s', $item['spread_time']) : '';
            $orderCount = $order->column($order_where + ['uid' => $item['uid']], 'count(`id`) as count,sum(`pay_price`) as price');
            $item['order_count'] = $orderCount[0]['count'] ?? 0;
            $item['order_price'] = $orderCount[0]['price'] ?? 0.00;
        }
        return [[$nowPrice, $frontPrice], $list];
    }

    /**
     * 获取推广列表
     * @param int $uid
     * @param UserServices $userServices
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSpreadList(array $where, string $field = '*', array $with = ['user'], bool $type = true)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $field, $with, $page, $limit);
        $count = $this->dao->count($where);
        /** @var BranchOrderServices $order */
        $order = app()->make(BranchOrderServices::class);
        foreach ($list as &$item) {
            $item['spread_time'] = $item['spread_time'] ? date('Y-m-d H:i:s', $item['spread_time']) : '';
            $item['type'] = $item['admin_id'] ? ('手动变更(' . ($item['real_name'] ?? '') . ')') : '自动变更';
            if ($type) {
                $orderCount = $order->column(['uid' => $item['uid'], 'is_del' => 0, 'is_system_del' => 0, 'paid' => 1, 'pid' => 0, 'refund_status' => [0, 3]], 'count(`id`) as count,sum(`pay_price`) as price');
                $item['order_count'] = $orderCount[0]['count'] ?? 0;
                $item['order_price'] = $orderCount[0]['price'] ?? 0.00;
            }
        }
        return compact('list', 'count');
    }

    /**
     * 获取好友uids 我推广的 推广我的
     * @param int $uid
     * @return array
     */
    public function getFriendUids(int $uid)
    {
        $result = [];
        if ($uid) {
            $spread = $this->dao->getColumn(['spread_uid' => $uid], 'uid');
            $sup_spread = $this->dao->getColumn(['uid' => $uid], 'spread_uid');
            $result = array_unique(array_merge($spread, $sup_spread));
        }
        return $result;
    }

    /**
     * 获取好友
     * @param int $id
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFriendList(int $uid, string $field = 'uid,nickname,level,add_time')
    {
        $uids = $this->getFriendUids($uid);
        $list = [];
        $count = 0;
        if ($uids) {
            [$page, $limit] = $this->getPageValue();
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $list = $userServices->getList(['uid' => $uids], $field, $page, $limit);
            /** @var SystemUserLevelServices $systemLevelServices */
            $systemLevelServices = app()->make(SystemUserLevelServices::class);
            $systemLevelList = $systemLevelServices->getWhereLevelList([], 'id,name');
            if ($systemLevelList) $systemLevelServices = array_combine(array_column($systemLevelList, 'id'), $systemLevelList);
            foreach ($list as &$item) {
                $item['type'] = $systemLevelServices[$item['level']]['name'] ?? '暂无';
                $item['add_time'] = $item['add_time'] && is_numeric($item['add_time']) ? date('Y-m-d H:i:s', $item['add_time']) : '';
            }
            $count = $this->dao->count(['spread_uid' => $uid]);
        }

        return compact('list', 'count');
    }
}
