<?php


namespace app\services\work;


use app\dao\work\WorkMomentSendResultDao;
use app\jobs\work\WorkMomentJob;
use app\services\BaseServices;
use crmeb\services\wechat\Work;
use crmeb\traits\ServicesTrait;
use think\facade\Log;

/**
 * Class WorkMomentSendResultServices
 * @package app\services\work
 * @method getMomentList(array $where, array $field = ['*'])
 */
class WorkMomentSendResultServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkMomentSendResultServices constructor.
     * @param WorkMomentSendResultDao $dao
     */
    public function __construct(WorkMomentSendResultDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $where
     * @return array
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getDataList($where, ['*'], $page, $limit, 'create_time', ['member']);
        $externalUserid = [];
        foreach ($list as $item) {
            $externalUserid = array_merge($externalUserid, $item['external_userid']);
        }
        if ($externalUserid) {
            $externalUserid = array_merge(array_unique(array_filter($externalUserid)));
            /** @var WorkClientServices $clientService */
            $clientService = app()->make(WorkClientServices::class);
            $externalUserList = $clientService->getColumn([
                ['external_userid', 'in', $externalUserid]
            ], 'name', 'external_userid');
            foreach ($list as &$item) {
                $item['external_user_list'] = [];
                foreach ($externalUserList as $k => $v) {
                    if (in_array($k, $item['external_userid'])) {
                        $item['external_user_list'][] = $v;
                    }
                }
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取任务执行详情
     * @param string $momentId
     * @param string $cursor
     * @return bool
     */
    public function getTaskInfo(string $momentId, string $cursor = '')
    {
        try {
            $resInfo = Work::getMomentTaskInfo($momentId, $cursor, 100);
            $taskList = $resInfo['task_list'] ?? [];
            foreach ($taskList as $item) {
                $res = $this->dao->get(['user_id' => $item['userid'], 'moment_id' => $momentId]);
                if (!$res) {
                    $res = $this->dao->save([
                        'user_id' => $item['userid'],
                        'status' => $item['publish_status'],
                        'moment_id' => $momentId,
                        'create_time' => time()
                    ]);
                } else {
                    if ($res->status != $item['publish_status']) {
                        $res->status = $item['publish_status'];
                        $res->save();
                    }
                }
                WorkMomentJob::dispatchDo('getCustomerPage', [$res->id, $momentId, $item['userid'], '']);

            }

            if ($resInfo['next_cursor']) {
                WorkMomentJob::dispatchDo('getTaskPage', [$momentId, $resInfo['next_cursor']]);
            }
        } catch (\Throwable $e) {
            Log::error([
                'message' => '获取任务执行详情失败:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
        return true;
    }

    /**
     * 获取某个人发送朋友圈详情
     * @param int $id
     * @param string $momentId
     * @param string $userId
     * @param string $cursor
     * @return bool
     */
    public function getCustomerList(int $id, string $momentId, string $userId, string $cursor = '')
    {
        try {
            $resInfo = Work::getMomentCustomerList($momentId, $userId, $cursor, 1000);

            $externalUserids = array_column($resInfo['customer_list'], 'external_userid');

            if ($externalUserids) {
                $value = $this->dao->value(['id' => $id], 'external_userid');
                $value = $value ? (is_array($value) ? $value : json_decode($value, true)) : [];
                $value = array_merge($value, $externalUserids);
                $this->dao->update($id, ['external_userid' => $value]);
            }

            if ($resInfo['next_cursor']) {
                WorkMomentJob::dispatchDo('getCustomerPage', [$id, $momentId, $userId, $resInfo['next_cursor']]);
            }
        } catch (\Throwable $e) {
            Log::error([
                'message' => '获取某个人发送朋友圈详情失败:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }

        return true;
    }


}
