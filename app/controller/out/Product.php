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
namespace app\controller\out;

use app\Request;
use app\services\product\category\StoreCategoryServices;
use app\services\out\StoreProductServices;

/**
 * 商品类
 * Class StoreProductController
 * @package app\api\controller\store
 */
class Product
{
    /**
     * 商品services
     * @var StoreProductServices
     */
    protected $services;

    public function __construct(StoreProductServices $services)
    {
        $this->services = $services;
    }

    /**
     * 商品详情
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail(Request $request)
    {
        $spu = $request->getMore([
            ['spu', 0]
        ], true);
        $data = $this->services->productDetail($request, $spu);
        return app('json')->successful($data);
    }

    /**
     * 修改状态
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function set_show(Request $request)
    {
        list($spu, $is_show) = $request->getMore([
            ['spu', 0],
            ['is_show', 0]
        ], true);
        $this->services->setShow($request, $spu, $is_show);
        return app('json')->success($is_show == 1 ? '上架成功' : '下架成功');
    }

    /**
     * 获取分类
     * @param StoreCategoryServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function category(StoreCategoryServices $services)
    {
        $data = $services->getOutList();
        return app('json')->success($data);
    }

    /**
     * 批量修改库存
     * @param Request $request
     * @param string $spu
     * @return
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function set_stock(Request $request, $spu = '')
    {
        [$data] = $request->postMore([
            ['data', []],
        ], true);
        if (!$spu || !$data) return $this->fail('参数错误');
        $res = $this->services->setStock((array)$data, (string)$spu);
        return app('json')->success('修改成功');
    }
}
