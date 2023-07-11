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

namespace app\controller;

use app\Request;

class InstallController
{
    public function index(Request $request)
    {
        [$step, $testdbpwd, $install, $n] = $request->getMore([
            ['step', 1],
            ['testdbpwd', 0],
            ['install', 0],
            ['n', 0]
        ], true);
        $path = app()->getRootPath();
        $step = app()->request->param('step') ?? 1;
        if (file_exists($path . 'public/install/install.lock')) {
            return '你已经安装过该系统，如果想重新安装，请先删除install目录下的 install.lock 文件，然后再安装。';
        }
        @set_time_limit(1000);
        if (phpversion() < '7.1.0' && '7.4.0' > phpversion()) {
            return '您的php版本过低，不能安装本软件，php版本7.4，谢谢！';
        }
        if (phpversion() > '8.0') {
            return '您的php版本太高，不能安装本软件，php版本7.4，谢谢！';
        }
        date_default_timezone_set('PRC');
        error_reporting(E_ALL & ~E_NOTICE);
        //数据库
        $sqlFile = 'crmeb.sql';
        $configFile = '.env';
        if (!file_exists($path . 'public/install/' . $sqlFile) || !file_exists($path . 'public/install/' . $configFile)) {
            return '缺少必要的安装文件!';
        }
        $Title = "CRMEB安装向导";
        $Powered = "Powered by CRMEB";
        $steps = array(
            '1' => '安装许可协议',
            '2' => '运行环境检测',
            '3' => '安装参数设置',
            '4' => '安装详细过程',
            '5' => '安装完成',
        );

        switch ($step) {
            case '1':
                return view('/install/view/step1', [
                    'title' => $Title,
                    'powered' => $Powered
                ]);
            case '2':
                $phpv = @ phpversion();
                $os = PHP_OS;
                $tmp = function_exists('gd_info') ? gd_info() : array();
                $server = $_SERVER["SERVER_SOFTWARE"];
                $host = (empty($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_HOST"] : $_SERVER["SERVER_ADDR"]);
                $name = $_SERVER["SERVER_NAME"];
                $max_execution_time = ini_get('max_execution_time');
                $allow_reference = (ini_get('allow_call_time_pass_reference') ? '<font color=green>[√]On</font>' : '<font color=red>[×]Off</font>');
                $allow_url_fopen = (ini_get('allow_url_fopen') ? '<font color=green>[√]On</font>' : '<font color=red>[×]Off</font>');
                $safe_mode = (ini_get('safe_mode') ? '<font color=red>[×]On</font>' : '<font color=green>[√]Off</font>');

                $err = 0;
                if (empty($tmp['GD Version'])) {
                    $gd = '<font color=red>[×]Off</font>';
                    $err++;
                } else {
                    $gd = '<font color=green>[√]On</font> ' . $tmp['GD Version'];
                }

                if (extension_loaded('redis')) {
                    $redis = '<span class="correct_span">&radic;</span> 已安装';
                } else {
                    $redis = '<a href="http://help.crmeb.net/crmebpro/1702275" target="_blank"><span class="correct_span error_span">&radic;</span> 点击查看帮助</a>';
                    $err++;
                }

                if (extension_loaded('swoole_loader')) {
                    $swoole = '<span class="correct_span">&radic;</span> 已安装';
                } else {
                    $swoole = '<a href="/install/compiler" target="_blank"><span class="correct_span error_span">&radic;</span> 点击查看帮助</a>';
                    $err++;
                }

                if (function_exists('mysqli_connect')) {
                    $mysql = '<span class="correct_span">&radic;</span> 已安装';
                } else {
                    $mysql = '<span class="correct_span error_span">&radic;</span> 请安装mysqli扩展';
                    $err++;
                }
                if (ini_get('file_uploads')) {
                    $uploadSize = '<span class="correct_span">&radic;</span> ' . ini_get('upload_max_filesize');
                } else {
                    $uploadSize = '<span class="correct_span error_span">&radic;</span>禁止上传';
                }
                if (function_exists('session_start')) {
                    $session = '<span class="correct_span">&radic;</span> 支持';
                } else {
                    $session = '<span class="correct_span error_span">&radic;</span> 不支持';
                    $err++;
                }
                if (function_exists('curl_init')) {
                    $curl = '<font color=green>[√]支持</font> ';
                } else {
                    $curl = '<font color=red>[×]不支持</font>';
                    $err++;
                }

                if (function_exists('bcadd')) {
                    $bcmath = '<font color=green>[√]支持</font> ';
                } else {
                    $bcmath = '<font color=red>[×]不支持</font>';
                    $err++;
                }
                if (function_exists('openssl_encrypt')) {
                    $openssl = '<font color=green>[√]支持</font> ';
                } else {
                    $openssl = '<font color=red>[×]不支持</font>';
                    $err++;
                }
                if (function_exists('finfo_open')) {
                    $finfo_open = '<font color=green>[√]支持</font> ';
                } else {
                    $finfo_open = '<a href="http://help.crmeb.net/crmebpro/1707557" target="_blank"><span class="correct_span error_span">&radic;</span>点击查看帮助</a>';
                    $err++;
                }

                $folder = array(
                    'public/uploads',
                    'runtime',
                );
                $dirStr = '';
                foreach ($folder as $dir) {
                    $Testdir = $path . $dir;
                    if (!is_file($Testdir)) {
                        if (!is_dir($Testdir)) {
                            $this->dir_create($Testdir);
                        }
                    }

                    if ($this->testwrite($Testdir)) {
                        $w = '<span class="correct_span">&radic;</span>可写 ';
                    } else {
                        $w = '<span class="correct_span error_span">&radic;</span>不可写 ';
                        $err++;
                    }

                    if (is_readable($Testdir)) {
                        $r = '<span class="correct_span">&radic;</span>可读';
                    } else {
                        $r = '<span class="correct_span error_span">&radic;</span>不可读';
                        $err++;
                    }

                    $dirStr .= "<tr>
                        <td>$dir</td>
                        <td>读写</td>
                        <td>$w</td>
                        <td>$r</td>
                    </tr>";

                }

                //必须开启函数
                if (function_exists('file_put_contents')) {
                    $file_put_contents = '<font color=green>[√]开启</font> ';
                } else {
                    $file_put_contents = '<font color=red>[×]关闭</font>';
                    $err++;
                }
                if (function_exists('imagettftext')) {
                    $imagettftext = '<font color=green>[√]开启</font> ';
                } else {
                    $imagettftext = '<font color=red>[×]关闭</font>';
                    $err++;
                }

                if ($err > 0) {
                    $next = '<a href="javascript:void(0)" onClick="javascript:alert(\'安装环境检测未通过，请检查\')" class="btn" style="background: gray;">下一步</a>';
                } else {
                    $next = '<a href="?step=3" class="btn">下一步</a>';
                }

                return view('/install/view/step2', [
                    'title' => $Title,
                    'powered' => $Powered,
                    'os' => $os,
                    'server' => $server,
                    'phpv' => $phpv,
                    'uploadSize' => $uploadSize,
                    'session' => $session,
                    'safe_mode' => $safe_mode,
                    'swoole' => $swoole,
                    'redis' => $redis,
                    'mysql' => $mysql,
                    'curl' => $curl,
                    'bcmath' => $bcmath,
                    'openssl' => $openssl,
                    'finfo_open' => $finfo_open,
                    'gd' => $gd,
                    'file_put_contents' => $file_put_contents,
                    'imagettftext' => $imagettftext,
                    'dirStr' => $dirStr,
                    'next' => $next,
                ]);
            case '3':
                if ($testdbpwd) {
                    $post = $request->postMore([
                        ['dbHost', ''],
                        ['dbport', ''],
                        ['dbUser', ''],
                        ['dbPwd', ''],
                        ['dbName', ''],
                        ['rbhost', ''],
                        ['rbport', ''],
                        ['rbselect', ''],
                        ['rbpw', ''],
                    ]);
                    $conn = @mysqli_connect($post['dbHost'], $post['dbUser'], $post['dbPwd'], NULL, $post['dbport']);
                    if (mysqli_connect_errno($conn)) {
                        return 0;
                    } else {
                        $result = mysqli_query($conn, "SELECT @@global.sql_mode");
                        $result = $result->fetch_array();
                        $version = mysqli_get_server_info($conn);
                        if ($version >= 5.7) {
                            if (strstr($result[0], 'STRICT_TRANS_TABLES') || strstr($result[0], 'STRICT_ALL_TABLES') || strstr($result[0], 'TRADITIONAL') || strstr($result[0], 'ANSI'))
                                return -1;
                        }
                        $result = mysqli_query($conn, "select count(table_name) as c from information_schema.`TABLES` where table_schema='$post[dbName]'");
                        $result = $result->fetch_array();
                        if ($result['c'] > 0)
                            return -2;
                    }

                    //redis数据库信息
                    $rbhost = $post['rbhost'] ?? '127.0.0.1';
                    $rbport = $post['rbport'] ?? 6379;
                    $rbpw = $post['rbpw'] ?? '';
                    $rbselect = $post['rbselect'] ?? 0;

                    try {
                        $redis = new \Redis();
                        $redis->connect($rbhost, $rbport);
                        if ($rbpw) {
                            $redis->auth($rbpw);
                        }
                        if ($rbselect) {
                            $redis->select($rbselect);
                        }
                        $res = $redis->set('install', 1, 10);
                        if ($res) {
                            return 1;
                        } else {
                            return -3;
                        }
                    } catch (\Throwable $e) {
                        return -3;
                    }
                }
                return view('/install/view/step3', [
                    'title' => $Title,
                    'powered' => $Powered,
                ]);

            case '4':
                $post = $request->postMore([
                    ['dbhost', ''],
                    ['dbport', ''],
                    ['dbname', ''],
                    ['dbuser', ''],
                    ['dbpw', ''],
                    ['dbprefix', ''],
                    ['rbhost', ''],
                    ['rbport', ''],
                    ['rbselect', ''],
                    ['rbpw', ''],
                    ['manager', ''],
                    ['manager_pwd', ''],
                    ['manager_email', ''],
                    ['demo', ''],
                    ['cache_prefix', ''],
                    ['cache_tag_prefix', ''],
                ]);

                if (intval($install)) {
                    if ($i == 999999) return false;
                    $arr = array();

                    $dbHost = trim($post['dbhost']);
                    $post['dbport'] = $post['dbport'] ? $post['dbport'] : '3306';
                    $dbName = strtolower(trim($post['dbname']));
                    $dbUser = trim($post['dbuser']);
                    $dbPwd = trim($post['dbpw']);
                    $dbPrefix = empty($post['dbprefix']) ? 'eb_' : trim($post['dbprefix']);

                    $username = trim($post['manager']);
                    $password = trim($post['manager_pwd']);
                    $email = trim($post['manager_email']);
                    if (!function_exists('mysqli_connect')) {
                        $arr['msg'] = "请安装 mysqli 扩展!";
                        return $arr;
                    }
                    $conn = @mysqli_connect($dbHost, $dbUser, $dbPwd, NULL, $post['dbport']);
                    if (mysqli_connect_errno($conn)) {
                        $arr['msg'] = "连接数据库失败!" . mysqli_connect_error($conn);
                        return $arr;
                    }
                    mysqli_set_charset($conn, "utf8"); //,character_set_client=binary,sql_mode='';
                    $version = mysqli_get_server_info($conn);
                    if ($version < 5.1) {
                        $arr['msg'] = '数据库版本太低! 必须5.1以上';
                        return $arr;
                    }

                    if (!mysqli_select_db($conn, $dbName)) {
                        //创建数据时同时设置编码
                        if (!mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `" . $dbName . "` DEFAULT CHARACTER SET utf8;")) {
                            $arr['msg'] = '数据库 ' . $dbName . ' 不存在，也没权限创建新的数据库！';
                            return $arr;
                        }
                        if ($n == -1) {
                            $arr['n'] = 0;
                            $arr['msg'] = "成功创建数据库:{$dbName}<br>";
                            return $arr;
                        }
                        mysqli_select_db($conn, $dbName);
                    }

                    //读取数据文件
                    $sqldata = file_get_contents($path . 'public/install/' . $sqlFile);
                    $sqlFormat = $this->sql_split($sqldata, $dbPrefix);
                    //创建写入sql数据库文件到库中 结束

                    /**
                     * 执行SQL语句
                     */
                    $counts = count($sqlFormat);
                    for ($i = $n; $i < $counts; $i++) {
                        $sql = trim($sqlFormat[$i]);
                        if (strstr($sql, 'CREATE TABLE')) {
                            preg_match('/CREATE TABLE (IF NOT EXISTS)? `eb_([^ ]*)`/is', $sql, $matches);
                            mysqli_query($conn, "DROP TABLE IF EXISTS `$matches[2]");
                            $sql = str_replace('`eb_', '`' . $dbPrefix, $sql);//替换表前缀
                            $ret = mysqli_query($conn, $sql);
                            if ($ret) {
                                $message = '<li><span class="correct_span">&radic;</span>创建数据表[' . $dbPrefix . $matches[2] . ']完成!<span style="float: right;">' . date('Y-m-d H:i:s') . '</span></li> ';
                            } else {
                                $err = mysqli_error($conn);
                                $message = '<li><span class="correct_span error_span">&radic;</span>创建数据表[' . $dbPrefix . $matches[2] . ']失败!失败原因：' . $err . '<span style="float: right;">' . date('Y-m-d H:i:s') . '</span></li>';
                            }
                            $i++;
                            $arr = array('n' => $i, 'msg' => $message);
                            return $arr;
                        } else {
                            if (trim($sql) == '')
                                continue;
                            $sql = str_replace('`eb_', '`' . $dbPrefix, $sql);//替换表前缀
                            $ret = mysqli_query($conn, $sql);
                            $message = '';
                            $i++;
                            $arr = array('n' => $i, 'msg' => $message);
                            return $arr;
                        }
                    }


                    // 清空测试数据
                    if (!$post['demo']) {
                        $result = mysqli_query($conn, "show tables");
                        $tables = mysqli_fetch_all($result);//参数MYSQL_ASSOC、MYSQLI_NUM、MYSQLI_BOTH规定产生数组类型
                        $bl_table = array('eb_system_admin'
                        , 'eb_system_role'
                        , 'eb_system_config'
                        , 'eb_system_config_tab'
                        , 'eb_system_menus'
                        , 'eb_system_file'
                        , 'eb_express'
                        , 'eb_system_group'
                        , 'eb_system_group_data'
                        , 'eb_template_message'
                        , 'eb_shipping_templates'
                        , "eb_shipping_templates_region"
                        , "eb_shipping_templates_free"
                        , 'eb_system_city'
                        , 'eb_diy'
                        , 'eb_member_ship'
                        , 'eb_member_right'
                        , 'eb_agreement'
                        , 'eb_store_service_speechcraft'
                        , 'eb_system_user_level'
                        , 'eb_city_area'
                        , 'eb_system_notification'
                        , 'eb_system_attachment_category'
                        , 'eb_system_attachment'
                        , 'eb_page_category'
                        , 'eb_page_link'
                        , 'eb_category'
                        , 'eb_store_product_label'
                        , 'eb_store_product_ensure'
                        , 'eb_cache');
                        foreach ($bl_table as $k => $v) {
                            $bl_table[$k] = str_replace('eb_', $dbPrefix, $v);
                        }

                        foreach ($tables as $key => $val) {
                            if (!in_array($val[0], $bl_table)) {
                                mysqli_query($conn, "truncate table " . $val[0]);
                            }
                        }
//                        $this->delFile($path . 'public/uploads'); // 清空测试图片
                    }
                    //读取配置文件，并替换真实配置数据1
                    $strConfig = file_get_contents($path . 'public/install/' . $configFile);
                    $app_key = md5(request()->ip() . uniqid() . time());
                    $strConfig = str_replace('#APP_KEY#', $app_key, $strConfig);

                    $strConfig = str_replace('#DB_HOST#', $dbHost, $strConfig);
                    $strConfig = str_replace('#DB_NAME#', $dbName, $strConfig);
                    $strConfig = str_replace('#DB_USER#', $dbUser, $strConfig);
                    $strConfig = str_replace('#DB_PWD#', $dbPwd, $strConfig);
                    $strConfig = str_replace('#DB_PORT#', $post['dbport'], $strConfig);
                    $strConfig = str_replace('#DB_PREFIX#', $dbPrefix, $strConfig);
                    $strConfig = str_replace('#DB_CHARSET#', 'utf8', $strConfig);
                    // $strConfig = str_replace('#DB_DEBUG#', false, $strConfig);

                    //redis数据库信息
                    $rbhost = $post['rbhost'] ?? '127.0.0.1';
                    $rbport = $post['rbport'] ?? '6379';
                    $rbpw = $post['rbpw'] ?? '';
                    $rbselect = $post['rbselect'] ?? 0;
                    $strConfig = str_replace('#RB_HOST#', $rbhost, $strConfig);
                    $strConfig = str_replace('#RB_PORT#', $rbport, $strConfig);
                    $strConfig = str_replace('#RB_PWD#', $rbpw, $strConfig);
                    $strConfig = str_replace('#RB_SELECT#', $rbselect, $strConfig);

                    //多项目部署配置
                    $cache_prefix = $post['cache_prefix'] ?? '';
                    $cache_tag_prefix = $post['cache_tag_prefix'] ?? '';
                    $strConfig = str_replace('#CACHE_PREFIX#', $cache_prefix, $strConfig);
                    $strConfig = str_replace('#CACHE_TAG_PREFIX#', $cache_tag_prefix, $strConfig);
                    @chmod($path . '/.env', 0777); //数据库配置文件的地址
                    @file_put_contents($path . '/.env', $strConfig); //数据库配置文件的地址

                    //读取配置文件，并替换换配置
                    //            $strConfig = file_get_contents(SITE_DIR . '/application/config.php');
                    //            $strConfig = str_replace('CRMEB_cache_prefix', $uniqid_str, $strConfig);
                    //            @chmod(SITE_DIR . '/application/config.php',0777); //配置文件的地址
                    //            @file_put_contents(SITE_DIR . '/application/config.php', $strConfig); //配置文件的地址

                    //更新网站配置信息2

                    //插入管理员表字段tp_admin表
                    $time = time();
                    $ip = request()->ip();
                    $ip = empty($ip) ? "0.0.0.0" : $ip;
                    $password = password_hash($post['manager_pwd'], PASSWORD_BCRYPT);
                    mysqli_query($conn, "truncate table {$dbPrefix}system_admin");
                    $addadminsql = "INSERT INTO `{$dbPrefix}system_admin` (`id`, `account`, `pwd`, `real_name`, `roles`, `last_ip`, `last_time`, `add_time`, `login_count`, `level`, `status`, `is_del`) VALUES (1, '" . $username . "', '" . $password . "', 'admin', '1', '" . $ip . "',$time , $time, 0, 0, 1, 0)";
                    $res = mysqli_query($conn, $addadminsql);
                    $res2 = true;
                    if (app()->request->host(true)) {
                        $site_url = '\'"http' . (app()->request->isSsl() ? 's' : '') . '://' . app()->request->host(true) . '"\'';
                        $res2 = mysqli_query($conn, 'UPDATE `' . $dbPrefix . 'system_config` SET `value`=' . $site_url . ' WHERE `menu_name`="site_url"');
                    }
                    if ($res) {
                        $message = '成功添加管理员<br />成功写入配置文件<br>安装完成．';
                        $arr = array('n' => 999999, 'msg' => $message);
                        return $arr;
                    } else {
                        $message = '添加管理员失败<br />成功写入配置文件<br>安装完成．';
                        $arr = array('n' => 999999, 'msg' => $message);
                        return $arr;
                    }
                }
                return view('/install/view/step4', [
                    'title' => $Title,
                    'powered' => $Powered,
                    'data' => json_encode($post)
                ]);
            case '5':
                $this->installlog();
                @touch($path . 'public/install/install.lock');
                return view('/install/view/step5', [
                    'title' => $Title,
                    'powered' => $Powered,
                    'ip' => request()->ip(),
                    'host' => request()->host(),
                    'version' => get_crmeb_version()
                ]);
        }
    }

    public function swooleCompiler()
    {
        // Set constants
        // Language items
        $languages['zh-cn'] = [
            'title' => 'Swoole Compiler Loader 安装助手',
        ];
        $languages['en'] = [
            'title' => 'Swoole Compiler Loader Wizard',
        ];

        // Set env variable for current environment
        $env = [];
        // Check os type
        $env['os'] = [];
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $env['os']['name'] = "windows";
            $env['os']['raw_name'] = php_uname();
        } else {
            $env['os']['name'] = "unix";
            $env['os']['raw_name'] = php_uname();
        }
        // Check php
        $env['php'] = [];
        $env['php']['version'] = phpversion();
        // Check run mode
        $sapi_type = php_sapi_name();
        if ("cli" == $sapi_type) {
            $env['php']['run_mode'] = "cli";
        } else {
            $env['php']['run_mode'] = "web";
        }
        // Check php bit
        if (PHP_INT_SIZE == 4) {
            $env['php']['bit'] = 32;
        } else {
            $env['php']['bit'] = 64;
        }
        $env['php']['sapi'] = $sapi_type;
        $env['php']['ini_loaded_file'] = php_ini_loaded_file();
        $env['php']['ini_scanned_files'] = php_ini_scanned_files();
        $env['php']['loaded_extensions'] = get_loaded_extensions();
        $env['php']['incompatible_extensions'] = ['xdebug', 'ionCube', 'zend_loader'];
        $env['php']['loaded_incompatible_extensions'] = [];
        $env['php']['extension_dir'] = ini_get('extension_dir');
        // Check incompatible extensions
        if (is_array($env['php']['loaded_extensions'])) {
            foreach ($env['php']['loaded_extensions'] as $loaded_extension) {
                foreach ($env['php']['incompatible_extensions'] as $incompatible_extension) {
                    if (strpos(strtolower($loaded_extension), strtolower($incompatible_extension)) !== false) {
                        $env['php']['loaded_incompatible_extensions'][] = $loaded_extension;
                    }
                }
            }
        }
        $env['php']['loaded_incompatible_extensions'] = array_unique($env['php']['loaded_incompatible_extensions']);
        // Parse System Environment Info
        $sysInfo = $this->w_getSysInfo($env);
        // Check php thread safety
        $env['php']['raw_thread_safety'] = isset($sysInfo['thread_safety']) ? $sysInfo['thread_safety'] : false;
        if (isset($sysInfo['thread_safety'])) {
            $env['php']['thread_safety'] = $sysInfo['thread_safety'] ? '线程安全' : '非线程安全';
        } else {
            $env['php']['thread_safety'] = '未知';
        }
        // Check swoole loader installation
        if (isset($sysInfo['swoole_loader']) and isset($sysInfo['swoole_loader_version'])) {
            $env['php']['swoole_loader']['status'] = $sysInfo['swoole_loader'] ? "<span style='color: #007bff;'>已安装</span>"
                : '未安装';
            if ($sysInfo['swoole_loader_version'] !== false) {
                $env['php']['swoole_loader']['version'] = "<span style='color: #007bff;'>" . $sysInfo['swoole_loader_version'] . "</span>";
            } else {
                $env['php']['swoole_loader']['version'] = '未知';
            }
        } else {
            $env['php']['swoole_loader']['status'] = '未安装';
            $env['php']['swoole_loader']['version'] = '未知';
        }
        /**
         *  Web mode
         */
        if (true) {
            $language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'zh-c', 0, 4);
            if (preg_match("/zh-c/i", $language)) {
                $env['lang'] = "zh-cn";
                $wizard_lang = $env['lang'];
            } else {
                $env['lang'] = "en";
                $wizard_lang = $env['lang'];
            }
            $html = '';
            // Header
            $html_header = '<!doctype html>
	<html lang="en">
	  <head>
		<!-- Required meta tags -->
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<!-- Bootstrap CSS -->
		<link href="https://lib.baomitu.com/twitter-bootstrap/4.1.0/css/bootstrap.min.css" rel="stylesheet">
		<title>%s</title>
		<style>
			.list_info {display: inline-block; width: 12rem;}
			.bold_text {font-weight: bold;}
			.code {color:#007bff;font-size: medium;}
		</style>
	  </head>
	  <body class="bg-light"> 
	  ';
            $html_header = sprintf($html_header, $languages[$wizard_lang]['title']);
            $html_body = '<div class="container">';
            $html_body_nav = '<div class="py-5 text-center"  style="padding-bottom: 1rem!important;">';
            $html_body_nav .= '<h2>CRMEB Swoole Compiler 安装向导</h2>';
            $html_body_nav .= '<p class="lead"> Version:2.0.2 Date:2019-01-09</p>';
            $html_body_nav .= '</div><hr>';

            // Environment information
            $html_body_environment = '
	<div class="col-12"  style="padding-top: 1rem!important;">
		<h5 class="text-center">检查当前环境</h5>
		<ul class="list-unstyled text-small">';
            $html_body_environment .= '<li><span class="list_info">操作系统 : </span>' . $env['os']['raw_name'] . '</li>';
            $html_body_environment .= '<li><span class="list_info">PHP版本 : </span>' . $env['php']['version'] . '</li>';
            $html_body_environment .= '<li><span class="list_info">PHP运行环境 : </span>' . $env['php']['sapi'] . '</li>';
            $html_body_environment .= '<li><span class="list_info">PHP配置文件 : </span>' . $env['php']['ini_loaded_file'] . '</li>';
            $html_body_environment .= '<li><span class="list_info">PHP扩展安装目录 : </span>' . $env['php']['extension_dir'] . '</li>';
            $html_body_environment .= '<li><span class="list_info">PHP是否线程安全 : </span>' . $env['php']['thread_safety'] . '</li>';
            $html_body_environment .= '<li><span class="list_info">是否安装swoole_loader : </span>' . $env['php']['swoole_loader']['status'] . '</li>';
            if (isset($sysInfo['swoole_loader']) and $sysInfo['swoole_loader']) {
                $html_body_environment .= '<li><span class="list_info">swoole_loader版本 : </span>' . $env['php']['swoole_loader']['version'] . '</li>';
            }
            if ($env['php']['bit'] == 32) {
                $html_body_environment .= '<li><span style="color:red">温馨提示：当前环境使用的PHP为 ' . $env['php']['bit'] . ' 位的PHP，Compiler 目前不支持 Debug 版本或 32 位的PHP，可在 phpinfo() 中查看对应位数，如果误报请忽略此提示</span></li>';
            }
            $html_body_environment .= '	</ul></div>';

            // Error infomation
            $html_error = "";
            if (!empty($env['php']['loaded_incompatible_extensions'])) {
                $html_error = '<hr>
		<div class="col-12"  style="padding-top: 1rem!important;">
		<h5 class="text-center" style="color:red">错误信息</h5>
		<p class="text-center" style="color:red">%s</p>
    </div>
		';
                $err_msg = "当前PHP包含与swoole_compiler_loader扩展不兼容的扩展" . implode(',', $env['php']['loaded_incompatible_extensions']) . "，请移除不兼容的扩展。";
                $html_error = sprintf($html_error, $err_msg);
            }

            // Check Loader Status
            $html_body_loader = '<hr>';
            if (empty($html_error)) {
                $html_body_loader .= '<div class="col-12" style="padding-top: 1rem!important;">';
                $html_body_loader .= '<h5 class="text-center">安装和配置Swoole Loader</h5>';
                $phpversion = substr($env['php']['version'], 0, 3);
                $phpversion = str_replace('.', '', $phpversion);
                $loaderFileName = '';
                if ($env['os']['name'] == "windows") {
                    $loaderFileName = 'php_swoole_loader_php' . $phpversion;
                    if ($env['php']['thread_safety'] == '非线程安全') {
                        $loaderFileName .= '_nzts_x64.dll';
                    } else {
                        $loaderFileName .= '_zts_x64.dll';
                    }
                } else {
                    if ($env['php']['thread_safety'] != '非线程安全') {
                        $loaderFileName = 'swoole_loader' . $phpversion . '_zts.so';
                    } else {

                        $loaderFileName = 'swoole_loader' . $phpversion . '.so';
                    }
                }
                $html_body_loader .= '<p><span class="bold_text">1 - 安装Swoole Loader</span></p><p>前往根目录 /help/swoole-loader/' . $loaderFileName . '扩展文件上传到当前PHP的扩展安装目录中：<br/><pre class="code">' . $env['php']['extension_dir'] . '</pre></p>';
                $html_body_loader .= '<p><span class="bold_text">2 - 修改php.ini配置</span>（如已修改配置，请忽略此步骤，不必重复添加）</p><p>';
                $html_body_loader .= '编辑此PHP配置文件：<span class="code">' . $env['php']['ini_loaded_file'] . '</span>，在此文件底部结尾处加入如下配置<br/>';
                if ($env['os']['name'] == "windows") {
                    $html_body_loader .= '<pre class="code">extension=' . $env['php']['extension_dir'] . DIRECTORY_SEPARATOR . $loaderFileName . '</pre>注意：需要名称和刚才上传到当前PHP的扩展安装目录中的文件名一致';
                } else {
                    $html_body_loader .= '<pre class="code">extension=' . $env['php']['extension_dir'] . DIRECTORY_SEPARATOR . $loaderFileName . '</pre>注意：需要名称和刚才上传到当前PHP的扩展安装目录中的文件名一致';
                }
                $html_body_loader .= '</p>';
                $html_body_loader .= '<p><span class="bold_text">3 - 重启服务</span></p><p>重启或重载PHP配置</p>';
                $html_body_loader .= '</div>';
            }

            // Body footer
            $html_body_footer = '<footer class="my-5 pt-5 text-muted text-center text-small">
	<p class="mb-1">CopyRight © 2018 - ' . date('Y') . ' Swoole.com 上海识沃网络科技有限公司</p>
  </footer>';
            $html_body .= $html_body_nav . '<div class="row">' . $html_body_environment . $html_error . $html_body_loader . '</div>' . $html_body_footer;
            $html_body .= '</div>';
            // Footer
            $html_footer = '
		<script src="https://lib.baomitu.com/jquery/3.3.1/jquery.min.js"></script>
		<script src="https://lib.baomitu.com/axios/0.18.0/axios.min.js"></script>
		<script src="https://lib.baomitu.com/twitter-bootstrap/4.1.0/js/bootstrap.min.js"></script>
		</body>
	</html>';
            // Make full html
            $html = $html_header . $html_body . $html_footer;
            echo $html;
        }
    }

    function w_getSysInfo($env)
    {
        $sysEnv = [];
        // Get content of phpinfo
        ob_start();
        phpinfo();
        $sysInfo = ob_get_contents();
        ob_end_clean();
        // Explode phpinfo content
        if ($env['php']['run_mode'] == 'cli') {
            $sysInfoList = explode('\n', $sysInfo);
        } else {
            $sysInfoList = explode('</tr>', $sysInfo);
        }
        foreach ($sysInfoList as $sysInfoItem) {
            if (preg_match('/thread safety/i', $sysInfoItem)) {
                $sysEnv['thread_safety'] = (preg_match('/(enabled|yes)/i', $sysInfoItem) != 0);
            }
            if (preg_match('/swoole_loader support/i', $sysInfoItem)) {
                $sysEnv['swoole_loader'] = (preg_match('/(enabled|yes)/i', $sysInfoItem) != 0);
            }
            if (preg_match('/swoole_loader version/i', $sysInfoItem)) {
                preg_match('/\d+.\d+.\d+/s', $sysInfoItem, $match);
                $sysEnv['swoole_loader_version'] = isset($match[0]) ? $match[0] : false;
            }
        }
        return $sysEnv;
    }

    //读取版本号
    function getversion()
    {
        try {
            $curent_version = @file(app()->getRootPath() . '.version');
        } catch (\Exception $e) {
            $curent_version = [];
        }
        if ($curent_version && is_array($curent_version)) {
            foreach ($curent_version as $val) {
                list($k, $v) = explode('=', $val);
                $version_arr[$k] = $v;
            }
        }
        return $version_arr;
    }

    //写入安装信息
    function installlog()
    {
        $mt_rand_str = $this->sp_random_string(6);
        $str_constant = "<?php" . PHP_EOL . "define('INSTALL_DATE'," . time() . ");" . PHP_EOL . "define('SERIALNUMBER','" . $mt_rand_str . "');";
        @file_put_contents(app()->getRootPath() . '.constant', $str_constant);
    }

    function sp_random_string($len = 8)
    {
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
        );
        $charsLen = count($chars) - 1;
        shuffle($chars);    // 将数组打乱
        $output = "";
        mt_srand();
        for ($i = 0; $i < $len; $i++) {
            $output .= $chars[mt_rand(0, $charsLen)];
        }
        return $output;
    }

