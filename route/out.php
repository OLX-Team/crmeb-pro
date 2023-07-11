<?php


use app\http\middleware\AllowOriginMiddleware;
use app\http\middleware\InstallMiddleware;
use app\http\middleware\out\AuthTokenMiddleware;
use app\http\middleware\StationOpenMiddleware;
use think\facade\Route;

/**
 * 对外接口路由配置
 */
Route::group('outapi', function () {

    Route::group(function () {
        //获取token
        Route::post('get_token', 'OutAccount/getToken')->name('getToken');
    })->middleware(StationOpenMiddleware::class);

    //授权接口
    Route::group(function () {
        //商品
        Route::get('product/detail/:spu', 'Product/detail')->name('detail');//商品详情
        Route::put('product/set_show/:spu/:is_show', 'Product/set_show')->name('setShow');//商品状态
        Route::get('product/category', 'Product/category')->name('category');//分类列表
        Route::post('product/set_stock/:spu', 'Product/set_stock')->name('setStock');//商品库存
        //订单
        Route::get('order/list', 'Order/lst')->name('OrderList');//订单列表
        Route::get('order/get_status/:oid', 'Order/get_status')->name('GetStatus');//订单状态
        Route::get('order/get_shipping_type/:oid', 'Order/get_shipping_type')->name('GetShippingType');//收货方式接口
        Route::get('order/delivery_type/:oid', 'Order/delivery_type')->name('deliveryType');//配送信息接口
        Route::put('order/take_delivery/:oid', 'Order/take_delivery')->name('takeDelivery');//确认收货接口
        Route::get('order/invoice/:oid', 'Order/invoice')->name('invoice');//查询发票接口
        Route::get('order/detail/:oid', 'Order/detail')->name('detail');//订单详情接口
        Route::get('order/refund/list', 'Order/refund_list')->name('refundList');//售后订单接口
        Route::post('order/update/:oid', 'Order/update')->name('update');//改价
        Route::get('order/postage', 'Order/postage')->name('postage');//获取商品运费
        Route::post('cart/add', 'Order/cart_add')->name('cartAdd'); //购物车添加
        Route::get('order/confirm', 'Order/confirm')->name('confirm');//订单确认
        Route::post('order/create', 'Order/create')->name('create');//提交订单
        //会员
        Route::get('user/detail/:uid', 'User/index')->name('detail'); //会员详情
        Route::post('user/update_other/:id', 'User/update_other')->name('update_other');//修改积分余额
        Route::post('user/save/:id', 'User/update')->name('update');//用户信息修改
        Route::get('user/address/:uid', 'User/address_list')->name('addressList');//用户地址列表
        Route::get('user/money/:uid', 'User/money')->name('money');  //获取余额详情
        Route::get('user/spread_commission/:uid', 'User/spread_commission')->name('spreadCommission'); //余额佣金明细
    })->middleware(AuthTokenMiddleware::class, true);


})->prefix('out.')->middleware(InstallMiddleware::class)->middleware(AllowOriginMiddleware::class)->middleware(StationOpenMiddleware::class);

