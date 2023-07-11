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

namespace app\controller\admin\v1\store;

use app\common\controller\Recharge as CommonRecharge;
use app\controller\admin\AuthController;
use app\services\user\UserRechargeServices;
use think\facade\App;

/**
 * 充值
 * Class Recharge
 * @package app\controller\admin\v1\store
 */
class Recharge extends AuthController
{

    use CommonRecharge;

    /**
     * @var UserRechargeServices
     */
    protected $services;

    /**
     * Order constructor.
     * @param App $app
     * @param UserRechargeServices $services
     */
    public function __construct(App $app, UserRechargeServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['data', ''],
            ['paid', 1],
            ['nickname', ''],
            ['store_id', -1]
        ]);
        $where['store_id'] = $where['store_id'] ?: -1;
        return $this->success($this->services->getRechargeList($where, '*', 0, ['staff']));
    }

    /**
     * 获取备注
     * @param $id
     * @return mixed
     */
    public function getRemark($id)
    {
        return $this->success(['remarks' => $this->services->value(['id' => $id], 'remarks')]);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function remarks($id)
    {
        $data = $this->request->param('remark', '');

        $this->services->update(['id' => $id], ['remarks' => $data]);

        return $this->success('备注提交成功');
    }

}
