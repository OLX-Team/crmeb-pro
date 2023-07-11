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

namespace crmeb\services;

use app\services\system\config\SystemConfigServices;
use crmeb\utils\Arr;
use think\facade\Config;
use think\facade\Db;

/** 获取系统配置服务类
 * Class SystemConfigService
 * @package service
 */
class SystemConfigService
{
    /**
     * 缓存前缀字符
     */
    const CACHE_SYSTEM = 'system_config';
    /**
     * 过期时间
     */
    const EXPIRE_TIME = 30 * 24 * 3600;

    /**
     * @var int
     */
    protected static $storeId = 0;

    /**
     * 获取配置缓存前缀
     * @return string
     */
    public static function getTag()
    {
        return Config::get('cache.stores.redis.tag_prefix') . 'cahce_' . self::CACHE_SYSTEM;
    }

    /**
     * @param int $storeId
     */
    public static function setStore(int $storeId)
    {
        self::$storeId = $storeId;
    }

    /**
     * 获取单个配置效率更高
     * @param $key
     * @param string $default
     * @param bool $isCaChe 是否获取缓存配置
     * @return bool|mixed|string
     */
    public static function get(string $key, $default = '', bool $isCaChe = false)
    {
        $cacheName = self::CACHE_SYSTEM . ':' . $key . (self::$storeId ? '_' . self::$storeId : '');
        $callable = function () use ($key) {
            event('get.config');
            /** @var SystemConfigServices $service */
            $service = app()->make(SystemConfigServices::class);
            return $service->getConfigValue($key, null, self::$storeId);
        };

        try {
            if ($isCaChe) {
                return $callable();
            }
            $value = CacheService::redisHandler(self::getTag())->remember($cacheName, $callable, self::EXPIRE_TIME);
            self::$storeId = 0;
            return $value;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * 获取多个配置
     * @param array $keys 示例 [['appid','1'],'appkey']
     * @param bool $isCaChe 是否获取缓存配置
     * @return array
     */
    public static function more(array $keys, bool $isCaChe = false)
    {
        $cacheName = self::CACHE_SYSTEM . ':' . md5(implode(',', $keys) . (self::$storeId ? '_' . self::$storeId : ''));
        $callable = function () use ($keys) {
            /** @var SystemConfigServices $service */
            $service = app()->make(SystemConfigServices::class);
            return Arr::getDefaultValue($keys, $service->getConfigAll($keys, self::$storeId));
        };
        try {
            if ($isCaChe)
                return $callable();

            $value = CacheService::redisHandler(self::getTag())->remember($cacheName, $callable, self::EXPIRE_TIME);
            self::$storeId = 0;
            return $value;
        } catch (\Throwable $e) {
            return Arr::getDefaultValue($keys);
        }
    }

    /**
     * 清空配置缓存
     * @return bool|void
     */
    public static function clear()
    {
        try {
            return CacheService::redisHandler(self::getTag())->clear();
        } catch (\Throwable $e) {
            \think\facade\Log::error('清空配置缓存失败：原因：' . $e->getMessage());
            return false;
        }
    }

}
