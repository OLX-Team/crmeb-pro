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

namespace app\controller\admin\v1\marketing\combination;

use app\controller\admin\AuthController;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\combination\StorePinkServices;
use think\facade\App;


/**
 * 拼团管理
 * Class StoreCombination
 * @package app\admin\controller\store
 */
class StoreCombination extends AuthController
{
    /**
     * StoreCombination constructor.
     * @param App $app
     * @param StoreCombinationServices $services
     */
    public function __construct(App $app, StoreCombinationServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 拼团列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['start_status', ''],
            ['is_show', ''],
            ['store_name', '']
        ]);
        $where['is_del'] = 0;
        $list = $this->services->systemPage($where);
        return $this->success($list);
    }

    /**
     * 拼团统计
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function statistics()
    {
        /** @var StorePinkServices $storePinkServices */
        $storePinkServices = app()->make(StorePinkServices::class);
        $info = $storePinkServices->getStatistics();
        return $this->success($info);
    }

    /**
     * 详情
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        $info = $info = $this->services->getInfo((int)$id);
        return $this->success(compact('info'));
    }

    /**
     * 保存新建的资源
     * @param int $id
     */
    public function save($id = 0)
    {
        $data = $this->request->postMore([
            [['product_id', 'd'], 0],
            [['title', 's'], ''],
            [['info', 's'], ''],
            [['unit_name', 's'], ''],
            ['images', []],
            ['section_time', []],
            [['is_host', 'd'], 0],
            [['is_show', 'd'], 0],
            [['num', 'd'], 0],
            [['temp_id', 'd'], 0],
            [['effective_time', 'd'], 0],
            [['people', 'd'], 0],
            [['description', 's'], ''],
            ['attrs', []],
            ['items', []],
            ['num', 1],
            ['once_num', 1],
            ['sort', 0],
            ['copy', 0],
            ['virtual', 100],
            ['is_support_refund', 1],//是否支持退款
            ['delivery_type', []],//物流方式
            ['freight', 1],//运费设置
            ['postage', 0],//邮费
            ['custom_form', ''],//自定义表单
            ['product_type', 0],//商品类型
        ]);
        $this->validate($data, \app\validate\admin\marketing\StoreCombinationValidate::class, 'save');
        if ($data['section_time']) {
            [$start_time, $end_time] = $data['section_time'];
            if (strtotime($end_time) < time()) {
                return $this->fail('活动结束时间不能小于当前时间');
            }
        }
        $bragain = [];
        if ($id) {
            $bragain = $this->services->get((int)$id);
            if (!$bragain) {
                return $this->fail('数据不存在');
            }
        }
        //限制编辑
        if ($data['copy'] == 0 && $bragain) {
            if ($bragain['stop_time'] < time()) {
                return $this->fail('活动已结束,请重新添加或复制');
            }
        }
        if($data['num'] < $data['once_num']){
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
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        $this->services->update($id, ['is_del' => 1]);
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
        $this->services->update($id, ['is_show' => $status]);
        return $this->success($status == 0 ? '关闭成功' : '开启成功');
    }

    /**
     * 拼团列表
     * @return mixed
     */
    public function combine_list()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['data', '', '', 'time'],
        ]);
        /** @var StorePinkServices $storePinkServices */
        $storePinkServices = app()->make(StorePinkServices::class);
        $list = $storePinkServices->systemPage($where);
        return $this->success($list);
    }

    /**
     * 拼团人列表
     * @return mixed
     */
    public function order_pink($id)
    {
        /** @var StorePinkServices $storePinkServices */
        $storePinkServices = app()->make(StorePinkServices::class);
        $list = $storePinkServices->getPinkMember($id);
        return $this->success(compact('list'));
    }

}
