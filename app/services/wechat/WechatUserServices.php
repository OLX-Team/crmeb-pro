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

use app\jobs\user\UserJob;
use app\services\BaseServices;
use app\dao\wechat\WechatUserDao;
use app\services\user\LoginServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use crmeb\exceptions\AuthException;
use crmeb\services\wechat\OfficialAccount;
use think\exception\ValidateException;

/**
 *
 * Class WechatUserServices
 * @package app\services\wechat
 * @method delete($id, ?string $key = null)  删除
 * @method update($id, array $data, ?string $key = null) 更新数据
 * @method getColumn(array $where, string $field, string $key = '') 获取某个字段数组
 * @method get($id, ?array $field = []) 用主键获取一条数据
 * @method getOne(array $where, ?string $field = '*', array $with = []) 获得一条数据
 * @method value(array $value, string $key) 获取一条数据
 * @method getWechatTrendData($time, $where, $timeType, $key)
 * @method getWechatOpenid(int $uid, string $userType = 'wechat') 获取微信公众号openid
 */
class WechatUserServices extends BaseServices
{

    /**
     * WechatUserServices constructor.
     * @param WechatUserDao $dao
     */
    public function __construct(WechatUserDao $dao)
    {
        $this->dao = $dao;
    }


    public function getColumnUser($user_ids, $column, $key, string $user_type = 'wechat')
    {
        return $this->dao->getColumn([['uid', 'IN', $user_ids], ['user_type', '=', $user_type]], $column, $key);
    }

    /**
     * 获取单个微信用户
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWechatUserInfo(array $where, $field = '*')
    {
        return $this->dao->getOne($where, $field);
    }

    /**
     * 用uid获得 微信openid
     * @param $uid
     * @return mixed
     */
    public function uidToOpenid(int $uid, string $userType = 'wechat')
    {
        return $this->dao->value(['uid' => $uid, 'user_type' => $userType], 'openid');
    }


    /**
     *  用openid获得uid
     * @param $openid
     * @param string $openidType
     * @return mixed
     */
    public function openidTouid($openid, $openidType = 'openid')
    {
        $uid = $this->dao->value([[$openidType, '=', $openid], ['user_type', '<>', 'h5']], 'uid');
        if (!$uid)
            throw new AdminException('对应的uid不存在');
        return $uid;
    }

    /**
     * 用户取消关注
     * @param $openid
     * @return bool
     */
    public function unSubscribe($openid)
    {
        if (!$this->dao->update($openid, ['subscribe' => 0, 'subscribe_time' => time()], 'openid'))
            throw new AdminException('取消关注失败');
        return true;
    }

    /**
     * 更新微信用户信息
     * @param $message
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveUserV1($message)
    {
        $openid = $message->FromUserName;
        if ($this->getWechatUserInfo(['openid' => $openid])) {
            $this->updateWecahtUser($openid);
        } else {
            $this->setWecahtUser($openid);
        }
        return true;
    }

    /**
     * 用户存在就更新 不存在就添加
     * @param $openid
     * @param int $spid
     * @param string $phone
     * @return \app\services\user\User|array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveUser($openid, $spid = 0, $phone = '')
    {
        $is_new = false;
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $wechatUser = $this->getWechatUserInfo(['openid' => $openid]);
        if ($wechatUser && $wechatUser['uid']) {
            $userInfo = $userServices->getUserInfo((int)$wechatUser['uid']);
            //无关注只是授权生成用户
            if (!$wechatUser['subscribe_time'] && $wechatUser['uid']) {
                $is_new = true;
                $spid = $userInfo['spread_uid'] ?? 0;
            }
            $this->updateWecahtUser($openid);
        } else {
            $userInfo = $this->setNewUser($openid, $spid, ['phone' => $phone]);
            $is_new = true;
        }
        if ($is_new) {
            UserJob::dispatchDo('subscribeSpreadLottery', [(int)$userInfo['uid'], $openid, (int)$spid]);
        }
        return $userInfo;
    }

    /**
     * 更新用户信息
     * @param $openid
     * @param string $phone
     * @return bool
     */
    public function updateWecahtUser($openid)
    {
        try {
            $userInfo = OfficialAccount::getUserInfo($openid);
        } catch (\Throwable $e) {
            $userInfo = [];
        }
        if (isset($userInfo['nickname']) && $userInfo['nickname']) {
            $userInfo['nickname'] = filter_emoji($userInfo['nickname']);
        } else {
            if (isset($userInfo['nickname'])) unset($userInfo['nickname']);
        }
        if (isset($userInfo['tagid_list'])) {
            $userInfo['tagid_list'] = implode(',', $userInfo['tagid_list']);
        }
        if ($userInfo && !$this->dao->update($openid, $userInfo, 'openid'))
            throw new AdminException('更新失败');
        return true;
    }

