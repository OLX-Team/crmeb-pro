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
use think\model\relation\HasOne;

/**
 * 企业微信群群成员
 * Class WorkGroupChatMember
 * @package app\model\work
 */
class WorkGroupChatMember extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_group_chat_member';

    /**
     * @var string
     */
    protected $autoWriteTimestamp = 'int';

    /**
     * @return HasOne
     */
    public function member()
    {
        return $this->hasOne(WorkMember::class, 'userid', 'userid');
    }

    /**
     * @return HasOne
     */
    public function client()
    {
        return $this->hasOne(WorkClient::class, 'external_userid', 'userid');
    }

    /**
     * @param $value
     * @return false|string
     */
    public function getJoinTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 名称模糊搜索
     * @param $query
     * @param $value
     */
    public function searchNameLikeAttr($query, $value)
    {
        if ('' !== $value) {
            $query->where(function ($query) use ($value) {
                $query->whereIn('userid', function ($query) use ($value) {
                    $query->name('work_client')->whereLike('name', '%' . $value . '%')->field(['external_userid']);
                })->whereOr('userid', 'in', function ($query) use ($value) {
                    $query->name('work_member')->whereLike('name', '%' . $value . '%')->field(['userid']);
                });
            });
        }
    }

}
