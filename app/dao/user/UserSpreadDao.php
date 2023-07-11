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

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\UserSpread;

/**
 * Class UserSpreadDao
 * @package app\dao\user
 */
class UserSpreadDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserSpread::class;
    }

    /**
     * 获取推广列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', array $with = [], int $page = 0, int $limit = 0)
    {
        return $this->search($where)->field($field)
            ->when($with, function ($query) use ($with) {
                $query->with($with);
            })->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order('spread_time desc,id desc')->select()->toArray();
    }

    /**
     * 获取推广uids
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSpreadUids(array $where)
    {
        return $this->search($where)->order('spread_time desc,id desc')->column('uid');
    }

    /**
     * 获取一段时间内推广人数
     * @param $datebefor
     * @param $dateafter
     * @return mixed
     */
    public function spreadTimeList(array $where, array $time, string $timeType = "week", string $countField = '*', string $sumField = 'pay_price', string $groupField = 'add_time')
    {
        return $this->getModel()->where($where)
            ->where($groupField, 'between time', $time)
            ->when($timeType, function ($query) use ($timeType, $countField, $sumField, $groupField) {
                switch ($timeType) {
                    case "hour":
                        $timeUnix = "%H";
                        break;
                    case "day" :
                        $timeUnix = "%m-%d";
                        break;
                    case "week" :
                        $timeUnix = "%w";
                        break;
                    case "month" :
                        $timeUnix = "%d";
                        break;
                    case "year" :
                        $timeUnix = "%m";
                        break;
                    default:
                        $timeUnix = "%m-%d";
                        break;
                }
                $query->field("FROM_UNIXTIME(`" . $groupField . "`,'$timeUnix') as day,count(`" . $countField . "`) as count,sum(`" . $sumField . "`) as price");
                $query->group("FROM_UNIXTIME($groupField, '$timeUnix')");
            })->order('add_time asc')->select()->toArray();
    }

	/**
     * 获取推广人排行
     * @param array $time
     * @param string $field
     * @param int $page
     * @param int $limit
     */
    public function getAgentRankList(array $time, string $field = '*', int $page = 0, int $limit = 0)
    {
        return $this->getModel()->with(['spreadUser'])
            ->field($field)
            ->order('count desc')
            ->order('uid desc')
            ->where('spread_time', 'BETWEEN', $time)
            ->page($page, $limit)
            ->group('spread_uid')
            ->select()->toArray();
    }
}
