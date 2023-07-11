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

namespace app\listener\store;


use crmeb\interfaces\ListenerInterface;

/**
 * 删除门店成功事件
 * Class DeleteSuccess
 * @package app\listener\store
 */
class DeleteSuccess implements ListenerInterface
{

    public function handle($event): void
    {
        //门店id
        [$id] = $event;
    }
}
