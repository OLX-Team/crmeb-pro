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
 * 图片选择组件
 * Class UploadFrame
 * @package crmeb\form\components
 */
class UploadFrame extends BaseComponent implements BuildInterface
{

    /**
     * 组件名
     */
    const NAME = 'uploadFrame';

    /**
     * 规则
     * @var array
     */
    protected $rule = [
        'upload' => [
            'url' => '',
            'width' => '960px',
            'height' => '505px',
            'field' => 'att_dir',
            'maxNum' => 1,
        ],
        'field' => '',
        'title' => '',
        'value' => '',
        'info' => '',
    ];

    /**
     * UploadFrame constructor.
     * @param string $field
     * @param string $title
     * @param null $value
     */
    public function __construct(string $field, string $title, $value = null)
    {
        $this->rule['title'] = $title;
        $this->rule['field'] = $field;
        $this->rule['value'] = !is_null($value) ? $value : null;
    }

    /**
     * 设置iframe跳转地址
     * @param string $url
     * @return $this
     */
    public function url(string $url)
    {
        $this->rule['upload']['url'] = $url;
        return $this;
    }

    /**
     * 设置iframe宽
     * @param string $width
     * @return $this
     */
    public function width(string $width = '960px')
    {
        $this->rule['upload']['width'] = $width;
        return $this;
    }

    /**
     * 设置iframe高
     * @param string $height
     * @return $this
     */
    public function height(string $height = '505px')
    {
        $this->rule['upload']['height'] = $height;
        return $this;
    }

    /**
     * 设置提取字段
     * @param string $field
     * @return $this
     */
    public function field(string $field)
    {
        $this->rule['upload']['field'] = $field;
        return $this;
    }

    /**
     * 多图单图选择
     * @param int $maxNum
     * @return $this
     */
    public function maxNum(int $maxNum = 1)
    {
        $this->rule['upload']['maxNum'] = $maxNum;
        return $this;
    }

    /**
     * 设置提示
     * @param string $info
     * @return $this
     */
    public function info(string $info)
    {
        $this->rule['info'] = $info;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        if ($this->rule['upload']['maxNum'] > 1 && $this->rule['value'] && !is_array($this->rule['value'])) {
            $this->rule['value'] = [];
        }
        $this->rule['name'] = self::NAME;
        $this->before();
        return $this->rule;
    }
}
