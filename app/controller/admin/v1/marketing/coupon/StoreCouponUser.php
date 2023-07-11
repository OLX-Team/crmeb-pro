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
namespace app\controller\admin\v1\marketing\coupon;

use app\controller\admin\AuthController;
use app\jobs\BatchHandleJob;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\other\queue\QueueServices;
use think\facade\App;

/**
 * 优惠券发放记录控制器
 * Class StoreCategory
 * @package app\admin\controller\system
 */
class StoreCouponUser extends AuthController
{
    public function __construct(App $app, StoreCouponUserServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 发放列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['coupon_title', ''],
            ['nickname', ''],
        ]);
        $list = $this->services->systemPage($where);
        return $this->success($list);
    }

    /**
     * 发放优惠券到指定个人
     * @param $id
     * @param $uid
     * @return \think\response\Json
     */
    public function grant1()
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['uid', '']
        ]);
        if (!$data['id']) return $this->fail('数据不存在!');
        /** @var StoreCouponIssueServices $issueService */
        $issueService = app()->make(StoreCouponIssueServices::class);
        $coupon = $issueService->get($data['id']);
        if (!$coupon) {
            return $this->fail('数据不存在!');
        } else {
            $coupon = $coupon->toArray();
        }
        $user = explode(',', $data['uid']);
        if (!$issueService->setCoupon($coupon, $user))
            return $this->fail('发放失败,请稍候再试!');
        else
            return $this->success('发放成功!');

    }

    /**
     * 批量发券
     * @return mixed
     */
    public function grant()
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['uid', ''],
            ['all', 0],
            ['where', []],
        ]);
        if (!$data['id']) return $this->fail('数据不存在!');
        if (!$data['uid'] && $data['all'] == 0) return $this->fail('缺少参数');
        /** @var StoreCouponIssueServices $issueService */
        $issueService = app()->make(StoreCouponIssueServices::class);
        $coupon = $issueService->get($data['id']);
        if (!$coupon) {
            return $this->fail('数据不存在!');
        } else {
            $coupon = $coupon->toArray();
        }
        $type = 1;//代表发放优惠券
        if ($data['all'] == 0) $user = explode(',', $data['uid']);
        if ($data['all'] == 1) $user = [];
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $queueService->setQueueData($data['where'], 'uid', $user, $type);
        //加入队列
        BatchHandleJob::dispatch([$coupon, $type]);
        return $this->success('后台程序已执行发放优惠券任务!');
    }
}
