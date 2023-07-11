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
namespace app\controller\admin\v1\notification\sms;

use crmeb\exceptions\AdminException;
use app\controller\admin\AuthController;
use crmeb\services\{sms\Sms, SystemConfigService};

/**
 * 短信购买
 * Class SmsPay
 * @package app\admin\controller\sms
 */
class SmsPay extends AuthController
{
    /**
     * @var Sms
     */
    protected $smsHandle;

    public function initialize()
    {
        parent::initialize();
        $data = SystemConfigService::more(['sms_account', 'sms_token', 'site_url']);
        $this->smsHandle = new Sms('yunxin', $data);
        if (!$this->smsHandle->isLogin()) {
            throw new AdminException('请先填写短息配置');
        }
    }

    /**
     *  获取账号信息
     */
    public function number()
    {
        $countInfo = $this->smsHandle->count();
        if ($countInfo['status'] == 400) return $this->fail($countInfo['msg']);
        $info['account'] = sys_config('sms_account');
        $info['number'] = $countInfo['data']['number'];
        $info['send_total'] = $countInfo['data']['send_total'];
        return $this->success($info);
    }

    /**
     *  获取支付套餐
     */
    public function price()
    {
        list($page, $limit) = $this->request->getMore([
            ['page', 1],
            ['limit', 20],
        ], true);
        $mealInfo = $this->smsHandle->meal($page, $limit);
        if ($mealInfo['status'] == 400) return $this->fail($mealInfo['msg']);
        return $this->success($mealInfo['data']);
    }

    /**
     * 获取支付码
     */
    public function pay()
    {
        list($payType, $mealId, $price) = $this->request->postMore([
            ['payType', 'weixin'],
            ['mealId', 0],
            ['price', 0],
        ], true);
        $payInfo = $this->smsHandle->pay($payType, $mealId, $price, $this->adminId);
        if ($payInfo['status'] == 400) return $this->fail($payInfo['msg']);
        return $this->success($payInfo['data']);
    }
}
