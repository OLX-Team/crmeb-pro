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


use app\dao\work\WorkLabelDao;
use app\services\BaseServices;

/**
 * 企业微信标签
 * Class WorkLabelServices
 * @package app\services\\work
 */
class WorkLabelServices extends BaseServices
{

    /**
     * WorkLabelServices constructor.
     * @param WorkLabelDao $dao
     */
    public function __construct(WorkLabelDao $dao)
    {
        $this->dao = $dao;
    }
}
