<?php

namespace crmeb\form\components;

use crmeb\form\BaseComponent;
use crmeb\form\BuildInterface;
use crmeb\form\CommonRule;

/**
 * 输入框组件
 * Class Input
 * @package crmeb\form\components
 * @method Input info(string $info) 设置info
 * @method Input placeholder(string $placeholder) 设置placeholder
 * @method Input suffix(string $suffix) 输入框头部图标
 * @method Input prefix(string $prefix) 输入框尾部图标
 * @method Input value($value) 设置value
 * @method Input rows(int $rows) 设置rows
 * @method Input type(string $type) 设置type
 * @method Input maxlength(int $maxlength) 设置maxlength
 */
class Input extends BaseComponent implements BuildInterface
{

    /**
     * 组件名称
     */
    const NAME = 'input';

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
        'disabled' => false,
        'placeholder' => '',
        'suffix' => '',
        'prefix' => '',
        'rows' => 2,
        'copy' => false,
        'copyText' => '',
        'randToken' => 0,
        'maxlength' => null,
    ];

    /**
     * Input constructor.
     * @param string $field
     * @param string $title
     * @param null $value
     */
    public function __construct(string $field, string $title, $value = null)
    {
        $this->rule['title'] = $title;
        $this->rule['field'] = $field;
        $this->rule['value'] = empty($value) ? '' : $value;
    }

    /**
     * 是否禁用
     * @param bool $disabled
     * @return $this
     */
    public function disabled(bool $disabled = true)
    {
        $this->rule['disabled'] = $disabled;
        return $this;
    }

    /**
     * 随机token
     * @return $this
     */
    public function randToken()
    {
        $this->rule['randToken'] = 1;
        return $this;
    }

    /**
     * 随机encodingAESKeyGen
     * @return $this
     */
    public function randAESK()
    {
        $this->rule['randToken'] = 2;
        return $this;
    }

    /**
     * 复制按钮
     * @param string $copyText
     * @return $this
     */
    public function copy(string $copyText = '复制')
    {
        $this->rule['copy'] = true;
        $this->rule['copyText'] = $copyText;
        return $this;
    }

    /**
     * @return string[]
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
        if (in_array($name, ['title', 'field', 'disabled', 'copyText'])) {
            return $this;
        }
        $keys = array_keys($this->rule);
        if (in_array($name, $keys)) {
            $this->rule[$name] = $arguments[0] ?? null;
        }
        return $this;
    }
}
