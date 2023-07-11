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

namespace app\controller\store\order;


use app\controller\store\AuthController;
use app\Request;
use app\services\order\cashier\CashierOrderServices;
use app\services\order\cashier\StoreHangOrderServices;
use app\services\order\StoreCartServices;
use app\services\other\QrcodeServices;
use app\services\pay\PayServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\store\SystemStoreStaffServices;
use app\services\user\UserServices;
use app\services\product\category\StoreCategoryServices;
use crmeb\services\AliPayService;
use crmeb\services\wechat\Payment;
use crmeb\utils\Canvas;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\ValidateException;
use think\facade\App;

/**
 * 收银台
 * Class Cashier
 * @package app\controller\store\order
 * @property Request $request
 */
class Cashier extends AuthController
{

    /**
     * @var CashierOrderServices
     */
    protected $service;

    /**
     * Cashier constructor.
     * @param App $app
     * @param CashierOrderServices $service
     */
    public function __construct(App $app, CashierOrderServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 获取用户信息
     * @param UserServices $services
     * @return mixed
     */
    public function getUserInfo(UserServices $services, StoreCartServices $cartServices)
    {
        $code = $this->request->post('code', '');
        $uid = $this->request->post('uid', '');
        if (!$code && !$uid) {
            return $this->fail('缺少参数');
        }

        $field = ['uid', 'avatar', 'phone', 'nickname', 'now_money', 'integral'];
        if ($uid) {
            $userInfo = $services->getUserInfo($uid, $field);
        } elseif ($code) {
            $userInfo = $services->get(['uniqid' => $code], $field);
        }

        if (!isset($userInfo) && !$userInfo) {
            return $this->fail('用户不存在');
        }

        $cart = $this->request->post('cart', []);
        if ($cart) {
            $cartServices->batchAddCart($cart, $this->storeId, $userInfo->uid);
        }

        return $this->success($userInfo->toArray());
    }

    /**
     * 获取一级分类
     * @param StoreCategoryServices $services
     * @return mixed
     * @throws DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws ModelNotFoundException
     */
    public function getCateGoryList(StoreCategoryServices $services)
    {
        return $this->success($services->getOneCategory());
    }

    /**
     * 获取商品列表
     * @param Request $request
     * @param StoreBranchProductServices $services
     * @return mixed
     */
    public function getProductList(Request $request, StoreBranchProductServices $services)
    {
        $where = $request->getMore([
            ['store_name', ''],
            ['cate_id', 0],
            ['field_key', ''],
            ['staff_id', '']
        ]);
        $where['store_id'] = $this->storeId;
        $where['field_key'] = $where['field_key'] == 'all' ? '' : $where['field_key'];
        $where['field_key'] = $where['field_key'] == 'id' ? 'product_id' : $where['field_key'];
        $staff_id = (int)$where['staff_id'];
        unset($where['staff_id']);
        $where['is_del'] = 0;
        $where['is_show'] = 1;
        $uid = $this->request->get('uid', 0);
        return $this->success($services->getCashierProductListV1($where, (int)$uid, 0));
    }

    /**
     * 获取商品详情
     * @param StoreBranchProductServices $services
     * @param $id
     * @return mixed
     */
    public function getProductDetail(StoreBranchProductServices $services, $id, $uid = 0)
    {
        if (!$id) {
            return $this->fail('缺少商品id');
        }
        $touristUid = $this->request->get('tourist_uid');
        return $this->success($services->getProductDetail($this->storeId, (int)$id, (int)$uid, (int)$touristUid));
    }

    /**
     * 购物车列表
     * @param StoreCartServices $services
     * @param $uid
     * @param $staff_id
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function getCartList(StoreCartServices $services, $uid, $staff_id)
    {
        if (!$staff_id) {
            return $this->fail('缺少参数');
        }
        $cartIds = $this->request->get('cart_ids', '');
        $touristUid = $this->request->get('tourist_uid', '');
        $cartIds = $cartIds ? explode(',', $cartIds) : [];
        if (!$touristUid && !$uid) {
            return $this->fail('缺少用户信息');
        }
        return $this->success($services->getUserCartList((int)$uid, -1, $cartIds, $this->storeId, $staff_id, 4, (int)$touristUid));
    }

    /**
     * 获取店员信息
     * @param SystemStoreStaffServices $services
     * @return mixed
     */
    public function getStaffList(SystemStoreStaffServices $services)
    {
        $where = [];
        $where['store_id'] = $this->storeId;
        $where['keyword'] = $this->request->get('keyword', '');
        $where['is_del'] = 0;
        return $this->success([
            'staffInfo' => $this->request->storeStaffInfo(),
            'staffList' => $services->getStoreStaff($where),
            'count' => $services->getStoreSearch($where)->count()
        ]);
    }

    /**
     * 解析条形码值
     * @return mixed
     */
    public function getAnalysisCode()
    {
        $code = $this->request->post('bar_code', '');
        $uid = $this->request->post('uid', 0);
        $staff_id = $this->request->post('staff_id', 0);
        $touristUid = $this->request->post('tourist_uid', '');
        if (!$touristUid && !$uid) {
            return $this->fail('缺少用户信息');
        }

        return $this->success($this->service->getAnalysisCode($code, $this->storeId, (int)$uid, (int)$staff_id, (int)$touristUid));
    }

    /**
     * 游客切换到用户
     * @param StoreCartServices $services
     * @param $staffId
     * @return mixed
     */
    public function switchCartUser(StoreCartServices $services, $staffId)
    {
        [$uid, $toUid, $isTourist] = $this->request->postMore([
            ['uid', 0],
            ['to_uid', 0],
            ['is_tourist', 0]
        ], true);
        if ($isTourist) {
            $where = ['tourist_uid' => $uid, 'store_id' => $this->storeId, 'staff_id' => $staffId];
            $touristCart = $services->getCartList($where);
            if ($touristCart) {
                $userWhere = ['uid' => $toUid, 'store_id' => $this->storeId, 'staff_id' => $staffId];
                $userCarts = $services->getCartList($userWhere);
                if ($userCarts) {
                    foreach ($touristCart as $cart) {
                        foreach ($userCarts as $userCart) {
                            //游客商品 存在用户购物车商品中  
                            if($cart['product_id'] == $userCart['product_id'] && $cart['product_attr_unique'] == $userCart['product_attr_unique']) {
                                //修改用户商品数量 删除游客购物车这条数据
                                $services->update(['id' => $userCart['id']], ['cart_num' => bcadd((string)$cart['cart_num'], (string)$userCart['cart_num'])]);
                                $services->delete(['id' => $cart['id']]);
                            }
                        }
                    }
                }
            }
            $services->update($where, ['uid' => $toUid, 'tourist_uid' => '']);
        }
        return $this->success('修改成功');
    }

    /**
     * 加入购物车
     * @param StoreCartServices $services
     * @param $uid
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws ModelNotFoundException
     */
    public function addCart(StoreCartServices $services, $uid)
    {
        $where = $this->request->postMore([
            ['productId', 0],//普通商品编号
            [['cartNum', 'd'], 1], //购物车数量
            ['uniqueId', ''],//属性唯一值
            ['staff_id', ''],//店员ID
            ['tourist_uid', '']//虚拟用户uid
        ]);

        if (!$where['productId']) {
            return $this->fail('参数错误');
        }

        //真实用户存在，虚拟用户uid为空
        if ($uid) {
            $where['tourist_uid'] = '';
        }

        if (!$uid && !$where['tourist_uid']) {
            return $this->fail('缺少用户UID');
        }

        $services->setItem('store_id', $this->storeId)
            ->setItem('tourist_uid', $where['tourist_uid']);
        $res = $services->addCashierCart((int)$uid, (int)$where['productId'], (int)$where['cartNum'], $where['uniqueId'], (int)$where['staff_id']);
        $services->reset();
        return $this->success(['cartId' => $res]);
    }

    /**
     * 删除购物车
     * @param StoreCartServices $services
     * @param $uid
     * @return mixed
     */
    public function delCart(StoreCartServices $services, $uid)
    {
        $where = $this->request->postMore([
            ['ids', []],//购物车编号
        ]);
        if (!count($where['ids'])) {
            return $this->fail('参数错误!');
        }

        if ($services->removeUserCart((int)$uid, $where['ids'])) {
            return $this->success();
        } else {
            return $this->fail('清除失败！');
        }
    }

    /**
     * 购物车 修改商品数量
     * @param Request $request
     * @return mixed
     * @throws Exception
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function numCart(StoreCartServices $services, $uid)
    {
        $where = $this->request->postMore([
            ['id', 0],//购物车编号
            ['number', 0],//购物数量
        ]);
        if (!$where['id'] || !$where['number'] || !is_numeric($where['id']) || !is_numeric($where['number'])) {
            return $this->fail('参数错误!');
        }

        if ($services->changeCashierCartNum((int)$where['id'], (int)$where['number'], $uid, $this->storeId)) {
            return $this->success();
        } else {
            return $this->fail('修改失败');
        }
    }

    /**
     * 购物车重选
     * @param Request $request
     * @return mixed
     */
    public function changeCart(StoreCartServices $services)
    {
        [$cart_id, $product_id, $unique] = $this->request->postMore([
            ['cart_id', 0],
            ['product_id', 0],
            ['unique', '']
        ], true);
        $services->modifyCashierCart($this->storeId, (int)$cart_id, (int)$product_id, $unique);
        return $this->success('重选成功');
    }

    /**
     * 获取用户优惠券列表
     * @param CashierOrderServices $services
     * @param $uid
     * @return mixed
     */
    public function couponList(CashierOrderServices $services, $uid)
    {
        [$cartIds] = $this->request->postMore([
            ['cart_id', []],
        ], true);
        if (!$uid) return $this->success([]);
        return $this->success($services->getCouponList((int)$uid, $this->storeId, $cartIds));
    }

    /**
     * 添加挂单数据
     * @return mixed
     */
    public function saveHangOrder()
    {
        return $this->success('挂单成功');
    }

    /**
     * 获取挂单列表和用户购买历史列表 挂单规定10个，历史记录规定20个没有分页
     * @param StoreHangOrderServices $services
     * @param int $staffId
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function getHangOrder(StoreHangOrderServices $services, $staffId = 0)
    {
        return $this->success($services->getHangOrder((int)$this->storeId, $staffId ?: (int)$this->storeStaffId));
    }

    /**
     * 获取挂单列表分页
     * @param StoreHangOrderServices $services
     * @param int $staffId
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function getHangOrderList(StoreHangOrderServices $services, $staffId = 0)
    {
        $search = $this->request->get('keyword', '');
        return $this->success($services->getHangOrderList((int)$this->storeId, 0, $search));
    }

    /**
     * 删除购物车信息
     * @param StoreCartServices $services
     * @return mixed
     */
    public function deleteHangOrder(StoreCartServices $services)
    {
        $id = $this->request->get('id');
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $id = explode(',', $id) ?: [];
        if ($services->search(['id' => $id])->delete()) {
            return $this->success('删除成功');
        } else {
            return $this->fail('删除失败');
        }
    }

    /**
     * 计算门店下的购物车内的金额
     * @param CashierOrderServices $services
     * @param $uid
     * @return mixed
     * @throws DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws ModelNotFoundException
     */
    public function computeOrder(CashierOrderServices $services, $uid)
    {
        [$integral, $coupon, $cartIds, $coupon_id] = $this->request->postMore([
            ['integral', 0],
            ['coupon', 0],
            ['cart_id', []],
            ['coupon_id', 0]
        ], true);

        if (!$cartIds) {
            return $this->fail('缺少购物车ID');
        }

        return $this->success($services->computeOrder((int)$uid, $this->storeId, $cartIds, !!$integral, !!$coupon, [], $coupon_id));
    }

    /**
     * 生成订单
     * @param CashierOrderServices $services
     * @param $uid
     * @return mixed
     */
    public function createOrder(CashierOrderServices $services, $uid)
    {
        [$integral, $coupon, $cartIds, $payType, $remarks, $staffId, $changePrice, $isPrice, $userCode, $coupon_id, $authCode, $touristUid] = $this->request->postMore([
            ['integral', 0],
            ['coupon', 0],
            ['cart_id', []],
            ['pay_type', ''],
            ['remarks', ''],
            ['staff_id', 0],
            ['change_price', 0],
            ['is_price', 0],
            ['userCode', ''],
            ['coupon_id', 0],
            ['auth_code', ''],
            ['tourist_uid', '']
        ], true);

        if (!$staffId) {
            $staffId = $this->request->storeStaffId();
        }

        if (!$cartIds) {
            return $this->fail('缺少购物车ID');
        }
        if (!in_array($payType, ['yue', 'cash']) && $authCode) {
            if (Payment::isWechatAuthCode($authCode)) {
                $payType = PayServices::WEIXIN_PAY;
            } else if (AliPayService::isAliPayAuthCode($authCode)) {
                $payType = PayServices::ALIAPY_PAY;
            } else {
                return $this->fail('未知,付款二维码');
            }
        }

        $userInfo = [];
        if ($uid) {
            /** @var UserServices $userService */
            $userService = app()->make(UserServices::class);
            $userInfo = $userService->getUserInfo($uid);
            if (!$userInfo) {
                return $this->fail('用户不存在');
            }
            $userInfo = $userInfo->toArray();
        }

        $computeData = $services->computeOrder($uid, $this->storeId, $cartIds, $integral, $coupon, $userInfo, $coupon_id);
        $cartInfo = $computeData['cartInfo'];

        return $services->transaction(function () use ($services, $userInfo, $computeData, $authCode, $uid, $staffId, $cartIds, $payType, $integral, $coupon, $remarks, $changePrice, $isPrice, $userCode, $coupon_id) {
            $orderInfo = $services->createOrder((int)$uid, $userInfo, $computeData, $this->storeId, (int)$staffId, $cartIds, $payType, !!$integral, !!$coupon, $remarks, $changePrice, !!$isPrice, $coupon_id);
            if (in_array($payType, [PayServices::YUE_PAY, PayServices::CASH_PAY, PayServices::ALIAPY_PAY, PayServices::WEIXIN_PAY])) {
                $res = $services->paySuccess($orderInfo['order_id'], $payType, $userCode, $authCode);
                $res['order_id'] = $orderInfo['order_id'];
                return app('json')->success($res);
            } else {
                return app('json')->success(['status' => 'ORDER_CREATE', 'order_id' => $orderInfo['order_id']]);
            }
        });
    }

    /**
     * 订单支付
     * @param CashierOrderServices $services
     * @param $orderId
     * @return mixed
     */
    public function payOrder(CashierOrderServices $services, $orderId)
    {
        if (!$orderId) {
            return $this->fail('缺少订单号');
        }
        $payType = $this->request->post('payType', 'yue');

        $userCode = $this->request->post('userCode', '');
        $authCode = $this->request->post('auth_code', '');
        if ($payType == PayServices::YUE_PAY && !$userCode) {
            return $this->fail('缺少用户余额支付CODE');
        }
        if (!in_array($payType, ['yue', 'cash']) && $authCode) {
            if (Payment::isWechatAuthCode($authCode)) {
                $payType = PayServices::WEIXIN_PAY;
            } else if (AliPayService::isAliPayAuthCode($authCode)) {
                $payType = PayServices::ALIAPY_PAY;
            } else {
                return $this->fail('未知,付款二维码');
            }
        }
        $res = $services->paySuccess($orderId, $payType, $userCode, $authCode);
        $res['order_id'] = $orderId;
        return $this->success($res);
    }

    /** 获取二维码
     * @return mixed
     */
    public function cashier_scan()
    {
        $store_id = $this->storeId;
        //生成h5地址
        $weixinPage = "/pages/users/order_pay/index?store_id=" . $store_id;
        $weixinFileName = "wechat_cashier_pay_" . $store_id . ".png";
        /** @var QrcodeServices $QrcodeService */
        $QrcodeService = app()->make(QrcodeServices::class);
        $wechatQrcode = $QrcodeService->getWechatQrcodePath($weixinFileName, $weixinPage, false, false);
        //生成小程序地址
        $routineQrcode = $QrcodeService->getRoutineQrcodePath($store_id, 0, 7, [], false);
        $qrcod = ['wechat' => $wechatQrcode, 'routine' => $routineQrcode];
        //生成画布
        $canvas = Canvas::instance();
        $path = 'uploads/offline/';
        $imageType = 'jpg';
        $siteUrl = sys_config('site_url');
        $canvas->setImageUrl(public_path() . 'statics/qrcode/offlines.jpg')->setImageHeight(730)->setImageWidth(500)->pushImageValue();
        foreach ($qrcod as $k => $v) {
            if ($v) {
                $name = 'offline_' . $k;
                $canvas->setImageUrl($v)->setImageHeight(344)->setImageWidth(344)->setImageLeft(76)->setImageTop(120)->pushImageValue();
                $image = $canvas->setFileName($name)->setImageType($imageType)->setPath($path)->setBackgroundWidth(500)->setBackgroundHeight(720)->starDrawChart();
                $data[$k] = $image ? $siteUrl . '/' . $image : '';
            } else {
                $data[$k] = "";
            }

        }
        return $this->success($data);
    }
}
