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

use crmeb\services\wechat\config\OfficialAccountConfig;
use crmeb\services\wechat\config\OpenAppConfig;
use crmeb\services\wechat\config\OpenWebConfig;
use EasyWeChat\BasicService\Url\Client;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\BadRequestException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use EasyWeChat\Kernel\Support\Collection;
use EasyWeChat\OfficialAccount\Application;
use EasyWeChat\OfficialAccount\Card\Card;
use EasyWeChat\OfficialAccount\User\TagClient;
use EasyWeChat\OfficialAccount\User\UserClient;
use GuzzleHttp\Exception\GuzzleException;
use Overtrue\Socialite\Providers\WeChat;
use Psr\Http\Message\ResponseInterface;
use think\Response;
use Yurun\Util\Swoole\Guzzle\SwooleHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * 公众号服务
 * Class OfficialAccount
 * @package crmeb\services\wechat
 * @method \EasyWeChat\OfficialAccount\Material\Client materialService() 永久素材
 * @method \EasyWeChat\BasicService\Media\Client mediaService() 临时素材
 * @method \EasyWeChat\BasicService\QrCode\Client qrcodeService() 微信二维码生成接口
 * @method UserClient userService() 用户接口
 * @method \EasyWeChat\OfficialAccount\CustomerService\Client staffService() 客服管理
 * @method \EasyWeChat\OfficialAccount\Menu\Client menuService() 微信公众号菜单接口
 * @method Client urlService() 短链接生成接口
 * @method WeChat oauthService() 用户授权
 * @method \EasyWeChat\OfficialAccount\TemplateMessage\Client templateService() 模板消息
 * @method Card cardServices() 卡券接口
 * @method TagClient userTagService() 用户标签
 */
class OfficialAccount extends BaseApplication
{

    /**
     * 配置
     * @var OfficialAccountConfig
     */
    protected $config;

    /**
     * @var array
     */
    protected $application;

    /**
     * @var string[]
     */
    protected static $property = [
        'materialService' => 'material',
        'mediaService' => 'media',
        'qrcodeService' => 'qrcode',
        'userService' => 'user',
        'staffService' => 'customer_service',
        'menuService' => 'menu',
        'urlService' => 'url',
        'oauthService' => 'oauth',
        'templateService' => 'template_message',
        'cardServices' => 'card',
        'userTagService' => 'user_tag'
    ];

    /**
     * OfficialAccount constructor.
     */
    public function __construct()
    {
        /** @var OfficialAccountConfig config */
        $this->config = app(OfficialAccountConfig::class);
        $this->debug = DefaultConfig::value('logger');
    }

