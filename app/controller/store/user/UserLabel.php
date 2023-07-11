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

use app\controller\store\AuthController;
use app\services\user\label\UserLabelCateServices;
use app\services\user\label\UserLabelRelationServices;
use app\services\user\label\UserLabelServices;
use think\facade\App;

/**
 * 用户标签
 * Class UserLabel
 * @package app\controller\store\user
 */
class UserLabel extends AuthController
{

    /**
     * UserLabel constructor.
     * @param App $app
     * @param UserLabelServices $service
     */
    public function __construct(App $app, UserLabelServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 标签列表
     * @param int $label_cate
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index($label_cate = 0)
    {
        return app('json')->success($this->service->getList(['label_cate' => $label_cate, 'store_id' => $this->storeId]));
    }

    /**
     * 添加标签表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create()
    {
        return app('json')->success($this->service->add(0, $this->type, $this->storeId));
    }

    /**
     * 修改标签表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function edit()
    {
        [$id] = $this->request->getMore([
            ['id', 0],
        ], true);
        return app('json')->success($this->service->add((int)$id, $this->type, $this->storeId));
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
            ['label_cate', 0],
            ['label_name', ''],
        ]);
        if (!$data['label_name'] = trim($data['label_name'])) return app('json')->fail('会员标签不能为空！');
        $this->service->save((int)$data['id'], $data, $this->type, $this->storeId);
        return app('json')->success('保存成功');
    }

    /**
     * 删除
     * @param $id
     * @return
     * @throws \Exception
     */
    public function delete()
    {
        list($id) = $this->request->getMore([
            ['id', 0],
        ], true);
        if (!$id) return app('json')->fail('数据不存在');
        $this->service->delLabel((int)$id);
        return app('json')->success('刪除成功！');
    }

    /**
     * 标签分类
     * @param UserLabelCateServices $services
     * @param $uid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLabel(UserLabelCateServices $services, $uid)
    {
        return app('json')->success($services->getUserLabel((int)$uid, $this->type, $this->storeId));
    }

    /**
     * 设置用户标签
     * @param UserLabelRelationServices $services
     * @param $uid
     * @return mixed
     */
    public function setUserLabel(UserLabelRelationServices $services, $uid)
    {
        [$labels, $unLabelIds] = $this->request->postMore([
            ['label_ids', []],
            ['un_label_ids', []]
        ], true);
        if (!count($labels) && !count($unLabelIds)) {
            return app('json')->fail('请先添加标签');
        }
        if ($services->setUserLable($uid, $labels, $this->storeId) && $services->unUserLabel($uid, $unLabelIds, $this->storeId)) {
            return app('json')->success('设置成功');
        } else {
            return app('json')->fail('设置失败');
        }
    }
}
