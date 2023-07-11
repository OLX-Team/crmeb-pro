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

namespace app\services\work;


use app\dao\work\WorkGroupTemplateDao;
use app\jobs\work\WorkGroupMsgJob;
use app\services\BaseServices;
use crmeb\services\wechat\WechatResponse;
use crmeb\traits\service\ContactWayQrCode;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;
use think\facade\Event;
use think\facade\Log;

/**
 * 企业微信群发模板
 * Class WorkGroupTemplateServices
 * @package app\services\work
 */
class WorkGroupTemplateServices extends BaseServices
{

    use ContactWayQrCode, ServicesTrait;

    /**
     * WorkGroupTemplateServices constructor.
     * @param WorkGroupTemplateDao $dao
     */
    public function __construct(WorkGroupTemplateDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取群聊模板列表
     * @param array $where
     * @return array
     */
    public function getGroupTemplate(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getDataList($where, ['*'], $page, $limit, 'create_time', ['msgIds']);
        /** @var WorkGroupMsgTaskServices $taskService */
        $taskService = app()->make(WorkGroupMsgTaskServices::class);
        foreach ($list as &$item) {
            $item['user_count'] = $item['unuser_count'] = $item['external_user_count'] = $item['external_unuser_count'] = 0;
            if (!empty($item['msgIds'])) {
                $msgIds = [];
                foreach ($item['msgIds'] as $value) {
                    $msgIds[] = $value['msg_id'];
                    if ($value['msg_id']) {
                        WorkGroupMsgJob::dispatchDo('getTaks', [(int)$item['type'], $value['msg_id'], null]);
                    }
                }
                $item = array_merge($item, $taskService->getSendMsgStatistics($msgIds, (int)$item['type']));
            }
        }

        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 创建或者修改
     * @param array $data
     * @param int $id
     * @return mixed
     */
    public function saveGroupTemplate(array $data)
    {
        $this->checkWelcome($data['welcome_words'], 0);

        $failList = $this->transaction(function () use ($data) {
            $res = $this->dao->save($data);
            //立即发送或者选择的时间小于当前时间
            if (!$data['template_type'] || ($data['template_type'] == 1 && $data['send_time'] < time())) {
                if ($data['type']) {
                    WorkGroupMsgJob::dispatchDo('batch', [$res->id, '', 0]);
                } else {
                    foreach ($data['userids'] as $key => $userid) {
                        WorkGroupMsgJob::dispatchDo('batch', [$res->id, $userid, $key + 1]);
                    }
                }
            }

            return $failList ?? [];
        });

        return $failList;
    }

    /**
     * 批量发送
     * @param int $id
     * @param string $userId
     * @param int $count
     * @return bool
     */
    public function batch(int $id, string $userId, int $count)
    {
        try {
            $groupTempInfo = $this->dao->get($id);
            if (!$groupTempInfo) {
                return true;
            }
            if ($groupTempInfo->send_type == 1) {
                return true;
            }
            $groupTempInfo = $groupTempInfo->toArray();
            $externalUserid = [];
            if (!$groupTempInfo['type']) {
                /** @var WorkClientServices $service */
                $service = app()->make(WorkClientServices::class);
                $where = ['userid' => $userId];
                if ($groupTempInfo['client_type']) {
                    //条件筛选
                    if ($groupTempInfo['where_time']) {
                        $where['time'] = $groupTempInfo['where_time'];
                        $where['timeKey'] = 'create_time';
                    }
                    if ($groupTempInfo['where_label']) {
                        $where['label'] = $groupTempInfo['where_label'];
                    }
                    if ($groupTempInfo['notLabel']) {
                        $where['notLabel'] = $groupTempInfo['where_not_label'];
                    }
                }
                $externalUserid = $service->getClientUserIds($where);
            }
            $this->sendTask($id, $externalUserid, $groupTempInfo, $count);
            return true;
        } catch (\Throwable $e) {
            Log::error([
                'message' => '创建群发任务失败:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            //记录发送失败原因
            try {
                $this->dao->update($id, ['send_type' => -1, 'fail_message' => '创建群发任务失败:' . $e->getMessage()]);
            } catch (\Throwable $e) {
            }

            return true;
        }
    }

    /**
     * @param int $id
     * @param array $externalUserid
     * @param array $data
     * @param int $count
     */
    public function sendTask(int $id, array $externalUserid, array $data, int $count)
    {
        $update = [];
        /** @var WorkGroupMsgRelationServices $msgRelationService */
        $msgRelationService = app()->make(WorkGroupMsgRelationServices::class);
        if ($data['type']) {
            //群主群发
            $sendWelcomeData = [];
            foreach ($data['userids'] as $item) {
                $sendWelcomeData[] = $this->sendMsgTemplate([], $data['welcome_words'], 'group', $item);
            }
            $failList = [];
            $msgIdData = [];
            foreach ($sendWelcomeData as $item) {
                $failList = array_merge($failList, $item['fail_list']);
                $msgIdData[] = $item['msgid'];
            }
            $update['send_type'] = 1;
            if ($failList) {
                $update['fail_external_userid'] = json_encode($failList);
            }
            if ($update) {
                $this->dao->update($id, $update);
            }
            $msgRelation = [];
            foreach ($msgIdData as $msgId) {
                $msgRelation[] = ['template_id' => $id, 'msg_id' => $msgId];
                WorkGroupMsgJob::dispatchSece(60, 'getTaks', [(int)$data['type'], $msgId, null]);
            }
            $msgRelationService->saveAll($msgRelation);
        } else {
            //成员群发
            $sendTemplateWelcome = $this->sendMsgTemplate($externalUserid, $data['welcome_words'], 'single');
            $failList = $sendTemplateWelcome['fail_list'] ?? [];
            if ($failList) {
                $update['fail_external_userid'] = json_encode($failList);
            }
            if ($count == count($data['userids'])) {
                $update['send_type'] = 1;
            } else {
                $update['send_type'] = 2;
            }
            if ($update) {
                $this->dao->update($id, $update);
            }
            $msgRelationService->save(['template_id' => $id, 'msg_id' => $sendTemplateWelcome['msgid']]);
            WorkGroupMsgJob::dispatchSece(60, 'getTaks', [(int)$data['type'], $sendTemplateWelcome['msgid'], null]);
        }
    }

    /**
     * 群发任务详情
     * @param int $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGroupTemplateInfo(int $id)
    {
        $info = $this->dao->get($id, ['*'], ['msgIds']);

        if (!$info) {
            throw new ValidateException('没有查到此数据');
        }
        $msgIds = [];
        $info = $info->toArray();
        if (!empty($info['msgIds'])) {
            $msgIds = array_column($info['msgIds'], 'msg_id');
        }
        $info['user_count'] = $info['unuser_count'] = $info['external_user_count'] = $info['external_unuser_count'] = 0;
        $info['user_list'] = [];
        if ($info['userids']) {
            /** @var WorkMemberServices $service */
            $service = app()->make(WorkMemberServices::class);
            $info['user_list'] = $service->getColumn([
                ['userid', 'in', $info['userids']]
            ], 'userid,name');
        }
        if ($msgIds) {
            /** @var WorkGroupMsgTaskServices $taskService */
            $taskService = app()->make(WorkGroupMsgTaskServices::class);
            $info = array_merge($info, $taskService->getSendMsgStatistics($msgIds, (int)$info['type']));
        }

        return $info;
    }

    /**
     * 删除群发模板
     * @param int $id
     * @return bool
     */
    public function deleteGroupTemplate(int $id)
    {
        /** @var WorkGroupMsgTaskServices $taskService */
        $taskService = app()->make(WorkGroupMsgTaskServices::class);
        /** @var WorkGroupMsgRelationServices $msgRelationService */
        $msgRelationService = app()->make(WorkGroupMsgRelationServices::class);
        /** @var WorkGroupMsgSendResultServices $sendResultService */
        $sendResultService = app()->make(WorkGroupMsgSendResultServices::class);
        $msgIds = $msgRelationService->getColumn(['template_id' => $id], 'msg_id');
        $this->transaction(function () use ($id, $msgRelationService, $msgIds, $taskService, $sendResultService) {
            $this->dao->delete($id);
            if ($msgIds) {
                $taskService->delete(['msg_id' => $msgIds]);
                $sendResultService->delete(['msg_id' => $msgIds]);
            }
        });
        return true;
    }

    /**
     * 执行定时发送群发内容
     */
    public function cornHandle()
    {
        $time = time();
        $list = $this->dao->getDataList(['send_time' => $time, 'send_type' => 0], ['*'], 0, 0, null, ['msgIds']);
        foreach ($list as $item) {
            if ($item['type']) {
                WorkGroupMsgJob::dispatchDo('batch', [$item['id'], '', 0]);
            } else {
                foreach ($item['userids'] as $count => $userid) {
                    WorkGroupMsgJob::dispatchDo('batch', [$item['id'], $userid, $count + 1]);
                }
            }
        }
    }

    /**
     * 发送应用消息
     * @param int $id
     * @param string $userid
     * @param string $sendTime
     * @return WechatResponse
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendMessage(int $id, string $userid, string $sendTime)
    {
        if ($id) {
            $template = $this->dao->get(['id' => $id], ['userids', 'type', 'name', 'create_time']);
            if (!$template) {
                throw new ValidateException('没有查到群发模板');
            }
            $userids = $template['userids'];
            $task = $template['type'] ? '客户群群发任务' : '客户群发任务';
            $text = "【任务提醒】有新的任务啦！\n" .
                "任务类型：{$task}\n" .
                "任务名称：{$template['name']}\n" .
                "创建时间：{$template['create_time']}\n" .
                "可前往【群发助手】中确认发送，记得及时完成哦\n";
        } else {
            $userids = [$userid];
            $text = "【任务提醒】有新的任务啦！\n" .
                "任务类型：客户群发任务\n" .
                "创建时间：{$sendTime}\n" .
                "可前往【群发助手】中确认发送，记得及时完成哦\n";
        }

        $res = Event::until('work.message', [
            'text', $text, ['toUser' => $userids], []
        ]);
        if ($res === false) {
            throw new ValidateException('发送消息失败');
        }
    }

}
