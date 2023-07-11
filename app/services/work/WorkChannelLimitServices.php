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


use app\dao\work\WorkChannelLimitDao;
use app\services\BaseServices;
use crmeb\traits\ServicesTrait;

/**
 * Class WorkChannelLimitServices
 * @package app\services\\work
 */
class WorkChannelLimitServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkChannelLimitServices constructor.
     * @param WorkChannelLimitDao $dao
     */
    public function __construct(WorkChannelLimitDao $dao)
    {
        $this->dao = $dao;
    }
}
