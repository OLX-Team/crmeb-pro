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
use app\services\system\attachment\SystemAttachmentServices;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 自动清除海报
 * Class AutoClearPosterJob
 * @package app\jobs\user
 */
class AutoClearPosterJob extends BaseJobs
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
            /** @var SystemAttachmentServices $attach */
            $attach = app()->make(SystemAttachmentServices::class);
            return $attach->emptyYesterdayAttachment();
        } catch (\Throwable $e) {
            Log::error('清除昨日海报,失败原因:[' . class_basename($this) . ']' . $e->getMessage());
        }

    }
}
