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

use app\webscoket\SocketPush;
use crmeb\interfaces\ListenerInterface;
use app\services\system\admin\SystemAdminServices;

/**
 * 用户申请提现事件
 * Class Recharge
 * @package app\listener\user
 */
class Extract implements ListenerInterface
{
    /**
     * 用户申请提现事件
     * @param $event
     */
    public function handle($event): void
    {
        [$user, $data, $res] = $event;
        try {
            SocketPush::admin()->type('WITHDRAW')->data(['id' => $res->id])->push();
        } catch (\Exception $e) {
        }
        /** @var SystemAdminServices $systemAdmin */
        $systemAdmin = app()->make(SystemAdminServices::class);
        $systemAdmin->adminNewPush();
        //提醒推送
        event('notice.notice', [['nickname' => $user['nickname'], 'money' => $data['money']], 'kefu_send_extract_application']);
    }
}
