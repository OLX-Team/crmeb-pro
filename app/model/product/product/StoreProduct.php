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

namespace app\model\product\product;

use app\model\product\brand\StoreBrand;
use app\model\product\sku\StoreProductAttrValue;
use app\model\store\product\Product;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use app\model\activity\coupon\StoreCouponProduct;
use think\Model;

/**
 *  商品Model
 * Class StoreProduct
 * @package app\model\product\product
 */
class StoreProduct extends BaseModel
{
    use  ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'store_product';

    /**
     * 配送信息
     * @param $value
     * @return string
     */
    protected function setDeliveryTypeAttr($value)
    {
        if ($value) {
            return is_array($value) ? implode(',', $value) : $value;
        }
        return '';
    }

    /**
     * 配送信息
     * @param $value
     * @return array|false|string[]
     */
    protected function getDeliveryTypeAttr($value)
    {
        if ($value) {
            return is_string($value) ? explode(',', $value) : $value;
        }
        return [];
    }

    /**
     * 自定义表单信息
     * @param $value
     * @return array|mixed
     */
    protected function getCustomFormAttr($value)
    {
        if ($value) {
            return is_string($value) ? json_decode($value, true) : $value;
        }
        return [];
    }

    /**
     * 商品标签
     * @param $value
     * @return array|mixed
     */
    protected function getLabelIdAttr($value)
    {
        if ($value) {
            return is_string($value) ? array_map('intval', array_filter(explode(',', $value))) : $value;
        }
        return [];
    }

    /**
     * @param $value
     * @return array|mixed
     */
    public function getStoreLabelIdAttr($value)
    {
        if ($value) {
            return is_string($value) ? array_map('intval', array_filter(explode(',', $value))) : $value;
        }
        return [];
    }

    /**
     * 商品保障服务
     * @param $value
     * @return array|mixed
     */
    protected function getEnsureIdAttr($value)
    {
        if ($value) {
            return is_string($value) ? array_map('intval', array_filter(explode(',', $value))) : $value;
        }
        return [];
    }

    /**
     * 参数信息
     * @param $value
     * @return array|mixed
     */
    protected function getSpecsAttr($value)
    {
        if ($value) {
            return is_string($value) ? json_decode($value, true) : $value;
        }
        return [];
    }


    /**
     * 一对一关联
     * 门店商品
     * @return \think\model\relation\HasOne
     */
    public function storeBranchProduct()
    {
        return $this->hasOne(Product::class, 'product_id', 'id');
    }

    /**
     * 一对一关联
     * 商品关联商品商品详情
     * @return \think\model\relation\HasOne
     */
    public function description()
    {
        return $this->hasOne(StoreDescription::class, 'product_id', 'id')->where('type', 0)->bind(['description']);
    }

    /**
     * 一对一关联
     * 商品关联商品商品品牌
     * @return \think\model\relation\HasOne
     */
    public function brand()
    {
        return $this->hasOne(StoreBrand::class, 'id', 'brand_id')->where(['is_show' => 1, 'is_del' => 0])->bind(['brand_name']);
    }


    /**
     * 一对多关联
     * 商品关联优惠卷模板id
     * @return \think\model\relation\HasMany
     */
    public function couponId()
    {
        return $this->hasMany(StoreCouponProduct::class, 'product_id', 'id');
    }

    /**
     * 优惠券名称一对多
     * @return \think\model\relation\HasMany
     */
    public function coupons()
    {
        return $this->hasMany(StoreProductCoupon::class, 'product_id', 'id');
    }

    /**
     * 评论一对多
     * @return \think\model\relation\HasMany
     */
    public function star()
    {
        return $this->hasMany(StoreProductReply::class, 'product_id', 'id')->where('is_del', 0)->field('product_score,product_id');
    }

    /**
     * 分类一对多
     * @return \think\model\relation\HasMany
     */
    public function cateName()
    {
        return $this->hasMany(StoreProductCate::class, 'product_id', 'id')->with('cateName');
    }

    /**
     * sku一对多
     * @return \think\model\relation\HasMany
     */
    public function attrValue()
    {
        return $this->hasMany(StoreProductAttrValue::class, 'product_id', 'id')->where('type', 0);
    }


    /**
     * 轮播图获取器
     * @param $value
     * @return array|mixed
     */
    public function getSliderImageAttr($value)
    {
        return is_string($value) ? json_decode($value, true) : [];
    }

    /**
     * @param $value
     * @return bool
     */
    public function getStoreDeliveryAttr($value)
    {
        //1=快递，2=门店自提，3=门店配送
        return in_array(3, $this->delivery_type);
    }

    /**
     * @param $value
     * @return bool
     */
    public function getStoreMentionAttr($value)
    {
        //1=快递，2=门店自提，3=门店配送
        return in_array(2, $this->delivery_type);
    }

    /**
     * @param $value
     * @return bool
     */
    public function getExpressDeliveryAttr($value)
    {
        //1=快递，2=门店自提，3=门店配送
        return in_array(1, $this->delivery_type);
    }

    /**
     * 商品类型搜索器
     * @param $query
     * @param $value
     */
    public function searchProductTypeAttr($query, $value)
    {
        if ($value !== '') {
            if (is_array($value)) {
                $query->whereIn('product_type', $value);
            } else {
                $query->where('product_type', $value ?? 0);
            }
        }
    }

