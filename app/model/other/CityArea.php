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

namespace app\model\other;


use crmeb\basic\BaseModel;

/**
 * 城市数据（包含街道）
 * Class CityArea
 * @package app\model\other
 */
class CityArea extends BaseModel
{

    /**
     * @var string
     */
    protected $name = 'city_area';

    /**
     * @var string
     */
    protected $key = 'id';

    /**
     * @return \think\model\relation\HasOne
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id');
    }

}
