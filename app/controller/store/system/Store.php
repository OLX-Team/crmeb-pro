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

namespace app\controller\store\system;


use app\validate\store\system\StoreValidata;
use think\facade\App;
use app\controller\store\AuthController;
use app\services\store\SystemStoreServices;

/**
 * 门店
 * Class Store
 * @package app\controller\store\system
 */
class Store extends AuthController
{

    public function __construct(App $app, SystemStoreServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 获取门店信息
     * @return mixed
     */
    public function info()
    {
        return app('json')->success($this->services->getStoreInfo($this->request->storeId()));
    }

    /**
     * 修改当前登录门店
     * @return mixed
     */
    public function update()
    {
        $data = $this->request->postMore([
            ['image', ''],
            ['name', ''],
            ['introduction', ''],
            ['phone', ''],
            ['address', ''],
            ['province', ''],
            ['city', ''],
            ['area', ''],
            ['street', ''],
            ['valid_range', 0],
            ['detailed_address', ''],
            ['is_show', ''],
            ['day_time', []],
            ['latitude', ''],
            ['longitude', ''],
            ['is_store', 0],
        ]);

        validate(StoreValidata::class)->check($data);
        [$data['day_start'], $data['day_end']] = $data['day_time'];
        $data['day_time'] = $data['day_time'] ? implode('-', $data['day_time']) : '';
        $data['valid_range'] = bcmul($data['valid_range'], '1000', 0);
        if ($this->services->update($this->request->storeId(), $data)) {
            return app('json')->success('修改成功');
        } else {
            return app('json')->fail('修改失败');
        }
    }

    /**
     * 获取门店财务信息
     * @return mixed
     */
    public function getFinanceInfo()
    {
        $storeInfo = $this->services->get((int)$this->storeId);
        if (!$storeInfo) {
            return app('json')->fail('门店不存在');
        }
        return app('json')->success($storeInfo->toArray());
    }

    /**
     * 设置门店财务信息
     * @return mixed
     */
    public function setFinanceInfo()
    {
        $data = $this->request->postMore([
            ['bank_code', ''],
            ['bank_address', ''],
            ['alipay_account', ''],
            ['alipay_qrcode_url', ''],
            ['wechat', ''],
            ['wechat_qrcode_url', '']
        ]);
        $storeInfo = $this->services->get((int)$this->storeId);
        if (!$storeInfo) {
            return app('json')->fail('门店不存在');
        }
        if ($this->services->update($storeInfo['id'], $data)) {
            return app('json')->success('设置成功');
        } else {
            return app('json')->fail('设置失败');
        }
    }

}
