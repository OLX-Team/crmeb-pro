<?php


namespace app\controller\api\v1\activity;


use app\Request;
use app\services\activity\discounts\StoreDiscountsServices;

/**
 * 优惠套餐控制器
 * Class StoreDiscountsController
 * @package app\controller\api\v1\activity
 */
class StoreDiscountsController
{
    protected $services;

    /**
     * StoreDiscountsController constructor.
     * @param StoreDiscountsServices $services
     */
    public function __construct(StoreDiscountsServices $services)
    {
        $this->services = $services;
    }

    /**
     * 获取优惠商品列表
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        list($product_id) = $request->postMore([
            ['product_id', 0]
        ], true);
        $uid = (int)$request->uid();
        if (!$product_id) return app('json')->fail('参数错误');
        $list = $this->services->getDiscounts($product_id, $uid, 1);
        return $list ? app('json')->successful($list) : app('json')->fail('活动商品已下架');
    }
}