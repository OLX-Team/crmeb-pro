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

namespace app\controller\admin\v1\diy;


use app\controller\admin\AuthController;
use app\services\article\ArticleServices;
use app\services\diy\DiyServices;
use app\services\other\CacheServices;
use app\services\product\category\StoreCategoryServices;
use app\services\product\product\StoreProductServices;
use crmeb\exceptions\AdminException;
use think\facade\App;

/**
 * Class Diy
 * @package app\controller\admin\v1\diy
 */
class Diy extends AuthController
{

    /**
     * Diy constructor.
     * @param App $app
     * @param DiyServices $services
     */
    public function __construct(App $app, DiyServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * DIY列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['type', ''],
            ['name', ''],
            ['version', ''],
        ]);
        $data = $this->services->getDiyList($where);
        return $this->success($data);
    }

    /**
     * 保存资源
     * @param int $id
     * @return mixed
     */
    public function saveData(int $id = 0)
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['title', ''],
            ['value', ''],
            ['type', ''],
            ['cover_image', ''],
            ['is_show', 0],
            ['is_bg_color', 0],
            ['is_bg_pic', 0],
            ['bg_tab_val', 0],
            ['color_picker', ''],
            ['bg_pic', ''],
        ]);
        $value = is_string($data['value']) ? json_decode($data['value'], true) : $data['value'];
        $infoDiy = $id ? $this->services->get($id, ['is_diy']) : [];
        if ($infoDiy && $infoDiy['is_diy']) {
            foreach ($value as $key => &$item) {
                if ($item['name'] === 'goodList') {
                    if (isset($item['selectConfig']['list'])) {
                        unset($item['selectConfig']['list']);
                    }
                    if (isset($item['goodsList']['list']) && is_array($item['goodsList']['list'])) {
                        $limitMax = config('database.page.limitMax', 50);
                        if (isset($item['numConfig']['val']) && isset($item['tabConfig']['tabVal']) && $item['tabConfig']['tabVal'] == 0 && $item['numConfig']['val'] > $limitMax) {
                            return $this->fail('您设置得商品个数超出系统限制,最大限制' . $limitMax . '个商品');
                        }
                        $item['goodsList']['ids'] = array_column($item['goodsList']['list'], 'id');
                        unset($item['goodsList']['list']);
                    }
                } elseif ($item['name'] === 'articleList') {
                    if (isset($item['selectList']['list']) && is_array($item['selectList']['list'])) {
                        unset($item['selectList']['list']);
                    }
                } elseif ($item['name'] === 'promotionList') {
                    unset($item['productList']['list']);
                }
            }
            $data['value'] = json_encode($value);
        } else {
            if (isset($value['d_goodList']['selectConfig']['list'])) {
                unset($value['d_goodList']['selectConfig']['list']);
            } elseif (isset($value['d_goodList']['goodsList']['list'])) {
                $limitMax = config('database.page.limitMax', 50);
                if (isset($value['d_goodList']['numConfig']['val']) && isset($value['d_goodList']['tabConfig']['tabVal']) && $value['d_goodList']['tabConfig']['tabVal'] == 0 && $value['d_goodList']['numConfig']['val'] > $limitMax) {
                    return $this->fail('您设置得商品个数超出系统限制,最大限制' . $limitMax . '个商品');
                }
                $value['d_goodList']['goodsList']['ids'] = array_column($value['d_goodList']['goodsList']['list'], 'id');
                unset($value['d_goodList']['goodsList']['list']);
            } elseif (isset($value['k_newProduct']['goodsList']['list'])) {
                $list = [];
                foreach ($value['k_newProduct']['goodsList']['list'] as $item) {
                    $list[] = [
                        'image' => $item['image'],
                        'store_info' => $item['store_info'],
                        'store_name' => $item['store_name'],
                        'id' => $item['id'],
                        'price' => $item['price'],
                        'ot_price' => $item['ot_price'],
                    ];
                }
                $value['k_newProduct']['goodsList']['list'] = $list;
            } elseif (isset($value['selectList']['list']) && is_array($value['selectList']['list'])) {
                unset($value['goodsList']['list']);
            }
            $data['value'] = json_encode($value);
        }
        $data['version'] = '1.0';
        return $this->success($id ? '修改成功' : '保存成功', ['id' => $this->services->saveData($id, $data)]);
    }

    /**
     * 删除模板
     * @param $id
     * @return mixed
     */
    public function del($id)
    {
        $this->services->del($id);
        return $this->success('删除成功');
    }

    /**
     * 使用模板
     * @param $id
     * @return mixed
     */
    public function setStatus($id)
    {
        return $this->success($this->services->setStatus($id));
    }

    /**
     * 获取一条数据
     * @param int $id
     * @return mixed
     */
    public function getInfo(int $id, StoreProductServices $services)
    {
        if (!$id) throw new AdminException('参数错误');
        $info = $this->services->get($id);
        if ($info) {
            $info = $info->toArray();
        } else {
            throw new AdminException('模板不存在');
        }
        $info['value'] = json_decode($info['value'], true);
        if ($info['value']) {
            /** @var ArticleServices $articleServices */
            $articleServices = app()->make(ArticleServices::class);
            if ($info['is_diy']) {
                foreach ($info['value'] as &$item) {
                    if ($item['name'] === 'goodList' && isset($item['goodsList']['ids']) && count($item['goodsList']['ids'])) {
                        $item['goodsList']['list'] = $services->getSearchList(['ids' => $item['goodsList']['ids']]);
                    } elseif ($item['name'] === 'articleList') {//文章
                        $data = [];
                        if ($item['selectConfig']['activeValue'] ?? 0) {
                            $data = $articleServices->getList(['cid' => $item['selectConfig']['activeValue'] ?? 0], 0, $item['numConfig']['val'] ?? 0);
                        }
                        $item['selectList']['list'] = $data['list'] ?? [];
                    } elseif ($item['name'] === 'promotionList') {//活动模仿
                        $data = [];
                        if (isset($item['tabConfig']['tabCur']) && $typeArr = $item['tabConfig']['list'][$item['tabConfig']['tabCur']] ?? []) {
                            $val = $typeArr['link']['activeVal'] ?? 0;
                            if ($val) {
                                $data = $this->get_groom_list($val, (int)($item['numConfig']['val'] ?? 0));
                            }
                        }
                        $item['productList']['list'] = $data;
                    }
                }
            } else {
                if ($info['value']) {
                    if (isset($info['value']['d_goodList']['goodsList'])) {
                        $info['value']['d_goodList']['goodsList']['list'] = [];
                    }
                    if (isset($info['value']['d_goodList']['goodsList']['ids']) && count($info['value']['d_goodList']['goodsList']['ids'])) {
                        $info['value']['d_goodList']['goodsList']['list'] = $services->getSearchList(['ids' => $info['value']['d_goodList']['goodsList']['ids']]);
                    }
                }
            }
        }
        return $this->success(compact('info'));
    }

    /**
     * 获取uni-app路径
     * @return mixed
     */
    public function getUrl()
    {
        $url = sys_data('uni_app_link');
        if ($url) {
            foreach ($url as &$link) {
                $link['url'] = $link['link'];
                $link['parameter'] = trim($link['param']);
            }
        } else {
            /** @var CacheServices $cache */
            $cache = app()->make(CacheServices::class);
            $url = $cache->getDbCache('uni_app_url', null);
        }
        return $this->success(compact('url'));
    }

    /**
     * 获取商品分类
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCategory()
    {
        /** @var StoreCategoryServices $categoryService */
        $categoryService = app()->make(StoreCategoryServices::class);
        $list = $categoryService->cascaderList(1, 1);
