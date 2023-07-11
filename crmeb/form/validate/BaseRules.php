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


use crmeb\form\CommonRule;

/**
 * Class BaseRules
 * @package crmeb\form\validate
 * @method CommonRule required() 是否必填
 * @method CommonRule message(string $message) 设置错误提示
 * @method CommonRule enum(array $enum) 枚举
 * @method CommonRule pattern(string $pattern) 正则表达式
 * @method CommonRule field($field, array $rule = []) 验证规则
 */
abstract class BaseRules
{
    /**
     * 是否初始化
     * @var bool
     */
    protected static $init = false;

    /**
     * @var CommonRule
     */
    protected static $rule;

    /**
     * @return mixed
     */
    public static function getType(): string
    {
        return '';
    }

    /**
     * 初始化
     */
    public static function init()
    {
        if (!self::$init) {
            self::$rule = new CommonRule();
            self::$rule->type(static::getType());
            self::$init = true;
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        self::init();
        if (method_exists(self::$rule, $name)) {
            return self::$rule->{$name}(...$arguments);
        }
        throw new FormValidate(__CLASS__ . ' Method does not exist' . $name . '()');
    }
}
