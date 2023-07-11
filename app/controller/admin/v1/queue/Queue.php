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

namespace app\controller\admin\v1\queue;

use app\controller\admin\AuthController;
use app\services\other\queue\QueueAuxiliaryServices;
use app\services\other\queue\QueueServices;
use think\facade\App;

/**
 * Class Queue
 * @package app\controller\admin\v1\queue
 */
class Queue extends AuthController
{
    /**
     * Queue constructor.
     * @param App $app
     * @param QueueServices $queueServices
     */
    public function __construct(App $app, QueueServices $queueServices)
    {
        parent::__construct($app);
        $this->services = $queueServices;
    }

    /**
     *  队列任务列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['data', '', '', 'time'],
            ['type', []],
            ['status', [0, 1, 2, 3]],
            ['page', 1],
            ['limit', 20],
        ]);
        $data = $this->services->getList($where);
        return $this->success($data);
    }

    /**
     * 批量发货记录
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delivery_log($id, $type)
    {
        /** @var QueueAuxiliaryServices $auxiliaryService */
        $auxiliaryService = app()->make(QueueAuxiliaryServices::class);
        $data = $auxiliaryService->deliveryLogList(['binding_id' => $id, 'type' => $type]);
        return $this->success($data);
    }

    /**
     * 再次执行批量任务
     * @param $id
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function again_do_queue($id, $type)
    {
        if (!$id || !$type) return $this->fail("参数缺失");
        $this->services->againDoQueue($id, $type);
        return $this->success("后台程序已再次执行此批量任务");
    }

    /**
     * 清除异常任务
     * @param $id
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function del_wrong_queue($id, $type)
    {
        if (!$id || !$type) return $this->fail("参数缺失");
        $res = $this->services->delWrongQueue($id, $type);
        return $this->success($res ? "异常任务清除成功" : "数据无异常");
    }


    /**
     * 任务停止
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function stop_wrong_queue($id)
    {
        if (!$id) return $this->fail("参数缺失");
        $queueInfo = $this->services->getQueueOne(['id' => $id]);
        if (!$queueInfo) $this->fail('任务不存在');
        $this->services->delWrongQueue($id, $queueInfo['type'], false);
        return $this->success("任务停止成功");
    }

}
