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

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\User;use app\model\user\UserAddress;

/**
 * 用户收获地址
 * Class UserAddressDao
 * @package app\dao\user
 */
class UserAddressDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserAddress::class;
    }

	protected function JoinModel() : string
	{
	  	return User::class;
	}

	public function getJoinModel(string $alias = 'a', string $join_alias = 'u', $join = 'left')
    {
        $this->alias = $alias;
        $this->joinAlis = $join_alias;
        /** @var User $user */
        $user = app()->make($this->joinModel());
        $table = $user->getName();
        return parent::getModel()->alias($alias)->join($table . ' ' . $join_alias, $alias . '.uid = ' . $join_alias . '.uid', $join);
    }

    /**
     * 获取列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', int $page = 0, int $limit = 0): array
    {
        return $this->search($where)->field($field)->page($page, $limit)->order('is_default DESC')->select()->toArray();
    }

	/**
     * 地域全部用户
     * @param $time
     * @param $userType
     * @return mixed
     */
    public function getRegionAll($time, $userType)
    {
        return $this->getJoinModel()->when($userType != '', function ($query) use ($userType) {
            $query->where($this->joinAlis . '.user_type', $userType);
        })->where(function ($query) use ($time) {
            $query->whereTime($this->joinAlis . '.add_time', '<', strtotime($time[1]) + 86400)->whereOr($this->joinAlis . '.add_time', NULL);
        })->field('count(distinct(' . $this->alias . '.uid)) as allNum,' . $this->alias . '.province')
            ->group($this->alias . '.province')->select()->toArray();
    }

    /**
     * 地域新增用户
     * @param $time
     * @param $userType
     * @return mixed
     */
    public function getRegionNew($time, $userType)
    {
        return $this->getJoinModel()->when($userType != '', function ($query) use ($userType) {
            $query->where($this->joinAlis . '.user_type', $userType);
        })->where(function ($query) use ($time) {
            if ($time[0] == $time[1]) {
                $query->whereDay($this->joinAlis . '.add_time', $time[0]);
            } else {
                $time[1] = date('Y/m/d', strtotime($time[1]) + 86400);
                $query->whereTime($this->joinAlis . '.add_time', 'between', $time);
            }
        })->field('count(distinct(' . $this->alias . '.uid)) as newNum,' . $this->alias . '.province')
            ->group($this->alias . '.province')->select()->toArray();
    }

}
