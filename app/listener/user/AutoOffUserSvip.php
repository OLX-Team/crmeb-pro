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

use app\services\user\UserServices;
use crmeb\utils\Cron;
use crmeb\interfaces\ListenerInterface;
use think\facade\Log;

/**
 * 定时清空用户到期svip
 * Class AutoOffUserSvip
 * @package app\listener\user
 */
class AutoOffUserSvip extends Cron implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        //定时清空用户svip
        $this->tick(1000 * 60 * 10, function () {
            try {
                /** @var UserServices $userServices */
                $userServices = app()->make(UserServices::class);
                $userServices->offUserSvip();
                return true;
            } catch (\Throwable $e) {
                Log::error('清空用户svip,失败原因:[' . class_basename($this) . ']' . $e->getMessage());
            }
        });

    }
}
