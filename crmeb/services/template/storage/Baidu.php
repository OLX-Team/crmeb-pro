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

namespace crmeb\services\template\storage;


use crmeb\basic\BaseMessage;

class Baidu extends BaseMessage
{
    /**
     * 初始化
     * @param array $config
     * @return mixed|void
     */
    protected function initialize(array $config)
    {
        parent::initialize($config); //
    }

    /**
     * 发送模板消息
     * @param string $templateId
     * @param array $data
     * @return mixed|void
     */
    public function send(string $templateId, array $data = [])
    {
        //
    }

    /**
     * 添加模板消息
     * @param string $shortId
     * @return mixed|void
     */
    public function add(string $shortId)
    {
        //
    }

    /**
     * 删除模板消息
     * @param string $templateId
     * @return mixed|void
     */
    public function delete(string $templateId)
    {
        //
    }

    /**
     * 模板消息列表
     * @return mixed|void
     */
    public function list()
    {
        //
    }
}
