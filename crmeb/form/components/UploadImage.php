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
 * 文件上传组件
 * Class UploadImage
 * @package crmeb\form\components
 */
class UploadImage extends BaseComponent implements BuildInterface
{

    // 组件名
    const NAME = 'uploadImage';
    //图片类型
    const IMAGE = 'image';
    //文件类型
    const FILE = 'file';
    //上传支持类型
    const TYPE = [self::IMAGE, self::FILE];

    /**
     * 规则
     * @var array
     */
    protected $rule = [
        'upload' => [
            'url' => '',
            'size' => 2097152,
            'format' => [],
            'headers' => [],
            'maxNum' => 1,
        ],
        'field' => '',
        'type' => '',
        'title' => '',
        'icon' => '',
        'info' => '',
        'value' => ''
    ];

    /**
     * UploadImage constructor.
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
     * 上传地址
     * @param string $url
     * @return $this
     */
    public function url(string $url)
    {
        $this->rule['upload']['url'] = $url;
        return $this;
    }


    /**
     * 上传类型
     * @param string $type
     * @return $this
     */
    public function type(string $type)
    {
        $this->rule['type'] = in_array($type, self::TYPE) ? $type : '';
        return $this;
    }

    /**
     * 上传展示icon
     * @param string $icon
     * @return $this
     */
    public function icon(string $icon)
    {
        $this->rule['icon'] = $icon;
        return $this;
    }


    /**
     * 上传文件headers
     * @param array $headers
     * @return $this
     */
    public function headers(array $headers = [])
    {
        $this->rule['upload']['headers'] = (object)$headers;
        return $this;
    }

    /**
     * 上传文件大小
     * @param string $size
     * @return $this
     */
    public function size(string $size)
    {
        $this->rule['upload']['size'] = $size;
        return $this;
    }

    /**
     * 上传文件类型
     * @param array $format
     * @return $this
     */
    public function format(array $format = [])
    {
        $this->rule['upload']['format'] = $format;
        return $this;
    }

    /**
     * 组件提示
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
        $this->rule['name'] = self::NAME;
        $this->before();
        return $this->rule;
    }
}
