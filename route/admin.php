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
use app\http\middleware\InstallMiddleware;
use think\facade\Route;
use think\facade\Config;
use think\Response;

/**
 * 后台管理路由配置
 */
Route::group('adminapi', function () {

    /**
     * 无需授权的接口
     */
    Route::group(function () {
        //用户名密码登录
        Route::post('login', 'Login/login')->name('AdminLogin')->option(['real_name' => '用户名密码登录']);
        //短信登录
        Route::post('mobile_login', 'Login/mobileLogin')->name('mobileLogin')->option(['real_name' => '短信登录']);
        //短信重置密码
        Route::post('reset_pwd', 'Login/resetPwd')->name('resetPwd')->option(['real_name' => '短信重置密码']);
        //后台登录页面数据
        Route::get('login/info', 'Login/info')->option(['real_name' => '登录信息']);
        //下载文件
        Route::get('download', 'PublicController/download')->option(['real_name' => '下载文件']);
        //获取ERP开关配置
        Route::get('erp/config', 'PublicController/getErpConfig')->option(['real_name' => '获取ERP开关配置']);
        //验证码
        Route::get('captcha_pro', 'Login/captcha')->name('')->option(['real_name' => '获取验证码']);
//        Route::get('index', 'Test/index')->option(['real_name' => '测试地址']);

//        Route::get('r', 'Test/rule')->option(['real_name' => '查看路由地址']);
    })->middleware(\app\http\middleware\AllowOriginMiddleware::class);
    /**
     * 文件下载、导出相关路由
     */
    Route::group(function () {
        //下载备份记录表
        Route::get('backup/download', 'v1.system.SystemDatabackup/downloadFile')->option(['real_name' => '下载表备份记录']);
        //首页统计数据
        Route::get('home/header', 'Common/homeStatics')->option(['real_name' => '首页统计数据']);
        //首页订单图表
        Route::get('home/order', 'Common/orderChart')->option(['real_name' => '首页订单图表']);
        //首页用户图表
        Route::get('home/user', 'Common/userChart')->option(['real_name' => '首页用户图表']);
        //首页交易额排行
        Route::get('home/rank', 'Common/purchaseRanking')->option(['real_name' => '首页交易额排行']);
        //消息提醒
        Route::get('jnotice', 'Common/jnotice')->option(['real_name' => '消息提醒']);
        //验证授权
        Route::get('check_auth', 'Common/check_auth')->option(['real_name' => '验证授权']);
        //申请授权
        Route::post('auth_apply', 'Common/auth_apply')->option(['real_name' => '申请授权']);
        //申请版权
        Route::post('crmeb_copyright', 'Common/crmeb_copyright')->option(['real_name' => '申请版权']);
        //授权信息
        Route::get('auth', 'Common/auth')->option(['real_name' => '授权信息']);
        //授权验证码
        Route::get('crmeb_verify', 'Common/crmeb_verify')->option(['real_name' => '授权验证码']);
        //登录
        Route::post('crmeb_login', 'Common/crmeb_login')->option(['real_name' => '登录']);
        //授权订单
        Route::post('crmeb_order', 'Common/crmeb_order')->option(['real_name' => '授权订单']);
        //获取授权订单
        Route::get('crmeb_order/:orderId', 'Common/crmeb_order_info')->option(['real_name' => '获取授权订单']);
        //授权支付
        Route::post('crmeb_pay', 'Common/crmeb_pay')->option(['real_name' => '授权支付']);
        //获取授权产品
        Route::get('crmeb_product', 'Common/crmeb_product')->option(['real_name' => '获取授权产品']);
        //保存版权
        Route::post('copyright', 'Common/saveCopyright')->option(['real_name' => '保存版权']);
        //获取版权
        Route::get('copyright', 'Common/getCopyright')->option(['real_name' => '获取版权']);
        //获取左侧菜单
        Route::get('menus', 'v1.setting.SystemMenus/menus')->option(['real_name' => '左侧菜单']);
        //获取搜索菜单列表
        Route::get('menusList', 'Common/menusList')->option(['real_name' => '搜索菜单列表']);
        //获取logo
        Route::get('logo', 'Common/getLogo')->option(['real_name' => '获取logo']);
        //获取城市数据
        Route::get('city', 'Common/city')->option(['real_name' => '获取城市数据']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);

    /**
     * 企业微信相关
     */
    Route::group('work', function () {

        //获取企业微信发送朋友圈任务列表
        Route::get('moment', 'Moment/index')->option(['real_name' => '获取企业微信发送朋友圈任务列表']);
        //保存企业微信发送朋友圈任务
        Route::post('moment', 'Moment/save')->option(['real_name' => '保存企业微信发送朋友圈任务']);
        //企业微信发送朋友圈详情
        Route::get('moment/:id', 'Moment/read')->option(['real_name' => '企业微信发送朋友圈详情']);
        //删除企业微信发送朋友圈
        Route::delete('moment/:id', 'Moment/delete')->option(['real_name' => '删除企业微信发送朋友圈']);
        //获取企业微信发送过朋友圈成员列表
        Route::get('moment_list', 'Moment/sendResultList')->option(['real_name' => '获取企业微信发送过朋友圈成员列表']);
        //企业微信客户列表
        Route::get('client', 'Client/index')->option(['real_name' => '企业微信客户列表']);
        //修改企业微信客户
        Route::put('client/:id', 'Client/update')->option(['real_name' => '修改企业微信客户']);
        //同步企业微信客户
        Route::get('client/synch', 'Client/synch')->option(['real_name' => '同步企业微信客户']);
        //批量设置企业微信客户标签
        Route::post('client/batchLabel', 'Client/batchLabel')->option(['real_name' => '批量设置企业微信客户标签']);
        //查找成员下的跟踪客户
        Route::post('client/count', 'Client/count')->option(['real_name' => '查找成员下的跟踪客户']);
        //企业微信组织架构
        Route::get('tree', 'Member/getMemberTree')->option(['real_name' => '企业微信组织架构']);
        //企业微信客户标签
        Route::get('label', 'Member/getUserLabel')->option(['real_name' => '企业微信客户标签']);
        //获取企业微信员工列表
        Route::get('member', 'Member/index')->option(['real_name' => '获取企业微信员工列表']);
        //获取企业微信部门
        Route::get('department', 'Department/index')->option(['real_name' => '获取企业微信部门']);
        //获取企业微信客户群聊
        Route::get('group_chat', 'GroupChat/index')->option(['real_name' => '获取企业微信客户群聊']);
        //获取企业微信客户群聊成员
        Route::get('group_chat/member', 'GroupChat/chatMember')->option(['real_name' => '获取企业微信客户群聊成员']);
        //客户群统计
        Route::get('group_chat/statistics', 'GroupChat/chatStatistics')->option(['real_name' => '客户群统计']);
        //客户群统计列表数据
        Route::get('group_chat/statisticsList', 'GroupChat/chatStatisticsList')->option(['real_name' => '客户群统计列表数据']);
        //同步客户群
        Route::post('group_chat/synch', 'GroupChat/synchGroupChat')->option(['real_name' => '同步客户群']);
        //同步企业微信成员
        Route::post('synchMember', 'Member/synchMember')->option(['real_name' => '同步企业微信成员']);
        //获取群发成员列表
        Route::get('group_template/memberList/:id', 'GroupTemplate/memberList')->option(['real_name' => '获取群发成员列表']);
        //获取群发客户列表
        Route::get('group_template/clientList/:id', 'GroupTemplate/clientList')->option(['real_name' => '获取群发客户列表']);
        //获取群发群列表
        Route::get('group_template_chat/groupChatList/:id', 'GroupTemplate/groupChatList')->option(['real_name' => '获取群发群列表']);
        //获取群发群主列表
        Route::get('group_template_chat/groupChatOwnerList/:id', 'GroupTemplate/groupChatOwnerList')->option(['real_name' => '获取群发群主列表']);
        //获取群发群主下的群列表
        Route::get('group_template_chat/getOwnerChatList', 'GroupTemplate/getOwnerChatList')->option(['real_name' => '获取群发群主下的群列表']);
        //提醒发送
        Route::post('group_template/sendMessage', 'GroupTemplate/sendMessage')->option(['real_name' => '提醒发送']);
        //自动拉群配置
        Route::resource('group_chat_auth', 'GroupChatAuth')->except(['create', 'edit'])->option([
            'real_name' => [
                'index' => '获取欢自动拉群配置列表',
                'read' => '获取自动拉群配置',
                'save' => '保存自动拉群配置',
                'update' => '修改自动拉群配置',
                'delete' => '删除自动拉群配置'
            ]
        ]);
        //欢迎语
        Route::resource('welcome', 'Welcome')->except(['create', 'edit'])->option([
            'real_name' => [
                'index' => '获取欢迎语列表',
                'read' => '获取创建欢迎语',
                'save' => '保存欢迎语',
                'update' => '修改欢迎语',
                'delete' => '删除欢迎语'
            ]
        ]);

        //客户群发
        Route::resource('group_template', 'GroupTemplate')->except(['create', 'edit', 'update'])->option([
            'real_name' => [
                'index' => '获取客户群发列表',
                'read' => '获取客户群发详情',
                'save' => '保存客户群发',
                'delete' => '删除客户群发'
            ]
        ]);
        //客户群群发
        Route::resource('group_template_chat', 'GroupTemplate')->except(['create', 'edit', 'update'])->option([
            'real_name' => [
                'index' => '获取客户群群发列表',
                'read' => '获取客户群群发详情',
                'save' => '保存客户群群发',
                'delete' => '删除客户群群发'
            ]
        ]);
        //获取扫描渠道码添加的客户列表
        Route::get('channel/code/client', 'ChannelCode/getClientList')->option(['real_name' => '获取扫描渠道码添加的客户列表']);
        //批量移动分类
        Route::post('channel/code/bactch/cate', 'ChannelCode/bactchMoveCate')->option(['real_name' => '批量移动分类']);
        //渠道二维码
        Route::resource('channel/code', 'ChannelCode')->except(['create', 'edit'])->option([
            'real_name' => [
                'index' => '获取渠道二维码列表',
                'read' => '获取创建渠道二维码',
                'save' => '保存渠道二维码',
                'update' => '修改渠道二维码',
                'delete' => '删除渠道二维码'
            ]
        ]);
        //渠道二维码分类
        Route::resource('channel/cate', 'ChannelCate')->except(['read'])->option([
            'real_name' => [
                'index' => '获取渠道二维码分类列表',
                'create' => '获取创建渠道二维码分类表单',
                'save' => '保存渠道二维码分类',
                'edit' => '获取修改渠道二维码分类表单',
                'update' => '修改渠道二维码分类',
                'delete' => '删除渠道二维码分类'
            ]
        ]);
    })->prefix('admin.v1.work.')
        ->middleware([
            \app\http\middleware\AllowOriginMiddleware::class,
            \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
            \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
            \app\http\middleware\admin\AdminLogMiddleware::class
        ]);

    /**
     * 分销管理 相关路由
     */
    Route::group('agent', function () {
        //分销员列表
        Route::get('index', 'v1.agent.AgentManage/index')->option(['real_name' => '分销员列表']);
        //修改上级推广人
        Route::put('spread', 'v1.agent.AgentManage/editSpread')->option(['real_name' => '修改上级推广人']);
        //头部统计
        Route::get('statistics', 'v1.agent.AgentManage/get_badge')->option(['real_name' => '分销员列表头部统计']);
        //推广人列表
        Route::get('stair', 'v1.agent.AgentManage/get_stair_list')->option(['real_name' => '推广人列表']);
        //推广人头部统计
        Route::get('stair/statistics', 'v1.agent.AgentManage/get_stair_badge')->option(['real_name' => '推广人头部统计']);
        //计推广订单列表
        Route::get('stair/order', 'v1.agent.AgentManage/get_stair_order_list')->option(['real_name' => '推广订单列表']);
        //推广订单列表头部
        Route::get('stair/order/statistics', 'v1.agent.AgentManage/get_stair_order_badge')->option(['real_name' => '推广订单列表头部']);
        //清除上级推广人
        Route::put('stair/delete_spread/:uid', 'v1.agent.AgentManage/delete_spread')->option(['real_name' => '清除上级推广人']);
        //取消推广资格
        Route::put('stair/delete_system_spread/:uid', 'v1.agent.AgentManage/delete_system_spread')->option(['real_name' => '取消推广资格']);
        //查看公众号推广二维码
        Route::get('look_code', 'v1.agent.AgentManage/look_code')->option(['real_name' => '查看公众号推广二维码']);
        //查看小程序推广二维码
        Route::get('look_xcx_code', 'v1.agent.AgentManage/look_xcx_code')->option(['real_name' => '查看小程序推广二维码']);
        //查看H5推广二维码
        Route::get('look_h5_code', 'v1.agent.AgentManage/look_h5_code')->option(['real_name' => '查看H5推广二维码']);
        //积分配置编辑表单
        Route::get('config/edit_basics', 'v1.setting.SystemConfig/edit_basics')->option(['real_name' => '积分配置编辑表单']);
        //积分配置保存数据
        Route::post('config/save_basics', 'v1.setting.SystemConfig/save_basics')->option(['real_name' => '积分配置保存数据']);
        //分销员等级资源路由
        Route::resource('level', 'v1.agent.AgentLevel')->name('AgentLevelResource')->option(['real_name' => [
            'index' => '获取分销等级列表',
            'read' => '获取分销等级详情',
            'create' => '获取创建分销等级表单',
            'save' => '保存分销等级',
            'edit' => '获取修改分销等级表单',
            'update' => '修改分销等级',
            'delete' => '删除分销等级'
        ]]);
        //修改分销等级状态
        Route::put('level/set_status/:id/:status', 'v1.agent.AgentLevel/set_status')->name('levelSetStatus')->option(['real_name' => '修改分销等级状态']);
        //分销员等级任务资源路由
        Route::resource('level_task', 'v1.agent.AgentLevelTask')->name('AgentLevelTaskResource')->option(['real_name' => [
            'index' => '获取分销等级任务列表',
            'read' => '获取分销等级任务详情',
            'create' => '获取创建分销等级任务表单',
            'save' => '保存分销等级任务',
            'edit' => '获取修改分销等级任务表单',
            'update' => '修改分销等级任务',
            'delete' => '删除分销等级任务'
        ]]);
        //修改分销等级任务状态
        Route::put('level_task/set_status/:id/:status', 'v1.agent.AgentLevelTask/set_status')->name('levelTaskSetStatus')->option(['real_name' => '修改分销等级任务状态']);
        //获取赠送分销等级表单
        Route::get('get_level_form', 'v1.agent.AgentManage/getLevelForm')->name('getLevelForm')->option(['real_name' => '获取赠送分销等级表单']);
        //赠送分销等级
        Route::post('give_level', 'v1.agent.AgentManage/giveAgentLevel')->name('giveAgentLevel')->option(['real_name' => '赠送分销等级']);
        //获取分销说明
        Route::get('get_agent_agreement', 'v1.agent.AgentManage/getAgentAgreement')->name('getAgentAgreement')->option(['real_name' => '获取分销说明']);
        //保存分销说明
        Route::post('set_agent_agreement/:id', 'v1.agent.AgentManage/setAgentAgreement')->name('setAgentAgreement')->option(['real_name' => '保存分销说明']);

    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 应用模块 相关路由
     */
    Route::group('app', function () {
        //一键同步订阅消息
        Route::get('routine/syncSubscribe', 'v1.application.routine.RoutineTemplate/syncSubscribe')->name('syncSubscribe')->option(['real_name' => '一键同步订阅消息']);
        //一键同步微信模版消息
        Route::get('wechat/syncSubscribe', 'v1.application.wechat.WechatTemplate/syncSubscribe')->name('syncSubscribe')->option(['real_name' => '一键同步微信模版消息']);
        //修改订阅消息状态
        Route::put('routine/set_status/:id/:status', 'v1.application.routine.RoutineTemplate/set_status')->name('RoutineSetStatus')->option(['real_name' => '修改订阅消息状态']);
        //微信公众号菜单列表
        Route::get('wechat/menu', 'v1.application.wechat.menus/index')->option(['real_name' => '微信公众号菜单列表']);
        //保存微信公众号菜单
        Route::post('wechat/menu', 'v1.application.wechat.menus/save')->option(['real_name' => '保存微信公众号菜单']);
        //修改模板消息状态
        Route::put('wechat/template/set_status/:id/:status', 'v1.application.wechat.WechatTemplate/set_status')->name('WechatTemplateSetStatus')->option(['real_name' => '修改模板消息状态']);
        //关注回复
        Route::get('wechat/reply', 'v1.application.wechat.Reply/reply')->option(['real_name' => '关注回复']);
        //获取关注回复二维码
        Route::get('wechat/code_reply/:id', 'v1.application.wechat.Reply/code_reply')->option(['real_name' => '获取关注回复二维码']);
        //关键字回复列表
        Route::get('wechat/keyword', 'v1.application.wechat.Reply/index')->option(['real_name' => '关键字回复列表']);
        //关键字回复详情
        Route::get('wechat/keyword/:id', 'v1.application.wechat.Reply/read')->option(['real_name' => '关键字回复详情']);
        //保存关键字回复
        Route::post('wechat/keyword/:id', 'v1.application.wechat.Reply/save')->option(['real_name' => '保存关键字回复']);
        //删除关键字回复
        Route::delete('wechat/keyword/:id', 'v1.application.wechat.Reply/delete')->option(['real_name' => '删除关键字回复']);
        //修改关键字回复状态
        Route::put('wechat/keyword/set_status/:id/:status', 'v1.application.wechat.Reply/set_status')->option(['real_name' => '修改关键字回复状态']);
        //图文列表
        Route::get('wechat/news', 'v1.application.wechat.WechatNewsCategory/index')->option(['real_name' => '图文列表']);
        //图文详情
        Route::get('wechat/news/:id', 'v1.application.wechat.WechatNewsCategory/read')->option(['real_name' => '图文详情']);
        //保存图文
        Route::post('wechat/news', 'v1.application.wechat.WechatNewsCategory/save')->option(['real_name' => '保存图文']);
        //删除图文
        Route::delete('wechat/news/:id', 'v1.application.wechat.WechatNewsCategory/delete')->option(['real_name' => '删除图文']);
        //发送图文消息
        Route::post('wechat/push', 'v1.application.wechat.WechatNewsCategory/push')->option(['real_name' => '发送图文消息']);
        //客服列表
        Route::get('wechat/kefu', 'v1.application.wechat.StoreService/index')->option(['real_name' => '客服列表']);
        //客服登录
        Route::get('wechat/kefu/login/:id', 'v1.application.wechat.StoreService/keufLogin')->option(['real_name' => '客服登录']);
        //新增客服选择用户列表
        Route::get('wechat/kefu/create', 'v1.application.wechat.StoreService/create')->option(['real_name' => '新增客服选择用户列表']);
        //添加客服表单
        Route::get('wechat/kefu/add', 'v1.application.wechat.StoreService/add')->option(['real_name' => '添加客服表单']);
        //添加客服
        Route::post('wechat/kefu', 'v1.application.wechat.StoreService/save')->option(['real_name' => '添加客服']);
        //修改客服表单
        Route::get('wechat/kefu/:id/edit', 'v1.application.wechat.StoreService/edit')->option(['real_name' => '修改客服表单']);
        //修改客服
        Route::put('wechat/kefu/:id', 'v1.application.wechat.StoreService/update')->option(['real_name' => '修改客服']);
        //删除客服
        Route::delete('wechat/kefu/:id', 'v1.application.wechat.StoreService/delete')->option(['real_name' => '删除客服']);
        //修改客服状态
        Route::put('wechat/kefu/set_status/:id/:status', 'v1.application.wechat.StoreService/set_status')->option(['real_name' => '修改客服状态']);
        //聊天记录
        Route::get('wechat/kefu/record/:id', 'v1.application.wechat.StoreService/chat_user')->option(['real_name' => '聊天记录']);
        //查看对话
        Route::get('wechat/kefu/chat_list', 'v1.application.wechat.StoreService/chat_list')->option(['real_name' => '查看对话']);
        //下载小程序模版页面数据
        Route::get('routine/info', 'v1.application.routine.RoutineTemplate/getDownloadInfo')->option(['real_name' => '下载小程序页面数据']);
        //下载小程序模版
        Route::post('routine/download', 'v1.application.routine.RoutineTemplate/downloadTemp')->option(['real_name' => '下载小程序模版']);
        //小程序订阅消息资源路由
        Route::resource('routine', 'v1.application.routine.RoutineTemplate')->name('RoutineResource')->option(['real_name' => [
            'index' => '获取小程序订阅消息列表',
            'read' => '获取小程序订阅消息详情',
            'create' => '获取创建小程序订阅消息表单',
            'save' => '保存小程序订阅消息',
            'edit' => '获取修改小程序订阅消息表单',
            'update' => '修改小程序订阅消息',
            'delete' => '删除小程序订阅消息'
        ]]);
        //公众号模版消息资源路由
        Route::resource('wechat/template', 'v1.application.wechat.WechatTemplate')->name('WechatTemplateResource')->option(['real_name' => [
            'index' => '获取公众号模版消息列表',
            'read' => '获取公众号模版消息详情',
            'create' => '获取创建公众号模版消息表单',
            'save' => '保存公众号模版消息',
            'edit' => '获取修改公众号模版消息表单',
            'update' => '修改公众号模版消息',
            'delete' => '删除公众号模版消息'
        ]]);
        //客服话术资源路由
        Route::resource('wechat/speechcraft', 'v1.application.wechat.StoreServiceSpeechcraft')->option(['real_name' => [
            'index' => '获取客服话术列表',
            'read' => '获取客服话术详情',
            'create' => '获取创建客服话术表单',
            'save' => '保存客服话术',
            'edit' => '获取修改客服话术表单',
            'update' => '修改客服话术',
            'delete' => '删除客服话术'
        ]]);
        //客服话术分类资源路由
        Route::resource('wechat/speechcraftcate', 'v1.application.wechat.StoreServiceSpeechcraftCate')->option(['real_name' => [
            'index' => '获取客服话术分类列表',
            'read' => '获取客服话术分类详情',
            'create' => '获取创建客服话术分类表单',
            'save' => '保存客服话术分类',
            'edit' => '获取修改客服话术分类表单',
            'update' => '修改客服话术分类',
            'delete' => '删除客服话术分类'
        ]]);
        //用户反馈资源路由
        Route::resource('feedback', 'v1.application.wechat.StoreServiceFeedback')->only(['index', 'delete', 'update', 'edit'])->option(['real_name' => [
            'index' => '获取用户反馈列表',
            'read' => '获取用户反馈详情',
            'create' => '获取创建用户反馈表单',
            'save' => '保存用户反馈',
            'edit' => '获取修改用户反馈表单',
            'update' => '修改用户反馈',
            'delete' => '删除用户反馈'
        ]]);
        //获取微信会员卡信息
        Route::get('wechat/card', 'v1.application.wechat.WechatCard/info')->option(['real_name' => '获取微信会员卡信息']);
        //保存微信会员卡信息
        Route::post('wechat/card', 'v1.application.wechat.WechatCard/save')->option(['real_name' => '保存微信会员卡信息']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 文章管理 相关路由
     */
    Route::group('cms', function () {
        //关联商品
        Route::put('cms/relation/:id', 'v1.cms.Article/relation')->name('Relation')->option(['real_name' => '文章关联商品']);
        //取消关联
        Route::put('cms/unrelation/:id', 'v1.cms.Article/unrelation')->name('UnRelation')->option(['real_name' => '取消文章关联商品']);
        //修改状态
        Route::put('category/set_status/:id/:status', 'v1.cms.ArticleCategory/set_status')->name('CategoryStatus')->option(['real_name' => '修改文章分类状态']);
        //分类列表
        Route::get('category_list', 'v1.cms.ArticleCategory/categoryList')->name('categoryList')->option(['real_name' => '选择文章分类列表']);
        //文章分类资源路由
        Route::resource('category', 'v1.cms.ArticleCategory')->name('ArticleCategoryResource')->option(['real_name' => [
            'index' => '获取文章分类列表',
            'read' => '获取文章分类详情',
            'create' => '获取创建文章分类表单',
            'save' => '保存文章分类',
            'edit' => '获取修改文章分类表单',
            'update' => '修改文章分类',
            'delete' => '删除文章分类'
        ]]);
        //文章资源路由
        Route::resource('cms', 'v1.cms.Article')->name('ArticleResource')->option(['real_name' => [
            'index' => '获取文章列表',
            'read' => '获取文章详情',
            'create' => '获取创建文章表单',
            'save' => '保存文章',
            'edit' => '获取修改文章表单',
            'update' => '修改文章',
            'delete' => '删除文章'
        ]]);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 首页Diy 相关路由
     */
    Route::group('diy', function () {

        //DIY列表
        Route::get('get_list', 'v1.diy.Diy/getList')->option(['real_name' => 'Diy模板列表']);
        //Diy数据详情
        Route::get('get_info/:id', 'v1.diy.Diy/getInfo')->option(['real_name' => 'Diy模板数据详情']);
        //删除DIY模板
        Route::delete('del/:id', 'v1.diy.Diy/del')->option(['real_name' => '删除DIY模板']);
        //使用DIY模板
        Route::put('set_status/:id', 'v1.diy.Diy/setStatus')->option(['real_name' => '使用DIY模板']);
        //添加DIY模板
        Route::post('save/[:id]', 'v1.diy.Diy/saveData')->option(['real_name' => '添加DIY模板']);
        //获取前端页面路径
        Route::get('get_url', 'v1.diy.Diy/getUrl')->option(['real_name' => '获取前端页面路径']);
        //获取商品分类
        Route::get('get_category', 'v1.diy.Diy/getCategory')->option(['real_name' => '获取商品分类']);
        //获取商品
        Route::get('get_product', 'v1.diy.Diy/getProduct')->option(['real_name' => '获取商品列表']);
        //获取门店自提开启状态
        Route::get('get_store_status', 'v1.diy.Diy/getStoreStatus')->option(['real_name' => '获取门店自提开启状态']);
        //还原Diy默认数据
        Route::get('recovery/:id', 'v1.diy.Diy/Recovery')->option(['real_name' => '还原Diy默认数据']);
        //设置Diy默认数据
        Route::get('set_default_data/:id', 'v1.diy.Diy/setDefaultData')->option(['real_name' => '设置Diy默认数据']);
        //获取所有二级分类
        Route::get('get_by_category', 'v1.diy.Diy/getByCategory')->option(['real_name' => '获取所有二级分类']);
        //获取推荐不同类型商品
        Route::get('groom_list/:type', 'v1.diy.Diy/groom_list')->name('groomList')->option(['real_name' => '获取推荐不同类型商品']);
        //换色和分类保存
        Route::put('color_change/:status/:type', 'v1.diy.Diy/colorChange')->option(['real_name' => '换色和分类保存']);
        //换色和分类详情
        Route::get('get_color_change/:type', 'v1.diy.Diy/getColorChange')->option(['real_name' => '换色和分类详情']);
        //获取diy小程序二维码
        Route::get('get_routine_code/:id', 'v1.diy.Diy/getRoutineCode')->option(['real_name' => 'diy小程序预览码']);
        //个人中心菜单获取
        Route::get('get_member', 'v1.diy.Diy/getMember')->option(['real_name' => '个人中心详情']);
        //个人中心菜单保存
        Route::post('member_save', 'v1.diy.Diy/memberSaveData')->option(['real_name' => '个人中心保存']);
        //获取页面链接分类
        Route::get('get_page_category', 'v1.diy.PageLink/getCategory')->option(['real_name' => '获取页面链接分类']);
        //获取页面链接
        Route::get('get_page_link/:cate_id', 'v1.diy.PageLink/getLinks')->option(['real_name' => '获取页面链接']);
        //保存自定义链接
        Route::post('save_link/:cate_id', 'v1.diy.PageLink/saveLink')->option(['real_name' => '保存自定义链接']);
        //删除DIY模板
        Route::delete('del_link/:id', 'v1.diy.PageLink/del')->option(['real_name' => '删除链接']);
        //开屏广告
        Route::get('open_adv/info', 'v1.diy.Diy/getOpenAdv')->option(['real_name' => '获取开屏广告']);
        Route::post('open_adv/add', 'v1.diy.Diy/openAdvAdd')->option(['real_name' => '保存开屏广告']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 导出excel相关路由
     */
    Route::group('export', function () {
        //用户资金
        Route::get('userFinance', 'v1.export.ExportExcel/userFinance')->option(['real_name' => '用户资金导出']);
        //用户佣金
        Route::get('userCommission', 'v1.export.ExportExcel/userCommission')->option(['real_name' => '用户佣金导出']);
        //用户积分
        Route::get('userPoint', 'v1.export.ExportExcel/userPoint')->option(['real_name' => '用户积分导出']);
        //用户充值
        Route::get('userRecharge', 'v1.export.ExportExcel/userRecharge')->option(['real_name' => '用户充值导出']);
        //分销员推广列表
        Route::get('userAgent', 'v1.export.ExportExcel/userAgent')->option(['real_name' => '分销员推广列表导出']);
        //微信用户
        Route::get('wechatUser', 'v1.export.ExportExcel/wechatUser')->option(['real_name' => '微信用户导出']);
        //砍价商品
        Route::get('storeBargain', 'v1.export.ExportExcel/storeBargain')->option(['real_name' => '砍价商品导出']);
        //拼团商品
        Route::get('storeCombination', 'v1.export.ExportExcel/storeCombination')->option(['real_name' => '拼团商品导出']);
        //秒杀商品
        Route::get('storeSeckill', 'v1.export.ExportExcel/storeSeckill')->option(['real_name' => '秒杀商品导出']);
        //商品
        Route::get('storeProduct', 'v1.export.ExportExcel/storeProduct')->option(['real_name' => '商品导出']);
        //订单
        Route::get('storeOrder', 'v1.export.ExportExcel/storeOrder')->option(['real_name' => '订单导出']);
        //提货点
        Route::get('storeMerchant', 'v1.export.ExportExcel/storeMerchant')->option(['real_name' => '提货点导出']);
        //导出会员卡
        Route::get('memberCard/:id', 'v1.export.ExportExcel/memberCard')->option(['real_name' => '会员卡导出']);
        //导出批量发货记录
        Route::get('batchOrderDelivery/:id/:queueType/:cacheType', 'v1.export.ExportExcel/batchOrderDelivery')->option(['real_name' => '批量发货记录导出']);
        //物流公司对照表导出
        Route::get('expressList', 'v1.export.ExportExcel/expressList')->option(['real_name' => '物流公司对照表导出']);
        //积分兑换订单导出
        Route::get('storeIntegralOrder', 'v1.export.ExportExcel/storeIntegralOrder')->option(['real_name' => '积分兑换订单导出']);
        //门店账单导出
        Route::get('storeFinanceRecord', 'v1.export.ExportExcel/financeRecord')->option(['real_name' => '门店账单导出']);
        //门店流水导出
        Route::get('storeFlowExport', 'v1.export.ExportExcel/flowExport')->option(['real_name' => '门店流水导出']);
        //商品卡号、卡密模版导出
        Route::get('storeProductCardTemplate', 'v1.export.ExportExcel/storeProductCardTemplate')->option(['real_name' => '商品卡号、卡密模版导出']);
        //发票导出
        Route::get('invoiceExport', 'v1.export.ExportExcel/invoiceExport')->option(['real_name' => '发票导出']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 附件相关路由
     */
    Route::group('file', function () {
        //图片附件列表
        Route::get('file', 'v1.file.SystemAttachment/index')->option(['real_name' => '图片附件列表']);
        //删除图片
        Route::post('file/delete', 'v1.file.SystemAttachment/delete')->option(['real_name' => '删除图片']);
        //移动图片分类表单
        Route::get('file/move', 'v1.file.SystemAttachment/move')->option(['real_name' => '移动图片分类表单']);
        //移动图片分类
        Route::put('file/do_move', 'v1.file.SystemAttachment/moveImageCate')->option(['real_name' => '移动图片分类']);
        //修改图片名称
        Route::put('file/update/:id', 'v1.file.SystemAttachment/update')->option(['real_name' => '修改图片名称']);
        //上传图片
        Route::post('upload/[:upload_type]', 'v1.file.SystemAttachment/upload')->option(['real_name' => '上传图片']);
        //获取上传类型
        Route::get('upload_type', 'v1.file.SystemAttachment/uploadType')->option(['real_name' => '上传类型']);
        //分片上传本地视频
        Route::post('video_upload', 'v1.file.SystemAttachment/videoUpload')->option(['real_name' => '分片上传本地视频']);
        //附件分类管理资源路由
        Route::resource('category', 'v1.file.SystemAttachmentCategory')->option(['real_name' => [
            'index' => '获取附件分类列表',
            'read' => '获取附件分类详情',
            'create' => '获取创建附件分类表单',
            'save' => '保存附件分类',
            'edit' => '获取修改附件分类表单',
            'update' => '修改附件分类',
            'delete' => '删除附件分类'
        ]]);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 财务模块 相关路由
     */
    Route::group('finance', function () {
        //资金记录类型
        Route::get('finance/bill_type', 'v1.finance.Finance/bill_type')->option(['real_name' => '资金记录类型']);
        //资金记录列表
        Route::get('finance/list', 'v1.finance.Finance/list')->option(['real_name' => '资金记录列表']);
        //佣金记录
        Route::get('finance/commission_list', 'v1.finance.Finance/get_commission_list')->option(['real_name' => '佣金记录列表']);
        //佣金详情用户信息
        Route::get('finance/user_info/:id', 'v1.finance.Finance/user_info')->option(['real_name' => '佣金详情用户信息']);
        //个人佣金记录列表
        Route::get('finance/extract_list/:id', 'v1.finance.Finance/getUserBrokeragelist')->option(['real_name' => '个人佣金记录列表']);
        //提现申请列表
        Route::get('extract', 'v1.finance.UserExtract/index')->option(['real_name' => '提现申请列表']);
        //提现记录修改表单
        Route::get('extract/:id/edit', 'v1.finance.UserExtract/edit')->option(['real_name' => '提现记录修改表单']);
        //提现记录修改
        Route::put('extract/:id', 'v1.finance.UserExtract/update')->option(['real_name' => '提现记录修改']);
        //拒绝提现申请
        Route::put('extract/refuse/:id', 'v1.finance.UserExtract/refuse')->option(['real_name' => '拒绝提现申请']);
        //通过提现申请
        Route::put('extract/adopt/:id', 'v1.finance.UserExtract/adopt')->option(['real_name' => '通过提现申请']);
        //充值记录列表
        Route::get('recharge', 'v1.finance.UserRecharge/index')->option(['real_name' => '充值记录列表']);
        //删除充值记录
        Route::delete('recharge/:id', 'v1.finance.UserRecharge/delete')->option(['real_name' => '删除充值记录']);
        //获取用户充值数据
        Route::get('recharge/user_recharge', 'v1.finance.UserRecharge/user_recharge')->option(['real_name' => '获取用户充值数据']);
        //充值退款表单
        Route::get('recharge/:id/refund_edit', 'v1.finance.UserRecharge/refund_edit')->option(['real_name' => '充值退款表单']);
        //充值退款
        Route::put('recharge/:id', 'v1.finance.UserRecharge/refund_update')->option(['real_name' => '充值退款']);

        //用户资金流水
        Route::get('flow/get_list', 'v1.finance.CapitalFlow/getFlowList')->option(['real_name' => '资金流水']);
        Route::post('flow/set_mark/:id', 'v1.finance.CapitalFlow/setMark')->option(['real_name' => '设置备注']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 物流公司相关路由
     */
    Route::group('freight', function () {
        //修改物流公司状态
        Route::put('express/set_status/:id/:status', 'v1.freight.Express/set_status')->option(['real_name' => '修改物流公司状态']);
        //同步物流公司
        Route::get('express/sync_express', 'v1.freight.Express/syncExpress')->option(['real_name' => '同步物流公司']);
        //物流公司资源路由
        Route::resource('express', 'v1.freight.Express')->name('ExpressResource')->option(['real_name' => [
            'index' => '获取物流公司列表',
            'read' => '获取物流公司详情',
            'create' => '获取创建物流公司表单',
            'save' => '保存物流公司',
            'edit' => '获取修改物流公司表单',
            'update' => '修改物流公司',
            'delete' => '删除物流公司'
        ]]);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);

    /**
     * 直播相关路由
     */
    Route::group('live', function () {

        //主播列表
        Route::get('anchor/list', 'v1.marketing.live.LiveAnchor/list')->option(['real_name' => '主播列表']);
        //添加修改主播表单
        Route::get('anchor/add/:id', 'v1.marketing.live.LiveAnchor/add')->option(['real_name' => '添加修改主播表单']);
        //保存主播数据
        Route::post('anchor/save', 'v1.marketing.live.LiveAnchor/save')->option(['real_name' => '保存主播数据']);
        //删除主播
        Route::delete('anchor/del/:id', 'v1.marketing.live.LiveAnchor/delete')->option(['real_name' => '删除主播']);
        //设置是否显示
        Route::get('anchor/set_show/:id/:is_show', 'v1.marketing.live.LiveAnchor/setShow')->option(['real_name' => '设置主播是否显示']);
        //同步主播
        Route::get('anchor/syncAnchor', 'v1.marketing.live.LiveAnchor/syncAnchor')->option(['real_name' => '同步主播']);
        //直播商品列表
        Route::get('goods/list', 'v1.marketing.live.LiveGoods/list')->option(['real_name' => '直播商品列表']);
        //生成直播商品
        Route::post('goods/create', 'v1.marketing.live.LiveGoods/create')->option(['real_name' => '生成直播商品']);
        //添加修改直播商品
        Route::post('goods/add', 'v1.marketing.live.LiveGoods/add')->option(['real_name' => '添加修改直播商品']);
        //直播商品详情
        Route::get('goods/detail/:id', 'v1.marketing.live.LiveGoods/detail')->option(['real_name' => '直播商品详情']);
        //直播商品重新审核
        Route::get('goods/audit/:id', 'v1.marketing.live.LiveGoods/audit')->option(['real_name' => '直播商品重新审核']);
        //直播商品撤回审核
        Route::get('goods/resestAudit/:id', 'v1.marketing.live.LiveGoods/resetAudit')->option(['real_name' => '直播商品撤回审核']);
        //删除直播商品
        Route::delete('goods/del/:id', 'v1.marketing.live.LiveGoods/delete')->option(['real_name' => '删除直播商品']);
        //设置直播商品是否显示
        Route::get('goods/set_show/:id/:is_show', 'v1.marketing.live.liveGoods/setShow')->option(['real_name' => '设置直播商品是否显示']);
        //同步直播商品状态
        Route::get('goods/syncGoods', 'v1.marketing.live.liveGoods/syncGoods')->option(['real_name' => '同步直播商品状态']);
        //直播间列表
        Route::get('room/list', 'v1.marketing.live.LiveRoom/list')->option(['real_name' => '直播间列表']);
        //直播间添加
        Route::post('room/add', 'v1.marketing.live.LiveRoom/add')->option(['real_name' => '直播间添加']);
        //直播间详情
        Route::get('room/detail/:id', 'v1.marketing.live.LiveRoom/detail')->option(['real_name' => '直播间详情']);
        //直播间添加商品
        Route::post('room/add_goods', 'v1.marketing.live.LiveRoom/addGoods')->option(['real_name' => '直播间添加商品']);
        //删除直播间
        Route::delete('room/del/:id', 'v1.marketing.live.LiveRoom/delete')->option(['real_name' => '删除直播间']);
        //设置直播间是否显示
        Route::get('room/set_show/:id/:is_show', 'v1.marketing.live.LiveRoom/setShow')->option(['real_name' => '设置直播间是否显示']);
        //同步直播间状态
        Route::get('room/syncRoom', 'v1.marketing.live.LiveRoom/syncRoom')->option(['real_name' => '同步直播间状态']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 抽奖相关路由
     */
    Route::group('lottery', function () {
        //抽奖活动列表
        Route::get('list', 'v1.marketing.lottery.LuckLottery/index')->option(['real_name' => '抽奖活动列表']);
        //抽奖活动详情
        Route::get('detail/:id', 'v1.marketing.lottery.LuckLottery/detail')->option(['real_name' => '抽奖活动详情']);
        //抽奖活动详情
        Route::get('factor_info/:factor', 'v1.marketing.lottery.LuckLottery/factorInfo')->option(['real_name' => '抽奖活动详情']);
        //添加抽奖活动
        Route::post('add', 'v1.marketing.lottery.LuckLottery/add')->option(['real_name' => '添加抽奖活动']);
        //修改抽奖活动数据
        Route::put('edit/:id', 'v1.marketing.lottery.LuckLottery/edit')->option(['real_name' => '修改抽奖活动数据']);
        //删除抽奖活动
        Route::delete('del/:id', 'v1.marketing.lottery.LuckLottery/delete')->option(['real_name' => '删除抽奖活动']);
        //设置抽奖活动是否显示
        Route::post('set_status/:id/:status', 'v1.marketing.lottery.LuckLottery/setStatus')->option(['real_name' => '设置抽奖活动是否显示']);
        //抽奖记录列表
        Route::get('record/list', 'v1.marketing.lottery.LuckLotteryRecord/list')->option(['real_name' => '抽奖记录列表']);
        //抽奖记录列表
        Route::get('record/list/:id', 'v1.marketing.lottery.LuckLotteryRecord/index')->option(['real_name' => '抽奖记录列表']);
        //抽奖中奖发货、备注处理
        Route::post('record/deliver', 'v1.marketing.lottery.LuckLotteryRecord/deliver')->option(['real_name' => '抽奖中奖发货、备注处理']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 优惠卷，砍价，拼团，秒杀 路由
     */
    Route::group('marketing', function () {
        //已发布优惠券列表
        Route::get('coupon/released', 'v1.marketing.coupon.StoreCouponIssue/index')->option(['real_name' => '已发布优惠券列表']);
        //添加优惠券
        Route::post('coupon/save_coupon', 'v1.marketing.coupon.StoreCouponIssue/saveCoupon')->option(['real_name' => '添加优惠券']);
        //修改优惠券状态
        Route::get('coupon/status/:id/:status', 'v1.marketing.coupon.StoreCouponIssue/status')->option(['real_name' => '修改优惠券状态']);
        //一键复制优惠券
        Route::get('coupon/copy/:id', 'v1.marketing.coupon.StoreCouponIssue/copy')->option(['real_name' => '一键复制优惠券']);
        //发送优惠券列表
        Route::get('coupon/grant', 'v1.marketing.coupon.StoreCouponIssue/index')->option(['real_name' => '发送优惠券列表']);
        //优惠券相关 资源路由
        Route::get('coupon/list', 'v1.marketing.coupon.StoreCoupon/index')->option(['real_name' => '优惠券']);
        //优惠卷添加
        Route::get('coupon/create/:type', 'v1.marketing.coupon.StoreCoupon/create')->option(['real_name' => '优惠卷添加']);
        //优惠卷数据添加
        Route::post('coupon/save', 'v1.marketing.coupon.StoreCoupon/save')->option(['real_name' => '优惠卷数据添加']);
        //优惠卷修改
        Route::delete('coupon/del/:id', 'v1.marketing.coupon.StoreCoupon/delete')->option(['real_name' => '优惠卷修改']);
        //修改优惠券状态
        Route::put('coupon/status/:id', 'v1.marketing.coupon.StoreCoupon/status')->option(['real_name' => '修改优惠券状态']);
        //发布优惠券表单
        Route::get('coupon/issue/:id', 'v1.marketing.coupon.StoreCoupon/issue')->option(['real_name' => '发布优惠券表单']);
        //发布优惠券
        Route::post('coupon/issue/:id', 'v1.marketing.coupon.StoreCoupon/update_issue')->option(['real_name' => '发布优惠券']);
        //已发布优惠券删除
        Route::delete('coupon/released/:id', 'v1.marketing.coupon.StoreCouponIssue/delete')->option(['real_name' => '已发布优惠券删除']);
        //已发布优惠券修改状态表单
        Route::get('coupon/released/:id/status', 'v1.marketing.coupon.StoreCouponIssue/edit')->option(['real_name' => '已发布优惠券修改状态表单']);
        //已发布优惠券修改状态
        Route::put('coupon/released/status/:id', 'v1.marketing.coupon.StoreCouponIssue/status')->option(['real_name' => '已发布优惠券修改状态']);
        //已发布优惠券领取记录
        Route::get('coupon/released/issue_log/:id', 'v1.marketing.coupon.StoreCouponIssue/issue_log')->option(['real_name' => '已发布优惠券领取记录']);
        //会员领取记录
        Route::get('coupon/user', 'v1.marketing.coupon.StoreCouponUser/index')->option(['real_name' => '会员领取记录']);
        //发送优惠券
        Route::post('coupon/user/grant', 'v1.marketing.coupon.StoreCouponUser/grant')->option(['real_name' => '发送优惠券']);
        //砍价商品列表
        Route::get('bargain', 'v1.marketing.bargain.StoreBargain/index')->option(['real_name' => '砍价商品列表']);
        //砍价商品详情
        Route::get('bargain/:id', 'v1.marketing.bargain.StoreBargain/read')->option(['real_name' => '砍价商品详情']);
        //新增或编辑砍价商品
        Route::post('bargain/:id', 'v1.marketing.bargain.StoreBargain/save')->option(['real_name' => '新增或编辑砍价商品']);
        //删除砍价商品
        Route::delete('bargain/:id', 'v1.marketing.bargain.StoreBargain/delete')->option(['real_name' => '删除砍价商品']);
        //修改砍价商品状态
        Route::put('bargain/set_status/:id/:status', 'v1.marketing.bargain.StoreBargain/set_status')->option(['real_name' => '修改砍价商品状态']);
        //参与砍价列表
        Route::get('bargain_list', 'v1.marketing.bargain.StoreBargain/bargainList')->option(['real_name' => '参与砍价列表']);
        //砍价人列表
        Route::get('bargain_list_info/:id', 'v1.marketing.bargain.StoreBargain/bargainListInfo')->option(['real_name' => '砍价人列表']);
        //拼团商品列表
        Route::get('combination', 'v1.marketing.combination.StoreCombination/index')->option(['real_name' => '拼团商品列表']);
        //拼团商品统计
        Route::get('combination/statistics', 'v1.marketing.combination.StoreCombination/statistics')->option(['real_name' => '拼团商品统计']);
        //拼团商品详情
        Route::get('combination/:id', 'v1.marketing.combination.StoreCombination/read')->option(['real_name' => '拼团商品详情']);
        //新增或编辑拼团商品
        Route::post('combination/:id', 'v1.marketing.combination.StoreCombination/save')->option(['real_name' => '新增或编辑拼团商品']);
        //删除拼团商品
        Route::delete('combination/:id', 'v1.marketing.combination.StoreCombination/delete')->option(['real_name' => '删除拼团商品']);
        //修改拼团商品状态
        Route::put('combination/set_status/:id/:status', 'v1.marketing.combination.StoreCombination/set_status')->option(['real_name' => '修改拼团商品状态']);
        //参与拼团列表
        Route::get('combination/combine/list', 'v1.marketing.combination.StoreCombination/combine_list')->option(['real_name' => '参与拼团列表']);
        //拼团人列表
        Route::get('combination/order_pink/:id', 'v1.marketing.combination.StoreCombination/order_pink')->option(['real_name' => '拼团人列表']);
        //秒杀商品列表
        Route::get('seckill', 'v1.marketing.seckill.StoreSeckill/index')->option(['real_name' => '秒杀商品列表']);
        //秒杀时间段列表
        Route::get('seckill/time_list', 'v1.marketing.seckill.StoreSeckill/time_list')->option(['real_name' => '秒杀时间段列表']);
        //秒杀商品详情
        Route::get('seckill/:id', 'v1.marketing.seckill.StoreSeckill/read')->option(['real_name' => '秒杀商品详情']);
        //新增或编辑秒杀商品
        Route::post('seckill/:id', 'v1.marketing.seckill.StoreSeckill/save')->option(['real_name' => '新增或编辑秒杀商品']);
        //删除秒杀商品
        Route::delete('seckill/:id', 'v1.marketing.seckill.StoreSeckill/delete')->option(['real_name' => '删除秒杀商品']);
        //修改秒杀商品状态
        Route::put('seckill/set_status/:id/:status', 'v1.marketing.seckill.StoreSeckill/set_status')->option(['real_name' => '修改秒杀商品状态']);
        //积分日志列表
        Route::get('integral', 'v1.marketing.integral.UserPoint/index')->option(['real_name' => '积分日志列表']);
        //积分日志头部数据
        Route::get('integral/statistics', 'v1.marketing.integral.UserPoint/integral_statistics')->option(['real_name' => '积分日志头部数据']);
        //积分配置编辑表单
        Route::get('integral_config/edit_basics', 'v1.setting.SystemConfig/edit_basics')->option(['real_name' => '积分配置编辑表单']);
        //积分配置保存数据
        Route::post('integral_config/save_basics', 'v1.setting.SystemConfig/save_basics')->option(['real_name' => '积分配置保存数据']);
        //积分商品列表
        Route::get('integral_product', 'v1.marketing.integral.StoreIntegral/index')->option(['real_name' => '积分商品列表']);
        //积分商品批量保存
        Route::post('integral/batch', 'v1.marketing.integral.StoreIntegral/batch_add')->option(['real_name' => '积分商品批量保存']);
        //积分商品新增或编辑
        Route::post('integral/:id', 'v1.marketing.integral.StoreIntegral/save')->option(['real_name' => '积分商品新增或编辑']);
        //积分商品详情
        Route::get('integral/:id', 'v1.marketing.integral.StoreIntegral/read')->option(['real_name' => '积分商品详情']);
        //修改积分商品状态
        Route::put('integral/set_show/:id/:is_show', 'v1.marketing.integral.StoreIntegral/set_show')->option(['real_name' => '修改积分商品状态']);
        //积分商品删除
        Route::delete('integral/:id', 'v1.marketing.integral.StoreIntegral/delete')->option(['real_name' => '积分商品删除']);
        //积分商城订单列表
        Route::get('integral/order/list', 'v1.marketing.integral.StoreIntegralOrder/lst')->option(['real_name' => '积分商城订单列表']);
        //积分商城订单数据
        Route::get('integral/order/chart', 'v1.marketing.integral.StoreIntegralOrder/chart')->option(['real_name' => '积分商城订单数据']);
        //积分商城订单详情数据
        Route::get('integral/order/info/:id', 'v1.marketing.integral.StoreIntegralOrder/order_info')->option(['real_name' => '积分商城订单详情数据']);
        //修改积分商品订单备注信息
        Route::put('integral/order/remark/:id', 'v1.marketing.integral.StoreIntegralOrder/remark')->option(['real_name' => '修改积分商品订单备注信息']);
        //获取积分订单状态
        Route::get('integral/order/status/:id', 'v1.marketing.integral.StoreIntegralOrder/status')->option(['real_name' => '获取积分订单状态']);
        //删除积分订单
        Route::delete('integral/order/del/:id', 'v1.marketing.integral.StoreIntegralOrder/del')->option(['real_name' => '删除积分订单']);
        //积分订单发送货
        Route::put('integral/order/delivery/:id', 'v1.marketing.integral.StoreIntegralOrder/update_delivery')->option(['real_name' => '积分订单发送货']);
        //获取积分订单配送信息表单
        Route::get('integral/order/distribution/:id', 'v1.marketing.integral.StoreIntegralOrder/distribution')->option(['real_name' => '获取积分订单配送信息表单']);
        //修改积分订单配送信息
        Route::put('integral/order/distribution/:id', 'v1.marketing.integral.StoreIntegralOrder/update_distribution')->option(['real_name' => '修改积分订单配送信息']);
        //积分订单确认收货
        Route::put('integral/order/take/:id', 'v1.marketing.integral.StoreIntegralOrder/take_delivery')->option(['real_name' => '积分订单确认收货']);
        //积分订单获取物流公司
        Route::get('integral/order/express_list', 'v1.marketing.integral.StoreIntegralOrder/express')->option(['real_name' => '积分订单获取物流公司']);
        //积分订单快递公司电子面单模版
        Route::get('integral/order/express/temp', 'v1.marketing.integral.StoreIntegralOrder/express_temp')->option(['real_name' => '积分订单快递公司电子面单模版']);
        //积分订单获取物流信息
        Route::get('integral/order/express/:id', 'v1.marketing.integral.StoreIntegralOrder/get_express')->option(['real_name' => '积分订单获取物流信息']);
        //打印积分订单
        Route::get('integral/order/print/:id', 'v1.marketing.integral.StoreIntegralOrder/order_print')->option(['real_name' => '打印积分订单']);
        //积分订单列表获取配送员
        Route::get('integral/order/delivery/list', 'v1.order.DeliveryService/get_delivery_list')->option(['real_name' => '积分订单列表获取配送员']);
        //积分订单获取面单默认配置信息
        Route::get('integral/order/sheet_info', 'v1.marketing.integral.StoreIntegralOrder/getDeliveryInfo')->option(['real_name' => '积分订单获取面单默认配置信息']);
        //添加或修改优惠套餐商品
        Route::post('discounts/save', 'v1.marketing.discounts.StoreDiscounts/save')->option(['real_name' => '添加或修改优惠套餐商品']);
        //优惠套餐列表
        Route::get('discounts/list', 'v1.marketing.discounts.StoreDiscounts/getList')->option(['real_name' => '优惠套餐列表']);
        //优惠套餐详情
        Route::get('discounts/info/:id', 'v1.marketing.discounts.StoreDiscounts/getInfo')->option(['real_name' => '优惠套餐详情']);
        //优惠套餐上下架
        Route::get('discounts/set_status/:id/:status', 'v1.marketing.discounts.StoreDiscounts/setStatus')->option(['real_name' => '优惠套餐上下架']);
        //优惠套餐删除
        Route::delete('discounts/del/:id', 'v1.marketing.discounts.StoreDiscounts/del')->option(['real_name' => '优惠套餐删除']);

        //促销活动列表
        Route::get('promotions/list/:type', 'v1.marketing.promotions.StorePromotions/index')->option(['real_name' => '促销活动列表']);
        //促销活动详情
        Route::get('promotions/info/:id', 'v1.marketing.promotions.StorePromotions/getInfo')->option(['real_name' => '促销活动详情']);
        //添加或修改折扣活动
        Route::post('promotions/save_discount/:type/:id', 'v1.marketing.promotions.StorePromotions/saveDiscount')->option(['real_name' => '添加或修改折扣活动']);
        //添加或修改满减活动
        Route::post('promotions/save/:type/:id', 'v1.marketing.promotions.StorePromotions/save')->option(['real_name' => '添加或修改满减活动']);
        //促销活动上下架
        Route::get('promotions/set_status/:id/:status', 'v1.marketing.promotions.StorePromotions/setStatus')->option(['real_name' => '促销活动上下架']);
        //促销活动删除
        Route::delete('promotions/del/:id', 'v1.marketing.promotions.StorePromotions/delete')->option(['real_name' => '促销活动删除']);

    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 门店相关路由
     */
    Route::group('store', function () {

        //门店首页头部统计数据
        Route::get('home/header', 'v1.store.Common/homeStatics')->option(['real_name' => '门店首页头部统计数据']);
        //门店首页营业趋势图表
        Route::get('home/operate', 'v1.store.Common/operateChart')->option(['real_name' => '门店首页营业趋势图表']);
        //门店首页交易图表
        Route::get('home/orderChart', 'v1.store.Common/orderChart')->option(['real_name' => '门店首页交易图表']);
        //门店首页店员统计
        Route::get('home/store', 'v1.store.Common/storeChart')->option(['real_name' => '门店首页门店统计']);
        //门店列表
        Route::get('store', 'v1.store.SystemStore/index')->option(['real_name' => '门店列表']);
        //门店上下架
        Route::put('store/set_show/:id/:is_show', 'v1.store.SystemStore/set_show')->option(['real_name' => '门店上下架']);
        //门店删除
        Route::delete('store/del/:id', 'v1.store.SystemStore/delete')->option(['real_name' => '门店删除']);
        //门店位置选择
        Route::get('store/address', 'v1.store.SystemStore/select_address')->option(['real_name' => '门店位置选择']);
        //获取ERP门店列表
        Route::get('erp/shop', 'v1.store.SystemStore/getErpShop')->option(['real_name' => '获取ERP门店列表']);
        //门店详情
        Route::get('store/get_info/:id', 'v1.store.SystemStore/get_info')->option(['real_name' => '门店详情']);
        //保存修改门店信息
        Route::post('store/:id', 'v1.store.SystemStore/save')->option(['real_name' => '保存修改门店信息']);
        //门店快捷登录
        Route::get('store/login/:id', 'v1.store.SystemStore/storeLogin')->option(['real_name' => '门店快捷登录']);
        //获取门店重置账号密码表单
        Route::get('store/reset_admin/:id', 'v1.store.SystemStore/resetAdminForm')->option(['real_name' => '获取门店重置账号密码表单']);
        //门店重置账号密码
        Route::post('store/reset_admin/:id', 'v1.store.SystemStore/resetAdmin')->option(['real_name' => '门店重置账号密码']);
        //获取头部数据
        Route::get('order/header', 'v1.store.Order/header')->name('StoreOrderHeader')->option(['real_name' => '获取门店订单头部统计']);
        //订单列表
        Route::get('order/list', 'v1.store.Order/index')->name('StoreOrderList')->option(['real_name' => '订单列表']);
        //订单头部数据
        Route::get('order/chart', 'v1.store.Order/chart')->name('StoreOrderChart')->option(['real_name' => '订单头部数据']);
        //订单详情
        Route::get('order/info/:id', 'v1.store.Order/order_info')->name('StoreOrderorInfo')->option(['real_name' => '订单详情']);
        //修改备注信息
        Route::put('order/remark/:id', 'v1.store.Order/remark')->name('StoreOrderorRemark')->option(['real_name' => '修改备注信息']);
        //获取订单状态
        Route::get('order/status/:id', 'v1.store.Order/status')->name('StoreOrderorStatus')->option(['real_name' => '获取订单状态']);
        //订单分配
        Route::post('share/order', 'v1.store.Order/shareOrder')->option(['real_name' => '订单分配']);
        //充值订单列表
        Route::get('recharge', 'v1.store.Recharge/index')->name('RechargeOrderList')->option(['real_name' => '充值订单列表']);
        //保存充值订单备注
        Route::put('recharge/remark/:id', 'v1.store.Recharge/remarks')->option(['real_name' => '保存充值订单备注']);
        //获取充值订单备注
        Route::get('recharge/remark/:id', 'v1.store.Recharge/getRemark')->option(['real_name' => '获取充值订单备注']);
        //付费会员订单列表
        Route::get('vip_order', 'v1.store.PayVipOrder/index')->name('PayVipOrderList')->option(['real_name' => '付费会员订单列表']);
        //获取会员状态
        Route::get('vip/status/:id', 'v1.store.PayVipOrder/status')->name('getStatusList')->option(['real_name' => '获取会员状态']);
        //获取会员备注
        Route::get('vip/remark/:id', 'v1.store.PayVipOrder/getRemark')->name('getRemark')->option(['real_name' => '获取会员备注']);
        //保存会员备注
        Route::put('vip/remark/:id', 'v1.store.PayVipOrder/remark')->name('remarkSave')->option(['real_name' => '保存会员备注']);
        //门店转账列表
        Route::get('extract/list', 'v1.store.StoreExtract/index')->option(['real_name' => '门店转账列表']);
        //门店转账记录备注
        Route::post('extract/mark/:id', 'v1.store.StoreExtract/mark')->option(['real_name' => '门店转账记录备注']);
        //提现审核
        Route::post('extract/verify/:id', 'v1.store.StoreExtract/verify')->option(['real_name' => '门店转账记录备注']);
        //转账表单
        Route::get('extract/transfer/:id', 'v1.store.StoreExtract/transfer')->option(['real_name' => '转账表单']);
        //转账提交
        Route::post('extract/save_transfer/:id', 'v1.store.StoreExtract/save_transfer')->option(['real_name' => '转账提交']);
        //门店流水列表
        Route::get('finance_flow/list', 'v1.store.StoreFinanceFlow/index')->option(['real_name' => '门店流水列表']);
        //门店流水备注
        Route::put('finance_flow/mark/:id', 'v1.store.StoreFinanceFlow/mark')->option(['real_name' => '门店流水备注']);
        //门店账单记录
        Route::get('finance_flow/fund_record', 'v1.store.StoreFinanceFlow/fundRecord')->option(['real_name' => '门店账单记录']);
        //门店账单详情
        Route::get('finance_flow/fund_record_info', 'v1.store.StoreFinanceFlow/fundRecordInfo')->option(['real_name' => '门店账单详情']);
        //门店财务设置头部
        Route::get('finance/header_basics', 'v1.setting.SystemConfig/header_basics')->option(['real_name' => '门店财务设置头部']);
        //门店财务编辑表单
        Route::get('finance/edit_basics', 'v1.setting.SystemConfig/edit_basics')->option(['real_name' => '门店财务编辑表单']);
        //门店财务保存数据
        Route::post('finance/save_basics', 'v1.setting.SystemConfig/save_basics')->option(['real_name' => '门店财务保存数据']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 运费模板 相关路由
     */
    Route::group('merchant', function () {
        //门店列表
        Route::get('store', 'v1.merchant.SystemStore/index')->option(['real_name' => '门店列表']);
        //门店列表头部数据
        Route::get('store/get_header', 'v1.merchant.SystemStore/get_header')->option(['real_name' => '门店列表头部数据']);
        //门店上下架
        Route::put('store/set_show/:id/:is_show', 'v1.merchant.SystemStore/set_show')->option(['real_name' => '门店上下架']);
        //门店删除
        Route::delete('store/del/:id', 'v1.merchant.SystemStore/delete')->option(['real_name' => '门店删除']);
        //门店位置选择
        Route::get('store/address', 'v1.merchant.SystemStore/select_address')->option(['real_name' => '门店位置选择']);
        //门店详情
        Route::get('store/get_info/:id', 'v1.merchant.SystemStore/get_info')->option(['real_name' => '门店详情']);
        //保存修改门店信息
        Route::post('store/:id', 'v1.merchant.SystemStore/save')->option(['real_name' => '保存修改门店信息']);
        //获取门店店员列表
        Route::get('store_staff', 'v1.merchant.SystemStoreStaff/index')->option(['real_name' => '获取门店店员列表']);
        //添加门店店员表单
        Route::get('store_staff/create', 'v1.merchant.SystemStoreStaff/create')->option(['real_name' => '添加门店店员表单']);
        //门店搜索列表
        Route::get('store_list', 'v1.merchant.SystemStoreStaff/store_list')->option(['real_name' => '门店搜索列表']);
        //修改店员状态
        Route::put('store_staff/set_show/:id/:is_show', 'v1.merchant.SystemStoreStaff/set_show')->option(['real_name' => '修改店员状态']);
        //修改店员表单
        Route::get('store_staff/:id/edit', 'v1.merchant.SystemStoreStaff/edit')->option(['real_name' => '修改店员表单']);
        //保存店员
        Route::post('store_staff/save/:id', 'v1.merchant.SystemStoreStaff/save')->option(['real_name' => '保存店员']);
        //删除店员
        Route::delete('store_staff/del/:id', 'v1.merchant.SystemStoreStaff/delete')->option(['real_name' => '删除店员']);
        //获取核销订单列表
        Route::get('verify_order', 'v1.merchant.SystemVerifyOrder/list')->option(['real_name' => '获取核销订单列表']);
        //获取核销订单头部
        Route::get('verify_badge', 'v1.merchant.SystemVerifyOrder/getVerifyBadge')->option(['real_name' => '获取核销订单头部']);
        //核销订单推荐人信息
        Route::get('verify/spread_info/:uid', 'v1.merchant.SystemVerifyOrder/order_spread_user')->option(['real_name' => '核销订单推荐人信息']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 消息通知管理、模版消息（列表，通知，添加，编辑）、短信 相关路由
     */
    Route::group('notify', function () {
        //保存短信配置
        Route::post('sms/config', 'v1.notification.sms.SmsConfig/save_basics')->option(['real_name' => '保存短信配置']);
        //短信发送记录
        Route::get('sms/record', 'v1.notification.sms.SmsConfig/record')->option(['real_name' => '短信发送记录']);
        //短信账号数据
        Route::get('sms/data', 'v1.notification.sms.SmsConfig/data')->option(['real_name' => '短信账号数据']);
        //查看短信账号是否登录
        Route::get('sms/is_login', 'v1.notification.sms.SmsConfig/is_login')->option(['real_name' => '查看短信账号是否登录']);
        //短信账号退出登录
        Route::get('sms/logout', 'v1.notification.sms.SmsConfig/logout')->option(['real_name' => '短信账号退出登录']);
        //发送短信验证码
        Route::post('sms/captcha', 'v1.notification.sms.SmsAdmin/captcha')->option(['real_name' => '发送短信验证码']);
        //修改或注册短信平台账号
        Route::post('sms/register', 'v1.notification.sms.SmsAdmin/save')->option(['real_name' => '修改或注册短信平台账号']);
        //短信模板列表
        Route::get('sms/temp', 'v1.notification.sms.SmsTemplateApply/index')->option(['real_name' => '短信模板列表']);
        //短信模板申请表单
        Route::get('sms/temp/create', 'v1.notification.sms.SmsTemplateApply/create')->option(['real_name' => '短信模板申请表单']);
        //短信模板申请
        Route::post('sms/temp', 'v1.notification.sms.SmsTemplateApply/save')->option(['real_name' => '短信模板申请']);
        //公共短信模板列表
        Route::get('sms/public_temp', 'v1.notification.sms.SmsPublicTemp/index')->option(['real_name' => '公共短信模板列表']);
        //短信剩余条数
        Route::get('sms/number', 'v1.notification.sms.SmsPay/number')->option(['real_name' => '短信剩余条数']);
        //获取短信购买套餐
        Route::get('sms/price', 'v1.notification.sms.SmsPay/price')->option(['real_name' => '获取短信购买套餐']);
        //获取短信购买支付码
        Route::post('sms/pay_code', 'v1.notification.sms.SmsPay/pay')->option(['real_name' => '获取短信购买支付码']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 订单路由
     */
    Route::group('order', function () {
        //打印订单
        Route::get('print/:id', 'v1.order.StoreOrder/order_print')->name('StoreOrderPrint')->option(['real_name' => '打印订单']);
        //订单列表
        Route::get('list', 'v1.order.StoreOrder/lst')->name('StoreOrderList')->option(['real_name' => '订单列表']);
        //订单头部数据
        Route::get('chart', 'v1.order.StoreOrder/chart')->name('StoreOrderChart')->option(['real_name' => '订单头部数据']);
        //订单核销
        Route::post('write', 'v1.order.StoreOrder/write_order')->name('writeOrder')->option(['real_name' => '订单核销']);
        //订单号核销
        Route::put('write_update/:order_id', 'v1.order.StoreOrder/write_update')->name('writeOrderUpdate')->option(['real_name' => '订单号核销']);
        //获取订单编辑表单
        Route::get('edit/:id', 'v1.order.StoreOrder/edit')->name('StoreOrderEdit')->option(['real_name' => '获取订单编辑表单']);
        //修改订单
        Route::put('update/:id', 'v1.order.StoreOrder/update')->name('StoreOrderUpdate')->option(['real_name' => '修改订单']);
        //确认收货
        Route::put('take/:id', 'v1.order.StoreOrder/take_delivery')->name('StoreOrderTakeDelivery')->option(['real_name' => '确认收货']);
        //订单发送货
        Route::put('delivery/:id', 'v1.order.StoreOrder/update_delivery')->name('StoreOrderUpdateDelivery')->option(['real_name' => '订单发送货']);
        //获取订单可拆分商品列表
        Route::get('split_cart_info/:id', 'v1.order.StoreOrder/split_cart_info')->name('StoreOrderSplitCartInfo')->option(['real_name' => '获取订单可拆分商品列表']);
        //拆单发送货
        Route::put('split_delivery/:id', 'v1.order.StoreOrder/split_delivery')->name('StoreOrderSplitDelivery')->option(['real_name' => '拆单发送货']);
        //获取订单拆分子订单列表
        Route::get('split_order/:id', 'v1.order.StoreOrder/split_order')->name('StoreOrderSplitOrder')->option(['real_name' => '获取订单拆分子订单列表']);
        //订单退款表单
        Route::get('refund/:id', 'v1.order.StoreOrder/refund')->name('StoreOrderRefund')->option(['real_name' => '订单退款表单']);
        //订单退款
        Route::put('refund/:id', 'v1.order.StoreOrder/update_refund')->name('StoreOrderUpdateRefund')->option(['real_name' => '订单退款']);
        //快递公司电子面单模版
        Route::get('express/temp', 'v1.order.StoreOrder/express_temp')->option(['real_name' => '快递公司电子面单模版']);
        //获取物流信息
        Route::get('express/:id', 'v1.order.StoreOrder/get_express')->name('StoreOrderUpdateExpress')->option(['real_name' => '获取物流信息']);
        //获取物流公司
        Route::get('express_list', 'v1.order.StoreOrder/express')->name('StoreOrdeRexpressList')->option(['real_name' => '获取物流公司']);
        //订单详情
        Route::get('info/:id', 'v1.order.StoreOrder/order_info')->name('StoreOrderorInfo')->option(['real_name' => '订单详情']);
        //获取配送信息表单
        Route::get('distribution/:id', 'v1.order.StoreOrder/distribution')->name('StoreOrderorDistribution')->option(['real_name' => '获取配送信息表单']);
        //修改配送信息
        Route::put('distribution/:id', 'v1.order.StoreOrder/update_distribution')->name('StoreOrderorUpdateDistribution')->option(['real_name' => '修改配送信息']);
        //获取不退款表单
        Route::get('no_refund/:id', 'v1.order.StoreOrder/no_refund')->name('StoreOrderorNoRefund')->option(['real_name' => '获取不退款表单']);
        //修改不退款理由
        Route::put('no_refund/:id', 'v1.order.StoreOrder/update_un_refund')->name('StoreOrderorUpdateNoRefund')->option(['real_name' => '修改不退款理由']);
        //线下支付
        Route::post('pay_offline/:id', 'v1.order.StoreOrder/pay_offline')->name('StoreOrderorPayOffline')->option(['real_name' => '线下支付']);
        //获取退积分表单
        Route::get('refund_integral/:id', 'v1.order.StoreOrder/refund_integral')->name('StoreOrderorRefundIntegral')->option(['real_name' => '获取退积分表单']);
        //修改退积分
        Route::put('refund_integral/:id', 'v1.order.StoreOrder/update_refund_integral')->name('StoreOrderorUpdateRefundIntegral')->option(['real_name' => '修改退积分']);
        //修改备注信息
        Route::put('remark/:id', 'v1.order.StoreOrder/remark')->name('StoreOrderorRemark')->option(['real_name' => '修改备注信息']);
        //获取订单状态
        Route::get('status/:id', 'v1.order.StoreOrder/status')->name('StoreOrderorStatus')->option(['real_name' => '获取订单状态']);
        //删除订单单个
        Route::delete('del/:id', 'v1.order.StoreOrder/del')->name('StoreOrderorDel')->option(['real_name' => '删除订单单个']);
        //批量删除订单
        Route::post('dels', 'v1.order.StoreOrder/del_orders')->name('StoreOrderorDels')->option(['real_name' => '批量删除订单']);
        //面单默认配置信息
        Route::get('sheet_info', 'v1.order.StoreOrder/getDeliveryInfo')->option(['real_name' => '面单默认配置信息']);
        //获取线下付款二维码
        Route::get('offline_scan', 'v1.order.OtherOrder/offline_scan')->name('OfflineScan')->option(['real_name' => '获取线下付款二维码']);
        //线下收银列表
        Route::get('scan_list', 'v1.order.OtherOrder/scan_list')->name('ScanList')->option(['real_name' => '线下收银列表']);
        //发票列表头部统计
        Route::get('invoice/chart', 'v1.order.StoreOrderInvoice/chart')->name('StoreOrderorInvoiceChart')->option(['real_name' => '发票列表头部统计']);
        //申请发票列表
        Route::get('invoice/list', 'v1.order.StoreOrderInvoice/list')->name('StoreOrderorInvoiceList')->option(['real_name' => '申请发票列表']);
        //设置发票状态
        Route::post('invoice/set/:id', 'v1.order.StoreOrderInvoice/set_invoice')->name('StoreOrderorInvoiceSet')->option(['real_name' => '设置发票状态']);
        //开票订单详情
        Route::get('invoice_order_info/:id', 'v1.order.StoreOrderInvoice/orderInfo')->name('StoreOrderorInvoiceOrderInfo')->option(['real_name' => '开票订单详情']);
        //配送员列表
        Route::get('delivery/index', 'v1.order.DeliveryService/index')->option(['real_name' => '配送员列表']);
        //新增配送员选择用户列表
        Route::get('delivery/create', 'v1.order.DeliveryService/create')->option(['real_name' => '新增配送员选择用户列表']);
        //新增配送表单
        Route::get('delivery/add', 'v1.order.DeliveryService/add')->option(['real_name' => '新增配送表单']);
        //保存新建的配送员
        Route::post('delivery/save', 'v1.order.DeliveryService/save')->option(['real_name' => '保存新建的配送员']);
        //编辑配送员表单
        Route::get('delivery/:id/edit', 'v1.order.DeliveryService/edit')->option(['real_name' => '编辑配送员表单']);
        //修改配送员
        Route::put('delivery/update/:id', 'v1.order.DeliveryService/update')->option(['real_name' => '修改配送员']);
        //删除配送员
        Route::delete('delivery/del/:id', 'v1.order.DeliveryService/delete')->option(['real_name' => '删除配送员']);
        //修改配送员状态
        Route::get('delivery/set_status/:id/:status', 'v1.order.DeliveryService/set_status')->option(['real_name' => '修改配送员状态']);
        //订单列表获取配送员
        Route::get('delivery/list', 'v1.order.DeliveryService/get_delivery_list')->option(['real_name' => '订单列表获取配送员']);
        //电子面单模板列表
        Route::get('expr/temp', 'v1.order.StoreOrder/expr_temp')->option(['real_name' => '电子面单模板列表']);
        //更多操作打印电子面单
        Route::get('order_dump/:order_id', 'v1.order.StoreOrder/order_dump')->option(['real_name' => '更多操作打印电子面单']);
        //批量发货
        Route::get('hand/batch_delivery', 'v1.order.StoreOrder/hand_batch_delivery')->option(['real_name' => '批量发货']);
        //自动批量发货
        Route::post('other/batch_delivery', 'v1.order.StoreOrder/other_batch_delivery')->option(['real_name' => '自动批量发货']);
        //订单批量删除
        Route::post('batch/del_orders', 'v1.order.StoreOrder/del_orders')->option(['real_name' => '订单批量删除']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 商品路由
     */
    Route::group('product', function () {

        //商品分类列表
        Route::get('category', 'v1.product.StoreCategory/index')->option(['real_name' => '商品分类列表']);
        //用户标签（分类）树形列表
        Route::get('user_label', 'v1.user.UserLabel/tree_list')->option(['real_name' => '用户标签（分类）树形列表']);
        //商品标签（分类）树形列表
        Route::get('product_label', 'v1.product.label.StoreProductLabel/tree_list')->option(['real_name' => '用户标签（分类）树形列表']);
        Route::get('all_label', 'v1.product.label.StoreProductLabel/allLabel')->option(['real_name' => '所有的用户标签']);
        Route::get('all_ensure', 'v1.product.ensure.StoreProductEnsure/allEnsure')->option(['real_name' => '所有的保障服务']);
        Route::get('all_specs', 'v1.product.specs.StoreProductSpecs/allSpecs')->option(['real_name' => '所有的参数模版']);
        //商品分类树形列表
        Route::get('category/tree/:type', 'v1.product.StoreCategory/tree_list')->option(['real_name' => '商品分类树形列表']);
        //商品分类cascader行列表
        Route::get('category/cascader_list/[:type]', 'v1.product.StoreCategory/cascader_list')->option(['real_name' => '商品分类cascader行列表']);
        //商品分类新增表单
        Route::get('category/create', 'v1.product.StoreCategory/create')->option(['real_name' => '商品分类新增表单']);
        //商品分类新增
        Route::post('category', 'v1.product.StoreCategory/save')->option(['real_name' => '商品分类新增']);
        //商品分类编辑表单
        Route::get('category/:id', 'v1.product.StoreCategory/edit')->option(['real_name' => '商品分类编辑表单']);
        //商品分类编辑
        Route::put('category/:id', 'v1.product.StoreCategory/update')->option(['real_name' => '商品分类编辑']);
        //删除商品分类
        Route::delete('category/:id', 'v1.product.StoreCategory/delete')->option(['real_name' => '删除商品分类']);
        //商品分类修改状态
        Route::put('category/set_show/:id/:is_show', 'v1.product.StoreCategory/set_show')->option(['real_name' => '商品分类修改状态']);
        //商品分类快捷编辑
        Route::put('category/set_category/:id', 'v1.product.StoreCategory/set_category')->option(['real_name' => '商品分类快捷编辑']);
        //商品列表
        Route::get('product', 'v1.product.StoreProduct/index')->option(['real_name' => '商品列表']);
        //获取退出未保存的数据
        Route::get('cache', 'v1.product.StoreProduct/getCacheData')->option(['real_name' => '获取退出未保存的数据']);
        //导入ERP商品到平台
        Route::post('import_erp_product', 'v1.product.StoreProduct/import_erp_product')->option(['real_name' => '导入ERP商品到平台']);
        //保存还未提交数据
        Route::post('cache', 'v1.product.StoreProduct/saveCacheData')->option(['real_name' => '保存还未提交数据']);
        //删除退出未保存的数据
        Route::delete('cache', 'v1.product.StoreProduct/deleteCacheData')->option(['real_name' => '删除退出未保存的数据']);
        //获取所有商品列表
        Route::get('product/list', 'v1.product.StoreProduct/search_list')->option(['real_name' => '获取所有商品列表']);
        //获取商品规格
        Route::get('product/attrs/:id/:type', 'v1.product.StoreProduct/get_attrs')->option(['real_name' => '获取商品规格']);
        //商品列表头部数据
        Route::get('product/type_header', 'v1.product.StoreProduct/type_header')->option(['real_name' => '商品列表头部数据']);
        //商品放入回收站
        Route::delete('product/:id', 'v1.product.StoreProduct/delete')->option(['real_name' => '商品放入回收站']);
        //新建或修改商品
        Route::post('product/:id', 'v1.product.StoreProduct/save')->option(['real_name' => '新建或修改商品']);
        //修改商品状态
        Route::put('product/set_show/:id/:is_show', 'v1.product.StoreProduct/set_show')->option(['real_name' => '修改商品状态']);
        //商品快速编辑
        Route::put('product/set_product/:id', 'v1.product.StoreProduct/set_product')->option(['real_name' => '商品快速编辑']);
        //设置批量商品上架
        Route::put('product/product_show', 'v1.product.StoreProduct/product_show')->option(['real_name' => '设置批量商品上架']);
        //设置批量商品下架
        Route::put('product/product_unshow', 'v1.product.StoreProduct/product_unshow')->option(['real_name' => '设置批量商品下架']);
        //设置商品配送方式
        Route::put('product/setDeliveryType', 'v1.product.StoreProduct/setProductDeliveryType')->option(['real_name' => '设置商品配送方式']);
        //商品规则列表
        Route::get('product/rule', 'v1.product.StoreProductRule/index')->option(['real_name' => '商品规则列表']);
        //新建或编辑商品规则
        Route::post('product/rule/:id', 'v1.product.StoreProductRule/save')->option(['real_name' => '新建或编辑商品规则']);
        //商品规则详情
        Route::get('product/rule/:id', 'v1.product.StoreProductRule/read')->option(['real_name' => '商品规则详情']);
        //删除商品规则
        Route::delete('product/rule/delete', 'v1.product.StoreProductRule/delete')->option(['real_name' => '删除商品规则']);
        //生成商品规格列表
        Route::post('generate_attr/:id/:type', 'v1.product.StoreProduct/is_format_attr')->option(['real_name' => '生成商品规格列表']);
        //商品评论列表
        Route::get('reply', 'v1.product.StoreProductReply/index')->option(['real_name' => '商品评论列表']);
        //商品回复评论
        Route::put('reply/set_reply/:id', 'v1.product.StoreProductReply/set_reply')->option(['real_name' => '商品回复评论']);
        //删除商品评论
        Route::delete('reply/:id', 'v1.product.StoreProductReply/delete')->option(['real_name' => '删除商品评论']);
        //获取复制商品配置
        Route::get('copy_config', 'v1.product.CopyTaobao/getConfig')->option(['real_name' => '获取复制商品配置']);
        //获取商品数据
        Route::post('crawl', 'v1.product.CopyTaobao/get_request_contents')->option(['real_name' => '获取采集商品数据']);
        //采集其他平台商品
        Route::post('copy', 'v1.product.CopyTaobao/copyProduct')->option(['real_name' => '复制其他平台商品']);
        //保存采集商品数据
        Route::post('crawl/save', 'v1.product.CopyTaobao/save_product')->option(['real_name' => '保存采集商品数据']);
        //虚拟评论表单
        Route::get('reply/fictitious_reply/:product_id', 'v1.product.StoreProductReply/fictitious_reply')->option(['real_name' => '虚拟评论表单']);
        //保存虚拟评论
        Route::post('reply/save_fictitious_reply', 'v1.product.StoreProductReply/save_fictitious_reply')->option(['real_name' => '保存虚拟评论']);
        //获取评论回复列表
        Route::get('reply/comment/:id', 'v1.product.StoreProductReply/getComment')->option(['real_name' => '获取评论回复列表']);
        //获取管理员评论回复
        Route::get('reply/get_reply/:id', 'v1.product.StoreProductReply/getReply')->option(['real_name' => '获取管理员评论回复']);
        //保存管理员回复
        Route::post('reply/save_comment/:replyId/:id', 'v1.product.StoreProductReply/saveComment')->option(['real_name' => '保存管理员回复']);
        //删除评论回复
        Route::delete('reply/delete_comment/:id', 'v1.product.StoreProductReply/deleteComment')->option(['real_name' => '删除评论回复']);
        //获取商品规则属性模板
        Route::get('product/get_rule', 'v1.product.StoreProduct/get_rule')->option(['real_name' => '获取商品规则属性模板']);
        //获取运费模板
        Route::get('product/get_template', 'v1.product.StoreProduct/get_template')->option(['real_name' => '获取运费模板']);
        //上传视频密钥接口
        Route::get('product/get_temp_keys', 'v1.product.StoreProduct/getTempKeys')->option(['real_name' => '上传视频密钥接口']);
        //检测是商品否有活动开启
        Route::get('product/check_activity/:id', 'v1.product.StoreProduct/check_activity')->option(['real_name' => '检测是商品否有活动开启']);
        //导入虚拟商品卡密
        Route::get('product/import_card', 'v1.product.StoreProduct/import_card')->option(['real_name' => '导入虚拟商品卡密']);
        //商品详情
        Route::get('product/:id', 'v1.product.StoreProduct/get_product_info')->option(['real_name' => '商品详情']);
        //获取商品规格
        Route::get('product/attrs/:id', 'v1.product.StoreProduct/getAttrs')->option(['real_name' => '获取商品规格']);
        //快速批量修改库存
        Route::put('product/saveStocks/:id', 'v1.product.StoreProduct/saveProductAttrsStock')->option(['real_name' => '快速批量修改库存']);
        //商品分类列表
        Route::get('brand', 'v1.product.StoreBrand/index')->option(['real_name' => '品牌分类列表']);
        //商品品牌cascader行列表
        Route::get('brand/cascader_list/[:type]', 'v1.product.StoreBrand/cascader_list')->option(['real_name' => '商品品牌cascader行列表']);
        //商品品牌新增
        Route::post('brand', 'v1.product.StoreBrand/save')->option(['real_name' => '品牌品牌新增']);
        //商品品牌编辑
        Route::put('brand/:id', 'v1.product.StoreBrand/update')->option(['real_name' => '商品品牌编辑']);
        //删除商品品牌
        Route::delete('brand/:id', 'v1.product.StoreBrand/delete')->option(['real_name' => '删除商品品牌']);
        //商品品牌修改状态
        Route::put('brand/set_show/:id/:is_show', 'v1.product.StoreBrand/set_show')->option(['real_name' => '商品品牌修改状态']);
        //获取所有商品单位
        Route::get('get_all_unit', 'v1.product.StoreProductUnit/getAllUnit')->option(['real_name' => '获取所有商品单位']);
        //商品单位
        Route::resource('unit', 'v1.product.StoreProductUnit')->option(['real_name' => [
            'index' => '获取商品单位列表',
            'read' => '获取商品单位详情',
            'create' => '获取创建商品单位表单',
            'save' => '保存商品单位',
            'edit' => '获取修改商品单位表单',
            'update' => '修改商品单位',
            'delete' => '删除商品单位'
        ]]);
        //商品标签分类
        Route::resource('label_cate', 'v1.product.label.StoreProductLabelCate')->option(['real_name' => [
            'index' => '获取商品标签分类列表',
            'read' => '获取商品标签分类详情',
            'create' => '获取创建商品标签分类表单',
            'save' => '保存商品标签分类',
            'edit' => '获取修改商品标签分类表单',
            'update' => '修改商品标签分类',
            'delete' => '删除商品标签分类'
        ]]);
        //商品标签
        Route::post('label/:id', 'v1.product.label.StoreProductLabel/save')->option(['real_name' => '保存商品标签']);
        Route::delete('label/:id', 'v1.product.label.StoreProductLabel/delete')->option(['real_name' => '删除商品标签']);
        Route::get('label/form', 'v1.product.label.StoreProductLabel/getLabelForm')->option(['real_name' => '获取商品标签表单']);
        //商品保障服务
        Route::resource('ensure', 'v1.product.ensure.StoreProductEnsure')->option(['real_name' => [
            'index' => '获取商品保障服务列表',
            'read' => '获取商品保障服务详情',
            'create' => '获取创建商品保障服务表单',
            'save' => '保存商品保障服务',
            'edit' => '获取修改商品保障服务表单',
            'update' => '修改商品保障服务',
            'delete' => '删除商品保障服务'
        ]]);
        Route::put('ensure/set_show/:id/:is_show', 'v1.product.ensure.StoreProductEnsure/set_show')->option(['real_name' => '商品保障服务是否显示']);
        //商品参数
        Route::get('specs', 'v1.product.specs.StoreProductSpecs/index')->option(['real_name' => '商品参数模版列表']);
        Route::get('specs/:id', 'v1.product.specs.StoreProductSpecs/getInfo')->option(['real_name' => '获取商品参数模版详情']);
        Route::post('specs/:id', 'v1.product.specs.StoreProductSpecs/save')->option(['real_name' => '保存商品参数模版']);
        Route::delete('specs/:id', 'v1.product.specs.StoreProductSpecs/delete')->option(['real_name' => '删除商品参数模版']);

    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 用户模块 相关路由
     */
    Route::group('queue', function () {
        //队列任务列表
        Route::get('index', 'v1.queue.Queue/index')->option(['real_name' => '队列任务列表']);
        //队列批量发货记录
        Route::get('delivery/log/:id/:type', 'v1.queue.Queue/delivery_log')->option(['real_name' => '队列批量发货记录']);
        //再次执行批量队列任务
        Route::get('again/do_queue/:id/:type', 'v1.queue.Queue/again_do_queue')->option(['real_name' => '再次执行批量队列任务']);
        //清除异常任务队列
        Route::get('del/wrong_queue/:id/:type', 'v1.queue.Queue/del_wrong_queue')->option(['real_name' => '清除异常任务队列']);
        //停止队列任务
        Route::get('stop/wrong_queue/:id', 'v1.queue.Queue/stop_wrong_queue')->option(['real_name' => '停止队列任务']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 服务平台路由
     */
    Route::group('serve', function () {
        //一号通平台登录
        Route::post('login', 'v1.serve.Login/login')->option(['real_name' => '一号通平台登录']);
        //一号通获取验证码
        Route::post('captcha', 'v1.serve.Login/captcha')->option(['real_name' => '一号通获取验证码']);
        //一号通验证验证码
        Route::post('checkCode', 'v1.serve.Login/checkCode')->option(['real_name' => '一号通验证验证码']);
        //一号通注册
        Route::post('register', 'v1.serve.Login/register')->option(['real_name' => '一号通注册']);
        //开通电子面单
        Route::post('opn_express', 'v1.serve.Serve/openExpress')->option(['real_name' => '一号通开通电子面单']);
        //一号通账户信息
        Route::get('info', 'v1.serve.Serve/getUserInfo')->option(['real_name' => '一号通账户信息']);
        //一号通支付套餐列表
        Route::get('meal_list', 'v1.serve.Serve/mealList')->option(['real_name' => '一号通支付套餐列表']);
        //一号通支付二维码
        Route::post('pay_meal', 'v1.serve.Serve/payMeal')->option(['real_name' => '一号通支付二维码']);
        //一号通开通短信服务
        Route::get('sms/open', 'v1.serve.Sms/openServe')->option(['real_name' => '一号通开通短信服务']);
        //一号通开通其他服务
        Route::get('open', 'v1.serve.Serve/openServe')->option(['real_name' => '一号通开通其他服务']);
        //一号通修改签名
        Route::put('sms/sign', 'v1.serve.Sms/editSign')->option(['real_name' => '一号通修改签名']);
        //一号通获取短信模板
        Route::get('sms/temps', 'v1.serve.Sms/temps')->option(['real_name' => '一号通获取短信模板']);
        //一号通申请模板
        Route::post('sms/apply', 'v1.serve.Sms/apply')->option(['real_name' => '一号通申请模板']);
        //一号通获取申请记录
        Route::get('sms/apply_record', 'v1.serve.Sms/applyRecord')->option(['real_name' => '一号通获取申请记录']);
        //一号通消费记录
        Route::get('record', 'v1.serve.Serve/getRecord')->option(['real_name' => '一号通消费记录']);
        //一号通是否开启电子面单打印
        Route::get('dump_open', 'v1.serve.Export/dumpIsOpen')->name('dumpIsOpen')->option(['real_name' => '一号通是否开启电子面单打印']);
        //一号通获取全部物流公司
        Route::get('export_all', 'v1.serve.Export/getExportAll')->option(['real_name' => '一号通获取全部物流公司']);
        //一号通获取物流公司模板
        Route::get('export_temp', 'v1.serve.Export/getExportTemp')->option(['real_name' => '一号通获取物流公司模板']);
        //一号通修改密码
        Route::post('modify', 'v1.serve.Serve/modify')->option(['real_name' => '一号通修改密码']);
        //一号通修改手机号码
        Route::post('update_phone', 'v1.serve.Serve/updatePhone')->option(['real_name' => '一号通修改手机号码']);
        //一号通短信配置编辑表单
        Route::get('sms_config/edit_basics', 'v1.setting.SystemConfig/edit_basics')->option(['real_name' => '一号通短信配置编辑表单']);
        //一号通短信配置保存数据
        Route::post('sms_config/save_basics', 'v1.setting.SystemConfig/save_basics')->option(['real_name' => '一号通短信配置保存数据']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 系统设置维护 系统权限管理、系统菜单管理 系统配置 相关路由
     */
    Route::group('setting', function () {

        //管理员退出登陆
        Route::get('admin/logout', 'v1.setting.SystemAdmin/logout')->name('SystemAdminLogout')->option(['real_name' => '退出登陆']);
        //修改管理员状态
        Route::put('set_status/:id/:status', 'v1.setting.SystemAdmin/set_status')->name('SystemAdminSetStatus')->option(['real_name' => '修改管理员状态']);
        //获取当前管理员信息
        Route::get('info', 'v1.setting.SystemAdmin/info')->name('SystemAdminInfo')->option(['real_name' => '获取当前管理员信息']);
        //修改当前管理员信息
        Route::put('update_admin', 'v1.setting.SystemAdmin/update_admin')->name('SystemAdminUpdateAdmin')->option(['real_name' => '修改当前管理员信息']);
        //修改权限规格显示状态
        Route::put('menus/show/:id', 'v1.setting.SystemMenus/show')->name('SystemMenusShow')->option(['real_name' => '修改权限规格显示状态']);
        //管理员身份列表
        Route::get('role', 'v1.setting.SystemRole/index')->option(['real_name' => '管理员身份列表']);
        //管理员身份权限列表
        Route::get('role/create', 'v1.setting.SystemRole/create')->option(['real_name' => '管理员身份权限列表']);
        //编辑管理员详情
        Route::get('role/:id/edit', 'v1.setting.SystemRole/edit')->option(['real_name' => '编辑管理员详情']);
        //新建或编辑管理员
        Route::post('role/:id', 'v1.setting.SystemRole/save')->option(['real_name' => '新建或编辑管理员']);
        //修改管理员身份状态
        Route::put('role/set_status/:id/:status', 'v1.setting.SystemRole/set_status')->option(['real_name' => '修改管理员身份状态']);
        //删除管理员身份
        Route::delete('role/:id', 'v1.setting.SystemRole/delete')->option(['real_name' => '删除管理员身份']);
        //修改配置分类状态
        Route::put('config_class/set_status/:id/:status', 'v1.setting.SystemConfigTab/set_status')->option(['real_name' => '修改配置分类状态']);
        //修改配置状态
        Route::put('config/set_status/:id/:status', 'v1.setting.SystemConfig/set_status')->option(['real_name' => '修改配置状态']);
        //基本配置编辑头部数据
        Route::get('config/header_basics', 'v1.setting.SystemConfig/header_basics')->option(['real_name' => '基本配置编辑头部数据']);
        //基本配置编辑表单
        Route::get('config/edit_basics', 'v1.setting.SystemConfig/edit_basics')->option(['real_name' => '基本配置编辑表单']);
        //新配置编辑表单
        Route::get('config/edit_new_build/:type', 'v1.setting.SystemConfig/getNewFormBuild')->option(['real_name' => '新配置编辑表单']);
        //获取置缩略图配置信息
        Route::get('config/image', 'v1.setting.SystemConfig/getImageConfig')->option(['real_name' => '获取置缩略图配置信息']);
        //基本配置保存数据
        Route::post('config/save_basics', 'v1.setting.SystemConfig/save_basics')->option(['real_name' => '基本配置保存数据']);
        //获取单个配置值
        Route::get('config/get_system/:name', 'v1.setting.SystemConfig/get_system')->option(['real_name' => '基本配置编辑表单']);
        //基本配置上传文件
        Route::post('config/upload', 'v1.setting.SystemConfig/file_upload')->option(['real_name' => '基本配置上传文件']);
        //组合数据全部
        Route::get('group_all', 'v1.setting.SystemGroup/getGroup')->option(['real_name' => '组合数据全部']);
        //组合数据头部
        Route::get('group_data/header', 'v1.setting.SystemGroupData/header')->option(['real_name' => '组合数据头部']);
        //修改组合数据状态
        Route::put('group_data/set_status/:id/:status', 'v1.setting.SystemGroupData/set_status')->option(['real_name' => '修改组合数据状态']);
        //获取城市数据列表
        Route::get('city/list/:parent_id', 'v1.setting.CityArea/index')->option(['real_name' => '获取城市数据列表']);
        //添加城市数据表单
        Route::get('city/add/:parent_id', 'v1.setting.CityArea/add')->option(['real_name' => '添加城市数据表单']);
        //修改城市数据表单
        Route::get('city/:id/edit', 'v1.setting.CityArea/edit')->option(['real_name' => '修改城市数据表单']);
        //新增/修改城市数据
        Route::post('city/save', 'v1.setting.CityArea/save')->option(['real_name' => '新增/修改城市数据']);
        //删除城市数据
        Route::delete('city/del/:city_id', 'v1.setting.CityArea/delete')->option(['real_name' => '删除城市数据']);
        //清除城市数据缓存
        Route::get('city/clean_cache', 'v1.setting.CityArea/clean_cache')->option(['real_name' => '清除城市数据缓存']);
        //运费模板列表
        Route::get('shipping_templates/list', 'v1.setting.ShippingTemplates/temp_list')->option(['real_name' => '运费模板列表']);
        //修改运费模板数据
        Route::get('shipping_templates/:id/edit', 'v1.setting.ShippingTemplates/edit')->option(['real_name' => '修改运费模板数据']);
        //新增或修改运费模版
        Route::post('shipping_templates/save/:id', 'v1.setting.ShippingTemplates/save')->option(['real_name' => '新增或修改运费模版']);
        //删除运费模板
        Route::delete('shipping_templates/del/:id', 'v1.setting.ShippingTemplates/delete')->option(['real_name' => '删除运费模板']);
        //城市数据接口
        Route::get('shipping_templates/city_list', 'v1.setting.ShippingTemplates/city_list')->option(['real_name' => '城市数据接口']);
        //获取客服广告
        Route::get('get_kf_adv', 'v1.setting.SystemGroupData/getKfAdv')->option(['real_name' => '获取客服广告']);
        //设置客服广告
        Route::post('set_kf_adv', 'v1.setting.SystemGroupData/setKfAdv')->option(['real_name' => '设置客服广告']);
        //获取隐私协议
        Route::get('get_user_agreement/:type', 'v1.setting.SystemGroupData/getUserAgreement')->option(['real_name' => '获取隐私协议']);
        //设置隐私协议
        Route::post('set_user_agreement/:type', 'v1.setting.SystemGroupData/setUserAgreement')->option(['real_name' => '设置隐私协议']);
        //签到数据头部
        Route::get('sign_data/header', 'v1.setting.SystemGroupData/header')->option(['real_name' => '签到数据头部']);
        //修改签到数据状态
        Route::put('sign_data/set_status/:id/:status', 'v1.setting.SystemGroupData/set_status')->option(['real_name' => '修改签到数据状态']);
        //订单数据字段
        Route::get('order_data/header', 'v1.setting.SystemGroupData/header')->option(['real_name' => '订单数据字段']);
        //订单数据状态
        Route::put('order_data/set_status/:id/:status', 'v1.setting.SystemGroupData/set_status')->option(['real_name' => '订单数据状态']);
        //个人中心菜单数据字段
        Route::get('usermenu_data/header', 'v1.setting.SystemGroupData/header')->option(['real_name' => '个人中心菜单数据字段']);
        //个人中心菜单数据状态
        Route::put('usermenu_data/set_status/:id/:status', 'v1.setting.SystemGroupData/set_status')->option(['real_name' => '个人中心菜单数据状态']);
        //分享海报数据字段
        Route::get('poster_data/header', 'v1.setting.SystemGroupData/header')->option(['real_name' => '分享海报数据字段']);
        //分享海报数据状态
        Route::put('poster_data/set_status/:id/:status', 'v1.setting.SystemGroupData/set_status')->option(['real_name' => '分享海报数据状态']);
        //秒杀数据字段
        Route::get('seckill_data/header', 'v1.setting.SystemGroupData/header')->option(['real_name' => '秒杀数据字段']);
        //秒杀数据状态
        Route::put('seckill_data/set_status/:id/:status', 'v1.setting.SystemGroupData/set_status')->option(['real_name' => '秒杀数据状态']);
        //云存储列表
        Route::get('config/storage', 'v1.setting.SystemStorage/index')->name('SystemStorageIndex')->option(['real_name' => '云存储列表']);
        //获取云存储创建表单
        Route::get('config/storage/create/:type', 'v1.setting.SystemStorage/create')->name('SystemStorageCreate')->option(['real_name' => '获取云存储创建表单']);
        //获取云存储配置表单
        Route::get('config/storage/form/:type', 'v1.setting.SystemStorage/getConfigForm')->name('getConfigForm')->option(['real_name' => '获取云存储配置表单']);
        //获取云存储配置
        Route::get('config/storage/config', 'v1.setting.SystemStorage/getConfig')->name('SystemStorageConfig')->option(['real_name' => '获取云存储配置']);
        //保存云存储配置
        Route::post('config/storage/config', 'v1.setting.SystemStorage/saveConfig')->name('SystemStorageSaveConfig')->option(['real_name' => '保存云存储配置']);
        //同步云存储列表
        Route::put('config/storage/synch/:type', 'v1.setting.SystemStorage/synch')->name('SystemStorageSynch')->option(['real_name' => '同步云存储列表']);
        //获取修改云存储域名表单
        Route::get('config/storage/domain/:id', 'v1.setting.SystemStorage/getUpdateDomainForm')->name('getUpdateDomainForm')->option(['real_name' => '获取修改云存储域名表单']);
        //修改云存储域名
        Route::post('config/storage/domain/:id', 'v1.setting.SystemStorage/updateDomain')->name('updateDomain')->option(['real_name' => '修改云存储域名']);
        //保存云存储数据
        Route::post('config/storage/:type', 'v1.setting.SystemStorage/save')->name('SystemStorageSave')->option(['real_name' => '保存云存储数据']);
        //删除云存储
        Route::delete('config/storage/:id', 'v1.setting.SystemStorage/delete')->name('SystemStorageDelete')->option(['real_name' => '删除云存储']);
        //修改云存储状态
        Route::put('config/storage/status/:id', 'v1.setting.SystemStorage/status')->name('SystemStorageStatus')->option(['real_name' => '修改云存储状态']);
        //订单详情动态图配置资源
        Route::resource('order_data', 'v1.setting.SystemGroupData')->option(['real_name' => [
            'index' => '获取订单详情动态图列表',
            'read' => '获取订单详情动态图详情',
            'create' => '获取创建订单详情动态图表单',
            'save' => '保存订单详情动态图',
            'edit' => '获取修改订单详情动态图表单',
            'update' => '修改订单详情动态图',
            'delete' => '删除订单详情动态图'
        ]]);
        //签到天数配置资源
        Route::resource('sign_data', 'v1.setting.SystemGroupData')->option(['real_name' => [
            'index' => '获取签到天数列表',
            'read' => '获取签到天数详情',
            'create' => '获取创建签到天数表单',
            'save' => '保存签到天数',
            'edit' => '获取修改签到天数表单',
            'update' => '修改签到天数',
            'delete' => '删除签到天数'
        ]]);
        //个人中心菜单配置资源
        Route::resource('usermenu_data', 'v1.setting.SystemGroupData')->option(['real_name' => [
            'index' => '获取个人中心菜单列表',
            'read' => '获取个人中心菜单详情',
            'create' => '获取创建个人中心菜单表单',
            'save' => '保存个人中心菜单',
            'edit' => '获取修改个人中心菜单表单',
            'update' => '修改个人中心菜单',
            'delete' => '删除个人中心菜单'
        ]]);
        //分享海报配置资源
        Route::resource('poster_data', 'v1.setting.SystemGroupData')->option(['real_name' => [
            'index' => '获取分享海报列表',
            'read' => '获取分享海报详情',
            'create' => '获取创建分享海报表单',
            'save' => '保存分享海报',
            'edit' => '获取修改分享海报表单',
            'update' => '修改分享海报',
            'delete' => '删除分享海报'
        ]]);
        //秒杀配置资源
        Route::resource('seckill_data', 'v1.setting.SystemGroupData')->option(['real_name' => [
            'index' => '获取秒杀配置列表',
            'read' => '获取秒杀配置详情',
            'create' => '获取创建秒杀配置表单',
            'save' => '保存秒杀配置',
            'edit' => '获取修改秒杀配置表单',
            'update' => '修改秒杀配置',
            'delete' => '删除秒杀配置'
        ]]);
        //组合数据资源路由
        Route::resource('group', 'v1.setting.SystemGroup')->option(['real_name' => [
            'index' => '获取组合数据列表',
            'read' => '获取组合数据详情',
            'create' => '获取创建组合数据表单',
            'save' => '保存组合数据',
            'edit' => '获取修改组合数据表单',
            'update' => '修改组合数据',
            'delete' => '删除组合数据'
        ]]);
        //组合数据子数据资源路由
        Route::resource('group_data', 'v1.setting.SystemGroupData')->option(['real_name' => [
            'index' => '获取组合数据子数据列表',
            'read' => '获取组合数据子数据详情',
            'create' => '获取创建组合数据子数据表单',
            'save' => '保存组合数据子数据',
            'edit' => '获取修改组合数据子数据表单',
            'update' => '修改组合数据子数据',
            'delete' => '删除组合数据子数据'
        ]]);
        //系统配置分类资源路由
        Route::resource('config_class', 'v1.setting.SystemConfigTab')->option(['real_name' => [
            'index' => '获取系统配置分类列表',
            'read' => '获取系统配置分类详情',
            'create' => '获取创建系统配置分类表单',
            'save' => '保存系统配置分类',
            'edit' => '获取修改系统配置分类表单',
            'update' => '修改系统配置分类',
            'delete' => '删除系统配置分类'
        ]]);
        //系统配置资源路由
        Route::resource('config', 'v1.setting.SystemConfig')->option(['real_name' => [
            'index' => '获取系统配置列表',
            'read' => '获取系统配置详情',
            'create' => '获取创建系统配置表单',
            'save' => '保存系统配置',
            'edit' => '获取修改系统配置表单',
            'update' => '修改系统配置',
            'delete' => '删除系统配置'
        ]]);
        //权限菜单资源路由
        Route::resource('menus', 'v1.setting.SystemMenus')->option(['real_name' => [
            'index' => '获取权限菜单列表',
            'read' => '获取权限菜单详情',
            'create' => '获取权限菜单表单',
            'save' => '保存权限菜单',
            'edit' => '获取修改权限菜单表单',
            'update' => '修改权限菜单',
            'delete' => '删除权限菜单'
        ]]);
        //未添加的权限规则列表
        Route::get('ruleList', 'v1.setting.SystemMenus/ruleList')->option(['real_name' => '未添加的权限规则列表']);
        //管理员资源路由
        Route::resource('admin', 'v1.setting.SystemAdmin')->except(['read'])->option(['real_name' => [
            'index' => '获取管理员列表',
            'read' => '获取管理员详情',
            'create' => '获取创建管理员表单',
            'save' => '保存管理员',
            'edit' => '获取修改管理员表单',
            'update' => '修改管理员',
            'delete' => '删除管理员'
        ]]);
        //系统通知
        //系统通知列表
        Route::get('notification/index', 'v1.setting.SystemNotification/index')->option(['real_name' => '系统通知列表']);
        //获取单条数据
        Route::get('notification/info', 'v1.setting.SystemNotification/info')->option(['real_name' => '获取单条通知数据']);
        //保存通知设置
        Route::post('notification/save', 'v1.setting.SystemNotification/save')->option(['real_name' => '保存通知设置']);
        //修改消息状态
        Route::put('notification/set_status/:type/:status/:id', 'v1.setting.SystemNotification/set_status')->option(['real_name' => '修改消息状态']);
        Route::resource('admin', 'v1.setting.SystemAdmin')->except(['read'])->option(['real_name' => '管理员']);
        //数据配置保存
        Route::post('group_data/save_all', 'v1.setting.SystemGroupData/saveAll')->option(['real_name' => '提交数据配置']);
        //对外接口账号信息
        Route::get('system_out/index', 'v1.setting.SystemOut/index')->option(['real_name' => '对外接口账号信息']);
        //对外接口账号信息详情
        Route::get('system_out/info/:id', 'v1.setting.SystemOut/info')->option(['real_name' => '对外接口账号信息详情']);
        //对外接口账号添加
        Route::post('system_out/save', 'v1.setting.SystemOut/save')->option(['real_name' => '对外接口账号添加']);
        //对外接口账号修改
        Route::post('system_out/update/:id', 'v1.setting.SystemOut/update')->option(['real_name' => '对外接口账号修改']);
        //设置账号是否禁用
        Route::put('system_out/set_status/:id/:status', 'v1.setting.SystemOut/set_status')->option(['real_name' => '设置账号是否禁用']);
        //删除账号
        Route::delete('system_out/delete/:id', 'v1.setting.SystemOut/delete')->option(['real_name' => '删除账号']);

    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 分销管理 相关路由
     */
    Route::group('statistic', function () {
        /** 用户统计 */
        //用户基础
        Route::get('user/get_basic', 'v1.statistic.UserStatistic/getBasic')->option(['real_name' => '用户基础统计']);
        //用户增长趋势
        Route::get('user/get_trend', 'v1.statistic.UserStatistic/getTrend')->option(['real_name' => '用户增长趋势']);
        //微信用户
        Route::get('user/get_wechat', 'v1.statistic.UserStatistic/getWechat')->option(['real_name' => '微信用户统计']);
        //微信用户成长趋势
        Route::get('user/get_wechat_trend', 'v1.statistic.UserStatistic/getWechatTrend')->option(['real_name' => '微信用户成长趋势']);
        //用户地域排行
        Route::get('user/get_region', 'v1.statistic.UserStatistic/getRegion')->option(['real_name' => '用户地域排行']);
        //用户性别分布
        Route::get('user/get_sex', 'v1.statistic.UserStatistic/getSex')->option(['real_name' => '用户性别分布']);
        //用户数据导出
        Route::get('user/get_excel', 'v1.statistic.UserStatistic/getExcel')->option(['real_name' => '用户数据导出']);
        /** 商品统计 */
        //商品基础
        Route::get('product/get_basic', 'v1.statistic.ProductStatistic/getBasic')->option(['real_name' => '商品基础统计']);
        //商品趋势
        Route::get('product/get_trend', 'v1.statistic.ProductStatistic/getTrend')->option(['real_name' => '商品趋势']);
        //商品排行
        Route::get('product/get_product_ranking', 'v1.statistic.ProductStatistic/getProductRanking')->option(['real_name' => '商品排行']);
        //商品数据导出
        Route::get('product/get_excel', 'v1.statistic.ProductStatistic/getExcel')->option(['real_name' => '商品数据导出']);
        /** 交易统计 */
        //今日营业额统计
        Route::get('trade/top_trade', 'v1.statistic.TradeStatistic/topTrade')->option(['real_name' => '今日营业额统计']);
        //交易统计底部数据
        Route::get('trade/bottom_trade', 'v1.statistic.TradeStatistic/bottomTrade')->option(['real_name' => '交易统计底部数据']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 维护 相关路由
     */
    Route::group('system', function () {
        //系统日志
        Route::get('log', 'v1.system.SystemLog/index')->name('SystemLog')->option(['real_name' => '系统日志']);
        //系统日志管理员搜索条件
        Route::get('log/search_admin', 'v1.system.SystemLog/search_admin')->option(['real_name' => '系统日志管理员搜索条件']);
        //文件校验
        Route::get('file', 'v1.system.SystemFile/index')->name('SystemFile')->option(['real_name' => '文件校验']);
        //打开目录
        //Route::get('file/opendir', 'v1.system.SystemFile/opendir')->option(['real_name' => '打开目录']);
        //读取文件
        //Route::get('file/openfile', 'v1.system.SystemFile/openfile')->option(['real_name' => '读取文件']);
        //保存文件
        //Route::post('file/savefile', 'v1.system.SystemFile/savefile')->option(['real_name' => '保存文件']);
        //数据库所有表
        Route::get('backup', 'v1.system.SystemDatabackup/index')->option(['real_name' => '数据库所有表']);
        //数据备份详情
        Route::get('backup/read', 'v1.system.SystemDatabackup/read')->option(['real_name' => '数据备份详情']);
        //数据备份 优化表
        Route::put('backup/optimize', 'v1.system.SystemDatabackup/optimize')->option(['real_name' => '数据备份优化表']);
        //数据备份 修复表
        Route::put('backup/repair', 'v1.system.SystemDatabackup/repair')->option(['real_name' => '数据备份修复表']);
        //数据备份 备份表
        Route::put('backup/backup', 'v1.system.SystemDatabackup/backup')->option(['real_name' => '数据备份备份表']);
        //数据库备份记录
        Route::get('backup/file_list', 'v1.system.SystemDatabackup/fileList')->option(['real_name' => '数据库备份记录']);
        //删除数据库备份记录
        Route::delete('backup/del_file', 'v1.system.SystemDatabackup/delFile')->option(['real_name' => '删除数据库备份记录']);
        //导入数据库备份记录
        Route::post('backup/import', 'v1.system.SystemDatabackup/import')->option(['real_name' => '导入数据库备份记录']);
        //清除用户数据
        Route::get('clear/:type', 'v1.system.SystemClearData/index')->option(['real_name' => '清除用户数据']);
        //清除系统缓存
        Route::get('refresh_cache/cache', 'v1.system.Clear/refresh_cache')->option(['real_name' => '清除系统缓存']);
        //清除系统日志
        Route::get('refresh_cache/log', 'v1.system.Clear/delete_log')->option(['real_name' => '清除系统日志']);
        //域名替换接口
        Route::post('replace_site_url', 'v1.system.SystemClearData/replaceSiteUrl')->option(['real_name' => '域名替换']);
        //预热营销商品redis库存
        Route::post('hot_product_stock', 'v1.system.SystemClearData/hotProductStock')->option(['real_name' => '预热营销商品redis库存']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 用户模块 相关路由
     */
    Route::group('user', function () {
        //添加用户
        Route::post('user/save', 'v1.user.user/save_info')->option(['real_name' => '添加用户']);
        //同步微信用户
        Route::get('user/syncUsers', 'v1.user.user/syncWechatUsers')->option(['real_name' => '同步微信用户']);
        //用户列表头部数据
        Route::get('user/type_header', 'v1.user.user/type_header')->option(['real_name' => '用户列表头部数据']);
        //赠送用户等级
        Route::get('give_level/:id', 'v1.user.user/give_level')->option(['real_name' => '赠送用户等级']);
        //执行赠送用户等级
        Route::put('save_give_level/:id', 'v1.user.user/save_give_level')->option(['real_name' => '执行赠送用户等级']);
        //赠送付费会员时长
        Route::get('give_level_time/:id', 'v1.user.user/give_level_time')->option(['real_name' => '赠送付费会员时长']);
        //执行赠送付费会员时长
        Route::put('save_give_level_time/:id', 'v1.user.user/save_give_level_time')->option(['real_name' => '执行赠送付费会员时长']);
        //清除用户等级
        Route::delete('del_level/:id', 'v1.user.user/del_level')->option(['real_name' => '清除用户等级']);
        //修改积分余额表单
        Route::get('edit_other/:id', 'v1.user.user/edit_other')->option(['real_name' => '修改积分余额表单']);
        //修改积分余额
        Route::put('update_other/:id', 'v1.user.user/update_other')->option(['real_name' => '修改积分余额']);
        //修改用户状态
        Route::put('set_status/:status/:id', 'v1.user.user/set_status')->option(['real_name' => '修改用户状态']);
        //获取指定用户的信息
        Route::get('one_info/:id', 'v1.user.user/oneUserInfo')->option(['real_name' => '获取指定用户的信息']);
        //商品浏览列表
        Route::get('visit_list/:id', 'v1.user.user/visitList')->option(['real_name' => '商品浏览列表']);
        //推荐人记录列表
        Route::get('spread_list/:id', 'v1.user.user/spreadList')->option(['real_name' => '推荐人记录列表']);
        /*会员设置模块*/
        //添加用户等级表单
        Route::get('user_level/create', 'v1.user.UserLevel/create')->option(['real_name' => '添加用户等级表单']);
        //添加或修改用户等级
        Route::post('user_level', 'v1.user.UserLevel/save')->option(['real_name' => '添加或修改用户等级']);
        //用户等级详情
        Route::get('user_level/read/:id', 'v1.user.UserLevel/read')->option(['real_name' => '用户等级详情']);
        //获取系统设置的用户等级列表
        Route::get('user_level/vip_list', 'v1.user.UserLevel/get_system_vip_list')->option(['real_name' => '获取系统设置的用户等级列表']);
        //删除用户等级
        Route::put('user_level/delete/:id', 'v1.user.UserLevel/delete')->option(['real_name' => '删除用户等级']);
        //设置用户等级上下架
        Route::put('user_level/set_show/:id/:is_show', 'v1.user.UserLevel/set_show')->option(['real_name' => '设置用户等级上下架']);
        //用户等级列表快速编辑
        Route::put('user_level/set_value/:id', 'v1.user.UserLevel/set_value')->option(['real_name' => '用户等级列表快速编辑']);
        //用户等级任务列表
        Route::get('user_level/task/:level_id', 'v1.user.UserLevel/get_task_list')->option(['real_name' => '用户等级任务列表']);
        //快速编辑用户等级任务
        Route::put('user_level/set_task/:id', 'v1.user.UserLevel/set_task_value')->option(['real_name' => '快速编辑用户等级任务']);
        //设置用户等级任务显示|隐藏
        Route::put('user_level/set_task_show/:id/:is_show', 'v1.user.UserLevel/set_task_show')->option(['real_name' => '设置用户等级任务显示|隐藏']);
        //设置用户等级任务是否务必达成
        Route::put('user_level/set_task_must/:id/:is_must', 'v1.user.UserLevel/set_task_must')->option(['real_name' => '设置用户等级任务是否务必达成']);
        //添加用户等级任务表单
        Route::get('user_level/create_task', 'v1.user.UserLevel/create_task')->option(['real_name' => '添加用户等级任务表单']);
        //保存或修改用户等级任务
        Route::post('user_level/save_task', 'v1.user.UserLevel/save_task')->option(['real_name' => '保存或修改用户等级任务']);
        //删除用户等级任务
        Route::delete('user_level/delete_task/:id', 'v1.user.UserLevel/delete_task')->option(['real_name' => '删除用户等级任务']);
        //获取用户分组列表
        Route::get('user_group/list', 'v1.user.UserGroup/index')->option(['real_name' => '获取用户分组列表']);
        //添加修改分组表单
        Route::get('user_group/add/:id', 'v1.user.UserGroup/add')->option(['real_name' => '添加修改分组表单']);
        //保存分组表单数据
        Route::post('user_group/save', 'v1.user.UserGroup/save')->option(['real_name' => '保存分组表单数据']);
        //删除用户分组数据
        Route::delete('user_group/del/:id', 'v1.user.UserGroup/delete')->option(['real_name' => '删除用户分组数据']);
        //用户分组表单
        Route::post('set_group', 'v1.user.user/set_group')->option(['real_name' => '用户分组表单']);
        //设置用户分组
        Route::put('save_set_group', 'v1.user.user/save_set_group')->option(['real_name' => '设置用户分组']);
        //用户标签列表
        Route::get('user_label', 'v1.user.UserLabel/index')->option(['real_name' => '用户标签列表']);
        //同步企业微信标签
        Route::get('synchro/work/label', 'v1.user.UserLabel/synchroWorkLabel')->option(['real_name' => '同步企业微信标签']);
        //添加或修改用户标签表单
        Route::get('user_label/add/:id', 'v1.user.UserLabel/add')->option(['real_name' => '添加或修改用户标签表单']);
        //添加或修改用户标签
        Route::post('user_label/save', 'v1.user.UserLabel/save')->option(['real_name' => '添加或修改用户标签']);
        //删除用户标签
        Route::delete('user_label/del/:id', 'v1.user.UserLabel/delete')->option(['real_name' => '删除用户标签']);
        //设置用户标签
        Route::post('set_label', 'v1.user.user/set_label')->option(['real_name' => '设置用户标签']);
        //获取用户标签
        Route::get('label/:uid', 'v1.user.UserLabel/getUserLabel')->option(['real_name' => '获取用户标签']);
        //获取用户标签分类全部
        Route::get('user_label_cate/all', 'v1.user.UserLabelCate/getAll')->option(['real_name' => '获取用户标签分类全部']);
        //设置和取消用户标签
        Route::post('label/:uid', 'v1.user.UserLabel/setUserLabel')->option(['real_name' => '设置和取消用户标签']);
        //保存用户标签
        Route::put('save_set_label', 'v1.user.user/save_set_label')->option(['real_name' => '保存用户标签']);
        //会员卡批次列表
        Route::get('member_batch/index', 'v1.user.member.MemberCardBatch/index')->option(['real_name' => '会员卡批次列表']);
        //添加会员卡批次
        Route::post('member_batch/save/:id', 'v1.user.member.MemberCardBatch/save')->option(['real_name' => '添加会员卡批次']);
        //会员卡列表
        Route::get('member_card/index/:card_batch_id', 'v1.user.member.MemberCard/index')->option(['real_name' => '会员卡列表']);
        //会员卡修改状态
        Route::get('member_card/set_status', 'v1.user.member.MemberCard/set_status')->option(['real_name' => '会员卡修改状态']);
        //会员卡批次快速修改
        Route::get('member_batch/set_value/:id', 'v1.user.member.MemberCardBatch/set_value')->option(['real_name' => '会员卡批次快速修改']);
        //会员类型列表
        Route::get('member/ship', 'v1.user.member.MemberCard/member_ship')->option(['real_name' => '会员类型列表']);
        //会员类型修改状态
        Route::get('member_ship/set_ship_status', 'v1.user.member.MemberCard/set_ship_status')->option(['real_name' => '会员类型修改状态']);
        //会员卡类型编辑
        Route::post('member_ship/save/:id', 'v1.user.member.MemberCard/ship_save')->option(['real_name' => '会员卡类型编辑']);
        //会员类型删除
        Route::delete('member_ship/delete/:id', 'v1.user.member.MemberCard/delete')->option(['real_name' => '会员类型删除']);
        //兑换会员卡二维码
        Route::get('member_scan', 'v1.user.member.MemberCardBatch/member_scan')->option(['real_name' => '兑换会员卡二维码']);
        //会员记录
        Route::get('member/record', 'v1.user.member.MemberCard/member_record')->option(['real_name' => '会员记录']);
        //会员权益列表
        Route::get('member/right', 'v1.user.member.MemberCard/member_right')->option(['real_name' => '会员权益列表']);
        //会员权益修改
        Route::post('member_right/save/:id', 'v1.user.member.MemberCard/right_save')->option(['real_name' => '会员权益修改']);
        //保存会员协议
        Route::post('member_agreement/save/:id', 'v1.user.member.MemberCardBatch/save_member_agreement')->option(['real_name' => '会员协议']);
        //获取会员协议
        Route::get('member/agreement', 'v1.user.member.MemberCardBatch/getAgreement')->option(['real_name' => '获取会员协议']);
        //会员类型select
        Route::get('member/ship_select', 'v1.user.member.MemberCard/get_ship_select')->option(['real_name' => '会员类型select']);
        //用户管理资源路由
        Route::resource('user', 'v1.user.user')->option(['real_name' => [
            'index' => '获取用户列表',
            'read' => '获取用户详情',
            'create' => '获取创建用户表单',
            'save' => '保存用户',
            'edit' => '获取修改用户表单',
            'update' => '修改用户',
            'delete' => '删除用户'
        ]]);
        //标签分类
        Route::resource('user_label_cate', 'v1.user.UserLabelCate')->option(['real_name' => [
            'index' => '获取标签分类列表',
            'read' => '获取标签分类详情',
            'create' => '获取创建标签分类表单',
            'save' => '保存标签分类',
            'edit' => '获取修改标签分类表单',
            'update' => '修改标签分类',
            'delete' => '删除标签分类'
        ]]);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * 售后 相关路由
     */
    Route::group('refund', function () {
        //售后列表
        Route::get('list', 'v1.order.RefundOrder/getRefundList')->option(['real_name' => '售后订单列表']);
        //商家同意退款，等待用户退货
        Route::get('agree/:order_id', 'v1.order.RefundOrder/agreeRefund')->option(['real_name' => '商家同意退款，等待用户退货']);
        //售后订单备注
        Route::put('remark/:id', 'v1.order.RefundOrder/remark')->option(['real_name' => '售后订单备注']);
        //售后订单退款表单
        Route::get('refund/:id', 'v1.order.RefundOrder/refund')->name('StoreOrderRefund')->option(['real_name' => '售后订单退款表单']);
        //售后订单退款
        Route::put('refund/:id', 'v1.order.RefundOrder/update_refund')->name('StoreOrderUpdateRefund')->option(['real_name' => '售后订单退款']);
        //售后详情
        Route::get('detail/:id', 'v1.order.RefundOrder/refundDetail')->option(['real_name' => '售后订单详情']);
    })->middleware([
        \app\http\middleware\AllowOriginMiddleware::class,
        \app\http\middleware\admin\AdminAuthTokenMiddleware::class,
        \app\http\middleware\admin\AdminCkeckRoleMiddleware::class,
        \app\http\middleware\admin\AdminLogMiddleware::class
    ]);
    /**
     * miss 路由
     */
    Route::miss(function () {
        if (app()->request->isOptions()) {
            $header = Config::get('cookie.header');
            $header['Access-Control-Allow-Origin'] = app()->request->header('origin');
            return Response::create('ok')->code(200)->header($header);
        } else
            return Response::create()->code(404);
    });
})->prefix('admin.')->middleware(InstallMiddleware::class);
