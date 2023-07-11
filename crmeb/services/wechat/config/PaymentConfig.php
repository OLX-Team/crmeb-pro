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
 * 支付配置
 * Class PaymentConfig
 * @package crmeb\services\wechat\config
 */
class PaymentConfig implements ConfigHandlerInterface
{

    /**
     * appid
     * @var string
     */
    protected $appId;

    /**
     * 商户密钥
     * @var string
     */
    protected $mchId;

    /**
     * API密钥
     * @var string
     */
    protected $key;

    /**
     * 证书cert
     * @var string
     */
    protected $certPath;

    /**
     * 证书key
     * @var string
     */
    protected $keyPath;

    /**
     * 支付异步回调地址
     * @var string
     */
    protected $notifyUrl;

    /**
     * 退款异步通知
     * @var string
     */
    protected $refundUrl;

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
     * PaymentConfig constructor.
     * @param LogCommonConfig $config
     * @param HttpCommonConfig $commonConfig
     */
    public function __construct(LogCommonConfig $config, HttpCommonConfig $commonConfig)
    {
        $this->logConfig = $config;
        $this->httpConfig = $commonConfig;
    }


    protected function init()
    {
        if ($this->init) {
            return;
        }
        $this->init = true;
        $this->appId = $this->appId ?: $this->httpConfig->getConfig(DefaultConfig::OFFICIAL_APPID, '');
        $this->mchId = $this->mchId ?: $this->httpConfig->getConfig(DefaultConfig::PAY_MCHID, '');
        $this->key = $this->key ?: $this->httpConfig->getConfig('pay.key', '');
        $this->certPath = $this->certPath ?: str_replace('//', '/', public_path() . $this->httpConfig->getConfig('pay.client_cert', ''));
        $this->keyPath = $this->keyPath ?: str_replace('//', '/', public_path() . $this->httpConfig->getConfig('pay.client_key', ''));
        $this->notifyUrl = $this->notifyUrl ?: trim($this->httpConfig->getConfig(DefaultConfig::COMMENT_URL)) . DefaultConfig::value('pay.notifyUrl');
        $this->refundUrl = $this->refundUrl ?: trim($this->httpConfig->getConfig(DefaultConfig::COMMENT_URL)) . DefaultConfig::value('pay.refundUrl');
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
     * 设置单个配置
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
     * 获取单个配置
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
     * 全部配置
     * @return array
     */
    public function all(): array
    {
        $this->init();
        return [
            'app_id' => $this->appId,
            'mch_id' => $this->mchId,
            'key' => $this->key,
            'cert_path' => $this->certPath,
            'key_path' => $this->keyPath,
            'notify_url' => $this->notifyUrl,
            'log' => $this->logConfig->all(),
            'http' => $this->httpConfig->all()
        ];
    }
}
