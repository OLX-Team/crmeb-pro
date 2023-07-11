<?php


namespace app\model\work;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * Class WorkMomentSendResult
 * @package app\model\work
 */
class WorkMomentSendResult extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_moment_send_result';

    /**
     * @var string
     */
    protected $pk = 'id';

    /**
     * @var bool
     */
    protected $autoWriteTimestamp = false;

    /**
     * @return \think\model\relation\HasOne
     */
    public function member()
    {
        return $this->hasOne(WorkMember::class, 'userid', 'user_id')->field(['userid', 'name'])->bind(['name' => 'name']);
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchMomentIdAttr($query, $value)
    {
        $query->where('moment_id', $value);
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchUserIdAttr($query, $value)
    {
        if (!empty($value)) {
            if (is_array($value)) {
                $query->whereIn('user_id', $value);
            } else {
                $query->where('user_id', $value);
            }
        }
    }

    public function searchStatusAttr($query, $value)
    {
        if ('' !== $value) {
            $query->where('status', $value);
        }
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setExternalUseridAttr($value)
    {
        return is_array($value) ? json_encode($value, true) : $value;
    }


    /**
     * @param $value
     * @return array|mixed
     */
    public function getExternalUseridAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }
}
