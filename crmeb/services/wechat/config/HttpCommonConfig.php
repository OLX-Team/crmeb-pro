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

namespace crmeb\services\wechat\config;

use crmeb\services\wechat\contract\ConfigHandlerInterface;
use crmeb\services\wechat\contract\ServeConfigInterface;
use crmeb\services\wechat\DefaultConfig;

/**
 * Http请求配置
 * Class HttpCommonConfig
 * @package crmeb\services\wechat\config
 */
class HttpCommonConfig implements ConfigHandlerInterface
{
    /**
     * @var bool[]
     */
    protected $config = [
        'verify' => false,
    ];

    /**
     * @var string
     */
    protected $serve;

    /**
     * @param string $serve
     * @return $this
     */
    public function setServe(string $serve)
    {
        $this->serve = $serve;
        return $this;
    }

    /**
     * 获取服务端实例
     * @return ServeConfigInterface
     */
    public function getServe()
    {
        return app()->make($this->serve);
    }

    /**
     * 直接获取配置
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->getServe()->getConfig(DefaultConfig::value($key), $default);
    }

    /**
     * @param string $key
     * @param $value
     * @return $this|mixed
     */
    public function set(string $key, $value)
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * @param string|null $key
     * @return bool|bool[]|mixed
     */
    public function get(string $key = null)
    {
        if ($key) {
            return $this->config[$key];
        }
        return $this->config;
    }

    /**
     * @return array|bool[]
     */
    public function all(): array
    {
        return $this->config;
    }
}
