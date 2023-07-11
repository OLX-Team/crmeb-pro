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


use app\dao\work\WorkClientDao;
use app\jobs\work\WorkClientJob;
use app\services\BaseServices;
use app\services\user\label\UserLabelRelationServices;
use app\services\user\UserServices;
use crmeb\services\wechat\config\WorkConfig;
use crmeb\services\wechat\WechatResponse;
use crmeb\services\wechat\Work;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;
use think\facade\Log;

/**
 * 企业微信客户
 * Class WorkClientServices
 * @package app\services\work
 * @method getClientUserIds(array $where) 获取客户userid
 */
class WorkClientServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkClientServices constructor.
     * @param WorkClientDao $dao
     */
    public function __construct(WorkClientDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $where
     * @param array $field
     * @param bool $isPage
     * @return array
     */
    public function getList(array $where, array $field = ['*'], bool $isPage = true)
    {
        $page = $limit = 0;
        if ($isPage) {
            [$page, $limit] = $this->getPageValue();
        }
        $list = $this->dao->getDataList($where, $field, $page, $limit, 'create_time', [
            'followOne' => function ($query) {
                $query->with([
                    'member' => function ($query) {
                        $query->field(['userid', 'id', 'name', 'main_department'])
                            ->with(['mastareDepartment']);
                    }
                ])->field(['userid', 'client_id', 'state', 'id']);
            },
            'follow' => function ($query) {
                $query->field(['id', 'client_id'])->with(['tags']);
            },
        ]);
        foreach ($list as &$item) {
            if (!empty($item['follow'])) {
                $tags = [];
                foreach ($item['follow'] as $value) {
                    if (!empty($value['tags'])) {
                        $tags = array_merge($tags, $value['tags']);
                    }
                }
                $newTags = [];
                foreach ($tags as $tag) {
                    if (!in_array($tag['tag_name'], array_column($newTags, 'tag_name'))) {
                        $newTags[] = $tag;
                    }
                }
                $item['followOne']['tags'] = $newTags;
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 自动同步客户
     * @param int $page
     * @param string $cursor
     * @return bool
     */
    public function authGetExternalcontact(int $page = 1, string $cursor = '')
    {
        /** @var WorkConfig $config */
        $config = app()->make(WorkConfig::class);
        $corpId = $config->get('corpId');
        if (!$corpId) {
            return true;
        }
        /** @var WorkMemberServices $memberService */
        $memberService = app()->make(WorkMemberServices::class);
        $menmberList = $memberService->getDataList(['corp_id' => $corpId], ['userid'], $page, 10);
        //没有数据就返回成功
        if (!$menmberList) {
            return true;
        }

        $userids = array_column($menmberList, 'userid');
        $response = Work::getBatchClientList($userids, $cursor);
        $externalContactList = $response['external_contact_list'] ?? [];
        $external = [];
        $followUser = [];//内部人员跟踪
        $externalUserids = [];//客户信息
        $this->transaction(function () use ($externalContactList, $corpId, $externalUserids, $followUser, $external) {
            foreach ($externalContactList as $item) {
                $externalContact = $item['external_contact'];
                $unionid = $externalContact['unionid'] ?? '';
                if (isset($externalContact['unionid'])) {
                    unset($externalContact['unionid']);
                }
                $corpName = $corpFullName = '';
                if (isset($externalContact['corp_name'])) {
                    $corpName = $externalContact['corp_name'];
                    unset($externalContact['corp_name']);
                }
                if (isset($externalContact['corp_full_name'])) {
                    $corpFullName = $externalContact['corp_full_name'];
                    unset($externalContact['corp_full_name']);
                }
                $externalContact['external_profile'] = json_encode($externalContact['external_profile'] ?? []);

                $followUserData = [
                    'userid' => $item['follow_info']['userid'],
                    'remark' => $item['follow_info']['remark'],
                    'description' => $item['follow_info']['description'],
                    'createtime' => $item['follow_info']['createtime'],
                    'remark_corp_name' => $item['follow_info']['remark_corp_name'] ?? '',
                    'remark_mobiles' => json_encode($item['follow_info']['remark_mobiles']),
                    'add_way' => $item['follow_info']['add_way'],
                    'oper_userid' => $item['follow_info']['oper_userid'] ?? '',
                    'create_time' => time(),
                    'tags' => [],
                ];

                if (!empty($item['follow_info']['tag_id'])) {
                    $tagRes = Work::getCorpTags($item['follow_info']['tag_id']);
                    foreach ($tagRes['tag_group'] ?? [] as $group) {
                        foreach ($group['tag'] as $tag) {
                            $followUserData['tags'][] = [
                                'group_name' => $group['group_name'],
                                'tag_name' => $tag['name'],
                                'type' => $tag['type'] ?? 1,
                                'tag_id' => $tag['id'],
                                'create_time' => time()
                            ];
                        }
                    }
                }

                $followUser[$externalContact['external_userid']] = $followUserData;
                $externalUserids[] = $externalContact['external_userid'];
                $externalUserid = $externalContact['external_userid'];
                $externalContact['corp_id'] = $corpId;
                $externalContact['unionid'] = $unionid;
                $externalContact['corp_name'] = $corpName;
                $externalContact['corp_full_name'] = $corpFullName;
                if ($this->dao->count(['external_userid' => $externalUserid, 'corp_id' => $corpId])) {
                    unset($externalContact['external_userid']);
                    $this->dao->update(['external_userid' => $externalUserid], $externalContact);
                } else {
                    $externalContact['create_time'] = time();
                    $externalContact['update_time'] = time();
                    $external[] = $externalContact;
                }
            }
            if ($external) {
                $this->dao->saveAll($external);
            }
            $clientList = $this->dao->getColumn([['external_userid', 'in', $externalUserids], ['corp_id', '=', $corpId]], 'id', 'external_userid');
            /** @var WorkClientFollowServices $followService */
            $followService = app()->make(WorkClientFollowServices::class);
            if ($followUser) {
                /** @var WorkClientFollowTagsServices $tagService */
                $tagService = app()->make(WorkClientFollowTagsServices::class);
                foreach ($followUser as $userid => $items) {
                    $items['client_id'] = $clientList[$userid];
                    if (($id = $followService->value(['client_id' => $clientList[$userid], 'userid' => $userid], 'id'))) {
                        $followService->update($id, [
                            'remark' => $items['remark'],
                            'description' => $items['description'],
                            'createtime' => $items['createtime'],
                            'remark_corp_name' => $items['remark_corp_name'],
                            'remark_mobiles' => $items['remark_mobiles'],
                            'add_way' => $items['add_way'],
                            'oper_userid' => $items['oper_userid'],
                        ]);
                    } else {
                        $res = $followService->save($items);
                        $id = $res->id;
                    }
                    if (!empty($items['tags'])) {
                        $tagService->delete(['follow_id' => $id]);
                        foreach ($items['tags'] as &$tag) {
                            $tag['follow_id'] = $id;
                        }
                        $tagService->saveAll($items['tags']);
                    }
                }
            }
        });

        if (count($externalContactList) >= 100) {
            WorkClientJob::dispatchDo('authClient', [$page, $response['next_cursor'] ?? '']);
        } else {
            WorkClientJob::dispatchDo('authClient', [$page + 1, '']);
        }
        return true;
    }


    public function saveClientTags(array $tagGroup)
    {

    }

    /**
     * 创建客户
     * @param array $payload
     * @return mixed
     */
    public function createClient(array $payload)
    {
        $corpId = $payload['ToUserName'];//企业id
        $externalUserID = $payload['ExternalUserID'];//外部企业userid
        $state = $payload['State'] ?? '';//扫码值
        $userId = $payload['UserID'];//成员userid

        //保存客户
        $clientId = $this->saveOrUpdateClient($corpId, $externalUserID, $userId);

        //发送欢迎语
        try {
            event('work.welcome', [$payload['WelcomeCode'], $state, $clientId, $userId]);
        } catch (\Throwable $e) {
            Log::error([
                'message' => '发送欢迎语失败：' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        //设置欢客户标签
        try {
            event('work.label', [$state, $userId, $externalUserID]);
        } catch (\Throwable $e) {
            Log::error([
                'message' => '设置欢客户标签失败：' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        //关联客户与商城用户
        try {
            event('work.user', [$clientId]);
        } catch (\Throwable $e) {
            Log::error([
                'message' => '关联客户与商城用户失败：' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        return $clientId;
    }

    /**
     * 更新客户信息
     * @param array $payload
     * @return mixed
     */
    public function updateClient(array $payload)
    {
        $corpId = $payload['ToUserName'];
        $externalUserID = $payload['ExternalUserID'];
        $userId = $payload['UserID'];//成员serid

        $clientId = $this->saveOrUpdateClient($corpId, $externalUserID, $userId);

        //关联客户与商城用户
        try {
            event('work.user', [$clientId]);
        } catch (\Throwable $e) {
            Log::error([
                'message' => '关联客户与商城用户失败：' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        return $clientId;
    }

    /**
     * 企业成员删除客户
     * @param array $payload
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteClient(array $payload)
    {
        $corpId = $payload['ToUserName'];
        $externalUserID = $payload['ExternalUserID'];
        $userId = $payload['UserID'];//成员serid
        $clientInfo = $this->dao->get(['external_userid' => $externalUserID, 'corp_id' => $corpId], ['id']);
        if ($clientInfo) {
            $this->transaction(function () use ($clientInfo, $userId) {
                $this->dao->destroy($clientInfo->id);
                /** @var WorkClientFollowServices $followService */
                $followService = app()->make(WorkClientFollowServices::class);
                $followService->update(['client_id' => $clientInfo->id, 'userid' => $userId], ['is_del_user' => 1]);
            });
        }
        return true;
    }

    /**
     * 客户删除企业微信成员
     * @param array $payload
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteFollowClient(array $payload)
    {
        $corpId = $payload['ToUserName'];
        $externalUserID = $payload['ExternalUserID'];
        $userId = $payload['UserID'];//成员serid
        $clientInfo = $this->dao->get(['external_userid' => $externalUserID, 'corp_id' => $corpId], ['id']);
        /** @var WorkClientFollowServices $followService */
        $followService = app()->make(WorkClientFollowServices::class);
        if ($clientInfo) {
            $followService->update(['client_id' => $clientInfo->id, 'userid' => $userId], ['is_del_user' => 1]);
        }
        return true;
    }

    /**
     * 更新或者添加客户信息
     * @param string $corpId
     * @param string $externalUserID
     * @param string $userId
     * @return mixed
     */
    public function saveOrUpdateClient(string $corpId, string $externalUserID, string $userId)
    {
        $response = Work::getClientInfo($externalUserID);
        $externalContact = $response['external_contact'] ?? [];
        $followUser = $response['follow_user'] ?? [];
        $res = true;
        $externalContact['corp_id'] = $corpId;
        $externalContact['external_profile'] = json_encode($externalContact['external_profile'] ?? []);
        $clientId = $this->dao->value(['external_userid' => $externalContact['external_userid'], 'corp_id' => $corpId], 'id');

        try {
            $clientId = $this->transaction(function () use ($userId, $res, $clientId, $externalContact, $followUser) {
                if ($clientId) {
                    $this->dao->update($clientId, $externalContact);
                } else {
                    $res = $this->dao->save($externalContact);
                    $clientId = $res->id;
                }
                $userids = [];
                $res1 = false;
                foreach ($followUser as &$item) {
                    $item['create_time'] = time();
                    if ($userId === $item['userid']) {
                        $res1 = true;
                    }
                    $userids[] = $item['userid'];
                    $item['client_id'] = $clientId;
                    if (isset($item['wechat_channels'])) {
                        unset($item['wechat_channels']);
                    }
                }
                if (!$res1 && $userId) {
                    $followUser[] = [
                        'client_id' => $clientId,
                        'userid' => $userId,
                        'createtime' => time(),
                        'tags' => []
                    ];
                }
                //添加了此外部联系人的企业成员
                if ($followUser) {
                    /** @var WorkClientFollowServices $followService */
                    $followService = app()->make(WorkClientFollowServices::class);
                    /** @var WorkClientFollowTagsServices $tagService */
                    $tagService = app()->make(WorkClientFollowTagsServices::class);
                    foreach ($followUser as $item) {
                        if (($id = $followService->value(['client_id' => $clientId, 'userid' => $item['userid']], 'id'))) {
                            $followService->update($id, [
                                'remark' => $item['remark'],
                                'description' => $item['description'],
                                'remark_corp_name' => $item['remark_corp_name'] ?? '',
                                'add_way' => $item['add_way'] ?? '',
                                'oper_userid' => $item['oper_userid'] ?? '',
                            ]);
                        } else {
                            $id = $followService->save($item);
                        }
                        $tagService->delete(['follow_id' => $id]);
                        if (!empty($item['tags'])) {
                            $tagsNews = [];
                            foreach ($item['tags'] as $tag) {
                                $tag['follow_id'] = $id;
                                $tagsNews[] = $tag;
                            }
                            $tagService->saveAll($tagsNews);
                        }
                    }
                }
                if (!$res) {
                    throw new ValidateException('保存失败');
                }
                return $clientId;
            });

        } catch (\Throwable $e) {
            Log::error([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return $clientId;
    }

    /**
     * @param string $userid
     * @param array $clientInfo
     * @return array
     */
    public function getClientInfo(string $userid, array $clientInfo)
    {
        $clientInfo['userInfo'] = [];
        if ($clientInfo['uid']) {
            /** @var UserServices $make */
            $make = app()->make(UserServices::class);
            $userInfo = $make->get($clientInfo['uid'], ['*'], ['label', 'userGroup', 'spreadUser']);
            if ($userInfo) {
                $clientInfo['userInfo'] = $userInfo->toArray();
                $clientInfo['userInfo']['birthday'] = $clientInfo['userInfo']['birthday'] ? date('Y-m-d', $clientInfo['userInfo']['birthday']) : '';
            }
        }
        return $clientInfo;
    }

    /**
     * 异步批量设置标签
     * @param array $addTag
     * @param array $removeTag
     * @param array $userId
     * @param array $where
     * @param int $isAll
     * @return bool
     */
    public function synchBatchLabel(array $addTag, array $removeTag, array $userId, array $where, int $isAll = 0)
    {
        if ($isAll) {
            $clientList = $this->dao->getDataList($where, ['external_userid', 'id', 'unionid', 'uid'], 0, 0, null, ['followOne']);
        } else {
            $clientList = $this->dao->getDataList(['external_userid' => $userId], ['external_userid', 'id', 'unionid', 'uid'], 0, 0, null, ['followOne']);
        }
        $batchClient = [];
        foreach ($clientList as $item) {
            if (!empty($item['followOne'])) {
                $batchClient[] = [
                    'external_userid' => $item['external_userid'],
                    'userid' => $item['followOne']['userid'],
                    'add_tag' => $addTag,
                    'remove_tag' => $removeTag,
                ];
            }
        }
        if ($batchClient) {
            foreach ($batchClient as $item) {
                WorkClientJob::dispatchDo('setLabel', [$item]);
            }
        }
        return true;
    }

    /**
     * 设置客户标签
     * @param array $markTag
     * @return WechatResponse|false
     */
    public function setClientMarkTag(array $markTag)
    {
        try {
            $res = Work::markTags($markTag['userid'], $markTag['external_userid'], $markTag['add_tag'], $markTag['remove_tag']);
            $res = new WechatResponse($res);
            //同步标签后同步用户信息
            /** @var WorkConfig $config */
            $config = app()->make(WorkConfig::class);
            $corpId = $config->get('corpId');
            WorkClientJob::dispatchSece(2, 'saveClientInfo', [$corpId, $markTag['external_userid'], $markTag['userid']]);
            return $res;
        } catch (\Throwable $e) {
            Log::error([
                'message' => '修改客户标签发生错误：' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    /**
     * 查找成员下附带的客户人数
     * @param array $where
     * @return int
     */
    public function getUserIdsByCount(array $where)
    {
        if ($where['is_all']) {
            unset($where['time'], $where['label'], $where['notLabel']);
        }
        $where['timeKey'] = 'create_time';
        return $this->dao->getClientCount($where);
    }

    /**
     * 解绑用户
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
