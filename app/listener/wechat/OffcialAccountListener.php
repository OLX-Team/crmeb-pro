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

use app\services\message\wechat\MessageServices;
use app\services\other\QrcodeServices;
use app\services\wechat\WechatMessageServices;
use app\services\wechat\WechatReplyServices;
use EasyWeChat\Kernel\Contracts\EventHandlerInterface;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\Transfer;
use EasyWeChat\Kernel\Messages\Voice;

/**
 * 公众号消息处理事件
 * Class OffcialAccountListener
 * @package app\listener\wechat
 */
class OffcialAccountListener implements EventHandlerInterface
{

    /**
     * 事件回调
     * @param null $payload
     * @return array|bool|Image|News|Text|Transfer|Voice|string|void
     */
    public function handle($payload = null)
    {
        try {
            /** @var MessageServices $messageService */
            $messageService = app()->make(MessageServices::class);
            /** @var WechatReplyServices $wechatReplyService */
            $wechatReplyService = app()->make(WechatReplyServices::class);
            /** @var WechatMessageServices $wechatMessage */
            $wechatMessage = app()->make(WechatMessageServices::class);
            $spid = 0;
            if (isset($payload['Ticket'])) {
                /** @var QrcodeServices $qrcodeService */
                $qrcodeService = app()->make(QrcodeServices::class);
                $qrInfo = $qrcodeService->getQrcode($payload['Ticket'], 'ticket');
                if ($qrInfo && isset($qrInfo['third_id'])) $spid = $qrInfo['third_id'];
            }
            $wechatMessage->wechatMessageBefore($payload, $spid);
            switch ($payload['MsgType']) {
                case 'event':
                    switch (strtolower($payload['Event'])) {
                        case 'subscribe':
                            $response = $messageService->wechatEventSubscribe($payload, $spid);
                            break;
                        case 'unsubscribe':
                            $messageService->wechatEventUnsubscribe($payload);
                            break;
                        case 'scan':
                            $response = $messageService->wechatEventScan($payload);
                            break;
                        case 'location':
                            $response = $messageService->wechatEventLocation($payload);
                            break;
                        case 'click':
                            $response = $wechatReplyService->reply($payload['EventKey']);
                            break;
                        case 'view':
                            $response = $messageService->wechatEventView($payload);
                            break;
                        case 'user_get_card'://卡券领取
                            $response = $messageService->wechatEventUserGetCard($payload);
                            break;
                        case 'submit_membercard_user_info'://卡券激活
                            $response = $messageService->wechatEventSubmitMembercardUserInfo($payload);
                            break;
                        case 'user_del_card'://卡券删除
                            $response = $messageService->wechatEventUserDelCard($payload);
                            break;
                    }
                    break;
                case 'text':
                    $response = $wechatReplyService->reply($payload['Content'], $payload['FromUserName']);
                    break;
                case 'image':
                    $response = $messageService->wechatMessageImage($payload);
                    break;
                case 'voice':
                    $response = $messageService->wechatMessageVoice($payload);
                    break;
                case 'video':
                    $response = $messageService->wechatMessageVideo($payload);
                    break;
                case 'location':
                    $response = $messageService->wechatMessageLocation($payload);
                    break;
                case 'link':
                    $response = $messageService->wechatMessageLink($payload);
                    break;
                // ... 其它消息
                default:
                    $response = $messageService->wechatMessageOther($payload);
                    break;
            }
        } catch (\Throwable $e) {
            \think\facade\Log::error(['title' => '微信消息服务端消息执行错误', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
        }
        return $response ?? false;
    }
}
