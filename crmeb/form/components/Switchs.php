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
 * 开关组件
 * Class Switchs
 * @package crmeb\form\components
 */
class Switchs extends BaseComponent implements BuildInterface
{
    /**
     * 组件名
     */
    const NAME = 'switch';

    /**
     * 规则
     * @var array
     */
    protected $rule = [
        'title' => '',
        'field' => '',
        'value' => '',
        'info' => '',
        'control' => [],
        'options' => [],
    ];

    /**
     * Switchs constructor.
     * @param string $field
     * @param string $title
     * @param int $value
     */
    public function __construct(string $field, string $title, int $value = null)
    {
        $this->rule['title'] = $title;
        $this->rule['field'] = $field;
        $this->rule['value'] = !is_null($value) ? intval($value) : null;
    }

    /**
     * 多组件群添加
     * @param array $controls
     * @return $this
     */
    public function controls(array $controls = [])
    {
        $this->rule['control'] = $controls;
        return $this;
    }

    /**
     * 组件联动添加
     * @param $value
     * @param array $components
     * @return $this
     */
    public function control(int $value, array $components = [])
    {
        $this->rule['control'][] = ['value' => $value, 'componentsModel' => $components];
        return $this;
    }

    /**
     * 开启值和名称设置
     * @param string $label
     * @param int $value
     * @return $this
     */
    public function trueValue(string $label, int $value)
    {
        $this->rule['options'][] = ['trueValue' => $value, 'label' => $label];
        return $this;
    }

    /**
     * 关闭值和名称设置
     * @param string $label
     * @param int $value
     * @return $this
     */
    public function falseValue(string $label, int $value)
    {
        $this->rule['options'][] = ['falseValue' => $value, 'label' => $label];
        return $this;
    }

    /**
     * 设置提示信息
     * @param string $info
     * @return $this
     */
    public function info(string $info)
    {
        $this->rule['info'] = $info;
        return $this;
    }

    /**
     * 数据转换
     * @return array
     */
    public function toArray(): array
    {
        $this->rule['name'] = self::NAME;
        $control = [];
        foreach ($this->rule['control'] as $item) {
            $data = ['value' => $item['value'], 'componentsModel' => []];
            foreach ($item['componentsModel'] as $value) {
                if ($value instanceof BuildInterface) {
                    $data['componentsModel'][] = $value->toArray();
                }
            }
            $control[] = $data;
        }
        $this->rule['control'] = $control;
        $this->before();
        return $this->rule;
    }
}
