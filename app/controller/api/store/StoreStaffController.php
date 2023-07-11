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
namespace app\controller\api\store;


use app\Request;
use app\services\order\OtherOrderServices;
use app\services\order\store\BranchOrderServices;
use app\services\store\SystemStoreStaffServices;
use app\services\user\UserCardServices;
use app\services\user\UserRechargeServices;
use app\services\user\UserSpreadServices;
use app\services\wechat\WechatCardServices;
use crmeb\services\CacheService;
use crmeb\services\wechat\OfficialAccount;


/**
 * 店员
 * Class StoreStaffController
 * @package app\controller\api\store
 */
class StoreStaffController
{
    /**
     * @var SystemStoreStaffServices
     */
    protected $services;

    /**
     * @var int
     */
    protected $uid;
    /**
     * 门店店员信息
     * @var array
     */
    protected $staffInfo;
    /**
     * 门店id
     * @var int|mixed
     */
    protected $store_id;

    /**
     * 门店店员ID
     * @var int|mixed
     */
    protected $staff_id;

    /**
     * 构造方法
     * StoreStaffController constructor.
     * @param SystemStoreStaffServices $services
     */
    public function __construct(SystemStoreStaffServices $services, Request $request)
    {
        $this->services = $services;
        $this->uid = (int)$request->uid();
        $this->staffInfo = $services->getStaffInfoByUid($this->uid)->toArray();
        $this->store_id = (int)$this->staffInfo['store_id'] ?? 0;
        $this->staff_id = (int)$this->staffInfo['id'] ?? 0;
    }

    /**
     * 获取店员信息
     * @return mixed
     */
    public function info()
    {
        return app('json')->success($this->staffInfo);
    }


    /**
     * 店员｜门店数据统计
     * @param Request $request
     * @return mixed
     */
    public function statistics(Request $request)
    {
        [$is_manager, $time] = $request->getMore([
            ['is_manager', 0],
            ['data', '', '', 'time'],
        ], true);
        if (!$is_manager || !$this->staffInfo['is_manager']) {
            $is_manager = 0;
        }
        $store_id = $this->store_id;
        $staff_id = $is_manager ? 0 : $this->staff_id;
        $data = $this->services->getStoreData($this->uid, $store_id, $staff_id, $time);
        return app('json')->successful($data);
    }


    /**
     * 增长率
     * @param $left
     * @param $right
     * @return int|string
     */
    public function growth($nowValue, $lastValue)
    {
        if ($lastValue == 0 && $nowValue == 0) return [0, 0, 0];
        if ($lastValue == 0) return [$nowValue, round(bcmul(bcdiv($nowValue, 1, 4), 100, 2), 2), 1];
        if ($nowValue == 0) return [$lastValue, round(bcmul(bcdiv($lastValue, 1, 4), 100, 2), 2), 0];
        $differ = bcsub($nowValue, $lastValue, 2);
        $increase_time_status = (float)$differ >= 0 ? 1 : 0;
        return [$differ, bcmul(bcdiv($differ, $lastValue, 4), 100, 2), $increase_time_status];
    }

    /**
     * 店员｜门店统计详情页列表
     * @param $type
     * @param Request $request
     * @return mixed
     */
    public function data($type, Request $request)
    {
        [$is_manager, $start, $stop] = $request->getMore([
            ['is_manager', 0],
            ['start', strtotime(date('Y-m'))],
            ['stop', time()]
        ], true);
        if (!$start || !$stop) {
            return app('json')->fail('请重新选择时间');
        }
        if (!$is_manager || !$this->staffInfo['is_manager']) {
            $is_manager = 0;
        }
        $staff_id = $is_manager ? 0 : $this->staff_id;
        [$start, $stop, $front_start] = getFrontTime((int)$start, (int)$stop);
        $front_stop = (int)bcsub((string)$start, '1');
        $data = $list = [];
        switch ($type) {
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
                /** @var BranchOrderServices $order */
                $order = app()->make(BranchOrderServices::class);
                [$data, $list] = $order->time($this->store_id, $staff_id, $type, [$start, $stop, $front_start, $front_stop]);
                break;
            case 6://付费会员
                /** @var OtherOrderServices $otherOrder */
                $otherOrder = app()->make(OtherOrderServices::class);
                [$data, $list] = $otherOrder->time($this->store_id, $staff_id, [$start, $stop, $front_start, $front_stop]);
                break;
            case 7://充值
                /** @var UserRechargeServices $userRecharge */
                $userRecharge = app()->make(UserRechargeServices::class);
                [$data, $list] = $userRecharge->time($this->store_id, $staff_id, [$start, $stop, $front_start, $front_stop]);
                break;
            case 8://推广用户
                /** @var UserSpreadServices $userSpread */
                $userSpread = app()->make(UserSpreadServices::class);
                [$data, $list] = $userSpread->time($this->store_id, $staff_id, [$start, $stop, $front_start, $front_stop]);
                break;
            case 9://激活会员卡
                /** @var UserCardServices $userCard */
                $userCard = app()->make(UserCardServices::class);
                [$data, $list] = $userCard->time($this->store_id, $staff_id, [$start, $stop, $front_start, $front_stop]);
                break;
            default:
                return app('json')->fail('没有此类型');
        }
        $now = $front = $differ = $growth = $increase_time_status = 0;
        if ($data) {
            [$now, $front] = $data;
            [$differ, $growth, $increase_time_status] = $this->growth($now, $front);
        }
        $data = compact('now', 'front', 'differ', 'growth', 'increase_time_status');
        return app('json')->success(compact('data', 'list'));
    }


    /**
     * 商品分享二维码 推广员
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function code(Request $request, WechatCardServices $cardServices)
    {
        $wechatCard = $cardServices->get(['card_type' => 'member_card', 'status' => 1, 'is_del' => 0]);
        $uid = (int)$request->uid();
        if ($wechatCard && !$request->isApp()) {

            $key = $wechatCard['card_id'] . '_cart_' . $uid;
            $data = CacheService::get($key);
            if (!$data) {
                $result = OfficialAccount::getCardQRCode($wechatCard['card_id'], $uid);
                $data = [
                    'url' => $result['url'] ?? '',
                    'show_qrcode_url' => $result['show_qrcode_url'] ?? ''
                ];
                CacheService::set($key, $data, 1500);
            }
        } else {
            $site_url = sys_config('site_url');
            $url = $site_url ? $site_url . '?spread=' . $uid : '';
            $data = [
                'url' => $url,
                'show_qrcode_url' => $url
            ];
        }
        return app('json')->success($data);
    }

}
