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
namespace app\listener\system;

use app\services\message\sms\SmsRecordServices;
use crmeb\utils\Cron;
use crmeb\interfaces\ListenerInterface;
use think\facade\Log;

/**
 * 定时更新短信状态
 * Class AutoSmsCode
 * @package app\listener\system
 */
class AutoSmsCode extends Cron implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        //更新短信状态
        $this->tick(1000 * 30, function () {
            try {
                //修改短信发送记录短信状态
                /** @var SmsRecordServices $smsRecord */
                $smsRecord = app()->make(SmsRecordServices::class);
                return $smsRecord->modifyResultCode();
            } catch (\Throwable $e) {
                Log::error('自动更新短信状态:[' . class_basename($this) . ']' . $e->getMessage());
            }
        });

    }
}
