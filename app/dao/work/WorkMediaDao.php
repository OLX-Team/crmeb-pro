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

namespace app\dao\work;


use app\dao\BaseDao;
use app\model\work\WorkMedia;

/**
 * Class WorkMediaDao
 * @package app\dao\work
 */
class WorkMediaDao extends BaseDao
{

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WorkMedia::class;
    }

    /**
     * 删除时效的media_id
     * @return \crmeb\basic\BaseModel
     */
    public function deleteValidFile()
    {
        return $this->getModel()
            ->where('temporary', 1)
            ->where('valid_time', '<>', 0)
            ->where('valid_time', '<', time())
            ->update(['media_id' => '', 'valid_time' => 0]);
    }
}
