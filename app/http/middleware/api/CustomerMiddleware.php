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
namespace app\http\middleware\api;

use app\services\message\service\StoreServiceServices;
use app\services\store\DeliveryServiceServices;
use app\services\store\SystemStoreStaffServices;
use crmeb\interfaces\MiddlewareInterface;
use app\Request;

class CustomerMiddleware implements MiddlewareInterface
{

    public function handle(Request $request, \Closure $next)
    {
        $uid = $request->uid();
        /** @var StoreServiceServices $services */
        $services = app()->make(StoreServiceServices::class);
        /** @var SystemStoreStaffServices $storeServices */
        $storeServices = app()->make(SystemStoreStaffServices::class);
        $rule = trim(strtolower($request->rule()->getRule()));
        /** @var DeliveryServiceServices $deliveryService */
        $deliveryService = app()->make(DeliveryServiceServices::class);
        $isDelivery = $deliveryService->checkoutIsService($uid);
        $withRule = ['/api/order/order_verific', "/api/admin/order/detail/<orderId>"];
        if (((!$services->checkoutIsService(['uid' => $uid, 'account_status' => 1, 'customer' => 1]) && !$storeServices->verifyStatus($uid)) && !$isDelivery) && !(in_array($rule, $withRule)))
            return app('json')->fail('权限不足');
        return $next($request);
    }
}
