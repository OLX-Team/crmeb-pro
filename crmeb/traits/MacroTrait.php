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

namespace crmeb\traits;

use app\Request;

/**
 * Trait MacroTrait
 * @package crmeb\traits
 * @property Request $request
 */
trait MacroTrait
{

    /**
     * 获取request内的值
     * @param string $name
     * @param null $default
     * @return |null
     */
    public function getMacro(string $name, $default = null)
    {
        return $this->request->hasMacro($name) ? $this->request->{$name}() : $default;
    }
}
