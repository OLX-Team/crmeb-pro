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

namespace app\listener\config;


use crmeb\interfaces\ListenerInterface;

/**
 * 修改状态
 * Class StatusSuccess
 * @package app\listener\config
 */
class StatusSuccess implements ListenerInterface
{

    public function handle($event): void
    {
        [$id, $isShow] = $event;
        \crmeb\services\SystemConfigService::clear();
    }
}
