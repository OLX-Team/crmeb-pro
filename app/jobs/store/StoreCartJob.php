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

namespace app\jobs\store;


use app\services\order\StoreOrderCartInfoServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 保存购物车
 * Class StoreCartJob
 * @package app\jobs\store
 */
class StoreCartJob extends BaseJobs
{
    use QueueTrait;

    public function doJob($id, $cartInfo, $uid, array $promotions = [])
    {
        /** @var StoreOrderCartInfoServices $cartServices */
        $cartServices = app()->make(StoreOrderCartInfoServices::class);
        $cartServices->setCartInfo($id, $cartInfo, (int)$uid, $promotions);
        return true;
    }
}
