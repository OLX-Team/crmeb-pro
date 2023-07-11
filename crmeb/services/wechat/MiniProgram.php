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


use crmeb\services\wechat\config\MiniProgramConfig;
use crmeb\services\wechat\live\LiveClient;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\DecryptException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use EasyWeChat\Kernel\Support\Collection;
use EasyWeChat\MiniProgram\Application;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;
use Yurun\Util\Swoole\Guzzle\SwooleHandler;
use crmeb\services\wechat\live\ServiceProvider as LiveServiceProvider;

/**
 * 小程序服务
 * Class MiniProgram
 * @package crmeb\services\wechat
 * @method \EasyWeChat\OfficialAccount\CustomerService\Client staffService() 客服
 * @method \EasyWeChat\BasicService\Media\Client mediaService() 临时素材
 * @method \EasyWeChat\MiniProgram\Encryptor encryptor() 解密
 * @method \EasyWeChat\MiniProgram\AppCode\Client qrcodeService() 小程序码
 * @method \EasyWeChat\MiniProgram\SubscribeMessage\Client subscribenoticeService() 订阅消息
 * @method LiveClient liveService() 直播
 */
class MiniProgram extends BaseApplication
{

    /**
     * @var MiniProgramConfig
     */
    protected $config;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var string[]
     */
    protected static $property = [
        'mediaService' => 'media',
        'staffService' => 'customer_service',
        'encryptor' => 'encryptor',
        'qrcodeService' => 'app_code',
        'subscribenoticeService' => 'subscribe_message',
        'liveService' => 'live'
    ];

    /**
     * MiniProgram constructor.
     * @param MiniProgramConfig $config
     */
    public function __construct(MiniProgramConfig $config)
    {
        $this->config = $config;
        $this->debug = DefaultConfig::value('logger');
    }

    /**
     * 初始化
     * @return Application
     */
    public function application()
    {
        if (!$this->application) {
            $this->application = Factory::miniProgram($this->config->all());
            $request = request();
            $this->application['guzzle_handler'] = SwooleHandler::class;
            $this->application->rebind('request', new Request($request->get(), $request->post(), [], [], [], $request->server(), $request->getContent()));
            $this->application->register(new LiveServiceProvider());
        }
        return $this->application;
    }

    /**
     * @return MiniProgram
     */
    public static function instance()
    {
        return app()->make(self::class);
    }

    /**
     * 获得用户信息 根据code 获取session_key
     * @param string $code
     * @return array|Collection|object|ResponseInterface|string
     */
    public static function getUserInfo(string $code)
    {
        try {
            $response = self::instance()->application()->auth->session($code);

            self::logger('获得用户信息 根据code 获取session_key', compact('code'), $response);

            return $response;
        } catch (\Throwable $e) {
            throw new WechatException($e->getMessage());
        }
    }

    /**
     * 解密数据
     * @param string $sessionKey
     * @param string $iv
     * @param string $encryptData
     * @return array
     * @throws DecryptException
     */
    public static function decryptData(string $sessionKey, string $iv, string $encryptData)
    {
        $response = self::encryptor()->decryptData($sessionKey, $iv, $encryptData);

        self::logger('解密数据', compact('sessionKey', 'iv', 'encryptData'), $response);

        return $response;
    }

    /**
     * 获取小程序码:适用于需要的码数量极多，或仅临时使用的业务场景
     * @param string $scene
     * @param string $path
     * @param int $width
     * @return array|Collection|object|ResponseInterface|string
     */
    public static function appCodeUnlimit(string $scene, string $path = '', int $width = 0)
    {
        $optional = [
            'page' => $path,
            'width' => $width
        ];
        if (!$optional['page']) {
            unset($optional['page']);
        }
        if (!$optional['width']) {
            unset($optional['width']);
        }
        $response = self::qrcodeService()->getUnlimit($scene, $optional);

        self::logger('获取小程序码', compact('scene', 'optional'), $response);

        return $response;
    }

    /**
     * 发送订阅消息
     * @param string $touser
     * @param string $templateId
     * @param array $data
     * @param string $link
     * @return array|Collection|object|ResponseInterface|string
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws GuzzleException
     */
    public static function sendSubscribeTemlate(string $touser, string $templateId, array $data, string $link = '')
    {
        $response = self::subscribenoticeService()->send([
            'template_id' => $templateId,
            'touser' => $touser,
            'page' => $link,
            'data' => $data
        ]);

        self::logger('发送订阅消息', compact('templateId', 'touser', 'link', 'data'), $response);

        return $response;
    }

