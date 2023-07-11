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

/**
 * 开放平台网页端配置
 * Class OpenWebConfig
 * @package crmeb\services\wechat\config
 */
class OpenWebConfig implements ConfigHandlerInterface
{

    /**
     * Appid
     * @var string
     */
    protected $appId;

    /**
     * Appsecret
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $aesKey;

    /**
     * @var bool
     */
    protected $init = false;

    /**
     * @var HttpCommonConfig
     */
    protected $config;

    /**
     * OpenWebConfig constructor.
     * @param HttpCommonConfig $config
     */
    public function __construct(HttpCommonConfig $config)
    {
        $this->config = $config;
    }

    /**
     * OpenWebConfig constructor.
     */
    public function init()
    {
        if ($this->init) {
            return;
        }
        $this->init = true;
        $this->appId = $this->appId ?: $this->config->getConfig('web.appid', '');
        $this->secret = $this->secret ?: $this->config->getConfig('web.secret', '');
        $this->token = $this->token ?: $this->config->getConfig('web.token', '');
        $this->aesKey = $this->aesKey ?: $this->config->getConfig('web.key', '');
    }

    /**
     * 获取配置
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->config->getConfig($key, $default);
    }

    /**
     * @param string $key
     * @param $value
     * @return $this|mixed
     */
    public function set(string $key, $value)
    {
        $this->{$key} = $value;
        return $this;
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function get(string $key = null)
    {
        $this->init();
        if ('http' === $key) {
            return $this->config->all();
        }
        return $this->{$key};
    }

    /**
     * @return array
     */
    public function all(): array
    {
        $this->init();
        return [
            'app_id' => $this->appId,
            'secret' => $this->secret,
            'token' => $this->token,
            'aes_key' => $this->aesKey,
            'http' => $this->config->all()
        ];
    }
}
