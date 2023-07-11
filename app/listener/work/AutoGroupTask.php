<?php


namespace app\listener\work;


use app\services\work\WorkGroupTemplateServices;
use crmeb\interfaces\ListenerInterface;
use crmeb\utils\Cron;
use think\facade\Log;

/**
 * Class AutoGroupTask
 * @package app\listener\work
 */
class AutoGroupTask extends Cron implements ListenerInterface
{

    /**
     * @param $event
     */
    public function handle($event): void
    {
        $this->tick(1000, function () {
            /** @var WorkGroupTemplateServices $service */
            $service = app()->make(WorkGroupTemplateServices::class);
            try {
                $service->cornHandle();
            } catch (\Throwable $e) {
                Log::error([
                    'message' => '执行定时发送群发任务失败:' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        });
    }
}
