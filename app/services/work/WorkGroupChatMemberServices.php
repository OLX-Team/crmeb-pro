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


use app\dao\work\WorkGroupChatMemberDao;
use app\services\BaseServices;
use app\services\user\UserServices;
use crmeb\traits\ServicesTrait;

/**
 * 企业微信群成员
 * Class WorkGroupChatMemberServices
 * @package app\services\work
 * @method int getToDayReturn(int $groupId) 获取今日退群成员数
 * @method int getToDaySum(int $groupId) 获取今日新增成员数
 * @method getChatMemberStatistics(int $groupId, string $fromUnixtime = 'create_time', array $field = [], int $status = 1, string $time = '', int $page = 0, int $limit = 0) 获取人数统计
 * @method getChatMemberStatisticsCount(int $groupId, int $status = 1, string $time = '')
 */
class WorkGroupChatMemberServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkGroupChatMemberServices constructor.
     * @param WorkGroupChatMemberDao $dao
     */
    public function __construct(WorkGroupChatMemberDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取群成员
     * @param int $chatId
     * @param string $name
     * @return array
     */
    public function getChatMemberList(int $chatId, string $name = '')
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getDataList(['group_id' => $chatId, 'name_like' => $name, 'status' => 1], ['*'], $page, $limit, 'create_time', [
            'member' => function ($query) {
                $query->field(['name', 'userid', 'gender', 'avatar', 'id', 'mobile']);
            },
            'client' => function ($query) {
                $query->field(['name', 'external_userid', 'gender', 'avatar', 'id']);
            }
        ]);
        $clientId = $mobile = [];

        foreach ($list as &$item) {
            $item['group_chat_num'] = $this->dao->getChatSum($item['userid']);
            if ($item['group_chat_num'] > 0) {
                $item['group_chat_num']--;
            }
            if (isset($item['client']['id'])) {
                $clientId[] = $item['client']['id'];
            }
            if (isset($item['member'])) {
                $mobile[] = $item['member']['mobile'];
            }
        }
        /** @var WorkClientFollowServices $followService */
        $followService = app()->make(WorkClientFollowServices::class);
        $followList = $followService->getDataList(['client_id' => $clientId], ['id', 'client_id',], 0, 0, 'create_time', ['tags']);
        if ($followList) {
            foreach ($list as &$item) {
                $newTag = [];
                if (isset($item['client']['id'])) {
                    foreach ($followList as $value) {
                        if ($item['client']['id'] == $value['client_id'] && !empty($value['tags'])) {
                            $newTag = array_column($value['tags'], 'tag_name');
                        }
                    }
                    $item['tags'] = $newTag;
                }
            }
        }
        if ($mobile) {
            /** @var UserServices $userService */
            $userService = app()->make(UserServices::class);
            $userList = $userService->getList(['phone' => $mobile], 'phone,uid', 0, 0);
            if ($userList) {
                foreach ($list as &$item) {
                    $newTag = [];
                    if (isset($item['member'])) {
                        foreach ($userList as $value) {
                            if ($item['member']['mobile'] == $value['phone'] && !empty($value['label'])) {
                                $newTag = array_column($value['label'], 'label_name');
                            }
                        }
                        $item['tags'] = $newTag;
                    }
                }
            }
        }
        $count = $this->dao->count(['group_id' => $chatId, 'name_like' => $name, 'status' => 1]);
        return compact('list', 'count');
    }
}
