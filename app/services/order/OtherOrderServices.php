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

namespace app\services\order;


use app\dao\order\OtherOrderDao;
use app\services\BaseServices;
use app\services\pay\PayServices;
use app\services\statistic\TradeStatisticServices;
use app\services\store\finance\StoreFinanceFlowServices;
use app\services\user\member\MemberShipServices;
use app\services\user\UserBillServices;
use app\services\user\UserServices;
use app\services\user\member\MemberCardServices;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;

/**
 * Class OtherOrderServices
 * @package app\services\order
 * @method getDistinctCount(array $where, $field, ?bool $search = true)
 * @method getPayUserCount(int $time, string $channel_type)
 * @method getTrendData($time, $type, $timeType, $str)
 * @method sum(array $where, string $field, bool $search = false) 求和
 */
class OtherOrderServices extends BaseServices
{

    use ServicesTrait;

    /**
     * 订单类型
     * @var string[]
     */
    protected $type = [
        0 => '免费领取',
        1 => '购买会员卡',
        2 => '卡密激活',
        3 => '收银订单',
        4 => '赠送'
    ];

    /**
     * 初始化，获得dao层句柄
     * OtherOrderServices constructor.
     * @param OtherOrderDao $dao
     */
    public function __construct(OtherOrderDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param int $storeId
     * @return int
     */
    public function getvipOrderCount(int $storeId)
    {
        return $this->dao->count(['store_id' => $storeId, 'paid' => 1, 'type' => [0, 1, 2, 4]]);
    }

    /**
     * 生成会员购买订单数据
     * @param array $data
     * @return mixed
     */
    public function addOtherOrderData(array $data)
    {
        if (!$data) throw new ValidateException('数据不能为空');
        $add = [
            'uid' => $data['uid'],
            'store_id' => $data['store_id'] ?? 0,
            'staff_id' => $data['staff_id'] ?? 0,
            'type' => $data['type'] ?? 1,
            'order_id' => $data['order_id'],
            'channel_type' => $data['channel_type'],
            'pay_type' => $data['pay_type'] ?? 0,
            'member_type' => $data['member_type'] ?? 0,
            'member_price' => $data['member_price'] ?? 0.00,
            'pay_price' => $data['pay_price'] ?? 0.00,
            'code' => $data['member_code'] ?? '',
            'vip_day' => $data['vip_day'] ?? 0,
            'is_permanent' => $data['is_permanent'] ?? 0,
            'is_free' => $data['is_free'] ?? 0,
            'overdue_time' => $data['overdue_time'] ?? 0,
            'status' => 0,
            'paid' => $data['paid'] ?? 0,
            'pay_time' => $data['pay_time'] ?? 0,
            'money' => $data['money'] ?? 0,
            'add_time' => time(),
        ];
        return $this->dao->save($add);
    }

    /**
     * 能否领取免费
     * @param int $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function isCanGetFree(int $uid)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        /** @var MemberShipServices $memberShipService */
        $memberShipService = app()->make(MemberShipServices::class);
        /** @var TradeStatisticServices $tradeService */
        $tradeService = app()->make(TradeStatisticServices::class);
        /** @var StoreOrderEconomizeServices $economizeService */
        $economizeService = app()->make(StoreOrderEconomizeServices::class);
        $freeDay = $memberShipService->getVipDay(['type' => "free"]);
        $freeConfig = array();
        $freeConfig['price'] = 0;
        $freeConfig['pre_price'] = 0;
        $freeConfig['title'] = "免费会员";
        $freeConfig['type'] = "free";
        $freeConfig['vip_day'] = $freeDay ? $freeDay : 0;
        $userInfo = $userService->get($uid);
        if ($freeConfig) {
            $freeConfig['is_record'] = 0;
            $record = $this->dao->getOneByWhere(['uid' => $uid, 'is_free' => 1]);
            if ($record) {
                $freeConfig['is_record'] = 1;
            }
        }
        $registerTime = $tradeService->TimeConvert(['start_time' => date('Y-m-d H:i:s', $userInfo['add_time']), 'end_time' => date('Y-m-d H:i:s', time())]);
        $userInfo['register_days'] = $registerTime['days'];
        $userInfo['economize_money'] = $economizeService->sumEconomizeMoney($uid);
        $userInfo['shop_name'] = sys_config('site_name');
        $freeConfig['user_info'] = $userInfo;
        return $freeConfig;
    }


