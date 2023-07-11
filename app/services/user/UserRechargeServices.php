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

use app\jobs\user\MicroPayOrderJob;
use app\services\BaseServices;
use app\dao\user\UserRechargeDao;
use app\services\pay\PayServices;
use app\services\pay\RechargeServices;
use app\services\store\finance\StoreFinanceFlowServices;
use app\services\system\CapitalFlowServices;
use app\services\system\config\SystemGroupDataServices;
use crmeb\exceptions\AdminException;
use crmeb\traits\ServicesTrait;
use crmeb\services\{AliPayService, FormBuilder as Form, wechat\Payment};
use think\exception\ValidateException;
use think\facade\Route as Url;

/**
 *
 * Class UserRechargeServices
 * @package app\services\user
 * @method be($map, string $field = '') 查询一条数据是否存在
 * @method getDistinctCount(array $where, $field, ?bool $search = true)
 * @method getTrendData($time, $type, $timeType, $str)
 */
class UserRechargeServices extends BaseServices
{

    use ServicesTrait;

    /**
     * UserRechargeServices constructor.
     * @param UserRechargeDao $dao
     */
    public function __construct(UserRechargeDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取单条数据
     * @param int $id
     * @param array $field
     */
    public function getRecharge(int $id, array $field = [])
    {
        return $this->dao->get($id, $field);
    }

    /**
     * @param int $storeId
     * @return int
     */
    public function getRechargeCount(int $storeId)
    {
        return $this->dao->count(['store_id' => $storeId, 'paid' => 1]);
    }

    /**
     * 获取统计数据
     * @param array $where
     * @param string $field
     * @return float
     */
    public function getRechargeSum(array $where, string $field = '')
    {
        $whereData = [];
        if (isset($where['data'])) {
            $whereData['time'] = $where['data'];
        }
        if (isset($where['paid']) && $where['paid'] != '') {
            $whereData['paid'] = $where['paid'];
        }
        if (isset($where['nickname']) && $where['nickname']) {
            $whereData['like'] = $where['nickname'];
        }
        if (isset($where['recharge_type']) && $where['recharge_type']) {
            $whereData['recharge_type'] = $where['recharge_type'];
        }
        if (isset($where['store_id'])) {
            $whereData['store_id'] = $where['store_id'];
        }
        return $this->dao->getWhereSumField($whereData, $field);
    }

    /**
     * 获取充值列表
     * @param array $where
     * @param string $field
     * @param int $limit
     * @return array
     */
    public function getRechargeList(array $where, string $field = '*', int $limit = 0, array $with = [])
    {
        $whereData = $where;
        if (isset($where['data'])) {
            $whereData['time'] = $where['data'];
            unset($whereData['data']);
        }
        if (isset($where['nickname']) && $where['nickname']) {
            $whereData['like'] = $where['nickname'];
            unset($whereData['nickname']);
        }
        if ($limit) {
            [$page] = $this->getPageValue();
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        $list = $this->dao->getList($whereData, $field, $page, $limit, $with);
        $count = $this->dao->count($whereData);

        foreach ($list as &$item) {
            switch ($item['recharge_type']) {
                case 'routine':
                    $item['_recharge_type'] = '小程序充值';
                    break;
                case 'weixin':
                    $item['_recharge_type'] = '公众号充值';
                    break;
                case 'alipay':
                    $item['_recharge_type'] = '支付宝充值';
                    break;
                case 'balance':
                    $item['_recharge_type'] = '佣金转入';
                    break;
                case 'store':
                    $item['_recharge_type'] = '门店余额充值';
                    break;
                default:
                    $item['_recharge_type'] = '其他充值';
                    break;
            }
            $item['_pay_time'] = $item['pay_time'] ? date('Y-m-d H:i:s', $item['pay_time']) : '暂无';
            $item['_add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '暂无';
            $item['paid_type'] = $item['paid'] ? '已支付' : '未支付';
            unset($item['user']);
        }
        return compact('list', 'count');
    }

    /**
     * 获取用户充值数据
     * @return array
     */
    public function user_recharge(array $where)
    {
        $data = [];
		$where['paid'] = 1;
        $data['sumPrice'] = $this->getRechargeSum($where, 'price');
        $data['sumRefundPrice'] = $this->getRechargeSum($where, 'refund_price');
        $where['recharge_type'] = 'routine';
        $data['sumRoutinePrice'] = $this->getRechargeSum($where, 'price');
        $where['recharge_type'] = 'weixin';
        $data['sumWeixinPrice'] = $this->getRechargeSum($where, 'price');
        return [
            [
                'name' => '充值总金额',
                'field' => '元',
                'count' => $data['sumPrice'],
                'className' => 'logo-yen',
                'col' => 6,
            ],
            [
                'name' => '充值退款金额',
                'field' => '元',
                'count' => $data['sumRefundPrice'],
                'className' => 'logo-usd',
                'col' => 6,
            ],
            [
                'name' => '小程序充值金额',
                'field' => '元',
                'count' => $data['sumRoutinePrice'],
                'className' => 'logo-bitcoin',
                'col' => 6,
            ],
            [
                'name' => '公众号充值金额',
                'field' => '元',
                'count' => $data['sumWeixinPrice'],
                'className' => 'ios-bicycle',
                'col' => 6,
            ],
        ];
    }

    /**
     * 退款表单
     * @param int $id
     * @return mixed
     */
    public function refund_edit(int $id)
    {
        $UserRecharge = $this->getRecharge($id);
        if (!$UserRecharge) {
            throw new AdminException('数据不存在!');
        }
        if ($UserRecharge['paid'] != 1) {
            throw new AdminException('订单未支付');
        }
        if ($UserRecharge['price'] == $UserRecharge['refund_price']) {
            throw new AdminException('已退完支付金额!不能再退款了');
        }
        if ($UserRecharge['recharge_type'] == 'balance') {
            throw new AdminException('佣金转入余额，不能退款');
        }
        $f = array();
        $f[] = Form::input('order_id', '退款单号', $UserRecharge->getData('order_id'))->disabled(true);
        $f[] = Form::radio('refund_price', '状态', 1)->options([['label' => '本金(扣赠送余额)', 'value' => 1], ['label' => '仅本金', 'value' => 0]]);
//        $f[] = Form::number('refund_price', '退款金额', (float)$UserRecharge->getData('price'))->precision(2)->min(0)->max($UserRecharge->getData('price'));
        if ($UserRecharge['store_id']) {
            return create_form('退款', $f, Url::buildUrl('/order/recharge/' . $id), 'PUT');
        } else {
            return create_form('退款', $f, Url::buildUrl('/finance/recharge/' . $id), 'PUT');
        }
    }

    /**
     * 充值退款操作
     * @param int $id
     * @param $refund_price
     * @return mixed
     */
    public function refund_update(int $id, string $refund_price)
    {
        $UserRecharge = $this->getRecharge($id);
        if (!$UserRecharge) {
            throw new AdminException('数据不存在!');
        }
        if ($UserRecharge['price'] == $UserRecharge['refund_price']) {
            throw new AdminException('已退完支付金额!不能再退款了');
        }
        if ($UserRecharge['recharge_type'] == 'balance') {
            throw new AdminException('佣金转入余额，不能退款');
        }
        $UserRecharge = $UserRecharge->toArray();

//        $data['refund_price'] = bcadd($refund_price, $UserRecharge['refund_price'], 2);
        $data['refund_price'] = $UserRecharge['price'];
//        $bj = bccomp((string)$UserRecharge['price'], (string)$data['refund_price'], 2);
//        if ($bj < 0) {
//            throw new AdminException('退款金额大于支付金额，请修改退款金额');
//        }
        $refund_data['pay_price'] = $UserRecharge['price'];
        $refund_data['refund_price'] = $UserRecharge['price'];
//        $refund_data['refund_account']='REFUND_SOURCE_RECHARGE_FUNDS';
        if ($refund_price == 1) {
            $number = bcadd($UserRecharge['price'], $UserRecharge['give_price'], 2);
        } else {
            $number = $UserRecharge['price'];
        }

        try {
            $recharge_type = $UserRecharge['recharge_type'];
            if ($recharge_type == 'alipay') {
                mt_srand();
                $refund_id = $refundData['refund_id'] ?? $UserRecharge['order_id'] . rand(100, 999);
                //支付宝退款
                AliPayService::instance()->refund($UserRecharge['order_id'], $refund_data['refund_price'], $refund_id);
            } else if ($recharge_type == 'weixin') {
                Payment::instance()->setAccessEnd(Payment::WEB)->payOrderRefund($UserRecharge['order_id'], $refund_data);
            } else {
                Payment::instance()->setAccessEnd(Payment::MINI)->payOrderRefund($UserRecharge['order_id'], $refund_data);
            }
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
        if (!$this->dao->update($id, $data)) {
            throw new AdminException('修改提现数据失败');
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $userInfo = $userServices->getUserInfo((int)$UserRecharge['uid']);
        if ($userInfo['now_money'] > $number) {
            $now_money = bcsub((string)$userInfo['now_money'], $number, 2);
        } else {
            $number = $userInfo['now_money'];
            $now_money = 0;
        }
        //修改用户余额
        $userServices->update((int)$UserRecharge['uid'], ['now_money' => $now_money], 'uid');
        $UserRecharge['nickname'] = $userInfo['nickname'];
        $UserRecharge['phone'] = $userInfo['phone'];

        /** @var UserMoneyServices $userMoneyServices */
        $userMoneyServices = app()->make(UserMoneyServices::class);
        //保存余额记录
        $userMoneyServices->income('user_recharge_refund', $UserRecharge['uid'], $number, $now_money, $id);

        //充值退款事件
        event('user.rechargeRefund', [$UserRecharge, $data]);
        return true;
    }

    /**
     * 删除
     * @param int $id
     * @return bool
     */
    public function delRecharge(int $id)
    {
        $rechargInfo = $this->getRecharge($id);
        if (!$rechargInfo) throw new AdminException('订单未找到');
        if ($rechargInfo->paid) {
            throw new AdminException('已支付的订单记录无法删除');
        }
        if ($this->dao->delete($id))
            return true;
        else
            throw new AdminException('删除失败');
    }

    /**
     * 生成充值订单号
     * @return bool|string
     */
    public function getOrderId()
    {
        return 'wx' . date('YmdHis', time()) . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    }

    /**
     * 导入佣金到余额
     * @param int $uid
     * @param $price
     * @return bool
     */
    public function importNowMoney(int $uid, $price)
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('数据不存在');
        }
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        $broken_commission = $userBrokerageServices->getUserFrozenPrice($uid);
        $commissionCount = bcsub((string)$user['brokerage_price'], (string)$broken_commission, 2);
        if ($price > $commissionCount) {
            throw new ValidateException('转入金额不能大于可提现佣金！');
        }
        return $this->transaction(function () use ($uid, $user, $price, $userServices) {
            $edit_data = [];
            $edit_data['now_money'] = bcadd((string)$user['now_money'], (string)$price, 2);
            $edit_data['brokerage_price'] = $user['brokerage_price'] > $price ? bcsub((string)$user['brokerage_price'], (string)$price, 2) : 0;
            //修改用户佣金、余额信息
            $userServices->update($uid, $edit_data, 'uid');
            //写入充值记录
            $rechargeInfo = [
                'uid' => $uid,
                'order_id' => $this->getOrderId(),
                'recharge_type' => 'balance',
                'price' => $price,
                'give_price' => 0,
                'paid' => 1,
                'pay_time' => time(),
                'add_time' => time()
            ];
            //写入充值记录
            $re = $this->dao->save($rechargeInfo);
            /** @var UserMoneyServices $userMoneyServices */
            $userMoneyServices = app()->make(UserMoneyServices::class);
            //余额记录
            $userMoneyServices->income('brokerage_to_nowMoney', $uid, $price, $edit_data['now_money'], $re['id']);
            $extractInfo = [
                'uid' => $uid,
                'real_name' => $user['nickname'],
                'extract_type' => 'balance',
                'extract_price' => $price,
                'balance' => $edit_data['brokerage_price'],
                'add_time' => time(),
                'status' => 1
            ];
            /** @var UserExtractServices $userExtract */
            $userExtract = app()->make(UserExtractServices::class);
            //写入提现记录
            $userExtract->save($extractInfo);
            //佣金提现记录
            /** @var UserBrokerageServices $userBrokerageServices */
            $userBrokerageServices = app()->make(UserBrokerageServices::class);
            $userBrokerageServices->income('brokerage_to_nowMoney', $uid, $price, $edit_data['brokerage_price'], $re['id']);
        });
    }

    /**
     * 申请充值
     * @param int $uid
     * @param $price
     * @param $recharId
     * @param $type
     * @param $from
     * @param array $staffinfo
     * @param string $authCode 扫码code
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function recharge(int $uid, $price, $recharId, $type, $from, array $staffinfo = [], string $authCode = '')
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('数据不存在');
        }
        $paid_price = 0;
        if ($recharId) {
            /** @var SystemGroupDataServices $systemGroupData */
            $systemGroupData = app()->make(SystemGroupDataServices::class);
            $data = $systemGroupData->getDateValue($recharId);
            if ($data === false) {
                return app('json')->fail('您选择的充值方式已下架!');
            } else {
                $paid_price = $data['give_money'] ?? 0;
            }
            $price = $data['price'];
        }
        switch ((int)$type) {
            case 0: //支付充值余额
                $recharge_data = [];
                $recharge_data['order_id'] = $this->getOrderId();
                $recharge_data['uid'] = $uid;
                $recharge_data['price'] = $price;
                $recharge_data['recharge_type'] = $from;
                $recharge_data['paid'] = 0;
                $recharge_data['add_time'] = time();
                $recharge_data['give_price'] = $paid_price;
                $recharge_data['channel_type'] = $user['user_type'];
                if (!$rechargeOrder = $this->dao->save($recharge_data)) {
                    throw new ValidateException('充值订单生成失败');
                }
                try {
                    /** @var RechargeServices $recharge */
                    $recharge = app()->make(RechargeServices::class);
                    $order_info = $recharge->recharge((int)$rechargeOrder->id);
                } catch (\Exception $e) {
                    throw new ValidateException($e->getMessage());
                }
                return ['msg' => '', 'type' => $from, 'data' => $order_info];
                break;
            case 1: //佣金转入余额
                $this->importNowMoney($uid, $price);
                return ['msg' => '转入余额成功', 'type' => $from, 'data' => []];
                break;
            case 2://门店充值-用户扫码付款
            case 3://门店充值-付款码付款
                if (!$staffinfo) {
                    throw new ValidateException('请稍后重试');
                }
                $recharge_data = [];
                $recharge_data['order_id'] = $this->getOrderId();
                $recharge_data['uid'] = $uid;
                $recharge_data['store_id'] = $staffinfo['store_id'];
                $recharge_data['staff_id'] = $staffinfo['id'];
                $recharge_data['price'] = $price;
                //自动判定支付方式
                if ($authCode) {
                    $recharge_data['auth_code'] = $authCode;
                    if (Payment::isWechatAuthCode($authCode)) {
                        $recharge_data['recharge_type'] = PayServices::WEIXIN_PAY;
                    } else if (AliPayService::isAliPayAuthCode($authCode)) {
                        $recharge_data['recharge_type'] = PayServices::ALIAPY_PAY;
                    } else {
                        throw new ValidateException('付款二维码错误');
                    }
                } else {
                    $recharge_data['recharge_type'] = $from;
                }
                $recharge_data['paid'] = 0;
                $recharge_data['add_time'] = time();
                $recharge_data['give_price'] = $paid_price;
                $recharge_data['channel_type'] = $user['user_type'];
                if (!$rechargeOrder = $this->dao->save($recharge_data)) {
                    throw new ValidateException('充值订单生成失败');
                }
                try {
                    /** @var RechargeServices $recharge */
                    $recharge = app()->make(RechargeServices::class);
                    $order_info = $recharge->recharge((int)$rechargeOrder->id, $authCode);
                    if ($type === 3) {
                        if ($order_info['paid'] === 1) {
                            //修改支付状态
                            $this->rechargeSuccess($recharge_data['order_id']);
                            return [
                                'msg' => $order_info['message'],
                                'status' => 'SUCCESS',
                                'type' => $from,
                                'payInfo' => [],
                                'data' => [
                                    'jsConfig' => [],
                                    'order_id' => $recharge_data['order_id']
                                ]
                            ];
                        } else {
                            //发起支付但是还没有支付，需要在5秒后查询支付状态
                            if ($recharge_data['recharge_type'] === PayServices::WEIXIN_PAY) {
                                if (isset($order_info['payInfo']['err_code']) && in_array($order_info['payInfo']['err_code'], ['AUTH_CODE_INVALID', 'NOTENOUGH'])) {
                                    return ['status' => 'ERROR', 'msg' => '支付失败', 'payInfo' => $order_info];
                                }
                                $secs = 5;
                                if (isset($order_info['payInfo']['err_code']) && $order_info['payInfo']['err_code'] === 'USERPAYING') {
                                    $secs = 10;
                                }
                                MicroPayOrderJob::dispatchSece($secs, [$recharge_data['order_id']]);
                            }
                            return [
                                'msg' => $order_info['message'] ?? '等待支付中',
                                'status' => 'PAY_ING',
                                'type' => $from,
                                'payInfo' => $order_info,
                                'data' => [
                                    'jsConfig' => [],
                                    'order_id' => $recharge_data['order_id']
                                ]
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    \think\facade\Log::error('充值失败，原因：' . $e->getMessage());
                    throw new ValidateException('充值失败');
                }
                return ['msg' => '', 'status' => 'PAY', 'type' => $from, 'data' => ['jsConfig' => $order_info, 'order_id' => $recharge_data['order_id']]];
                break;
            default:
                throw new ValidateException('缺少参数');
                break;
        }
    }

    /**
     * 用户充值成功
     * @param $orderId
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function rechargeSuccess($orderId)
    {
        $order = $this->dao->getOne(['order_id' => $orderId, 'paid' => 0]);
        if (!$order) {
            throw new ValidateException('订单失效或者不存在');
        }
        $order = $order->toArray();
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $user = $userServices->getUserInfo((int)$order['uid']);
        if (!$user) {
            throw new ValidateException('数据不存在');
        }
        $price = bcadd((string)$order['price'], (string)$order['give_price'], 2);
        if (!$this->dao->update($order['id'], ['paid' => 1, 'pay_time' => time()], 'id')) {
            throw new ValidateException('修改订单失败');
        }
        $now_money = bcadd((string)$user['now_money'], (string)$price, 2);
        /** @var UserMoneyServices $userMoneyServices */
        $userMoneyServices = app()->make(UserMoneyServices::class);
        $userMoneyServices->income('user_recharge', $user['uid'], ['number' => $price, 'price' => $order['price'], 'give_price' => $order['give_price']], $now_money, $order['id']);
        if (!$userServices->update((int)$order['uid'], ['now_money' => $now_money], 'uid')) {
            throw new ValidateException('修改用户信息失败');
        }

        //记录流水账单
        if ($order['store_id'] > 0) {
            $data = $order;
            $data['pay_time'] = time();
            /** @var StoreFinanceFlowServices $storeFinanceFlowServices */
            $storeFinanceFlowServices = app()->make(StoreFinanceFlowServices::class);
            $storeFinanceFlowServices->setFinance($data, 2);
        }
        $order['nickname'] = $user['nickname'];
        $order['phone'] = $user['phone'];

        //用户充值成功事件
        event('user.recharge', [$order, $now_money]);
        return true;
    }

    /**
     * 根据查询用户充值金额
     * @param array $where
     * @return float|int
     */
    public function getRechargeMoneyByWhere(array $where, string $rechargeSumField, string $selectType, string $group = "")
    {
        switch ($selectType) {
            case "sum" :
                return $this->dao->getWhereSumField($where, $rechargeSumField);
            case "group" :
                return $this->dao->getGroupField($where, $rechargeSumField, $group);
        }
    }

    /**
     * 充值每日统计数据
     * @param int $store_id
     * @param int $staff_id
     * @param array $time
     * @return array
     */
    public function getDataPriceCount(int $store_id, int $staff_id = 0, $time = [])
    {
        [$page, $limit] = $this->getPageValue();
        $where = ['store_id' => $store_id, 'paid' => 1, 'time' => $time];
        if ($staff_id) {
            $where['staff_id'] = $staff_id;
        }
        return $this->dao->getDataPriceCount($where, ['sum(price) as price', 'count(id) as count', 'FROM_UNIXTIME(add_time, \'%m-%d\') as time'], $page, $limit);
    }

    /**
     * 门店充值统计详情列表
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
        $where = ['store_id' => $store_id, 'paid' => 1];
        if ($staff_id) $where['staff_id'] = $staff_id;
        $frontPrice = $this->dao->sum($where + ['time' => [$front, $front_stop]], 'price', true);
        $nowPrice = $this->dao->sum($where + ['time' => [$start, $stop]], 'price', true);
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where + ['time' => [$start, $stop]], 'id,uid,order_id,price,add_time', $page, $limit);
        foreach ($list as &$item) {
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
        }
        return [[$nowPrice, $frontPrice], $list];
    }
}
