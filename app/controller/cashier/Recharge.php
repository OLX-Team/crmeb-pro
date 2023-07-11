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
use app\services\user\UserRechargeServices;
use app\services\user\UserServices;

/**
 * 收银台用户充值
 */
class Recharge extends AuthController
{
    /**
     * 充值数据
     * @return mixed
     */
    public function rechargeInfo()
    {
        $rechargeQuota = sys_data('user_recharge_quota') ?? [];
        $data['recharge_quota'] = $rechargeQuota;
        $recharge_attention = sys_config('recharge_attention');
        $recharge_attention = explode("\n", $recharge_attention);
        $data['recharge_attention'] = $recharge_attention;
        return $this->success($data);
    }

    /**
     * 收银台用户充值
     * @param Request $request
     * @param UserServices $userServices
     * @param UserRechargeServices $userRechargeServices
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function recharge(Request $request, UserServices $userServices, UserRechargeServices $userRechargeServices)
    {
        [$uid, $price, $recharId, $payType, $authCode] = $request->postMore([
            ['uid', 0],
            ['price', 0],
            ['rechar_id', 0],
            [['pay_type', 'd'], 2], //2=用户扫码支付，3=付款码扫码支付
            ['auth_code', '']
        ], true);
        if (!$authCode && $payType == 3) {
            return $this->fail('缺少付款码二维码CODE');
        }
        if (!$price || $price <= 0) {
            return $this->fail('充值金额不能为0元!');
        }

        $storeMinRecharge = sys_config('store_user_min_recharge');
        if ($price < $storeMinRecharge) return $this->fail('充值金额不能低于' . $storeMinRecharge);
        if (!$userServices->userExist($uid)) {
            return $this->fail('用户不存在');
        }
        $re = $userRechargeServices->recharge($uid, $price, $recharId, (int)$payType, 'store', $this->cashierInfo, $authCode);
        if ($re) {
            $msg = $re['msg'];
            unset($re['msg']);
            return $this->success($msg, $re);
        }
        return $this->fail('充值失败');
    }
}
