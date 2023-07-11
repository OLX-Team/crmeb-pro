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

namespace app\jobs\pink;


use app\services\activity\combination\StorePinkServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;

/**
 * 分页处理拼团
 * Class AuthPinkFail
 * @package app\jobs\pink
 */
class AuthPinkFail extends BaseJobs
{
    use QueueTrait;

    /**
     * @return string
     */
    protected static function queueName()
    {
        return 'CRMEB_PRO_TASK';
    }

    /**
     * @param $page
     * @param $limit
     * @return bool
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function doJob($page, $limit)
    {
        /** @var StorePinkServices $service */
        $service = app()->make(StorePinkServices::class);
        return $service->statusPink((int)$page, (int)$limit);
    }

}
