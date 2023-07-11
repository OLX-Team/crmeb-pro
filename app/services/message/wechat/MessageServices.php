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


use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\combination\StorePinkServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\BaseServices;
use app\services\other\QrcodeServices;
use app\services\product\product\StoreProductServices;
use app\services\user\LoginServices;
use app\services\user\UserCardServices;
use app\services\user\UserServices;
use app\services\wechat\WechatReplyServices;
use app\services\wechat\WechatUserServices;
use crmeb\services\CacheService;
use crmeb\services\SystemConfigService;
use crmeb\services\wechat\Messages;
use crmeb\services\wechat\OfficialAccount;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use EasyWeChat\Kernel\Exceptions\RuntimeException;
use EasyWeChat\Kernel\Messages\Image;
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\Text;
use EasyWeChat\Kernel\Messages\Transfer;
use EasyWeChat\Kernel\Messages\Voice;
use think\facade\Log;

/**
 * Class MessageServices
 * @package app\services\message\wechat
 */
class MessageServices extends BaseServices
{
    /**
     * 事件处理
     * @param $qrInfo
     * @param $openid
     * @return array|Image|News|Text|Transfer|Voice|string
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function wechatEvent($qrInfo, $openid)
    {
        $response = Messages::transfer();
        $thirdType = explode('-', $qrInfo['third_type']);
        $baseUrl = sys_config('site_url');
        switch (strtolower($thirdType[0])) {
            case 'spread':
                try {
                    $spreadUid = $qrInfo['third_id'];
                    /** @var WechatUserServices $wechatUser */
                    $wechatUser = app()->make(WechatUserServices::class);
                    $uid = $wechatUser->getFieldValue($openid, 'openid', 'uid');
                    /** @var UserServices $userService */
                    $userService = app()->make(UserServices::class);
                    $userInfo = $userService->get($uid);
                    if ($userInfo && $spreadUid != $uid && !$userInfo['spread_uid']) {
                        /** @var LoginServices $loginService */
                        $loginService = app()->make(LoginServices::class);
                        if ($loginService->updateUserInfo(['code' => $spreadUid], $userInfo)) {
                            $response = '绑定推荐人失败!';
                        }
                    }
                    $data = SystemConfigService::more(['site_name', 'site_logo', 'site_name']);
                    $wechatNews['title'] = $data['site_name'] ?? '';
                    $wechatNews['image'] = $data['site_logo'] ?? '';
                    $wechatNews['description'] = $data['site_name'] ?? '';
                    $wechatNews['url'] = $baseUrl . '/pages/index/index';
                    $messages = Messages::newMessage($wechatNews);
                    OfficialAccount::staffService()->message($messages)->to($openid)->send();
                } catch (\Exception $e) {
                    $response = $e->getMessage();
                }
                break;
            case 'reply':
                /** @var WechatReplyServices $replyServices */
                $replyServices = app()->make(WechatReplyServices::class);
                $data = $replyServices->get($qrInfo['third_id']);
                if ($data) {
                    $response = $replyServices->replyDataByMessage($data->toArray());
                }
                break;
            case 'product':
                /** @var StoreProductServices $productService */
                $productService = app()->make(StoreProductServices::class);
                $productInfo = $productService->get($thirdType[1] ?? 0);
                $wechatNews['title'] = $productInfo->store_name;
                $wechatNews['image'] = $productInfo->image;
                $wechatNews['description'] = $productInfo->store_info;
                $wechatNews['url'] = $baseUrl . '/pages/goods_details/index?id=' . $thirdType[1];
                $messages = Messages::newMessage($wechatNews);
                OfficialAccount::staffService()->message($messages)->to($openid)->send();
                break;
            case 'combination':
                /** @var StoreCombinationServices $combinationService */
                $combinationService = app()->make(StoreCombinationServices::class);
                $productInfo = $combinationService->get($thirdType[1] ?? 0);
                $wechatNews['title'] = $productInfo->title;
                $wechatNews['image'] = $productInfo->image;
                $wechatNews['description'] = $productInfo->info;
                $wechatNews['url'] = $baseUrl . '/pages/activity/goods_combination_details/index?id=' . $thirdType[1];
                $messages = Messages::newMessage($wechatNews);
                OfficialAccount::staffService()->message($messages)->to($openid)->send();
                break;
            case 'seckill':
                /** @var StoreSeckillServices $seckillService */
                $seckillService = app()->make(StoreSeckillServices::class);
                $productInfo = $seckillService->get($thirdType[1] ?? 0);
                $wechatNews['title'] = $productInfo->title;
                $wechatNews['image'] = $productInfo->image;
                $wechatNews['description'] = $productInfo->info;
                $wechatNews['url'] = $baseUrl . '/pages/activity/goods_seckill_details/index?id=' . $thirdType[1] . '&time=' . $thirdType[2] . '&status=' . $thirdType[3];
                $messages = Messages::newMessage($wechatNews);
                OfficialAccount::staffService()->message($messages)->to($openid)->send();
                break;
            case 'bargain':
                /** @var StoreBargainServices $bargainService */
                $bargainService = app()->make(StoreBargainServices::class);
                $productInfo = $bargainService->get($thirdType[1] ?? 0);
                $wechatNews['title'] = $productInfo->title;
                $wechatNews['image'] = $productInfo->image;
                $wechatNews['description'] = $productInfo->info;
                $wechatNews['url'] = $baseUrl . '/pages/activity/goods_bargain_details/index?id=' . $thirdType[1] . '&bargain=' . $thirdType[2];
                $messages = Messages::newMessage($wechatNews);
                OfficialAccount::staffService()->message($messages)->to($openid)->send();
                break;
            case 'pink':
                /** @var StorePinkServices $pinkService */
                $pinkService = app()->make(StorePinkServices::class);
                /** @var StoreCombinationServices $combinationService */
                $combinationService = app()->make(StoreCombinationServices::class);
                $pinktInfo = $pinkService->get($thirdType[1]);
                $productInfo = $combinationService->get($pinktInfo->cid);
                $wechatNews['title'] = $productInfo->title;
                $wechatNews['image'] = $productInfo->image;
                $wechatNews['description'] = $productInfo->info;
                $wechatNews['url'] = $baseUrl . '/pages/activity/goods_combination_status/index?id=' . $thirdType[1];
                $messages = Messages::newMessage($wechatNews);
                OfficialAccount::staffService()->message($messages)->to($openid)->send();
                break;
            case 'lucklottery':
                try {
                    $lottery = $qrInfo['lottery'] ?? [];
                    $wechatNews['title'] = $lottery['name'] ?? '关注成功，立即参与抽奖';
                    $wechatNews['image'] = $lottery['image'] ?? '';
                    $wechatNews['description'] = $lottery['name'] ?? '关注成功，获得一次抽奖机会';
                    $wechatNews['url'] = $baseUrl . '/pages/activity/lottery/grids/index?type=5&spread=' . $thirdType[1] ?? '';
                    $messages = Messages::newMessage($wechatNews);
                    OfficialAccount::staffService()->message($messages)->to($openid)->send();
                } catch (\Exception $e) {
                    \think\facade\Log::error('发送关注抽奖失败：' . $e->getMessage());
                    $response = $e->getMessage();
                }
                break;
        }
        return $response;
    }

    /**
     * 扫码发送图文消息
     * @param $title
     * @param $image
     * @param $info
     * @param $url
     * @param $openId
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws RuntimeException
     */
    public function sendMessage($title, $image, $info, $url, $openId)
    {
        $wechatNews['title'] = $title;
        $wechatNews['image'] = $image;
        $wechatNews['description'] = $info;
        $wechatNews['url'] = $url;
        $message = Messages::newMessage($wechatNews);
        OfficialAccount::staffService()->message($message)->to($openId)->send();
    }

    /**
     * 扫码
     * @param $message
     * @return array|Image|News|Text|Transfer|Voice|string
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function wechatEventScan($message)
    {
        /** @var QrcodeServices $qrcodeService */
        $qrcodeService = app()->make(QrcodeServices::class);
        /** @var WechatReplyServices $wechatReplyService */
        $wechatReplyService = app()->make(WechatReplyServices::class);
        $response = $wechatReplyService->reply('subscribe');
        if ($message['EventKey'] && ($qrInfo = $qrcodeService->getQrcode($message['Ticket'], 'ticket'))) {
            $qrcodeService->scanQrcode($message['Ticket'], 'ticket');
            $response = $this->wechatEvent($qrInfo, $message['FromUserName']);
        }
        $ticket = $message['EventKey'];
        if (strpos($ticket, 'wechat_scan_login:') === 0) {
            $openId = $message['FromUserName'];
            $key = str_replace('wechat_scan_login:', '', $ticket);
            /** @var WechatUserServices $wechatUserSerives */
            $wechatUserSerives = app()->make(WechatUserServices::class);
            $wechatUser = $wechatUserSerives->getWechatUserInfo(['openid' => $openId]);
            if ($wechatUser) {
                CacheService::set('wechat_scan_login:' . $key, $wechatUser['uid']);
            }

        }
        return $response;
    }

    /**
     * 取消关注
     * @param $message
     */
    public function wechatEventUnsubscribe($message)
    {
        /** @var WechatUserServices $wechatUser */
        $wechatUser = app()->make(WechatUserServices::class);
        $wechatUser->unSubscribe($message['FromUserName']);
    }

    /**
     * 公众号关注
     * @param $message
     * @param $spid
     * @return array|Image|News|Text|Transfer|Voice|string
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws RuntimeException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function wechatEventSubscribe($message, $spid)
    {
        /** @var WechatReplyServices $wechatReplyService */
        $wechatReplyService = app()->make(WechatReplyServices::class);
        $response = $wechatReplyService->reply('subscribe');
        if (isset($message['EventKey'])) {
            /** @var QrcodeServices $qrcodeService */
            $qrcodeService = app()->make(QrcodeServices::class);
            if ($message['EventKey'] && ($qrInfo = $qrcodeService->getQrcode($message['Ticket'], 'ticket'))) {
                $qrcodeService->scanQrcode($message['Ticket'], 'ticket');
                $response = $this->wechatEvent($qrInfo, $message['FromUserName']);
            }
        }
        try {
            /** @var WechatUserServices $wechatUserSerives */
            $wechatUserSerives = app()->make(WechatUserServices::class);
            $wechatUserSerives->saveUser($message['FromUserName'], $spid);
        } catch (\Throwable $e) {
            Log::error('关注公众号生成用户失败，原因：' . $e->getMessage() . $e->getFile() . $e->getLine());
        }
        return $response;
    }

    /**
     * 位置 事件
     * @param $message
     * @return string
     */
    public function wechatEventLocation($message)
    {
        //return 'location';
    }

    /**
     * 跳转URL  事件
     * @param $message
     * @return string
     */
    public function wechatEventView($message)
    {
        //return 'view';
    }

    /**
     * 图片 消息
     * @param $message
     * @return string
     */
    public function wechatMessageImage($message)
    {
        //return 'image';
    }

    /**
     * 语音 消息
     * @param $message
     * @return string
     */
    public function wechatMessageVoice($message)
    {
        //return 'voice';
    }

    /**
     * 视频 消息
     * @param $message
     * @return string
     */
    public function wechatMessageVideo($message)
    {
        //return 'video';
    }

    /**
     * 位置  消息
     */
    public function wechatMessageLocation($message)
    {
        //return 'location';
    }

    /**
     * 链接   消息
     * @param $message
     * @return string
     */
    public function wechatMessageLink($message)
    {
        //return 'link';
    }

    /**
     * 其它消息  消息
     */
    public function wechatMessageOther($message)
    {
        //return 'other';
    }

    /**
     * 领取卡券
     * @param $message
     */
    public function wechatEventUserGetCard($message)
    {
        try {
            /** @var UserCardServices $userCardServices */
            $userCardServices = app()->make(UserCardServices::class);
            $userCardServices->userGetCard($message);
        } catch (\Throwable $e) {
            Log::error('领取微信卡券失败，原因：' . $e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 激活卡券
     * @param $message
     */
    public function wechatEventSubmitMembercardUserInfo($message)
    {
        try {
            /** @var UserCardServices $userCardServices */
            $userCardServices = app()->make(UserCardServices::class);
            $userCardServices->userSubmitCard($message);
        } catch (\Throwable $e) {
            Log::error('激活微信卡券失败，原因：' . $e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    /**
     * 删除卡券
     * @param $message
     */
    public function wechatEventUserDelCard($message)
    {
        /** @var UserCardServices $userCardServices */
        $userCardServices = app()->make(UserCardServices::class);
        $userCardServices->userDelCard($message);
    }
}
