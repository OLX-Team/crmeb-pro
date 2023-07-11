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

namespace app\jobs\system;

use app\services\system\log\SystemLogServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 后台日志
 * Class AdminLogJob
 * @package app\jobs\system
 */
class AdminLogJob extends BaseJobs
{
    use QueueTrait;

    /**
     * @param $adminId
     * @param $adminName
     * @param $module
     * @param $rule
     * @param $ip
     * @param $type
     */
    public function doJob($adminId, $adminName, $module, $rule, $ip, $type)
    {
        try {
            /** @var SystemLogServices $services */
            $services = app()->make(SystemLogServices::class);
            $services->recordAdminLog((int)$adminId, $adminName, $module, $rule, $ip, $type);
        } catch (\Exception $e) {

        }
    }
}
