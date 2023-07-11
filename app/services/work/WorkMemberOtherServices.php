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


use app\dao\work\WorkMemberOtherDao;
use app\services\BaseServices;
use crmeb\traits\ServicesTrait;

/**
 * Class WorkMemberOtherServices
 * @package app\services\\work
 */
class WorkMemberOtherServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkMemberOtherServices constructor.
     * @param WorkMemberOtherDao $dao
     */
    public function __construct(WorkMemberOtherDao $dao)
    {
        $this->dao = $dao;
    }
}
