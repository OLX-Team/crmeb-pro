<?php

namespace crmeb\services\erp\storage\jushuitan;

use crmeb\services\erp\AccessToken;
use crmeb\services\erp\storage\Jushuitan;

class Product extends Jushuitan
{
    /**
     * token句柄
     * @var AccessToken
     */
    protected $accessToken;

    /*** @var Jushuitan */
    protected $jushuitan;

    /**
     * @param AccessToken $accessToken
     * @param Jushuitan $jushuitan
     */
    public function __construct(AccessToken $accessToken, Jushuitan $jushuitan)
    {
        $this->accessToken = $accessToken;
        $this->jushuitan = $jushuitan;
    }

    /**
     * 上传商品
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function updateProduct($data)
    {
        $url = $this->accessToken->getApiUrl("/open/jushuitan/itemsku/upload");

        //业务参数
        $biz['items'] = $data;

        //拼装请求参数
        $params = $this->getParams($biz);

        //请求平台接口
        $request = $this->postRequest($url, $params);

        if ($request['code'] == 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 上传店铺商品
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function updateShopProduct($data)
    {
        $url = $this->accessToken->getApiUrl("/open/jushuitan/skumap/upload");

        //业务参数
        $biz['items'] = $data;

        //拼装请求参数
        $params = $this->getParams($biz);

        //请求平台接口
        $request = $this->postRequest($url, $params);

        if ($request['code'] == 0) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 同步商品
     * @param $spuArr
     * @return mixed
     * @throws \Exception
     */
    public function syncProduct($spuArr)
    {
        $url = $this->accessToken->getApiUrl("/open/mall/item/query");

        //业务参数
        $biz['i_ids'] = $spuArr;

        //拼装请求参数
        $params = $this->getParams($biz);

        //请求平台接口
        $request = $this->postRequest($url, $params);

        //获取ERP商品信息
        return $request["data"];
    }

    /**
     * 库存查询
     * @param string $codeStr
     * @return mixed
     * @throws \Exception
     */
    public function syncStock(string $codeStr)
    {
        $url = $this->accessToken->getApiUrl("/open/inventory/query");

        //业务参数
        $biz = [];

        $biz["sku_ids"] = $codeStr;

        //拼装请求参数
        $params = $this->getParams($biz);

        //请求平台接口
        $request = $this->postRequest($url, $params);
        return $request["data"];
    }
}