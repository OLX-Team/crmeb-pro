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
use crmeb\services\wechat\DefaultConfig;

/**
 * 小程序配置
 * Class MiniProgramConfig
 * @package crmeb\services\wechat\config
 */
class MiniProgramConfig implements ConfigHandlerInterface
{

    /**
     * APPid
     * @var string
     */
    protected $appId;

    /**
     * APPsecret
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $responseType = 'array';

    /**
     * 日志记录
     * @var LogCommonConfig
     */
    protected $logConfig;

    /**
     * http配置
     * @var HttpCommonConfig
     */
    protected $httpConfig;

    /**
     * 是否初始化过
     * @var bool
     */
    protected $init = false;

    /**
     * MiniProgramConfig constructor.
     * @param LogCommonConfig $config
     * @param HttpCommonConfig $commonConfig
     */
    public function __construct(LogCommonConfig $config, HttpCommonConfig $commonConfig)
    {
        $this->logConfig = $config;
        $this->httpConfig = $commonConfig;
    }

    /**
     * 初始化
     */
    protected function init()
    {
        if ($this->init) {
            return;
        }
        $this->init = true;
        $this->appId = $this->appId ?: $this->httpConfig->getConfig(DefaultConfig::MINI_APPID, '');
        $this->secret = $this->secret ?: $this->httpConfig->getConfig('mini.secret', '');
    }


    /**
     * 获取配置
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->httpConfig->getConfig($key, $default);
    }

    /**
     * 设置
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
     * @return array|mixed
     */
    public function get(string $key = null)
    {
        $this->init();
        if ('log' === $key) {
            return $this->logConfig->all();
        }
        if ('http' === $key) {
            return $this->httpConfig->all();
        }
        return $this->{$key};
    }

    /**
     * 全部
     * @return array
     */
    public function all(): array
    {
        $this->init();
        return [
            'app_id' => $this->appId,
            'secret' => $this->secret,
            'response_type' => $this->responseType,
            'log' => $this->logConfig->all(),
            'http' => $this->httpConfig->all()
        ];
    }
}
