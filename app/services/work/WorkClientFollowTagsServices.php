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


use app\dao\work\WorkClientFollowTagsDao;
use app\services\BaseServices;
use crmeb\traits\ServicesTrait;

/**
 * 企业微信客户跟踪标签
 * Class WorkClientFollowTagsServices
 * @package app\services\work
 */
class WorkClientFollowTagsServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkClientFollowTagsServices constructor.
     * @param WorkClientFollowTagsDao $dao
     */
    public function __construct(WorkClientFollowTagsDao $dao)
    {
        $this->dao = $dao;
    }
}
