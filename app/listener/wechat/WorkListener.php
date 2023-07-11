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

namespace app\listener\wechat;

use app\services\user\label\UserLabelServices;
use app\services\work\WorkClientServices;
use app\services\work\WorkDepartmentServices;
use app\services\work\WorkGroupChatServices;
use app\services\work\WorkMemberServices;
use EasyWeChat\Kernel\Contracts\EventHandlerInterface;

/**
 * 企业微信服务消息处理
 * Class WorkListener
 * @package app\listener\wechat
 */
class WorkListener implements EventHandlerInterface
{

    public function handle($payload = null)
    {
        $response = null;
        switch ($payload['MsgType']) {
            case 'event':
                switch ($payload['Event']) {
                    case 'change_contact'://通讯录事件
                        $this->changeContactEvent($payload);
                        break;
                    case 'change_external_chat'://客户群事件
                        $this->changeExternalChatEvent($payload);
                        break;
                    case 'change_external_contact'://客户事件
                        $this->externalContactEvent($payload);
                        break;
                    case 'change_external_tag'://客户标签事件
                        $this->changeExternalTagEvent($payload);
                        break;
                    case 'batch_job_result'://异步任务完成通知
                        $this->batchJobResultEvent($payload);
                        break;
                }
                break;
            case 'text'://文本消息
                break;
            case 'image'://图片消息
                break;
            case 'voice'://语音消息
                break;
            case 'video'://视频消息
                break;
            case 'news'://图文消息
                break;
            case 'update_button'://模板卡片更新消息
                break;
            case 'update_template_card'://更新点击用户的整张卡片
                break;
        }
        return $response;
    }


    public function batchJobResultEvent(array $payload)
    {
        switch ($payload['JobType']) {
            case 'sync_user'://增量更新成员
                break;
            case 'replace_user'://全量覆盖成员
                break;
            case 'invite_user'://邀请成员关注
                break;
            case 'replace_party'://全量覆盖部门
                break;
        }
    }

    /**
     * 企业微信通讯录事件
     * @param array $payload
     * @return null
     */
    public function changeContactEvent(array $payload)
    {
        $response = null;
        try {
            switch ($payload['ChangeType']) {
                case 'create_user'://新增成员事件
                    /** @var WorkMemberServices $make */
                    $make = app()->make(WorkMemberServices::class);
                    $make->createMember($payload);
                    break;
                case 'update_user'://更新成员事件
                    /** @var WorkMemberServices $make */
                    $make = app()->make(WorkMemberServices::class);
                    $make->updateMember($payload);
                    break;
                case 'delete_user'://删除成员事件
                    /** @var WorkMemberServices $make */
                    $make = app()->make(WorkMemberServices::class);
                    $make->deleteMember($payload['ToUserName'], $payload['UserID']);
                    break;
                case 'create_party'://新增部门事件
                    /** @var WorkDepartmentServices $make */
                    $make = app()->make(WorkDepartmentServices::class);
                    $make->createDepartment($payload);
                    break;
                case 'update_party'://更新部门事件
                    /** @var WorkDepartmentServices $make */
                    $make = app()->make(WorkDepartmentServices::class);
                    $make->updateDepartment($payload['ToUserName'], (int)$payload['Id'], $payload['Name']);
                    break;
                case 'delete_party'://删除部门事件
                    /** @var WorkDepartmentServices $make */
                    $make = app()->make(WorkDepartmentServices::class);
                    $make->deleteDepartment($payload['ToUserName'], (int)$payload['Id']);
                    break;
                case 'update_tag'://标签成员变更事件

                    break;
            }
        } catch (\Throwable $e) {
            \think\facade\Log::error([
                'message' => '企业微信通讯录事件发生错误:' . $e->getMessage(),
                'payload' => $payload,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return $response;
    }

    /**
     * 客户事件
     * @param array $payload
     * @return |null
     */
    public function externalContactEvent(array $payload)
    {
        $response = null;
        try {
            switch ($payload['ChangeType']) {
                case 'add_external_contact'://添加企业客户事件
                    /** @var WorkClientServices $make */
                    $make = app()->make(WorkClientServices::class);
                    $make->createClient($payload);
                    break;
                case 'edit_external_contact'://编辑企业客户事件
                    /** @var WorkClientServices $make */
                    $make = app()->make(WorkClientServices::class);
                    $make->updateClient($payload);
                    break;
                case 'del_external_contact':
                    /** @var WorkClientServices $make */
                    $make = app()->make(WorkClientServices::class);
                    $make->deleteClient($payload);
                    break;
                case 'add_half_external_contact'://外部联系人免验证添加成员事件
                    break;
                case 'del_follow_user'://删除跟进成员事件
                    /** @var WorkClientServices $make */
                    $make = app()->make(WorkClientServices::class);
                    $make->deleteFollowClient($payload);
                    break;
                case 'transfer_fail'://客户接替失败事件
                    break;
            }
        } catch (\Throwable $e) {
            \think\facade\Log::error([
                'message' => '客户事件发生错误:' . $e->getMessage(),
                'payload' => $payload,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return $response;
    }

    /**
     * 客户群事件
     * @param array $payload
     */
    public function changeExternalChatEvent(array $payload)
    {
        try {
            switch ($payload['ChangeType']) {
                case 'create'://客户群创建事件
                    /** @var WorkGroupChatServices $make */
                    $make = app()->make(WorkGroupChatServices::class);
                    $make->saveWorkGroupChat($payload['ToUserName'], $payload['ChatId']);
                    break;
                case 'update'://客户群变更事件
                    /** @var WorkGroupChatServices $make */
                    $make = app()->make(WorkGroupChatServices::class);
                    $make->updateGroupChat($payload);
                    break;
                case 'dismiss'://客户群解散事件
                    /** @var WorkGroupChatServices $make */
                    $make = app()->make(WorkGroupChatServices::class);
                    $make->dismissGroupChat($payload['ToUserName'], $payload['ChatId']);
                    break;
            }
        } catch (\Throwable $e) {
            \think\facade\Log::error([
                'message' => $e->getMessage(),
                'payload' => $payload,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }


    /**
     * 客户标签事件
     * @param array $payload
     */
    public function changeExternalTagEvent(array $payload)
    {
        switch ($payload['ChangeType']) {
            case 'create'://企业客户标签创建事件
                /** @var UserLabelServices $make */
                $make = app()->make(UserLabelServices::class);
                $make->createUserLabel($payload['ToUserName'], $payload['Id'], $payload['TagType']);
                break;
            case 'update'://企业客户标签变更事件
                /** @var UserLabelServices $make */
                $make = app()->make(UserLabelServices::class);
                $make->updateUserLabel($payload['ToUserName'], $payload['Id'], $payload['TagType']);
                break;
            case 'delete'://企业客户标签删除事件
                /** @var UserLabelServices $make */
                $make = app()->make(UserLabelServices::class);
                $make->deleteUserLabel($payload['ToUserName'], $payload['Id'], $payload['TagType']);
                break;
            case 'shuffle'://企业客户标签重排事件
                break;
        }
    }
}
