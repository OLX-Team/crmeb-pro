<?php


namespace app\dao\work;


use app\dao\BaseDao;
use app\model\work\WorkMoment;
use crmeb\traits\SearchDaoTrait;

/**
 * 朋友圈
 * Class WorkMoment
 * @package app\dao\work
 */
class WorkMomentDao extends BaseDao
{
    use SearchDaoTrait;

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WorkMoment::class;
    }
}
