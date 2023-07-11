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
namespace app\controller\cashier;

use app\Request;
use app\services\cashier\UserServices;
use app\services\store\StoreUserServices;
use app\services\order\StoreCartServices;
use app\services\store\SystemStoreStaffServices;

/**
 * 收银台用户控制器
 */
class User extends AuthController
{
    /**
     * 修改收银员信息
     * @param Request $request
     * @param SystemStoreStaffServices $services
     * @return mixed
     */
    public function updatePwd(Request $request, SystemStoreStaffServices $services)
    {
        $data = $request->postMore([
            ['real_name', ''],
            ['pwd', ''],
            ['new_pwd', ''],
            ['conf_pwd', ''],
            ['avatar', ''],
        ]);
        if ($data['pwd'] && !preg_match('/^(?![^a-zA-Z]+$)(?!\D+$).{6,}$/', $data['new_pwd'])) {
            return $this->fail('设置的密码过于简单(不小于六位包含数字字母)');
        }
        if ($services->updateStaffPwd($this->cashierId, $data))
            return $this->success('修改成功');
        else
            return $this->fail('修改失败');
    }

    /**
     * 获取登录店员详情
     * @return mixed
     */
    public function getCashierInfo()
    {
        return $this->success($this->cashierInfo);
    }

    /**
     * 收银台选择用户列表
     * @param Request $request
     * @param UserServices $services
     * @return mixed
     */
    public function getUserList(Request $request, StoreUserServices $storeUserservices, \app\services\user\UserServices $services)
    {
        $data = $request->getMore([
            ['keyword', ''],
            ['field_key', '']
        ]);
        if ($data['keyword']) {
            if ($data['field_key'] == 'all') {
                $data['field_key'] = '';
            }
            if ($data['field_key'] && in_array($data['field_key'], ['uid', 'phone'])) {
                $where[$data['field_key']] = trim($data['keyword']);
            } else {
                $where['store_like'] = trim($data['keyword']);
            }
            return $this->success($services->getUserList($where));
        } else {
            return app('json')->success($storeUserservices->index($data, $this->storeId));
        }
    }

    /**
     * 获取当前门店店员列表和店员信息
     * @param Request $request
     * @param SystemStoreStaffServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCashierList(Request $request, SystemStoreStaffServices $services)
    {
        $where = $request->getMore([
            ['keyword', '']
        ]);
        $where['store_id'] = $this->storeId;
        $where['is_del'] = 0;
        return $this->success([
            'staffInfo' => $request->cashierInfo(),
            'staffList' => $services->getStoreStaff($where),
            'count' => $services->getStoreSearch($where)->count()
        ]);
    }

    /**
     * 游客切换到用户
     * @param Request $request
     * @param StoreCartServices $services
     * @param $cashierId
     * @return mixed
     */
    public function switchCartUser(Request $request, StoreCartServices $services, $cashierId)
    {
        [$uid, $toUid, $isTourist] = $request->postMore([
            ['uid', 0],
            ['to_uid', 0],
            ['is_tourist', 0]
        ], true);
        if ($isTourist) {
            $where = ['tourist_uid' => $uid, 'store_id' => $this->storeId, 'staff_id' => $cashierId];
            $touristCart = $services->getCartList($where);
            if ($touristCart) {
                $userWhere = ['uid' => $toUid, 'store_id' => $this->storeId, 'staff_id' => $cashierId];
                $userCarts = $services->getCartList($userWhere);
                if ($userCarts) {
                    foreach ($touristCart as $cart) {
                        foreach ($userCarts as $userCart) {
                            //游客商品 存在用户购物车商品中  
                            if($cart['product_id'] == $userCart['product_id'] && $cart['product_attr_unique'] == $userCart['product_attr_unique']) {
                                //修改用户商品数量 删除游客购物车这条数据
                                $services->update(['id' => $userCart['id']], ['cart_num' => bcadd((string)$cart['cart_num'], (string)$userCart['cart_num'])]);
                                $services->delete(['id' => $cart['id']]);
                            }
                        }
                    }
                }
            }
            $services->update($where, ['uid' => $toUid, 'tourist_uid' => '']);
        }
        return $this->success('修改成功');
    }

    /**
     * 用户信息
     * @param Request $request
     * @param \app\services\user\UserServices $services
     * @param StoreCartServices $cartServices
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfo(Request $request, \app\services\user\UserServices $services, StoreCartServices $cartServices)
    {
        $code = $request->post('code', '');
        $uid = $request->post('uid', '');
        if (!$code && !$uid) {
            return $this->fail('缺少参数');
        }
        $field = ['uid', 'avatar', 'phone', 'nickname', 'now_money', 'integral'];
        if ($uid) {
            $userInfo = $services->getUserInfo($uid, $field);
        } elseif ($code) {
            $userInfo = $services->get(['uniqid' => $code], $field);
        }

        if (!isset($userInfo) && !$userInfo) {
            return $this->fail('用户不存在');
        }
        $cart = $request->post('cart', []);
        if ($cart) {
            $cartServices->batchAddCart($cart, $this->storeId, $userInfo->uid);
        }
        return $this->success($userInfo->toArray());
    }

    /**
     * 收银台获取当前用户信息
     * @param \app\services\user\UserServices $userServices
     * @param $uid
     * @return mixed
     */
    public function getUidInfo(\app\services\user\UserServices $userServices, $uid)
    {
        return $this->success($userServices->read((int)$uid));
    }

    /**
     * 收银台用户记录
     * @param Request $request
     * @param UserServices $userServices
     * @param $uid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userRecord(Request $request, UserServices $userServices, $uid)
    {
        $type = $request->get('type', '');
        return $this->success($userServices->userRecord($uid, $type));
    }
}
