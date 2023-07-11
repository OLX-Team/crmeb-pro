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

use app\services\message\service\StoreServiceLogServices;
use app\services\message\service\StoreServiceRecordServices;
use app\services\message\service\StoreServiceServices;
use app\services\order\StoreOrderRefundServices;
use app\services\order\StoreOrderServices;
use app\services\product\product\StoreProductServices;
use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use app\jobs\template\WechatTemplateJob;
use crmeb\services\wechat\Messages;
use crmeb\services\wechat\OfficialAccount;
use crmeb\utils\Arr;
use think\facade\Log;

/**
 * socket 事件基础类
 * Class BaseHandler
 * @package app\webscoket
 */
abstract class BaseHandler
{

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var Room
     */
    protected $room;

    /**
     * @var int
     */
    protected $fd;

    /**
     * 用户聊天端
     * @var int|null
     */
    protected $formType;

    /**
     * 登陆
     * @param array $data
     * @param Response $response
     * @return mixed
     */
    abstract public function login(array $data = [], Response $response);

    /**
     * 事件入口
     * @param $event
     * @return |null
     */
    public function handle($event)
    {
        [$method, $result, $manager, $room] = $event;
        $this->manager = $manager;
        $this->room = $room;
        $this->fd = array_shift($result);
        $this->formType = array_shift($result);
        if (method_exists($this, $method)) {
            if (($method == 'login' || $method == 'kefu_login') && is_string($result[0])) {
                $result[0] = ['token' => $result[0]];
            }
            return $this->{$method}(...$result);
        } else {
            Log::error('socket 回调事件' . $method . '不存在,消息内容为:' . json_encode($result));
            return null;
        }
    }

    /**
     * 聊天事件
     * @param array $data
     * @param Response $response
     * @return bool|\think\response\Json|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function chat(array $data = [], Response $response)
    {
        $user = $this->room->get($this->fd);
        if (!$user) {
            return $response->fail('聊天用户不存在');
        }
        $to_uid = $data['to_uid'] ?? 0;
        $msn_type = $data['type'] ?? 0;
        $msn = $data['msn'] ?? '';
        $formType = $this->formType ?? null;
        //是否为游客
        $isTourist = $data['is_tourist'] ?? 0;
        $tourist_uid = $data['tourist_uid'] ?? 0;
        $isTourist = $isTourist && $tourist_uid;
        $tourist_avatar = $data['tourist_avatar'] ?? '';
        $uid = $isTourist ? $tourist_uid : $user['uid'];
        if (!$to_uid) {
            return $response->message('err_tip', ['msg' => '用户不存在']);
        }
        if ($to_uid == $uid) {
            return $response->message('err_tip', ['msg' => '不能和自己聊天']);
        }

        /** @var StoreServiceLogServices $logServices */
        $logServices = app()->make(StoreServiceLogServices::class);
        if (!in_array($msn_type, StoreServiceLogServices::MSN_TYPE)) {
            return $response->message('err_tip', ['msg' => '格式错误']);
        }
        $msn = trim(strip_tags(str_replace(["\n", "\t", "\r", "&nbsp;"], '', htmlspecialchars_decode($msn))));
        $data = compact('to_uid', 'msn_type', 'msn', 'uid');
        $data['add_time'] = time();
        $data['is_tourist'] = $data['is_tourist'] ?? 0;

        //获取收消息人的所有fd信息
        $toUserFd = $this->manager->userFd($user['type'] == 2 ? 1 : 2, $to_uid);
        $toUser = ['to_uid' => -1];
        $fremaData = [];
        foreach ($toUserFd as $value) {
            if ($frem = $this->room->get($value)) {
                $fremaData[] = $frem;
                //如果当收消息人在和当前发消息人对话中
                if ($frem['to_uid'] == $user['uid']) {
                    $toUser = $frem;
                }
            }
        }
        //是否在线
        //是否和当前用户对话
        $online = $toUserFd && $toUser && $toUser['to_uid'] !== -1;

