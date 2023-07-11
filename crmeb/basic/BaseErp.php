<?php


namespace crmeb\basic;

use crmeb\services\erp\AccessToken;

abstract class BaseErp extends BaseStorage
{
    /**
     * token句柄
     * @var AccessToken
     */
    protected $accessToken;

    /**
     * 驱动类型
     * @var string
     */
    protected $name;

    /**
     * 配置文件名
     * @var string
     */
    protected $configFile;

    /**
     * BaseErp constructor.
     * @param string      $name
     * @param AccessToken $accessToken
     * @param string      $configFile
     */
    public function __construct(string $name, AccessToken $accessToken, string $configFile)
    {
        parent::__construct($name, [], $configFile);
        $this->accessToken = $accessToken;
    }

    /**
     * 初始化
     * @param array $config
     * @return mixed|void
     */
    protected function initialize(array $config = [])
    {
//        parent::initialize($config);
    }
}