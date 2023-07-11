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

use crmeb\form\FormValidate;

/**
 * Class StrRules
 * @package crmeb\form\validate
 */
class StrRules extends BaseRules
{

    /**
     * 手机号正则
     */
    const PHONE_NUMBER = '/(^(\d{3,4}-)?[0-9]{7,8}$)|(^(13[0-9]|14[01456879]|15[0-35-9]|16[2567]|17[0-8]|18[0-9]|19[0-35-9])[0-9]{8}$)/';

    /**
     * 设置类型
     * @return string
     */
    public static function getType(): string
    {
        return 'string';
    }

}
