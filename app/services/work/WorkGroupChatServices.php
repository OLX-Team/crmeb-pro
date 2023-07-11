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


use app\dao\work\WorkGroupChatDao;
use app\jobs\user\UserLabelJob;
use app\jobs\work\WorkClientJob;
use app\jobs\work\WorkGroupChatJob;
use app\services\BaseServices;
use crmeb\services\wechat\config\WorkConfig;
use crmeb\services\wechat\Work;
use crmeb\traits\ServicesTrait;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;

/**
 * Class WorkGroupChatServices
 * @package app\services\work
 */
class WorkGroupChatServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkGroupChatServices constructor.
     * @param WorkGroupChatDao $dao
     */
    public function __construct(WorkGroupChatDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取客户群群发列表
     * @param array $where
     * @param array $with
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function groupChatList(array $where, array $with = [])
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->groupChat($where)->with($with)->page($page, $limit)->select()->toArray();
        $count = $this->dao->groupChat($where)->count();
        return compact('list', 'count');
    }

    /**
     * 群发详情列表
     * @param array $chatIds
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getOwnerChatList(array $chatIds, $status = null)
    {
        return $this->groupChatList(['chat_id' => $chatIds, 'status' => $status], [
            'sendResult' => function ($query) {
                $query->field(['chat_id', 'status']);
            }, 'chatMember' => function ($query) {
                $query->field(['count(*) as sun', 'group_id']);
            }
        ]);
    }

    /**
     * 同步企业微信客户群
     */
    public function authGroupChat()
    {
        $res = Work::getGroupChats();
        if (0 !== $res['errcode']) {
            throw new ValidateException($res['errmsg']);
        }
        $groupChatList = $res['group_chat_list'] ?? [];
        /** @var WorkConfig $confg */
        $config = app()->make(WorkConfig::class);
        $corpId = $config->get('corpId');
        if (!$corpId) {
            throw new ValidateException('请先配置企业微信ID');
        }
        if ($groupChatList) {
            $groupChat = [];
            foreach ($groupChatList as $item) {
                $item['corp_id'] = $corpId;
                if (($id = $this->dao->value(['chat_id' => $item['chat_id'], 'corp_id' => $corpId], 'id'))) {
                    $this->dao->update($id, $item);
                } else {
                    $item['create_time'] = time();
                    $groupChat[] = $item;
                }
            }

            if ($groupChat) {
                $this->dao->saveAll($groupChat);
            }

            foreach ($groupChatList as $item) {
                WorkGroupChatJob::dispatchDo('authChat', [$corpId, $item['chat_id']]);
            }
        }
    }

    /**
     * 保存群详情
     * @param string $corpId
     * @param string $chatId
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function saveWorkGroupChat(string $corpId, string $chatId)
    {
        $response = Work::getGroupChat($chatId);
        if (0 !== $response['errcode']) {
            throw new ValidateException($response['errmsg']);
        }
        $groupInfo = $response['group_chat'] ?? [];
        $groupInfo['admin_list'] = json_encode(array_column($groupInfo['admin_list'], 'userid'));
        $memberList = $groupInfo['member_list'] ?? [];
        unset($groupInfo['member_list']);
        $group = $this->dao->get(['corp_id' => $corpId, 'chat_id' => $chatId]);
        return $this->transaction(function () use ($chatId, $corpId, $group, $groupInfo, $memberList) {
            if ($group) {
                $group->name = $groupInfo['name'];
                $group->owner = $groupInfo['owner'];
                $group->notice = $groupInfo['notice'] ?? '';
                $group->group_create_time = $groupInfo['create_time'];
                $group->member_num = count($memberList);
                $group->save();
            } else {
                $group = $this->dao->save([
                    'corp_id' => $corpId,
                    'chat_id' => $chatId,
                    'name' => $groupInfo['name'],
                    'owner' => $groupInfo['owner'],
                    'notice' => $groupInfo['notice'] ?? '',
                    'member_num' => count($memberList),
                    'group_create_time' => $groupInfo['create_time'],
                    'status' => $groupInfo['status'] ?? 0,
                ]);
            }
            $this->saveMember($memberList, $group->id, $group->member_num);
            return $group->id;
        });
    }

    /**
     * @param array $where
     * @return array
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $where['timeKey'] = 'group_create_time';
        $list = $this->dao->getDataList($where, ['*'], $page, $limit, 'group_create_time', [
            'ownerInfo' => function ($query) {
                $query->field(['userid', 'name']);
            },
        ]);

        //提取管理员数据
        $adminUserId = [];
        foreach ($list as $item) {
            $adminUserId = array_merge($adminUserId, $item['admin_list'] ?? []);
        }
        $adminUserId = array_merge(array_unique(array_filter($adminUserId)));
        if ($adminUserId) {
            /** @var WorkMemberServices $memberService */
            $memberService = app()->make(WorkMemberServices::class);
            $adminUserList = $memberService->getColumn([
                ['userid', 'in', $adminUserId],
            ], 'name', 'userid');
            foreach ($list as &$item) {
                $newAdminUser = [];
                if (!empty($item['admin_list'])) {
                    foreach ($adminUserList as $key => $value) {
                        if (in_array($key, $item['admin_list'])) {
                            $newAdminUser[] = ['name' => $value, 'userid' => $key];
                        }
                    }
                }
                $item['admin_user_list'] = $newAdminUser;
            }
        }

        $count = $this->dao->count();
        return compact('list', 'count');
    }

    /**
     * 企业微信客户群变动
     * @param array $payload
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function updateGroupChat(array $payload)
    {
        $corpId = $payload['ToUserName'];
        $chatId = $payload['ChatId'];

        $groupInfo = $this->dao->get(['corp_id' => $corpId, 'chat_id' => $chatId]);
        if (!$groupInfo) {
            $groupId = $this->saveWorkGroupChat($corpId, $chatId);
            $groupInfo = $this->dao->get($groupId);
        }
        $response = Work::getGroupChat($chatId);
        if (0 !== $response['errcode']) {
            throw new ValidateException($response['errmsg'] ?? '企业微信查询群详情失败');
        }
        $groupChat = $response['group_chat'] ?? [];
        $memberList = $groupChat['member_list'];

        /** @var WorkGroupChatStatisticServices $statisticService */
        $statisticService = app()->make(WorkGroupChatStatisticServices::class);

        $this->transaction(function () use ($payload, $groupInfo, $groupChat, $memberList, $statisticService) {
            switch ($payload['UpdateDetail']) {
                case 'add_member':
                    $groupInfo->member_num++;
                    $this->saveMember($memberList, $groupInfo->id, $groupInfo->member_num, true);
                    $statisticService->saveOrUpdate($groupInfo->id, true, false, $groupInfo->member_num, $groupInfo->retreat_group_num);
                    break;
                case 'del_member':
                    $groupInfo->member_num--;
                    $groupInfo->retreat_group_num++;
                    $this->saveMember($memberList, $groupInfo->id, $groupInfo->member_num, false);
                    $statisticService->saveOrUpdate($groupInfo->id, false, true, $groupInfo->member_num, $groupInfo->retreat_group_num);
                    break;
                case 'change_owner':
                    $groupInfo->owner = $groupChat['owner'];
                    break;
                case 'change_name':
                    $groupInfo->name = $groupChat['name'];
                    break;
                case 'change_notice':
                    $groupInfo->notice = $groupChat['notice'];
                    break;
            }
            if (!empty($groupChat['admin_list'])) {
                $groupInfo->admin_list = json_encode(array_column($groupChat['admin_list'], 'userid'));
            }
            $groupInfo->save();
        });
    }

    /**
     * 保存群成员
     * @param array $memberList
     * @param int $groupId
     * @param int $sum
     * @param bool $plus
     * @return mixed
     */
    public function saveMember(array $memberList, int $groupId, int $sum = 0, bool $plus = false)
    {
        $data = [];
        /** @var WorkGroupChatMemberServices $chatMemberService */
        $chatMemberService = app()->make(WorkGroupChatMemberServices::class);
        $newUserIds = array_column($memberList, 'userid');
        $userids = $chatMemberService->getColumn(['group_id' => $groupId], 'userid');
        $unUserIds = array_diff($userids, $newUserIds);
        $labelList = [];
        foreach ($memberList as $item) {
            $item['group_id'] = $groupId;
            $state = $item['state'] ?? '';
            if (isset($item['state'])) {
                unset($item['state']);
            }
            $item['invitor_userid'] = $item['invitor']['userid'];
            $unionid = $item['unionid'] ?? '';
            unset($item['invitor'], $item['unionid']);
            if ($chatMemberService->count(['group_id' => $groupId, 'userid' => $item['userid']])) {
                $chatMemberService->update(['group_id' => $groupId, 'userid' => $item['userid']], [
                    'type' => $item['type'],
                    'unionid' => $unionid,
                    'chat_sum' => $sum,
                    'status' => 1,
                    'join_time' => $item['join_time'],
                    'join_scene' => $item['join_scene'],
                    'invitor_userid' => $item['invitor_userid'],
                    'group_nickname' => $item['group_nickname'],
                ]);
            } else {
                if ($state) {
                    $labelList[] = ['userid' => $item['userid'], 'type' => $item['type'], 'state' => $state];
                }
                $item['unionid'] = $unionid;
                $item['chat_sum'] = $sum;
                $item['state'] = $state;
                $item['create_time'] = time();
                $data[] = $item;
            }
        }
        if ($data) {
            $chatMemberService->saveAll($data);
            //如果没有客户信息同步客户信息
            /** @var WorkClientServices $clientService */
            $clientService = app()->make(WorkClientServices::class);
            $corpId = app()->make(WorkConfig::class)->get('corpId');
            foreach ($data as $item) {
                if (2 == $item['type'] && $clientService->count(['external_userid' => $item['userid']])) {
                    WorkClientJob::dispatchDo('saveClientInfo', [$corpId, $item['userid'], '']);
                }
            }
        }
        if ($unUserIds) {
            $chatMemberService->update([
                ['userid', 'in', $unUserIds]
            ], ['status' => 0]);
        }
        return true;
    }

    /**
     * 解散客户群
     * @param string $corpId
     * @param string $chatId
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function dismissGroupChat(string $corpId, string $chatId)
    {
        $groupChat = $this->dao->get(['corp_id' => $corpId, 'chat_id' => $chatId], ['id']);
        if (!$groupChat) {
            throw new ValidateException('没有查询到群');
        }
        return $this->transaction(function () use ($groupChat) {
            /** @var WorkGroupChatMemberServices $chatMemberService */
            $chatMemberService = app()->make(WorkGroupChatMemberServices::class);
            $chatMemberService->delete(['group_id' => $groupChat->id]);
            return $groupChat->delete();
        });
    }

    /**
     * 群统计
     * @param int $id
     * @param string $time
     * @return array
     */
    public function getChatStatistics(int $id, string $time)
    {
        /** @var WorkGroupChatMemberServices $chatMemberService */
        $chatMemberService = app()->make(WorkGroupChatMemberServices::class);
        $data = [
            'toDaySum' => $chatMemberService->getToDaySum($id),
            'toDayReturn' => $chatMemberService->getToDayReturn($id),
            'groupChatSum' => $this->dao->value(['id' => $id], 'member_num'),
            'groupChatReturnSum' => $this->dao->value(['id' => $id], 'retreat_group_num')
        ];
        $data['groupChatList'] = $chatMemberService->getChatMemberStatistics($id, 'join_time', ['count(*) as sum'], 1, $time);
        $data['groupChatReturnList'] = $chatMemberService->getChatMemberStatistics($id, 'join_time', ['count(*) as sum'], 0, $time);
        return $data;
    }

    /**
     * 群成员统计
     * @param int $id
     * @param string $time
     * @return array
     */
    public function getChatStatisticsList(int $id, string $time)
    {
        [$page, $limit] = $this->getPageValue();
        /** @var WorkGroupChatMemberServices $chatMemberService */
        $chatMemberService = app()->make(WorkGroupChatMemberServices::class);
        $newCount = $chatMemberService->getChatMemberStatisticsCount($id, 1, $time);
        $returnCount = $chatMemberService->getChatMemberStatisticsCount($id, 0, $time);
        $SumCount = $newCount > $returnCount ? $newCount : $returnCount;
        $groupChatList = $chatMemberService->getChatMemberStatistics($id, 'join_time', ['count(*) as sum', 'chat_sum', 'retreat_chat_num'], 1, $time, $page, $limit);
        $groupChatReturnList = $chatMemberService->getChatMemberStatistics($id, 'join_time', ['count(*) as retreat_sum'], 0, $time, $page, $limit);
        $count = ($rCount = count($groupChatReturnList)) > ($count = count($groupChatList)) ? $rCount : $count;
        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'time' => $groupChatList[$i]['time'] ?? $groupChatReturnList[$i]['time'] ?? '',
                'sum' => $groupChatList[$i]['sum'] ?? 0,
                'retreat_chat_num' => $groupChatList[$i]['retreat_chat_num'] ?? 0,
                'chat_sum' => $groupChatList[$i]['chat_sum'] ?? 0,
                'retreat_sum' => $groupChatReturnList[$i]['retreat_sum'] ?? 0,
            ];
        }
        return ['list' => $data, 'count' => $SumCount];
    }

    /**
     * @param string $chatId
     * @param string $corpId
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getGroupInfo(string $chatId, string $corpId)
    {
        $groupInfo = $this->dao->get(['chat_id' => $chatId, 'corp_id' => $corpId]);
        if (!$groupInfo) {
            throw new ValidateException('客户群未查到');
        }
        /** @var WorkGroupChatMemberServices $service */
        $service = app()->make(WorkGroupChatMemberServices::class);
        $groupInfo['todaySum'] = $service->getToDaySum($groupInfo->id);
        $groupInfo['todayReturnSum'] = $service->getToDayReturn($groupInfo->id);
        return $groupInfo->toArray();
    }
}
