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

namespace app\listener\user;


use app\services\work\WorkMemberServices;
use crmeb\interfaces\ListenerInterface;

/**
 * 用户绑定成员
 * Class UserBindWorkMember
 * @package app\listener\user
 */
class UserBindWorkMember implements ListenerInterface
{

    public function handle($event): void
    {
        [$uid, $phone] = $event;
        /** @var WorkMemberServices $service */
        $service = app()->make(WorkMemberServices::class);

        try {
            $memberInfo = $service->get(['mobile' => $phone], ['id', 'uid']);
            if ($memberInfo) {
                $memberInfo->uid = $uid;
                $memberInfo->save();
            }
        } catch (\Throwable $e) {
            \think\facade\Log::error([
                'error' => '用户绑定成员失败:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

}
