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

namespace app\listener\work;


use app\services\wechat\WechatUserServices;
use app\services\work\WorkClientServices;
use crmeb\interfaces\ListenerInterface;
use think\exception\ValidateException;

/**
 * 客户绑定商城用户事件
 * Class ClientBindUserListener
 * @package app\listener\work
 */
class ClientBindUserListener implements ListenerInterface
{

    public function handle($event): void
    {
        [$clientId] = $event;

        /** @var WorkClientServices $service */
        $service = app()->make(WorkClientServices::class);
        $clientInfo = $service->get($clientId, ['type', 'id', 'unionid', 'uid']);
        if (!$clientInfo) {
            throw new ValidateException('没有查询到客户身份');
        }
        if (!$clientInfo->unionid) {
            throw new ValidateException('没有查询到unionid');
        }

        //查询关联用户
        /** @var WechatUserServices $wechatUserService */
        $wechatUserService = app()->make(WechatUserServices::class);
        $uid = $wechatUserService->value(['unionid' => $clientInfo->unionid], 'uid');

        //有用户关联客户和用户身份
        if ($uid && $clientInfo->uid != $uid) {
            $clientInfo->uid = $uid;
            $clientInfo->save();
        }
    }
}
