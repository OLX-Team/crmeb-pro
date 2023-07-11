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

namespace app\controller\admin\v1\marketing\integral;

use app\controller\admin\AuthController;
use app\services\activity\integral\StoreIntegralServices;
use think\facade\App;


/**
 * 积分商城管理
 * Class StoreCombination
 * @package app\admin\controller\store
 */
class StoreIntegral extends AuthController
{
    /**
     * StoreIntegral constructor.
     * @param App $app
     * @param StoreIntegralServices $services
     */
    public function __construct(App $app, StoreIntegralServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 积分商品列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['integral_time', ''],
            ['is_show', ''],
            ['store_name', '']
        ]);
        $where['is_del'] = 0;
        $list = $this->services->systemPage($where);
        return $this->success($list);
    }

    /**
     * 保存商品
     * @param int $id
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            [['product_id', 'd'], 0],
            [['title', 's'], ''],
            [['unit_name', 's'], ''],
            ['image', ''],
            ['images', []],
            [['num', 'd'], 0],
            [['is_host', 'd'], 0],
            [['is_show', 'd'], 0],
            [['once_num', 'd'], 0],
            [['sort', 'd'], 0],
            [['description', 's'], ''],
            ['attrs', []],
            ['items', []],
            ['copy', 0],
            ['delivery_type', []],//物流方式
            ['freight', 1],//运费设置
            ['postage', 0],//邮费
            ['custom_form', ''],//自定义表单
            ['product_type', 0],//商品类型
        ]);

        $this->validate($data, \app\validate\admin\marketing\StoreIntegralValidate::class, 'save');
        $bragain = [];
        if ($id) {
            $bragain = $this->services->get((int)$id);
            if (!$bragain) {
                return $this->fail('数据不存在');
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
     * 批量添加商品
     * @return mixed
     */
    public function batch_add()
    {
        $data = $this->request->postMore([
            ['attrs', []],
            [['is_show', 'd'], 0]
        ]);
        if (!$data['attrs']) return $this->fail('请选择提交的商品');
        $this->services->saveBatchData($data);
        return $this->success('保存成功');
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
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function set_show($id, $is_show)
    {
        $this->services->update($id, ['is_show' => $is_show]);
        return $this->success($is_show == 0 ? '下架成功' : '上架成功');
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
        return $this->success('删除成功!');
    }

}
