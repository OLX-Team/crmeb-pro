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

namespace app\services\user;

use app\services\BaseServices;
use app\dao\user\UserAuthDao;
use app\services\work\WorkClientServices;
use crmeb\exceptions\AuthException;
use crmeb\services\CacheService;
use crmeb\services\wechat\config\WorkConfig;
use crmeb\utils\JwtAuth;

/**
 *
 * Class UserAuthServices
 * @package app\services\user
 */
class UserAuthServices extends BaseServices
{

    /**
     * UserAuthServices constructor.
     * @param UserAuthDao $dao
     */
    public function __construct(UserAuthDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取授权信息
     * @param $token
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException\
     */
    public function parseToken($token): array
    {
        $md5Token = is_null($token) ? '' : md5($token);

        if ($token === 'undefined') {
            throw new AuthException('请登录', 410000);
        }
        if (!$token || !$tokenData = CacheService::getTokenBucket($md5Token))
            throw new AuthException('请登录', 410000);

        if (!is_array($tokenData) || empty($tokenData) || !isset($tokenData['uid'])) {
            throw new AuthException('请登录', 410000);
        }

        /** @var JwtAuth $jwtAuth */
        $jwtAuth = app()->make(JwtAuth::class);
        //设置解析token
        [$id, $type] = $jwtAuth->parseToken($token);
        try {
            $jwtAuth->verifyToken();
        } catch (\Throwable $e) {
            if (!request()->isCli()) CacheService::clearToken($md5Token);
            throw new AuthException('登录已过期,请重新登录', 410001);
        }

        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $user = $userService->getUserCacheInfo($id);
        if (!$user) throw new AuthException('用户不存在，请重新登陆', 410001);
        if (!$user['status'])
            throw new AuthException('您已被禁止登录，请联系管理员', 410002);


        if (!$user || $user->uid != $tokenData['uid']) {
            if (!request()->isCli()) CacheService::clearToken($md5Token);
            throw new AuthException('登录状态有误,请重新登录', 410002);
        }
        $tokenData['type'] = $type;
        return compact('user', 'tokenData');
    }

    /**
     * 获取企业客户
     * @param string $userid
     * @return array
     */
    public function parseClient(string $userid)
    {
        /** @var WorkConfig $workConfig */
        $workConfig = app()->make(WorkConfig::class);
        $corpId = $workConfig->get('corpId');
        if (!$corpId) {
            throw new AuthException('请先配置企业微信');
        }
        /** @var WorkClientServices $service */
        $service = app()->make(WorkClientServices::class);
        $clientInfo = $service->get(['corp_id' => $corpId, 'external_userid' => $userid]);
        if (!$clientInfo) {
            throw new AuthException('客户信息不存在');
        }

        return $clientInfo->toArray();
    }

}
