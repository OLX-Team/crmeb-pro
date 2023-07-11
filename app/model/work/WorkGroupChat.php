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
 * 企业微信群
 * Class WorkGroupChat
 * @package app\model\work
 */
class WorkGroupChat extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_group_chat';

    /**
     * @var string
     */
    protected $key = 'id';

    /**
     * @var string
     */
    protected $autoWriteTimestamp = 'int';

    /**
     * @return \think\model\relation\HasOne
     */
    public function ownerInfo()
    {
        return $this->hasOne(WorkMember::class, 'userid', 'owner');
    }

    /**
     * @return \think\model\relation\HasOne
     */
    public function sendResult()
    {
        return $this->hasOne(WorkGroupMsgSendResult::class, 'chat_id', 'chat_id');
    }

    /**
     * @return \think\model\relation\HasOne
     */
    public function chatMember()
    {
        return $this->hasOne(WorkGroupChatMember::class, 'group_id', 'id');
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getAdminListAttr($value)
    {
        return json_decode($value, true);
    }

    /**
     * @param $value
     * @return false|string
     */
    public function getGroupCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
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
     * @param $query
     * @param $value
     */
    public function searchUserIdsAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('owner', $value);
            } else {
                $query->where('owner', $value);
            }
        }
    }

    public function searchNameAttr($query, $value)
    {
        if ('' !== $value) {
            $query->whereLike('name', '%' . $value . '%');
        }
    }
}
