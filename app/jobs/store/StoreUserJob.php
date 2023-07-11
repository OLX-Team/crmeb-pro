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


use app\services\store\StoreUserServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

class StoreUserJob extends BaseJobs
{

    use QueueTrait;

    public function doJob($uid, $storeId)
    {
        try {
            /** @var StoreUserServices $storeUserServices */
            $storeUserServices = app()->make(StoreUserServices::class);
            $storeUserServices->setStoreUser((int)$uid, (int)$storeId);
        } catch (\Throwable $e) {

        }
        return true;
    }
}
