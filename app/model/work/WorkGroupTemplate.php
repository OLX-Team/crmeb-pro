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
 * 企业群发模板
 * Class WorkGroupTemplate
 * @package app\model\work
 */
class WorkGroupTemplate extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_group_template';

    /**
     * @var string
     */
    protected $key = 'id';

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
    public function msgIds()
    {
        return $this->hasMany(WorkGroupMsgRelation::class, 'template_id', 'id');
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setWhereExternalUseridsAttr($value)
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return array|mixed
     */
    public function getWhereExternalUseridsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setUseridsAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getUseridsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @param $value
     * @return false|string
     */
    public function getWhereLabelAttr($value)
    {
        return json_decode($value, true);
    }

    /**
     * @param $value
     * @return array|mixed
     */
    public function setWhereLabelAttr($value)
    {
        return json_encode($value);
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
    public function getWelcomeWordsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
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
    public function searchSendTypeAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('send_type', $value);
        } else {
            $query->where('send_type', $value);
        }
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchNameAttr($query, $value)
    {
        if ('' !== $value) {
            $query->whereLike('name', '%' . $value . '%');
        }
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchClientTypeAttr($query, $value)
    {
        if ('' !== $value) {
            $query->where('client_type', $value);
        }
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchUpdateTimeAttr($query, $value)
    {
        $this->searchTimeAttr($query, $value, ['timeKey' => 'update_time']);
    }


}
