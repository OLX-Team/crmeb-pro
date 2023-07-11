<?php


namespace app\model\work;


use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 朋友圈
 * Class WorkMoment
 * @package app\model\work
 */
class WorkMoment extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_moment';

    /**
     * @var string
     */
    protected $pk = 'id';

    /**
     * @var string
     */
    protected $autoWriteTimestamp = 'int';

    /**
     * @var string
     */
    protected $timeKey = 'create_time';

    /**
     * @return \think\model\relation\HasMany
     */
    public function sendResult()
    {
        return $this->hasMany(WorkMomentSendResult::class, 'moment_id', 'moment_id');
    }

    /**
     * @param $value
     * @return array|mixed
     */
    public function getWelcomeWordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setWelcomeWordsAttr($value)
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return array|mixed
     */
    public function getClientTagListAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @param $value
     * @return array|mixed
     */
    public function getUserIdsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setClientTagListAttr($value)
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setUserIdsAttr($value)
    {
        return json_encode($value);
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchSendTimeAttr($query, $value)
    {
        $query->where('send_time', $value);
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchJobidNullAttr($query, $value)
    {
        $query->whereNull('jobid');
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchNameLikeAttr($query, $value)
    {
        if ('' !== $value) {
            $query->whereLike('name', '%' . $value . '%');
        }
    }
}
