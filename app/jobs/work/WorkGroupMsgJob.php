<?php


namespace app\jobs\work;


use app\services\work\WorkGroupMsgSendResultServices;
use app\services\work\WorkGroupMsgTaskServices;
use app\services\work\WorkGroupTemplateServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 企业微信群发任务
 * Class WorkGroupMsgJob
 * @package app\jobs\work
 */
class WorkGroupMsgJob extends BaseJobs
{

    use QueueTrait;

    /**
     * @return mixed
     */
    protected static function queueName()
    {
        return config('queue.connections.redis.batch_queue');
    }

    public function batch($id, $userId, $count)
    {
        /** @var WorkGroupTemplateServices $service */
        $service = app()->make(WorkGroupTemplateServices::class);
        return $service->batch((int)$id, $userId, (int)$count);
    }

    /**
     * 获取群发成员发送任务列表
     * @param $type
     * @param $msgid
     * @param $cursor
     * @return mixed
     */
    public function getTaks($type, $msgid, $cursor)
    {
        /** @var WorkGroupMsgTaskServices $service */
        $service = app()->make(WorkGroupMsgTaskServices::class);
        return $service->getTaks($type, $msgid, $cursor);
    }

    /**
     * @param $type
     * @param $userid
     * @param $msgid
     * @param $cursor
     * @return bool
     */
    public function getSendResult($type, $userid, $msgid, $cursor)
    {
        /** @var WorkGroupMsgSendResultServices $service */
        $service = app()->make(WorkGroupMsgSendResultServices::class);
        return $service->getSendResult($type, $userid, $msgid, $cursor);
    }
}
