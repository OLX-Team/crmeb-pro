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
use app\services\work\WorkGroupChatAuthServices;
use app\validate\admin\work\GroupChatAuthValidata;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use GuzzleHttp\Exception\GuzzleException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\App;

/**
 * 自动拉群
 * Class GroupChatAuth
 * @package app\controller\admin\v1\work
 */
class GroupChatAuth extends AuthController
{

    /**
     * GroupChatAuth constructor.
     * @param App $app
     * @param WorkGroupChatAuthServices $services
     */
    public function __construct(App $app, WorkGroupChatAuthServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 列表
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['create_time', ''],
            ['name', ''],
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
            ['auth_group_chat', 0],
            ['chat_id', []],
            ['group_name', ''],
            ['group_num', 0],
            ['welcome_words', []],
            ['admin_user', []],
            ['owner', '']
        ]);

        validate(GroupChatAuthValidata::class)->check($data);

        $this->services->saveGroupChatAuth($data);

        return $this->success('添加自动拉群成功');
    }

    /**
     * 更新
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['auth_group_chat', 0],
            ['chat_id', []],
            ['group_name', ''],
            ['group_num', 0],
            ['label', []],
        ]);

        validate(GroupChatAuthValidata::class)->check($data);

        $this->services->saveGroupChatAuth($data, (int)$id);

        return $this->success('修改自动拉群成功');
    }

    /**
     * 查看详情
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function read($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        return $this->success($this->services->getGrouChatAuthInfo((int)$id));
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     * @throws InvalidConfigException
     * @throws GuzzleException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function delete($id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        if ($this->services->deleteGroupChatAuth((int)$id)) {
            return $this->success('删除自动拉群成功');
        } else {
            return $this->fail('删除自动拉群失败');
        }
    }
}