    //判断权限
    function testwrite($d)
    {
        if (is_file($d)) {
            if (is_writeable($d)) {
                return true;
            }
            return false;

        } else {
            $tfile = "_test.txt";
            $fp = @fopen($d . "/" . $tfile, "w");
            if (!$fp) {
                return false;
            }
            fclose($fp);
            $rs = @unlink($d . "/" . $tfile);
            if ($rs) {
                return true;
            }
            return false;
        }
    }

    // 获取客户端IP地址
    function get_client_ip()
    {
        static $ip = NULL;
        if ($ip !== NULL)
            return $ip;
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos)
                unset($arr[$pos]);
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
        return $ip;
    }

    //创建目录
    function dir_create($path, $mode = 0777)
    {
        if (is_dir($path))
            return TRUE;
        $ftp_enable = 0;
        $path = $this->dir_path($path);
        $temp = explode('/', $path);
        $cur_dir = '';
        $max = count($temp) - 1;
        for ($i = 0; $i < $max; $i++) {
            $cur_dir .= $temp[$i] . '/';
            if (@is_dir($cur_dir))
                continue;
            @mkdir($cur_dir, 0777, true);
            @chmod($cur_dir, 0777);
        }
        return is_dir($path);
    }

    function dir_path($path)
    {
        $path = str_replace('\\', '/', $path);
        if (substr($path, -1) != '/')
            $path = $path . '/';
        return $path;
    }

    function sql_split($sql, $tablepre)
    {

        if ($tablepre != "tp_")
            $sql = str_replace("tp_", $tablepre, $sql);

        $sql = preg_replace("/TYPE=(InnoDB|MyISAM|MEMORY)( DEFAULT CHARSET=[^; ]+)?/", "ENGINE=\\1 DEFAULT CHARSET=utf8", $sql);

        $sql = str_replace("\r", "\n", $sql);
        $ret = array();
        $num = 0;
        $queriesarray = explode(";\n", trim($sql));
        unset($sql);
        foreach ($queriesarray as $query) {
            $ret[$num] = '';
            $queries = explode("\n", trim($query));
            $queries = array_filter($queries);
            foreach ($queries as $query) {
                $str1 = substr($query, 0, 1);
                if ($str1 != '#' && $str1 != '-')
                    $ret[$num] .= $query;
            }
            $num++;
        }
        return $ret;
    }

    // 递归删除文件夹
    function delFile($dir, $file_type = '')
    {
        if (is_dir($dir)) {
            $files = scandir($dir);
            //打开目录 //列出目录中的所有文件并去掉 . 和 ..
            foreach ($files as $filename) {
                if ($filename != '.' && $filename != '..') {
                    if (!is_dir($dir . '/' . $filename)) {
                        if (empty($file_type)) {
                            unlink($dir . '/' . $filename);
                        } else {
                            if (is_array($file_type)) {
                                //正则匹配指定文件
                                if (preg_match($file_type[0], $filename)) {
                                    unlink($dir . '/' . $filename);
                                }
                            } else {
                                //指定包含某些字符串的文件
                                if (false != stristr($filename, $file_type)) {
                                    unlink($dir . '/' . $filename);
                                }
                            }
                        }
                    } else {
                        $this->delFile($dir . '/' . $filename);
                        rmdir($dir . '/' . $filename);
                    }
                }
            }
        } else {
            if (file_exists($dir)) unlink($dir);
        }
    }
}
