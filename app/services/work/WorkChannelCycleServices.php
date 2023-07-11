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

namespace app\services\work;


use app\dao\work\WorkChannelCycleDao;
use app\services\BaseServices;
use crmeb\traits\ServicesTrait;

/**
 * 渠道码周期规则
 * Class WorkChannelCycleServices
 * @package app\services\work
 */
class WorkChannelCycleServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkChannelCycleServices constructor.
     * @param WorkChannelCycleDao $dao
     */
    public function __construct(WorkChannelCycleDao $dao)
    {
        $this->dao = $dao;
    }
}
