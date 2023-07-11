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

namespace app\jobs\user;


use app\services\user\UserFriendsServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 用户好友关系
 * Class UserFriendsJob
 * @package app\jobs\user
 */
class UserFriendsJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 记录用户好友关系
     * @param int $uid
     * @param int $spreadUid
     * @return bool
     */
    public function doJob(int $uid, int $spreadUid)
    {
        if (!$uid || !$spreadUid || $uid == $spreadUid) {
            return true;
        }
        try {
            /** @var UserFriendsServices $serviceFriend */
            $serviceFriend = app()->make(UserFriendsServices::class);
            $serviceFriend->saveFriend($uid, $spreadUid);
        } catch (\Throwable $e) {
            Log::error('记录好友关系失败,失败原因:' . $e->getMessage());
        }
        return true;
    }
}