    /**
     * 查询会员卡订单数据
     * @param array $where
     * @param string $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getOne(array $where, string $field = '*')
    {
        return $this->dao->getOne($where, $field);
    }

    /**
     * 创建订单
     * @param int $uid
     * @param string $channelType 支付渠道
     * @param bool $memberType 会员卡类型
     * @param string $payPrice 支付金额
     * @param string $payType 支付方式
     * @param $type 订单类型
     * @param int $money
     * @param int $store_id
     * @param int $staff_id
     * @return mixed
     * @throws \Exception
     */
    public function createOrder(int $uid, string $channelType, $memberType = false, string $payPrice, string $payType, $type, $money = 0, int $store_id = 0, int $staff_id = 0)
    {
        /** @var StoreOrderCreateServices $storeOrderCreateService */
        $storeOrderCreateService = app()->make(StoreOrderCreateServices::class);
        $orderInfo = [
            'uid' => $uid,
            'order_id' => $storeOrderCreateService->getNewOrderId(),
            'pay_price' => $payPrice,
            'pay_type' => $payType,
            'channel_type' => $channelType,
            'member_code' => "",
            'store_id' => $store_id,
            'staff_id' => $staff_id
        ];
        if ($type != 3) { //区别 0：免费领取会员 1：购买会员  2：卡密领取会员  3：线下付款
            if (!$memberType) throw new ValidateException('memberType miss');
            list($memberPrice, $isFree, $isPermanent, $overdueTime, $type, $newMemberRight) = $this->checkPayMemberType($memberType, $payPrice, $type, $uid);
            $orderInfo['member_price'] = $memberPrice;
            $orderInfo['money'] = $memberPrice;
            $orderInfo['vip_day'] = $newMemberRight[$memberType]['vip_day'];
            $orderInfo['member_type'] = $memberType;
            $orderInfo['overdue_time'] = $overdueTime;
            $orderInfo['is_permanent'] = $isPermanent;
            $orderInfo['is_free'] = $isFree;
            $orderInfo['type'] = $type;
            $changeType = "create_member_order";
        } else {
            $orderInfo['type'] = $type;
            $orderInfo['member_code'] = "";
            $changeType = "create_offline_scan_order";
            $orderInfo['money'] = $money ? $money : $payPrice;
        }
        $memberOrder = $this->addOtherOrderData($orderInfo);
        if (!$memberOrder) {
            throw new ValidateException('订单生成失败!');
        }
        /** @var OtherOrderStatusServices $statusService */
        $statusService = app()->make(OtherOrderStatusServices::class);
        $statusService->save([
            'oid' => $memberOrder['id'],
            'change_type' => $changeType,
            'change_message' => '订单生成',
            'change_time' => time(),
            'shop_type' => $type,
        ]);
        return $memberOrder;
    }

    /**
     * 免费卡领取支付
     * @param $orderInfo
     * @return bool
     */
    public function zeroYuanPayment($orderInfo)
    {
        if ($orderInfo['paid']) {
            throw new ValidateException('该订单已支付!');
        }
        /** @var MemberShipServices $memberShipServices */
        $memberShipServices = app()->make(MemberShipServices::class);
        $member_type = $memberShipServices->value(['id' => $orderInfo['member_type']], 'type');
        if ($member_type != 'free') {
            throw new ValidateException('支付失败!');
        }
        $res = $this->paySuccess($orderInfo, 'yue');//余额支付成功
        return $res;

    }

