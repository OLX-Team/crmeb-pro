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
namespace app\controller\admin\v1\order;

use app\common\controller\Order;
use app\controller\admin\AuthController;
use app\services\order\{
    StoreOrderServices
};
use think\facade\App;

/**
 * 订单管理
 * Class StoreOrder
 * @package app\controller\admin\v1\order
 */
class StoreOrder extends AuthController
{

    use Order;

    /**
     * StoreOrder constructor.
     * @param App $app
     * @param StoreOrderServices $service
     */
    public function __construct(App $app, StoreOrderServices $service)
    {
        parent::__construct($app);
        $this->services = $service;
    }

}
