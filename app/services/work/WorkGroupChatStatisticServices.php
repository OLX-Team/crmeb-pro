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


use app\dao\work\WorkGroupChatStatisticDao;
use app\services\BaseServices;

/**
 * 群聊统计
 * Class WorkGroupChatStatisticServices
 * @package app\services\work
 */
class WorkGroupChatStatisticServices extends BaseServices
{

    /**
     * WorkGroupChatServicesServices constructor.
     * @param WorkGroupChatStatisticDao $dao
     */
    public function __construct(WorkGroupChatStatisticDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 保存或者修改
     * @param int $chatId
     * @param bool $todaySum
     * @param bool $todayReturnSum
     * @param int $chatSum
     * @param int $chatReturnSum
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveOrUpdate(int $chatId, bool $todaySum, bool $todayReturnSum, int $chatSum, int $chatReturnSum)
    {
        $info = $this->dao->getToDayInfo($chatId);
        if ($info) {
            if ($todaySum) {
                $info->today_sum++;
            }
            if ($todayReturnSum) {
                $info->today_return_sum++;
            }
            $info->chat_sum = $chatSum;
            $info->chat_return_sum = $chatReturnSum;
            $info->save();
        } else {
            $this->dao->save([
                'group_id' => $chatId,
                'today_sum' => $todaySum ? 1 : 0,
                'today_return_sum' => $todayReturnSum ? 1 : 0,
                'chat_sum' => $chatSum,
                'chat_return_sum' => $chatReturnSum,
            ]);
        }
    }

    /**
     * 群统计列表
     * @param int $id
     * @param string $time
     * @return array
     */
    public function getChatStatisticsList(int $id, string $time)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getDataList(['time' => $time, 'group_id' => $id], ['*'], $page, $limit);
        $count = $this->dao->count(['time' => $time, 'group_id' => $id]);
        return compact('list', 'count');
    }
}
