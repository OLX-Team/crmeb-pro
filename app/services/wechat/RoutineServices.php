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
declare (strict_types=1);

namespace app\services\wechat;

use app\services\BaseServices;
use app\dao\wechat\WechatUserDao;
use app\services\other\QrcodeServices;
use app\services\user\LoginServices;
use app\services\user\UserServices;
use app\services\user\UserVisitServices;
use crmeb\services\CacheService;
use crmeb\services\template\Template;
use crmeb\services\wechat\MiniProgram;
use crmeb\services\wechat\WechatResponse;
use think\exception\ValidateException;
use think\facade\Config;

/**
 *
 * Class RoutineServices
 * @package app\services\wechat
 */
class RoutineServices extends BaseServices
{

    /**
     * @var string
     */
    protected $sessionKey = 'eb_routine_api_code_';

    /**
     * RoutineServices constructor.
     * @param WechatUserDao $dao
     */
    public function __construct(WechatUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取小程序订阅消息id
     * @return mixed
     */
    public function temlIds()
    {
        $temlIdsName = Config::get('template.stores.subscribe.template_id', []);
        $temlIdsList = CacheService::handler('TEMPLATE')->remember('TEML_IDS_LIST', function () use ($temlIdsName) {
            $temlId = [];
            $templdata = new Template('subscribe');
            foreach ($temlIdsName as $key => $item) {
                $temlId[strtolower($key)] = $templdata->getTempId($item);
            }
            return $temlId;
        });
        return $temlIdsList;
    }

    /**
     * 获取小程序直播列表
     * @param $pgae
     * @param $limit
     * @return mixed
     */
    public function live($page, $limit)
    {
        $list = CacheService::get('WECHAT_LIVE_LIST_' . $page . '_' . $limit, function () use ($page, $limit) {
            $list = MiniProgram::getLiveInfo((int)$page, (int)$limit);
            foreach ($list as &$item) {
                $item['_start_time'] = date('m-d H:i', $item['start_time']);
            }
            return $list;
        }, 600) ?: [];
        return $list;
    }

    /**
     * 通过code获取授权信息
     * @param string $code
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     */
    public function getUserInfoByCode(string $code)
    {
        if (!$code)
            throw new ValidateException('授权失败,参数有误');
        try {
            $userInfoConfig = MiniProgram::getUserInfo($code);
        } catch (\Exception $e) {
            throw new ValidateException('授权失败，请检查您的配置！:' . $e->getMessage() . 'line' . $e->getLine());
        }

        $userInfoConfig = new WechatResponse($userInfoConfig);
        if (!isset($userInfoConfig['openid']) || !$userInfoConfig['openid']) {
            throw new ValidateException('openid获取失败');
        }
        return $userInfoConfig->toArray();
    }

    /**
     * 解密获取用户信息
     * @param $userInfoConfig
     * @param $iv
     * @param $encryptedData
     * @return mixed
     */
    public function encryptorUserInfo($userInfoConfig, $iv, $encryptedData)
    {
        if (!$userInfoConfig)
            throw new ValidateException('授权失败,参数有误');
        $session_key = $userInfoConfig['session_key'] ?? '';
        if (!$session_key) {
            throw new ValidateException('获取session_key失败,参数有误');
        }
        try {
            //解密获取用户信息
            $userInfo = MiniProgram::decryptData($session_key, $iv, $encryptedData);
        } catch (\Exception $e) {
            $userInfo = [];
            if ($e->getCode() == '-41003') {
                throw new ValidateException('获取会话密匙失败');
            }
        }
        return $userInfo;
    }

    /**
     * 处理小程序授权获取用户信息
     * @param $routine
     * @return array
     */
    public function routineOauth($routine)
    {
        $routineInfo['nickname'] = isset($routine['nickName']) ? filter_emoji($routine['nickName']) : (isset($routine['nickname']) ? filter_emoji($routine['nickname']) : '');//姓名
        $routineInfo['sex'] = $routine['gender'] ?? '';//性别
        $routineInfo['language'] = $routine['language'] ?? '';//语言
        $routineInfo['city'] = $routine['city'] ?? '';//城市
        $routineInfo['province'] = $routine['province'] ?? "";//省份
        $routineInfo['country'] = $routine['country'] ?? '';//国家
        $routineInfo['headimgurl'] = $routine['avatarUrl'] ?? $routine['headimgurl'] ?? '';//头像
        $routineInfo['openid'] = $routine['openid'] ?? '';
        $routineInfo['session_key'] = $routine['session_key'] ?? '';//会话密匙
        $routineInfo['unionid'] = $routine['unionId'] ?? $routine['unionid'] ?? '';//用户在开放平台的唯一标识符
        $routineInfo['user_type'] = 'routine';//用户类型
        $routineInfo['phone'] = $routine['phone'] ?? $routine['purePhoneNumber'] ?? '';
        $spid = (int)($routine['spid'] ?? 0);//绑定关系uid
        if (!$spid && isset($routine['code']) && $routine['code']) {
            //获取是否有扫码进小程序
            /** @var QrcodeServices $qrcode */
            $qrcode = app()->make(QrcodeServices::class);
            $info = $qrcode->get((int)$routine['code']);
            if ($info) {
                $spid = $info['third_id'];
            }
        }
        return [$routine['openid'] ?? '', $routineInfo, $spid, $routine['login_type'] ?? 'routine', 'routine'];
    }

    /**
     * 获取返回信息
     * @param $user
     * @param string $userType
     * @return array
     */
    public function getReturnInfo($user, string $userType = 'routine')
    {
        if (!$user || !isset($user['uid']) || !$user['uid']) {
            throw new ValidateException('获取用户信息失败');
        }
        $token = $this->createToken((int)$user['uid'], $userType);
        if (!$token) {
            throw new ValidateException('登录失败!');
        }
        /** @var UserVisitServices $visitServices */
        $visitServices = app()->make(UserVisitServices::class);
        $visitServices->loginSaveVisit($user);
        $token['userInfo'] = $user;
        $token['expires_time'] = $token['params']['exp'] ?? 0;
        return $token;
    }


    /**
     * 小程序授权登录
     * @param $code
     * @param $post_cache_key
     * @param $login_type
     * @param $spread_spid
     * @param $spread_code
     * @param $iv
     * @param $encryptedData
     * @return mixed
     */
    public function mp_auth($code, $post_cache_key, $login_type, $spread_spid, $spread_code, $iv, $encryptedData)
    {
        $userInfoConfig = $this->getUserInfoByCode((string)$code);
        $userInfo = $this->encryptorUserInfo($userInfoConfig, $iv, $encryptedData);
        $userInfo['unionId'] = $userInfoConfig['unionid'] ?? '';
        $userInfo['openid'] = $userInfoConfig['openid'];
        $userInfo['spid'] = $spread_spid;
        $userInfo['code'] = $spread_code;
        $userInfo['session_key'] = $userInfoConfig['session_key'] ?? '';
        $userInfo['login_type'] = $login_type;
        [$openid, $wechatInfo, $spid, $login_type, $userType] = $createData = $this->routineOauth($userInfo);

        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $user = $wechatUserServices->getAuthUserInfo($openid, $userType);
        if (!$user) {
            $user = $wechatUserServices->wechatOauthAfter($createData);
        } else {
            //更新用户信息
            $wechatUserServices->wechatUpdata([$user['uid'], $wechatInfo]);
        }
        return $this->getReturnInfo($user);
    }

    /**
     * 小程序授权登录
     * @param $code
     * @param $spid
     * @param $spread
     * @param $iv
     * @param $encryptedData
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function newAuth($code, $spid, $spread, $iv, $encryptedData)
    {
        $userInfoConfig = $this->getUserInfoByCode((string)$code);
        $userInfo = $this->encryptorUserInfo($userInfoConfig, $iv, $encryptedData);
        $userInfo['unionId'] = $userInfoConfig['unionid'] ?? '';
        $userInfo['openid'] = $userInfoConfig['openid'];
        $userInfo['spid'] = $spid;
        $userInfo['code'] = $spread;
        $userInfo['session_key'] = $userInfoConfig['session_key'] ?? '';
        $userInfo['login_type'] = 'routine';
        [$openid, $wechatInfo, $spid, $login_type, $userType] = $createData = $this->routineOauth($userInfo);
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $user = $wechatUserServices->getAuthUserInfo($openid, $userType);
        //获取是否强制绑定手机号
        $storeUserMobile = sys_config('store_user_mobile');
        if ($storeUserMobile && !$user) {
            $userInfoKey = md5($openid . '_' . time() . '_rouine');
            CacheService::setTokenBucket($userInfoKey, $createData, 7200);
            return ['key' => $userInfoKey];
        } else if (!$user) {
            $user = $wechatUserServices->wechatOauthAfter($createData);
        } else {
            //更新用户信息
            $wechatUserServices->wechatUpdata([$user['uid'], $wechatInfo]);
        }
        return $this->getReturnInfo($user);
    }

    /**
     * 静默授权
     * @param $code
     * @param $spread
     * @return mixed
     */
    public function silenceAuth(string $code, int $spread, int $spid, bool $notLogin = false)
    {
        $userInfoConfig = $this->getUserInfoByCode($code);
        $routineInfo = [];
        $routineInfo['unionid'] = $userInfoConfig['unionid'] ?? '';
        $routineInfo['openid'] = $userInfoConfig['openid'];
        $routineInfo['spid'] = $spid;
        $routineInfo['code'] = $spread;
        $routineInfo['headimgurl'] = sys_config('h5_avatar');

        [$openid, $wechatInfo, $spid, $login_type, $userType] = $createData = $this->routineOauth($routineInfo);
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $user = $wechatUserServices->getAuthUserInfo($openid, $userType);
        if (!$user) {
            //获取是否强制绑定手机号
            $storeUserMobile = sys_config('store_user_mobile');
            if ($notLogin || $storeUserMobile) {
                $userInfoKey = md5($openid . '_' . time() . '_routine');
                CacheService::setTokenBucket($userInfoKey, $createData, 7200);
                return ['auth_login' => 1, 'key' => $userInfoKey];
            } else {
                //写入用户信息
                $user = $wechatUserServices->wechatOauthAfter($createData);
            }
        } else {
            //更新用户信息
            $wechatUserServices->wechatUpdata([$user['uid'], ['code' => $spid]]);
        }
        return $this->getReturnInfo($user);

    }

    /**
     * 手机号登录 静默授权绑定关系
     * @param $code
     * @param $spread
     * @return mixed
     */
    public function silenceAuthBindingPhone($code, $spread, $spid, $phone)
    {
        $userInfoConfig = $this->getUserInfoByCode((string)$code);
        $routineInfo = [];
        $routineInfo['unionid'] = $userInfoConfig['unionid'] ?? '';
        $openid = $userInfoConfig['openid'];
        $routineInfo['openid'] = $openid;
        $routineInfo['spid'] = $spid;
        $routineInfo['code'] = $spread;
        $routineInfo['headimgurl'] = sys_config('h5_avatar');
        $routineInfo['phone'] = $phone;
        $createData = $this->routineOauth($routineInfo);

        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        //写入用户信息
        $user = $wechatUserServices->wechatOauthAfter($createData);
        return $this->getReturnInfo($user);
    }

    /**
     * 自动获取手机号绑定
     * @param $code
     * @param $spread
     * @return mixed
     */
    public function authBindingPhone($code, $iv, $encryptedData, $spread, $spid, $key = '')
    {
        $wechatInfo = [];
        $userType = $login_type = 'routine';
        if ($key) {
            [$openid, $wechatInfo, $spreadId, $login_type, $userType] = $createData = CacheService::getTokenBucket($key);
        }
        $userInfoConfig = $this->getUserInfoByCode((string)$code);
        $userInfo = $this->encryptorUserInfo($userInfoConfig, $iv, $encryptedData);
        if (!$userInfo || !isset($userInfo['purePhoneNumber'])) {
            throw new ValidateException('获取用户信息失败');
        }
        $openid = $userInfoConfig['openid'];
        $wechatInfo['openid'] = $openid;
        $wechatInfo['unionid'] = $userInfoConfig['unionid'] ?? '';
        $wechatInfo['spid'] = $spid;
        $wechatInfo['code'] = $spread;
        $wechatInfo['session_key'] = $userInfoConfig['session_key'] ?? '';
        $wechatInfo['phone'] = $userInfo['purePhoneNumber'];
        $createData = $this->routineOauth($wechatInfo);
        $wechatInfo = $createData[1] ?? [];
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        //写入用户信息
        $user = $wechatUserServices->wechatOauthAfter([$openid, $wechatInfo, $spreadId, $login_type, $userType]);
        return $this->getReturnInfo($user);
    }

    /**
     * 更新用户信息
     * @param $uid
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateUserInfo($uid, array $data)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('数据不存在');
        }
        $userInfo = [];
        $userInfo['nickname'] = filter_emoji($data['nickName'] ?? '');//姓名
        $userInfo['sex'] = $data['gender'] ?? '';//性别
        $userInfo['language'] = $data['language'] ?? '';//语言
        $userInfo['city'] = $data['city'] ?? '';//城市
        $userInfo['province'] = $data['province'] ?? '';//省份
        $userInfo['country'] = $data['country'] ?? '';//国家
        $userInfo['headimgurl'] = $data['avatarUrl'] ?? '';//头像
        $userInfo['is_complete'] = 1;
        /** @var LoginServices $loginService */
        $loginService = app()->make(LoginServices::class);
        $loginService->updateUserInfo($userInfo, $user);
        //更新用户信息
        if (!$this->dao->update(['uid' => $user['uid'], 'user_type' => 'routine'], $userInfo)) {
            throw new ValidateException('更新失败');
        }
        return true;
    }
}
