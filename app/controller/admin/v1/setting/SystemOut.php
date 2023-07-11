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
namespace app\controller\admin\v1\setting;

use app\controller\admin\AuthController;
use app\services\out\OutAccountServices;
use think\facade\App;


/**
 * 对外接口账户
 * Class SystemCity
 * @package app\controller\admin\v1\setting
 */
class SystemOut extends AuthController
{
    /**
     * 构造方法
     * SystemCity constructor.
     * @param App $app
     * @param OutAccountServices $services
     */
    public function __construct(App $app, OutAccountServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 账号信息
     * @return string
     * @throws \Exception
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['name', '', ''],
            ['status', ''],
        ]);
        return $this->success($this->services->getList($where));
    }

    /**
     * 修改状态
     * @param string $status
     * @param string $id
     * @return mixed
     */
    public function set_status($id = '', $status = '')
    {
        if ($status == '' || $id == '') return $this->fail('缺少参数');
        $this->services->update($id, ['status' => $status]);
        return $this->success($status == 1 ? '开启成功' : '禁用成功');
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if ($id == '') return $this->fail('缺少参数');
        $this->services->update($id, ['is_del' => 1]);
        return $this->success('删除成功!');
    }

    public function info($id)
    {
        return $this->success($this->services->getOne(['id' => $id]));
    }

    /**
     * 添加保存
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            [['appid', 's'], ''],
            [['appsecret', 's'], ''],
            [['title', 's'], ''],
        ]);
        if (!$data['appid']) {
            return $this->fail('参数错误');
        }
        if ($this->services->getOne(['appid' => $data['appid']])) return $this->fail('账号重复');
        if (!$data['appsecret']) {
            unset($data['appsecret']);
        } else {
            $data['appsecret'] = password_hash($data['appsecret'], PASSWORD_DEFAULT);
        }
        $data['add_time'] = time();
        if (!$this->services->save($data)) {
            return $this->fail('添加失败');
        } else {
            return $this->success('添加成功');
        }
    }

    /**
     * 修改保存
     * @param string $id
     * @return mixed
     */
    public function update($id = '')
    {
        $data = $this->request->postMore([
            [['appid', 's'], ''],
            [['appsecret', 's'], ''],
            [['title', 's'], ''],
        ]);
        if (!$data['appid']) {
            return $this->fail('参数错误');
        }
        if (!$data['appsecret']) {
            unset($data['appsecret']);
        } else {
            $data['appsecret'] = password_hash($data['appsecret'], PASSWORD_DEFAULT);
        }
        if (!$this->services->getOne(['id' => $id])) return $this->fail('没有此账号');

        $res = $this->services->update($id, $data);
        if (!$res) {
            return $this->fail('修改失败');
        } else {
            return $this->success('修改成功!');
        }
    }
}
