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
use app\model\work\WorkMemberRelation;

/**
 * 企业微信成员关联表
 * Class WorkMemberRelationDao
 * @package app\dao\work
 */
class WorkMemberRelationDao extends BaseDao
{

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WorkMemberRelation::class;
    }

    /**
     * @param array $where
     * @param array|string[] $field
     * @return array
     */
    public function getMemberRelationList(array $where, array $field = ['*'])
    {
        return $this->getModel()->where($where)->field($field)->select()->toArray();
    }
}
