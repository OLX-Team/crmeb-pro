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

namespace crmeb\services\erp\storage\jushuitan;

use crmeb\exceptions\AdminException;
use crmeb\services\erp\AccessToken;
use crmeb\services\erp\storage\Jushuitan;
use Exception;

class Order
{
    /**
     * token句柄
     * @var AccessToken
     */
    protected $accessToken;

    /*** @var Jushuitan */
    protected $jushuitan;

    /**
     * @param Jushuitan $jushuitan
     */
    public function __construct(AccessToken $accessToken, Jushuitan $jushuitan)
    {
        $this->accessToken = $accessToken;
        $this->jushuitan = $jushuitan;
    }

    /**
     * 订单上传(推荐)
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function ordersUpload(array $data): array
    {
        $url = $this->accessToken->getApiUrl("/open/jushuitan/orders/upload");

        //拼装请求参数
        $params = $this->jushuitan->getParams($data);

        //请求平台接口
        $request = $this->jushuitan->postRequest($url, $params);
        return $request["data"];
    }

    /**
     * 订单查询
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function ordersSingleQuery(array $data): array
    {
        $url = $this->accessToken->getApiUrl("/open/orders/single/query");

        //业务参数
        $biz = [];

        if (isset($data["page_index"])) {
            //int 第几页，从第一页开始，默认1
            $biz["page_index"] = intval($data["page_index"]);
        }
        if (isset($data["page_size"])) {
            //int 每页多少条；默认30条，最大50条
            $biz["page_size"] = intval($data["page_size"]);
        }
        if (isset($data["modified_begin"])) {
            if (empty($data["modified_end"])) {
                throw new AdminException("起始和结束时间必须同时存在");
            }
            //string日志起始时间,起始时间和 结束时间必须同时存在，时间间隔不能超过七天
            $biz["modified_begin"] = strval($data["modified_begin"]);
            $biz["modified_end"] = strval($data["modified_end"]);
        }
        ///list 线上单号号，最大限制20条
        if (isset($data["so_ids"])) {
            $biz["so_ids"] = $data["so_ids"];
        } elseif (empty($biz["modified_begin"]) || empty($biz["modified_end"])) {
            //线上单号与修改时间不能同时为空
            throw new AdminException("线上单号，与修改时间不能同时为空");
        }
        //店铺编号
        if (!empty($data["shop_id"])) {
            $biz["shop_id"] = $data["shop_id"];
        }
        //shop_id为0且is_offline_shop为true查询线下店铺单据  非必填  bool
        if (isset($data["is_offline_shop"]) && !is_null($data["is_offline_shop"])) {
            $biz["is_offline_shop"] = $data["is_offline_shop"];
        }

        if (!empty($data["status"])) {
            $biz["status"] = $data["status"];
        }

        //拼装请求参数
        $params = $this->jushuitan->getParams($biz);

        //请求平台接口
        $request = $this->jushuitan->postRequest($url, $params);
        return $request["data"];
    }

    /**
     * 订单取消-按内部单号取消
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function orderByOIdCancel(array $data): bool
    {
        $url = $this->accessToken->getApiUrl("/open/jushuitan/orderbyoid/cancel");

        //拼装请求参数
        $params = $this->jushuitan->getParams($data);

        //请求平台接口
        $this->jushuitan->postRequest($url, $params);

        return true;
    }

    /**
     * 实际收货上传
     * @param array $list
     * @return array
     * @throws Exception
     */
    public function afterSaleUpload(array $list): array
    {
        $url = $this->accessToken->getApiUrl("/open/aftersale/upload");

        //拼装请求参数
        $params = $this->jushuitan->getParams($list);

        //请求平台接口
        $request = $this->jushuitan->postRequest($url, $params);
        return $request["data"];
    }

}