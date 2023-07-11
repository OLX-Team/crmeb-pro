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

namespace app\services\message\wechat;


use app\services\BaseServices;
use EasyWeChat\Server\Guard;

/**
 * 企业微信事件处理
 * Class WorkMessageServices
 * @package app\services\message\wechat
 * 回调测试地址: https://open.work.weixin.qq.com/wwopen/devtool/interface/combine
 */
class WorkMessageServices extends BaseServices
{

    /**
     * 回调事件处理
     * @param Guard $server
     * 事件处理文档: https://work.weixin.qq.com/api/doc/90000/90135/90237
     */
    public function hook($server)
    {
        $server->setMessageHandler(function ($message) {
            switch ($message->MsgType) {
                case 'event':
                    $this->event($message);
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
            }
        });
    }

    /**
     * 事件处理
     * @param $message
     */
    protected function event($message)
    {
        switch (strtolower($message->Event)) {
            case 'subscribe'://成员关注及取消关注事件

                break;
            case 'unsubscribe'://成员关注及取消关注事件

                break;
            case 'enter_agent'://进入应用

                break;
            case 'location'://上报地理位置

                break;
            case 'batch_job_result'://异步任务完成事件推送

                break;
            case 'change_contact'://通讯录变更事件
                $this->changeContactEvenv($message);
                break;
            case 'click'://点击菜单拉取消息的事件推送

                break;
            case 'view'://点击菜单跳转链接的事件推送

                break;
            case 'scancode_push'://扫码推事件的事件推送

                break;
            case 'scancode_waitmsg'://扫码推事件且弹出“消息接收中”提示框的事件推送

                break;
            case 'pic_sysphoto'://弹出系统拍照发图的事件推送

                break;
            case 'pic_photo_or_album'://弹出拍照或者相册发图的事件推送

                break;
            case 'pic_weixin'://弹出微信相册发图器的事件推送

                break;
            case 'location_select'://弹出地理位置选择器的事件推送

                break;
            case 'open_approval_change'://审批状态通知事件

                break;
            case 'taskcard_click'://任务卡片事件推送

                break;
            case 'share_agent_change'://共享应用事件回调

                break;
        }
    }

    /**
     * 通讯录变更事件处理
     * @param $message
     */
    protected function changeContactEvenv($message)
    {
        switch (strtolower($message->ChangeType)) {
            case 'create_party'://新增部门事件

                break;
            case 'update_party'://更新部门事件

                break;
            case 'delete_party'://删除部门事件

                break;
            case 'update_tag'://标签成员变更事件

                break;
        }
    }
}
