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
 * 企业微信客户跟踪标签
 * Class WorkClientFollowTags
 * @package app\model\work
 */
class WorkClientFollowTags extends BaseModel
{
    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'work_client_follow_tags';

    /**
     * @var bool
     */
    protected $autoWriteTimestamp = false;

    /**
     * @param $value
     * @return false|string
     */
    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
}
