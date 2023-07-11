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

namespace app\controller\admin\v1\work;


use app\controller\admin\AuthController;
use app\services\work\WorkDepartmentServices;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 组织架构
 * Class Department
 * @package app\controller\admin\v1\work
 */
class Department extends AuthController
{

    /**
     * Department constructor.
     * @param WorkDepartmentServices $services
     */
    public function __construct(WorkDepartmentServices $services)
    {
        $this->services = $services;
    }

    /**
     * 获取组织架构
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        return $this->success($this->services->getDepartmentList());
    }
}
