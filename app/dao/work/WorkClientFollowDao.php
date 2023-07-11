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
use app\model\work\WorkClientFollow;
use crmeb\traits\SearchDaoTrait;

/**
 * 企业微信客户跟踪
 * Class WorkClientFollowDao
 * @package app\dao\wechat\work
 */
class WorkClientFollowDao extends BaseDao
{

    use SearchDaoTrait;

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WorkClientFollow::class;
    }

    /**
     * 搜索
     * @param array $where
     * @param bool $authWhere
     * @return \crmeb\basic\BaseModel
     */
    public function searchWhere(array $where, bool $authWhere = true)
    {
        return $this->getModel()->when(!empty($where['user_name']), function ($query) use ($where) {
            $query->whereIn('client_id', function ($query) use ($where) {
                $query->name('work_client')->where('name', 'LIKE', '%' . $where['user_name'] . '%')->field(['id']);
            });
        })->when(!empty($where['state']), function ($query) use ($where) {
            $query->where('state', $where['state']);
        })->when(!empty($where['userid']), function ($query) use ($where) {
            if (is_array($where['userid'])) {
                $query->whereIn('userid', $where['userid']);
            } else {
                $query->where('userid', $where['userid']);
            }
        });
    }

    /**
     * 根据员工userid获取今日添加客户总数
     * @param array $userId
     * @param int $codeId
     * @return mixed
     */
    public function userIdByCilentCount(array $userId, int $codeId)
    {
        return $this->getModel()->whereIn('userid', $userId)->group('userid')->field(['userid', 'count(*) as sum'])->select()->toArray();
    }

    /**
     * 获取二维码添加客户数量
     * @param array $channeId
     * @return mixed
     */
    public function channelIdByCilentCount(array $channeId)
    {
        return $this->getModel()->whereDay('create_time')->whereIn('state', $channeId)->group('state')->field(['state', 'count(*) as sum'])->select()->toArray();
    }
}
