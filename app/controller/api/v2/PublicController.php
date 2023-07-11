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

namespace app\controller\api\v2;


use app\Request;
use app\services\diy\DiyServices;
use app\services\other\CityAreaServices;
use app\services\product\category\StoreCategoryServices;
use app\services\product\product\StoreProductServices;
use app\services\wechat\WechatUserServices;
use crmeb\services\CacheService;
use crmeb\services\SystemConfigService;

class PublicController
{
    /**
     * 主页获取
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $data = SystemConfigService::more(['fast_number', 'bast_number', 'first_number', 'promotion_number', 'tengxun_map_key', 'site_name']);
        $site_name = $data['site_name'] ?? '';
        $tengxun_map_key = $data['tengxun_map_key'] ?? '';
        $fastNumber = (int)($data['fast_number'] ?? 0);// 快速选择分类个数
        $bastNumber = (int)($data['bast_number'] ?? 0);// 精品推荐个数
        $firstNumber = (int)($data['first_number'] ?? 0);// 首发新品个数
        $promotionNumber = (int)($data['promotion_number'] ?? 0);// 首发新品个数
        /** @var StoreCategoryServices $categoryService */
        $categoryService = app()->make(StoreCategoryServices::class);
        $info['fastList'] = $fastNumber ? $categoryService->byIndexList($fastNumber, 'id,cate_name,pid,pic') : [];// 快速选择分类个数
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        $info['bastList'] = $bastNumber ? $storeProductServices->getRecommendProduct($request->uid(), 'is_best', $bastNumber) : [];// 精品推荐个数
        $info['firstList'] = $firstNumber ? $storeProductServices->getRecommendProduct($request->uid(), 'is_new', $firstNumber) : [];// 首发新品个数
        $benefit = $promotionNumber ? $storeProductServices->getRecommendProduct($request->uid(), 'is_benefit', $promotionNumber) : [];// 首页促销单品
        $likeInfo = $storeProductServices->getRecommendProduct($request->uid(), 'is_hot', 3);// 热门榜单 猜你喜欢
        if ($request->uid()) {
            /** @var WechatUserServices $wechatUserService */
            $wechatUserService = app()->make(WechatUserServices::class);
            $subscribe = $wechatUserService->value(['uid' => $request->uid(), 'user_type' => 'wechat'], 'subscribe') ? true : false;
        } else {
            $subscribe = true;
        }
        return app('json')->successful(compact('info', 'benefit', 'likeInfo', 'subscribe', 'tengxun_map_key', 'site_name'));
    }

    /**
     * 获取页面数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiy($name = '')
    {
        /** @var DiyServices $diyService */
        $diyService = app()->make(DiyServices::class);
        $data = CacheService::redisHandler('diy')->remember('diy_' . $name, function () use ($name, $diyService) {
            $data = $diyService->getDiy($name);
            if (isset($data['f_scroll_box']['goodsList']['list'])) {
                $data['f_scroll_box']['goodsList']['list'] = get_thumb_water($data['f_scroll_box']['goodsList']['list'], 'small', ['pic']);
            }
            return $data;
        });
        return app('json')->successful($data);
    }

    /**
     * 是否强制绑定手机号
     * @return mixed
     */
    public function bindPhoneStatus()
    {
        $status = sys_config('store_user_mobile') ? true : false;
        return app('json')->success(compact('status'));
    }

    /**
     * 是否关注
     * @param Request $request
     * @param WechatUserServices $services
     * @return mixed
     */
    public function subscribe(Request $request, WechatUserServices $services)
    {
        return app('json')->success(['subscribe' => $services->value(['uid' => $request->uid(), 'user_type' => 'wechat'], 'subscribe') ? true : false]);
    }

    /**
     * 获取提货点自提开启状态
     * @return mixed
     */
    public function getStoreStatus()
    {
        $data['store_status'] = sys_config('store_self_mention', 0);
        return app('json')->successful($data);
    }

    /**
     * 获取颜色选择和分类模板选择
     * @param DiyServices $services
     * @param $name
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function colorChange(DiyServices $services, $name)
    {
        $status = (int)$services->getColorChange((string)$name);
        $navigation = (int)sys_config('navigation_open');
        return app('json')->success(compact('status', 'navigation'));
    }

    /**
     * 获取城市
     * @param CityAreaServices $services
     * @return mixed
     */
    public function city(Request $request, CityAreaServices $services)
    {
        $pid = $request->get('pid', 0);
        return app('json')->success($services->getCityTreeList((int)$pid));
    }

    /**
     * 解析（获取导入微信地址）
     * @param CityAreaServices $services
     * @return mixed
     */
    public function cityList(Request $request, CityAreaServices $services)
    {
        $address = $request->param('address', '');
        if (!$address)
            return app('json')->fail('地址不存在');

        $city = $services->searchCity(compact('address'));
        if (!$city) return app('json')->fail('地址暂未录入，请联系管理员');
        $where = [['id', 'in', array_merge([$city['id']], explode('/', trim($city->path, '/')))]];
        return app('json')->success($services->getCityList($where, 'id as value,id,name as label,parent_id as pid', ['children']));
    }

    /**
     * 获取客服类型配置
     * @return mixed
     */
    public function getCustomerType()
    {
        $data = SystemConfigService::more(['customer_type', 'customer_phone', 'customer_url']);
        $data['userInfo'] = [];
        return app('json')->success($data);
    }
}
