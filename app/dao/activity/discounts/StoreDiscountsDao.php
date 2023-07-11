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

namespace app\dao\activity\discounts;

use app\dao\BaseDao;
use app\model\activity\discounts\StoreDiscounts;


/**
 * 优惠套餐
 * Class StoreDiscountsDao
 * @package app\dao\activity\discounts
 */
class StoreDiscountsDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreDiscounts::class;
    }

    public function search(array $where = [])
    {
        return parent::search($where)
            ->when(isset($where['is_time']) && $where['is_time'] == 1, function ($query) {
                $query->where(function ($q) {
                    $q->where(function ($query) {
                        $query->where('start_time', '<=', time())->where('stop_time', '>=', time());
                    })->whereOr(function ($query) {
                        $query->where('start_time', 0)->where('stop_time', 0);
                    });
                });
            });
    }

    /**
     * 获取列表
     * @param $where
     * @param array $with
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where, array $with = [], $page = 0, $limit = 0)
    {
        return $this->search($where)->where('is_del', 0)
            ->when($page != 0 && $limit != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->when($with, function ($query) use ($with) {
                $query->with($with);
            })->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 优惠套餐列表
     * @param $product_id
     * @param int $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiscounts($product_id, $type = 0)
    {
        return $this->search(['product_ids' => $product_id])
            ->where('is_del', 0)->where('status', 1)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<=', time())->where('stop_time', '>=', time());
                })->whereOr(function ($query) {
                    $query->where('start_time', 0)->where('stop_time', 0);
                });
            })->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('is_limit', 0);
                })->whereOr(function ($query) {
                    $query->where('is_limit', 1)->where('limit_num', '>', 0);
                });
            })->with(['products'])->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 优惠套餐数量
     * @return int
     */
    public function getDiscountsCount()
    {
        return $this->getModel()
            ->where('is_del', 0)->where('status', 1)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('start_time', '<=', time())->where('stop_time', '>=', time());
                })->whereOr(function ($query) {
                    $query->where('start_time', 0)->where('stop_time', 0);
                });
            })->count();
    }

    public function decLimitNum($id, $num = 1)
    {
        return $this->getModel()->where('id', $id)->dec('limit_num', $num)->update();
    }

    public function incLimitNum(int $id, $num = 1)
    {
        return $this->getModel()->where('id', $id)->inc('limit_num', $num)->update();
    }
}
