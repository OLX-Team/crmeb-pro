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

namespace app\services\work;


use app\dao\work\WorkGroupChatAuthDao;
use app\services\BaseServices;
use app\services\user\label\UserLabelServices;
use crmeb\services\wechat\Work;
use crmeb\traits\service\ContactWayQrCode;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;

/**
 * 企业微信自动拉群
 * Class WorkGroupChatAuthServices
 * @package app\services\work
 */
class WorkGroupChatAuthServices extends BaseServices
{

    use ServicesTrait, ContactWayQrCode;

    /**
     * WorkGroupChatAuthServices constructor.
     * @param WorkGroupChatAuthDao $dao
     */
    public function __construct(WorkGroupChatAuthDao $dao)
    {
        $this->dao = $dao;
    }


    /**
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getDataList($where, ['*'], $page, $limit, 'create_time');
        $chatIds = [];
        $labels = [];
        foreach ($list as $item) {
            $chatIds = array_merge($chatIds, $item['chat_id']);
            $labels = array_merge($labels, $item['label'] ?? []);
        }
        $chatIds = array_merge(array_unique(array_filter($chatIds)));
        $labels = array_merge(array_unique(array_filter($labels)));
        /** @var UserLabelServices $userLabelService */
        $userLabelService = app()->make(UserLabelServices::class);
        $labelList = $userLabelService->getColumn([
            ['tag_id', 'in', $labels]
        ], 'label_name', 'tag_id');
        /** @var WorkGroupChatServices $service */
        $service = app()->make(WorkGroupChatServices::class);
        $chatList = $service->getColumn([
            ['chat_id', 'in', $chatIds]
        ], 'name', 'chat_id');
        foreach ($list as &$item) {
            $chatNewList = $labelNewList = [];
            foreach ($chatList as $key => $val) {
                if (in_array($key, $item['chat_id'])) {
                    $chatNewList[] = ['name' => $val, 'chat_id' => $key];
                }
            }
            if ($item['label']) {
                foreach ($labelList as $k => $v) {
                    if (in_array($k, $item['label'])) {
                        $labelNewList[] = ['name' => $v, 'label_id' => $k];
                    }
                }
            }
            $item['chat_list'] = $chatNewList;
            $item['label_list'] = $labelNewList;
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 保存或者修改自动拉群
     * @param array $data
     * @param int $id
     * @return mixed
     */
    public function saveGroupChatAuth(array $data, int $id = 0)
    {
        return $this->transaction(function () use ($data, $id) {
            if ($id) {
                $authInfo = $this->dao->get(['id' => $id], ['config_id']);
                $this->dao->update($id, $data);
                $this->handleGroupChat($data, $id, $authInfo->config_id);
            } else {
                $res = $this->dao->save($data);
                $id = $res->id;
                $this->handleGroupChat($data, $id);
            }
            return $id;
        });
    }

    /**
     * 配置加入群聊并获取二维码
     * @param string $configId
     * @param array $data
     * @param int $id
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function handleGroupChat(array $data, int $id, string $configId = '')
    {
        //设置进群配置
        if ($configId) {
            $qrCode = Work::groupChat()->updateJoinWay($configId, $data['group_name'], $data['chat_id'], 'groupChat-' . $id, (int)$data['auth_group_chat'], (int)$data['group_num']);
        } else {
            $qrCode = Work::groupChat()->addJoinWay($data['group_name'], $data['chat_id'], 'groupChat-' . $id, (int)$data['auth_group_chat'], (int)$data['group_num']);
        }

        if (0 !== $qrCode['errcode']) {
            throw new ValidateException($qrCode['errmsg']);
        }

        if (!$configId) {
            $this->dao->update($id, ['config_id' => $qrCode['config_id']]);
            $configId = $qrCode['config_id'];
        }

        //获取群二维码
        $qrCodeInfo = Work::groupChat()->getJoinWay($configId);
        if (0 !== $qrCodeInfo['errcode']) {
            throw new ValidateException($qrCodeInfo['errmsg']);
        }

        $this->dao->update($id, ['qr_code' => $qrCodeInfo['join_way']['qr_code']]);
    }

    /**
     * 删除客户进群配置
     * @param int $id
     * @return bool
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteGroupChatAuth(int $id)
    {
        $groupChatAuthInfo = $this->dao->get($id);

        if (!$groupChatAuthInfo) {
            throw new ValidateException('删除的群聊配置不存在');
        }

        //删除入群配置
        if ($groupChatAuthInfo->config_id) {
            $qrCode = Work::groupChat()->deleteJoinWay($groupChatAuthInfo->config_id);
            if (0 !== $qrCode['errcode']) {
                throw new ValidateException($qrCode['errmsg']);
            }
        }

        return $this->dao->destroy($id);
    }

    /**
     * 获取群配置
     * @param int $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGrouChatAuthInfo(int $id)
    {
        $groupChatAuthInfo = $this->dao->get($id);
        if (!$groupChatAuthInfo) {
            throw new ValidateException('删除的群聊配置不存在');
        }
        $groupChatAuthInfo['labelList'] = $groupChatAuthInfo['chatList'] = [];
        if ($groupChatAuthInfo->label) {
            /** @var UserLabelServices $userLabelService */
            $userLabelService = app()->make(UserLabelServices::class);
            $groupChatAuthInfo['labelList'] = $userLabelService->getColumn([
                ['tag_id', 'in', $groupChatAuthInfo->label]
            ], 'label_name,tag_id');
        }
        if ($groupChatAuthInfo->chat_id) {
            /** @var WorkGroupChatServices $service */
            $service = app()->make(WorkGroupChatServices::class);
            $groupChatAuthInfo['chatList'] = $service->getColumn([
                ['chat_id', 'in', $groupChatAuthInfo->chat_id]
            ], 'name,chat_id');
        }
        return $groupChatAuthInfo->toArray();
    }

    /**
     *
     * @param int $groupAuthId
     * @param string $userid
     * @param string $externalUserID
     */
    public function clientAddLabel(int $groupAuthId, string $userid, string $externalUserID)
    {
        $label = $this->dao->value(['id' => $groupAuthId], 'label');

        $resTage = Work::markTags($userid, $externalUserID, $label);
        if (0 !== $resTage['errcode']) {
            throw new ValidateException($resTage['errmsg']);
        }
    }

}
