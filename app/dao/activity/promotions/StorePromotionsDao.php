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
declare (strict_types=1);

namespace app\dao\activity\promotions;

use app\dao\BaseDao;
use app\model\activity\promotions\StorePromotions;

/**
 * 促销活动
 * Class StorePromotionsDao
 * @package app\dao\activity\promotions
 */
class StorePromotionsDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StorePromotions::class;
    }

    /**
     * 搜索
     * @param array $where
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    protected function search(array $where = [])
    {
        return parent::search($where)->when(isset($where['promotionsTime']), function ($query) use ($where) {
            [$startTime, $stopTime] = is_array($where['promotionsTime']) ? $where['promotionsTime'] : [time(), time()];
            $query->where('start_time', '<=', $startTime)->where('stop_time', '>=', $stopTime);
        })->when(isset($where['ids']) && $where['ids'], function($query) use($where) {
            $query->whereIn('id', $where['ids']);
        })->when(isset($where['not_ids']) && $where['not_ids'], function($query) use($where) {
            $query->whereNotIn('id', $where['not_ids']);
        })->when(isset($where['start_status']) && $where['start_status'] !== '', function ($query) use ($where) {
            $time = time();
            switch ($where['start_status']) {
                case -1:
                    $query->where(function ($q) use ($time) {
                        $q->where('stop_time', '<', $time)->whereOr('status', '0');
                    });
                    break;
                case 0:
                    $query->where('start_time', '>', $time)->where('status', 1);
                    break;
                case 1:
                    $query->where('start_time', '<=', $time)->where('stop_time', '>=', $time)->where('status', 1);
                    break;
            }
        })->when(isset($where['product_id']) && $where['product_id'], function ($query) use ($where) {
            $query->where(function ($q) use ($where) {
                $q->whereOr('product_partake_type', 1)
                ->whereOr(function ($w) use ($where) {
                    $w->where('product_partake_type', 2)->whereIn('id', function ($a) use ($where) {
                        $a->name('store_promotions_auxiliary')->field('promotions_id')->where('type', 1)->where('product_partake_type', 2)->where(function ($p)use($where){
                            if(is_array($where['product_id'])){
                                $p->whereIn('product_id', $where['product_id']);
                            }else{
                                $p->where('product_id', $where['product_id']);
                            }
                        });
                    });
                })->whereOr(function ($e) use ($where) {
                    $e->where('product_partake_type', 3)->whereNotIn('id', function ($a) use ($where) {
                        $a->name('store_promotions_auxiliary')->field('promotions_id')->where('type', 1)->where('product_partake_type', 3)->where('is_all', 1)->where(function ($p)use($where){
                            if(is_array($where['product_id'])){
                                $p->whereIn('product_id', $where['product_id']);
                            }else{
                                $p->where('product_id', $where['product_id']);
                            }
                        });
                    });
                });
            });
        });
    }

    /**
     * 获取促销活动列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @param array $with
     * @param string $order
     * @param string $group
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', int $page = 0, int $limit = 0, array $with = [], string $order = 'update_time desc,id desc', string $group = '')
    {
        return $this->search($where)->field($field)
            ->when($page != 0 && $limit != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->when($with, function ($query) use ($with) {
                $query->with($with);
            })->order($order)->when($group, function($query) use($group) {
                $query->group($group);
            })->select()->toArray();
    }


    /**
     * 获取一条活动
     * @param int $id
     * @param string $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function validPromotions(int $id, string $field = '*')
    {
        $where = ['status' => 1, 'is_del' => 0];
        $time = time();
        return $this->search($where)
            ->where('id', $id)
            ->where('start_time', '<=', $time)
            ->where('stop_time', '>=', $time)
            ->field($field)->find();
    }

    /**
     * 获取一个活动包含子集的所有ID
     * @param int $id
     * @return array
     */
    public function getOnePromotionsIds(int $id)
    {
        $result = $this->getModel()->where('id|pid', $id)->column('id');
        $res = [];
        if ($result) {
            $res = array_column($result, 'id');
        }
        return $res;
    }
}