//        $data = [];
//        foreach ($list as $value) {
//            $data[] = [
//                'id' => $value['id'],
//                'title' => $value['html'] . $value['cate_name']
//            ];
//        }
        return $this->success($list);
    }

    /**
     * 获取商品
     * @return mixed
     */
    public function getProduct()
    {
        $where = $this->request->getMore([
            ['id', 0],
            ['salesOrder', ''],
            ['priceOrder', ''],
        ]);
        $id = $where['id'];
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        unset($where['id']);
        /** @var StoreCategoryServices $storeCategoryServices */
        $storeCategoryServices = app()->make(StoreCategoryServices::class);
        if ($storeCategoryServices->value(['id' => $id], 'pid')) {
            $where['sid'] = $id;
        } else {
            $where['cid'] = $id;
        }
        [$page, $limit] = $this->services->getPageValue();
        /** @var StoreProductServices $productService */
        $productService = app()->make(StoreProductServices::class);
        $list = $productService->getSearchList($where, $page, $limit, ['id,store_name,cate_id,image,IFNULL(sales, 0) + IFNULL(ficti, 0) as sales,price,stock,activity,ot_price,spec_type,recommend_image,unit_name,is_vip,vip_price']);
        return $this->success($list);
    }

    /**
     * 获取提货点自提开启状态
     * @return mixed
     */
    public function getStoreStatus()
    {
        $data['store_status'] = sys_config('store_self_mention', 0);
        return $this->success($data);
    }

    /**
     * 设置模版默认数据
     * @param $id
     * @return mixed
     */
    public function setDefaultData($id)
    {
        if (!$id) throw new AdminException('参数错误');
        $info = $this->services->get($id);
        if ($info) {
            $info->default_value = $info->value;
            $info->update_time = time();
            $info->save();
            event('diy.update');
            return $this->success('设置成功');
        } else {
            throw new AdminException('模板不存在');
        }
    }

    /**
     * 还原模板数据
     * @param $id
     * @return mixed
     */
    public function Recovery($id)
    {
        if (!$id) throw new AdminException('参数错误');
        $info = $this->services->get($id);
        if ($info) {
            $info->value = $info->default_value;
            $info->update_time = time();
            $info->save();
            event('diy.update');
            return $this->success('还原成功');
        } else {
            throw new AdminException('模板不存在');
        }
    }

    /**
     * 获取二级分类
     * @return mixed
     */
    public function getByCategory()
    {
        $where = $this->request->getMore([
            ['pid', -1],
            ['name', '']
        ]);
        /** @var StoreCategoryServices $categoryServices */
        $categoryServices = app()->make(StoreCategoryServices::class);
        return $this->success($categoryServices->getALlByIndex($where));
    }

    /**
     * 获取首页推荐不同类型商品的轮播图和商品
     * @param $type
     * @return mixed
     */
    public function groom_list($type)
    {
        $info['list'] = $this->get_groom_list($type);
        return $this->success($info);
    }

    /**
     * 实际获取方法
     * @param $type
     * @return array
     */
    protected function get_groom_list($type, int $num = 0)
    {
        /** @var StoreProductServices $services */
        $services = app()->make(StoreProductServices::class);
        $info = [];
        if ($type == 1) {// 精品推荐
            $info = $services->getRecommendProduct(0, 'is_best', $num);// 精品推荐个数
        } else if ($type == 2) {//  热门榜单
            $info = $services->getRecommendProduct(0, 'is_hot', $num);// 热门榜单 猜你喜欢
        } else if ($type == 3) {// 首发新品
            $info = $services->getRecommendProduct(0, 'is_new', $num);// 首发新品
        } else if ($type == 4) {// 促销单品
            $info = $services->getRecommendProduct(0, 'is_benefit', $num);// 促销单品
        } else if ($type == 5) {// 会员商品
            $whereVip = [
                ['vip_price', '>', 0],
                ['is_vip', '=', 1],
            ];
            $info = $services->getRecommendProduct(0, $whereVip, $num);// 会员商品
        }
        return $info;
    }

    /**
     * 一键换色
     * @param $status
     * @return mixed
     */
    public function colorChange($status, $type)
    {
        if (!$status) throw new AdminException('参数错误');
        $info = $this->services->get(['template_name' => $type, 'type' => 3]);
        if ($info) {
            $info->value = $status;
            $info->update_time = time();
            $info->save();
            event('diy.update');
            return $this->success('设置成功');
        } else {
            throw new AdminException('模板不存在');
        }
    }

    /**
     * 获取颜色选择和分类模板选择
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getColorChange($type)
    {
        $status = (int)$this->services->getColorChange((string)$type);
        return $this->success(compact('status'));
    }

    /**
     * 获取单个diy小程序预览二维码
     * @param $id
     * @return mixed
     */
    public function getRoutineCode($id)
    {
        $image = $this->services->getRoutineCode((int)$id);
        return $this->success(compact('image'));
    }

    /**
     * 获取会员中心数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMember()
    {
        $data = $this->services->getMemberData();
        return $this->success($data);
    }

    /**
     * 保存个人中心数据
     * @return mixed
     */
    public function memberSaveData()
    {
        $data = $this->request->postMore([
            ['status', 0],
            ['order_status', 0],
            ['my_banner_status', 0],
            ['menu_status', 1],
            ['service_status', 1],
            ['routine_my_banner', []],
            ['routine_my_menus', []]
        ]);
        $this->services->memberSaveData($data);
        event('diy.update');
        return $this->success('保存成功');
    }

    /**
     * 获取开屏广告
     * @return mixed
     */
    public function getOpenAdv()
    {
        /** @var CacheServices $cacheServices */
        $cacheServices = app()->make(CacheServices::class);
        $data = $cacheServices->getDbCache('open_adv','');
        $data = $data ?: [];
        return app('json')->success($data);
    }

    /**
     * 保存开屏广告
     * @return mixed
     */
    public function openAdvAdd()
    {
        $data = $this->request->postMore([
            ['status', 0],
            ['time', 0],
            ['type', ''],
            ['value', []],
            ['video_link','']
        ]);
        /** @var CacheServices $cacheServices */
        $cacheServices = app()->make(CacheServices::class);
        $cacheServices->setDbCache('open_adv', $data);
        event('diy.update');
        return app('json')->success('保存成功');
    }
}
