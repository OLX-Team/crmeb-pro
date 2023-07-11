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

namespace app\jobs;


use app\services\other\queue\QueueServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 批量任务队列
 * Class BatchHandleJob
 * @package app\jobs
 */
class BatchHandleJob extends BaseJobs
{
    use QueueTrait;

    /**
     * @return mixed
     */
    public static function queueName()
    {
        $default = config('queue.default');
        return config('queue.connections.' . $default . '.batch_queue');
    }

    /**
     * 批量任务队列
     * @param false $data
     * @param $type
     * @param array $other
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function doJob($data = false, $type, array $other = [])
    {
        /** @var QueueServices $queueServices */
        $queueServices = app()->make(QueueServices::class);
        $re = true;
        try {
            switch ($type) {
                case 1://批量发放优惠券
                    if (!$data) {
                        return true;
                    }
                    $re = $queueServices->sendCoupon($data, $type);
                    break;
                case 2://批量设置用户分组
                    if (!$data) {
                        return true;
                    }
                    $re = $queueServices->setUserGroup($data, $type);
                    break;
                case 3://批量设置用户标签
                    if (!$data) {
                        return true;
                    }
                    $re = $queueServices->setUserLabel($data, $type, $other);
                    break;
                case 4://批量上下架商品
                    $re = $queueServices->setProductShow($data, $type);
                    break;
                case 5://批量删除商品规格
                    $re = $queueServices->delProductRule($type);
                    break;
                case 6://批量删除用户已删除订单
                    $re = $queueServices->delOrder($type);
                    break;
                case 7://批量手动发货
                case 8://批量电子面单发货
                case 9://批量配送
                case 10://批量虚拟发货
                    $re = $queueServices->orderDelivery($data, $other);
                    break;
                default:
                    $re = false;
                    break;
            }
        } catch (\Throwable $e) {
            $queueName = $queueServices->queue_type_name[$type] ?? '';
            Log::error($queueName . '失败，原因' . $e->getMessage());
            $re = false;
        }
        if ($re === false) $queueServices->delWrongQueue(0, $type, false);
        return true;
    }
}
