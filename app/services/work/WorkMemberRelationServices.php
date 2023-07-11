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


use app\dao\work\WorkMemberRelationDao;
use app\services\BaseServices;
use crmeb\traits\ServicesTrait;

/**
 * 企业微信成员关联表
 * Class WorkMemberRelationServices
 * @package app\services\\work
 * @method getMemberRelationList(array $where, array $field = ['*'])
 */
class WorkMemberRelationServices extends BaseServices
{

    use ServicesTrait;

    /**
     * WorkMemberRelationServices constructor.
     * @param WorkMemberRelationDao $dao
     */
    public function __construct(WorkMemberRelationDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 添加成员部门
     * @param int $memberId
     * @param string $isLeaderInDept
     * @param string $department
     * @return mixed
     */
    public function saveMemberDepartment(int $memberId, string $isLeaderInDept, string $department)
    {
        $res = true;
        if ($isLeaderInDept && $department) {
            $department = explode(',', $department);
            $isLeaderInDept = explode(',', $isLeaderInDept);
            $departmentData = [];
            for ($i = 0; $i < count($department); $i++) {
                $departmentData[] = [
                    'department' => $department[$i],
                    'is_leader_in_dept' => $isLeaderInDept[$i],
                    'member_id' => $memberId,
                    'create_time' => time()
                ];
            }
            $this->dao->delete(['member_id' => $memberId]);
            $res = $this->dao->saveAll($departmentData);
        }
        return $res;
    }
}
