<?php


namespace app\jobs\work;


use app\services\work\WorkMomentSendResultServices;
use app\services\work\WorkMomentServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * Class WorkMomentJob
 * @package app\jobs\work
 */
class WorkMomentJob extends BaseJobs
{

    use QueueTrait;

    /**
     * @param $jobid
     * @return mixed
     */
    public function task($jobid)
    {
        /** @var WorkMomentServices $service */
        $service = app()->make(WorkMomentServices::class);
        return $service->getTaskInfo($jobid);
    }

    /**
     * 获取任务详情
     * @param $momentId
     * @param $cursor
     * @return mixed
     */
    public function getTaskPage($momentId, $cursor)
    {
        /** @var WorkMomentSendResultServices $service */
        $service = app()->make(WorkMomentSendResultServices::class);
        return $service->getTaskInfo($momentId, $cursor);
    }

    /**
     * 获取某个成员发送朋友圈详情
     * @param $id
     * @param $momentId
     * @param $userId
     * @param $cursor
     * @return bool
     */
    public function getCustomerPage($id, $momentId, $userId, $cursor)
    {
        /** @var WorkMomentSendResultServices $service */
        $service = app()->make(WorkMomentSendResultServices::class);
        return $service->getCustomerList((int)$id, $momentId, $userId, $cursor);
    }
}
