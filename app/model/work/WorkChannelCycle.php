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
 * 渠道码周期规则
 * Class WorkChannelCycle
 * @package app\model\work
 */
class WorkChannelCycle extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_channel_cycle';

    /**
     * @param $value
     * @return mixed
     */
    public function getWokrTimeAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @param $value
     * @return false|string
     */
    public function serWokrTimeAttr($value)
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setUseridsAttr($value)
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return array|mixed
     */
    public function getUseridsAttr($value)
    {
        return $value ? json_decode($value, true) : [];
    }

}
