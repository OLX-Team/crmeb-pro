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
//declare (strict_types=1);
namespace app\services\user;

use app\jobs\user\UserFriendsJob;
use app\jobs\user\UserJob;
use app\jobs\user\UserSpreadJob;
use app\jobs\user\UserSvipJob;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\agent\AgentLevelServices;
use app\services\BaseServices;
use app\dao\user\UserDao;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\diy\DiyServices;
use app\services\message\service\StoreServiceRecordServices;
use app\services\message\service\StoreServiceServices;
use app\services\order\OtherOrderServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderTakeServices;
use app\services\other\QrcodeServices;
use app\services\product\product\StoreProductLogServices;
use app\services\other\queue\QueueServices;
use app\services\product\product\StoreProductRelationServices;
use app\services\message\SystemMessageServices;
use app\services\user\group\UserGroupServices;
use app\services\user\label\UserLabelServices;
use app\services\user\label\UserLabelRelationServices;
use app\services\user\level\SystemUserLevelServices;
use app\services\user\level\UserLevelServices;
use app\services\wechat\WechatUserServices;
use app\services\work\WorkClientServices;
use app\services\work\WorkMemberServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use crmeb\services\FormBuilder as Form;
use crmeb\services\FormBuilder;
use crmeb\services\SystemConfigService;
use crmeb\services\wechat\OfficialAccount;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Route as Url;
use think\Model;

/**
 *
 * Class UserServices
 * @package app\services\user
 * @method array getUserInfoArray(array $where, string $field, string $key) 根据条件查询对应的用户信息以数组形式返回
 * @method update($id, array $data, ?string $key = null) 修改数据
 * @method Model get($id, ?array $field = [], ?array $with = []) 获取一条数据
 * @method count(array $where) 获取指定条件下的数量
 * @method value(array $where, string $field) 获取指定的键值
 * @method bcInc($key, string $incField, string $inc, string $keyField = null, int $acc = 2) 高精度加法
 * @method bcDec($key, string $decField, string $dec, string $keyField = null, int $acc = 2, bool $dec_return_false = true) 高精度减法
 * @method getTrendData($time, $type, $timeType)
 * @method incPayCount(int $uid) 用户支付成功个数增加
 * @method totalUserCount($time) 特定时间用户数量
 * @method destroy($id, ?string $key = null) 软删除
 * @method getList(array $where, string $field = '*', int $page = 0, int $limit = 0)
 * @method getAgentRankList(array $time, string $field = '*', int $page = 0, int $limit = 0)
 * @method getUserWithTrashedInfo(int $uid, $field = '*') 获取删除和没有删除的用户信息
 * @method getWithTrashedCount(array $where = [], array $time = []) 获取未删除用户总数
 */
class UserServices extends BaseServices
{
    /**
     * UserServices constructor.
     * @param UserDao $dao
     */
    public function __construct(UserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取用户信息
     * @param $id
     * @param $field
     */
    public function getUserInfo(int $uid, $field = '*')
    {
        if (is_string($field)) $field = explode(',', $field);
        return $this->dao->get($uid, $field);
    }

    /**
     * 是否存在
     * @param int $uid
     * @return bool
     */
    public function userExist(int $uid)
    {
        return $this->dao->exist($uid);
    }

    /**
     * 获取用户缓存信息
     * @param int $uid
     * @param string $field
     * @param int $expire
     * @return bool|mixed|null
     */
    public function getUserCacheInfo(int $uid, int $expire = 60)
    {
        return CacheService::redisHandler('apiUserInfo')->remember('user_info_' . $uid, function () use ($uid) {
            return $this->dao->get($uid);
        }, $expire);
    }

    /**
     * 获取用户列表
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserList(array $where, string $field = '*'): array
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->count($where);
        return compact('list', 'count');
    }

    /**
     * 列表条数
     * @param array $where
     * @return int
     */
    public function getCount(array $where, bool $is_list = false)
    {
        return $this->dao->getCount($where, $is_list);
    }

    /**
     * 保存用户信息
     * @param $user
     * @param int $spreadUid
     * @param string $userType
     * @return User|\think\Model
     */
    public function setUserInfo($user, int $spreadUid = 0, string $userType = 'wechat')
    {
        mt_srand();
        $ip = app()->request->ip();
        $data = [
            'account' => $user['account'] ?? 'wx' . rand(1, 9999) . time(),
            'pwd' => $user['pwd'] ?? md5('123456'),
            'nickname' => $user['nickname'] ?? '',
            'avatar' => !empty($user['headimgurl']) ? $user['headimgurl'] : sys_config('h5_avatar'),
            'phone' => $user['phone'] ?? '',
            'birthday' => $user['birthday'] ?? '',
            'add_time' => time(),
            'add_ip' => $ip,
            'last_time' => time(),
            'last_ip' => $ip,
            'user_type' => $userType
        ];
        if ($spreadUid && $this->dao->get((int)$spreadUid)) {
            $data['spread_uid'] = $spreadUid;
            $data['spread_time'] = time();
        }
        $res = $this->dao->save($data);
        if (!$res)
            throw new AdminException('保存用户信息失败');
        //用户注册成功事件
        $userInfo = $res->toArray();
        $userInfo['unionid'] = $user['unionid'] ?? '';
        event('user.register', [$userInfo, true, $spreadUid]);
        return $res;
    }

    /**
     * 某些条件用户佣金总和
     * @param array $where
     * @return mixed
     */
    public function getSumBrokerage(array $where)
    {
        return $this->dao->getWhereSumField($where, 'brokerage_price');
    }

    /**
     * 根据条件获取用户指定字段列表
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getColumn(array $where, string $field = '*', string $key = '')
    {
        return $this->dao->getColumn($where, $field, $key);
    }

    /**
     * 获取分销用户
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAgentUserList(array $where = [], string $field = '*', $is_page = true)
    {
        $where_data['status'] = 1;
        if (sys_config('store_brokerage_statu') != 2) $where_data['is_promoter'] = 1;
        $where_data['spread_open'] = 1;
        if (isset($where['nickname']) && $where['nickname'] !== '') {
            $where_data['like'] = $where['nickname'];
        }
        if (isset($where['data']) && $where['data']) {
            $where_data['time'] = $where['data'];
        }
        [$page, $limit] = $this->getPageValue($is_page);
        $list = $this->dao->getAgentUserList($where_data, $field, $page, $limit);
        $count = $this->dao->count($where_data);
        return compact('count', 'list');
    }

    /**
     * 获取分销员ids
     * @param array $where
     * @return array
     */
    public function getAgentUserIds(array $where)
    {
        $where['status'] = 1;
        if (sys_config('store_brokerage_statu') != 2) $where['is_promoter'] = 1;
        $where['spread_open'] = 1;
        if (isset($where['nickname']) && $where['nickname'] !== '') {
            $where['like'] = $where['nickname'];
        }
        if (isset($where['data']) && $where['data']) {
            $where['time'] = $where['data'];
        }
        return $this->dao->getAgentUserIds($where);
    }

    /**
     * 获取推广人列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSairList(array $where, string $field = '*')
    {
        $where_data = [];
        if (isset($where['uid'])) {
            if (isset($where['type'])) {
                $type = (int)$where['type'];
                $type = in_array($type, [1, 2]) ? $type : 0;
                $uids = $this->getUserSpredadUids((int)$where['uid'], $type);
                $where_data['uid'] = count($uids) > 0 ? $uids : 0;
            }
            if (isset($where['data']) && $where['data']) {
                $where_data['time'] = $where['data'];
            }
            if (isset($where['nickname']) && $where['nickname']) {
                $where_data['like'] = $where['nickname'];
            }
            $where_data['status'] = 1;
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getSairList($where_data, '*', $page, $limit);
        $count = $this->dao->count($where_data);
        return compact('list', 'count');
    }

    /**
     * 获取推广人统计
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSairCount(array $where)
    {
        $where_data = [];
        if (isset($where['uid'])) {
            if (isset($where['type'])) {
                $uids = $this->getColumn(['spread_uid' => $where['uid']], 'uid');
                switch ((int)$where['type']) {
                    case 1:
                        $where_data['uid'] = count($uids) > 0 ? $uids : 0;
                        break;
                    case 2:
                        if (count($uids))
                            $spread_uid_two = $this->dao->getColumn([['spread_uid', 'IN', $uids]], 'uid');
                        else
                            $spread_uid_two = [];
                        $where_data['uid'] = count($spread_uid_two) > 0 ? $spread_uid_two : 0;
                        break;
                    default:
                        if (count($uids)) {
                            if ($spread_uid_two = $this->dao->getColumn([['spread_uid', 'IN', $uids]], 'uid')) {
                                $uids = array_merge($uids, $spread_uid_two);
                                $uids = array_unique($uids);
                                $uids = array_merge($uids);
                            }
                        }
                        $where_data['uid'] = count($uids) > 0 ? $uids : 0;
                        break;
                }
            }
            if (isset($where['data']) && $where['data']) {
                $where_data['time'] = $where['data'];
            }
            if (isset($where['nickname']) && $where['nickname']) {
                $where_data['like'] = $where['nickname'];
            }
            $where_data['status'] = 1;
        }
        return $this->dao->count($where_data);
    }

    /**
     * 写入用户信息
     * @param array $data
     * @return bool
     */
    public function create(array $data)
    {
        if (!$this->dao->save($data))
            throw new AdminException('写入失败');
        return true;
    }

    /**
     * 设置用户登录类型
     * @param int $uid
     * @param string $type
     * @return bool
     * @throws Exception
     */
    public function setLoginType(int $uid, string $type = 'h5')
    {
        if (!$this->dao->update($uid, ['login_type' => $type]))
            throw new Exception('设置登录类型失败');
        return true;
    }

    /**
     * 设置用户分组
     * @param $uids
     * @param int $group_id
     */
    public function setUserGroup($uids, int $group_id)
    {
        return $this->dao->batchUpdate($uids, ['group_id' => $group_id], 'uid');
    }

    /**
     * 获取用户标签
     * @param $uid
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLablel(array $uids, int $store_id = 0)
    {
        /** @var UserLabelRelationServices $services */
        $services = app()->make(UserLabelRelationServices::class);
        $userlabels = $services->getUserLabelList($uids, $store_id);
        $data = [];
        foreach ($uids as $uid) {
            $labels = array_filter($userlabels, function ($item) use ($uid) {
                if ($item['uid'] == $uid) {
                    return true;
                }
            });
            $data[$uid] = implode(',', array_column($labels, 'label_name'));
        }
        return $data;
    }

