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
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\user\UserBillServices;
use app\services\user\UserBrokerageServices;
use app\services\user\UserMoneyServices;
use app\services\user\UserBrokerageFrozenServices;
use think\facade\Db;
use app\services\user\UserServices;


class UpgradeController
{
    /**
     * @param string $field
     * @param int $n
     * @return bool
     */
    public function setIsUpgrade(string $field, int $n = 0)
    {
        $upgrade = parse_ini_file(app()->getRootPath() . '/.upgrade');
        if ($n) {
            if (!is_array($upgrade)) {
                $upgrade = [];
            }
            $string = '';
            foreach ($upgrade as $key => $item) {
                $string .= $key . '=' . $item . "\r\n";
            }
            $string .= $field . '=' . $n . "\r\n";
            file_put_contents(app()->getRootPath() . '/.upgrade', $string);
            return true;
        } else {
            if (!is_array($upgrade)) {
                return false;
            }
            return isset($upgrade[$field]);
        }
    }

    /**
     * 获取当前版本号
     * @return array
     */
    public function getversion($str)
    {
        $version_arr = [];
        $curent_version = @file(app()->getRootPath() . $str);

        foreach ($curent_version as $val) {
            list($k, $v) = explode('=', $val);
            $version_arr[$k] = $v;
        }
        return $version_arr;
    }

    public function index(Request $request)
    {
        $data = $this->upData();
        $Title = "CRMEB升级程序";
        $Powered = "Powered by CRMEB";

        //获取当前版本号
        $version_now = $this->getversion('.version')['version'];
        $version_new = $data['new_version'];
        $isUpgrade = true;
        $executeIng = false;

        return view('/upgrade/step1', [
            'title' => $Title,
            'powered' => $Powered,
            'version_now' => $version_now,
            'version_new' => $version_new,
            'isUpgrade' => json_encode($isUpgrade),
            'executeIng' => json_encode($executeIng),
            'next' => 1,
            'action' => 'upgrade'
        ]);

    }

