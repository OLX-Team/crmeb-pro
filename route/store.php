<?php


use app\http\middleware\AllowOriginMiddleware;
use app\http\middleware\InstallMiddleware;
use app\http\middleware\store\AuthTokenMiddleware;
use app\http\middleware\store\StoreCkeckRoleMiddleware;
use app\http\middleware\StationOpenMiddleware;
use app\http\middleware\store\StoreLogMiddleware;
use think\facade\Config;
use think\facade\Route;
use think\Response;

/**
 * 门店路由配置
 */
Route::group('storeapi', function () {

    /**
     * 不需要登录不验证权限
     */
    Route::group(function () {
        Route::get('code', 'Test/code')->name('code')->option(['real_name' => '测试验证码']);
        Route::get('index', 'Test/index')->name('index')->option(['real_name' => '测试主页']);

        //账号密码登录
        Route::post('login', 'Login/login')->name('login')->option(['real_name' => '账号密码登录']);
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
        //修改密码
        Route::put('updatePwd', 'staff.StoreStaff/updateStaffPwd')->option(['real_name' => '修改密码']);

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

    })->middleware([AuthTokenMiddleware::class, StoreCkeckRoleMiddleware::class]);


    /**
     * 基础管理
     */
    Route::group('system', function () {
        //获取角色菜单列表
        Route::get('menusList', 'system.SystemMenus/index')->option(['real_name' => '获取角色菜单列表']);
        //获取菜单子权限列表
        Route::get('sonMenusList/:role_id/:id', 'system.SystemMenus/sonMenusList')->option(['real_name' => '获取菜单子权限列表']);
        //管理员身份列表
        Route::get('role', 'system.SystemRole/index')->option(['real_name' => '管理员身份列表']);
        //管理员身份权限列表
        Route::get('role/create', 'system.SystemRole/create')->option(['real_name' => '管理员身份权限列表']);
        //编辑角色详情
        Route::get('role/:id/edit', 'system.SystemRole/edit')->option(['real_name' => '编辑角色详情']);
        //新建或编辑管理员
        Route::post('role/:id', 'system.SystemRole/save')->option(['real_name' => '新建或编辑管理员']);
        //修改管理员身份状态
        Route::put('role/set_status/:id/:status', 'system.SystemRole/set_status')->option(['real_name' => '修改管理员身份状态']);
        //删除管理员身份
        Route::delete('role/:id', 'system.SystemRole/delete')->option(['real_name' => '删除管理员身份']);
        //获取当前登录门店信息
        Route::get('store/info', 'system.Store/info')->option(['real_name' => '获取当前登录门店信息']);
        //修改当前登录门店信息
        Route::put('store/update', 'system.Store/update')->option(['real_name' => '修改当前登录门店信息']);
        //门店管理员资源路由
        Route::resource('admin', 'system.StoreAdmin')->option(['real_name' => [
            'index' => '获取管理员列表',
            'read' => '获取管理员详情',
            'create' => '获取创建管理员表单',
            'save' => '保存管理员',
            'edit' => '获取修改管理员表单',
            'update' => '修改管理员',
            'delete' => '删除管理员'
        ]]);
        //修改管理员状态
        Route::put('admin/set_status/:id/:status', 'system.StoreAdmin/set_status')->option(['real_name' => '修改管理员状态']);

        Route::get('config/edit_basics', 'system.Config/edit_basics')->option(['real_name' => '门店配置表单']);
        Route::get('config/:type', 'system.Config/getConfig')->option(['real_name' => '获取门店配置']);
        Route::post('config/:type', 'system.Config/save')->option(['real_name' => '保存门店配置']);


        //系统日志
        Route::get('log', 'system.Log/index')->name('SystemLog')->option(['real_name' => '系统日志']);
        //系统日志管理员搜索条件
        Route::get('log/search_admin', 'system.Log/search_admin')->option(['real_name' => '系统日志管理员搜索条件']);

    })->middleware([AuthTokenMiddleware::class, StoreCkeckRoleMiddleware::class, StoreLogMiddleware::class]);

    /**
     * 用户
     */
    Route::group('user', function () {
        //门店搜索用户
        Route::get('search', 'user.User/search')->option(['real_name' => '门店搜索用户']);
        //获取指定用户的信息
        Route::get('one_info/:id', 'user.User/oneUserInfo')->option(['real_name' => '获取指定用户的信息']);
        //用户管理资源路由
        Route::resource('user', 'user.User')->except(['create', 'save'])->option(['real_name' => [
            'index' => '获取门店用户列表',
            'read' => '获取门店用户详情',
            'edit' => '获取修改用户表单',
            'update' => '修改用户',
            'delete' => '删除用户'
        ]]);
        //用户标签分类
        Route::resource('user_label_cate', 'user.UserLabelCate')->option(['real_name' => [
            'index' => '获取标签分类列表',
            'read' => '获取标签分类详情',
            'create' => '获取创建标签分类表单',
            'save' => '保存标签分类',
            'edit' => '获取修改标签分类表单',
            'update' => '修改标签分类',
            'delete' => '删除标签分类'
        ]]);
        //添加或修改用户标签
        Route::post('user_label/save', 'user.UserLabel/save')->option(['real_name' => '添加或修改用户标签']);
        //用户标签
        Route::resource('user_label', 'user.UserLabel')->except(['read', 'save', 'update'])->option(['real_name' => [
            'index' => '获取标签列表',
            'read' => '获取标签详情',
            'create' => '获取创建标签表单',
            'save' => '保存分类',
            'edit' => '获取修改标签表单',
            'update' => '修改标签',
            'delete' => '删除标签'
        ]]);
        //获取用户标签
        Route::get('label/:uid', 'user.UserLabel/getUserLabel')->option(['real_name' => '获取用户标签']);
        //设置和取消用户标签
        Route::post('label/:uid', 'user.UserLabel/setUserLabel')->option(['real_name' => '设置和取消用户标签']);
        //设置用户标签
        Route::post('set_label', 'user.User/set_label')->option(['real_name' => '设置用户标签']);
        //保存用户标签
        Route::put('save_set_label', 'user.User/save_set_label')->option(['real_name' => '保存用户标签']);

        //获取充值套餐
        Route::get('recharge/meal', 'user.UserRecharge/index')->option(['real_name' => '获取充值套餐']);
        //给用户充值
        Route::post('recharge', 'user.UserRecharge/recharge')->option(['real_name' => '获取充值套餐']);
        //获取svip列表
        Route::get('member/ship', 'user.UserMember/index')->option(['real_name' => '获取svip列表']);
        //给用户购买付费会员
        Route::post('member', 'user.UserMember/payMember')->option(['real_name' => '给用户购买付费会员']);

    })->middleware([AuthTokenMiddleware::class, StoreCkeckRoleMiddleware::class, StoreLogMiddleware::class]);

    /**
     * 员工
     */
    Route::group('staff', function () {
        //登录收银端
        Route::get('login_cashier/:id', 'staff.StoreStaff/loginCashier')->option(['real_name' => '登录收银端']);
        //获取店员详情
        Route::get('staff_info', 'staff.StoreStaff/info')->option(['real_name' => '获取店员详情']);
        //获取店员统计详情
        Route::get('info/:id', 'staff.StoreStaff/staffDetail')->option(['real_name' => '获取店员统计详情']);
        //获取店员交易统计
        Route::get('statistics', 'staff.StoreStaff/statistics')->option(['real_name' => '获取店员交易统计']);
        //获取店员交易头部数据
        Route::get('statisticsHeader', 'staff.StoreStaff/statisticsHeader')->option(['real_name' => '获取店员交易头部数据']);
        //获取门店所有店员
        Route::get('staff/all', 'staff.StoreStaff/getStaffSelect')->option(['real_name' => '获取门店所有店员']);
        //店员资源路由
        Route::resource('staff', 'staff.StoreStaff')->option(['real_name' => [
            'index' => '获取门店店员列表',
            'read' => '获取门店店员详情',
            'create' => '添加门店店员表单',
            'save' => '保存店员',
            'edit' => '获取修改门店店员表单',
            'update' => '修改门店店员',
            'delete' => '删除门店店员'
        ]]);

        //店员绑定uid
        Route::post('binding/user', 'staff.StoreStaff/bandingUser')->option(['real_name' => '店员绑定uid']);
        //修改店员状态
        Route::put('staff/set_show/:id/:is_show', 'staff.StoreStaff/set_show')->option(['real_name' => '修改店员状态']);
        //获取配送员统计详情
        Route::get('delivery/info/:id', 'staff.StoreDelivery/deliveryDetail')->option(['real_name' => '获取配送员统计详情']);
        //配送员账单统计
        Route::get('delivery/statistics', 'staff.StoreDelivery/statistics')->option(['real_name' => '配送员账单统计']);//配送员账单统计
        //获取配送员select
        Route::get('delivery/get_delivery_select', 'staff.StoreDelivery/getDeliverySelect')->option(['real_name' => '获取配送员select']);
        //配送员账单统计头部
        Route::get('delivery/statisticsHeader', 'staff.StoreDelivery/statisticsHeader')->option(['real_name' => '配送员账单头部']);
        //配送员资源路由
        Route::resource('delivery', 'staff.StoreDelivery')->option(['real_name' => [
            'index' => '获取配送员列表',
            'read' => '获取配送员详情',
            'create' => '添加配送员表单',
            'save' => '保存配送员',
            'edit' => '获取修改配送员表单',
            'update' => '修改配送员',
            'delete' => '删除配送员'
        ]]);
        //修改配送员状态
        Route::put('delivery/set_show/:id/:status', 'staff.StoreDelivery/set_status')->option(['real_name' => '修改配送员状态']);


    })->middleware([AuthTokenMiddleware::class, StoreCkeckRoleMiddleware::class, StoreLogMiddleware::class]);

    /**
     * 财务
     */
    Route::group('finance', function () {
        //获取门店财务信息
        Route::get('info', 'system.Store/getFinanceInfo')->option(['real_name' => '获取关联用户标签']);
        //设置门店财务信息
        Route::post('info', 'system.Store/setFinanceInfo')->option(['real_name' => '设置门店财务信息']);
        //门店转账列表
        Route::get('storeExtract/list', 'finance.StoreExtract/index')->option(['real_name' => '门店转账列表']);
        //门店转账记录备注
        Route::post('storeExtract/mark/:id', 'finance.StoreExtract/mark')->option(['real_name' => '门店转账记录备注']);
        //门店申请转账
        Route::post('storeExtract/cash', 'finance.StoreExtract/cash')->option(['real_name' => '门店申请转账']);
        //门店流水列表
        Route::get('store_finance_flow/list', 'finance.StoreFinanceFlow/index')->option(['real_name' => '门店流水列表']);
        //获取店员select
        Route::get('store_finance_flow/staff', 'finance.StoreFinanceFlow/getStaffSelect')->option(['real_name' => '获取店员select']);
        //门店流水备注
        Route::post('store_finance_flow/mark/:id', 'finance.StoreFinanceFlow/mark')->option(['real_name' => '门店流水备注']);
        //门店账单记录
        Route::get('store_finance_flow/fund_record', 'finance.StoreFinanceFlow/fundRecord')->option(['real_name' => '门店账单记录']);
        //门店账单详情
        Route::get('store_finance_flow/fund_record_info', 'finance.StoreFinanceFlow/fundRecordInfo')->option(['real_name' => '门店账单详情']);
    })->middleware([AuthTokenMiddleware::class, StoreCkeckRoleMiddleware::class, StoreLogMiddleware::class]);

    /**
     * 商品
     */
    Route::group('product', function () {
        //获取商品规格
        Route::get('product/attrs/:id', 'product.StoreProduct/getAttrs')->option(['real_name' => '获取商品规格']);
        //快速批量修改库存
        Route::put('product/saveStocks/:id', 'product.StoreProduct/saveProductAttrsStock')->option(['real_name' => '快速批量修改库存']);
        //门店同步商品库存
        Route::post('product/synchStocks', 'product.StoreProduct/synchStocks')->option(['real_name' => '门店同步商品库存']);

        //商品管理路由
        Route::resource('product', 'product.StoreProduct')->except(['create', 'save', 'delete'])->option(['real_name' => [
            'index' => '获取商品列表',
            'read' => '获取商品详情',
            'edit' => '获取修改商品表单',
            'update' => '修改商品',
        ]]);
        //获取关联用户标签
        Route::get('getUserLabel', 'product.StoreProduct/getUserLabel')->option(['real_name' => '获取关联用户标签']);
        //商品分类cascader行列表
        Route::get('category', 'product.StoreProduct/cascader_list')->option(['real_name' => '商品分类cascader行列表']);
        //商品列表头部数据
        Route::get('type_header', 'product.StoreProduct/type_header')->option(['real_name' => '商品列表头部数据']);
        //修改商品状态
        Route::put('product/set_show/:id/:is_show', 'product.StoreProduct/set_show')->option(['real_name' => '修改商品状态']);
        //商品评价
        //商品评论列表
        Route::get('reply', 'product.StoreProductReply/index')->option(['real_name' => '商品评论列表']);
        //商品回复评论
        Route::put('reply/set_reply/:id', 'product.StoreProductReply/set_reply')->option(['real_name' => '商品回复评论']);
        //删除商品评论
        Route::delete('reply/:id', 'product.StoreProductReply/delete')->option(['real_name' => '删除商品评论']);

    })->middleware([AuthTokenMiddleware::class, StoreCkeckRoleMiddleware::class, StoreLogMiddleware::class]);


    /**
     * 附件相关路由
     */
    Route::group('file', function () {
        //图片附件列表
        Route::get('file', 'file.SystemAttachment/index')->option(['real_name' => '图片附件列表']);
        //删除图片
        Route::post('file/delete', 'file.SystemAttachment/delete')->option(['real_name' => '删除图片']);
        //移动图片分类表单
        Route::get('file/move', 'file.SystemAttachment/move')->option(['real_name' => '移动图片分类表单']);
        //移动图片分类
        Route::put('file/do_move', 'file.SystemAttachment/moveImageCate')->option(['real_name' => '移动图片分类']);
        //修改图片名称
        Route::put('file/update/:id', 'file.SystemAttachment/update')->option(['real_name' => '修改图片名称']);
        //上传图片
        Route::post('upload/[:upload_type]', 'file.SystemAttachment/upload')->option(['real_name' => '上传图片']);
        //附件分类管理资源路由
        Route::resource('category', 'file.SystemAttachmentCategory')->option(['real_name' => [
            'index' => '获取附件分类列表',
            'read' => '获取附件分类详情',
            'create' => '获取创建附件分类表单',
            'save' => '保存附件分类',
            'edit' => '获取修改附件分类表单',
            'update' => '修改附件分类',
            'delete' => '删除附件分类'
        ]]);

    })->middleware([AuthTokenMiddleware::class, StoreCkeckRoleMiddleware::class, StoreLogMiddleware::class]);

    Route::group('order', function () {

        Route::group('cashier', function () {
            Route::post('user', 'order.Cashier/getUserInfo')->name('cashierUserInfo')->option(['real_name' => '获取收银台用户信息']);
            Route::get('cate', 'order.Cashier/getCateGoryList')->name('cashierCateGoryList')->option(['real_name' => '获取收银台一级分类列表']);
            Route::get('product', 'order.Cashier/getProductList')->name('cashierProductList')->option(['real_name' => '获取收银台商品信息']);
            Route::get('cart/:uid/:staff_id', 'order.Cashier/getCartList')->name('cashierCartList')->option(['real_name' => '获取收银台购物车信息']);
            Route::post('cart/:uid', 'order.Cashier/addCart')->name('cashierAddCart')->option(['real_name' => '收银台添加购物车']);
            Route::post('hang', 'order.Cashier/saveHangOrder')->name('saveHangOrder')->option(['real_name' => '收银台保存挂单']);
            Route::delete('hang', 'order.Cashier/deleteHangOrder')->name('deleteHangOrder')->option(['real_name' => '收银台删除挂单']);
            Route::post('switch/:staffId', 'order.Cashier/switchCartUser')->name('switchCartUser')->option(['real_name' => '收银台切换购物车用户']);
            Route::get('hang/:staffId', 'order.Cashier/getHangOrder')->name('getHangOrder')->option(['real_name' => '收银台获取挂单']);
            Route::get('hang/list/:staffId', 'order.Cashier/getHangOrderList')->name('getHangOrderList')->option(['real_name' => '收银台获取挂单列表分页']);
            Route::delete('cart/:uid', 'order.Cashier/delCart')->name('cashierDelCart')->option(['real_name' => '收银台删除购物车信息']);
            Route::put('cart/:uid', 'order.Cashier/numCart')->name('cashierNumCart')->option(['real_name' => '收银台更改购物车数量']);
            Route::put('changeCart', 'order.Cashier/changeCart')->name('cashierChangeCart')->option(['real_name' => '收银台更改购物车规格']);
            Route::post('compute/:uid', 'order.Cashier/computeOrder')->name('cashierComputeOrder')->option(['real_name' => '收银台计算订单金额']);
            Route::post('create/:uid', 'order.Cashier/createOrder')->name('cashierCreateOrder')->option(['real_name' => '收银台创建订单']);
            Route::get('staff', 'order.Cashier/getStaffList')->name('getStaffList')->option(['real_name' => '获取当前门店店员列表和店员信息']);
            Route::post('code', 'order.Cashier/getAnalysisCode')->name('getAnalysisCode')->option(['real_name' => '扫码自动解析']);
            Route::get('detail/:id/[:uid]', 'order.Cashier/getProductDetail')->name('getProductDetail')->option(['real_name' => '收银台获取商品详情']);
            Route::post('pay/:orderId', 'order.Cashier/payOrder')->name('payOrder')->option(['real_name' => '收银台订单支付']);
            Route::get('cashier_scan', 'order.Cashier/cashier_scan')->name('cashierScan')->option(['real_name' => '门店收银台二维码']);
            Route::post('coupon_list/:uid', 'order.Cashier/couponList')->name('cashierScan')->option(['real_name' => '用户优惠券列表']);
        });
        //充值订单列表
        Route::get('recharge', 'order.Recharge/index')->name('RechargeOrderList')->option(['real_name' => '充值订单列表']);
        //删除充值记录
        Route::delete('recharge/:id', 'order.Recharge/delete')->option(['real_name' => '删除充值记录']);
        //获取用户充值数据
        Route::get('recharge/user_recharge', 'order.Recharge/user_recharge')->option(['real_name' => '获取用户充值数据']);
        //充值退款表单
        Route::get('recharge/:id/refund_edit', 'order.Recharge/refund_edit')->option(['real_name' => '充值退款表单']);
        //充值退款
        Route::put('recharge/:id', 'order.Recharge/refund_update')->option(['real_name' => '充值退款']);
        //保存充值订单备注
        Route::put('recharge/remark/:id', 'order.Recharge/remarks')->option(['real_name' => '保存充值订单备注']);
        //获取充值订单备注
        Route::get('recharge/remark/:id', 'order.Recharge/getRemark')->option(['real_name' => '获取充值订单备注']);
        //充值退款表单
        Route::get('recharge/:id/refund_edit', 'order.Recharge/refund_edit')->option(['real_name' => '充值退款表单']);
        //充值退款
        Route::put('recharge/:id', 'order.Recharge/refund_update')->option(['real_name' => '充值退款']);

        //付费会员订单列表
        Route::get('vip_order', 'order.PayVipOrder/index')->name('PayVipOrderList')->option(['real_name' => '付费会员订单列表']);
        //获取会员备注
        Route::get('vip/remark/:id', 'order.PayVipOrder/getRemark')->name('getRemark')->option(['real_name' => '获取会员备注']);
        //获取会员状态
        Route::get('vip/status/:id', 'order.PayVipOrder/status')->name('getStatusList')->option(['real_name' => '获取会员状态']);
        //保存会员备注
        Route::put('vip/remark/:id', 'order.PayVipOrder/remark')->name('remarkSave')->option(['real_name' => '保存会员备注']);
        //打印订单
        Route::get('print/:id', 'order.Order/order_print')->name('StoreOrderPrint')->option(['real_name' => '打印订单']);
        //获取头部数据
        Route::get('header', 'order.Order/header')->name('StoreOrderHeader')->option(['real_name' => '获取门店订单头部统计']);
        //订单列表
        Route::get('list', 'order.Order/index')->name('StoreOrderList')->option(['real_name' => '订单列表']);
        //订单头部数据
        Route::get('chart', 'order.Order/chart')->name('StoreOrderChart')->option(['real_name' => '订单头部数据']);
        //订单核销
        Route::post('write', 'order.Order/write_order')->name('writeOrder')->option(['real_name' => '订单核销']);
        //获取核销订单商品信息
        Route::get('writeOff/cartInfo', 'order.Order/orderCartInfo')->name('writeOrderCartInfo')->option(['real_name' => '获取核销订单商品信息']);
        //订单号核销
        Route::put('write_update/:order_id', 'order.Order/wirteoff')->name('writeOrderUpdate')->option(['real_name' => '订单号核销']);
        //获取订单编辑表单
        Route::get('edit/:id', 'order.Order/edit')->name('StoreOrderEdit')->option(['real_name' => '获取订单编辑表单']);
        //修改订单
        Route::put('update/:id', 'order.Order/update')->name('StoreOrderUpdate')->option(['real_name' => '修改订单']);
        //确认收货
        Route::put('take/:id', 'order.Order/take_delivery')->name('StoreOrderTakeDelivery')->option(['real_name' => '确认收货']);
        //订单发送货
        Route::put('delivery/:id', 'order.Order/update_delivery')->name('StoreOrderUpdateDelivery')->option(['real_name' => '订单发送货']);
        //获取订单可拆分商品列表
        Route::get('split_cart_info/:id', 'order.Order/split_cart_info')->name('StoreOrderSplitCartInfo')->option(['real_name' => '获取订单可拆分商品列表']);
        //拆单发送货
        Route::put('split_delivery/:id', 'order.Order/split_delivery')->name('StoreOrderSplitDelivery')->option(['real_name' => '拆单发送货']);
        //获取订单拆分子订单列表
        Route::get('split_order/:id', 'order.Order/split_order')->name('StoreOrderSplitOrder')->option(['real_name' => '获取订单拆分子订单列表']);
        //订单退款表单
        Route::get('refund/:id', 'order.Order/refund')->name('StoreOrderRefund')->option(['real_name' => '订单退款表单']);
        //订单退款
        Route::put('refund/:id', 'order.Order/update_refund')->name('StoreOrderUpdateRefund')->option(['real_name' => '订单退款']);
        //快递公司电子面单模版
        Route::get('express/temp', 'order.Order/express_temp')->option(['real_name' => '快递公司电子面单模版']);
        //获取物流信息
        Route::get('express/:id', 'order.Order/get_express')->name('StoreOrderUpdateExpress')->option(['real_name' => '获取物流信息']);
        //获取物流公司
        Route::get('express_list', 'order.Order/express')->name('StoreOrdeRexpressList')->option(['real_name' => '获取物流公司']);
        //订单详情
        Route::get('info/:id', 'order.Order/order_info')->name('StoreOrderorInfo')->option(['real_name' => '订单详情']);
        //获取配送信息表单
        Route::get('distribution/:id', 'order.Order/distribution')->name('StoreOrderorDistribution')->option(['real_name' => '获取配送信息表单']);
        //修改配送信息
        Route::put('distribution/:id', 'order.Order/update_distribution')->name('StoreOrderorUpdateDistribution')->option(['real_name' => '修改配送信息']);
        //获取不退款表单
        Route::get('no_refund/:id', 'order.Order/no_refund')->name('StoreOrderorNoRefund')->option(['real_name' => '获取不退款表单']);
        //修改不退款理由
        Route::put('no_refund/:id', 'order.Order/update_un_refund')->name('StoreOrderorUpdateNoRefund')->option(['real_name' => '修改不退款理由']);
        //线下支付
        Route::post('pay_offline/:id', 'order.Order/pay_offline')->name('StoreOrderorPayOffline')->option(['real_name' => '线下支付']);
        //获取退积分表单
        Route::get('refund_integral/:id', 'order.Order/refund_integral')->name('StoreOrderorRefundIntegral')->option(['real_name' => '获取退积分表单']);
        //修改退积分
        Route::put('refund_integral/:id', 'order.Order/update_refund_integral')->name('StoreOrderorUpdateRefundIntegral')->option(['real_name' => '修改退积分']);
        //修改备注信息
        Route::put('remark/:id', 'order.Order/remark')->name('StoreOrderorRemark')->option(['real_name' => '修改备注信息']);
        //获取订单状态
        Route::get('status/:id', 'order.Order/status')->name('StoreOrderorStatus')->option(['real_name' => '获取订单状态']);
        //删除订单单个
        Route::delete('del/:id', 'order.Order/del')->name('StoreOrderorDel')->option(['real_name' => '删除订单单个']);
        //批量删除订单
        Route::post('dels', 'order.Order/del_orders')->name('StoreOrderorDels')->option(['real_name' => '批量删除订单']);
        //面单默认配置信息
        Route::get('sheet_info', 'order.Order/getDeliveryInfo')->option(['real_name' => '面单默认配置信息']);
        //电子面单模板列表
        Route::get('expr/temp', 'order.Order/expr_temp')->option(['real_name' => '电子面单模板列表']);
        //更多操作打印电子面单
        Route::get('order_dump/:order_id', 'order.Order/order_dump')->option(['real_name' => '更多操作打印电子面单']);
        //批量发货
        Route::get('hand/batch_delivery', 'order.Order/hand_batch_delivery')->option(['real_name' => '批量发货']);
        //自动批量发货
        Route::post('other/batch_delivery', 'order.Order/other_batch_delivery')->option(['real_name' => '自动批量发货']);
        //订单批量删除
        Route::post('batch/del_orders', 'order.Order/del_orders')->option(['real_name' => '订单批量删除']);
        //订单导出
        Route::post('export/:type', 'order.Order/export')->option(['real_name' => '订单导出']);
        //订单列表获取配送员
        Route::get('delivery/list', 'order.Order/getDeliveryList')->option(['real_name' => '订单列表获取配送员']);

    })->middleware([AuthTokenMiddleware::class, StoreCkeckRoleMiddleware::class, StoreLogMiddleware::class]);


    /**
     * 售后 相关路由
     */
    Route::group('refund', function () {
        //售后列表
        Route::get('list', 'order.Refund/getRefundList')->option(['real_name' => '售后订单列表']);
        //商家同意退款，等待用户退货
        Route::get('agree/:order_id', 'order.Refund/agreeRefund')->option(['real_name' => '商家同意退款，等待用户退货']);
        //售后订单备注
        Route::put('remark/:id', 'order.Refund/remark')->option(['real_name' => '售后订单备注']);
        //售后订单退款表单
        Route::get('refund/:id', 'order.Refund/refund')->name('StoreOrderRefund')->option(['real_name' => '售后订单退款表单']);
        //售后订单退款
        Route::put('refund/:id', 'order.Refund/update_refund')->name('StoreOrderUpdateRefund')->option(['real_name' => '售后订单退款']);
        //售后订单详情
        Route::get('detail/:id', 'order.Refund/detail')->name('StoreOrderRefundDetail')->option(['real_name' => '售后订单详情']);

    })->middleware([AuthTokenMiddleware::class, StoreCkeckRoleMiddleware::class, StoreLogMiddleware::class,]);

    /**
     * 导出excel相关路由
     */
    Route::group('export', function () {
        //门店账单导出
        Route::get('financeRecord', 'export.ExportExcel/financeRecord')->option(['real_name' => '门店账单导出']);

    })->middleware([AuthTokenMiddleware::class, StoreCkeckRoleMiddleware::class, StoreLogMiddleware::class]);

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

})->prefix('store.')->middleware(InstallMiddleware::class)->middleware(AllowOriginMiddleware::class)->middleware(StationOpenMiddleware::class);


