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
 * 日志
 * Class LogCommonConfig
 * @package crmeb\services\wechat\config
 */
class LogCommonConfig implements ConfigHandlerInterface
{

    /**
     * 日志配置
     * @var array
     */
    protected $log = [];

    /**
     * LogCommonConfig constructor.
     */
    public function __construct()
    {
        $default = env('APP_DEBUG', false) ? 'prod' : 'dev';
        $this->log = [
            'default' => $default, // 默认使用的 channel，生产环境可以改为下面的 prod
            'channels' => [
                // 测试环境
                'dev' => [
                    'driver' => 'single',
                    'path' => runtime_path() . 'easywechat.log',
                    'level' => 'debug',
                ],
                // 生产环境
                'prod' => [
                    'driver' => 'daily',
                    'path' => runtime_path() . 'easywechat.log',
                    'level' => 'info',
                ],
            ],
        ];
    }

    public function set(string $key, $value)
    {

    }

    /**
     * @param string|null $key
     * @return array|mixed
     */
    public function get(string $key = null)
    {
        return $this->log;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->log;
    }
}
