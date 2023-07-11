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
namespace app\controller\admin\v1\application\wechat;


use app\controller\admin\AuthController;
use app\services\wechat\WechatCardServices;
use think\facade\App;

/**
 * 微信卡券
 * Class WechatCard
 * @package app\controller\admin\v1\application\wechat
 */
class WechatCard extends AuthController
{

    /**
     * WechatCard constructor.
     * @param App $app
     * @param WechatCardServices $services
     */
    public function __construct(App $app, WechatCardServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    public function index()
    {
        $where = $this->request->postMore([
            ['kerword', ''],
            ['status', '']
        ]);
        return app('json')->success($this->services->getList($where));
    }

    /**
     * 获取微信会员卡信息
     * @return mixed
     */
    public function info()
    {
        return $this->success($this->services->getInfo());
    }


    public function create()
    {
        return $this->success($this->services->createForm());
    }

    /**
     * 添加｜编辑会员卡
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['brand_name', ''],
            ['title', ''],
            ['logo_url', ''],
            ['service_phone', ''],
            ['background_pic_url', ''],
            ['color', ''],
            ['notice', ''],
            ['description', ''],
            ['center_title', ''],
            ['center_sub_title', ''],
            ['center_url', ''],
            ['prerogative', ''],
            ['custom_cell', []],
        ]);
        if (!$data['brand_name']) return app('json')->fail('请输入商户名称');
        if (!$data['title']) return app('json')->fail('请输入卡券名称');
        if (!$data['logo_url']) return app('json')->fail('请选择LOGO');
        if (!$data['service_phone']) return app('json')->fail('请输入正确手机号');
        $this->services->save($data);
        return app('json')->success('设置成功');
    }

    public function edit($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $card = $this->dao->get($id);
        if (!$card) {
            throw new AdminException('卡券不存在!');
        }
        return $this->success($this->services->createForm($card));
    }


    public function setShow($id, $is_show)
    {
        if (!$id)
            return app('json')->fail('数据不存在');
        return app('json')->success($this->services->isShow((int)$id, $is_show));
    }

    public function delete($id)
    {
        if (!$id)
            return app('json')->fail('数据不存在');
        $this->services->delete($id);
        return app('json')->success('删除成功');
    }

}

