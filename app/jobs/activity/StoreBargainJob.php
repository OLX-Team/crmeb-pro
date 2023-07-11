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

namespace app\jobs\activity;


use app\services\activity\bargain\StoreBargainServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 营销：砍价
 * Class StoreBargainJob
 * @package app\jobs\activity
 */
class StoreBargainJob extends BaseJobs
{

    use QueueTrait;

    /**
     * 下单成功修改砍价状态
     * @param int $uid
     * @param int $bargainId
     * @return bool
     */
    public function setBargainUserStatus(int $uid, int $bargainId)
    {
        try {
            /** @var StoreBargainServices $bargainServices */
            $bargainServices = app()->make(StoreBargainServices::class);
            $bargainServices->setBargainUserStatus($bargainId, $uid);
        } catch (\Throwable $e) {
            Log::error('下单成功修改砍价状态失败,失败原因:' . $e->getMessage());
        }
        return true;
    }
}
