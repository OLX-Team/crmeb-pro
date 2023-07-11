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

namespace app\listener\product;


use crmeb\interfaces\ListenerInterface;
use crmeb\services\CacheService;

/**
 * Class ShippingUpdateSuccess
 * @package app\listener\product
 */
class ShippingUpdateSuccess implements ListenerInterface
{

    public function handle($event): void
    {
        CacheService::redisHandler('apiShipping')->clear();
    }
}
