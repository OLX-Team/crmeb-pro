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
 * 多选组件
 * Class Select
 * @package crmeb\form\components
 * @method placeholder(string $placeholder)
 * @method options(array $options = [])
 * @method info(string $info)
 */
class Select extends BaseComponent implements BuildInterface
{

    /**
     * 组件名
     */
    const NAME = 'select';

    /**
     * 规则
     * @var string[]
     */
    protected $rule = [
        'title' => '',
        'value' => '',
        'type' => '',
        'field' => '',
        'info' => '',
        'placeholder' => '',
        'options' => [],
    ];

    /**
     * Radio constructor.
     * @param string $title
     * @param string $field
     * @param null $value
     */
    public function __construct(string $field, string $title, $value = null)
    {
        $this->rule['title'] = $title;
        $this->rule['field'] = $field;
        $this->rule['value'] = !is_null($value) ? $value : null;
    }

    /**
     * 转换数据
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
        return $this;
    }


}