    /**
     * 添加订阅消息模版
     * @param string $tid
     * @param array $kidList
     * @param string $sceneDesc
     * @return mixed
     */
    public static function addSubscribeTemplate(string $tid, array $kidList, string $sceneDesc = '')
    {
        try {
            $res = self::subscribenoticeService()->addTemplate($tid, $kidList, $sceneDesc);

            self::logger('添加订阅消息模版', compact('tid', 'kidList', 'sceneDesc'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['priTmplId'])) {
                return $res['priTmplId'];
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException($e);
        }
    }

    /**
     * 删除订阅消息
     * @param string $templateId
     * @return array|Collection|object|ResponseInterface|string
     */
    public static function delSubscribeTemplate(string $templateId)
    {
        try {
            $response = self::subscribenoticeService()->deleteTemplate($templateId);

            self::logger('删除订阅消息', compact('templateId'), $response);

            return $response;
        } catch (\Throwable $e) {
            throw new WechatException($e->getMessage());
        }
    }

    /**
     * 获取模版标题的关键词列表
     * @param string $tid
     * @return mixed
     */
    public static function getSubscribeTemplateKeyWords(string $tid)
    {
        try {
            $res = self::subscribenoticeService()->getTemplateKeywords($tid);

            self::logger('获取模版标题的关键词列表', compact('tid'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['data'])) {
                return $res['data'];
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException($e);
        }
    }

    /**
     * 获取直播列表
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getLiveInfo(int $page = 1, int $limit = 10)
    {
        try {
            $res = self::liveService()->getRooms($page, $limit);

            self::logger('获取直播列表', compact('page', 'limit'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['room_info']) && $res['room_info']) {
                return $res['room_info'];
            } else {
                return [];
            }
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 获取直播回放
     * @param int $room_id
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public static function getLivePlayback(int $room_id, int $page = 1, int $limit = 10)
    {
        try {
            $res = self::liveService()->getPlaybacks($room_id, $page, $limit);

            self::logger('获取直播回放', compact('room_id', 'page', 'limit'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['live_replay'])) {
                return $res['live_replay'];
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getValidMessgae($e));
        }
    }

    /**
     * 创建直播间
     * @param array $data
     * @return mixed
     */
    public static function createLiveRoom(array $data)
    {
        try {
            $res = self::liveService()->createRoom($data);

            self::logger('创建直播间', compact('data'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['roomId'])) {
                unset($res['errcode']);
                return $res;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getValidMessgae($e));
        }
    }

    /**
     * 直播间添加商品
     * @param int $roomId
     * @param $ids
     * @return bool
     */
    public static function roomAddGoods(int $roomId, $ids)
    {
        try {
            $res = self::liveService()->roomAddGoods($roomId, $ids);

            self::logger('直播间添加商品', compact('roomId', 'ids'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0) {
                return true;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getValidMessgae($e));
        }
    }

    /**
     * 获取商品列表
     * @param int $status
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public static function getGoodsList(int $status = 2, int $page = 1, int $limit = 10)
    {
        try {
            $res = self::liveService()->getGoodsList($status, $page, $limit);

            self::logger('获取商品列表', compact('status', 'page', 'limit'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['goods'])) {
                return $res['goods'];
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getValidMessgae($e));
        }
    }

    /**
     * 获取商品详情
     * @param $goods_ids
     * @return mixed
     */
    public static function getGooodsInfo($goods_ids)
    {
        try {
            $res = self::liveService()->getGooodsInfo($goods_ids);

            self::logger('获取商品详情', compact('goods_ids'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['goods'])) {
                return $res['goods'];
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getValidMessgae($e));
        }
    }

    /**
     * 添加商品
     * @param string $coverImgUrl
     * @param string $name
     * @param int $priceType
     * @param string $url
     * @param $price
     * @param string $price2
     * @return mixed
     */
    public static function addGoods(string $coverImgUrl, string $name, int $priceType, string $url, $price, $price2 = '')
    {
        try {
            $res = self::liveService()->addGoods($coverImgUrl, $name, $priceType, $url, $price, $price2);

            self::logger('添加商品', compact('coverImgUrl', 'name', 'priceType', 'url', 'price', 'price2'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['goodsId'])) {
                unset($res['errcode']);
                return $res;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getValidMessgae($e));
        }
    }

    /**
     * 商品撤回审核
     * @param int $goodsId
     * @param $auditId
     * @return bool
     */
    public static function resetauditGoods(int $goodsId, $auditId)
    {
        try {
            $res = self::liveService()->resetauditGoods($goodsId, $auditId);

            self::logger('商品撤回审核', compact('goodsId', 'auditId'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0) {
                return true;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getValidMessgae($e));
        }
    }

    /**
     * 商品重新提交审核
     * @param int $goodsId
     * @return mixed
     */
    public static function auditGoods(int $goodsId)
    {
        try {
            $res = self::liveService()->auditGoods($goodsId);

            self::logger('商品重新提交审核', compact('goodsId'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['auditId'])) {
                return $res['auditId'];
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getValidMessgae($e));
        }
    }

    /**
     * 删除商品
     * @param int $goodsId
     * @return bool
     */
    public static function deleteGoods(int $goodsId)
    {
        try {
            $res = self::liveService()->deleteGoods($goodsId);

            self::logger('删除商品', compact('goodsId'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0) {
                return true;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getValidMessgae($e));
        }
    }

    /**
     * 更新商品
     * @param int $goodsId
     * @param string $coverImgUrl
     * @param string $name
     * @param int $priceType
     * @param string $url
     * @param $price
     * @param string $price2
     * @return bool
     */
    public static function updateGoods(int $goodsId, string $coverImgUrl, string $name, int $priceType, string $url, $price, $price2 = '')
    {
        try {
            $res = self::liveService()->updateGoods($goodsId, $coverImgUrl, $name, $priceType, $url, $price, $price2);

            self::logger('更新商品', compact('goodsId', 'coverImgUrl', 'name', 'priceType', 'url', 'price', 'price2'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0) {
                return true;
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getValidMessgae($e));
        }
    }

    /**
     * 获取成员列表
     * @param int $role
     * @param int $page
     * @param int $limit
     * @param string $keyword
     * @return mixed
     */
    public static function getRoleList($role = 2, int $page = 0, int $limit = 30, $keyword = '')
    {
        try {
            $res = self::liveService()->getRoleList($role, $page, $limit, $keyword);

            self::logger('获取成员列表', compact('role', 'page', 'limit', 'keyword'), $res);

            if (isset($res['errcode']) && $res['errcode'] == 0 && isset($res['list'])) {
                return $res['list'];
            } else {
                throw new WechatException($res['errmsg']);
            }
        } catch (\Throwable $e) {
            throw new WechatException(ErrorMessage::getValidMessgae($e));
        }
    }

    /**
     * 小程序临时素材上传
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

        self::logger('小程序-临时素材上传', compact('path', 'type'), $response);

        return new WechatResponse($response);
    }


}
