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

//declare (strict_types=1);

namespace app\services\out;


use app\services\BaseServices;
use app\dao\user\UserDao;
use app\services\user\UserBillServices;
use app\services\user\UserMoneyServices;
use crmeb\exceptions\AdminException;


/**
 *
 * Class UserServices
 * @package app\services\user
 */
class UserServices extends BaseServices
{

    /**
     * UserServices constructor.
     * @param UserDao $dao
     */
    public function __construct(UserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取用户信息
     * @param $id
     * @param $field
     */
    public function getUserInfo(int $uid, $field = '*')
    {
        if (is_string($field)) $field = explode(',', $field);
        $info = $this->dao->getOutOne($uid, $field);
        $info = $info ? $info->toArray() : [];
        return $info;
    }

    /**
     * 修改提交处理
     * @param $id
     * @return mixed
     */
    public function updateInfo(int $id, array $data)
    {
        $user = $this->getUserInfo($id);
        if (!$user) {
            throw new AdminException('数据不存在!');
        }
        $res1 = false;
        $res2 = false;
        $edit = array();

        if (isset($data['is_other']) && $data['is_other']) {
            if ($data['money_status'] && $data['money']) {//余额增加或者减少
                /** @var UserMoneyServices $userMoneyServices */
                $userMoneyServices = app()->make(UserMoneyServices::class);
                if ($data['money_status'] == 1) {//增加
                    $edit['now_money'] = bcadd($user['now_money'], $data['money'], 2);
                    $res1 = $userMoneyServices->income('system_add', $user['uid'], $data['money'], $edit['now_money'], $data['adminId'] ?? 0);
                } else if ($data['money_status'] == 2) {//减少
                    if ($user['now_money'] > $data['money']) {
                        $edit['now_money'] = bcsub($user['now_money'], $data['money'], 2);
                    } else {
                        $edit['now_money'] = 0;
                        $data['money'] = $user['now_money'];
                    }
                    $res1 = $userMoneyServices->income('system_sub', $user['uid'], $data['money'], $edit['now_money'], $data['adminId'] ?? 0);
                }
            } else {
                $res1 = true;
            }
            if ($data['integration_status'] && $data['integration']) {//积分增加或者减少
                $integral_data = ['link_id' => $data['adminId'] ?? 0, 'number' => $data['integration'], 'balance' => $user['integral']];
                /** @var UserBillServices $userBill */
                $userBill = app()->make(UserBillServices::class);
                if ($data['integration_status'] == 1) {//增加
                    $edit['integral'] = bcadd($user['integral'], $data['integration'], 2);
                    $integral_data['title'] = '系统增加积分';
                    $integral_data['mark'] = '系统增加了' . floatval($data['integration']) . '积分';
                    $res2 = $userBill->incomeIntegral($user['uid'], 'system_add', $integral_data);
                } else if ($data['integration_status'] == 2) {//减少
                    $edit['integral'] = bcsub($user['integral'], $data['integration'], 2);
                    $integral_data['title'] = '系统减少积分';
                    $integral_data['mark'] = '系统扣除了' . floatval($data['integration']) . '积分';
                    $res2 = $userBill->expendIntegral($user['uid'], 'system_sub', $integral_data);
                }
            } else {
                $res2 = true;
            }
        } else {
            $res2 = $res1 = true;
        }

        //修改基本信息
        if (!isset($data['is_other']) || !$data['is_other']) {
            $edit['real_name'] = $data['real_name'];
            $edit['card_id'] = $data['card_id'];
            $edit['birthday'] = strtotime($data['birthday']);
            $edit['phone'] = $data['phone'];
            $edit['addres'] = $data['addres'];
        }
        if ($edit) $res3 = $this->dao->update($id, $edit);
        else $res3 = true;
        if ($res1 && $res2 && $res3)
            return true;
        else throw new AdminException('修改失败');
    }

}
