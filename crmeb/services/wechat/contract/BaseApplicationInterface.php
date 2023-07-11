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
 * Interface BaseApplicationInterface
 * @package crmeb\services\wechat\contract
 */
interface BaseApplicationInterface
{

    /**
     * @return mixed
     */
    public static function instance();

    /**
     * @return mixed
     */
    public function application();
}
