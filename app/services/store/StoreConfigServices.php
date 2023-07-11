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

namespace app\services\store;


use app\dao\store\StoreConfigDao;
use app\services\BaseServices;

/**
 * Class StoreConfigServices
 * @package app\services\store
 */
class StoreConfigServices extends BaseServices
{

    //打印机配置
    const PRINTER_KEY = [
        'store_terminal_number', 'store_printing_client_id',
        'store_printing_api_key', 'store_develop_id', 'store_pay_success_printing_switch'
    ];
    //快递发货配置
    const EXPRESS_KEY = [
        'store_config_export_id', 'store_config_export_temp_id', 'store_config_export_to_name',
        'store_config_export_to_tel', 'store_config_export_to_address', 'store_config_export_siid', 'store_config_export_open'
    ];

    const CONFIG_TYPE = [
        'store_printing_deploy' => self::PRINTER_KEY,
        'store_electronic_sheet' => self::EXPRESS_KEY
    ];

    /**
     * StoreConfigServices constructor.
     * @param StoreConfigDao $dao
     */
    public function __construct(StoreConfigDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存或者更新门店配置
     * @param array $data
     * @param int $storeId
     */
    public function saveConfig(array $data, int $storeId)
    {
        $config = [];
        foreach ($data as $key => $value) {
            if ($this->dao->count(['key_name' => $key, 'store_id' => $storeId])) {
                $this->dao->update(['key_name' => $key, 'store_id' => $storeId], ['value' => json_encode($value)]);
            } else {
                $config[] = [
                    'key_name' => $key,
                    'store_id' => $storeId,
                    'value' => json_encode($value)
                ];
            }
        }
        if ($config) {
            $this->dao->saveAll($config);
        }
    }

    /**
     * 获取配置
     * @param int $storeId
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function getConfig(int $storeId, string $key, $default = null)
    {
        $value = $this->dao->value(['key_name' => $key, 'store_id' => $storeId], 'value');
        return is_null($value) ? $default : json_decode($value, true);
    }

    /**
     * @param int $storeId
     * @param array $key
     */
//    public function getConfigAll(int $storeId, array $key)
//    {
//        $confing = $this->dao->search()->whereIn('key_name', $key)->where('store_id', $storeId)->column('value', 'key_name');
//        return array_map(function ($item) {
//            return json_decode($item, true);
//        }, $confing);
//    }

}
