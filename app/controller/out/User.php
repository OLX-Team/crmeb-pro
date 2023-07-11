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
namespace app\controller\out;

use app\Request;
use app\services\out\UserServices;
use app\services\user\UserAddressServices;
use app\services\user\UserMoneyServices;

/**
 * 用户类
 * Class StoreProductController
 * @package app\api\controller\store
 */
class User
{
    /**
     * 用户services
     * @var UserServices
     */
    protected $services;

    public function __construct(UserServices $services)
    {
        $this->services = $services;
    }

    /**
     * 获取用户信息
     * @param string $uid
     * @return mixed
     */
    public function index($uid)
    {
        return app('json')->success($this->services->getUserInfo($uid, 'uid,phone,nickname,now_money,integral,addres'));
    }

    /**
     * 执行编辑其他
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    public function update_other(Request $request, $id = 0)
    {
        $data = $request->postMore([
            ['money_status', 0],
            ['money', 0],
            ['integration_status', 0],
            ['integration', 0],
        ]);
        if (!$id) return $this->fail('数据不存在');
        $data['adminId'] = $request->outId;
        $data['money'] = (string)$data['money'];
        $data['integration'] = (string)$data['integration'];
        $data['is_other'] = true;
        return app('json')->success($this->services->updateInfo($id, $data) ? '修改成功' : '修改失败');
    }

    /**
     *
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function update(Request $request, $id)
    {
        $data = $request->postMore([
            ['phone', 0],
            ['addres', ''],
            ['real_name', ''],
            ['card_id', ''],
            ['birthday', '']
        ]);
        if ($data['phone']) {
            if (!check_phone($data['phone'])) return app('json')->fail('手机号码格式不正确');
        }
        if ($data['card_id']) {
            if (!check_card($data['card_id'])) return app('json')->fail('请输入正确的身份证');
        }
        if (!$id) return app('json')->fail('数据不存在');
        $data['adminId'] = $request->outId;
        return app('json')->success($this->services->updateInfo($id, $data) ? '修改成功' : '修改失败');
    }

    /**
     * 获取地址信息
     * @param Request $request
     * @param UserAddressServices $services
     * @param int $uid
     * @return mixed
     */
    public function address_list(Request $request, UserAddressServices $services, $uid = 0)
    {
        if (!$uid) return app('json')->fail('数据不存在');
        return app('json')->successful($services->getUserAddressList($uid, 'id,real_name,phone,province,city,district,detail,is_default,city_id'));
    }

    /**
     * 获取余额
     * @param Request $request
     * @return mixed
     */
    public function money(Request $request, $uid)
    {
        if (!$uid) return app('json')->fail('数据不存在');
        return app('json')->success($this->services->getUserInfo($uid, 'now_money'));
    }

    /**
     * 消费明细
     * @param UserMoneyServices $services
     * @param $uid
     * @return mixed
     */
    public function spread_commission(UserMoneyServices $services, $uid)
    {
        if (!$uid) return app('json')->fail('数据不存在');
        return app('json')->successful($services->userMoneyList($uid, 1));
    }
}