    /**
     * 会员卡支付成功
     * @param array $orderInfo
     * @param string $paytype
     * @return bool
     */
    public function paySuccess(array $orderInfo, string $paytype = PayServices::WEIXIN_PAY, array $other = [])
    {
        /** @var OtherOrderStatusServices $statusService */
        $statusService = app()->make(OtherOrderStatusServices::class);
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        /** @var UserBillServices $userBillServices */
        $userBillServices = app()->make(UserBillServices::class);
        /** @var MemberShipServices $memberShipServices */
        $memberShipServices = app()->make(MemberShipServices::class);
        $orderInfo['member_type'] = $memberShipServices->value(['id' => $orderInfo['member_type']], 'type');
        switch ($orderInfo['type']) {
            case 0 :
            case 1:
            case 2 :
                $res1 = $userServices->setMemberOverdueTime($orderInfo['vip_day'], $orderInfo['uid'], 1, $orderInfo['member_type']);
                break;
            case 3:
                $res1 = true;
                break;
        }
        if ($paytype == PayServices::ALIAPY_PAY && isset($other['trade_no'])) {
            $updata['trade_no'] = $other['trade_no'];
        }
        $updata['paid'] = 1;
        $updata['pay_type'] = $paytype;
        $updata['pay_time'] = time();
        $res2 = $this->dao->update($orderInfo['id'], $updata);
        $res3 = $statusService->save([
            'oid' => $orderInfo['id'],
            'change_type' => 'pay_success',
            'change_message' => '用户付款成功',
            'shop_type' => $orderInfo['type'],
            'change_time' => time()
        ]);
        //记录流水账单
        if ($orderInfo['store_id'] > 0) {
            /** @var StoreFinanceFlowServices $storeFinanceFlowServices */
            $storeFinanceFlowServices = app()->make(StoreFinanceFlowServices::class);
            $orderInfo['pay_type'] = $paytype;
            $orderInfo['pay_time'] = time();
            $storeFinanceFlowServices->setFinance($orderInfo, 3);
        }

        //支付成功后发送消息
        event('user.vipPay', [$orderInfo]);
//        OtherOrderJob::dispatch([$orderInfo]);

        //用户推送消息事件
//        event('notice.notice', [$orderInfo, 'order_pay_success']);
        //支付成功给客服发送消息
//        event('notice.notice', [$orderInfo, 'admin_pay_success_code']);
        $res = $res1 && $res2 && $res3;
        return false !== $res;
    }

    /**
     * 修改
     * @param array $where
     * @param array $data
     * @return mixed
     */
    public function update(array $where, array $data)
    {
        return $this->dao->update($where, $data);
    }

    /**
     * 购买会员卡数据校验
     * @param $memberType
     * @param $pay_price
     * @param $type
     * @return array
     */
    public function checkPayMemberType(string $memberType, string $payPrice, string $type, $uid)
    {
        /** @var MemberCardServices $memberCardService */
        $memberCardService = app()->make(MemberCardServices::class);
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $userInfo = $userService->get($uid);
        if ($userInfo['is_money_level'] > 0 && $userInfo['is_ever_level'] > 0) throw new ValidateException('您已是永久会员无需再购买!');
        $newMemberRight = $memberCardService->getMemberTypeValue();
        if (!array_key_exists($memberType, $newMemberRight)) throw new ValidateException('该会员卡暂时无法购买!');
        $memberTypes = $newMemberRight[$memberType]['type'] ?? '';
        $price = $newMemberRight[$memberType]['pre_price'];
        if ($payPrice != $price) throw new ValidateException('请核实价格!');
        if ($memberTypes == 'free' && $newMemberRight[$memberType]['vip_day'] <= 0) throw new ValidateException('网络错误!');
        if ($userInfo['overdue_time'] > time()) {
            $time = $userInfo['overdue_time'];
        } else {
            $time = time();
        }
        switch ($memberTypes) {
            case "free"://免费会员
                $isCanGetFree = $this->isCanGetFree($uid);
                if ($isCanGetFree['is_record'] == 1) throw new ValidateException('您已经领取过免费会员!');
                $memberPrice = 0.00; //会员卡价格
                $isFree = 1;//代表免费
                $isPermanent = 0;//代表非永久
                $overdueTime = bcadd(bcmul(abs($newMemberRight[$memberType]['vip_day']), "86400", 0), $time, 0);
                break;
            case "ever":
                $memberPrice = $price;
                $isFree = 0;
                $isPermanent = 1;
                $overdueTime = -1;
                break;
            default:
                $memberPrice = $price;
                $isFree = 0;
                $isPermanent = 0;
                $overdueTime = bcadd(bcmul(abs($newMemberRight[$memberType]['vip_day']), 86400, 0), $time, 0);
                break;
        }
        return [$memberPrice, $isFree, $isPermanent, $overdueTime, $type, $newMemberRight];
    }

    /**
     * 根据查询用户购买会员金额
     * @param array $where
     * @return mixed
     */
    public function getMemberMoneyByWhere(array $where, string $sumField, string $selectType, string $group = "")
    {
        switch ($selectType) {
            case "sum" :
                return $this->dao->getWhereSumField($where, $sumField);
            case "group" :
                return $this->dao->getGroupField($where, $sumField, $group);
        }
    }

