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

namespace app\controller\admin\v1\work;


use app\controller\admin\AuthController;
use app\model\work\WorkGroupChatStatistic;
use app\services\work\WorkGroupChatMemberServices;
use app\services\work\WorkGroupChatServices;
use app\services\work\WorkGroupChatStatisticServices;
use think\facade\App;

/**
 * 企业微信群
 * Class GroupChat
 * @package app\controller\admin\v1\work
 */
class GroupChat extends AuthController
{

    /**
     * GroupChat constructor.
     * @param App $app
     * @param WorkGroupChatServices $services
     */
    public function __construct(App $app, WorkGroupChatServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 获取群列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['userids', []],
            ['time', ''],
            ['name', '']
        ]);
        return $this->success($this->services->getList($where));
    }

    /**
     * 同步企业微信群
     * @return mixed
     */
    public function synchGroupChat()
    {
        $this->services->authGroupChat();
        return $this->success('已加入消息队列，请稍后查看');
    }

    /**
     * 群成员
     * @param WorkGroupChatMemberServices $services
     * @param $id
     * @return mixed
     */
    public function chatMember(WorkGroupChatMemberServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        return $this->success($services->getChatMemberList((int)$id));
    }

    /**
     * 客户群统计
     * @param $id
     * @return mixed
     */
    public function chatStatistics($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $time = $this->request->get('time', '');
        return $this->success($this->services->getChatStatistics((int)$id, $time));
    }

    /**
     * 客户群统计列表数据
     * @param WorkGroupChatStatisticServices $services
     * @param $id
     * @return mixed
     */
    public function chatStatisticsList(WorkGroupChatStatisticServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $time = $this->request->get('time', '');
        return $this->success($services->getChatStatisticsList((int)$id, $time));
    }
}
