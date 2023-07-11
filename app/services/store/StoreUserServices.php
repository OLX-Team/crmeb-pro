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

namespace app\services\store;


use app\dao\store\StoreUserDao;
use app\services\BaseServices;
use app\services\user\level\SystemUserLevelServices;
use app\services\user\level\UserLevelServices;
use app\services\user\UserServices;
use think\exception\ValidateException;

/**
 * 门店用户
 * Class StoreUser
 * @package app\services\store
 */
class StoreUserServices extends BaseServices
{
    /**
     * 构造方法
     * StoreUser constructor.
     * @param StoreUserDao $dao
     */
    public function __construct(StoreUserDao $dao)
    {
        $this->dao = $dao;
    }


    public function index(array $where, int $store_id)
    {
        $where['store_id'] = $store_id;
        /** @var UserStoreUserServices $userStoreUserServices */
        $userStoreUserServices = app()->make(UserStoreUserServices::class);
        $fields = 'u.*';
        [$list, $count] = $userStoreUserServices->getWhereUserList($where, $fields);
        if ($list) {
            $uids = array_column($list, 'uid');
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userlabel = $userServices->getUserLablel($uids, $store_id);
            $levelName = app()->make(SystemUserLevelServices::class)->getUsersLevel(array_unique(array_column($list, 'level')));
            $userLevel = app()->make(UserLevelServices::class)->getUsersLevelInfo($uids);
            $spread_names = $userServices->getColumn([], 'nickname', 'uid');
            /** @var SystemStoreStaffServices $staffServices */
            $staffServices = app()->make(SystemStoreStaffServices::class);
            $staffNames = $staffServices->getColumn([['store_id', '=', $store_id], ['uid', 'in', array_column($list, 'uid')], ['is_del', '=', 0]], 'uid,staff_name', 'uid');
            foreach ($list as &$item) {
                $item['status'] = ($item['status'] == 1) ? '正常' : '禁止';
                $item['birthday'] = $item['birthday'] ? date('Y-m-d', (int)$item['birthday']) : '';
                $item['spread_uid_nickname'] = $item['spread_uid'] ? ($spread_names[$item['spread_uid']] ?? '') . '/' . $item['spread_uid'] : '无';
                $item['staff_name'] = $staffNames[$item['uid']]['staff_name'] ?? '';
                //用户类型
                if ($item['user_type'] == 'routine') {
                    $item['user_type'] = '小程序';
                } else if ($item['user_type'] == 'wechat') {
                    $item['user_type'] = '公众号';
                } else if ($item['user_type'] == 'h5') {
                    $item['user_type'] = 'H5';
                } else if ($item['user_type'] == 'pc') {
                    $item['user_type'] = 'PC';
                } else if ($item['user_type'] == 'app') {
                    $item['user_type'] = 'APP';
                } else $item['user_type'] = '其他';
                //等级名称
                $item['level'] = $levelName[$item['level']] ?? '无';
                //用户等级
                $item['vip_name'] = false;
                $levelinfo = $userLevel[$item['uid']] ?? null;
                if ($levelinfo) {
                    if ($levelinfo && ($levelinfo['is_forever'] || time() < $levelinfo['valid_time'])) {
                        $item['vip_name'] = $item['level'] != '无' ? $item['level'] : false;
                    }
                }
                $item['labels'] = $userlabel[$item['uid']] ?? '';
                $item['isMember'] = $item['is_money_level'] > 0 ? 1 : 0;
            }
        }

        return compact('count', 'list');
    }


    /**
     * 同步写入门店用户
     * @param int $uid
     * @param int $store_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setStoreUser(int $uid, int $store_id)
    {
        $storeUser = $this->dao->getOne(['uid' => $uid, 'store_id' => $store_id]);
        if (!$storeUser) {
            if (!$this->dao->save(['uid' => $uid, 'store_id' => $store_id, 'add_time' => time()])) {
                throw new ValidateException('新增门店用户失败');
            }
        }
        return true;
    }

}
