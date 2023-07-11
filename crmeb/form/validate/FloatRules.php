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

namespace crmeb\form\validate;

/**
 * 浮点
 * Class FloatRules
 * @package crmeb\form\validate
 */
class FloatRules extends BaseRules
{

    /**
     * @return string
     */
    public static function getType(): string
    {
        return 'float';
    }
}
