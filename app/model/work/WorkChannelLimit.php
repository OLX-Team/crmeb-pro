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
 * Class WorkChannelLimit
 * @package app\model\work
 */
class WorkChannelLimit extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_channel_limit';

    /**
     * @return \think\model\relation\HasOne
     */
    public function member()
    {
        return $this->hasOne(WorkMember::class, 'userid', 'userid')->bind([
            'name' => 'name',
            'avatar' => 'avatar'
        ]);
    }

}
