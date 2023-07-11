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
use app\model\work\WorkGroupChat;
use crmeb\traits\SearchDaoTrait;

/**
 * 企业微信群
 * Class WorkGroupChatDao
 * @package app\dao\work
 */
class WorkGroupChatDao extends BaseDao
{

    use SearchDaoTrait;

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return WorkGroupChat::class;
    }

    /**
     * @param array $where
     * @return \crmeb\basic\BaseModel
     */
    public function groupChat(array $where)
    {
        return $this->getModel()->when(!empty($where['chat_id']), function ($query) use ($where) {
            $query->whereIn('owner', function ($query) use ($where) {
                $query->name('work_group_msg_task')->when(!empty($where['status']), function ($query) use ($where) {
                    $query->where('status', $where['status']);
                })->whereIn('chat_id', $where['chat_id'])->field('userid');
            });
        })->when(!empty($where['owner']), function ($query) use ($where) {
            $query->whereIn('owner', $where['owner']);
        })->when(!empty($where['name']), function ($query) use ($where) {
            $query->whereLike('name', '%' . $where['name'] . '%');
        })->when(!empty($where['chat_id']), function ($query) use ($where) {
            if (is_array($where['chat_id'])) {
                $query->whereIn('chat_id', $where['chat_id']);
            } else {
                $query->where('chat_id', $where['chat_id']);
            }
        });
    }
}
