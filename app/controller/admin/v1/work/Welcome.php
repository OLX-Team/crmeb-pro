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
use app\services\work\WorkWelcomeServices;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\App;

/**
 * 企业微信欢迎语
 * Class Welcome
 * @package app\controller\admin\v1\work
 */
class Welcome extends AuthController
{

    /**
     * Welcome constructor.
     * @param App $app
     * @param WorkWelcomeServices $services
     */
    public function __construct(App $app, WorkWelcomeServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['add_time', ''],
            ['userids', []]
        ]);
        return $this->success($this->services->getList($where));
    }

    /**
     * 欢迎语
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        return $this->success($this->services->getWelcomeInfo((int)$id));
    }

    /**
     * 保存
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['type', 0],
            ['content', ''],
            ['attachments', []],
            ['userids', []],
            ['sort', 0]
        ]);

        if (!$data['attachments'] && !$data['content']) {
            return $this->fail('欢迎语内容和欢迎语消息体不能同时为空');
        }
        if (!$data['content']) {
            return $this->fail('缺少消息内容');
        }
        if ($data['type'] == 1 && !$data['userids']) {
            return $this->fail('至少选择一个成员');
        }

        if ($this->services->saveWelcome($data)) {
            return $this->success('添加成功');
        } else {
            return $this->fail('添加失败');
        }
    }

    /**
     * 修改
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['type', 0],
            ['content', ''],
            ['attachments', []],
            ['userids', []],
            ['sort', 0]
        ]);

        if (!$data['attachments'] && !$data['content']) {
            return $this->fail('欢迎语内容和欢迎语消息体不能同时为空');
        }
        if (!$data['content']) {
            return $this->fail('缺少消息内容');
        }
        if ($data['type'] == 1 && !$data['userids']) {
            return $this->fail('至少选择一个成员');
        }

        if ($this->services->saveWelcome($data, (int)$id)) {
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

        if ($this->services->deleteWelcome($id)) {
            return $this->success('删除成功');
        } else {
            return $this->fail('删除失败');
        }
    }
}
