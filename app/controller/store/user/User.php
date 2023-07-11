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
namespace app\controller\store\user;

use app\services\store\DeliveryServiceServices;
use think\facade\App;
use app\controller\store\AuthController;
use app\services\store\StoreUserServices;
use app\services\user\UserServices;
use app\services\other\queue\QueueServices;
use app\services\user\label\UserLabelServices;
use app\jobs\BatchHandleJob;

/**
 * 门店用户
 * Class User
 * @package app\controller\store\user
 */
class User extends AuthController
{
    /**
     * DeliveryService constructor.
     * @param App $app
     * @param StoreUserServices $services
     */
    public function __construct(App $app, StoreUserServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 搜索用户
     * @param UserServices $services
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function search(UserServices $services)
    {
        $data = $this->request->getMore([
            ['keyword', ''],
            ['field_key', ''],
        ]);
        if ($data['field_key'] == 'all') {
            $data['field_key'] = '';
        }
        if ($data['field_key'] && in_array($data['field_key'], ['uid', 'phone'])) {
            $where[$data['field_key']] = trim($data['keyword']);
        } else {
            $where['store_like'] = trim($data['keyword']);
        }
		$result = $services->getUserList($where, 'uid,nickname,avatar,phone,now_money,user_type');
        $list = $result['list'] ?? [];
        $count = $result['count'] ?? 0;
		if ($list) {
			foreach ($list as &$item) {
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
			}
		}
        return app('json')->success(compact('list', 'count'));
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
            ['isMember', '']
        ]);
        return app('json')->success($this->services->index($where, $this->storeId));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        return app('json')->success($this->services->saveForm());
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
            ['spread_open', 1]
        ]);
        if ($data['phone']) {
            if (!check_phone($data['phone'])) {
                return app('json')->fail('手机号码格式不正确');
            }
            if ($this->services->count(['phone' => $data['phone']])) {
                return app('json')->fail('手机号已经存在不能添加相同的手机号用户');
            }
            $data['nickname'] = substr_replace($data['phone'], '****', 3, 4);
        }
        if ($data['card_id']) {
            if (!check_card($data['card_id'])) return app('json')->fail('请输入正确的身份证');
        }
        if ($data['pwd']) {
            if (!$data['true_pwd']) {
                return app('json')->fail('请输入确认密码');
            }
            if ($data['pwd'] != $data['true_pwd']) {
                return app('json')->fail('两次输入的密码不一致');
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
        return app('json')->success('添加成功');
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @param UserServices $services
     * @return \think\Response
     */
    public function read($id, UserServices $services)
    {
        if (is_string($id)) {
            $id = (int)$id;
        }
        return app('json')->success($services->read($id));
    }

    /**
     * 设置用户标签表单
     * @param UserServices $services
     * @return mixed
     */
    public function set_label(UserServices $services)
    {
        list($uids, $all, $where) = $this->request->postMore([
            ['uids', []],
            ['all', 0],
            ['where', ""],
        ], true);
        return app('json')->success($services->setLabel($uids, $all, $where, 2, $this->storeId));
    }

    /**
     * 批量设置用户标签
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
        if (!$uids && $all == 0) return app('json')->fail('缺少参数');
        if (!$lables || (count($lables) == 1 && !$lables[0])) return app('json')->fail('请选择标签');
        if ($all == 0) {
            $uids = explode(',', $uids);
            $where = [];
        }
        if ($all == 1) {
            $uids = [];
            $where = $where ? json_decode($where, true) : [];
        }
        /** @var UserLabelServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelServices::class);
        $count = $userLabelServices->getCount([['id', 'IN', $lables]]);
        if ($count != count($lables)) {
            return app('json')->fail('有用户标签不存在或被删除');
        }
        $type = 3;//批量设置用户标签
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $queueService->setQueueData($where, 'uid', $uids, $type, $lables);
        //加入队列
        BatchHandleJob::dispatch([$lables, $type, ['store_id' => $this->storeId]]);
        return app('json')->success('后台程序已执行批量设置用户标签任务!');
    }

    /**
     * 编辑其他
     * @param $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function edit_other($id)
    {
        if (!$id) return app('json')->fail('数据不存在');
        return app('json')->success($this->services->editOther((int)$id));
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
        if (!$id) return app('json')->fail('数据不存在');
        $data['adminId'] = $this->adminId;
        $data['money'] = (string)$data['money'];
        $data['integration'] = (string)$data['integration'];
        $data['is_other'] = true;
        return app('json')->success($this->services->updateInfo($id, $data) ? '修改成功' : '修改失败');
    }

    /**
     * 编辑会员信息
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function edit($id)
    {
        if (!$id) return app('json')->fail('数据不存在');
        return app('json')->success($this->services->edit($id));
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
            ['label_id', ''],
            ['group_id', 0],
            ['pwd', ''],
            ['true_pwd'],
            ['spread_open', 1]
        ]);
        if ($data['phone']) {
            if (!check_phone($data['phone'])) return app('json')->fail('手机号码格式不正确');
        }
        if ($data['card_id']) {
            if (!check_card($data['card_id'])) return app('json')->fail('请输入正确的身份证');
        }
        if ($data['pwd']) {
            if (!$data['true_pwd']) {
                return app('json')->fail('请输入确认密码');
            }
            if ($data['pwd'] != $data['true_pwd']) {
                return app('json')->fail('两次输入的密码不一致');
            }
            $data['pwd'] = md5($data['pwd']);
        } else {
            unset($data['pwd']);
        }
        unset($data['true_pwd']);
        if (!$id) return app('json')->fail('数据不存在');
        $data['adminId'] = $this->adminId;
        $data['money'] = (string)$data['money'];
        $data['integration'] = (string)$data['integration'];
        return app('json')->success($this->services->updateInfo($id, $data) ? '修改成功' : '修改失败');
    }

    /**
     * 获取单个用户信息
     * @param $id 用户id
     * @param UserServices $services
     * @return mixed
     */
    public function oneUserInfo($id, UserServices $services)
    {
        $data = $this->request->getMore([
            ['type', ''],
        ]);
        $id = (int)$id;
        if ($data['type'] == '') return app('json')->fail('缺少参数');
        return app('json')->success($services->oneUserInfo($id, $data['type']));
    }
}
