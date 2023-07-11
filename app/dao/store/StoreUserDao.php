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

namespace app\dao\store;


use app\dao\BaseDao;
use app\model\store\StoreUser;

/**
 * 门店用户
 * Class StoreUserDao
 * @package app\dao\store
 */
class StoreUserDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreUser::class;
    }


    /**
     * 获取用户列表
     * @param array $where
     * @param string $field
     * @param array $with
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', array $with = [], int $page = 0, int $limit = 0): array
    {
        return $this->search($where)->field($field)->when($with, function ($query) use ($with) {
            $query->with(['label']);
        })->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->select()->toArray();
    }

    /**
     * 获取一段时间内新增人数
     * @param array $where
     * @param array $time
     * @param string $timeType
     * @param string $countField
     * @param string $sumField
     * @param string $groupField
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userTimeList(array $where, array $time, string $timeType = "week", string $countField = '*', string $sumField = 'pay_price', string $groupField = 'add_time')
    {
        return $this->getModel()->where($where)
            ->where($groupField, 'between time', $time)
            ->when($timeType, function ($query) use ($timeType, $countField, $sumField, $groupField) {
                switch ($timeType) {
                    case "hour":
                        $timeUnix = "%H";
                        break;
                    case "week" :
                        $timeUnix = "%w";
                        break;
                    case "month" :
                        $timeUnix = "%d";
                        break;
                    case "weekly" :
                        $timeUnix = "%W";
                        break;
                    case "year" :
                        $timeUnix = "%Y-%m";
                        break;
                    default:
                        $timeUnix = "%m-%d";
                        break;
                }
                $query->field("FROM_UNIXTIME(`" . $groupField . "`,'$timeUnix') as day,count(" . $countField . ") as count");
                $query->group('day');
            })->order('add_time asc')->select()->toArray();
    }
}
