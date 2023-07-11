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

namespace app\model\product\label;


use app\model\product\sku\StoreProductAttrValue;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 标签辅助表
 * Class StoreProductLabelAuxiliary
 * @package app\model\product\label
 */
class StoreProductLabelAuxiliary extends BaseModel
{
    use ModelTrait;

    /**
     * @var string
     */
    protected $key = 'id';

    /**
     * @var string
     */
    protected $name = 'store_product_label_auxiliary';

}
