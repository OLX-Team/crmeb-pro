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


use app\controller\admin\AuthController;
use app\services\order\OtherOrderServices;
use app\services\order\StoreOrderServices;
use app\services\user\UserRechargeServices;
use think\facade\App;
use \app\common\controller\Order as CommonOrder;

/**
 * Class Order
 * @package app\controller\admin\v1\order
 */
class Order extends AuthController
{

    use CommonOrder;

    /**
     * @var StoreOrderServices
     */
    protected $services;

    /**
     * Order constructor.
     * @param App $app
     * @param StoreOrderServices $services
     */
    public function __construct(App $app, StoreOrderServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 订单列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['type', ''],
            ['status', ''],
            ['time', ''],
            ['real_name', ''],
            ['store_id', -1]
        ]);
		if (!$where['store_id']) $where['store_id'] = -1;
        $where['is_system_del'] = 0;
        return $this->success($this->services->getOrderList($where, ['*'], ['split' => function ($query) {
            $query->field('id,pid');
        }, 'pink', 'invoice', 'storeStaff']));
    }

    /**
     * 获取订单类型数量
     * @return mixed
     */
    public function chart()
    {
        $where = $this->request->getMore([
            ['data', '', '', 'time'],
            [['type', 'd'], 0],
            ['store_id', -1]
        ]);
        $where['store_id'] = $where['store_id'] ?: -1;
        $data = $this->services->orderStoreCount($where);
        return $this->success($data);
    }

    /**
     * 获取头部统计数据
     * @param UserRechargeServices $services
     * @param OtherOrderServices $orderServices
     * @return mixed
     */
    public function header(UserRechargeServices $services, OtherOrderServices $orderServices)
    {
        [$store_id] = $this->request->getMore([
            ['store_id', -1]
        ], true);
        $store_id = $store_id ?: -1;
        $store_id = (int)$store_id;
        $data = $this->services->getStoreOrderHeader($store_id);
        $data['recharg'] = $services->getRechargeCount($store_id);
        $data['vip'] = $orderServices->getvipOrderCount($store_id);
        return $this->success($data);
    }

    /**
     * 获取配置信息
     * @return mixed
     */
    public function getDeliveryInfo()
    {
        return $this->success([
            'express_temp_id' => store_config($this->storeId, 'config_export_temp_id'),
            'id' => store_config($this->storeId, 'config_export_id'),
            'to_name' => store_config($this->storeId, 'config_export_to_name'),
            'to_tel' => store_config($this->storeId, 'config_export_to_tel'),
            'to_add' => store_config($this->storeId, 'config_export_to_address'),
            'export_open' => (int)store_config($this->storeId, 'config_export_open') ? true : false
        ]);
    }

    /**
     * 订单分配
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function shareOrder()
    {
        [$oid, $store_id] = $this->request->getMore([
            ['oid', 0],
            ['store_id', 0]
        ], true);
        if (!$oid || !$store_id) {
            return $this->fail('缺少参数');
        }
        $this->services->shareOrder((int)$oid, (int)$store_id);
        return $this->success('分配成功');
    }
}
