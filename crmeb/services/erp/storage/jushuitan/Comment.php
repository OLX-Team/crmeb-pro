<?php


namespace crmeb\services\erp\storage\jushuitan;


use crmeb\services\erp\AccessToken;
use crmeb\services\erp\storage\Jushuitan;

/**
 * 公用接口
 * Class Comment
 * @package crmeb\services\erp\storage\jushuitan
 */
class Comment
{
    /**
     * token句柄
     * @var AccessToken
     */
    protected $accessToken;

    /*** @var Jushuitan */
    protected $jushuitan;

    /**
     * Comment constructor.
     * @param AccessToken $accessToken
     * @param Jushuitan $jushuitan
     */
    public function __construct(AccessToken $accessToken, Jushuitan $jushuitan)
    {
        $this->accessToken = $accessToken;
        $this->jushuitan = $jushuitan;
    }

    /**
     * 获取商铺列表
     * @param int $page
     * @param int $limit
     * @return mixed
     * @throws \Exception
     */
    public function getShopList(int $page = 1, int $limit = 10)
    {
        $api = $this->accessToken->getApiUrl('/open/shops/query');
        $biz['items'] = ['page_index' => $page, 'page_size' => $limit];
        $params = $this->jushuitan->getParams($biz);
        return $this->jushuitan->postRequest($api, $params);
    }
}
