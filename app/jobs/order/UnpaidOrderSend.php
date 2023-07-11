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

namespace app\jobs\order;


use app\services\order\StoreOrderServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 未支付订单10分钟后发送短信
 * Class UnpaidOrderSend
 * @package app\jobs
 */
class UnpaidOrderSend extends BaseJobs
{
    use QueueTrait;

    /**
     * @param $id
     * @return bool
     */
    public function doJob($id)
    {
        try {
            /** @var StoreOrderServices $services */
            $services = app()->make(StoreOrderServices::class);
            $orderInfo = $services->get($id);
            if (!$orderInfo) {
                return true;
            }
            if ($orderInfo->paid) {
                return true;
            }
            if ($orderInfo->is_del) {
                return true;
            }
            //未支付用户发送消息
            event('notice.notice', [['order' => $orderInfo], 'order_pay_false']);
        } catch (\Throwable $e) {
            Log::error('未支付订单发送短信失败，原因：' . $e->getMessage());
        }
        return true;
    }

}
