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

namespace crmeb\services\erp;

use crmeb\services\HttpService;
use crmeb\services\CacheService;
use think\facade\Config;
use think\facade\Log;
use think\helper\Str;
use Psr\SimpleCache\CacheInterface;

/**
 * Class AccessTokenServeService
 * @package crmeb\services
 */
class AccessToken extends HttpService
{
    /**
     * 配置
     * @var string
     */
    protected $account;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $apiUrl;

    /**
     * 授权登录账号
     * @var string
     */
    protected $authAccount;

    /**
     * 授权登录密码
     * @var string
     */
    protected $authPassword;

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * 驱动类型
     * @var string
     */
    protected $name;

    /**
     * 配置文件名
     * @var string
     */
    protected $configFile;

    /**
     * 缓存token
     * @var string
     */
    protected $cacheTokenPrefix = "_crmeb_erp";

    /**
     * 刷新token
     * @var string
     */
    protected $cacheRefreshTokenPrefix = "_crmeb_erp_re";

    /**
     * AccessTokenServeService constructor.
     * AccessToken constructor.
     * @param string $name
     * @param string $configFile
     * @param array $config
     * @param CacheInterface|null $cache
     */
    public function __construct(string $name, string $configFile, array $config, ?CacheInterface $cache = null)
    {
        if (!$cache) {
            /** @var CacheService $cache */
            $cache = app()->make(CacheService::class);
        }
        $this->account = isset($config["app_key"]) ? $config["app_key"] : config($configFile . '.stores.' . $name . '.app_key', '');
        $this->secret = isset($config["secret"]) ? $config["secret"] : config($configFile . '.stores.' . $name . '.secret', '');
        $this->authAccount = $config['login_account'] ?? config($configFile . '.stores.' . $name . '.login_account', '');
        $this->authPassword = $config['login_password'] ?? config($configFile . '.stores.' . $name . '.login_password', '');
        $this->cache = $cache;
        $this->name = $name;
        $this->configFile = $configFile;
        $this->apiUrl = Config::get($configFile . '.stores.' . $name . '.url', '');
        $this->apiUrl = 'https://openapi.jushuitan.com';
    }

    /**
     * 获取配置
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'account' => $this->account,
            'secret' => $this->secret
        ];
    }

    /**
     * 获取请求链接
     * @param string $url
     * @return string
     */
    public function getApiUrl(string $url = ''): string
    {
        return $url ? $this->apiUrl . $url : $this->apiUrl;
    }

    /**
     * 获取appKey
     * @return string
     */
    public function getAccount(): string
    {
        return $this->account;
    }

    /**
     * @return mixed|string
     */
    public function getAuthAccount()
    {
        return $this->authAccount;
    }

    /**
     * @return mixed|string
     */
    public function getAuthPassword()
    {
        return $this->authPassword;
    }

    /**
     * 获取appSecret
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * 获取token
     * @return string
     * @throws \Exception
     */
    public function getAccessToken(): ?string
    {
        if (isset($this->accessToken)) {
            return $this->accessToken;
        }

        /**
         * @see   getJushuitanAccessToken
         */
        $action = 'get' . Str::studly($this->name) . 'AccessToken';
        if (method_exists($this, $action)) {
            return $this->{$action}();
        } else {
            throw new \RuntimeException(__CLASS__ . '->' . $action . '(),Method not worn in');
        }
    }

    /**
     * 获取聚水潭token
     * @return mixed|null|string
     * @throws \Exception
     */
    protected function getJushuitanAccessToken(): ?string
    {
        //读缓存
        $cacheKey = md5($this->account . '_' . $this->secret . $this->cacheTokenPrefix);
        $this->accessToken = $this->cache->get($cacheKey);

        //需要前端异步授权
        if (empty($this->accessToken)) {
            throw new \RuntimeException("请跳转授权", 610);
        }

        return $this->accessToken;
    }

    /**
     * 设置AccessToken缓存
     * @param string $accessToken
     * @param int $expiresIn
     * @return bool
     */
    public function setAccessToken(string $accessToken, int $expiresIn): bool
    {
        if (empty($accessToken) || !is_numeric($expiresIn) || $expiresIn <= 0) {
            return false;
        }
        //写缓存
        $cacheKey = md5($this->account . '_' . $this->secret . $this->cacheTokenPrefix);
        $this->cache->redisHandler()->tag('erp_shop')->set($cacheKey, $accessToken, $expiresIn);

        $this->accessToken = $accessToken;

        return true;
    }

    /**
     * 设置AccessToken提前过期缓存
     * @param string $accessToken
     * @param int $expiresIn
     * @return bool
     */
    public function setTokenExpire(string $accessToken, int $expiresIn): bool
    {
        if (empty($accessToken) || !is_numeric($expiresIn) || $expiresIn <= 0) {
            return false;
        }
        //写缓存
        $cacheKey = md5($this->account . '_' . $this->secret . '_epr_expire');
        $this->cache->redisHandler()->tag('erp_shop')->set($cacheKey, $accessToken, $expiresIn);

        $this->accessToken = $accessToken;

        return true;
    }

    /**
     * 获取提前过期缓存
     * @return bool|mixed|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getTokenExpire()
    {
        $cacheKey = md5($this->account . '_' . $this->secret . '_epr_expire');
        return $this->cache->get($cacheKey);
    }

    /**
     * 设置refreshToken缓存
     * @param string refreshToken
     * @return bool
     */
    public function setRefreshToken(string $refreshToken): bool
    {
        if (empty($refreshToken)) {
            return false;
        }
        //写缓存
        $cacheKey = md5($this->account . '_' . $this->secret . $this->cacheRefreshTokenPrefix);
        $this->cache->redisHandler()->tag('erp_shop')->set($cacheKey, $refreshToken);

        return true;
    }

    /**
     * 获取refreshToken缓存
     * @param string refreshToken
     * @return string
     */
    public function getRefreshToken(): string
    {
        //读缓存
        $cacheKey = md5($this->account . '_' . $this->secret . $this->cacheRefreshTokenPrefix);

        return $this->cache->get($cacheKey);
    }


}
