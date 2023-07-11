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

namespace app\dao\store\finance;


use app\dao\BaseDao;
use app\model\store\finance\StoreFinanceFlow;

/**
 * 门店流水
 * Class StoreExtractDao
 * @package app\dao\store\finance
 */
class StoreFinanceFlowDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreFinanceFlow::class;
    }


    /**
     * 获取提现列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', int $page = 0, int $limit = 0, array $with = [])
    {
        return $this->search($where)
            ->when($with, function ($query) use ($with) {
                $query->with($with);
            })->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->field($field)->order('id desc')->select()->toArray();
    }

    /**
     *
     * @param array $where
     * @return \crmeb\basic\BaseModel|int|mixed|\think\Model
     */
    public function getCount(array $where = [])
    {
        return $this->search($where)->count();
    }

    /**
     * 搜索
     * @param array $where
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    public function search(array $where = [])
    {
        return parent::search($where)
            ->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                $query->where(function ($que) use ($where) {
                    $que->whereLike('order_id', '%' . $where['keyword'] . '%')->whereOr('uid', 'in', function ($q) use ($where) {
                        $q->name('user')->whereLike('nickname|uid', '%' . $where['keyword'] . '%')->field(['uid'])->select();
                    });
                });
            });
    }

    /**
     * 门店账单
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getFundRecord(array $where = [], int $page = 0, int $limit = 0)
    {

        $model = parent::search($where)
            ->when(isset($where['timeType']) && $where['timeType'] !== '', function ($query) use ($where) {
                switch ($where['timeType']) {
                    case "day" :
                        $timeUnix = "%d";
                        break;
                    case "week" :
                        $timeUnix = "%u";
                        break;
                    case "month" :
                        $timeUnix = "%m";
                        break;
                }
                $query->field("FROM_UNIXTIME(add_time,'$timeUnix') as day,sum(if(pm = 1,number,0)) as income_num,sum(if(pm = 0,number,0)) as exp_num,add_time,group_concat(id) as ids");
                $query->group("FROM_UNIXTIME(add_time, '$timeUnix')");
            });
        $count = $model->count();
        $list = $model->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order('add_time desc')->select()->toArray();
        return compact('list', 'count');
    }

    /**
     * 店员交易统计头部数据
     * @param array $where
     * @param string $group
     * @param string $field
     * @return mixed
     */
    public function getStatisticsHeader(array $where = [], $group = 'staff_id', string $field = 'number')
    {
        return parent::search($where)->with(['systemStoreStaff'])
            ->field("*,sum(`" . $field . "`) as total_number,count(*) as order_count")
            ->group($group)
            ->order('total_number desc')
            ->select()->toArray();
    }

    /**
     * 获取一段时间订单统计数量、金额
     * @param array $where
     * @param array $time
     * @param string $timeType
     * @param string $countField
     * @param string $sumField
     * @param string $groupField
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderAddTimeList(array $where, array $time, string $timeType = "week", string $countField = '*', string $sumField = 'number', string $groupField = 'add_time')
    {
        return parent::search($where)
            ->where(isset($where['timekey']) && $where['timekey'] ? $where['timekey'] : 'add_time', 'between time', $time)
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
                $query->field("FROM_UNIXTIME(`" . $groupField . "`,'$timeUnix') as day,count(" . $countField . ") as count,sum(`" . $sumField . "`) as price");
                $query->group('day');
            })->order('add_time asc')->select()->toArray();
    }
}
