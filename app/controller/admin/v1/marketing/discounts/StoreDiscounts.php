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

namespace app\controller\admin\v1\marketing\discounts;


use app\controller\admin\AuthController;
use app\services\activity\discounts\StoreDiscountsServices;
use think\facade\App;

/**
 * 优惠套餐
 * Class StoreDiscounts
 * @package app\controller\admin\v1\marketing\discounts
 */
class StoreDiscounts extends AuthController
{
    /**
     * StoreDiscounts constructor.
     * @param App $app
     * @param StoreDiscountsServices $services
     */
    public function __construct(App $app, StoreDiscountsServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 新增修改优惠
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->getMore([
            ['id', 0],
            ['title', ''],
            ['image', ''],
            ['type', 0],
            ['is_limit', 0],
            ['limit_num', 0],
            ['link_ids', ''],
            ['is_time', 0],
            ['time', []],
            ['sort', 0],
            ['free_shipping', 0],
            ['status', 0],
            ['products', []],
            ['delivery_type', []],//物流方式
            ['freight', 1],//运费设置
            ['postage', 0],//邮费
            ['custom_form', ''],//自定义表单
        ]);
        if ($data['title'] == '') return $this->fail('请填写套餐名称');
        if ($data['image'] == '') return $this->fail('请选择套餐主图');
        if ($data['is_limit'] && !$data['limit_num']) return $this->fail('套餐限量不能为0');
        if (!count($data['products'])) $this->fail('请添加套餐商品');
        foreach ($data['products'] as $item) {
            if (!isset($item['items'])) $this->fail('请选择' . $item['store_name'] . '的规格');
        }
        if ($data['is_time'] && is_array($data['is_time'])) {
            [$start, $end] = $data['is_time'];
            $start = strtotime($start);
            $end = strtotime($end);
            if($start > $end){
                return $this->fail('开始时间必须小于结束时间');
            }
            if($start < time() || $end < time()){
                return $this->fail('套餐时间不能小于当前时间');
            }
        }
        $msg = $data['id'] ? '编辑' : '添加';
        $res = $this->services->saveDiscounts($data);
        if ($res) {
            return $this->success($msg . '成功');
        } else {
            return $this->fail($msg . '失败');
        }
    }

    /**
     * 获取优惠商品列表
     * @return mixed
     */
    public function getList()
    {
        $where = $this->request->getMore([
            ['type', 0],
            ['status', ''],
            ['title', '']
        ]);
        $info = $this->services->getList($where);
        return $this->success($info);
    }

    /**
     * 获取优惠商品数据
     * @param int $id
     * @return mixed
     */
    public function getInfo($id = 0)
    {
        if (!$id) return $this->fail('参数错误');
        $info = $this->services->getInfo($id);
        if ($info) {
            return $this->success($info);
        } else {
            return $this->fail('获取失败');
        }
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function setStatus($id, $status)
    {
        if (!$id) return $this->fail('参数错误');
        $res = $this->services->setStatus($id, $status);
        if ($res) {
            return $this->success('设置成功');
        } else {
            return $this->fail('设置失败');
        }
    }

    /**
     * 删除优惠套餐
     * @param $id
     * @return mixed
     */
    public function del($id)
    {
        if (!$id) return $this->fail('参数错误');
        $res = $this->services->del($id);
        if ($res) {
            return $this->success('删除成功');
        } else {
            return $this->fail('删除失败');
        }
    }
}