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


use app\model\user\label\UserLabel;
use app\model\user\label\UserLabelRelation;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\model\relation\HasManyThrough;
use think\model\relation\HasOne;

/**
 * 企业微信成员
 * Class WorkMember
 * @package app\model\work
 */
class WorkMember extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_member';

    /**
     * @var string
     */
    protected $key = 'id';

    /**
     * @var string
     */
    protected $autoWriteTimestamp = 'int';

    public function department()
    {
        return $this->hasManyThrough(WorkDepartment::class, WorkMemberRelation::class, 'member_id', 'department_id', 'id', 'department');
    }

    /**
     * 主部门
     * @return HasOne
     */
    public function mastareDepartment()
    {
        return $this->hasOne(WorkDepartment::class, 'department_id', 'main_department')->field(['department_id', 'name'])->bind(['mastare_department_name' => 'name']);
    }

    /**
     * @return \think\model\relation\HasMany
     */
    public function departmentRelation()
    {
        return $this->hasMany(WorkMemberRelation::class, 'member_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function chat()
    {
        return $this->hasOne(WorkGroupChatMember::class, 'userid', 'userid')->where('type', 1);
    }

    /**
     * @return HasOne
     */
    public function clientFollow()
    {
        return $this->hasOne(WorkClientFollow::class, 'userid', 'userid');
    }

    /**
     * @return HasManyThrough
     */
    public function tags()
    {
        return $this->hasManyThrough(
            UserLabel::class,
            UserLabelRelation::class,
            'phone',
            'id',
            'mobile',
            'id'
        );
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setDirectLeaderAttr($value)
    {
        return is_string($value) ? $value : json_encode($value);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getDirectLeaderAttr($value)
    {
        return json_decode($value, true);
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchUseridAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('userid', $value);
        } else {
            $query->where('userid', $value);
        }
    }

    /**
     * 企业id查询
     * @param $query
     * @param $value
     */
    public function searchCorpIdAttr($query, $value)
    {
        $query->where('corp_id', $value);
    }
}
