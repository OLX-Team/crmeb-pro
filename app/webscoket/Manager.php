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


namespace app\webscoket;

use think\Config;
use crmeb\services\CacheService;
use Swoole\Websocket\Frame;
use think\Event;
use think\response\Json;
use think\swoole\Websocket;
use think\swoole\websocket\Room;
use app\webscoket\Room as NowRoom;
use think\swoole\websocket\socketio\Handler;

/**
 * Class Manager
 * @package app\webscoket
 */
class Manager extends Handler
{

    /**
     * @var
     */
    protected $manager;

    /**
     * @var int
     */
    protected $cache_timeout;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var \Redis
     */
    protected $cache;

    /**
     * @var NowRoom
     */
    protected $nowRoom;

    const USER_TYPE = ['admin', 'user', 'kefu', 'store'];

    const KEFU_TYPE_NUM = 2;
    const USER_TYPE_NUM = 1;

    /**
     * Manager constructor.
     * @param Websocket $websocket
     * @param Config $config
     * @param Room $room
     * @param Event $event
     * @param Response $response
     * @param \app\webscoket\Room $nowRoom
     */
    public function __construct(Event $event, Config $config, Websocket $websocket, Response $response, NowRoom $nowRoom)
    {
        parent::__construct($event, $config, $websocket);
        $this->response = $response;
        $this->nowRoom = $nowRoom;
        $this->cache = CacheService::redisHandler();
        $this->nowRoom->setCache($this->cache);
        $this->cache_timeout = intval(app()->config->get('swoole.websocket.ping_timeout', 60000) / 1000) + 2;
    }

    /**
     * @param \think\Request $request
     * @return bool|void
     */
    public function onOpen(\think\Request $request)
    {
        $fd = $this->websocket->getSender();
        $type = $request->get('type');
        $token = $request->get('token');
        $touristUid = $request->get('tourist_uid', '');
        $tourist = !!$touristUid;
        if (!$token || !in_array($type, self::USER_TYPE)) {
            return $this->websocket->close();
        }
        // 只有用户模式下才能使用游客模式
        if ($type !== self::USER_TYPE[1] && $tourist) {
            return $this->websocket->close();
        }
        $types = self::USER_TYPE;
        $this->nowRoom->type(array_flip($types)[$type]);
        try {
            $data = $this->exec($type, 'login', [$fd, $request->get('form_type', null), ['token' => $token, 'tourist' => $tourist], $this->response])->getData();
        } catch (\Throwable $e) {
            return $this->websocket->close();
        }
        if ($tourist) {
            $data['status'] = 200;
            $data['data']['uid'] = $touristUid;
        }
        if ($data['status'] != 200 || !($data['data']['uid'] ?? null)) {
            return $this->websocket->close();
        }
        $this->resetPingTimeout($this->pingInterval + $this->pingTimeout);
        $uid = $data['data']['uid'];
        $type = array_search($type, self::USER_TYPE);
        $this->login($type, $uid, $fd);
        $this->nowRoom->add((string)$fd, $uid, 0, $tourist ? 1 : 0);
        $this->send($fd, $this->response->message('ping', ['now' => time()]));
        return $this->send($fd, $this->response->success());
    }

    public function login($type, $uid, $fd)
    {
        $key = '_ws_' . $type;
        $this->cache->sadd($key, $fd);
        $this->cache->sadd($key . $uid, $fd);
        $this->refresh($type, $uid);
    }

    public function refresh($type, $uid)
    {
        $key = '_ws_' . $type;
        $this->cache->expire($key, 1800);
        $this->cache->expire($key . $uid, 1800);
    }

    public function logout($type, $uid, $fd)
    {
        $key = '_ws_' . $type;
        $this->cache->srem($key, $fd);
        $this->cache->srem($key . $uid, $fd);
    }

    /**
     * 获取当前用户所有的fd
     * @param $type
     * @param string $uid
     * @return array
     */
    public static function userFd($type, $uid = '')
    {
        $key = '_ws_' . $type . $uid;
        return CacheService::redisHandler()->smembers($key) ?: [];
    }

    /**
     * 执行事件调度
     * @param $type
     * @param $method
     * @param $result
     * @return null|Json
     */
    protected function exec($type, $method, $result)
    {
        if (!in_array($type, self::USER_TYPE)) {
            return null;
        }
        if (!is_array($result)) {
            return null;
        }
        /** @var Json $response */
        return $this->event->until('swoole.websocket.' . $type, [$method, $result, $this, $this->nowRoom]);
    }

    /**
     * @param Frame $frame
     * @return bool
     */
    public function onMessage(Frame $frame)
    {
        $fd = $this->websocket->getSender();
        $info = $this->nowRoom->get($fd);

        $result = json_decode($frame->data, true) ?: [];
        if (!isset($result['type']) || !$result['type']) return true;
        $this->resetPingTimeout($this->pingInterval + $this->pingTimeout);
        $this->refresh($info['type'], $info['uid']);
        if ($result['type'] == 'ping') {
            return $this->send($fd, $this->response->message('ping', ['now' => time()]));
        }
        $data = $result['data'] ?? [];
        $frame->uid = $info['uid'];
        /** @var Response $res */
        $res = $this->exec(self::USER_TYPE[$info['type']], $result['type'], [$fd, $result['form_type'] ?? null, $data, $this->response]);

        if ($res) return $this->send($fd, $res);
        return true;
    }

    /**
     * @param int $type
     * @param int $userId
     * @param int $toUserId
     * @param string $field
     */
    public function updateTabelField(int $type, int $userId, int $toUserId, string $field = 'to_uid')
    {
        $fds = self::userFd($type, $userId);
        foreach ($fds as $fd) {
            $this->nowRoom->update($fd, $field, $toUserId);
        }
    }

    /**
     * 发送文本响应
     * @param $fd
     * @param Json $json
     * @return bool
     */
    public function send($fd, \think\response\Json $json)
    {
        return $this->pushing($fd, $json->getData());
    }

    /**
     * 发送
     * @param $data
     * @return bool
     */
    public function pushing($fds, $data, $exclude = null)
    {
        if ($data instanceof \think\response\Json) {
            $data = $data->getData();
        }
        $data = is_array($data) ? json_encode($data) : $data;
        $fds = is_array($fds) ? $fds : [$fds];

        foreach ($fds as $fd) {
            if (!$fd) {
                continue;
            }
            if ($exclude && is_array($exclude) && !in_array($fd, $exclude)) {
                continue;
            } elseif ($exclude && $exclude == $fd) {
                continue;
            }
            $this->websocket->to($fd)->push($data);
        }
        return true;
    }

    /**
     * 关闭连接
     */
    public function onClose()
    {
        $fd = $this->websocket->getSender();
        $tabfd = (string)$fd;
        if ($this->nowRoom->exist($fd)) {
            $data = $this->nowRoom->get($tabfd);
            $this->logout($data['type'], $data['uid'], $fd);
            $this->nowRoom->type($data['type'])->del($tabfd);
            $this->exec(self::USER_TYPE[$data['type']], 'close', [$fd, null, ['data' => $data], $this->response]);
        }
        parent::onClose();
    }
}
