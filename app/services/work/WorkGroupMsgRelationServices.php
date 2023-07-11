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


use app\dao\work\WorkGroupMsgRelationDao;
use app\services\BaseServices;
use crmeb\traits\ServicesTrait;

/**
 * 企业微信群发关联表
 * Class WorkGroupMsgRelationServices
 * @package app\services\work
 */
class WorkGroupMsgRelationServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkGroupMsgRelationServices constructor.
     * @param WorkGroupMsgRelationDao $dao
     */
    public function __construct(WorkGroupMsgRelationDao $dao)
    {
        $this->dao = $dao;
    }
}