    /**
     * 显示资源列表头部
     * @return array[]
     */
    public function typeHead()
    {
        //全部会员
        $all = $this->getCount([]);
        /** @var UserWechatuserServices $userWechatUser */
        $userWechatUser = app()->make(UserWechatuserServices::class);
        //小程序会员
        $routine = $userWechatUser->getCount(['w.user_type' => 'routine']);
        //公众号会员
        $wechat = $userWechatUser->getCount(['w.user_type' => 'wechat']);
        //H5会员
        $h5 = $userWechatUser->getCount(['w.openid' => '', 'u.user_type' => 'h5']);
        //pc会员
        $pc = $userWechatUser->getCount(['w.openid' => '', 'u.user_type' => 'pc']);
        //app会员
        $app = $userWechatUser->getCount(['w.user_type' => 'app']);
        return [
            ['user_type' => '', 'name' => '全部会员', 'count' => $all],
            ['user_type' => 'routine', 'name' => '小程序会员', 'count' => $routine],
            ['user_type' => 'wechat', 'name' => '公众号会员', 'count' => $wechat],
            ['user_type' => 'h5', 'name' => 'H5会员', 'count' => $h5],
            ['user_type' => 'pc', 'name' => 'PC会员', 'count' => $pc],
            ['user_type' => 'app', 'name' => 'APP会员', 'count' => $app],
        ];
    }

    /**
     * 会员列表
     * @param array $where
     * @return array
     */
    public function index(array $where)
    {
        /** @var UserWechatuserServices $userWechatUser */
        $userWechatUser = app()->make(UserWechatuserServices::class);
        $fields = 'u.*,w.country,w.province,w.city,w.sex,w.unionid,w.openid,w.user_type as w_user_type,w.groupid,w.tagid_list,w.subscribe,w.subscribe_time';
        [$list, $count] = $userWechatUser->getWhereUserList($where, $fields);
        if ($list) {
            $uids = array_column($list, 'uid');
            $userlabel = $this->getUserLablel($uids);
            $userGroup = app()->make(UserGroupServices::class)->getUsersGroupName(array_unique(array_column($list, 'group_id')));
            $userExtract = app()->make(UserExtractServices::class)->getUsersSumList($uids);
            $levelName = app()->make(SystemUserLevelServices::class)->getUsersLevel(array_unique(array_column($list, 'level')));
            $userLevel = app()->make(UserLevelServices::class)->getUsersLevelInfo($uids);
            $spread_names = $this->dao->getColumn([], 'nickname', 'uid');
            /** @var WorkClientServices $workClientService */
            $workClientService = app()->make(WorkClientServices::class);
            $clientData = $workClientService->getList(['uid' => $uids], ['id', 'uid', 'name', 'external_userid', 'corp_id', 'unionid'], false);
            $clientlist = $clientData['list'];
            foreach ($list as &$item) {
                if (empty($item['addres'])) {
                    if (!empty($item['country']) || !empty($item['province']) || !empty($item['city'])) {
                        $item['addres'] = $item['country'] . $item['province'] . $item['city'];
                    }
                }
                $item['status'] = ($item['status'] == 1) ? '正常' : '禁止';
                $item['birthday'] = $item['birthday'] ? date('Y-m-d', (int)$item['birthday']) : '';
                $item['extract_count_price'] = $userExtract[$item['uid']] ?? 0;//累计提现
                $item['spread_uid_nickname'] = $item['spread_uid'] ? ($spread_names[$item['spread_uid']] ?? '') . '/' . $item['spread_uid'] : '无';
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
                if ($item['sex'] == 1) {
                    $item['sex'] = '男';
                } else if ($item['sex'] == 2) {
                    $item['sex'] = '女';
                } else $item['sex'] = '保密';
                //等级名称
                $item['level'] = $levelName[$item['level']] ?? '无';
                //分组名称
                $item['group_id'] = $userGroup[$item['group_id']] ?? '无';
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
                $item['follow_list'] = [];
                if ($clientlist) {
                    foreach ($clientlist as $value) {
                        if (!empty($value['followOne']) && $value['uid'] == $item['uid']) {
                            $item['follow_list'][] = $value['followOne'];
                        }
                    }
                }
            }
        }
        return compact('count', 'list');
    }