    /**
     * 是否是svip商品搜索器
     * @param $query
     * @param $value
     */
    public function searchIsVipProductAttr($query, $value)
    {
        if ($value !== '') {
            switch ($value) {
                case -1:
                    break;
                case 0:
                    $query->where('is_vip_product', 0);
                    break;
                case 1:
                    $query->where('is_vip_product', 1);
                    break;
                default:
                    $query->where('is_vip_product', 0);
                    break;
            }
        }
    }

    /**
     * 是否预售搜索器
     * @param $query
     * @param $value
     */
    public function searchIsPresaleProductAttr($query, $value)
    {
        if ($value !== '') $query->where('is_presale_product', $value ?? 0);
    }

    /**
     * 是否显示搜索器
     * @param $query
     * @param $value
     */
    public function searchIsShowAttr($query, $value)
    {
        $query->where('is_show', $value ?? 1);
    }

    /**
     * @param Model $query
     * @param $value
     */
    public function searchIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('id', $value);
        } else {
            $query->where('id', $value);
        }
    }

    /**
     * 是否删除搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        $query->where('is_del', $value ?: 0);
    }

    /**
     * 条形码搜索器
     * @param Model $query
     * @param $value
     */
    public function searchBarCodeAttr($query, $value)
    {
        if ($value !== '') $query->where(function ($query) use ($value) {
            $query->where('bar_code', $value)->whereOr('id', 'IN', function ($q) use ($value) {
                $q->name('store_product_attr_value')->field('product_id')->where('bar_code', $value)->select();
            });
        });
    }

    /**
     * 商户ID搜索器
     * @param Model $query
     * @param $value
     */
    public function searchMerIdAttr($query, $value)
    {
        $query->where('mer_id', $value ?? 0);
    }

    /**
     * keyword搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchStoreNameAttr($query, $value, $data)
    {
        if ($value != '' && !isset($data['store_id'])) {
            $field = 'id|keyword|store_name|store_info|bar_code';
            if (is_string($value)) {
                $query->whereLike($field, htmlspecialchars("%" . trim($value) . "%"));
            } elseif (is_array($value) && count($value) > 0) {
                $query->where(function ($q) use ($value, $field) {
                    $data = [];
                    foreach ($value as $k) {
                        $data[] = [$field, 'like', "%" . trim($k) . "%"];
                    }
                    $q->where($data);
                });
            }
        }
    }

    /**
     * 新品商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsNewAttr($query, $value)
    {
        if ($value) $query->where('is_new', $value);
    }

    /**
     * 优惠商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsBenefitAttr($query, $value)
    {
        $query->where('is_benefit', $value ?? 1);
    }

    /**
     * 热卖商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsHotAttr($query, $value)
    {
        $query->where('is_hot', $value ?? 1);
    }

    /**
     * 精品商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsBestAttr($query, $value)
    {
        $query->where('is_best', $value ?? 1);
    }

    /**
     * 精品商品搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchIsGoodAttr($query, $value)
    {
        $query->where('is_good', $value ?? 1);
    }

    /**
     * 用户标签搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchLabelIdAttr($query, $value)
    {
        if ($value !== '') $query->whereFindInSet('label_id', $value);
    }

    /**
     * 商品标签搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchStoreLabelIdAttr($query, $value)
    {
        if ($value !== '') $query->whereFindInSet('store_label_id', $value);
    }

    /**
     * 保障服务搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchEnsureIdAttr($query, $value)
    {
        if ($value !== '') $query->whereFindInSet('ensure_id', $value);
    }

    /**
     * SPU搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchSpuAttr($query, $value)
    {
        $query->where('spu', $value);
    }

    /**
     * 库存搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchStockAttr($query, $value)
    {
        $query->where('stock', $value);
    }

    /**
     * 分类搜索器
     * @param Model $query
     * @param int $value
     */
    public function searchCateIdAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('id', function ($query) use ($value) {
                    $query->name('store_product_cate')->where('cate_id', 'IN', $value)->field('product_id')->select();
                });
            } else {
                $query->whereFindInSet('cate_id', $value);
            }
        }
    }

    /**
     * 商品数量条件搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchTypeAttr($query, $value, $data)
    {
        switch ((int)$value) {
            case 1:
                $query->where(['is_show' => 1, 'is_del' => 0]);
                break;
            case 2:
                $query->where(['is_show' => 0, 'is_del' => 0]);
                break;
            case 3:
                $query->where(['is_del' => 0]);
                break;
            case 4:
                $query->where(['is_del' => 0])->where(function ($query) {
                    $query->whereIn('id', function ($query) {
                        $query->name('store_product_attr_value')->where('stock', 0)->where('type', 0)->field('product_id')->select();
                    })->whereOr('stock', 0);
                });
                break;
            case 5:
                if (isset($data['store_stock']) && $data['store_stock']) {
                    $store_stock = $data['store_stock'];
                    $query->where(['is_show' => 1, 'is_del' => 0])->where('stock', '<=', $store_stock)->where('stock', '>', 0);
                } else {
                    $query->where(['is_show' => 1, 'is_del' => 0])->where('stock', '>', 0);
                }
                break;
            case 6:
                $query->where(['is_del' => 1]);
                break;
            case 7://回收站 & 下架商品
                $query->where(function ($q) {
                    $q->where(['is_del' => 1])->whereOr('is_show', 0);
                });
                break;
        };
    }

    /**
     * 品牌搜索器
     * @param Model $query
     * @param $value
     */
    public function searchBrandIdAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('brand_id', $value);
            } else {
                $query->where('brand_id', $value);
            }
        }
    }
}
