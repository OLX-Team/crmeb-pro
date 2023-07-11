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

namespace app\services\store;

use app\dao\store\UserStoreUserDao;
use app\services\BaseServices;

/**
 * Class UserStoreUserServices
 * @package app\services\store
 */
class UserStoreUserServices extends BaseServices
{

    /**
     * UserStoreUserServices constructor.
     * @param UserStoreUserDao $dao
     */
    public function __construct(UserStoreUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 自定义简单查询总数
     * @param array $where
     * @return int
     */
    public function getCount(array $where): int
    {
        return $this->dao->getCount($where);
    }

    /**
     * 复杂条件搜索列表
     * @param array $where
     * @param string $field
     * @return array
     */
    public function getWhereUserList(array $where, string $field): array
    {
        [$page, $limit] = $this->getPageValue();
        $order_string = '';
        $order_arr = ['asc', 'desc'];
        if (isset($where['now_money']) && in_array($where['now_money'], $order_arr)) {
            $order_string = 'now_money ' . $where['now_money'];
        }
        $list = $this->dao->getListByModel($where, $field, $order_string, $page, $limit);
        $count = $this->dao->getCountByWhere($where);
        return [$list, $count];
    }

    /**
     * 门店搜索用户
     * @param array $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function storeSearch(array $data)
    {
        $where = [];
        if ($data['field_key']) {
            $where['u.' . $data['field_key']] = $data['keyword'];
        } else {
            $where['u.uid|u.phone'] = $data['keyword'];
        }
        $fields = 'u.*,w.country,w.province,w.city,w.sex,w.unionid,w.openid,w.user_type as w_user_type,w.groupid,w.tagid_list,w.subscribe,w.subscribe_time';
        $list = $this->dao->getList($where, $fields);
        $count = $this->dao->getCount($where);
        return compact('list', 'count');
    }
}
