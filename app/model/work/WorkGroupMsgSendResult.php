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
 *
 * Class WorkGroupMsgSendResult
 * @package app\model\work
 */
class WorkGroupMsgSendResult extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_group_msg_send_result';

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
    public function client()
    {
        return $this->hasOne(WorkClient::class, 'external_userid', 'external_userid')
            ->field(['external_userid', 'name'])
            ->bind(['name' => 'name']);
    }

    public function chat()
    {
        return $this->hasOne(WorkGroupChat::class,'chat_id','chat_id');
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchMsgIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('msg_id', $value);
        } else {
            $query->where('msg_id', $value);
        }
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        if ('' !== $value) {
            if (is_array($value)) {
                $query->whereIn('status', $value);
            } else {
                $query->where('status', $value);
            }
        }
    }

    /**
     * @param $value
     * @return false|string
     */
    public function getSendTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }
}
