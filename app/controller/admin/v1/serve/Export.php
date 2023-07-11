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

namespace app\controller\admin\v1\serve;


use app\controller\admin\AuthController;
use app\services\serve\ServeServices;
use app\services\other\ExpressServices;
use crmeb\services\SystemConfigService;
use think\facade\App;

/**
 * 一号通平台物流服务
 * Class Export
 * @package app\controller\admin\v1\serve
 */
class Export extends AuthController
{

    /**
     * Export constructor.
     * @param App $app
     * @param ExpressServices $services
     */
    public function __construct(App $app, ExpressServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 物流公司
     * @return mixed
     */
    public function getExportAll()
    {
        return $this->success($this->services->expressList());
    }

    /**
     *
     * 获取面单信息
     * @param string $com
     * @return mixed
     */
    public function getExportTemp(ServeServices $services)
    {
        [$com] = $this->request->getMore([
            ['com', ''],
        ], true);
        return $this->success($services->express()->temp($com));
    }

    /**
     * 打印电子面单是否开启
     * @return mixed
     */
    public function dumpIsOpen(ServeServices $services)
    {
        $userInfo = $services->user()->getUser();
        $res = false;
        if ($userInfo['dump']['open']) {
            $res = true;
            $data = SystemConfigService::more(['config_export_siid', 'config_export_com', 'config_export_to_name', 'config_export_to_tel', 'config_export_to_address']);
            if (!$data['config_export_siid']
                && !$data['config_export_com']
                && !$data['config_export_to_name']
                && !$data['config_export_to_tel']
                && !$data['config_export_to_address']
            ) {
                $res = false;
            }
        }
        return $this->success(['isOpen' => $res]);
    }
}
