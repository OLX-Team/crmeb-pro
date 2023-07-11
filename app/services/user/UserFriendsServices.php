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

namespace app\services\user;


use app\dao\user\UserFriendsDao;
use app\services\BaseServices;
use app\services\user\level\SystemUserLevelServices;
use crmeb\traits\ServicesTrait;

/**
 * 获取好友列表
 * Class UserFriendsServices
 * @package app\services\user
 */
class UserFriendsServices extends BaseServices
{

    use ServicesTrait;

    public function __construct(UserFriendsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存好友关系
     * @param int $uid
     * @param int $friends_uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveFriend(int $uid, int $friends_uid)
    {
        $data = [
            'uid' => $uid,
            'friends_uid' => $friends_uid
        ];
        $userFriend = $this->dao->get($data);
        $res1 = true;
        if (!$userFriend) {
            $data['add_time'] = time();
            $res1 = $this->dao->save($data);
        }
        return $res1;
    }

    /**
     * 获取好友uids 我推广的 推广我的
     * @param int $uid
     * @return array
     */
    public function getFriendUids(int $uid)
    {
        $result = [];
        if ($uid) {
            $spread = $this->dao->getColumn(['uid' => $uid], 'friends_uid');
            $sup_spread = $this->dao->getColumn(['friends_uid' => $uid], 'uid');
            $result = array_unique(array_merge($spread, $sup_spread));
        }
        return $result;
    }

    /**
     * 获取好友
     * @param int $id
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFriendList(int $uid, string $field = 'uid,nickname,level,add_time')
    {
        $uids = $this->getFriendUids($uid);
        $list = [];
        $count = 0;
        if ($uids) {
            [$page, $limit] = $this->getPageValue();
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $list = $userServices->getList(['uid' => $uids], $field, $page, $limit);
            /** @var SystemUserLevelServices $systemLevelServices */
            $systemLevelServices = app()->make(SystemUserLevelServices::class);
            $systemLevelList = $systemLevelServices->getWhereLevelList([], 'id,name');
            if ($systemLevelList) $systemLevelList = array_combine(array_column($systemLevelList, 'id'), $systemLevelList);
            foreach ($list as &$item) {
                $item['type'] = $systemLevelList[$item['level']]['name'] ?? '暂无';
                $item['add_time'] = $item['add_time'] && is_numeric($item['add_time']) ? date('Y-m-d H:i:s', $item['add_time']) : '';
            }
            $count = $userServices->count(['uid' => $uids]);
        }

        return compact('list', 'count');
    }

}