    public function upgrade(Request $request)
    {
        list($sleep, $page, $prefix) = $request->getMore([
            ['sleep', 0],
            ['page', 1],
            ['prefix', 'eb_'],
        ], true);
        $data = $this->upData();
        $code_now = $this->getversion('.version')['version_code'];
        $sql_arr = [];
        foreach ($data['update_sql'] as $items) {
            if ($items['code'] > $code_now) {
                $sql_arr[] = $items;
            }
        }
        if (!isset($sql_arr[$sleep])) {
            file_put_contents(app()->getRootPath() . '.version', "version=" . $data['new_version'] . "\nversion_code=" . $data['new_code']);
            return app('json')->successful(['sleep' => -1]);
        }
        $sql = $sql_arr[$sleep];
        Db::startTrans();
        try {
            if ($sql['type'] == 1) {
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $table = $prefix . $sql['table'];
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (!empty(Db::query($findSql))) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = $table . '表已存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    Db::execute($upSql);
                    $item['table'] = $table;
                    $item['status'] = 1;
                    $item['error'] = $table . '表添加成功';
                    $item['sleep'] = $sleep + 1;
                    $item['add_time'] = date('Y-m-d H:i:s', time());
                    Db::commit();
                    return app('json')->successful($item);
                }
            } elseif ($sql['type'] == 2) {
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $table = $prefix . $sql['table'];
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (empty(Db::query($findSql))) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = $table . '表不存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    Db::execute($upSql);
                    $item['table'] = $table;
                    $item['status'] = 1;
                    $item['error'] = $table . '表删除成功';
                    $item['sleep'] = $sleep + 1;
                    $item['add_time'] = date('Y-m-d H:i:s', time());
                    Db::commit();
                    return app('json')->successful($item);
                }
            } elseif ($sql['type'] == 3) {
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $table = $prefix . $sql['table'];
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (!empty(Db::query($findSql))) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = $table . '表中' . $sql['field'] . '已存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    Db::execute($upSql);
                    $item['table'] = $table;
                    $item['status'] = 1;
                    $item['error'] = $table . '表中' . $sql['field'] . '字段添加成功';
                    $item['sleep'] = $sleep + 1;
                    $item['add_time'] = date('Y-m-d H:i:s', time());
                    Db::commit();
                    return app('json')->successful($item);
                }
            } elseif ($sql['type'] == 4) {
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $table = $prefix . $sql['table'];
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (empty(Db::query($findSql))) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = $table . '表中' . $sql['field'] . '不存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    Db::execute($upSql);
                    $item['table'] = $table;
                    $item['status'] = 1;
                    $item['error'] = $table . '表中' . $sql['field'] . '字段修改成功';
                    $item['sleep'] = $sleep + 1;
                    $item['add_time'] = date('Y-m-d H:i:s', time());
                    Db::commit();
                    return app('json')->successful($item);
                }
            } elseif ($sql['type'] == 5) {
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $table = $prefix . $sql['table'];
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (empty(Db::query($findSql))) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = $table . '表中' . $sql['field'] . '不存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    Db::execute($upSql);
                    $item['table'] = $table;
                    $item['status'] = 1;
                    $item['error'] = $table . '表中' . $sql['field'] . '字段删除成功';
                    $item['sleep'] = $sleep + 1;
                    $item['add_time'] = date('Y-m-d H:i:s', time());
                    Db::commit();
                    return app('json')->successful($item);
                }
            } elseif ($sql['type'] == 6) {
                $table = $prefix . $sql['table'] ?? '';
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (!empty(Db::query($findSql))) {
                        $item['table'] = $prefix . $sql['table'];
                        $item['status'] = 1;
                        $item['error'] = $table . '表中此数据已存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    if (isset($sql['whereSql']) && $sql['whereSql']) {
						$whereTable = $prefix . $sql['whereTable'] ?? '';
                        $whereSql = str_replace('@whereTable', $whereTable, $sql['whereSql']);
                        $tabId = Db::query($whereSql)[0]['tabId'] ?? 0;
                        if (!$tabId) {
                            $item['table'] = $whereTable;
                            $item['status'] = 1;
                            $item['error'] = '查询父类ID不存在';
                            $item['sleep'] = $sleep + 1;
                            $item['add_time'] = date('Y-m-d H:i:s', time());
                            Db::commit();
                            return app('json')->successful($item);
                        }
                        $upSql = str_replace('@tabId', $tabId, $upSql);
                    }
                    if (Db::execute($upSql)) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = '数据添加成功';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
            } elseif ($sql['type'] == 7) {
                $table = $prefix . $sql['table'] ?? '';
                $whereTable = $prefix . $sql['whereTable'] ?? '';
                if (isset($sql['findSql']) && $sql['findSql']) {
                    $findSql = str_replace('@table', $table, $sql['findSql']);
                    if (empty(Db::query($findSql))) {
                        $item['table'] = $prefix . $sql['table'];
                        $item['status'] = 1;
                        $item['error'] = $table . '表中此数据不存在';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    if (isset($sql['whereSql']) && $sql['whereSql']) {
                        $whereSql = str_replace('@whereTable', $whereTable, $sql['whereSql']);
                        $tabId = Db::query($whereSql)[0]['tabId'] ?? 0;
                        if (!$tabId) {
                            $item['table'] = $whereTable;
                            $item['status'] = 1;
                            $item['error'] = '查询父类ID不存在';
                            $item['sleep'] = $sleep + 1;
                            $item['add_time'] = date('Y-m-d H:i:s', time());
                            Db::commit();
                            return app('json')->successful($item);
                        }
                        $upSql = str_replace('@tabId', $tabId, $upSql);
                    }
                    if (Db::execute($upSql)) {
                        $item['table'] = $table;
                        $item['status'] = 1;
                        $item['error'] = '数据修改成功';
                        $item['sleep'] = $sleep + 1;
                        $item['add_time'] = date('Y-m-d H:i:s', time());
                        Db::commit();
                        return app('json')->successful($item);
                    }
                }
            } elseif ($sql['type'] == 8) {

            } elseif ($sql['type'] == -1) {
                $table = $prefix . $sql['table'];
                if (isset($sql['sql']) && $sql['sql']) {
                    $upSql = $sql['sql'];
                    $upSql = str_replace('@table', $table, $upSql);
                    if (isset($sql['new_table']) && $sql['new_table']) {
                        $new_table = $prefix . $sql['new_table'];
                        $upSql = str_replace('@new_table', $new_table, $upSql);
                    }
                    Db::execute($upSql);
                    $item['table'] = $table;
                    $item['status'] = 1;
                    $item['error'] = $table . '更新sql执行成功';
                    $item['sleep'] = $sleep + 1;
                    $item['add_time'] = date('Y-m-d H:i:s', time());
                    Db::commit();
                    return app('json')->successful($item);
                }
            }
        } catch (\Throwable $e) {
            $item['table'] = $prefix . $sql['table'];
            $item['status'] = 0;
            $item['sleep'] = $sleep + 1;
            $item['add_time'] = date('Y-m-d H:i:s', time());
            $item['error'] = $e->getMessage();
            Db::rollBack();
            return app('json')->successful($item);
        }
    }

    public function upData()
    {
        $data['new_version'] = 'CRMEB-PRO v2.2.0';
        $data['new_code'] = 220;
        $data['update_sql'] = [
			[
                'code' => 220,
                'type' => 6,
                'table' => "system_config_tab",
                'findSql' => "select id from @table where eng_title = 'work'",
                'sql' => "INSERT INTO `@table` (`id`, `is_store`, `pid`, `title`, `eng_title`, `status`, `info`, `icon`, `type`, `sort`) VALUES (null , '0', '0', '企业微信', 'work', '1', '0', '', '0', '0')"
            ],
            [
                'code' => 220,
                'type' => 6,
                'table' => "system_config_tab",
                'findSql' => "select id from @table where eng_title = 'erp_config'",
                'sql' => "INSERT INTO `@table` (`id`, `is_store`, `pid`, `title`, `eng_title`, `status`, `info`, `icon`, `type`, `sort`) VALUES (null , 0, 65, 'ERP配置', 'erp_config', 1, 0, '', 0, 0)"
            ],
            [
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'wechat_work_build_secret'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'work'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'wechat_work_build_secret', 'text', 'input', @tabId, '', 1, '', 100, 0, '', '应用Secret', '企业微信自建应用secret，可前往【应用管理】自建应用应用内查看secret', 0, 1)"
            ],
            [
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'wechat_work_build_agent_id'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'work'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'wechat_work_build_agent_id', 'text', 'input', @tabId, '', 1, '', 100, 0, '', '应用AgentId', '企业微信自建应用AgentId，可前往【应用管理】进行自建应用', 0, 1)"
            ],
			[
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'wechat_work_user_secret'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'work'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'wechat_work_user_secret', 'text', 'input', @tabId, '', 1, '', 100, 0, '', 'Secret', '企业微信客户secret，可前往【客户与上下游】查看secret', 0, 1)"
            ],
			[
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'wechat_work_aes_key'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'work'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'wechat_work_aes_key', 'text', 'input', @tabId, '', 1, '', 100, 0, '', 'EncodingAESKey', '企业微信通讯录EncodingAESKey', 0, 1)"
            ],
			[
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'wechat_work_token'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'work'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'wechat_work_token', 'text', 'input', @tabId, '', 1, '', 100, 0, '', 'Token', '通讯录事件接收服务器token', 0, 1)"
            ],
			[
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'wechat_work_address_secret'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'work'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'wechat_work_address_secret', 'text', 'input', @tabId, '', 1, '', 100, 0, '', 'Secret', '通讯录Secret，可前往企业微信【管理工具】->【通讯录同步】查看secret', 0, 1)"
            ],
			[
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'wechat_work_corpid'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'work'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'wechat_work_corpid', 'text', 'input', @tabId, '', 1, '', 100, 0, '', '企业ID', '企业微信ID，前往企业微信后台【我的企业】企业信息中可查看', 0, 1)"
            ],
            [
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'withdraw_fee'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'extract_set'",
                'sql' => "INSERT INTO `@table` VALUES (null, 0, 'withdraw_fee', 'text', 'input', @tabId, '', 1, '', 100, 0, '20', '提现手续费（%）', '提现金额百分比（0~100）%，例如：提现100，手续费20%，实际到账100 * （1-20/100）', 0, 1)"
            ],
			[
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'links_open'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'system_pc'",
                'sql' => "INSERT INTO `@table` VALUES (NULL, '0', 'links_open', 'radio', '', @tabId, '1=>开启\r\n0=>关闭', '1', '', '0', '0', '1', '友情链接开关', '友情链接开关', '0', '1')"
            ],
            [
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'erp_open'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'erp_config'",
                'sql' => "INSERT INTO `@table` VALUES (NULL, 0, 'erp_open', 'radio', '', @tabId, '1=>开启\n0=>关闭', 1, '', 0, 0, '0', '是否开启ERP', '是否开启ERP', 0, 1)"
            ],
			[
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'erp_type'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'erp_config'",
                'sql' => "INSERT INTO `@table` VALUES (NULL, 0, 'erp_type', 'radio', '', @tabId, '0=>未选择\n1=>聚水潭', 1, '', 0, 0, '1', '接口选择', 'ERP接口选择', 0, 1)"
            ],
			[
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'jst_appkey'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'erp_config'",
                'sql' => "INSERT INTO `@table` VALUES (NULL, 0, 'jst_appkey', 'text', 'input', @tabId, '', 1, '', 100, 0, '', '聚水潭AppKey', '聚水潭AppKey',  0, 1)"
            ],
            [
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'jst_appsecret'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'erp_config'",
                'sql' => "INSERT INTO `@table` VALUES (NULL, 0, 'jst_appsecret', 'text', 'input', @tabId, '', 1, '', 100, 0, '', '聚水潭AppSecret', '聚水潭AppSecret', 0, 1)"
            ],
			[
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'jst_default_shopid'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'erp_config'",
                'sql' => "INSERT INTO `@table` VALUES (NULL, 0, 'jst_default_shopid', 'text', 'input', @tabId, '', 1, '', 100, 0, '', '聚水潭平台店铺ID', '聚水潭平台店铺ID', 0, 1)"
            ],
            [
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'jst_login_account'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'erp_config'",
                'sql' => "INSERT INTO `@table` VALUES (NULL, '0', 'jst_login_account', 'text', 'input', @tabId, '', '1', '', '100', '0', '', '聚水潭授权账号', '聚水潭开放平台授权登录账号', '0', '1')"
            ],
            [
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'jst_login_password'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'erp_config'",
                'sql' => "INSERT INTO `@table` VALUES (NULL, '0', 'jst_login_password', 'text', 'input', @tabId, '', '1', '', '100', '0', '', '聚水潭授权密码', '聚水潭开放平台授权登录的密码', '0', '1')"
            ],
            [
                'code' => 220,
                'type' => 6,
                'table' => "system_config",
                'whereTable' => "system_config_tab",
                'findSql' => "select id from @table where menu_name = 'start_login_logo'",
                'whereSql' => "select id as tabId from @whereTable where eng_title = 'store_base'",
                'sql' => "INSERT INTO `@table` VALUES (NULL, '0', 'start_login_logo', 'upload', '', @tabId, '', '1', '', '0', '0', '\"\"', '门店登录LOGO', '门店登录页面右上角LOGO，建议尺寸：170x50', '0', '1')"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "user",
                'field' => "is_del",
                'findSql' => "show columns from `@table` like 'is_del'",
                'sql' => "ALTER TABLE `@table` ADD `is_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否删除' AFTER `street`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "user",
                'field' => "delete_time",
                'findSql' => "show columns from `@table` like 'delete_time'",
                'sql' => "ALTER TABLE `@table` ADD `delete_time` TIMESTAMP NULL DEFAULT NULL COMMENT '删除时间'"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "user",
                'field' => "login_city",
                'findSql' => "show columns from `@table` like 'login_city'",
                'sql' => "ALTER TABLE `@table` ADD `login_city` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '登录城市' AFTER `login_type`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "user",
                'field' => "work_uid",
                'findSql' => "show columns from `@table` like 'work_uid'",
                'sql' => "ALTER TABLE `@table` ADD `work_uid` int(11) NOT NULL DEFAULT '0' COMMENT '绑定企业微信成员uid' AFTER `spread_lottery`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "user",
                'field' => "work_userid",
                'findSql' => "show columns from `@table` like 'work_userid'",
                'sql' => "ALTER TABLE `@table` ADD `work_userid` VARCHAR(64) NULL DEFAULT NULL COMMENT '绑定企业微信成员uid' AFTER `work_uid`"
            ],
			[
                'code' => 220,
                'type' => -1,
                'table' => "user",
                'sql' => "ALTER TABLE `@table` ADD INDEX(`work_uid`)"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "wechat_user",
                'field' => "is_del",
                'findSql' => "show columns from `@table` like 'is_del'",
                'sql' => "ALTER TABLE `@table` ADD `is_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否删除' AFTER `is_complete`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_service",
                'field' => "is_del",
                'findSql' => "show columns from `@table` like 'is_del'",
                'sql' => "ALTER TABLE `@table` ADD `is_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否删除' AFTER `uniqid`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_product",
                'field' => "store_label_id",
                'findSql' => "show columns from `@table` like 'store_label_id'",
                'sql' => "ALTER TABLE `@table` ADD `store_label_id` LONGTEXT NULL DEFAULT NULL COMMENT '商品标签iDS' AFTER `is_support_refund`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_product",
                'field' => "ensure_id",
                'findSql' => "show columns from `@table` like 'ensure_id'",
                'sql' => "ALTER TABLE `@table` ADD `ensure_id` LONGTEXT NULL DEFAULT NULL COMMENT '商品保障服务ids' AFTER `store_label_id`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_product",
                'field' => "specs",
                'findSql' => "show columns from `@table` like 'specs'",
                'sql' => "ALTER TABLE `@table` ADD `specs` LONGTEXT NULL DEFAULT NULL COMMENT '商品参数' AFTER `ensure_id`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_product",
                'field' => "specs_id",
                'findSql' => "show columns from `@table` like 'specs_id'",
                'sql' => "ALTER TABLE `@table` ADD `specs_id` int(11) NOT NULL DEFAULT '0' COMMENT '参数模版ID' AFTER `specs`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_seckill",
                'field' => "store_label_id",
                'findSql' => "show columns from `@table` like 'store_label_id'",
                'sql' => "ALTER TABLE `@table` ADD `store_label_id` LONGTEXT NULL DEFAULT NULL COMMENT '商品标签iDS'"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_seckill",
                'field' => "ensure_id",
                'findSql' => "show columns from `@table` like 'ensure_id'",
                'sql' => "ALTER TABLE `@table` ADD `ensure_id` LONGTEXT NULL DEFAULT NULL COMMENT '商品保障服务ids' AFTER `store_label_id`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_seckill",
                'field' => "specs",
                'findSql' => "show columns from `@table` like 'specs'",
                'sql' => "ALTER TABLE `@table` ADD `specs` LONGTEXT NULL DEFAULT NULL COMMENT '商品参数' AFTER `ensure_id`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_combination",
                'field' => "store_label_id",
                'findSql' => "show columns from `@table` like 'store_label_id'",
                'sql' => "ALTER TABLE `@table` ADD `store_label_id` LONGTEXT NULL DEFAULT NULL COMMENT '商品标签iDS'"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_combination",
                'field' => "ensure_id",
                'findSql' => "show columns from `@table` like 'ensure_id'",
                'sql' => "ALTER TABLE `@table` ADD `ensure_id` LONGTEXT NULL DEFAULT NULL COMMENT '商品保障服务ids' AFTER `store_label_id`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_combination",
                'field' => "specs",
                'findSql' => "show columns from `@table` like 'specs'",
                'sql' => "ALTER TABLE `@table` ADD `specs` LONGTEXT NULL DEFAULT NULL COMMENT '商品参数' AFTER `ensure_id`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_bargain",
                'field' => "store_label_id",
                'findSql' => "show columns from `@table` like 'store_label_id'",
                'sql' => "ALTER TABLE `@table` ADD `store_label_id` LONGTEXT NULL DEFAULT NULL COMMENT '商品标签iDS'"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_bargain",
                'field' => "ensure_id",
                'findSql' => "show columns from `@table` like 'ensure_id'",
                'sql' => "ALTER TABLE `@table` ADD `ensure_id` LONGTEXT NULL DEFAULT NULL COMMENT '商品保障服务ids' AFTER `store_label_id`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_bargain",
                'field' => "specs",
                'findSql' => "show columns from `@table` like 'specs'",
                'sql' => "ALTER TABLE `@table` ADD `specs` LONGTEXT NULL DEFAULT NULL COMMENT '商品参数' AFTER `ensure_id`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_integral",
                'field' => "store_label_id",
                'findSql' => "show columns from `@table` like 'store_label_id'",
                'sql' => "ALTER TABLE `@table` ADD `store_label_id` LONGTEXT NULL DEFAULT NULL COMMENT '商品标签iDS'"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_integral",
                'field' => "ensure_id",
                'findSql' => "show columns from `@table` like 'ensure_id'",
                'sql' => "ALTER TABLE `@table` ADD `ensure_id` LONGTEXT NULL DEFAULT NULL COMMENT '商品保障服务ids' AFTER `store_label_id`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_integral",
                'field' => "specs",
                'findSql' => "show columns from `@table` like 'specs'",
                'sql' => "ALTER TABLE `@table` ADD `specs` LONGTEXT NULL DEFAULT NULL COMMENT '商品参数' AFTER `ensure_id`"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_product_attr_result",
                'sql' => "ALTER TABLE `@table` ADD `id` INT(10) NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (`id`)"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_product_reply",
                'field' => "praise",
                'findSql' => "show columns from `@table` like 'praise'",
                'sql' => "ALTER TABLE `@table` ADD `praise` int(11) NOT NULL DEFAULT '0' COMMENT '点赞' AFTER `add_time`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_product_reply",
                'field' => "views_num",
                'findSql' => "show columns from `@table` like 'views_num'",
                'sql' => "ALTER TABLE `@table` ADD `views_num` int(11) NOT NULL DEFAULT '0' COMMENT '浏览量' AFTER `praise`"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_product_relation",
                'sql' => "ALTER TABLE `@table` CHANGE `product_id` `relation_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' COMMENT '关联ID'"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "system_admin",
                'field' => "phone",
                'findSql' => "show columns from `@table` like 'phone'",
                'sql' => "ALTER TABLE `@table` ADD `phone` VARCHAR(32) NOT NULL DEFAULT '' COMMENT '用户电话' AFTER `real_name`"
            ],
			[
                'code' => 220,
                'type' => -1,
                'table' => "store_coupon_issue",
                'sql' => "ALTER TABLE `@table` CHANGE `type` `type` TINYINT(2) NOT NULL DEFAULT '0' COMMENT '优惠券类型 0-通用 1-品类券 2-商品券' AFTER `cid`"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_coupon_issue",
                'sql' => "ALTER TABLE `@table` CHANGE `receive_type` `receive_type` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '1 手动领取，2 新人券，3赠送券，4会员券' AFTER `type`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_coupon_issue",
                'field' => "coupon_type",
                'findSql' => "show columns from `@table` like 'coupon_type'",
                'sql' => "ALTER TABLE `@table` ADD `coupon_type`  TINYINT(1) NOT NULL DEFAULT '1' COMMENT '1:满减券2:折扣券' AFTER `receive_type`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "user_extract",
                'field' => "extract_fee",
                'findSql' => "show columns from `@table` like 'extract_fee'",
                'sql' => "ALTER TABLE `@table` ADD `extract_fee` decimal(12,2) unsigned NOT NULL DEFAULT '0.00' COMMENT '手续费金额' AFTER `extract_price`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "user_label",
                'field' => "tag_id",
                'findSql' => "show columns from `@table` like 'tag_id'",
                'sql' => "ALTER TABLE `@table` ADD `tag_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '企业微信同步标签id' AFTER `label_name`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_order_cart_info",
                'field' => "uid",
                'findSql' => "show columns from `@table` like 'uid'",
                'sql' => "ALTER TABLE `@table` ADD `uid` INT(10) NOT NULL DEFAULT '0' COMMENT '用户uid' AFTER `id`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_order_cart_info",
                'field' => "promotions_id",
                'findSql' => "show columns from `@table` like 'promotions_id'",
                'sql' => "ALTER TABLE `@table` ADD `promotions_id` LONGTEXT NULL DEFAULT NULL COMMENT '关联使用优惠活动ID' AFTER `product_type`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_order_cart_info",
                'field' => "is_gift",
                'findSql' => "show columns from `@table` like 'is_gift'",
                'sql' => "ALTER TABLE `@table` ADD `is_gift` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否是赠品1:是0:否' AFTER `promotions_id`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_order",
                'field' => "promotions_price",
                'findSql' => "show columns from `@table` like 'promotions_price'",
                'sql' => "ALTER TABLE `@table` ADD `promotions_price` DECIMAL(8,2) NOT NULL DEFAULT '0.00' COMMENT '优惠活动优惠金额' AFTER `coupon_price`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_order",
                'field' => "promotions_give",
                'findSql' => "show columns from `@table` like 'promotions_give'",
                'sql' => "ALTER TABLE `@table` ADD `promotions_give` LONGTEXT NULL DEFAULT NULL COMMENT '优惠活动赠送' AFTER `custom_form`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_order",
                'field' => "give_integral",
                'findSql' => "show columns from `@table` like 'give_integral'",
                'sql' => "ALTER TABLE `@table` ADD `give_integral` int(10) NOT NULL DEFAULT '0' COMMENT '优惠活动赠送积分' AFTER `promotions_give`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_order",
                'field' => "give_coupon",
                'findSql' => "show columns from `@table` like 'give_coupon'",
                'sql' => "ALTER TABLE `@table` ADD `give_coupon` LONGTEXT NULL DEFAULT NULL COMMENT '优惠活动赠送优惠券' AFTER `give_integral`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_order",
                'field' => "erp_id",
                'findSql' => "show columns from `@table` like 'erp_id'",
                'sql' => "ALTER TABLE `@table` ADD `erp_id` int(10) NOT NULL DEFAULT '0' COMMENT 'ERP内部单号' AFTER `give_coupon`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_order",
                'field' => "erp_order_id",
                'findSql' => "show columns from `@table` like 'erp_order_id'",
                'sql' => "ALTER TABLE `@table` ADD `erp_order_id` varchar(32) NOT NULL DEFAULT '' COMMENT 'ERP订单ID' AFTER `erp_id`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "system_store_staff",
                'field' => "is_cashier",
                'findSql' => "show columns from `@table` like 'is_cashier'",
                'sql' => "ALTER TABLE `@table` ADD `is_cashier` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否收银员1=是，0=否	' AFTER `is_manager`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_product_log",
                'field' => "delete_time",
                'findSql' => "show columns from `@table` like 'delete_time'",
                'sql' => "ALTER TABLE `@table` ADD `delete_time` TIMESTAMP NULL DEFAULT NULL COMMENT '删除时间'"
            ],
			[
                'code' => 220,
                'type' => -1,
                'table' => "system_config",
                'sql' => "UPDATE `@table` set `desc` = '建议尺寸：140 * 60px' where `menu_name` = 'pc_logo'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "system_config",
                'sql' => "UPDATE `@table` set `desc` = '开启后下单页面支持到店自提，需要在门店->门店列表中增加门店且开启自提，关闭则隐藏此功能' where `menu_name` = 'store_self_mention'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "system_menus",
                'sql' => "ALTER TABLE `@table` CHANGE `icon` `icon` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '图标'"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_order_invoice",
                'field' => "invoice_amount",
                'findSql' => "show columns from `@table` like 'invoice_amount'",
                'sql' => "ALTER TABLE `@table` ADD `invoice_amount` DECIMAL(8,2) NOT NULL DEFAULT '0' COMMENT '开票金额' AFTER `invoice_number`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_order_invoice",
                'field' => "is_del",
                'findSql' => "show columns from `@table` like 'is_del'",
                'sql' => "ALTER TABLE `@table` ADD `is_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否删除' AFTER `invoice_time`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "system_store",
                'field' => "erp_shop_id",
                'findSql' => "show columns from `@table` like 'erp_shop_id'",
                'sql' => "ALTER TABLE `@table` ADD `erp_shop_id` INT(11) NULL DEFAULT NULL COMMENT 'EPR门店id' AFTER `id`"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "page_categroy",
                'new_table' => "page_category",
                'sql' => "RENAME TABLE `@table` TO `@new_table`"
            ],
            [
                'code' => 220,
                'type' => 3,
                'table' => "store_branch_product_attr_value",
                'field' => "code",
                'findSql' => "show columns from `@table` like 'code'",
                'sql' => "ALTER TABLE `@table` ADD `code` varchar(50) NOT NULL DEFAULT '' COMMENT '商品编码' AFTER `bar_code`"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_product_label",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `type` TINYINT(1) NULL DEFAULT '1' COMMENT '类型：1平台2:门店',
    `store_id` INT(11) NOT NULL DEFAULT '0' COMMENT '门店ID',
    `label_cate` int(10) NOT NULL DEFAULT '0' COMMENT '标签分类',
    `label_name` varchar(255) NOT NULL DEFAULT '' COMMENT '标签名称',
    `sort` int(10) NOT NULL DEFAULT '0' COMMENT '排序',
    `add_time` int(10) NOT NULL DEFAULT '0' COMMENT '添加时间',
    PRIMARY KEY (`id`),
    KEY `label_cate` (`label_cate`),
    KEY `type` (`type`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='商品标签表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_product_ensure",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `type` TINYINT(1) NULL DEFAULT '1' COMMENT '类型：1平台2:门店',
    `store_id` INT(11) NOT NULL DEFAULT '0' COMMENT '门店ID',
    `name` varchar(255) NOT NULL DEFAULT '' COMMENT '标签名称',
    `image` varchar(255) NOT NULL DEFAULT '' COMMENT '图片',
    `desc` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
    `sort` int(10) NOT NULL DEFAULT '0' COMMENT '排序',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
    `add_time` int(10) NOT NULL DEFAULT '0' COMMENT '添加时间',
    PRIMARY KEY (`id`),
    KEY `store_id` (`store_id`),
    KEY `type` (`type`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='商品保障服务表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_product_specs",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `type` TINYINT(1) NULL DEFAULT '1' COMMENT '类型：1平台2:门店',
    `store_id` INT(11) NOT NULL DEFAULT '0' COMMENT '门店ID',
    `temp_id` INT(11) NOT NULL DEFAULT '0' COMMENT '模版ID',
    `name` varchar(255) NOT NULL DEFAULT '' COMMENT '参数名称',
    `value` varchar(255) NOT NULL DEFAULT '' COMMENT '参数值',
    `sort` int(10) NOT NULL DEFAULT '0' COMMENT '排序',
    `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
    `add_time` int(10) NOT NULL DEFAULT '0' COMMENT '添加时间',
    PRIMARY KEY (`id`),
    KEY `store_id` (`store_id`),
    KEY `type` (`type`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='商品参数表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_product_reply_comment",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL DEFAULT '0',
  `praise` int(11) NOT NULL DEFAULT '0' COMMENT '点赞数量',
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '门店id',
  `content` varchar(1000) NOT NULL DEFAULT '' COMMENT '回复内容',
  `pid` int(11) NOT NULL DEFAULT '0' COMMENT '上级回复id',
  `reply_id` int(11) NOT NULL DEFAULT '0' COMMENT '评论id',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `reply_id` (`reply_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品评价回复表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_product_label_auxiliary",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label_id` int(11) NOT NULL DEFAULT '0',
  `product_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `label_id` (`label_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品标签辅助表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_promotions",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
    `id` INT(10) NOT NULL AUTO_INCREMENT ,
    `pid` INT(10)  NOT NULL DEFAULT '0' COMMENT '同一活动ID' ,
    `type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '类型：1平台2:门店' ,
    `store_id` INT(10) NOT NULL DEFAULT '0' COMMENT '门店ID' ,
    `promotions_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '活动类型：1：限时折扣2:第N件N折3:满减满折4:满送' ,
    `promotions_cate` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '优惠方式1:阶梯2:循环',
    `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '活动名称' ,
    `title` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '优惠标题' ,
    `desc` TEXT NULL DEFAULT NULL COMMENT '优惠内容详情' ,
    `threshold_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '门槛类型1:满N元2:满N件' ,
    `threshold` DECIMAL(8,2) NOT NULL DEFAULT '0.00' COMMENT '优惠门槛' ,
    `discount_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '优惠类型1:满减2:满折' ,
    `n_piece_n_discount` TINYINT(1) NOT NULL DEFAULT '1' COMMENT 'n件n折类型：1:第二件半件2:买1送1 3:自定义' ,
    `discount` DECIMAL(8,2) NOT NULL DEFAULT '0.00' COMMENT '优惠' ,
    `give_integral` INT(10) NOT NULL DEFAULT '0' COMMENT '赠送积分' ,
    `give_coupon_id` LONGTEXT  NULL DEFAULT NULL COMMENT '赠送优惠券ID' ,
    `give_product_id` LONGTEXT  NULL DEFAULT NULL COMMENT '赠送商品ID' ,
    `give_product_unique` varchar(20) NOT NULL DEFAULT '' COMMENT '赠送商品规格唯一值',
    `overlay` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '优惠叠加' ,
    `label_id` LONGTEXT NULL DEFAULT NULL COMMENT '关联标签ids' ,
    `product_partake_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '参与商品类型：1:全部商品2：指定商品参与3：指定商品不参与' ,
    `product_id` LONGTEXT NULL DEFAULT NULL COMMENT '关联商品ids' ,
    `is_limit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否限量0不限量1限量',
    `limit_num` int(10) NOT NULL DEFAULT '0' COMMENT '限量个数',
    `start_time` INT(10) NOT NULL DEFAULT '0' COMMENT '活动开始时间' ,
    `stop_time` INT(10) NOT NULL DEFAULT '0' COMMENT '活动结束时间' ,
    `sort` SMALLINT(5) NOT NULL DEFAULT '0' COMMENT '排序' ,
    `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '状态' ,
    `is_del` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否删除' ,
    `update_time` INT(10) NOT NULL DEFAULT '0' COMMENT '最后一次修改时间' ,
    `add_time` INT(10) NOT NULL DEFAULT '0' COMMENT '添加时间' ,
    PRIMARY KEY (`id`),
    KEY `pid` (`pid`),
    KEY `type` (`type`,`store_id`),
    KEY `promotions_type` (`promotions_type`),
    KEY `update_time` (`update_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='优惠活动表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_promotions_auxiliary",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '类型1：关联商品2：赠送优惠券3：赠送商品' ,
    `promotions_id` int(11) NOT NULL DEFAULT '0',
    `product_partake_type` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '参与商品类型：1:全部商品2：指定商品参与3：指定商品不参与' ,
    `product_id` int(11) NOT NULL DEFAULT '0' COMMENT '商品id' ,
    `coupon_id` int(11) NOT NULL DEFAULT '0' COMMENT '优惠券id' ,
    `limit_num` int(10) NOT NULL DEFAULT '0' COMMENT '限量个数',
    `surplus_num` int(10) NOT NULL DEFAULT '0' COMMENT '限量剩余个数',
    `is_all` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '是否是全部规格：1是0否' ,
    `unique` LONGTEXT NULL DEFAULT NULL COMMENT '商品unique' ,
    PRIMARY KEY (`id`),
    KEY `promotions_id` (`type`,`promotions_id`,`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='优惠活动辅助表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_order_promotions",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` INT(10) NOT NULL AUTO_INCREMENT ,
  `oid` INT(10) NOT NULL DEFAULT '0' COMMENT '订单ID' ,
  `uid` INT(10) NOT NULL DEFAULT '0' COMMENT '用户uid' ,
  `promotions_id` INT(10) NOT NULL DEFAULT '0' COMMENT '优惠活动ID' ,
  `product_id` INT(10) NOT NULL DEFAULT '0' COMMENT '商品ID' ,
  `promotions_price` DECIMAL(8,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额' ,
  `add_time` INT(10) NOT NULL DEFAULT '0' COMMENT '添加时间' ,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB COMMENT = '订单优惠活动辅助表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "capital_flow",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '编号',
  `flow_id` varchar(32) NOT NULL DEFAULT '' COMMENT '流水id',
  `order_id` varchar(50) NOT NULL DEFAULT '' COMMENT '关联id',
  `store_id` int(11) NOT NULL DEFAULT '0' COMMENT '门店id',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '用户id',
  `nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '昵称',
  `phone` varchar(20) NOT NULL DEFAULT '' COMMENT '电话',
  `price` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT '交易金额',
  `trading_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '交易类型',
  `pay_type` varchar(32) NOT NULL DEFAULT '' COMMENT '支付类型',
  `mark` varchar(500) NOT NULL DEFAULT '' COMMENT '备注',
  `add_time` int(11) NOT NULL DEFAULT '0' COMMENT '交易时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='资金流水表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_channel_code",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '类型：0=全天在线；1=自动上下线',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '二维码名称',
  `cate_id` int(11) NOT NULL DEFAULT '0' COMMENT '分类',
  `label_id` varchar(1000) NOT NULL DEFAULT '' COMMENT '客户标签',
  `reserve_userid` varchar(64) NOT NULL DEFAULT '' COMMENT '备用成员',
  `userids` varchar(1000) NOT NULL DEFAULT '' COMMENT '全天在线成员',
  `skip_verify` tinyint(1) NOT NULL DEFAULT '0' COMMENT '自动加好友：0=关闭，1=开启',
  `add_upper_limit` tinyint(1) NOT NULL DEFAULT '0' COMMENT '员工添加上限0=关闭,1=开启',
  `welcome_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=使用渠道欢迎，1=默认欢迎语',
  `welcome_words` varchar(1000) NOT NULL DEFAULT '' COMMENT '欢迎语',
  `qrcode_url` varchar(255) NOT NULL DEFAULT '' COMMENT '联系我二维码链接',
  `config_id` varchar(64) NOT NULL DEFAULT '' COMMENT '新增联系方式的配置id',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：1=开启，0=关闭',
  `client_num` int(11) NOT NULL DEFAULT '0' COMMENT '扫码添加客户人数',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`),
  KEY `cate_id` (`cate_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业渠道码（联系我）'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_channel_cycle",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `channel_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道码',
  `userids` varchar(1000) NOT NULL DEFAULT '' COMMENT '成员userid',
  `start_time` varchar(5) NOT NULL DEFAULT '' COMMENT '上班开始',
  `end_time` varchar(5) NOT NULL DEFAULT '' COMMENT '上班结束',
  `wokr_time` varchar(50) NOT NULL DEFAULT '' COMMENT '工作周期，json转换',
  KEY `channel_id` (`channel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='渠道码周期规则'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_channel_limit",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `channel_id` int(11) NOT NULL DEFAULT '0' COMMENT '渠道码id',
  `userid` varchar(64) NOT NULL DEFAULT '' COMMENT '成员userid',
  `max` int(11) NOT NULL DEFAULT '0' COMMENT '限制个数',
  KEY `channel_id` (`channel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='渠道码添加成员限制器'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_client",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `corp_id` varchar(18) NOT NULL DEFAULT '' COMMENT '企业微信id',
  `external_userid` varchar(64) NOT NULL COMMENT '外部联系人的userid',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '商城用户uid',
  `name` varchar(50) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '外部联系人的名称',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '外部联系人头像',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1表示该外部联系人是微信用户，2表示该外部联系人是企业微信用户',
  `gender` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别 0-未知 1-男性 2-女性',
  `unionid` varchar(64) NOT NULL DEFAULT '' COMMENT '开放平台的唯一身份标识',
  `position` varchar(50) NOT NULL DEFAULT '' COMMENT '外部联系人的职位',
  `corp_name` varchar(50) NOT NULL DEFAULT '' COMMENT '外部联系人所在企业的简称',
  `corp_full_name` varchar(100) DEFAULT NULL COMMENT '外部联系人所在企业的主体名称',
  `external_profile` varchar(1000) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL COMMENT '备注信息',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间戳',
  PRIMARY KEY (`id`),
  KEY `external_userid` (`external_userid`),
  KEY `corp_id` (`corp_id`,`external_userid`),
  KEY `uid` (`uid`),
  KEY `unionid` (`unionid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信客户'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_client_follow",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL DEFAULT '0' COMMENT '客户id',
  `userid` varchar(64) NOT NULL DEFAULT '' COMMENT '添加了此外部联系人的企业成员userid',
  `remark` varchar(50) NOT NULL DEFAULT '' COMMENT '该成员对此外部联系人的备注',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '该成员对此外部联系人的描述',
  `createtime` int(11) NOT NULL DEFAULT '0' COMMENT '该成员添加此外部联系人的时间',
  `remark_corp_name` varchar(50) DEFAULT NULL COMMENT '该成员对此微信客户备注的企业名称',
  `remark_mobiles` varchar(255) NOT NULL DEFAULT '' COMMENT '该成员对此客户备注的手机号码',
  `add_way` int(3) NOT NULL DEFAULT '0' COMMENT '该成员添加此客户的来源',
  `oper_userid` varchar(64) NOT NULL COMMENT '发起添加的userid，如果成员主动添加，为成员的userid；如果是客户主动添加，则为客户的外部联系人userid；如果是内部成员共享/管理员分配，则为对应的成员/管理员userid',
  `state` varchar(30) NOT NULL DEFAULT '' COMMENT '自定义字段返回数据',
  `is_del_user` tinyint(1) NOT NULL DEFAULT '0' COMMENT '客户是否删除跟踪人:0=没有,1=删除',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `state` (`state`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信客服跟踪'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_client_follow_tags",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `follow_id` int(11) NOT NULL DEFAULT '0' COMMENT '跟踪人id',
  `group_name` varchar(255) NOT NULL DEFAULT '' COMMENT '该成员添加此外部联系人所打标签的分组名称',
  `tag_name` varchar(255) NOT NULL DEFAULT '' COMMENT '该成员添加此外部联系人所打标签名称',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1-企业设置，2-用户自定义，3-规则组标签',
  `tag_id` varchar(32) NOT NULL DEFAULT '' COMMENT '该成员添加此外部联系人所打企业标签的id，用户自定义类型标签（type=2）不返回',
  `create_time` int(11) NOT NULL,
  KEY `follow_id` (`follow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业微信客户跟踪标签'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_department",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `corp_id` varchar(18) NOT NULL DEFAULT '' COMMENT '企业id',
  `department_id` int(11) DEFAULT '0' COMMENT '部门id',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '部门名称',
  `name_en` varchar(50) NOT NULL DEFAULT '' COMMENT '部门英文名称',
  `department_leader` varchar(1000) NOT NULL DEFAULT '' COMMENT 'leader',
  `parentid` int(11) NOT NULL DEFAULT '0' COMMENT '上级id',
  `srot` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信部门'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_group_chat",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `corp_id` varchar(18) NOT NULL DEFAULT '' COMMENT '企业ID',
  `chat_id` varchar(40) NOT NULL DEFAULT '' COMMENT '客户群ID',
  `name` varchar(255) NOT NULL COMMENT '群名',
  `owner` varchar(64) NOT NULL DEFAULT '' COMMENT '群主ID',
  `group_create_time` int(11) NOT NULL DEFAULT '0' COMMENT '群的创建时间',
  `notice` varchar(255) NOT NULL COMMENT '群公告',
  `admin_list` varchar(1000) NOT NULL DEFAULT '' COMMENT '群管理员userid',
  `member_num` int(11) NOT NULL DEFAULT '0' COMMENT '群人数',
  `retreat_group_num` int(11) NOT NULL DEFAULT '0' COMMENT '退群总数',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '客户群跟进状态。\r\n0 - 跟进人正常\r\n1 - 跟进人离职\r\n2 - 离职继承中\r\n3 - 离职继承完成',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `corp_id` (`corp_id`,`chat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信群'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_group_chat_auth",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '二维码名称',
  `auth_group_chat` tinyint(1) NOT NULL DEFAULT '0' COMMENT '自动建群0=关闭，1=开启',
  `chat_id` varchar(1000) NOT NULL DEFAULT '' COMMENT '群聊chat_id',
  `group_name` varchar(100) NOT NULL DEFAULT '' COMMENT '群名称',
  `group_num` int(11) NOT NULL DEFAULT '0' COMMENT '群序列号',
  `label` varchar(255) NOT NULL DEFAULT '' COMMENT '客户标签',
  `config_id` varchar(64) DEFAULT NULL COMMENT '配置id',
  `qr_code` varchar(255) DEFAULT NULL COMMENT '联系二维码的UR',
  `create_time` int(11) NOT NULL COMMENT '创建',
  `update_time` int(11) NOT NULL COMMENT '更新',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信自动拉群'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_group_chat_member",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT '0' COMMENT '企业群ID',
  `userid` varchar(64) NOT NULL COMMENT '群成员id',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '成员类型。\r\n1 - 企业成员\r\n2 - 外部联系人',
  `unionid` varchar(64) NOT NULL DEFAULT '' COMMENT '微信开放平台的唯一身份标识（微信unionid）',
  `join_time` int(11) NOT NULL DEFAULT '0' COMMENT '入群时间',
  `join_scene` tinyint(1) NOT NULL DEFAULT '0' COMMENT '入群方式。\r\n1 - 由群成员邀请入群（直接邀请入群）\r\n2 - 由群成员邀请入群（通过邀请链接入群）\r\n3 - 通过扫描群二维码入群',
  `invitor_userid` varchar(64) NOT NULL DEFAULT '' COMMENT '邀请者。目前仅当是由本企业内部成员邀请入群时会返回该值',
  `group_nickname` varchar(100) NOT NULL DEFAULT '' COMMENT '在群里的昵称',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '名字。仅当 need_name = 1 时返回',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=在群中,0=已退群',
  `chat_sum` int(11) NOT NULL DEFAULT '0' COMMENT '当前群人数',
  `retreat_chat_num` int(11) NOT NULL DEFAULT '0' COMMENT '当前退群人数',
  `state` varchar(100) DEFAULT NULL,
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `group_id_2` (`group_id`,`userid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信群成员列表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_group_chat_statistic",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL DEFAULT '0' COMMENT '群id',
  `today_sum` int(11) NOT NULL DEFAULT '0' COMMENT '今日新增人员',
  `today_return_sum` int(11) NOT NULL DEFAULT '0' COMMENT '今日退群人员',
  `chat_sum` int(11) NOT NULL DEFAULT '0' COMMENT '当前群人员',
  `chat_return_sum` int(11) NOT NULL DEFAULT '0' COMMENT '当前退群人员',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信群统计'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_group_msg_relation",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `template_id` int(11) NOT NULL DEFAULT '0' COMMENT '群发模板id',
  `msg_id` varchar(64) NOT NULL DEFAULT '' COMMENT '群发消息id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业群发消息id关联表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_group_msg_send_result",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `msg_id` varchar(64) NOT NULL DEFAULT '' COMMENT '消息id',
  `external_userid` varchar(64) DEFAULT NULL COMMENT '外部联系人userid，群发消息到企业的客户群不返回该字段',
  `chat_id` varchar(64) DEFAULT NULL COMMENT '外部客户群id，群发消息到客户不返回该字段',
  `userid` varchar(64) NOT NULL DEFAULT '' COMMENT '企业服务人员的userid',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0-未发送 1-已发送 2-因客户不是好友导致发送失败 3-因客户已经收到其他群发消息导致发送失败',
  `send_time` int(11) NOT NULL DEFAULT '0' COMMENT '发送时间，发送状态为1时返回',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `msg_id` (`msg_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业群发记录'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_group_msg_task",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `msg_id` varchar(64) NOT NULL DEFAULT '' COMMENT '消息id',
  `userid` varchar(64) NOT NULL DEFAULT '' COMMENT '成员id',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态0-未发送 2-已发送',
  `send_time` int(11) NOT NULL DEFAULT '0' COMMENT '发送时间',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `msg_id` (`msg_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='群发成员发送任务'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_group_template",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=客户群发，1=客户群群发',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '名称',
  `userids` text COMMENT '成员userid',
  `client_type` tinyint(1) DEFAULT '0' COMMENT '0=全部客户，1=筛选客户',
  `where_time` varchar(100) NOT NULL DEFAULT '' COMMENT '筛选时间',
  `where_label` text COMMENT '筛选标签',
  `where_not_label` text COMMENT '筛选排除标签',
  `template_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=立即发送，1=定时发送',
  `send_time` int(11) DEFAULT '0' COMMENT '定时发送时间',
  `send_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否发送1=已发送，2=正在发送中，0=没有发送',
  `welcome_words` text COMMENT '群发内容',
  `fail_external_userid` text COMMENT '发送失败的客户userid',
  `fail_message` VARCHAR(255) NULL DEFAULT NULL COMMENT '发送失败原因',
  `create_time` int(11) NOT NULL DEFAULT '0',
  `update_time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `template_type` (`template_type`,`send_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信群发'"
            ],

            [
                'code' => 220,
                'type' => -1,
                'table' => "work_label",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `corp_id` varchar(18) NOT NULL DEFAULT '' COMMENT '企业微信id',
  `group_id` int(11) NOT NULL DEFAULT '0' COMMENT '标签组id',
  `group_name` varchar(50) NOT NULL DEFAULT '' COMMENT '标签组名称',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '标签名称',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间 ',
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业微信标签'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_media",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `md5_path` varchar(32) DEFAULT NULL COMMENT '本地路径md5值',
  `type` enum('image','voice','video','file') DEFAULT 'image' COMMENT '素材类型',
  `upload_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=临时素材附件,1=朋友圈附件,2=商品附件',
  `path` varchar(255) DEFAULT NULL COMMENT '本地文件路径',
  `media_id` varchar(255) DEFAULT NULL COMMENT '媒体文件上传后获取的唯一标识',
  `url` varchar(255) DEFAULT NULL COMMENT '永久素材路径',
  `temporary` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=永久，1=非永久',
  `valid_time` int(11) NOT NULL DEFAULT '0' COMMENT '有效期',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '添加时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `type` (`type`,`md5_path`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信素材'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_member",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `corp_id` varchar(18) NOT NULL DEFAULT '' COMMENT '企业微信id',
  `userid` varchar(64) NOT NULL DEFAULT '' COMMENT '成员UserID',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '商城uid',
  `name` varchar(64) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '成员名称',
  `position` varchar(50) NOT NULL DEFAULT '' COMMENT '职务信息',
  `mobile` varchar(11) NOT NULL DEFAULT '' COMMENT '手机号码',
  `gender` tinyint(1) NOT NULL DEFAULT '0' COMMENT '性别。0表示未定义，1表示男性，2表示女性',
  `email` varchar(50) NOT NULL DEFAULT '' COMMENT '邮箱',
  `biz_mail` varchar(50) NOT NULL DEFAULT '' COMMENT '企业邮箱',
  `direct_leader` varchar(500) DEFAULT NULL COMMENT '直属上级UserID',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像url',
  `thumb_avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '头像缩略图url',
  `telephone` varchar(50) NOT NULL DEFAULT '' COMMENT '座机',
  `alias` varchar(30) NOT NULL DEFAULT '' COMMENT '别名',
  `enable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '启用/禁用成员。1表示启用成员，0表示禁用成员',
  `is_leader` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否是领导',
  `hide_mobile` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否隐藏手机号',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `open_userid` varchar(64) NOT NULL DEFAULT '' COMMENT '全局唯一',
  `main_department` tinyint(1) NOT NULL DEFAULT '0' COMMENT '主部门',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '激活状态: 1=已激活，2=已禁用，4=未激活，5=退出企业',
  `qr_code` varchar(255) NOT NULL DEFAULT '' COMMENT '员工个人二维码',
  `external_position` varchar(100) NOT NULL DEFAULT '' COMMENT '对外职务',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid` (`userid`) USING BTREE,
  KEY `corp_id` (`corp_id`),
  KEY `corp_id_2` (`corp_id`,`userid`),
  KEY `mobile` (`mobile`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信成员'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_member_other",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '企业成员id',
  `extattr` longtext COMMENT '扩展属性',
  `external_profile` longtext COMMENT '成员对外属性',
  UNIQUE KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业微信成员其他信息'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_member_relation",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `member_id` int(11) NOT NULL DEFAULT '0' COMMENT '员工ID',
  `department` int(11) NOT NULL DEFAULT '0' COMMENT '所属部门',
  `srot` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `is_leader_in_dept` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否为部门负责人',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='企业成员关联表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_moment",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '任务名称',
  `type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0=全部成员，1=选择成员',
  `user_ids` text COMMENT '成员userid',
  `client_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '客户类型0=全部，1=筛选',
  `client_tag_list` text COMMENT '客户标签',
  `welcome_words` text COMMENT '内容',
  `send_type` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发送类型',
  `send_time` int(11) NOT NULL DEFAULT '0' COMMENT '定时发送时间',
  `jobid` varchar(64) DEFAULT NULL COMMENT '任务id',
  `invalid_sender_list` text COMMENT '不合法的执行者列表，包括不存在的id以及不在应用可见范围内的部门或者成员',
  `moment_id` varchar(64) NOT NULL COMMENT '朋友圈id',
  `invalid_external_contact_list` text NOT NULL COMMENT '无效客户',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `jobid` (`jobid`),
  KEY `moment_id` (`moment_id`),
  KEY `send_time` (`send_time`,`send_type`,`jobid`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信发送朋友圈'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_moment_send_result",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `moment_id` varchar(64) NOT NULL DEFAULT '' COMMENT '朋友圈id',
  `user_id` varchar(64) NOT NULL DEFAULT '' COMMENT '成员id',
  `external_userid` text COMMENT '客户ids',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '发送状态',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `moment_id` (`moment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信朋友圈发送情况'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_welcome",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL COMMENT '0=通用,1=指定员工',
  `content` text COMMENT '消息内容',
  `attachments` text COMMENT '欢迎语消息体',
  `sort` int(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `create_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  `delete_time` int(11) DEFAULT NULL COMMENT '删除时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COMMENT='企业微信欢迎语'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "work_welcome_relation",
                'sql' => "CREATE TABLE IF NOT EXISTS `@table` (
  `welcome_id` int(11) NOT NULL COMMENT '欢迎语id',
  `userid` varchar(64) NOT NULL DEFAULT '' COMMENT '用户userid',
  KEY `welcome_id` (`welcome_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='欢迎语辅助表'"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "page_link",
                'sql' => <<<SQL
INSERT INTO `@table` (`id`, `cate_id`, `type`, `name`, `url`, `param`, `example`, `status`, `sort`, `add_time`) VALUES
(null, 6, 7, '限时折扣', '/pages/activity/discount/index?promotions_type=1', ' ', '/pages/activity/discount/index?promotions_type=1', 1, 0, 1626837579),
(null, 6, 7, '第N件N折', '/pages/activity/discount/index?promotions_type=2', ' ', '/pages/activity/discount/index?promotions_type=2', 1, 0, 1626837579),
(null, 6, 7, '满减满折', '/pages/activity/discount/index?promotions_type=3', ' ', '/pages/activity/discount/index?promotions_type=3', 1, 0, 1626837579),
(null, 6, 7, '满送活动', '/pages/activity/discount/index?promotions_type=4', ' ', '/pages/activity/discount/index?promotions_type=4', 1, 0, 1626837579)
SQL
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_product_label",
                'sql' => <<<SQL
INSERT INTO `@table` (`id`, `type`, `store_id`, `label_cate`, `label_name`, `sort`, `add_time`) VALUES
(1, 1, 0, 1, '热卖单品', 0, 1650284912),
(2, 1, 0, 1, '促销单品', 0, 1650284912),
(3, 1, 0, 1, '精品推荐', 0, 1650284912),
(4, 1, 0, 1, '首发新品', 0, 1650284912),
(5, 1, 0, 1, '优品推荐', 0, 1650284912)
SQL
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "store_product_ensure",
                'sql' => <<<SQL
INSERT INTO `@table` (`id`, `type`, `store_id`, `name`, `image`, `desc`, `sort`, `status`, `add_time`) VALUES
(1, 1, 0, '特权保证', 'https://wuht.oss-cn-beijing.aliyuncs.com/attach/2022/02/69db12022022311555243.png', '特权保证', 0, 1, 1648467987),
(2, 1, 0, '店铺发货&售后', 'https://wuht.oss-cn-beijing.aliyuncs.com/attach/2022/02/69db12022022311555243.png', '店铺发货&售后', 0, 1, 1648468137),
(3, 1, 0, '退换货运费险', 'https://wuht.oss-cn-beijing.aliyuncs.com/attach/2022/02/69db12022022311555243.png', '退换货运费险', 0, 1, 1648468153),
(4, 1, 0, '7天无理由退货', 'https://wuht.oss-cn-beijing.aliyuncs.com/attach/2022/02/69db12022022311555243.png', '7天无理由退货', 0, 1, 1650284743)
SQL
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "system_menus",
                'sql' => "TRUNCATE TABLE `@table`"
            ],
            [
                'code' => 220,
                'type' => -1,
                'table' => "system_menus",
                'sql' => <<<SQL
INSERT INTO `@table` (`id`, `pid`, `type`, `icon`, `menu_name`, `module`, `controller`, `action`, `api_url`, `methods`, `params`, `sort`, `is_show`, `is_show_path`, `access`, `menu_path`, `path`, `auth_type`, `header`, `is_header`, `unique_auth`, `is_del`) VALUES
(1, 0, 1, 'md-basket', '商品', 'admin', 'product', 'index', '', '', '[]', 126, 1, 0, 1, '/admin/product', '', 1, 'product', 1, 'admin-product', 0),
(2, 1, 1, 'md-basket', '商品列表', 'admin', 'product.product', 'index', '', '', '[]', 1, 1, 0, 1, '/admin/product/product_list', '1', 1, '', 0, 'admin-store-storeProuduct-index', 0),
(3, 1, 1, 'ios-color-filter', '商品分类', 'admin', 'product.storeCategory', 'index', '', '', '[]', 1, 1, 0, 1, '/admin/product/product_classify', '1', 1, 'product', 1, 'admin-store-storeCategory-index', 0),
(4, 0, 1, 'md-cart', '订单', 'admin', 'order', 'index', '', '', '[]', 110, 1, 0, 1, '/admin/order', '', 1, 'order', 1, 'admin-order', 0),
(5, 4, 1, 'md-cart', '订单列表', 'admin', 'order.store_order', 'index', '', '', '[]', 1, 1, 0, 1, '/admin/order/list', '4', 1, 'order', 1, 'admin-order-storeOrder-index', 0),
(6, 1, 1, 'md-chatboxes', '商品评论', 'admin', 'store.store_product_reply', 'index', '', '', '[]', -1, 1, 0, 1, '/admin/product/product_reply', '1', 1, 'product', 1, 'product-product-reply', 0),
(7, 0, 1, 'md-home', '运营', 'admin', 'index', '', '', '', '[]', 127, 1, 0, 1, '/admin/home/', '', 1, 'home', 1, 'admin-index-index', 0),
(9, 0, 1, 'md-person', '用户', 'admin', 'user.user', '', '', '', '[]', 100, 1, 0, 1, '/admin/user', '', 1, 'user', 1, 'admin-user', 0),
(10, 1092, 1, '', '用户列表', 'admin', 'user.user', 'index', '', '', '[]', 10, 1, 0, 1, '/admin/user/list', '9/1092', 1, 'user', 1, 'admin-user-user-index', 0),
(11, 1093, 1, '', '用户等级', 'admin', 'user.user_level', 'index', '', '', '[]', 7, 1, 0, 1, '/admin/vipuser/level', '9/1093', 1, 'user', 1, 'user-user-level', 0),
(12, 0, 1, 'md-settings', '设置', 'admin', 'setting.system_config', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/setting', '', 1, 'setting', 1, 'admin-setting', 0),
(14, 12, 1, 'ios-people', '权限设置', 'admin', 'setting.system_admin', '', '', '', '[]', 120, 1, 0, 1, '/admin/setting/auth/list', '12', 1, 'setting', 1, 'setting-system-admin', 0),
(19, 14, 1, '', '角色管理', 'admin', 'setting.system_role', 'index', '', '', '[]', 1, 1, 0, 1, '/admin/setting/system_role/index', '', 1, 'setting', 1, 'setting-system-role', 0),
(20, 14, 1, '', '管理员列表', 'admin', 'setting.system_admin', 'index', '', '', '[]', 1, 1, 0, 1, '/admin/setting/system_admin/index', '', 1, 'setting', 0, 'setting-system-list', 0),
(21, 14, 1, '', '权限规则', 'admin', 'setting.system_menus', 'index', '', '', '[]', 1, 1, 0, 1, '/admin/setting/system_menus/index', '', 1, 'setting', 0, 'setting-system-menus', 0),
(22, 1, 1, '', '产品添加', 'admin', 'store.store_product', 'save', '', '', '[]', 1, 1, 1, 1, '/admin/product/add_product', '', 1, 'product', 1, 'product-product-save', 0),
(23, 12, 1, 'md-settings', '系统设置', 'admin', 'setting.system_config', 'index', '', '', '[]', 9, 0, 0, 1, '/admin/setting/system_config', '12', 1, 'setting', 1, 'setting-system-config', 0),
(25, 12, 1, 'md-build', '系统维护', 'admin', 'system', '', '', '', '[]', -1, 1, 0, 1, '/admin/system', '12', 1, 'setting', 1, 'admin-system', 0),
(26, 0, 1, 'ios-people', '分销', 'admin', 'agent', '', '', '', '[]', 90, 1, 0, 1, '/admin/agent', '', 1, 'agent', 1, 'admin-agent', 0),
(27, 0, 1, 'ios-paper-plane', '营销', 'admin', 'marketing', '', '', '', '[]', 91, 1, 0, 1, '/admin/marketing', '', 1, 'marketing', 1, 'admin-marketing', 0),
(28, 26, 1, 'ios-switch', '分销设置', 'admin', 'setting.system_config', '', '', '', '[]', 8, 1, 0, 1, '/admin/setting/system_config_retail/2/9', '26', 1, 'agent', 1, 'setting-system-config', 0),
(29, 26, 1, 'md-person', '分销员管理', 'admin', 'agent.agent_manage', 'index', '', '', '[]', 10, 1, 0, 1, '/admin/agent/agent_manage/index', '26', 1, 'agent', 1, 'agent-agent-manage', 0),
(30, 1393, 1, '', '优惠券', 'admin', 'marketing.store_coupon', '', '', '', '[]', 96, 1, 0, 1, '/admin/marketing/store_coupon', '27/1393', 1, 'marketing', 1, 'marketing-store_coupon-index', 0),
(31, 27, 1, 'ios-pricetags', '砍价', 'admin', 'marketing.store_bargain', '', '', '', '[]', 97, 1, 0, 1, '/admin/marketing/store_bargain', '27', 1, 'marketing', 1, 'marketing-store_bargain-index', 0),
(32, 27, 1, 'md-contacts', '拼团', 'admin', 'marketing.store_combination', '', '', '', '[]', 98, 1, 0, 1, '/admin/marketing/store_combination', '27', 1, 'marketing', 1, 'marketing-store_combination-index', 0),
(33, 27, 1, 'ios-timer', '秒杀', 'admin', 'marketing.store_seckill', '', '', '', '[]', 99, 1, 0, 1, '/admin/marketing/store_seckill', '27', 1, 'marketing', 1, 'marketing-store_seckill-index', 0),
(34, 27, 1, 'logo-euro', '积分', 'admin', 'marketing.user_point', '', '', '', '[]', 95, 1, 0, 1, '/admin/marketing/user_point', '27', 1, 'marketing', 1, 'marketing-user_point-index', 0),
(35, 0, 1, 'logo-usd', '财务', 'admin', 'finance', '', '', '', '[]', 80, 1, 0, 1, '/admin/finance', '', 1, 'finance', 1, 'admin-finance', 0),
(36, 35, 1, 'logo-usd', '财务操作', 'admin', 'finance', '', '', '', '[]', 1, 1, 0, 1, '/admin/finance/user_extract', '35', 1, 'finance', 1, 'finance-user_extract-index', 0),
(37, 35, 1, 'ios-paper', '财务记录', 'admin', 'finance', '', '', '', '[]', 1, 1, 0, 1, '/admin/finance/user_recharge', '35', 1, 'finance', 1, 'finance-user-recharge-index', 0),
(38, 35, 1, 'md-contacts', '佣金记录', 'admin', 'finance', '', '', '', '[]', 1, 1, 0, 1, '/admin/finance/finance', '35', 1, 'finance', 1, 'finance-finance-index', 0),
(39, 36, 1, '', '提现申请', 'admin', 'finance.user_extract', '', '', '', '[]', 1, 1, 0, 1, '/admin/finance/user_extract/index', '', 1, 'finance', 0, 'finance-user_extract', 0),
(40, 37, 1, '', '充值记录', 'admin', 'finance.user_recharge', '', '', '', '[]', 1, 1, 0, 1, '/admin/finance/user_recharge/index', '', 1, 'finance', 0, 'finance-user-recharge', 0),
(41, 37, 1, '', '购买记录', 'admin', 'finance.finance', '', '', '', '[]', 1, 0, 0, 1, '/admin/finance/finance/bill', '35/37', 1, 'finance', 1, 'finance-finance-bill', 0),
(42, 38, 1, '', '佣金记录', 'admin', 'finance.finance', '', '', '', '[]', 1, 1, 0, 1, '/admin/finance/finance/commission', '', 1, 'finance', 0, 'finance-finance-commission', 0),
(43, 27, 1, 'md-bookmarks', '文章', 'admin', 'cms', '', '', '', '[]', 92, 1, 0, 1, '/admin/cms', '27', 1, 'marketing', 1, 'admin-cms', 0),
(44, 43, 1, '', '文章管理', 'admin', 'cms.article', 'index', '', '', '[]', 1, 1, 0, 1, '/admin/cms/article/index', '', 1, 'cms', 0, 'cms-article-index', 0),
(45, 43, 1, '', '文章分类', 'admin', 'cms.article_category', 'index', '', '', '[]', 1, 1, 0, 1, '/admin/cms/article_category/index', '', 1, 'cms', 0, 'cms-article-category', 0),
(46, 43, 1, '', '文章添加', 'admin', 'cms.article', 'add_article', '', '', '[]', 0, 1, 1, 1, '/admin/cms/article/add_article', '', 1, 'cms', 1, 'cms-article-creat', 0),
(47, 65, 1, '', '系统日志', 'admin', 'system.system_log', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/system/maintain/system_log/index', '', 1, 'system', 0, 'system-maintain-system-log', 0),
(48, 7, 1, '', '控制台', 'admin', 'index', 'index', '', '', '[]', 127, 1, 0, 1, '/admin/home/index', '', 1, '', 0, '', 1),
(56, 25, 1, '', '开发配置', 'admin', 'system', '', '', '', '[]', 10, 1, 0, 1, '/admin/system/config', '', 1, 'system', 1, 'system-config-index', 0),
(57, 65, 1, '', '刷新缓存', 'admin', 'system', 'clear', '', '', '[]', 1, 1, 0, 1, '/admin/system/maintain/clear/index', '', 1, 'system', 1, 'system-clear', 0),
(64, 65, 1, '', '文件校验', 'admin', 'system.system_file', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/system/maintain/system_file/index', '', 1, 'system', 0, 'system-maintain-system-file', 0),
(65, 25, 1, '', '安全维护', 'admin', 'system', '', '', '', '[]', 7, 1, 0, 1, '/admin/system/maintain', '', 1, 'system', 1, 'system-maintain-index', 0),
(66, 65, 1, '', '清除数据', 'admin', 'system.system_cleardata', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/system/maintain/system_cleardata/index', '', 1, 'system', 0, 'system-maintain-system-cleardata', 0),
(67, 65, 1, '', '数据备份', 'admin', 'system.system_databackup', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/system/maintain/system_databackup/index', '', 1, 'system', 0, 'system-maintain-system-databackup', 0),
(69, 135, 1, '', '公众号', 'admin', 'wechat', '', '', '', '[]', 0, 1, 0, 1, '/admin/app/wechat', '', 1, 'app', 1, 'admin-wechat', 0),
(70, 30, 1, '', '优惠券模板', 'admin', 'marketing.store_coupon', 'index', '', '', '[]', 0, 0, 0, 1, '/admin/marketing/store_coupon/index', '', 1, 'marketing', 1, 'marketing-store_coupon', 0),
(71, 30, 1, '', '优惠券列表', 'admin', 'marketing.store_coupon_issue', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_coupon_issue/index', '', 1, 'marketing', 1, 'marketing-store_coupon_issue', 0),
(72, 30, 1, '', '用户领取记录', 'admin', 'marketing.store_coupon_user', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_coupon_user/index', '', 1, 'marketing', 1, 'marketing-store_coupon_user', 0),
(74, 31, 1, '', '砍价商品', 'admin', 'marketing.store_bargain', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_bargain/index', '', 1, 'marketing', 1, 'marketing-store_bargain', 0),
(75, 32, 1, '', '拼团商品', 'admin', 'marketing.store_combination', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_combination/index', '', 1, 'marketing', 1, 'marketing-store_combination', 0),
(76, 32, 1, '', '拼团列表', 'admin', 'marketing.store_combination', 'combina_list', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_combination/combina_list', '', 1, 'marketing', 0, 'marketing-store_combination-combina_list', 0),
(77, 33, 1, '', '秒杀商品', 'admin', 'marketing.store_seckill', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_seckill/index', '', 1, 'marketing', 1, 'marketing-store_seckill', 0),
(78, 33, 1, '', '秒杀配置', 'admin', 'marketing.store_seckill', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_seckill_data/index/49', '', 1, 'marketing', 1, 'marketing-store_seckill-data', 0),
(79, 34, 1, '', '积分配置', 'admin', 'setting.system_config/index.html', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/integral/system_config/3/11', '', 1, 'marketing', 1, 'marketing-integral-system_config', 0),
(80, 34, 1, '', '积分日志', 'admin', 'marketing.user_point', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/user_point/index', '', 1, 'marketing', 0, 'marketing-user_point', 0),
(90, 32, 1, '', '拼团添加', 'admin', 'marketing.store_combination', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_combination/add_commodity/:id', '', 1, 'marketing', 0, '', 1),
(91, 69, 1, '', '公众号配置', 'admin', 'application.wechat', '', '', '', '[]', 0, 1, 0, 1, '/admin/app/wechat/setting', '', 1, 'app', 0, '', 1),
(92, 69, 1, '', '微信菜单', 'admin', 'application.wechat_menus', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/app/wechat/setting/menus/index', '', 1, 'app', 0, 'application-wechat-menus', 0),
(94, 1421, 1, '', '一号通', 'admin', 'setting.sms_config', '', '', '', '[]', 90, 1, 0, 1, '/admin/setting/sms/sms_config/index', '12/1421', 1, 'setting', 1, 'setting-sms', 0),
(95, 94, 1, '', '账户管理', 'admin', 'sms.sms_config', 'index', '', '', '[]', 0, 1, 1, 1, '/admin/setting/sms/sms_config/index', '12/94', 1, 'setting', 1, 'setting-sms-sms-config', 0),
(96, 94, 1, '', '短信模板', 'admin', 'sms.sms_template_apply', 'index', '', '', '[]', 0, 1, 1, 1, '/admin/setting/sms/sms_template_apply/index', '12/94', 1, 'setting', 1, 'setting-sms-config-template', 0),
(97, 94, 1, '', '套餐购买', 'admin', 'sms.sms_pay', 'index', '', '', '[]', 0, 1, 1, 1, '/admin/setting/sms/sms_pay/index', '12/94', 1, 'setting', 1, 'setting-sms-sms-template', 0),
(99, 1, 1, 'md-cube', '商品规格', 'admin', 'store.store_product', 'index', '', '', '[]', 1, 1, 0, 1, '/admin/product/product_attr', '1', 1, 'product', 1, 'product-product-attr', 0),
(105, 22, 1, '', '添加产品保存', 'admin', 'store.store_product', 'save', 'product/product/<id>', 'POST', '[]', 0, 0, 0, 1, '/admin/product/save', '', 2, 'product', 0, 'product-save', 0),
(108, 2, 1, '', '产品列表', 'admin', 'product.product', 'index', 'product/product', 'GET', '[]', 20, 0, 0, 1, '/admin/product/product', '', 2, 'product', 1, 'product-product-index', 0),
(109, 69, 1, '', '图文管理', 'admin', 'wechat.wechat_news_category', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/app/wechat/news_category/index', '', 1, 'app', 0, 'wechat-wechat-news-category-index', 0),
(110, 69, 1, '', '图文添加', 'admin', 'wechat.wechat_news_category', 'save', '', '', '[]', 0, 1, 1, 1, '/admin/app/wechat/news_category/save', '', 1, 'app', 1, 'wechat-wechat-news-category-save', 0),
(111, 56, 1, '', '配置分类', 'admin', 'setting.system_config_tab', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/system/config/system_config_tab/index', '', 1, 'system', 0, 'system-config-system_config-tab', 0),
(112, 56, 1, '', '组合数据', 'admin', 'setting.system_group', 'index', '', '', '[]', 0, 1, 0, 1, '/admin/system/config/system_group/index', '', 1, 'system', 0, 'system-config-system_config-group', 0),
(113, 114, 1, '', '微信关注回复', 'admin', 'wechat.reply', 'index', '', '', '[]', 0, 0, 0, 1, '/admin/app/wechat/reply/follow/subscribe', '12/135/69/114', 1, 'app', 1, 'wechat-wechat-reply-subscribe', 0),
(114, 69, 1, '', '自动回复', 'admin', 'wechat.reply', '', '', '', '[]', 0, 1, 0, 1, '/admin/app/wechat/reply', '', 1, 'app', 0, 'wechat-wechat-reply-index', 0),
(115, 114, 1, '', '关键字回复', 'admin', 'wechat.reply', 'keyword', '', '', '[]', 0, 0, 0, 1, '/admin/app/wechat/reply/keyword', '12/135/69/114', 1, 'app', 1, 'wechat-wechat-reply-keyword', 0),
(116, 114, 1, '', '无效关键词回复', 'admin', 'wechat.reply', 'index', '', '', '[]', 0, 0, 0, 1, '/admin/app/wechat/reply/index/default', '12/135/69/114', 1, 'app', 1, 'wechat-wechat-reply-default', 0),
(125, 56, 1, '', '配置列表', 'admin', 'system.config', 'index', '', '', '[]', 0, 1, 1, 1, '/admin/system/config/system_config_tab/list', '', 1, 'system', 1, 'system-config-system_config_tab-list', 0),
(126, 56, 1, '', '组合数据列表', 'admin', 'system.system_group', 'list', '', '', '[]', 0, 1, 1, 1, '/admin/system/config/system_group/list', '', 1, 'system', 1, 'system-config-system_config-list', 0),
(128, 656, 1, 'ios-grid', '页面数据', 'admin', 'setting.system_group_data', 'index', '', '', '[]', 2, 1, 0, 1, '/admin/setting/system_visualization_data', '12/656', 1, 'devise', 1, 'admin-setting-system_visualization_data', 0),
(134, 114, 1, '', '关键字添加', 'admin', '', 'index', '', '', '[]', 0, 0, 1, 1, '/admin/app/wechat/reply/keyword/save', '', 1, 'app', 1, 'wechat-wechat-reply-save', 0),
(135, 12, 1, 'ios-appstore', '应用设置', 'admin', 'app', 'index', '', '', '[]', 100, 1, 0, 1, '/admin/app', '12', 1, 'setting', 1, 'admin-app', 0),
(144, 303, 1, '', '提货点设置', 'admin', 'merchant.system_store', 'index', '', '', '[]', 5, 1, 0, 1, '/admin/setting/merchant/system_store/index', '', 1, 'setting', 0, 'setting-system-config-merchant', 1),
(145, 1352, 1, '', '物流公司', 'admin', 'freight.express', 'index', '', '', '[]', 80, 1, 0, 1, '/admin/setting/freight/express/index', '12/1350/1352', 1, '', 0, 'setting-freight-express', 0),
(146, 31, 1, '', '添加砍价', 'admin', '/marketing.store_bargain', 'create', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/store_bargain/create', '', 1, '', 0, 'marketing-store_bargain-create', 0),
(147, 32, 1, '', '添加拼团', 'admin', 'marketing.store_combination', 'create', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/store_combination/create', '', 1, '', 0, 'marketing-store_combination-create', 0),
(148, 33, 1, '', '添加秒杀', 'admin', 'marketing.store_seckill', 'create', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/store_seckill/create', '27/33', 1, '', 0, 'marketing-store_seckill-create', 0),
(149, 0, 1, '', '顶部菜单', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admindingbu', '', 1, '拉拉', 0, '', 1),
(150, 149, 1, '', '二级菜单', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/adminerji', '', 1, '', 0, '', 1),
(151, 150, 1, '', '三级菜单', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/adminsanji', '', 1, '', 0, '', 1),
(152, 149, 1, '', '二级菜单2', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/adminerji', '', 1, '', 0, '', 1),
(153, 152, 1, '', '三级菜单2', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/adminsanji', '', 1, '', 0, '', 1),
(154, 34, 1, '', '积分签到', 'admin', 'setting.system_group_data', 'index', '', '', '[]', 1, 1, 0, 1, '/admin/marketing/integral/signIn', '27/34', 1, '', 0, 'marketing-integral-sign', 0),
(165, 9, 1, 'logo-whatsapp', '客服管理', 'admin', 'setting.storeService', 'index', '', '', '[]', 1, 1, 0, 1, '/admin/kefu', '9', 1, 'user', 0, 'setting-store-service', 0),
(166, 25, 1, '', '日志', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/system/log', '', 1, '', 0, 'system-log', 0),
(167, 0, 1, '', '测试', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin1212/12', '', 1, '', 0, '', 1),
(168, 167, 1, '', '1212', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin1212', '', 1, '', 0, '', 1),
(169, 577, 1, '', '商品删除', 'admin', 'product', '商品删除', 'product/product/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '0', 1, '', 0),
(170, 3, 1, '', '分类列表', 'admin', '', '', 'product/category', 'GET', '[]', 0, 0, 0, 1, '/adminproduct/category', '', 2, '', 0, '', 0),
(171, 578, 1, '', '删除分类', 'admin', '', '', 'product/category/<id>', 'DELETE', '[]', 0, 0, 0, 1, '/adminproduct/category/<id>', '', 2, '', 0, '', 0),
(172, 578, 1, '', '修改分类', 'admin', '', '', 'product/category/<id>', 'PUT', '[]', 0, 0, 0, 1, '/adminproduct/category/<id>', '', 2, '', 0, '', 0),
(173, 578, 1, '', '新增分类', 'admin', '', '', 'product/category', 'POST', '[]', 0, 0, 0, 1, '/adminproduct/category', '', 2, '', 0, 'product-save-cate', 0),
(174, 578, 1, '', '分类状态', 'admin', '', '', 'product/category/set_show/<id>/<is_show>', 'PUT', '[]', 0, 0, 0, 1, '/adminproduct/category/set_show/<id>/<is_show>', '', 2, '', 0, '', 0),
(175, 578, 1, '', '快速编辑', 'admin', '', '', 'product/category/set_category/<id>', 'PUT', '[]', 0, 0, 0, 1, '/adminproduct/category/set_category/<id>', '', 2, '', 0, '', 0),
(176, 578, 1, '', '分类表单添加', 'admin', '', '', 'product/category/create', 'GET', '[]', 0, 0, 0, 1, '/admincategory/create', '', 2, '', 0, '', 0),
(177, 578, 1, '', '分类表单编辑', 'admin', '', '', 'product/category/<id>', 'GET', '[]', 0, 0, 0, 1, '/admincategory/<id>/edit', '', 2, '', 0, '', 0),
(178, 3, 1, '', '分类树形列表', 'admin', '', '', 'product/category/tree/<type>', 'GET', '[]', 0, 0, 0, 1, '/admincategory/tree/:type', '', 2, '', 0, '', 0),
(179, 577, 1, '', '产品状态', 'admin', '', '', 'product/product/set_show/<id>/<is_show>', 'PUT', '[]', 0, 0, 0, 1, '/adminproduct/set_show/<id>/<is_show>', '', 2, '', 0, '', 0),
(180, 577, 1, '', '快速编辑', 'admin', '', '', 'product/product/set_product/<id>', 'PUT', '[]', 0, 0, 0, 1, '/adminproduct/product/set_product/<id>', '', 2, '', 0, '', 0),
(181, 577, 1, '', '批量上架商品', 'admin', '', '', 'product/product/product_show', 'PUT', '[]', 0, 0, 0, 1, '/adminproduct/product/product_show', '', 2, '', 0, 'product-product-product_show', 0),
(182, 577, 1, '', '采集商品', 'admin', '', '', 'product/copy', 'POST', '[]', 0, 0, 0, 1, '/adminproduct/crawl', '', 2, '', 0, 'product-crawl-save', 0),
(183, 577, 1, '', '采集商品保存', 'admin', '', '', 'product/crawl/save', 'POST', '[]', 0, 0, 0, 1, '/adminproduct/crawl/save', '', 2, '', 0, '', 0),
(184, 579, 1, '', '虚拟评论表单', 'admin', '', '', 'product/reply/fictitious_reply/<product_id>', 'GET', '[]', 0, 0, 0, 1, '/adminproduct/reply/fictitious_reply', '', 2, '', 0, '', 0),
(185, 579, 1, '', '保存虚拟评论', 'admin', '', '', 'product/reply/save_fictitious_reply', 'POST', '[]', 0, 0, 0, 1, '/adminproduct/reply/save_fictitious_reply', '', 2, '', 0, 'product-reply-save_fictitious_reply', 0),
(186, 22, 1, '', '获取属性模板列表', 'admin', '', '', 'product/product/get_rule', 'GET', '[]', 0, 0, 0, 1, '/adminproduct/product/get_rule', '', 2, '', 0, '', 0),
(187, 22, 1, '', '运费模板列表', 'admin', '', '', 'product/product/get_template', 'GET', '[]', 0, 0, 0, 1, '/adminproduct/product/get_template', '', 2, '', 0, '', 0),
(188, 579, 1, '', '删除评论', 'admin', '', '', 'product/reply/<id>', 'DELETE', '[]', 0, 0, 0, 1, '/adminproduct/reply/<id>', '', 2, '', 0, '', 0),
(189, 579, 1, '', '评论回复', 'admin', '', '', 'product/reply/set_reply/<id>', 'PUT', '[]', 0, 0, 0, 1, '/adminreply/set_reply/<id>', '', 2, '', 0, '', 0),
(190, 6, 1, '', '评论列表', 'admin', '', '', 'product/reply', 'GET', '[]', 0, 0, 0, 1, '/adminproduct/reply', '', 2, '', 0, '', 0),
(191, 22, 1, '', '生成属性', 'admin', '', '', 'product/generate_attr/<id>/<type>', 'POST', '[]', 0, 0, 0, 1, '/adminproduct/generate_attr/<id>', '', 2, '', 0, '', 0),
(192, 2, 1, '', '商品列表头部', 'admin', '', '', 'product/product/type_header', 'GET', '[]', 10, 0, 0, 1, '/adminproduct/product/type_header', '', 2, '', 0, '', 0),
(193, 577, 1, '', '商品列表插件', 'admin', '', '', 'product/product/list', 'GET', '[]', 0, 0, 0, 1, '/adminproduct/product/list', '', 2, '', 0, '', 0),
(194, 99, 1, '', '属性规则列表', 'admin', '', '', 'product/product/rule', 'GET', '[]', 0, 0, 0, 1, '/adminproduct/product/rule', '', 2, '', 0, '', 0),
(195, 580, 1, '', '保存修改规则', 'admin', '', '', 'product/product/rule/<id>', 'POST', '[]', 0, 0, 0, 1, '/adminproduct/rule/<id>', '', 2, '', 0, 'product-rule-save', 0),
(196, 580, 1, '', '规则详情', 'admin', '', '', 'product/product/rule/<id>', 'GET', '[]', 0, 0, 0, 1, '/adminproduct/product/rule/<id>', '', 2, '', 0, '', 0),
(197, 580, 1, '', '删除规则', 'admin', '', '', 'product/product/rule/delete', 'DELETE', '[]', 0, 0, 0, 1, '/adminproduct/product/rule/delete', '', 2, '', 0, 'product-product-rule-delete', 0),
(198, 5, 1, '', '订单列表', 'admin', '', '', 'order/list', 'GET', '[]', 0, 1, 0, 1, '/adminorder/list', '4/5', 2, '', 0, '', 0),
(199, 5, 1, '', '订单数据', 'admin', '', '', 'order/chart', 'GET', '[]', 0, 0, 0, 1, '/adminorder/chart', '', 2, '', 0, '', 0),
(200, 581, 1, '', '订单核销', 'admin', '', '', 'order/write', 'POST', '[]', 0, 0, 0, 1, '/adminorder/write', '', 2, '', 0, 'order-write', 0),
(201, 215, 1, '', '订单修改表格', 'admin', '', '', 'order/edit/<id>', 'GET', '[]', 0, 0, 0, 1, '/adminorder/edit/<id>', '', 2, '', 0, '', 0),
(202, 215, 1, '', '订单修改', 'admin', '', '', 'order/update/<id>', 'PUT', '[]', 0, 0, 0, 1, '/adminorder/update/<id>', '', 2, '', 0, '', 0),
(203, 581, 1, '', '订单收货', 'admin', '', '', 'order/take/<id>', 'PUT', '[]', 0, 0, 0, 1, '/adminorder/take/<id>', '', 2, '', 0, '', 0),
(204, 209, 1, '', '订单发货', 'admin', '', '', 'order/delivery/<id>', 'PUT', '[]', 0, 0, 0, 1, '/adminorder/delivery/<id>', '', 2, '', 0, '', 0),
(205, 214, 1, '', '订单退款表格', 'admin', '', '', 'order/refund/<id>', 'GET', '[]', 0, 0, 0, 1, '/adminorder/refund/<id>', '', 2, '', 0, '', 0),
(206, 214, 1, '', '订单退款', 'admin', '', '', 'order/refund/<id>', 'PUT', '[]', 0, 0, 0, 1, '/adminorder/refund/<id>', '', 2, '', 0, '', 0),
(207, 581, 1, '', '订单物流信息', 'admin', '', '', 'order/express/<id>', 'GET', '[]', 0, 0, 0, 1, '/adminorder/express/<id>', '', 2, '', 0, '', 0),
(208, 209, 1, '', '物流公司列表', 'admin', '', '', 'order/express_list', 'GET', '[]', 0, 0, 0, 1, '/adminorder/express_list', '', 2, '', 0, '', 0),
(209, 581, 1, '', '发货', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/adminorder/delivery', '', 1, '', 0, '', 0),
(210, 1364, 1, '', '附加权限', 'admin', '', '', '', 'GET', '[]', 99, 0, 0, 1, '/adminorder/info/<id>', '35/36/767/1364', 1, '', 0, '', 0),
(211, 213, 1, '', '订单配送表格', 'admin', '', '', 'order/distribution/<id>', 'GET', '[]', 0, 0, 0, 1, '/adminorder/distribution/<id>', '', 2, '', 0, '', 0),
(212, 213, 1, '', '修改配送信息', 'admin', '', '', 'order/distribution/<id>', 'PUT', '[]', 0, 0, 0, 1, '/adminorder/distribution/<id>', '', 2, '', 0, '', 0),
(213, 581, 1, '', '订单配送', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/adminorder/distribution', '', 1, '', 0, '', 0),
(214, 581, 1, '', '退款', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/adminorder/refund', '', 1, '', 0, '', 0),
(215, 581, 1, '', '修改', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/adminorder/update', '', 1, '', 0, '', 0),
(216, 581, 1, '', '不退款', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/adminorder/no_refund', '', 1, '', 0, '', 0),
(217, 216, 1, '', '不退款表格', 'admin', '', '', 'order/no_refund/<id>', 'GET', '[]', 0, 0, 0, 1, '/adminorder/no_refund/<id>', '', 2, '', 0, '', 0),
(218, 216, 1, '', '不退款理由修改', 'admin', '', '', 'order/no_refund/<id>', 'PUT', '[]', 0, 0, 0, 1, '/adminorder/no_refund/<id>', '', 2, '', 0, '', 0),
(219, 581, 1, '', '线下支付', 'admin', '', '', 'order/pay_offline/<id>', 'POST', '[]', 98, 0, 0, 1, '', '', 2, '', 0, '', 0),
(220, 581, 1, '', '退积分', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/adminorder/refund_integral', '', 1, '', 0, '', 0),
(221, 220, 1, '', '退积分表单', 'admin', '', '', 'order/refund_integral/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(222, 220, 1, '', '修改退积分', 'admin', '', '', 'order/refund_integral/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(223, 581, 1, '', '订单备注', 'admin', '', '', 'order/remark/<id>', 'PUT', '[]', 97, 0, 0, 1, '', '', 2, '', 0, '', 0),
(224, 209, 1, '', '获取电子面单信息', 'admin', '', '', 'order/express/temp', 'GET', '[]', 96, 0, 1, 1, '', '4/5/581/209', 2, '', 0, '', 0),
(225, 581, 1, '', '订单删除', 'admin', '', '', 'order/del/<id>', 'DELETE', '[]', 95, 0, 0, 1, '', '', 2, '', 0, '', 0),
(226, 581, 1, '', '批量删除订单', 'admin', '', '', 'order/dels', 'POST', '[]', 100, 0, 0, 1, '', '', 2, '', 0, '', 0),
(227, 1092, 1, '', '用户分组', 'admin', 'user.user_group', 'index', '', '', '[]', 9, 1, 0, 1, '/admin/user/group', '9/1092', 1, 'user', 1, 'user-user-group', 0),
(229, 1352, 1, '', '城市数据', 'admin', 'setting.system_city', '', '', '', '[]', 70, 1, 0, 1, '/admin/setting/freight/city/list', '12/1350/1352', 1, 'setting', 1, 'setting-system-city', 0),
(230, 1352, 1, '', '运费模板', 'admin', 'setting.shipping_templates', '', '', '', '[]', 90, 1, 0, 1, '/admin/setting/freight/shipping_templates/list', '12/1350/1352', 1, 'setting', 1, 'setting-shipping-templates', 0),
(231, 1364, 1, '', '发票列表接口', 'admin', '', '', 'order/invoice/list', 'GET', '[]', 0, 1, 0, 1, '', '35/36/767/1364', 2, '', 0, 'admin-order-invoice-index', 0),
(232, 585, 1, '', '用户详情', 'admin', '', '', 'user/one_info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(233, 585, 1, '', '创建用户表单', 'admin', '', '', 'user/user/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(234, 585, 1, '', '修改用户信息表单', 'admin', '', '', 'user/user/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(235, 585, 1, '', '获取用户信息', 'admin', '', '', 'user/user/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(236, 585, 1, '', '修改用户信息', 'admin', '', '', 'user/user/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(238, 585, 1, '', '发送优惠卷', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/coupon', '', 1, '', 0, 'admin-user-coupon', 0),
(239, 238, 1, '', '优惠卷列表', 'admin', '', '', 'marketing/coupon/grant', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(240, 238, 1, '', '发送优惠卷', 'admin', '', '', 'marketing/coupon/user/grant', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(241, 585, 1, '', '发送图文', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/wechat/news/', '', 1, '', 0, 'admin-wechat-news', 0),
(242, 241, 1, '', '图文列表', 'admin', '', '', 'app/wechat/news', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(243, 241, 1, '', '发送图文', 'admin', '', '', 'app/wechat/push', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(244, 585, 1, '', '批量用户分组', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/group_set/', '', 1, '', 0, 'admin-user-group_set', 0),
(245, 244, 1, '', '用户分组表单', 'admin', '', '', 'user/set_group/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(246, 244, 1, '', '保存分组', 'admin', '', '', 'user/set_group', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(247, 586, 1, '', '添加修改用户等级', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/level_add', '', 1, '', 0, 'admin-user-level_add', 0),
(248, 247, 1, '', '添加会员等级表单', 'admin', '', '', 'user/user_level/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(249, 247, 1, '', '保存会员等级', 'admin', '', '', 'user/user_level', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(250, 1366, 1, '', '用户等级列表', 'admin', '', '', 'user/user_level/vip_list', 'GET', '[]', 0, 1, 0, 1, '', '9/1093/11/1366', 2, '', 0, '', 0),
(251, 586, 1, '', '用户等级是否显示', 'admin', '', '', 'user/user_level/set_show/<id>/<is_show>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(252, 586, 1, '', '删除用户等级', 'admin', '', '', 'user/user_level/delete/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(253, 586, 1, '', '等级任务', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/user_level', '', 1, '', 0, '', 0),
(254, 253, 1, '', '等级任务列表', 'admin', '', '', 'user/user_level/task/<level_id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(255, 253, 1, '', '等级任务显示隐藏', 'admin', '', '', 'user/user_level/set_task_show/<id>/<is_show>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(256, 253, 1, '', '等级任务是否必达', 'admin', '', '', 'user/user_level/set_task_must/<id>/<is_must>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(257, 253, 1, '', '添加修改等级任务', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(258, 257, 1, '', '添加等级任务表单', 'admin', '', '', 'user/user_level/create_task', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(259, 257, 1, '', '保存修改任务', 'admin', '', '', 'user/user_level/save_task', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(260, 253, 1, '', '删除等级任务', 'admin', '', '', 'user/user_level/delete_task/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(261, 227, 1, '', '用户分组列表', 'admin', '', '', 'user/user_group/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(262, 227, 1, '', '删除用户分组', 'admin', '', '', 'user/user_group/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(263, 227, 1, '', '添加修改用户分组', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/group', '', 1, '', 0, 'admin-user-group', 0),
(264, 263, 1, '', '添加修改用户分组表单', 'admin', '', '', 'user/user_group/add/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(265, 263, 1, '', '保存修改用户分组', 'admin', '', '', 'user/user_group/save', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(266, 29, 1, '', '分销员列表', 'admin', '', '', 'agent/index', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(267, 584, 1, '', '分销员数据', 'admin', '', '', 'agent/statistics', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(268, 29, 1, '', '推广人列表', 'admin', '', '', 'agent/stair', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(269, 29, 1, '', '推广人订单列表', 'admin', '', '', 'agent/stair/order', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(270, 584, 1, '', '清除推广人', 'admin', '', '', 'agent/stair/delete_spread/<uid>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(271, 584, 1, '', '推广二维码', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(272, 271, 1, '', '公众号推广二维码', 'admin', '', '', 'agent/look_code', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(273, 271, 1, '', '小程序推广二维码', 'admin', '', '', 'agent/look_xcx_code', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(274, 583, 1, '', '添加优惠卷', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/store_coupon/add', '27/30/70/583', 1, '', 0, 'admin-marketing-store_coupon-add', 0),
(275, 274, 1, '', '添加优惠卷表单', 'admin', '', '', 'marketing/coupon/create/<type>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(276, 274, 1, '', '保存优惠卷', 'admin', '', '', 'marketing/coupon/save_coupon', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(277, 583, 1, '', '发布优惠卷', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/marketing/store_coupon/push', '', 1, '', 0, 'admin-marketing-store_coupon-push', 0),
(278, 277, 1, '', '发布优惠卷表单', 'admin', '', '', 'marketing/coupon/issue/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(279, 277, 1, '', '发布优惠卷', 'admin', '', '', 'marketing/coupon/issue/<id>', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(280, 583, 1, '', '立即失效', 'admin', '', '', 'marketing/coupon/status/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(281, 583, 1, '', '删除优惠卷', 'admin', '', '', 'marketing/coupon/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(282, 71, 1, '', '优惠卷已发布列表', 'admin', '', '', 'marketing/coupon/released', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(283, 71, 1, '', '领取记录', 'admin', '', '', 'marketing/coupon/released/issue_log/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(284, 71, 1, '', '删除优惠卷', 'admin', '', '', 'marketing/coupon/released/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(285, 71, 1, '', '修改状态', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(286, 285, 1, '', '修改状态表单', 'admin', '', '', 'marketing/coupon/released/<id>/status', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(287, 285, 1, '', '保存修改状态', 'admin', '', '', 'marketing/coupon/released/status/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(288, 80, 1, '', '积分日志列表', 'admin', '', '', 'marketing/integral', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(289, 80, 1, '', '积分日志数据', 'admin', '', '', 'marketing/integral/statistics', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(290, 405, 1, '', '审核状态通过', 'admin', '', '', 'finance/extract/adopt/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(291, 405, 1, '', '拒绝申请', 'admin', '', '', 'finance/extract/refuse/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(292, 405, 1, '', '提现编辑', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(293, 292, 1, '', '编辑表单', 'admin', '', '', 'finance/extract/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(294, 292, 1, '', '保存修改', 'admin', '', '', 'finance/extract/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(295, 40, 1, '', '充值列表', 'admin', '', '', 'finance/recharge', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(296, 40, 1, '', '充值数据', 'admin', '', '', 'finance/recharge/user_recharge', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(297, 40, 1, '', '退款', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(298, 297, 1, '', '获取退款表单', 'admin', '', '', 'finance/recharge/<id>/refund_edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(299, 297, 1, '', '保存退款', 'admin', '', '', 'finance/recharge/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(303, 12, 1, 'md-cart', '发货设置', 'admin', 'setting', 'index', '', '', '[]', 6, 0, 0, 1, '/admin/setting/freight', '12', 1, 'setting', 0, '', 0),
(304, 303, 1, '', '物流配置', 'admin', 'setting.systemConfig', 'index', '', '', '[]', 0, 0, 0, 1, '/admin/setting/system_config_logistics/3/10', '', 1, '', 0, 'setting-system-config-logistics', 0),
(305, 44, 1, '', '文章列表', 'admin', '', '', 'cms/cms', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(306, 409, 1, '', '文章分类', 'admin', '', '', 'cms/category', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(307, 42, 1, '', '佣金记录列表', 'admin', '', '', 'finance/finance/commission_list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(308, 42, 1, '', '用户详情', 'admin', 'finance.finance', 'user_info', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(309, 308, 1, '', '获取用户信息', 'admin', '', '', 'finance/finance/user_info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(310, 308, 1, '', '佣金详细列表', 'admin', '', '', 'finance/finance/extract_list/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(311, 41, 1, '', '资金类型', 'admin', '', '', 'finance/finance/bill_type', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(312, 41, 1, '', '资金列表', 'admin', '', '', 'finance/finance/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(313, 23, 1, '', '获取头部导航', 'admin', '', '', 'setting/config/header_basics', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(314, 23, 1, '', '获取配置列表', 'admin', '', '', 'setting/config/edit_basics', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(315, 23, 1, '', '修改配置', 'admin', '', '', 'setting/config/save_basics', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(316, 423, 1, '', '添加客服', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/store_service/add', '', 1, '', 0, 'setting-store_service-add', 0),
(317, 316, 1, '', '客服用户列表', 'admin', '', '', 'app/wechat/kefu/add', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(318, 316, 1, '', '保存客服', 'admin', '', '', 'app/wechat/kefu', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(319, 423, 1, '', '聊天记录', 'admin', '', '', 'app/wechat/kefu/record/', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(320, 423, 1, '', '编辑客服', 'admin', '', '', '', '', '[]', 80, 0, 0, 1, '/admin/setting/store_service/edit', '', 1, '', 0, 'setting-store_service-edit', 0),
(321, 423, 1, '', '删除客服', 'admin', '', '', 'app/wechat/kefu/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(322, 423, 1, '', '客服是否开启', 'admin', '', '', 'app/wechat/kefu/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(323, 320, 1, '', '编辑客服表单', 'admin', '', '', 'app/wechat/kefu/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(324, 320, 1, '', '修改客服', 'admin', '', '', 'app/wechat/kefu/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(325, 19, 1, '', '添加身份', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/system_role/add', '', 1, '', 0, 'setting-system_role-add', 0),
(326, 325, 1, '', '添加身份表单', 'admin', '', '', 'setting/role/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(327, 325, 1, '', '添加修改身份', 'admin', '', '', 'setting/role/<id>', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(328, 325, 1, '', '修改身份表单', 'admin', '', '', 'setting/role/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(329, 19, 1, '', '修改身份状态', 'admin', '', '', 'setting/role/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(330, 19, 1, '', '删除身份', 'admin', '', '', 'setting/role/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(331, 20, 1, '', '添加管理员', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/system_admin/add', '', 1, '', 0, 'setting-system_admin-add', 0),
(332, 331, 1, '', '添加管理员表单', 'admin', '', '', 'setting/admin/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(333, 331, 1, '', '添加管理员', 'admin', '', '', 'setting/admin', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(334, 20, 1, '', '编辑管理员', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin /setting/system_admin/edit', '', 1, '', 0, ' setting-system_admin-edit', 0),
(335, 334, 1, '', '编辑管理员表单', 'admin', '', '', 'setting/admin/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(336, 334, 1, '', '修改管理员', 'admin', '', '', 'setting/admin/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(337, 20, 1, '', '删除管理员', 'admin', '', '', 'setting/admin/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(338, 21, 1, '', '添加规则', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/system_menus/add', '', 1, '', 0, 'setting-system_menus-add', 0),
(339, 338, 1, '', '添加权限表单', 'admin', '', '', 'setting/menus/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(340, 338, 1, '', '添加权限', 'admin', '', '', 'setting/menus', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(341, 21, 1, '', '修改权限', 'admin', 'setting.system_menus', 'edit', '', '', '[]', 0, 0, 0, 1, '/admin/setting/system_menus/edit', '', 1, '', 0, '/setting-system_menus-edit', 0),
(342, 341, 1, '', '编辑权限表单', 'admin', '', '', 'setting/menus/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(343, 341, 1, '', '修改权限', 'admin', '', '', 'setting/menus/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(344, 21, 1, '', '修改权限状态', 'admin', '', '', 'setting/menus/show/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(345, 21, 1, '', '删除权限菜单', 'admin', '', '', 'setting/menus/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(346, 338, 1, '', '添加子菜单', 'admin', 'setting.system_menus', 'add', '', '', '[]', 0, 0, 0, 1, '/admin/setting/system_menus/add_sub', '', 1, '', 0, 'setting-system_menus-add_sub', 0),
(347, 361, 1, '', '是否登陆短信平台', 'admin', '', '', 'notify/sms/is_login', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(348, 361, 1, '', '短信剩余条数', 'admin', '', '', 'notify/sms/number', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(349, 95, 1, '', '获取短信验证码', 'admin', '', '', 'serve/captcha', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(350, 95, 1, '', '修改注册账号', 'admin', '', '', 'serve/register', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(351, 95, 1, '', '登陆短信平台', 'admin', '', '', 'serve/login', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(353, 95, 1, '', '退出短信登陆', 'admin', '', '', 'notify/sms/logout', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(355, 96, 1, '', '短信模板列表', 'admin', '', '', 'serve/sms/temps', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(356, 96, 1, '', '申请模板', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/sms/sms_template_apply/add', '', 1, '', 0, 'setting-sms-sms_template_apply-add', 0),
(357, 356, 1, '', '申请短信模板表单', 'admin', '', '', 'notify/sms/temp/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(358, 356, 1, '', '保存申请短信模板', 'admin', '', '', 'notify/sms/temp', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(359, 97, 1, '', '短信套餐', 'admin', '', '', 'serve/meal_list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(360, 97, 1, '', '短信购买支付码', 'admin', '', '', 'serve/pay_meal', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(361, 94, 1, '', '短信设置附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/sms/attach', '', 1, '', 0, '', 0),
(366, 1091, 1, '', '首页统计数据', 'admin', '', '', 'home/header', 'GET', '[]', 0, 1, 0, 1, '', '7/1091', 2, '', 0, '', 0),
(367, 1091, 1, '', '首页订单图表', 'admin', '', '', 'home/order', 'GET', '[]', 0, 0, 0, 1, '', '7/1091', 2, '', 0, '', 0),
(368, 1091, 1, '', '首页用户图表', 'admin', '', '', 'home/user', 'GET', '[]', 0, 0, 0, 1, '', '7/1091', 2, '', 0, '', 0),
(369, 1091, 1, '', '首页交易额排行', 'admin', '', '', 'home/rank', 'GET', '[]', 0, 0, 0, 1, '', '7/1091', 2, '', 0, '', 0),
(370, 72, 1, '', '优惠卷领取列表', 'admin', '', '', 'marketing/coupon/user', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(371, 74, 1, '', '砍价列表', 'admin', '', '', 'marketing/bargain', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(372, 74, 1, '', '附加权限', 'admin', 'marketing.store_bargain', '', '', '', '[]', 0, 0, 0, 1, '/admin/marketing/store_bargain/attr', '', 1, '', 0, '', 0),
(373, 372, 1, '', '修改砍价状态', 'admin', '', '', 'marketing/bargain/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(374, 372, 1, '', '砍价商品详情', 'admin', '', '', 'marketing/bargain/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(375, 74, 1, '', '公共权限', 'admin', 'marketing.store_bargain', 'public', '', '', '[]', 0, 0, 0, 1, '/admin/marketing/store_bargain/public', '', 1, '', 0, '', 0),
(376, 375, 1, '', '分类树型列表', 'admin', '', '', 'product/category/tree/<type>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(377, 375, 1, '', '商品插件列表', 'admin', '', '', 'product/product/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(378, 375, 1, '', '运费模板', 'admin', '', '', 'product/product/get_template', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(379, 372, 1, '', '修改添加砍价商品', 'admin', '', '', 'marketing/bargain/<id>', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(380, 372, 1, '', '删除砍价商品', 'admin', '', '', 'marketing/bargain/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(381, 75, 1, '', '拼团列表', 'admin', '', '', 'marketing/combination', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(382, 75, 1, '', '拼团数据', 'admin', '', '', 'marketing/combination/statistics', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(383, 75, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(384, 383, 1, '', '拼团状态', 'admin', '', '', 'marketing/combination/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(385, 383, 1, '', '删除拼团', 'admin', '', '', 'marketing/combination/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(386, 75, 1, '', '公共权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(387, 386, 1, '', '树型分类列表', 'admin', '', '', 'product/category/tree/<type>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(388, 386, 1, '', '商品插件列表', 'admin', '', '', 'product/product/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(389, 386, 1, '', '运费模板列表', 'admin', '', '', 'product/product/get_template', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(390, 383, 1, '', '获取拼团详情', 'admin', '', '', 'marketing/combination/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(391, 383, 1, '', '编辑添加拼团', 'admin', '', '', 'marketing/combination/<id>', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(392, 76, 1, '', '正在拼团列表', 'admin', '', '', 'marketing/combination/combine/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(393, 76, 1, '', '拼团人员列表', 'admin', '', '', 'marketing/combination/order_pink/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(395, 77, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(396, 395, 1, '', '修改拼团状态', 'admin', '', '', 'marketing/seckill/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(397, 77, 1, '', '公共权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(398, 397, 1, '', '分类树型列表', 'admin', '', '', 'product/category/tree/<type>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(399, 397, 1, '', '商品插件列表', 'admin', '', '', 'product/product/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(400, 397, 1, '', '运费模板列表', 'admin', '', '', 'product/product/get_template', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(401, 397, 1, '', '秒杀时间段列表', 'admin', '', '', 'marketing/seckill/time_list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(402, 395, 1, '', '编辑添加秒杀商品', 'admin', '', '', 'marketing/seckill/<id>', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(403, 395, 1, '', '删除秒杀商品', 'admin', '', '', 'marketing/seckill/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(404, 39, 1, '', '提现申请列表', 'admin', '', '', 'finance/extract', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(405, 39, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(406, 44, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(407, 406, 1, '', '保存修改文章', 'admin', '', '', 'cms/cms', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(408, 406, 1, '', '获取文章详情', 'admin', '', '', 'cms/cms/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(409, 44, 1, '', '公共权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(410, 406, 1, '', '关联商品列表', 'admin', '', '', 'product/product/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(411, 406, 1, '', '分类树型列表', 'admin', '', '', 'product/category/tree/<type>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(412, 406, 1, '', '关联商品', 'admin', '', '', 'cms/cms/relation/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(413, 406, 1, '', '取消关联', 'admin', '', '', 'cms/cms/unrelation/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(414, 406, 1, '', '删除文章', 'admin', '', '', 'cms/cms/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(415, 45, 1, '', '文章列表', 'admin', '', '', 'cms/category', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(416, 45, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(417, 416, 1, '', '文章分类添加表单', 'admin', '', '', 'cms/category/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'cms-category-create', 0),
(418, 416, 1, '', '保存文章分类', 'admin', '', '', 'cms/category', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(419, 416, 1, '', '编辑文章分类', 'admin', '', '', 'cms/category/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(420, 416, 1, '', '修改文章分类', 'admin', '', '', 'cms/category/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(421, 416, 1, '', '删除文章分类', 'admin', '', '', 'cms/category/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(422, 678, 1, '', '客服列表', 'admin', '', '', 'app/wechat/kefu', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(423, 678, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(425, 0, 1, '', '公共权限', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(426, 425, 1, '', '地图KEY权限', 'admin', '', '', 'merchant/store/address', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(431, 425, 1, '', '店员搜索门店列表', 'admin', '', '', 'merchant/store_list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(435, 425, 1, '', '选择用户插件列表', 'admin', '', '', 'app/wechat/kefu/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(442, 229, 1, '', '城市数据列表', 'admin', '', '', 'setting/city/list/<parent_id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(443, 229, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(444, 443, 1, '', '获取添加城市表单', 'admin', '', '', 'setting/city/add/<parent_id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(445, 443, 1, '', '保存修改城市数据', 'admin', '', '', 'setting/city/save', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(446, 443, 1, '', '获取修改城市表单', 'admin', '', '', 'setting/city/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(447, 443, 1, '', '删除城市数据', 'admin', '', '', 'setting/city/del/<city_id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(448, 145, 1, '', '物流公司列表', 'admin', '', '', 'freight/express', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(449, 145, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(450, 449, 1, '', '修改物流公司状态', 'admin', '', '', 'freight/express/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(451, 449, 1, '', '获取添加物流公司表单', 'admin', '', '', 'freight/express/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(452, 449, 1, '', '保存物流公司', 'admin', '', '', 'freight/express', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(453, 449, 1, '', '获取编辑物流公司表单', 'admin', '', '', 'freight/express/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(454, 449, 1, '', '修改物流公司', 'admin', '', '', 'freight/express/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(455, 449, 1, '', '删除物流公司', 'admin', '', '', 'freight/express/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(456, 230, 1, '', '运费模板列表', 'admin', '', '', 'setting/shipping_templates/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(457, 230, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(458, 457, 1, '', '运费模板城市数据', 'admin', '', '', 'setting/shipping_templates/city_list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(459, 457, 1, '', '保存或者修改运费模板', 'admin', '', '', 'setting/shipping_templates/save/<id>', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(460, 457, 1, '', '删除运费模板', 'admin', '', '', 'setting/shipping_templates/del/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(461, 111, 1, '', '配置分类列表', 'admin', '', '', 'setting/config_class', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(462, 111, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(463, 462, 1, '', '配置分类添加表单', 'admin', '', '', 'setting/config_class/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(464, 462, 1, '', '保存配置分类', 'admin', '', '', 'setting/config_class', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(465, 641, 1, '', '编辑配置分类', 'admin', '', '', 'setting/config_class/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(466, 462, 1, '', '删除配置分类', 'admin', '', '', 'setting/config_class/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(467, 125, 1, '', '配置列表展示', 'admin', '', '', 'setting/config', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(468, 125, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(469, 468, 1, '', '添加配置字段表单', 'admin', '', '', 'setting/config/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(470, 468, 1, '', '保存配置字段', 'admin', '', '', 'setting/config', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(471, 468, 1, '', '编辑配置字段表单', 'admin', '', '', 'setting/config/<id>/edit', '', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(472, 468, 1, '', '编辑配置分类', 'admin', '', '', 'setting/config/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(473, 468, 1, '', '删除配置', 'admin', '', '', 'setting/config/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(474, 468, 1, '', '修改配置状态', 'admin', '', '', 'setting/config/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(475, 112, 1, '', '组合数据列表', 'admin', '', '', 'setting/group', 'GET', '[]', 0, 1, 0, 1, '', '', 2, '', 0, '', 0),
(476, 112, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(477, 476, 1, '', '新增组合数据', 'admin', '', '', 'setting/group', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(478, 476, 1, '', '获取组合数据', 'admin', '', '', 'setting/group/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(479, 476, 1, '', '修改组合数据', 'admin', '', '', 'setting/group/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(480, 476, 1, '', '删除组合数据', 'admin', '', '', 'setting/group/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(481, 126, 1, '', '组合数据列表表头', 'admin', '', '', 'setting/group_data/header', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(482, 126, 1, '', '组合数据列表', 'admin', '', '', 'setting/group_data', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(483, 126, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(484, 483, 1, '', '获取组合数据添加表单', 'admin', '', '', 'setting/group_data/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(485, 483, 1, '', '保存组合数据', 'admin', '', '', 'setting/group_data', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(486, 483, 1, '', '获取组合数据信息', 'admin', '', '', 'setting/group_data/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(487, 483, 1, '', '修改组合数据信息', 'admin', '', '', 'setting/group_data/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(488, 483, 1, '', '删除组合数据', 'admin', '', '', 'setting/group_data/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(489, 483, 1, '', '修改组合数据状态', 'admin', '', '', 'setting/group_data/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(490, 57, 1, '', '清除缓存', 'admin', '', '', 'system/refresh_cache/cache', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(491, 57, 1, '', '清除日志', 'admin', '', '', 'system/refresh_cache/log', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(492, 47, 1, '', '管理员搜索列表', 'admin', '', '', 'system/log/search_admin', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(493, 47, 1, '', '系统日志列表', 'admin', '', '', 'system/log', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(494, 64, 1, '', '文件校验列表', 'admin', '', '', 'system/file', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(495, 66, 1, '', '清除数据接口', 'admin', '', '', 'system/clear/<type>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(496, 67, 1, '', '数据库列表', 'admin', '', '', 'system/backup', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(497, 67, 1, '', '数据库备份列表', 'admin', '', '', 'system/backup/file_list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(498, 67, 1, '', '数据表详情', 'admin', '', '', 'system/backup/read', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(499, 67, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(500, 499, 1, '', '备份表', 'admin', '', '', 'system/backup/backup', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(501, 499, 1, '', '优化表', 'admin', '', '', 'system/backup/optimize', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(502, 499, 1, '', '修复表', 'admin', '', '', 'system/backup/repair', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(503, 499, 1, '', '导入sql', 'admin', '', '', 'system/backup/import', 'POST', '[]', 0, 0, 1, 1, '', '', 2, '', 0, '', 0),
(504, 499, 1, '', '删除数据库备份', 'admin', '', '', 'system/backup/del_file', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(505, 499, 1, '', '备份下载', 'admin', '', '', 'backup/download', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(507, 92, 1, '', '微信菜单列表', 'admin', '', '', 'app/wechat/menu', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(508, 92, 1, '', '保存微信菜单', 'admin', '', '', 'app/wechat/menu', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(553, 109, 1, '', '图文列表', 'admin', '', '', 'app/wechat/news', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(554, 109, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(555, 554, 1, '', '保存图文', 'admin', '', '', 'app/wechat/news', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(556, 554, 1, '', '图文详情', 'admin', '', '', 'app/wechat/news/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(557, 554, 1, '', '删除图文', 'admin', '', '', 'app/wechat/news/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(558, 114, 1, '', '公共权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(559, 558, 1, '', '回复关键词', 'admin', '', '', 'app/wechat/reply', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(560, 115, 1, '', '关键词回复列表', 'admin', '', '', 'app/wechat/keyword', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(561, 115, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(562, 558, 1, '', '保存修改关键字', 'admin', '', '', 'app/wechat/keyword/<id>', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(563, 561, 1, '', '获取关键字信息', 'admin', '', '', 'app/wechat/keyword/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(564, 561, 1, '', '修改关键字状态', 'admin', '', '', 'app/wechat/keyword/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(565, 561, 1, '', '删除关键字', 'admin', '', '', 'app/wechat/keyword/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(566, 656, 1, 'md-images', '素材中心', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/system/file', '656', 1, 'devise', 1, 'system-file', 0),
(567, 566, 1, '', '附件列表', 'admin', '', '', 'file/file', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(568, 566, 1, '', '附件分类', 'admin', '', '', 'file/category', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(569, 566, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(570, 569, 1, '', '附件分类表单', 'admin', '', '', 'file/category/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(571, 569, 1, '', '附件分类保存', 'admin', '', '', 'file/category', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(572, 569, 1, '', '删除附件', 'admin', '', '', 'file/file/delete', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(573, 569, 1, '', '移动附件分类', 'admin', '', '', 'file/file/do_move', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(574, 566, 1, '', '上传附件', 'admin', '', '', 'file/upload/<upload_type?>', 'POST', '[]', 10, 0, 0, 1, '', '', 2, '', 0, '', 0),
(575, 569, 1, '', '附件分类编辑表单', 'admin', '', '', 'file/category/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(576, 569, 1, '', '附件分类修改', 'admin', '', '', 'file/category/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(577, 2, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(578, 3, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(579, 6, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(580, 99, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(581, 5, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(582, 70, 1, '', '优惠卷模板列表', 'admin', '', '', 'marketing/coupon/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(583, 70, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(584, 29, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(585, 10, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(586, 1366, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '9/1093/11/1366', 1, '', 0, '', 0),
(587, 25, 1, '', '个人中心', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/system/user', '', 1, '', 0, 'system-user', 0),
(589, 1092, 1, '', '用户标签', 'admin', 'user.user_label', 'index', '', '', '[]', 8, 1, 0, 1, '/admin/user/label', '9/1092', 1, 'user', 1, 'user-user-label', 0),
(590, 589, 1, '', '用户标签接口', 'admin', '', '', 'user/user_label', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(591, 589, 1, '', '删除用户标签', 'admin', '', '', 'user/user_label/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(592, 589, 1, '', '添加修改用户标签', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/label_add', '', 1, '', 0, 'admin-user-label_add', 0),
(593, 592, 1, '', '添加修改用户标签表单', 'admin', '', '', 'user/user_label/add/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(594, 592, 1, '', '保存修改用户标签', 'admin', '', '', 'user/user_label/save', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(596, 2, 1, '', '商品导出', 'admin', '', '', 'export/storeProduct', 'GET', '[]', 10, 0, 0, 1, '', '', 2, '', 0, 'export-storeProduct', 0),
(597, 5, 1, '', '订单导出', 'admin', '', '', 'export/storeorder', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'export-storeOrder', 0),
(598, 77, 1, '', '秒杀商品导出', 'admin', '', '', 'export/storeSeckill', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'export-storeSeckill', 0),
(600, 75, 1, '', '拼团商品导出', 'admin', '', '', 'export/storeCombination', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'export-storeCombination', 0),
(601, 74, 1, '', '砍价商品导出', 'admin', '', '', 'export/storeBargain', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'export-storeBargain', 0),
(602, 29, 1, '', '推广员列表导出', 'admin', '', '', 'export/userAgent', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'export-userAgent', 0),
(603, 40, 1, '', '用户充值导出', 'admin', '', '', 'export/userRecharge', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'export-userRecharge', 0),
(605, 25, 1, '', '商业授权', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/system/maintain/auth', '', 1, '', 0, 'system-maintain-auth', 0),
(606, 29, 1, '', '分销员数据', 'admin', '', '', 'agent/statistics', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(607, 587, 1, '', '修改密码', 'admin', '', '', 'setting/update_admin', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(608, 605, 1, '', '商业授权', 'admin', '', '', 'auth', 'GET', '[]', 0, 1, 0, 1, '', '', 2, '', 0, '', 0),
(610, 20, 1, '', '管理员列表', 'admin', '', '', 'setting/admin', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(611, 19, 1, '', '身份列表', 'admin', '', '', 'setting/role', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(612, 2, 1, '', '批量上下架', 'admin', '', '', 'product/product/product_show', 'PUT', '[]', 5, 0, 0, 1, '', '', 2, '', 0, 'product-product-product_show', 0),
(613, 585, 1, '', '批量设置标签', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/set_label', '', 1, '', 0, 'admin-user-set_label', 0),
(614, 613, 1, '', '获取标签表单', 'admin', '', '', 'user/set_label', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(615, 613, 1, '', '保存标签', 'admin', '', '', 'user/save_set_label', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(616, 80, 1, '', '积分导出', 'admin', '', '', 'export/userPoint', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'export-userPoint', 0),
(617, 41, 1, '', '资金记录导出', 'admin', '', '', 'export/userFinance', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'export-userFinance', 0),
(618, 42, 1, '', '佣金导出', 'admin', '', '', 'export/userCommission', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'export-userCommission', 0),
(619, 21, 1, '', '权限列表', 'admin', '', '', 'setting/menus', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(620, 22, 1, '', '商品详情', 'admin', '', '', 'product/product/<id>', 'GET', '[]', 0, 1, 1, 1, '', '', 2, '', 0, '', 0),
(621, 585, 1, '', '保存用户信息', 'admin', '', '', 'user/user', 'POST', '[]', 10, 0, 0, 1, '', '', 2, '', 0, '', 0),
(622, 585, 1, '', '积分余额', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/edit_other', '', 1, '', 0, '', 0),
(623, 622, 1, '', '获取修改用户详情表单', 'admin', '', '', 'user/edit_other/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(624, 622, 1, '', '修改用户余额', 'admin', '', '', 'user/update_other/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(625, 585, 1, '', '赠送用户', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/user_level', '', 1, '', 0, '', 0),
(626, 625, 1, '', '获取表单', 'admin', '', '', 'user/give_level/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(627, 625, 1, '', '赠送会员等级', 'admin', '', '', 'user/save_give_level/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(628, 585, 1, '', '单个用户分组设置', 'admin', '', '', 'user/save_set_group', 'PUT', '[]', 10, 0, 0, 1, '', '', 2, '', 0, '', 0),
(630, 375, 1, '', '获取商品属性', 'admin', '', '', 'product/product/attrs/<id>/<type>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(631, 386, 1, '', '商品规格获取', 'admin', '', '', 'product/product/attrs/<id>/<type>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(632, 397, 1, '', '商品规格和获取', 'admin', '', '', 'product/product/attrs/<id>/<type>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(633, 395, 1, '', '获取秒杀详情', 'admin', '', '', 'marketing/seckill/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(634, 40, 1, '', '删除充值记录', 'admin', '', '', 'finance/recharge/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(635, 20, 1, '', '修改管理员状态', 'admin', '', '', 'setting/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(638, 457, 1, '', '获取运费模板详情', 'admin', '', '', 'setting/shipping_templates/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(639, 457, 1, '', '删除运费模板', 'admin', '', '', 'setting/shipping_templates/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(640, 462, 1, '', '修改配置分类状态', 'admin', '', '', 'setting/config_class/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(641, 462, 1, '', '编辑配置分类', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, 'system/config/system_config_tab/edit', '', 1, '', 0, '', 0),
(642, 641, 1, '', '获取编辑配置分类表单', 'admin', '', '', 'setting/config_class/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(655, 65, 1, '', '在线升级', 'admin', '', '', '', '', '[]', 0, 0, 1, 1, '/admin/system/system_upgradeclient/index', '', 1, '', 0, 'system-system-upgradeclient', 0),
(656, 0, 1, '', '装修', 'admin', '', '', '', '', '[]', 89, 1, 0, 1, '/admin/setting/pages', '', 1, 'devise', 1, 'admin-setting-pages', 0),
(657, 656, 1, 'md-umbrella', '店铺装修', 'admin', '', '', '', '', '[]', 4, 1, 0, 1, '/admin/setting/pages/devise', '12/656', 1, 'devise', 1, 'admin-setting-pages-devise', 0),
(658, 657, 1, '', '页面编辑', 'admin', '', '', '', '', '[]', 1, 0, 1, 1, '/admin/setting/pages/diy', '656/657', 1, '', 0, 'admin-setting-pages-diy', 0),
(661, 657, 1, '', 'DIY列表', 'admin', '', '', 'diy/get_list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(662, 657, 1, '', '组件文章分类', 'admin', '', '', 'cms/category_list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(663, 657, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/diy', '', 1, '', 0, 'admin-setting-diy-additional', 0),
(664, 663, 1, '', '获取页面设计', 'admin', '', '', 'diy/get_info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(665, 663, 1, '', '保存和修改页面', 'admin', '', '', 'diy/save/<id?>', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'admin-setting-pages-diy-save', 0),
(666, 657, 1, '', '路径列表', 'admin', '', '', 'diy/get_url', 'GET', '[]', 0, 0, 0, 1, '', '12/656/657', 2, '', 0, '', 0),
(667, 663, 1, '', '删除页面', 'admin', '', '', 'diy/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(668, 663, 1, '', '修改页面状态', 'admin', '', '', 'diy/set_status/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(669, 2, 1, '', '批量下架', 'admin', '', '', 'product/product/product_unshow', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(670, 581, 1, '', '订单打印', 'admin', '', '', 'order/print/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(671, 585, 1, '', '清除会员等级', 'admin', '', '', 'user/del_level/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(672, 271, 1, '', 'H5推广二维码', 'admin', '', '', 'agent/look_h5_code', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(673, 416, 1, '', '修改文章分类状态', 'admin', '', '', 'cms/category/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(674, 229, 1, '', '清除城市缓存', 'admin', '', '', 'setting/city/clean_cache', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(675, 657, 1, '', '组件商品分类', 'admin', '', '', 'diy/get_category', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(676, 657, 1, '', '组件商品列表', 'admin', '', '', 'diy/get_product', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(677, 581, 1, '', '订单号核销', 'admin', '', '', 'order/write_update/<order_id>', 'PUT', '[]', 0, 0, 0, 1, 'order/dels', '', 2, '', 0, 'admin-order-write_update', 0),
(678, 165, 1, '', '客服列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/setting/store_service/index', '', 1, '', 0, 'admin-setting-store_service-index', 0),
(679, 165, 1, '', '客服话术', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/setting/store_service/speechcraft', '', 1, '', 0, 'admin-setting-store_service-speechcraft', 0),
(685, 22, 1, '', '上传商品视频', 'admin', '', '', 'product/product/get_temp_keys', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(686, 27, 1, 'md-videocam', '直播', 'admin', '', '', '', '', '[]', 91, 1, 0, 1, '/admin/marketing/live', '27', 1, 'marketing', 0, 'admin-marketing-live', 0),
(687, 686, 1, '', '直播间管理', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/live/live_room', '', 1, '', 0, 'admin-marketing-live-live_room', 0),
(688, 686, 1, '', '直播商品管理', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/live/live_goods', '', 1, '', 0, 'admin-marketing-live-live_goods', 0),
(689, 686, 1, '', '主播管理', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/live/anchor', '', 1, '', 0, 'admin-marketing-live-anchor', 0),
(690, 687, 1, '', '添加直播间', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/live/add_live_room', '27/686/687', 1, '', 0, 'admin-marketing-live-add_live_room', 0),
(691, 688, 1, '', '添加直播商品', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/live/add_live_goods', '27/686/688', 1, '', 0, 'admin-marketing-live-add_live_goods', 0),
(693, 689, 1, '', '主播列表', 'admin', '', '', 'live/anchor/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'admin-marketing-live-anchor', 0),
(694, 689, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/*', '', 1, '', 0, '', 0),
(695, 694, 1, '', '添加/修改主播表单', 'admin', '', '', 'live/anchor/add/<id>', 'GET', '[]', 0, 0, 0, 1, 'live/anchor/add/<id>', '', 2, '', 0, 'live-anchor-add', 0),
(696, 694, 1, '', '添加/修改提交', 'admin', '', '', 'live/anchor/save', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(697, 694, 1, '', '删除主播', 'admin', '', '', 'live/anchor/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(698, 694, 1, '', '设置主播是否显示', 'admin', '', '', 'live/anchor/set_show/<id>/<is_show>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(699, 688, 1, '', '直播商品列表', 'admin', '', '', 'live/goods/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(700, 691, 1, '', '生成直播商品', 'admin', '', '', 'live/goods/create', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(701, 691, 1, '', '保存直播商品', 'admin', '', '', 'live/goods/add', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(702, 688, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/*', '', 1, '', 0, '/admin/*', 0),
(703, 702, 1, '', '直播商品详情', 'admin', '', '', 'live/goods/detail/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(704, 702, 1, '', '删除直播商品', 'admin', '', '', 'live/goods/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(705, 702, 1, '', '同步直播商品', 'admin', '', '', 'live/goods/syncGoods', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(706, 702, 1, '', '设置直播商品是否显示', 'admin', '', '', 'live/goods/set_show/<id>/<is_show>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(707, 687, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/*', '', 1, '', 0, '', 0),
(708, 687, 1, '', '直播间列表', 'admin', '', '', 'live/room/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(709, 707, 1, '', '添加直播间提交', 'admin', '', '', 'live/room/add', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(710, 707, 1, '', '直播间详情', 'admin', '', '', 'live/room/detail/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(711, 707, 1, '', '直播间添加（关联）商品', 'admin', '', '', 'live/room/add_goods', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(712, 707, 1, '', '删除直播间', 'admin', '', '', 'live/room/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(713, 707, 1, '', '设置直播间是否显示', 'admin', '', '', 'live/room/set_show/<id>/<is_show>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(714, 707, 1, '', '同步直播间状态', 'admin', '', '', 'live/room/syncRoom', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(717, 7, 1, 'ios-pie', '商品统计', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/statistic/product', '7', 1, '', 0, 'admin-statistic', 0),
(718, 7, 1, 'ios-stats', '用户统计', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/statistic/user', '7', 1, '', 0, 'admin-statistic', 0),
(719, 71, 1, '', '添加优惠卷', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/store_coupon_issue/create', '27/30/71', 1, '', 0, 'admin-marketing-store_coupon_issue-create', 0),
(720, 1352, 1, '', '配送员管理', 'admin', '', '', '', '', '[]', 10, 1, 0, 1, '/admin/setting/delivery_service/index', '12/1350/1352', 1, '', 0, 'setting-delivery-service', 0),
(721, 729, 1, '', '编辑配送员', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/delivery_service/edit', '', 1, '', 0, 'setting-delivery_service-edit', 0),
(722, 720, 1, '', '配送员列表', 'admin', '', '', 'order/delivery/index', 'GET', '[]', 0, 0, 0, 1, '', '12/303/720', 2, '', 0, '', 0),
(723, 721, 1, '', '修改配送员', 'admin', '', '', 'order/delivery/update/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(724, 729, 1, '', '添加配送员', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/delivery_service/add', '', 1, '', 0, 'setting-delivery_service-add', 0),
(725, 724, 1, '', '获取添加配送员表单', 'admin', '', '', 'order/delivery/add', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(726, 724, 1, '', '保存配送员', 'admin', '', '', 'order/delivery/save', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(727, 729, 1, '', '删除配送员', 'admin', '', '', 'order/delivery/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(728, 729, 1, '', '配送员是否开启', 'admin', '', '', 'order/delivery/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(729, 720, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '', 1, '', 0, '', 0),
(731, 1093, 1, '', '付费会员', 'admin', '', '', '', '', '[]', 7, 1, 0, 1, '/admin/vipuser/grade', '9/1093', 1, '', 0, 'user-user-grade', 0),
(732, 762, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 1, 1, '/admin*', '', 1, '', 0, '', 0),
(733, 732, 1, '', ' 添加会员批次', 'admin', '', '', 'user/member_batch/save/<id>', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(734, 732, 1, '', '列表字段修改', 'admin', '', '', 'user/member_batch/set_value/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'user-member_batch-set_value', 0),
(735, 732, 1, '', '会员卡导出', 'admin', '', '', 'export/memberCard/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'export-member_card', 0),
(736, 762, 1, '', '卡密列表', 'admin', '', '', 'user/member_batch/index', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(737, 732, 1, '', '会员卡列表', 'admin', '', '', 'user/member_card/index', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'user-member_card-index', 0),
(738, 165, 1, '', '用户留言', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/setting/store_service/feedback', '', 1, '', 0, 'admin-setting-store_service-feedback', 0),
(739, 738, 1, '', '列表展示', 'admin', '', '', 'app/feedback', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(740, 738, 1, '', '附件权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '*', '', 1, '', 0, '', 0),
(741, 740, 1, '', '删除反馈', 'admin', '', '', 'app/feedback/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(742, 679, 1, '', '列表展示', 'admin', '', '', 'app/wechat/speechcraft', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(743, 679, 1, '', '附件权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '*', '', 1, '', 0, '', 0),
(744, 743, 1, '', '添加话术', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/store_service/speechcraft/add', '', 1, '', 0, 'admin-setting-store_service-speechcraft-add', 0),
(745, 743, 1, '', '编辑话术', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/store_service/speechcraft/edit', '', 1, '', 0, 'admin-setting-store_service-speechcraft-edit', 0),
(746, 744, 1, '', '获取添加话术表单', 'admin', '', '', 'app/wechat/speechcraft/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(747, 744, 1, '', '保存话术', 'admin', '', '', 'app/wechat/speechcraft', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(748, 745, 1, '', '获取编辑话术表单', 'admin', '', '', 'app/wechat/speechcraft/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(749, 745, 1, '', '确认修改', 'admin', '', '', 'app/wechat/speechcraft/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(750, 743, 1, '', '删除话术', 'admin', '', '', 'app/wechat/speechcraft/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(751, 731, 1, '', '会员类型', 'admin', '', '', '', '', '[]', 5, 1, 0, 1, '/admin/vipuser/grade/type', '9/1093/731', 1, '', 0, 'admin-user-member-type', 0),
(752, 751, 1, '', '会员分类列表', 'admin', '', '', 'user/member/ship', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'user-member-ship', 0),
(753, 751, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 1, 1, '/admin*', '', 1, '', 0, '', 0),
(754, 753, 1, '', '会员卡类型保存', 'admin', '', '', 'user/member_ship/save/<id>', 'POST', '[]', 0, 1, 1, 1, '', '', 2, '', 0, 'user-member_ship-save', 0),
(755, 31, 1, '', '砍价列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_bargain/bargain_list', '', 1, '', 0, 'marketing-store_bargain-bargain_list', 0),
(756, 585, 1, '', '添加用户', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/save', '', 1, '', 0, 'admin-user-save', 0),
(757, 756, 1, '', '获取添加用户表单', 'admin', '', '', 'user/user/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(758, 756, 1, '', '保存用户', 'admin', '', '', 'user/user', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(759, 585, 1, '', '同步公众号用户', 'admin', '', '', 'user/user/syncUsers', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'admin-user-synchro', 0),
(760, 4, 1, 'md-desktop', '收银订单', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/order/offline', '4', 1, 'order', 1, 'admin-order-offline', 0),
(761, 760, 1, '', '线下收银订单', 'admin', '', '', 'order/scan_list', 'GET', '[]', 0, 0, 1, 1, '', '', 2, '', 0, 'admin-order-scan_list', 0),
(762, 731, 1, '', '卡密会员', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/vipuser/grade/card', '9/1093/731', 1, '', 0, 'admin-user-grade-card', 0),
(763, 731, 1, '', '会员记录', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/vipuser/grade/record', '9/1093/731', 1, '', 0, 'admin-user-grade-record', 0),
(764, 763, 1, '', '会员记录列表', 'admin', '', '', 'user/member/record', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'user-member-record', 0),
(765, 731, 1, '', '会员权益', 'admin', '', '', '', '', '[]', 4, 1, 0, 1, '/admin/vipuser/grade/right', '9/1093/731', 1, '', 0, 'admin-user-grade-right', 0),
(766, 7, 1, 'md-trending-up', '交易统计', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/statistic/transaction', '7', 1, '', 0, 'admin-statistic', 0),
(767, 36, 1, '', '发票管理', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/order/invoice', '35/36', 1, '', 0, 'admin-order-startOrderInvoice-index', 0),
(768, 210, 1, '', '编辑', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '', '', 2, '', 0, 'admin-order-invoice-edit', 0),
(769, 210, 1, '', '订单信息', 'admin', '', '', 'order/invoice_order_info/<id>', 'GET', '[]', 0, 1, 0, 1, '', '', 2, '', 0, 'admin-order-invoice-orderInfo', 0),
(770, 210, 1, '', '编辑提交', 'admin', '', '', 'order/invoice/set/<id>', 'POST', '[]', 0, 1, 0, 1, '', '', 2, '', 0, 'admin-order-invoice-update', 0),
(771, 765, 1, '', '会员权益列表', 'admin', '', '', 'user/member/right', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'user-member-right', 0),
(772, 765, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 1, 1, '/admin*', '', 1, '', 0, '', 0),
(773, 772, 1, '', '会员权益保存', 'admin', '', '', 'user/member_right/save/<id>', 'POST', '[]', 0, 1, 1, 1, '', '', 2, '', 0, 'user-member_right-save', 0),
(774, 589, 1, '', '用户标签列表', 'admin', '', '', 'user/user_label_cate/all', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'admin-user-user_lable_cate-all', 0),
(775, 731, 1, '', '会员协议', 'admin', '', '', '', '', '[]', 3, 1, 0, 1, '/admin/vipuser/grade/agreement', '9/1093/731', 1, '', 0, 'admin-user-grade-agreement', 0),
(776, 775, 1, '', '编辑会员协议', 'admin', '', '', 'user/member_agreement/save/<id>', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'member_agreement-save', 0),
(777, 775, 1, '', '会员协议列表', 'admin', '', '', 'user/member/agreement', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'user-member-agreement-list', 0),
(778, 740, 1, '', '获取修改备注表单接口', 'admin', '', '', 'app/feedback/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(779, 740, 1, '', '修改用户备注接口', 'admin', '', '', 'app/feedback/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(780, 589, 1, '', '标签分类', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/label_cate', '', 1, '', 0, '', 0),
(781, 780, 1, '', '获取标签分类列表', 'admin', '', '', 'user/user_label_cate/all', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(782, 780, 1, '', '添加标签分类', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/label_cate/add', '', 1, '', 0, '', 0),
(783, 782, 1, '', '获取标签分类表单', 'admin', '', '', 'user/user_label_cate/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(784, 782, 1, '', '保存标签分类', 'admin', '', '', 'user/user_label_cate', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(785, 780, 1, '', '修改标签分类', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/user/label_cate/edit', '', 1, '', 0, '', 0),
(786, 785, 1, '', '获取修改标签分类表单', 'admin', '', '', 'user/user_label_cate/<id>/edit', 'GET', '[]', 0, 0, 0, 1, 'user/user_label_cate/<id>/edit', '', 2, '', 0, '', 0),
(787, 785, 1, '', '保存用户标签分类', 'admin', '', '', 'user/user_label_cate/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(788, 780, 1, '', '删除用户标签分类', 'admin', '', '', 'user/user_label_cate/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(789, 743, 1, '', '话术分类', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/store_service/speechcraft/cate', '', 1, '', 0, 'admin-setting-store_service-speechcraft-cate', 0),
(790, 789, 1, '', '获取话术分类列表', 'admin', '', '', 'app/wechat/speechcraftcate', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(791, 789, 1, '', '添加话术分类', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/store_service/speechcraft/cate/create', '', 1, '', 0, '', 0),
(792, 791, 1, '', '获取话术分类表单', 'admin', '', '', 'app/wechat/speechcraftcate/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(793, 791, 1, '', '保存话术分类', 'admin', '', '', 'app/wechat/speechcraftcate', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(794, 795, 1, '', '获取话术分类表单', 'admin', '', '', 'app/wechat/speechcraftcate/<id>/edit', 'GET', '[]', 0, 0, 0, 1, 'app/wechat/speechcraftcate/<id>/edit', '', 2, '', 0, '', 0),
(795, 789, 1, '', '修改话术分类', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/store_service/speechcraft/cate/edit', '', 1, '', 0, '', 0),
(796, 795, 1, '', '保存修改客户话术分类', 'admin', '', '', 'app/wechat/speechcraftcate/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(797, 789, 1, '', '删除话术分类', 'admin', '', '', 'app/wechat/speechcraftcate/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(798, 209, 1, '', '获取送货人列表', 'admin', '', '', 'order/delivery/list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(799, 209, 1, '', '获取电子面单打印默认配置', 'admin', '', '', 'order/sheet_info', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(800, 581, 1, '', '电子面单打印', 'admin', '', '', 'order/order_dump/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(801, 760, 1, '', '获取收银二维码', 'admin', '', '', 'order/offline_scan', 'GET', '[]', 0, 0, 0, 1, '', '4/760', 2, '', 0, '', 0),
(802, 1364, 1, '', '获取订单发票数据', 'admin', '', '', 'order/invoice/chart', 'GET', '[]', 0, 1, 0, 1, '', '35/36/767/1364', 2, '', 0, '', 0),
(803, 762, 1, '', '下载卡密二维码', 'admin', '', '', 'user/member_scan', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(805, 584, 1, '', '修改推广人', 'admin', '', '', 'agent/spread', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(806, 71, 1, '', '复制优惠券', 'admin', '', '', 'marketing/coupon/copy/<id>', 'GET', '[]', 0, 0, 0, 1, 'marketing/coupon/copy/369', '', 2, '', 0, '', 0),
(807, 755, 1, '', '获取砍价列表', 'admin', '', '', 'marketing/bargain_list', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(808, 77, 1, '', '秒杀商品列表', 'admin', '', '', 'marketing/seckill', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(809, 95, 1, '', '获取平台用户信息', 'admin', '', '', 'serve/info', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(810, 95, 1, '', '获取平台消费列表', 'admin', '', '', 'serve/record', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(811, 95, 1, '', '修改手机号', 'admin', '', '', 'serve/update_phone', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(812, 95, 1, '', '修改签名', 'admin', '', '', 'serve/sms/sign', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(813, 95, 1, '', '修改账号密码', 'admin', '', '', 'serve/modify', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(814, 721, 1, '', '获取编辑配送员表单', 'admin', '', '', 'order/delivery/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(815, 717, 1, '', '获取基础商品接口', 'admin', '', '', 'statistic/product/get_basic', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(816, 717, 1, '', '获取商品趋势', 'admin', '', '', 'statistic/product/get_trend', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(817, 717, 1, '', '获取商品排行', 'admin', '', '', 'statistic/product/get_product_ranking', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(818, 718, 1, '', '获取用户基础', 'admin', '', '', 'statistic/user/get_basic', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(819, 718, 1, '', '获取用户趋势', 'admin', '', '', 'statistic/user/get_trend', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(820, 718, 1, '', '获取用户地区排行', 'admin', '', '', 'statistic/user/get_region', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(821, 718, 1, '', '获取用户性别排行', 'admin', '', '', 'statistic/user/get_sex', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(822, 766, 1, '', '获取交易趋势', 'admin', '', '', 'statistic/trade/top_trade', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(823, 766, 1, '', '获取订单趋势', 'admin', '', '', 'statistic/trade/bottom_trade', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(824, 718, 1, '', '导出用户统计', 'admin', '', '', 'statistic/user/get_excel', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(825, 717, 1, '', '导出商品统计', 'admin', '', '', 'statistic/product/get_excel', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(828, 10, 1, '', '用户列表', 'admin', '', '', 'user/user', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(830, 732, 1, '', '卡密列表', 'admin', '', '', 'user/member_card/index/<card_batch_id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(831, 423, 1, '', '进入工作台', 'admin', '', '', 'app/wechat/kefu/login/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(832, 71, 1, '', '保存优惠券', 'admin', '', '', 'marketing/coupon/save_coupon', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(833, 755, 1, '', '砍价详情', 'admin', '', '', 'marketing/bargain_list_info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(834, 95, 1, '', '短信记录列表', 'admin', '', '', 'notify/sms/record', 'GET', '[]', 0, 0, 0, 1, 'notify/sms/record', '', 2, '', 0, '', 0),
(835, 28, 1, '', '分销设置表单', 'admin', '', '', 'agent/config/edit_basics', 'GET', '[]', 0, 1, 0, 1, '', '26/28', 2, '', 0, '', 0),
(836, 28, 1, '', '分销设置表单提交', 'admin', '', '', 'agent/config/save_basics', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(837, 79, 1, '', '积分配置表单', 'admin', '', '', 'marketing/integral_config/edit_basics', 'GET', '[]', 0, 1, 0, 1, '', '', 2, '', 0, '', 0),
(838, 79, 1, '', '积分配置表单提交', 'admin', '', '', 'marketing/integral_config/save_basics', 'POST', '[]', 0, 1, 0, 1, '', '', 2, '', 0, '', 0),
(843, 154, 1, '', '签到天数头部数据', 'admin', '', '', 'setting/sign_data/header', 'GET', '[]', 0, 1, 0, 1, '', '', 2, '', 0, '', 0),
(844, 154, 1, '', '设置签到数据状态', 'admin', '', '', 'setting/sign_data/set_status/<id>/<status>', 'PUT', '[]', 0, 1, 0, 1, '', '', 2, '', 0, '', 0),
(845, 154, 1, '', '签到天数列表', 'admin', '', '', 'setting/sign_data', 'GET', '[]', 0, 1, 0, 1, '', '', 2, '', 0, '', 0),
(846, 154, 1, '', '添加签到天数表单', 'admin', '', '', 'setting/sign_data/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(847, 154, 1, '', '添加签到天数', 'admin', '', '', 'setting/sign_data', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(848, 154, 1, '', '编辑签到天数表单', 'admin', '', '', 'setting/sign_data/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(849, 154, 1, '', '编辑签到天数', 'admin', '', '', 'setting/sign_data/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(850, 154, 1, '', '删除签到天数', 'admin', '', '', 'setting/sign_data/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(876, 78, 1, '', '秒杀配置列表', 'admin', '', '', 'setting/seckill_data', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(877, 78, 1, '', '添加秒杀表单', 'admin', '', '', 'setting/seckill_data/create', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(878, 78, 1, '', '添加秒杀', 'admin', '', '', 'setting/seckill_data', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(879, 78, 1, '', '编辑秒杀表单', 'admin', '', '', 'setting/seckill_data/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(880, 78, 1, '', '编辑秒杀', 'admin', '', '', 'settting/seckill_data/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(881, 78, 1, '', '删除秒杀', 'admin', '', '', 'setting/seckill_data/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(884, 128, 1, '', '获取数据分类', 'admin', '', '', 'setting/group_all', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(885, 569, 1, '', '附件名称修改', 'admin', '', '', 'file/file/update/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '25/566/569', 2, '', 0, '', 0),
(888, 577, 1, '', '商品活动状态检测', 'admin', '', '', 'product/product/check_activity/<id>', 'GET', '[]', 0, 1, 0, 1, '', '1/2/577', 2, '', 0, '', 0),
(889, 5, 1, '', '手动发货订单导出', 'admin', '', '', 'export/batchOrderDelivery/<id>', 'GET', '[]', 0, 0, 0, 1, '', '4/5', 2, '', 0, 'export-batchOrderDelivery', 0),
(890, 5, 1, '', '手动批量发货', 'admin', '', '', 'order/hand/batch_delivery', 'GET', '[]', 0, 0, 0, 1, '', '4/5', 2, '', 0, 'order-hand-batch_delivery', 0),
(891, 5, 1, '', '自动批量发货', 'admin', '', '', 'order/other/batch_delivery', 'POST', '[]', 0, 0, 0, 1, '', '4/5', 2, '', 0, 'order-other-batch_delivery', 0),
(892, 5, 1, '', '物流公司对照表导出', 'admin', '', '', 'export/expressList', 'GET', '[]', 0, 0, 0, 1, '', '4/5', 2, '', 0, 'export-expressList', 0),
(893, 5, 1, '', '批量删除订单', 'admin', '', '', 'order/batch/del_orders', 'POST', '[]', 0, 0, 0, 1, '', '4/5', 2, '', 0, 'order-batch-del_orders', 0),
(894, 5, 1, '', '订单批量任务记录', 'admin', '', '', 'queue/index', 'GET', '[]', 0, 0, 0, 1, '', '4/5', 2, '', 0, 'queue-index', 0),
(895, 894, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '4/5/894', 1, '', 0, '', 0),
(896, 895, 1, '', '查看具体队列数据', 'admin', '', '', 'queue/delivery/log/<id>/<type>', 'GET', '[]', 0, 0, 0, 1, '', '4/5/894/895', 2, '', 0, 'queue-delivery-log', 0),
(897, 895, 1, '', '重新执行任务', 'admin', '', '', 'queue/again/do_queue/<id>/<type>', 'GET', '[]', 0, 0, 0, 1, '', '4/5/894/895', 2, '', 0, 'queue-again-do_queue', 0),
(899, 895, 1, '', '清除任务', 'admin', '', '', 'queue/del/wrong_queue/<id>/<type>', 'GET', '[]', 0, 0, 0, 1, '', '4/5/894/895', 2, '', 0, 'queue-del-wrong_queue', 0),
(901, 895, 1, '', '停止任务', 'admin', '', '', 'queue/stop/wrong_queue/<id>', 'GET', '[]', 0, 0, 0, 1, 'queue/stop/wrong_queue/<id>', '4/5/894/895', 2, '', 0, 'queue-stop-wrong_queue', 0),
(902, 895, 1, '', '发货记录导出', 'admin', '', '', 'export/batchOrderDelivery/<id>/<queueType>/<cacheType>', 'GET', '[]', 0, 0, 0, 1, '', '4/5/894/895', 2, '', 0, 'export-batchOrderDelivery', 0),
(903, 731, 1, '', '会员卡列表', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/vipuser/grade/list', '9/731', 1, '', 0, '', 0),
(904, 903, 1, '', '会员卡列表', 'admin', '', '', 'user/member_card/index/<card_batch_id>', 'GET', '[]', 0, 0, 0, 1, '', '9/731/903', 2, '', 0, '', 0),
(905, 657, 1, 'logo-snapchat', '页面设计', 'admin', '', '', '', '', '[]', 0, 0, 1, 1, '/admin/setting/pages/template', '656/657', 1, '', 0, 'setting-pages-template', 0),
(906, 581, 1, '', '订单详情查看', 'admin', '', '', 'order/info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '4/5/581', 2, '', 0, '', 0),
(908, 581, 1, '', '订单详情', 'admin', '', '', 'order/info/<id>', 'GET', '[]', 0, 0, 1, 1, '', '4/5/581', 2, '', 0, '', 0),
(909, 78, 1, '', '秒杀配置列表表头', 'admin', '', '', 'setting/seckill_data/header', 'GET', '[]', 0, 0, 0, 1, '', '27/33/78', 2, '', 0, '', 0),
(910, 581, 1, '', '订单记录', 'admin', '', '', 'order/status/<id>', 'GET', '[]', 0, 0, 0, 1, '', '4/5/581', 2, '', 0, '', 0),
(911, 2, 1, '', '获取商品草稿', 'admin', '', '', 'product/cache', 'GET', '[]', 0, 0, 0, 1, '', '1/2', 2, '', 0, '', 0),
(912, 2, 1, '', '获取商品分类cascade', 'admin', '', '', 'product/category/cascader_list/<type?>', 'GET', '[]', 0, 0, 0, 1, '', '1/2', 2, '', 0, '', 0),
(915, 1036, 1, '', '保存广告', 'admin', '', '', 'setting/set_kf_adv', 'POST', '[]', 0, 0, 0, 1, '', '12/656/1036', 2, '', 0, 'adminapi-setting-set_kf_adv', 0),
(916, 1036, 1, '', '获取广告', 'admin', '', '', 'setting/get_kf_adv', 'GET', '[]', 0, 0, 0, 1, '', '12/656/1036', 2, '', 0, 'adminapi-setting-get_kf_adv', 0),
(917, 128, 1, '', '获取隐私协议', 'admin', '', '', 'setting/get_user_agreement', 'GET', '[]', 0, 0, 0, 1, '', '12/656/914', 2, '', 0, 'adminapi-setting-get_user_agreement', 0),
(918, 128, 1, '', '设置隐私协议', 'admin', '', '', 'setting/set_user_agreement', 'POST', '[]', 0, 0, 0, 1, '', '12/656/914', 2, '', 0, 'adminapi-setting-set_user_agreement', 0),
(919, 27, 1, 'ios-apps', '九宫格抽奖', 'admin', '', '', '', '', '[]', 93, 1, 0, 1, '/admin/marketing/lottery/index', '27', 1, 'marketing', 0, 'marketing-lottery-index', 0),
(920, 922, 1, '', '抽奖活动列表', 'admin', '', '', 'lottery/list', 'GET', '[]', 0, 1, 0, 1, '', '27/919/922', 2, '', 0, 'lottery-list', 0),
(921, 922, 1, '', '抽奖详情', 'admin', '', '', 'lottery/detail/<id>', 'GET', '[]', 0, 1, 0, 1, '', '27/919/922', 2, '', 0, '', 0),
(922, 919, 1, '', '抽奖配置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/lottery/create', '27/919', 1, '', 0, 'admin-marketing-lottery-create', 0),
(923, 919, 1, '', '中奖记录', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/lottery/recording_list', '27/919', 1, '', 0, 'admin-marketing-lottery-recording_list', 0),
(924, 922, 1, '', '添加抽奖活动', 'admin', '', '', 'lottery/add', 'POST', '[]', 0, 0, 0, 1, '', '27/913/922', 2, '', 0, '', 0),
(925, 922, 1, '', '修改抽奖活动', 'admin', '', '', 'lottery/edit/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '27/913/922', 2, '', 0, '', 0),
(926, 922, 1, '', '删除抽奖活动', 'admin', '', '', 'lottery/del/<id>', 'DELETE', '[]', 0, 1, 0, 1, '', '27/919/922', 2, '', 0, '', 0),
(927, 922, 1, '', '设置抽奖活动是否显示', 'admin', '', '', 'lottery/set_status/<id>/<status>', 'POST', '[]', 0, 1, 0, 1, '', '27/919/922', 2, '', 0, '', 0),
(928, 923, 1, '', '获取抽奖记录列表', 'admin', '', '', 'lottery/record/list/<id>', 'GET', '[]', 0, 0, 0, 1, '', '27/913/923', 2, '', 0, '', 0),
(929, 923, 1, '', '中奖发货备注', 'admin', '', '', 'lottery/record/deliver', 'POST', '[]', 0, 0, 0, 1, '', '27/913/923', 2, '', 0, '', 0),
(930, 34, 1, '', '积分商品', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_integral/index', '27/34', 1, '', 0, 'marketing-store_integral', 0),
(931, 34, 1, '', '添加积分商品', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/store_integral/create', '27/34', 1, '', 0, 'marketing-store_integral-create', 0),
(932, 4, 1, 'ios-cart-outline', '积分订单', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_integral/order_list', '4', 1, '', 0, 'marketing-store_integral-order', 0),
(933, 34, 1, '', '批量添加积分商品', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/pages/marketing/store_integral/add_store_integral', '27/34', 1, '', 0, 'marketing-store_integral-create', 0),
(934, 930, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '27/34/930/934', 1, '', 0, '', 0),
(935, 934, 1, '', '积分商品批量保存', 'admin', '', '', 'marketing/integral/batch', 'POST', '[]', 0, 0, 0, 1, '', '27/34/930/934', 2, '', 0, '', 0),
(936, 934, 1, '', '积分详情', 'admin', '', '', 'marketing/integral/<id>', 'GET', '[]', 0, 0, 0, 1, '', '27/34/930/934', 2, '', 0, '', 0),
(937, 934, 1, '', '修改积分商品状态', 'admin', '', '', 'marketing/integral/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '27/34/930/934/934', 2, '', 0, '', 0),
(938, 934, 1, '', '积分商品删除', 'admin', '', '', 'marketing/integral/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '27/34/930/934/934', 2, '', 0, '', 0),
(939, 930, 1, '', '公共权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '27/34/930', 1, '', 0, '', 0),
(940, 939, 1, '', '分类树型列表', 'admin', '', '', 'product/category/tree/<type>', 'GET', '[]', 0, 0, 0, 1, '', '27/34/930/939', 2, '', 0, '', 0),
(941, 939, 1, '', '商品插件列表', 'admin', '', '', 'product/product/list', 'GET', '[]', 0, 0, 0, 1, '', '27/34/930/939', 2, '', 0, '', 0),
(942, 939, 1, '', '商品规格和获取', 'admin', '', '', 'product/product/attrs/<id>/<type>', 'GET', '[]', 0, 0, 0, 1, '', '27/34/930/939', 2, '', 0, '', 0),
(943, 932, 1, '', '积分商城订单列表', 'admin', '', '', 'marketing/integral/order/list', 'GET', '[]', 0, 0, 0, 1, '', '27/34/932', 2, '', 0, '', 0),
(944, 932, 1, '', '附加权限', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin*', '27/34/932', 1, '', 0, '', 0),
(945, 944, 1, '', '订单详情', 'admin', '', '', 'marketing/integral/order/info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(946, 944, 1, '', '备注信息', 'admin', '', '', 'marketing/integral/order/remark/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(947, 944, 1, '', '订单状态', 'admin', '', '', 'marketing/integral/order/status/<id>', 'GET', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(948, 944, 1, '', '发送货', 'admin', '', '', 'marketing/integral/order/delivery/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(949, 944, 1, '', '获取配送信', 'admin', '', '', 'marketing/integral/order/distribution/<id>', 'GET', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(950, 944, 1, '', '修改配送信息', 'admin', '', '', 'marketing/integral/order/distribution/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(951, 944, 1, '', '确认收货', 'admin', '', '', 'marketing/integral/order/take/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(952, 944, 1, '', '获取物流信息', 'admin', '', '', 'marketing/integral/order/express/<id>', 'GET', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(953, 944, 1, '', '打印订单', 'admin', '', '', 'marketing/integral/order/print/<id>', 'GET', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(954, 944, 1, '', '获取物流公司', 'admin', '', '', 'marketing/integral/order/express_list', 'GET', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(955, 944, 1, '', '获取配送员', 'admin', '', '', 'marketing/integral/order/delivery/list', 'GET', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(956, 944, 1, '', '电子面单模版', 'admin', '', '', 'marketing/integral/order/express/temp', 'GET', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(957, 944, 1, '', '面单默认信息', 'admin', '', '', 'marketing/integral/order/sheet_info', 'GET', '[]', 0, 0, 0, 1, '', '27/34/932/944', 2, '', 0, '', 0),
(958, 934, 1, '', '积分商品保存', 'admin', '', '', 'marketing/integral/<id>', 'POST', '[]', 0, 0, 0, 1, '', '27/34/930/934', 2, '', 0, '', 0),
(959, 27, 1, 'md-cafe', '优惠套餐', 'admin', '', '', '', '', '[]', 94, 1, 0, 1, '/admin/marketing/store_discounts/index', '27', 1, 'marketing', 0, 'marketing-store_discounts-index', 0),
(960, 959, 1, '', '用户标签', 'admin', '', '', 'user/user_label', 'GET', '[]', 0, 0, 0, 1, '', '27/959', 2, '', 0, '', 0),
(961, 959, 1, '', '套餐上下架', 'admin', '', '', 'marketing/discounts/set_status/<id>/<status>', 'GET', '[]', 0, 0, 0, 1, '', '27/959', 2, '', 0, '', 0),
(962, 959, 1, '', '删除套餐', 'admin', '', '', 'marketing/discounts/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '27/959', 2, '', 0, '', 0),
(963, 959, 1, '', '套餐详情', 'admin', '', '', 'marketing/discounts/info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '27/959', 2, '', 0, '', 0),
(964, 959, 1, '', '套餐保存', 'admin', '', '', 'marketing/discounts/save', 'POST', '[]', 0, 0, 0, 1, '', '27/959', 2, '', 0, '', 0),
(965, 581, 1, '', '拆单发货', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/order/split_delivery', '4/5/581', 1, '', 0, '/admin/order/split_delivery', 0),
(966, 5, 1, '', '子订单列表', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/order/split_list', '4/5', 1, '', 0, '/admin/order/split_list', 0),
(967, 966, 1, '', '获取子订单列表', 'admin', '', '', 'order/split_order/<id>', 'GET', '[]', 0, 0, 0, 1, '', '4/5/966', 2, '', 0, 'order_split_order', 0),
(968, 209, 1, '', '获取订单可拆分商品列表', 'admin', '', '', 'order/split_cart_info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '4/5/581/209', 2, '', 0, 'order_split_cart_info', 0),
(969, 209, 1, '', '订单拆分发货', 'admin', '', '', 'order/split_delivery/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '4/5/581/209', 2, '', 0, 'order_split_delivery', 0),
(970, 959, 1, '', '获取套餐列表', 'admin', '', '', 'marketing/discounts/list', 'GET', '[]', 0, 0, 0, 1, '', '27/959', 2, '', 0, '', 0),
(971, 4, 1, 'md-contacts', '售后订单', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/order/refund', '4', 1, 'order', 1, 'admin-order-refund', 0),
(972, 26, 1, 'logo-vimeo', '分销等级', 'admin', '', '', '', '', '[]', 9, 1, 0, 1, '/admin/setting/membership_level/index', '26', 1, 'agent', 0, '/admin/setting/membership_level/index', 0),
(978, 569, 1, '', '附件分类删除', 'admin', '', '', 'file/category/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '25/566/569', 2, '', 0, '', 0),
(980, 12, 1, 'md-text', '消息设置', 'admin', '', '', '', '', '[]', 110, 1, 0, 1, '/admin/setting/notification', '12', 1, 'setting', 0, 'setting-notification', 0),
(981, 972, 1, '', '分销员等级列表', 'admin', '', '', 'agent/level', 'GET', '[]', 0, 0, 0, 1, '', '26/972', 2, '', 0, '', 0),
(982, 980, 1, '', '一键同步订阅消息', 'admin', '', '', 'app/routine/syncSubscribe', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'app-wechat-template-sync', 0),
(983, 972, 1, '', '添加分销员等级', 'admin', '', '', 'agent/level', 'POST', '[]', 0, 0, 0, 1, '', '26/972', 2, '', 0, '', 0),
(984, 972, 1, '', '添加分销员等级表单', 'admin', '', '', 'agent/level/create', 'GET', '[]', 0, 0, 0, 1, '', '26/972', 2, '', 0, '', 0),
(987, 971, 1, '', '售后订单列表', 'admin', '', '', 'refund/list', 'GET', '[]', 0, 0, 0, 1, '', '4/971', 2, '', 0, '', 0),
(988, 971, 1, '', '商家同意退款，等待用户退货', 'admin', '', '', 'refund/agree/<order_id>', 'GET', '[]', 0, 0, 0, 1, '', '4/971', 2, '', 0, '', 0),
(989, 972, 1, '', '修改分销等级状态', 'admin', '', '', 'agent/level/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '26/972', 2, '', 0, '', 0),
(990, 972, 1, '', '编辑分销员等级表单', 'admin', '', '', 'agent/level/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '26/972', 2, '', 0, '', 0),
(991, 972, 1, '', '编辑分销员等级', 'admin', '', '', 'agent/level/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '26/972', 2, '', 0, '', 0),
(992, 972, 1, '', '删除分销员等级', 'admin', '', '', 'agent/level/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '26/972', 2, '', 0, '', 0),
(993, 972, 1, '', '分销员等级任务列表', 'admin', '', '', 'agent/level_task', 'GET', '[]', 0, 0, 0, 1, '', '26/972', 2, '', 0, '', 0),
(994, 972, 1, '', '添加分销员等级任务表单', 'admin', '', '', 'agent/level_task/create', 'GET', '[]', 0, 0, 0, 1, '', '26/972', 2, '', 0, '', 0),
(995, 972, 1, '', '添加分销员等级任务', 'admin', '', '', 'agent/level_task', 'POST', '[]', 0, 1, 0, 1, '', '26/972', 2, '', 0, '', 0),
(996, 972, 1, '', '编辑分销员等级任务表单', 'admin', '', '', 'agent/level_task/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '26/972', 2, '', 0, '', 0),
(997, 972, 1, '', '编辑分销员等级任务', 'admin', '', '', 'agent/level_task/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '26/972', 2, '', 0, '', 0),
(998, 972, 1, '', '修改分销等级任务状态', 'admin', '', '', 'agent/level_task/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '26/972', 2, '', 0, '', 0),
(999, 972, 1, '', '删除分销员等级任务', 'admin', '', '', 'agent/level_task/<id>', 'DELETE', '[]', 0, 1, 0, 1, '', '26/972', 2, '', 0, '', 0),
(1000, 29, 1, '', '获取赠送分销等级表单', 'admin', '', '', 'agent/get_level_form', 'GET', '[]', 0, 0, 0, 1, '', '26/29', 2, '', 0, '', 0),
(1001, 29, 1, '', '赠送分销等级', 'admin', '', '', 'agent/give_level', 'POST', '[]', 0, 0, 0, 1, '', '26/29', 2, '', 0, '', 0),
(1002, 959, 1, '', '优惠套餐列表', 'admin', '', '', 'marketing/discounts/list', 'GET', '[]', 0, 0, 0, 1, '', '27/959', 2, '', 0, '', 0),
(1003, 959, 1, '', '优惠套餐详情', 'admin', '', '', 'marketing/discounts/info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '27/959', 2, '', 0, '', 0),
(1004, 980, 1, '', '系统通知列表', 'admin', '', '', 'setting/notification/index', 'GET', '[]', 0, 0, 0, 1, '', '12/980', 2, '', 0, '', 0),
(1005, 980, 1, '', '获取单条通知数据', 'admin', '', '', 'setting/notification/info', 'GET', '[]', 0, 0, 0, 1, '', '12/980', 2, '', 0, '', 0),
(1006, 980, 1, '', '保存通知设置', 'admin', '', '', 'setting/notification/save', 'POST', '[]', 0, 0, 0, 1, '', '12/980', 2, '', 0, '', 0),
(1008, 930, 1, '', '积分商品列表', 'admin', '', '', 'marketing/integral_product', 'GET', '[]', 0, 0, 0, 1, '', '27/34/930', 2, '', 0, '', 0),
(1009, 930, 1, '', '修改积分商品状态', 'admin', '', '', 'marketing/integral/set_show/<id>/<is_show>', 'PUT', '[]', 0, 0, 0, 1, '', '27/34/930', 2, '', 0, '', 0),
(1010, 932, 1, '', '积分商城订单数据', 'admin', '', '', 'marketing/integral/order/chart', 'GET', '[]', 0, 0, 0, 1, '', '27/34/932', 2, '', 0, '', 0),
(1011, 932, 1, '', '积分兑换订单导出', 'admin', '', '', 'export/storeIntegralOrder', 'GET', '[]', 0, 0, 0, 1, '', '27/34/932', 2, '', 0, '', 0),
(1012, 932, 1, '', '删除积分订单', 'admin', '', '', 'marketing/integral/order/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '27/34/932', 2, '', 0, '', 0),
(1013, 959, 1, '', '优惠套餐删除', 'admin', '', '', 'marketing/discounts/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '27/959', 2, '', 0, '', 0),
(1014, 657, 1, '', '设置Diy默认数据', 'admin', '', '', 'diy/set_default_data/<id>', 'GET', '[]', 0, 0, 0, 1, '', '12/656/657', 2, '', 0, '', 0),
(1015, 657, 1, '', '还原Diy默认数据', 'admin', '', '', 'diy/recovery/<id>', 'GET', '[]', 0, 0, 0, 1, '', '12/656/657', 2, '', 0, '', 0),
(1016, 657, 1, '', '获取门店自提开启状态', 'admin', '', '', 'diy/get_store_status', 'GET', '[]', 0, 0, 0, 1, '', '12/656/657', 2, '', 0, '', 0),
(1017, 657, 1, '', '获取所有二级分类', 'admin', '', '', 'diy/get_by_category', 'GET', '[]', 0, 0, 0, 1, '', '12/656/657', 2, '', 0, '', 0),
(1018, 657, 1, '', '获取推荐不同类型商品', 'admin', '', '', 'diy/groom_list/<type>', 'GET', '[]', 0, 0, 0, 1, '', '12/656/657', 2, '', 0, '', 0),
(1019, 687, 1, '', '同步主播', 'admin', '', '', 'live/anchor/syncAnchor', 'GET', '[]', 0, 0, 0, 1, '', '27/686/687', 2, '', 0, '', 0),
(1020, 145, 1, '', '同步物流公司', 'admin', '', '', 'freight/express/sync_express', 'GET', '[]', 0, 0, 0, 1, '', '25/145', 2, '', 0, '', 0),
(1021, 585, 1, '', '赠送付费会员时长', 'admin', '', '', 'user/give_level_time/<id>', 'GET', '[]', 0, 0, 0, 1, '', '9/10/585', 2, '', 0, '', 0),
(1022, 585, 1, '', '执行赠送付费会员时长', 'admin', '', '', 'user/save_give_level_time/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '9/10/585', 2, '', 0, '', 0),
(1023, 29, 1, '', '推广人头部统计', 'admin', '', '', 'agent/stair/statistics', 'GET', '[]', 0, 0, 0, 1, '', '26/29', 2, '', 0, '', 0),
(1024, 29, 1, '', '推广订单列表头部', 'admin', '', '', 'agent/stair/order/statistics', 'GET', '[]', 0, 0, 0, 1, '', '26/29', 2, '', 0, '', 0),
(1025, 584, 1, '', '取消推广资格', 'admin', '', '', 'agent/stair/delete_system_spread/<uid>', 'PUT', '[]', 0, 0, 0, 1, '', '26/29/584', 2, '', 0, '', 0),
(1026, 585, 1, '', '添加用户', 'admin', '', '', 'user/user/save', 'POST', '[]', 0, 0, 0, 1, '', '9/10/585', 2, '', 0, '', 0),
(1027, 585, 1, '', '获取用户标签', 'admin', '', '', 'user/label/<uid>', 'GET', '[]', 0, 0, 0, 1, '', '9/10/585', 2, '', 0, '', 0),
(1028, 585, 1, '', '设置和取消用户标签', 'admin', '', '', 'user/label/<uid>', 'POST', '[]', 0, 0, 0, 1, '', '9/10/585', 2, '', 0, '', 0),
(1029, 585, 1, '', '用户列表头部数据', 'admin', '', '', 'user/user/type_header', 'GET', '[]', 0, 0, 0, 1, '', '9/10/585', 2, '', 0, '', 0),
(1030, 585, 1, '', '修改用户状态', 'admin', '', '', 'user/set_status/<status>/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '9/10/585', 2, '', 0, '', 0),
(1031, 586, 1, '', '用户等级详情', 'admin', '', '', 'user/user_level/read/<id>', 'GET', '[]', 0, 0, 0, 1, '', '9/11/586', 2, '', 0, '', 0),
(1032, 1366, 1, '', '用户等级列表快速编辑', 'admin', '', '', 'user/user_level/set_value/<id>', 'PUT', '[]', 0, 1, 0, 1, '', '9/1093/11/1366', 2, '', 0, '', 0),
(1033, 903, 1, '', '会员卡修改状态', 'admin', '', '', 'user/member_card/set_status', 'GET', '[]', 0, 0, 0, 1, '', '9/731/903', 2, '', 0, '', 0),
(1034, 751, 1, '', '会员类型修改状态', 'admin', '', '', 'user/member_ship/set_ship_status', 'GET', '[]', 0, 0, 0, 1, '', '9/731/751', 2, '', 0, '', 0),
(1035, 656, 1, 'md-shirt', '主题风格', 'admin', '', '', '', '', '[]', 3, 1, 0, 1, '/admin/setting/theme_style', '12/656', 1, 'devise', 0, 'admin-setting-theme_style', 0),
(1036, 656, 1, 'ios-desktop', 'PC商城', 'admin', '', '', '', '', '[]', 1, 1, 0, 1, '/admin/setting/pc_group_data', '12/656', 1, 'devise', 1, 'setting-system-pc_data', 0),
(1037, 0, 1, '', '门店', 'admin', '', '', '', '', '[]', 127, 1, 0, 1, '/admin/store', '', 1, 'store', 1, 'admin-store', 0),
(1038, 1037, 1, 'md-menu', '门店菜单', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/store/store_menus/index', '1037', 1, '', 0, 'admin-store_menus-index', 0),
(1040, 1037, 1, 'md-analytics', '运营概况', 'admin', '', '', '', '', '[]', 9, 1, 0, 1, '/admin/store/statistics', '1037', 1, 'store', 0, 'admin-store-store_statistics', 0),
(1041, 1037, 1, 'ios-apps', '门店列表', 'admin', '', '', '', '', '[]', 8, 1, 0, 1, '/admin/store/store/index', '1037', 1, 'store', 0, 'admin-store-store_list', 0),
(1042, 1037, 1, 'md-cart', '门店订单', 'admin', '', '', '', '', '[]', 7, 1, 0, 1, '/admin/store/order/index', '1037', 1, 'store', 0, 'admin-store-store_order', 0),
(1043, 1037, 1, 'logo-usd', '门店财务', 'admin', '', '', '', '', '[]', 6, 1, 0, 1, '/admin/store/finance', '1037', 1, 'store', 1, 'admin-store-finance', 0),
(1044, 1043, 1, '', '门店流水', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/store/capital/index', '1037/1043', 1, '', 0, 'admin-store-capital-index', 0),
(1045, 1043, 1, '', '账单记录', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/store/bill/index', '1037/1043', 1, '', 0, 'admin-store-bill-index', 0),
(1046, 1043, 1, '', '转账申请', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/store/cash/index', '1037/1043', 1, '', 0, 'admin-store-cash-index', 0),
(1047, 1043, 1, '', '财务设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/store/finance/set/2/80', '1037/1043', 1, '', 0, 'admin-store-finance-set', 0),
(1048, 0, 2, 'ios-home-outline', '首页', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/home', '1070/1237/1240', 1, '', 0, 'store-operate', 0),
(1049, 1048, 2, '', '运营概况', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/home/index', '1048', 1, '', 0, 'store-statistics-index', 0),
(1050, 0, 2, 'md-basket', '商品', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/product', '', 1, '', 0, 'store-product', 0),
(1051, 1050, 2, '', '商品列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/product/index', '1050', 1, '', 0, 'store-product-index', 0),
(1052, 0, 2, 'md-cart', '订单', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/order', '', 1, '', 0, 'store-order', 0),
(1053, 1052, 2, '', '收银台', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/store/order/create', '1052', 1, '', 0, 'store-order-create', 0),
(1054, 1052, 2, '', '订单列表', 'admin', '', '', '', '', '[]', 2, 1, 0, 1, '/store/order/index', '1052', 1, '', 0, 'store-order-index', 0),
(1055, 0, 2, 'ios-people', '用户', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/user', '', 1, '', 0, 'store-user', 0),
(1056, 1055, 2, '', '用户列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/user/index', '1055', 1, '', 0, 'store-user-index', 0),
(1057, 1055, 2, '', '用户标签', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/user/label/index', '1055', 1, '', 0, 'store-user-label-index', 0),
(1058, 0, 2, 'ios-man', '员工', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/staff', '', 1, '', 0, 'store-staff', 0),
(1059, 1058, 2, '', '店员管理', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/staff/manage', '1058', 1, '', 0, 'store-staff-manage', 0),
(1060, 1059, 2, '', '店员列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/staff/index', '1058/1059', 1, '', 0, 'store-staff-index', 0),
(1061, 1059, 2, '', '业绩统计', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/staff/statistics', '1058/1059', 1, '', 0, 'store-staff-statistics', 0),
(1062, 1058, 2, '', '配送员管理', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/delivery', '1058', 1, '', 0, 'store-delivery', 0),
(1063, 1062, 2, '', '配送员列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/delivery/index', '1058/1062', 1, '', 0, 'store-delivery-index', 0),
(1064, 1062, 2, '', '账单统计', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/delivery/statistics', '1058/1062', 1, '', 0, 'store-delivery-statistics', 0),
(1065, 0, 2, 'logo-usd', '财务', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/finance', '', 1, '', 0, 'store-finance', 0),
(1066, 1065, 2, '', '门店流水', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/capital/index', '1065', 1, '', 0, 'store-capital-index', 0),
(1067, 1065, 2, '', '账单记录', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/bill/index', '1065', 1, '', 0, 'store-bill-index', 0),
(1068, 1065, 2, '', '转账申请', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/cash/index', '1065', 1, '', 0, 'store-cash-index', 0),
(1069, 1065, 2, '', '财务设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/finance/set', '1065', 1, '', 0, 'store-finance-set', 0),
(1070, 0, 2, 'ios-cog', '设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/set', '', 1, '', 0, 'store-set', 0),
(1071, 1070, 2, '', '门店设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/set/store', '1070', 1, '', 0, 'store-set-store', 0),
(1072, 1070, 2, '', '管理权限', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/auth', '1070', 1, '', 0, 'store-auth', 0),
(1073, 1072, 2, '', '管理员列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/admin/index', '1070/1072', 1, '', 0, 'store-admin-index', 0),
(1075, 1035, 1, '', '主题风格', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/theme_style', '656/1035', 1, '', 0, 'admin-setting-theme_style', 0),
(1081, 1050, 2, '', '商品评价', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/store/product/product_reply', '1050', 1, '', 0, 'store-product-product_reply', 0),
(1082, 1073, 2, '', '管理员列表', 'admin', '', '', 'system/admin', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, 'admin-marketing-live-anchor', 0),
(1083, 1073, 2, '', '添加/修改管理员提交', 'admin', '', '', 'system/admin', 'POST', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(1084, 1073, 2, '', '删除管理员', 'admin', '', '', 'system/admin/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(1085, 1073, 2, '', '设置管理员是否显示', 'admin', '', '', 'sysem/admin/set_show/<id>/<is_show>', 'GET', '[]', 0, 0, 0, 1, '', '', 2, '', 0, '', 0),
(1086, 1054, 2, '', '子订单列表', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/store/order/split_list', '1052/1054', 1, '', 0, 'order_split_order', 0),
(1088, 1052, 2, '', '售后订单', 'admin', '', '', '', '', '[]', 1, 1, 0, 1, '/store/order/refund', '1052', 1, '', 0, 'store-order-refund', 0),
(1089, 1070, 2, '', '系统设置', 'admin', '', '', '', '', '[]', 1, 1, 0, 1, '/store/set/index', '1070', 1, '', 0, 'store-set-index', 0),
(1090, 69, 1, '', '微信会员卡券', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/app/wechat/card', '135/69', 1, '', 0, 'wechat-wechat-card', 0),
(1091, 7, 1, 'md-home', '首页', 'admin', '', '', '', '', '[]', 1, 1, 0, 1, '/admin/home/', '7', 1, 'home', 1, 'admin-index-index', 0),
(1092, 9, 1, 'md-person', '用户管理', 'admin', '', '', '', '', '[]', 3, 1, 0, 1, '/admin/user/manage', '9', 1, 'user', 0, 'admin-user', 0),
(1093, 9, 1, 'md-school', '会员管理', 'admin', '', '', '', '', '[]', 2, 1, 0, 1, '/admin/vipuser', '9', 1, 'user', 1, 'admin-user', 0),
(1097, 1049, 2, '', '首页头部统计数据', 'admin', '', '', 'home/header', 'GET', '[]', 0, 0, 0, 1, '', '1048/1049', 2, '', 0, '', 0),
(1098, 1049, 2, '', '首页营业趋势图表', 'admin', '', '', 'home/operate', 'GET', '[]', 0, 0, 0, 1, '', '1048/1049', 2, '', 0, '', 0),
(1099, 1049, 2, '', '首页交易图表', 'admin', '', '', 'home/orderChart', 'GET', '[]', 0, 0, 0, 1, '', '1048/1049', 2, '', 0, '', 0),
(1100, 1049, 2, '', '首页店员统计', 'admin', '', '', 'home/staff', 'GET', '[]', 0, 0, 0, 1, '', '1048/1049', 2, '', 0, '', 0),
(1101, 1051, 2, '', '商品分类cascader行列表', 'admin', '', '', 'product/category', 'GET', '[]', 0, 1, 0, 1, '', '1050/1051', 2, '', 0, '', 0),
(1102, 1051, 2, '', '商品列表头部数据', 'admin', '', '', 'product/type_header', 'GET', '[]', 0, 0, 0, 1, '', '1050/1051', 2, '', 0, '', 0),
(1103, 1051, 2, '', '获取关联用户标签', 'admin', '', '', 'product/getUserLabel', 'GET', '[]', 0, 0, 0, 1, '', '1050/1051', 2, '', 0, '', 0),
(1104, 1081, 2, '', '商品评论列表', 'admin', '', '', 'product/reply', 'GET', '[]', 0, 0, 0, 1, '', '1050/1081', 2, '', 0, '', 0),
(1105, 1081, 2, '', '商品回复评论', 'admin', '', '', 'product/reply/set_reply/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1050/1081', 2, '', 0, '', 0),
(1106, 1081, 2, '', '删除商品评论', 'admin', '', '', 'product/reply/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '1050/1081', 2, '', 0, '', 0),
(1108, 1053, 2, '', '获取收银台一级分类列表', 'admin', '', '', 'order/cashier/cate', 'GET', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1109, 1053, 2, '', '获取收银台商品信息', 'admin', '', '', 'order/cashier/product', 'GET', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1110, 1053, 2, '', '获取收银台购物车信息', 'admin', '', '', 'order/cashier/cart/<uid>/<staff_id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1111, 1053, 2, '', '收银台添加购物车', 'admin', '', '', 'order/cashier/cart/<uid>', 'POST', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1112, 1053, 2, '', '收银台删除购物车信息', 'admin', '', '', 'order/cashier/cart/<uid>', 'DELETE', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1113, 1053, 2, '', '收银台更改购物车数量', 'admin', '', '', 'order/cashier/cart/<uid>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1114, 1053, 2, '', '收银台更改购物车规格', 'admin', '', '', 'order/cashier/changeCart', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1115, 1053, 2, '', '收银台计算订单金额', 'admin', '', '', 'order/cashier/compute/<uid>', 'POST', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1116, 1053, 2, '', '收银台创建订单', 'admin', '', '', 'order/cashier/create/<uid>', 'POST', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1117, 1053, 2, '', '获取当前门店店员列表和店员信息', 'admin', '', '', 'order/cashier/staff', 'GET', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1118, 1053, 2, '', '扫码自动解析', 'admin', '', '', 'order/cashier/code', 'POST', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1119, 1053, 2, '', '收银台获取商品详情', 'admin', '', '', 'order/cashier/detail/<id>/<uid?>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1120, 1053, 2, '', '收银台订单支付', 'admin', '', '', 'order/cashier/pay/<orderId>', 'POST', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1122, 1088, 2, '', '订单退款表单', 'admin', '', '', 'order/refund/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1088', 2, '', 0, '', 0),
(1123, 1088, 2, '', '修改不退款理由', 'admin', '', '', 'order/no_refund/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1088', 2, '', 0, '', 0),
(1124, 1088, 2, '', '获取退积分表单', 'admin', '', '', 'order/refund_integral/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1088', 2, '', 0, '', 0),
(1125, 1088, 2, '', '修改退积分', 'admin', '', '', 'order/refund_integral/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1088', 2, '', 0, '', 0),
(1126, 1088, 2, '', '获取不退款表单', 'admin', '', '', 'order/no_refund/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1088', 2, '', 0, '', 0),
(1127, 1088, 2, '', '订单退款', 'admin', '', '', 'order/refund/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1088', 2, '', 0, '', 0),
(1129, 1054, 2, '', '普通订单', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/store/order/product', '1052/1054', 1, '', 0, '', 0),
(1130, 1054, 2, '', '会员订单', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/store/order/vip', '1052/1054', 1, '', 0, '', 0),
(1131, 1054, 2, '', '充值订单', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/store/order/recharge', '1052/1054', 1, '', 0, '', 0),
(1132, 1129, 2, '', '打印订单', 'admin', '', '', 'order/print/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1133, 1129, 2, '', '获取门店订单头部统计', 'admin', '', '', 'order/header', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1134, 1129, 2, '', '订单列表', 'admin', '', '', 'order/list', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1135, 1129, 2, '', '订单头部数据', 'admin', '', '', 'order/chart', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1136, 1129, 2, '', '确认收货', 'admin', '', '', 'order/take/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1137, 1129, 2, '', '订单核销', 'admin', '', '', 'order/write', 'POST', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1138, 1129, 2, '', '订单号核销', 'admin', '', '', 'order/write_update/<order_id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1139, 1129, 2, '', '获取订单编辑表单', 'admin', '', '', 'order/edit/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1140, 1129, 2, '', '订单发送货', 'admin', '', '', 'order/delivery/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1141, 1129, 2, '', '修改订单', 'admin', '', '', 'order/update/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1142, 1129, 2, '', '获取订单可拆分商品列表', 'admin', '', '', 'order/split_cart_info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1143, 1129, 2, '', '拆单发送货', 'admin', '', '', 'order/split_delivery/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1144, 1129, 2, '', '快递公司电子面单模版', 'admin', '', '', 'order/express/temp', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1145, 1129, 2, '', '获取订单拆分子订单列表', 'admin', '', '', 'order/split_order/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1146, 1129, 2, '', '获取物流信息', 'admin', '', '', 'order/express/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1147, 1129, 2, '', '订单详情', 'admin', '', '', 'order/info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1148, 1129, 2, '', '获取物流公司', 'admin', '', '', 'order/express_list', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1149, 1129, 2, '', '获取配送信息表单', 'admin', '', '', 'order/distribution/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1150, 1129, 2, '', '获取订单状态', 'admin', '', '', 'order/status/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1151, 1129, 2, '', '修改配送信息', 'admin', '', '', 'order/distribution/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1152, 1129, 2, '', '批量删除订单', 'admin', '', '', 'order/dels', 'POST', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1153, 1129, 2, '', '线下支付', 'admin', '', '', 'order/pay_offline/<id>', 'POST', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1154, 1129, 2, '', '修改备注信息', 'admin', '', '', 'order/remark/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1155, 1129, 2, '', '删除订单单个', 'admin', '', '', 'order/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1156, 1129, 2, '', '面单默认配置信息', 'admin', '', '', 'order/sheet_info', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1157, 1129, 2, '', '电子面单模板列表', 'admin', '', '', 'order/expr/temp', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1158, 1129, 2, '', '更多操作打印电子面单', 'admin', '', '', 'order/order_dump/<order_id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1159, 1129, 2, '', '批量发货', 'admin', '', '', 'order/hand/batch_delivery', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1160, 1129, 2, '', '自动批量发货', 'admin', '', '', 'order/other/batch_delivery', 'POST', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1161, 1129, 2, '', '订单批量删除', 'admin', '', '', 'order/batch/del_orders', 'POST', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1162, 1129, 2, '', '订单导出', 'admin', '', '', 'order/export/<type>', 'POST', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, 'order-export', 0),
(1163, 1129, 2, '', '订单列表获取配送员', 'admin', '', '', 'order/delivery/list', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1129', 2, '', 0, '', 0),
(1164, 1088, 2, '', '商家同意退款，等待用户退货', 'admin', '', '', 'refund/agree/<order_id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1088', 2, '', 0, '', 0),
(1165, 1088, 2, '', '售后订单列表', 'admin', '', '', 'refund/list', 'GET', '[]', 0, 1, 0, 1, '', '1052/1088', 2, '', 0, '', 0),
(1166, 1130, 2, '', '付费会员订单列表', 'admin', '', '', 'order/vip_order', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1130', 2, '', 0, '', 0),
(1167, 1130, 2, '', '获取会员备注', 'admin', '', '', 'order/vip/remark/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1130', 2, '', 0, '', 0),
(1168, 1130, 2, '', '保存会员备注', 'admin', '', '', 'order/vip/remark/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1054/1130', 2, '', 0, '', 0),
(1169, 1130, 2, '', '获取会员状态', 'admin', '', '', 'order/vip/status/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1130', 2, '', 0, '', 0),
(1170, 1131, 2, '', '充值订单列表', 'admin', '', '', 'order/recharge', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1131', 2, '', 0, '', 0),
(1171, 1131, 2, '', '获取用户充值数据', 'admin', '', '', 'order/recharge/user_recharge', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1131', 2, '', 0, '', 0),
(1172, 1131, 2, '', '充值退款表单', 'admin', '', '', 'order/recharge/<id>/refund_edit', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1131', 2, '', 0, '', 0),
(1173, 1131, 2, '', '删除充值记录', 'admin', '', '', 'order/recharge/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '1052/1054/1131', 2, '', 0, '', 0),
(1174, 1131, 2, '', '获取充值订单备注', 'admin', '', '', 'order/recharge/remark/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054/1131', 2, '', 0, '', 0),
(1175, 1131, 2, '', '充值退款', 'admin', '', '', 'order/recharge/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1054/1131', 2, '', 0, '', 0),
(1176, 1131, 2, '', '保存充值订单备注', 'admin', '', '', 'order/recharge/remark/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1052/1054/1131', 2, '', 0, '', 0),
(1177, 1056, 2, '', '门店搜索用户', 'admin', '', '', 'user/search', 'GET', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1178, 1056, 2, '', '设置用户标签', 'admin', '', '', 'user/set_label', 'POST', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1179, 1056, 2, '', '添加或修改用户标签', 'admin', '', '', 'user/user_label/save', 'POST', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1180, 1056, 2, '', '获取用户标签', 'admin', '', '', 'user/label/<uid>', 'GET', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1181, 1056, 2, '', '设置和取消用户标签', 'admin', '', '', 'user/label/<uid>', 'POST', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1182, 1056, 2, '', '保存用户标签', 'admin', '', '', 'user/save_set_label', 'PUT', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1183, 1056, 2, '', '获取指定用户的信息', 'admin', '', '', 'user/one_info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1184, 1056, 2, '', '给用户购买付费会员', 'admin', '', '', 'user/member', 'POST', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1185, 1056, 2, '', '获取svip列表', 'admin', '', '', 'user/member/ship', 'GET', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1186, 1056, 2, '', '获取充值套餐', 'admin', '', '', 'user/recharge/meal', 'GET', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1187, 1056, 2, '', '获取充值套餐', 'admin', '', '', 'user/recharge', 'POST', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1188, 1056, 2, '', '删除用户', 'admin', '', '', 'user/user/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1189, 1056, 2, '', '获取修改用户表单', 'admin', '', '', 'user/user/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1190, 1056, 2, '', '获取门店用户列表', 'admin', '', '', 'user/user', 'GET', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1191, 1056, 2, '', '获取门店用户详情', 'admin', '', '', 'user/user/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1192, 1056, 2, '', '修改用户', 'admin', '', '', 'user/user/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1193, 1057, 2, '', '获取标签分类列表', 'admin', '', '', 'user/user_label_cate', 'GET', '[]', 0, 0, 0, 1, '', '1055/1057', 2, '', 0, '', 0),
(1194, 1057, 2, '', '获取标签列表', 'admin', '', '', 'user/user_label', 'GET', '[]', 0, 0, 0, 1, '', '1055/1057', 2, '', 0, '', 0),
(1195, 1057, 2, '', '保存标签分类', 'admin', '', '', 'user/user_label_cate', 'POST', '[]', 0, 0, 0, 1, '', '1055/1057', 2, '', 0, '', 0),
(1196, 1057, 2, '', '获取创建标签分类表单', 'admin', '', '', 'user/user_label_cate/create', 'GET', '[]', 0, 0, 0, 1, '', '1055/1057', 2, '', 0, 'user-user_label_cate-create', 0),
(1197, 1057, 2, '', '获取创建标签表单', 'admin', '', '', 'user/user_label/create', 'GET', '[]', 0, 0, 0, 1, '', '1055/1057', 2, '', 0, 'user-user_label-create', 0),
(1198, 1060, 2, '', '获取店员详情', 'admin', '', '', 'staff/staff_info', 'GET', '[]', 0, 0, 0, 1, '', '1058/1059/1060', 2, '', 0, '', 0),
(1199, 1060, 2, '', '获取店员统计详情', 'admin', '', '', 'staff/info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1058/1059/1060', 2, '', 0, '', 0),
(1200, 1060, 2, '', '修改店员状态', 'admin', '', '', 'staff/staff/set_show/<id>/<is_show>', 'PUT', '[]', 0, 0, 0, 1, '', '1058/1059/1060', 2, '', 0, '', 0),
(1201, 1061, 2, '', '获取店员交易统计', 'admin', '', '', 'staff/statistics', 'GET', '[]', 0, 0, 0, 1, '', '1058/1059/1061', 2, '', 0, '', 0),
(1202, 1062, 2, '', '获取店员交易头部数据', 'admin', '', '', 'staff/statisticsHeader', 'GET', '[]', 0, 0, 0, 1, '', '1058/1062', 2, '', 0, '', 0),
(1203, 1066, 2, '', '门店流水列表', 'admin', '', '', 'finance/store_finance_flow/list', 'GET', '[]', 0, 0, 0, 1, '', '1065/1066', 2, '', 0, '', 0),
(1204, 1067, 2, '', '门店转账列表', 'admin', '', '', 'finance/storeExtract/list', 'GET', '[]', 0, 0, 0, 1, '', '1065/1067', 2, '', 0, '', 0),
(1205, 1068, 2, '', '门店申请转账', 'admin', '', '', 'finance/storeExtract/cash', 'POST', '[]', 0, 0, 0, 1, '', '1065/1068', 2, '', 0, 'finance-storeExtract-cash', 0),
(1206, 1068, 2, '', '门店转账记录备注', 'admin', '', '', 'finance/storeExtract/mark/<id>', 'POST', '[]', 0, 0, 0, 1, '', '1065/1068', 2, '', 0, '', 0),
(1207, 1069, 2, '', '获取关联用户标签', 'admin', '', '', 'finance/info', 'GET', '[]', 0, 0, 0, 1, '', '1065/1069', 2, '', 0, '', 0),
(1208, 1069, 2, '', '设置门店财务信息', 'admin', '', '', 'finance/info', 'POST', '[]', 0, 1, 0, 1, '', '1065/1069', 2, '', 0, '', 0),
(1209, 1071, 2, '', '保存门店配置', 'admin', '', '', 'system/config/<type>', 'POST', '[]', 0, 0, 0, 1, '', '1070/1071', 2, '', 0, '', 0),
(1210, 1071, 2, '', '获取门店配置', 'admin', '', '', 'system/config/<type>', 'GET', '[]', 0, 0, 0, 1, '', '1070/1071', 2, '', 0, '', 0),
(1211, 1071, 2, '', '门店配置表单', 'admin', '', '', 'system/config/edit_basics', 'GET', '[]', 0, 1, 0, 1, '', '1070/1071', 2, '', 0, '', 0),
(1212, 1067, 2, '', '门店账单详情', 'admin', '', '', 'finance/store_finance_flow/fund_record_info', 'GET', '[]', 0, 0, 0, 1, '', '1065/1067', 2, '', 0, '', 0),
(1213, 1067, 2, '', '门店账单记录', 'admin', '', '', 'finance/store_finance_flow/fund_record', 'GET', '[]', 0, 0, 0, 1, '', '1065/1067', 2, '', 0, '', 0),
(1214, 1066, 2, '', '门店流水备注', 'admin', '', '', 'finance/store_finance_flow/mark/<id>', 'POST', '[]', 0, 0, 0, 1, '', '1065/1066', 2, '', 0, '', 0),
(1215, 1063, 2, '', '获取配送员统计详情', 'admin', '', '', 'staff/delivery/info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1058/1062/1063', 2, '', 0, '', 0),
(1216, 1064, 2, '', '配送员账单统计', 'admin', '', '', 'staff/delivery/statistics', 'GET', '[]', 0, 0, 0, 1, '', '1058/1062/1064', 2, '', 0, '', 0),
(1217, 1064, 2, '', '配送员账单头部', 'admin', '', '', 'staff/delivery/statisticsHeader', 'GET', '[]', 0, 0, 0, 1, '', '1058/1062/1064', 2, '', 0, '', 0),
(1218, 1063, 2, '', '修改配送员状态', 'admin', '', '', 'staff/delivery/set_show/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '1058/1062/1063', 2, '', 0, '', 0),
(1219, 1063, 2, '', '保存配送员', 'admin', '', '', 'staff/delivery', 'POST', '[]', 0, 0, 0, 1, '', '1058/1062/1063', 2, '', 0, '', 0),
(1220, 1063, 2, '', '获取配送员', 'admin', '', '', 'staff/delivery/get_delivery_select', 'GET', '[]', 0, 0, 0, 1, '', '1058/1062/1063', 2, '', 0, '', 0),
(1221, 1070, 2, '', '附件管理', 'admin', '', '', '', '', '[]', 0, 0, 1, 1, '/store/file', '1070', 1, '', 0, '', 0),
(1222, 1221, 2, '', '上传图片', 'admin', '', '', 'file/upload/<upload_type?>', 'POST', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1223, 1221, 2, '', '修改图片名称', 'admin', '', '', 'file/file/update/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1224, 1221, 2, '', '移动图片分类表单', 'admin', '', '', 'file/file/move', 'GET', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1225, 1221, 2, '', '移动图片分类', 'admin', '', '', 'file/file/do_move', 'PUT', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1226, 1221, 2, '', '图片附件列表', 'admin', '', '', 'file/file', 'GET', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1227, 1221, 2, '', '删除图片', 'admin', '', '', 'file/file/delete', 'POST', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1228, 1073, 2, '', '管理员身份权限列表', 'admin', '', '', 'system/role/create', 'GET', '[]', 0, 0, 0, 1, '', '1070/1072/1073', 2, '', 0, 'system-role-create', 0),
(1229, 1073, 2, '', '修改管理员身份状态', 'admin', '', '', 'system/role/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '1070/1072/1073', 2, '', 0, '', 0),
(1230, 1073, 2, '', '编辑管理员详情', 'admin', '', '', 'system/role/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '1070/1072/1073', 2, '', 0, '', 0),
(1231, 1073, 2, '', '新建或编辑管理员', 'admin', '', '', 'system/role/<id>', 'POST', '[]', 0, 0, 0, 1, '', '1070/1072/1073', 2, '', 0, '', 0),
(1232, 1073, 2, '', '管理员身份列表', 'admin', '', '', 'system/role', 'GET', '[]', 0, 0, 0, 1, '', '1070/1072/1073', 2, '', 0, '', 0),
(1233, 1073, 2, '', '删除管理员身份', 'admin', '', '', 'system/role/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '1070/1072/1073', 2, '', 0, '', 0),
(1234, 1073, 2, '', '修改管理员状态', 'admin', '', '', 'system/admin/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '1070/1072/1073', 2, '', 0, '', 0),
(1235, 1067, 2, '', '门店账单导出', 'admin', '', '', 'export/financeRecord', 'GET', '[]', 0, 0, 0, 1, '', '1065/1067', 2, '', 0, '', 0),
(1236, 1073, 2, '', '获取菜单子权限列表', 'admin', '', '', 'system/sonMenusList/<role_id>/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1070/1072/1073', 2, '', 0, '', 0),
(1237, 1070, 2, '', '个人中心', 'admin', '', '', '', '', '[]', 0, 0, 1, 1, '/store/system/user', '1070', 1, '', 0, '', 0),
(1238, 1237, 2, '', '修改当前登录门店信息', 'admin', '', '', 'system/store/update', 'PUT', '[]', 0, 0, 0, 1, '', '1070/1237', 2, '', 0, '', 0),
(1239, 1237, 2, '', '获取角色菜单列表', 'admin', '', '', 'system/menusList', 'GET', '[]', 0, 0, 0, 1, '', '1070/1237', 2, '', 0, '', 0),
(1240, 1237, 2, '', '获取当前登录门店信息', 'admin', '', '', 'system/store/info', 'GET', '[]', 0, 0, 0, 1, '', '1070/1237', 2, '', 0, '', 0),
(1241, 1061, 2, '', '获取门店所有店员', 'admin', '', '', 'staff/staff/all', 'GET', '[]', 0, 0, 0, 1, '', '1058/1059/1061', 2, '', 0, '', 0),
(1242, 1054, 2, '', '门店收银台二维码', 'admin', '', '', 'order/cashier/cashier_scan', 'GET', '[]', 0, 0, 0, 1, '', '1052/1054', 2, '', 0, 'order-cashier-cashier_scan', 0),
(1243, 1060, 2, '', '获取添加店员表单', 'admin', '', '', 'staff/staff/create', 'GET', '[]', 0, 0, 0, 1, '', '1058/1059/1060', 2, '', 0, 'staff-staff-create', 0),
(1244, 1063, 2, '', '获取添加配送员表单', 'admin', '', '', 'staff/delivery/create', 'GET', '[]', 0, 0, 0, 1, '', '1058/1062/1063', 2, '', 0, 'staff-delivery-create', 0),
(1245, 1073, 2, '', '获取管理员详情', 'admin', '', '', 'system/admin/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1070/1072/1073', 2, '', 0, '', 0),
(1246, 1073, 2, '', '获取创建管理员表单', 'admin', '', '', 'system/admin/create', 'GET', '[]', 0, 0, 0, 1, '', '1070/1072/1073', 2, '', 0, 'system-admin-create', 0),
(1247, 1073, 2, '', '修改管理员', 'admin', '', '', 'system/admin/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1070/1072/1073', 2, '', 0, '', 0),
(1248, 1073, 2, '', '获取修改管理员表单', 'admin', '', '', 'system/admin/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '1070/1072/1073', 2, '', 0, '', 0),
(1249, 1057, 2, '', '获取标签分类详情', 'admin', '', '', 'user/user_label_cate/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1055/1057', 2, '', 0, '', 0),
(1250, 1057, 2, '', '修改标签分类', 'admin', '', '', 'user/user_label_cate/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1055/1057', 2, '', 0, '', 0),
(1251, 1057, 2, '', '删除标签分类', 'admin', '', '', 'user/user_label_cate/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '1055/1057', 2, '', 0, '', 0),
(1252, 1057, 2, '', '获取修改标签表单', 'admin', '', '', 'user/user_label/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '1055/1057', 2, '', 0, '', 0),
(1253, 1057, 2, '', '获取修改标签分类表单', 'admin', '', '', 'user/user_label_cate/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '1055/1057', 2, '', 0, '', 0),
(1254, 1057, 2, '', '删除标签', 'admin', '', '', 'user/user_label/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '1055/1057', 2, '', 0, '', 0),
(1255, 1060, 2, '', '获取门店店员列表', 'admin', '', '', 'staff/staff', 'GET', '[]', 0, 0, 0, 1, '', '1058/1059/1060', 2, '', 0, '', 0),
(1256, 1060, 2, '', '获取修改门店店员表单', 'admin', '', '', 'staff/staff/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '1058/1059/1060', 2, '', 0, '', 0),
(1257, 1060, 2, '', '获取门店店员详情', 'admin', '', '', 'staff/staff/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1058/1059/1060', 2, '', 0, '', 0),
(1258, 1060, 2, '', '删除门店店员', 'admin', '', '', 'staff/staff/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '1058/1059/1060', 2, '', 0, '', 0),
(1259, 1060, 2, '', '修改门店店员', 'admin', '', '', 'staff/staff/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1058/1059/1060', 2, '', 0, '', 0),
(1260, 1060, 2, '', '保存店员', 'admin', '', '', 'staff/staff', 'POST', '[]', 0, 0, 0, 1, '', '1058/1059/1060', 2, '', 0, '', 0),
(1261, 1063, 2, '', '获取修改配送员表单', 'admin', '', '', 'staff/delivery/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '1058/1062/1063', 2, '', 0, '', 0),
(1262, 1063, 2, '', '获取配送员详情', 'admin', '', '', 'staff/delivery/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1058/1062/1063', 2, '', 0, '', 0),
(1263, 1063, 2, '', '获取配送员列表', 'admin', '', '', 'staff/delivery', 'GET', '[]', 0, 0, 0, 1, '', '1058/1062/1063', 2, '', 0, '', 0),
(1264, 1063, 2, '', '修改配送员', 'admin', '', '', 'staff/delivery/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1058/1062/1063', 2, '', 0, '', 0),
(1265, 1063, 2, '', '删除配送员', 'admin', '', '', 'staff/delivery/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '1058/1062/1063', 2, '', 0, '', 0),
(1266, 1221, 2, '', '删除附件分类', 'admin', '', '', 'file/category/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1267, 1221, 2, '', '获取附件分类列表', 'admin', '', '', 'file/category', 'GET', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1268, 1221, 2, '', '获取附件分类详情', 'admin', '', '', 'file/category/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1269, 1221, 2, '', '获取创建附件分类表单', 'admin', '', '', 'file/category/create', 'GET', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1270, 1221, 2, '', '获取修改附件分类表单', 'admin', '', '', 'file/category/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1271, 1221, 2, '', '修改附件分类', 'admin', '', '', 'file/category/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1272, 1221, 2, '', '保存附件分类', 'admin', '', '', 'file/category', 'POST', '[]', 0, 0, 0, 1, '', '1070/1221', 2, '', 0, '', 0),
(1273, 1051, 2, '', '获取修改商品表单', 'admin', '', '', 'product/product/<id>/edit', 'GET', '[]', 0, 0, 0, 1, '', '1050/1051', 2, '', 0, '', 0),
(1274, 1051, 2, '', '获取商品详情', 'admin', '', '', 'product/product/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1050/1051', 2, '', 0, '', 0),
(1275, 1051, 2, '', '修改商品', 'admin', '', '', 'product/product/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1050/1051', 2, '', 0, '', 0),
(1276, 1051, 2, '', '获取商品列表', 'admin', '', '', 'product/product', 'GET', '[]', 0, 0, 0, 1, '', '1050/1051', 2, '', 0, '', 0),
(1277, 1056, 2, '', '轮询订单状态接口', 'admin', '', '', 'check_order_status/<type>', 'POST', '[]', 0, 1, 0, 1, '', '1055/1056', 2, '', 0, '', 0),
(1278, 2, 1, '', '批量设置商品配送方式', 'admin', '', '', '/product/product/setDeliveryType', 'PUT', '[]', 0, 0, 0, 1, '', '1/2', 2, '', 0, 'product-product-set_delivery_type', 0),
(1279, 980, 1, '', '一键同步公众号模板消息', 'admin', '', '', '/app/wechat/syncSubscribe', 'GET', '[]', 0, 0, 0, 1, '', '12/980', 2, '', 0, 'app-wechat-wechat-sync', 0),
(1280, 1035, 1, '', '获取设置的主题', 'admin', '', '', 'diy/get_color_change/<type>', 'GET', '[]', 0, 0, 0, 1, '', '12/656/1035', 2, '', 0, '', 0),
(1281, 1035, 1, '', '设置主题风格', 'admin', '', '', 'diy/color_change/:status/<type>', 'PUT', '[]', 0, 0, 0, 1, 'diy/color_change/:status/:type', '12/656/1035', 2, '', 0, '', 0),
(1282, 128, 1, '', '获取页面链接分类', 'admin', '', '', 'diy/get_page_category', 'GET', '[]', 0, 0, 0, 1, '', '656/128', 2, '', 0, '', 0),
(1283, 128, 1, '', '获取页面链接', 'admin', '', '', 'diy/get_page_link/<cate_id>', 'GET', '[]', 0, 0, 0, 1, '', '656/128', 2, '', 0, '', 0),
(1284, 657, 1, '', '获取个人中心菜单', 'admin', '', '', 'diy/get_member', 'GET', '[]', 0, 0, 0, 1, '', '12/656/657', 2, '', 0, '', 0),
(1285, 657, 1, '', '保存个人中心菜单', 'admin', '', '', 'diy/member_save', 'POST', '[]', 0, 0, 0, 1, '', '12/656/657', 2, '', 0, '', 0),
(1286, 657, 1, '', '获取小程序预览码', 'admin', '', '', 'diy/get_routine_code/<id>', 'GET', '[]', 0, 0, 0, 1, '', '12/656/657', 2, '', 0, '', 0),
(1287, 25, 1, '', '对外接口', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/out', '25', 1, '', 0, 'admin-out', 0),
(1288, 1287, 1, '', '账号列表', 'admin', '', '', 'setting/system_out/index', 'GET', '[]', 0, 0, 0, 1, '', '25/1044', 2, '', 0, '', 0),
(1289, 1287, 1, '', '账号详情', 'admin', '', '', 'setting/system_out/info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '25/1044', 2, '', 0, '', 0),
(1290, 1287, 1, '', '添加账号', 'admin', '', '', 'setting/system_out/save', 'POST', '[]', 0, 0, 0, 1, '', '25/1044', 2, '', 0, '', 0),
(1291, 1287, 1, '', '修改账号', 'admin', '', '', 'setting/system_out/update/<id>', 'POST', '[]', 0, 0, 0, 1, '', '25/1044', 2, '', 0, '', 0),
(1292, 1287, 1, '', '修改状态', 'admin', '', '', 'setting/system_out/set_status/<id>/<status>', 'PUT', '[]', 0, 0, 0, 1, '', '25/1044', 2, '', 0, '', 0),
(1293, 1287, 1, '', '删除账号', 'admin', '', '', 'setting/system_out/delete/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '25/1044', 2, '', 0, '', 0),
(1294, 1036, 1, '', '获取PC商城LOGO', 'admin', '', '', 'setting/config/get_system/<name>', 'GET', '[]', 0, 1, 0, 1, '', '656/1036', 2, '', 0, '', 0),
(1295, 980, 1, '', '消息设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/setting/notification/index', '12/980', 1, '', 0, 'setting-notification', 0),
(1296, 1053, 2, '', '获取优惠券', 'admin', '', '', 'order/cashier/coupon_list/<id>', '', '[]', 0, 0, 0, 1, '', '1052/1053', 2, '', 0, '', 0),
(1297, 1041, 1, '', '门店上下架', 'admin', '', '', 'store/store/set_show/<id>/<is_show>', 'PUT', '[]', 0, 0, 0, 1, '', '1037/1041', 2, '', 0, '', 0),
(1298, 1041, 1, '', '门店删除', 'admin', '', '', 'store/store/del/<id>', 'DELETE', '[]', 0, 0, 0, 1, '', '1037/1041', 2, '', 0, '', 0),
(1299, 1041, 1, '', '门店位置选择', 'admin', '', '', 'store/store/address', 'GET', '[]', 0, 0, 0, 1, '', '1037/1041', 2, '', 0, '', 0),
(1300, 1041, 1, '', '门店详情', 'admin', '', '', 'store/store/get_info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1037/1041', 2, '', 0, '', 0),
(1301, 1041, 1, '', '保存修改门店信息', 'admin', '', '', 'store/store/<id>', 'POST', '[]', 0, 0, 0, 1, '', '1037/1041', 2, '', 0, '', 0),
(1302, 1041, 1, '', '门店快捷登录', 'admin', '', '', 'store/store/login/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1037/1041', 2, '', 0, '', 0),
(1303, 1041, 1, '', '获取门店重置账号密码表单', 'admin', '', '', 'store/store/reset_admin/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1037/1041', 2, '', 0, '', 0),
(1304, 1041, 1, '', '门店重置账号密码', 'admin', '', '', 'store/store/reset_admin/<id>', 'POST', '[]', 0, 0, 0, 1, '', '1037/1041', 2, '', 0, '', 0),
(1305, 1042, 1, '', '获取门店订单头部统计', 'admin', '', '', 'store/order/header', 'GET', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1306, 1042, 1, '', '订单列表', 'admin', '', '', 'store/order/list', 'GET', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1307, 1041, 1, '', '门店列表', 'admin', '', '', 'store/store', 'GET', '[]', 0, 0, 0, 1, '', '1037/1041', 2, '', 0, '', 0),
(1308, 1042, 1, '', '订单头部数据', 'admin', '', '', 'store/order/chart', 'GET', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1309, 1042, 1, '', '订单详情', 'admin', '', '', 'store/order/info/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1310, 1042, 1, '', '修改备注信息', 'admin', '', '', 'store/order/remark/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1311, 1042, 1, '', '获取订单状态', 'admin', '', '', 'store/order/status/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1312, 1042, 1, '', '订单分配', 'admin', '', '', 'store/share/order', 'POST', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1313, 1042, 1, '', '充值订单列表', 'admin', '', '', 'store/recharge', 'GET', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1314, 1042, 1, '', '保存充值订单备注', 'admin', '', '', 'store/recharge/remark/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1315, 1042, 1, '', '获取充值订单备注', 'admin', '', '', 'store/recharge/remark/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1316, 1042, 1, '', '付费会员订单列表', 'admin', '', '', 'store/vip_order', 'GET', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1317, 1042, 1, '', '获取会员状态', 'admin', '', '', 'store/vip/status/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1318, 1042, 1, '', '获取会员备注', 'admin', '', '', 'store/vip/remark/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1319, 1042, 1, '', '保存会员备注', 'admin', '', '', 'store/vip/remark/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1037/1042', 2, '', 0, '', 0),
(1320, 1046, 1, '', '门店转账列表', 'admin', '', '', 'store/extract/list', 'GET', '[]', 0, 0, 0, 1, '', '1037/1043/1046', 2, '', 0, '', 0),
(1321, 1046, 1, '', '门店转账记录备注', 'admin', '', '', 'store/extract/mark/<id>', 'POST', '[]', 0, 0, 0, 1, '', '1037/1043/1046', 2, '', 0, '', 0),
(1322, 1046, 1, '', '门店转账记录备注', 'admin', '', '', 'store/extract/verify/<id>', 'POST', '[]', 0, 0, 0, 1, '', '1037/1043/1046', 2, '', 0, '', 0),
(1323, 1046, 1, '', '转账表单', 'admin', '', '', 'store/extract/transfer/<id>', 'GET', '[]', 0, 0, 0, 1, '', '1037/1043/1046', 2, '', 0, '', 0),
(1324, 1046, 1, '', '转账提交', 'admin', '', '', 'store/extract/save_transfer/<id>', 'POST', '[]', 0, 0, 0, 1, '', '1037/1043/1046', 2, '', 0, '', 0),
(1325, 1044, 1, '', '门店流水列表', 'admin', '', '', 'store/finance_flow/list', 'GET', '[]', 0, 0, 0, 1, '', '1037/1043/1044', 2, '', 0, '', 0),
(1326, 1044, 1, '', '门店流水备注', 'admin', '', '', 'store/finance_flow/mark/<id>', 'PUT', '[]', 0, 0, 0, 1, '', '1037/1043/1044', 2, '', 0, '', 0),
(1327, 1045, 1, '', '门店账单记录', 'admin', '', '', 'store/finance_flow/fund_record', 'GET', '[]', 0, 0, 0, 1, '', '1037/1043/1045', 2, '', 0, '', 0),
(1328, 1045, 1, '', '门店账单详情', 'admin', '', '', 'store/finance_flow/fund_record_info', 'GET', '[]', 0, 0, 0, 1, '', '1037/1043/1045', 2, '', 0, '', 0),
(1329, 1047, 1, '', '门店财务设置头部', 'admin', '', '', 'store/finance/header_basics', 'GET', '[]', 0, 0, 0, 1, '', '1037/1043/1047', 2, '', 0, '', 0),
(1330, 1047, 1, '', '门店财务编辑表单', 'admin', '', '', 'store/finance/edit_basics', 'GET', '[]', 0, 0, 0, 1, '', '1037/1043/1047', 2, '', 0, '', 0),
(1331, 1047, 1, '', '门店财务保存数据', 'admin', '', '', 'store/finance/save_basics', 'POST', '[]', 0, 0, 0, 1, '', '1037/1043/1047', 2, '', 0, '', 0),
(1332, 1040, 1, '', '门店首页头部统计数据', 'admin', '', '', 'store/home/header', 'GET', '[]', 0, 1, 0, 1, '', '1037/1040', 2, '', 0, '', 0),
(1333, 1040, 1, '', '门店首页营业趋势图表', 'admin', '', '', 'store/home/operate', 'GET', '[]', 0, 0, 0, 1, '', '1037/1040', 2, '', 0, '', 0),
(1334, 1040, 1, '', '门店首页交易图表', 'admin', '', '', 'store/home/orderChart', 'GET', '[]', 0, 0, 0, 1, '', '1037/1040', 2, '', 0, '', 0),
(1335, 1040, 1, '', '门店首页门店统计', 'admin', '', '', 'store/home/store', 'GET', '[]', 0, 0, 0, 1, '', '1037/1040', 2, '', 0, '', 0),
(1336, 1090, 1, '', '获取个人中心菜单', 'admin', '', '', 'app/wechat/card', 'GET', '[]', 0, 0, 0, 1, '', '12/135/69/1090', 2, '', 0, '', 0),
(1337, 1090, 1, '', '保存微信会员卡', 'admin', '', '', 'app/wechat/card', 'POST', '[]', 0, 0, 0, 1, '', '12/135/69/1090', 2, '', 0, '', 0),
(1338, 1, 1, 'logo-hackernews', '商品品牌', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/product/product_brand', '1', 1, 'product', 1, 'admin-store-storeBrand-index', 0),
(1339, 1338, 1, '', '品牌分类列表', 'admin', '', '', 'product/brand', 'GET', '[]', 0, 1, 0, 1, '', '1/1321', 2, '', 0, '', 0),
(1340, 1338, 1, '', '商品品牌cascader行列表', 'admin', '', '', 'product/brand/cascader_list/<type?>', 'GET', '[]', 0, 1, 0, 1, '', '1/1321', 2, '', 0, '', 0),
(1341, 1338, 1, '', '品牌新增', 'admin', '', '', 'product/brand', 'POST', '[]', 0, 1, 0, 1, '', '1/1321', 2, '', 0, '', 0),
(1342, 1338, 1, '', '商品品牌编辑', 'admin', '', '', 'product/brand/<id>', 'PUT', '[]', 0, 1, 0, 1, '', '1/1321', 2, '', 0, '', 0),
(1343, 1338, 1, '', '删除商品品牌', 'admin', '', '', 'product/brand/<id>', 'DELETE', '[]', 0, 1, 0, 1, '', '1/1321', 2, '', 0, '', 0),
(1344, 1338, 1, '', '商品品牌修改状态', 'admin', '', '', 'product/brand/set_show/<id>/<is_show>', 'PUT', '[]', 0, 1, 0, 1, '', '1/1321', 2, '', 0, '', 0),
(1345, 135, 1, '', '小程序', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/app/routine/download', '12/135', 1, '', 0, 'admin-routine', 0),
(1346, 1345, 1, '', '小程序下载', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/app/routine/download', '12/135/1345', 1, '', 0, 'routine-download', 0),
(1347, 1345, 1, '', '下载小程序页面数据', 'admin', '', '', 'app/routine/info', 'GET', '[]', 0, 1, 0, 1, '', '12/135/1345', 2, '', 0, '', 0),
(1348, 1345, 1, '', '下载小程序模版', 'admin', '', '', 'app/routine/download', 'POST', '[]', 0, 1, 0, 1, '', '12/135/1345', 2, '', 0, '', 0),
(1349, 1, 1, 'ios-disc', '商品单位', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/product/unitList', '1', 1, 'product', 1, 'admin-storeProduct-unit', 0),
(1350, 12, 1, 'ios-basket', '商城设置', 'admin', '', '', '', '', '[]', 125, 1, 0, 1, '/admin/setting/shop', '12', 1, 'setting', 0, '', 0),
(1351, 1421, 1, '', '系统设置', 'admin', '', '', '', '', '[]', 127, 1, 0, 1, '/admin/setting/shop/base', '12/1421', 1, 'setting', 1, 'setting-shop-base', 0),
(1352, 1350, 1, '', '配送设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/setting/shop/distribution', '12/1350', 1, '', 0, 'setting-shop-distribution', 0),
(1353, 1350, 1, '', '交易设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/setting/shop/trade', '12/1350', 1, '', 0, 'setting-shop-trade', 0),
(1354, 1350, 1, '', '支付设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/setting/shop/pay', '12/1350', 1, '', 0, 'setting-shop-pay', 0),
(1355, 1421, 1, '', '第三方接口', 'admin', '', '', '', '', '[]', 80, 1, 0, 1, '/admin/setting/third_party', '12/1421', 1, 'setting', 1, 'setting-third-party', 0),
(1356, 1421, 1, '', '存储配置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/setting/storage', '12/1421', 1, 'setting', 1, '', 0),
(1357, 1356, 1, '', '存储设置', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/storage/index', '12/1356', 1, '', 0, '', 0),
(1358, 1356, 1, '', '缩略图设置', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/setting/storage/thumbnail', '12/1356', 1, '', 0, '', 0),
(1359, 1352, 1, '', '发货设置', 'admin', '', '', '', '', '[]', 100, 1, 0, 1, '/admin/setting/distribution/deliver', '12/1350/1352', 1, '', 0, 'setting-distribution-deliver', 0),
(1360, 135, 1, '', '微信开放平台', 'admin', '', '', '', '', '[]', 0, 0, 0, 1, '/admin/app/wechat_open', '12/135', 1, '', 0, 'app-wechat-open', 0),
(1361, 135, 1, '', 'PC', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/app/pc', '12/135', 1, '', 0, 'app-pc', 0),
(1362, 69, 1, '', '基础配置', 'admin', '', '', '', '', '[]', 10, 1, 0, 1, '/admin/app/wechat/base', '12/135/69', 1, '', 0, 'app-wechat-base', 0),
(1363, 731, 1, '', '会员设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/vipuser/grade/setup', '9/1093/731', 1, '', 0, 'vipuser-grade-setup', 0),
(1364, 767, 1, '', '发票列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/order/invoice/list', '35/36/767', 1, '', 0, 'admin-order-startOrderInvoice-index', 0),
(1365, 767, 1, '', '发票设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/order/invoice/setup', '35/36/767', 1, '', 0, 'admin-order-invoice-setup', 0),
(1366, 11, 1, '', '等级列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/vipuser/level/list', '9/1093/11', 1, '', 0, 'user-user-level', 0),
(1367, 11, 1, '', '等级设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/vipuser/level/setup', '9/1093/11', 1, '', 0, 'vipuser-level-setup', 0),
(1368, 165, 1, '', '客服设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/kefu/setup', '9/165', 1, '', 0, 'admin-kefu-setup', 0),
(1369, 27, 1, 'md-card', '余额充值', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/recharge', '27', 1, 'marketing', 1, 'marketing-recharge', 0),
(1370, 1369, 1, '', '充值金额', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/balance_recharge', '27/1369', 1, '', 0, 'marketing-balance_recharge', 0),
(1377, 1369, 1, '', '充值设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/setup_recharge', '27/1369', 1, '', 0, 'marketing-setup-recharge', 0),
(1378, 959, 1, '', '添加、编辑优惠套餐', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/store_discounts/create', '27/959', 1, '', 0, 'admin-marketing-store_discounts-create', 0),
(1379, 135, 1, '', 'APP', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/app/app', '12/135', 1, '', 0, 'app-app', 0),
(1380, 1, 1, 'md-pricetags', '商品标签', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/product/label', '1', 1, '', 0, 'admin-storeProduct-label', 0),
(1381, 1, 1, 'ios-list-box', '商品参数', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/product/specs', '1', 1, '', 0, 'admin-storeProduct-specs', 0),
(1382, 1, 1, 'ios-man', '保障服务', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/product/ensure', '1', 1, '', 0, 'admin-storeProduct-ensure', 0),
(1383, 1382, 1, '', '添加商品参数模版', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/product/ensure/create', '1/1382', 1, '', 0, 'admin-storeProduct-ensure-create', 0),
(1384, 37, 1, '', '资金流水', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/statistic/capital', '35/37', 1, '', 0, 'admin-statistic-capital', 0),
(1385, 26, 1, 'ios-book', '分销说明', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/agent/agreement', '26', 1, '', 0, 'agent-agreement', 0),
(1386, 1350, 1, '', '政策协议', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/setting/shop/agreemant', '12/1350', 1, '', 0, 'setting-shop-agreement', 0),
(1387, 980, 1, '', '消息设置', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/setting/notification/notificationEdit', '12/980', 1, '', 0, '', 0),
(1388, 0, 1, '', '企业微信', 'admin', '', '', '', '', '[]', 70, 1, 0, 1, '/admin/work', '', 1, 'work', 1, 'admin-work', 0),
(1389, 1418, 1, '', '企微渠道码', 'admin', '', '', '', '', '[]', 9, 1, 0, 1, '/admin/work/channel_code', '1388/1418', 1, '', 0, 'work-channel-code', 0),
(1390, 1423, 1, '', '客户群列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/work/client/group_chat', '1388/1423', 1, '', 0, 'work-customer-base', 0),
(1391, 1423, 1, '', '自动拉群', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/work/auth_group', '1388/1423', 1, '', 0, 'work-auth-group', 0),
(1392, 1418, 1, '', '欢迎语', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/work/welcome', '1388/1418', 1, '', 0, 'work-welcome', 0),
(1393, 27, 1, 'md-bonfire', '优惠活动', 'admin', '', '', '', '', '[]', 96, 1, 0, 1, '/admin/marketing/discount', '27', 1, '', 0, 'admin-marketing-discount', 0),
(1394, 1393, 1, '', '限时折扣', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/discount/list', '27/1393', 1, '', 0, 'marketing-discount-list', 0),
(1395, 1393, 1, '', '满送活动', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/discount/give', '27/1393', 1, '', 0, 'marketing-discount-give', 0),
(1396, 1393, 1, '', '满减满折', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/discount/full_discount', '27/1393', 1, '', 0, 'marketing-discount-full_discount', 0),
(1397, 1393, 1, '', '第N件N折', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/discount/pieces_discount', '27/1393', 1, '', 0, 'marketing-discount-pieces_discount', 0),
(1398, 1394, 1, '', '添加限时折扣', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/discount/add', '27/1393/1394', 1, '', 0, 'marketing-discount-add', 0),
(1399, 1395, 1, '', '添加满减送', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/discount/add_give', '27/1393/1395', 1, '', 0, 'marketing-discount-add_give', 0),
(1400, 1396, 1, '', '添加满减满折', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/discount/add_discount', '27/1393/1396', 1, '', 0, 'marketing-discount-add_discount', 0),
(1401, 1397, 1, '', '添加第N件N折', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/marketing/discount/add_pieces', '27/1393/1397', 1, '', 0, 'marketing-discount-add_pieces', 0),
(1403, 1388, 1, '', '添加企业渠道码', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/work/createCode', '1388', 1, '', 0, 'work-code-create', 0),
(1404, 1388, 1, '', '新建好友欢迎语', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/work/addWelcome', '1388', 1, '', 0, 'work-addWelcome', 0),
(1405, 1423, 1, '', '新建自动拉群', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/work/addAuthGroup', '1388/1423', 1, '', 0, 'work-addAuthGroup', 0),
(1406, 1388, 1, '', '员工列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/work/staffList', '1388', 1, '', 0, 'work-staffList', 0),
(1407, 1037, 1, 'ios-menu', '收银台菜单', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/store/cashier_menus/index', '1037', 1, '', 0, 'admin-cashier_menus-index', 0),
(1410, 0, 3, 'iconshouyintai-shouyin', '收银', 'admin', '', '', '', '', '[]', 127, 1, 0, 1, '/cashier/cashier/index', '', 1, '', 0, 'cashier-cashier-index', 0),
(1411, 0, 3, 'iconshouyintai-dingdan', '订单', 'admin', '', '', '', '', '[]', 3, 1, 0, 1, '/cashier/order/index', '', 1, '', 0, 'cashier-order-index', 0),
(1412, 0, 3, 'iconshouyintai-guadan', '挂单', 'admin', '', '', '', '', '[]', 127, 1, 0, 1, '/cashier/hang/index', '', 1, '', 0, 'cashier-hang-index', 0),
(1413, 0, 3, 'iconshouyintai-hexiao', '核销', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/cashier/verify/index', '', 1, '', 0, 'cashier-verify-index', 0),
(1414, 0, 3, 'iconshouyintai-tuihuo', '退货', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/cashier/refund/index', '', 1, '', 0, 'cashier-refund-index', 0),
(1415, 1414, 3, '', '退款单列表', 'admin', '', '', '/asd/asd/asd', 'GET', '[]', 0, 1, 0, 1, '', '1037/1407/1414', 2, '', 0, '', 0),
(1416, 1388, 1, '', '企业微信设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/work/config', '1388', 1, '', 0, 'admin-work-config', 0),
(1417, 0, 3, 'iconshouyintai-chongzhi', '充值', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/cashier/recharge/index', '', 1, '', 0, 'cashier-recharge-index', 0),
(1418, 1388, 1, '', '客户管理', 'admin', '', '', '', '', '[]', 8, 1, 0, 1, '/admin/work/client', '1388', 1, '', 0, '', 0),
(1419, 1418, 1, '', '客户列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/work/client/list', '1388/1418', 1, '', 0, 'work-client-list', 0),
(1420, 1423, 1, '', '客户群统计', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/work/client/statistical', '1388/1423', 1, '', 0, 'work-client-statistical', 0),
(1421, 12, 1, 'md-cog', '系统设置', 'admin', '', '', '', '', '[]', 127, 1, 0, 1, '/admin/setting/base', '12', 1, '', 0, '', 0),
(1422, 1418, 1, '', '客户群发', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/work/client/group', '1388/1418', 1, '', 0, 'work-client-group', 0),
(1423, 1388, 1, '', '客户群运营', 'admin', '', '', '', '', '[]', 7, 1, 0, 1, '/admin/work/group', '1388', 1, '', 0, '', 0),
(1424, 1423, 1, '', '客户群群发', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/work/group/template', '1388/1423', 1, '', 0, 'work-group-template', 0),
(1428, 1418, 1, '', '添加客户群发', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/work/client/add_group', '1388/1418', 1, '', 0, 'work-client-add_group', 0),
(1429, 1423, 1, '', '添加客户群群发', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/work/group/add_template', '1388/1423', 1, '', 0, 'work-group-add_template', 0),
(1430, 1418, 1, '', '朋友圈添加', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/work/client/add_moment', '1388/1418', 1, '', 0, 'work-client-add-moment', 0),
(1431, 1418, 1, '', '朋友圈列表', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/work/client/moment', '1388/1418', 1, '', 0, 'work-client-moment', 0),
(1432, 1418, 1, '', '客户群发详情', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/work/client/group_info', '1388/1418', 1, '', 0, 'work-client-groupInfo', 0),
(1433, 1423, 1, '', '客户群群发详情', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/work/group/template_info', '1388/1423', 1, '', 0, 'work-group-templateInfo', 0),
(1434, 1418, 1, '', '朋友圈详情', 'admin', '', '', '', '', '[]', 0, 1, 1, 1, '/admin/work/client/moment_info', '1388/1418', 1, '', 0, 'work-client-momentInfo', 0),
(1435, 31, 1, '', '砍价设置', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_bargain/setting', '27/31', 1, '', 0, 'marketing-store_bargain-setting', 0),
(1436, 34, 1, '', '积分订单', 'admin', '', '', '', '', '[]', 0, 1, 0, 1, '/admin/marketing/store_integral/order_list', '27/34', 1, '', 0, 'marketing-store_integral-order', 0)
SQL
            ],
        ];
        return $data;
    }

}
