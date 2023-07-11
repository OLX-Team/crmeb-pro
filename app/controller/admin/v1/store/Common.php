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
namespace app\controller\admin\v1\store;

use app\controller\admin\AuthController;
use app\services\order\store\BranchOrderServices;
use app\services\store\SystemStoreServices;

/**
 * 平台门店公共
 * Class Common
 * @package app\controller\admin\v1\store
 */
class Common extends AuthController
{
    /**
     * 首页运营头部统计
     * @param BranchOrderServices $orderServices
     * @return mixed
     */
    public function homeStatics(BranchOrderServices $orderServices)
    {
        [$time] = $this->request->getMore([
            ['data', '', '', 'time']
        ], true);
        $time = $orderServices->timeHandle($time);
        return app('json')->success($orderServices->homeStatics(-1, $time));
    }

    /**
     * 首页营业趋势图表
     * @param BranchOrderServices $orderServices
     * @return mixed
     */
    public function operateChart(BranchOrderServices $orderServices)
    {
        [$time] = $this->request->getMore([
            ['data', '', '', 'time']
        ], true);
        $time = $orderServices->timeHandle($time, true);
        return app('json')->success($orderServices->operateChart(-1, $time));
    }

    /**
     * 首页交易统计
     * @param BranchOrderServices $orderServices
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderChart(BranchOrderServices $orderServices)
    {
        [$time] = $this->request->getMore([
            ['data', '', '', 'time']
        ], true);
        $time = $orderServices->timeHandle($time);
        return $this->success($orderServices->orderChart(-1, $time));
    }

    /**
     * 首页门店统计
     * @param SystemStoreServices $storeServices
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function storeChart(SystemStoreServices $storeServices)
    {
        [$time] = $this->request->getMore([
            ['data', '', '', 'time']
        ], true);
        $time = $storeServices->timeHandle($time);
        return $this->success($storeServices->storeChart($time));
    }
}
