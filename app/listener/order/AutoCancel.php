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
namespace app\listener\order;

use app\services\order\StoreOrderServices;
use crmeb\utils\Cron;
use crmeb\interfaces\ListenerInterface;
use think\facade\Log;

/**
 * 订单定时取消
 * Class Create
 * @package app\listener\order
 */
class AutoCancel extends Cron implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        //自动取消订单
        $this->tick(1000 * 60 * 20, function () {
            //自动取消订单
            try {
                /** @var StoreOrderServices $orderServices */
                $orderServices = app()->make(StoreOrderServices::class);
                return $orderServices->orderUnpaidCancel();
            } catch (\Throwable $e) {
                Log::error('自动取消订单,失败原因:[' . class_basename($this) . ']' . $e->getMessage());
            }
        });
    }
}
