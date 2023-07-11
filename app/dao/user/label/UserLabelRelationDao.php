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
declare (strict_types=1);

namespace app\dao\user\label;

use app\dao\BaseDao;
use app\model\user\label\UserLabelRelation;

/**
 * 用户关联标签
 * Class UserLabelRelationDao
 * @package app\dao\user\label
 */
class UserLabelRelationDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserLabelRelation::class;
    }

    /**
     * 获取用户个标签列表按照用户id进行分组
     * @param array $uids
     * @param int $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLabelList(array $uids, int $store_id = 0)
    {
        return $this->search(['uid' => $uids])->where('store_id', $store_id)->with('label')->select()->toArray();
    }

}
