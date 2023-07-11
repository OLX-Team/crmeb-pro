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

namespace app\controller\store\system;


use app\controller\store\AuthController;
use app\services\store\SystemStoreStaffServices;
use app\services\system\log\SystemLogServices;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\App;

/**
 * Class Log
 * @package app\controller\store\system
 */
class Log extends AuthController
{

    /**
     * Log constructor.
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
        $where['store_id'] = $this->storeId;
        return $this->success($this->services->getLogList($where, (int)$this->storeStaffInfo['level']));
    }

    /**
     * 获取
     * @param SystemStoreStaffServices $services
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function search_admin(SystemStoreStaffServices $services)
    {
        $info = $services->getOrdAdmin('id,staff_name', $this->storeId, $this->storeStaffInfo['level']);
        return $this->success(compact('info'));
    }

}
