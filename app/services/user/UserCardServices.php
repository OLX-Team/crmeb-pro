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
use app\dao\user\UserCardDao;
use app\services\store\SystemStoreStaffServices;
use app\services\wechat\WechatCardServices;
use app\services\wechat\WechatUserServices;
use crmeb\services\wechat\OfficialAccount;

/**
 * 用户领取卡券
 * Class UserCardServices
 * @package app\services\user
 */
class UserCardServices extends BaseServices
{

    /**
     * 激活会员卡微信字段对应eb_user字段
     * @var string[]
     */
    protected $userField = [
        'USER_FORM_INFO_FLAG_MOBILE' => 'phone',
        'USER_FORM_INFO_FLAG_BIRTHDAY' => 'birthday'
    ];

    /**
     * UserCardServices constructor.
     * @param UserCardDao $dao
     */
    public function __construct(UserCardDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 用户领取微信会员卡事件
     * @param $message
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userGetCard($message)
    {
        if (isset($message['CardId'])) {
            $card_id = $message['CardId'];
            $openid = $message['FromUserName'];
            /** @var WechatCardServices $wechatCardServices */
            $wechatCardServices = app()->make(WechatCardServices::class);
            $card = $wechatCardServices->getOne(['card_id' => $card_id]);
            if ($card) {
                $staffInfo = [];
                $uid = (int)$message['OuterId'];
                if ($uid) {
                    try {
                        /** @var SystemStoreStaffServices $staffServices */
                        $staffServices = app()->make(SystemStoreStaffServices::class);
                        $staffInfo = $staffServices->getStaffInfoByUid($uid);
                    } catch (\Throwable $e) {
                        $staffInfo = [];
                    }
                }
                $data = [
                    'openid' => $openid,
                    'card_id' => $card_id,
                    'code' => $message['UserCardCode'],
                    'wechat_card_id' => $card['id'],
                    'staff_id' => $staffInfo['id'] ?? 0,
                    'store_id' => $staffInfo['store_id'] ?? 0,
                    'spread_uid' => $uid,
                    'add_time' => time()
                ];
                $userCard = $this->dao->getOne(['openid' => $openid, 'card_id' => $card_id, 'is_del' => 0]);
                if ($userCard) {
                    $this->dao->update($userCard['id'], $data);
                } else {
                    $this->dao->save($data);
                }
            }
        }
        return true;
    }

    /**
     * 激活会员事件
     * @param $message
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userSubmitCard($message)
    {
        if (isset($message['CardId'])) {
            $card_id = $message['CardId'];
            $openid = $message['FromUserName'];
            /** @var WechatCardServices $wechatCardServices */
            $wechatCardServices = app()->make(WechatCardServices::class);
            $card = $wechatCardServices->getOne(['card_id' => $card_id]);
            if ($card) {
                $userCard = $this->dao->getOne(['openid' => $openid, 'card_id' => $card_id, 'is_del' => 0]);
                if ($userCard && !$userCard['is_submit']) {
                    $this->transaction(function () use ($openid, $userCard, $card_id) {

                        $data = $this->getWechatCardInfo($card_id, $userCard['code']);
                        /** @var WechatUserServices $wechatUserSerives */
                        $wechatUserSerives = app()->make(WechatUserServices::class);
                        $userInfo = $wechatUserSerives->saveUser($openid, $userCard['spread_uid'], $data['phone']);
                        $this->dao->update($userCard['id'], ['uid' => $userInfo['uid'] ?? 0, 'is_submit' => 1, 'submit_time' => time()]);
                    });
                }
            }
        }
        return true;
    }

    /**
     * 获取用户激活会员卡填写信息
     * @param string $card_id
     * @param string $code
     * @return array
     */
    public function getWechatCardInfo(string $card_id, string $code)
    {
        //获取用户激活填写字段信息
        $cart = OfficialAccount::getMemberCardUser($card_id, $code);
        $formList = $cart['user_info']['common_field_list'] ?? [];
        $userField = $this->userField;
        $fields = array_keys($userField);
        $data = [];
        foreach ($formList as $item) {
            if (in_array($item['name'], $fields)) {
                $data[$userField[$item['name']]] = $item['value'];
            }
        }
        return $data;
    }

    /**
     * 用户删除微信会员卡事件
     * @param $message
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userDelCard($message)
    {
        if (isset($message['CardId'])) {
            $card_id = $message['CardId'];
            $openid = $message['FromUserName'];
            /** @var WechatCardServices $wechatCardServices */
            $wechatCardServices = app()->make(WechatCardServices::class);
            $card = $wechatCardServices->getOne(['card_id' => $card_id]);
            if ($card) {
                $userCard = $this->dao->getOne(['openid' => $openid, 'card_id' => $card_id, 'is_del' => 0]);
                if ($userCard) {
                    $this->dao->update($userCard['id'], ['is_del' => 1, 'del_time' => time()]);
                }
            }
        }
        return true;
    }

    /**
     * 门店推广统计详情列表
     * @param int $store_id
     * @param int $staff_id
     * @param array $time
     * @return array|array[]
     */
    public function time(int $store_id, int $staff_id, array $time = [])
    {
        if (!$time) {
            return [[], []];
        }
        [$start, $stop, $front, $front_stop] = $time;
        $where = ['store_id' => $store_id, 'is_submit' => 1];
        if ($staff_id) {
            $where['staff_id'] = $staff_id;
        }
        $frontPrice = $this->dao->count($where + ['time' => [$front, $front_stop]]);
        $nowPrice = $this->dao->count($where + ['time' => [$start, $stop]]);
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where + ['time' => [$start, $stop]], '*', ['user'], $page, $limit);
        foreach ($list as &$item) {
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
        }
        return [[$nowPrice, $frontPrice], $list];
    }

    /**
     * 获取会员卡列表
     * @param int $uid
     * @param UserServices $userServices
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCardList(array $where, string $field = '*', array $with = ['user'])
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $field, $with, $page, $limit);
        $count = $this->dao->count($where);
        foreach ($list as &$item) {
            $item['submit_time'] = $item['submit_time'] ? date('Y-m-d H:i:s', $item['submit_time']) : '';
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
        }
        return compact('list', 'count');
    }
}
