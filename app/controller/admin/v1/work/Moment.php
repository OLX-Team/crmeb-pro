<?php


namespace app\controller\admin\v1\work;


use app\controller\admin\AuthController;
use app\services\work\WorkMomentSendResultServices;
use app\services\work\WorkMomentServices;
use think\facade\App;

/**
 * 朋友圈
 * Class Moment
 * @package app\controller\admin\v1\work
 */
class Moment extends AuthController
{

    /**
     * Moment constructor.
     * @param App $app
     * @param WorkMomentServices $services
     */
    public function __construct(App $app, WorkMomentServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 查看列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['time', ''],
            ['name', '', '', 'name_like'],
        ]);

        return $this->success($this->services->getList($where));
    }

    /**
     * 保存
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['type', 0],
            ['user_ids', []],
            ['client_type', 0],
            ['client_tag_list', []],
            ['welcome_words', []],
            ['send_type', 0],
            ['send_time', 0],
        ]);
        if ($data['type'] && !$data['user_ids']) {
            return $this->fail('请选择成员');
        }
        if ($data['client_type'] && !$data['client_tag_list']) {
            return $this->fail('请选择客户标签');
        }
        if ($data['send_type']) {
            if (!$data['send_time']) {
                return $this->fail('请选择定时发送时间');
            }
            $data['send_time'] = strtotime($data['send_time']);
        }
        $this->services->createMomentTask($data);
        return $this->success('添加成功');
    }

    /**
     * 查看任务发送详情
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        return $this->success($this->services->getMomentInfo((int)$id));
    }

    /**
     * 删除朋友圈
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delete($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        if ($this->services->deleteMoment($id)) {
            return $this->success('删除成功');
        } else {
            return $this->fail('删除失败');
        }
    }

    /**
     * 朋友圈发送列表
     * @param WorkMomentSendResultServices $services
     * @return mixed
     */
    public function sendResultList(WorkMomentSendResultServices $services)
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['userid', []],
            ['moment_id', '']
        ]);
        $where['userid'] = array_filter($where['userid']);
        if (!$where['moment_id']) {
            return $this->success(['list' => [], 'count' => 0]);
        }

        return $this->success($services->getList($where));
    }
}