        $data['type'] = $online ? 1 : 0;
        $data = $logServices->save($data);
        $data = $data->toArray();
        $data['_add_time'] = $data['add_time'];
        $data['add_time'] = strtotime($data['add_time']);
        $_userInfo = [];
        if (!$isTourist) {
            /** @var StoreServiceServices $userService */
            $userService = app()->make(StoreServiceServices::class);
            $_userInfo = $userService->get(['uid' => $data['uid']], ['nickname', 'avatar']);
            if (!$_userInfo) {
                /** @var UserServices $userService */
                $userService = app()->make(UserServices::class);
                $_userInfo = $userService->getUserInfo($data['uid'], 'nickname,avatar');
            }
        }
        if ($isTourist || !$_userInfo) {
            $avatar = sys_config('tourist_avatar');
            $_userInfo['avatar'] = $tourist_avatar ? $tourist_avatar : Arr::getArrayRandKey(is_array($avatar) ? $avatar : []);
            $_userInfo['nickname'] = '游客' . $uid;

        }
        $data['nickname'] = $_userInfo['nickname'];
        $data['avatar'] = $_userInfo['avatar'];

        //商品消息类型
        $data['productInfo'] = [];
        if ($msn_type == StoreServiceLogServices::MSN_TYPE_GOODS && $msn) {
            /** @var StoreProductServices $productServices */
            $productServices = app()->make(StoreProductServices::class);
            $productInfo = $productServices->getProductInfo((int)$msn, 'store_name,IFNULL(sales,0) + IFNULL(ficti,0) as sales,image,slider_image,price,vip_price,ot_price,stock,id');
            $data['productInfo'] = $productInfo ? $productInfo->toArray() : [];
        }
        //订单消息类型
        $data['orderInfo'] = [];
        if ($msn_type == StoreServiceLogServices::MSN_TYPE_ORDER && $msn) {
            /** @var StoreOrderServices $orderServices */
            $orderServices = app()->make(StoreOrderServices::class);
            $order = $orderServices->getUserOrderDetail($msn, $uid);
            if ($order) {
                $order = $orderServices->tidyOrder($order->toArray(), true, true);
                $order['add_time_y'] = date('Y-m-d', $order['add_time']);
                $order['add_time_h'] = date('H:i:s', $order['add_time']);
                $data['orderInfo'] = $order;
            }
        }
        if ($msn_type == StoreServiceLogServices::MSN_TYPE_REFUND_ORDER && $msn) {
            /** @var StoreOrderRefundServices $orderRefundServices */
            $orderRefundServices = app()->make(StoreOrderRefundServices::class);
            $order = $orderRefundServices->refundDetail($msn);
            if ($order) {
                $order['add_time_y'] = date('Y-m-d', $order['add_time']);
                $order['add_time_h'] = date('H:i:s', $order['add_time']);
                $data['orderInfo'] = $order;
            }
        }

