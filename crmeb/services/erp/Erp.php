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

namespace crmeb\services\erp;

use crmeb\basic\BaseManager;
use crmeb\services\erp\storage\Jushuitan;
use think\Container;
use think\facade\Config;


/**
 * Class Erp
 * @package crmeb\services\erp
 * @mixin Jushuitan
 */
class Erp extends BaseManager
{

    /**
     * 空间名
     * @var string
     */
    protected $namespace = '\\crmeb\\services\\erp\\storage\\';

    protected $type = [
        'nothing',
        'jushuitan',
    ];

    /**
     * 默认驱动
     * @return mixed
     */
    protected function getDefaultDriver()
    {
        $this->config = [
            'app_key' => sys_config('jst_appkey'),
            'secret' => sys_config('jst_appsecret'),
            'login_account' => sys_config('jst_login_account'),
            'login_password' => sys_config('jst_login_password'),
        ];
        return $this->type[sys_config('erp_type')];
    }

    /**
     * 获取类的实例
     * @param $class
     * @return mixed|void
     */
    protected function invokeClass($class)
    {
        if (!class_exists($class)) {
            throw new \RuntimeException('class not exists: ' . $class);
        }
        $this->getConfigFile();

        if (!$this->config) {
            $this->config = Config::get($this->configFile . '.stores.' . $this->name, []);
        }
        $handleAccessToken = new AccessToken($this->name, $this->configFile, $this->config);
        $handle = Container::getInstance()->invokeClass($class, [$this->name, $handleAccessToken, $this->configFile]);
        $this->config = [];

        return $handle;
    }
}
