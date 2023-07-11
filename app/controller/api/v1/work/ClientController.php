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


use app\services\work\WorkClientServices;

/**
 * 客户
 * Class SidebarController
 * @package app\controller\api\v1\work
 */
class ClientController extends BaseWorkController
{

    /**
     * SidebarController constructor.
     * @param WorkClientServices $services
     */
    public function __construct(WorkClientServices $services)
    {
        parent::__construct();
        $this->service = $services;
    }

    /**
     * @return mixed
     */
    public function getClientInfo()
    {
        return $this->success($this->service->getClientInfo($this->userid, $this->clientInfo));
    }
}
