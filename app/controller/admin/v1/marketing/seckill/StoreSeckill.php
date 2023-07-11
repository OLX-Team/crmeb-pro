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

namespace app\controller\admin\v1\marketing\seckill;

use app\controller\admin\AuthController;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\product\sku\StoreProductAttrValueServices;
use crmeb\services\CacheService;
use think\facade\App;

/**
 * 限时秒杀  控制器
 * Class StoreSeckill
 * @package app\admin\controller\store
 */
class StoreSeckill extends AuthController
{
    public function __construct(App $app, StoreSeckillServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['start_status', ''],
            [['status', 's'], ''],
            [['store_name', 's'], '']
        ]);
        $where['is_del'] = 0;
        return $this->success($this->services->systemPage($where));
    }

    /**
     * 详情
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        $info = $this->services->getInfo($id);
        return $this->success(compact('info'));
    }

    /**
     * 保存秒杀商品
     * @param int $id
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            [['product_id', 'd'], 0],
            [['title', 's'], ''],
            [['info', 's'], ''],
            [['unit_name', 's'], ''],
            ['images', []],
            [['give_integral', 'd'], 0],
            ['section_time', []],
            [['is_hot', 'd'], 0],
            [['status', 'd'], 0],
            [['num', 'd'], 0],
            [['once_num', 'd'], 0],
            [['time_id', 'd'], 0],
            [['temp_id', 'd'], 0],
            [['sort', 'd'], 0],
            [['description', 's'], ''],
            ['attrs', []],
            ['items', []],
            ['copy', 0],
            ['is_support_refund', 1],//是否支持退款
            ['delivery_type', []],//物流方式
            ['freight', 1],//运费设置
            ['postage', 0],//邮费
            ['custom_form', ''],//自定义表单
            ['product_type', 0],//商品类型
        ]);
        $this->validate($data, \app\validate\admin\marketing\StoreSeckillValidate::class, 'save');
        if ($data['section_time']) {
            [$start_time, $end_time] = $data['section_time'];
            if (strtotime($end_time) + 86400 < time()) {
                return $this->fail('活动结束时间不能小于当前时间');
            }
        }
        $seckill = [];
        if ($id) {
            $seckill = $this->services->get((int)$id);
            if (!$seckill) {
                return $this->fail('数据不存在');
            }
        }
        //限制编辑
        if ($data['copy'] == 0 && $seckill) {
            if (($seckill['stop_time'] + 86400) < time()) {
                return $this->fail('活动已结束,请重新添加或复制');
            }
        }
        if ($data['num'] < $data['once_num']) {
            return $this->fail('限制单次购买数量不能大于总购买数量');
        }
        if ($data['copy'] == 1) {
            $id = 0;
            unset($data['copy']);
        }
        $this->services->saveData($id, $data);
        return $this->success('保存成功');
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if (!$id) return $this->fail('缺少参数');
        $this->services->update($id, ['is_del' => 1]);
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $unique = $storeProductAttrValueServices->value(['product_id' => $id, 'type' => 1], 'unique');
        if ($unique) {
            $name = 'seckill_' . $unique . '_1';
            /** @var CacheService $cache */
            $cache = app()->make(CacheService::class);
            $cache->del($name);
        }
        return $this->success('删除成功!');
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function set_status($id, $status)
    {
        $this->services->update($id, ['status' => $status]);
        return $this->success($status == 0 ? '关闭成功' : '开启成功');
    }

    /**
     * 秒杀时间段列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function time_list()
    {
        $list['data'] = sys_data('routine_seckill_time');
        return $this->success(compact('list'));
    }
}