    /**
     * 获取修改页面数据
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function edit(int $id)
    {
        $user = $this->getUserInfo($id);
        if (!$user)
            throw new AdminException('数据不存在');
        $f = array();
        $f[] = Form::input('uid', '用户编号', $user->getData('uid'))->disabled(true);
        $f[] = Form::input('real_name', '真实姓名', $user->getData('real_name'))->col(12);
        $f[] = Form::input('phone', '手机号码', $user->getData('phone'))->col(12);
        $f[] = Form::date('birthday', '生日', $user->getData('birthday') ? date('Y-m-d', $user->getData('birthday')) : '')->col(12);
        $f[] = Form::input('card_id', '身份证号', $user->getData('card_id'))->col(12);
        $f[] = Form::input('addres', '用户地址', $user->getData('addres'));
        $f[] = Form::textarea('mark', '用户备注', $user->getData('mark'));
        $f[] = Form::input('pwd', '登录密码')->type('password')->col(12)->placeholder('不改密码请留空');
        $f[] = Form::input('true_pwd', '确认密码')->type('password')->col(12)->placeholder('不改密码请留空');
        //查询高于当前会员的所有会员等级
//        $grade = app()->make(UserLevelServices::class)->getUerLevelInfoByUid($id, 'grade');
        $systemLevelList = app()->make(SystemUserLevelServices::class)->getWhereLevelList([], 'id,name');
        $setOptionLevel = function () use ($systemLevelList) {
            $menus = [];
            foreach ($systemLevelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['name']];
            }
            return $menus;
        };
        $f[] = Form::select('level', '用户等级', (int)$user->getData('level'))->col(12)->setOptions(FormBuilder::setOptions($setOptionLevel))->filterable(true);
        $systemGroupList = app()->make(UserGroupServices::class)->getGroupList();
        $setOptionGroup = function () use ($systemGroupList) {
            $menus = [];
            foreach ($systemGroupList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['group_name']];
            }
            return $menus;
        };
        $f[] = Form::select('group_id', '用户分组', $user->getData('group_id'))->col(12)->setOptions(FormBuilder::setOptions($setOptionGroup))->filterable(true);
        /** @var UserLabelServices $userlabelServices */
        $userlabelServices = app()->make(UserLabelServices::class);
        $systemLabelList = $userlabelServices->getLabelList(['type' => 1]);
        $labels = app()->make(UserLabelRelationServices::class)->getUserLabels($user['uid']);
        $setOptionLabel = function () use ($systemLabelList) {
            $menus = [];
            foreach ($systemLabelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['label_name']];
            }
            return $menus;
        };
        $f[] = Form::select('label_id', '用户标签', $labels)->setOptions(FormBuilder::setOptions($setOptionLabel))->filterable(true)->multiple(true);
        $f[] = Form::radio('spread_open', '推广资格', $user->getData('spread_open'))->info('禁用用户的推广资格后，在任何分销模式下该用户都无分销权限')->options([['value' => 1, 'label' => '启用'], ['value' => 0, 'label' => '禁用']]);
        //分销模式  人人分销
        $storeBrokerageStatus = sys_config('store_brokerage_statu', 1);
        if (in_array($storeBrokerageStatus, [1, 3])) {
            $f[] = Form::radio('is_promoter', '推广员权限', $user->getData('is_promoter'))->info('指定分销模式下，开启或关闭用户的推广权限')->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        }
        $f[] = Form::radio('status', '用户状态', $user->getData('status'))->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '锁定']]);
        return create_form('编辑', $f, Url::buildUrl('/user/user/' . $id), 'PUT');
    }

    /**
     * 添加用户表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function saveForm()
    {
        $f = array();
        $f[] = Form::input('real_name', '真实姓名', '')->col(12)->placeholder('请输入真实姓名');
        $f[] = Form::input('phone', '手机号码', '')->col(12)->placeholder('请输入手机号码')->required();
        $f[] = Form::date('birthday', '生日', '')->col(12)->placeholder('请选择生日');
        $f[] = Form::input('card_id', '身份证号', '')->col(12)->placeholder('请输入身份证号');
        $f[] = Form::input('addres', '用户地址', '')->placeholder('请输入用户地址');
        $f[] = Form::textarea('mark', '用户备注', '')->placeholder('请输入用户备注');
        $f[] = Form::input('pwd', '登录密码')->col(12)->type('password')->placeholder('请输入登录密码');
        $f[] = Form::input('true_pwd', '确认密码')->col(12)->type('password')->placeholder('请再次确认密码');
        /** @var SystemUserLevelServices $systemUserLevelServices */
        $systemUserLevelServices = app()->make(SystemUserLevelServices::class);
        $systemLevelList = $systemUserLevelServices->getWhereLevelList([], 'id,name');
        $setOptionLevel = function () use ($systemLevelList) {
            $menus = [];
            foreach ($systemLevelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['name']];
            }
            return $menus;
        };
        $f[] = Form::select('level', '用户等级', '')->col(12)->setOptions(FormBuilder::setOptions($setOptionLevel))->filterable(true);
        $systemGroupList = app()->make(UserGroupServices::class)->getGroupList();
        $setOptionGroup = function () use ($systemGroupList) {
            $menus = [];
            foreach ($systemGroupList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['group_name']];
            }
            return $menus;
        };
        $f[] = Form::select('group_id', '用户分组', '')->col(12)->setOptions(FormBuilder::setOptions($setOptionGroup))->filterable(true);
        /** @var UserLabelServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelServices::class);
        $systemLabelList = $userLabelServices->getLabelList(['type' => 1]);
        $setOptionLabel = function () use ($systemLabelList) {
            $menus = [];
            foreach ($systemLabelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['label_name']];
            }
            return $menus;
        };
        $f[] = Form::select('label_id', '用户标签', '')->setOptions(FormBuilder::setOptions($setOptionLabel))->filterable(true)->multiple(true);
        $f[] = Form::radio('spread_open', '推广资格', 1)->info('禁用用户的推广资格后，在任何分销模式下该用户都无分销权限')->options([['value' => 1, 'label' => '启用'], ['value' => 0, 'label' => '禁用']]);
        //分销模式  人人分销
        $storeBrokerageStatus = sys_config('store_brokerage_statu', 1);
        if (in_array($storeBrokerageStatus, [1, 3])) {
            $f[] = Form::radio('is_promoter', '推广员权限', 0)->info('指定分销模式下，开启或关闭用户的推广权限')->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '关闭']]);
        }
        $f[] = Form::radio('status', '用户状态', 1)->options([['value' => 1, 'label' => '开启'], ['value' => 0, 'label' => '锁定']]);
        return create_form('添加用户', $f, $this->url('/user/user'), 'POST');
    }

    /**
     * 修改提交处理
     * @param $id
     * @return mixed
     */
    public function updateInfo(int $id, array $data)
    {
        $user = $this->getUserInfo($id);
        if (!$user) {
            throw new AdminException('数据不存在!');
        }
        $res1 = false;
        $res2 = false;
        $edit = [];
        if ($data['money_status'] && $data['money']) {//余额增加或者减少
            /** @var UserMoneyServices $userMoneyServices */
            $userMoneyServices = app()->make(UserMoneyServices::class);
            if ($data['money_status'] == 1) {//增加
                $edit['now_money'] = bcadd($user['now_money'], $data['money'], 2);
                $res1 = $userMoneyServices->income('system_add', $user['uid'], $data['money'], $edit['now_money'], $data['adminId'] ?? 0);
            } else if ($data['money_status'] == 2) {//减少
                if ($user['now_money'] > $data['money']) {
                    $edit['now_money'] = bcsub($user['now_money'], $data['money'], 2);
                } else {
                    $edit['now_money'] = 0;
                    $data['money'] = $user['now_money'];
                }
                $res1 = $userMoneyServices->income('system_sub', $user['uid'], $data['money'], $edit['now_money'], $data['adminId'] ?? 0);
            }
        } else {
            $res1 = true;
        }
        if ($data['integration_status'] && $data['integration']) {//积分增加或者减少
            $integral_data = ['link_id' => $data['adminId'] ?? 0, 'number' => $data['integration'], 'balance' => $user['integral']];
            /** @var UserBillServices $userBill */
            $userBill = app()->make(UserBillServices::class);
            if ($data['integration_status'] == 1) {//增加
                $edit['integral'] = bcadd($user['integral'], $data['integration'], 0);
                $integral_data['title'] = '系统增加积分';
                $integral_data['mark'] = '系统增加了' . floatval($data['integration']) . '积分';
                $res2 = $userBill->incomeIntegral($user['uid'], 'system_add', $integral_data);
            } else if ($data['integration_status'] == 2) {//减少
                $edit['integral'] = bcsub($user['integral'], $data['integration'], 0);
                $integral_data['title'] = '系统减少积分';
                $integral_data['mark'] = '系统扣除了' . floatval($data['integration']) . '积分';
                $res2 = $userBill->expendIntegral($user['uid'], 'system_sub', $integral_data);
            }
        } else {
            $res2 = true;
        }
        //修改基本信息
        if (!isset($data['is_other']) || !$data['is_other']) {
            if ($data['phone']) {
                $otherUser = $this->getOne(['phone' => $data['phone']], 'uid,phone');
                if ($otherUser && $otherUser['uid'] != $id) {
                    throw new AdminException('该手机号码已被注册');
                }
            }
            /** @var UserLabelRelationServices $userLabel */
            $userLabel = app()->make(UserLabelRelationServices::class);
            if (is_string($data['label_id'])) {
                $data['label_id'] = [$data['label_id']];
            }
            $userLabel->setUserLable([$id], $data['label_id'], 0, true);
            if (isset($data['pwd']) && $data['pwd'] && $data['pwd'] != $user['pwd']) {
                $edit['pwd'] = $data['pwd'];
            }
            if (isset($data['spread_open'])) {
                $edit['spread_open'] = $data['spread_open'];
            }
            $edit['status'] = $data['status'];
            $edit['real_name'] = $data['real_name'];
            $edit['card_id'] = $data['card_id'];
            $edit['birthday'] = strtotime($data['birthday']);
            $edit['mark'] = $data['mark'];
            $edit['is_promoter'] = $data['is_promoter'];
            $edit['level'] = $data['level'];
            $edit['phone'] = $data['phone'];
            $edit['addres'] = $data['addres'];
            $edit['group_id'] = $data['group_id'];
            if ($data['spread_uid'] != -1) {
                $edit['spread_uid'] = $data['spread_uid'];
                $edit['spread_time'] = $data['spread_uid'] ? time() : 0;
            }
            $edit['sex'] = $data['sex'];
            $edit['provincials'] = $data['provincials'];
            $edit['province'] = $data['province'];
            $edit['city'] = $data['city'];
            $edit['area'] = $data['area'];
            $edit['street'] = $data['street'];
            if ($user['level'] != $data['level']) {
                /** @var UserLevelServices $userLevelService */
                $userLevelService = app()->make(UserLevelServices::class);
                $userLevelService->setUserLevel((int)$user['uid'], (int)$data['level']);
            }
            if ($data['spread_uid'] != $user['spread_uid']) {
                //记录推广绑定关系
                UserSpreadJob::dispatch([$id, (int)$data['spread_uid'], 0, (int)$data['adminId']]);
                //记录好友关系
                UserFriendsJob::dispatch([$id, (int)$data['spread_uid']]);
            }
            /** @var WechatUserServices $wechatUser */
            $wechatUser = app()->make(WechatUserServices::class);
            $wechatUser->update(['uid' => $id], ['sex' => $data['sex']]);
        }
        if ($edit) $res3 = $this->dao->update($id, $edit);
        else $res3 = true;
        if ($res1 && $res2 && $res3)
            return true;
        else throw new AdminException('修改失败');
    }

    /**
     * 编辑其他
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function editOther($id)
    {
        $user = $this->getUserInfo($id);
        if (!$user) {
            throw new AdminException('数据不存在!');
        }
        $f = array();
        $f[] = Form::radio('money_status', '修改余额', 1)->options([['value' => 1, 'label' => '增加'], ['value' => 2, 'label' => '减少']]);
        $f[] = Form::number('money', '余额', 0)->max(999999)->precision(2)->min(0)->style(['width' => '100px']);
        $f[] = Form::radio('integration_status', '修改积分', 1)->options([['value' => 1, 'label' => '增加'], ['value' => 2, 'label' => '减少']]);
        $f[] = Form::number('integration', '积分', 0)->max(999999)->min(0)->precision(0)->style(['width' => '100px']);
        return create_form('修改积分余额', $f, Url::buildUrl('/user/update_other/' . $id), 'PUT');
    }

    /**
     * 设置会员分组
     * @param $id
     * @return mixed
     */
    public function setGroup($uids, $all, $where)
    {
        $userGroup = app()->make(UserGroupServices::class)->getGroupList();
        if (count($uids) == 1) {
            $user = $this->getUserInfo($uids[0], ['group_id']);
            $setOptionUserGroup = function () use ($userGroup) {
                $menus = [];
                foreach ($userGroup as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['group_name']];
                }
                return $menus;
            };
            $field[] = Form::select('group_id', '用户分组', $user->getData('group_id'))->setOptions(FormBuilder::setOptions($setOptionUserGroup))->filterable(true);
        } else {
            $setOptionUserGroup = function () use ($userGroup) {
                $menus = [];
                foreach ($userGroup as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['group_name']];
                }
                return $menus;
            };
            $field[] = Form::select('group_id', '用户分组')->setOptions(FormBuilder::setOptions($setOptionUserGroup))->filterable(true);
        }
        $field[] = Form::hidden('uids', implode(',', $uids));
        $field[] = Form::hidden('all', $all);
        $field[] = Form::hidden('where', $where ? json_encode($where) : "");
        return create_form('设置用户分组', $field, Url::buildUrl('/user/save_set_group'), 'PUT');
    }

    /**
     * 保存会员分组
     * @param $id
     * @return mixed
     */
    public function saveSetGroup($uids, int $group_id, $redisKey, $queueId)
    {
        /** @var UserGroupServices $userGroup */
        $userGroup = app()->make(UserGroupServices::class);
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        if (!$userGroup->getGroup($group_id)) {
            throw new AdminException('该分组不存在');
        }
        if (!$this->setUserGroup($uids, $group_id)) {
            $queueService->addQueueFail($queueId['id'], $redisKey);
            throw new AdminException('设置分组失败或无改动');
        } else {
            $queueService->doSuccessSremRedis($uids, $redisKey, $queueId['type']);
        }
        return true;
    }

    /**
     * 设置用户标签
     * @param $uids
     * @param $all
     * @param $where
     * @param int $type
     * @param int $store_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setLabel($uids, $all, $where, int $type = 1, int $store_id = 0)
    {
        /** @var UserLabelServices $userlabelServices */
        $userlabelServices = app()->make(UserLabelServices::class);
        $userLabel = $userlabelServices->getLabelList(['type' => $type, 'store_id' => $store_id]);
        if (count($uids) == 1) {
            /** @var UserLabelRelationServices $userLabeLRelation */
            $userLabeLRelation = app()->make(UserLabelRelationServices::class);
            $lids = $userLabeLRelation->getUserLabels($uids[0], $store_id);
            $setOptionUserLabel = function () use ($userLabel) {
                $menus = [];
                foreach ($userLabel as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['label_name']];
                }
                return $menus;
            };
            $field[] = Form::select('label_id', '用户标签', $lids)->setOptions(FormBuilder::setOptions($setOptionUserLabel))->filterable(true)->multiple(true);
        } else {
            $setOptionUserLabel = function () use ($userLabel) {
                $menus = [];
                foreach ($userLabel as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['label_name']];
                }
                return $menus;
            };
            $field[] = Form::select('label_id', '用户标签')->setOptions(FormBuilder::setOptions($setOptionUserLabel))->filterable(true)->multiple(true);
        }
        $field[] = Form::hidden('uids', implode(',', $uids));
        $field[] = Form::hidden('all', $all);
        $field[] = Form::hidden('where', $where ? json_encode($where) : "");
        return create_form('设置用户标签', $field, Url::buildUrl('/user/save_set_label'), 'PUT');
    }

    /**
     * 保存用户标签
     * @return mixed
     */
    public function saveSetLabel($uids, $lable_id)
    {
        /** @var UserLabelServices $userlabel */
        $userlabel = app()->make(UserLabelServices::class);
        if (count($lable_id) != $userlabel->getCount([['id', 'in', $lable_id]])) {
            throw new AdminException('用户标签不存在或被删除');
        }
        /** @var UserLabelRelationServices $services */
        $services = app()->make(UserLabelRelationServices::class);
        if (!$services->setUserLable($uids, $lable_id)) {
            throw new AdminException('设置标签失败');
        }
        return true;
    }

    /**
     * 批量队列设置标签
     * @param $uids
     * @param $lable_id
     * @param $redisKey
     * @param $queueId
     * @return bool
     */
    public function saveBatchSetLabel($uids, $lable_id, $redisKey, $queueId)
    {
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        foreach ($lable_id as $id) {
            if (!app()->make(UserLabelServices::class)->getLable((int)$id)) {
                throw new AdminException('用户标签不存在或被删除');
            }
        }
        /** @var UserLabelRelationServices $services */
        $services = app()->make(UserLabelRelationServices::class);
        if (!$services->setUserLable($uids, $lable_id)) {
            $queueService->addQueueFail($queueId['id'], $redisKey);
            throw new AdminException('设置标签失败');
        } else {
            $queueService->doSuccessSremRedis($uids, $redisKey, $queueId['type']);
        }
        return true;
    }

    /**
     * 赠送会员等级
     * @param int $uid
     * @return mixed
     * */
    public function giveLevel($id)
    {
        if (!$this->userExist($id)) {
            throw new AdminException('用户不存在');
        }
        //查询高于当前会员的所有会员等级
        $grade = app()->make(UserLevelServices::class)->getUerLevelInfoByUid($id, 'grade');
        $systemLevelList = app()->make(SystemUserLevelServices::class)->getWhereLevelList(['grade', '>', $grade ?? 0], 'id,name');
        $setOptionlevel = function () use ($systemLevelList) {
            $menus = [];
            foreach ($systemLevelList as $menu) {
                $menus[] = ['value' => $menu['id'], 'label' => $menu['name']];
            }
            return $menus;
        };
        $field[] = Form::select('level_id', '用户等级')->setOptions(FormBuilder::setOptions($setOptionlevel))->filterable(true);
        return create_form('赠送等级', $field, Url::buildUrl('/user/save_give_level/' . $id), 'PUT');
    }

    /**
     * 执行赠送会员等级
     * @param int $uid
     * @return mixed
     * */
    public function saveGiveLevel(int $id, int $level_id)
    {
        if (!$this->userExist($id)) {
            throw new AdminException('用户不存在');
        }
        /** @var SystemUserLevelServices $systemLevelServices */
        $systemLevelServices = app()->make(SystemUserLevelServices::class);
        /** @var UserLevelServices $userLevelServices */
        $userLevelServices = app()->make(UserLevelServices::class);
        //查询当前选择的会员等级
        $systemLevel = $systemLevelServices->getLevel($level_id);
        if (!$systemLevel) throw new AdminException('您选择赠送的用户等级不存在！');
        //检查是否拥有此会员等级
        $level = $userLevelServices->getWhereLevel(['uid' => $id, 'level_id' => $level_id], 'valid_time,is_forever');
        if ($level && $level['status'] == 1 && $level['is_del'] == 0) {
            throw new AdminException('此用户已有该用户等级，无法再次赠送');
        }
        //保存会员信息
        if (!$userLevelServices->setUserLevel($id, $level_id, $systemLevel)) {
            throw new AdminException('赠送失败');
        }
        return true;
    }

    /**
     * 赠送付费会员时长
     * @param int $uid
     * @return mixed
     * */
    public function giveLevelTime($id)
    {
        if (!$this->userExist($id)) {
            throw new AdminException('用户不存在');
        }
        $field[] = Form::number('days', '增加时长(天)')->min(0)->max(999999)->precision(0);
        return create_form('赠送付费会员时长', $field, Url::buildUrl('/user/save_give_level_time/' . $id), 'PUT');
    }

    /**
     * 执行赠送付费会员时长
     * @param int $uid
     * @return mixed
     * */
    public function saveGiveLevelTime(int $id, int $days)
    {
        $userInfo = $this->getUserInfo($id);
        if (!$userInfo) {
            throw new AdminException('用户不存在');
        }
        if ($days <= 0) throw new AdminException('赠送天数不能小于1天');
        if ($userInfo->is_money_level == 0) {
            $userInfo->is_money_level = 3;
            $userInfo->overdue_time = $time = time() + ($days * 86400);
        } else {
            $userInfo->overdue_time = $time = $userInfo->overdue_time + ($days * 86400);
        }
        $userInfo->save();
        /** @var StoreOrderCreateServices $storeOrderCreateService */
        $storeOrderCreateService = app()->make(StoreOrderCreateServices::class);
        $orderInfo = [
            'uid' => $id,
            'order_id' => $storeOrderCreateService->getNewOrderId(),
            'type' => 4,
            'member_type' => 0,
            'pay_type' => 'admin',
            'paid' => 1,
            'pay_time' => time(),
            'is_free' => 1,
            'overdue_time' => $time,
            'vip_day' => $days,
            'add_time' => time()
        ];
        /** @var OtherOrderServices $otherOrder */
        $otherOrder = app()->make(OtherOrderServices::class);
        $otherOrder->save($orderInfo);
        return true;
    }

    /**
     * 清除会员等级
     * @paran int $uid
     * @paran boolean
     * */
    public function cleanUpLevel($uid)
    {
        if (!$this->userExist($uid))
            throw new AdminException('用户不存在');
        /** @var UserLevelServices $services */
        $services = app()->make(UserLevelServices::class);
        return $this->transaction(function () use ($uid, $services) {
            $res = $services->delUserLevel($uid);
            $res1 = $this->dao->update($uid, ['clean_time' => time(), 'level' => 0, 'exp' => 0], 'uid');
            if (!$res && !$res1)
                throw new AdminException('清除失败');
            return true;
        });
    }

    /**
     * 获取用户详情里面的用户消费能力和用户余额积分等
     * @param $uid
     * @return array[]
     */
    public function getHeaderList(int $uid, $userInfo = [])
    {
        if (!$userInfo) {
            $userInfo = $this->getUserInfo($uid);
        }
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $where = ['pid' => 0, 'uid' => $uid, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0];
        return [
            [
                'title' => '余额',
                'value' => $userInfo['now_money'] ?? 0,
                'key' => '元',
            ],
            [
                'title' => '总计订单',
                'value' => $orderServices->count($where),
                'key' => '笔',
            ],
            [
                'title' => '总消费金额',
                'value' => $orderServices->together($where, 'pay_price'),
                'key' => '元',
            ],
            [
                'title' => '积分',
                'value' => $userInfo['integral'] ?? 0,
                'key' => '',
            ],
            [
                'title' => '用户经验',
                'value' => $userInfo['exp'] ?? 0,
                'key' => '',
            ],
            [
                'title' => '消费次数',
                'value' => $userInfo['pay_count'] ?? 0,
                'key' => '',
            ],
//            [
//                'title' => '本月订单',
//                'value' => $orderServices->count($where + ['time' => 'month']),
//                'key' => '笔',
//            ],
//            [
//                'title' => '本月消费金额',
//                'value' => $orderServices->together($where + ['time' => 'month'], 'pay_price'),
//                'key' => '元',
//            ]
        ];
    }

    /**
     * 用户详情
     * @param int $uid
     * @return array
     */
    public function read(int $uid)
    {
        $userInfo = $this->dao->getUserWithTrashedInfo($uid);
        if (!$userInfo) {
            throw new AdminException('数据不存在');
        }
        $userInfo = $userInfo->toArray();
        $spread_uid_nickname = '';
        if ($userInfo['spread_uid']) {
            $spread_uid_nickname = $this->dao->value(['uid' => $userInfo['spread_uid']], 'nickname');
        }
        $userInfo['spread_uid_nickname'] = $userInfo['spread_uid'] ? $spread_uid_nickname . '/' . $userInfo['spread_uid'] : '';
        $userInfo['_add_time'] = date('Y-m-d H:i:s', $userInfo['add_time']);
        $userInfo['_last_time'] = date('Y-m-d H:i:s', $userInfo['last_time']);
        $userInfo['birthday'] = $userInfo['birthday'] ? date('Y-m-d', $userInfo['birthday']) : '';
        /** @var UserLabelRelationServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelRelationServices::class);
        $label_list = $userLabelServices->getUserLabelList([$uid]);
        $label_id = [];
        $userInfo['label_list'] = '';
        if ($label_list) {
            $userInfo['label_list'] = implode(',', array_column($label_list, 'label_name'));
            foreach ($label_list as $item) {
                $label_id[] = [
                    'id' => $item['label_id'],
                    'label_name' => $item['label_name']
                ];
            }
        }
        $userInfo['label_id'] = $label_id;
        $workClientInfo = $workMemberInfo = null;
        if ($userInfo['phone']) {
            /** @var WorkMemberServices $workMemberService */
            $workMemberService = app()->make(WorkMemberServices::class);
            $workMemberInfo = $workMemberService->get(['mobile' => $userInfo['phone']]);
            if ($workMemberInfo) {
                if (!$workMemberInfo->uid) {
                    $workMemberInfo->uid = $userInfo['uid'];
                    $workMemberInfo->save();
                }
                $workMemberInfo = $workMemberInfo->toArray();
            }
        }
        if (!$workMemberInfo) {
            /** @var WorkClientServices $workClientService */
            $workClientService = app()->make(WorkClientServices::class);
            $workClientInfo = $workClientService->get(['uid' => $userInfo['uid']]);
            if ($workClientInfo) {
                $workClientInfo = $workClientInfo->toArray();
            }
        }
        $info = [
            'uid' => $uid,
            'userinfo' => [],
            'headerList' => $this->getHeaderList($uid, $userInfo),
            'count' => [0, 0, 0],
            'ps_info' => $userInfo,
            'workClientInfo' => $workClientInfo,
            'workMemberInfo' => $workMemberInfo,
        ];
        return $info;
    }

    /**
     * 获取单个用户信息
     * @param $id 用户id
     * @return mixed
     */
    public function oneUserInfo(int $id, string $type)
    {
        switch ($type) {
            case 'spread':
//                /** @var UserSpreadServices $services */
//                $services = app()->make(UserSpreadServices::class);
                /** @var UserFriendsServices $services */
                $services = app()->make(UserFriendsServices::class);
                return $services->getFriendList($id);
                break;
            case 'order':
                /** @var StoreOrderServices $services */
                $services = app()->make(StoreOrderServices::class);
                return $services->getUserOrderList($id);
                break;
            case 'integral':
                /** @var UserBillServices $services */
                $services = app()->make(UserBillServices::class);
                return $services->getIntegralList($id, [], 'title,number,balance,mark,add_time');
                break;
            case 'sign':
                /** @var UserBillServices $services */
                $services = app()->make(UserBillServices::class);
                return $services->getSignList($id, [], 'title,number,mark,add_time');
                break;
            case 'coupon':
                /** @var StoreCouponUserServices $services */
                $services = app()->make(StoreCouponUserServices::class);
                return $services->getUserCouponList($id, 0);
                break;
            case 'balance_change':
                /** @var UserMoneyServices $services */
                $services = app()->make(UserMoneyServices::class);
                return $services->getUserMoneyList($id, [], 'title,type,number,balance,mark,pm,status,add_time');
                break;
            default:
                throw new AdminException('type参数错误');
        }
    }

    /**
     * 用户图表
     * @return array
     */
    public function userChart()
    {
        [$starday, $yesterday, $timeType, $timeKey] = $this->timeHandle('thirtyday', true);
        $user_list = $this->dao->userList($starday, $yesterday);
        $chartdata = [];
        $chartdata['legend'] = ['用户数'];//分类
        $chartdata['yAxis']['maxnum'] = 0;//最大值数量
        $chartdata['xAxis'] = $timeKey;//X轴值
        $chartdata['series'] = [];//分类1值
        if (!empty($user_list)) {
            $user_list = array_combine(array_column($user_list, 'day'), $user_list);
            $chartdata['yAxis']['maxnum'] = max(array_column($user_list, 'count'));
            foreach ($timeKey as $day) {
                if (isset($user_list[$day])) {
                    $chartdata['series'][] = $user_list[$day]['count'] ?? 0;
                } else {
                    $chartdata['series'][] = 0;
                }
            }
        }
        $chartdata['bing_xdata'] = ['未消费用户', '消费一次用户', '留存客户', '回流客户'];
        $color = ['#5cadff', '#b37feb', '#19be6b', '#ff9900'];
        $pay[0] = $this->dao->count(['pay_count' => 0]);
        $pay[1] = $this->dao->count(['pay_count' => 1]);
        $pay[2] = $this->dao->userCount(1);
        $pay[3] = $this->dao->userCount(2);
        foreach ($pay as $key => $item) {
            $bing_data[] = ['name' => $chartdata['bing_xdata'][$key], 'value' => $pay[$key], 'itemStyle' => ['color' => $color[$key]]];
        }
        $chartdata['bing_data'] = $bing_data;
        return $chartdata;
    }

    /***********************************************/
    /************ 前端api services *****************/
    /***********************************************/
    /**
     * 用户信息
     * @param $info
     * @return mixed
     */
    public function userInfo($info)
    {
        $uid = (int)$info['uid'];
        $broken_time = intval(sys_config('extract_time'));
        $search_time = time() - 86400 * $broken_time;
        //改造时间
        $search_time = '1970/01/01' . ' - ' . date('Y/m/d H:i:s', $search_time);
        //可提现佣金
        //返佣 +
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        $brokerage_commission = (string)$userBrokerageServices->getUsersBokerageSum(['uid' => $uid, 'pm' => 1], $search_time);
        //退款退的佣金 -
        $refund_commission = (string)$userBrokerageServices->getUsersBokerageSum(['uid' => $uid, 'pm' => 0], $search_time);
        $info['broken_commission'] = bcsub($brokerage_commission, $refund_commission, 2);
        if ($info['broken_commission'] < 0)
            $info['broken_commission'] = 0;
        $info['commissionCount'] = bcsub($info['brokerage_price'], $info['broken_commission'], 2);
        if ($info['commissionCount'] < 0)
            $info['commissionCount'] = 0;
        return $info;
    }

    /**
     * 个人中心
     * @param array $user
     */
    public function personalHome(array $user, $tokenData)
    {
        $uid = (int)$user['uid'];
        event('user.login', [$uid, app('request')->ip()]);
        /** @var StoreCouponUserServices $storeCoupon */
        $storeCoupon = app()->make(StoreCouponUserServices::class);
        /** @var UserMoneyServices $userMoneyServices */
        $userMoneyServices = app()->make(UserMoneyServices::class);
        /** @var UserExtractServices $userExtract */
        $userExtract = app()->make(UserExtractServices::class);
        /** @var StoreOrderServices $storeOrder */
        $storeOrder = app()->make(StoreOrderServices::class);
        /** @var UserLevelServices $userLevel */
        $userLevel = app()->make(UserLevelServices::class);
        /** @var StoreServiceServices $storeService */
        $storeService = app()->make(StoreServiceServices::class);
        /** @var WechatUserServices $wechatUser */
        $wechatUser = app()->make(WechatUserServices::class);
        /** @var StoreProductRelationServices $productRelation */
        $productRelation = app()->make(StoreProductRelationServices::class);
        /** @var SystemMessageServices $systemMessageServices */
        $systemMessageServices = app()->make(SystemMessageServices::class);
        /** @var DiyServices $diyServices */
        $diyServices = app()->make(DiyServices::class);
        /** @var AgentLevelServices $agentLevelServices */
        $agentLevelServices = app()->make(AgentLevelServices::class);
        /** @var StoreProductLogServices $storeProductLogServices */
        $storeProductLogServices = app()->make(StoreProductLogServices::class);
        //是否存在核销码
        if (!$user['bar_code']) {
            $bar_code = $this->getBarCode();
            $this->dao->update($uid, ['bar_code' => $bar_code], 'uid');
        }
        //获取配置参数
        $configData = SystemConfigService::more([
            'member_card_status',
            'brokerage_func_status',
            'store_brokerage_statu',
            'store_brokerage_price',
            'member_func_status',
            'recharge_switch',
            'extract_time',
            'balance_func_status',
            'invoice_func_status',
            'special_invoice_status',
            'user_extract_bank_status',
            'user_extract_wechat_status',
            'user_extract_alipay_status'
        ]);
        //看付费会员是否开启
        $user['is_open_member'] = $user['svip_open'] = !!($configData['member_card_status'] ?? 0);
        $user['agent_level_name'] = '';
        if ($user['agent_level']) {
            $levelInfo = $agentLevelServices->getLevelInfo((int)$user['agent_level'], 'id,name');
            $user['agent_level_name'] = $levelInfo && $levelInfo['name'] ? $levelInfo['name'] : '';
        }
        $wechatUserInfo = $wechatUser->getOne(['uid' => $uid, 'user_type' => $tokenData['type']]);
        $user['is_complete'] = $wechatUserInfo['is_complete'] ?? 0;
        $user['couponCount'] = $storeCoupon->getUserValidCouponCount((int)$uid);
        $user['like'] = $productRelation->getUserCount($uid, 0, 'like');
        $user['collectCount'] = $productRelation->getUserCount($uid, 0, 'collect');
        $user['orderStatusNum'] = $storeOrder->getOrderData($uid);
        $user['notice'] = 0;
        $user['recharge'] = $userMoneyServices->getRechargeSum($uid);//累计充值
        $user['orderStatusSum'] = (float)$userMoneyServices->sum(['uid' => $uid, 'pm' => 0, 'status' => 1], 'number', true);
        $user['extractTotalPrice'] = $userExtract->getExtractSum(['uid' => $uid, 'status' => 1]);//累计提现
        $user['extractPrice'] = $user['brokerage_price'];//可提现
        $user['statu'] = (int)($configData['store_brokerage_statu'] ?? 0);
        $orderStatusSum = (float)$storeOrder->sum(['pid' => 0, 'paid' => 1, 'refund_status' => [0, 3], 'uid' => $user['uid'], 'is_del' => 0], 'pay_price', true);//累计有效消费
        $user['spread_status'] = ($configData['brokerage_func_status'] ?? 1) && $this->checkUserPromoter($user['uid'], $user, $orderStatusSum);
        if (!$user['is_promoter'] && $user['spread_status']) {
            $this->dao->update($uid, ['is_promoter' => 1], 'uid');
            $user['is_promoter'] = 1;
        }
        if ($user['statu'] == 3) {
            $storeBrokeragePrice = $configData['store_brokerage_price'] ?? 0;
            $user['promoter_price'] = bcsub((string)$storeBrokeragePrice, (string)$user['orderStatusSum'], 2);
        }
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        $user['broken_commission'] = $userBrokerageServices->getUserFrozenPrice($uid);
        if ($user['broken_commission'] < 0)
            $user['broken_commission'] = 0;
        $user['commissionCount'] = bcsub($user['brokerage_price'], $user['broken_commission'], 2);
        if ($user['commissionCount'] < 0)
            $user['commissionCount'] = 0;
        if (!($configData['member_func_status'] ?? 0))
            $user['vip'] = false;
        else {
            $userLevel = $userLevel->getUerLevelInfoByUid($user['uid']);
            $user['vip'] = $userLevel ? true : false;
            if ($user['vip']) {
                $user['vip_id'] = $userLevel['id'] ?? 0;
                $user['vip_icon'] = $userLevel['icon'] ?? '';
                $user['vip_name'] = $userLevel['name'] ?? '';
            }
        }
        $user['yesterDay'] = $userBrokerageServices->getUsersBokerageSum(['uid' => $uid, 'pm' => 1], 'yesterday');
        $user['recharge_switch'] = (int)($configData['recharge_switch'] ?? 0);//充值开关
        $user['adminid'] = $storeService->checkoutIsService(['uid' => $uid, 'status' => 1, 'customer' => 1]);
        $user['broken_day'] = (int)($configData['extract_time'] ?? 0);//佣金冻结时间
        $user['balance_func_status'] = (int)($configData['balance_func_status'] ?? 0);
        $user['invioce_func'] = !!($configData['invoice_func_status'] ?? 0);
        $user['special_invoice'] = $user['invioce_func'] && ($configData['special_invoice_status'] ?? 0);
        $user['pay_vip_status'] = $user['is_ever_level'] || ($user['is_money_level'] && $user['overdue_time'] > time());
        $user['member_style'] = (int)$diyServices->getColorChange('member');
        if ($user['is_ever_level']) {
            $user['vip_status'] = 1;//永久会员
        } else {
            if (!$user['is_money_level'] && $user['overdue_time'] && $user['overdue_time'] < time()) {
                $user['vip_status'] = -1;//开通过已过期
            } else if (!$user['overdue_time'] && !$user['is_money_level']) {
                $user['vip_status'] = 2;//没有开通过
            } else if ($user['is_money_level'] && $user['overdue_time'] && $user['overdue_time'] > time()) {
                $user['vip_status'] = 3;//开通了，没有到期
            }
        }
        /** @var StoreServiceRecordServices $servicesRecord */
        $servicesRecord = app()->make(StoreServiceRecordServices::class);
        $service_num = $servicesRecord->sum(['user_id' => $uid], 'mssage_num');
        $message = $systemMessageServices->count(['uid' => $uid, 'look' => 0, 'is_del' => 0]);
        $user['service_num'] = $service_num + $message;
        $user['is_agent_level'] = ($configData['brokerage_func_status'] ?? 1) && $agentLevelServices->count(['status' => 1, 'is_del' => 0]);
        $user['visit_num'] = $storeProductLogServices->getDistinctCount(['uid' => $uid, 'type' => 'visit'], 'product_id');
        $user['user_extract_bank_status'] = (int)($configData['user_extract_bank_status'] ?? 1);
        $user['user_extract_wechat_status'] = (int)($configData['user_extract_wechat_status'] ?? 1);
        $user['user_extract_alipay_status'] = (int)($configData['user_extract_alipay_status'] ?? 1);
        return $user;
    }

    /**
     * 用户资金统计
     * @param int $uid
     */
    public function balance(int $uid)
    {
        $userInfo = $this->getUserInfo($uid);
        if (!$userInfo) {
            throw new ValidateException('数据不存在');
        }
        /** @var UserMoneyServices $userMoneyServices */
        $userMoneyServices = app()->make(UserMoneyServices::class);
        /** @var StoreOrderServices $storeOrder */
        $storeOrder = app()->make(StoreOrderServices::class);
        $user['now_money'] = $userInfo['now_money'];//当前总资金
        $user['recharge'] = $userMoneyServices->getRechargeSum($uid);//累计充值
        $user['orderStatusSum'] = $storeOrder->sum(['pid' => 0, 'uid' => $uid, 'paid' => 1, 'is_del' => 0, 'refund_status' => [0, 3]], 'pay_price', true);//累计消费
        return $user;
    }

    /**
     * 用户修改信息
     * @param Request $request
     * @return mixed
     */
    public function eidtNickname(int $uid, array $data)
    {
        if (!$this->userExist($uid)) {
            throw new ValidateException('用户不存在');
        }
        if (!$this->dao->update($uid, $data, 'uid')) {
            throw new ValidateException('修改失败');
        }
        return true;
    }

    /**
     * 获取推广人排行
     * @param $data 查询条件
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRankList(array $data)
    {
        $startTime = $endTime = 0;
        switch ($data['type']) {
            case 'week':
                $startTime = strtotime('this week Monday');
                $endTime = time();
                break;
            case 'month':
                $startTime = strtotime('first day of ' . date('F Y'));
                $endTime = time();
                break;
        }
        [$page, $limit] = $this->getPageValue();
        $field = 'spread_uid,count(uid) AS count,spread_time';
        /** @var  $userSpreadServices */
        $userSpreadServices = app()->make(UserSpreadServices::class);
        $list = $userSpreadServices->getAgentRankList([$startTime, $endTime], $field, $page, $limit);
        $rank = 0;
        foreach ($list as $key => $item) {
            if ($item['spread_uid'] == $data['uid']) $rank = $key + 1;
        }
        $week = $userSpreadServices->count(['spread_uid' => $data['uid'], 'time' => [strtotime('this week Monday'), time()], 'timeKey' => 'spread_time']);
        $month = $userSpreadServices->count(['spread_uid' => $data['uid'], 'time' => [strtotime('last month'), time()], 'timeKey' => 'spread_time']);
        $start = date('Y-m-d H:i', $startTime);
        $end = date('Y-m-d H:i', time());
        return compact('list', 'rank', 'week', 'month', 'start', 'end');
    }

    /**
     * 静默绑定推广人
     * @param Request $request
     * @return mixed
     */
    public function spread(int $uid, int $spreadUid, $code)
    {
        $userInfo = $this->getUserInfo($uid);
        if (!$userInfo) {
            throw new ValidateException('数据不存在');
        }
        if ($code && !$spreadUid) {
            /** @var QrcodeServices $qrCode */
            $qrCode = app()->make(QrcodeServices::class);
            if ($info = $qrCode->getOne(['id' => $code, 'status' => 1])) {
                $spreadUid = $info['third_id'];
            }
        }
        $userSpreadUid = $this->dao->value(['uid' => $spreadUid], 'spread_uid');
        //记录好友关系
        if ($spreadUid && $uid && $spreadUid != $uid) {
            /** @var UserFriendsServices $serviceFriend */
            $serviceFriend = app()->make(UserFriendsServices::class);
            $serviceFriend->saveFriend($uid, $spreadUid);
        }
        //记录上下级关系
        if (!$userInfo['spread_uid'] && $spreadUid != $uid && $userSpreadUid != $userInfo['uid']) {
            if ((sys_config('brokerage_bindind') == 2 && $userInfo['add_time'] == $userInfo['last_time']) || sys_config('brokerage_bindind') == 1) {
                if (!$this->dao->update($uid, ['spread_uid' => $spreadUid, 'spread_time' => time()], 'uid')) {
                    throw new ValidateException('绑定推广关系失败');
                }
                /** @var UserBillServices $userBill */
                $userBill = app()->make(UserBillServices::class);
                //邀请新用户增加经验
                $userBill->inviteUserIncExp((int)$spreadUid);
            }
        }
        return true;
    }

    /**
     * 添加访问记录
     * @param Request $request
     * @return mixed
     */
    public function setVisit(array $data)
    {
        $userInfo = $this->getUserInfo($data['uid']);
        if (!$userInfo) {
            throw new ValidateException('数据不存在');
        }
        $data['channel_type'] = $userInfo['user_type'];
        $data['add_time'] = time();
        /** @var WechatUserServices $wechatUserServices */
        $wechatUserServices = app()->make(WechatUserServices::class);
        $wechatUser = $wechatUserServices->get(['uid' => $userInfo['uid'], 'user_type' => $userInfo['user_type']]);
        if (!$wechatUser) {
            $wechatUser = $wechatUserServices->get(['uid' => $userInfo['uid']]);
        }
        if ($wechatUser) {
            $data['province'] = $wechatUser['province'];
        }
        /** @var UserVisitServices $userVisit */
        $userVisit = app()->make(UserVisitServices::class);
        if ($userVisit->save($data)) {
            return true;
        } else {
            throw new ValidateException('添加访问记录失败');
        }
    }

    /**
     * 获取活动状态
     * @return mixed
     */
    public function activity()
    {
        /** @var StoreBargainServices $storeBragain */
        $storeBragain = app()->make(StoreBargainServices::class);
        /** @var StoreCombinationServices $storeCombinaion */
        $storeCombinaion = app()->make(StoreCombinationServices::class);
        /** @var StoreSeckillServices $storeSeckill */
        $storeSeckill = app()->make(StoreSeckillServices::class);
        $data['is_bargin'] = $storeBragain->validBargain() ? true : false;
        $data['is_pink'] = $storeCombinaion->validCombination() ? true : false;
        $data['is_seckill'] = $storeSeckill->getSeckillCount() ? true : false;
        return $data;
    }

    /**
     * 获取用户下级推广人
     * @param int $uid 当前用户
     * @param int $grade 等级  0  一级 1 二级
     * @param string $orderBy 排序
     * @param string $keyword
     * @return array|bool
     */
    public function getUserSpreadGrade(int $uid = 0, $grade = 0, $orderBy = '', $keyword = '', $time = [])
    {
        $user = $this->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('数据不存在');
        }
        $spread_one_ids = $this->getUserSpredadUids($uid, 1);
        $spread_two_ids = $this->getUserSpredadUids($uid, 2);
        $data = [
            'total' => count($spread_one_ids),
            'totalLevel' => count($spread_two_ids),
            'list' => []
        ];
        /** @var UserStoreOrderServices $userStoreOrder */
        $userStoreOrder = app()->make(UserStoreOrderServices::class);
        $list = [];
        if ($grade == 0) {
            if ($spread_one_ids) $list = $userStoreOrder->getUserSpreadCountList($spread_one_ids, $orderBy, $keyword, $time);
        } else {
            if ($spread_two_ids) $list = $userStoreOrder->getUserSpreadCountList($spread_two_ids, $orderBy, $keyword, $time);
        }
        foreach ($list as &$item) {
            if (isset($item['spread_time']) && $item['spread_time']) {
                $item['time'] = date('Y/m/d', $item['spread_time']);
            }
        }
        $data['list'] = $list;
        $data['count'] = 0;
        $data['price'] = 0;
        if ($list) {
            /** @var StoreOrderServices $storeOrder */
            $storeOrder = app()->make(StoreOrderServices::class);
            $uids = array_column($list, 'uid');
            $data['count'] = count($uids);
            //排除自购
            $uids = array_diff($uids, [$uid]);
            $data['price'] = $storeOrder->sum([['uid', 'in', $uids]], $grade == 0 ? 'one_brokerage' : 'two_brokerage');
        }

        return $data;
    }

    /**
     * 获取推广人uids
     * @param int $uid
     * @param bool $one
     * @return array
     */
    public function getUserSpredadUids(int $uid, int $type = 0)
    {
        $uids = $this->dao->getColumn(['spread_uid' => $uid], 'uid');
        if ($type === 1) {
            return $uids;
        }
        if ($uids) {
            $uidsTwo = $this->dao->getColumn([['spread_uid', 'in', $uids]], 'uid');
            if ($type === 2) {
                return $uidsTwo;
            }
            if ($uidsTwo) {
                $uids = array_merge($uids, $uidsTwo);
            }
        }
        return $uids;
    }

    /**
     * 检测用户是否是推广员
     * @param int $uid
     * @param array $user
     * @param float $sumPrice
     * @return bool
     */
    public function checkUserPromoter(int $uid, $user = [], float $sumPrice = 0.00)
    {
        if (!$user) {
            $user = $this->getUserInfo($uid);
        }
        if (!$user) {
            return false;
        }
        //分销是否开启
        if (!sys_config('brokerage_func_status')) {
            return false;
        }
        //用户分校推广资格是否开启4.0.32
        if (isset($user['spread_open']) && !$user['spread_open']) {
            return false;
        }
        $store_brokerage_statu = sys_config('store_brokerage_statu');
        if ($user['is_promoter'] || $store_brokerage_statu == 2) {
            return true;
        }
        if ($store_brokerage_statu == 3) {
            if ($sumPrice) {
                /** @var StoreOrderServices $storeOrder */
                $storeOrder = app()->make(StoreOrderServices::class);
                $sumPrice = $storeOrder->sum(['pid' => 0, 'uid' => $uid, 'paid' => 1, 'is_del' => 0, 'refund_status' => [0, 3]], 'pay_price');//累计消费
            }
            $store_brokerage_price = sys_config('store_brokerage_price');
            if ($sumPrice > $store_brokerage_price) {
                return true;
            }
        }
        return false;
    }

    /**
     * 同步微信粉丝用户(后台接口)
     * @return bool
     */
    public function syncWechatUsers()
    {
        $key = md5('sync_wechat_users');
        //一天点击一次
        if (CacheService::get($key)) {
            return true;
        }
        $next_openid = null;
        do {
            $result = OfficialAccount::userService()->list($next_openid);
            $userOpenids = $result['data'];
            //拆分大数组
            $opemidArr = array_chunk($userOpenids, 100);
            foreach ($opemidArr as $openids) {
                //加入同步|更新用户队列
                UserJob::dispatch([$openids]);
            }
            $next_openid = $result['next_openid'];
        } while ($next_openid != null);
        CacheService::set($key, 1, 3600 * 24);
        return true;
    }

    /**
     * 导入微信粉丝用户
     * @param array $openids
     * @return bool
     */
    public function importUser(array $noBeOpenids)
    {
        if (!$noBeOpenids) {
            return true;
        }
        $dataAll = $data = [];
        $time = time();
        foreach ($noBeOpenids as $openid) {
            try {
                $info = OfficialAccount::userService()->get($openid);
                $info = is_object($info) ? $info->toArray() : $info;
            } catch (\Throwable $e) {
                $info = [];
            }
            if (!$info) continue;
            if (($info['subscribe'] ?? 1) == 1) {
                $data['nickname'] = $info['nickname'] ?? '';
                $data['headimgurl'] = $info['headimgurl'] ?? '';
                $userInfoData = $this->setUserInfo($data);
                if (!$userInfoData) {
                    throw new AdminException('用户信息储存失败!');
                }
                $data['uid'] = $userInfoData['uid'];
                $data['subscribe'] = $info['subscribe'];
                $data['unionid'] = $info['unionid'] ?? '';
                $data['openid'] = $info['openid'] ?? '';
                $data['sex'] = $info['sex'] ?? 0;
                $data['language'] = $info['language'] ?? '';
                $data['city'] = $info['city'] ?? '';
                $data['province'] = $info['province'] ?? '';
                $data['country'] = $info['country'] ?? '';
                $data['subscribe_time'] = $info['subscribe_time'] ?? '';
                $data['groupid'] = $info['groupid'] ?? 0;
                $data['remark'] = $info['remark'] ?? '';
                $data['tagid_list'] = isset($info['tagid_list']) && $info['tagid_list'] ? implode(',', $info['tagid_list']) : '';
                $data['add_time'] = $time;
                $data['is_complete'] = 1;
                $dataAll[] = $data;
            }
        }
        if ($dataAll) {
            /** @var WechatUserServices $wechatUser */
            $wechatUser = app()->make(WechatUserServices::class);
            if (!$wechatUser->saveAll($dataAll)) {
                throw new ValidateException('保存用户信息失败');
            }
        }
        return true;
    }

    /**
     * 修改会员的时间及是否会员状态
     * @param int $vip_day 会员天数
     * @param array $user_id 用户id
     * @param int $is_money_level 会员来源途径
     * @param bool $member_type 会员卡类型
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setMemberOverdueTime($vip_day, int $user_id, int $is_money_level, $member_type = false)
    {
        if ($vip_day == 0) throw new ValidateException('天数不能为0');
        $user_info = $this->getUserInfo($user_id);
        if (!$user_info) throw new ValidateException('用户数据不存在');
        if (!$member_type) $member_type = "month";
        if ($member_type == 'ever') {
            $overdue_time = 0;
            $is_ever_level = 1;
        } else {
            if ($user_info['is_money_level'] == 0) {
                $overdue_time = bcadd(bcmul($vip_day, 86400, 0), time(), 0);
            } else {
                $overdue_time = bcadd(bcmul($vip_day, 86400, 0), $user_info['overdue_time'], 0);
            }
            $is_ever_level = 0;
        }
        $setData['overdue_time'] = $overdue_time;
        $setData['is_ever_level'] = $is_ever_level;
        $setData['is_money_level'] = $is_money_level ? $is_money_level : 0;
        // if ($user_info['level'] == 0) $setData['level'] = 1;
        return $this->dao->update(['uid' => $user_id], $setData);
    }

    /**
     * 清空到期svip（分批加入队列）
     * @return bool
     */
    public function offUserSvip()
    {
        $users = $this->dao->getColumn([['is_ever_level', '=', 0], ['is_money_level', '>', 0], ['overdue_time', '<', time()]], 'uid');
        if ($users) {
            //拆分大数组
            $uidsArr = array_chunk($users, 100);
            foreach ($uidsArr as $uids) {
                //加入同步|更新用户队列
                UserSvipJob::dispatch([$uids]);
            }
        }
        return true;
    }

    /**
     * 会员过期改变状态，变为普通会员
     * @param $uid
     * @param null $userInfo
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function offMemberLevel($uid, $userInfo = null)
    {
        if (!$uid) return false;
        $userInfo = $userInfo ?: $this->dao->get($uid);
        if (!$userInfo) return false;
        if ($userInfo['is_ever_level'] == 0 && $userInfo['is_money_level'] > 0 && $userInfo['overdue_time'] < time()) {
            $this->dao->update(['uid' => $uid], ['is_money_level' => 0]);
            return false;
        }
        return true;
    }

    /**
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfoList(array $where, $field = "*")
    {
        return $this->dao->getUserInfoList($where, $field);
    }

    /**
     * 增加推广用户佣金
     * @param int $uid
     * @param int $spread_uid
     * @param array $userInfo
     * @param array $spread_user
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addBrokeragePrice(int $uid, int $spread_uid, array $userInfo = [], array $spread_user = [])
    {
        if (!$uid || !$spread_uid) {
            return false;
        }
        //商城分销功能是否开启 0关闭1开启
        if (!sys_config('brokerage_func_status')) return true;
        //获取设置推广佣金单价
        $brokerage_price = sys_config('uni_brokerage_price', 0);
        //推广佣金是否开启
        if (!sys_config('brokerage_user_status', 0)) {
            return true;
        }
        //获取推广佣金当日限额
        $day_brokerage_price_upper = sys_config('day_brokerage_price_upper', 0);
        if (!floatval($brokerage_price) || !floatval($day_brokerage_price_upper)) {
            return true;
        }
        if (!$userInfo) {
            $userInfo = $this->getUserInfo($uid);
        }
        if (!$userInfo) {
            return false;
        }
        if (!$spread_user) {
            $spread_user = $this->dao->getOne(['uid' => $spread_uid, 'status' => 1]);
        }
        if (!$spread_user) {
            return false;
        }
        if (!$this->checkUserPromoter($spread_uid, $spread_user)) {
            return false;
        }
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        // -1不限制
        if ($day_brokerage_price_upper != -1) {
            if ($day_brokerage_price_upper <= 0) {
                return true;
            } else {
                //获取上级用户今日获取推广用户佣金
                $spread_day_brokerage = $userBrokerageServices->getUserBillBrokerageSum($spread_uid, ['brokerage_user'], 'today');
                //超过上限
                if (($spread_day_brokerage + $brokerage_price) > $day_brokerage_price_upper) {
                    return true;
                }
            }
        }
        $spreadPrice = $spread_user['brokerage_price'];
        // 上级推广员返佣之后的金额
        $balance = bcadd($spreadPrice, $brokerage_price, 2);
        return $this->transaction(function () use ($uid, $spread_uid, $brokerage_price, $userInfo, $balance, $userBrokerageServices) {
            // 添加返佣记录
            $res1 = $userBrokerageServices->income('get_user_brokerage', $spread_uid, [
                'nickname' => $userInfo['nickname'],
                'number' => floatval($brokerage_price)
            ], $balance, $uid);
            // 添加用户余额
            $res2 = $this->dao->bcInc($spread_uid, 'brokerage_price', $brokerage_price, 'uid');
            //给上级发送获得佣金的模板消息
            /** @var StoreOrderTakeServices $storeOrderTakeServices */
            $storeOrderTakeServices = app()->make(StoreOrderTakeServices::class);
            $storeOrderTakeServices->sendBackOrderBrokerage([], $spread_uid, $brokerage_price, 'user');
            return $res1 && $res2;
        });
    }

    /**
     * 获取上级uid
     * @param int $uid
     * @param array $userInfo
     * @param bool $is_spread
     * @return int|mixed
     */
    public function getSpreadUid(int $uid, $userInfo = [], $is_spread = true)
    {
        if (!$uid) {
            return 0;
        }
        //商城分销功能是否开启 0关闭1开启
        if (!sys_config('brokerage_func_status')) return -1;
        if (!$userInfo) {
            $userInfo = $this->getUserCacheInfo($uid);
        }
        if (!$userInfo) {
            return 0;
        }
        //上级的上级不需要检测自购
        if ($is_spread) {
            //开启自购
            $is_self_brokerage = sys_config('is_self_brokerage', 0);
            if ($is_self_brokerage && $is_spread) {
                return $uid;
            }
        }
        //绑定类型
        $store_brokergae_binding_status = sys_config('store_brokerage_binding_status', 1);
        if ($store_brokergae_binding_status == 1 || $store_brokergae_binding_status == 3) {
            return $userInfo['spread_uid'];
        }
        //分销绑定类型为时间段且没过期
        $store_brokerage_binding_time = sys_config('store_brokerage_binding_time', 30);
        if ($store_brokergae_binding_status == 2 && ($userInfo['spread_time'] + $store_brokerage_binding_time * 24 * 3600) > time()) {
            return $userInfo['spread_uid'];
        }
        return -1;
    }

    /**
     * 用户付款code
     * @param int $uid
     * @return bool|mixed|null
     */
    public function getRandCode(int $uid)
    {
        $key = 'user_rand_code' . $uid;
        return CacheService::get($key, function () {
            return substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 9), 1))), 0, 3) . str_pad((string)mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        }, 600);
    }

    /**
     * 获取barcode
     * @return bool|int|mixed|null
     */
    public function getBarCode()
    {
        mt_srand();
        $code = substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 9), 1))), 0, 4) . str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        if (!$this->dao->count(['bar_code' => $code])) {
            return $code;
        } else {
            return $this->getBarCode();
        }
    }

    /**
     * 获取用户推广用户列表
     * @param $uid
     * @param $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function agentUserList($uid, $type)
    {
        $where['spread_uid'] = $uid;
        if ($type == 1) {
            $where['pay_count'] = -1;
        }
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, 'uid,nickname,avatar,FROM_UNIXTIME(spread_time, \'%Y.%m.%d %H:%m\') as spread_time', $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }
}
