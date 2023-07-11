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
use app\services\user\UserServices;
use app\services\user\UserVisitServices;
use crmeb\services\CacheService;
use crmeb\services\CacheService as Cache;
use crmeb\services\wechat\OfficialAccount;
use crmeb\services\wechat\Work;
use crmeb\utils\Canvas;
use EasyWeChat\Kernel\Exceptions\BadRequestException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use GuzzleHttp\Exception\GuzzleException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\exception\ValidateException;

/**
 *
 * Class WechatServices
 * @package app\services\wechat
 * @method value(array $where, ?string $field)
 */
class WechatServices extends BaseServices
{

    /**
     * WechatServices constructor.
     * @param WechatUserDao $dao
     */
    public function __construct(WechatUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 微信公众号服务
     * @return \think\Response
     * @throws BadRequestException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws \ReflectionException
     */
    public function serve()
    {
        ob_clean();
        return OfficialAccount::serve();
    }

    /**
     * 企业微信服务
     * @return \think\Response
     * @throws BadRequestException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws \ReflectionException
     */
    public function workServe()
    {
        ob_clean();
        return Work::serve();
    }

    /**
     * 公众号权限配置信息获取
     * @param $url
     * @return mixed
     * @throws GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function config($url)
    {
        return json_decode(OfficialAccount::jsSdk($url), true);
    }

    /**
     * 获取授权信息
     * @param string $code
     * @return array
     */
    public function getAuthWechatInfo()
    {
        try {
            $userInfoConfig = OfficialAccount::tokenFromCode();
        } catch (\Throwable $e) {
            \think\facade\Log::error([
                'error' => '授权失败：' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw new ValidateException('授权失败');
        }
        if (!isset($userInfoConfig['openid']) || !$userInfoConfig['openid']) {
            throw new ValidateException('openid获取失败');
        }
        return $userInfoConfig;
    }

    /**
     * 获取返回信息
     * @param $user
     * @param string $userType
     * @return array
     */
    public function getReturnInfo($user, string $userType = 'wechat')
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
        $token['userInfo'] = is_object($user) && method_exists($user, 'toArray') ? $user->toArray() : $user;
        $token['expires_time'] = $token['params']['exp'] ?? 0;
        return $token;
    }

    /**
     * 公众号授权登陆
     * @return mixed
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException|InvalidConfigException
     */
    public function auth($spreadId, $login_type)
    {
        $wechatInfo = $this->getAuthWechatInfo();
        if (!isset($wechatInfo['nickname'])) {
            $wechatInfo = OfficialAccount::userService()->get($wechatInfo['openid']);
            if (!isset($wechatInfo['nickname']))
                throw new ValidateException('授权失败');
            if (isset($wechatInfo['tagid_list']))
                $wechatInfo['tagid_list'] = implode(',', $wechatInfo['tagid_list']);
        } else {
            if (isset($wechatInfo['privilege'])) unset($wechatInfo['privilege']);
            /** @var WechatUserServices $wechatUser */
            $wechatUser = app()->make(WechatUserServices::class);
            if (!$wechatUser->getOne(['openid' => $wechatInfo['openid']])) {
                $wechatInfo['subscribe'] = 0;
            }
        }
        $wechatInfo['user_type'] = 'wechat';
        $openid = $wechatInfo['openid'];
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $user = $wechatUserServices->getAuthUserInfo($openid, 'wechat');
        $createData = [$openid, $wechatInfo, $spreadId, $login_type, 'wechat'];
        if (!$user) {
            $user = $wechatUserServices->wechatOauthAfter($createData);
        } else {
            //更新用户信息
            $wechatUserServices->wechatUpdata([$user['uid'], $wechatInfo]);
        }
        return $this->getReturnInfo($user);
    }

    /**
     * 新公众号授权登录
     * @param $spreadId
     * @param $login_type
     * @return mixed
     * @throws DataNotFoundException
     * @throws InvalidConfigException
     * @throws ModelNotFoundException
     */
    public function newAuth($spreadId, $login_type)
    {
        $wechatInfo = OfficialAccount::userFromCode();
        if (!isset($wechatInfo['nickname'])) {
            $wechatInfo = OfficialAccount::userService()->get($wechatInfo['openid']);
            if (!isset($wechatInfo['nickname']))
                throw new ValidateException('授权失败');
            if (isset($wechatInfo['tagid_list']))
                $wechatInfo['tagid_list'] = implode(',', $wechatInfo['tagid_list']);
        } else {
            if (isset($wechatInfo['privilege'])) unset($wechatInfo['privilege']);
        }
        $wechatInfo['user_type'] = 'wechat';
        $openid = $wechatInfo['openid'];
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $user = $wechatUserServices->getAuthUserInfo($openid, 'wechat');
        $createData = [$openid, $wechatInfo, $spreadId, $login_type, 'wechat'];
        //获取是否强制绑定手机号
        $storeUserMobile = sys_config('store_user_mobile');
        if ($storeUserMobile && !$user) {
            $userInfoKey = md5($openid . '_' . time() . '_wechat');
            Cache::setTokenBucket($userInfoKey, $createData, 7200);
            return ['key' => $userInfoKey];
        } else if (!$user) {
            $user = $wechatUserServices->wechatOauthAfter($createData);
        } else {
            //更新用户信息
            $wechatUserServices->wechatUpdata([$user['uid'], $wechatInfo]);
        }
        return $this->getReturnInfo($user);
    }

    public function follow()
    {
        $canvas = Canvas::instance();
        $path = 'uploads/follow/';
        $imageType = 'jpg';
        $name = 'follow';
        $siteUrl = sys_config('site_url');
        $imageUrl = $path . $name . '.' . $imageType;
        $canvas->setImageUrl(public_path() . 'statics/qrcode/follow.png')->setImageHeight(720)->setImageWidth(500)->pushImageValue();
        $wechatQrcode = sys_config('wechat_qrcode');
        if (($strlen = stripos($wechatQrcode, 'uploads')) !== false) {
            $wechatQrcode = substr($wechatQrcode, $strlen);
        }
        if (!$wechatQrcode)
            throw new ValidateException('请上传二维码');
        $canvas->setImageUrl($wechatQrcode)->setImageHeight(344)->setImageWidth(344)->setImageLeft(76)->setImageTop(76)->pushImageValue();
        $image = $canvas->setFileName($name)->setImageType($imageType)->setPath($path)->setBackgroundWidth(500)->setBackgroundHeight(720)->starDrawChart();
        return ['path' => $image ? $siteUrl . '/' . $image : ''];
    }

    /**
     * 微信公众号静默授权
     * @param $spread
     * @param bool $notLogin
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function silenceAuth($spread, bool $notLogin = false, string $snsapi = '')
    {
        if ($snsapi) {
            $wechatInfoConfig = $this->getAuthWechatInfo();
        } else {
            $wechatInfoConfig = OfficialAccount::userFromCode();
        }
        $openid = $wechatInfoConfig['openid'];
        try {
            $wechatInfo = OfficialAccount::userService()->get($wechatInfoConfig['openid']);
        } catch (\Throwable $e) {
            $createData = [$openid, [], $spread, '', 'wechat'];
            $userInfoKey = md5($openid . '_' . time() . '_wechat');
            Cache::setTokenBucket($userInfoKey, $createData, 7200);
            return ['auth_login' => 1, 'key' => $userInfoKey];
        }
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $user = $wechatUserServices->getAuthUserInfo($openid, 'wechat');
        if (!$user) {
            $wechatInfo['headimgurl'] = isset($wechatInfo['headimgurl']) && $wechatInfo['headimgurl'] != '' ? $wechatInfo['headimgurl'] : sys_config('h5_avatar');
            $createData = [$openid, $wechatInfo, $spread, '', 'wechat'];
            //获取是否强制绑定手机号
            $storeUserMobile = sys_config('store_user_mobile');
            if ($notLogin || $storeUserMobile) {
                $userInfoKey = md5($openid . '_' . time() . '_wechat');
                Cache::setTokenBucket($userInfoKey, $createData, 7200);
                return ['auth_login' => 1, 'key' => $userInfoKey];
            } else {
                //写入用户信息
                $user = $wechatUserServices->wechatOauthAfter($createData);
            }
        } else {
            //更新用户信息
            $wechatUserServices->wechatUpdata([$user['uid'], ['code' => $spread]]);
        }
        return $this->getReturnInfo($user);
    }

    /**
     * 微信公众号静默授权
     * @param $spread
     * @param $phone
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException|\Psr\SimpleCache\InvalidArgumentException
     */
    public function silenceAuthBindingPhone($key, $phone)
    {
        if (!$key) {
            throw new ValidateException('请刷新页面或者重新授权');
        }
        [$openid, $wechatInfo, $spreadId, $login_type, $userType] = $createData = CacheService::getTokenBucket($key);
        if (!$createData) {
            throw new ValidateException('请刷新页面或者重新授权');
        }
        $wechatInfo['phone'] = $phone;
        /** @var WechatUserServices $wechatUser */
        $wechatUser = app()->make(WechatUserServices::class);
        //更新用户信息
        $user = $wechatUser->wechatOauthAfter([$openid, $wechatInfo, $spreadId, $login_type, $userType]);
        return $this->getReturnInfo($user);
    }

    /**
     * @param array $userData
     * @param string $phone
     * @param string $userType
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    public function appAuth(array $userData, string $phone, string $userType = 'app')
    {
        $openid = $userData['openId'] ?? "";
        $userInfo = [
            'phone' => $phone,
            'unionid' => $userData['unionId'] ?? '',
            'headimgurl' => $userData['avatarUrl'] ?? '',
            'nickname' => $userData['nickName'] ?? '',
            'province' => $userData['province'] ?? '',
            'country' => $userData['country'] ?? '',
            'city' => $userData['city'] ?? '',
            'openid' => $openid,
        ];
        $login_type = $userType;
        $spreadId = $userInfo['spreadId'] ?? "";
        if (!$phone) {
            //获取是否强制绑定手机号
            $storeUserMobile = sys_config('store_user_mobile');
            if ($userInfo['unionid'] && $storeUserMobile) {
                /** @var UserServices $userServices */
                $userServices = app()->make(UserServices::class);
                $uid = $this->dao->value(['unionid' => $userInfo['unionid'], 'is_del' => 0], 'uid');
                $res = $userServices->value(['uid' => $uid], 'phone');
                if (!$uid && !$res) {
                    return false;
                }
            }
            if ($openid && $storeUserMobile) {
                /** @var UserServices $userServices */
                $userServices = app()->make(UserServices::class);
                $uid = $this->dao->value(['openid' => $openid, 'is_del' => 0], 'uid');
                $res = $userServices->value(['uid' => $uid], 'phone');
                if (!$uid && !$res) {
                    return false;
                }
            }
        }
        /** @var WechatUserServices $wechatUser */
        $wechatUser = app()->make(WechatUserServices::class);
        //更新用户信息
        $user = $wechatUser->wechatOauthAfter([$openid, $userInfo, $spreadId, $login_type, $userType]);
        $token = $this->getReturnInfo($user);
        $token['isbind'] = false;
        return $token;
    }

    /**
     * 是否关注
     * @param int $uid
     * @return bool
     */
    public function isSubscribe(int $uid)
    {
        if ($uid) {
            $subscribe = $this->dao->value(['uid' => $uid], 'subscribe') ? true : false;
        } else {
            $subscribe = true;
        }
        return $subscribe;
    }

    /**
     * 更新公众号用户信息
     * @param int $uid
     * @return array
     */
    public function updateUserInfo(int $uid)
    {
        $wechatInfoConfig = OfficialAccount::userFromCode();
        $openid = $wechatInfoConfig['openid'] ?? null;
        try {
            $wechatInfo = OfficialAccount::userService()->get($wechatInfoConfig['openid']);
        } catch (\Throwable $e) {
            throw new ValidateException('更新公众号用户信息失败：' . $e->getMessage());
        }

        if (!$openid) {
            throw new ValidateException('更新公众号用户信息失败：没有获取到openid');
        }

        $wechatInfo['nickname'] = $wechatInfoConfig['nickname'] ?? $wechatInfo['nickname'];
        $wechatInfo['headimgurl'] = $wechatInfoConfig['headimgurl'] ?? $wechatInfo['headimgurl'];

        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $id = $wechatUserServices->value(['openid' => $openid, 'uid' => $uid, 'user_type' => 'wechat'], 'id');
        if (!$id) {
            throw new ValidateException('没有查到用户信息');
        }
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $user = $userService->getUserInfo($uid);
        if (isset($user['status']) && !$user['status']) {
            throw new ValidateException('您已被禁止登录，请联系管理员');
        }
        if ($user) {
            //更新用户信息
            $wechatUserServices->wechatUpdata([$user['uid'], $wechatInfo]);
        }

        return [
            'nickname' => $wechatInfo['nickname'],
            'avatar' => $wechatInfo['headimgurl'],
            'is_complete' => 1
        ];
    }
}
