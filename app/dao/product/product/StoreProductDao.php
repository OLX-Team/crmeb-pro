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

namespace app\dao\product\product;


use app\dao\BaseDao;
use app\model\product\product\StoreProduct;
use think\facade\Config;

/**
 * Class StoreProductDao
 * @package app\dao\product\product
 */
class StoreProductDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProduct::class;
    }

    /**
     * 条件获取数量
     * @param array $where
     * @return int
     */
    public function getCount(array $where)
    {
        return $this->search($where)
            ->when(isset($where['store_name']) && $where['store_name'], function ($query) use ($where) {
                if (isset($where['field_key']) && $where['field_key'] && in_array($where['field_key'], ['product_id', 'bar_code'])) {
                    if ($where['field_key'] == 'product_id') {
                        $query->where('id', trim($where['store_name']));
                    } else {
                        $query->where(function ($query) use ($where) {
                            $query->where('bar_code', trim($where['store_name']))->whereOr('id', 'IN', function ($q) use ($where) {
                                $q->name('store_product_attr_value')->field('product_id')->where('bar_code', trim($where['store_name']))->select();
                            });
                        });
                    }
                } else {
                    $query->where(function ($q) use ($where) {
                        $q->where('id|store_name|bar_code|keyword', 'LIKE', '%' . trim($where['store_name']) . '%')->whereOr('id', 'IN', function ($q) use ($where) {
                            $q->name('store_product_attr_value')->field('product_id')->where('bar_code', trim($where['store_name']))->select();
                        });
                    });
                }
            })->when(isset($where['sid']) && $where['sid'], function ($query) use ($where) {
                $query->whereIn('id', function ($query) use ($where) {
                    $query->name('store_product_cate')->where('cate_id', $where['sid'])->field('product_id')->select();
                });
            })->when(isset($where['cid']) && $where['cid'], function ($query) use ($where) {
                $query->whereIn('id', function ($query) use ($where) {
                    $query->name('store_product_cate')->whereIn('cate_id', function ($query) use ($where) {
                        $query->name('store_category')->where('pid', $where['cid'])->field('id')->select();
                    })->field('product_id')->select();
                });
            })->when(isset($where['ids']) && $where['ids'], function ($query) use ($where) {
                if (isset($where['type']) && $where['type'] && isset($where['store_id']) && $where['store_id']) {
                    $query->whereNotIn('id', function ($que) use ($where) {
                        $que->name('store_branch_product')->where(['is_show' => 0, 'store_id' => $where['store_id']])->field(['product_id'])->select();
                    });
                } else {
                    if (!isset($where['type']))
                        $query->where('id', 'in', $where['ids']);
                }
            })->when(isset($where['not_ids']) && $where['not_ids'], function ($query) use ($where) {
                $query->whereNotIn('id', $where['not_ids']);
            })->when(isset($where['is_live']) && $where['is_live'] == 1, function ($query) use ($where) {
                $query->whereNotIn('id', function ($query) {
                    $query->name('live_goods')->where('is_del', 0)->where('audit_status', '<>', 3)->field('product_id')->select();
                });
            })->count();
    }

    /**
     * 获取商品列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, int $page = 0, int $limit = 0, string $order = '')
    {
        $prefix = Config::get('database.connections.' . Config::get('database.default') . '.prefix');
        return $this->search($where)->order(($order ? $order . ' ,' : '') . 'sort desc,id desc')
            ->when(isset($where['store_name']) && $where['store_name'], function ($query) use ($where) {
                if (isset($where['field_key']) && $where['field_key'] && in_array($where['field_key'], ['product_id', 'bar_code'])) {
                    if ($where['field_key'] == 'product_id') {
                        $query->where('id', trim($where['store_name']));
                    } else {
                        $query->where(function ($query) use ($where) {
                            $query->where('bar_code', trim($where['store_name']))->whereOr('id', 'IN', function ($q) use ($where) {
                                $q->name('store_product_attr_value')->field('product_id')->where('bar_code', trim($where['store_name']))->select();
                            });
                        });
                    }
                } else {
                    $query->where('id|store_name|bar_code|keyword', 'LIKE', '%' . trim($where['store_name']) . '%');
                }
            })->when($page != 0 && $limit != 0, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->when(isset($where['store_id']) && $where['store_id'], function ($query) use ($where) {
                $query->with(['storeBranchProduct' => function ($querys) use ($where) {
                    $querys->where('store_id', $where['store_id'])->bind([
                        'branch_sales' => 'sales',
                        'branch_stock' => 'stock',
                        'branch_is_show' => 'is_show'
                    ]);
                }]);
            })->when(isset($where['ids']), function ($query) use ($where) {
                if (isset($where['type']) && $where['type'] && $where['type'] == 1 && isset($where['store_id']) && $where['store_id']) {
                    $query->whereNotIn('id', function ($que) use ($where) {
                        $que->name('store_branch_product')->where(['is_show' => 0, 'store_id' => $where['store_id']])->field(['product_id'])->select();
                    });
                } else {
                    if (!isset($where['type']))
                        $query->where('id', 'in', $where['ids']);
                }
            })->field([
                '*',
                '(SELECT count(*) FROM `' . $prefix . 'store_product_relation` WHERE `relation_id` = `' . $prefix . 'store_product`.`id` AND `type` = \'collect\') as collect',
                '(SELECT count(*) FROM `' . $prefix . 'store_product_relation` WHERE `relation_id` = `' . $prefix . 'store_product`.`id` AND `type` = \'like\') as likes',
                '(SELECT SUM(stock) FROM `' . $prefix . 'store_product_attr_value` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `type` = 0) as stock',
                //                '(SELECT SUM(sales) FROM `' . $prefix . 'store_product_attr_value` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `type` = 0) as sales',
                '(SELECT count(*) FROM `' . $prefix . 'store_visit` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `product_type` = \'product\') as visitor',
            ])->select()->toArray();
    }

    /**
     * 获取商品详情
     * @param int $id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfo(int $id)
    {
        return $this->search()->with('coupons')->find($id);
    }

    /**
     * 获取门店商品
     * @param $where
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBranchProduct($where)
    {
        return $this->search($where)
            ->when(isset($where['store_id']) && $where['store_id'], function ($query) use ($where) {
                $query->with(['storeBranchProduct' => function ($querys) use ($where) {
                    $querys->where('store_id', $where['store_id']);
                }]);
            })->find();
    }

    /**
     * 条件获取商品列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @param array $field
     * @param string $order
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSearchList(array $where, int $page = 0, int $limit = 0, array $field = ['*'], string $order = '', array $with = ['couponId', 'description'])
    {
        if (isset($where['star'])) $with[] = 'star';
        return $this->search($where)->with($with)->when($page != 0 && $limit != 0, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->when(isset($where['store_name']) && $where['store_name'], function ($query) use ($where) {
            if (isset($where['field_key']) && $where['field_key'] && in_array($where['field_key'], ['product_id', 'bar_code'])) {
                if ($where['field_key'] == 'product_id') {
                    $query->where('id', trim($where['store_name']));
                } else {
                    $query->where(function ($query) use ($where) {
                        $query->where('bar_code', trim($where['store_name']))->whereOr('id', 'IN', function ($q) use ($where) {
                            $q->name('store_product_attr_value')->field('product_id')->where('bar_code', trim($where['store_name']))->select();
                        });
                    });
                }
            } else {
                $query->where(function ($q) use ($where) {
                    $q->where('id|store_name|bar_code|keyword', 'LIKE', '%' . trim($where['store_name']) . '%')->whereOr('id', 'IN', function ($q) use ($where) {
                        $q->name('store_product_attr_value')->field('product_id')->where('bar_code', trim($where['store_name']))->select();
                    });
                });
            }
        })->when(isset($where['sid']) && $where['sid'], function ($query) use ($where) {
            $query->whereIn('id', function ($query) use ($where) {
                $query->name('store_product_cate')->where('cate_id', $where['sid'])->field('product_id')->select();
            });
        })->when(isset($where['cid']) && $where['cid'], function ($query) use ($where) {
            $query->whereIn('id', function ($query) use ($where) {
                $query->name('store_product_cate')->whereIn('cate_id', function ($query) use ($where) {
                    $query->name('store_category')->where('pid', $where['cid'])->field('id')->select();
                })->field('product_id')->select();
            });
        })->when(isset($where['ids']) && $where['ids'], function ($query) use ($where) {
            if ((isset($where['priceOrder']) && $where['priceOrder'] != '') || (isset($where['salesOrder']) && $where['salesOrder'] != '')) {
                $query->whereIn('id', $where['ids']);
            } else {
                $query->whereIn('id', $where['ids'])->orderField('id', $where['ids'], 'asc');
            }
        })->when(isset($where['not_ids']) && $where['not_ids'], function ($query) use ($where) {
            $query->whereNotIn('id', $where['not_ids']);
        })->when(isset($where['is_live']) && $where['is_live'] == 1, function ($query) use ($where) {
            $query->whereNotIn('id', function ($query) {
                $query->name('live_goods')->where('is_del', 0)->where('audit_status', '<>', 3)->field('product_id')->select();
            });
        })->when(isset($where['priceOrder']) && $where['priceOrder'] != '', function ($query) use ($where) {
            if ($where['priceOrder'] === 'desc') {
                $query->order("price desc");
            } else {
                $query->order("price asc");
            }
        })->when(isset($where['salesOrder']) && $where['salesOrder'] != '', function ($query) use ($where) {
            if ($where['salesOrder'] === 'desc') {
                $query->order("sales desc");
            } else {
                $query->order("sales asc");
            }
        })->when(!isset($where['ids']), function ($query) use ($where, $order) {
            if (isset($where['timeOrder']) && $where['timeOrder'] == 1) {
                $query->order('id desc');
            } else if ($order == 'rand') {
                $query->orderRand();
            } else if ($order) {
                $query->orderRaw($order);
            } else {
                $query->order('sort desc,id desc');
            }
        })->when(isset($where['use_min_price']) && $where['use_min_price'], function ($query) use ($where) {
            if (is_array($where['use_min_price']) && count($where['use_min_price']) == 2) {
                $query->where('price', $where['use_min_price'][0] ?? '=', $where['use_min_price'][1] ?? 0);
            }
        })->when(!$page && $limit, function ($query) use ($limit) {
            $query->limit($limit);
        })->field($field)->select()->toArray();
    }

    /**商品列表
     * @param array $where
     * @param $limit
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductLimit(array $where, $limit, $field)
    {
        return $this->search($where)->field($field)->order('val', 'desc')->limit($limit)->select()->toArray();

    }

    /**
     * 根据id获取商品数据
     * @param array $ids
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function idByProductList(array $ids, string $field)
    {
        return $this->getModel()->whereIn('id', $ids)->field($field)->select()->toArray();
    }

    /**
     * 获取推荐商品
     * @param array $where
     * @param $field
     * @param int $num
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRecommendProduct(array $where, $field, int $num = 0, int $page = 0, int $limit = 0)
    {
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        if (!is_array($field)) {
            $where[$field] = 1;
        }
        return $this->search($where)->with(['couponId', 'star'])
            ->field(['id', 'image', 'store_name', 'store_info', 'cate_id', 'price', 'ot_price', 'IFNULL(sales,0) + IFNULL(ficti,0) as sales', 'unit_name', 'sort', 'activity', 'stock', 'vip_price', 'is_vip'])
            ->when(is_array($field), function ($query) use ($field) {
                $query->where($field);
            })
            ->when($num, function ($query) use ($num) {
                $query->limit($num);
            })
            ->when($page, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })
            ->when($limit, function ($query) use ($limit) {
                $query->limit($limit);
            })
            ->order('sort DESC, id DESC')->select()->toArray();

    }

    /**
     * 获取加入购物车的商品
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductCartList(array $where, int $page, int $limit, array $field = ['*'])
    {
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        return $this->search($where)->when($page, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->field($field)->order('sort DESC,id DESC')->select()->toArray();
    }

    /**
     * 获取用户购买热销榜单
     * @param array $where
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserProductHotSale(array $where, int $limit = 20)
    {
        return $this->search($where)->field(['IFNULL(sales,0) + IFNULL(ficti,0) as sales', 'store_name', 'image', 'id', 'price', 'ot_price', 'stock'])->limit($limit)->order('sales desc')->select()->toArray();
    }

    /**
     * 通过商品id获取商品分类
     * @param array $productIds
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function productIdByCateId(array $productIds)
    {
        return $this->search(['id' => $productIds])->with('cateName')->field('id')->select()->toArray();
    }

    /**
     * @param array $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductListByWhere(array $where, $field)
    {
        return $this->search($where)->field($field)->select()->toArray();
    }

    /**
     * 搜索条件获取字段column
     * @param array $where
     * @param string $field
     * @param string $key
     * @return array
     */
    public function getColumnList(array $where, string $field = 'brand_id', string $key = 'id')
    {
        return $this->search($where)
            ->when(isset($where['sid']) && $where['sid'], function ($query) use ($where) {
                $query->whereIn('id', function ($query) use ($where) {
                    $query->name('store_product_cate')->where('cate_id', $where['sid'])->field('product_id')->select();
                });
            })->when(isset($where['cid']) && $where['cid'], function ($query) use ($where) {
                $query->whereIn('id', function ($query) use ($where) {
                    $query->name('store_product_cate')->whereIn('cate_id', function ($query) use ($where) {
                        $query->name('store_category')->where('pid', $where['cid'])->field('id')->select();
                    })->field('product_id')->select();
                });
            })->when(isset($where['ids']) && $where['ids'], function ($query) use ($where) {
                $query->whereIn('id', $where['ids']);
            })->field($field)->column($field, $key);
    }

    /**
     * 自动上下架
     * @param int $is_show
     * @return \crmeb\basic\BaseModel
     */
    public function overUpperShelves($is_show = 0)
    {
        return $this->getModel()->where(['is_del' => 0])
            ->when(in_array($is_show, [0, 1]), function ($query) use ($is_show) {
                if ($is_show == 1) {
                    $query->where('is_show', 0)->where('auto_on_time', '<>', 0)->where('auto_on_time', '<=', time())->where('auto_off_time', '>', time());
                } else {
                    $query->where('is_show', 1)->where('auto_off_time', '<>', 0)->where('auto_off_time', '<', time());
                }
            })->update(['is_show' => $is_show]);
    }

    /**
     * 获取预售列表
     * @param $where
     * @param $page
     * @param $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPresaleList($where, $page, $limit)
    {
        $model = $this->getModel()->where('is_presale_product', 1)->where('is_del', 0)->where('is_show', 1)->where(function ($query) use ($where) {
            switch ($where['time_type']) {
                case 1:
                    $query->where('presale_start_time', '>', time());
                    break;
                case 2:
                    $query->where('presale_start_time', '<=', time())->where('presale_end_time', '>=', time());
                    break;
                case 3:
                    $query->where('presale_end_time', '<', time());
                    break;
            }
        });
        $count = $model->count();
        $list = $model->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order('add_time desc')->select()->toArray();
        return compact('list', 'count');
    }

    /**
     * 获取使用某服务保障商品数量
     * @param int $ensure_id
     * @return int
     */
    public function getUseEnsureCount(int $ensure_id)
    {
        return $this->getModel()->whereFindInSet('ensure_id', $ensure_id)->count();
    }

    /**
     * 保存数据
     * @param array $data
     * @return mixed|\think\Collection
     * @throws \Exception
     */
    public function saveAll(array $data)
    {
        return $this->getModel()->saveAll($data);
    }

    /**
     * 同步商品保存获取id
     * @param $data
     * @return int|string
     */
    public function ErpProductSave($data)
    {
        return $this->getModel()->insertGetId($data);
    }
}
