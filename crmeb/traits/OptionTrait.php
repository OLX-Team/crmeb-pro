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

/**
 * 设置参数
 * Trait OptionTrait
 * @package crmeb\traits
 */
trait OptionTrait
{

    protected $item = [];

    /**
     * @param string $key
     * @param $default
     * @return mixed
     */
    public function getItem(string $key, $default = null)
    {
        return $this->item[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param $value
     * @return $this
     */
    public function setItem(string $key, $value)
    {
        $this->item[$key] = $value;
        return $this;
    }

    /**
     * 重置
     */
    public function reset()
    {
        $this->item = [];
    }

}
