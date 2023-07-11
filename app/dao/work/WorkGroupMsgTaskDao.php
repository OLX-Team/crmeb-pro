<?php


namespace app\dao\work;


use app\dao\BaseDao;
use app\model\work\WorkGroupMsgTask;
use crmeb\basic\BaseAuth;
use crmeb\traits\SearchDaoTrait;

/**
 * Class WorkGroupMsgTaskDao
 * @package app\dao\work
 */
class WorkGroupMsgTaskDao extends BaseDao
{

    use SearchDaoTrait;

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WorkGroupMsgTask::class;
    }

    /**
     * @param array $where
     * @param bool $authWhere
     * @return \crmeb\basic\BaseModel
     */
    public function searchWhere(array $where, bool $authWhere = true)
    {
        [$with, $whereKey] = app()->make(BaseAuth::class)->________(array_keys($where), $this->setModel());
        $whereData = [];
        foreach ($whereKey as $key) {
            if (isset($where[$key]) && 'timeKey' !== $key) {
                $whereData[$key] = $where[$key];
            }
        }

        return $this->getModel()->withSearch($with, $where)->when(!empty($where['user_name']), function ($query) use ($where) {
            $query->whereIn('userid', function ($query) use ($where) {
                $query->name('work_member')->where('name', 'like', '%' . $where['user_name'] . '%')->field(['userid']);
            });
        });
    }
}
