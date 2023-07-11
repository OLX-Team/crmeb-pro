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


use app\services\user\UserServices;
use app\services\user\UserSpreadServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 用户推广关系
 * Class UserSpreadJob
 * @package app\jobs\user
 */
class UserSpreadJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 记录用户推广关系
     * @param int $uid
     * @param int $spreadUid
     * @return bool
     */
    public function doJob(int $uid, int $spreadUid, int $spread_time = 0, int $admin_id = 0)
    {
        if (!$uid || !$spreadUid || $uid == $spreadUid) {
            return true;
        }
        try {
            /** @var UserSpreadServices $userSpreadServices */
            $userSpreadServices = app()->make(UserSpreadServices::class);
            //记录
            $userSpreadServices->setSpread($uid, $spreadUid, $spread_time, $admin_id);
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            //增加推广人数
            $userServices->incField($spreadUid, 'spread_count', 1);
        } catch (\Throwable $e) {
            Log::error('记录用户推广失败,失败原因:' . $e->getMessage());
        }
        return true;
    }
}
