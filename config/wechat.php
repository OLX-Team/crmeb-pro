<?php
// +----------------------------------------------------------------------
// | 微信服务相关配置
// +----------------------------------------------------------------------


return [
    //请求响应日志
    'logger' => env('APP_DEBUG', false),
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
