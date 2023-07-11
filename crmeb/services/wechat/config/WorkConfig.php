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
use crmeb\services\wechat\contract\WorkAppConfigHandlerInterface;
use crmeb\services\wechat\DefaultConfig;

/**
 * 企业微信配置
 * Class WorkConfig
 * @package crmeb\services\wechat\config
 */
class WorkConfig implements ConfigHandlerInterface
{

    //应用
    const TYPE_APP = 'app';
    //客户联系
    const TYPE_USER = 'user';
    //通讯录同步
    const TYPE_ADDRESS = 'address';
    //客服
    const TYPE_KEFU = 'kefu';
    //审批
    const TYPE_APPROVE = 'approve';
    //会议室
    const TYPE_MEETING = 'meeting';
    //自建应用
    const TYPE_USER_APP = 'build';

    /**
     * @var string
     */
    protected $corpId;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $aesKey;

    /**
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
     * @var WorkAppConfigHandlerInterface
     */
    protected $handler;

    /**
     * @var array
     */
    protected $appConfig;

    /**
     * WorkConfig constructor.
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
        $this->corpId = $this->corpId ?: $this->httpConfig->getConfig(DefaultConfig::WORK_CORP_ID, '');
        $this->token = $this->token ?: $this->httpConfig->getConfig('work.token', '');
        $this->aesKey = $this->aesKey ?: $this->httpConfig->getConfig('work.key', '');
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
     * 获取全部值
     * @return array
     */
    public function all(): array
    {
        $this->init();
        return [
            'corp_id' => $this->corpId,
            'token' => $this->token,
            'aes_key' => $this->aesKey,
            'response_type' => $this->responseType,
            'log' => $this->logConfig->all(),
            'http' => $this->httpConfig->all()
        ];
    }

    /**
     * 获取应用配置
     * @param string $type
     * @return array
     */
    public function getAppConfig(string $type): array
    {
        if (!isset($this->appConfig[$type])) {
            /** @var WorkAppConfigHandlerInterface $make */
            $make = app()->make($this->handler);
            if (!$this->corpId) {
                $this->init();
            }
            $this->appConfig[$type] = $make->getAppConfig($this->corpId, $type);
        }
        return $this->appConfig[$type];
    }

    /**
     * 设置
     * @param string $handler
     * @return $this
     */
    public function setHandler(string $handler)
    {
        $this->handler = $handler;
        return $this;
    }
}
