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
namespace app\controller\api\v1;


use app\services\activity\combination\StorePinkServices;
use app\services\diy\DiyServices;
use app\services\message\service\StoreServiceServices;
use app\services\store\DeliveryServiceServices;
use app\services\other\CacheServices;
use app\services\product\category\StoreCategoryServices;
use app\services\product\product\StoreProductServices;
use app\services\other\ExpressServices;
use app\services\other\SystemCityServices;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\system\config\SystemConfigServices;
use app\services\store\SystemStoreServices;
use app\services\store\SystemStoreStaffServices;
use app\services\user\UserBillServices;
use app\services\user\UserInvoiceServices;
use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use app\webscoket\SocketPush;
use Joypack\Tencent\Map\Bundle\Location;
use Joypack\Tencent\Map\Bundle\LocationOption;
use app\Request;
use crmeb\services\CacheService;
use crmeb\services\UploadService;
use crmeb\basic\BaseController;

/**
 * 公共类
 * Class PublicController
 * @package app\api\controller
 */
class PublicController extends BaseController
{
    /**
     * 主页获取
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $banner = sys_data('routine_home_banner') ?: [];// 首页banner图
        $menus = sys_data('routine_home_menus') ?: [];// 首页按钮
        $roll = sys_data('routine_home_roll_news') ?: [];// 首页滚动新闻
        $activity = sys_data('routine_home_activity', 3) ?: [];// 首页活动区域图片
        $explosive_money = sys_data('index_categy_images') ?: [];// 首页超值爆款
        $site_name = sys_config('site_name');
        $routine_index_page = sys_data('routine_index_page');
        $info['fastInfo'] = $routine_index_page[0]['fast_info'] ?? '';// 快速选择简介
        $info['bastInfo'] = $routine_index_page[0]['bast_info'] ?? '';// 精品推荐简介
        $info['firstInfo'] = $routine_index_page[0]['first_info'] ?? '';// 首发新品简介
        $info['salesInfo'] = $routine_index_page[0]['sales_info'] ?? '';// 促销单品简介
        $logoUrl = sys_config('routine_index_logo');// 促销单品简介
        if (strstr($logoUrl, 'http') === false && $logoUrl) {
            $logoUrl = sys_config('site_url') . $logoUrl;
        }
        $logoUrl = str_replace('\\', '/', $logoUrl);
        $fastNumber = (int)sys_config('fast_number', 0);// 快速选择分类个数
        $bastNumber = (int)sys_config('bast_number', 0);// 精品推荐个数
        $firstNumber = (int)sys_config('first_number', 0);// 首发新品个数
        $promotionNumber = (int)sys_config('promotion_number', 0);// 首发新品个数

        /** @var StoreCategoryServices $categoryService */
        $categoryService = app()->make(StoreCategoryServices::class);
        $info['fastList'] = $fastNumber ? $categoryService->byIndexList($fastNumber, 'id,cate_name,pid,pic') : [];// 快速选择分类个数
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        $info['bastList'] = $bastNumber ? $storeProductServices->getRecommendProduct($request->uid(), 'is_best', $bastNumber) : [];// 精品推荐个数
        $info['firstList'] = $firstNumber ? $storeProductServices->getRecommendProduct($request->uid(), 'is_new', $firstNumber) : [];// 首发新品个数
        $info['bastBanner'] = sys_data('routine_home_bast_banner') ?? [];// 首页精品推荐图片
        $benefit = $promotionNumber ? $storeProductServices->getRecommendProduct($request->uid(), 'is_benefit', $promotionNumber) : [];// 首页促销单品
        $lovely = sys_data('routine_home_new_banner') ?: [];// 首发新品顶部图
        $likeInfo = $storeProductServices->getRecommendProduct($request->uid(), 'is_hot', 3);// 热门榜单 猜你喜欢
        if ($request->uid()) {
            /** @var WechatUserServices $wechatUserService */
            $wechatUserService = app()->make(WechatUserServices::class);
            $subscribe = $wechatUserService->value(['uid' => $request->uid()], 'subscribe') ? true : false;
        } else {
            $subscribe = true;
        }
        $newGoodsBananr = sys_config('new_goods_bananr');
        $tengxun_map_key = sys_config('tengxun_map_key');
        return app('json')->successful(compact('banner', 'menus', 'roll', 'info', 'activity', 'lovely', 'benefit', 'likeInfo', 'logoUrl', 'site_name', 'subscribe', 'newGoodsBananr', 'tengxun_map_key', 'explosive_money'));
    }

    /**
     * 获取分享配置
     * @return mixed
     */
    public function share()
    {
        $data['img'] = sys_config('wechat_share_img');
        if (strstr($data['img'], 'http') === false) {
            $data['img'] = sys_config('site_url') . $data['img'];
        }
        $data['img'] = str_replace('\\', '/', $data['img']);
        $data['title'] = sys_config('wechat_share_title');
        $data['synopsis'] = sys_config('wechat_share_synopsis');
        return app('json')->successful($data);
    }

    /**
     * 获取网站配置
     * @return mixed
     */
    public function getSiteConfig()
    {
        $data['record_No'] = sys_config('record_No');
        return app('json')->success($data);
    }

    /**
     * 获取个人中心菜单
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function menu_user(Request $request)
    {
        $menusInfo = sys_data('routine_my_menus') ?? [];
        $uid = 0;
        $userInfo = [];
        if ($request->hasMacro('user')) $userInfo = $request->user();
        if ($request->hasMacro('uid')) $uid = $request->uid();

        $vipOpen = sys_config('member_func_status');
        $brokerageFuncStatus = sys_config('brokerage_func_status');
        $balanceFuncStatus = sys_config('balance_func_status');
        $vipCard = sys_config('member_card_status', 0);
        $svipOpen = sys_config('member_card_status') ? true : false;
        $userService = $invoiceStatus = $deliveryUser = $isUserPromoter = $userVerifyStatus = $userOrder = $isStaff = $isDelivery = true;

        if ($uid && $userInfo) {
            /** @var StoreServiceServices $storeService */
            $storeService = app()->make(StoreServiceServices::class);
            $userService = $storeService->checkoutIsService(['uid' => $uid, 'status' => 1, 'account_status' => 1]);
            $userOrder = $storeService->checkoutIsService(['uid' => $uid, 'account_status' => 1, 'customer' => 1]);
            /** @var SystemStoreStaffServices $systemStoreStaff */
            $systemStoreStaff = app()->make(SystemStoreStaffServices::class);
            /** @var UserServices $user */
            $user = app()->make(UserServices::class);
            /** @var UserInvoiceServices $userInvoice */
            $userInvoice = app()->make(UserInvoiceServices::class);
            $invoiceStatus = $userInvoice->invoiceFuncStatus(false);
            /** @var DeliveryServiceServices $deliveryService */
            $deliveryService = app()->make(DeliveryServiceServices::class);
            $deliveryUser = $deliveryService->checkoutIsService($uid);
            $isUserPromoter = $user->checkUserPromoter($uid, $userInfo);
            $userVerifyStatus = $systemStoreStaff->verifyStatus($uid);
            try {
                $isStaff = $systemStoreStaff->getStaffInfoByUid($uid);
            } catch (\Throwable $e) {
                $isStaff = false;
            }
            try {
                $isDelivery = $deliveryService->getDeliveryInfoByUid($uid);
            } catch (\Throwable $e) {
                $isDelivery = false;
            }
        }
        $auth = [];
        $auth['/pages/users/user_vip/index'] = !$vipOpen;
        $auth['/pages/users/user_spread_user/index'] = !$brokerageFuncStatus || !$isUserPromoter || $uid == 0;
        $auth['/pages/users/user_money/index'] = !$balanceFuncStatus;
        $auth['/pages/admin/order/index'] = !$userOrder || $uid == 0;
        $auth['/pages/admin/order_cancellation/index'] = (!$userVerifyStatus && !$deliveryUser) || $uid == 0;
        $auth['/pages/users/user_invoice_list/index'] = !$invoiceStatus;
        $auth['/pages/annex/vip_paid/index'] = !$vipCard || !$svipOpen;
        $auth['/kefu/mobile_list'] = !$userService || $uid == 0;
        $auth['/pages/admin/store/index'] = $uid == 0 || !$isStaff;
        $auth['/pages/admin/distribution/index'] = $uid == 0 || !$isDelivery;
        $auth['/pages/store_spread/index'] = $uid == 0 || !$isStaff;
        foreach ($menusInfo as $key => &$value) {
            $value['pic'] = set_file_url($value['pic'] ?? '');
            $value['url'] = $value['url'] ?? '';
            if (isset($auth[$value['url']]) && $auth[$value['url']]) {
                unset($menusInfo[$key]);
                continue;
            }
            if ($value['url'] == '/kefu/mobile_list') {
                $value['url'] = sys_config('site_url') . $value['url'];
                if ($request->isRoutine()) {
                    $value['url'] = str_replace('http://', 'https://', $value['url']);
                }
            }
        }
        /** @var SystemConfigServices $systemConfigServices */
        $systemConfigServices = app()->make(SystemConfigServices::class);
        $bannerInfo = $systemConfigServices->getSpreadBanner() ?? [];
        $my_banner = sys_data('routine_my_banner');
        $routine_contact_type = sys_config('routine_contact_type', 0);
        /** @var DiyServices $diyServices */
        $diyServices = app()->make(DiyServices::class);

        $diy_data = $diyServices->get(['template_name' => 'member', 'type' => 3], ['value', 'status', 'order_status', 'my_banner_status', 'menu_status', 'service_status']);
        $diy_data = $diy_data ? $diy_data->toArray() : [];
        return app('json')->successful(['routine_my_menus' => array_merge($menusInfo), 'routine_my_banner' => $my_banner, 'routine_spread_banner' => $bannerInfo, 'routine_contact_type' => $routine_contact_type, 'diy_data' => $diy_data]);
    }

    /**
     * 热门搜索关键字获取
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search()
    {
        $routineHotSearch = sys_data('routine_hot_search') ?? [];
        $searchKeyword = [];
        if (count($routineHotSearch)) {
            foreach ($routineHotSearch as $key => &$item) {
                array_push($searchKeyword, $item['title']);
            }
        }
        return app('json')->successful($searchKeyword);
    }


    /**
     * 图片上传
     * @param Request $request
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function upload_image(Request $request, SystemAttachmentServices $services)
    {
        $data = $request->postMore([
            ['filename', 'file'],
        ]);
        if (!$data['filename']) return app('json')->fail('参数有误');
        if (CacheService::has('start_uploads_' . $request->uid()) && CacheService::get('start_uploads_' . $request->uid()) >= 100) return app('json')->fail('非法操作');
        $upload = UploadService::init();
        $info = $upload->to('store/comment')->validate()->move($data['filename']);
        if ($info === false) {
            return app('json')->fail($upload->getError());
        }
        $res = $upload->getUploadInfo();
        $services->attachmentAdd($res['name'], $res['size'], $res['type'], $res['dir'], $res['thumb_path'], 1, (int)sys_config('upload_type', 1), $res['time'], 3);
        if (CacheService::has('start_uploads_' . $request->uid()))
            $start_uploads = (int)CacheService::get('start_uploads_' . $request->uid());
        else
            $start_uploads = 0;
        $start_uploads++;
        CacheService::set('start_uploads_' . $request->uid(), $start_uploads, 86400);
        $res['dir'] = path_to_url($res['dir']);
        if (strpos($res['dir'], 'http') === false) $res['dir'] = $request->domain() . $res['dir'];
        return app('json')->successful('图片上传成功!', ['name' => $res['name'], 'url' => $res['dir']]);
    }

    /**
     * 物流公司
     * @return mixed
     */
    public function logistics(Request $request, ExpressServices $services)
    {
        [$status] = $request->getMore([
            ['status', ''],
        ], true);
        if ($status == 1) $data['status'] = $status;
        $data['is_show'] = 1;
        $expressList = $services->expressList($data);
        return app('json')->successful($expressList ?? []);
    }

    /**
     * 反向解析地址
     * @param Request $request
     * @return mixed
     */
    public function geoLbscoder(Request $request)
    {
        [$data] = $request->getMore([
            ['location', '']
        ], true);
        $locationOption = new LocationOption(sys_config('tengxun_map_key'));
        $data = explode(',', $data);
        $locationOption->setLocation($data[0] ?? '', $data[1] ?? '');
        $location = new Location($locationOption);
        $res = $location->request();
        if ($res->error) {
            return app('json')->fail($res->error);
        }
        if ($res->status) {
            return app('json')->fail($res->message);
        }
        if (!$res->result) {
            return app('json')->fail('获取失败');
        }
        return app('json')->success($res->result);
    }

    /**
     * 短信购买异步通知
     *
     * @param Request $request
     * @return mixed
     */
    public function sms_pay_notify(Request $request)
    {
        [$order_id, $price, $status, $num, $pay_time, $attach] = $request->postMore([
            ['order_id', ''],
            ['price', 0.00],
            ['status', 400],
            ['num', 0],
            ['pay_time', time()],
            ['attach', 0],
        ], true);
        if ($status == 200) {
            try {
                SocketPush::admin()->type('PAY_SMS_SUCCESS')->data(['price' => $price, 'number' => $num])->push();
            } catch (\Throwable $e) {
            }
            return app('json')->successful();
        }
        return app('json')->fail();
    }

    /**
     * 记录用户分享
     * @param Request $request
     * @param UserBillServices $services
     * @return mixed
     */
    public function user_share(Request $request, UserBillServices $services)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($services->setUserShare($uid));
    }

    /**
     * 获取图片base64
     * @param Request $request
     * @return mixed
     */
    public function get_image_base64(Request $request)
    {
        [$imageUrl, $codeUrl] = $request->postMore([
            ['image', ''],
            ['code', ''],
        ], true);
        try {
            $code = CacheService::get($codeUrl, function () use ($codeUrl) {
                $codeTmp = $code = $codeUrl ? image_to_base64($codeUrl) : false;
                if (!$codeTmp) {
                    $codeUrl = explode('?', $codeUrl)[0] ?? $codeUrl;
                    $putCodeUrl = put_image($codeUrl);
                    $code = $putCodeUrl ? image_to_base64(app()->request->domain(true) . '/' . $putCodeUrl) : false;
                    $code ?? unlink(public_path() . $putCodeUrl);
                }
                return $code;
            });
            $image = CacheService::get($imageUrl, function () use ($imageUrl) {
                $imageTmp = $image = $imageUrl ? image_to_base64($imageUrl) : false;
                if (!$imageTmp) {
                    $imageUrl = explode('?', $imageUrl)[0] ?? $imageUrl;
                    $putImageUrl = put_image($imageUrl);
                    $image = $putImageUrl ? image_to_base64(app()->request->domain(true) . '/' . $putImageUrl) : false;
                    $image ?? unlink(public_path() . $putImageUrl);
                }
                return $image;
            });
            return app('json')->successful(compact('code', 'image'));
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 门店列表
     * @param Request $request
     * @param SystemStoreServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function store_list(Request $request, SystemStoreServices $services)
    {
        [$latitude, $longitude, $product_id] = $request->getMore([
            ['latitude', ''],
            ['longitude', ''],
            ['product_id', 0]
        ], true);
		$where = ['type' => 0, 'is_store' => 1];
        $data['list'] = $services->getStoreList($where, ['id', 'name', 'phone', 'address', 'detailed_address', 'image', 'is_show', 'day_start', 'day_end', 'latitude', 'longitude'], $latitude, $longitude, (int)$product_id);
        $data['tengxun_map_key'] = sys_config('tengxun_map_key');
        return app('json')->successful($data);
    }

    /**
     * 查找城市数据
     * @param Request $request
     * @return mixed
     */
    public function city_list(Request $request)
    {
        /** @var SystemCityServices $systemCity */
        $systemCity = app()->make(SystemCityServices::class);
        return app('json')->successful($systemCity->cityList());
    }

    /**
     * 获取拼团数据
     * @return mixed
     */
    public function pink(Request $request, StorePinkServices $pink, UserServices $user)
    {
		[$type] = $request->getMore([
            ['type', 1],
        ], true);
		$where = ['is_refund' => 0];
		if ($type == 1) {
			$where['status'] = 2;
		}
        $data['pink_count'] = $pink->getCount($where);
        $uids = array_flip($pink->getColumn($where, 'uid'));
        if (count($uids)) {
            mt_srand();
            $uids = array_rand($uids, count($uids) < 3 ? count($uids) : 3);
        }
        $data['avatars'] = $uids ? $user->getColumn(is_array($uids) ? [['uid', 'in', $uids]] : ['uid' => $uids], 'avatar') : [];
        return app('json')->successful($data);
    }

    /**
     * 复制口令接口
     * @return mixed
     */
    public function copy_words()
    {
        $data['words'] = sys_config('copy_words');
        return app('json')->successful($data);
    }

    /**
     * 生成口令关键字
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function copy_share_words(Request $request)
    {
        list($productId) = $request->getMore([
            ['product_id', ''],
        ], true);
        /** @var StoreProductServices $productService */
        $productService = app()->make(StoreProductServices::class);
        $keyWords['key_words'] = $productService->getProductWords($productId);
        return app('json')->successful($keyWords);
    }

    /**
     * 获取页面数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiy(DiyServices $services, $id = 0)
    {
        return app('json')->successful($services->getDiyInfo((int)$id));
    }

    /**
     * 获取底部导航
     * @param DiyServices $services
     * @param string $template_name
     * @return mixed
     */
    public function getNavigation(DiyServices $services, string $template_name = '')
    {
        return app('json')->success($services->getNavigation($template_name));
    }

    /**
     * 获取用户协议内容
     * @return mixed
     */
    public function getUserAgreement(Request $request, $type = 1)
    {
        /** @var CacheServices $cache */
        $cache = app()->make(CacheServices::class);
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $content = $cache->getDbCache($type, '');
        $uid = $request->uid() ?? 0;
        $userInfo = $userService->get($uid);
        $name = $userInfo['nickname'] ?? '';
        $avatar = $userInfo['avatar'] ?? '';
        return app('json')->success(compact('content', 'uid', 'name', 'avatar'));
    }

    /**
     * 统计代码
     * @return array|string
     */
    public function getScript()
    {
        return sys_config('system_statistics', '');
    }

    /**
     * 首页开屏广告
     * @return mixed
     */
    public function getOpenAdv()
    {
        /** @var CacheServices $cache */
        $cache = app()->make(CacheServices::class);
        $data = $cache->getDbCache('open_adv', '');
        return app('json')->success($data);
    }

    /**
     * 用户注销
     * @param Request $request
     * @return mixed
     */
    public function cancelUser(Request $request)
    {
        $uid = $request->uid();
        if (!$uid) return app('json')->fail('用户不存在');
        event('user.cancelUser', [$uid]);
        return app('json')->success('注销成功');
    }

    /**
     * 获取版权
     * @return mixed
     */
    public function getCopyright()
    {
        try {
            $copyright = $this->__z6uxyJQ4xYa5ee1mx5();
        } catch (\Throwable $e) {
            $copyright = false;
        }
        return $this->success(['copyright' => $copyright]);
    }
}
