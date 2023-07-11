<?php


namespace app\services\work;


use app\dao\work\WorkMomentDao;
use app\jobs\work\WorkMomentJob;
use app\services\BaseServices;
use crmeb\services\wechat\Work;
use crmeb\traits\service\ContactWayQrCode;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;
use think\facade\Log;

/**
 * 创建发布朋友圈
 * Class WorkMomentServices
 * @package app\services\work
 */
class WorkMomentServices extends BaseServices
{

    use ServicesTrait, ContactWayQrCode;

    /**
     * WorkMomentServices constructor.
     * @param WorkMomentDao $dao
     */
    public function __construct(WorkMomentDao $dao)
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
        $list = $this->dao->getDataList($where, ['*'], $page, $limit, 'create_time', [
            'sendResult' => function ($query) {
                $query->field(['status', 'moment_id']);
            }
        ]);
        $userIds = $userList = [];
        foreach ($list as $v) {
            $userIds = array_merge($userIds, $v['user_ids']);
        }
        if ($userIds) {
            $userIds = array_merge(array_unique(array_filter($userIds)));
            /** @var WorkMemberServices $service */
            $service = app()->make(WorkMemberServices::class);
            $userList = $service->getColumn([
                ['userid', 'in', $userIds]
            ], 'name', 'userid');
        }
        foreach ($list as &$item) {
            $item['user_count'] = $item['unuser_count'] = $userCount = $unuserCount = 0;
            if (!empty($item['sendResult'])) {
                foreach ($item['sendResult'] as $value) {
                    if ($value['status']) {
                        $userCount++;
                    } else {
                        $unuserCount++;
                    }
                }
            }
            $item['user_count'] = $userCount;
            $item['unuser_count'] = $unuserCount;
            $item['user_list'] = [];
            foreach ($userList as $k => $v) {
                if (in_array($k, $item['user_ids'])) {
                    $item['user_list'][] = $v;
                }
            }

        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 添加发送朋友圈任务
     * @param array $data
     * @return mixed
     */
    public function createMomentTask(array $data)
    {
        $this->checkWelcome($data['welcome_words'], 0);

        return $this->transaction(function () use ($data) {

            $res = $this->dao->save($data);
            //立即发送或者过了发送时间直接发送
            if (!$data['send_type'] || ($data['send_type'] == 1 && $data['send_time'] < time())) {
                $this->sendMomentTask($res->id, $data['welcome_words'], $data['type'] ? $data['user_ids'] : [], $data['type'] ? $data['client_tag_list'] : []);
            }
            return $res;
        });
    }

    /**
     * 发送
     * @param int $id
     * @param array $content
     * @param array $userIds
     * @param array $tags
     */
    public function sendMomentTask(int $id, array $content, array $userIds, array $tags = [])
    {

        $resTask = $this->addMomentTask($content, $userIds, $tags);

        $this->dao->update($id, ['jobid' => $resTask['jobid']]);

        WorkMomentJob::dispatchSece(60 * 3, 'task', [$resTask['jobid']]);
    }

    /**
     * 获取异步任务执行结果
     * @param string $jobId
     * @return bool
     */
    public function getTaskInfo(string $jobId)
    {
        try {

            $res = Work::getMomentTask($jobId);

            if ($res['status'] !== 3) {
                WorkMomentJob::dispatchSece(60 * 3, 'task', [$jobId]);
                return true;
            }
            $this->dao->update(['jobid' => $jobId], [
                'moment_id' => $res['result']['moment_id'],
                'invalid_sender_list' => isset($res['result']['invalid_sender_list']) ? json_encode($res['result']['invalid_sender_list']) : null,
                'invalid_external_contact_list' => isset($res['result']['invalid_external_contact_list']) ? json_encode($res['result']['invalid_external_contact_list']) : null,
            ]);

            //执行获取任务详情
            WorkMomentJob::dispatchDo('getTaskPage', [$res['result']['moment_id'], '']);

            return true;
        } catch (\Throwable $e) {
            Log::error([
                'message' => '获取异步任务结果失败:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    /**
     * 获取发送朋友圈详情
     * @param int $id
     * @return array
     */
    public function getMomentInfo(int $id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new ValidateException('没有查询到此朋友圈信息');
        }
        $userCount = $sendUserCount = $unSendUserCount = $externalUserCount = 0;
        if ($info->moment_id) {
            /** @var WorkMomentSendResultServices $service */
            $service = app()->make(WorkMomentSendResultServices::class);
            $userCount = $service->count(['moment_id' => $info->moment_id]);
            $sendUserCount = $service->count(['moment_id' => $info->moment_id, 'status' => 1]);
            $unSendUserCount = $service->count(['moment_id' => $info->moment_id, 'status' => 0]);
            $list = $service->getMomentList(['moment_id' => $info->moment_id, 'status' => 1], ['external_userid']);
            $externalUserid = [];
            foreach ($list as $item) {
                $externalUserid = array_merge($externalUserid, $item['external_userid']);
            }
            $externalUserCount = count($externalUserid);
        }
        $info = $info->toArray();
        $info['info'] = compact('userCount', 'sendUserCount', 'unSendUserCount', 'externalUserCount');
        return $info;
    }

    /**
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteMoment(int $id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new ValidateException('没有查询到此朋友圈信息');
        }

        /** @var WorkMomentSendResultServices $service */
        $service = app()->make(WorkMomentSendResultServices::class);
        return $this->transaction(function () use ($info, $id, $service) {
            if ($info->moment_id) {
                $service->delete(['moment_id' => $info->moment_id]);
            }
            return $info->delete();
        });
    }

    /**
     * 执行定时任务发送朋友圈
     */
    public function cronHandle()
    {
        $time = time();

        $list = $this->dao->getDataList(['send_time' => $time, 'send_type' => 1, 'jobid_null' => true]);

        foreach ($list as $item) {
            $this->sendMomentTask($item['id'], $item['welcome_words'], $item['type'] ? $item['user_ids'] : [], $item['type'] ? $item['client_tag_list'] : []);
        }
    }
}
