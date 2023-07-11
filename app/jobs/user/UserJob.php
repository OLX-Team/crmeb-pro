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

namespace app\jobs\user;


use app\services\activity\lottery\LuckLotteryServices;
use app\services\message\wechat\MessageServices;
use app\services\user\UserServices;
use app\services\wechat\WechatUserServices;
use app\services\work\WorkClientServices;
use app\services\work\WorkMemberServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 同步用户
 * Class UserJob
 * @package app\jobs
 */
class UserJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 执行同步数据后
     * @param $openids
     * @return bool
     */
    public function doJob($openids)
    {
        if (!$openids || !is_array($openids)) {
            return true;
        }
        $noBeOpenids = [];
        try {
            /** @var WechatUserServices $wechatUser */
            $wechatUser = app()->make(WechatUserServices::class);
            $noBeOpenids = $wechatUser->syncWechatUser($openids);
        } catch (\Throwable $e) {
            Log::error('更新wechatUser用户信息失败,失败原因:' . $e->getMessage());
        }
        if (!$noBeOpenids) {
            return true;
        }
        try {
            /** @var UserServices $user */
            $user = app()->make(UserServices::class);
            $user->importUser($noBeOpenids);
        } catch (\Throwable $e) {
            Log::error('新增用户失败,失败原因:' . $e->getMessage());
        }
        return true;
    }

    /**
     * 关注推官新用户（发送抽奖消息 、推广用户增加抽奖次数）
     * @param int $uid
     * @param string $openid
     * @param int $spread_uid
     * @return bool
     */
    public function subscribeSpreadLottery(int $uid, string $openid, int $spread_uid)
    {
        /** @var LuckLotteryServices $lotteryServices */
        $lotteryServices = app()->make(LuckLotteryServices::class);
        $lottery = $lotteryServices->getFactorLottery(5);
        if (!$lottery) {
            return true;
        }
        try {
            /** @var MessageServices $messageServices */
            $messageServices = app()->make(MessageServices::class);
            $messageServices->wechatEvent(['third_type' => 'luckLottery-' . $uid, 'lottery' => $lottery], $openid);
        } catch (\Throwable $e) {
            Log::error('发送关注抽奖消息失败，原因：' . $e->getMessage());
        }
        if (!$spread_uid) {
            return true;
        }
        try {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $spreadUser = $userServices->getUserInfo($spread_uid, 'uid,spread_lottery');
            //增加推广用户抽奖次数
            if (!$spreadUser) {
                return true;
            }
            if ($lottery['lottery_num_term'] == 1) {
                $where = ['time' => 'today', 'timeKey' => 'spread_time'];
            } else {
                $where = ['spread_uid' => $spreadUser['uid']];
            }
            $spreadCount = $userServices->count($where);
            if ($spreadCount) {
                $lotterySum = bcmul((string)$spreadCount, (string)$lottery['spread_num'], 0);
                if ($lotterySum < $lottery['lottery_num']) {
                    $update_lottery_num = $spreadUser['spread_lottery'] + (int)$lottery['spread_num'];
                    if ($update_lottery_num > $lottery['lottery_num']) {
                        $update_lottery_num = $lottery['lottery_num'];
                    }
                    $userServices->update($spreadUser['uid'], ['spread_lottery' => $update_lottery_num], 'uid');
                }
            }
        } catch (\Throwable $e) {
            Log::error('增加推广用户抽奖次数失败，原因：' . $e->getMessage());
        }

        return true;

    }

    /**
     * 绑定企业微信成员
     * @param $uid
     * @param $unionid
     * @return bool
     */
    public function bindWorkMember($uid, $unionid)
    {
        if (!$unionid) {
            return true;
        }
        /** @var WorkClientServices $service */
        $service = app()->make(WorkClientServices::class);
        $clienInfo = $service->get(['unionid' => $unionid], ['id', 'external_userid', 'uid'], ['followOne']);
        if ($clienInfo) {
            if (!$clienInfo->uid) {
                $clienInfo->uid = $uid;
                $clienInfo->save();
            }
            if (!empty($clienInfo->followOne->userid)) {
                $userId = $clienInfo->followOne->userid;
                /** @var WorkMemberServices $memberService */
                $memberService = app()->make(WorkMemberServices::class);
                $menber = $memberService->get(['userid' => $userId], ['uid', 'mobile']);
                if (!$menber) {
                    Log::error([
                        'message' => '绑定企业微信成员失败：没有查询到成员身份',
                        'request' => ['uid' => $uid, 'unionid' => $unionid],
                    ]);
                    return true;
                }
                /** @var UserServices $userService */
                $userService = app()->make(UserServices::class);
                if (!$menber->uid && $menber->mobile) {
                    $menberUid = $userService->value(['phone' => $menber->mobile], 'uid');
                    if ($menberUid && $menberUid != $uid) {
                        $menber->uid = $menberUid;
                        $menber->save();
                    }
                }
                $userInfo = $userService->get($uid, ['uid', 'work_uid', 'work_userid']);
                if (!$userInfo) {
                    return true;
                }
                if (!$userInfo->work_userid) {
                    $userInfo->work_userid = $userId;
                    $userInfo->work_uid = $menber->uid;
                    $userInfo->save();
                }
            }
        }
        return true;
    }
}
