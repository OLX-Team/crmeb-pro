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

namespace app\http\middleware\cashier;


use app\Request;
use app\jobs\system\AdminLogJob;
use crmeb\interfaces\MiddlewareInterface;

/**
 * 操作日志记录
 * Class StoreLogMiddleware
 * @package app\http\middleware\store
 */
class CashierLogMiddleware implements MiddlewareInterface
{

    /**
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        $module = app('http')->getName();
        $rule = trim(strtolower($request->rule()->getRule()));
        //记录后台日志
        AdminLogJob::dispatch([$request->storeId(), $request->cashierInfo()['account'], $module, $rule, $request->ip(), 'cashier']);

        return $next($request);
    }
}
