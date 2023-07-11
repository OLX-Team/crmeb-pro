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

namespace app\controller\admin\v1\finance;

use app\services\user\UserBillServices;
use app\services\user\UserBrokerageServices;
use app\services\user\UserMoneyServices;
use think\facade\App;
use app\controller\admin\AuthController;

/**
 * Class Finance
 * @package app\controller\admin\v1\finance
 */
class Finance extends AuthController
{
    /**
     * Finance constructor.
     * @param App $app
     * @param UserBillServices $services
     */
    public function __construct(App $app, UserBillServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 筛选类型
     */
    public function bill_type(UserMoneyServices $services)
    {
        return $this->success($services->bill_type());
    }

    /**
     * 资金记录
     */
    public function list(UserMoneyServices $services)
    {
        $where = $this->request->getMore([
            ['start_time', ''],
            ['end_time', ''],
            ['nickname', ''],
            ['limit', 20],
            ['page', 1],
            ['type', ''],
        ]);
        return $this->success($services->getMoneyList($where));
    }

    /**
     * 用户佣金记录（用户列表）
     * @return mixed
     */
    public function get_commission_list(UserBrokerageServices $services)
    {
        $where = $this->request->getMore([
            ['nickname', ''],
            ['price_max', ''],
            ['price_min', ''],
            ['sum_number', 'normal'],
            ['brokerage_price', 'normal'],
            ['date', '', '', 'time']
        ]);
        return $this->success($services->getCommissionList($where));
    }

    /**
     * 佣金详情用户信息
     * @param $id
     * @return mixed
     */
    public function user_info(UserBrokerageServices $services, $id)
    {
        return $this->success($services->user_info((int)$id));
    }

    /**
     * 获取用户佣金列表
     * @param UserBrokerageServices $services
     * @param string $id
     * @return mixed
     */
    public function getUserBrokeragelist(UserBrokerageServices $services, $id = '')
    {
        if ($id == '') return $this->fail('缺少参数');
        $where = $this->request->getMore([
            ['start_time', ''],
            ['end_time', ''],
            ['nickname', '']
        ]);
        $where['uid'] = (int)$id;
        return $this->success($services->getBrokerageList($where));
    }

}
