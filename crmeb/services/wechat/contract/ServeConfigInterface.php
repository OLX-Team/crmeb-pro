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

namespace crmeb\services\wechat\contract;

/**
 * Interface ServeConfigInterface
 * @package crmeb\services\wechat\contract
 */
interface ServeConfigInterface
{

    /**
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null);

}
