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

namespace app\jobs\order;


use app\services\order\StoreOrderTakeServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 自动执行确认收货
 * Class AutoTakeOrderJob
 * @package app\jobs\order
 */
class AutoTakeOrderJob extends BaseJobs
{

    use QueueTrait;

    /**
     * @return string
     */
    protected static function queueName()
    {
        return 'CRMEB_PRO_TASK';
    }

    /**
     * @param $where
     * @param $page
     * @param $limit
     * @return bool
     */
    public function doJob($where, $page, $limit)
    {
        /** @var StoreOrderTakeServices $service */
        $service = app()->make(StoreOrderTakeServices::class);
        return $service->runAutoTakeOrder($where, $page, $limit);
    }
}
