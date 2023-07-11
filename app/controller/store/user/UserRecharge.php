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
namespace app\controller\store\user;


use app\services\user\UserServices;
use think\facade\App;
use app\controller\store\AuthController;
use app\services\user\UserRechargeServices;

/**
 * 充值类
 * Class UserRechargeController
 * @package app\api\controller\user
 */
class UserRecharge extends AuthController
{
    protected $services = NUll;

    /**
     * UserRechargeController constructor.
     * @param App $app
     * @param UserRechargeServices $services
     */
    public function __construct(App $app, UserRechargeServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }


    /**
     * 充值额度选择
     * @return mixed
     */
    public function index()
    {
        $rechargeQuota = sys_data('user_recharge_quota') ?? [];
        $data['recharge_quota'] = $rechargeQuota;
        $recharge_attention = sys_config('recharge_attention');
        $recharge_attention = explode("\n", $recharge_attention);
        $data['recharge_attention'] = $recharge_attention;
        return app('json')->successful($data);
    }

    /**
     * 充值
     * @param UserServices $userServices
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function recharge(UserServices $userServices)
    {
        [$uid, $price, $recharId, $payType, $authCode] = $this->request->postMore([
            ['uid', 0],
            ['price', 0],
            ['rechar_id', 0],
            ['pay_type', 2], //2=用户扫码支付，3=付款码扫码支付
            ['auth_code', '']
        ], true);
        $payType = (int)$payType;
        if (!$authCode && $payType === 3) {
            return app('json')->fail('缺少付款码二维码CODE');
        }
        if (!$price || $price <= 0) return app('json')->fail('充值金额不能为0元!');

        $storeMinRecharge = sys_config('store_user_min_recharge');
        if ($price < $storeMinRecharge) return app('json')->fail('充值金额不能低于' . $storeMinRecharge);
        if (!$userServices->userExist($uid)) {
            return app('json')->fail('用户不存在');
        }
        $re = $this->services->recharge($uid, $price, $recharId, $payType, 'store', $this->storeStaffInfo, $authCode);
        if ($re) {
            $msg = $re['msg'];
            unset($re['msg']);
            return app('json')->successful($msg, $re);
        }
        return app('json')->fail('充值失败');
    }


}
