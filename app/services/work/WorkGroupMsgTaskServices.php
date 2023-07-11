<?php


namespace app\services\work;


use app\dao\work\WorkGroupMsgTaskDao;
use app\jobs\work\WorkGroupMsgJob;
use app\services\BaseServices;
use crmeb\services\wechat\Work;
use crmeb\traits\ServicesTrait;
use think\facade\Log;

/**
 * 企业微信群发消息任务
 * Class WorkGroupMsgTaskServices
 * @package app\services\work
 */
class WorkGroupMsgTaskServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkGroupmsgTaskServices constructor.
     * @param WorkGroupMsgTaskDao $dao
     */
    public function __construct(WorkGroupMsgTaskDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取成员列表
     * @param array $where
     * @return array
     */
    public function getTaksList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getDataList($where, ['*'], $page, $limit, 'send_time', [
            'member', 'sendResult' => function ($query) use ($where) {
                $query->whereIn('msg_id', $where['msg_id'])->field(['userid', 'count(*) as num_count'])->group('userid');
            }
        ]);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取群发成员发送任务列表
     * @param int $type
     * @param string $msgid
     * @param string|null $cursor
     * @return bool
     */
    public function getTaks(int $type, string $msgid, string $cursor = null)
    {
        try {
            $response = Work::getGroupmsgTask($msgid, 500, $cursor);

            $taskList = $response['task_list'] ?? [];

            foreach ($taskList as $item) {
                $info = $this->dao->get(['msg_id' => $msgid, 'userid' => $item['userid']]);
                if (!$info) {
                    $info = $this->dao->save([
                        'msg_id' => $msgid,
                        'userid' => $item['userid'],
                        'status' => $item['status'],
                        'create_time' => time(),
                        'send_time' => $item['send_time'] ?? 0
                    ]);
                } else {
                    $info->status = $item['status'];
                    $info->send_time = $item['send_time'] ?? 0;
                    $info->save();
                }
                WorkGroupMsgJob::dispatchDo('getSendResult', [$type, $item['userid'], $msgid, null]);
            }

            if ($response['next_cursor']) {
                WorkGroupMsgJob::dispatchDo('getTaks', [$type, $msgid, $cursor]);
            }
            return true;
        } catch (\Throwable $e) {
            Log::error([
                'message' => '获取群发成员发送任务列表失败:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return false;
        }
    }

    /**
     * 统计群发模板数据
     * @param array $msgIds
     * @return array
     */
    public function getSendMsgStatistics(array $msgIds, int $type = 0)
    {
        $data = [];
        $data['user_count'] = $data['unuser_count'] = $data['external_user_count'] = $data['external_unuser_count'] = 0;
        $data['user_count'] = $this->dao->count(['msg_id' => $msgIds, 'status' => 2]);
        $data['unuser_count'] = $this->dao->count(['msg_id' => $msgIds, 'status' => 0]);
        /** @var WorkGroupMsgSendResultServices $service */
        $service = app()->make(WorkGroupMsgSendResultServices::class);
        if ($type) {
            $data['external_user_count'] = $service->count(['msg_id' => $msgIds, 'notChatId' => true, 'status' => 1]);
            $data['external_unuser_count'] = $service->count(['msg_id' => $msgIds, 'notChatId' => true, 'status' => [0, 2, 3]]);
        } else {
            $data['external_user_count'] = $service->count(['msg_id' => $msgIds, 'status' => 1]);
            $data['external_unuser_count'] = $service->count(['msg_id' => $msgIds, 'status' => [0, 2, 3]]);
        }
        return $data;
    }
}
