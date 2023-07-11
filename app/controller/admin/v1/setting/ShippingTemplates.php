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
use app\services\product\shipping\ShippingTemplatesServices;
use app\services\other\SystemCityServices;
use think\facade\App;

/**
 * 运费模板
 * Class ShippingTemplates
 * @package app\controller\admin\v1\setting
 */
class ShippingTemplates extends AuthController
{
    /**
     * 构造方法
     * ShippingTemplates constructor.
     * @param App $app
     * @param ShippingTemplatesServices $services
     */
    public function __construct(App $app, ShippingTemplatesServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 运费模板列表
     * @return mixed
     */
    public function temp_list()
    {
        $where = $this->request->getMore([
            [['name', 's'], '']
        ]);
        return $this->success($this->services->getShippingList($where));
    }

    /**
     * 修改
     * @return string
     * @throws \Exception
     */
    public function edit($id)
    {
        return $this->success($this->services->getShipping((int)$id));
    }

    /**
     * 保存或者修改
     * @param int $id
     */
    public function save($id = 0)
    {
        $data = $this->request->postMore([
            [['region_info', 'a'], []],
            [['appoint_info', 'a'], []],
            [['no_delivery_info', 'a'], []],
            [['sort', 'd'], 0],
            [['type', 'd'], 0],
            [['name', 's'], ''],
            [['appoint', 'd'], 0],
            [['no_delivery', 'd'], 0]
        ]);
        $this->validate($data, \app\validate\admin\setting\ShippingTemplatesValidate::class, 'save');
        $temp['name'] = $data['name'];
        $temp['type'] = $data['type'];
        $temp['appoint'] = $data['appoint'] && $data['appoint_info'] ? 1 : 0;
        $temp['no_delivery'] = $data['no_delivery'] && $data['no_delivery_info'] ? 1 : 0;
        $temp['sort'] = $data['sort'];
        $temp['add_time'] = time();
        $this->services->save((int)$id, $temp, $data);
        event('product.shipping.update');
        return $this->success((int)$id ? '修改成功！' : '添加成功!');
    }

    /**
     * 删除运费模板
     */
    public function delete()
    {
        [$id] = $this->request->getMore([
            [['id', 'd'], 0],
        ], true);
        if ($id == 1) {
            return $this->fail('默认模板不能删除');
        } else {
            $this->services->detete($id);
            event('product.shipping.update');
            return $this->success('删除成功');
        }
    }

    /**
     * 城市数据
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function city_list(SystemCityServices $services)
    {
        return $this->success($services->getShippingCity());
    }
}
