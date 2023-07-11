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


use crmeb\form\BuildInterface;

/**
 * 警告框
 * Class Alert
 * @package crmeb\form\components
 */
class Alert implements BuildInterface
{

    //组件名
    const NAME = 'alert';
    //提示类型
    const INFO = 'info';
    //成功类型
    const SUCCESS = 'success';
    //警告类型
    const WARNING = 'warning';
    //错误类型
    const ERROR = 'error';
    //组件类型
    const  TYPE = [self::INFO, self::SUCCESS, self::WARNING, self::ERROR];

    /**
     * 规则
     * @var array
     */
    protected $rule = [
        'title' => '',
        'type' => '',
        'closable' => false,
        'showIcon' => false
    ];

    /**
     * Alert constructor.
     * @param string $title
     * @param string $type
     * @param bool $closable
     * @param bool $showIcon
     */
    public function __construct(string $title, string $type = '', bool $closable = false, bool $showIcon = false)
    {
        $this->rule['type'] = in_array($type, self::TYPE) ? $type : '';
        $this->rule['title'] = $title;
        $this->rule['closable'] = $closable;
        $this->rule['showIcon'] = $showIcon;
    }

    /**
     * 设置类型
     * @param string $type
     * @return $this
     */
    public function type(string $type)
    {
        $this->rule['type'] = in_array($type, self::TYPE) ? $type : '';
        return $this;
    }

    /**
     * 是否可关闭
     * @param bool $closable
     * @return $this
     */
    public function closable(bool $closable = false)
    {
        $this->rule['closable'] = $closable;
        return $this;
    }

    /**
     * 是否展示图标
     * @param bool $showIcon
     * @return $this
     */
    public function showIcon(bool $showIcon = false)
    {
        $this->rule['showIcon'] = $showIcon;
        return $this;
    }

    public function toArray(): array
    {
        $this->rule['name'] = self::NAME;
        return $this->rule;
    }
}
