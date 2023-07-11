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
namespace app\controller\erp;

use app\jobs\product\ProductSyncErp;
use app\Request;
use crmeb\services\erp\Erp as ErpServices;
use crmeb\services\erp\storage\jushuitan\Product as ProductService;
use think\Response;

/**
 * 商品类
 * Class ProductController
 * @package app\controller\erp
 */
class ProductController
{

    /*** @var ProductService */
    protected $services;

    public function __construct(ErpServices $services)
    {
        $this->services = $services->serviceDriver('product');
    }

    /**
     * 使用spu同步商品
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function syncProduct(Request $request)
    {
        [$spuStr] = $request->getMore([
            ['spu_str', ''],
        ], true);

        if (empty($spuStr)) {
            return app('json')->fail('请输入ERP商品SPU');
        }
        $spuArr = explode(',', $spuStr);
        foreach ($spuArr as $item) {
            // 获取商品
            ProductSyncErp::dispatchDo('productFromErp', [$item]);
        }
        return app('json')->success('正在同步中，请稍后查看');
    }

    /**
     * 使用sku同步库存
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function syncStock(Request $request)
    {
        [$ids] = $request->getMore([
            ['ids', ''],
        ], true);

        if (empty($ids)) {
            return app('json')->fail('请选择商品');
        }

        $idArr = explode(',', $ids);
        $data = array_chunk($idArr, 1);
        foreach ($data as $item) {
            // 获取库存
            ProductSyncErp::dispatchDo('stockFromErp', [$item]);
        }
        return app('json')->success('正在同步中，请稍后查看');
    }
}