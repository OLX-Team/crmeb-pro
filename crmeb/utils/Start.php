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

namespace crmeb\utils;

use think\App;

/**
 * Start输出类
 * Class Json
 * @package crmeb\utils
 */
class Start
{
    public function show()
    {
        $this->opCacheClear();

        $context = $this->logo();
        $context .= $this->displayItem('php      version', phpversion());
        $context .= $this->displayItem('swoole   version', phpversion('swoole'));
        $context .= $this->displayItem('thinkphp version', App::VERSION);
        $context .= $this->displayItem('crmeb    version', get_crmeb_version());

        //http配置
        $httpConf = \config("swoole.http");
        $context  .= $this->displayItem('http enable', $httpConf["enable"]);
        $context  .= $this->displayItem('http host', $httpConf["host"]);
        $context  .= $this->displayItem('http port', $httpConf["port"]);
        $context  .= $this->displayItem('http worker_num', $httpConf["worker_num"]);

        //websocket配置
        $context .= $this->displayItem('websocket enable', \config("swoole.websocket.enable"));

        //rpc配置
        $rpcConf = \config("swoole.rpc.server");
        $context .= $this->displayItem('rpc enable', $rpcConf["enable"]);
        if ($rpcConf["enable"]) {
            $context .= $this->displayItem('rpc host', $rpcConf["host"]);
            $context .= $this->displayItem('rpc port', $rpcConf["port"]);
            $context .= $this->displayItem('rpc worker_num', $rpcConf["worker_num"]);
        }

        //队列配置
        $context .= $this->displayItem('queue enable', \config("swoole.queue.enable"));

        //热更新配置
        $context .= $this->displayItem('hot_update enable', \config("swoole.hot_update.enable") ? true : false);

        //debug配置
        $context .= $this->displayItem('app_debug enable', env("APP_DEBUG") ? true : false);

        //打印信息
        echo $context;
    }


    private function logo()
    {
        return <<<LOGO
   ██████    ███████     ████     ████   ████████   ██████             ███████    ███████       ███████  
  ██░░░░██  ░██░░░░██   ░██░██   ██░██  ░██░░░░░   ░█░░░░██           ░██░░░░██  ░██░░░░██     ██░░░░░██ 
 ██    ░░   ░██   ░██   ░██░░██ ██ ░██  ░██        ░█   ░██           ░██   ░██  ░██   ░██    ██     ░░██
░██         ░███████    ░██ ░░███  ░██  ░███████   ░██████     █████  ░███████   ░███████    ░██      ░██
░██         ░██░░░██    ░██  ░░█   ░██  ░██░░░░    ░█░░░░ ██  ░░░░░   ░██░░░░    ░██░░░██    ░██      ░██
░░██    ██  ░██  ░░██   ░██   ░    ░██  ░██        ░█    ░██          ░██        ░██  ░░██   ░░██     ██ 
 ░░██████   ░██   ░░██  ░██        ░██  ░████████  ░███████           ░██        ░██   ░░██   ░░███████  
  ░░░░░░    ░░     ░░   ░░         ░░   ░░░░░░░░   ░░░░░░░            ░░         ░░     ░░     ░░░░░░░
  

LOGO;
    }

    private function displayItem($name, $value)
    {
        if ($value === true) {
            $value = 'true';
        }
        elseif ($value === false) {
            $value = 'false';
        }
        elseif ($value === null) {
            $value = 'null';
        }

        return "\e[32m" . str_pad($name, 30, ' ', STR_PAD_RIGHT) . "\e[34m" . $value . "\e[0m \n";
    }

    private function opCacheClear()
    {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}
