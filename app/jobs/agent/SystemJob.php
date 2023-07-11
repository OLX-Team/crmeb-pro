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

namespace app\jobs\agent;


use app\services\agent\AgentManageServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 重置分销有效期
 * Class SystemJob
 * @package app\jobs
 */
class SystemJob extends BaseJobs
{

    use QueueTrait;


    public function resetSpreadTime()
    {
        /** @var AgentManageServices $agentManage */
        $agentManage = app()->make(AgentManageServices::class);
        $agentManage->resetSpreadTime();
    }
}
