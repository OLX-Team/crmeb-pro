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


use app\dao\work\WorkMemberDao;
use app\jobs\work\WorkMemberJob;
use app\services\BaseServices;
use crmeb\services\wechat\config\WorkConfig;
use crmeb\services\wechat\Work;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;
use think\facade\Log;
use think\helper\Str;

/**
 * 企业微信成员
 * Class WorkMemberServices
 * @package app\services\work
 * @method getDataList(array $where, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = [])
 */
class WorkMemberServices extends BaseServices
{
    use ServicesTrait;

    const TABLE_FIELD = ['Name', 'MainDepartment', 'DirectLeader',
        'Mobile', 'Position', 'Gender', 'Email', 'BizMail', 'Status', 'Avatar', 'Alias',
        'Telephone', 'Address'];

    /**
     * WorkMemberServices constructor.
     * @param WorkMemberDao $dao
     */
    public function __construct(WorkMemberDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 员工列表
     * @param array $where
     * @return array
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getDataList($where, ['*'], $page, $limit, 'create_time', [
            'departmentRelation',
            'clientFollow' => function ($query) {
                $query->group('userid')->where('is_del_user', 0)->field(['count(*) as sum_follow', 'userid']);
            },
            'chat' => function ($query) {
                $query->group('userid')->field(['count(*) as sum_chat', 'userid']);
            },
        ]);
        $departmentIds = [];
        foreach ($list as &$item) {
            if (!empty($item['departmentRelation'])) {
                $item['departmentId'] = array_column($item['departmentRelation'], 'department');
            } else {
                $item['departmentId'] = [];
            }
            $departmentIds = array_merge($departmentIds, $item['departmentId']);
        }
        $departmentIds = array_merge(array_unique(array_filter($departmentIds)));
        if ($departmentIds) {
            /** @var WorkDepartmentServices $services */
            $services = app()->make(WorkDepartmentServices::class);
            $departmentList = $services->getColumn([
                ['department_id', 'in', $departmentIds],
            ], 'name', 'department_id');
            foreach ($list as &$item) {
                $department = [];
                foreach ($departmentList as $k => $v) {
                    if (in_array($k, $item['departmentId'])) {
                        $department[] = ['name' => $v, 'department' => $k];
                    }
                }
                $item['department_list'] = $department;
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 保存成员数据
     * @param array $members
     * @return bool
     */
    public function saveMember(array $members)
    {
        /** @var WorkConfig $config */
        $config = app()->make(WorkConfig::class);
        $corpId = $config->get('corpId');
        /** @var WorkDepartmentServices $departmentService */
        $departmentService = app()->make(WorkDepartmentServices::class);
        $defaultDepartment = $departmentService->value(['corp_id' => $corpId, 'parentid' => 0], 'department_id');

        $this->transaction(function () use ($members, $corpId, $defaultDepartment) {
            $data = [];
            $relation = [];
            $other = [];
            $userids = array_column($members, 'userid');
            foreach ($members as $member) {
                if (isset($member['english_name'])) {
                    unset($member['english_name']);
                }
                $address = '';
                if (isset($member['address'])) {
                    $address = $member['address'];
                    unset($member['address']);
                }
                $member['address'] = $address;
                if (isset($member['extattr']) && $member['extattr']) {
                    $other[$member['userid']] = [
                        'extattr' => json_encode($member['extattr'] ?? []),
                        'external_profile' => json_encode($member['external_profile'] ?? []),
                    ];
                }
                if (!empty($member['department'])) {
                    foreach ($member['department'] as $i => $department) {
                        $relation[$member['userid']][] = [
                            'department' => $member['department'][$i] ?? 0,
                            'srot' => $member['order'][$i] ?? 0,
                            'is_leader_in_dept' => $member['is_leader_in_dept'][$i] ?? 0
                        ];
                    }
                } else {
                    //写入默认部门
                    $relation[$member['userid']][] = ['department' => $defaultDepartment, 'srot' => 0, 'is_leader_in_dept' => 0];
                }
                $member['direct_leader'] = json_encode($member['direct_leader']);
                $member['is_leader'] = $member['isleader'];
                $member['corp_id'] = $corpId;
                if (isset($member['external_profile'])) {
                    unset($member['external_profile']);
                }
                unset($member['isleader'], $member['is_leader_in_dept'], $member['order'], $member['department'], $member['extattr']);
                if ($this->dao->count(['userid' => $member['userid'], 'corp_id' => $corpId])) {
                    $this->dao->update(['userid' => $member['userid']], $member);
                } else {
                    $member['create_time'] = time();
                    $data[] = $member;
                }
            }
            //写入成员数据
            if ($data) {
                $this->dao->saveAll($data);
            }
            $userList = $this->dao->getColumn([['userid', 'in', $userids], ['corp_id', '=', $corpId]], 'id', 'userid');
            $userValueAll = array_values($userList);
            //写入关联数据
            if (count($relation)) {
                /** @var WorkMemberRelationServices $relationService */
                $relationService = app()->make(WorkMemberRelationServices::class);
                $relationService->delete([['member_id', 'in', $userValueAll]]);
                $saveRelation = [];
                foreach ($relation as $userid => $item) {
                    $memberId = $userList[$userid];
                    foreach ($item as $value) {
                        $saveRelation[] = [
                            'member_id' => $memberId,
                            'create_time' => time(),
                            'department' => $value['department'],
                            'srot' => $value['srot'],
                            'is_leader_in_dept' => $value['is_leader_in_dept'],
                        ];
                    }
                }
                $relationService->saveAll($saveRelation);
            }
            //写入其他数据
            if (count($other)) {
                /** @var WorkMemberOtherServices $otherService */
                $otherService = app()->make(WorkMemberOtherServices::class);
                $otherService->delete([['member_id', 'in', $userValueAll]]);
                foreach ($other as $userid => &$item) {
                    $memberId = $userList[$userid];
                    $item['member_id'] = $memberId;
                }
                $otherService->saveAll($other);
            }
        });
        return true;
    }

    /**
     * 自动更新企业成员
     * @param int $departmentId
     */
    public function authUpdataMember(int $departmentId)
    {
        $res = Work::getDetailedDepartmentUsers($departmentId);
        $members = $res['userlist'] ?? [];
        $maxCount = 500;
        $sumCount = count($members);
        if ($sumCount > $maxCount) {
            $page = ceil($maxCount / $sumCount);
            for ($i = 1; $i < $page; $i++) {
                $res = collect($members)->slice($maxCount * $i, $maxCount)->toArray();
                WorkMemberJob::dispatchDo('save', [$res]);
            }
        } else {
            $this->saveMember($members);
        }
    }

    /**
     * 获取提交字段
     * @param array $payload
     * @return array
     */
    protected function getTableField(array $payload)
    {
        $data = [];
        foreach (self::TABLE_FIELD as $key) {
            $strKey = Str::snake($key);
            if (isset($payload[$strKey])) {
                $data[$strKey] = $payload[$strKey];
            }
        }
        return $data;
    }

    /**
     * 更新企业成员
     * @param array $payload
     * @return mixed
     */
    public function updateMember(array $payload)
    {
        $corpId = $payload['ToUserName'];
        $userId = $payload['UserID'];
        $updateData = $this->getTableField($payload);
        if (!empty($payload['NewUserID'])) {
            $updateData['userid'] = $payload['NewUserID'];
        }

        $memberInfo = Work::getMemberInfo($userId);
        if (0 !== $memberInfo['errcode']) {
            throw new ValidateException($memberInfo['errmsg']);
        }
        $extattr = $memberInfo['extattr'] ?? [];
        $externalProfile = $memberInfo['external_profile'] ?? [];
        unset($memberInfo['errcode'], $memberInfo['errmsg'], $memberInfo['department'],
            $memberInfo['order'], $memberInfo['is_leader_in_dept'], $memberInfo['extattr'],
            $memberInfo['external_profile']);
        $updateData = array_merge($updateData, $memberInfo);

        $memberId = $this->dao->value(['userid' => $userId], 'id');
        if ($memberId) {
            if ($updateData) {
                $dbCorpId = $this->dao->value(['userid' => $userId], 'corp_id');
                if (!$dbCorpId) {
                    $updateData['corp_id'] = $corpId;
                }
                $this->dao->update(['corp_id' => $corpId, 'userid' => $userId], $updateData);
            }
        } else {
            if (!empty($payload['NewUserID'])) {
                $updateData['userid'] = $payload['NewUserID'];
            }
            $res = $this->dao->save($updateData);
            $memberId = $res->id;
        }
        /** @var WorkMemberRelationServices $relationServices */
        $relationServices = app()->make(WorkMemberRelationServices::class);
        $relationServices->saveMemberDepartment($memberId, $payload['IsLeaderInDept'] ?? '', $payload['Department'] ?? '');

        //写入其他数据
        if (!empty($extattr['attrs']) || !empty($externalProfile)) {
            /** @var WorkMemberOtherServices $otherService */
            $otherService = app()->make(WorkMemberOtherServices::class);
            $otherInfo = $otherService->get(['member_id' => $memberId]);
            if ($otherInfo) {
                $otherInfo->extattr = json_encode($extattr);
                $otherInfo->external_profile = json_encode($externalProfile);
                $otherInfo->save();
            } else {
                $otherService->save([
                    'member_id' => $memberId,
                    'extattr' => json_encode($extattr),
                    'external_profile' => json_encode($externalProfile),
                ]);
            }
        }

        return $memberId;
    }

    /**
     * 创建企业微信成员
     * @param array $payload
     * @return mixed
     */
    public function createMember(array $payload)
    {
        $corpId = $payload['ToUserName'];
        if (!$corpId) {
            /** @var WorkConfig $config */
            $config = app()->make(WorkConfig::class);
            $corpId = $config->get('corpId');
        }
        $userId = $payload['UserID'];
        $data = $this->getTableField($payload);
        $memberInfo = Work::getMemberInfo($userId);
        if (0 !== $memberInfo['errcode']) {
            throw new ValidateException($memberInfo['errmsg']);
        }
        $extattr = $memberInfo['extattr'] ?? [];
        $externalProfile = $memberInfo['external_profile'] ?? [];
        unset($memberInfo['errcode'], $memberInfo['errmsg'], $memberInfo['department'],
            $memberInfo['order'], $memberInfo['is_leader_in_dept'], $memberInfo['extattr'],
            $memberInfo['external_profile']);
        $data = array_merge($data, $memberInfo);
        $memberId = $this->dao->value(['userid' => $userId], 'id');
        if ($memberId) {
            if ($data) {
                $dbCorpId = $this->dao->value(['userid' => $userId], 'corp_id');
                if (!$dbCorpId) {
                    $data['corp_id'] = $corpId;
                }
                $this->dao->update(['userid' => $userId], $data);
            }
        } else {
            $data['corp_id'] = $corpId;
            $res = $this->dao->save($data);
            $memberId = $res->id;
        }

        //记录
        $isLeaderInDept = $payload['IsLeaderInDept'] ?? '';
        $department = $payload['Department'] ?? '';
        if (!$department && !$isLeaderInDept) {
            //写入主部门
            /** @var WorkDepartmentServices $departmentService */
            $departmentService = app()->make(WorkDepartmentServices::class);
            $id = $departmentService->value(['corp_id' => $corpId, 'parentid' => 0], 'department_id');
            if ($id) {
                $department = (string)$id;
                $isLeaderInDept = '0';
            }
        }
        /** @var WorkMemberRelationServices $relationServices */
        $relationServices = app()->make(WorkMemberRelationServices::class);
        $relationServices->saveMemberDepartment($memberId, $isLeaderInDept, $department);

        //写入其他数据
        if (!empty($extattr['attrs']) || !empty($externalProfile)) {
            /** @var WorkMemberOtherServices $otherService */
            $otherService = app()->make(WorkMemberOtherServices::class);
            $otherInfo = $otherService->get(['member_id' => $memberId]);
            if ($otherInfo) {
                $otherInfo->extattr = json_encode($extattr);
                $otherInfo->external_profile = json_encode($externalProfile);
                $otherInfo->save();
            } else {
                $otherService->save([
                    'member_id' => $memberId,
                    'extattr' => json_encode($extattr),
                    'external_profile' => json_encode($externalProfile),
                ]);
            }
        }

        return $memberId;
    }

    /**
     * 删除企业微信成员
     * @param string $corpId
     * @param string $userid
     */
    public function deleteMember(string $corpId, string $userid)
    {
        $memberId = $this->dao->value(['corp_id' => $corpId, 'userid' => $userid], 'id');
        if ($memberId) {
            $this->transaction(function () use ($memberId) {
                /** @var WorkMemberRelationServices $relationServices */
                $relationServices = app()->make(WorkMemberRelationServices::class);
                $relationServices->delete(['member_id' => $memberId]);
                /** @var WorkMemberOtherServices $otherServices */
                $otherServices = app()->make(WorkMemberOtherServices::class);
                $otherServices->delete(['member_id' => $memberId]);
                $this->dao->delete($memberId);
            });
        }
    }

    /**
     * 用户注销解绑用户
     * @param int $uid
     */
    public function unboundUser(int $uid)
    {
        try {
            $this->dao->update(['uid' => $uid], ['uid' => 0]);
        } catch (\Throwable $e) {
            Log::error([
                'message' => '解绑用户失败:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
}
