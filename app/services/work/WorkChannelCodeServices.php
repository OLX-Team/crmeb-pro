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


use app\dao\work\WorkChannelCodeDao;
use app\services\BaseServices;
use app\services\user\label\UserLabelServices;
use crmeb\services\wechat\Work;
use crmeb\traits\service\ContactWayQrCode;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;

/**
 * 渠道码
 * Class WorkChannelCodeServices
 * @package app\services\work
 */
class WorkChannelCodeServices extends BaseServices
{

    use ServicesTrait, ContactWayQrCode;

    /**
     * WorkChannelCodeServices constructor.
     * @param WorkChannelCodeDao $dao
     */
    public function __construct(WorkChannelCodeDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取渠道二维码列表
     * @param array $where
     * @return array
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getDataList($where, ['*'], $page, $limit, 'create_time', ['category' => function ($query) {
            $query->field(['id', 'name']);
        }]);
        //获取标签
        $labelIds = [];
        foreach ($list as $item) {
            $labelIds = array_merge($labelIds, $item['label_id']);
        }
        $labelIds = array_merge(array_unique(array_filter($labelIds)));
        $labelList = [];
        if ($labelIds) {
            /** @var UserLabelServices $labelServices */
            $labelServices = app()->make(UserLabelServices::class);
            $labelList = $labelServices->getColumn([
                ['tag_id', 'in', $labelIds]
            ], 'label_name', 'tag_id');
        }
        foreach ($list as &$item) {
            foreach ($labelList as $key => $value) {
                if (in_array($key, $item['label_id'])) {
                    $item['label_name'][] = $value;
                }
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取渠道码详情
     * @param int $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChannelInfo(int $id)
    {
        $channelInfo = $this->dao->get($id, ['*'], ['cycle', 'useridLimit' => function ($query) {
            $query->with(['member']);
        }]);
        if (!$channelInfo) {
            throw new ValidateException('没有查询到渠道二维码信息');
        }

        /** @var WorkMemberServices $make */
        $make = app()->make(WorkMemberServices::class);

        //重组周期数据
        if (!empty($channelInfo['cycle'])) {
            $userIds = [];
            foreach ($channelInfo['cycle'] as $item) {
                $userIds = array_merge($userIds, $item['userids']);
            }
            if ($userIds) {
                $userIds = array_merge(array_unique(array_filter($userIds)));
                $userList = $make->getColumn([['userid', 'in', $userIds]], 'name', 'userid');
                foreach ($channelInfo['cycle'] as &$item) {
                    $userItem = [];
                    foreach ($userList as $key => $value) {
                        if (in_array($key, $item['userids'])) {
                            $userItem[] = ['name' => $value, 'userid' => $key];
                        }
                    }
                    $item['userItem'] = $userItem;
                }
            }
        }
        $channelInfo = $channelInfo->toArray();
        $channelInfo['presentUseUserIds'] = $this->getPresentUseUserIds((int)$channelInfo['type'], $channelInfo['cycle'] ?? [], $channelInfo['useridLimit'] ?? [], [
            'reserve_userid' => $channelInfo['reserve_userid'],
            'userids' => $channelInfo['userids'],
            'code_id' => $channelInfo['id'],
            'add_upper_limit' => $channelInfo['add_upper_limit'],
        ]);

        $channelInfo['presentUseUserList'] = $make->getColumn([['userid', 'in', $channelInfo['presentUseUserIds']]], 'name');
        $channelInfo['reserve_user_list'] = $make->getColumn([['userid', 'in', $channelInfo['reserve_userid']]], 'name');
        $channelInfo['user_list'] = $make->getColumn([['userid', 'in', $channelInfo['userids']]], 'name,userid,avatar');
        return $channelInfo;
    }

    /**
     * 保存和修改渠道二维码
     * @param array $data
     * @param int $id
     * @return mixed
     */
    public function saveChanne(array $data, int $id = 0)
    {
        $data['type'] = (int)$data['type'];
        $cycle = $data['cycle'];
        $useridLimit = $data['useridLimit'];
        unset($data['cycle'], $data['useridLimit']);
        //验证数据
        if (1 == $data['type']) {
            if (!count($cycle)) {
                throw new ValidateException('至少设置一个周期');
            }
            $this->checkEmployee($cycle);
        } else {
            if (!$data['userids']) {
                throw new ValidateException('必须选择一个成员');
            }
            if (count($data['userids']) > 100) {
                throw new ValidateException('选择的成员不能大于100个');
            }
        }
        //验证欢迎语
        $this->checkWelcome($data['welcome_words'], (int)$data['welcome_type']);

        /** @var WorkChannelCycleServices $cycleService */
        $cycleService = app()->make(WorkChannelCycleServices::class);
        /** @var WorkChannelLimitServices $limitService */
        $limitService = app()->make(WorkChannelLimitServices::class);
        //保存数据
        $id = $this->transaction(function () use ($cycle, $id, $data, $cycleService, $useridLimit, $limitService) {
            $configId = null;
            if ($id) {
                $this->dao->update($id, $data);
                $configId = $this->dao->value(['id' => $id], 'config_id');
            } else {
                $data['create_time'] = time();
                $res = $this->dao->save($data);
                if (!$res) {
                    throw new ValidateException('保存失败');
                }
                $id = $res->id;
            }
            $newCycle = $cycle;
            foreach ($cycle as &$item) {
                $item['channel_id'] = $id;
                $item['userids'] = json_encode($item['userids']);
                $item['wokr_time'] = json_encode($item['wokr_time']);
            }

            foreach ($useridLimit as &$item) {
                $item['channel_id'] = $id;
            }
            $cycleService->delete(['channel_id' => $id]);
            $cycleService->saveAll($cycle);
            $limitService->delete(['channel_id' => $id]);
            $limitService->saveAll($useridLimit);

            //提取在线成员生成二维码
            $userIds = $this->getPresentUseUserIds((int)$data['type'], $newCycle, $useridLimit, [
                'reserve_userid' => $data['reserve_userid'],
                'userids' => $data['userids'],
                'code_id' => $id,
                'add_upper_limit' => $data['add_upper_limit'],
            ]);
            $this->handleQrCode((int)$id, $userIds, !!$data['skip_verify'], $configId);

            return $id;
        });
        return $id;
    }

    /**
     * 获取当前时间内值班的成员userid
     * @param int $type
     * @param array $cycle
     * @param array $useridLimit
     * @param array $option
     * @return array|mixed
     */
    public function getPresentUseUserIds(int $type, array $cycle, array $useridLimit, array $option = [])
    {
        $addUpperLimit = (int)($option['add_upper_limit'] ?? 0);
        if (0 !== $type) {
            $newUserIdLimit = [];
            foreach ($useridLimit as $item) {
                $newUserIdLimit[$item['userid']] = $item;
            }
            $userIds = $this->cycleByUserid($cycle, $newUserIdLimit, $option['code_id'], $addUpperLimit);
        } else {
            //限制添加成员
            if (1 === $addUpperLimit) {
                $userIds = $this->addClientLimit($option['userids'] ?? [], $useridLimit, $option['code_id']);
            } else {
                $userIds = $option['userids'] ?? [];
            }
        }
        if (!$userIds) {
            $userIds = $option['reserve_userid'] ?? [];
        }
        return $userIds;
    }


    /**
     * 检测成员限制
     * @param array $cycle
     */
    public function checkEmployee(array $cycle)
    {
        $week = [];
        foreach ($cycle as $item) {
            foreach ($item['wokr_time'] as $num) {
                $week[$num] = array_merge(($week[$num] ?? []), $item['userids']);
            }
        }
        foreach ($week as $item) {
            if (count($item) > 100) {
                throw new ValidateException('周期内每个联系方式最多配置100个使用成员');
            }
        }
    }

    /**
     * 根据周期规则获取当前值班成员userid
     * @param array $cycle
     * @param array $useridLimit
     * @param int $codeId
     * @param int $addUpperLimit
     * @return array
     */
    public function cycleByUserid(array $cycle, array $useridLimit = [], int $codeId = 0, int $addUpperLimit = 0)
    {
        $userids = [];
        $weekNam = date('w');
        $nowHon = date('H:i');
        foreach ($cycle as $item) {
            //查询出当前周期内工作的值班人员
            if (in_array($weekNam, $item['wokr_time']) && $item['start_time'] <= $nowHon && $nowHon <= $item['end_time']) {
                $userids = array_merge($userids, $item['userids']);
            }
            //24小时值班人员
            if (in_array($weekNam, $item['wokr_time']) && $item['start_time'] == $item['end_time']) {
                $userids = array_merge($userids, $item['userids']);
            }
        }
        //每日值班人数大于100的需要使用备用人员
        $userids = array_merge(array_unique($userids));
        if ($userids && count($userids) > 100) {
            return [];
        }
        //不限制添加客户上限
        if (0 === $addUpperLimit) {
            return $userids;
        }
        return $this->addClientLimit($userids, $useridLimit, $codeId);
    }

    /**
     * 获取添加员工上限
     * @param array $userids
     * @param array $useridLimit
     * @param int $codeId
     * @return array
     */
    protected function addClientLimit(array $userids, array $useridLimit, int $codeId)
    {
        /** @var WorkClientFollowServices $followService */
        $followService = app()->make(WorkClientFollowServices::class);
        $userCount = $followService->userIdByCilentCount($userids, $codeId);
        if (!$userCount) {
            return $userids;
        }
        $useridLimitNew = [];
        foreach ($useridLimit as $value) {
            $useridLimitNew[$value['userid']] = $value;
        }
        $returnUserId = [];
        //限制员工每日添加客户个数
        foreach ($userids as $userid) {
            foreach ($userCount as $item) {
                if ($userid == $item['userid'] && $useridLimitNew[$userid]['max'] > $item['sum']) {
                    $returnUserId[] = $userid;
                }
            }
        }
        return $returnUserId;
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function deleteChannel(int $id)
    {
        $configId = $this->dao->value(['id' => $id], 'config_id');
        return $this->transaction(function () use ($id, $configId) {
            if ($configId) {
                Work::deleteQrCode($configId);
            }
            return $this->dao->destroy($id);
        });
    }

    /**
     * 执行生成渠道二维码
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cronHandle()
    {
        $channelList = $this->dao->getDataList(['status' => 1, 'type' => 1], ['*'], 0, 0, null, ['cycle', 'useridLimit']);

        foreach ($channelList as $item) {

            //提取在线成员生成二维码
            $userIds = $this->getPresentUseUserIds(
                (int)$item['type'],
                $item['cycle'] ?? [],
                $item['useridLimit'] ?? [],
                [
                    'reserve_userid' => $item['reserve_userid'],
                    'userids' => $item['userids'],
                    'code_id' => $item['id'],
                    'add_upper_limit' => $item['add_upper_limit'],
                ]
            );

            if ($userIds) {
                $this->handleQrCode((int)$item['id'], $userIds, !!$item['skip_verify'], $item['config_id']);
            }

        }
    }
}
