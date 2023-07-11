<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

use app\http\middleware\AllowOriginMiddleware;
use app\http\middleware\InstallMiddleware;
use app\http\middleware\cashier\AuthTokenMiddleware;
use app\http\middleware\cashier\CashierCheckRoleMiddleware;
use app\http\middleware\StationOpenMiddleware;
use think\facade\Config;
use think\facade\Route;
use think\Response;

/**
 * 门店路由配置
 */
Route::group('cashierapi', function () {

    /**
     * 不需要登录不验证权限
     */
    Route::group(function () {
        //账号密码登录
        Route::post('login', 'Login/login')->name('login')->option(['real_name' => '账号密码登录']);
        //微信扫码登录
        Route::get('wechat_scan_login', 'Login/wechatScanLogin')->name('wechatScanLogin')->option(['real_name' => '微信扫码登录']);
        //企业微信扫码登录
        Route::get('work_scan_login', 'Login/workScanLogin')->name('workScanLogin')->option(['real_name' => '企业微信扫码登录']);
        //企业微信配置
        Route::get('work/config', 'Login/getWechatConfig')->name('getWechatConfig')->option(['real_name' => '企业微信配置']);
        //扫码登录状态信息检测获取
        Route::post('check_scan_login', 'Login/checkScanLogin')->name('checkScanLogin')->option(['real_name' => '扫码登录状态信息检测获取']);
        //登录信息
        Route::get('login/info', 'Login/info')->name('loginInfo')->option(['real_name' => '登录信息']);
        //图片验证码
        Route::get('captcha_store', 'Login/captcha')->name('captcha')->option(['real_name' => '图片验证码']);
    });

    /**
     * 只需登录不验证权限
     */
    Route::group(function () {
        //获取logo
        Route::get('logo', 'Common/getLogo')->option(['real_name' => '获取logo']);
        //获取配置
        Route::get('config', 'Common/getConfig')->option(['real_name' => '获取配置']);
        //erp配置
        Route::get('erp/config', 'Common/getConfig')->option(['real_name' => '获取配置']);
        //获取未读消息
        Route::get('jnotice', 'Common/jnotice')->option(['real_name' => '获取未读消息']);
        //获取省市区街道
        Route::get('city', 'Common/city')->option(['real_name' => '获取省市区街道']);
        //获取搜索菜单列表
        Route::get('menusList', 'Common/menusList')->option(['real_name' => '搜索菜单列表']);
        //修改当前管理员信息
        Route::put('update_store', 'Login/updateStore')->name('updateStore')->option(['real_name' => '修改当前登录店员信息']);
        //退出登录
        Route::get('logout', 'Login/logOut')->option(['real_name' => '退出登录']);
        //修改收银员信息
        Route::put('updatePwd', 'User/updatePwd')->option(['real_name' => '修改收银员信息']);

        //公共类
        Route::post('upload/image', 'Common/upload_image')->name('uploadImage');//图片上传

    })->middleware(AuthTokenMiddleware::class);

    /**
     * 需登录验证权限
     */
    Route::group(function () {
        //首页头部统计数据
        Route::get('home/header', 'Common/homeStatics')->option(['real_name' => '首页头部统计数据']);
        //首页营业趋势图表
        Route::get('home/operate', 'Common/operateChart')->option(['real_name' => '首页营业趋势图表']);
        //首页交易图表
        Route::get('home/orderChart', 'Common/orderChart')->option(['real_name' => '首页交易图表']);
        //首页店员统计
        Route::get('home/staff', 'Common/staffChart')->option(['real_name' => '首页店员统计']);
        //轮询查询扫码订单支付状态
        Route::post('check_order_status/:type', 'Common/checkOrderStatus')->option(['real_name' => '轮询订单状态接口'])->name('checkOrderStatus');//轮询订单状态接口

        //获取充值套餐
        Route::get('store/recharge_info', 'Recharge/rechargeInfo')->option(['real_name' => '获取充值套餐']);
        //收银台用户充值
        Route::post('store/recharge', 'Recharge/recharge')->option(['real_name' => '获取充值套餐']);

        //获取登录店员详情
        Route::get('user/cashier_info', 'User/getCashierInfo')->option(['real_name' => '获取登录店员详情']);
        //获取当前门店店员列表和店员信息
        Route::get('user/cashier_list', 'User/getCashierList')->option(['real_name' => '获取当前门店店员列表和店员信息']);
        //收银台选择用户列表
        Route::get('user/get_list', 'User/getUserList')->option(['real_name' => '收银台选择用户列表']);
        //收银台切换购物车用户
        Route::post('user/switch/:cashierId', 'User/switchCartUser')->option(['real_name' => '收银台切换购物车用户']);
        //获取收银台用户信息
        Route::post('user/user_Info', 'User/getUserInfo')->option(['real_name' => '获取收银台用户信息']);
        //收银台获取当前用户信息
        Route::get('user/info/:uid', 'User/getUidInfo')->option(['real_name' => '获取当前用户信息']);
        //收银台获取当前用户记录
        Route::get('user/record/:uid', 'User/userRecord')->option(['real_name' => '收银台获取当前用户记录']);

        //获取收银订单用户
        Route::get('order/get_user_list/:cashierId', 'Order/getUserList')->option(['real_name' => '获取收银订单用户']);
        //收银台挂单列表
        Route::get('order/get_hang_list/:cashierId', 'Order/getHangList')->option(['real_name' => '收银台挂单列表']);
        //收银台删除挂单
        Route::delete('order/del_hang', 'Order/deleteHangOrder')->option(['real_name' => '收银台删除挂单']);
        //收银台订单列表
        Route::post('order/get_order_list/[:orderType]', 'Order/getOrderList')->option(['real_name' => '收银台订单列表']);
        //收银台核销订单列表
        Route::post('order/get_verify_list', 'Order/getVerifyList')->option(['real_name' => '收银台核销订单列表']);
        //收银台核销订单数据
        Route::get('order/verify_cart_info', 'Order/verifyCartInfo')->option(['real_name' => '收银台核销订单数据']);
        //收银台订单核销
        Route::put('order/write_off/:id', 'Order/writeOff')->option(['real_name' => '订单号核销']);
        //收银台订单详情
        Route::get('order/get_order_Info/:id', 'Order/order_info')->option(['real_name' => '收银台订单详情']);
        //收银台获取订单状态
        Route::get('order/get_order_status/:id', 'Order/status')->option(['real_name' => '获取订单状态']);
        //收银台计算订单金额
        Route::post('order/compute/:uid', 'Order/orderCompute')->option(['real_name' => '收银台计算订单金额']);
        //收银台创建订单
        Route::post('order/create/:uid', 'Order/createOrder')->option(['real_name' => '收银台创建订单']);
        //收银台再次支付订单
        Route::post('order/pay/:orderId', 'Order/payOrder')->option(['real_name' => '收银台再次支付订单']);
        //收银台订单小票打印
        Route::get('order/print/:id', 'Order/order_print')->option(['real_name' => '收银台订单小票打印']);
        //收银台订单备注
        Route::put('order/remark/:id', 'Order/remark')->option(['real_name' => '收银台订单备注']);
        //用户优惠券列表
        Route::post('order/coupon_list/:uid', 'Order/couponList')->option(['real_name' => '用户优惠券列表']);
        //用户领取优惠券
        Route::post('coupon/receive/:uid', 'Order/couponReceive')->option(['real_name' => '用户领取优惠券']);
        //收银台获取物流公司
        Route::get('order/express_list', 'Order/express')->option(['real_name' => '收银台获取物流公司']);
        //收银台获取配送员
        Route::get('order/delivery_list', 'Order/getDeliveryList')->option(['real_name' => '收银台获取配送员']);
        //面单默认配置信息
        Route::get('order/sheet_info', 'Order/getSheetInfo')->option(['real_name' => '面单默认配置信息']);
        //获取订单可拆分商品列表
        Route::get('order/split_cart_info/:id', 'Order/split_cart_info')->option(['real_name' => '获取订单可拆分商品列表']);
        //收银台订单发送货
        Route::put('order/delivery/:id', 'Order/updateDelivery')->option(['real_name' => '收银台订单发送货']);


        //订单退款表单
        Route::get('refund/refund/:id', 'Order/refund')->name('StoreOrderRefund')->option(['real_name' => '订单退款表单']);
        //订单退款
        Route::put('order/refund/:id', 'Order/update_refund')->name('StoreOrderUpdateRefund')->option(['real_name' => '订单退款']);
        //收银台退款订单列表
        Route::get('order/get_refund_list', 'Refund/getRefundList')->option(['real_name' => '收银台退款订单列表']);
        //收银台退款订单详情
        Route::get('order/get_refund_Info/:id', 'Refund/detail')->option(['real_name' => '收银台退款订单详情']);
        //售后订单退款
        Route::put('order/order_refund/:id', 'Refund/update_refund')->option(['real_name' => '售后订单退款']);
        //商家同意退款，等待用户退货
        Route::get('order/refund/agree/:id', 'Refund/agreeRefund')->option(['real_name' => '商家同意退款，等待用户退货']);
        //售后订单备注
        Route::put('order/refund/remark/:id', 'Refund/remark')->option(['real_name' => '售后订单备注']);


        //获取商品一级分类
        Route::get('product/get_one_category', 'Product/getOneCategory')->option(['real_name' => '获取商品一级分类']);
        //获取收银台商品列表
        Route::get('product/get_list', 'Product/getProductList')->option(['real_name' => '获取收银台商品列表']);
        //获取收银台商品详情
        Route::get('product/get_info/:id/[:uid]', 'Product/getProductInfo')->option(['real_name' => '获取收银台商品详情']);
        //获取收银台商品规格
        Route::get('product/get_attr/:id/[:uid]', 'Product/getProductAttr')->option(['real_name' => '获取收银台商品详情']);

        //获取收银台购物车信息
        Route::get('cart/get_cart/:uid/:cashierId', 'Order/getCartList')->option(['real_name' => '获取收银台购物车信息']);
        //收银台选择商品进入购物车
        Route::post('cart/set_cart/:uid', 'Order/addCart')->option(['real_name' => '收银台添加购物车']);
        //收银台更改购物车数量
        Route::put('cart/set_cart_num/:uid', 'Order/numCart')->option(['real_name' => '收银台更改购物车数量']);
        //收银台删除购物车信息
        Route::delete('cart/del_cart/:uid', 'Order/delCart')->option(['real_name' => '收银台删除购物车信息']);
        //收银台更改购物车规格
        Route::put('cart/change_cart', 'Order/changeCart')->option(['real_name' => '收银台更改购物车规格']);

        //获取活动商品数量信息
        Route::get('promotions/count/:uid', 'Promotions/promotionsCount')->option(['real_name' => '获取收银台购物车信息']);
        //收银台获取活动商品列表
        Route::get('promotions/activity_list/:uid/:type', 'Promotions/activityList')->option(['real_name' => '收银台获取活动商品列表']);


    })->middleware([AuthTokenMiddleware::class, CashierCheckRoleMiddleware::class]);


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

})->prefix('cashier.')->middleware(InstallMiddleware::class)->middleware(AllowOriginMiddleware::class)->middleware(StationOpenMiddleware::class);


