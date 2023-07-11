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

namespace crmeb\form;

/**
 * Class CommonRule
 * @package crmeb\form
 */
class CommonRule implements BuildInterface
{
    /**
     * 验证类型
     */
    const VALIDATE_TYPE = ['string', 'number', 'boolean', 'method', 'regexp', 'integer', 'float', 'array', 'object', 'enum', 'date', 'url', 'hex', 'email'];

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var bool
     */
    protected $required = false;

    /**
     * @var string
     */
    protected $pattern = '';

    /**
     * 枚举值
     * @var array
     */
    protected $enum = [];

    /**
     * 提示语
     * @var string
     */
    protected $message = '';

    /**
     * 深度验证对象
     * @var array
     */
    protected $fields = [];

    /**
     * 验证触发方式
     * @var string
     */
    protected $trigger = 'blur';

    /**
     * @param string $type
     * @return $this
     */
    public function type(string $type)
    {
        $this->type = in_array($type, self::VALIDATE_TYPE) ? $type : null;
        if (!$this->type) {
            throw new FormValidate('验证类型错误');
        }
        return $this;
    }

    /**
     * 是否必填
     * @return $this
     */
    public function required()
    {
        $this->required = true;
        return $this;
    }

    /**
     * 设置错误提示
     * @param string $message
     * @return $this
     */
    public function message(string $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * 提示语
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 枚举数据
     * @param array $enum
     * @return $this
     */
    public function enum(array $enum)
    {
        $this->enum = $enum;
        return $this;
    }

    /**
     * 正则表达式
     * @param string $pattern
     * @return $this
     */
    public function pattern(string $pattern)
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * 验证规则
     * @param string|array|BuildInterface $field
     * @param array $rule
     * @return $this
     */
    public function field($field, array $rule = [])
    {
        if (!in_array($this->type, ['array', 'object'])) {
            throw new BuildException('无效规则，类型只能在array或者object情况下才可设置');
        }
        if ($this->type === 'array') {
            $rules = [];
            if ($field instanceof BuildInterface) {
                $rules = $field->toArray();
            }
            $this->fields[] = $rules;
        } else {
            $rules = [];
            foreach ($rule as $item) {
                if ($item instanceof BuildInterface) {
                    $rules[] = $item->toArray();
                }
            }
            $this->fields[$field] = $rules;
        }
        return $this;
    }

    /**
     * 数据转换
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'required' => $this->required,
            'message' => $this->message,
            'trigger' => $this->trigger,
            'pattern' => $this->pattern,
            'enum' => $this->enum,
            'type' => $this->type
        ];
        $res = [];
        foreach ($data as $key => $value) {
            if (is_bool($value) || $value) {
                $res[$key] = $value;
            }
        }
        return $res;
    }
}
