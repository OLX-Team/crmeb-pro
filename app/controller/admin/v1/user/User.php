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
namespace app\controller\admin\v1\user;

use app\jobs\BatchHandleJob;
use app\services\other\queue\QueueServices;
use app\services\product\product\StoreProductLogServices;
use app\services\user\group\UserGroupServices;
use app\services\user\label\UserLabelServices;
use app\services\user\UserServices;
use app\controller\admin\AuthController;
use app\services\user\UserSpreadServices;
use app\services\user\UserWechatuserServices;
use think\exception\ValidateException;
use think\facade\App;

class User extends AuthController
{
    /**
     * user constructor.
     * @param App $app
     * @param UserServices $services
     */
    public function __construct(App $app, UserServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 显示资源列表头部
     *
     * @return \think\Response
     */
    public function type_header()
    {
        $list = $this->services->typeHead();
        return $this->success(compact('list'));
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['page', 1],
            ['limit', 20],
            ['nickname', ''],
            ['status', ''],
            ['pay_count', ''],
            ['is_promoter', ''],
            ['order', ''],
            ['data', ''],
            ['user_type', ''],
            ['country', ''],
            ['province', ''],
            ['city', ''],
            ['user_time_type', ''],
            ['user_time', ''],
            ['sex', ''],
            [['level', 0], 0],
            [['group_id', 'd'], 0],
            [['label_id', 'd'], 0],
            ['now_money', 'normal'],
            ['field_key', ''],
            ['isMember', ''],
            ['label_ids', '']
        ]);
        if ($where['label_ids']) {
            $where['label_id'] = explode(',', $where['label_ids']);
            unset($where['label_ids']);
        }
        $where['user_time_type'] = $where['user_time_type'] == 'all' ? '' : $where['user_time_type'];
        return $this->success($this->services->index($where));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        return $this->success($this->services->saveForm());
    }

    /**
     * 保存新建的资源
     *
     * @param \think\Request $request
     * @return \think\Response
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['is_promoter', 0],
            ['real_name', ''],
            ['card_id', ''],
            ['birthday', ''],
            ['mark', ''],
            ['status', 0],
            ['level', 0],
            ['phone', 0],
            ['addres', ''],
            ['label_id', []],
            ['group_id', 0],
            ['pwd', ''],
            ['true_pwd', ''],
            ['spread_open', 1],
            ['sex', 0],
            ['provincials', ''],
            ['province', 0],
            ['city', 0],
            ['area', 0],
            ['street', 0],
        ]);
        if ($data['phone']) {
            if (!check_phone($data['phone'])) {
                return $this->fail('手机号码格式不正确');
            }
            if ($this->services->count(['phone' => $data['phone']])) {
                return $this->fail('手机号已经存在不能添加相同的手机号用户');
            }
            $data['nickname'] = substr_replace($data['phone'], '****', 3, 4);
        }
        if ($data['card_id']) {
            if (!check_card($data['card_id'])) return $this->fail('请输入正确的身份证');
        }
		if ($data['birthday']) {
			if (strtotime($data['birthday']) > time()) return $this->fail('生日请选择今天之前日期');
		}
        if ($data['pwd']) {
            if (!$data['true_pwd']) {
                return $this->fail('请输入确认密码');
            }
            if ($data['pwd'] != $data['true_pwd']) {
                return $this->fail('两次输入的密码不一致');
            }
            if (strlen($data['pwd']) < 6) {
                return $this->fail('密码长度最小6位');
            }
            $data['pwd'] = md5($data['pwd']);
        } else {
            unset($data['pwd']);
        }
        unset($data['true_pwd']);
        $data['avatar'] = sys_config('h5_avatar');
        $data['adminId'] = $this->adminId;
        $data['user_type'] = 'h5';
        $lables = $data['label_id'];
        unset($data['label_id']);
        foreach ($lables as $k => $v) {
            if (!$v) {
                unset($lables[$k]);
            }
        }
        $data['birthday'] = empty($data['birthday']) ? 0 : strtotime($data['birthday']);
        $data['add_time'] = time();
        $this->services->transaction(function () use ($data, $lables) {
            $res = true;
            $userInfo = $this->services->save($data);
            if ($lables) {
                $res = $this->services->saveSetLabel([$userInfo->uid], $lables);
            }
            if ($data['level']) {
                $res = $this->services->saveGiveLevel((int)$userInfo->uid, (int)$data['level']);
            }
            if (!$res) {
                throw new ValidateException('保存添加用户失败');
            }
            event('user.register', [$this->services->get((int)$userInfo->uid), true, 0]);
        });
        event('user.create', $data);
        return $this->success('添加成功');
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        if (is_string($id)) {
            $id = (int)$id;
        }
        return $this->success($this->services->read($id));
    }

    /**
     * 赠送会员等级
     * @param int $uid
     * @return mixed
     * */
    public function give_level($id)
    {
        if (!$id) return $this->fail('缺少参数');
        return $this->success($this->services->giveLevel((int)$id));
    }

    /**
     * 执行赠送会员等级
     * @param int $uid
     * @return mixed
     * */
    public function save_give_level($id)
    {
        if (!$id) return $this->fail('缺少参数');
        list($level_id) = $this->request->postMore([
            ['level_id', 0],
        ], true);
        return $this->success($this->services->saveGiveLevel((int)$id, (int)$level_id) ? '赠送成功' : '赠送失败');
    }

    /**
     * 赠送付费会员时长
     * @param int $uid
     * @return mixed
     * */
    public function give_level_time($id)
    {
        if (!$id) return $this->fail('缺少参数');
        return $this->success($this->services->giveLevelTime((int)$id));
    }

    /**
     * 执行赠送付费会员时长
     * @param int $uid
     * @return mixed
     * */
    public function save_give_level_time($id)
    {
        if (!$id) return $this->fail('缺少参数');
        list($days) = $this->request->postMore([
            ['days', 0],
        ], true);
        return $this->success($this->services->saveGiveLevelTime((int)$id, (int)$days) ? '赠送成功' : '赠送失败');
    }

    /**
     * 清除会员等级
     * @param int $uid
     * @return json
     */
    public function del_level($id)
    {
        if (!$id) return $this->fail('缺少参数');
        return $this->success($this->services->cleanUpLevel((int)$id) ? '清除成功' : '清除失败');
    }

    /**
     * 设置会员分组
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function set_group()
    {
        list($uids, $all, $where) = $this->request->postMore([
            ['uids', []],
            ['all', 0],
            ['where', []],
        ], true);
        return $this->success($this->services->setGroup($uids, $all, $where));
    }

    /**
     * 保存会员分组
     * @param $id
     * @return mixed
     */
    public function save_set_group()
    {
        [$group_id, $uids, $all, $where] = $this->request->postMore([
            ['group_id', 0],
            ['uids', ''],
            ['all', 0],
            ['where', ""],
        ], true);
        if (!$uids && $all == 0) return $this->fail('缺少参数');
        if (!$group_id) return $this->fail('请选择分组');
        $type = 2;//代表设置用户标签
        if ($all == 0) {
            $uids = explode(',', $uids);
            $where = [];
        }
        if ($all == 1) {
            $where = $where ? json_decode($where, true) : [];
            /** @var UserWechatuserServices $userWechatUser */
            $userWechatUser = app()->make(UserWechatuserServices::class);
            $fields = 'u.uid';
            [$list, $count] = $userWechatUser->getWhereUserList($where, $fields);
            $uids = array_unique(array_column($list, 'uid'));
            $where = [];
        }
        /** @var UserGroupServices $userGroup */
        $userGroup = app()->make(UserGroupServices::class);
        if (!$userGroup->getGroup($group_id)) {
            return $this->fail('该分组不存在');
        }
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $queueService->setQueueData($where, 'uid', $uids, $type, $group_id);
        //加入队列
        BatchHandleJob::dispatch([$group_id, $type]);
        return $this->success('后台程序已执行用户分组任务!');
    }

    /**
     * 设置用户标签
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function set_label()
    {
        list($uids, $all, $where) = $this->request->postMore([
            ['uids', []],
            ['all', 0],
            ['where', ""],
        ], true);
        return $this->success($this->services->setLabel($uids, $all, $where));
    }

    /**
     * 保存用户标签
     * @return mixed
     */
    public function save_set_label()
    {
        list($lables, $uids, $all, $where) = $this->request->postMore([
            ['label_id', []],
            ['uids', ''],
            ['all', 0],
            ['where', ""],
        ], true);
        if (!$uids && $all == 0) return $this->fail('缺少参数');
        if (!$lables) return $this->fail('请选择标签');
        if ($all == 0) {
            $uids = is_array($uids) ? $uids : explode(',', $uids);
            $where = [];
        }
        if ($all == 1) {
            $where = $where ? (is_string($where) ? json_decode($where, true) : $where) : [];
            /** @var UserWechatuserServices $userWechatUser */
            $userWechatUser = app()->make(UserWechatuserServices::class);
            $fields = 'u.uid';
            [$list, $count] = $userWechatUser->getWhereUserList($where, $fields);
            $uids = array_unique(array_column($list, 'uid'));
            $where = [];
        }
        /** @var UserLabelServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelServices::class);
        $count = $userLabelServices->getCount([['id', 'IN', $lables]]);
        if ($count != count($lables)) {
            return app('json')->fail('用户标签不存在或被删除');
        }
        $type = 3;//批量设置用户标签
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $queueService->setQueueData($where, 'uid', $uids, $type, $lables);
        //加入队列
        BatchHandleJob::dispatch([$lables, $type]);
        return $this->success('后台程序已执行批量设置用户标签任务!');
    }

    /**
     * 编辑其他
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function edit_other($id)
    {
        if (!$id) return $this->fail('数据不存在');
        return $this->success($this->services->editOther((int)$id));
    }

    /**
     * 执行编辑其他
     * @param int $id
     * @return mixed
     */
    public function update_other($id)
    {
        $data = $this->request->postMore([
            ['money_status', 0],
            ['money', 0],
            ['integration_status', 0],
            ['integration', 0],
        ]);
        if (!$id) return $this->fail('数据不存在');
        $data['adminId'] = $this->adminId;
        $data['money'] = (string)$data['money'];
        $data['integration'] = (string)$data['integration'];
        $data['is_other'] = true;
        return $this->success($this->services->updateInfo($id, $data) ? '修改成功' : '修改失败');
    }

    /**
     * 修改user表状态
     *
     * @return array
     */
    public function set_status($status, $id)
    {
//        if ($status == '' || $id == 0) return $this->fail('参数错误');
//        UserModel::where(['uid' => $id])->update(['status' => $status]);
        return $this->success($status == 0 ? '禁用成功' : '解禁成功');
    }

    /**
     * 编辑会员信息
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function edit($id)
    {
        if (!$id) return $this->fail('数据不存在');
        return $this->success($this->services->edit($id));
    }

    public function update($id)
    {
        $data = $this->request->postMore([
            ['money_status', 0],
            ['is_promoter', 0],
            ['real_name', ''],
            ['card_id', ''],
            ['birthday', ''],
            ['mark', ''],
            ['money', 0],
            ['integration_status', 0],
            ['integration', 0],
            ['status', 0],
            ['level', 0],
            ['phone', 0],
            ['addres', ''],
            ['label_id', []],
            ['group_id', 0],
            ['pwd', ''],
            ['true_pwd'],
            ['spread_open', 1],
            ['sex', 0],
            ['provincials', ''],
            ['province', 0],
            ['city', 0],
            ['area', 0],
            ['street', 0],
            ['spread_uid', -1]
        ]);
        if ($data['phone']) {
            if (!check_phone($data['phone'])) return $this->fail('手机号码格式不正确');
        }
        if ($data['card_id']) {
            if (!check_card($data['card_id'])) return $this->fail('请输入正确的身份证');
        }
		if ($data['birthday']) {
			if (strtotime($data['birthday']) > time()) return $this->fail('生日请选择今天之前日期');
		}
        if ($data['pwd']) {
            if (!$data['true_pwd']) {
                return $this->fail('请输入确认密码');
            }
            if ($data['pwd'] != $data['true_pwd']) {
                return $this->fail('两次输入的密码不一致');
            }
            $data['pwd'] = md5($data['pwd']);
        } else {
            unset($data['pwd']);
        }
        $userInfo = $this->services->get($id);
        if (!$userInfo) {
            return $this->fail('用户不存在');
        }
        if (!in_array($data['spread_uid'], [0, -1])) {
            $spreadUid = $data['spread_uid'];
            if ($id == $spreadUid) {
                return $this->fail('上级推广人不能为自己');
            }
            if (!$this->services->count(['uid' => $spreadUid])) {
                return $this->fail('上级用户不存在');
            }
            $spreadInfo = $this->services->get($spreadUid);
            if ($spreadInfo->spread_uid == $id) {
                return $this->fail('上级推广人不能为自己下级');
            }
        }
        unset($data['true_pwd']);
        if (!$id) return $this->fail('数据不存在');
        $data['adminId'] = $this->adminId;
        $data['money'] = (string)$data['money'];
        $data['integration'] = (string)$data['integration'];
        return $this->success($this->services->updateInfo((int)$id, $data) ? '修改成功' : '修改失败');
    }

    /**
     * 获取单个用户信息
     * @param $id 用户id
     * @return mixed
     */
    public function oneUserInfo($id)
    {
        $data = $this->request->getMore([
            ['type', ''],
        ]);
        $id = (int)$id;
        if ($data['type'] == '') return $this->fail('缺少参数');
        return $this->success($this->services->oneUserInfo($id, $data['type']));
    }

    /**
     * 同步微信粉丝用户
     * @return mixed
     */
    public function syncWechatUsers()
    {
        $this->services->syncWechatUsers();
        return $this->success('加入消息队列成功，正在异步执行中');
    }

    /**
     * 商品浏览记录
     * @param $id
     * @param StoreProductLogServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function visitList($id, StoreProductLogServices $services)
    {
        $where['uid'] = (int)$id;
        $where['type'] = 'visit';
        return app('json')->success($services->getList($where, 'product_id'));
    }

    /**
     * 获取推广人记录
     * @param $id
     * @param UserSpreadServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function spreadList($id, UserSpreadServices $services)
    {
        $where['store_id'] = 0;
        $where['staff_id'] = 0;
        $where['uid'] = $id;
        return app('json')->success($services->getSpreadList($where, '*', ['spreadUser', 'admin'], false));
    }
}
