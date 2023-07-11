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

namespace crmeb\services\wechat;


use crmeb\services\wechat\config\WorkConfig;
use crmeb\services\wechat\groupChat\Client;
use crmeb\services\wechat\groupChat\ServiceProvider;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\BadRequestException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpFoundation\Request;
use think\Response;
use Yurun\Util\Swoole\Guzzle\SwooleHandler;
use EasyWeChat\Work\Application;
use EasyWeChat\Work\ExternalContact\MessageTemplateClient;

/**
 * 企业微信
 * Class Work
 * @package crmeb\services\wechat
 * @method Client groupChat() 加入群聊配置
 * @method MessageTemplateClient groupChatWelcome() 入群欢迎语素材
 */
class Work extends BaseApplication
{
    /**
     * @var WorkConfig
     */
    protected $config;

    /**
     * @var Application[]
     */
    protected $application = [];

    /**
     * @var string
     */
    protected $configHandler;

    /**
     * @var string[]
     */
    protected static $property = [
        'groupChat' => 'external_contact',
        'groupChatWelcome' => 'external_contact_message_template'
    ];

    /**
     * Work constructor.
     */
    public function __construct()
    {
        /** @var WorkConfig config */
        $this->config = app()->make(WorkConfig::class);
        $this->debug = DefaultConfig::value('logger');
    }

    /**
     * 设置获取配置
     * @param string $handler
     * @return $this
     */
    public function setConfigHandler(string $handler)
    {
        $this->configHandler = $handler;
        return $this;
    }

    /**
     * @return Work
     */
    public static function instance()
    {
        return app()->make(static::class);
    }

    /**
     * 获取实例化句柄
     * @param string $type
     * @return Application
     */
    public function application(string $type = WorkConfig::TYPE_USER)
    {
        $config = $this->config->all();
        $config = array_merge($config, $this->config->setHandler($this->configHandler)->getAppConfig($type));
        if (!isset($this->application[$type])) {
            $this->application[$type] = Factory::work($config);
            $this->application[$type]['guzzle_handler'] = SwooleHandler::class;
            $request = request();
            $this->application[$type]->rebind('request', new Request($request->get(), $request->post(), [], [], [], $request->server(), $request->getContent()));
            $this->application[$type]->register(new ServiceProvider());
        }
        return $this->application[$type];
    }

    /**
     * 服务端
     * @return Response
     * @throws BadRequestException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws \ReflectionException
     */
    public static function serve(): Response
    {
        $make = self::instance();
        $make->application()->server->push($make->pushMessageHandler);
        $response = $make->application()->server->serve();
        return response($response->getContent());
    }

    /**
     * 获取用户授权信息
     * @param string $code
     * @return array
     */
    public static function getAuthUserInfo(string $code = '')
    {
        $code = $code ?: request()->param('code');
        if (!$code) {
            throw new WechatException('缺少CODE');
        }
        try {
            $userInfo = self::instance()->application(WorkConfig::TYPE_USER_APP)->oauth->detailed()->userFromCode($code);

            self::logger('获取用户授权信息', compact('code'), $userInfo);

        } catch (\Throwable $e) {
            throw new WechatException($e->getMessage());
        }
        return $userInfo->getRaw();
    }

    /**
     * 创建联系我二维码
     * @param int $channelCodeId
     * @param array $users
     * @param bool $skipVerify
     * @return array
     * @throws InvalidConfigException
     * @throws GuzzleException
     */
    public static function createQrCode(int $channelCodeId, array $users, bool $skipVerify = true)
    {
        $config = [
            'skip_verify' => $skipVerify,
            'state' => 'channelCode-' . $channelCodeId,
            'user' => $users,
        ];

        $response = self::instance()->application()->contact_way->create(2, 2, $config);

        self::logger('创建联系我二维码', $config, $response);

        return $response;
    }

    /**
     * 更新联系我二维码
     * @param int $channelCodeId
     * @param array $users
     * @param string $wxConfigId
     * @param bool $skipVerify
     * @return array
     * @throws InvalidConfigException
     * @throws GuzzleException
     */
    public static function updateQrCode(int $channelCodeId, array $users, string $wxConfigId, bool $skipVerify = true)
    {
        $config = [
            'skip_verify' => $skipVerify,
            'state' => 'channelCode-' . (string)$channelCodeId,
            'user' => $users,
        ];

        $response = self::instance()->application()->contact_way->update($wxConfigId, $config);

        self::logger('更新联系我二维码', compact('config', 'wxConfigId'), $response);

        return $response;
    }

