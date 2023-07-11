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


namespace crmeb\listeners;


use app\listener\system\AutoConfig;
use crmeb\interfaces\ListenerInterface;
use Swoole\Lock;
use think\facade\Event;

/**
 * 安装检测
 */
class InitInstall implements ListenerInterface
{

    public function handle($event): void
    {
        if (!extension_loaded('swoole_loader')) {
            $swoole = '<span class="correct_span">&radic;</span> 已安装';
        } else {
            $swoole = '<a href="/install/compiler" target="_blank"><span class="correct_span error_span">&radic;</span> 点击查看帮助</a>';
        }
    }
}
