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

namespace crmeb\form\components;


use crmeb\form\BaseComponent;
use crmeb\form\BuildInterface;

/**
 * 数字输入框
 * Class InputNumber
 * @package crmeb\form\components
 */
class InputNumber extends BaseComponent implements BuildInterface
{

    /**
     * 组件名
     */
    const NAME = 'inputNumber';

    /**
     * 规则
     * @var string[]
     */
    protected $rule = [
        'title' => '',
        'value' => '',
        'type' => '',
        'field' => '',
        'prefix' => '',
        'suffix' => '',
        'info' => '',
        'min' => null,
        'max' => 99999999
    ];

    /**
     * InputNumber constructor.
     * @param string $field
     * @param string $title
     * @param null $value
     */
    public function __construct(string $field, string $title, $value = null)
    {
        $this->rule['title'] = $title;
        $this->rule['field'] = $field;
        $this->rule['value'] = floatval($value);
    }

    /**
     * 提示语
     * @param string $info
     * @return $this
     */
    public function info(string $info)
    {
        $this->rule['info'] = $info;
        return $this;
    }

    /**
     * 最小值
     * @param int $min
     * @return $this
     */
    public function min(int $min)
    {
        $this->rule['min'] = $min;
        return $this;
    }

    /**
     * 最大值
     * @param int $max
     * @return $this
     */
    public function max(int $max)
    {
        $this->rule['max'] = $max;
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function toArray(): array
    {
        $this->rule['name'] = self::NAME;
        $this->before();
        return $this->rule;
    }

    /**
     * @param $name
     * @param $arguments
     * @return $this
     */
    public function __call($name, $arguments)
    {
        if (in_array($name, ['title', 'field'])) {
            return $this;
        }
        $keys = array_keys($this->rule);
        if (in_array($name, $keys)) {
            $this->rule[$name] = $arguments[0] ?? null;
        }
    }
}
