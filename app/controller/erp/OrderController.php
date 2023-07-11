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

use app\Request;
use app\services\erp\OrderServices;
use think\facade\Log;

class OrderController
{
    protected $services;

    /**
     * OrderController constructor.
     * @param OrderServices $services
     */
    public function __construct(OrderServices $services)
    {
        $this->services = $services;
    }

    /**
     * 订单发货回调
     * @return mixed
     */
    public function deliverCallback(Request $request)
    {
        $data = $request->postMore([
            ['type', 1],
            ['logistics_company', ''], //快递公司名称
            ['l_id', ''],  // 快递单号
            ['lc_id', ''], // 快递公司编码
            ['o_id', ''],  // 内部订单号
            ['so_id', ''], // 线上单号
            ['send_date', ''], // 发货时间
            ['items', []],    // 商品列表
        ]);

        Log::info(['data' => json_encode($data), 'type' => 'deliverCallback']);
        if (sys_config('erp_open')) {
            $this->services->deliverCallback($data);
        }
        return app('json')->success();
    }

    /**
     * 订单取消回调
     * @return mixed
     */
    public function cancelCallback(Request $request)
    {
        $data = $request->postMore([
            ['so_id', 0],
            ['remark', ''],
        ]);

        Log::info(['data' => json_encode($data), 'type' => 'cancelCallback']);
        if (sys_config('erp_open')) {
            $this->services->cancelCallback($data);
        }
        return app('json')->success();
    }

    /**
     * 售后收货回调
     * @return mixed
     */
    public function receiveCallback(Request $request)
    {
        $data = $request->postMore([
            ['so_id', 0],
            ['shop_id', 0],      // 店铺ID
            ['action_name', ''], // 操作类型
            ['as_id', 0],        // 售后单号
            ['o_id', 0],         // 内部单号
            ['outer_as_id', ''], // 外部售后单号
            ['remark', ''],
            ['items', []],       // 商品列表
        ]);

        Log::info(['data' => json_encode($data), 'type' => 'receiveCallback']);
        if (sys_config('erp_open')) {
            $this->services->receivedCallback($data, 1, true);
        }
        return app('json')->success();
    }
}