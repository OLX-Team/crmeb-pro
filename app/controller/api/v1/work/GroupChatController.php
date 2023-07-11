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

namespace app\controller\api\v1\work;


use app\Request;
use app\services\work\WorkGroupChatMemberServices;
use app\services\work\WorkGroupChatServices;
use crmeb\services\wechat\config\WorkConfig;

/**
 * 客户群
 * Class GroupChatController
 * @package app\controller\api\v1\work
 */
class GroupChatController extends BaseWorkController
{

    /**
     * GroupChatController constructor.
     * @param Request $request
     * @param WorkGroupChatServices $services
     */
    public function __construct(Request $request, WorkGroupChatServices $services)
    {
        $this->request = $request;
        $this->service = $services;
    }

    /**
     * @param WorkConfig $config
     * @return mixed
     */
    public function getGroupInfo(WorkConfig $config)
    {
        $chatId = $this->request->param('chat_id');
        if (!$chatId) {
            return $this->fail('缺少参数');
        }
        $corpId = $config->get('corpId');
        return $this->success($this->service->getGroupInfo($chatId, $corpId));
    }

    /**
     * 获取群成员列表
     * @param WorkGroupChatMemberServices $services
     * @param $id
     * @return mixed
     */
    public function getChatMemberList(WorkGroupChatMemberServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $name = $this->request->get('name', '');
        return $this->success($services->getChatMemberList((int)$id, $name));
    }
}
