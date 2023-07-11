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

namespace app\services\work;


use app\services\BaseServices;
use crmeb\services\wechat\config\WorkConfig;
use crmeb\services\wechat\contract\WorkAppConfigHandlerInterface;

/**
 * 企业微信配置
 * Class WorkConfigServices
 * @package app\services
 */
class WorkConfigServices extends BaseServices implements WorkAppConfigHandlerInterface
{

    /**
     * 获取应用配置
     * @param string $corpId
     * @param string $type
     * @return array
     */
    public function getAppConfig(string $corpId, string $type): array
    {
        $config = [];
        switch ($type) {
            case WorkConfig::TYPE_USER:
                $config = [
                    'secret' => sys_config('wechat_work_user_secret')
                ];
                break;
            case WorkConfig::TYPE_ADDRESS:
                $config = [
                    'secret' => sys_config('wechat_work_address_secret'),
                ];
                break;
            case WorkConfig::TYPE_USER_APP:
                $config = [
                    'agent_id' => sys_config('wechat_work_build_agent_id'),
                    'secret' => sys_config('wechat_work_build_secret'),
                ];
                break;
        }
        return $config;
    }
}
