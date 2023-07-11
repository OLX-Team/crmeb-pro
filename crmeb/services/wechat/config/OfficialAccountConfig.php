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
 * 公众号配置
 * Class OfficialAccountConfig
 * @package crmeb\services\wechat\config
 */
class OfficialAccountConfig implements ConfigHandlerInterface
{

    /**
     * AppID
     * @var string
     */
    protected $appId;

    /**
     * AppSecret
     * @var string
     */
    protected $secret;

    /**
     * Token
     * @var string
     */
    protected $token;

    /**
     * EncodingAESKey
     * @var string
     */
    protected $aesKey;

    /**
     * 指定 API 调用返回结果的类型
     * @var string
     */
    protected $responseType = 'array';

    /**
     * @var LogCommonConfig
     */
    protected $logConfig;

    /**
     * @var HttpCommonConfig
     */
    protected $httpConfig;

    /**
     * @var bool
     */
    protected $init = false;

    /**
     * OfficialAccountConfig constructor.
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
        $this->appId = $this->appId ?: $this->httpConfig->getConfig('official.appid', '');
        $this->secret = $this->secret ?: $this->httpConfig->getConfig('official.secret', '');
        $this->token = $this->token ?: $this->httpConfig->getConfig('official.token', '');
        $this->aesKey = $this->aesKey ?: ($this->httpConfig->getConfig('official.encode', -1) > 0 ? $this->httpConfig->getConfig('official.key', '') : '');
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
     * 获取配置
     * @param string|null $key
     * @return array|mixed|null
     */
    public function get(string $key = null)
    {
        $this->init();
        if ($key) {
            if (isset($this->{$key})) {
                return $this->{$key};
            }
            if ('log' === $key) {
                return $this->logConfig->all();
            }
            if ('http' === $key) {
                return $this->httpConfig->all();
            }
        }
        return null;
    }

    /**
     * 获取所有配置
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
            'response_type' => $this->responseType,
            'log' => $this->logConfig->all(),
            'http' => $this->httpConfig->all()
        ];
    }
}
