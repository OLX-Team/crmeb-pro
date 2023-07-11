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
namespace app\listener\pink;

use app\services\activity\combination\StorePinkServices;
use crmeb\utils\Cron;
use crmeb\interfaces\ListenerInterface;
use think\facade\Log;

/**
 * 定时处理拼团状态
 * Class AutoPink
 * @package app\listener\order
 */
class AutoPink extends Cron implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        //定时处理拼团状态
        $this->tick(1000 * 60, function () {
            try {
                /** @var StorePinkServices $storePinkServices */
                $storePinkServices = app()->make(StorePinkServices::class);
                $storePinkServices->useStatusPink();
                return true;
            } catch (\Throwable $e) {
                Log::error('拼团失败处理,失败原因:[' . class_basename($this) . ']' . $e->getMessage());
            }
        });

    }
}
