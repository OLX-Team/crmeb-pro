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
namespace app\controller\api\v1\user;

use app\Request;
use app\services\other\CityAreaServices;
use app\services\user\UserAddressServices;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

/**
 * 用户地址类
 * Class UserController
 * @package app\api\controller\store
 */
class UserAddressController
{
    protected $services = NUll;

    /**
     * UserController constructor.
     * @param UserAddressServices $services
     */
    public function __construct(UserAddressServices $services)
    {
        $this->services = $services;
    }

    /**
     * 地址 获取单个
     * @param Request $request
     * @param $id
     * @return mixed
     * @throws DbException
     */
    public function address(Request $request, CityAreaServices $services, $id)
    {
        if (!$id) {
            return app('json')->fail('缺少参数');
        }
        $data = $this->services->address((int)$id);
        if (!$data) {
            return app('json')->fail('地址不存在');
        }
        $address = implode('/', [$data['province'] ?? '', $data['city'] ?? '', $data['district'] ?? '', $data['street'] ?? '']);
        $city = $services->searchCity(compact('address'));
        if ($city) {
            $where = [['id', 'in', array_merge([$city['id']], explode('/', trim($city->path, '/')))]];
            $data['city_list'] = $services->getCityList($where, 'id as value,id,name as label,parent_id as pid');
        }
        return app('json')->successful($data);
    }

    /**
     * 地址列表
     * @param Request $request
     * @param $page
     * @param $limit
     * @return mixed
     */
    public function address_list(Request $request)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->getUserAddressList($uid));
    }

    /**
     * 设置默认地址
     *
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     */
    public function address_default_set(Request $request)
    {
        list($id) = $request->getMore([['id', 0]], true);
        if (!$id || !is_numeric($id)) return app('json')->fail('参数错误!');
        $uid = (int)$request->uid();
        $res = $this->services->setDefault($uid, (int)$id);
        if (!$res)
            return app('json')->fail('地址不存在!');
        else
            return app('json')->successful();
    }

    /**
     * 获取默认地址
     * @param Request $request
     * @return mixed
     * @throws DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws ModelNotFoundException
     */
    public function address_default(Request $request)
    {
        $uid = (int)$request->uid();
        $defaultAddress = $this->services->getUserDefaultAddress($uid);
        if ($defaultAddress) {
            $defaultAddress = $defaultAddress->toArray();
            return app('json')->successful('ok', $defaultAddress);
        }
        return app('json')->successful('empty', []);
    }

    /**
     * 修改 添加地址
     * @param Request $request
     * @return mixed
     */
    public function address_edit(Request $request)
    {
        $addressInfo = $request->postMore([
            ['address', []],
            ['is_default', false],
            ['real_name', ''],
            ['post_code', ''],
            ['phone', ''],
            ['detail', ''],
            [['id', 'd'], 0],
            [['type', 'd'], 0],
            ['latitude', ''],
            ['longitude', ''],
        ]);
        if (!isset($addressInfo['address']['province']) || !$addressInfo['address']['province'] || $addressInfo['address']['province'] == '省') return app('json')->fail('收货地址格式错误!');
        if (!isset($addressInfo['address']['city']) || !$addressInfo['address']['city'] || $addressInfo['address']['city'] == '市') return app('json')->fail('收货地址格式错误!');
        if (!isset($addressInfo['address']['district']) || !$addressInfo['address']['district'] || $addressInfo['address']['district'] == '区') return app('json')->fail('收货地址格式错误!');
        if (!isset($addressInfo['address']['city_id']) && $addressInfo['type'] == 0) {
            return app('json')->fail('收货地址格式错误!请重新选择!');
        }
        if (!$addressInfo['detail']) return app('json')->fail('请填写详细地址!');
        $uid = (int)$request->uid();
        $addressInfo['upgrade'] = 1;
        $re = $this->services->editAddress($uid, $addressInfo);
        if ($re) {
            return app('json')->success($re['type'] == 'edit' ? $re['msg'] : $re['data']);
        } else {
            return app('json')->fail('处理失败');
        }

    }

    /**
     * 删除地址
     *
     * @param Request $request
     * @return mixed
     */
    public function address_del(Request $request)
    {
        list($id) = $request->postMore([['id', 0]], true);
        if (!$id || !is_numeric($id)) return app('json')->fail('参数错误!');
        $uid = (int)$request->uid();
        $re = $this->services->delAddress($uid, (int)$id);
        if ($re)
            return app('json')->successful();
        else
            return app('json')->fail('删除地址失败!');
    }
}
