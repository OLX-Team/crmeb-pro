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

namespace crmeb\services\wechat;


use crmeb\services\wechat\config\OpenWebConfig;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\BadRequestException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use EasyWeChat\OpenPlatform\Application;
use Symfony\Component\HttpFoundation\Request;
use think\Response;
use Yurun\Util\Swoole\Guzzle\SwooleHandler;

/**
 * 开放平台
 * Class OpenPlatform
 * @package crmeb\services\wechat
 */
class OpenPlatform extends BaseApplication
{

    /**
     * @var OpenWebConfig
     */
    protected $config;

    /**
     * @var Application
     */
    protected $application;

    /**
     * OpenPlatform constructor.
     */
    public function __construct()
    {
        /** @var OpenWebConfig config */
        $this->config = app()->make(OpenWebConfig::class);
        $this->debug = DefaultConfig::value('logger');
    }

    /**
     * @return OpenPlatform
     */
    public static function instance()
    {
        return app()->make(static::class);
    }

    /**
     * @return Application
     */
    public function application()
    {
        if (!$this->application) {
            $this->application = Factory::openPlatform($this->config->all());
            $request = request();
            $this->application['guzzle_handler'] = SwooleHandler::class;
            $this->application->rebind('request', new Request($request->get(), $request->post(), [], [], [], $request->server(), $request->getContent()));
        }
        return $this->application;
    }

    /**
     * 服务端
     * @return Response
     * @throws BadRequestException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws \ReflectionException
     */
    public static function serve(): Response
    {
        $make = self::instance();
        $make->application()->server->push($make->pushMessageHandler);
        $response = $make->application()->server->serve();
        return response($response->getContent());
    }

}
