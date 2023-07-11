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
namespace app\controller\admin\v1\store;


use think\facade\App;
use app\controller\admin\AuthController;
use app\services\store\finance\StoreFinanceFlowServices;


/**
 * 门店流水
 * Class StoreFinanceFlow
 * @package app\controller\admin\v1\store
 */
class StoreFinanceFlow extends AuthController
{
    /**
     * StoreFinanceFlow constructor.
     * @param App $app
     * @param StoreExtractServices $services
     */
    public function __construct(App $app, StoreFinanceFlowServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }


    /**
     * 显示资源列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['data', '', '', 'time'],
            ['store_id', '']
        ]);
        $where['keyword'] = $this->request->param('keyword', '');
        $where['is_del'] = 0;
        $where['trade_type'] = 1;
        $where['no_type'] = 2;
        return app('json')->success($this->services->getList($where));
    }

    /**
     * 增加备注
     * @param $id
     * @return mixed
     */
    public function mark($id)
    {
        [$mark] = $this->request->getMore([
            ['mark', '']
        ], true);
        if (!$id || !$mark) {
            return app('json')->fail('缺少参数');
        }
        $info = $this->services->get((int)$id);
        if (!$info) {
            return app('json')->fail('账单流水不存在');
        }
        if (!$this->services->update($id, ['remark' => $mark])) {
            return app('json')->fail('备注失败');
        }
        return app('json')->success('备注成功');
    }


    /**
     * 账单记录
     * @return mixed
     */
    public function fundRecord()
    {
        $where = $this->request->getMore([
            ['timeType', 'day'],
            ['data', '', '', 'time'],
            ['store_id', '']
        ]);
        $where['trade_type'] = 1;
        $where['no_type'] = 1;
        return app('json')->success($this->services->getFundRecord($where));
    }

    /**
     * 账单详情
     * @param $ids
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function fundRecordInfo()
    {
        $where = $this->request->getMore([
            ['ids', ''],
            ['store_id', '']
        ]);
        $where['keyword'] = $this->request->param('keyword', '');
        $where['id'] = $where['ids'] ? explode(',', $where['ids']) : [];
        unset($where['ids']);
        $where['is_del'] = 0;
        $where['trade_type'] = 1;
        return app('json')->success($this->services->getList($where));
    }
}
