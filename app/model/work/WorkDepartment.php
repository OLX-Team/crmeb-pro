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

namespace app\model\work;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 企业微信部门
 * Class WorkDepartment
 * @package app\model\work
 */
class WorkDepartment extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_department';

    /**
     * @var string
     */
    protected $key = 'id';

    /**
     * @var string
     */
    protected $autoWriteTimestamp = 'int';

    /**
     * 远程关联成员
     * @return \think\model\relation\HasManyThrough
     */
    public function member()
    {
        return $this->hasManyThrough(
            WorkMember::class,
            WorkMemberRelation::class, 'department', 'id', 'department_id', 'member_id');
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchMobileAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('mobile', $value);
        } else {
            $query->where('mobile', $value);
        }
    }
}
