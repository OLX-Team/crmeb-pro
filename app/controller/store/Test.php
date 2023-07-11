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

namespace app\controller\store;


use app\services\order\cashier\CashierOrderServices;
use app\services\other\CityAreaServices;
use app\services\other\SystemCityServices;
use crmeb\services\HttpService;
use crmeb\utils\Captcha;

class Test
{

    public function index(CashierOrderServices $services)
    {
        dump($services->mpSort([
            [
                'coupon_price' => '50.00',
                'id' => 1,
            ],
            [
                'coupon_price' => '10.00',
                'id' => 3,
            ],
            [
                'coupon_price' => '100.00',
                'id' => 5,
            ],
            [
                'coupon_price' => '20.00',
                'id' => 2,
            ]
        ], 'coupon_price'));
    }

    public function code()
    {
        return app()->make(Captcha::class)->create();
    }
}
