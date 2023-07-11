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


use app\dao\work\WorkWelcomeDao;
use app\services\BaseServices;
use crmeb\traits\service\ContactWayQrCode;
use crmeb\traits\ServicesTrait;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;

/**
 * 企业微信欢迎语
 * Class WorkWelcomeServices
 * @package app\services\\work
 */
class WorkWelcomeServices extends BaseServices
{

    use ServicesTrait, ContactWayQrCode;

    /**
     * WorkWelcomeServices constructor.
     * @param WorkWelcomeDao $dao
     */
    public function __construct(WorkWelcomeDao $dao)
    {
        $this->dao = $dao;
    }


    /**
     * 获取列表
     * @param array $where
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getDataList($where, ['*'], $page, $limit, ['sort', 'create_time'], [
            'userList' => function ($query) {
                $query->with([
                    'member' => function ($query) {
                        $query->field(['userid', 'name']);
                    }
                ]);
            }
        ]);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取欢迎语
     * @param int $id
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getWelcomeInfo(int $id)
    {
        $welcomeInfo = $this->dao->get($id, ['*'], [
            'userList' => function ($query) {
                $query->with([
                    'member' => function ($query) {
                        $query->field(['userid', 'name']);
                    }
                ]);
            }
        ]);
        if (!$welcomeInfo) {
            throw new ValidateException('没有查到欢迎语');
        }

        return $welcomeInfo->toArray();
    }

    /**
     * 保存欢迎语
     * @param array $data
     * @param int $id
     * @return bool
     */
    public function saveWelcome(array $data, int $id = 0)
    {
        $userids = $data['userids'];
        unset($data['userids']);

        $this->checkWelcome(['text' => ['content' => $data['content']], 'attachments' => $data['attachments']], 0);

        /** @var WorkWelcomeRelationServices $relationServices */
        $relationServices = app()->make(WorkWelcomeRelationServices::class);

        $this->transaction(function () use ($data, $id, $relationServices, $userids) {
            if ($id) {
                $this->dao->update($id, $data);
            } else {
                $res = $this->dao->save($data);
                $id = $res->id;
            }
            $userData = [];
            foreach ($userids as $userid) {
                $userData[] = [
                    'welcome_id' => $id,
                    'userid' => $userid
                ];
            }
            $relationServices->delete(['welcome_id' => $id]);
            if ($userData) {
                $relationServices->saveAll($userData);
            }
        });

        return true;
    }

    /**
     * 获取欢迎语
     * @param string $userId
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getWorkWelcome(string $userId)
    {

        if ($userId) {
            /** @var WorkWelcomeRelationServices $service */
            $service = app()->make(WorkWelcomeRelationServices::class);
            $welcomeIds = $service->getColumn(['userid' => $userId], 'welcome_id');
            if ($welcomeIds) {
                $welcomeList = $this->dao->getDataList(['id' => $welcomeIds], ['*'], 1, 1, ['sort' => 'desc', 'create_time' => 'desc']);
                $welcomeWords = $welcomeList[0] ?? [];
            }
        }

        if (empty($welcomeWords)) {
            $welcomeList = $this->dao->getDataList(['type' => 0], ['*'], 1, 1, ['sort' => 'desc', 'create_time' => 'desc']);
            $welcomeWords = $welcomeList[0] ?? [];
        }

        return [
            'text' => [
                'content' => $welcomeWords['content'] ?? '',
            ],
            'attachments' => $welcomeWords['attachments'] ?? [],
        ];
    }

    /**
     * 删除欢迎语
     * @param int $id
     * @return bool
     */
    public function deleteWelcome(int $id)
    {
        /** @var WorkWelcomeRelationServices $relationServices */
        $relationServices = app()->make(WorkWelcomeRelationServices::class);

        $this->transaction(function () use ($id, $relationServices) {
            $relationServices->delete(['welcome_id' => $id]);
            $this->dao->delete($id);
        });
        return true;
    }
}
