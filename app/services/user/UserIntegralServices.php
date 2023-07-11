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

namespace app\services\user;

use app\dao\user\UserBillDao;
use app\jobs\user\UserIntegralJob;
use app\services\BaseServices;

/**
 * 用户积分
 * Class UserIntegralServices
 * @package app\services\user
 */
class UserIntegralServices extends BaseServices
{

    /**
     * UserIntegralServices constructor.
     * @param UserBillDao $dao
     */
    public function __construct(UserBillDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 清空到期积分（分批加入队列）
     * @return bool
     */
    public function clearExpireIntegral()
    {
        //是否开启积分有效期
        if (!sys_config('integral_effective_status', 0)) {
            return true;
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $users = $userServices->getColumn([['integral', '>', 0]], 'uid');
        if ($users) {
            //拆分大数组
            $uidsArr = array_chunk($users, 100);
            foreach ($uidsArr as $uids) {
                //加入同步|更新用户队列
                UserIntegralJob::dispatch($uids);
            }
        }
        return true;
    }

    /**
     * 执行清空到期积分
     * @param array $uids
     * @return bool
     */
    public function doClearExpireIntegral(array $uids)
    {
        if (!$uids) return true;
        //是否开启积分有效期
        if (!sys_config('integral_effective_status', 0)) {
            return true;
        }
        [$clear_time, $start_time, $end_time] = $this->getTime();
        $start = date('Y年m月d日', $start_time);
        $end = date('Y年m月d日', $end_time);
        $where = ['category' => 'integral', 'pm' => 1, 'status' => 1];
        $where['add_time'] = [$start_time, $end_time];
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $users = $userServices->getColumn([['uid', 'in', $uids]], 'uid,integral', 'uid');
        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
        //查询是否清除过
        $clear_uids = $userBillServices->getColumn([['category', '=', 'integral'], ['type', '=', 'system_clear'], ['add_time', '>=', $clear_time], ['add_time', '<=', $clear_time + 86400]], 'uid');
        foreach ($uids as $uid) {
            $number = 0;
            if (!isset($users[$uid]) || in_array($uid, $clear_uids)) continue;
            $user = $users[$uid];
            $where['uid'] = $uid;
            $userSumIntegralInc = $userBillServices->getBillSum($where);
            $where['pm'] = 0;
            $userSumIntegralDec = $userBillServices->getBillSum($where);
            $userSumIntegral = $userSumIntegralInc > $userSumIntegralDec ? (int)bcsub((string)$userSumIntegralInc, (string)$userSumIntegralDec, 0) : 0;
            if ($userSumIntegral) {
                $user_data = [];
                $number = $userSumIntegral;
                if ($userSumIntegral >= $user['integral']) {
                    $number = $user['integral'];
                    $user_data['integral'] = 0;
                } else {
                    $user_data['integral'] = (int)bcsub((string)$user['integral'], (string)$userSumIntegral, 0);
                }
                //记录清除积分
                $userBillServices->income('system_clear_integral', $uid, $number, $user_data['integral'], $uid);
                $userServices->update($uid, $user_data);
            }
        }
        return true;
    }

    /**
     * 获取用户清空到期积分
     * @param int $uid
     * @param array $user
     * @return array|int[]
     */
    public function getUserClearIntegral(int $uid, $user = [])
    {
        if (!$uid) return [0, 0];
        //是否开启积分有效期
        if (!sys_config('integral_effective_status', 0)) {
            return [0, 0];
        }
        [$clear_time, $start_time, $end_time] = $this->getTime();

        $where = ['category' => 'integral', 'pm' => 1, 'status' => 1];
        $where['add_time'] = [$start_time, $end_time];
        if (!$user) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $user = $userServices->get($uid, 'uid,integral');
        }

        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
        $where['uid'] = $uid;
        $userSumIntegralInc = $userBillServices->getBillSum($where);
        $where['pm'] = 0;
        $userSumIntegralDec = $userBillServices->getBillSum($where);
        $userSumIntegral = $userSumIntegralInc > $userSumIntegralDec ? (int)bcsub((string)$userSumIntegralInc, (string)$userSumIntegralDec, 0) : 0;
        if ($userSumIntegral) {
            if ($userSumIntegral >= $user['integral']) {
                $userSumIntegral = $user['integral'];
            }
        }
        return [$userSumIntegral, $clear_time];
    }


    /**
     * 获取清空积分时间段
     * @param int $type
     * @return int[]
     */
    public function getTime(int $type = 0)
    {
        if (!$type) $type = (int)sys_config('integral_effective_time', 3);
        switch ($type) {
            case 1://月
                $start = date('Y-m-01 00:00:00', strtotime('-1 month'));
                $end = date("Y-m-d 23:59:59", strtotime(-date('d') . 'day'));
                $clear_end = date('Y-m-t', strtotime(date('Y-m-d')));
                break;
            case 2://季度
                $season = ceil((date('n')) / 3) - 1;//上季度是第几季度
                $start = date('Y-m-d 00:00:00', mktime(0, 0, 0, $season * 3 - 3 + 1, 1, date('Y')));
                $end = date('Y-m-d 23:59:59', mktime(23, 59, 59, $season * 3, date('t', mktime(0, 0, 0, $season * 3, 1, date("Y"))), date('Y')));
                $clear_end = date('Y-m-t', mktime(0, 0, 0, ($season + 1) * 3, 1, date('Y')));
                break;
            case 3://年
            default://默认年
                $start = date('Y-01-01 00:00:00', strtotime('-1 year'));
                $end = date('Y-m-d 23:59:59', strtotime($start . "+12 month -1 day"));
                $clear_end = date('Y-12-31');
                break;
        }
        return [strtotime($clear_end), strtotime($start), strtotime($end)];
    }
}