    /**
     * 线下收银列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getScanOrderList(array $where)
    {
        $where['type'] = 3;
        $where['paid'] = 1;
        [$page, $limit] = $this->getPageValue();
        if ($where['add_time']) {
            [$startTime, $endTime] = explode('-', $where['add_time']);
            if ($startTime || $endTime) {
                $startTime = strtotime($startTime);
                $endTime = strtotime($endTime . ' 23:59:59');
                $where['add_time'] = [$startTime, $endTime];
            }
        }
        if ($where['name']) {
            /** @var UserServices $userService */
            $userService = app()->make(UserServices::class);
            $userInfo = $userService->getUserInfoList(['nickname' => $where['name']], "uid");
            if ($userInfo) $where['uid'] = array_column($userInfo, 'uid');
        }
        $list = $this->dao->getScanOrderList($where, $page, $limit);
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        if ($list) {
            $userInfos = $userService->getColumn([['uid', 'IN', array_unique(array_column($list, 'uid'))]], 'uid,phone,nickname', 'uid');
            foreach ($list as &$v) {
                $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                $v['pay_time'] = $v['pay_time'] ? date('Y-m-d H:i:s', $v['pay_time']) : '';
                $v['phone'] = $userInfos[$v['uid']]['phone'] ?? '';
                $v['nickname'] = $userInfos[$v['uid']]['nickname'] ?? '';
                switch ($v['pay_type']) {
                    case "yue" :
                        $v['pay_type'] = "余额";
                        break;
                    case "weixin" :
                        $v['pay_type'] = "微信";
                        break;
                    case "alipay" :
                        $v['pay_type'] = "支付宝";
                        break;
                }
                $v['true_price'] = bcsub($v['money'], $v['pay_price'], 2);
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取会员记录
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMemberRecord(array $where, int $limit = 0)
    {
        $where['type'] = [0, 1, 2, 4];
        if (isset($where['add_time']) && $where['add_time']) {
            $where['time'] = $where['add_time'];
            unset($where['add_time']);
        }
        if ($limit) {
            [$page] = $this->getPageValue();
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        $list = $this->dao->getMemberRecord($where, '*', ['user', 'staff'], $page, $limit);
        if ($list) {
            /** @var MemberShipServices $memberShipService */
            $memberShipService = app()->make(MemberShipServices::class);
            $shipInfo = $memberShipService->getColumn([], 'title,type', 'id');
            foreach ($list as &$v) {
                $v['overdue_time'] = $v['member_type'] == 'ever' || ($shipInfo[$v['member_type']]['type'] ?? '') == 'ever' ? '永久' : ($v['overdue_time'] ? date('Y-m-d H:i:s', $v['overdue_time']) : '');
                $v['member_type'] = $v['member_type'] ? ($shipInfo[$v['member_type']]['title'] ?? '') : ($this->type[$v['type']] ?? '其他');
                $v['pay_time'] = $v['pay_time'] ? date('Y-m-d H:i:s', $v['pay_time']) : '';
                $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
                switch ($v['pay_type']) {
                    case "yue" :
                        $v['pay_type'] = "余额";
                        break;
                    case "weixin" :
                        $v['pay_type'] = "微信";
                        break;
                    case "alipay" :
                        $v['pay_type'] = "支付宝";
                        break;
                    case "admin" :
                        $v['pay_type'] = "后台赠送";
                        break;
                }
                if ($v['type'] == 0) $v['pay_type'] = "免费领取";
                if ($v['type'] == 2) {
                    $v['pay_type'] = "卡密领取";
                    $v['member_type'] = "卡密激活";
                }
                if ($v['type'] == 1 && $v['is_free'] == 1) $v['pay_type'] = "免费领取";
                $v['user']['overdue_time'] = isset($v['user']['overdue_time']) ? (date('Y-m-d', $v['user']['overdue_time']) == "1970-01-01" ? "" : date('Y-m-d H:i:s', $v['user']['overdue_time'])) : '';
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 门店付费会员统计详情列表
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
        $where = ['store_id' => $store_id, 'paid' => 1, 'type' => [0, 1, 2, 4]];
        if ($staff_id) {
            $where['staff_id'] = $staff_id;
        }
        $frontPrice = $this->dao->sum($where + ['time' => [$front, $front_stop]], 'pay_price', true);
        $nowPrice = $this->dao->sum($where + ['time' => [$start, $stop]], 'pay_price', true);
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getMemberRecord($where + ['time' => [$start, $stop]], 'id,order_id,uid,pay_price,add_time', ['user' => function ($query) {
            $query->field(['uid', 'avatar', 'nickname', 'phone'])->bind([
                'avatar' => 'avatar',
                'nickname' => 'nickname',
                'phone' => 'phone'
            ]);
        }], $page, $limit);
        foreach ($list as &$item) {
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
        }
        return [[$nowPrice, $frontPrice], $list];
    }

}
