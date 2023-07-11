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

namespace app\dao\order;

use app\dao\BaseDao;
use app\model\order\StoreCart;

/**
 *
 * Class StoreCartDao
 * @package app\dao\order
 */
class StoreCartDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreCart::class;
    }

    /**
     * @param array $where
     * @param array $unique
     * @return array
     */
    public function getUserCartNums(array $where, array $unique)
    {
        return $this->search($where)->whereIn('product_attr_unique', $unique)->column('cart_num', 'product_attr_unique');
    }

    /**
     * 获取挂单数据
     * @param string $search
     * @param int $storeId
     * @param int $staffId
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getHangOrder(int $storeId, int $staffId = 0, string $search = '', int $page = 0, int $limit = 0)
    {
        return $this->getModel()->where([
            'status' => 1,
            'is_del' => 0,
            'is_pay' => 0,
			'is_new' => 0,
            'store_id' => $storeId
        ])->when($staffId, function ($query) use ($staffId) {
			$query->where('staff_id', $staffId);
        })->when($search !== '', function ($query) use ($search) {
            $query->whereIn('uid', function ($query) use ($search) {
                $query->name('user')->where('uid|phone|nickname', 'like', "%" . $search . "%")->field('uid');
            });
        })->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->field(['tourist_uid', 'add_time', 'GROUP_CONCAT(id) as cart_id', 'store_id', 'staff_id', 'uid'])->with('user')
            ->group('uid,tourist_uid');
    }

    /**
     * 搜索
     * @param array $where
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    public function search(array $where = [])
    {
        return parent::search($where)->when(isset($where['id']) && $where['id'], function ($query) use ($where) {
            $query->whereIn('id', $where['id']);
        })->when(isset($where['status']), function ($query) use ($where) {
            $query->where('status', $where['status']);
        });
    }

    /**
     * 根据商品id获取购物车数量
     * @param array $ids
     * @param int $uid
     * @param int $staff_id
     * @return mixed
     */
    public function productIdByCartNum(array $ids, int $uid, int $staff_id = 0, int $touristUid = 0, int $storeId = 0)
    {
        return $this->search([
			'product_id' => $ids,
			'is_pay' => 0,
			'is_del' => 0,
			'is_new' => 0,
			'tourist_uid' => $touristUid,
			'uid' => $uid,
			'store_id' => $storeId])->when($staff_id, function ($query) use ($staff_id) {
			$query->where('staff_id', $staff_id);
        })->group('product_attr_unique')->column('cart_num,product_id', 'product_attr_unique');
    }

    /**
     * 获取购物车列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCartList(array $where, int $page = 0, int $limit = 0, array $with = [])
    {
        if (isset($where['store_id']) && $where['store_id']) {
            $with = array_merge($with, ['storeBranchProductAttr' => function ($querys) use ($where) {
                $querys->where('store_id', $where['store_id'])->bind([
                    'branch_sales' => 'sales',
                    'branch_stock' => 'stock'
                ]);
            }]);
        }
        return $this->search($where)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->when(count($with), function ($query) use ($with, $where) {
            $query->with($with);
        })->when(isset($where['is_cashier']), function ($query) use ($where) {
            $query->where('is_cashier', $where['is_cashier']);
        })->order('add_time DESC')->select()->toArray();
    }

    /**
     * 修改购物车数据未已删除
     * @param array $id
     * @param array $data
     * @return \crmeb\basic\BaseModel
     */
    public function updateDel(array $id)
    {
        return $this->getModel()->whereIn('id', $id)->update(['is_del' => 1]);
    }

    /**
     * 删除购物车
     * @param int $uid
     * @param array $ids
     * @return bool
     * @throws \Exception
     */
    public function removeUserCart(int $uid, array $ids)
    {
        return $this->getModel()->where('uid', $uid)->whereIn('id', $ids)->delete();
    }

    /**
     * 获取购物车数量
     * @param $uid
     * @param $type
     * @param $numType
     */
    public function getUserCartNum($uid, $type, $numType)
    {
        $model = $this->getModel()->where(['uid' => $uid, 'type' => $type, 'is_pay' => 0, 'is_new' => 0, 'is_del' => 0]);
        if ($numType) {
            return $model->count();
        } else {
            return $model->sum('cart_num');
        }
    }

    /**
     * 用户购物车统计数据
     * @param int $uid
     * @param string $field
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserCartList(array $where, string $field = '*', array $with = [])
    {
        return $this->getModel()->where(array_merge($where, ['is_pay' => 0, 'is_new' => 0, 'is_del' => 0, 'type' => 0]))
            ->when(count($with), function ($query) use ($with) {
                $query->with($with);
            })->order('add_time DESC')->field($field)->select()->toArray();
    }

    /**
     * 修改购物车数量
     * @param $cartId
     * @param $cartNum
     * @param $uid
     */
    public function changeUserCartNum(array $where, int $carNum)
    {
        return $this->getModel()->where($where)->update(['cart_num' => $carNum]);
    }

    /**
     * 修改购物车状态
     * @param $cartIds
     * @return \crmeb\basic\BaseModel
     */
    public function deleteCartStatus($cartIds)
    {
        return $this->getModel()->where('id', 'IN', $cartIds)->delete();
    }

    /**
     * 获取购物车最大的id
     * @return mixed
     */
    public function getCartIdMax()
    {
        return $this->getModel()->max('id');
    }

    /**
     * 求和
     * @param $where
     * @param $field
     * @return float
     */
    public function getSum($where, $field)
    {
        return $this->search($where)->sum($field);
    }

    /**
     * 购物车趋势
     * @param $time
     * @param $timeType
     * @param $str
     * @return mixed
     */
    public function getProductTrend($time, $timeType, $str)
    {
        return $this->getModel()->where(function ($query) use ($time) {
            if ($time[0] == $time[1]) {
                $query->whereDay('add_time', $time[0]);
            } else {
                $time[1] = date('Y/m/d', strtotime($time[1]) + 86400);
                $query->whereTime('add_time', 'between', $time);
            }
        })->field("FROM_UNIXTIME(add_time,'$timeType') as days,$str as num")->group('days')->select()->toArray();
    }
}