    /**
     * 删除联系我二维码
     * @param string $wxConfigId
     * @return array
     * @throws InvalidConfigException
     * @throws GuzzleException
     */
    public static function deleteQrCode(string $wxConfigId)
    {
        $response = self::instance()->application()->contact_way->delete($wxConfigId);

        self::logger('删除联系我二维码', compact('wxConfigId'), $response);

        return $response;
    }


    /**
     * 添加企业群发消息模板
     * @param array $msg
     * @return WechatResponse
     */
    public static function addMsgTemplate(array $msg)
    {
        $response = self::instance()->application()->external_contact_message->submit($msg);

        self::logger('添加企业群发消息模板', compact('msg'), $response);

        return new WechatResponse($response);
    }

    /**
     * 获取群发成员发送任务列表
     * @param string $msgId
     * @param int|null $limit
     * @param string|null $cursor
     * @return WechatResponse
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public static function getGroupmsgTask(string $msgId, ?int $limit = null, ?string $cursor = null)
    {
        $response = self::instance()->application()->external_contact_message->getGroupmsgTask($msgId, $limit, $cursor);

        self::logger('获取群发成员发送任务列表', compact('msgId', 'limit', 'cursor'), $response);

        return new WechatResponse($response);
    }

    /**
     * 获取企业群发成员执行结果
     * @param string $msgId
     * @param string $userid
     * @param int|null $limit
     * @param string|null $cursor
     * @return WechatResponse
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public static function getGroupmsgSendResult(string $msgId, string $userid, ?int $limit = null, ?string $cursor = null)
    {
        $response = self::instance()->application()->external_contact_message->getGroupmsgSendResult($msgId, $userid, $limit, $cursor);

        self::logger('获取企业群发成员执行结果', compact('msgId', 'userid', 'limit', 'cursor'), $response);

        return new WechatResponse($response);
    }

    /**
     * 创建发送朋友圈任务
     * @param array $param
     * @return WechatResponse
     */
    public static function addMomentTask(array $param)
    {
        $response = self::instance()->application()->external_contact_moment->createTask($param);

        self::logger('创建发送朋友圈任务', compact('param'), $response);

        return new WechatResponse($response);
    }

    /**
     * 获取发送朋友圈任务创建结果
     * @param string $jobId
     * @return WechatResponse
     */
    public static function getMomentTask(string $jobId)
    {
        $response = self::instance()->application()->external_contact_moment->getTask($jobId);

        self::logger('获取发送朋友圈任务创建结果', compact('jobId'), $response);

        return new WechatResponse($response);
    }


    /**
     * 获取客户朋友圈企业发表的列表
     * @param string $momentId
     * @param string $cursor
     * @param int $limit
     * @return WechatResponse
     */
    public static function getMomentTaskInfo(string $momentId, string $cursor = '', int $limit = 500)
    {
        $response = self::instance()->application()->external_contact_moment->getTasks($momentId, $cursor, $limit);

        self::logger('获取客户朋友圈企业发表的列表', compact('momentId', 'cursor', 'limit'), $response);

        return new WechatResponse($response);
    }

    /**
     * 获取客户朋友圈发表时选择的可见范围
     * @param string $momentId
     * @param string $userId
     * @param string $cursor
     * @param int $limit
     * @return WechatResponse
     */
    public static function getMomentCustomerList(string $momentId, string $userId, string $cursor, int $limit = 500)
    {
        $response = self::instance()->application()->external_contact_moment->getCustomers($momentId, $userId, $cursor, $limit);

        self::logger('获取客户朋友圈发表时选择的可见范围', compact('momentId', 'cursor', 'userId', 'limit'), $response);

        return new WechatResponse($response);
    }

    /**
     * 发送应用消息
     * @param array $message
     * @return WechatResponse
     * @throws GuzzleException
     * @throws InvalidConfigException
     */
    public static function sendMessage(array $message)
    {
        $instance = self::instance();

        if (empty($message['agentid'])) {
            $config = $instance->config->getAppConfig(WorkConfig::TYPE_USER_APP);

            if (empty($config['agent_id'])) {
                throw new WechatException('请先配置agent_id');
            }

            $message['agentid'] = $config['agent_id'];
        }

        $response = $instance->application(WorkConfig::TYPE_USER_APP)->message->send($message);

        self::logger('发送应用消息', compact('message'), $response);

        return new WechatResponse($response);
    }

