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

namespace app\dao\work;


use app\dao\BaseDao;
use app\model\work\WorkGroupChatMember;
use crmeb\traits\SearchDaoTrait;

/**
 * 企业微信群成员
 * Class WorkGroupChatMemberDao
 * @package app\dao\work
 */
class WorkGroupChatMemberDao extends BaseDao
{

    use SearchDaoTrait;

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WorkGroupChatMember::class;
    }

    /**
     * 获取今日新增成员数
     * @param int $groupId
     * @return int
     */
    public function getToDaySum(int $groupId)
    {
        return $this->getModel()->where('status', 1)->where('group_id', $groupId)->whereDay('join_time')->count();
    }

    /**
     * 获取今日退群成员数
     * @param int $groupId
     * @return int
     */
    public function getToDayReturn(int $groupId)
    {
        return $this->getModel()->where('status', 0)->where('group_id', $groupId)->whereDay('join_time')->count();
    }

    /**
     * 获取人数统计
     * @param int $groupId
     * @param string $fromUnixtime
     * @param array $field
     * @param int $status
     * @param string $time
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public function getChatMemberStatistics(int $groupId, string $fromUnixtime = 'create_time', array $field = [], int $status = 1, string $time = '', int $page = 0, int $limit = 0)
    {
        $date = '%Y-%m-%d';
        switch ($time) {
            case 'today':
            case 'yesterday':
                $date = '%Y-%m-%d %H';
                break;
            case 'week':
            case 'last week':
            case 'last month':
            case 'month':
                $date = '%Y-%m-%d';
                break;
            case 'year':
            case 'last year':
                $date = '%Y-%m';
                break;
        }
        return $this->search(['time' => $time, 'timeKey' => $fromUnixtime])
            ->where('group_id', $groupId)
            ->where('status', $status)
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })
            ->field(array_merge($field, ['from_unixtime(' . $fromUnixtime . ',"' . $date . '") as time']))
            ->group('time')
            ->select()->toArray();
    }

    /**
     * 获取人数统计条数
     * @param int $groupId
     * @param int $status
     * @param string $time
     * @return mixed
     */
    public function getChatMemberStatisticsCount(int $groupId, int $status = 1, string $time = '')
    {
        $date = '%Y-%m-%d';
        switch ($time) {
            case 'today':
            case 'yesterday':
                $date = '%Y-%m-%d %H';
                break;
            case 'week':
            case 'last week':
            case 'last month':
            case 'month':
                $date = '%Y-%m-%d';
                break;
            case 'year':
            case 'last year':
                $date = '%Y-%m';
                break;
        }
        return $this->search(['time' => $time, 'timeKey' => 'update_time'])
            ->where('group_id', $groupId)
            ->where('status', $status)
            ->field(['from_unixtime(update_time,"' . $date . '") as time'])
            ->group('time')
            ->count();
    }

    /**
     * 当前用户所在群个数
     * @param string $userid
     * @return int
     */
    public function getChatSum(string $userid)
    {
        return $this->getModel()->where('userid', $userid)->group('group_id')->count();
    }
}
