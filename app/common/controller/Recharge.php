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

namespace app\common\controller;

/**
 * 退款
 * Trait Recharge
 * @package app\common\controller
 */
trait Recharge
{

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['data', ''],
            ['paid', ''],
            ['nickname', ''],
        ]);
        $where['store_id'] = 0;
        return $this->success($this->services->getRechargeList($where));
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
        return $this->success($this->services->delRecharge((int)$id) ? '删除成功' : '删除失败');
    }

    /**
     * 获取用户充值数据
     * @return array
     */
    public function user_recharge()
    {
        $where = $this->request->getMore([
            ['data', ''],
            ['paid', ''],
            ['nickname', ''],
        ]);
        $where['store_id'] = 0;
        return $this->success($this->services->user_recharge($where));
    }

    /**
     * 退款表单
     * @param $id
     * @return mixed
     */
    public function refund_edit($id)
    {
        if (!$id) return $this->fail('数据不存在');
        return $this->success($this->services->refund_edit((int)$id));
    }

    /**
     * 退款操作
     * @param $id
     */
    public function refund_update($id)
    {
        $data = $this->request->postMore([
            'refund_price',
        ]);
        if (!$id) return $this->fail('数据不存在');
        return $this->success($this->services->refund_update((int)$id, $data['refund_price']) ? '退款成功' : '退款失败');
    }

}
