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

namespace crmeb\traits\service;


use app\dao\BaseDao;
use app\services\work\WorkMediaServices;
use crmeb\services\wechat\ErrorMessage;
use crmeb\services\wechat\WechatResponse;
use crmeb\services\wechat\Work;
use think\exception\ValidateException;

/**
 * 客户联系
 * Trait ContactWayQrCode
 * @package crmeb\traits\service
 * @property BaseDao $dao
 */
trait ContactWayQrCode
{


    /**
     * 检测欢迎语字段
     * @param array $welcomeWords
     * @param int $type
     */
    public function checkWelcome(array $welcomeWords, int $type)
    {
        if (1 === $type) {
            return;
        }

        if (empty($welcomeWords['text']['content']) && empty($welcomeWords['attachments'])) {
            throw new ValidateException('请填写欢迎语');
        }

        if (!empty($welcomeWords['text']['content']) && strlen($welcomeWords['text']['content']) > 3000) {
            throw new ValidateException('内容不能超过4000字');
        }

        foreach ($welcomeWords['attachments'] as $item) {
            switch ($item['msgtype']) {
                case 'image':
                    if (empty($item['image']['pic_url'])) {
                        throw new ValidateException('请上传欢迎语图片');
                    }
                    break;
                case 'link':
                    if (empty($item['link']['title'])) {
                        throw new ValidateException('请填写连接标题');
                    }
                    if (empty($item['link']['url'])) {
                        throw new ValidateException('请填写连接地址');
                    }
                    break;
                case 'miniprogram':
                    if (empty($item['miniprogram']['title'])) {
                        throw new ValidateException('请填写小程序消息标题');
                    }
                    if (empty($item['miniprogram']['appid'])) {
                        throw new ValidateException('请填写小程序Appid');
                    }
                    if (empty($item['miniprogram']['page'])) {
                        throw new ValidateException('请填写小程序页面路径');
                    }
                    if (empty($item['miniprogram']['pic_url'])) {
                        throw new ValidateException('请选择小程序消息封面图');
                    }
                    break;
                case 'video':
                    if (empty($item['video']['url'])) {
                        throw new ValidateException('请上传视频文件');
                    }
                    break;
                case 'file':
                    if (empty($item['file']['url'])) {
                        throw new ValidateException('请上传文件');
                    }
                    break;
            }
        }
    }

    /**
     * 执行创建或者修改【联系我】成员情况
     * @param int $channleId
     * @param array $userIds
     * @param bool $skipVerify
     * @param string|null $wxConfigId
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handleQrCode(int $channleId, array $userIds, bool $skipVerify = true, string $wxConfigId = null)
    {
        if (!$wxConfigId) {
            $qrCodeRes = Work::createQrCode($channleId, $userIds, $skipVerify);
        } else {
            $qrCodeRes = Work::updateQrCode($channleId, $userIds, $wxConfigId, $skipVerify);
        }

        if ($qrCodeRes['errcode'] !== 0) {
            throw new ValidateException(ErrorMessage::getWorkMessage($qrCodeRes['errcode'], $qrCodeRes['errmsg'] ?? '生成企业渠道码失败'));
        }

        if (!$wxConfigId) {
            $this->dao->update($channleId, [
                'qrcode_url' => $qrCodeRes['qr_code'],
                'config_id' => $qrCodeRes['config_id']
            ]);
        }
    }

    /**
     * 创建企业微信群发
     * @param array $externalUserid
     * @param array $attachments
     * @param string $chatType
     * @param string|null $sender
     * @return WechatResponse
     */
    public function sendMsgTemplate(array $externalUserid, array $attachments, string $chatType = 'single', string $sender = null)
    {
        $msg = [
            'chat_type' => $chatType,
            'external_userid' => $externalUserid,
        ];
        if ('group' == $chatType) {
            if (!$sender) {
                throw new ValidateException('群发消息成员userid为必须填写');
            }
        }
        if ($sender) {
            $msg['sender'] = $sender;
        }
        if (empty($msg['external_userid'])) {
            unset($msg['external_userid']);
        }

        //转换欢迎语当中的图片为素材库中
        /** @var WorkMediaServices $mediaService */
        $mediaService = app()->make(WorkMediaServices::class);
        $attachments = $mediaService->resolvingWelcome($attachments);
        $msg = array_merge($msg, $attachments);

        return Work::addMsgTemplate($msg);
    }

    /**
     * 创建发送朋友圈
     * @param array $attachments
     * @param array $userIds
     * @param array $tag
     * @return WechatResponse
     */
    public function addMomentTask(array $attachments, array $userIds = [], array $tag = [])
    {
        //转换欢迎语当中的图片为素材库中
        /** @var WorkMediaServices $mediaService */
        $mediaService = app()->make(WorkMediaServices::class);
        $data = $mediaService->resolvingWelcome($attachments, 1);
        if ($userIds) {
            $data['visible_range']['sender_list']['user_list'] = $userIds;
        }

        if ($tag) {
            $data['visible_range']['external_contact_list']['tag_list'] = $tag;
        }

        return Work::addMomentTask($data);
    }
}
