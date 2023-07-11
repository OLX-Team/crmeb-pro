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


use app\services\user\label\UserLabelServices;
use app\services\work\WorkGroupChatAuthServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 用户标签
 * Class UserLabelJob
 * @package app\jobs\user
 */
class UserLabelJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 同步后台标签到企业微信客户后台
     * @param $cateId
     * @param $groupName
     * @return bool
     */
    public function authLabel($cateId, $groupName)
    {
        /** @var UserLabelServices $make */
        $make = app()->make(UserLabelServices::class);
        return $make->addCorpClientLabel($cateId, $groupName);
    }

    /**
     * 同步企业微信客户标签到平台
     * @return bool
     */
    public function authWorkLabel()
    {
        /** @var UserLabelServices $make */
        $make = app()->make(UserLabelServices::class);
        return $make->authWorkLabel();
    }

    /**
     * 编辑客户标签
     * @param $userid
     * @param $groupAuthId
     * @return mixed
     */
    public function clientAddLabel($userid, $externalUserID, $groupAuthId)
    {
        /** @var WorkGroupChatAuthServices $chatAuthService */
        $chatAuthService = app()->make(WorkGroupChatAuthServices::class);
        return $chatAuthService->clientAddLabel((int)$groupAuthId, $userid, $externalUserID);
    }

}
