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

// 事件定义文件
return [
    'bind' => [

    ],

    'listen' => [
        'AppInit' => [],
        'HttpRun' => [],
        'HttpEnd' => [],
        'LogLevel' => [],
        'LogWrite' => [],
        //swoole 初始化事件
        'swoole.init' => [
            \crmeb\listeners\InitSwooleLockListen::class, //初始化
        ],
        //swoole 启动事件
        'swoole.workerStart' => [
            \crmeb\listeners\SwooleCronListen::class, //定时任务
        ],
        'swoole.workerExit' => [],
        'swoole.workerError' => [],
        'swoole.workerStop' => [],
        'swoole.shutDown' => [\crmeb\listeners\SwooleShutdownListen::class],//swoole 停止事件
        'swoole.websocket.user' => [\app\webscoket\handler\UserHandler::class],//socket 用户调用事件
        'swoole.websocket.admin' => [\app\webscoket\handler\AdminHandler::class],//socket 后台事件
        'swoole.websocket.kefu' => [\app\webscoket\handler\KefuHandler::class],//socket 客服事件
        'swoole.websocket.store' => [\app\webscoket\handler\StoreHandler::class],//socket 门店事件
        //定时执行
        'crontab' => file_exists(root_path() . "public/install/") && !file_exists(root_path() . "public/install/install.lock") ? [] : [
            \app\listener\order\AutoCancel::class,//自动取消订单
            \app\listener\order\AutoTake::class,//自动确认收货
            \app\listener\user\AutoClearIntegral::class,//自动清空用户积分
            \app\listener\user\AutoOffUserSvip::class,//自动取消用户到期svip
            \app\listener\user\AutoAgent::class,//自动解绑上下级
            \app\listener\system\AutoClearPoster::class,//自动清除昨日海报
            \app\listener\system\AutoSmsCode::class,//更新短信状态
            \app\listener\live\AutoUpdateLive::class,//自动更新直播产品状态和直播间状态
            \app\listener\pink\AutoPink::class,//拼团状态自动更新
            \app\listener\product\AutoShow::class,//自动上下架商品
            \app\listener\work\AutoChannel::class,//渠道码定时任务
            \app\listener\work\AutoMoment::class,//定时创建发送朋友圈任务
            \app\listener\work\AutoGroupTask::class,//定时发送群发任务
            \app\listener\erp\AuthErpLogin::class,//定时检测erp授权登录获取token
        ],
        'user.register' => [\app\listener\user\Register::class], //用户注册事件
        'user.cancelUser' => [\app\listener\user\CancelUser::class], //用户注销事件
        'user.login' => [\app\listener\user\Login::class], //用户登录事件
        'user.vipPay' => [\app\listener\user\VipUser::class], //用户购买VIP支付成功
        'user.recharge' => [\app\listener\user\Recharge::class], //用户充值支付成功
        'user.rechargeRefund' => [\app\listener\user\RechargeRefund::class], //用户充值退款成功
        'user.extract' => [\app\listener\user\Extract::class], //用户申请提现
        'user.work' => [\app\listener\user\UserBindWorkMember::class],
        'user.create' => [\app\listener\user\CreateSuccess::class],//用户创建事件
        'user.update' => [\app\listener\user\UpdateSuccess::class],//用户更新事件
        'user.status' => [],//修改用户状态事件
        'user.client' => [\app\listener\user\UserBindClient::class],//用户绑定企业微信客户
        'user.member.update' => [\app\listener\user\MemberUpdateSuccess::class],//会员更新事件
        'user.address.update' => [\app\listener\user\AddressUpdateSuccess::class],//修改用户状态事件
        'pay.notify' => [\app\listener\pay\PayNotifyListener::class],//支付异步回调事件
        'pay.scan.notify' => [\app\listener\pay\ScannedNotifyListener::class],//支付扫码异步回调
        'pay.refunded.notify' => [\app\listener\pay\ScannedNotifyListener::class],//支付扫码异步回调
        'cart.add' => [\app\listener\order\AddCart::class], //加入购物车事件
        'order.create' => [\app\listener\order\Create::class], //订单创建事件
        'order.price' => [\app\listener\order\PriceRevision::class], //订单改价格事件
        'order.applyRefund' => [\app\listener\order\ApplyRefund::class], //订单退款申请事件
        'order.refuseRefund' => [\app\listener\order\RefuseRefund::class], //订单拒绝退款申请事件
        'order.refund' => [\app\listener\order\Refund::class], //订单退款事件
        'order.pay' => [\app\listener\order\Pay::class], //订单支付事件
        'order.delivery' => [\app\listener\order\Delivery::class], //订单发货事件
        'order.take' => [\app\listener\order\Take::class], //订单收货事件
        'order.comment' => [\app\listener\order\Comment::class], //订单评价事件
        'order.cancel' => [\app\listener\order\Cancel::class], //订单取消删除事件
        'notice.notice' => [\app\listener\notice\Notice::class], //通知->消息事件
        'admin.login' => [\app\listener\admin\LoginSuccess::class],//后台登录事件
        'admin.logout' => [\app\listener\admin\LogoutSuccess::class],//后台退出事件
        'product.create' => [\app\listener\product\CreateSuccess::class],//商品创建事件
        'product.delete' => [\app\listener\product\DeleteSuccess::class],//商品删除事件
        'product.status' => [\app\listener\product\StatusSuccess::class],//商品状态事件
        'product.reply.update' => [\app\listener\product\ReplyUpdateSuccess::class],//个更新评论事件
        'product.shipping.update' => [\app\listener\product\ShippingUpdateSuccess::class],//修改运费模板事件
        'store.create' => [\app\listener\store\StoreSuccess::class],//创建门店事件
        'store.delete' => [\app\listener\store\DeleteSuccess::class],//删除门店事件
        'store.status' => [\app\listener\store\StatusSuccess::class],//门店状态事件
        'diy.update' => [\app\listener\system\DiyUpdateSuccess::class],//DIY更新事件
        'config.create' => [\app\listener\config\CreateSuccess::class],//创建配置事件
        'config.delete' => [\app\listener\config\DeleteSuccess::class],//删除配置事件
        'config.status' => [\app\listener\config\StatusSuccess::class],//删除配置事件
        'sms.sendAfter' => [\app\listener\sms\SendAfterSuccess::class],//短信发送后事件
        'work.welcome' => [\app\listener\work\WelcomeSendListener::class],//企业微信欢迎语事件
        'work.label' => [\app\listener\work\ClientLabelListener::class],//企业微信客户标签
        'work.user' => [\app\listener\work\ClientBindUserListener::class],//企业微信客户绑定商城用户
        'work.message' => [\app\listener\work\MessageSendListener::class],//企业微信应用消息发送
    ],

    'subscribe' => [

    ],
];
