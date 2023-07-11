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
namespace app\controller\api\v1\user;

use app\Request;
use app\services\product\product\StoreProductRelationServices;
use crmeb\services\CacheService;


/**
 * 用户收藏
 * Class UserCollectController
 * @package app\api\controller\v1\user
 */
class UserCollectController
{
    protected $services = NUll;

    /**
     * UserCollectController constructor.
     * @param StoreProductRelationServices $services
     */
    public function __construct(StoreProductRelationServices $services)
    {
        $this->services = $services;
    }


    /**
     * 获取收藏商品
     *
     * @param Request $request
     * @return mixed
     */
    public function collect_user(Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->getUserCollectProduct($uid));
    }

    /**
     * 添加收藏
     * @param Request $request
     * @param $id
     * @param $category
     * @return mixed
     */
    public function collect_add(Request $request)
    {
        [$id, $category] = $request->postMore([
            ['id', 0],
            ['category', 'product']
        ], true);
        if (!$id) return app('json')->fail('参数错误');
        if(is_numeric($id)) $id = [$id];
        $res = $this->services->productRelation($request->uid(), $id, 'collect', $category);
        if (!$res) {
            return app('json')->fail('添加收藏失败');
        } else {
            CacheService::clearTokenAll('relation_' . fmod((float)$request->uid(), (float)10));
            return app('json')->successful('收藏成功');
        }
    }

    /**
     * 取消收藏
     *
     * @param Request $request
     * @return mixed
     */
    public function collect_del(Request $request)
    {
        [$id, $category] = $request->postMore([
            ['id', 0],
            ['category', 'product']
        ], true);
        if (!$id) return app('json')->fail('参数错误');
        if (is_numeric($id)) $id = [$id];
        $uid = (int)$request->uid();
        $res = $this->services->unProductRelation($uid, $id, 'collect', $category);
        if (!$res) {
            return app('json')->fail('取消收藏失败');
        } else {
            CacheService::clearTokenAll('relation_' . fmod((float)$request->uid(), (float)10));
            return app('json')->successful('取消收藏成功');
        }
    }

    /**
     * 批量收藏
     * @param Request $request
     * @return mixed
     */
    public function collect_all(Request $request)
    {
        $collectInfo = $request->postMore([
            ['id', ''],
            ['category', 'product'],
        ]);
        $collectInfo['id'] = explode(',', $collectInfo['id']);
        if (!count($collectInfo['id'])) {
            return app('json')->fail('参数错误');
        }
        $uid = (int)$request->uid();
        $productIdS = $collectInfo['id'];
        $res = $this->services->productRelation($uid, $productIdS, 'collect', $collectInfo['category']);
        if (!$res) {
            return app('json')->fail('收藏失败');
        } else {
            CacheService::clearTokenAll('relation_' . fmod((float)$request->uid(), (float)10));
            return app('json')->successful('收藏成功');
        }
    }
}
