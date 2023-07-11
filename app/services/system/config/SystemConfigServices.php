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

namespace app\services\system\config;


use app\dao\system\config\SystemConfigDao;
use app\jobs\agent\SystemJob;
use app\services\BaseServices;
use app\services\store\StoreConfigServices;
use app\services\user\UserIntegralServices;
use crmeb\exceptions\AdminException;
use crmeb\form\Build;
use crmeb\form\components\Alert;
use crmeb\form\components\InputNumber;
use crmeb\form\validate\StrRules;
use crmeb\services\FileService;
use crmeb\services\FormBuilder;
use crmeb\services\wechat\contract\ServeConfigInterface;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;
use think\facade\Log;

/**
 * 系统配置
 * Class SystemConfigServices
 * @package app\services\system\config
 * @method getUploadTypeList(string $configName) 获取上传配置中的上传类型
 */
class SystemConfigServices extends BaseServices implements ServeConfigInterface
{
    use ServicesTrait;

    /**
     * form表单句柄
     * @var FormBuilder
     */
    protected $builder;

    /**
     * 表单数据切割符号
     * @var string
     */
    protected $cuttingStr = '=>';

    /**
     * 表单提交url
     * @var string[]
     */
    protected $postUrl = [
        'setting' => [
            'url' => '/setting/config/save_basics',
            'auth' => [],
        ],
        'serve' => [
            'url' => '/serve/sms_config/save_basics',
            'auth' => ['short_letter_switch'],
        ],
        'freight' => [
            'url' => '/freight/config/save_basics',
            'auth' => ['express'],
        ],
        'agent' => [
            'url' => '/agent/config/save_basics',
            'auth' => ['fenxiao'],
        ],
        'marketing' => [
            'url' => '/marketing/integral_config/save_basics',
            'auth' => ['point'],
        ],
        'store' => [
            'url' => '/store/finance/header_basics',
            'auth' => [],
        ]
    ];

    /**
     * 子集控制规则
     * @var array[]
     */
    protected $relatedRule = [
        'brokerage_func_status' => [
            'son_type' => [
                'store_brokerage_statu' => [
                    'son_type' => ['store_brokerage_price' => ''],
                    'show_value' => 3
                ],
                'brokerage_bindind' => '',
                'store_brokerage_binding_status' => [
                    'son_type' => ['store_brokerage_binding_time' => ''],
                    'show_value' => 2
                ],
                'spread_banner' => '',
            ],
            'show_value' => 1
        ],
        'brokerage_user_status' => [
            'son_type' => [
                'uni_brokerage_price' => '',
                'day_brokerage_price_upper' => '',
            ],
            'show_value' => 1
        ],
        'pay_success_printing_switch' => [
            'son_type' => [
                'develop_id' => '',
                'printing_api_key' => '',
                'printing_client_id' => '',
                'terminal_number' => '',
            ],
            'show_value' => 1
        ],
        'wss_open' => [
            'son_type' => [
                'wss_local_cert' => '',
                'wss_local_pk' => '',
            ],
            'show_value' => 1
        ],
        'invoice_func_status' => [
            'son_type' => [
                'special_invoice_status' => '',
            ],
            'show_value' => 1
        ],
        'member_func_status' => [
            'son_type' => [
                'member_price_status' => '',
                'order_give_exp' => '',
                'sign_give_exp' => '',
                'invite_user_exp' => ''
            ],
            'show_value' => 1
        ],
        'balance_func_status' => [
            'son_type' => [
                'recharge_attention' => '',
                'recharge_switch' => '',
                'store_user_min_recharge' => '',
            ],
            'show_value' => 1
        ],
        'system_product_copy_type' => [
            'son_type' => [
                'copy_product_apikey' => '',
            ],
            'show_value' => 2
        ],
        'logistics_type' => [
            'son_type' => [
                'system_express_app_code' => '',
            ],
            'show_value' => 2
        ],
        'ali_pay_status' => [
            'son_type' => [
                'ali_pay_appid' => '',
                'alipay_merchant_private_key' => '',
                'alipay_public_key' => '',
            ],
            'show_value' => 1
        ],
        'pay_weixin_open' => [
            'son_type' => [
                'pay_weixin_mchid' => '',
                'pay_weixin_key' => '',
                'pay_weixin_client_cert' => '',
                'pay_weixin_client_key' => '',
                'paydir' => '',
            ],
            'show_value' => 1
        ],
        'config_export_open' => [
            'son_type' => [
                'config_export_to_name' => '',
                'config_export_to_tel' => '',
                'config_export_to_address' => '',
                'config_export_siid' => '',
            ],
            'show_value' => 1
        ],
        'image_watermark_status' => [
            'son_type' => [
                'watermark_type' => [
                    'son_type' => [
                        'watermark_image' => '',
                        'watermark_opacity' => '',
                        'watermark_rotate' => '',
                    ],
                    'show_value' => 1
                ],
                'watermark_position' => '',
                'watermark_x' => '',
                'watermark_y' => '',
                'watermark_type@' => [
                    'son_type' => [
                        'watermark_text' => '',
                        'watermark_text_size' => '',
                        'watermark_text_color' => '',
                        'watermark_text_angle' => ''
                    ],
                    'show_value' => 2
                ],
            ],
            'show_value' => 1
        ],
        'share_qrcode' => [
            'son_type' => [
                'spread_share_forever' => '',
            ],
            'show_value' => 1
        ],
        'integral_effective_status' => [
            'son_type' => [
                'integral_effective_time' => [
                    'son_type' => [
                        'next_clear_month_time' => '',
                    ],
                    'show_value' => 1
                ],
                'integral_effective_time@' => [
                    'son_type' => [
                        'next_clear_quarter_time' => '',
                    ],
                    'show_value' => 2
                ],
                'integral_effective_time#' => [
                    'son_type' => [
                        'next_clear_year_time' => '',
                    ],
                    'show_value' => 3
                ],
            ],
            'show_value' => 1
        ],
        'user_extract_bank_status' => [
            'son_type' => [
                'user_extract_bank' => '',
            ],
            'show_value' => 1
        ],
    ];

    /**
     * SystemConfigServices constructor.
     * @param SystemConfigDao $dao
     */
    public function __construct(SystemConfigDao $dao, FormBuilder $builder)
    {
        $this->dao = $dao;
        $this->builder = $builder;
    }

    public function getSonConfig()
    {
        $sonConfig = [];
        $rolateRule = $this->relatedRule;
        if ($rolateRule) {
            foreach ($rolateRule as $key => $value) {
                $sonConfig = array_merge($sonConfig, array_keys($value['son_type']));
                foreach ($value['son_type'] as $k => $v) {
                    if (isset($v['son_type'])) {
                        $sonConfig = array_merge($sonConfig, array_keys($v['son_type']));
                    }
                }
            }
        }
        return $sonConfig;
    }

    /**
     * 获取单个系统配置
     * @param string $configName
     * @param null $default
     * @return mixed|null
     */
    public function getConfigValue(string $configName, $default = null, int $storeId = 0)
    {
        if ($storeId) {
            /** @var StoreConfigServices $service */
            $service = app()->make(StoreConfigServices::class);
            return $service->getConfig($storeId, $configName);
        } else {
            $value = $this->dao->getConfigValue($configName, $storeId);
            return is_null($value) ? $default : json_decode($value, true);
        }
    }

    /**
     * 获取全部配置
     * @param array $configName
     * @return array
     */
    public function getConfigAll(array $configName = [], int $storeId = 0)
    {
        if ($storeId) {
            /** @var StoreConfigServices $service */
            $service = app()->make(StoreConfigServices::class);
            return $service->getConfigAll($storeId, $configName);
        } else {
            return array_map(function ($item) {
                return json_decode($item, true);
            }, $this->dao->getConfigAll($configName, $storeId));
        }
    }

    /**
     * 获取配置并分页
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConfigList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getConfigList($where, $page, $limit);
        $count = $this->dao->count($where);
        foreach ($list as &$item) {
            $item['value'] = $item['value'] ? json_decode($item['value'], true) ?: '' : '';
            if ($item['type'] == 'radio' || $item['type'] == 'checkbox') {
                $item['value'] = $this->getRadioOrCheckboxValueInfo($item['menu_name'], $item['value']);
            }
            if ($item['type'] == 'upload' && !empty($item['value'])) {
                if ($item['upload_type'] == 1 || $item['upload_type'] == 3) {
                    $item['value'] = [set_file_url($item['value'])];
                } elseif ($item['upload_type'] == 2) {
                    $item['value'] = set_file_url($item['value']);
                }
                foreach ($item['value'] as $key => $value) {
                    $tidy_srr[$key]['filepath'] = $value;
                    $tidy_srr[$key]['filename'] = basename($value);
                }
                $item['value'] = $tidy_srr;
            }
        }
        return compact('count', 'list');
    }

    /**
     * 获取单选按钮或者多选按钮的显示值
     * @param $menu_name
     * @param $value
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRadioOrCheckboxValueInfo(string $menu_name, $value): string
    {
        $option = [];
        $config_one = $this->dao->getOne(['menu_name' => $menu_name]);
        if (!$config_one) {
            return '';
        }
        $parameter = explode("\n", $config_one['parameter']);
        foreach ($parameter as $k => $v) {
            if (isset($v) && strlen($v) > 0) {
                $data = explode('=>', $v);
                $option[$data[0]] = $data[1];
            }
        }
        $str = '';
        if (is_array($value)) {
            foreach ($value as $v) {
                $str .= $option[$v] . ',';
            }
        } else {
            $str .= !empty($value) ? $option[$value] ?? '' : $option[0] ?? '';
        }
        return $str;
    }

    /**
     * 获取系统配置信息
     * @param int $tabId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getReadList(int $tabId)
    {
        $info = $this->dao->getConfigTabAllList($tabId);
        foreach ($info as $k => $v) {
            if (!is_null(json_decode($v['value'])))
                $info[$k]['value'] = json_decode($v['value'], true);
            if ($v['type'] == 'upload' && !empty($v['value'])) {
                if ($v['upload_type'] == 1 || $v['upload_type'] == 3) $info[$k]['value'] = explode(',', $v['value']);
            }
        }
        return $info;
    }

    /**
     * 创建单行表单
     * @param string $type
     * @param array $data
     * @return array
     */
    public function createTextForm(string $type, array $data)
    {
        $formbuider = [];
        switch ($type) {
            case 'input':
                $data['value'] = isset($data['value']) ? json_decode($data['value'], true) : '';
                $formbuider[] = $this->builder->input($data['menu_name'], $data['info'], $data['value'])->info($data['desc'])->placeholder($data['desc'])->col(13);
                break;
            case 'number':
                $data['value'] = isset($data['value']) ? json_decode($data['value'], true) : 0;
                if ($data['menu_name'] == 'integral_max_num' || $data['menu_name'] == 'order_give_integral') {
                    $formbuider[] = $this->builder->number($data['menu_name'], $data['info'], (float)$data['value'])->info($data['desc'])->precision(0);
                } else {
                    $formbuider[] = $this->builder->number($data['menu_name'], $data['info'], (float)$data['value'])->info($data['desc']);
                }
                break;
            case 'dateTime':
                $formbuider[] = $this->builder->dateTime($data['menu_name'], $data['info'], $data['value'])->info($data['desc']);
                break;
            case 'color':
                $data['value'] = isset($data['value']) ? json_decode($data['value'], true) : '';
                $formbuider[] = $this->builder->color($data['menu_name'], $data['info'], $data['value'])->info($data['desc']);
                break;
            default:
                $data['value'] = isset($data['value']) ? json_decode($data['value'], true) : '';
                $formbuider[] = $this->builder->input($data['menu_name'], $data['info'], $data['value'])->info($data['desc'])->placeholder($data['desc'])->col(13);
                break;
        }
        return $formbuider;
    }

