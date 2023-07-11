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

namespace app\webscoket\handler;


use app\services\kefu\LoginServices;
use app\services\message\service\StoreServiceRecordServices;
use app\services\message\service\StoreServiceServices;
use app\services\user\UserServices;
use app\webscoket\BaseHandler;
use app\webscoket\Manager;
use app\webscoket\Response;
use crmeb\exceptions\AuthException;

/**
 * Class KefuHandler
 * @package app\webscoket\handler
 */
class KefuHandler extends BaseHandler
{

    /**
     * 客服登陆
     * @param array $data
     * @param Response $response
     * @return Response
     */
    public function login(array $data = [], Response $response)
    {
        if (!isset($data['token']) || !$token = $data['token']) {
            return $response->fail('授权失败!');
        }

        try {
            /** @var LoginServices $services */
            $services = app()->make(LoginServices::class);
            $kefuInfo = $services->parseToken($token);
        } catch (AuthException $e) {
            return $response->fail($e->getMessage());
        }

        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $user = $userService->get($kefuInfo['uid'], ['uid', 'nickname']);
        if (!isset($user['uid'])) {
            return $response->fail('您登录的客服用户不存在');
        }

        /** @var StoreServiceRecordServices $service */
        $service = app()->make(StoreServiceRecordServices::class);
        $service->updateRecord(['to_uid' => $user['uid']], ['online' => 1]);
        /** @var StoreServiceServices $service */
        $service = app()->make(StoreServiceServices::class);
        $service->update(['uid' => $user['uid']], ['online' => 1]);

        return $response->success($user->toArray());
    }

    /**
     * 兼容前端方法
     * @param array $data
     * @param Response $response
     */
    public function kefu_login(array $data = [], Response $response)
    {
        return $this->login($data, $response);
    }

    /**
     * 上下线
     * @param array $data
     * @param Response $response
     */
    public function online(array $data = [], Response $response)
    {
        $online = $data['online'] ?? 0;
        $user = $this->room->get($this->fd);
        if ($user) {
            /** @var StoreServiceServices $service */
            $service = app()->make(StoreServiceServices::class);
            $service->update(['uid' => $user['uid']], ['online' => $online]);
            if ($user['to_uid']) {
                $fd = $this->manager->userFd('', $user['to_uid']);
                //给当前正在聊天的用户发送上下线消息
                $this->manager->pushing($fd, $response->message('online', [
                    'online' => $online,
                    'uid' => $user['uid']
                ])->getData());
            }
        }
    }

    /**
     * 客服转接
     * @param array $data
     * @param Response $response
     * @return \think\response\Json
     */
    public function transfer(array $data, Response $response)
    {
        $data = $data['data'] ?? [];
        $uid = $data['recored']['uid'] ?? 0;
        if ($uid && $this->manager->userFd('', $uid)) {
            $data['recored']['online'] = 1;
        } else {
            $data['recored']['online'] = 0;
        }
        return $response->message('transfer', $data);
    }

    /**
     * 退出登录
     * @param array $data
     * @param Response $response
     */
    public function logout(array $data = [], Response $response)
    {
        $user = $this->room->get($this->fd);
        $uid = $user['uid'] ?? 0;
        if ($uid) {
            /** @var StoreServiceServices $service */
            $service = app()->make(StoreServiceServices::class);
            $service->update(['uid' => $user['uid']], ['online' => 0]);
            /** @var StoreServiceRecordServices $service */
            $service = app()->make(StoreServiceRecordServices::class);
            $service->updateRecord(['to_uid' => $uid], ['online' => 0]);
            $this->manager->pushing($this->manager->userFd(Manager::KEFU_TYPE_NUM), $response->message('online', [
                'online' => 0,
                'uid' => $uid
            ]), $this->fd);
        }
    }
}
