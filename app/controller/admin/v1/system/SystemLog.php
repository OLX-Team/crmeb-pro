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
namespace app\controller\admin\v1\system;

use app\services\system\admin\SystemAdminServices;
use think\facade\App;
use app\controller\admin\AuthController;
use app\services\system\log\SystemLogServices;

/**
 * 管理员操作记录表控制器
 * Class SystemLog
 * @package app\controller\admin\v1\system
 */
class SystemLog extends AuthController
{

    /**
     * 构造方法
     * SystemLog constructor.
     * @param App $app
     * @param SystemLogServices $services
     */
    public function __construct(App $app, SystemLogServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
        $this->services->deleteLog();
    }

    /**
     * 显示操作记录
     */
    public function index()
    {

        $where = $this->request->getMore([
            ['pages', ''],
            ['path', ''],
            ['ip', ''],
            ['admin_id', ''],
            ['data', '', '', 'time'],
        ]);
        return $this->success($this->services->getLogList($where, (int)$this->adminInfo['level']));
    }

    public function search_admin(SystemAdminServices $services)
    {
        $info = $services->getOrdAdmin('id,real_name', $this->adminInfo['level']);
        return $this->success(compact('info'));
    }

}

