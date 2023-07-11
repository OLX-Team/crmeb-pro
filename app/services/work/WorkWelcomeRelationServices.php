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


use app\dao\work\WorkWelcomeRelationDao;
use app\services\BaseServices;
use crmeb\traits\ServicesTrait;

/**
 * Class WorkWelcomeRelationServices
 * @package app\services\work
 */
class WorkWelcomeRelationServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkWelcomeRelationServices constructor.
     * @param WorkWelcomeRelationDao $dao
     */
    public function __construct(WorkWelcomeRelationDao $dao)
    {
        $this->dao = $dao;
    }
}
