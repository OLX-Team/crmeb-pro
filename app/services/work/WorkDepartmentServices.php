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


use app\dao\work\WorkDepartmentDao;
use app\jobs\work\WorkMemberJob;
use app\services\BaseServices;
use crmeb\services\wechat\config\WorkConfig;
use crmeb\services\wechat\Work;
use crmeb\traits\ServicesTrait;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;

/**
 * 企业微信部门
 * Class WorkDepartmentServices
 * @package app\services\work
 */
class WorkDepartmentServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkDepartmentServices constructor.
     * @param WorkDepartmentDao $dao
     */
    public function __construct(WorkDepartmentDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取组织架构
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getDepartmentList()
    {
        /** @var WorkConfig $config */
        $config = app()->make(WorkConfig::class);
        $data = $this->dao->getDataList(['corp_id' => $config->get('corpId')], ['department_id', 'parentid', 'name', 'name_en']);
        $departmentId = array_column($data, 'department_id');
        if ($departmentId) {
            /** @var WorkMemberRelationServices $memberRelationService */
            $memberRelationService = app()->make(WorkMemberRelationServices::class);
            $memberList = $memberRelationService->getMemberRelationList([
                ['department', 'in', $departmentId]
            ], ['member_id', 'department']);
            foreach ($data as &$item) {
                $memberId = [];
                foreach ($memberList as $value) {
                    if ($value['department'] == $item['department_id']) {
                        $memberId[] = $value['member_id'];
                    }
                }
                $item['count'] = count($memberId);
            }
        }
        return get_tree_children($data, 'children', 'department_id', 'parentid');
    }

    /**
     * 同步企业微信部门和成员信息
     */
    public function authDepartment()
    {
        $res = Work::getDepartment();
        $department = $res['department'] ?? [];
        $data = [];
        $ids = [];
        /** @var WorkConfig $config */
        $config = app()->make(WorkConfig::class);
        $corpId = $config->get('corpId');
        if (!$corpId) {
            throw new ValidateException('请先配置企业微信ID');
        }
        foreach ($department as $item) {
            $item['srot'] = $item['order'];
            $item['department_leader'] = json_encode($item['department_leader']);
            $item['department_id'] = $item['id'];
            unset($item['order'], $item['id']);
            if ($this->dao->count(['department_id' => $item['department_id'], 'corp_id' => $corpId])) {
                $this->dao->update($item['department_id'], [
                    'name' => $item['name'],
                    'srot' => $item['srot'],
                    'department_leader' => $item['department_leader'],
                    'parentid' => $item['parentid']
                ]);
            } else {
                $item['create_time'] = time();
                $item['corp_id'] = $corpId;
                $data[] = $item;
            }
            $ids[] = $item['department_id'];
        }
        if ($data) {
            $this->dao->saveAll($data);
        }
        if ($ids) {
            foreach ($ids as $id) {
                WorkMemberJob::dispatchDo('run', [$id]);
            }
        }
    }

    /**
     * 获取部门+成员tree型数据
     * @param string $corpId
     * @param array $mobile
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getMailChildren(string $corpId, array $mobile)
    {
        $list = $this->dao->getDataList(['corp_id' => $corpId], ['department_id', 'parentid', 'name', 'name_en'], 0, 0, 'srot', ['member' => function ($query) use ($mobile, $corpId) {
            $query->whereIn('mobile', $mobile)
                ->where('corp_id', $corpId)
                ->field(['userid', 'id', 'mobile', 'avatar', 'thumb_avatar', 'name'])
                ->with('departmentRelation');
        }]);
        $userList = [];
        foreach ($list as $item) {
            if ($item['member'] && is_array($item['member'])) {
                $userList = array_merge($userList, $item['member']);
            }
        }
        foreach ($list as &$item) {
            $item['member'] = [];
            $user = [];
            foreach ($userList as $value) {
                $frameIds = $value['departmentRelation'] ? array_column($value['departmentRelation'], 'department') : [];
                unset($value['departmentRelation']);
                if (in_array($item['department_id'], $frameIds)) {
                    $user[] = $value;
                }
            }
            $item['member'] = $user;
            $item['member_count'] = count($user);
        }
        return get_tree_children($list, 'children', 'department_id', 'parentid');
    }

    /**
     * 创建部门
     * @param array $payload
     * @return \crmeb\basic\BaseModel|mixed|\think\Model
     */
    public function createDepartment(array $payload)
    {
        $where = ['corp_id' => $payload['ToUserName'], 'department_id' => $payload['Id']];
        if ($this->dao->count($where)) {
            return $this->updateDepartment($payload['ToUserName'], (int)$payload['Id'], $payload['Name']);
        } else {
            return $this->dao->save([
                'corp_id' => $payload['ToUserName'],
                'department_id' => $payload['Id'],
                'name' => $payload['Name'],
                'parentid' => $payload['ParentId'],
                'sort' => $payload['Order'],
                'create_time' => time()
            ]);
        }
    }

    /**
     * 更新部门
     * @param string $corpId
     * @param int $department_id
     * @param string $name
     * @return mixed
     */
    public function updateDepartment(string $corpId, int $departmentId, string $name)
    {
        return $this->dao->update(['corp_id' => $corpId, 'department_id' => $departmentId], ['name' => $name]);
    }

    /**
     * 删除部门
     * @param string $corpId
     * @param int $departmentId
     * @return mixed
     */
    public function deleteDepartment(string $corpId, int $departmentId)
    {
        return $this->dao->delete(['corp_id' => $corpId, 'department_id' => $departmentId]);
    }
}
