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


use app\services\order\OtherOrderServices;
use app\services\pay\OrderPayServices;
use app\services\user\member\MemberShipServices;
use app\services\user\UserServices;
use think\facade\App;
use app\controller\store\AuthController;


/**
 * 付费会员类
 * Class UserMember
 * @package app\controller\store\user
 */
class UserMember extends AuthController
{
    protected $services = NUll;

    /**
     * UserMember constructor.
     * @param App $app
     * @param MemberShipServices $services
     */
    public function __construct(App $app, MemberShipServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }


    /**
     * svip选择
     * @return mixed
     */
    public function index()
    {
        $isOpen = sys_config('member_card_status', 1);
        $data = [];
        if ($isOpen) {
            $data = $this->services->getApiList(['is_del' => 0]);
        }
        return app('json')->successful($data);
    }

    /**
     * 购买svip
     * @param UserServices $userServices
     * @param OtherOrderServices $otherOrderServices
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function payMember(UserServices $userServices, OtherOrderServices $otherOrderServices)
    {
        [$uid, $memberId] = $this->request->postMore([
            ['uid', 0],
            ['member_id', 0],
        ], true);
        $uid = (int)$uid;
        $memberInfo = $this->services->getMemberInfo((int)$memberId);
        $userInfo = $userServices->getUserInfo($uid);
        if (!$userInfo) {
            return app('json')->fail('用户不存在');
        }
        $order = $otherOrderServices->createOrder($uid, $userInfo['user_type'], $memberId, $memberInfo['pre_price'], 'weixin', $memberInfo['type'] == 'free' ? 0 : 1, 0, (int)$this->storeId, (int)$this->storeStaffId);
        if (!$order) {
            return app('json')->fail('创建付费会员订单失败');
        }
        $order = $order->toArray();
        $info = ['order_id' => $order['order_id']];
        //支付金额为0
        if (bcsub((string)$order['pay_price'], '0', 2) <= 0) {
            //创建订单jspay支付
            $payPriceStatus = $otherOrderServices->zeroYuanPayment($order);
            if ($payPriceStatus)//0元支付成功
                return app('json')->status('success', '激活成功', $info);
            else
                return app('json')->status('pay_error');
        } else {
            /** @var OrderPayServices $payServices */
            $payServices = app()->make(OrderPayServices::class);
            $info['jsConfig'] = $payServices->orderPay($order, 'store');
            return app('json')->status('wechat_h5_pay', '前往支付', $info);
        }
    }


}