    /**
     * 获取部门列表
     * @return mixed
     */
    public static function getDepartment()
    {
        try {

            $response = self::instance()->application(WorkConfig::TYPE_ADDRESS)->department->list();

            self::logger('获取部门列表', [], $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * 获取部门成员详细信息
     * @param int $departmentId
     * @return array
     */
    public static function getDetailedDepartmentUsers(int $departmentId)
    {
        try {

            $response = self::instance()->application(WorkConfig::TYPE_ADDRESS)->user->getDetailedDepartmentUsers($departmentId, true);

            self::logger('获取部门成员详细信息', compact('departmentId'), $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * 获取通讯录成员详情
     * @param string $userId
     * @return array
     */
    public static function getMemberInfo(string $userId)
    {
        try {

            $response = self::instance()->application(WorkConfig::TYPE_ADDRESS)->user->get($userId);

            self::logger('获取通讯录成员详情', compact('userId'), $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * userid转openid
     * @param string $userId
     * @return mixed|null
     */
    public static function useridByOpenid(string $userId)
    {
        try {

            $response = self::instance()->application(WorkConfig::TYPE_ADDRESS)->user->userIdToOpenid($userId);

            self::logger('userid转openid', compact('userId'), $response);

            return $response['openid'] ?? null;
        } catch (\Throwable $e) {

            self::error($e);

            return null;
        }
    }

    /**
     * 获取某个成员下的客户信息
     * @param string $externalUserID
     * @return array
     */
    public static function getClientInfo(string $externalUserID)
    {
        try {

            $response = self::instance()->application()->external_contact->get($externalUserID);

            self::logger('获取某个成员下的客户信息', compact('externalUserID'), $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * 批量获取客户详情
     * @param array $userids
     * @param string $cursor
     * @param int $limit
     * @return array|mixed|null
     */
    public static function getBatchClientList(array $userids, string $cursor = '', int $limit = 100)
    {
        if ($limit > 100) {
            $limit = 100;
        }
        try {

            $response = self::instance()->application()->external_contact->batchGet($userids, $cursor, $limit);

            self::logger('批量获取客户详情', compact('userids', 'cursor', 'limit'), $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * 获取客户标签
     * @param array $tagIds
     * @param array $groupIds
     * @return array
     */
    public static function getCorpTags(array $tagIds = [], array $groupIds = [])
    {
        try {

            $response = self::instance()->application()->external_contact->getCorpTags($tagIds, $groupIds);

            self::logger('获取客户标签', compact('tagIds', 'groupIds'), $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * 添加客户标签
     * @param string $groupName
     * @param array $tag
     * @return array
     */
    public static function addCorpTag(string $groupName, array $tag = [])
    {
        $params = [
            "group_name" => $groupName,
            "tag" => $tag
        ];
        try {

            $response = self::instance()->application()->external_contact->addCorpTag($params);

            self::logger('添加客户标签', compact('groupName', 'tag'), $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * 编辑客户标签
     * @param string $id
     * @param string $name
     * @param int $order
     * @return array
     */
    public static function updateCorpTag(string $id, string $name, int $order = 1)
    {
        try {

            $response = self::instance()->application()->external_contact->updateCorpTag($id, $name, $order);

            self::logger('编辑客户标签', compact('id', 'name', 'order'), $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * 删除客户标签
     * @param array $tagId
     * @param array $groupId
     * @return array
     */
    public static function deleteCorpTag(array $tagId, array $groupId)
    {
        try {

            $response = self::instance()->application()->external_contact->deleteCorpTag($tagId, $groupId);

            self::logger('删除客户标签', compact('tagId', 'groupId'), $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * 编辑客户标签
     * @param string $userid
     * @param string $externalUserid
     * @param array $addTag
     * @param array $removeTag
     * @return array
     */
    public static function markTags(string $userid, string $externalUserid, array $addTag = [], array $removeTag = [])
    {
        $params = [
            "userid" => $userid,
            "external_userid" => $externalUserid,
            "add_tag" => $addTag,
            "remove_tag" => $removeTag
        ];
        try {

            $response = self::instance()->application()->external_contact->markTags($params);

            self::logger('编辑客户标签', compact('params'), $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * 获取客户群列表
     * @param array $useridList
     * @param string $offset
     * @return array
     */
    public static function getGroupChats(array $useridList = [], string $offset = null)
    {
        $params = [
            "status_filter" => 0,
            "owner_filter" => [
                "userid_list" => $useridList,
            ],
            "cursor" => $offset,
            "limit" => 1000
        ];
        try {

            $response = self::instance()->application()->external_contact->getGroupChats($params);

            self::logger('获取客户群列表', compact('params'), $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * 获取群详情
     * @param string $chatId
     * @return array
     */
    public static function getGroupChat(string $chatId)
    {
        try {

            $response = self::instance()->application()->external_contact->getGroupChat($chatId);

            self::logger('获取群详情', compact('chatId'), $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * 获取群聊数据统计
     * @param int $dayBeginTime
     * @param int $dayEndTime
     * @param array $userIds
     * @return array
     */
    public static function groupChatStatisticGroupByDay(int $dayBeginTime, int $dayEndTime, array $userIds)
    {
        try {

            $response = self::instance()->application()->external_contact_statistics->groupChatStatisticGroupByDay($dayBeginTime, $dayEndTime, $userIds);

            self::logger('获取群聊数据统计', compact('dayBeginTime', 'dayEndTime', 'userIds'), $response);

            return $response;
        } catch (\Throwable $e) {

            self::error($e);

            return [];
        }
    }

    /**
     * 发送欢迎语
     * @param string $welcomeCode
     * @param array $message
     * @return array|WechatResponse
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws GuzzleException
     */
    public static function sendWelcome(string $welcomeCode, array $message)
    {
        $response = self::instance()->application()->external_contact_message->sendWelcome($welcomeCode, $message);

        self::logger('发送欢迎语', compact('welcomeCode', 'message'), $response);

        return new WechatResponse($response);
    }

    /**
     * 上传临时素材
     * @param string $path
     * @param string $type
     * @return WechatResponse
     * @throws InvalidConfigException
     * @throws GuzzleException
     */
    public static function mediaUpload(string $path, string $type = 'image')
    {
        $response = self::instance()->application()->media->upload($type, $path);

        self::logger('上传临时素材', compact('type', 'path'), $response);

        return new WechatResponse($response);
    }

    /**
     * 上传附件资源
     * @param string $path
     * @param string $mediaType
     * @param string $attachmentType
     * @return WechatResponse
     * @throws GuzzleException
     * @throws InvalidConfigException
     */
    public static function mediaUploadAttachment(string $path, string $mediaType = 'image', string $attachmentType = '1')
    {
        $response = self::instance()->application()->external_contact->uploadAttachment($path, $mediaType, $attachmentType);

        self::logger('上传附件资源', compact('path', 'mediaType', 'attachmentType'), $response);

        return new WechatResponse($response);
    }

    /**
     * 获取临时素材
     * @param string $mediaId
     * @return array
     * @throws InvalidConfigException
     * @throws GuzzleException
     */
    public static function getMedia(string $mediaId)
    {
        $response = self::instance()->application()->media->get($mediaId);

        self::logger('获取临时素材', compact('mediaId'), $response);

        return new WechatResponse($response);
    }

    /**
     * 获取jsSDK权限配置
     * @return array|object
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function getJsSDK(string $url = '')
    {
        try {
            $jsSDK = self::instance()->application(WorkConfig::TYPE_USER_APP)->jssdk;
            if ($url) {
                $jsSDK->setUrl($url);
            }
            return $jsSDK->buildConfig(['getCurExternalContact', 'getCurExternalChat', 'getContext', 'chooseImage'], false, false, false);
        } catch (\Throwable $e) {
            return (object)[];
        }
    }

    /**
     * 获取应用配置信息
     * @param string|null $url
     * @return array|string
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function getAgentConfig(string $url = null)
    {

        $instance = self::instance();
        $jsSDK = $instance->application(WorkConfig::TYPE_USER_APP)->jssdk;

        $config = $instance->config->getAppConfig(WorkConfig::TYPE_USER_APP);

        if (empty($config['agent_id'])) {
            throw new WechatException('请先配置agent_id');
        }

        try {
            return $jsSDK->getAgentConfigArray(['getCurExternalContact', 'getCurExternalChat', 'getContext', 'chooseImage'], $config['agent_id'], false, false, [], $url);
        } catch (\Throwable $e) {
            return (object)[];
        }

    }
}
