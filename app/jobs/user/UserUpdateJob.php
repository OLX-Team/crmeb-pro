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
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * Class UserUpdateJob
 * @package app\jobs\user
 */
class UserUpdateJob extends BaseJobs
{

    use QueueTrait;


    /**
     * @param $uid
     * @param $realName
     * @param $userPhone
     * @return bool
     */
    public function updateRealName($uid, $realName, $userPhone)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $userService->update(['uid' => $uid], ['real_name' => $realName, 'record_phone' => $userPhone]);

        return true;
    }

}
