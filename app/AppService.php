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
namespace app;

use app\listener\wechat\OffcialAccountListener;
use app\listener\wechat\OpenPlatformListener;
use app\listener\wechat\WorkListener;
use crmeb\services\SystemConfigService;
use app\services\work\WorkConfigServices;
use crmeb\services\GroupDataService;
use crmeb\services\wechat\config\HttpCommonConfig;
use crmeb\services\wechat\config\LogCommonConfig;
use crmeb\services\wechat\config\WorkConfig;
use crmeb\services\wechat\OfficialAccount;
use crmeb\services\wechat\OpenPlatform;
use crmeb\services\wechat\Work;
use crmeb\utils\Json;
use think\Service;
use Yurun\Util\Swoole\Guzzle\SwooleHandler;
use GuzzleHttp\DefaultHandler;

/**
 * Class AppService
 * @package app
 */
class AppService extends Service
{

    public $bind = [
        'json' => Json::class,
        'sysConfig' => SystemConfigService::class,
        'sysGroupData' => GroupDataService::class
    ];

    public function boot()
    {
        defined('DS') || define('DS', DIRECTORY_SEPARATOR);
        DefaultHandler::setDefaultHandler(SwooleHandler::class);
    }

    /**
     * 注册
     */
    public function register()
    {
        //http配置服务
        $this->app->bind(HttpCommonConfig::class, function () {
            return (new HttpCommonConfig())->setServe(\app\services\system\config\SystemConfigServices::class);
        });
        //公众号
        $this->app->bind(OfficialAccount::class, function () {
            return (new OfficialAccount)->setPushMessageHandler(OffcialAccountListener::class);
        });
        //开放平台
        $this->app->bind(OpenPlatform::class, function () {
            return (new OpenPlatform)->setPushMessageHandler(OpenPlatformListener::class);
        });
        //实例化企业微信配置
        $this->app->bind(WorkConfig::class, function () {
            return (new WorkConfig(new LogCommonConfig(), $this->app->make(HttpCommonConfig::class)))->setHandler(WorkConfigServices::class);
        });
        //企业微信
        $this->app->bind(Work::class, function () {
            return (new Work)->setPushMessageHandler(WorkListener::class)
                ->setConfigHandler(WorkConfigServices::class);
        });
    }

}
