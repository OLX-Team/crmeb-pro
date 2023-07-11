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


use app\services\activity\coupon\StoreCouponIssueServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 营销：优惠券
 * Class StoreCouponJob
 * @package app\jobs\user
 */
class StoreCouponJob extends BaseJobs
{

    use QueueTrait;

    /**
     * 增加新人券
     * @param $uid
     * @return bool
     */
    public function newUserGiveCoupon($uid)
    {
        try {
            /**@var StoreCouponIssueServices $storeCoupon */
            $storeCoupon = app()->make(StoreCouponIssueServices::class);
            $storeCoupon->userFirstSubGiveCoupon((int)$uid);
        } catch (\Throwable $e) {
            Log::error('赠送新人券失败,失败原因:' . $e->getMessage());
        }
        return true;
    }
}
