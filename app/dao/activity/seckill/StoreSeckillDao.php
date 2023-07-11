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

namespace app\dao\activity\seckill;

use app\dao\BaseDao;
use app\model\activity\seckill\StoreSeckill;

/**
 * 秒杀商品
 * Class StoreSeckillDao
 * @package app\dao\activity\seckill
 */
class StoreSeckillDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreSeckill::class;
    }

    /**
     * 搜索
     * @param array $where
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    protected function search(array $where = [])
    {
        return parent::search($where)->when(isset($where['seckllTime']), function ($query) use ($where) {
            [$startTime, $stopTime] = is_array($where['seckllTime']) ? $where['seckllTime'] : [time(), time() - 86400];
            $query->where('start_time', '<=', $startTime)->where('stop_time', '>=', $stopTime);
        })->when(isset($where['storeProductId']), function ($query) {
            $query->where('product_id', 'IN', function ($query) {
                $query->name('store_product')->where('is_show', 1)->where('is_del', 0)->field('id');
            });
        })->when(isset($where['time_id']) && $where['time_id'], function ($query) use ($where) {
            $query->where('time_id', $where['time_id']);
        })->when(isset($where['start_status']) && $where['start_status'] !== '', function ($query) use ($where) {
            $time = time();
            switch ($where['start_status']) {
                case -1:
                    $query->where(function ($q) use ($time) {
                        $q->where('stop_time', '<', $time - 86400)->whereOr('status', '0');
                    });
                    break;
                case 0:
                    $query->where('start_time', '>', $time)->where('status', 1);
                    break;
                case 1:
                    $query->where('start_time', '<=', $time)->where('stop_time', '>=', $time - 86400)->where('status', 1);
                    break;
            }
        });
    }

    /**
     * 获取秒杀列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, int $page = 0, int $limit = 0)
    {
        return $this->search($where)
            ->when($page != 0 && $limit != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 根据商品id获取当前正在开启秒杀产品的列表以数组返回
     * @param array $ids
     * @param array $field
     * @return array
     */
    public function getSeckillIdsArray(array $ids, array $field = [])
    {
        return $this->search(['is_del' => 0, 'status' => 1])
            ->where('start_time', '<=', time())
            ->where('stop_time', '>=', time() - 86400)
            ->whereIn('product_id', $ids)
            ->field($field)->select()->toArray();
    }

    /**
 	* 获取某个时间段的秒杀列表
	* @param int $timeId
	* @param array $ids
	* @param int $page
	* @param int $limit
	* @param bool $isStore
	* @return array
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	 */
    public function getListByTime(int $timeId, array $ids = [], int $page = 0, int $limit = 0, bool $isStore = false)
    {
        if ($timeId == 0) return [];
        return $this->search(['is_del' => 0, 'status' => 1])
            ->where('start_time', '<=', time())
            ->where('stop_time', '>=', time() - 86400)
            ->when($timeId, function ($query) use ($timeId) {
                $query->where('time_id', $timeId);
            })->where('product_id', 'IN', function ($query) {
                $query->name('store_product')->where('is_show', 1)->where('is_del', 0)->field('id');
            })->when($ids, function($query) use($ids) {
                $query->whereIn('product_id', $ids);
            })->when($isStore, function($query) {
                $query->where(function ($q) {
					$q->whereOr(function ($c) {
                            $c->whereFindInSet('delivery_type', 2);
                        })->whereOr(function ($d) {
                            $d->whereFindInSet('delivery_type', 3);
                        });
                });
            })->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 根据id获取秒杀数据
     * @param array $ids
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function idSeckillList(array $ids, string $field)
    {
        return $this->getModel()->whereIn('id', $ids)->field($field)->select()->toArray();
    }

    /**获取一条秒杀商品
     * @param $id
     * @param $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function validProduct($id, $field)
    {
        $where = ['status' => 1, 'is_del' => 0];
        $time = time();
        return $this->search($where)
            ->where('id', $id)
            ->where('start_time', '<', $time)
            ->where('stop_time', '>', $time - 86400)
            ->field($field)->with(['product'])->find();
    }

    /**
 	* 获取正在开启的秒杀总数
	* @param $timeId
	* @param $ids
	* @param $not_ids
	* @param bool $isStore
	* @return int
	 */
    public function getTimeCount($timeId, $ids = [], $not_ids = [], bool $isStore = true)
    {
        if ($timeId == 0) return 0;
        return $this->search(['is_del' => 0, 'status' => 1])
            ->where('start_time', '<=', time())
            ->where('stop_time', '>=', time() - 86400)
            ->where('time_id', $timeId)->where('product_id', 'IN', function ($query) {
                $query->name('store_product')->where('is_show', 1)->where('is_del', 0)->field('id');
            })->when($ids, function($query) use($ids) {
                $query->whereIn('product_id', $ids);
            })->when($not_ids, function($query) use($not_ids) {
                $query->whereNotIn('product_id', $not_ids);
            })->when($isStore, function($query) {
                $query->where(function ($q) {
					$q->whereOr(function ($c) {
                            $c->whereFindInSet('delivery_type', 2);
                        })->whereOr(function ($d) {
                            $d->whereFindInSet('delivery_type', 3);
                        });
                });
            })->count();
    }
}
