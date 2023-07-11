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

use app\controller\admin\AuthController;
use app\services\user\label\UserLabelCateServices;
use app\services\user\label\UserLabelRelationServices;
use app\services\user\label\UserLabelServices;
use crmeb\services\wechat\config\WorkConfig;
use think\facade\App;

/**
 * 用户标签控制器
 * Class UserLabel
 * @package app\controller\admin\v1\user
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
        $this->services = $service;
    }

    /**
     * 标签列表
     * @return mixed
     */
    public function index($label_cate = 0)
    {
        return $this->success($this->services->getList(['label_cate' => $label_cate, 'type' => 1]));
    }

    /**
     * 获取带分类的用户标签列表
     * @param UserLabelCateServices $userLabelCateServices
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function tree_list(UserLabelCateServices $userLabelCateServices)
    {
        $cate = $userLabelCateServices->getLabelCateAll();
        $data = [];
        $label = [];
        if ($cate) {
            foreach ($cate as $value) {
                $data[] = [
                    'id' => $value['id'] ?? 0,
                    'value' => $value['id'] ?? 0,
                    'label_cate' => 0,
                    'label_name' => $value['name'] ?? '',
                    'label' => $value['name'] ?? '',
                    'store_id' => $value['store_id'] ?? 0,
                    'type' => $value['type'] ?? 1,
                ];
            }
            $label = $this->services->getList(['type' => 1]);
            $label = $label['list'] ?? [];
            if ($label) {
                foreach ($label as &$item) {
                    $item['label'] = $item['label_name'];
                    $item['value'] = $item['id'];
                }
            }
        }
        return $this->success($this->services->get_tree_children($data, $label));
    }

    /**
     * 添加修改标签表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function add()
    {
        [$id, $label_cate] = $this->request->getMore([
            ['id', 0],
            ['label_cate', 0]
        ], true);
        return $this->success($this->services->add((int)$id, 1, 0, $label_cate));
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
        if (!$data['label_name'] = trim($data['label_name'])) return $this->fail('会员标签不能为空！');
        $this->services->save((int)$data['id'], $data);
        return $this->success('保存成功');
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
        $this->services->delLabel((int)$id);
        return $this->success('刪除成功！');
    }

    /**
     * 标签分类
     * @param UserLabelCateServices $services
     * @return mixed
     */
    public function getUserLabel(UserLabelCateServices $services, $uid)
    {
        [$uids, $all, $where] = $this->request->postMore([
            ['uids', []],
            ['all', 0],
            ['where', ""],
        ], true);
        if (count($uids) == 1) {
            $uid = $uids[0] ?? 0;
        }
        return $this->success($services->getUserLabel((int)$uid));
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
            return $this->fail('请先添加用户标签');
        }
        if ($services->setUserLable($uid, $labels) && $services->unUserLabel($uid, $unLabelIds)) {
            return $this->success('设置成功');
        } else {
            return $this->fail('设置失败');
        }
    }

    /**
     * 同步客户标签
     * @param WorkConfig $config
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function synchroWorkLabel(WorkConfig $config)
    {
        if (!$config->get('corpId')) {
            return $this->fail('请先配置企业微信ID');
        }
        $config = $config->getAppConfig(WorkConfig::TYPE_USER);
        if (empty($config['secret'])) {
            return $this->fail('请先配置企业微信客户secret');
        }
        if ($this->services->authWorkClientLabel()) {
            return $this->success('已加入消息队列，进行同步。请稍等片刻');
        } else {
            return $this->fail('加入消息队列失败');
        }
    }

}
