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
 * 自定义表格
 * Class DiyTable
 * @package crmeb\form\components
 */
class DiyTable extends BaseComponent implements BuildInterface
{
    /**
     * 组件名称
     */
    const NAME = 'diyTable';

    //内部表格自定义类型
    const TYPE = ['input', 'select', 'inputNumber', 'switch'];

    /**
     * 规则
     * @var string[]
     */
    protected $rule = [
        'title' => '',
        'value' => [],
        'type' => '',
        'field' => '',
        'options' => [],
        'info' => '',
    ];

    /**
     * DiyTable constructor.
     * @param string $field
     * @param string $title
     * @param array $value
     * @param array $options
     */
    public function __construct(string $field, string $title, array $value = [], array $options = [])
    {
        $this->rule['title'] = $title;
        $this->rule['field'] = $field;
        $this->rule['options'] = $options;
        $this->rule['value'] = $value;
    }

    /**
     * @param string $info
     * @return $this
     */
    public function info(string $info)
    {
        $this->rule['info'] = $info;
        return $this;
    }

    /**
     * 设置列
     * @param string $name
     * @param string $key
     * @param string $type
     * @param array $props
     * @return $this
     */
    public function column(string $name, string $key, string $type = 'input', array $props = [])
    {
        $this->rule['options'][] = ['name' => $name, 'key' => $key, 'type' => $type, 'props' => $props];
        return $this;
    }

    public function toArray(): array
    {
        $this->rule['name'] = self::NAME;
        $this->before();
        return $this->rule;
    }
}
