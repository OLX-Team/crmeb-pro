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

namespace app\controller\api\v1\work;


use app\Request;
use crmeb\services\wechat\Work;

/**
 * 企业微信
 * Class WorkController
 * @package app\controller\api\v1\work
 */
class WorkController
{

    /**
     * 获取企业微信配置
     * @param Request $request
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function config(Request $request)
    {
        return response(['status' => 200, 'msg' => 'ok', 'data' => Work::getJsSDK($request->get('url', ''))], 200, [], 'json');
    }

    /**
     * 获取应用配置
     * @param Request $request
     * @return \think\Response
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function agentConfig(Request $request)
    {
        return response(['status' => 200, 'msg' => 'ok', 'data' => Work::getAgentConfig($request->get('url', ''))], 200, [], 'json');
    }

}
