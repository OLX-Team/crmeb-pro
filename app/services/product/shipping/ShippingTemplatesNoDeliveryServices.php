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


use app\dao\product\shipping\ShippingTemplatesNoDeliveryDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\utils\Arr;

/**
 * 不送达
 * Class ShippingTemplatesNoDeliveryServices
 * @package app\services\product\shipping
 * @method isNoDelivery($tempId, $cityid) 是否不送达
 */
class ShippingTemplatesNoDeliveryServices extends BaseServices
{
    /**
     * 构造方法
     * ShippingTemplatesNoDeliveryServices constructor.
     * @param ShippingTemplatesNoDeliveryDao $dao
     */
    public function __construct(ShippingTemplatesNoDeliveryDao $dao)
    {
        $this->dao = $dao;
    }


    /**
     * 添加不送达信息
     * @param array $noDeliveryInfo
     * @param int $tempId
     * @return bool|mixed
     */
    public function saveNoDeliveryV1(array $noDeliveryInfo, int $tempId = 0)
    {
        $res = true;
        if ($tempId) {
            if ($this->dao->count(['temp_id' => $tempId])) {
                $res = $this->dao->delete($tempId, 'temp_id');
            }
        }
        $placeList = [];
        mt_srand();
        foreach ($noDeliveryInfo as $item) {
            $uniqid = uniqid('adminapi') . rand(1000, 9999);
            foreach ($item['city_ids'] as $cityId) {
                $placeList [] = [
                    'temp_id' => $tempId,
                    'city_id' => $cityId[count($cityId) - 1],
                    'value' => json_encode($cityId),
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
     * 添加不送达信息
     * @param array $noDeliveryInfo
     * @param int $tempId
     * @return bool|mixed
     */
    public function saveNoDelivery(array $noDeliveryInfo, int $tempId = 0)
    {
        $res = true;
        if ($tempId) {
            if ($this->dao->count(['temp_id' => $tempId])) {
                $res = $this->dao->delete($tempId, 'temp_id');
            }
        }
        $placeList = [];
        mt_srand();
        foreach ($noDeliveryInfo as $item) {
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
    public function getNoDeliveryListV1(int $tempId)
    {
        $freeList = $this->dao->getShippingList(['temp_id' => $tempId]);
        return Arr::formatShipping($freeList);
    }

    /**
     * 获得指定包邮城市地址
     * @param int $tempId
     * @return array
     */
    public function getNoDeliveryList(int $tempId)
    {
        $freeIdList = $this->dao->getShippingGroupArray(['temp_id' => $tempId], 'uniqid', 'uniqid', '');
        $freeData = [];
        $infos = $this->dao->getShippingArray(['uniqid' => $freeIdList, 'temp_id' => $tempId], '*', 'uniqid');
        foreach ($freeIdList as $uniqid) {
            $info = $infos[$uniqid];
            $freeData[] = [
                'place' => $this->getNoDeliveryTemp($uniqid, $info['province_id']),
            ];
        }
        foreach ($freeData as &$item) {
            $item['placeName'] = implode(';', array_column($item['place'], 'name'));
        }
        return $freeData;
    }

    /**
     * 获取不送达的省份
     * @param string $uniqid
     * @param int $provinceId
     * @return array
     */
    public function getNoDeliveryTemp(string $uniqid, int $provinceId)
    {
        /** @var ShippingTemplatesNoDeliveryCityServices $service */
        $service = app()->make(ShippingTemplatesNoDeliveryCityServices::class);
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
        /** @var ShippingTemplatesNoDeliveryCityServices $service */
        $service = app()->make(ShippingTemplatesNoDeliveryCityServices::class);
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
