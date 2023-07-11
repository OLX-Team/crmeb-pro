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
namespace app\controller\admin\v1\product;

use app\controller\admin\AuthController;
use app\jobs\BatchHandleJob;
use app\services\other\queue\QueueServices;
use app\services\product\sku\StoreProductRuleServices;
use think\facade\App;

/**
 * 规则管理
 * Class StoreProductRule
 * @package app\controller\admin\v1\product
 */
class StoreProductRule extends AuthController
{

    public function __construct(App $app, StoreProductRuleServices $service)
    {
        parent::__construct($app);
        $this->services = $service;
    }

    /**
     * 规格列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['rule_name', '']
        ]);
        $list = $this->services->getList($where);
        return $this->success($list);
    }

    /**
     * 保存规格
     * @param $id
     * @return mixed
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            ['rule_name', ''],
            ['spec', []]
        ]);
		if (!$data['rule_name']) {
			return $this->fail('请输入分类名称');
		}
        $this->services->save($id, $data);
        return $this->success('保存成功!');
    }

    /**
     * 获取规格信息
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        $info = $this->services->getInfo($id);
        return $this->success($info);
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete()
    {
        [$ids, $all, $where] = $this->request->postMore([
            ['ids', ''],
            ['all', 0],
            ['where', []],
        ], true);
        if ($all == 0) {//单页不走队列
            if (empty($ids)) return $this->fail('请选择需要删除的规格');
            $this->services->del((string)$ids);
            return $this->success('删除成功');

        }
        if ($all == 1) $ids = [];
        $type = 5;//删除规格
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $queueService->setQueueData($where, 'id', $ids, $type);
        //加入队列
        BatchHandleJob::dispatch([[], $type]);
        return $this->success('后台程序已执行商品规格删除任务!');
    }
}
