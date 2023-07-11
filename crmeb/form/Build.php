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


use crmeb\form\components\Alert;
use crmeb\form\components\Card;
use crmeb\form\components\DiyTable;
use crmeb\form\components\Input;
use crmeb\form\components\InputNumber;
use crmeb\form\components\Radio;
use crmeb\form\components\Select;
use crmeb\form\components\Switchs;
use crmeb\form\components\Tabs;
use crmeb\form\components\UploadFrame;
use crmeb\form\components\UploadImage;

/**
 * Class Build
 * @package crmeb\form
 * @method Input input(string $field, string $title, $value = null)
 * @method Tabs tabs(array $options = [])
 * @method Card card(string $title)
 * @method InputNumber inputNum(string $field, string $title, $value = null)
 * @method Select select(string $field, string $title, $value = null)
 * @method UploadFrame uploadFrame(string $field, string $title, $value = null)
 * @method UploadImage uploadImage(string $field, string $title, $value = null)
 * @method Radio radio(string $field, string $title, $value = null)
 * @method Switchs switch (string $field, string $title, $value = null)
 * @method Alert alert (string $title, string $type = '', bool $closable = false, bool $showIcon = false)
 * @method DiyTable diyTable(string $field, string $title, array $value = [], array $options = [])
 */
class Build
{

    /**
     * 挂载组件
     * @var string[]
     */
    protected static $components = [
        'input' => Input::class,
        'tabs' => Tabs::class,
        'card' => Card::class,
        'inputNum' => InputNumber::class,
        'select' => Select::class,
        'uploadFrame' => UploadFrame::class,
        'uploadImage' => UploadImage::class,
        'radio' => Radio::class,
        'switch' => Switchs::class,
        'alert' => Alert::class,
        'diyTable' => DiyTable::class
    ];

    /**
     * @var array
     */
    protected $rule = [];

    /**
     * 请求地址
     * @var
     */
    protected $url;

    /**
     * @var string
     */
    protected $method = 'POST';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Build constructor.
     * @param string|null $url
     * @param array $rule
     * @param string|null $method
     * @param array $data
     */
    public function __construct(string $url = null, array $rule = [], string $method = null, array $data = [])
    {
        $this->url = $url;
        $this->rule = $rule;
        $this->method = $method ?: 'POST';
        $this->data = $data;
    }

    /**
     * @param array $rule
     * @return $this
     */
    public function rule(array $rule = [])
    {
        $this->rule = $rule;
        return $this;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function url(string $url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function method(string $method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @param array $data
     * @return Build
     */
    public function data(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * 批量设置数据
     * @param $rule
     * @return mixed
     */
    public function setValue($rule)
    {
        if (!$this->data) {
            return $rule;
        }
        foreach ($rule as &$value) {
            if (isset($value['value']) && $value['value'] !== '' && isset($value['field'])) {
                $value['value'] = $this->data[$value['field']];
            }
            if (isset($value['options']) && $value['options']) {
                foreach ($value['options'] as $i => $option) {
                    if (isset($option['componentsModel']) && $option['componentsModel']) {
                        $value['options'][$i] = $this->setValue($option['componentsModel']);
                    }
                }
            }
            if (isset($value['control']) && $value['control']) {
                foreach ($value['control'] as $ii => $control) {
                    if (isset($control['componentsModel']) && $control['componentsModel']) {
                        $value['control'][$ii] = $this->setValue($control['componentsModel']);
                    }
                }
            }
            if (isset($value['componentsModel']) && $value['componentsModel']) {
                $value['componentsModel'] = $this->setValue($value['componentsModel']);
            }
        }
        return $rule;
    }

    /**
     * 提取验证值
     * @param $rule
     * @return array
     */
    protected function getValidate($rule)
    {
        $validate = [];
        foreach ($rule as $value) {
            if (isset($value['field']) && isset($value['validate']) && $value['validate']) {
                $validate[$value['field']] = $value['validate'];
            }
            if (isset($value['options']) && $value['options']) {
                foreach ($value['options'] as $option) {
                    if (isset($option['componentsModel']) && $option['componentsModel']) {
                        $validate = array_merge($validate, $this->getValidate($option['componentsModel']));
                    }
                }
            }
            if (isset($value['control']) && $value['control']) {
                foreach ($value['control'] as $control) {
                    if (isset($control['componentsModel']) && $control['componentsModel']) {
                        $validate = array_merge($validate, $this->getValidate($control['componentsModel']));
                    }
                }
            }
            if (isset($value['componentsModel']) && $value['componentsModel']) {
                $validate = array_merge($validate, $this->getValidate($value['componentsModel']));
            }
        }
        return $validate;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $rule = [];
        foreach ($this->rule as $item) {
            if ($item instanceof BuildInterface) {
                $rule[] = $item->toArray();
            }
        }
        $data = [
            'rules' => $this->setValue($rule),
            'validate' => $this->getValidate($rule),
            'url' => $this->url,
            'method' => $this->method
        ];
        $data['validate'] = $data['validate'] ?: (object)[];
        $this->url = null;
        $this->rule = [];
        $this->method = 'POST';
        return $data;
    }

    /**
     * @return false|string
     */
    public function toString()
    {
        return json_encode($this->toArray());
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $compKeys = array_keys(self::$components);
        if (in_array($name, $compKeys)) {
            return new self::$components[$name](...$arguments);
        }
        throw new BuildException('Method does not exist');
    }
}
