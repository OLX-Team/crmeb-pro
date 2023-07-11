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

namespace app\controller\store\order;


use app\controller\store\AuthController;
use app\services\order\OtherOrderServices;
use app\services\order\OtherOrderStatusServices;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\App;

/**
 * Class PayVipOrder
 * @package app\controller\store\order
 */
class PayVipOrder extends AuthController
{

    /**
     * @var OtherOrderServices
     */
    protected $services;

    /**
     * Order constructor.
     * @param App $app
     * @param OtherOrderServices $services
     */
    public function __construct(App $app, OtherOrderServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['add_time', ""],
            ['member_type', ""],
            ['pay_type', ""],
            ['staff_id', '']
        ]);
        $where['name'] = $this->request->param('name', '');
        $where['store_id'] = $this->storeId;
        $where['paid'] = 1;
        $data = $this->services->getMemberRecord($where);
        return $this->success($data);
    }

    /**
     * 获取备注
     * @param $id
     * @return mixed
     */
    public function getRemark($id)
    {
        return $this->success(['remarks' => $this->services->value(['id' => $id], 'remarks')]);
    }

    /**
     * 修改备注
     * @param $id
     * @return mixed
     */
    public function remark($id)
    {
        $data = $this->request->param('remarks', '');

        $this->services->update(['id' => $id], ['remarks' => $data]);

        return $this->success('备注提交成功');
    }

    /**
     * 获取vip订单状态
     * @param OtherOrderStatusServices $services
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function status(OtherOrderStatusServices $services, $id)
    {
        return $this->success($services->getStatusList((int)$id));
    }
}
