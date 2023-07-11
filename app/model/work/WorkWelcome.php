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
 * Class WorkWelcome
 * @package app\model\work
 */
class WorkWelcome extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_welcome';

    /**
     * @var string
     */
    protected $key = 'id';

    /**
     * @var string
     */
    protected $autoWriteTimestamp = 'int';

    /**
     * 成员
     * @return \think\model\relation\HasMany
     */
    public function userList()
    {
        return $this->hasMany(WorkWelcomeRelation::class, 'welcome_id', 'id');
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setAttachmentsAttr($value)
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getAttachmentsAttr($value)
    {
        return json_decode($value, true);
    }


}
