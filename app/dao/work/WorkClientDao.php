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
use app\model\work\WorkClient;
use crmeb\basic\BaseAuth;
use crmeb\traits\SearchDaoTrait;

/**
 * 企业微信客户
 * Class WorkClientDao
 * @package app\dao\work
 */
class WorkClientDao extends BaseDao
{

    use SearchDaoTrait;

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WorkClient::class;
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
            if (isset($where[$key])) {
                $whereData[$key] = $where[$key];
            }
        }

        return $this->getModel()->withSearch($with, $where)->when(!empty($where['label']) || !empty($where['notLabel']), function ($query) use ($where) {
            $query->whereIn('id', function ($query) use ($where) {
                $query->name('work_client_follow')->whereIn('id', function ($query) use ($where) {
                    $query->name('work_client_follow_tags')->when(!empty($where['label']), function ($query) use ($where) {
                        $query->whereIn('tag_id', $where['label']);
                    })->when(!empty($where['notLabel']), function ($query) use ($where) {
                        $query->whereNotIn('tag_id', $where['notLabel']);
                    })->field('follow_id');
                })->field('client_id');
            });
        })->when(!empty($where['userid']), function ($query) use ($where) {
            $query->whereIn('id', function ($query) use ($where) {
                $query->name('work_client_follow')->when(!empty($where['state']), function ($query) use ($where) {
                    $query->where('state', '<>', '');
                })->whereIn('userid', $where['userid'])->field('client_id');
            });
        })->when(isset($where['name']) && '' !== $where['name'], function ($query) use ($where) {
            $query->whereLike('name', '%' . $where['name'] . '%');
        })->when(!empty($where['corp_id']), function ($query) use ($where) {
            $query->where('corp_id', $where['corp_id']);
        })->when(!empty($where['gender']), function ($query) use ($where) {
            $query->where('gender', $where['gender']);
        })->when(!empty($where['state']) && empty($where['userid']), function ($query) use ($where) {
            $query->whereIn('id', function ($query) use ($where) {
                $query->name('work_client_follow')->where('state', '<>', '')->field('client_id');
            });
        })->when(!empty($where['status']), function ($query) use ($where) {
            $query->whereIn('id', function ($query) use ($where) {
                $query->name('work_client_follow')->where('is_del_user', $where['status'])->field('client_id');
            });
        })->when(!empty($where['notUserid']), function ($query) use ($where) {
            $query->whereNotIn('external_userid', $where['notUserid']);
        });
    }

    /**
     * @param array $where
     * @return int
     */
    public function getClientCount(array $where)
    {
        return $this->searchWhere($where)->count();
    }

    /**
     * 获取客户userid
     * @param array $where
     * @return array
     */
    public function getClientUserIds(array $where)
    {
        return $this->searchWhere($where)->column('external_userid');
    }
}
