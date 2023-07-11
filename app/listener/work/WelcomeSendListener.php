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

namespace app\listener\work;


use app\services\work\WorkChannelCodeServices;
use app\services\work\WorkClientServices;
use app\services\work\WorkMediaServices;
use app\services\work\WorkWelcomeServices;
use crmeb\interfaces\ListenerInterface;
use crmeb\services\wechat\Work;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use GuzzleHttp\Exception\GuzzleException;
use think\exception\ValidateException;

/**
 * 发送欢迎语事件
 * Class WelcomeSendListener
 * @package app\listener\work
 */
class WelcomeSendListener implements ListenerInterface
{

    /**
     * @param $event
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws GuzzleException
     */
    public function handle($event): void
    {
        //$welcomeCode只能在20秒内使用
        [$welcomeCode, $state, $clientId, $userId] = $event;

        $channelId = 0;
        if (false !== strstr($state, 'channelCode-')) {
            $channelId = (int)str_replace('channelCode-', '', $state);
        }

        //获取欢迎语
        if ($channelId) {
            /** @var WorkChannelCodeServices $channelService */
            $channelService = app()->make(WorkChannelCodeServices::class);
            $channelInfo = $channelService->get(['id' => $channelId], ['welcome_words', 'welcome_type']);
            if (!$channelInfo->welcome_type) {
                $welcomeWords = is_array($channelInfo->welcome_words) ? $channelInfo->welcome_words : json_decode($channelInfo->welcome_words, true);
            }
            //更新客户数量
            $channelInfo->client_num++;
            $channelInfo->save();
        }

        //渠道码没有欢迎语获取默认员工欢迎语
        if (!isset($welcomeWords)) {
            /** @var WorkWelcomeServices $welcomeService */
            $welcomeService = app()->make(WorkWelcomeServices::class);
            $welcomeWords = $welcomeService->getWorkWelcome($userId);
        }

        //替换客户名称
        if (!empty($welcomeWords['text']['content'])) {
            //客户名称
            /** @var WorkClientServices $clientService */
            $clientService = app()->make(WorkClientServices::class);
            $clientName = $clientService->value(['id' => $clientId], 'name');
            $welcomeWords['text']['content'] = str_replace('##客户名称##', $clientName, $welcomeWords['text']['content']);
        }

        //转换欢迎语当中的图片为素材库中
        /** @var WorkMediaServices $mediaService */
        $mediaService = app()->make(WorkMediaServices::class);
        $welcomeWords = $mediaService->resolvingWelcome($welcomeWords);

        //欢迎语内容和欢迎语消息体都为空直接不发送
        if (empty($welcomeWords['text']['content']) && empty($welcomeWords['attachments'])) {
            return;
        }

        //发送欢迎语
        $reWelocme = Work::sendWelcome($welcomeCode, $welcomeWords);
        if (0 !== $reWelocme['errcode']) {
            throw new ValidateException($reWelocme['errmsg'] ?? '发送欢迎语失败');
        }
    }
}
