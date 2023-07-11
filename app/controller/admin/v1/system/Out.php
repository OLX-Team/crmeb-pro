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

use think\facade\App;
use app\services\system\log\ClearServices;
use app\controller\admin\AuthController;


/**
 * 清除数据控制器
 * Class Clear
 * @package app\controller\admin\v1\system
 */
class Out extends AuthController
{
    public function __construct(App $app, ClearServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 刷新数据缓存
     */
    public function refresh_cache()
    {
        $this->services->refresCache();
        return $this->success('数据缓存刷新成功!');
    }


    /**
     * 删除日志
     */
    public function delete_log()
    {
        $this->services->deleteLog();
        return $this->success('数据缓存刷新成功!');
    }
}