        //用户向客服发送消息，判断当前客服是否在登录中
        /** @var StoreServiceRecordServices $serviceRecored */
        $serviceRecored = app()->make(StoreServiceRecordServices::class);
        $unMessagesCount = $logServices->getMessageNum(['uid' => $uid, 'to_uid' => $to_uid, 'type' => 0, 'is_tourist' => $isTourist ? 1 : 0]);
        //记录当前用户和他人聊天记录
        $data['recored'] = $serviceRecored->saveRecord($uid, $to_uid, $msn, $formType ?? 0, $msn_type, $unMessagesCount, $isTourist, $data['nickname'], $data['avatar']);
        //是否在线
        if ($online) {
            $this->manager->pushing($toUserFd, $response->message('reply', $data)->getData());
        } else {
            //用户在线，可是没有和当前用户进行聊天，给当前用户发送未读条数
            if ($toUserFd && $toUser && isset($toUser['to_uid']) && $toUser['to_uid'] != $uid) {
                $data['recored']['nickname'] = $_userInfo['nickname'];
                $data['recored']['avatar'] = $_userInfo['avatar'];

                $this->manager->pushing($toUserFd, $response->message('mssage_num', [
                    'uid' => $uid,
                    'num' => $unMessagesCount,
                    'recored' => $data['recored']
                ])->getData());

            }
            if (!$isTourist) {
                //用户不在线
                /** @var WechatUserServices $wechatUserServices */
                $wechatUserServices = app()->make(WechatUserServices::class);
                $userInfo = $wechatUserServices->getOne(['uid' => $to_uid, 'user_type' => 'wechat'], 'nickname,subscribe,openid,headimgurl');
                if ($userInfo && $userInfo['subscribe'] && $userInfo['openid']) {
                    $description = '您有新的消息，请注意查收！';
                    if ($formType !== null) {
                        $head = '客服接待消息提醒';
                        $url = sys_config('site_url') . '/kefu/mobile_chat?toUid=' . $uid . '&nickname=' . $_userInfo['nickname'];
                    } else {
                        $head = '客服回复消息提醒';
                        $url = sys_config('site_url') . '/pages/customer_list/chat?uid=' . $uid;
                    }
                    $message = Messages::newsMessage($head, $description, $url, $_userInfo['avatar']);
                    $userInfo = $userInfo->toArray();
                    try {
                        OfficialAccount::staffService()->message($message)->to($userInfo['openid'])->send();
                    } catch (\Exception $e) {

                        if ($msn_type == 3) {
                            $msn = '[图片]';
                        } elseif ($msn_type == 4) {
                            $msn = '[语音]';
                        } elseif ($msn_type == 5) {
                            $msn = '[商品]';
                        } elseif ($msn_type == 6) {
                            $msn = '[订单]';
                        } elseif ($msn_type == 7) {
                            $msn = '[退款订单]';
                        }

                        $res = WechatTemplateJob::dispatchDo('sendServiceNoticeNew', [$userInfo['openid'], [
                            'first' => $head,
                            'keyword1' => $formType !== null ? '客服接待' : '客服回复',
                            'keyword2' => '回复中',
                            'keyword3' => date('Y-m-d H:i:s', time()),
                            'remark' => '消息内容:' . $msn . ';点击查看更多消息!'
                        ], $url]);
                        if (!$res) Log::error($userInfo['nickname'] . '发送失败' . $e->getMessage());
                    }
                }
            }
        }
        return $response->message('chat', $data);
    }

    /**
     * 切换用户聊天
     * @param array $data
     * @param Response $response
     * @return \think\response\Json
     */
    public function to_chat(array $data = [], Response $response)
    {
        $toUid = $data['id'] ?? 0;
        $res = $this->room->get($this->fd);
        if ($res && $toUid) {
            $uid = $res['uid'];
            $this->manager->updateTabelField((int)$res['type'], (int)$uid, (int)$toUid);
            //不是游客进入记录
            if (!$res['tourist']) {
                /** @var StoreServiceRecordServices $service */
                $service = app()->make(StoreServiceRecordServices::class);
                $service->update(['user_id' => $uid, 'to_uid' => $toUid], ['mssage_num' => 0]);
                /** @var StoreServiceLogServices $logServices */
                $logServices = app()->make(StoreServiceLogServices::class);
                $logServices->update(['uid' => $toUid, 'to_uid' => $uid], ['type' => 1]);
            }
            return $response->message('mssage_num', ['uid' => $toUid, 'num' => 0, 'recored' => (object)[]]);
        }
    }

    /**
     * 测试原样返回
     * @param array $data
     * @param Response $response
     * @return bool|\think\response\Json|null
     */
    public function test(array $data = [], Response $response)
    {
        return $response->success($data);
    }

    /**
     * 关闭连接触发
     * @param array $data
     * @param Response $response
     */
    public function close(array $data = [], Response $response)
    {
        $uid = $data['uid'] ?? 0;
        if ($uid) {
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
