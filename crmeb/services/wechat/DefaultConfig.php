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

use think\facade\Config;

/**
 * 默认配置
 * Class DefaultConfig
 * @package crmeb\services\wechat
 */
class DefaultConfig
{
    //小程序appid
    const MINI_APPID = 'mini.appid';
    //公众号appid
    const OFFICIAL_APPID = 'official.appid';
    //开放平台appid
    const APP_APPID = 'app.appid';
    //开放平台网页端appid
    const WEB_APPID = 'web.appid';
    //企业微信id
    const WORK_CORP_ID = 'work.corp_id';
    //商户id
    const PAY_MCHID = 'pay.mchid';
    //系统配置域名地址,携带,格式:http://www.a.com
    const COMMENT_URL = 'comment.url';

    /**
     *
     */
    const WECHAT_CONFIG = [
        //请求响应日志
        'logger' => true,
        //公用
        'comment' => [
            'url' => 'site_url',
        ],
        //小程序配置
        'mini' => [
            'appid' => 'routine_appId',
            'secret' => 'routine_appsecret',
            'notifyUrl' => '/api/pay/notify/routine',//必须携带斜杠开头
        ],
        //公众号配置
        'official' => [
            'appid' => 'wechat_appid',
            'secret' => 'wechat_appsecret',
            'token' => 'wechat_token',
            'key' => 'wechat_encodingaeskey',
            'encode' => 'wechat_encode',
        ],
        //开放平台APP
        'app' => [
            'appid' => 'wechat_app_appid',
            'secret' => 'wechat_app_appsecret',
            'token' => 'wechat_openapp_app_token',
            'key' => 'wechat_openapp_app_aes_key',
            'notifyUrl' => '/api/pay/notify/app',//必须携带斜杠开头
        ],
        //开放平台网页应用
        'web' => [
            'appid' => 'wechat_open_app_id',
            'secret' => 'wechat_open_app_secret',
            'token' => 'wechat_open_app_token',
            'key' => 'wechat_open_app_aes_key',
        ],
        //企业微信
        'work' => [
            'corp_id' => 'wechat_work_corpid',
            'token' => 'wechat_work_token',
            'key' => 'wechat_work_aes_key',
        ],
        //支付
        'pay' => [
            'mchid' => 'pay_weixin_mchid',//商户号
            'key' => 'pay_weixin_key',//支付key
            'client_cert' => 'pay_weixin_client_cert',//证书
            'client_key' => 'pay_weixin_client_key',//证书
            'notifyUrl' => '/api/pay/notify/wechat',//支付回调,必须携带斜杠开头
            'refundUrl' => '/api/pay/refund/wechat',//退款回到,必须携带斜杠开头
        ]
    ];

    /**
     * 获取配置
     * @param string $key
     * @return array|mixed|string[]|null
     */
    public static function value(string $key)
    {
        $config = [];
        if (Config::has('wechat')) {
            $config = Config::get('wechat', []);
        }
        $config = array_merge(self::WECHAT_CONFIG, $config);

        $key = explode('.', $key);
        $value = null;
        foreach ($key as $k) {
            if ($value) {
                $value = $value[$k] ?? null;
            } else {
                $value = $config[$k] ?? null;
            }
        }

        return $value;
    }
}
