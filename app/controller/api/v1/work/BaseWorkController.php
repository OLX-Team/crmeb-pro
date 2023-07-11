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

/**
 * Class BaseWorkController
 * @package app\controller\api\v1\work
 */
abstract class BaseWorkController
{

    /**
     * 客户userid
     * @var string
     */
    protected $userid;

    /**
     * 客户详细信息
     * @var array
     */
    protected $clientInfo;

    /**
     * @var \think\facade\Request|\think\Request
     */
    protected $request;

    /**
     * @var
     */
    protected $service;

    /**
     * BaseWorkController constructor.
     */
    public function __construct()
    {
        $this->request = request();
        $this->clientInfo = $this->request->clientInfo();
        $this->userid = $this->request->userid();
    }

    /**
     * @param $msg
     * @param array $data
     * @return mixed
     */
    public function success($msg, array $data = [])
    {
        return app('json')->success($msg, $data);
    }

    /**
     * @param string $msg
     * @param array|null $data
     * @return mixed
     */
    public function fail($msg = 'fail', ?array $data = null)
    {
        return app('json')->fail($msg, $data);
    }
}
