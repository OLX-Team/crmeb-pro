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

use app\services\system\attachment\SystemAttachmentServices;
use crmeb\utils\Cron;
use crmeb\interfaces\ListenerInterface;
use think\facade\Log;

/**
 * 定时清除海报
 * Class AutoClearPoster
 * @package app\listener\user
 */
class AutoClearPoster extends Cron implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        //定时清除海报
        $this->tick(1000 * 60 * 60 * 30, function () {
            try {
                /** @var SystemAttachmentServices $attach */
                $attach = app()->make(SystemAttachmentServices::class);
                return $attach->emptyYesterdayAttachment();
            } catch (\Throwable $e) {
                Log::error('清除昨日海报,失败原因:[' . class_basename($this) . ']' . $e->getMessage());
            }
        });

    }
}
