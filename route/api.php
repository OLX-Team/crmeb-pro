<?php


use app\http\middleware\AllowOriginMiddleware;
use app\http\middleware\api\AuthTokenMiddleware;
use app\http\middleware\api\ClientMiddleware;
use app\http\middleware\InstallMiddleware;
use app\http\middleware\StationOpenMiddleware;
use think\facade\Config;
use think\facade\Route;
use think\Response;

/**
 * 用户端路由配置
 */
Route::group('api', function () {

    Route::any('wechat/serve', 'v1.wechat.WechatController/serve');//公众号服务
    Route::any('work/serve', 'v1.wechat.WechatController/work');//公众号服务
    Route::any('wechat/notify', 'v1.wechat.WechatController/notify');//公众号支付回调
    Route::any('routine/notify', 'v1.wechat.AuthController/notify');//小程序支付回调
    Route::any('pay/notify/:type', 'v1.PayController/notify');//支付回调
    Route::get('get_script', 'v1.PublicController/getScript');//统计代码
    Route::get('get_copyright', 'v1.PublicController/getCopyright');//获取版权

    Route::group('work', function () {
        //获取企业微信jsSDK配置
        Route::get('config', 'v1.work.WorkController/config')->name('WorkConfig');
        //获取企业微信应用jsSDK配置
        Route::get('agentConfig', 'v1.work.WorkController/agentConfig')->name('agentConfig');
        //获取客户群详情
        Route::get('groupInfo', 'v1.work.GroupChatController/getGroupInfo')->name('getGroupInfo');
        //获取群成员列表
        Route::get('groupMember/:id', 'v1.work.GroupChatController/getChatMemberList')->name('getChatMemberList');

        Route::group(function () {
            //获取客户信息详情
            Route::get('client/info', 'v1.work.ClientController/getClientInfo')->name('getClientInfo');
            //获取客户订单列表
            Route::get('order/list', 'v1.work.OrderController/getUserOrderList')->name('getWorkOrderList');
            //获取客户订单详情
            Route::get('order/info/:id', 'v1.work.OrderController/orderInfo')->name('getWorkOrderInfo');
            //购买商品记录
            Route::get('product/cart_list', 'v1.work.ProductController/getCartProductList')->name('getCartProductList');
            //浏览记录商品记录
            Route::get('product/visit_list', 'v1.work.ProductController/getVisitProductList')->name('getVisitProductList');

        })->middleware(ClientMiddleware::class);
    });


    Route::group(function () {
        //apple快捷登陆
        Route::post('apple_login', 'v1.LoginController/appleLogin')->name('appleLogin');//微信APP授权
        //账号密码登录
        Route::post('login', 'v1.LoginController/login')->name('login');
        // 获取发短信的key
        Route::get('verify_code', 'v1.LoginController/verifyCode')->name('verifyCode');
        //手机号登录
        Route::post('login/mobile', 'v1.LoginController/mobile')->name('loginMobile');
        //图片验证码
        Route::get('sms_captcha', 'v1.LoginController/captcha')->name('captcha');
        //验证码发送
        Route::post('register/verify', 'v1.LoginController/verify')->name('registerVerify');
        //手机号注册
        Route::post('register', 'v1.LoginController/register')->name('register');
        //手机号修改密码
        Route::post('register/reset', 'v1.LoginController/reset')->name('registerReset');
        // 绑定手机号(静默授权 还未有用户信息)
        Route::post('binding', 'v1.LoginController/binding_phone')->name('bindingPhone');
        // 支付宝复制链接支付
        Route::get('ali_pay', 'v1.order.StoreOrderController/aliPay')->name('aliPay');

    })->middleware(StationOpenMiddleware::class);


    //管理员订单操作类
    Route::group(function () {
        Route::get('admin/erp/config', 'v1.admin.StoreOrderController/getErpConfig')->name('getErpConfig');//获取erp配置
        Route::get('admin/order/statistics', 'v1.admin.StoreOrderController/statistics')->name('adminOrderStatistics');//订单数据统计
        Route::get('admin/order/data', 'v1.admin.StoreOrderController/data')->name('adminOrderData');//订单每月统计数据
        Route::get('admin/order/list', 'v1.admin.StoreOrderController/lst')->name('adminOrderList');//订单列表
        Route::get('admin/refund_order/list', 'v1.admin.StoreOrderController/refundOrderList')->name('RefundOrderList');//退款订单列表
        Route::get('admin/refund_order/detail/:uni', 'v1.admin.StoreOrderController/refundOrderDetail')->name('RefundOrderDetail');//退款订单详情
        Route::post('admin/refund_order/remark', 'v1.admin.StoreOrderController/refundRemark')->name('refundRemark');//退款订单备注
        Route::get('admin/order/detail/:orderId', 'v1.admin.StoreOrderController/detail')->name('adminOrderDetail');//订单详情
        Route::get('admin/order/delivery/gain/:orderId', 'v1.admin.StoreOrderController/delivery_gain')->name('adminOrderDeliveryGain');//订单发货获取订单信息
        Route::post('admin/order/delivery/keep/:id', 'v1.admin.StoreOrderController/delivery_keep')->name('adminOrderDeliveryKeep');//订单发货
        Route::post('admin/order/price', 'v1.admin.StoreOrderController/price')->name('adminOrderPrice');//订单改价
        Route::post('admin/order/remark', 'v1.admin.StoreOrderController/remark')->name('adminOrderRemark');//订单备注
        Route::get('admin/order/time', 'v1.admin.StoreOrderController/time')->name('adminOrderTime');//订单交易额时间统计
        Route::post('admin/order/offline', 'v1.admin.StoreOrderController/offline')->name('adminOrderOffline');//订单支付
        Route::post('admin/order/refund', 'v1.admin.StoreOrderController/refund')->name('adminOrderRefund');//订单退款
        Route::post('admin/order/refund_agree/:id', 'v1.admin.StoreOrderController/agreeRefund')->name('adminOrderAgreeRefund');//商家同意退货退款
        Route::post('order/order_verific', 'v1.admin.StoreOrderController/order_verific')->name('order');//订单核销
        Route::get('admin/order/delivery', 'v1.admin.StoreOrderController/getDeliveryAll')->name('getDeliveryAll');//获取配送员
        Route::get('admin/order/delivery_info', 'v1.admin.StoreOrderController/getDeliveryInfo')->name('getDeliveryInfo');//获取电子面单默认信息
        Route::get('admin/order/export_temp', 'v1.admin.StoreOrderController/getExportTemp')->name('getExportTemp');//获取电子面单模板获取
        Route::get('admin/order/export_all', 'v1.admin.StoreOrderController/getExportAll')->name('getExportAll');//获取物流公司
        Route::get('admin/order/split_cart_info/:id', 'v1.admin.StoreOrderController/split_cart_info')->name('StoreOrderSplitCartInfo')->option(['real_name' => '获取订单可拆分商品列表']);//获取订单可拆分商品列表
        Route::put('admin/order/split_delivery/:id', 'v1.admin.StoreOrderController/split_delivery')->name('StoreOrderSplitDelivery')->option(['real_name' => '拆单发送货']);//拆单发送货
    })->middleware(StationOpenMiddleware::class)->middleware(AuthTokenMiddleware::class, true)->middleware(\app\http\middleware\api\CustomerMiddleware::class);

    //会员授权接口
    Route::group(function () {
        //保存商品评价回复
        Route::post('reply/comment/:id', 'v1.store.StoreProductController/replyComment')->name('replyComment');
        //获取评论详情
        Route::get('reply/info/:id', 'v1.store.StoreProductController/replyInfo')->name('replyInfo');
        //评论回复点赞
        Route::post('reply/praise/:id', 'v1.store.StoreProductController/commentPraise')->name('commentPraise');
        //取消评论回复点赞
        Route::post('reply/un_praise/:id', 'v1.store.StoreProductController/unCommentPraise')->name('unCommentPraise');
        //评论点赞
        Route::post('reply/reply_praise/:id', 'v1.store.StoreProductController/replyPraise')->name('replyPraise');
        //取消评论点赞
        Route::post('reply/un_reply_praise/:id', 'v1.store.StoreProductController/unReplyPraise')->name('unReplyPraise');

        // 用户注销
        Route::get('cancel/user', 'v1.PublicController/cancelUser')->name('cancelUser');
        //经纬度转位置信息
        Route::get('geoLbscoder', 'v1.PublicController/geoLbscoder')->name('geoLbscoder');
        //用户修改手机号
        Route::post('user/updatePhone', 'v1.LoginController/update_binding_phone')->name('updateBindingPhone');
        //设置登录code
        Route::post('user/code', 'v1.user.StoreService/setLoginCode')->name('setLoginCode');
        //查看code是否可用
        Route::get('user/code', 'v1.LoginController/setLoginKey')->name('getLoginKey');
        //用户绑定手机号
        Route::post('user/binding', 'v1.LoginController/user_binding_phone')->name('userBindingPhone');
        Route::get('logout', 'v1.LoginController/logout')->name('logout');// 退出登录
        Route::post('switch_h5', 'v1.LoginController/switch_h5')->name('switch_h5');// 切换账号
        //商品类
        Route::get('product/code/:id', 'v1.store.StoreProductController/code')->name('productCode');//商品分享二维码 推广员

        //公共类
        Route::post('upload/image', 'v1.PublicController/upload_image')->name('uploadImage');//图片上传
        //用户类 客服聊天记录
        Route::get('user/service/list', 'v1.user.StoreService/lst')->name('userServiceList');//客服列表
        Route::get('user/service/record', 'v1.user.StoreService/record')->name('userServiceRecord');//客服聊天记录
        Route::post('user/service/feedback', 'v1.user.StoreService/saveFeedback')->name('saveFeedback');//保存客服反馈信息
        Route::get('user/service/feedback', 'v1.user.StoreService/getFeedbackInfo')->name('getFeedbackInfo');//获得客服反馈头部信息


        //用户类  用户
        Route::get('user', 'v1.user.UserController/user')->name('user');//个人中心
        Route::post('user/spread', 'v1.user.UserController/spread')->name('userSpread');//静默绑定授权
        Route::post('user/edit', 'v1.user.UserController/edit')->name('userEdit');//用户修改信息
        Route::get('user/balance', 'v1.user.UserController/balance')->name('userBalance');//用户资金统计
        Route::get('userinfo', 'v1.user.UserController/userinfo')->name('userinfo');// 用户信息
        Route::get('user/rand_code', 'v1.user.UserController/randCode')->name('randCode');//查看用户code
        Route::get('user/visit_list', 'v1.user.UserController/visitList')->name('visitList');//商品浏览列表
        Route::delete('user/visit', 'v1.user.UserController/visitDelete')->name('visitDelete');//商品浏览记录删除


        //用户类  地址
        Route::get('address/detail/:id', 'v1.user.UserAddressController/address')->name('address');//获取单个地址
        Route::get('address/list', 'v1.user.UserAddressController/address_list')->name('addressList');//地址列表
        Route::post('address/default/set', 'v1.user.UserAddressController/address_default_set')->name('addressDefaultSet');//设置默认地址
        Route::get('address/default', 'v1.user.UserAddressController/address_default')->name('addressDefault');//获取默认地址
        Route::post('address/edit', 'v1.user.UserAddressController/address_edit')->name('addressEdit');//修改 添加 地址
        Route::post('address/del', 'v1.user.UserAddressController/address_del')->name('addressDel');//删除地址
        //用户类 收藏
        Route::get('collect/user', 'v1.user.UserCollectController/collect_user')->name('collectUser');//收藏商品列表
        Route::post('collect/add', 'v1.user.UserCollectController/collect_add')->name('collectAdd');//添加收藏
        Route::post('collect/del', 'v1.user.UserCollectController/collect_del')->name('collectDel');//取消收藏
        Route::post('collect/all', 'v1.user.UserCollectController/collect_all')->name('collectAll');//批量添加收藏

        Route::get('brokerage_rank', 'v1.user.UserBrokerageController/brokerage_rank')->name('brokerageRank');//佣金排行
        Route::get('rank', 'v1.user.UserController/rank')->name('rank');//推广人排行
        //用戶类 分享
        Route::post('user/share', 'v1.PublicController/user_share')->name('user_share');//记录用户分享
        Route::get('user/share/words', 'v1.PublicController/copy_share_words')->name('user_share_words');//关键字分享
        //用户类 点赞
//    Route::post('like/add', 'user.UserController/like_add')->name('likeAdd');//添加点赞
//    Route::post('like/del', 'user.UserController/like_del')->name('likeDel');//取消点赞
        //用户类 签到
        Route::get('sign/config', 'v1.user.UserSignController/sign_config')->name('signConfig');//签到配置
        Route::get('sign/list', 'v1.user.UserSignController/sign_list')->name('signList');//签到列表
        Route::get('sign/month', 'v1.user.UserSignController/sign_month')->name('signIntegral');//签到列表（年月）
        Route::post('sign/user', 'v1.user.UserSignController/sign_user')->name('signUser');//签到用户信息
        Route::post('sign/integral', 'v1.user.UserSignController/sign_integral')->name('signIntegral');//签到
        //优惠券类
        Route::post('coupon/receive', 'v1.store.StoreCouponsController/receive')->name('couponReceive'); //领取优惠券
        Route::post('coupon/receive/batch', 'v1.store.StoreCouponsController/receive_batch')->name('couponReceiveBatch'); //批量领取优惠券
        Route::get('coupons/user/:types', 'v1.store.StoreCouponsController/user')->name('couponsUser');//用户已领取优惠券
        Route::get('coupons/order/:price', 'v1.store.StoreCouponsController/order')->name('couponsOrder');//优惠券 订单列表
        //购物车类
        Route::get('cart/list', 'v1.store.StoreCartController/lst')->name('cartList'); //购物车列表
        Route::post('cart/add', 'v1.store.StoreCartController/add')->name('cartAdd'); //购物车添加
        Route::post('cart/del', 'v1.store.StoreCartController/del')->name('cartDel'); //购物车删除
        Route::post('order/cancel', 'v1.order.StoreOrderController/cancel')->name('orderCancel'); //订单取消
        Route::post('cart/num', 'v1.store.StoreCartController/num')->name('cartNum'); //购物车 修改商品数量
        Route::get('cart/count', 'v1.store.StoreCartController/count')->name('cartCount'); //购物车 获取数量
        //订单类
        Route::post('order/confirm', 'v1.order.StoreOrderController/confirm')->name('orderConfirm'); //订单确认
        Route::post('order/computed/:key', 'v1.order.StoreOrderController/computedOrder')->name('computedOrder'); //计算订单金额
        Route::post('order/create/:key', 'v1.order.StoreOrderController/create')->name('orderCreate'); //订单创建
        Route::get('order/data', 'v1.order.StoreOrderController/data')->name('orderData'); //订单统计数据
        Route::get('order/list', 'v1.order.StoreOrderController/lst')->name('orderList'); //订单列表
        Route::get('order/detail/:uni', 'v1.order.StoreOrderController/detail')->name('orderDetail'); //订单详情
        //订单售后
        Route::get('order/refund/reason', 'v1.order.StoreOrderController/refund_reason')->name('orderRefundReason'); //订单退款理由
        Route::get('order/refund/cart_info/:id', 'v1.order.StoreOrderController/refundCartInfo')->name('StoreOrderRefundCartInfo');//获取退款商品列表
        Route::post('order/refund/cart_info', 'v1.order.StoreOrderController/refundCartInfoList')->name('StoreOrderRefundCartInfoList');//获取退款商品列表
        Route::post('order/refund/apply/:id', 'v1.order.StoreOrderController/applyRefund')->name('StoreOrderApplRefund');//订单申请退款V2
        Route::post('order/refund/verify', 'v1.order.StoreOrderController/refund_verify')->name('orderRefundVerify'); //订单申请退款
        Route::post('order/refund/express', 'v1.order.StoreOrderController/refund_express')->name('orderRefundExpress'); //退货退款填写订单号
        Route::get('order/refund/list', 'v1.order.StoreOrderRefundController/lst')->name('orderRefundList'); //售后订单列表
        Route::get('order/refund/detail/:uni', 'v1.order.StoreOrderRefundController/detail')->name('orderRefundDetail'); //售后订单详情
        Route::post('order/refund/cancel/:uni', 'v1.order.StoreOrderRefundController/cancelApply')->name('orderRefundCancel'); //取消售后申请
        Route::get('order/refund/del/:uni', 'v1.order.StoreOrderRefundController/delRefundOrder')->name('delRefundOrder'); //删除已退款和拒绝退款的订单

        Route::post('order/take', 'v1.order.StoreOrderController/take')->name('orderTake'); //订单收货
        Route::get('order/express/:uni/[:type]', 'v1.order.StoreOrderController/express')->name('orderExpress'); //订单查看物流
        Route::post('order/del', 'v1.order.StoreOrderController/del')->name('orderDel'); //订单删除
        Route::post('order/again', 'v1.order.StoreOrderController/again')->name('orderAgain'); //订单 再次下单
        Route::post('order/pay', 'v1.order.StoreOrderController/pay')->name('orderPay'); //订单支付
        Route::post('order/product', 'v1.order.StoreOrderController/product')->name('orderProduct'); //订单商品信息
        Route::post('order/comment', 'v1.order.StoreOrderController/comment')->name('orderComment'); //订单评价
        Route::get('order/pay_cashier', 'v1.order.StoreOrderController/payCashierOrder')->name('payCashierOrder'); //用户门店下单付款
        //活动---砍价
        Route::get('bargain/detail/:id', 'v1.activity.StoreBargainController/detail')->name('bargainDetail');//砍价商品详情
        Route::post('bargain/start', 'v1.activity.StoreBargainController/start')->name('bargainStart');//砍价开启
        Route::post('bargain/start/user', 'v1.activity.StoreBargainController/start_user')->name('bargainStartUser');//砍价 开启砍价用户信息
        Route::post('bargain/share', 'v1.activity.StoreBargainController/share')->name('bargainShare');//砍价 观看/分享/参与次数
        Route::post('bargain/help', 'v1.activity.StoreBargainController/help')->name('bargainHelp');//砍价 帮助好友砍价
        Route::post('bargain/help/price', 'v1.activity.StoreBargainController/help_price')->name('bargainHelpPrice');//砍价 砍掉金额
        Route::post('bargain/help/count', 'v1.activity.StoreBargainController/help_count')->name('bargainHelpCount');//砍价 砍价帮总人数、剩余金额、进度条、已经砍掉的价格
        Route::post('bargain/help/list', 'v1.activity.StoreBargainController/help_list')->name('bargainHelpList');//砍价 砍价帮
        Route::get('bargain/user/list', 'v1.activity.StoreBargainController/user_list')->name('bargainUserList');//砍价列表(已参与)
        Route::post('bargain/user/cancel', 'v1.activity.StoreBargainController/user_cancel')->name('bargainUserCancel');//砍价取消
        Route::get('bargain/poster_info/:bargainId', 'v1.activity.StoreBargainController/posterInfo')->name('posterInfo');//砍价海报详细信息
        //活动---拼团
        Route::get('combination/pink/:id', 'v1.activity.StoreCombinationController/pink')->name('combinationPink');//拼团开团
        Route::post('combination/remove', 'v1.activity.StoreCombinationController/remove')->name('combinationRemove');//拼团 取消开团
        Route::get('combination/poster_info/:id', 'v1.activity.StoreCombinationController/posterInfo')->name('pinkPosterInfo');//拼团海报详细获取
        //账单类
        Route::get('commission', 'v1.user.UserBrokerageController/commission')->name('commission');//推广数据 昨天的佣金 累计提现金额 当前佣金
        Route::post('spread/people', 'v1.user.UserController/spread_people')->name('spreadPeople');//推荐用户
        Route::post('spread/order', 'v1.user.UserBrokerageController/spread_order')->name('spreadOrder');//推广订单
        Route::get('spread/commission/:type', 'v1.user.UserBillController/spread_commission')->name('spreadCommission');//推广佣金明细
        Route::get('spread/count/:type', 'v1.user.UserBrokerageController/spread_count')->name('spreadCount');//推广 佣金 3/提现 4 总和
        Route::get('integral/list', 'v1.user.UserBillController/integral_list')->name('integralList');//积分记录
        Route::get('user/routine_code', 'v1.user.UserBillController/getRoutineCode')->name('getRoutineCode');//小程序二维码
        Route::get('user/spread_info', 'v1.user.UserBillController/getSpreadInfo')->name('getSpreadInfo');//获取分销背景等信息
        //提现类
        Route::get('extract/bank', 'v1.user.UserExtractController/bank')->name('extractBank');//提现银行/提现最低金额
        Route::post('extract/cash', 'v1.user.UserExtractController/cash')->name('extractCash');//提现申请
        //充值类
        Route::post('recharge/recharge', 'v1.user.UserRechargeController/recharge')->name('rechargeRecharge');//统一充值
        Route::post('recharge/routine', 'v1.user.UserRechargeController/routine')->name('rechargeRoutine');//小程序充值
        Route::post('recharge/wechat', 'v1.user.UserRechargeController/wechat')->name('rechargeWechat');//公众号充值
        Route::get('recharge/index', 'v1.user.UserRechargeController/index')->name('rechargeQuota');//充值余额选择
        //会员等级类
        Route::get('user/level/detection', 'v1.user.UserLevelController/detection')->name('userLevelDetection');//检测用户是否可以成为会员
        Route::get('user/level/grade', 'v1.user.UserLevelController/grade')->name('userLevelGrade');//会员等级列表
        Route::get('user/level/task/:id', 'v1.user.UserLevelController/task')->name('userLevelTask');//获取等级任务
        Route::get('user/level/info', 'v1.user.UserLevelController/userLevelInfo')->name('levelInfo');//获取等级任务
        Route::get('user/level/expList', 'v1.user.UserLevelController/expList')->name('expList');//获取等级任务
        Route::get('user/record', 'v1.user.StoreService/recordList')->name('recordList');//获取用户和客服的消息列表

        //首页获取未支付订单
        Route::get('order/nopay', 'v1.order.StoreOrderController/get_noPay')->name('getNoPay');//获取未支付订单

        Route::get('seckill/code/:id', 'v1.activity.StoreSeckillController/code')->name('seckillCode');//秒杀商品海报
        Route::get('combination/code/:id', 'v1.activity.StoreCombinationController/code')->name('combinationCode');//拼团商品海报

        //会员卡
        Route::get('user/member/card/index', 'v1.user.MemberCardController/index')->name('userMemberCardIndex');// 主页会员权益介绍页
        Route::post('user/member/card/draw', 'v1.user.MemberCardController/draw_member_card')->name('userMemberCardDraw');//卡密领取会员卡
        Route::post('user/member/card/create', 'v1.order.OtherOrderController/create')->name('userMemberCardCreate');//购买卡创建订单
        Route::get('user/member/coupons/list', 'v1.user.MemberCardController/memberCouponList')->name('userMemberCouponsList');//会员券列表
        Route::get('user/member/overdue/time', 'v1.user.MemberCardController/getOverdueTime')->name('userMemberOverdueTime');//会员时间
        //线下付款
        Route::post('order/offline/check/price', 'v1.order.OtherOrderController/computed_offline_pay_price')->name('orderOfflineCheckPrice'); //检测线下付款金额
        Route::post('order/offline/create', 'v1.order.OtherOrderController/create')->name('orderOfflineCreate'); //检测线下付款金额
        Route::get('order/offline/pay/type', 'v1.order.OtherOrderController/pay_type')->name('orderOfflineCreate'); //线下付款支付方式
        //积分商城订单
        Route::post('store_integral/order/confirm', 'v1.activity.StoreIntegralOrderController/confirm')->name('storeIntegralOrderConfirm'); //订单确认
        Route::post('store_integral/order/create', 'v1.activity.StoreIntegralOrderController/create')->name('storeIntegralOrderCreate'); //订单创建
        Route::get('store_integral/order/detail/:uni', 'v1.activity.StoreIntegralOrderController/detail')->name('storeIntegralOrderDetail'); //订单详情
        Route::get('store_integral/order/list', 'v1.activity.StoreIntegralOrderController/lst')->name('storeIntegralOrderList'); //订单列表
        Route::post('store_integral/order/take', 'v1.activity.StoreIntegralOrderController/take')->name('storeIntegralOrderTake'); //订单收货
        Route::get('store_integral/order/express/:uni', 'v1.activity.StoreIntegralOrderController/express')->name('storeIntegralOrderExpress'); //订单查看物流
        Route::post('store_integral/order/del', 'v1.activity.StoreIntegralOrderController/del')->name('storeIntegralOrderDel'); //订单删除
        //消息站内信
        Route::get('user/message_system/list', 'v1.user.MessageSystemController/message_list')->name('MessageSystemList'); //站内信列表
        Route::get('user/message_system/detail/:id', 'v1.user.MessageSystemController/detail')->name('MessageSystemDetail'); //详情
    })->middleware(StationOpenMiddleware::class)->middleware(AuthTokenMiddleware::class, true);

    //无需授权接口
    Route::group(function () {
        Route::get('city', 'v2.PublicController/city')->name('city');//增加省市区

        Route::get('site_config', 'v1.PublicController/getSiteConfig')->name('getSiteConfig');//获取网站配置
        Route::get('diy/get_diy/[:id]', 'v1.PublicController/getDiy');//DIY接口
        Route::get('navigation/[:template_name]', 'v1.PublicController/getNavigation')->name('getNavigation');//获取底部导航
        Route::get('search/keyword', 'v1.PublicController/search')->name('searchKeyword');//热门搜索关键字获取

        Route::get('category', 'v1.store.CategoryController/category')->name('category');//商品分类类
        Route::get('reply/list/:id', 'v1.store.StoreProductController/reply_list')->name('replyList');//商品评价列表
        Route::get('reply/config/:id', 'v1.store.StoreProductController/reply_config')->name('replyConfig');//商品评价数量和好评度


        Route::get('user_agreement/[:type]', 'v1.PublicController/getUserAgreement')
            ->name('getUserAgreement')
            ->middleware(AuthTokenMiddleware::class, false);//获取用户协议

        Route::get('get_open_adv', 'v1.PublicController/getOpenAdv')->name('getOpenAdv');//首页开屏广告

    })->middleware(StationOpenMiddleware::class);


    //授权不通过,不会抛出异常继续执行
    Route::group(function () {
        //公共类
        Route::get('index', 'v1.PublicController/index')->name('index');//首页
        Route::get('menu/user', 'v1.PublicController/menu_user')->name('menuUser');//个人中心菜单

        //商品类
        Route::get('brand', 'v1.store.StoreProductController/brand')->name('brand');//品牌列表
        Route::get('presale/list', 'v1.store.StoreProductController/presaleList')->name('presaleList');//预售商品列表
        Route::post('image_base64', 'v1.PublicController/get_image_base64')->name('getImageBase64');// 获取图片base64
        Route::get('product/detail/:id/[:type]', 'v1.store.StoreProductController/detail')->name('detail');//商品详情
        Route::get('groom/list/:type', 'v1.store.StoreProductController/groom_list')->name('groomList');//获取首页推荐不同类型商品的轮播图和商品
        Route::get('products', 'v1.store.StoreProductController/lst')->name('products');//商品列表
        Route::get('product/hot', 'v1.store.StoreProductController/product_hot')->name('productHot');//为你推荐
        Route::get('reply/comment/:id', 'v1.store.StoreProductController/commentList')->name('commentList');//评价回复列表

        //文章分类类
        Route::get('article/category/list', 'v1.publics.ArticleCategoryController/lst')->name('articleCategoryList');//文章分类列表
        //文章类
        Route::get('article/list/:cid', 'v1.publics.ArticleController/lst')->name('articleList');//文章列表
        Route::get('article/details/:id', 'v1.publics.ArticleController/details')->name('articleDetails');//文章详情
        Route::get('article/hot/list', 'v1.publics.ArticleController/hot')->name('articleHotList');//文章 热门
        Route::get('article/new/list', 'v1.publics.ArticleController/new')->name('articleNewList');//文章 最新
        Route::get('article/banner/list', 'v1.publics.ArticleController/banner')->name('articleBannerList');//文章 banner
        //活动---秒杀
        Route::get('seckill/index', 'v1.activity.StoreSeckillController/index')->name('seckillIndex');//秒杀商品时间区间
        Route::get('seckill/list/:time', 'v1.activity.StoreSeckillController/lst')->name('seckillList');//秒杀商品列表
        Route::get('seckill/detail/:id/[:time]', 'v1.activity.StoreSeckillController/detail')->name('seckillDetail');//秒杀商品详情
        //活动---砍价
        Route::get('bargain/config', 'v1.activity.StoreBargainController/config')->name('bargainConfig');//砍价商品列表配置
        Route::get('bargain/list', 'v1.activity.StoreBargainController/lst')->name('bargainList');//砍价商品列表
        //活动---拼团
        Route::get('combination/list', 'v1.activity.StoreCombinationController/lst')->name('combinationList');//拼团商品列表
        Route::get('combination/detail/:id', 'v1.activity.StoreCombinationController/detail')->name('combinationDetail');//拼团商品详情
        //用户类
        Route::get('user/activity', 'v1.user.UserController/activity')->name('userActivity');//活动状态

        //微信
        Route::get('wechat/config', 'v1.wechat.WechatController/config')->name('wechatConfig');//微信 sdk 配置
        Route::get('wechat/auth', 'v1.wechat.WechatController/auth')->name('wechatAuth');//微信授权
        Route::post('wechat/app_auth', 'v1.wechat.WechatController/appAuth')->name('appAuth');//微信APP授权

        //小程序登陆
        Route::post('wechat/mp_auth', 'v1.wechat.AuthController/mp_auth')->name('mpAuth');//小程序登陆
        Route::get('wechat/get_logo', 'v1.wechat.AuthController/get_logo')->name('getLogo');//小程序登陆授权展示logo
        Route::get('wechat/teml_ids', 'v1.wechat.AuthController/teml_ids')->name('wechatTemlIds');//小程序订阅消息
        Route::get('wechat/live', 'v1.wechat.AuthController/live')->name('wechatLive');//小程序直播列表
        Route::get('wechat/livePlaybacks/:id', 'v1.wechat.AuthController/livePlaybacks')->name('livePlaybacks');//小程序直播回放

        //物流公司
        Route::get('logistics', 'v1.PublicController/logistics')->name('logistics');//物流公司列表

        //分享配置
        Route::get('share', 'v1.PublicController/share')->name('share');//分享配置

        //优惠券
        Route::get('coupons', 'v1.store.StoreCouponsController/lst')->name('couponsList'); //可领取优惠券列表

        //短信购买异步通知
        Route::post('sms/pay/notify', 'v1.PublicController/sms_pay_notify')->name('smsPayNotify'); //短信购买异步通知

        //获取关注微信公众号海报
        Route::get('wechat/follow', 'v1.wechat.WechatController/follow')->name('Follow');
        //用户是否关注
        Route::get('subscribe', 'v1.user.UserController/subscribe')->name('Subscribe');
        //门店列表
        Route::get('store_list', 'v1.PublicController/store_list')->name('storeList');
        //获取城市列表
        Route::get('city_list', 'v1.PublicController/city_list')->name('cityList');

        Route::get('pink', 'v1.PublicController/pink')->name('pinkData');
        Route::get('combination/banner_list', 'v1.activity.StoreCombinationController/banner_list')->name('combinationBannerList');//拼团列表轮播图

        Route::post('user/set_visit', 'v1.user.UserController/set_visit')->name('setVisit');// 添加用户访问记录
        Route::get('copy_words', 'v1.PublicController/copy_words')->name('copyWords');// 复制口令接口

        //活动---积分商城
        Route::get('store_integral/index', 'v1.activity.StoreIntegralController/index')->name('storeIntegralIndex');//积分商城首页数据
        Route::get('store_integral/list', 'v1.activity.StoreIntegralController/lst')->name('storeIntegralList');//积分商品列表
        Route::get('store_integral/detail/:id', 'v1.activity.StoreIntegralController/detail')->name('storeIntegralDetail');//积分商品详情

        //优惠套餐列表
        Route::get('store_discounts/list/:product_id', 'v1.activity.StoreDiscountsController/index');

        //获取客服类型
        Route::get('get_customer_type', 'v2.PublicController/getCustomerType')->name('getCustomerType');//获取客服类型
        Route::get('user/service/get_adv', 'v1.user.StoreService/getKfAdv')->name('userServiceGetKfAdv');//获取客服页面广告

    })->middleware(StationOpenMiddleware::class)->middleware(AuthTokenMiddleware::class, false);

    /**
     * v1.1 版本路由
     */
    Route::group('v2', function () {
        //无需授权接口
        Route::group(function () {
            //公众号授权登录
            Route::get('wechat/auth', 'v2.wechat.WechatController/auth');
            //小程序授权
            Route::get('wechat/routine_auth', 'v2.wechat.AuthController/auth');
            //小程序静默授权
            Route::get('wechat/silence_auth', 'v2.wechat.AuthController/silenceAuthNoLogin');
            //小程序静默授权登陆
            Route::get('wechat/silence_auth_login', 'v2.wechat.AuthController/silenceAuth');
            //公众号静默授权
            Route::get('wechat/wx_silence_auth', 'v2.wechat.WechatController/silenceAuthNoLogin');
            //公众号静默授权登陆
            Route::get('wechat/wx_silence_auth_login', 'v2.wechat.WechatController/silenceAuth');
            //DIY接口
            Route::get('diy/get_diy/[:name]', 'v2.PublicController/getDiy');
            //是否强制绑定手机号
            Route::get('bind_status', 'v2.PublicController/bindPhoneStatus');
            //小程序授权绑定手机号
            Route::post('auth_bindind_phone', 'v2.wechat.AuthController/authBindingPhone');
            //小程序手机号登录直接绑定
            Route::post('phone_silence_auth', 'v2.wechat.AuthController/silenceAuthBindingPhone');
            //微信手机号登录直接绑定
            Route::post('phone_wx_silence_auth', 'v2.wechat.WechatController/silenceAuthBindingPhone');
            //获取门店自提开启状态
            Route::get('diy/get_store_status', 'v2.PublicController/getStoreStatus');
            //一键换色
            Route::get('diy/color_change/:name', 'v2.PublicController/colorChange');
            //获取地址列表
            Route::get('cityList', 'v2.PublicController/cityList');
            //活动优惠活动商品列表
            Route::get('promotions/productList/:type', 'v2.activity.StorePromotions/productList');
			//优惠活动赠品信息
            Route::get('promotions/give_info/:id', 'v2.activity.StorePromotions/getPromotionsGive');
        });
        //需要授权
        Route::group(function () {
            Route::post('reset_cart', 'v2.store.StoreCartController/resetCart')->name('resetCart');
            Route::get('new_coupon', 'v2.store.StoreCouponsController/getNewCoupon')->name('getNewCoupon');//获取新人券
            Route::post('user/user_update', 'v2.wechat.AuthController/updateInfo');
            Route::post('order/product_coupon/:orderId', 'v2.store.StoreCouponsController/getOrderProductCoupon');//获取订单商品关联优惠券
            Route::get('user/service/record', 'v2.user.StoreService/record')->name('userServiceRecord');//客服聊天记录
            Route::get('cart_list', 'v2.store.StoreCartController/getCartList');
            Route::get('get_attr/:id/:type', 'v2.store.StoreProductController/getProductAttr');
            Route::post('set_cart_num', 'v2.store.StoreCartController/setCartNum');
            //订单申请发票
            Route::post('order/make_up_invoice', 'v2.order.StoreOrderInvoiceController/makeUp')->name('orderMakeUpInvoice');
            //用户发票列表
            Route::get('invoice', 'v2.user.UserInvoiceController/invoiceList')->name('userInvoiceLIst');
            //单个发票详情
            Route::get('invoice/detail/:id', 'v2.user.UserInvoiceController/invoice')->name('userInvoiceDetail');
            //修改|添加发票
            Route::post('invoice/save', 'v2.user.UserInvoiceController/saveInvoice')->name('userInvoiceSave');
            //设置默认发票
            Route::post('invoice/set_default/:id', 'v2.user.UserInvoiceController/setDefaultInvoice')->name('userInvoiceSetDefault');
            //获取默认发票
            Route::get('invoice/get_default/:type', 'v2.user.UserInvoiceController/getDefaultInvoice')->name('userInvoiceGetDefault');
            //删除发票
            Route::get('invoice/del/:id', 'v2.user.UserInvoiceController/delInvoice')->name('userInvoiceDel');
            //订单申请开票记录
            Route::get('order/invoice_list', 'v2.order.StoreOrderInvoiceController/list')->name('orderInvoiceList');
            //订单开票详情
            Route::get('order/invoice_detail/:uni', 'v2.order.StoreOrderInvoiceController/detail')->name('orderInvoiceList');

            //清除搜索记录
            Route::get('user/clean_search', 'v2.user.UserSearchController/cleanUserSearch')->mame('cleanUserSearch');
            //更新公众号用户信息
            Route::get('user/wechat', 'v2.user.UserController/updateUserInfo')->name('updateUserInfo');
           
            //抽奖活动详情
            Route::get('lottery/info/[:factor]', 'v2.activity.LuckLotteryController/lotteryInfo')->mame('lotteryInfo');
            //参与抽奖
            Route::post('lottery', 'v2.activity.LuckLotteryController/luckLottery')->mame('luckLottery');
            //领取奖品
            Route::post('lottery/receive', 'v2.activity.LuckLotteryController/lotteryReceive')->mame('lotteryReceive');
            //抽奖记录
            Route::get('lottery/record', 'v2.activity.LuckLotteryController/lotteryRecord')->mame('lotteryRecord');
            //获取分销等级列表
            Route::get('agent/level_list', 'v2.agent.AgentLevel/levelList')->name('agentLevelList');
            //获取分销等级任务列表
            Route::get('agent/level_task_list', 'v2.agent.AgentLevel/levelTaskList')->name('agentLevelTaskList');

            //获取用户余额、佣金、提现明细列表
            Route::get('user/money_list/:type', 'v2.user.UserController/userMoneyList')->name('userMoneyList');
            //获取用户推广用户列表
            Route::get('agent/agent_user_list/:type', 'v2.agent.AgentController/agentUserList')->name('agentUserList');
            //获取用户推广获得收益，佣金轮播，分销规则
            Route::get('agent/agent_info', 'v2.agent.AgentController/agentInfo')->name('agentInfo');
            //优惠活动凑单商品列表
            Route::get('promotions/collect_order/product', 'v2.activity.StorePromotions/collectOrderProduct');

        })->middleware(AuthTokenMiddleware::class, true);

        //授权不通过,不会抛出异常继续执行
        Route::group(function () {
            //用户搜索记录
            Route::get('user/search_list', 'v2.user.UserSearchController/getUserSeachList')->mame('userSearchList');
            Route::get('get_today_coupon', 'v2.store.StoreCouponsController/getTodayCoupon');//新优惠券弹窗接口
            Route::get('subscribe', 'v2.PublicController/subscribe')->name('WechatSubscribe');// 微信公众号用户是否关注
            //公共类
            Route::get('index', 'v2.PublicController/index')->name('index');//首页
            //优惠券
            Route::get('coupons', 'v2.store.StoreCouponsController/lst')->name('couponsList'); //可领取优惠券列表
            //商品评价列表
            Route::get('reply/list/:id', 'v2.store.StoreProductController/reply_list')->name('v2replyList');//商品评价列表
        })->middleware(AuthTokenMiddleware::class, false);

    })->middleware(StationOpenMiddleware::class);

    Route::group('pc', function () {
        //登陆接口
        Route::group(function () {
            Route::get('key', 'pc.LoginController/getLoginKey')->name('getLoginKey');//获取扫码登录key
            Route::get('scan/:key', 'pc.LoginController/scanLogin')->name('scanLogin');//检测扫码情况
            Route::get('get_appid', 'pc.LoginController/getAppid')->name('getAppid');//检测扫码情况
            Route::get('wechat_auth', 'pc.LoginController/wechatAuth')->name('wechatAuth');//检测扫码情况
        });

        //未授权接口
        Route::group(function () {
            Route::get('get_pay_vip_code', 'pc.HomeController/getPayVipCode')->name('getPayVipCode');//获取付费会员购买页面二维码
            Route::get('get_product_phone_buy', 'pc.HomeController/getProductPhoneBuy')->name('getProductPhoneBuy');//手机购买跳转url配置
            Route::get('get_banner', 'pc.HomeController/getBanner')->name('getBanner');//PC首页轮播图
            Route::get('get_category_product', 'pc.HomeController/getCategoryProduct')->name('getCategoryProduct');//首页分类尚品
            Route::get('get_products', 'pc.ProductController/getProductList')->name('getProductList');//商品列表
            Route::get('get_product_code/:product_id', 'pc.ProductController/getProductRoutineCode')->name('getProductRoutineCode');//商品详情小程序二维码
            Route::get('get_city/:pid', 'pc.PublicController/getCity')->name('getCity');//获取城市数据
            Route::get('check_order_status/:order_id/:end_time', 'pc.OrderController/checkOrderStatus')->name('checkOrderStatus');//轮询订单状态接口
            Route::get('get_company_info', 'pc.PublicController/getCompanyInfo')->name('getCompanyInfo');//获取公司信息
            Route::get('get_recommend/:type', 'pc.ProductController/getRecommendList')->name('getRecommendList');//获取推荐商品
            Route::get('get_wechat_qrcode', 'pc.PublicController/getWechatQrcode')->name('getWechatQrcode');//获取关注二维码
            Route::get('get_good_product', 'pc.ProductController/getGoodProduct')->name('getGoodProduct');//获取优品推荐
        })->middleware(AuthTokenMiddleware::class, false);

        //会员授权接口
        Route::group(function () {
            Route::get('get_cart_list', 'pc.CartController/getCartList')->name('getCartList');//购物车列表
            Route::get('get_balance_record/:type', 'pc.UserController/getBalanceRecord')->name('getBalanceRecord');//余额记录
            Route::get('get_order_list', 'pc.OrderController/getOrderList')->name('getOrderList');//订单列表
            Route::get('get_collect_list', 'pc.UserController/getCollectList')->name('getCollectList');//收藏列表
            Route::post('order/refund/cart_info', 'pc.OrderController/refundCartInfoList')->name('StoreOrderRefundCartInfoList');//获取退款商品列表
            Route::get('order/refund/list', 'pc.OrderController/refundList')->name('orderRefundList'); //售后订单列表
        })->middleware(AuthTokenMiddleware::class, true);

    });


    //门店订单操作类
    Route::group('store', function () {
        Route::get('staff/info', 'store.StoreStaffController/info')->name('storeStaffInfo');//门店店员信息
        Route::get('staff/statistics', 'store.StoreStaffController/statistics')->name('storeStatistics');//门店|店员统计信息
        Route::get('staff/data/:type', 'store.StoreStaffController/data');//详细信息列表
        Route::get('order/statistics', 'store.StoreOrderController/statistics')->name('adminOrderStatistics');//订单数据统计
        Route::get('order/data', 'store.StoreOrderController/data')->name('adminOrderData');//订单每月统计数据
        Route::get('staff/card/code', 'store.StoreStaffController/code');//详细信息列表

        Route::get('delivery/info', 'store.StoreDeliveryController/info')->name('storeStaffInfo');//配送员信息
        Route::get('delivery/statistics', 'store.StoreDeliveryController/statistics')->name('deliveryStatistics');//门店配送员统计信息
        Route::get('delivery/data', 'store.StoreDeliveryController/data')->name('deliveryData');//每月配送统计列表数据
        Route::get('delivery/order', 'store.StoreDeliveryController/orderList')->name('deliveryOrder');//配送员订单列表
        Route::get('delivery/list', 'store.StoreDeliveryController/getDeliveryAll')->name('getDeliveryAll');//获取配送员列表

        Route::get('order/list', 'store.StoreOrderController/lst')->name('storeOrderList');//订单列表
        Route::get('refund/list', 'store.StoreOrderController/refundList')->name('refundList');//订单列表
        Route::get('refund/detail/:id', 'store.StoreOrderController/refundDetail')->name('refundDetail');//订单列表
        Route::get('order/detail/:id', 'store.StoreOrderController/detail')->name('storeOrderDetail');//订单详情
        Route::get('order/delivery_info/:orderId', 'store.StoreOrderController/deliveryInfo')->name('storeOrderDeliveryInfo');//订单发货获取订单信息
        Route::post('order/delivery/:id', 'store.StoreOrderController/delivery')->name('storeOrderDelivery');//订单发货
        Route::get('order/split_cart_info/:id', 'store.StoreOrderController/split_cart_info')->name('StoreOrderSplitCartInfo');//获取订单可拆分商品列表
        Route::put('order/split_delivery/:id', 'store.StoreOrderController/split_delivery')->name('StoreOrderSplitDelivery');//拆单发送货
        Route::post('order/price', 'store.StoreOrderController/price')->name('storeOrderPrice');//订单改价
        Route::post('order/remark', 'store.StoreOrderController/remark')->name('storeOrderRemark');//订单备注
        Route::post('refund/remark', 'store.StoreOrderController/refundRemark')->name('refundRemark');//订单备注
        Route::post('order/offline', 'store.StoreOrderController/offline')->name('storeOrderOffline');//订单支付
        Route::post('order/refund', 'store.StoreOrderController/refund')->name('storeOrderRefund');//订单退款
        Route::post('order/refund_agree/:id', 'store.StoreOrderController/agreeRefund')->name('storeOrderAgreeRefund');//商家同意退货退款
        Route::get('order/export_temp', 'store.StoreOrderController/getExportTemp')->name('storeOrderExportTemp');//获取电子面单模板获取
        Route::get('order/export_all', 'store.StoreOrderController/getExportAll')->name('storeOrderExportAll');//获取物流公司
        Route::post('order/cancel/:id', 'store.StoreOrderController/cancel')->name('storeOrderCancel'); //订单取消
        Route::delete('order/del/:id', 'store.StoreOrderController/del')->name('storeOrderDel');//订单删除

        Route::get('order/writeoff_info/:type', 'store.StoreOrderController/writeoffOrderinfo')->name('writeoffOrderinfo');//扫码核销获取订单信息
        Route::get('order/cart_info/:type', 'store.StoreOrderController/orderCartInfo')->name('writeoffOrderCartInfo');//核销获取商品信息
        Route::post('order/writeoff/:type', 'store.StoreOrderController/wirteoff')->name('storeOrderWriteoff');//订单核销

    })->middleware(StationOpenMiddleware::class)->middleware(AuthTokenMiddleware::class, true);


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

})->prefix('api.')->middleware(InstallMiddleware::class)->middleware(AllowOriginMiddleware::class)->middleware(StationOpenMiddleware::class);

