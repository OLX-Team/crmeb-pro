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

namespace app\controller\api\pc;


use app\Request;
use app\services\pc\UserServices;
use app\services\user\UserBrokerageServices;
use app\services\user\UserMoneyServices;

/**
 * pc端用户信息类
 * Class UserController
 * @package app\controller\api\pc
 */
class UserController
{
    protected $services;

    public function __construct(UserServices $services)
    {
        $this->services = $services;
    }

    /**
     * 用户记录0：所有余额1：余额消费2：余额充值3：佣金4：提现
     * @param Request $request
     * @param $type
     * @return mixed
     */
    public function getBalanceRecord(Request $request, $type)
    {
        $data = $request->getMore([
            ['start', 0],
            ['stop', 0],
            ['keyword', ''],
        ]);
        $uid = (int)$request->uid();
        switch ((int)$type) {
            case 0:
            case 1:
            case 2:
                /** @var UserMoneyServices $services */
                $services = app()->make(UserMoneyServices::class);
                $data = $services->userMoneyList($uid, (int)$type);
                break;
            case 3:
                /** @var UserBrokerageServices $services */
                $services = app()->make(UserBrokerageServices::class);
                $data = $services->userBrokerageList($uid, $data);
                break;
            case 4:
                /** @var UserBrokerageServices $services */
                $services = app()->make(UserBrokerageServices::class);
                $data = $services->userExtractList($uid);
                break;
            default:
                $data = [];
        }
        return app('json')->successful($data);
    }

    /**
     * 获取收藏列表
     * @param Request $request
     * @return mixed
     */
    public function getCollectList(Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->getCollectList($uid));
    }
}
