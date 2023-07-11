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
namespace app\controller\erp;

use crmeb\services\erp\Erp;
use think\Response;

/**
 * Class AuthController
 * @package app\controller\erp
 */
class AuthController
{

    /*** @var Erp */
    protected $services;

    public function __construct(Erp $services)
    {
        $this->services = $services;
    }

    /**
     * 获取auth测试
     * @return mixed
     */
    public function auth()
    {
        $params = $this->services->getAuthParams();

        $url = $params["url"] . "?";
        unset($params["url"]);
        $url .= http_build_query($params);

        return app('json')->success([$params, 'jump_url' => $url]);
    }

    /**
     * 授权回调测试
     * @return mixed
     */
    public function authCallBack()
    {
        $rep = $this->services->authCallback();
        return Response::create($rep->getData(), "json");
    }

    /**
     * token测试
     * @return void
     */
    public function accessToken()
    {
        $param = $this->services->getAccessToken();
        return app('json')->success($param);
    }
}