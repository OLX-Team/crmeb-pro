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
namespace app\controller\admin\v1\agent;


use app\controller\admin\AuthController;
use app\services\agent\AgentLevelServices;
use app\services\agent\AgentLevelTaskServices;
use think\facade\App;


/**
 * Class AgentLevel
 * @package app\controller\admin\v1\agent
 */
class AgentLevel extends AuthController
{
    /**
     * AgentLevel constructor.
     * @param App $app
     * @param AgentLevelServices $services
     */
    public function __construct(App $app, AgentLevelServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['keyword', '']
        ]);
        return $this->success($this->services->getLevelList($where));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        return $this->success($this->services->createForm());
    }

    /**
     * 保存新建的资源
     *
     * @return \think\Response
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['grade', 0],
            ['image', ''],
            ['one_brokerage', 0],
            ['two_brokerage', 0],
            ['status', 0]]);
        $data['name'] = trim($data['name']);
        if (!$data['name']) return $this->fail('请输入等级名称');
        if (!$data['grade']) return $this->fail('请输入等级');
        if (!$data['image']) return $this->fail('请选择等级图标');

        //获取后台一级返佣比例
        $storeBrokerageRatio = sys_config('store_brokerage_ratio');
        //获取二级返佣比例
        $storeBrokerageTwo = sys_config('store_brokerage_two');
        $one = (float)bcmul((string)$storeBrokerageRatio, (string)bcdiv((string)$data['one_brokerage'], '100', 2), 4);
        $two = (float)bcmul((string)$storeBrokerageTwo, (string)bcdiv((string)$data['two_brokerage'], '100', 2), 4);

        if ($two > $one) {
            return $this->fail('二级上浮整体返佣比例不能大于一级');
        }
        $grade = $this->services->get(['grade' => $data['grade'], 'is_del' => 0]);
        if ($grade) {
            return $this->fail('当前等级已存在');
        }
        $data['add_time'] = time();
        $this->services->save($data);
        return $this->success('添加等级成功!');
    }

    /**
     * 显示指定的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param int $id
     * @return \think\Response
     */
    public function edit($id)
    {
        return $this->success($this->services->editForm((int)$id));
    }

    /**
     * 保存更新的资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['grade', 0],
            ['image', ''],
            ['one_brokerage', 0],
            ['two_brokerage', 0],
            ['status', 0]]);
        $data['name'] = trim($data['name']);
        if (!$data['name']) return $this->fail('请输入等级名称');
        if (!$data['grade']) return $this->fail('请输入等级');
        if (!$data['image']) return $this->fail('请选择等级图标');

        //获取后台一级返佣比例
        $storeBrokerageRatio = sys_config('store_brokerage_ratio');
        //获取二级返佣比例
        $storeBrokerageTwo = sys_config('store_brokerage_two');
        $one = (float)bcmul((string)$storeBrokerageRatio, (string)bcdiv((string)$data['one_brokerage'], '100', 2), 4);
        $two = (float)bcmul((string)$storeBrokerageTwo, (string)bcdiv((string)$data['two_brokerage'], '100', 2), 4);
        if ($two > $one) {
            return $this->fail('二级上浮整体分拥比例不能大于一级');
        }
        if (!$levelInfo = $this->services->getLevelInfo((int)$id)) return $this->fail('编辑的等级不存在!');
        $grade = $this->services->get(['grade' => $data['grade'], 'is_del' => 0]);
        if ($grade && $grade['id'] != $id) {
            return $this->fail('当前等级已存在');
        }

        $levelInfo->name = $data['name'];
        $levelInfo->grade = $data['grade'];
        $levelInfo->image = $data['image'];
        $levelInfo->one_brokerage = $data['one_brokerage'];
        $levelInfo->two_brokerage = $data['two_brokerage'];
        $levelInfo->status = $data['status'];
        $levelInfo->save();
        return $this->success('修改成功!');
    }

    /**
     * 删除指定资源
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delete($id)
    {
        if (!$id) return $this->fail('参数错误，请重新打开');
        $levelInfo = $this->services->getLevelInfo((int)$id);
        if ($levelInfo) {
            $res = $this->services->update($id, ['is_del' => 1]);
            if (!$res)
                return $this->fail('删除失败,请稍候再试!');
            /** @var AgentLevelTaskServices $agentLevelTaskServices */
            $agentLevelTaskServices = app()->make(AgentLevelTaskServices::class);
            $agentLevelTaskServices->update(['level_id' => $id], ['is_del' => 1]);
        }
        return $this->success('删除成功!');
    }

    /**
     * 修改状态
     * @param int $id
     * @param string $status
     * @return mixed
     */
    public function set_status($id = 0, $status = '')
    {
        if ($status == '' || $id == 0) return $this->fail('参数错误');
        $this->services->update($id, ['status' => $status]);
        return $this->success($status == 0 ? '隐藏成功' : '显示成功');
    }
}
