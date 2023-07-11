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

namespace app\controller\api\v1;


use crmeb\services\AliPayService;
use crmeb\services\wechat\Payment;

/**
 * 支付相关回调
 * Class PayController
 * @package app\api\controller\v1
 */
class PayController
{

    /**
     * 支付回调
     * @param string $type
     * @return string|\think\Response
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function notify(string $type)
    {
        switch (urldecode($type)) {
            case 'alipay':
                return AliPayService::handleNotify();
                break;
            case 'routine':
                return Payment::instance()->setAccessEnd(Payment::MINI)->handleNotify();
                break;
            case 'wechat':
                return Payment::instance()->setAccessEnd(Payment::WEB)->handleNotify();
                break;
            case 'app':
                return Payment::instance()->setAccessEnd(Payment::APP)->handleNotify();
                break;
        }
    }

    /**
     * 退款回调
     * @param string $type
     * @return \think\Response
     * @throws \EasyWeChat\Kernel\Exceptions\Exception
     */
    public function refund(string $type)
    {
        switch (urldecode($type)) {
            case 'alipay':

                break;
            case 'routine':
                return Payment::instance()->setAccessEnd(Payment::MINI)->handleRefundedNotify();
                break;
            case 'wechat':
                return Payment::instance()->setAccessEnd(Payment::WEB)->handleRefundedNotify();
                break;
            case 'app':
                return Payment::instance()->setAccessEnd(Payment::APP)->handleRefundedNotify();
                break;
        }
    }
}
