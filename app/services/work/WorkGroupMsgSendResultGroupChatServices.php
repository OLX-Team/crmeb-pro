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


use app\dao\work\WorkGroupMsgSendResultGroupChatDao;
use app\services\BaseServices;

/**
 * Class WorkGroupMsgSendResultGroupChatServices
 * @package app\services\work
 */
class WorkGroupMsgSendResultGroupChatServices extends BaseServices
{

    /**
     * WorkGroupMsgSendResultGroupChatServices constructor.
     * @param WorkGroupMsgSendResultGroupChatDao $dao
     */
    public function __construct(WorkGroupMsgSendResultGroupChatDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();

        $list = $this->dao->groupChat($where)->page($page, $limit)->select()->toArray();
        $count = $this->dao->groupChat($where)->count();
        return compact('list', 'count');
    }
}
