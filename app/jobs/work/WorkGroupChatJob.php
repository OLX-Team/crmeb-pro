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

namespace app\jobs\work;


use app\services\work\WorkGroupChatServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 企业微信群
 * Class WorkGroupChatJob
 * @package app\jobs\work
 */
class WorkGroupChatJob extends BaseJobs
{

    use QueueTrait;

    public function authChat($corpId, $chatId)
    {
        /** @var WorkGroupChatServices $make */
        $make = app()->make(WorkGroupChatServices::class);
        return $make->saveWorkGroupChat($corpId, $chatId);
    }

}