    /**
     * 创建多行文本框
     * @param array $data
     * @return mixed
     */
    public function createTextareaForm(array $data)
    {
        $data['value'] = json_decode($data['value'], true) ?: '';
        $formbuider[] = $this->builder->textarea($data['menu_name'], $data['info'], $data['value'])->placeholder($data['desc'])->info($data['desc'])->rows(6)->col(13);
        return $formbuider;
    }

    /**
     * 创建当选表单
     * @param array $data
     * @param false $control
     * @param array $control_two
     * @param array $controlle_three
     * @return array
     */
    public function createRadioForm(array $data, $control = false, $control_two = [], $controlle_three = [])
    {
        $formbuider = [];
        $data['value'] = json_decode($data['value'], true) ?: '0';
        $parameter = explode("\n", $data['parameter']);
        $options = [];
        if ($parameter) {
            foreach ($parameter as $v) {
                if (strstr($v, $this->cuttingStr) !== false) {
                    $pdata = explode($this->cuttingStr, $v);
                    $options[] = ['label' => $pdata[1], 'value' => (int)$pdata[0]];
                }
            }
            $formbuider[] = $radio = $this->builder->radio($data['menu_name'], $data['info'], (int)$data['value'])->options($options)->info($data['desc'])->col(13);
            if ($control) {
                $radio->appendControl(isset($data['show_value']) ? $data['show_value'] : 1, is_array($control) ? $control : [$control]);
            }
            if ($control_two && isset($data['show_value2'])) {
                $radio->appendControl(isset($data['show_value2']) ? $data['show_value2'] : 2, is_array($control_two) ? $control_two : [$control_two]);
            }
            if ($controlle_three && isset($data['show_value3'])) {
                $radio->appendControl(isset($data['show_value3']) ? $data['show_value3'] : 3, is_array($controlle_three) ? $controlle_three : [$controlle_three]);
            }
            return $formbuider;
        }
    }

    /**
     * 创建上传组件表单
     * @param int $type
     * @param array $data
     * @param int $store_id
     * @return array
     */
    public function createUpoadForm(int $type, array $data, int $store_id = 0)
    {
        $formbuider = [];
        $from = $store_id > 0 ? 'store' : 'admin';
        switch ($type) {
            case 1:
                $data['value'] = json_decode($data['value'], true) ?: '';
                if (!$data['value']) $data['value'] = set_file_url($data['value']);
                $formbuider[] = $this->builder->frameImage($data['menu_name'], $data['info'], $this->url($from . '/widget.images/index', ['fodder' => $data['menu_name']], true), $data['value'])
                    ->icon('ios-image')->width('960px')->height('505px')->modal(['footer-hide' => true])->info($data['desc'])->col(13);
                break;
            case 2:
                $data['value'] = json_decode($data['value'], true) ?: [];
                if (!$data['value']) $data['value'] = set_file_url($data['value']);
                $formbuider[] = $this->builder->frameImages($data['menu_name'], $data['info'], $this->url($from . '/widget.images/index', ['fodder' => $data['menu_name'], 'type' => 'many', 'maxLength' => 5], true), $data['value'])
                    ->maxLength(5)->icon('ios-images')->width('960px')->modal(['footer-hide' => true])->height('505px')
                    ->info($data['desc'])->col(13);
                break;
            case 3:
                $data['value'] = json_decode($data['value'], true) ?: '';
                if (!$data['value']) $data['value'] = set_file_url($data['value']);
                $formbuider[] = $this->builder->uploadFile($data['menu_name'], $data['info'], $this->url('/adminapi/file/upload/1', ['type' => 1], false, false), $data['value'])
                    ->name('file')->info($data['desc'])->col(13)->headers([
                        'Authori-zation' => app()->request->header('Authori-zation'),
                    ]);
                break;
        }
        return $formbuider;
    }

    /**
     * 创建单选框
     * @param array $data
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createCheckboxForm(array $data)
    {
        $formbuider = [];
        $data['value'] = json_decode($data['value'], true) ?: [];
        $parameter = explode("\n", $data['parameter']);
        $options = [];
        if ($parameter) {
            foreach ($parameter as $v) {
                if (strstr($v, $this->cuttingStr) !== false) {
                    $pdata = explode($this->cuttingStr, $v);
                    $options[] = ['label' => $pdata[1], 'value' => $pdata[0]];
                }
            }
            $formbuider[] = $this->builder->checkbox($data['menu_name'], $data['info'], $data['value'])->options($options)->info($data['desc'])->col(13);
        }
        return $formbuider;
    }

    /**
     * 创建选择框表单
     * @param array $data
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createSelectForm(array $data)
    {
        $formbuider = [];
        $data['value'] = json_decode($data['value'], true) ?: [];
        $parameter = explode("\n", $data['parameter']);
        $options = [];
        if ($parameter) {
            foreach ($parameter as $v) {
                if (strstr($v, $this->cuttingStr) !== false) {
                    $pdata = explode($this->cuttingStr, $v);
                    $options[] = ['label' => $pdata[1], 'value' => $pdata[0]];
                }
            }
            $formbuider[] = $this->builder->select($data['menu_name'], $data['info'], $data['value'])->options($options)->info($data['desc'])->col(13);
        }
        return $formbuider;
    }

    public function bindBuilderData($data, $relatedRule)
    {
        if (!$data) return false;
        $p_list = array();
        foreach ($relatedRule as $rk => $rv) {
            $p_list[$rk] = $data[$rk];
            if (isset($rv['son_type']) && is_array($rv['son_type'])) {
                foreach ($rv['son_type'] as $sk => $sv) {
                    if (is_array($sv) && isset($sv['son_type'])) {
                        foreach ($sv['son_type'] as $ssk => $ssv) {
                            $tmp = $data[$sk];
                            $tmp['console'] = $data[$ssk];
                            $p_list[$rk]['console'][] = $tmp;
                        }
                    } else {
                        $p_list[$rk]['console'][] = $data[$sk];
                    }
                }
            }

        }
        return array_values($p_list);
    }

    /**
     * 获取系统配置表单
     * @param int $id
     * @param array $formData
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */

    public function formTypeShine($data, $control = false, $controle_two = [], $controlle_three = [])
    {

        switch ($data['type']) {
            case 'text'://文本框
                return $this->createTextForm($data['input_type'], $data);
                break;
            case 'radio'://单选框
                return $this->createRadioForm($data, $control, $controle_two, $controlle_three);
                break;
            case 'textarea'://多行文本框
                return $this->createTextareaForm($data);
                break;
            case 'upload'://文件上传
                return $this->createUpoadForm((int)$data['upload_type'], $data);
                break;
            case 'checkbox'://多选框
                return $this->createCheckboxForm($data);
                break;
            case 'select'://多选框
                return $this->createSelectForm($data);
                break;
        }
    }

