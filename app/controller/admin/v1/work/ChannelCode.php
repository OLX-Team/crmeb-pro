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

namespace app\controller\admin\v1\work;


use app\controller\admin\AuthController;
use app\Request;
use app\services\work\WorkChannelCategoryServices;
use app\services\work\WorkChannelCodeServices;
use app\services\work\WorkClientFollowServices;
use app\validate\admin\work\WechatWorkChannelCodeValidata;
use think\facade\App;

/**
 * 渠道码
 * Class ClientCode
 * @package app\controller\admin\v1\work
 */
class ChannelCode extends AuthController
{

    /**
     * ClientCode constructor.
     * @param App $app
     * @param WorkChannelCodeServices $services
     */
    public function __construct(App $app, WorkChannelCodeServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 获取渠道码列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['name', ''],
            ['type', ''],
            ['cate_id', '']
        ]);

        return $this->success($this->services->getList($where));
    }

    /**
     * 获取渠道码详情
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        return $this->success($this->services->getChannelInfo((int)$id));
    }

    /**
     * 保存渠道码
     * @param Request $request
     * @return mixed
     */
    public function save(Request $request)
    {
        $data = $request->postMore([
            ['type', 0],
            ['name', ''],
            ['cate_id', 0],
            ['label_id', []],
            ['reserve_userid', []],
            ['userids', []],
            ['skip_verify', 0],//自动加好友
            ['add_upper_limit', 0],//员工添加上限
            ['welcome_words', []],
            ['status', 0],
            ['welcome_type', 0],
            ['cycle', []],
            ['useridLimit', []],
        ]);

        $this->validate($data, WechatWorkChannelCodeValidata::class);

        if ($data['type'] && !count($data['cycle'])) {
            return $this->fail('至少设置一个周期规则');
        }
        if ($data['add_upper_limit'] && !$data['useridLimit']) {
            return $this->fail('请设置添加上限');
        }

        if ($this->services->saveChanne($data)) {
            return $this->success('保存成功');

        } else {
            return $this->fail('保存失败');
        }

    }

    /**
     * 修改渠道码
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function update(Request $request, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        $data = $request->postMore([
            ['type', 0],
            ['name', ''],
            ['cate_id', 0],
            ['label_id', []],
            ['reserve_userid', []],
            ['userids', []],
            ['skip_verify', 0],//自动加好友
            ['add_upper_limit', 0],//员工添加上限
            ['welcome_words', []],
            ['status', 0],
            ['cycle', []],
            ['welcome_type', 0],
            ['useridLimit', []],
        ]);

        $this->validate($data, WechatWorkChannelCodeValidata::class);

        if ($data['type'] && !count($data['cycle'])) {
            return $this->fail('至少设置一个周期规则');
        }
        if ($data['add_upper_limit'] && !$data['useridLimit']) {
            return $this->fail('请设置添加上限');
        }

        if ($this->services->saveChanne($data, $id)) {
            return $this->success('修改成功');

        } else {
            return $this->fail('修改失败');
        }
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function status($id, $status)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        if ($this->services->update($id, ['status' => $status])) {
            return $this->success('修改成功');
        } else {
            return $this->fail('修改失败');
        }
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        if ($this->services->deleteChannel((int)$id)) {
            return $this->success('删除成功');
        } else {
            return $this->fail('删除失败');
        }
    }

    /**
     * 获取扫描渠道码添加的客户列表
     * @param WorkClientFollowServices $services
     * @param $id
     * @return mixed
     */
    public function getClientList(WorkClientFollowServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $name = $this->request->get('name', '');
        return $this->success($services->getChannelCodeClientList((int)$id, $name));
    }

    /**
     * 批量移动分类
     * @return mixed
     */
    public function bactchMoveCate()
    {
        [$ids, $cateId] = $this->request->postMore([
            ['ids', []],
            ['cate_id', 0]
        ], true);

        if (!$ids) {
            return $this->fail('请选择需要移动的渠道码');
        }
        if (!$cateId) {
            return $this->fail('请选择分类');
        }

        if ($this->services->update(['id' => $ids], ['cate_id' => $cateId])) {
            return $this->success('移动成功');
        } else {
            return $this->fail('移动失败');
        }
    }
}
