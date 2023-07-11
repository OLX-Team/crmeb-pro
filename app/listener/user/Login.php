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

use app\jobs\user\LoginJob;
use crmeb\interfaces\ListenerInterface;

/**
 * 登录完成后置事件
 * Class Register
 * @package app\listener\user
 */
class Login implements ListenerInterface
{
    /**
     * 登录完成后置事件
     * @param $event
     */
    public function handle($event): void
    {
        [$uid, $ip] = $event;
        try {
            LoginJob::dispatch([$uid, $ip]);
        } catch (\Throwable $e) {

        }
    }

}
