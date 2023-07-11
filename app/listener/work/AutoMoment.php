<?php


namespace app\listener\work;


use app\services\work\WorkMomentServices;
use crmeb\interfaces\ListenerInterface;
use crmeb\utils\Cron;
use think\facade\Log;

/**
 * 定时发送朋友圈
 * Class AutoMoment
 * @package app\listener\work
 */
class AutoMoment extends Cron implements ListenerInterface
{

    /**
     * @param $event
     */
    public function handle($event): void
    {
        $this->tick(1000, function () {
            /** @var WorkMomentServices $make */
            $make = app()->make(WorkMomentServices::class);

            try {
                $make->cronHandle();
            } catch (\Throwable $e) {
                Log::error([
                    'message' => '执行发送朋友圈发生错误：' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        });
    }
}
