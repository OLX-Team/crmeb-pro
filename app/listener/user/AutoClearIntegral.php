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
namespace app\listener\user;

use app\services\user\UserIntegralServices;
use crmeb\utils\Cron;
use crmeb\interfaces\ListenerInterface;
use think\facade\Log;

/**
 * 定时清空用户积分
 * Class AutoClearIntegral
 * @package app\listener\user
 */
class AutoClearIntegral extends Cron implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        //定时清空用户积分
        $this->tick(1000 * 60 * 60, function () {
            //清空积分
            try {
                /** @var UserIntegralServices $userIntegralServices */
                $userIntegralServices = app()->make(UserIntegralServices::class);
                [$clear_time, $start_time, $end_time] = $userIntegralServices->getTime();
                //到清空积分的最后一天
                if ($clear_time == strtotime(date('Y-m-d', time()))) {
                    return $userIntegralServices->clearExpireIntegral();
                }
                return true;
            } catch (\Throwable $e) {
                Log::error('清空积分,失败原因:[' . class_basename($this) . ']' . $e->getMessage());
            }
        });

    }
}
