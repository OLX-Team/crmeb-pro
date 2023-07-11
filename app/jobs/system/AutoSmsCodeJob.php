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
namespace app\jobs\system;

use crmeb\basic\BaseJobs;
use app\services\message\sms\SmsRecordServices;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 自动更新短信状态
 * Class AutoSmsCodeJob
 * @package app\jobs\user
 */
class AutoSmsCodeJob extends BaseJobs
{
    use QueueTrait;

    /**
     * @return string
     */
    protected static function queueName()
    {
        return 'CRMEB_PRO_TASK';
    }

    /**
     * @param $event
     */
    public function doJob()
    {
        //清除昨日海报
        try {
            //修改短信发送记录短信状态
            $smsRecord = app()->make(SmsRecordServices::class);
            return $smsRecord->modifyResultCode();
        } catch (\Throwable $e) {
            Log::error('自动更新短信状态:[' . class_basename($this) . ']' . $e->getMessage());
        }

    }
}
