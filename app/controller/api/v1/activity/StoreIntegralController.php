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
namespace app\controller\api\v1\activity;


use app\Request;
use app\services\activity\integral\StoreIntegralServices;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;


/**
 * 积分商城
 * Class StoreIntegralController
 * @package app\api\controller\activity
 */
class StoreIntegralController
{

    protected $services;

    public function __construct(StoreIntegralServices $services)
    {
        $this->services = $services;
    }

    /**
     * 积分商城首页数据
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $data['banner'] = sys_data('integral_shop_banner') ?? [];// 积分商城banner
        $where = ['is_show' => 1];
        $where['is_host'] = 1;
        $data['list'] = $this->services->getIntegralList($where);
        return app('json')->successful(get_thumb_water($data, 'mid'));
    }

    /**
     * 商品列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        $where = $request->getMore([
            ['store_name', ''],
            ['priceOrder', ''],
            ['salesOrder', ''],
        ]);
        $where['is_show'] = 1;
        $list = $this->services->getIntegralList($where);
        return app('json')->successful(get_thumb_water($list));
    }

    /**
     * 积分商品详情
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function detail(Request $request, $id)
    {
        $data = $this->services->integralDetail($request, $id);
        return app('json')->successful($data);
    }

}
