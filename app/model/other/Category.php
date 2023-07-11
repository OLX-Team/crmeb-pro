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


use app\model\product\label\StoreProductLabel;
use app\model\product\specs\StoreProductSpecs;
use app\model\user\label\UserLabel;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 分类表
 * Class Category
 * @package app\model\other
 */
class Category extends BaseModel
{

    use ModelTrait;

    /**
     * 表名
     * @var string
     */
    protected $name = 'category';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 用户标签
     * @return \think\model\relation\HasMany
     */
    public function label()
    {
        return $this->hasMany(UserLabel::class, 'label_cate', 'id');
    }

    /**
     * 商品标签
     * @return \think\model\relation\HasMany
     */
    public function productLabel()
    {
        return $this->hasMany(StoreProductLabel::class, 'label_cate', 'id');
    }

    /**
     * 商品参数
     * @return \think\model\relation\HasMany
     */
    public function specs()
    {
        return $this->hasMany(StoreProductSpecs::class, 'temp_id', 'id');
    }


    /**
     *  归属人
     * @param Model $query
     * @param $value
     */
    public function searchOwnerIdAttr($query, $value)
    {
        $query->where('owner_id', $value);
    }

    /**
     * 平台类型1：平台2：门店
     * @param Model $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        $query->where('type', $value);
    }

    /**
     * 门店id类型
     * @param Model $query
     * @param $value
     */
    public function searchStoreIdAttr($query, $value)
    {
        if ($value) $query->where('store_id', $value);
    }

    /**
     * 标签类型0：用户1：客服话术
     * @param Model $query
     * @param $value
     */
    public function searchGroupAttr($query, $value)
    {
        $query->where('group', $value);
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchNotIdAttr($query, $value)
    {
        $query->where('id', '<>', $value);
    }
}
