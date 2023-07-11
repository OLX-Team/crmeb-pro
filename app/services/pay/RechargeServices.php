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

namespace app\services\pay;


use app\services\user\UserRechargeServices;
use app\services\wechat\WechatUserServices;
use think\exception\ValidateException;

/**
 *
 * Class RechargeServices
 * @package app\services\pay
 */
class RechargeServices
{
    protected $pay;

    /**
     * RechargeServices constructor.
     * @param PayServices $pay
     */
    public function __construct(PayServices $pay)
    {
        $this->pay = $pay;
    }

    /**
     * @param int $recharge_id
     * @param string $authCode
     * @return array|string
     */
    public function recharge(int $recharge_id, string $authCode = '')
    {
        /** @var UserRechargeServices $rechargeServices */
        $rechargeServices = app()->make(UserRechargeServices::class);
        $recharge = $rechargeServices->getRecharge($recharge_id);
        if (!$recharge) {
            throw new ValidateException('订单失效或者不存在');
        }
        if ($recharge['paid'] == 1) {
            throw new ValidateException('订单已支付');
        }
        $openid = '';
        //没有付款码，不是微信H5支付，门店支付，PC支付，不再APP端，需要判断用户openid
        if (!$authCode && !in_array($recharge['recharge_type'], ['weixinh5', 'store', 'pc']) && !request()->isApp()) {
            $userType = '';
            switch ($recharge['recharge_type']) {
                case 'weixin':
                case 'weixinh5':
                    $userType = 'wechat';
                    break;
                case 'routine':
                    $userType = 'routine';
                    break;
            }
            if (!$userType) {
                throw new ValidateException('不支持该类型方式');
            }
            /** @var WechatUserServices $wechatUser */
            $wechatUser = app()->make(WechatUserServices::class);
            $openid = $wechatUser->uidToOpenid((int)$recharge['uid'], $userType);
            if (!$openid) {
                throw new ValidateException('获取用户openid失败,无法支付');
            }
        }
        return $this->pay->setAuthCode($authCode)->pay($recharge['recharge_type'], $openid, $recharge['order_id'], $recharge['price'], 'user_recharge', '用户充值');
    }

}
