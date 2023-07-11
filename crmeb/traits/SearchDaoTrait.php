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

namespace crmeb\traits;

use app\dao\BaseDao;
use crmeb\basic\BaseAuth;

/**
 * Trait SearchDaoTrait
 * @package crmeb\traits
 * @mixin BaseDao
 */
trait SearchDaoTrait
{

    /**
     * 搜索（没有进入搜索器的自动进入where条件）
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

        return $this->getModel()->withSearch($with, $where)->when($authWhere && $whereData, function ($query) use ($whereData) {
            $query->where($whereData);
        });
    }

    /**
     * @param array $where
     * @param bool $authWhere
     * @return int
     */
    public function count(array $where = [], bool $authWhere = true): int
    {
        return $this->searchWhere($where, $authWhere)->count();
    }

    /**
     * 搜索
     * @param array $where
     * @param array|string[] $field
     * @param int $page
     * @param int $limit
     * @param null $sort
     * @param array $with
     * @return array
     */
    public function getDataList(array $where, array $field = ['*'], int $page = 0, int $limit = 0, $sort = null, array $with = [])
    {
        return $this->searchWhere($where)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->when(!$page && $limit, function ($query) use ($limit) {
            $query->limit($limit);
        })->when($sort, function ($query, $sort) {
            if (is_array($sort)) {
                foreach ($sort as $k => $v) {
                    if (is_numeric($k)) {
                        $query->order($v, 'desc');
                    } else {
                        $query->order($k, $v);
                    }
                }
            } else {
                $query->order($sort, 'desc');
            }
        })->field($field)->with($with)->select()->toArray();
    }
}