    /**
     * 初始化
     * @return Application
     */
    public function application()
    {
        $request = request();
        switch ($accessEnd = $this->getAuthAccessEnd($request)) {
            case self::APP:
                /** @var OpenAppConfig $meke */
                $meke = app()->make(OpenAppConfig::class);
                $config = $meke->all();
                break;
            case self::PC:
                /** @var OpenWebConfig $meke */
                $meke = app()->make(OpenWebConfig::class);
                $config = $meke->all();
                break;
            default:
                $config = $this->config->all();
                break;
        }
        if (!isset($this->application[$accessEnd])) {
            $this->application[$accessEnd] = Factory::officialAccount($config);
            $this->application[$accessEnd]['guzzle_handler'] = SwooleHandler::class;
            $this->application[$accessEnd]->rebind('request', new Request($request->get(), $request->post(), [], [], [], $request->server(), $request->getContent()));
        }

        return $this->application[$accessEnd];
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
     * @return OfficialAccount
     */
    public static function instance()
    {
        return app()->make(self::class);
    }

    /**
     * 获取js的SDK
     * @param string $url
     * @return string
     * @throws GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function jsSdk($url = '')
    {
        $apiList = ['openAddress', 'updateTimelineShareData', 'updateAppMessageShareData', 'onMenuShareTimeline', 'onMenuShareAppMessage', 'onMenuShareQQ', 'onMenuShareWeibo', 'onMenuShareQZone', 'startRecord', 'stopRecord', 'onVoiceRecordEnd', 'playVoice', 'pauseVoice', 'stopVoice', 'onVoicePlayEnd', 'uploadVoice', 'downloadVoice', 'chooseImage', 'previewImage', 'uploadImage', 'downloadImage', 'translateVoice', 'getNetworkType', 'openLocation', 'getLocation', 'hideOptionMenu', 'showOptionMenu', 'hideMenuItems', 'showMenuItems', 'hideAllNonBaseMenuItem', 'showAllNonBaseMenuItem', 'closeWindow', 'scanQRCode', 'chooseWXPay', 'openProductSpecificView', 'addCard', 'chooseCard', 'openCard'];
        $jsService = self::instance()->application()->jssdk;
        if ($url) $jsService->setUrl($url);
        try {
            return $jsService->buildConfig($apiList, false, true);
        } catch (\Exception $e) {
            return '{}';
        }
    }

    /**
     * 获取微信用户信息
     * @param $openid
     * @return array|Collection|mixed|object|ResponseInterface|string
     */
    public static function getUserInfo($openid)
    {
        $userService = self::userService();
        $userInfo = [];
        try {
            if (is_array($openid)) {
                $res = $userService->select($openid);
                if (isset($res['user_info_list'])) {
                    $userInfo = $res['user_info_list'];
                } else {
                    throw new WechatException($res['errmsg'] ?? '获取微信粉丝信息失败');
                }
            } else {
                $userInfo = $userService->get($openid);
                $userInfo = is_object($userInfo) ? $userInfo->toArray() : $userInfo;
            }
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }

        self::logger('获取微信用户信息', compact('openid'), $userInfo);

        return $userInfo;
    }

    /**
     * 获取会员卡列表
     * @param int $offset
     * @param int $count
     * @param string $statusList
     * @return mixed
     * @throws GuzzleException
     */
    public static function getCardList($offset = 0, $count = 10, $statusList = 'CARD_STATUS_VERIFY_OK')
    {
        try {
            $res = self::cardServices()->list($offset, $count, $statusList);

            self::logger('获取会员卡列表', compact('offset', 'count', 'statusList'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['card_id_list'])) {
                return $res['card_id_list'];
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 获取卡券颜色
     * @return mixed
     */
    public static function getCardColors()
    {
        try {

            $response = self::cardServices()->colors();

            self::logger('获取卡券颜色', [], $response);

            return $response;
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 创建卡券
     * @param string $cardType
     * @param array $baseInfo
     * @param array $especial
     * @param array $advancedInfo
     * @return mixed
     * @throws GuzzleException
     */
    public static function createCard(string $cardType, array $baseInfo, array $especial = [], array $advancedInfo = [])
    {
        try {
            $res = self::cardServices()->create($cardType, array_merge(['base_info' => $baseInfo, 'advanced_info' => $advancedInfo], $especial));

            self::logger('创建卡券', compact('cardType', 'baseInfo', 'especial', 'advancedInfo'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['card_id'])) {
                return $res;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 获取卡券信息
     * @param $cardId
     * @return mixed
     * @throws GuzzleException
     */
    public static function getCard($cardId)
    {
        try {

            $res = self::cardServices()->get($cardId);

            self::logger('获取卡券信息', compact('cardId'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0) {
                return $res;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 修改卡券
     * @param string $cardId
     * @param string $type
     * @param array $baseInfo
     * @param array $especial
     * @return mixed
     * @throws GuzzleException
     */
    public static function updateCard(string $cardId, string $type, array $baseInfo = [], array $especial = [])
    {
        try {
            $res = self::cardServices()->update($cardId, $type, array_merge(['base_info' => $baseInfo], $especial));

            self::logger('修改卡券', compact('cardId', 'type', 'baseInfo', 'especial'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0) {
                return $res;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 获取领卡券二维码
     * @param string $card_id 卡券ID
     * @param string $outer_id 生成二维码标识参数
     * @param string $code 自动移code
     * @param int $expire_time
     * @return mixed
     * @throws GuzzleException
     */
    public static function getCardQRCode(string $card_id, string $outer_id, string $code = '', int $expire_time = 1800)
    {
        $data = [
            'action_name' => 'QR_CARD',
            'expire_seconds' => $expire_time,
            'action_info' => [
                'card' => [
                    'card_id' => $card_id,
                    'is_unique_code' => false,
                    'outer_id' => $outer_id
                ]
            ]
        ];
        if ($code) $data['action_info']['card']['code'] = $code;
        try {
            $res = self::cardServices()->createQrCode($data);

            self::logger('获取领卡券二维码', compact('data'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['url'])) {
                return $res;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 设置会员卡激活字段
     * @param string $cardId
     * @param array $requiredForm
     * @param array $optionalForm
     * @return mixed
     * @throws GuzzleException
     */
    public static function cardActivateUserForm(string $cardId, array $requiredForm = [], array $optionalForm = [])
    {
        try {
            $res = self::cardServices()->member_card->setActivationForm($cardId, array_merge($requiredForm, $optionalForm));

            self::logger('设置会员卡激活字段', compact('cardId', 'requiredForm', 'optionalForm'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0) {
                return $res;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 会员卡激活
     * @param string $card_id
     * @param string $code
     * @param string $membership_number
     * @return mixed
     * @throws GuzzleException
     */
    public static function cardActivate(string $card_id, string $code, $membership_number = '')
    {
        $info = [
            'membership_number' => $membership_number ? $membership_number : $code, //会员卡编号，由开发者填入，作为序列号显示在用户的卡包里。可与Code码保持等值。
            'code' => $code, //创建会员卡时获取的初始code。
            'activate_begin_time' => '', //激活后的有效起始时间。若不填写默认以创建时的 data_info 为准。Unix时间戳格式
            'activate_end_time' => '', //激活后的有效截至时间。若不填写默认以创建时的 data_info 为准。Unix时间戳格式。
            'init_bonus' => '0', //初始积分，不填为0。
            'init_balance' => '0', //初始余额，不填为0。
        ];
        try {
            $res = self::cardServices()->member_card->activate($info);

            self::logger('会员卡激活', compact('info'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['url'])) {
                return $res;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 获取会员信息
     * @param string $cardId
     * @param string $code
     * @return mixed
     * @throws GuzzleException
     */
    public static function getMemberCardUser(string $cardId, string $code)
    {
        try {
            $res = self::cardServices()->member_card->getUser($cardId, $code);

            self::logger('获取会员信息', compact('cardId', 'code'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['user_info'])) {
                return $res;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 更新会员信息
     * @param array $data
     * @return mixed
     * @throws GuzzleException
     */
    public static function updateMemberCardUser(array $data)
    {
        try {
            $res = self::cardServices()->member_card->updateUser($data);

            self::logger('更新会员信息', compact('data'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['user_info'])) {
                return $res;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 设置模版消息行业
     * @param int $industryOne
     * @param int $industryTwo
     * @return array|Collection|object|ResponseInterface|string
     * @throws GuzzleException
     * @throws InvalidConfigException
     */
    public static function setIndustry(int $industryOne, int $industryTwo)
    {
        $response = self::templateService()->setIndustry($industryOne, $industryTwo);

        self::logger('设置模版消息行业', compact('industryOne', 'industryTwo'), $response);

        return $response;
    }

    /**
     * 获得添加模版ID
     * @param $templateIdShort
     * @return array|Collection|object|ResponseInterface|string
     * @throws GuzzleException
     */
    public static function addTemplateId($templateIdShort)
    {
        try {
            $response = self::templateService()->addTemplate($templateIdShort);

            self::logger('获得添加模版ID', compact('templateIdShort'), $response);

            return $response;
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 获取模板列表
     * @return array|Collection|object|ResponseInterface|string
     * @throws GuzzleException
     */
    public static function getPrivateTemplates()
    {
        try {
            $response = self::templateService()->getPrivateTemplates();

            self::logger('获取模板列表', [], $response);

            return $response;
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 根据模版ID删除模版
     * @param string $templateId
     * @return array|Collection|object|ResponseInterface|string
     * @throws GuzzleException
     */
    public static function deleleTemplate(string $templateId)
    {
        try {
            return self::templateService()->deletePrivateTemplate($templateId);
        } catch (\Exception $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 获取行业
     * @return array|Collection|object|ResponseInterface|string
     */
    public static function getIndustry()
    {
        try {
            $response = self::templateService()->getIndustry();

            self::logger('获取行业', [], $response);

            return $response;
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getMessage($e->getMessage()));
        }
    }

    /**
     * 发送模板消息
     * @param string $openid
     * @param string $templateId
     * @param array $data
     * @param string|null $url
     * @param string|null $defaultColor
     * @return array|Collection|object|ResponseInterface|string
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public static function sendTemplate(string $openid, string $templateId, array $data, string $url = null, string $defaultColor = null)
    {
        $response = self::templateService()->send([
            'touser' => $openid,
            'template_id' => $templateId,
            'data' => $data,
            'url' => $url
        ]);

        self::logger('发送模板消息', compact('openid', 'templateId', 'data', 'url'), $response);

        return $response;
    }

    /**
     * 静默授权-使用code获取用户授权信息
     * @param string|null $code
     * @return array
     */
    public static function tokenFromCode(string $code = null)
    {
        $code = $code ?: request()->param('code');
        if (!$code) {
            throw new WechatException('无效CODE');
        }

        try {
            $response = self::oauthService()->setGuzzleOptions(['verify' => false])->tokenFromCode($code);

            self::logger('静默授权-使用code获取用户授权信息', compact('code'), $response);

            return $response;
        } catch (\Throwable $e) {
            throw new WechatException('授权失败' . $e->getMessage() . 'line' . $e->getLine());
        }
    }

    /**
     * 使用code获取用户授权信息
     * @param string|null $code
     * @return array
     */
    public static function userFromCode(string $code = null)
    {
        $code = $code ?: request()->param('code');
        if (!$code) {
            throw new WechatException('无效CODE');
        }
        try {
            $response = self::oauthService()->setGuzzleOptions(['verify' => false])->userFromCode($code);

            self::logger('使用code获取用户授权信息', compact('code'), $response);

            return $response->getRaw();
        } catch (\Throwable $e) {
            throw new WechatException('授权失败' . $e->getMessage() . 'line' . $e->getLine());
        }
    }

    /**
     * 永久素材上传
     * @param string $path
     * @return WechatResponse
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public static function uploadImage(string $path)
    {
        $response = self::materialService()->uploadImage($path);

        self::logger('素材管理-上传附件', compact('path'), $response);

        return new WechatResponse($response);
    }

    /**
     * 临时素材上传
     * @param string $path
     * @param string $type
     * @return WechatResponse
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public static function temporaryUpload(string $path, string $type = 'image')
    {
        $response = self::mediaService()->upload($type, $path);

        self::logger('临时素材上传', compact('path', 'type'), $response);

        return new WechatResponse($response);
    }
}
