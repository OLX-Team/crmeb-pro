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


use app\model\user\User;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\model\concern\SoftDelete;
use think\model\relation\HasMany;
use think\model\relation\HasManyThrough;
use think\model\relation\HasOne;

/**
 * 企业微信客户
 * Class WorkClient
 * @package app\model\work
 */
class WorkClient extends BaseModel
{

    use ModelTrait, SoftDelete;

    /**
     * @var string
     */
    protected $name = 'work_client';

    /**
     * @var string
     */
    protected $key = 'id';

    /**
     * @var string
     */
    protected $autoWriteTimestamp = 'int';

    /**
     * @return HasMany
     */
    public function follow()
    {
        return $this->hasMany(WorkClientFollow::class, 'client_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function followOne()
    {
        return $this->hasOne(WorkClientFollow::class, 'client_id', 'id')->order('createtime', 'asc');
    }

    /**
     * 商城用户关联
     * @return HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }

    public function chat()
    {
        return $this->hasOne(WorkGroupChatMember::class, 'userid', 'external_userid')->where('type', 2);
    }

    /**
     * @return HasManyThrough
     */
    public function tags()
    {
        return $this->hasManyThrough(
            WorkClientFollowTags::class,
            WorkClientFollow::class,
            'follow_id',
            'id',
            'client_id',
            'id'
        );
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchExternalUseridAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('external_userid', $value);
        } else {
            $query->where('external_userid', $value);
        }
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchCorpIdAttr($query, $value)
    {
        $query->where('corp_id', $value);
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } else {
            $query->where('uid', $value);
        }
    }
}
