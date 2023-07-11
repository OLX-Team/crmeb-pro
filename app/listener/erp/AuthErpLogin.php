<?php


namespace app\listener\erp;


use crmeb\interfaces\ListenerInterface;
use crmeb\services\erp\Erp;
use crmeb\utils\Cron;
use think\facade\Log;

/**
 * 检测ERPtoken是否失效，失效自动授权
 * Class AuthErpLogin
 * @package app\listener\erp
 */
class AuthErpLogin extends Cron implements ListenerInterface
{

    /**
     * @param $event
     */
    public function handle($event): void
    {
        $this->tick(1000, function () {

            $erpOpen = !!sys_config('erp_open', 0);
            if (!$erpOpen) {
                return;
            }
            /** @var Erp $erpService */
            $erpService = app()->make(Erp::class);

            try {
                $token = $erpService->getTokenExpire();

                if ($token) {
                    return;
                }

                $erpService->authLogin();
            } catch (\Throwable $e) {
                Log::error([
                    'message' => '自动授权ERP发生错误，错误原因:' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        });
    }
}
