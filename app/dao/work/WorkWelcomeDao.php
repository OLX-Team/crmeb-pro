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
use app\model\work\WorkWelcome;
use crmeb\basic\BaseAuth;
use crmeb\traits\SearchDaoTrait;

/**
 * Class WorkWelcomeDao
 * @package app\dao\wechat\work
 */
class WorkWelcomeDao extends BaseDao
{

    use SearchDaoTrait;

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WorkWelcome::class;
    }

    /**
     * 搜索
     * @param array $where
     * @param bool $authWhere
     * @return \crmeb\basic\BaseModel
     */
    public function searchWhere(array $where, bool $authWhere = true)
    {
        [$with] = app()->make(BaseAuth::class)->________(array_keys($where), $this->setModel());

        return $this->getModel()->withSearch($with, $where)->when(!empty($where['userids']), function ($query) use ($where) {
            $query->whereIn('id', function ($query) use ($where) {
                $query->name('wechat_work_welcome_relation')->whereIn('userid', $where['userids'])->field(['welcome_id']);
            });
        })->when(!empty($where['id']), function ($query) use ($where) {
            if (is_array($where['id'])) {
                $query->whereIn('id', $where['id']);
            } else {
                $query->where('id', $where['id']);
            }
        });
    }
}
