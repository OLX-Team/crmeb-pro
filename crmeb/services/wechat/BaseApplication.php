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


use crmeb\services\wechat\contract\BaseApplicationInterface;
use think\facade\Log;

/**
 * Class BaseApplication
 * @package crmeb\services\wechat
 */
abstract class BaseApplication implements BaseApplicationInterface
{

    //app端
    const APP = 'app';
    //h5端、公众端
    const WEB = 'web';
    //小程序端
    const MINI = 'mini';
    //开发平台
    const OPEN = 'open';
    //pc端
    const PC = 'pc';

    /**
     * 访问端
     * @var string
     */
    protected $accessEnd;

    /**
     * @var array
     */
    protected static $property = [];

    /**
     * @var string
     */
    protected $pushMessageHandler;

    /**
     * Debug
     * @var bool
     */
    protected $debug = true;

    /**
     * 设置消息处理类
     * @param string $handler
     * @return $this
     */
    public function setPushMessageHandler(string $handler)
    {
        $this->pushMessageHandler = $handler;
        return $this;
    }

    /**
     * 设置访问端
     * @param string $accessEnd
     * @return $this
     */
    public function setAccessEnd(string $accessEnd)
    {
        if (in_array($accessEnd, [self::APP, self::WEB, self::MINI])) {
            $this->accessEnd = $accessEnd;
        }
        return $this;
    }

    /**
     * 自动获取访问端
     * @param \think\Request $request
     * @return string
     */
    public function getAuthAccessEnd(\think\Request $request)
    {
        if (!$this->accessEnd) {
            try {
                if ($request->isApp()) {
                    $this->accessEnd = self::APP;
                } else if ($request->isPc()) {
                    $this->accessEnd = self::PC;
                } else if ($request->isWechat() || $request->isH5()) {
                    $this->accessEnd = self::WEB;
                } else if ($request->isRoutine()) {
                    $this->accessEnd = self::MINI;
                } else {
                    $this->accessEnd = self::WEB;
                }
            } catch (\Throwable $e) {
                $this->accessEnd = self::WEB;
            }
        }
        return $this->accessEnd;
    }

    /**
     * 记录错误日志
     * @param \Throwable $e
     */
    protected static function error(\Throwable $e)
    {
        static::instance()->debug && Log::error([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);
    }

    /**
     * 请求日志
     * @param string $message
     * @param $request
     * @param $response
     */
    protected static function logger(string $message, $request, $response)
    {
        $debug = static::instance()->debug;

        if ($debug) {
            Log::info([
                'message' => $message,
                'request' => json_encode($request),
                'response' => json_encode($response)
            ]);
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (in_array($name, array_keys(static::$property))) {
            $name = static::$property[$name];
            return static::instance()->application()->{$name};
        }
        throw new WechatException('方法不存在');
    }
}
