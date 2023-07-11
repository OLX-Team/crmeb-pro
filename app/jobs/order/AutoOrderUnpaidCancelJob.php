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


use app\services\order\StoreOrderServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 自动取消未支付订单
 * Class AutoOrderUnpaidCancelJob
 * @package app\jobs\order
 */
class AutoOrderUnpaidCancelJob extends BaseJobs
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
     * @param $page
     * @param $limit
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function doJob($page, $limit)
    {
        /** @var StoreOrderServices $service */
        $service = app()->make(StoreOrderServices::class);
        return $service->runOrderUnpaidCancel($page, $limit);
    }

}
