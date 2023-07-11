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
use app\services\work\WorkClientServices;
use app\services\work\WorkGroupChatServices;
use app\services\work\WorkGroupMsgRelationServices;
use app\services\work\WorkGroupMsgSendResultGroupChatServices;
use app\services\work\WorkGroupMsgSendResultServices;
use app\services\work\WorkGroupMsgTaskServices;
use app\services\work\WorkGroupTemplateServices;
use think\facade\App;

/**
 * 群发模板
 * Class GroupTemplate
 * @package app\controller\admin\v1\work
 */
class GroupTemplate extends AuthController
{

    /**
     * GroupTemplate constructor.
     * @param App $app
     * @param WorkGroupTemplateServices $services
     */
    public function __construct(App $app, WorkGroupTemplateServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['time', '', '', 'update_time'],
            ['client_type', ''],
            ['name', ''],
            ['type', 0]
        ]);

        return $this->success($this->services->getGroupTemplate($where));
    }

    /**
     * @param WorkClientServices $services
     * @return mixed
     */
    public function save(WorkClientServices $services)
    {
        $data = $this->request->postMore([
            ['type', 0],
            ['name', ''],
            ['userids', []],
            ['client_type', 0],
            ['where_time', 0],
            ['where_label', []],
            ['where_not_label', []],
            ['template_type', 0],
            ['send_time', 0],
            ['welcome_words', []],
        ]);
        if ($data['template_type']) {
            if (!$data['send_time']) {
                return $this->fail('请设置定时发送时间');
            }
            $data['send_time'] = strtotime($data['send_time']);
        }
        if (!$data['name']) {
            return $this->fail('请设置群发名称');
        }
        if ($data['type']) {
            if (!$data['userids']) {
                return $this->fail('请选择群主');
            }
        } else {
            if ($data['client_type']) {
                if (!$data['where_time'] && !$data['where_label'] && !$data['where_not_label']) {
                    return $this->fail('请设置查询客户条件');
                }
            }
        }
        if (empty($data['welcome_words']['text']['content'])) {
            return $this->fail('请设置群发内容');
        }
        $failList = $this->services->saveGroupTemplate($data);
        if ($failList && $data['template_type']) {
            $failListName = $services->getColumn([
                ['external_userid', 'in', $failList]
            ], 'name');
            return $this->success('立即发送成功,以下客户发送失败:' . implode(',', $failListName));
        }
        return $this->success('保存成功');
    }

    /**
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        return $this->success($this->services->getGroupTemplateInfo((int)$id));
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        if ($this->services->deleteGroupTemplate($id)) {
            return $this->success('删除成功');
        } else {
            return $this->fail('删除失败');
        }
    }

    /**
     * 获取成员列表
     * @param WorkGroupMsgRelationServices $relationServices
     * @param WorkGroupMsgTaskServices $services
     * @param $id
     * @return mixed
     */
    public function memberList(WorkGroupMsgRelationServices $relationServices, WorkGroupMsgTaskServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $where = $this->request->getMore([
            ['user_name', ''],
            ['status', '']
        ]);
        $where['msg_id'] = $relationServices->getColumn(['template_id' => $id], 'msg_id');
        return $this->success($services->getTaksList($where));
    }

    /**
     * 获取客户列表
     * @param WorkGroupMsgRelationServices $relationServices
     * @param WorkGroupMsgSendResultServices $services
     * @param $id
     * @return mixed
     */
    public function clientList(WorkGroupMsgRelationServices $relationServices, WorkGroupMsgSendResultServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $where = $this->request->getMore([
            ['client_name', ''],
            ['status', '']
        ]);
        $where['msg_id'] = $relationServices->getColumn(['template_id' => $id], 'msg_id');
        return $this->success($services->getClientList($where));
    }

    /**
     * 客户群接受详情
     * @param WorkGroupMsgRelationServices $relationServices
     * @param WorkGroupChatServices $services
     * @param $id
     * @return mixed
     */
    public function groupChatList(WorkGroupMsgRelationServices $relationServices, WorkGroupChatServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $where = $this->request->getMore([
            ['owner', []],
            ['status', 0],
            ['name', '']
        ]);
        $where['msg_id'] = $relationServices->getColumn(['template_id' => $id], 'msg_id');
        if (!$where['owner']) {
            $userids = $this->services->value(['id' => $id], 'userids');
            $userids = is_string($userids) ? json_decode($userids, true) : [];
            $where['owner'] = $userids;
        }
        if (!$where['msg_id']) {
            $where['msg_id'] = [0];
        }
        return $this->success($services->groupChatList($where, ['ownerInfo' => function ($query) {
            $query->field(['userid', 'name']);
        }]));
    }

    /**
     * 获取群主发送详情
     * @param WorkGroupMsgRelationServices $relationServices
     * @param WorkGroupMsgSendResultServices $resultServices
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function groupChatOwnerList(WorkGroupMsgRelationServices $relationServices, WorkGroupMsgSendResultServices $resultServices, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $where = $this->request->getMore([
            ['owner', []],
            ['status', 0]
        ]);
        $where['msg_id'] = $relationServices->getColumn(['template_id' => $id], 'msg_id');
        if (!$where['owner']) {
            $userids = $this->services->value(['id' => $id], 'userids');
            $userids = is_string($userids) ? json_decode($userids, true) : [];
            $where['owner'] = $userids;
        }
        if (!$where['msg_id']) {
            $where['msg_id'] = [0];
        }
        return $this->success($resultServices->getGroupChatMsgList($where));
    }

    /**
     * 群发详情列表
     * @param WorkGroupMsgSendResultGroupChatServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOwnerChatList(WorkGroupMsgSendResultGroupChatServices $services)
    {
        $chatIds = $this->request->get('chat_id', '');
        if (!$chatIds) {
            return $this->fail('缺少群聊id');
        }
        $chatIds = explode(',', $chatIds);
        $status = $this->request->get('status', '');
        return $this->success($services->getList(['chat_id' => $chatIds, 'status' => $status]));
    }

    /**
     * 发送提醒消息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendMessage()
    {
        $userid = $this->request->post('userid');
        $time = $this->request->post('time');
        $id = $this->request->post('id');
        if (!$id && !$userid) {
            return $this->fail('缺少参数');
        }
        if (!is_string($userid)) {
            return $this->fail('类型错误userid应为字符串');
        }

        $this->services->sendMessage((int)$id, $userid, $time);

        return $this->success('发送成功');
    }
}