    /**
     * 写入微信用户信息
     * @param $openid
     * @param int $uid
     * @return bool
     */
    public function setWecahtUser($openid, int $uid = 0)
    {
        try {
            $wechatInfo = OfficialAccount::getUserInfo($openid);
        } catch (\Throwable $e) {
            $wechatInfo = [];
        }
        if (!isset($wechatInfo['openid']))
            throw new ValidateException('请关注公众号!');
        if (isset($wechatInfo['nickname']) && $wechatInfo['nickname']) {
            $wechatInfo['nickname'] = filter_emoji($wechatInfo['nickname']);
        } else {
            mt_srand();
            $wechatInfo['nickname'] = 'wx' . rand(100000, 999999);
        }
        if (isset($wechatInfo['tagid_list'])) {
            $wechatInfo['tagid_list'] = implode(',', $wechatInfo['tagid_list']);
        }
        $wechatInfo['user_type'] = 'wechat';
        $wechatInfo['uid'] = $uid;
        $wechatInfo['add_time'] = time();
        if ($wechatInfo && !$this->dao->save($wechatInfo)) {
            throw new AdminException('用户储存失败!');
        }
        return true;
    }

    /**
     * 添加新用户
     * @param $openid
     * @param int $spreadId
     * @param array $append 追加字段
     * @return \app\services\user\User|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setNewUser($openid, $spreadId = 0, array $append = [])
    {

        try {
            $wechatInfo = OfficialAccount::getUserInfo($openid);
        } catch (\Throwable $e) {
            $wechatInfo = [];
        }
        if (!isset($wechatInfo['openid']))
            throw new ValidateException('请关注公众号!');

        if (isset($wechatInfo['nickname']) && $wechatInfo['nickname']) {
            $wechatInfo['nickname'] = $wechatInfo['nickname'];
            $wechatInfo['is_complete'] = 1;
        } else {
            //昵称不存在的信息不完整
            $wechatInfo['is_complete'] = 0;
            mt_srand();
            $wechatInfo['nickname'] = 'wx' . rand(100000, 999999);
        }
        if (isset($wechatInfo['tagid_list'])) {
            $wechatInfo['tagid_list'] = implode(',', $wechatInfo['tagid_list']);
        }

        $uid = 0;
        $userType = 'wechat';
        $userInfo = [];
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if (isset($append['phone']) && $append['phone']) {
            $userInfo = $userServices->getOne(['phone' => $append['phone']]);
            $wechatInfo['phone'] = $append['phone'];
        }
        if (!$userInfo) {
            if (isset($wechatInfo['unionid']) && $wechatInfo['unionid']) {
                $uid = $this->dao->value(['unionid' => $wechatInfo['unionid']], 'uid');
                if ($uid) {
                    $userInfo = $userServices->getOne(['uid' => $uid]);
                }
            } else {
                $userInfo = $this->getAuthUserInfo($openid, $userType);
            }
        }
        if ($userInfo) {
            $uid = (int)$userInfo['uid'];
            if (isset($userInfo['status']) && !$userInfo['status'])
                throw new ValidateException('您已被禁止登录，请联系管理员');
        }
        $wechatInfo['user_type'] = $userType;
        if ($userInfo) {
            //更新用户表和wechat_user表
            //判断该类性用户在wechatUser中是否存在
            $wechatUser = $this->dao->getOne(['uid' => $uid, 'user_type' => $userType]);
            if ($wechatUser) {
                $wechatUser = $this->dao->getOne(['openid' => $openid]);
            }
            /** @var LoginServices $loginService */
            $loginService = app()->make(LoginServices::class);
            $this->transaction(function () use ($openid, $loginService, $wechatInfo, $userInfo, $uid, $userType, $spreadId, $wechatUser) {
                $wechatInfo['code'] = $spreadId;
                $wechatInfo['uid'] = $uid;
                $loginService->updateUserInfo($wechatInfo, $userInfo);
                if ($wechatUser) {
                    if (isset($append['phone']) && $append['phone'] && $wechatUser['openid'] != $openid) {
                        throw new ValidateException('该手机号已被注册');
                    }
                    if (!$this->dao->update($wechatUser['id'], $wechatInfo, 'id')) {
                        throw new ValidateException('更新数据失败');
                    }
                } else {
                    if (!$this->dao->save($wechatInfo)) {
                        throw new ValidateException('写入信息失败');
                    }
                }
            });
        } else {
            //user表没有用户,wechat_user表没有用户创建新用户
            //不存在则创建用户
            $userInfo = $this->transaction(function () use ($openid, $userServices, $wechatInfo, $spreadId, $userType) {
                $userInfo = $userServices->setUserInfo($wechatInfo, (int)$spreadId, $userType);
                if (!$userInfo) {
                    throw new AuthException('生成User用户失败!');
                }
                $wechatInfo['uid'] = $userInfo->uid;
                $wechatInfo['add_time'] = $userInfo->add_time;
                $wechatUser = $this->dao->getOne(['openid' => $openid]);
                if ($wechatUser) {
                    if (!$this->dao->update($wechatUser['id'], $wechatInfo, 'id')) {
                        throw new ValidateException('更新数据失败');
                    }
                } else {
                    if (!$this->dao->save($wechatInfo)) {
                        throw new AuthException('生成微信用户失败!');
                    }
                }
                return $userInfo;
            });
        }
        return $userInfo;
    }

    /**
     * 授权后获取用户信息
     * @param $openid
     * @param $user_type
     */
    public function getAuthUserInfo($openid, $user_type)
    {
        $user = [];
        //兼容老用户
        $uids = $this->dao->getColumn(['unionid|openid' => $openid, 'is_del' => 0], 'uid,user_type', 'user_type');
        if ($uids) {
            $uid = $uids[$user_type]['uid'] ?? 0;
            if (!$uid) {
                $ids = array_column($uids, 'uid');
                $uid = $ids[0];
            }
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $user = $userServices->getUserInfo($uid);
            if (isset($user['status']) && !$user['status'])
                throw new ValidateException('您已被禁止登录，请联系管理员');
        }
        return $user;
    }

    /**
     * 更新微信用户信息
     * @param $event
     * @return bool
     */
    public function wechatUpdata($data)
    {
        [$uid, $userData] = $data;
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if (!$userInfo = $userServices->getUserInfo($uid)) {
            return false;
        }
        /** @var LoginServices $loginService */
        $loginService = app()->make(LoginServices::class);
        $loginService->updateUserInfo($userData, $userInfo);
        //更新用户信息
        /** @var WechatUserServices $wechatUser */
        $wechatUser = app()->make(WechatUserServices::class);

        $wechatUserInfo = [];
        if (isset($userData['nickname']) && $userData['nickname']) $wechatUserInfo['nickname'] = filter_emoji($userData['nickname'] ?? '');//姓名
        if (isset($userData['headimgurl']) && $userData['headimgurl']) $wechatUserInfo['headimgurl'] = $userData['headimgurl'] ?? '';//头像
        if (isset($userData['sex']) && $userData['sex']) $wechatUserInfo['sex'] = $userData['gender'] ?? '';//性别
        if (isset($userData['language']) && $userData['language']) $wechatUserInfo['language'] = $userData['language'] ?? '';//语言
        if (isset($userData['city']) && $userData['city']) $wechatUserInfo['city'] = $userData['city'] ?? '';//城市
        if (isset($userData['province']) && $userData['province']) $wechatUserInfo['province'] = $userData['province'] ?? '';//省份
        if (isset($userData['country']) && $userData['country']) $wechatUserInfo['country'] = $userData['country'] ?? '';//国家
        if (!empty($wechatUserInfo['nickname']) || !empty($wechatUserInfo['headimgurl'])) {
            $wechatUserInfo['is_complete'] = 1;
        } else {
            $wechatUserInfo['is_complete'] = 0;
        }
        if ($wechatUserInfo) {
            if (isset($userData['openid']) && $userData['openid'] && false === $wechatUser->update(['uid' => $userInfo['uid'], 'openid' => $userData['openid']], $wechatUserInfo)) {
                throw new ValidateException('更新失败');
            }
        }
        return true;
    }

    /**
     * 微信授权成功后
     * @param $event
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function wechatOauthAfter(array $data)
    {
        [$openid, $wechatInfo, $spreadId, $login_type, $userType] = $data;
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if ($spreadId && !$userServices->userExist((int)$spreadId)) {
            $spreadId = 0;
        }
        //删除多余字段
        unset($wechatInfo['subscribe_scene'], $wechatInfo['qr_scene'], $wechatInfo['qr_scene_str']);
        if ($login_type) {
            $wechatInfo['login_type'] = $login_type;
        }
        if (!isset($wechatInfo['nickname'])) {
            if (isset($wechatInfo['phone']) && $wechatInfo['phone']) {
                $wechatInfo['nickname'] = substr_replace($wechatInfo['phone'], '****', 3, 4);
            } else {
                mt_srand();
                $wechatInfo['nickname'] = 'wx' . rand(100000, 999999);
            }
        } else {
            $wechatInfo['is_complete'] = 1;
            $wechatInfo['nickname'] = filter_emoji($wechatInfo['nickname']);
        }
        //统一用户处理：1：同一手机号用户 2：开放平台 3：openid
        $userInfo = [];
        if (isset($wechatInfo['phone']) && $wechatInfo['phone']) {
            $userInfo = $userServices->getOne(['phone' => $wechatInfo['phone']]);
        }
        if (!$userInfo) {
            if (isset($wechatInfo['unionid']) && $wechatInfo['unionid']) {
                $uid = $this->dao->value(['unionid' => $wechatInfo['unionid'], 'is_del' => 0], 'uid');
                if ($uid) {
                    $userInfo = $userServices->getOne(['uid' => $uid]);
                }
            } else {
                $userInfo = $this->getAuthUserInfo($openid, $userType);
            }
        }
        $uid = (int)($userInfo['uid'] ?? 0);
        $wechatInfo['user_type'] = $userType;
        //user表存在和wechat_user表同时存在
        return $this->transaction(function () use ($openid, $uid, $userInfo, $wechatInfo, $userServices, $spreadId, $userType) {
            $wechatInfo['code'] = $spreadId;
            $wechatInfo['uid'] = $uid;
            if ($userInfo) {
                if (isset($userInfo['status']) && !$userInfo['status'])
                    throw new ValidateException('您已被禁止登录，请联系管理员');
                //更新用户表
                /** @var LoginServices $loginService */
                $loginService = app()->make(LoginServices::class);
                $loginService->updateUserInfo($wechatInfo, $userInfo);
            } else {
                //新增用户表
                $userInfo = $userServices->setUserInfo($wechatInfo, (int)$spreadId, $userType);
                if (!$userInfo) {
                    throw new AuthException('生成User用户失败!');
                }
                //用户绑定客户事件
                if (!empty($wechatInfo['unionid'])) {
                    event('user.client', [$userInfo['uid'], $wechatInfo['unionid']]);
                }
                //用户绑定成员事件
                if (!empty($userInfo['phone'])) {
                    event('user.work', [$userInfo['uid'], $userInfo['phone']]);
                }
            }
            $uid = $userInfo['uid'];
            $wechatInfo['uid'] = $userInfo->uid;
            $wechatInfo['add_time'] = $userInfo->add_time;

            //判断该类性用户在wechatUser中是否存在
            $wechatUser = $this->dao->getOne(['uid' => $uid, 'user_type' => $userType, 'is_del' => 0]);
            if (!$wechatUser) {
                $wechatUser = $this->dao->getOne(['openid' => $openid, 'is_del' => 0]);
            }
            if ($wechatUser) {
                if (isset($wechatInfo['phone']) && $wechatInfo['phone'] && $wechatUser['openid'] != $openid) {
                    throw new ValidateException('该手机号已被注册');
                }
                if (!$this->dao->update($wechatUser['id'], $wechatInfo, 'id')) {
                    throw new ValidateException('更新数据失败');
                }
            } else {
                if (!$this->dao->save($wechatInfo)) {
                    throw new AuthException('生成微信用户失败!');
                }
            }
            return $userInfo;
        });
    }

    /**
     * 更新用户信息（同步）
     * @param array $openids
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function syncWechatUser(array $openids)
    {
        if (!$openids) {
            return [];
        }
        $wechatUser = $this->dao->getList([['openid', 'in', $openids]]);
        $noBeOpenids = $openids;
        if ($wechatUser) {
            $beOpenids = array_column($wechatUser, 'openid');
            $noBeOpenids = array_diff($openids, $beOpenids);
            if ($beOpenids) {
                $data = [];
                foreach ($beOpenids as $openid) {
                    try {
                        $info = OfficialAccount::getUserInfo($openid);
                    } catch (\Throwable $e) {
                        $info = [];
                    }
                    if (!$info) continue;
                    $data['subscribe'] = $info['subscribe'] ?? 1;
                    if (($info['subscribe'] ?? 1) == 1) {
                        $data['unionid'] = $info['unionid'] ?? '';
                        $data['nickname'] = $info['nickname'] ?? '';
                        $data['sex'] = $info['sex'] ?? 0;
                        $data['language'] = $info['language'] ?? '';
                        $data['city'] = $info['city'] ?? '';
                        $data['province'] = $info['province'] ?? '';
                        $data['country'] = $info['country'] ?? '';
                        $data['headimgurl'] = $info['headimgurl'] ?? '';
                        $data['subscribe_time'] = $info['subscribe_time'] ?? '';
                        $data['groupid'] = $info['groupid'] ?? 0;
                        $data['remark'] = $info['remark'] ?? '';
                        $data['tagid_list'] = isset($info['tagid_list']) && $info['tagid_list'] ? implode(',', $info['tagid_list']) : '';
                    }
                    $this->dao->update(['openid' => $info['openid']], $data);
                }
            }
        }
        return $noBeOpenids;
    }
}
