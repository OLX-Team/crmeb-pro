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
use app\services\store\StoreConfigServices;
use app\services\system\config\SystemConfigServices;
use app\services\system\config\SystemConfigTabServices;
use think\facade\App;
use app\Request;

/**
 * Class Config
 * @package app\controller\store\system
 */
class Config extends AuthController
{

    /**
     * Config constructor.
     * @param App $app
     * @param SystemConfigServices $services
     */
    public function __construct(App $app, SystemConfigServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * @param $type
     * @param StoreConfigServices $services
     * @return mixed
     */
    public function getConfig($type, StoreConfigServices $services)
    {
        if (!isset(StoreConfigServices::CONFIG_TYPE[$type])) {
            return $this->fail('类型不正确');
        }
        return $this->success($services->getConfigAll($this->storeId, StoreConfigServices::CONFIG_TYPE[$type]));
    }

    /**
     * 保存数据
     * @param $type
     * @param StoreConfigServices $services
     * @return mixed
     */
    public function save($type, StoreConfigServices $services)
    {
        if (!isset(StoreConfigServices::CONFIG_TYPE[$type])) {
            return $this->fail('类型不正确');
        }
        $data = $this->request->postMore(StoreConfigServices::CONFIG_TYPE[$type]);
        $services->saveConfig($data, $this->storeId);
        return $this->success('修改成功');
    }

    /**
     * 基础配置
     * @param Request $request
     * @param SystemConfigServices $services
     * @param SystemConfigTabServices $tabServices
     * @return
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit_basics(Request $request, SystemConfigServices $services, SystemConfigTabServices $tabServices)
    {
        $name = $this->request->param('name', '');
        if (!$name) {
            return $this->fail('参数错误');
        }
        $tabId = $tabServices->value(['eng_title' => $name], 'id');
        $url = $request->baseUrl();
        $store_id = $this->storeId;
        return $this->success($services->getConfigForm($url, $tabId, $store_id));
    }
}
