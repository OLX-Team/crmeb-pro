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
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * 企业微信客户跟踪
 * Class WorkClientFollow
 * @package app\model\work
 */
class WorkClientFollow extends BaseModel
{
    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_client_follow';

    /**
     * @var string
     */
    protected $key = 'id';

    /**
     * @var string
     */
    protected $autoWriteTimestamp = 'int';

    /**
     * @return HasOne
     */
    public function client()
    {
        return $this->hasOne(WorkClient::class, 'id', 'client_id');
    }

    /**
     * @return HasMany
     */
    public function tags()
    {
        return $this->hasMany(WorkClientFollowTags::class, 'follow_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function member()
    {
        return $this->hasOne(WorkMember::class, 'userid', 'userid');
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchClientIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('client_id', $value);
        } else {
            $query->where('client_id', $value);
        }
    }
}
