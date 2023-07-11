<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
namespace app\services\cashier;

use app\services\activity\coupon\StoreCouponUserServices;
use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use app\services\product\product\StoreProductLogServices;
use app\services\user\UserBillServices;
use app\services\user\UserFriendsServices;
use app\services\user\UserMoneyServices;
use app\services\user\UserSpreadServices;
use app\services\user\UserWechatuserServices;
use crmeb\exceptions\AdminException;

/**
 * 收银台用户Services
 */
class UserServices extends BaseServices
{
    protected $userType = [
        'wechat' => '公众号',
        'routine' => '小程序',
        'h5' => 'H5',
        'pc' => 'PC',
        'app' => 'APP'
    ];

    /**
     * 收银台选择用户列表
     * @param array $where
     * @return array
     */
    public function getUserList(array $where)
    {
        /** @var UserWechatuserServices $services */
        $services = app()->make(UserWechatuserServices::class);
        [$list, $count] = $services->getWhereUserList($where, 'u.uid,avatar,u.nickname,phone,u.user_type,now_money,integral');
        foreach ($list as &$item) {
            $item['user_type'] = $this->userType[$item['user_type']] ?? '其他';
        }
        return compact('list', 'count');
    }

    /**
     * 收银台用户记录
     * @param $uid
     * @param $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userRecord($uid, $type)
    {
        switch ($type) {
            case 'spread':
                /** @var UserFriendsServices $services */
                $services = app()->make(UserFriendsServices::class);
                return $services->getFriendList($uid);
            case 'order':
                /** @var StoreOrderServices $services */
                $services = app()->make(StoreOrderServices::class);
                return $services->getUserOrderList($uid);
            case 'integral':
                /** @var UserBillServices $services */
                $services = app()->make(UserBillServices::class);
                return $services->getIntegralList($uid, [], 'title,number,balance,mark,add_time');
            case 'sign':
                /** @var UserBillServices $services */
                $services = app()->make(UserBillServices::class);
                return $services->getSignList($uid, [], 'title,number,mark,add_time');
            case 'coupon':
                /** @var StoreCouponUserServices $services */
                $services = app()->make(StoreCouponUserServices::class);
                return $services->getUserCouponList($uid, 0);
            case 'balance_change':
                /** @var UserMoneyServices $services */
                $services = app()->make(UserMoneyServices::class);
                return $services->getUserMoneyList($uid, [], 'title,type,number,balance,mark,pm,status,add_time');
            case 'visit':
                /** @var StoreProductLogServices $services */
                $services = app()->make(StoreProductLogServices::class);
                return $services->getList(['uid' => $uid, 'type' => 'visit'], 'product_id');
            case 'spread_change':
                /** @var UserSpreadServices $services */
                $services = app()->make(UserSpreadServices::class);
                return $services->getSpreadList(['store_id' => 0, 'staff_id' => 0, 'uid' => $uid], '*', ['spreadUser', 'admin'], false);
            default:
                throw new AdminException('type参数错误');
        }
    }
}
