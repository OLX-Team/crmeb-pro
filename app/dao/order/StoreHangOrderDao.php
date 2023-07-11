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

namespace app\dao\order;


use app\dao\BaseDao;
use app\model\order\StoreHangOrder;

/**
 * Class StoreHangOrderDao
 * @package app\dao\order
 */
class StoreHangOrderDao extends BaseDao
{

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return StoreHangOrder::class;
    }

    /**
     * @param array $where
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHang(array $where, int $limit = 50)
    {
        return $this->search($where)->with('user')->limit($limit)->order('add_time desc')->select()->toArray();
    }

    /**
     * 获取挂单分页
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHangPage(array $where, int $page, int $limit)
    {
        return $this->search($where)->when(isset($where['search']) && $where['search'] !== '', function ($query) use ($where) {
            $query->whereIn('uid', function ($query) use ($where) {
                $query->name('user')->whereIn('uid', function ($query) use ($where) {
                    $query->name('store_user')->where('store_id', $where['store_id'])->field('uid');
                })->where('phone|nickname', 'like', "%" . $where['search'] . "%")->field('uid');
            });
        })->with('user')->page($page, $limit)->order('add_time desc')->select()->toArray();
    }
}
