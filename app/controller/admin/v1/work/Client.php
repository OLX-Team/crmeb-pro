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
use app\services\user\UserServices;
use app\services\work\WorkClientServices;
use crmeb\services\wechat\config\WorkConfig;
use think\facade\App;

/**
 * 客户管理
 * Class Client
 * @package app\controller\admin\v1\work
 */
class Client extends AuthController
{

    /**
     * Client constructor.
     * @param App $app
     * @param WorkClientServices $services
     */
    public function __construct(App $app, WorkClientServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 企微客户列表
     * @param WorkConfig $config
     * @return mixed
     */
    public function index(WorkConfig $config)
    {
        $where = $this->request->getMore([
            ['time', ''],//添加时间
            ['userid', []],//所属客服
            ['label', []],//客户标签
            ['name', ''],
            ['field_key', ''],//客户搜索
            ['gender', 0],//性别
            ['status', 0],//客户状态
            ['state', 0],//0=扫码，1=自行添加
        ]);
        $where['corp_id'] = $config->get('corpId');
        $where['timeKey'] = 'create_time';
        return $this->success($this->services->getList($where));
    }

    /**
     * 非企微客户列表
     * @param UserServices $services
     * @return mixed
     */
    public function userList(UserServices $services)
    {
        $where = $this->request->getMore([
            ['time', '', '', 'user_time'],//添加时间
            ['name', '', '', 'nickname'],
            ['field_key', ''],//客户搜索
            ['gender', 0, '', 'sex'],//性别
        ]);

        return $this->success($services->index($where));
    }

    /**
     * 同步客户
     * @return mixed
     */
    public function synch()
    {
        $this->services->authGetExternalcontact();
        return $this->success('已加入消息队列,请稍后查看');
    }

    /**
     * 修改客户
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['remark', '']
        ]);
        if (!$id) {
            return $this->fail('缺少参数');
        }

        $this->services->update($id, $data);
        return $this->success('修改成功');
    }

    /**
     * 批量设置标签
     * @return mixed
     */
    public function batchLabel()
    {
        [$labelId, $removeTag, $userId, $isAll] = $this->request->postMore([
            ['add_tag', []],
            ['removeTag', []],
            ['userid', []],
            ['is_all', 0]
        ], true);
        if (!$labelId) {
            return $this->fail('请选择标签');
        }
        if (!$isAll && !$userId) {
            return $this->fail('请选择客户');
        }
        $where = $this->request->getMore([
            ['time', ''],//添加时间
            ['userid', []],//所属客服
            ['label', []],//客户标签
            ['name', ''],
            ['field_key', ''],//客户搜索
            ['gender', 0],//性别
            ['status', 0],//客户状态
            ['state', 0],//0=扫码，1=自行添加
        ]);
        $this->services->synchBatchLabel($labelId, $removeTag, $userId, $where, (int)$isAll);
        return $this->success('已加入消息队列');
    }

    /**
     * @return mixed
     */
    public function count()
    {
        $where = $this->request->postMore([
            ['userid', []],
            ['time', ''],
            ['label', []],
            ['notLabel', []],
            ['is_all', 0]
        ]);

        return $this->success(['sum_count' => $this->services->getUserIdsByCount($where)]);
    }

}
