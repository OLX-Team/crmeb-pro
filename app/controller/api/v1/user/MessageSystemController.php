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
namespace app\controller\api\v1\user;

use app\Request;
use app\services\message\SystemMessageServices;


/**
 * 用户地址类
 * Class UserController
 * @package app\api\controller\store
 */
class MessageSystemController
{
    protected $services = NUll;

    /**
     * MessageSystemController constructor.
     * @param SystemMessageServices $services
     */
    public function __construct(SystemMessageServices $services)
    {
        $this->services = $services;
    }

    /**
     * 站内信列表
     * @param Request $request
     * @return mixed
     */
    public function message_list(Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->getMessageSystemList($uid));
    }

    /**
     * 站内信详情
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function detail(Request $request, $id)
    {
        if (!$id) {
            app('json')->fail('缺少参数');
        }
        $uid = (int)$request->uid();
        $where['uid'] = $uid;
        $where['id'] = $id;
        return app('json')->successful($this->services->getInfo($where));
    }
}