    /**
     * @param int $tabId
     * @param array $formData
     * @param array $relatedRule
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createConfigForm(int $tabId, array $relatedRule)
    {
        $list = $this->dao->getConfigTabAllList($tabId);
        if (!$relatedRule) {
            $formbuider = $this->createNoCrontrolForm($list);
        } else {
            $formbuider = $this->createBindCrontrolForm($list, $relatedRule);
        }
        return $formbuider;
    }

    /**
     * 创建
     * @param array $list
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createForm(array $list, $store_id = 0)
    {
        if (!$list) return [];
        $list = array_combine(array_column($list, 'menu_name'), $list);
        $formbuider = [];
        $relateRule = $this->relatedRule;
        $sonConfig = $this->getSonConfig();
        $next_clear_month_time = $next_clear_quarter_time = $next_clear_year_time = '';
        if (in_array('next_clear_month_time', $sonConfig)) {
            /** @var UserIntegralServices $userIntergralServices */
            $userIntergralServices = app()->make(UserIntegralServices::class);
            [$next_clear_month_time] = $userIntergralServices->getTime(1);
            [$next_clear_quarter_time] = $userIntergralServices->getTime(2);
            [$next_clear_year_time] = $userIntergralServices->getTime(3);
        }
        foreach ($list as $key => $data) {
            if (in_array($key, $sonConfig)) {
                continue;
            }
            switch ($data['type']) {
                case 'text'://文本框
                    $formbuider = array_merge($formbuider, $this->createTextForm($data['input_type'], $data));
                    break;
                case 'radio'://单选框
                    $builder = [];
                    if (isset($relateRule[$key])) {
                        $role = $relateRule[$key];
                        $data['show_value'] = $role['show_value'];
                        foreach ($role['son_type'] as $sk => $sv) {
                            if (isset($list[$sk])) {
                                $son_data = $list[$sk];
                                $son_data['show_value'] = $role['show_value'];
                                $son_build = [];
                                if (isset($sv['son_type'])) {
                                    foreach ($sv['son_type'] as $ssk => $ssv) {
                                        $son_data['show_value'] = $sv['show_value'];
                                        if ($ssk == 'next_clear_month_time') {
                                            $son_build[] = $this->builder->input('next_clear_month_time', '最近清零时间', $next_clear_month_time ? date('Y-m-d', $next_clear_month_time) : '')->info('最近清零时间')->disabled(true)->col(13);
                                        } else {
                                            $son_build[] = $this->formTypeShine($list[$ssk])[0];
                                            unset($list[$ssk]);
                                        }
                                    }
                                }
                                $son_build_two = [];
                                if (isset($role['son_type'][$sk . '@'])) {
                                    $son_type_two = $role['son_type'][$sk . '@'];
                                    $son_data['show_value2'] = $son_type_two['show_value'];
                                    if (isset($son_type_two['son_type'])) {
                                        foreach ($son_type_two['son_type'] as $ssk => $ssv) {
                                            if ($ssk == 'next_clear_quarter_time') {
                                                $son_build_two[] = $this->builder->input('next_clear_quarter_time', '最近清零时间', $next_clear_quarter_time ? date('Y-m-d', $next_clear_quarter_time) : '')->info('最近清零时间')->disabled(true)->col(13);
                                            } else {
                                                $son_build_two[] = $this->formTypeShine($list[$ssk])[0];
                                                unset($list[$ssk]);
                                            }
                                        }
                                    }
                                }
                                $son_build_three = [];
                                if (isset($role['son_type'][$sk . '#'])) {
                                    $son_type_two = $role['son_type'][$sk . '#'];
                                    $son_data['show_value3'] = $son_type_two['show_value'];
                                    if (isset($son_type_two['son_type'])) {
                                        foreach ($son_type_two['son_type'] as $ssk => $ssv) {
                                            if ($ssk == 'next_clear_year_time') {
                                                $son_build_three[] = $this->builder->input('next_clear_year_time', '最近清零时间', $next_clear_year_time ? date('Y-m-d', $next_clear_year_time) : '')->info('最近清零时间')->disabled(true)->col(13);
                                            } else {
                                                $son_build_three[] = $this->formTypeShine($list[$ssk])[0];
                                                unset($list[$ssk]);
                                            }
                                        }
                                    }
                                }
                                $builder[] = $this->formTypeShine($son_data, $son_build, $son_build_two, $son_build_three)[0];
                                unset($list[$sk]);
                            }
                        }
                        $data['show_value'] = $role['show_value'];
                    }
                    $formbuider = array_merge($formbuider, $this->createRadioForm($data, $builder));
                    break;
                case 'textarea'://多行文本框
                    $formbuider = array_merge($formbuider, $this->createTextareaForm($data));
                    break;
                case 'upload'://文件上传
                    $formbuider = array_merge($formbuider, $this->createUpoadForm((int)$data['upload_type'], $data, $store_id));
                    break;
                case 'checkbox'://多选框
                    $formbuider = array_merge($formbuider, $this->createCheckboxForm($data));
                    break;
                case 'select'://多选框
                    $formbuider = array_merge($formbuider, $this->createSelectForm($data));
                    break;
            }
        }
        return $formbuider;
    }

    /**无组件绑定规则
     * @param array $list
     * @return array|bool
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createNoCrontrolForm(array $list)
    {
        if (!$list) return false;
        $formbuider = [];
        foreach ($list as $key => $data) {

            switch ($data['type']) {
                case 'text'://文本框
                    $formbuider = array_merge($formbuider, $this->createTextForm($data['input_type'], $data));
                    break;
                case 'radio'://单选框
                    $formbuider = array_merge($formbuider, $this->createRadioForm($data));
                    break;
                case 'textarea'://多行文本框
                    $formbuider = array_merge($formbuider, $this->createTextareaForm($data));
                    break;
                case 'upload'://文件上传
                    $formbuider = array_merge($formbuider, $this->createUpoadForm((int)$data['upload_type'], $data));
                    break;
                case 'checkbox'://多选框
                    $formbuider = array_merge($formbuider, $this->createCheckboxForm($data));
                    break;
                case 'select'://多选框
                    $formbuider = array_merge($formbuider, $this->createSelectForm($data));
                    break;
            }
        }
        return $formbuider;
    }

    /**
     * 有组件绑定规则
     * @param array $list
     * @param array $relatedRule
     * @return array|bool
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createBindCrontrolForm(array $list, array $relatedRule)
    {
        if (!$list || !$relatedRule) return false;
        $formbuider = [];
        $new_data = array();
        foreach ($list as $dk => $dv) {
            $new_data[$dv['menu_name']] = $dv;
        }
        foreach ($relatedRule as $rk => $rv) {
            if (isset($rv['son_type'])) {
                $data = $new_data[$rk];
                switch ($data['type']) {
                    case 'text'://文本框
                        $formbuider = array_merge($formbuider, $this->createTextForm($data['input_type'], $data));
                        break;
                    case 'radio'://单选框
                        $son_builder = array();
                        foreach ($rv['son_type'] as $sk => $sv) {
                            if (isset($sv['son_type'])) {
                                foreach ($sv['son_type'] as $ssk => $ssv) {
                                    $son_data = $new_data[$sk];
                                    $son_data['show_value'] = $sv['show_value'];
                                    $son_builder[] = $this->formTypeShine($son_data, $this->formTypeShine($new_data[$ssk])[0])[0];
                                }
                            } else {
                                $son_data = $new_data[$sk];
                                $son_data['show_value'] = $rv['show_value'];
                                $son_builder[] = $this->formTypeShine($son_data)[0];
                            }

                        }
                        $formbuider = array_merge($formbuider, $this->createRadioForm($data, $son_builder));
                        break;
                    case 'textarea'://多行文本框
                        $formbuider = array_merge($formbuider, $this->createTextareaForm($data));
                        break;
                    case 'upload'://文件上传
                        $formbuider = array_merge($formbuider, $this->createUpoadForm((int)$data['upload_type'], $data));
                        break;
                    case 'checkbox'://多选框
                        $formbuider = array_merge($formbuider, $this->createCheckboxForm($data));
                        break;
                    case 'select'://多选框
                        $formbuider = array_merge($formbuider, $this->createSelectForm($data));
                        break;
                }
            }
        }
        return $formbuider;
    }

    /**
     * 系统配置form表单创建
     * @param int $tabId
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConfigForm($url, int $tabId, $store_id = 0)
    {
        /** @var SystemConfigTabServices $service */
        $service = app()->make(SystemConfigTabServices::class);
        $title = $service->value(['id' => $tabId], 'title');
        $list = $this->dao->getConfigTabAllList($tabId, 1, $store_id);
        if ($store_id != 0) {
            foreach ($list as &$item) {
                $item['value'] = $item['store_value'] ?? '';
            }
        }
        $formbuider = $this->createForm($list, $store_id);
        $name = 'setting';
        if ($url) {
            $name = explode('/', $url)[2] ?? $name;
        }
        $postUrl = $this->postUrl[$name]['url'] ?? '/setting/config/save_basics';
        $postUrl = $store_id ? $url : $postUrl;
        return create_form($title, $formbuider, $this->url($postUrl), 'POST');
    }

    /**
     * 新增路由增加设置项验证
     * @param $url
     * @param $post
     * @return bool
     */
    public function checkParam($url, $post)
    {
        $name = '';
        if ($url) {
            $name = explode('/', $url)[2] ?? $name;
        }
        $auth = $this->postUrl[$name]['auth'] ?? false;
        if ($auth === false) {
            throw new ValidateException('请求不被允许');
        }
        if ($auth) {
            /** @var SystemConfigTabServices $systemConfigTabServices */
            $systemConfigTabServices = app()->make(SystemConfigTabServices::class);
            foreach ($post as $key => $value) {
                $tab_ids = $systemConfigTabServices->getColumn([['eng_title', 'IN', $auth]], 'id');
                if (!$tab_ids || !in_array($key, $this->dao->getColumn([['config_tab_id', 'IN', $tab_ids]], 'menu_name'))) {
                    throw new ValidateException('设置类目不被允许');
                }
            }
        }
        return true;
    }

    /**
     * 修改配置获取form表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editConfigForm(int $id)
    {
        $menu = $this->dao->get($id)->getData();
        if (!$menu) {
            throw new AdminException('修改数据不存在!');
        }
        /** @var SystemConfigTabServices $service */
        $service = app()->make(SystemConfigTabServices::class);
        $formbuider = [];
        $formbuider[] = $this->builder->input('menu_name', '字段变量', $menu['menu_name'])->disabled(1);
        $formbuider[] = $this->builder->hidden('type', $menu['type']);
        $formbuider[] = $this->builder->cascader('config_tab_id', '分类', [$menu['config_tab_id']])->data($service->getSelectForm())->changeOnSelect(true);
        $formbuider[] = $this->builder->input('info', '配置名称', $menu['info'])->autofocus(1);
        $formbuider[] = $this->builder->input('desc', '配置简介', $menu['desc']);
        switch ($menu['type']) {
            case 'text':
                $menu['value'] = json_decode($menu['value'], true);
                $formbuider[] = $this->builder->select('input_type', '类型', $menu['input_type'])->setOptions([
                    ['value' => 'input', 'label' => '文本框']
                    , ['value' => 'dateTime', 'label' => '时间']
                    , ['value' => 'color', 'label' => '颜色']
                    , ['value' => 'number', 'label' => '数字']
                ]);
                //输入框验证规则
                if (!$menu['is_store']) {
                    $formbuider[] = $this->builder->input('value', '默认值', $menu['value']);
                }
                if (!empty($menu['required'])) {
                    $formbuider[] = $this->builder->number('width', '文本框宽(%)', (int)$menu['width']);
                    $formbuider[] = $this->builder->input('required', '验证规则', $menu['required'])->placeholder('多个请用,隔开例如：required:true,url:true');
                }
                break;
            case 'textarea':
                $menu['value'] = json_decode($menu['value'], true);
                //多行文本
                if (!empty($menu['high'])) {
                    if (!$menu['is_store']) {//友情链接 数据是数组
                        $formbuider[] = $this->builder->textarea('value', '默认值', is_array($menu['value']) ? json_encode($menu['value']) : $menu['value'])->rows(5);
                    }
                    $formbuider[] = $this->builder->number('width', '文本框宽(%)', (int)$menu['width']);
                    $formbuider[] = $this->builder->number('high', '多行文本框高(%)', (int)$menu['high']);
                } else {
                    if (!$menu['is_store']) {
                        $formbuider[] = $this->builder->input('value', '默认值', $menu['value']);
                    }
                }
                break;
            case 'radio':
                $formbuider = array_merge($formbuider, $this->createRadioForm($menu));
                //单选和多选参数配置
                if (!empty($menu['parameter'])) {
                    $formbuider[] = $this->builder->textarea('parameter', '配置参数', $menu['parameter'])->placeholder("参数方式例如:\n1=>白色\n2=>红色\n3=>黑色");
                }
                break;
            case 'checkbox':
                $formbuider = array_merge($formbuider, $this->createCheckboxForm($menu));
                //单选和多选参数配置
                if (!empty($menu['parameter'])) {
                    $formbuider[] = $this->builder->textarea('parameter', '配置参数', $menu['parameter'])->placeholder("参数方式例如:\n1=>白色\n2=>红色\n3=>黑色");
                }
                break;
            case 'upload':
                $formbuider = array_merge($formbuider, $this->createUpoadForm(($menu['upload_type']), $menu));
                //上传类型选择
                if (!empty($menu['upload_type'])) {
                    $formbuider[] = $this->builder->radio('upload_type', '上传类型', $menu['upload_type'])->options([['value' => 1, 'label' => '单图'], ['value' => 2, 'label' => '多图'], ['value' => 3, 'label' => '文件']]);
                }
                break;
        }
        $formbuider[] = $this->builder->number('sort', '排序', (int)$menu['sort'])->min(0);
        $formbuider[] = $this->builder->radio('status', '状态', $menu['status'])->options([['value' => 1, 'label' => '显示'], ['value' => 2, 'label' => '隐藏']]);
        return create_form('编辑字段', $formbuider, $this->url('/setting/config/' . $id), 'PUT');
    }

    /**
     * 字段状态
     * @return array
     */
    public function formStatus(): array
    {
        return [['value' => 1, 'label' => '显示'], ['value' => 2, 'label' => '隐藏']];
    }

    /**
     * 选择文文件类型
     * @return array
     */
    public function uploadType(): array
    {
        return [
            ['value' => 1, 'label' => '单图']
            , ['value' => 2, 'label' => '多图']
            , ['value' => 3, 'label' => '文件']
        ];
    }

    /**
     * 选择文本框类型
     * @return array
     */
    public function textType(): array
    {
        return [
            ['value' => 'input', 'label' => '文本框']
            , ['value' => 'dateTime', 'label' => '时间']
            , ['value' => 'color', 'label' => '颜色']
            , ['value' => 'number', 'label' => '数字']
        ];
    }

    /**
     * 获取创建配置规格表单
     * @param int $type
     * @param int $tab_id
     * @return array
     */
    public function createFormRule(int $type, int $tab_id): array
    {
        /** @var SystemConfigTabServices $service */
        $service = app()->make(SystemConfigTabServices::class);
        $formbuider = [];
        $form_type = '';
        $info_type = [];
        $parameter = [];
        $formbuider[] = $this->builder->radio('is_store', '配置类型', 0)->options([
            ['value' => 0, 'label' => '总后台'],
            ['value' => 1, 'label' => '门店后台']
        ]);
        switch ($type) {
            case 0://文本框
                $form_type = 'text';
                $info_type = $this->builder->select('input_type', '类型')->setOptions($this->textType());
                $parameter[] = $this->builder->input('value', '默认值');
                $parameter[] = $this->builder->number('width', '文本框宽(%)', 100);
                $parameter[] = $this->builder->input('required', '验证规则')->placeholder('多个请用,隔开例如：required:true,url:true');
                break;
            case 1://多行文本框
                $form_type = 'textarea';
                $parameter[] = $this->builder->textarea('value', '默认值');
                $parameter[] = $this->builder->number('width', '文本框宽(%)', 100);
                $parameter[] = $this->builder->number('high', '多行文本框高(%)', 5);
                break;
            case 2://单选框
                $form_type = 'radio';
                $parameter[] = $this->builder->textarea('parameter', '配置参数')->placeholder("参数方式例如:\n1=>男\n2=>女\n3=>保密");
                $parameter[] = $this->builder->input('value', '默认值');
                break;
            case 3://文件上传
                $form_type = 'upload';
                $parameter[] = $this->builder->radio('upload_type', '上传类型', 1)->options($this->uploadType());
                break;
            case 4://多选框
                $form_type = 'checkbox';
                $parameter[] = $this->builder->textarea('parameter', '配置参数')->placeholder("参数方式例如:\n1=>白色\n2=>红色\n3=>黑色");
                break;
            case 5://下拉框
                $form_type = 'select';
                $parameter[] = $this->builder->textarea('parameter', '配置参数')->placeholder("参数方式例如:\n1=>白色\n2=>红色\n3=>黑色");
                break;
        }
        if ($form_type) {
            $formbuider[] = $this->builder->hidden('type', $form_type);
            $formbuider[] = $this->builder->cascader('config_tab_id', '分类', [])->data($service->getSelectForm())->changeOnSelect(true);
            if ($info_type) {
                $formbuider[] = $info_type;
            }
            $formbuider[] = $this->builder->input('info', '配置名称')->autofocus(1);
            $formbuider[] = $this->builder->input('menu_name', '字段变量')->placeholder('例如：site_url');
            $formbuider[] = $this->builder->input('desc', '配置简介');
            $formbuider = array_merge($formbuider, $parameter);
            $formbuider[] = $this->builder->number('sort', '排序', 0)->min(0);
            $formbuider[] = $this->builder->radio('status', '状态', 1)->options($this->formStatus());
        }
        return create_form('添加字段', $formbuider, $this->url('/setting/config'), 'POST');
    }

    /**
     * radio 和 checkbox规则的判断
     * @param $data
     * @return bool
     */
    public function valiDateRadioAndCheckbox($data)
    {
        $option = [];
        $option_new = [];
        $data['parameter'] = str_replace("\r\n", "\n", $data['parameter']);//防止不兼容
        $parameter = explode("\n", $data['parameter']);
        if (count($parameter) < 2) {
            throw new AdminException('请输入正确格式的配置参数');
        }
        foreach ($parameter as $k => $v) {
            if (isset($v) && !empty($v)) {
                $option[$k] = explode('=>', $v);
            }
        }
        if (count($option) < 2) {
            throw new AdminException('请输入正确格式的配置参数');
        }
        $bool = 1;
        foreach ($option as $k => $v) {
            $option_new[$k] = $option[$k][0];
            foreach ($v as $kk => $vv) {
                $vv_num = strlen($vv);
                if (!$vv_num) {
                    $bool = 0;
                }
            }
        }
        if (!$bool) {
            throw new AdminException('请输入正确格式的配置参数');
        }
        $num1 = count($option_new);//提取该数组的数目
        $arr2 = array_unique($option_new);//合并相同的元素
        $num2 = count($arr2);//提取合并后数组个数
        if ($num1 > $num2) {
            throw new AdminException('请输入正确格式的配置参数');
        }
        return true;
    }

    /**
     * 验证参数
     * @param $data
     * @return bool
     */
    public function valiDateValue($data)
    {
        if (!$data || !isset($data['required']) || !$data['required']) {
            return true;
        }
        $valids = explode(',', $data['required']);
        foreach ($valids as $valid) {
            $valid = explode(':', $valid);
            if (isset($valid[0]) && isset($valid[1])) {
                $k = strtolower(trim($valid[0]));
                $v = strtolower(trim($valid[1]));
                switch ($k) {
                    case 'required':
                        if ($v == 'true' && $data['value'] === '') {
                            throw new ValidateException(($data['info'] ?? '') . '请输入默认值');
                        }
                        break;
                    case 'url':
                        if ($v == 'true' && !check_link($data['value'])) {
                            throw new ValidateException(($data['info'] ?? '') . '请输入正确url');
                        }
                        break;
                }
            }
        }
    }

    /**
     * 保存平台电子面单打印信息
     * @param array $data
     * @return bool
     */
    public function saveExpressInfo(array $data)
    {
        if (!is_array($data) || !$data) return false;
        // config_export_id 快递公司id
        // config_export_temp_id 快递公司模板id
        // config_export_com 快递公司编码
        // config_export_to_name 发货人姓名
        // config_export_to_tel 发货人电话
        // config_export_to_address 发货人详细地址
        // config_export_siid 电子面单打印机编号
        foreach ($data as $key => $value) {
            $this->dao->update(['menu_name' => 'config_export_' . $key], ['value' => json_encode($value)]);
        }
        \crmeb\services\SystemConfigService::clear();
        return true;
    }

    /**
     * 获取分享海报 兼容方法
     */
    public function getSpreadBanner()
    {
        //配置
        $banner = sys_config('spread_banner', []);
        if (!$banner) {
            //组合数据
            $banner = sys_data('routine_spread_banner');
            if ($banner) {
                $banner = array_column($banner, 'pic');
                $this->dao->update(['menu_name' => 'spread_banner'], ['value' => json_encode($banner)]);
                \crmeb\services\SystemConfigService::clear();
            }
        }
        return $banner;
    }

    /**
     * 保存wss配置
     * @param int $wssOpen
     * @param string $wssLocalpk
     * @param string $wssLocalCert
     */
    public function saveSslFilePath(int $wssOpen, string $wssLocalpk, string $wssLocalCert)
    {
        $wssFile = root_path() . '.wss';
        $content = <<<WSS
wssOpen = $wssOpen
wssLocalpk = $wssLocalpk
wssLocalCert = $wssLocalCert
WSS;
        try {
            file_put_contents($wssFile, $content);
        } catch (\Throwable $e) {
            throw new ValidateException('保存wss证书失败，失败原因：' . $e->getMessage());
        }
    }

    /**
     * 获取wss配置
     * @param string $key
     * @return array|false|mixed
     */
    public function getSslFilePath(string $key = '')
    {
        $wssFile = root_path() . '.wss';
        try {
            $content = parse_ini_file($wssFile);
        } catch (\Throwable $e) {
            $content = [];
        }
        if (isset($content[$key])) {
            return $content[$key];
        } else {
            return $content;
        }
    }

    /**
     * 检测缩略图水印配置是否更改
     * @param array $post
     * @return bool
     */
    public function checkThumbParam(array $post)
    {
        unset($post['upload_type'], $post['image_watermark_status']);
        /** @var SystemConfigTabServices $systemConfigTabServices */
        $systemConfigTabServices = app()->make(SystemConfigTabServices::class);
        //上传配置->基础配置
        $tab_id = $systemConfigTabServices->getColumn(['eng_title' => 'base_config'], 'id');
        if ($tab_id) {
            $all = $this->dao->getColumn(['config_tab_id' => $tab_id], 'value', 'menu_name');
            if (array_intersect(array_keys($all), array_keys($post))) {
                foreach ($post as $key => $item) {
                    //配置更改删除原来生成的缩略图
                    if (isset($all[$key]) && $item != $all[$key]) {
                        try {
                            FileService::delDir(public_path('uploads/thumb_water'));
                            break;
                        } catch (\Throwable $e) {

                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 变更分销绑定关系模式
     * @param array $post
     * @return bool
     */
    public function checkBrokerageBinding(array $post)
    {
        try {
            $config_data = $post['store_brokerage_binding_status'];
            $config_one = $this->dao->getOne(['menu_name' => 'store_brokerage_binding_status']);
            $config_old = json_decode($config_one['value'], true);
            if ($config_old != 2 && $config_data == 2) {
                //自动解绑上级绑定
                SystemJob::dispatch('resetSpreadTime');
            }
        } catch (\Throwable $e) {
            Log::error('变更分销绑定模式重置绑定时间失败,失败原因:' . $e->getMessage());
            return false;
        }
        return true;
    }


    /**
     * 获取表单
     * @param string $type
     * @return array
     */
    public function getNewFormBuildRule(string $type)
    {
        switch ($type) {
            case 'base'://商城基础设置
                $data = $this->shopBaseFormBuild();
                break;
            case 'trade'://交易设置
                $data = $this->shopTradeFormBuild();
                break;
            case 'pay'://支付设置
                $data = $this->shopPayFormBuild();
                break;
            case 'wechat'://微信设置
                $data = $this->wechatBaseFormBuild();
                break;
            case 'routine'://小程序设置
                $data = $this->routineBaseFormBuild();
                break;
            case 'pc'://pc
                $data = $this->pcBaseFormBuild();
                break;
            case 'app'://app
                $data = $this->appBaseFormBuild();
                break;
            case 'wxopen'://开放平台
                $data = $this->wxOpenBaseFormBuild();
                break;
            case 'third'://第三方配置
                $data = $this->thirdPartyFormBuild();
                break;
            case 'deliver'://发货设置
                $data = $this->deliverFormBuild();
                break;
            case 'recharge'://充值设置
                $data = $this->rechargeFormBuild();
                break;
            case 'svip'://付费会员
                $data = $this->svipFormBuild();
                break;
            case 'invoice'://发票
                $data = $this->invoiceFormBuild();
                break;
            case 'vip'://会员等级
                $data = $this->vipFormBuild();
                break;
            case 'kefu'://客服配置
                $data = $this->kefuFormBuild();
                break;
            case 'integral'://积分设置
                $data = $this->integralFormBuild();
                break;
            case 'distribution'://分销设置
                $data = $this->distributionFormBuild();
                break;
            case 'work'://企业微信设置
                $data = $this->workFormBuild();
                break;
            case 'finance'://门店财务设置
                $data = $this->financeFormBuild();
                break;
            case 'bargain'://砍价设置
                $data = $this->bargainFormBuild();
                break;
            default:
                throw new ValidateException('类型错误');
        }

        return $data;
    }

    /**
     * 获取全部配置
     * @param array $configName
     * @param int $storeId
     * @return array
     */
    public function getConfigAllField(array $configName = [], int $storeId = 0)
    {
        $list = $this->dao->getConfigAllField($configName, $storeId, ['info', 'type', 'value', 'desc', 'parameter']);
        foreach ($list as &$item) {
            $item['value'] = json_decode($item['value'], true);
        }
        $value = [];
        foreach ($configName as $key) {
            $value[$key] = $list[$key] ?? ['info' => '', 'type' => 'text', 'value' => '', 'desc' => '', 'parameter' => ''];
        }
        return $value;
    }


    public function getOptions(string $parameter)
    {
        $parameter = explode("\n", $parameter);
        $options = [];
        foreach ($parameter as $v) {
            if (strstr($v, $this->cuttingStr) !== false) {
                $pdata = explode($this->cuttingStr, $v);
                $options[] = ['label' => $pdata[1], 'value' => (int)$pdata[0]];
            }
        }
        return $options;
    }

    /**
     * 分销设置
     * @return array
     */
    public function distributionFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'brokerage_func_status', 'store_brokerage_statu', 'store_brokerage_price', 'brokerage_bindind',
            'store_brokerage_binding_status', 'store_brokerage_binding_time', 'spread_banner', 'store_brokerage_ratio',
            'store_brokerage_two', 'extract_time', 'is_self_brokerage', 'brokerage_user_status', 'uni_brokerage_price',
            'day_brokerage_price_upper', 'brokerage_type', 'user_extract_min_price', 'user_extract_bank_status',
            'user_extract_wechat_status', 'user_extract_alipay_status', 'user_extract_bank',
            'pay_weixin_client_cert', 'pay_weixin_client_key', 'withdraw_fee'
        ]);

        $build->rule([
            Build::tabs()->option('分销模式', [
                Build::switch('brokerage_func_status', $data['brokerage_func_status']['info'], (int)$data['brokerage_func_status']['value'])
                    ->falseValue('关闭', 0)->trueValue('开启', 1)->control(1, [
                        Build::radio('store_brokerage_statu', $data['store_brokerage_statu']['info'], $data['store_brokerage_statu']['value'])
                            ->info($data['store_brokerage_statu']['desc'])
                            ->options($this->getOptions($data['store_brokerage_statu']['parameter']))
                            ->control(3, [
                                Build::inputNum('store_brokerage_price', $data['store_brokerage_price']['info'], $data['store_brokerage_price']['value'])->info($data['store_brokerage_price']['desc'])
                            ]),
                        Build::radio('brokerage_bindind', $data['brokerage_bindind']['info'], $data['brokerage_bindind']['value'])->info($data['brokerage_bindind']['desc'])->options($this->getOptions($data['brokerage_bindind']['parameter'])),
                        Build::radio('store_brokerage_binding_status', $data['store_brokerage_binding_status']['info'], $data['store_brokerage_binding_status']['value'])
                            ->options($this->getOptions($data['store_brokerage_binding_status']['parameter']))
                            ->control(2, [
                                Build::inputNum('store_brokerage_binding_time', $data['store_brokerage_binding_time']['info'], $data['store_brokerage_binding_time']['value'])->info($data['store_brokerage_binding_time']['desc']),
                            ])->info($data['store_brokerage_binding_status']['desc']),
                        Build::uploadFrame('spread_banner', $data['spread_banner']['info'], $data['spread_banner']['value'])->maxNum(5)->info($data['spread_banner']['desc'])->url('/admin/widget.images/index.html')
                    ])->info($data['brokerage_func_status']['desc']),
            ])->option('返佣设置', [
                Build::inputNum('store_brokerage_ratio', $data['store_brokerage_ratio']['info'], $data['store_brokerage_ratio']['value'])->min(0)->info($data['store_brokerage_ratio']['desc']),
                Build::inputNum('store_brokerage_two', $data['store_brokerage_two']['info'], $data['store_brokerage_two']['value'])->min(0)->info($data['store_brokerage_two']['desc']),
                Build::inputNum('extract_time', $data['extract_time']['info'], $data['extract_time']['value'])->min(0)->info($data['extract_time']['desc']),
                Build::switch('is_self_brokerage', $data['is_self_brokerage']['info'], (int)$data['is_self_brokerage']['value'])
                    ->trueValue('开启', 1)->falseValue('关闭', 0)->info($data['is_self_brokerage']['desc']),
                Build::switch('brokerage_user_status', $data['brokerage_user_status']['info'], (int)$data['brokerage_user_status']['value'])->control(1, [
                    Build::inputNum('uni_brokerage_price', $data['uni_brokerage_price']['info'], $data['uni_brokerage_price']['value'])->min(0)->info($data['uni_brokerage_price']['desc']),
                    Build::inputNum('day_brokerage_price_upper', $data['day_brokerage_price_upper']['info'], $data['day_brokerage_price_upper']['value'])->min(0)->info($data['day_brokerage_price_upper']['desc']),
                ])->trueValue('开启', 1)->falseValue('关闭', 0)->info($data['brokerage_user_status']['desc']),
            ])->option('提现设置', [
                Build::alert('微信提现到零钱为自动到账（需要开通微信企业付款到零钱，并确保配置微信支付证书正确），其他方式均需要手动转账', Alert::WARNING)->showIcon(true),
                Build::radio('brokerage_type', $data['brokerage_type']['info'], $data['brokerage_type']['value'])->options($this->getOptions($data['brokerage_type']['parameter']))->control(1, [
                    Build::uploadImage('pay_weixin_client_cert', $data['pay_weixin_client_cert']['info'], $data['pay_weixin_client_cert']['value'])
                        ->url('/file/upload/1?type=1')->format(config('upload.fileExt'))->headers(['Authori-zation' => app()->request->header('Authori-zation')])
                        ->type('file')->icon('md-add')->info($data['pay_weixin_client_cert']['desc']),
                    Build::uploadImage('pay_weixin_client_key', $data['pay_weixin_client_key']['info'], $data['pay_weixin_client_key']['value'])
                        ->url('/file/upload/1?type=1')->format(config('upload.fileExt'))->headers(['Authori-zation' => app()->request->header('Authori-zation')])
                        ->type('file')->icon('md-add')->info($data['pay_weixin_client_key']['desc']),
                ])->info($data['brokerage_type']['desc']),
                Build::inputNum('user_extract_min_price', $data['user_extract_min_price']['info'], $data['user_extract_min_price']['value'])->info($data['user_extract_min_price']['desc']),
                Build::inputNum('withdraw_fee', $data['withdraw_fee']['info'], $data['withdraw_fee']['value'])->info($data['withdraw_fee']['desc']),
                Build::switch('user_extract_bank_status', $data['user_extract_bank_status']['info'], (int)$data['user_extract_bank_status']['value'])->trueValue('开启', 1)->falseValue('关闭', 0)->control(1, [
                    Build::input('user_extract_bank', $data['user_extract_bank']['info'], $data['user_extract_bank']['value'])->type('textarea')->rows(6)->info($data['user_extract_bank']['desc'])
                ])->info($data['user_extract_bank_status']['desc']),
                Build::switch('user_extract_wechat_status', $data['user_extract_wechat_status']['info'], (int)$data['user_extract_wechat_status']['value'])->info($data['user_extract_wechat_status']['desc'])->trueValue('开启', 1)->falseValue('关闭', 0),
                Build::switch('user_extract_alipay_status', $data['user_extract_alipay_status']['info'], (int)$data['user_extract_alipay_status']['value'])->info($data['user_extract_alipay_status']['desc'])->trueValue('开启', 1)->falseValue('关闭', 0),
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * 积分设置
     * @return array
     */
    public function integralFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'integral_ratio', 'integral_max_num', 'order_give_integral', 'integral_effective_status',
            'integral_effective_time', 'next_clear_month_time', 'next_clear_quarter_time', 'next_clear_year_time'
        ]);

        /** @var UserIntegralServices $userIntergralServices */
        $userIntergralServices = app()->make(UserIntegralServices::class);
        [$next_clear_month_time] = $userIntergralServices->getTime(1);
        [$next_clear_quarter_time] = $userIntergralServices->getTime(2);
        [$next_clear_year_time] = $userIntergralServices->getTime(3);

        $build->rule([
            Build::card('积分设置')->components([
                Build::inputNum('integral_ratio', $data['integral_ratio']['info'], $data['integral_ratio']['value'])->info($data['integral_ratio']['desc'])->min(0),
                Build::inputNum('integral_max_num', $data['integral_max_num']['info'], $data['integral_max_num']['value'])->info($data['integral_max_num']['desc'])->min(0),
                Build::inputNum('order_give_integral', $data['order_give_integral']['info'], $data['order_give_integral']['value'])->info($data['order_give_integral']['desc'])->min(0),
                Build::radio('integral_effective_status', $data['integral_effective_status']['info'], $data['integral_effective_status']['value'])
                    ->info($data['integral_effective_status']['desc'])->control(1, [
                        Build::radio('integral_effective_time', $data['integral_effective_time']['info'], $data['integral_effective_time']['value'])
                            ->info($data['integral_effective_time']['desc'])->control(1, [
                                Build::input('next_clear_month_time', '最近清零时间', $next_clear_month_time ? date('Y-m-d', $next_clear_month_time) : '')->disabled()->info('最近清零时间')
                            ])->control(2, [
                                Build::input('next_clear_quarter_time', '最近清零时间', $next_clear_quarter_time ? date('Y-m-d', $next_clear_quarter_time) : '')->info('最近清零时间')->disabled()
                            ])->control(3, [
                                Build::input('next_clear_year_time', '最近清零时间', $next_clear_year_time ? date('Y-m-d', $next_clear_year_time) : '')->info('最近清零时间')->disabled()
                            ])->options($this->getOptions($data['integral_effective_time']['parameter']))
                    ])->options($this->getOptions($data['integral_effective_status']['parameter'])),
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * 客服配置
     * @return array
     */
    public function kefuFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'customer_type', 'service_feedback', 'tourist_avatar', 'customer_phone', 'customer_url'
        ]);

        $options = $this->getOptions($data['customer_type']['parameter']);

        $build->rule([
            Build::card('客服设置')->components([
                Build::radio('customer_type', $data['customer_type']['info'], $data['customer_type']['value'])
                    ->options($options)->control(0, [
                        Build::uploadFrame('tourist_avatar', $data['tourist_avatar']['info'], $data['tourist_avatar']['value'])->maxNum(5)->info($data['tourist_avatar']['desc'])->url('/admin/widget.images/index.html'),
                        Build::input('service_feedback', $data['service_feedback']['info'], $data['service_feedback']['value'])->type('textarea')->rows(5)->info($data['service_feedback']['desc']),
                    ])->control(1, [
                        Build::input('customer_phone', $data['customer_phone']['info'], $data['customer_phone']['value'])->info($data['customer_phone']['desc'])->validate(StrRules::pattern(StrRules::PHONE_NUMBER)->message('请输入正确的手机号')),
                    ])->control(2, [
                        Build::input('customer_url', $data['customer_url']['info'], $data['customer_url']['value'])->info($data['customer_url']['desc']),
                    ])->info($data['customer_type']['desc']),
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * 等级设置
     * @return array
     */
    public function vipFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'member_func_status', 'member_price_status', 'order_give_exp', 'sign_give_exp', 'invite_user_exp'
        ]);

        $build->rule([
            Build::card('等级设置')->components([
                Build::switch('member_func_status', $data['member_func_status']['info'], (int)$data['member_func_status']['value'])
                    ->falseValue('关闭', 0)->trueValue('开启', 1)->control(1, [
//                        Build::switch('member_price_status', $data['member_price_status']['info'], (int)$data['member_price_status']['value'])
//                            ->falseValue('关闭', 0)->trueValue('开启', 1)->info($data['member_price_status']['desc']),
                        Build::inputNum('order_give_exp', $data['order_give_exp']['info'], $data['order_give_exp']['value'])->info($data['order_give_exp']['desc'])->min(0),
                        Build::inputNum('sign_give_exp', $data['sign_give_exp']['info'], $data['sign_give_exp']['value'])->info($data['sign_give_exp']['desc'])->min(0),
                        Build::inputNum('invite_user_exp', $data['invite_user_exp']['info'], $data['invite_user_exp']['value'])->info($data['invite_user_exp']['desc'])->min(0),
                    ])->info($data['member_func_status']['desc']),
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * 发票
     * @return array
     */
    public function invoiceFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'invoice_func_status', 'special_invoice_status'
        ]);

        $build->rule([
            Build::card('发票设置')->components([
                Build::switch('invoice_func_status', $data['invoice_func_status']['info'], (int)$data['invoice_func_status']['value'])
                    ->falseValue('关闭', 0)->trueValue('开启', 1)->info($data['invoice_func_status']['desc']),
                Build::switch('special_invoice_status', $data['special_invoice_status']['info'], (int)$data['special_invoice_status']['value'])
                    ->falseValue('关闭', 0)->trueValue('开启', 1)->info($data['special_invoice_status']['desc']),
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * 付费会员
     * @return array
     */
    public function svipFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'member_card_status', 'svip_price_status'
        ]);

        //缺少svip会员价格是否展示字段
        $build->rule([
            Build::card('会员设置')->components([
                Build::switch('member_card_status', $data['member_card_status']['info'], (int)$data['member_card_status']['value'])
                    ->falseValue('关闭', 0)->trueValue('开启', 1)->info($data['member_card_status']['desc'])->control(1, [
                        Build::switch('svip_price_status', $data['svip_price_status']['info'], (int)$data['svip_price_status']['value'])->falseValue('关闭', 0)->trueValue('开启', 1)->info($data['svip_price_status']['desc'])
                    ])
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * 充值设置
     * @return array
     */
    public function rechargeFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'balance_func_status', 'recharge_attention', 'recharge_switch', 'store_user_min_recharge'
        ]);

        $build->rule([
            Build::card('充值设置')->components([
                Build::switch('balance_func_status', $data['balance_func_status']['info'], (int)$data['balance_func_status']['value'])->falseValue('关闭', 0)->trueValue('开启', 1)->control(1, [
                    Build::input('recharge_attention', $data['recharge_attention']['info'], $data['recharge_attention']['value'])->rows(5)->type($data['recharge_attention']['type'])->info($data['recharge_attention']['desc']),
                    Build::switch('recharge_switch', $data['recharge_switch']['info'], (int)$data['recharge_switch']['value'])->trueValue('开启', 1)->falseValue('关闭', 0)->info($data['recharge_switch']['desc']),
                    Build::inputNum('store_user_min_recharge', $data['store_user_min_recharge']['info'], $data['store_user_min_recharge']['value'])->info($data['store_user_min_recharge']['desc'])->min(0.01)->max(999999999),
                ])->info($data['balance_func_status']['desc']),
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * 发货设置
     * @return array
     */
    public function deliverFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'store_free_postage', 'offline_postage', 'store_self_mention'
        ]);

        $build->rule([
            Build::card('发货设置')->components([
                Build::switch('whole_free_shipping', '全场包邮', $data['store_free_postage']['value'] > 0 ? 1 : 0)
                    ->falseValue('关闭', 0)->trueValue('开启', 1)->control(1, [
                        Build::inputNum('store_free_postage', $data['store_free_postage']['info'], $data['store_free_postage']['value'])->info($data['store_free_postage']['desc'])->min(0)
                    ])->info('开启全场包邮必须设置包邮金额大于0的金额才能开启'),
                Build::switch('offline_postage', '线下支付是否包邮', $data['store_free_postage']['value'] > 0 ? 1 : 0)
                    ->falseValue('不包邮', 0)->trueValue('包邮', 1)->info($data['store_free_postage']['desc'] ?? ''),
                Build::switch('store_self_mention', '是否开启到店自提', $data['store_self_mention']['value'] > 0 ? 1 : 0)
                    ->falseValue('关闭', 0)->trueValue('开启', 1)->info($data['store_self_mention']['desc'] ?? ''),
            ]),

        ]);

        return $build->toArray();
    }

    /**
     * 第三方配置
     * @return array
     */
    public function thirdPartyFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'pay_success_printing_switch', 'develop_id', 'printing_api_key', 'printing_client_id',
            'terminal_number', 'system_product_copy_type', 'copy_product_apikey', 'logistics_type',
            'system_express_app_code', 'store_config_export_open', 'config_export_to_name', 'config_export_to_tel',
            'tengxun_map_key', 'system_statistics', 'config_export_to_address', 'verify_expire_time', 'erp_open',
            'erp_type', 'jst_appkey', 'jst_appsecret', 'jst_default_shopid', 'jst_login_account', 'jst_login_password'
        ]);

        $build->rule([
            Build::tabs()->option('小票打印配置', [
                Build::switch('pay_success_printing_switch', $data['pay_success_printing_switch']['info'], (int)$data['pay_success_printing_switch']['value'])->control(1, [
                    Build::input('develop_id', $data['develop_id']['info'], $data['develop_id']['value'])->info($data['develop_id']['desc']),
                    Build::input('printing_api_key', $data['printing_api_key']['info'], $data['printing_api_key']['value'])->info($data['printing_api_key']['desc']),
                    Build::input('printing_client_id', $data['printing_client_id']['info'], $data['printing_client_id']['value'])->info($data['printing_client_id']['desc']),
                    Build::input('terminal_number', $data['terminal_number']['info'], $data['terminal_number']['value'])->info($data['terminal_number']['desc']),
                ])->trueValue('打开', 1)->falseValue('关闭', 0),
            ])->option('采集商品配置', [
                Build::radio('system_product_copy_type', $data['system_product_copy_type']['info'], $data['system_product_copy_type']['value'])->control(2, [
                    Build::input('copy_product_apikey', $data['copy_product_apikey']['info'], $data['copy_product_apikey']['value'])->info($data['copy_product_apikey']['desc'])
                ])->options($this->getOptions($data['system_product_copy_type']['parameter']))->info($data['system_product_copy_type']['desc'])
            ])->option('物流查询', [
                Build::radio('logistics_type', $data['logistics_type']['info'], $data['logistics_type']['value'])->control(2, [
                    Build::input('system_express_app_code', $data['system_express_app_code']['info'], $data['system_express_app_code']['value'])->info($data['system_express_app_code']['desc'])
                ])->options($this->getOptions($data['logistics_type']['parameter']))->info($data['logistics_type']['desc'])
            ])->option('电子面单', [
                Build::radio('store_config_export_open', $data['store_config_export_open']['info'], $data['store_config_export_open']['value'])->control(1, [
                    Build::input('config_export_to_name', $data['config_export_to_name']['info'], $data['config_export_to_name']['value'])->info($data['config_export_to_name']['desc']),
                    Build::input('config_export_to_tel', $data['config_export_to_tel']['info'], $data['config_export_to_tel']['value'])->info($data['config_export_to_tel']['desc']),
                    Build::input('config_export_to_address', $data['config_export_to_address']['info'], $data['config_export_to_address']['value'])->info($data['config_export_to_address']['desc']),
                ])->options($this->getOptions($data['store_config_export_open']['parameter']))->info($data['store_config_export_open']['desc'])
            ])->option('地图配置', [
                Build::input('tengxun_map_key', $data['tengxun_map_key']['info'], $data['tengxun_map_key']['value'])->info($data['tengxun_map_key']['desc']),
            ])->option('短信', [
                Build::inputNum('verify_expire_time', $data['verify_expire_time']['info'], $data['verify_expire_time']['value'])->info($data['verify_expire_time']['desc'])->min(0),
            ])->option('统计', [
                Build::input('system_statistics', $data['system_statistics']['info'], $data['system_statistics']['value'])->rows(7)->type('textarea')->info($data['system_statistics']['desc']),
            ])->option('ERP配置', [
                Build::switch('erp_open', $data['erp_open']['info'], (int)$data['erp_open']['value'])->control(1, [
                    Build::radio('erp_type', $data['erp_type']['info'], $data['erp_type']['value'])->control(1, [
                        Build::input('jst_login_account', $data['jst_login_account']['info'], $data['jst_login_account']['value'])->info($data['jst_login_account']['desc']),
                        Build::input('jst_login_password', $data['jst_login_password']['info'], $data['jst_login_password']['value'])->info($data['jst_login_password']['desc']),
                        Build::input('jst_appkey', $data['jst_appkey']['info'], $data['jst_appkey']['value'])->info($data['jst_appkey']['desc']),
                        Build::input('jst_appsecret', $data['jst_appsecret']['info'], $data['jst_appsecret']['value'])->info($data['jst_appsecret']['desc']),
                        Build::input('jst_default_shopid', $data['jst_default_shopid']['info'], $data['jst_default_shopid']['value'])->info($data['jst_default_shopid']['desc']),
                    ])->options($this->getOptions($data['erp_type']['parameter']))->info($data['erp_type']['desc'])
                ])->trueValue('打开', 1)->falseValue('关闭', 0),
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * 微信开放平台
     * @return array
     */
    public function wxOpenBaseFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'wechat_open_app_id', 'wechat_open_app_secret'
        ]);

        $build->rule([
            Build::card('微信开放平台')->components([
                Build::alert('小程序、公众号、PC端登录、APP微信登录或企业微信用户同步必须配置微信开放平台，申请微信开放平台地址：https://open.weixin.qq.com', 'warning')->showIcon(true),
                Build::input('wechat_open_app_id', $data['wechat_open_app_id']['info'], $data['wechat_open_app_id']['value'])->info($data['wechat_open_app_id']['desc']),
                Build::input('wechat_open_app_secret', $data['wechat_open_app_secret']['info'], $data['wechat_open_app_secret']['value'])->info($data['wechat_open_app_secret']['desc']),
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * PC设置
     * @return array
     */
    public function pcBaseFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'pc_logo', 'bast_number', 'first_number', 'product_phone_buy_url', 'contact_number',
            'company_address', 'copyright', 'seo_title', 'site_keywords', 'site_description', 'record_No',
            'wechat_open_app_id', 'wechat_open_app_secret', 'links_open', 'links_list'
        ]);
        $base[] = Build::uploadFrame('pc_logo', $data['pc_logo']['info'], $data['pc_logo']['value'])->info($data['pc_logo']['desc'])->url('/admin/widget.images/index.html');
        foreach (['contact_number', 'company_address', 'copyright', 'seo_title', 'site_keywords', 'site_description', 'record_No'] as $key) {
            $base[] = Build::input($key, $data[$key]['info'], $data[$key]['value'])->info($data[$key]['desc'])->type($data[$key]['type']);
        }
        $open = [];
        foreach (['wechat_open_app_id', 'wechat_open_app_secret'] as $key) {
            $open[] = Build::input($key, $data[$key]['info'], $data[$key]['value'])->info($data[$key]['desc'])->type($data[$key]['type']);
        }
        $build->rule([
            Build::card('基础设置')->components($base),
            Build::card('商品设置')->components([
                Build::inputNum('bast_number', $data['bast_number']['info'], $data['bast_number']['value'])->info($data['bast_number']['desc'])->min(0),
                Build::inputNum('first_number', $data['first_number']['info'], $data['first_number']['value'])->info($data['first_number']['desc'])->min(0),
                Build::radio('product_phone_buy_url', $data['product_phone_buy_url']['info'], $data['product_phone_buy_url']['value'])->info($data['product_phone_buy_url']['desc'])->options($this->getOptions($data['product_phone_buy_url']['parameter'])),
            ]),
            Build::card('微信开放平台')->components($open),
            Build::card('友情链接')->components([
                Build::switch('links_open', $data['links_open']['info'], $data['links_open']['value'])->info($data['links_open']['desc'])->control(1, [
                    Build::diyTable('links_list', $data['links_list']['info'], is_array($data['links_list']['value']) ? $data['links_list']['value'] : [])->info($data['links_list']['desc'])
                        ->column('链接名称', 'name')->column('链接地址', 'url')->column('排序', 'sort', InputNumber::NAME, ['editable' => false]),
                ])->trueValue('开启', 1)->falseValue('关闭', 0),
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * PC设置
     * @return array
     */
    public function appBaseFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'wechat_app_appid', 'wechat_app_appsecret'
        ]);
        $open = [];
        foreach (['wechat_app_appid', 'wechat_app_appsecret'] as $key) {
            $open[] = Build::input($key, $data[$key]['info'], $data[$key]['value'])->info($data[$key]['desc'])->type($data[$key]['type']);
        }
        $build->rule([
            Build::card('微信开放平台')->components($open),
        ]);

        return $build->toArray();
    }

    /**
     * 微信基础配置
     * @return array
     */
    public function wechatBaseFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'share_qrcode', 'spread_share_forever', 'wechat_qrcode', 'wechat_appid',
            'wechat_appsecret', 'wechat_encodingaeskey', 'wechat_token', 'api', 'wechat_encode',
            'wechat_share_img', 'wechat_share_title', 'wechat_share_synopsis'
        ]);

        $build->rule([
            Build::card('公众号开发者信息')->components([
                Build::input('wechat_appid', $data['wechat_appid']['info'], $data['wechat_appid']['value'])->info($data['wechat_appid']['desc']),
                Build::input('wechat_appsecret', $data['wechat_appsecret']['info'], $data['wechat_appsecret']['value'])->info($data['wechat_appsecret']['desc']),
            ]),
            Build::card('服务器配置')->components([
                Build::input('wechat_encodingaeskey', $data['wechat_encodingaeskey']['info'], $data['wechat_encodingaeskey']['value'])->info($data['wechat_encodingaeskey']['desc'])->randAESK(),
                Build::input('wechat_token', $data['wechat_token']['info'], $data['wechat_token']['value'])->info($data['wechat_token']['desc'])->randToken(),
                Build::input('api', $data['api']['info'], sys_config('site_url') . '/api/wechat/serve')->info($data['api']['desc'])->disabled()->copy(),
                Build::radio('wechat_encode', $data['wechat_encode']['info'], $data['wechat_encode']['value'])->vertical(true)->info($data['wechat_encode']['desc'])->options($this->getOptions($data['wechat_encode']['parameter'])),
            ]),
            Build::card('微信公众号')->components([
                Build::radio('share_qrcode', $data['share_qrcode']['info'], $data['share_qrcode']['value'])->info($data['share_qrcode']['desc'])->options($this->getOptions($data['share_qrcode']['parameter'])),
                Build::radio('spread_share_forever', $data['spread_share_forever']['info'], $data['spread_share_forever']['value'])->info($data['spread_share_forever']['desc'])->options($this->getOptions($data['spread_share_forever']['parameter'])),
                Build::uploadFrame('wechat_qrcode', $data['wechat_qrcode']['info'], $data['wechat_qrcode']['value'])->info($data['wechat_qrcode']['desc'])->url('/admin/widget.images/index.html'),
            ]),
            Build::card('首页分享')->components([
                Build::uploadFrame('wechat_share_img', $data['wechat_share_img']['info'], $data['wechat_share_img']['value'])->info($data['wechat_share_img']['desc'])->url('/admin/widget.images/index.html'),
                Build::input('wechat_share_title', $data['wechat_share_title']['info'], $data['wechat_share_title']['value'])->info($data['wechat_share_title']['desc']),
                Build::input('wechat_share_synopsis', $data['wechat_share_synopsis']['info'], $data['wechat_share_synopsis']['value'])->type('textarea')->info($data['wechat_share_synopsis']['desc']),
            ])
        ]);

        return $build->toArray();
    }

    /**
     * 小程序基础配置
     * @return array
     */
    public function routineBaseFormBuild()
    {
        $data = $this->getConfigAllField([
            'routine_appId', 'routine_appsecret', 'routine_contact_type', 'routine_name'
        ]);
        return $data;
    }


    /**
     * 支付
     * @return array
     */
    public function shopPayFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'pay_weixin_open', 'pay_weixin_mchid', 'pay_weixin_key', 'paydir', 'yue_pay_status', 'offline_pay_status',
            'offline_pay_status', 'ali_pay_status', 'ali_pay_appid', 'alipay_public_key', 'alipay_merchant_private_key',
            'pay_weixin_client_cert', 'pay_weixin_client_key'
        ]);
        $site_url = sys_config('site_url', '');
        $build->rule([
            Build::tabs()->option('微信支付', [
                Build::alert('登录微信商户(地址：https://pay.weixin.qq.com，支付授权目录、回调链接：' . $site_url . '； http,https最好都配置)，帮助文档地址：https://doc.crmeb.com/web/pro/crmebprov2/1203', Alert::WARNING)->showIcon(true),
                Build::switch('pay_weixin_open', $data['pay_weixin_open']['info'], (int)$data['pay_weixin_open']['value'])->control(1, [
                    Build::input('pay_weixin_mchid', $data['pay_weixin_mchid']['info'], $data['pay_weixin_mchid']['value'])->info($data['pay_weixin_mchid']['desc']),
                    Build::input('pay_weixin_key', $data['pay_weixin_key']['info'], $data['pay_weixin_key']['value'])->info($data['pay_weixin_key']['desc']),
//                    Build::switch('user_extract_wechat_status', $data['user_extract_wechat_status']['info'], (int)$data['user_extract_wechat_status']['value'])->control(1, [
                    Build::uploadImage('pay_weixin_client_cert', $data['pay_weixin_client_cert']['info'], $data['pay_weixin_client_cert']['value'])
                        ->url('/file/upload/1?type=1')->format(config('upload.fileExt'))->headers(['Authori-zation' => app()->request->header('Authori-zation')])
                        ->type('file')->icon('md-add')->info($data['pay_weixin_client_cert']['desc']),
                    Build::uploadImage('pay_weixin_client_key', $data['pay_weixin_client_key']['info'], $data['pay_weixin_client_key']['value'])
                        ->url('/file/upload/1?type=1')->format(config('upload.fileExt'))->headers(['Authori-zation' => app()->request->header('Authori-zation')])
                        ->type('file')->icon('md-add')->info($data['pay_weixin_client_key']['desc']),
//                    ])->trueValue('开启', 1)->falseValue('关闭', 0)->info($data['user_extract_wechat_status']['desc'])
                    //Build::input('paydir', $data['paydir']['info'], $data['paydir']['value'])->type('textarea')->info($data['paydir']['desc']),
                ])->trueValue('开启', 1)->falseValue('关闭', 0)->info($data['pay_weixin_open']['desc']),
            ])->option('余额支付', [
                Build::switch('yue_pay_status', $data['yue_pay_status']['info'], (int)$data['yue_pay_status']['value'])->trueValue('开启', 1)->falseValue('关闭', 2)->info($data['yue_pay_status']['desc'])
            ])->option('线下支付', [
                Build::switch('offline_pay_status', $data['offline_pay_status']['info'], (int)$data['offline_pay_status']['value'])->trueValue('开启', 1)->falseValue('关闭', 2)->info($data['offline_pay_status']['desc'])
            ])->option('支付宝支付', [
                Build::alert('登录支付宝商家(地址：https://b.alipay.com，需要配置ip白名单以及回调地址回调地址：' . $site_url . ')，帮助文档地址：https://doc.crmeb.com/web/pro/crmebprov2/1204', Alert::WARNING)->showIcon(true),
                Build::switch('ali_pay_status', $data['ali_pay_status']['info'], (int)$data['ali_pay_status']['value'])->control(1, [
                    Build::input('ali_pay_appid', $data['ali_pay_appid']['info'], $data['ali_pay_appid']['value'])->info($data['ali_pay_appid']['desc']),
                    Build::input('alipay_public_key', $data['alipay_public_key']['info'], $data['alipay_public_key']['value'])->rows(5)->type('textarea')->info($data['alipay_public_key']['desc']),
                    Build::input('alipay_merchant_private_key', $data['alipay_merchant_private_key']['info'], $data['alipay_merchant_private_key']['value'])->rows(5)->type('textarea')->info($data['alipay_merchant_private_key']['desc']),
                ])->trueValue('开启', 1)->falseValue('关闭', 0)->info($data['ali_pay_status']['desc']),
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * 砍价设置
     * @return array
     */
    public function bargainFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'bargain_subscribe'
        ]);

        $build->rule([
            Build::card('砍价设置')->components([
                Build::switch('bargain_subscribe', $data['bargain_subscribe']['info'], (int)$data['bargain_subscribe']['value'])->trueValue('开启', 1)->falseValue('关闭', 0)->info($data['bargain_subscribe']['desc']),
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * 交易设置
     * @return array
     */
    public function shopTradeFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'store_stock', 'order_cancel_time', 'order_activity_time', 'order_cancel_time',
            'order_activity_time', 'order_bargain_time', 'order_seckill_time', 'order_pink_time',
            'system_delivery_time', 'refund_name', 'refund_phone', 'refund_address', 'stor_reason',
            'bargain_subscribe'
            // 'store_cashier_order_rate', 'store_recharge_order_rate', 'store_self_order_rate', 'store_svip_order_rate', 'store_writeoff_order_rate'
        ]);
        $timeData[] = Build::alert('营销活动未支付时间如果设置为0将使用默认活动取消时间，优先使用单独活动配置', Alert::WARNING)->showIcon(true);
        foreach (['order_cancel_time', 'order_activity_time',
                     'order_bargain_time', 'order_seckill_time', 'order_pink_time'] as $item) {
            $timeData[] = Build::inputNum($item, $data[$item]['info'], $data[$item]['value'])->info($data[$item]['desc'])->min(0);
        }
        $refund[] = Build::alert('售后处理默认退货地址（门店订单退货默认门店地址）', Alert::WARNING)->showIcon(true);
        foreach (['refund_name', 'refund_phone', 'refund_address', 'stor_reason'] as $key) {
            $rule = Build::input($key, $data[$key]['info'], $data[$key]['value'])->rows(5)->type($data[$key]['type'])->info($data[$key]['desc']);
            if ('refund_phone' === $key) {
                $rule->validate(StrRules::pattern(StrRules::PHONE_NUMBER)->message('请输入正确的手机号码'));
            }
            $refund[] = $rule;
        }
        // $store[] = Build::alert('需要和门店对账，请仔细配置（配置立即生效，不影响已成交订单）', Alert::WARNING)->showIcon(true);
        // foreach (['store_cashier_order_rate', 'store_recharge_order_rate', 'store_self_order_rate', 'store_svip_order_rate', 'store_writeoff_order_rate'] as $key) {
        //     $store[] = Build::inputNum($key, $data[$key]['info'], $data[$key]['value'])->min(0)->info($data[$key]['desc']);
        // }
        $build->rule([
            Build::card('库存警戒')->components([
                Build::inputNum('store_stock', $data['store_stock']['info'], $data['store_stock']['value'])->info($data['store_stock']['desc'])->min(0),
            ]),
            Build::card('订单取消时间')->components($timeData),
            Build::card('自动收货时间')->components([
                Build::alert('输入0为不设置自动收货', Alert::WARNING)->showIcon(true),
                Build::inputNum('system_delivery_time', $data['system_delivery_time']['info'], $data['system_delivery_time']['value'])->info($data['system_delivery_time']['desc'])->min(0),
            ]),
            Build::card('售后退款设置')->components($refund),
            // Build::card('门店手续费设置')->components($store),
        ]);

        return $build->toArray();
    }

    /**
     * 商城首页
     * @return array
     */
    public function shopBaseFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'station_open', 'site_name', 'site_url', 'wap_login_logo', 'site_phone',
            'admin_login_slide', 'site_logo', 'site_logo_square', 'login_logo',
            'h5_avatar', 'store_user_mobile', 'navigation_open', 'cache_config', 'start_login_logo'
        ]);

        $system = [];
        foreach (['site_name', 'site_url', 'site_phone', 'cache_config'] as $key) {
            $system[] = Build::input($key, $data[$key]['info'], $data[$key]['value'])->maxlength($key === 'site_name' ? 20 : null)->info($data[$key]['desc'])->type($data[$key]['type']);
        }
        $setting = [];
        foreach (['site_logo', 'site_logo_square', 'login_logo', 'admin_login_slide', 'start_login_logo'] as $key) {
            $setting[] = Build::uploadFrame($key, $data[$key]['info'], $data[$key]['value'])->url('/admin/widget.images/index.html')->info($data[$key]['desc'])->maxNum($key === 'admin_login_slide' ? 5 : 1);
        }
        $build->rule([
            Build::tabs()->option('系统信息', [
                Build::switch('station_open', $data['station_open']['info'], (int)$data['station_open']['value'])->control(1, $system)->trueValue('开启', 1)->falseValue('关闭', 0)->info($data['station_open']['desc']),
            ])->option('后台设置', $setting)->option('移动端设置', [
                Build::uploadFrame('wap_login_logo', $data['wap_login_logo']['info'], $data['wap_login_logo']['value'])->url('/admin/widget.images/index.html')->info($data['wap_login_logo']['desc']),
                Build::uploadFrame('h5_avatar', $data['h5_avatar']['info'], $data['h5_avatar']['value'])->url('/admin/widget.images/index.html')->info($data['h5_avatar']['desc']),
                Build::radio('store_user_mobile', $data['store_user_mobile']['info'], $data['store_user_mobile']['value'])->options($this->getOptions($data['store_user_mobile']['parameter']))->info($data['store_user_mobile']['desc']),
                Build::switch('navigation_open', $data['navigation_open']['info'], (int)$data['navigation_open']['value'])->trueValue('开启', 1)->falseValue('关闭', 0)->info($data['navigation_open']['desc']),
            ])
//                ->option('首页分享', [
//                Build::uploadFrame('wechat_share_img', $data['wechat_share_img']['info'], $data['wechat_share_img']['value'])->info($data['wechat_share_img']['desc'])->url('/admin/widget.images/index.html'),
//                Build::input('wechat_share_title', $data['wechat_share_title']['info'], $data['wechat_share_title']['value'])->info($data['wechat_share_title']['desc']),
//                Build::input('wechat_share_synopsis', $data['wechat_share_synopsis']['info'], $data['wechat_share_synopsis']['value'])->type('textarea')->info($data['wechat_share_synopsis']['desc']),
//            ]),
//                ->option('APP微信开放平台', [
//                    Build::alert('小程序、公众号、PC端登录、APP微信登录或企业微信用户同步必须配置微信开放平台，申请微信开放平台地址：https://open.weixin.qq.com', 'warning')->showIcon(true),
//                    Build::input('wechat_app_appid', $data['wechat_app_appid']['info'], $data['wechat_app_appid']['value'])->info($data['wechat_app_appid']['desc']),
//                    Build::input('wechat_app_appsecret', $data['wechat_app_appsecret']['info'], $data['wechat_app_appsecret']['value'])->info($data['wechat_app_appsecret']['desc']),
//                ]),
        ]);

        return $build->toArray();
    }

    /**
     * 企业微信配置
     * @return array
     */
    public function workFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'wechat_work_corpid', 'wechat_work_address_secret', 'wechat_work_token', 'wechat_work_aes_key',
            'wechat_work_user_secret', 'wechat_work_build_agent_id', 'wechat_work_build_secret',
        ]);

        $build->rule([
            Build::card('企业微信基础配置')->components([
                Build::input('wechat_work_corpid', $data['wechat_work_corpid']['info'], $data['wechat_work_corpid']['value'])->info($data['wechat_work_corpid']['desc']),
            ]),
            Build::card('企业微信通讯录配置')->components([
                Build::alert('1.请先登录企业微信:https://work.weixin.qq.com 客户与上下游->客户联系->关联微信开发者ID。2.请必须绑定微信开放平台', Alert::WARNING)->closable(true),
                Build::input('wechat_work_address_secret', $data['wechat_work_address_secret']['info'], $data['wechat_work_address_secret']['value'])->info($data['wechat_work_address_secret']['desc']),
                Build::input('wechat_work_token', $data['wechat_work_token']['info'], $data['wechat_work_token']['value'])->randToken()->copy()->info($data['wechat_work_token']['desc']),
                Build::input('wechat_work_aes_key', $data['wechat_work_aes_key']['info'], $data['wechat_work_aes_key']['value'])->randAESK()->copy()->info($data['wechat_work_aes_key']['desc']),
                Build::input('work_address_url', '服务器地址', sys_config('site_url') . '/api/work/serve')->disabled()->copy(),
            ]),
            Build::card('企业微信客户设置')->components([
                Build::input('wechat_work_user_secret', $data['wechat_work_user_secret']['info'], $data['wechat_work_user_secret']['value'])->info($data['wechat_work_user_secret']['desc']),
            ]),
            Build::card('企业微信自建应用设置')->components([
                Build::input('wechat_work_build_agent_id', $data['wechat_work_build_agent_id']['info'], $data['wechat_work_build_agent_id']['value'])->info($data['wechat_work_build_agent_id']['desc']),
                Build::input('wechat_work_build_secret', $data['wechat_work_build_secret']['info'], $data['wechat_work_build_secret']['value'])->info($data['wechat_work_build_secret']['desc']),
            ]),
        ]);

        return $build->toArray();
    }

    /**
     * 门店财务设置
     * @return array
     */
    public function financeFormBuild()
    {
        $build = new Build();
        $build->url('setting/config/save_basics');

        $data = $this->getConfigAllField([
            'store_cashier_order_rate', 'store_self_order_rate', 'store_writeoff_order_rate', 'store_recharge_order_rate',
            'store_svip_order_rate', 'store_extract_min_price', 'store_extract_max_price',
        ]);

        $build->rule([
            Build::tabs()->option('手续费', [
                Build::inputNum('store_cashier_order_rate', $data['store_cashier_order_rate']['info'], $data['store_cashier_order_rate']['value'])
                    ->info($data['store_cashier_order_rate']['desc'])->min(0),
                Build::inputNum('store_self_order_rate', $data['store_self_order_rate']['info'], $data['store_self_order_rate']['value'])
                    ->info($data['store_self_order_rate']['desc'])->min(0),
                Build::inputNum('store_writeoff_order_rate', $data['store_writeoff_order_rate']['info'], $data['store_writeoff_order_rate']['value'])
                    ->info($data['store_writeoff_order_rate']['desc'])->min(0),
                Build::inputNum('store_recharge_order_rate', $data['store_recharge_order_rate']['info'], $data['store_recharge_order_rate']['value'])
                    ->info($data['store_recharge_order_rate']['desc'])->min(0),
                Build::inputNum('store_svip_order_rate', $data['store_svip_order_rate']['info'], $data['store_svip_order_rate']['value'])
                    ->info($data['store_svip_order_rate']['desc'])->min(0),
            ])->option('提现设置', [
                Build::inputNum('store_extract_min_price', $data['store_extract_min_price']['info'], $data['store_extract_min_price']['value'])
                    ->info($data['store_extract_min_price']['desc'])->min(0),
                Build::inputNum('store_extract_max_price', $data['store_extract_max_price']['info'], $data['store_extract_max_price']['value'])
                    ->info($data['store_extract_max_price']['desc'])->min(0),
            ])
        ]);

        return $build->toArray();
    }

    /**
     * 获取缩略图配置
     * @return array
     */
    public function getImageConfig()
    {
        return $this->getConfigAllField([
            'image_watermark_status', 'thumb_big_width', 'thumb_big_height', 'thumb_mid_width',
            'thumb_mid_height', 'thumb_small_width', 'thumb_small_height', 'watermark_type',
            'watermark_text', 'watermark_text_angle', 'watermark_text_color', 'watermark_text_size',
            'watermark_position', 'watermark_image', 'watermark_opacity', 'watermark_rotate',
            'watermark_x', 'watermark_y', 'upload_type'
        ]);
    }

    /**
     * 获取配置
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return sys_config($key, $default);
    }
}
