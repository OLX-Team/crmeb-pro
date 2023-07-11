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
namespace app\controller\admin\v1\marketing\live;

use app\controller\admin\AuthController;
use app\services\activity\live\LiveAnchorServices;
use think\facade\App;

/**
 * 直播间主播
 * Class LiveAnchor
 * @package app\controller\admin\store
 */
class LiveAnchor extends AuthController
{
    /**
     * LiveAnchor constructor.
     * @param App $app
     * @param LiveAnchorServices $services
     */
    public function __construct(App $app, LiveAnchorServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 列表
     * @return mixed
     */
    public function list()
    {
        $where = $this->request->postMore([
            ['kerword', ''],
        ]);
        return $this->success($this->services->getList($where));
    }

    /**
     * 添加修改表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function add()
    {
        list($id) = $this->request->getMore([
            ['id', 0],
        ], true);
        return $this->success($this->services->add((int)$id));
    }

    /**
     * 保存标签表单数据
     * @param int $id
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['name', ''],
            ['wechat', ''],
            ['phone', ''],
            ['cover_img', '']
        ]);
        if (!$data['name'] = trim($data['name'])) return $this->fail('名称不能为空！');
        if (!$data['wechat'] = trim($data['wechat'])) return $this->fail('微信号不能为空！');
        if (!$data['phone'] = trim($data['phone'])) return $this->fail('手机号不能为空！');
        if (!check_phone($data['phone'])) return app('json')->fail('请输入正确手机号');
        if (!$data['cover_img'] = trim($data['cover_img'])) return $this->fail('请选择主播图像');
        $res = $this->services->save((int)$data['id'], $data);
        if ($res === true) {
            return $this->success('保存成功', ['auth' => false]);
        }
        return $this->success('请先去小程序认证主播', $res);
    }

    /**
     * 删除
     * @param $id
     * @throws \Exception
     */
    public function delete()
    {
        list($id) = $this->request->getMore([
            ['id', 0],
        ], true);
        if (!$id) return $this->fail('数据不存在');
        $this->services->delAnchor((int)$id);
        return $this->success('刪除成功！');
    }

    /**
     * 设置会员等级显示|隐藏
     *
     * @return json
     */
    public function setShow($id = '', $is_show = '')
    {
        if ($is_show == '' || $id == '') return $this->fail('缺少参数');
        return $this->success($this->services->setShow((int)$id, (int)$is_show));
    }

    /**
     * 同步主播
     * @return mixed
     */
    public function syncAnchor()
    {
        $this->services->syncAnchor();
        return app('json')->success('同步成功');
    }
}
