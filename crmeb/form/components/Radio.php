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
 * 单选框组件
 * Class Radio
 * @package crmeb\form\components
 */
class Radio extends BaseComponent implements BuildInterface
{
    /**
     * 组件名
     */
    const NAME = 'radio';

    /**
     * 组件规则
     * @var array
     */
    protected $rule = [
        'title' => '',
        'field' => '',
        'value' => 0,
        'info' => '',
        'vertical' => false,
        'control' => [],
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
     * 多个组件联动
     * @param array $controls
     * @return $this
     */
    public function controls(array $controls = [])
    {
        $this->rule['control'] = $controls;
        return $this;
    }

    /**
     * options数据 ['label'=>'确定','value'=>1]
     * @param array $options
     * @return $this
     */
    public function options(array $options = [])
    {
        $this->rule['options'] = $options;
        return $this;
    }

    /**
     * 组件联动
     * @param $value
     * @param array $components
     * @return $this
     */
    public function control($value, array $components = [])
    {
        $this->rule['control'][] = ['value' => $value, 'componentsModel' => $components];
        return $this;
    }

    /**
     * 设置提示语
     * @param string $info
     * @return $this
     */
    public function info(string $info)
    {
        $this->rule['info'] = $info;
        return $this;
    }

    /**
     * 是否垂直展示
     * @param bool $vertical
     * @return $this
     */
    public function vertical(bool $vertical)
    {
        $this->rule['vertical'] = $vertical;
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
