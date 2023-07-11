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

namespace app\jobs\user;


use app\services\order\StoreCartServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderSuccessServices;
use app\services\pay\PayServices;
use app\services\user\UserRechargeServices;
use crmeb\basic\BaseJobs;
use crmeb\services\wechat\Payment;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 付款码支付
 * Class MicroPayOrderJob
 * @package app\jobs\user
 */
class MicroPayOrderJob extends BaseJobs
{

    use QueueTrait;


    public function doJob(string $outTradeNo, int $type = 1, int $num = 1)
    {
        if ($type) {
            /** @var UserRechargeServices $make */
            $make = app()->make(UserRechargeServices::class);
        } else {
            /** @var StoreOrderSuccessServices $make */
            $make = app()->make(StoreOrderSuccessServices::class);
        }
        $orderInfo = $make->get(['order_id' => $outTradeNo]);
        if (!$orderInfo) {
            return true;
        }
        if ($orderInfo->paid) {
            return true;
        }
        if (!$type && $orderInfo->is_del) {
            return true;
        }
        try {
            //查询订单支付状态
            $respones = Payment::queryOrder($outTradeNo);
            if ($respones['paid'] && ($respones['payInfo']['trade_state'] ?? '') == 'SUCCESS') {
                if ($type) {
                    $make->rechargeSuccess($outTradeNo);
                } else {
                    //删除购物车
                    /** @var StoreCartServices $cartServices */
                    $cartServices = app()->make(StoreCartServices::class);
                    $cartServices->deleteCartStatus($orderInfo['cart_id'] ?? []);
                    //修改支付状态
                    $make->paySuccess($orderInfo->toArray(), PayServices::WEIXIN_PAY, [
                        'trade_no' => $respones['payInfo']['transaction_id'] ?? ''
                    ]);
                }
            } else {
                //15秒后还是状态异常直接取消订单
                if ($num >= 3) {
                    try {
                        $respones = Payment::reverseOrder($outTradeNo);
                        if ($type) {
                            /** @var StoreOrderCreateServices $service */
                            $service = app()->make(StoreOrderCreateServices::class);
                            $make->update(
                                [
                                    'order_id' => $outTradeNo
                                ],
                                [
                                    $type ? 'remarks' : 'remark' => '支付状态异常自动撤销订单，并从新生成订单号',
                                    'order_id' => $type ? $make->getOrderId() : $service->getNewOrderId(),
                                ]
                            );
                        }

                    } catch (\Throwable $e) {
                        Log::error([
                            'message' => '撤销订单失败，订单号:' . $outTradeNo . ';错误原因：' . $e->getMessage(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                    }
                    return true;
                }
                $secs = 5;
                if (isset($respones['payInfo']['err_code']) && $respones['payInfo']['err_code'] === 'USERPAYING') {
                    $secs = 10;
                }
                self::dispatchSece($secs, [$outTradeNo, $type, $num + 1]);
            }
        } catch (\Throwable $e) {

        }
        return true;
    }
}
