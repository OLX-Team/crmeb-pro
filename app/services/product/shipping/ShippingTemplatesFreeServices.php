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

namespace app\services\product\shipping;


use app\dao\product\shipping\ShippingTemplatesFreeDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use crmeb\utils\Arr;

/**
 * 包邮
 * Class ShippingTemplatesFreeServices
 * @package app\services\product\shipping
 * @method  delete($id, ?string $key = null) 删除数据
 * @method isFree($tempId, $cityid, $number, $price, $type) 是否可以满足包邮
 * @method isFreeList($tempId, $cityid, $price = 0, string $field = '*', string $key = 'ALL') 是否包邮模版数据列表
 */
class ShippingTemplatesFreeServices extends BaseServices
{
    /**
     * 构造方法
     * ShippingTemplatesFreeServices constructor.
     * @param ShippingTemplatesDao $dao
     */
    public function __construct(ShippingTemplatesFreeDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $tempIds
     * @param array $cityIds
     * @param int $expire
     * @return bool|mixed|null
     */
    public function isFreeListCache(array $tempIds, array $cityIds, int $expire = 60)
    {
        return CacheService::redisHandler('apiShipping')->remember(md5('isFreeList' . json_encode([$tempIds, $cityIds])), function () use ($tempIds, $cityIds) {
            return $this->dao->isFreeList($tempIds, $cityIds, 0, 'temp_id,number,price', 'temp_id');
        }, $expire);
    }

    /**
     * 添加包邮信息
     * @param array $appointInfo
     * @param int $type
     * @param int $tempId
     * @return bool
     * @throws \Exception
     */
    public function saveFreeV1(array $appointInfo, int $type = 0, int $tempId = 0)
    {
        $res = true;
        if ($tempId) {
            if ($this->dao->count(['temp_id' => $tempId])) {
                $res = $this->dao->delete($tempId, 'temp_id');
            }
        }
        $placeList = [];
        mt_srand();
        foreach ($appointInfo as $item) {
            $uniqid = uniqid('adminapi') . rand(1000, 9999);
            foreach ($item['city_ids'] as $cityId) {
                $placeList [] = [
                    'temp_id' => $tempId,
                    'city_id' => $cityId[count($cityId) - 1],
                    'value' => json_encode($cityId),
                    'number' => $item['number'] ?? 0,
                    'price' => $item['price'] ?? 0,
                    'type' => $type,
                    'uniqid' => $uniqid,
                ];
            }
        }
        if (count($placeList)) {
            return $res && $this->dao->saveAll($placeList);
        } else {
            return $res;
        }
    }

    /**
     * 添加包邮信息
     * @param array $appointInfo
     * @param int $type
     * @param int $tempId
     * @return bool
     * @throws \Exception
     */
    public function saveFree(array $appointInfo, int $type = 0, int $tempId = 0)
    {
        $res = true;
        if ($tempId) {
            if ($this->dao->count(['temp_id' => $tempId])) {
                $res = $this->dao->delete($tempId, 'temp_id');
            }
        }
        $placeList = [];
        mt_srand();
        foreach ($appointInfo as $item) {
            if (isset($item['place']) && is_array($item['place'])) {
                $uniqid = uniqid('adminapi') . rand(1000, 9999);
                foreach ($item['place'] as $value) {
                    if (isset($value['children']) && is_array($value['children'])) {
                        foreach ($value['children'] as $vv) {
                            if (!isset($vv['city_id'])) {
                                throw new AdminException('缺少城市id无法保存');
                            }
                            $placeList [] = [
                                'temp_id' => $tempId,
                                'province_id' => $value['city_id'] ?? 0,
                                'city_id' => $vv['city_id'] ?? 0,
                                'number' => $item['a_num'] ?? 0,
                                'price' => $item['a_price'] ?? 0,
                                'type' => $type,
                                'uniqid' => $uniqid,
                            ];
                        }
                    }
                }
            }
        }
        if (count($placeList)) {
            return $res && $this->dao->saveAll($placeList);
        } else {
            return $res;
        }
    }

    /**
     * @param int $tempId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFreeListV1(int $tempId)
    {
        $freeList = $this->dao->getShippingList(['temp_id' => $tempId]);
        return Arr::formatShipping($freeList);
    }

    /**
     * 获得指定包邮城市地址
     * @param int $tempId
     * @return array
     */
    public function getFreeList(int $tempId)
    {
        $freeIdList = $this->dao->getShippingGroupArray(['temp_id' => $tempId], 'uniqid', 'uniqid', '');
        $freeData = [];
        $infos = $this->dao->getShippingArray(['uniqid' => $freeIdList, 'temp_id' => $tempId], '*', 'uniqid');
        foreach ($freeIdList as $uniqid) {
            $info = $infos[$uniqid];
            $freeData[] = [
                'place' => $this->getFreeTemp($uniqid, $info['province_id']),
                'a_num' => $info['number'] ? floatval($info['number']) : 0,
                'a_price' => $info['price'] ? floatval($info['price']) : 0,
            ];
        }
        foreach ($freeData as &$item) {
            $item['placeName'] = implode(';', array_column($item['place'], 'name'));
        }
        return $freeData;
    }

    /**
     * 获取包邮的省份
     * @param string $uniqid
     * @param int $provinceId
     * @return array
     */
    public function getFreeTemp(string $uniqid, int $provinceId)
    {
        /** @var ShippingTemplatesFreeCityServices $service */
        $service = app()->make(ShippingTemplatesFreeCityServices::class);
        $infoList = $service->getUniqidList(['uniqid' => $uniqid]);
        $childrenData = [];
        foreach ($infoList as $item) {
            $childrenData[] = [
                'city_id' => $item['province_id'],
                'name' => $item['name'] ?? '全国',
                'children' => $this->getCityTemp($uniqid, $provinceId)
            ];
        }
        return $childrenData;
    }

    /**
     * 获取市区数据
     * @param string $uniqid
     * @param int $provinceId
     * @return array
     */
    public function getCityTemp(string $uniqid, int $provinceId)
    {
        /** @var ShippingTemplatesFreeCityServices $service */
        $service = app()->make(ShippingTemplatesFreeCityServices::class);
        $infoList = $service->getUniqidList(['uniqid' => $uniqid, 'province_id' => $provinceId], false);
        $childrenData = [];
        foreach ($infoList as $item) {
            $childrenData[] = [
                'city_id' => $item['city_id'],
                'name' => $item['name'] ?? '全国',
            ];
        }
        return $childrenData;
    }

}
