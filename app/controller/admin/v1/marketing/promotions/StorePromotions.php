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

namespace app\controller\admin\v1\marketing\promotions;

use app\controller\admin\AuthController;
use app\services\activity\promotions\StorePromotionsServices;
use think\facade\App;

/**
 * 促销活动
 * Class StorePromotions
 * @package app\controller\admin\v1\marketing\promotions
 */
class StorePromotions extends AuthController
{

    /**
     * StorePromotions constructor.
     * @param App $app
     * @param StorePromotionsServices $services
     */
    public function __construct(App $app, StorePromotionsServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index($type)
    {
        $where = $this->request->getMore([
            [['status', 's'], ''],
            ['threshold_type', ''],
			['n_piece_n_discount', ''],
            [['name', 's'], '']
        ]);
        $where['promotions_type'] = $type;
        $where['type'] = 1;
        $where['store_id'] = 0;
        $where['pid'] = 0;
        $where['is_del'] = 0;
        return $this->success($this->services->systemPage($where));
    }

    /**
     * 详情
     * @param $id
     * @return mixed
     */
    public function getInfo($id)
    {
        $info = $this->services->getInfo($id);
        return $this->success(compact('info'));
    }

    /**
     * 保存折扣促销活动
     * @param $type
     * @param $id
     * @return mixed
     */
    public function saveDiscount($type, $id)
    {
        $data = $this->request->postMore([
            [['promotions_cate', 'd'], 1],//优惠类型
            [['name', 's'], ''],//名称
            [['threshold_type', 'd'], 1],//门槛类型1:满N元2:满N件
            ['threshold', 0],//优惠门槛
            [['discount_type', 'd'], 1],//优惠类型1:满减2:满折
            [['n_piece_n_discount', 'd'], 3],//n件n折类型：1:第二件半件2:买1送1 3:自定义
            ['discount', 0],//优惠
            [['give_integral', 'd'], 0],//赠送积分
            ['give_coupon_id', []],//赠送优惠券ID
            ['give_product_id', []],//赠送商品ID
            ['give_product_unique', []],//赠送商品规格唯一值
            [['is_label', 'd'], 0],
            ['label_id', []],//关联标签
            [['product_partake_type', 'd'], 1],//商品参与类型
            ['product_id', []],//关联商品
            [['is_limit', 'd'], 0],//是否限量
            [['limit_num', 'd'], 0],//限量个数
            [['is_overlay', 'd'], 0],
            ['overlay', []],//叠加方式方式
            ['section_time', []],//时间
            [['sort', 'd'], 0],//排序
        ]);
        $data['promotions_type'] = $type;
        if (!$data['is_label']) {
            $data['label_id'] = [];
        }
        if (!$data['is_overlay']) {
            $data['overlay'] = [];
        }
        unset($data['is_label'], $data['is_overlay']);
        $data['promotions'][] = [
            'threshold_type' => $data['threshold_type'],
            'threshold' => $data['threshold'],
            'discount_type' => $data['discount_type'],
            'n_piece_n_discount' => $data['n_piece_n_discount'],
            'discount' => $data['discount'],
            'give_integral' => $data['give_integral'],
            'give_coupon_id' => [],
            'give_product_id' => [],
            'give_product_unique' => [],
        ];
        $this->services->saveData((int)$id, $data);
        return $this->success('保存成功');
    }

    /**
     * 保存促销活动
     * @param $type
     * @param $id
     * @return mixed
     */
    public function save($type, $id)
    {
        $data = $this->request->postMore([
            [['promotions_cate', 'd'], 1],//优惠类型
            [['threshold_type', 'd'], 1],//门槛类型1:满N元2:满N件
            [['name', 's'], ''],//名称
            [['is_label', 'd'], 0],
            ['label_id', []],//关联标签
            [['product_partake_type', 'd'], 1],//商品参与类型
            ['product_id', []],//关联商品
            [['is_limit', 'd'], 0],//是否限量
            [['limit_num', 'd'], 0],//限量个数
            [['is_overlay', 'd'], 0],
            ['overlay', []],//叠加方式方式
            ['section_time', []],//时间
            [['sort', 'd'], 0],//排序
            ['promotions', []]//阶梯优惠数组
        ]);
        $data['promotions_type'] = $type;
        if (!$data['is_label']) {
            $data['label_id'] = [];
        }
        if (!$data['is_overlay']) {
            $data['overlay'] = [];
        }
        unset($data['is_label'], $data['is_overlay']);
        $this->services->saveData((int)$id, $data);
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
        $this->services->update(['id|pid' => $id], ['is_del' => 1]);
        return $this->success('删除成功!');
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function setStatus($id, $status)
    {
        $this->services->update($id, ['status' => $status, 'update_time' => time()]);
        return $this->success($status == 0 ? '关闭成功' : '开启成功');
    }

}
