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
declare (strict_types=1);

namespace app\services\activity\promotions;

use app\dao\activity\promotions\StorePromotionsDao;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderComputedServices;
use app\services\BaseServices;
use app\services\product\label\StoreProductLabelServices;
use app\services\product\product\StoreProductServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\user\label\UserLabelServices;
use app\services\order\StoreOrderServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreCartServices;
use app\services\product\category\StoreCategoryServices;
use crmeb\exceptions\AdminException;
use think\exception\ValidateException;
use \crmeb\traits\OptionTrait;


/**
 * 促销活动
 * Class StorePromotionsServices
 * @package app\services\activity\promotions
 * @method getList(array $where, string $field = '*', int $page = 0, int $limit = 0, array $with = []) 获取列表
 */
class StorePromotionsServices extends BaseServices
{
    use OptionTrait;
    /**
     * 活动类型
     * @var string[]
     */
    protected $promotionsType = [
        1 => '限时折扣',
        2 => '第N件N折',
        3 => '满减满折',
        4 => '满送',
    ];

    /**
     * 优惠内容数据
     * @var array
     */
    protected $promotionsData = [
        'threshold_type' => 1,//门槛类型1:满N元2:满N件
        'threshold' => 0,//优惠门槛
        'discount_type' => 1,//优惠类型1:满减2:满折
        'n_piece_n_discount' => 3,//n件n折类型：1:第二件半件2:买1送1 3:自定义
        'discount' => 0,//优惠
        'give_integral' => 0,//赠送积分
        'give_coupon_id' => [],//赠送优惠券ID
        'give_product_id' => [],//赠送商品ID
    ];

    /**
     * StorePromotionsServices constructor.
     * @param StorePromotionsDao $dao
     */
    public function __construct(StorePromotionsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取打折折扣 || 优惠折扣
     * @param $num
     * @param int $unit
     * @param int $type 1:打折折扣 2:优惠折扣
     * @return float
     */ 
    public function computedDiscount($num, int $unit = 100, int $type = 1)
    {   
        if ((float)$num < 0) {
            $num = 0;
        } elseif ((float)$num > 100) {
            $num = 100;
        }
        $discount = bcdiv((string)$num, (string)$unit, 2);
        if ($type == 2) {//优惠折扣 打9折扣优惠就是1折
            $discount = bcsub('1', (string)$discount, 2);
        }
        return (float)$discount;
    }


    /**
 	*  获取优惠活动标题，内容详情
	* @param int $type
	* @param int $promotions_cate
	* @param array $promotions
	* @return array
	 */
    public function getPromotionsDesc(int $type, int $promotions_cate, array $promotions)
    {
        $title = '';
        $desc = [];
        if ($promotions) {
            switch ($type) {
                case 1:
                    $title = '限时折扣';
					$base = '限时打' . $this->computedDiscount($promotions[0]['discount'] ?? 0, 10) . '折';
					if (isset($promotions['is_limit']) && isset($promotions['limit_num']) && (int)$promotions['is_limit'] && (int)$promotions['limit_num']) {
						$base .= '，每人限购' . (int)$promotions['limit_num'] . '件';
					}
                    $desc[] = $base;
                    break;
                case 2:
                    switch ($promotions[0]['n_piece_n_discount'] ?? 3) {
                        case 1:
                            $title = '第二件半价';
                            break;
                        case 2:
                            $title = '买1送1';
                            break;
                        case 3:
                            $title = '第' . floatval($promotions[0]['threshold'] ?? 0) . '件' . $this->computedDiscount($promotions[0]['discount'] ?? 0, 10) . '折';
                            break;
                    }
                    $desc[] = '买' . floatval($promotions[0]['threshold'] ?? 0) . '件商品，其中一件享' . $this->computedDiscount($promotions[0]['discount'] ?? 0, 10) . '折优惠';
                    break;
                case 3:
                    $title = '满减满折';
                    foreach ($promotions as $p) {
						if ($promotions_cate == 2) {
							$give = '每满' . floatval($p['threshold'] ?? 0);
						} else {
							$give = '满' . floatval($p['threshold'] ?? 0);
						}
                        $give .= $p['threshold_type'] == 1 ? '元' : '件';
                        $give .= $p['discount_type'] == 1 ? ('减' . floatval($p['discount'] ?? 0) . '元') : '打' . $this->computedDiscount($p['discount'] ?? 0, 10) . '折';
                        $desc[] = $give;
                    }
                    break;
                case 4:
                    $title = '满送活动';
                    foreach ($promotions as $p) {
						if ($promotions_cate == 2) {
							$base = '每满' . floatval($p['threshold'] ?? 0);
						} else {
							$base = '满' . floatval($p['threshold'] ?? 0);
						}
						$base .= $p['threshold_type'] == 1 ? '元送' : '件送';
                        if ($p['give_integral']) {
                            $desc[] = $base . floatval($p['give_integral'] ?? 0) . '积分';
                        }
                        if ($p['give_coupon_id']) {
                            $desc[] = $base . '优惠券';
                        }
                        if ($p['give_product_id']) {
							$desc[] = $base . '赠品';
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        return [$title, $desc];
    }

    /**
     * 获取商品所属活动
     * @param array $productIds
     * @param int $promotions_type
     * @param string $field
     * @param array $with
     * @param string $group
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductsPromotions(array $productIds, int $promotions_type = 0, string $field = '*', array $with = [], string $group = '')
    {
		if (!$productIds) {
			return  [];
		}
		$where = [];
		$where['type'] = 1;
		$where['store_id'] = 0;
		$where['pid'] = 0;
		$where['is_del'] = 0;
		$where['promotionsTime'] = true;
//			$where['product_id'] = $productIds;
		$where['status'] = 1;
		if ($promotions_type) $where['promotions_type'] = $promotions_type;
		$ids = [];
		foreach ($productIds as $id) {
			$where['product_id'] = [$id];
			$promotions = $this->dao->getList($where, 'id');
			if ($promotions) {
				$pids = array_column($promotions, 'id');
				$ids = array_merge($ids, $pids);
			}
		}
		$ids = array_unique($ids);
		$result = [];
		if($ids) {
			$order = 'promotions_type asc,update_time desc';
			unset($where['product_id']);
			$where['ids'] = $ids;
			$promotions = $this->dao->getList($where, $field, 0, 0, $with, $order);

			if ($promotions) {
				$data = $this->promotionsData;
				$data['giveCoupon'] = [];
				$data['giveProducts'] = [];
				$preType = [];
				foreach ($promotions as &$item) {
					if(!isset($item['promotions'])){
						$item['promotions'] = [];
					}
					if ($group && in_array($item['promotions_type'], $preType)) {
						continue;
					}
					$preType[] = $item['promotions_type'];
					$first = array_merge($data, array_intersect_key($item, $data));
					array_unshift($item['promotions'], $first);
					$item['promotions'] = $this->handelPromotions($item['promotions']);
					$result[] = $item;
				}
			}
		}
        return $result;
    }

    /**
     * 获取商品所有优惠活动 所属活动详情
     * @param array $productIds
     * @param int $promotions_type
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductsPromotionsDetail(array $productIds, int $promotions_type = 0, string $field = '*', array $with = [])
    {
        $promotions = $this->getProductsPromotions($productIds, $promotions_type, $field, $with);
        $productDetails = [];
        $promotionsDetails = [];
        if ($promotions) {
            foreach ($promotions as $info) {
                foreach ($productIds as $productId) {
                    $id = $info['id'];
                    $pIds = is_string($info['product_id']) ? explode(',', $info['product_id']) : $info['product_id'];
                    switch ($info['product_partake_type']) {
                        case 1:
                            $productDetails[$productId][] = $id;
                            $promotionsDetails[$id][] = $productId;
                            break;
                        case 2:
                            if (in_array($productId, $pIds)) {
                                $productDetails[$productId][] = $id;
                                $promotionsDetails[$id][] = $productId;
                            }
                            break;
                        case 3:
                            $products = $info['products'] ?? [];
                            if ($products) $products = array_combine(array_column($products, 'product_id'), $products);
                            if (!in_array($productId, $pIds) || (isset($products[$productId]['is_all']) && $products[$productId]['is_all'] == 0)) {
                                $productDetails[$productId][] = $id;
                                $promotionsDetails[$id][] = $productId;
                            }
                            break;
                    }
                }
            }
        }
        return [$promotions, $productDetails, $promotionsDetails];
    }

    /**
     * 检测活动内容,格式数据
     * @param int $type
     * @param array $data
     * @return array
     */
    public function checkPromotions(int $type, array $data)
    {
        if (!$data) {
            throw new AdminException('请添加活动优惠内容');
        }
        $data = array_merge($this->promotionsData, array_intersect_key($data, $this->promotionsData));
        switch ($type) {
            case 1:
            case 2:
                $data['promotions_cate'] = 1;
                $data['discount_type'] = 2;
                $data['give_coupon_id'] = $data['give_product_id'] = [];
                if ($type == 2) {
                    $data['threshold_type'] = 2;
                    if (!$data['threshold']) {
                        throw new AdminException('请输入打折门槛');
                    }
                }
                if ($data['discount'] === '') {
                    throw new AdminException('请添加折扣');
                }
                if ($data['discount'] < 0 || $data['discount'] >= 100) {
                    throw new AdminException('折扣必须为0～99数字');
                }
                break;
            case 3:
                $data['give_coupon_id'] = $data['give_product_id'] = [];
                if (!$data['threshold']) {
                    throw new AdminException('请输入优惠门槛');
                }
                if ($data['discount'] === '') {
                    throw new AdminException($data['discount_type'] == 1 ? '请输入优惠金额' : '请输入打折折扣');
                }
                if ($data['discount_type'] == 2 && ($data['discount'] < 0 || $data['discount'] >= 100)) {
                    throw new AdminException('折扣必须为0～99数字');
                }
                break;
            case 4:
                if (!$data['threshold']) {
                    throw new AdminException('请输入优惠门槛');
                }
                if (!$data['give_integral'] && !$data['give_coupon_id'] && !$data['give_product_id']) {
                    throw new AdminException('请至少选择一项赠送内容');
                }
                if ($data['give_coupon_id']) {
                    $couponsIds = array_column($data['give_coupon_id'], 'give_coupon_id');
                    $giveCoupon = array_combine($couponsIds, $data['give_coupon_id']);
                    /** @var StoreCouponIssueServices $storeCouponServices */
                    $storeCouponServices = app()->make(StoreCouponIssueServices::class);
                    $coupons = $storeCouponServices->getValidGiveCoupons($couponsIds, 'id,is_permanent,remain_count');
                    if (!$coupons || count($coupons) != count($couponsIds)) {
                        throw new AdminException('优惠券已失效请重新选择');
                    }
                    foreach ($coupons as $coupon) {
                        if (!isset($giveCoupon[$coupon['id']]['give_coupon_num']) || !$giveCoupon[$coupon['id']]['give_coupon_num']) {
                            throw new AdminException('请输入赠送优惠券数量');
                        }
                        if ($coupon['is_permanent'] == 0 && $coupon['remain_count'] < $giveCoupon[$coupon['id']]['give_coupon_num']) {
                            throw new AdminException('赠送优惠券数量不能超出优惠券限量');
                        }
                    }
                }
                if ($data['give_product_id']) {
                    $productIds = array_column($data['give_product_id'], 'give_product_id');
                    $giveProduct = array_combine($productIds, $data['give_product_id']);
                    /** @var StoreProductServices $storeProductServices */
                    $storeProductServices = app()->make(StoreProductServices::class);
                    $products = $storeProductServices->getSearchList(['ids' => $productIds], 0, 0, ['id,stock']);
                    if (!$products || count($products) != count(array_unique($productIds))) {
                        throw new AdminException('商品已失效请重新选择');
                    }
                    foreach ($products as $product) {
                        if (!isset($giveProduct[$product['id']]['give_product_num']) || !$giveProduct[$product['id']]['give_product_num']) {
                            throw new AdminException('请输入赠送商品数量');
                        }
                        if ($product['stock'] < $giveProduct[$product['id']]['give_product_num']) {
                            throw new AdminException('赠送商品数量不能超出商品库存');
                        }
                    }
                }
                break;
            default:
                throw new AdminException('暂不支持该类型优惠活动');
        }
        return $data;
    }


    /**
     * 获取列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function systemPage(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, '*', $page, $limit, ['giveProducts' => function ($query) {
            $query->field('promotions_id,product_id,limit_num,surplus_num')->with(['productInfo' => function ($query) {
                $query->field('id,store_name');
            }]);
        }, 'giveCoupon' => function ($query) {
            $query->field('promotions_id,coupon_id,limit_num,surplus_num')->with(['coupon' => function ($query) {
                $query->field('id,type,coupon_type,coupon_title,coupon_price,use_min_price');
            }]);
        }, 'promotions' => function ($query) {
            $query->field('id,pid,promotions_type,promotions_cate,threshold_type,threshold,discount_type,n_piece_n_discount,discount,give_integral,give_coupon_id,give_product_id,give_product_unique')->with(['giveProducts' => function ($query) {
                $query->field('promotions_id, product_id,limit_num,surplus_num')->with(['productInfo' => function ($query) {
                    $query->field('id,store_name');
                }]);
            }, 'giveCoupon' => function ($query) {
                $query->field('promotions_id, coupon_id,limit_num,surplus_num')->with(['coupon' => function ($query) {
                    $query->field('id,type,coupon_type,coupon_title,coupon_price,use_min_price');
                }]);
            }]);
        }]);
        $count = 0;
        if ($list) {
            $count = $this->dao->count($where);
            $data = $this->promotionsData;
            $data['giveCoupon'] = [];
            $data['giveProducts'] = [];
            /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
            $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
            /** @var StoreOrderServices $storeOrderServices */
            $storeOrderServices = app()->make(StoreOrderServices::class);
            $getPromotions = function($cartList, $oids){
                $promotionsPrice = 0;
				$uids = [];
                $oldUids = [];
				$ids = [];
                foreach ($cartList as $key => $cart) {
					if (!in_array($cart['oid'], $oids)) continue;
                    $info = is_string($cart['cart_info']) ? json_decode($cart['cart_info'], true) : $cart['cart_info'];
                    $promotionsPrice = bcadd((string)$promotionsPrice, (string)bcmul((string)($info['promotions_true_price'] ?? 0), (string)$info['cart_num'], 2), 2);
					if (!in_array($cart['oid'], $ids)) {
						$ids[] = $cart['oid'];
						if (!in_array($cart['uid'], $uids)) {
							$uids[] = $cart['uid'];
						} else {
							$oldUids[] = $cart['uid'];
						}
					}
                }
                return [$promotionsPrice, $uids, $oldUids];
            };
            foreach ($list as &$item) {
                if ($item['status']) {
                    if ($item['start_time'] > time())
                        $item['start_name'] = '未开始';
                    else if (bcadd((string)$item['stop_time'], '86400') < time())
                        $item['start_name'] = '已结束';
                    else if (bcadd((string)$item['stop_time'], '86400') > time() && $item['start_time'] < time()) {
                        $item['start_name'] = '进行中';
                    }
                } else $item['start_name'] = '已结束';
                $end_time = $item['stop_time'] ? date('Y/m/d', (int)$item['stop_time']) : '';
                $item['_stop_time'] = $end_time;
                $item['stop_status'] = $item['stop_time'] + 86400 < time() ? 1 : 0;

                $item['sum_pay_price'] = 0.00;
                $item['sum_promotions_price'] = 0.00;
                $item['sum_order'] = 0;
                $item['sum_user'] = 0;
                $item['old_user'] = 0;
                $item['new_user'] = 0;
				$pids = array_merge([$item['id']], array_column($item['promotions'], 'id'));
                $cartInfos = $storeOrderCartInfoServices->getColumn(['promotions_id' => $pids], 'oid,uid,cart_info', 'id', true);
                if ($cartInfos) {
					$oids = $storeOrderServices->getColumn(['id' => array_unique(array_column($cartInfos, 'oid')), 'is_del' => 0, 'pid' => 0, 'is_system_del' => 0, 'refund_status' => [0, 3]] , 'id', '');
                    $item['sum_pay_price'] = $storeOrderServices->sum(['id' => $oids, 'is_del' => 0, 'pid' => 0, 'is_system_del' => 0, 'refund_status' => [0, 3]] , 'pay_price', true);
                    [$promotionsPrice , $uids, $oldUids] = $getPromotions($cartInfos, $oids);
                    $item['sum_promotions_price'] = $promotionsPrice;
                    $item['sum_order'] = $storeOrderServices->count(['id' => $oids, 'is_del' => 0, 'pid' => 0, 'is_system_del' => 0, 'refund_status' => [0, 3]]);
                    $item['sum_user'] = count($uids);
                    $item['old_user'] = count(array_unique($oldUids));
                    $item['new_user'] = bcsub((string)$item['sum_user'], (string)$item['old_user'], 0);
                }

                $first = array_merge($data, array_intersect_key($item, $data));
                array_unshift($item['promotions'], $first);
                $item['promotions'] = $this->handelPromotions($item['promotions']);
            }
        }
        return compact('list', 'count');
    }

    /**
     * 获取信息
     * @param int $id
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfo(int $id)
    {
        $info = $this->dao->get($id, ['*'], ['products' => function ($query) {
            $query->field('promotions_id,product_id,unique')->with(['productInfo', 'attrValue']);
        }, 'giveProducts' => function ($query) {
            $query->field('type,promotions_id,product_id,limit_num,unique')->with(['productInfo'  => function ($q) {
				$q->field('id,store_name');
			}, 'giveAttrValue']);
        }, 'giveCoupon' => function ($query) {
            $query->field('type,promotions_id,coupon_id,limit_num')->with(['coupon']);
        }, 'promotions' => function ($query) {
            $query->field('id,pid,promotions_type,promotions_cate,threshold_type,threshold,discount_type,n_piece_n_discount,discount,give_integral,give_coupon_id,give_product_id,give_product_unique')->with(['giveProducts' => function ($query) {
                $query->field('type,promotions_id, product_id,limit_num,unique')->with(['productInfo'  => function ($q) {
					$q->field('id,store_name');
				}, 'giveAttrValue']);
            }, 'giveCoupon' => function ($query) {
                $query->field('type,promotions_id, coupon_id,limit_num')->with(['coupon']);
            }]);
        }]);
        if (!$info) {
            throw new AdminException('数据不存在');
        }
        $info = $info->toArray();
        if ($info['start_time'])
            $start_time = date('Y-m-d', (int)$info['start_time']);
        if ($info['stop_time'])
            $stop_time = date('Y-m-d', (int)$info['stop_time']);
        if (isset($start_time) && isset($stop_time))
            $info['section_time'] = [$start_time, $stop_time];
        else
            $info['section_time'] = [];
        unset($info['start_time'], $info['stop_time']);
        $info['is_label'] = $info['label_id'] ? 1 : 0;
        if ($info['is_label']) {
            $label_id = is_array($info['label_id']) ? $info['label_id'] : explode(',', $info['label_id']);
            /** @var UserLabelServices $userLabelServices */
            $userLabelServices = app()->make(UserLabelServices::class);
            $info['label_id'] = $userLabelServices->getLabelList(['ids' => $label_id], ['id', 'label_name']);
        } else {
            $info['label_id'] = [];
        }

        $info['threshold'] = floatval($info['threshold']);
        $info['discount'] = floatval($info['discount']);
        $info['give_integral'] = intval($info['give_integral']);
        $info['is_overlay'] = $info['overlay'] ? 1 : 0;
        $info['promotions'] = $info['promotions'] ?? [];
        $info['products'] = $info['products'] ?? [];
        $info['giveCoupon'] = $info['giveCoupon'] ?? [];
        $info['giveProducts'] = $info['giveProducts'] ?? [];
        if ($info['products']) {
            /** @var StoreProductLabelServices $storeProductLabelServices */
            $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
            $products = [];
            foreach ($info['products'] as &$item) {
                $product = is_object($item) ? $item->toArray() : $item;
                $product = array_merge($product, $product['productInfo'] ?? []);
                $product['store_label'] = '';
                if (isset($product['store_label_id']) && $product['store_label_id']) {
                    $storeLabelList = $storeProductLabelServices->getColumn([['store_id', '=', 0], ['type', '=', 1], ['id', 'IN', $product['store_label_id']]], 'id,label_name');
                    $product['store_label'] = $storeLabelList ? implode(',', array_column($storeLabelList, 'label_name')) : '';
                }
                $unique = is_string($product['unique']) ? explode(',', $product['unique']) : $product['unique'];
                foreach($product['attrValue'] as $key => $value) {
                    if (!in_array($value['unique'], $unique)){
                        unset($product['attrValue'][$key]);
                    }
                }
                $product['attrValue'] = array_merge($product['attrValue']);
                unset($product['productInfo']);
                $products[] = $product;
            }
            if ($products) {
                $cateIds = implode(',', array_column($products, 'cate_id'));
                /** @var StoreCategoryServices $categoryService */
                $categoryService = app()->make(StoreCategoryServices::class);
                $cateList = $categoryService->getCateParentAndChildName($cateIds);
                foreach ($products as $key => &$item) {
                    $cateName = array_filter($cateList, function ($val) use ($item) {
                        if (in_array($val['id'], explode(',', $item['cate_id']))) {
                            return $val;
                        }
                    });
                    $item['cate_name'] = [];
                    foreach ($cateName as $k => $v) {
                        $item['cate_name'][] = $v['one'] . '/' . $v['two'];
                    }
                    $item['cate_name'] = is_array($item['cate_name']) ? implode(',', $item['cate_name']) : '';
                    foreach ($item['attrValue'] as $key => &$value) {
                        $value['store_label'] = $item['store_label'] ?? '';
                        $value['cate_name'] = $item['cate_name'] ?? '';
                    }
                }
            }
            
            unset($info['products']);
            $info['products'] = $products;
        }
        $data = $this->promotionsData;
        $data['giveCoupon'] = [];
        $data['giveProducts'] = [];
        $first = array_merge($data, array_intersect_key($info, $data));
        array_unshift($info['promotions'], $first);

        $info['promotions'] = $this->handelPromotions($info['promotions']);
        return $info;
    }

    /**
     * 处理阶梯优惠赠送商品、优惠券
     * @param array $promotions
     * @return array
     */
    public function handelPromotions(array $promotions)
    {
        if ($promotions) {
            foreach ($promotions as &$p) {
                $p['threshold'] = (float)$p['threshold'];
                $p['discount'] = (float)$p['discount'];
                if (isset($p['giveCoupon']) && $p['giveCoupon']) {
                    $coupons = [];
                    foreach ($p['giveCoupon'] as &$coupon) {
                        $coupon = is_object($coupon) ? $coupon->toArray() : $coupon;
                        $coupon = array_merge($coupon, $coupon['coupon'] ?? []);
                        unset($coupon['coupon']);
                        $coupons[] = $coupon;
                    }
                    unset($p['giveCoupon']);
                    $p['giveCoupon'] = $coupons;
                }
                if (isset($p['giveProducts']) && $p['giveProducts']) {
                    $products = [];
                    foreach ($p['giveProducts'] as &$product) {
                        $product = is_object($product) ? $product->toArray() : $product;
						$product = array_merge($product, $product['productInfo'] ?? []);
                        $product = array_merge($product, $product['giveAttrValue'] ?? []);
                        unset($product['productInfo'], $product['giveAttrValue']);
                        $products[] = $product;
                    }
                    unset($p['giveProducts']);
                    $p['giveProducts'] = $products;
                }
            }
        }
        return $promotions;
    }

    /**
     * 保存促销活动
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function saveData(int $id, array $data)
    {
        if (!$data['section_time'] || count($data['section_time']) != 2) {
            throw new AdminException('请选择活动时间');
        }
        [$start_time, $end_time] = $data['section_time'];
        if (strtotime($end_time) + 86400 < time()) {
            throw new AdminException('活动结束时间不能小于当前时间');
        }
        if ($data['product_partake_type'] != 1 && !$data['product_id']) {
            throw new AdminException('请选择添加参与活动商品');
        }
        if ($id) {
            $info = $this->dao->get((int)$id);
            if (!$info) {
                throw new AdminException('数据不存在');
            }
        }
        $data['start_time'] = strtotime($start_time);
        $data['stop_time'] = strtotime($end_time) + 86399;
        $data['label_id'] = $data['label_id'] ? implode(',', $data['label_id']) : '';
        $data['overlay'] = $data['overlay'] ? implode(',', $data['overlay']) : '';
        $productData = $data['product_id'];
        $productIds = $productData ? array_column($productData, 'product_id') : [];
        $data['product_id'] = $productIds ? implode(',', $productIds) : '';

        if ($data['product_partake_type'] == 1) {
            $data['product_id'] = [];
        }
        if (in_array($data['product_partake_type'], [2, 3])) {
            /** @var StoreProductServices $storeProductServices */
            $storeProductServices = app()->make(StoreProductServices::class);
            $count = $storeProductServices->count(['is_show' => 1, 'is_del' => 0, 'id' => $productIds]);
            $productCount = count(array_unique($productIds));
            if ($count != $productCount) {
                throw new AdminException('选择商品中有已下架或移入回收站');
            }
        }

        $promotions = $data['promotions'];
        $threshold = -1;
        foreach ($promotions as &$value) {
            if ($threshold != -1 && $value['threshold'] <= $threshold) {
                throw new AdminException('优惠门槛只能递增（例如二级必须大于一级优惠）');
            }
            $threshold = $value['threshold'];
            $value = $this->checkPromotions((int)$data['promotions_type'], $value);
            $value['threshold_type'] = $data['threshold_type'];
        }
        [$title, $desc] = $this->getPromotionsDesc((int)$data['promotions_type'], (int)$data['promotions_cate'], array_merge($promotions, ['is_limit' => $data['is_limit'], 'limit_num' => $data['limit_num']]));
        $data['title'] = $title;
        $data['desc'] = implode(',', $desc);

        $first = array_shift($promotions);
        $giveCoupon = $first['give_coupon_id'] ?? [];
        $giveProduct = $first['give_product_id'] ?? [];
        unset($first['give_coupon_id'], $first['give_product_id']);
        $first['give_coupon_id'] = $giveCoupon ? implode(',', array_unique(array_column($giveCoupon, 'give_coupon_id'))) : '';
        $first['give_product_id'] = $giveProduct ? implode(',', array_unique(array_column($giveProduct, 'give_product_id'))) : '';
        $first['give_product_unique'] = $giveProduct ? implode(',', array_unique(array_column($giveProduct, 'unique'))) : '';
        $data = array_merge($data, $first);

        unset($data['section_time'], $data['promotions']);
        $this->transaction(function () use ($id, $data, $promotions, $productIds, $productData, $giveCoupon, $giveProduct) {
            $time = time();
            $data['update_time'] = $time;
            if ($id) {
                $this->dao->update($id, $data);
                //删除之前阶梯数据
                $this->dao->delete(['pid' => $id]);
            } else {
                $data['add_time'] = $time;
                $res = $this->dao->save($data);
                $id = $res->id;
            }
            /** @var StorePromotionsAuxiliaryServices $storePromotionsAuxiliaryServices */
            $storePromotionsAuxiliaryServices = app()->make(StorePromotionsAuxiliaryServices::class);
            $storePromotionsAuxiliaryServices->savePromotionsRelation((int)$id, (int)$data['product_partake_type'], $productData, $giveCoupon, $giveProduct);

            if ($promotions) {
                foreach ($promotions as $item) {
                    $giveCoupon = $item['give_coupon_id'] ?? [];
                    $giveProduct = $item['give_product_id'] ?? [];
                    unset($item['give_coupon_id'], $item['give_product_id']);
                    $item['give_coupon_id'] = $giveCoupon ? implode(',', array_unique(array_column($giveCoupon, 'give_coupon_id'))) : '';
                    $item['give_product_id'] = $giveProduct ? implode(',', array_unique(array_column($giveProduct, 'give_product_id'))) : '';
                    $item['give_product_unique'] = $giveProduct ? implode(',', array_unique(array_column($giveProduct, 'unique'))) : '';

                    $item['pid'] = $id;
                    $item['promotions_type'] = (int)$data['promotions_type'];
                    $item['promotions_cate'] = (int)$data['promotions_cate'];
                    $item['add_time'] = $time;
                    $res = $this->dao->save($item);
                    $storePromotionsAuxiliaryServices->savePromotionsRelation((int)$res->id, (int)$data['product_partake_type'], $productData, $giveCoupon, $giveProduct);
                }
            }

        });
        return true;
    }

    /**
     * 验证购买商品规格是否在优惠活动适用商品选择规格中
     * @param array $productIds
     * @param array $cartList
     * @param array $promotions
     * @return array
     */ 
    public function checkProductCanUsePromotions(array $productIds, array $cartList, array $promotions)
    {
        $ids = $uniques = [];
        if (!$productIds || !$cartList || !$promotions) return [$ids, $uniques];
        $useProducts = $promotions['products'] ?? [];
        if ($useProducts) {
            $useProducts = array_combine(array_column($useProducts, 'product_id'), $useProducts);
        }
        foreach($cartList as $cart){
            if (!in_array($cart['product_id'], $productIds)) continue;
            $productUnique = $cart['product_attr_unique'] ?? '';
            $product_id = $cart['product_id'] ?? 0;
            if (!$productUnique) continue;
            if (in_array($promotions['product_partake_type'], [2, 3])) {
                $useUniques = $useProducts[$product_id]['unique'] ?? [];
                if ($promotions['product_partake_type'] == 2){
                    $uniques[$product_id] = $useUniques;
                    if (!in_array($productUnique, $useUniques)) {
                        continue;
                    }
                } else {
                    $uniques[$product_id] = $useUniques;
                    if (in_array($productUnique, $useUniques)) {
                        continue;
                    }
                }
            }
            $ids[] = $product_id;
        }
        return [$ids, $uniques];
    }

    /**
    * 检查优惠活动限量
    * @param StoreOrderCartInfoServices $storeOrderCartInfoServices
    * @param int $uid
    * @param int $id
    * @param array $productIds
    * @param array $promotions
    * @return array
     */
    public function checkPromotionsLimit(StoreOrderCartInfoServices $storeOrderCartInfoServices, int $uid, int $id, array $productIds, array $promotions)
    {
        if (!$productIds) {
            return [];
        }
        if ($uid && $promotions['promotions_type'] == 1 && $promotions['is_limit']) {//限时折扣 存在限量
            //获取包含子级获取ids
            $ids = array_unique(array_merge([$id], array_column($promotions['promotions'] ?? [], 'id')));
            $data = [];
            foreach ($productIds as $key => $product_id) {
                if ($storeOrderCartInfoServices->count(['uid' => $uid, 'product_id' => $product_id, 'promotions_id' => $ids]) < $promotions['limit_num']) {
                    $data[] = $product_id;
                }
            }
            return $data;
        }
        return $productIds;
    }

    /**
    * 计算几个商品总金额 并返回商品信息
    * @param int $promotions_type
    * @param array $productIds
    * @param array $cartList
    * @param array $uniques
    * @param int $product_partake_type
    * @param bool $isGive
    * @return array
     */
    protected function getPromotionsProductInfo(int $promotions_type, array $productIds, array $cartList, array $uniques = [], int $product_partake_type = 1, bool $isGive = false)
    {
        $sumPrice = $sumCount = 0;
        $p = [];
        if (!$cartList || !$productIds) {
            return [$sumPrice, $sumCount, $p];
        }
        $productComputedArr = $this->getItem('productComputedArr', []);
        $computedArr = $this->getItem('computedArr', []);
        foreach($cartList as $product){
            if (!in_array($product['product_id'], $productIds)) continue;
            $cart_num = $product['cart_num'] ?? 1;
            $unique = $product['product_attr_unique'] ?? '';
            $product_id = $product['product_id'];
            if (!$this->checkProductUnque($unique, $uniques[$product_id] ?? [], $product_partake_type)) {
                continue;
            }
            if ($isGive) {
                $key = 'true_price';
            } else {
                if ($promotions_type == 1){
                    $key = 'price';
                } else {
                    if (isset($productComputedArr[$product_id]['typeArr']) && in_array(1, $productComputedArr[$product_id]['typeArr'])){
                        $key = 'price';
                    } else {
                        $key = 'true_price';
                    }
                }
            }
            if ($key == 'price') {
                $price = isset($product['productInfo']['attrInfo']['price']) ? $product['productInfo']['attrInfo']['price'] : ($product['productInfo']['price'] ?? 0);
            } else {
                $price = $product['truePrice'];
            }
            $isOverlay = $productComputedArr[$product_id]['is_overlay'] ?? false;
            if ($isOverlay && $computedArr) {
                foreach ($computedArr as $key => $computedDetail) {
                    if (isset($computedDetail[$unique]['promotions_true_price'])) {
                        $price = bcsub((string)$price, (string)$computedDetail[$unique]['promotions_true_price'], 2);
                    }
                }
            }
            $product['price'] = floatval($price) > 0 ? $price : 0;
            $sumPrice = bcadd((string)$sumPrice, (string)bcmul((string)$price, (string)$cart_num, 2), 2);
            if ($isGive && isset($product['coupon_price'])) {
                $sumPrice = bcsub((string)$sumPrice, (string)$product['coupon_price'], 2);
            }
            $sumCount = bcadd((string)$sumCount, (string)$cart_num, 0);

            $p[] = $product;
        }
        return [$sumPrice, $sumCount, $p];
    }

    /**
     * 验证是否叠加其他活动
     * @param int $promotions_type
     * @param array $overlay
     * @return bool
     */ 
    public function checkOverlay(int $promotions_type, array $overlay)
    {
        $data = [1, 2, 3];
        return boolval(array_intersect($overlay, array_diff($data, [$promotions_type])));
    }

    /**
    * 获取商品活动是否叠加计算
    * @param array $promotionsList
    * @param array $productDetails
    * @param array $promotionsDetail
    * @return array[]
     */
    public function getProductComputedPromotions(array $promotionsList, array $productDetails, array $promotionsDetail)
    {
        $productArr = [];
        $promotionsArr = [];
        $productComputedArr = [];
        if (!$promotionsList) {
            return [$productArr, $promotionsArr, $productComputedArr];
        }
        //验证是否能叠加使用
        foreach ($productDetails as $product_id  => $promotionsIds) {
            $pIds = [];
            $overlayPIds = [];
            $unOverlayPIds = [];
            $prevType = [];
            $preOverlay = [];
            $isOverlay = true;
            foreach ($promotionsIds as $id) {
                $promotions = $promotionsList[$id] ?? [];
                if (!$promotions) continue;
                $overlay = is_string($promotions['overlay']) ? explode(',', $promotions['overlay']) : $promotions['overlay'];
                //同一个商品 同一类型活动取最新一个
                if (!in_array($promotions['promotions_type'], $prevType)) {
                    if (!$prevType) {
                        $overlayPIds[] = $unOverlayPIds[] = $id;
                        $prevType[] = $promotions['promotions_type'];
                        $preOverlay[$promotions['promotions_type']] = $overlay;
                    } else {
                        //有限时折扣
                        if (isset($preOverlay[1]) && !$this->checkOverlay(1, $preOverlay[1])) {
                            if ($promotions['promotions_type'] == 4) {
                                $prevType[] = $promotions['promotions_type'];
                                $preOverlay[$promotions['promotions_type']] = $overlay;
                                $unOverlayPIds[] = $id;
                            }
                        } else {
                            if ($promotions['promotions_type'] == 4) {
                                $prevType[] = $promotions['promotions_type'];
                                $preOverlay[$promotions['promotions_type']] = $overlay;
                                $overlayPIds[] = $id;
                                $unOverlayPIds[] = $id;
                            } else {
                                foreach ($preOverlay as $key => $value) {
                                    if (in_array($key, $overlay) && in_array($promotions['promotions_type'], $value)) {
                                        $overlayPIds[] = $id;
                                    } else {
                                        $unOverlayPIds[] = $id;
                                    }
                                }
                            }
                            $prevType[] = $promotions['promotions_type'];
                            $preOverlay[$promotions['promotions_type']] = $overlay;
                        }
                    }
                }
            }
            $overlayPIds = array_unique($overlayPIds);
            $unOverlayPIds = array_unique($unOverlayPIds);
            if (count($overlayPIds) > 1) {
                $isOverlay = true;
                $pIds = $overlayPIds;
            } else if (count($overlayPIds) == 1 && count($unOverlayPIds) == 1) {
                $isOverlay = false;
                $pIds = $overlayPIds;
            } else {
                $isOverlay = false;
                $pIds = $unOverlayPIds;
            }
            
            $productComputedArr[$product_id]['is_overlay'] = $pIds && $isOverlay;
			$typeArr = [];
            //重新整理商品关联ids数据
            foreach ($pIds as $id) {
                $promotions = $promotionsList[$id] ?? [];
                if ($promotions) {
                    $productArr[$product_id][] = $id;
                    $typeArr[] = $promotions['promotions_type'];
                }
            }
			//存在限时折扣 不叠加
			if (count($typeArr) > 1 && in_array(1, $typeArr) && !$productComputedArr[$product_id]['is_overlay'] ) {
				$typeArr = [];
				$productArr[$product_id] = [];
				foreach ($pIds as $id) {
					$promotions = $promotionsList[$id] ?? [];
					if ($promotions) {
						if (in_array($promotions['promotions_type'], [1, 4])) {
							$productArr[$product_id][] = $id;
                    		$typeArr[] = $promotions['promotions_type'];
						}
					}
				}
			}
			$productComputedArr[$product_id]['type'] = $typeArr;
        }
        //重新整理活动关联商品ids数据
        foreach ($promotionsDetail as $promotions_id => $productIds) {
            foreach ($productIds as $pid) {
                $pIds = $productArr[$pid] ?? [];
                if ($pIds && in_array($promotions_id, $pIds)) {
                    $promotionsArr[$promotions_id][] = $pid;
                }
            }
        }
        return [$productArr, $promotionsArr, $productComputedArr];
    }

    /**
     * 组合使用的优惠活动详情
     * @param int $uid
     * @param int $store_id
     * @param array $usePromotionsIds
     * @param array $promotionsArr
     * @param array $computedArr
     * @return array
     */ 
    public function getUsePromotiosnInfo(int $uid, int $store_id, array $usePromotionsIds, array $promotionsArr, array $computedArr)
    {
        $usePromotions = [];
        if (!$usePromotionsIds || !$promotionsArr || !$computedArr) {
            return $usePromotions;
        }
        $giveCoupon = $giveProduct = $giveCartList = [];
        $giveIntegral = 0;
        foreach ($usePromotionsIds as $id) {
            $promotionsInfo = $promotionsArr[$id] ?? [];
            if (!$promotionsInfo) continue;
            $details = $computedArr[$id] ?? [];
            $promotionsInfo['details'] = $details;
            $promotionsInfo = array_merge($promotionsInfo, $details['give'] ?? []);
            $promotionsInfo['is_valid'] = $details['is_valid'] ?? 0;
			$promotionsInfo['reach_threshold'] = $details['reach_threshold'] ?? 0;
			$promotionsInfo['sum_promotions_price'] = $details['sum_promotions_price'] ?? 0;
			$promotionsInfo['differ_threshold'] = $details['differ_threshold'] ?? 0;//下一级优惠差多少元｜件
			$promotionsInfo['differ_price'] = $details['differ_price'] ?? 0;//下一级优惠金额
			$promotionsInfo['differ_discount'] = $details['differ_discount'] ?? 0;//下一级享受折扣
            $giveProductIds = $giveCart = [];
            if (isset($promotionsInfo['give_product']) && $promotionsInfo['give_product']) {
                $giveCart = $this->createGiveProductCart($uid, (int)$id, $promotionsInfo['give_product'], $promotionsInfo, $store_id);
                if ($giveCart) {
                    $giveProductIds = array_column($giveProductIds, 'product_id');
                    $giveCartList = array_merge($giveCartList, $giveCart);
                }
            }
            $promotionsInfo['product_ids'] = $promotionsDetail[$id] ?? [];
            $promotionsInfo['product_ids'] = array_merge($promotionsInfo['product_ids'], $giveProductIds);

            $promotionsInfo['give_product'] = $giveProductIds;

            $giveCoupon = array_merge($giveCoupon, $promotionsInfo['give_coupon'] ?? []);
            $giveProduct = array_merge($giveProduct, $promotionsInfo['give_product'] ?? []);
            $giveIntegral = bcadd((string)$giveIntegral, (string)($promotionsInfo['give_integral'] ?? 0), 0);
            $usePromotions[] = $promotionsInfo;
        }
        return [$usePromotions, $giveIntegral, $giveCoupon, $giveCartList];         
    }

    /**
    * 验证购物车商品规格是否在活动中
    * @param string $unique
    * @param array $uniques
    * @param int $product_partake_type
    * @return bool
     */
    public function checkProductUnque(string $unique, array $uniques, int $product_partake_type = 1)
    {
        if (!$unique) {
            return false;
        }
        if (!$uniques) {
            return true;
        }
        switch ($product_partake_type) {
            case 1:
                break;
            case 2:
                if(!in_array($unique, $uniques)){
                    return false;
                }
                break;
            case 3:
                if (in_array($unique, $uniques)) {
                    return false;
                }
                break;
            default:
                return false;
                break;
        }
        return true;
    }


    /**
     * 计算商品优惠价格
     * @param int $uid
     * @param array $cartList
     * @param int $store_id
     * @param int $couponId
     * @param bool $isCart
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function computedPromotions(int $uid, array $cartList, int $store_id = 0, int $couponId = 0, bool $isCart = false)
    {
        $giveIntegral = $couponPrice = 0;
        $giveCoupon = $giveCartList = $usePromotions = $useCounpon = [];
        if ($cartList) {
            $productIds = array_column($cartList, 'product_id');
            // $productArr = array_combine($productIds, $cartList);
            $with = ['products' => function ($query) {
                $query->field('promotions_id,product_id,is_all,unique');
            },'giveProducts' => function ($query) {
                $query->field('type,promotions_id,product_id,limit_num,surplus_num,unique')->with(['productInfo' => function ($query) {
                    $query->field('id,store_name');
                }]);
            }, 'giveCoupon' => function ($query) {
                $query->field('type,promotions_id,coupon_id,limit_num,surplus_num')->with(['coupon' => function ($query) {
                    $query->field('id,type,coupon_type,coupon_title,coupon_price,use_min_price,remain_count,is_permanent');
                }]);
            }, 'promotions' => function ($query) {
                $query->field('id,pid,promotions_type,promotions_cate,threshold_type,threshold,discount_type,n_piece_n_discount,discount,give_integral,give_coupon_id,give_product_id,give_product_unique')->with(['giveProducts' => function ($query) {
                    $query->field('type,promotions_id,product_id,limit_num,surplus_num,unique')->with(['productInfo' => function ($query) {
                        $query->field('id,store_name');
                    }]);
                }, 'giveCoupon' => function ($query) {
                    $query->field('type,promotions_id, coupon_id,limit_num,surplus_num')->with(['coupon' => function ($query) {
                        $query->field('id,type,coupon_type,coupon_title,coupon_price,use_min_price,remain_count,is_permanent');
                    }]);
                }]);
            }];
            //获取购物车商品所有活动
            [$promotionsArr, $productDetails, $promotionsDetail] = $this->getProductsPromotionsDetail($productIds, 0, '*', $with);
            $computedArr = [];
            $usePromotionsIds = [];
            if ($promotionsArr) {
                $promotionsArr = array_combine(array_column($promotionsArr, 'id'), $promotionsArr);
                //获取商品活动是否叠加计算
                [$productDetails, $promotionsDetail, $productComputedArr] = $this->getProductComputedPromotions($promotionsArr, $productDetails, $promotionsDetail);
                //计算优惠金额
                $computedArr = $this->doComputeV1($uid, $cartList, $promotionsDetail, $promotionsArr, $productComputedArr);
                foreach ($cartList as &$cart) {
                    $sum_promotions_true_price = 0;
                    $product_id = (int)($cart['product_id'] ?? 0);
                    $unique = $cart['product_attr_unique'] ?? '';
                    $promotionsIds = $productDetails[$product_id] ?? [];
                    $cart['promotions_id'] = [];
                    if (!$promotionsIds || !$unique) {
                        continue;
                    }
                    $price = isset($cart['productInfo']['attrInfo']['price']) ? $cart['productInfo']['attrInfo']['price'] : ($cart['productInfo']['price'] ?? 0);                    
                    //叠加
                    $typeArr = $productComputedArr[$product_id]['typeArr'] ?? [];
					$isOverly = isset($productComputedArr[$product_id]['is_overlay']) && $productComputedArr[$product_id]['is_overlay'];
					foreach ($promotionsIds as $promotions_id) {
						$promotionsInfo = $promotionsArr[$promotions_id] ?? [];
						$trueDetail = $computedArr[$promotions_id] ?? [];
						if (!$promotionsInfo || !$trueDetail) continue;
						if (!$this->checkProductUnque($unique, $trueDetail['uniques'][$product_id] ?? [], (int)$promotionsInfo['product_partake_type'])) {
							continue;
						}
						$trueArr = $trueDetail[$unique] ?? [];
						if (!isset($trueDetail['is_valid']) || $trueDetail['is_valid'] == 0) {
							if ($isCart) {//购物车不满足也展示
								$cart['promotions_id'][] = $promotions_id;
							}
						} else {
							//活动叠加商品 单件总计优惠金额
							if ($isOverly) {
								$cart['promotions_id'][] = $promotions_id;
								$sum_promotions_true_price = bcadd((string)$sum_promotions_true_price, (string)($trueArr['promotions_true_price'] ?? 0), 2);
							} else { //不叠加取最优惠
								if ($isCart) {
									$cart['promotions_id'][] = $promotions_id;
								}
								if ($sum_promotions_true_price < ($trueArr['promotions_true_price'] ?? 0)) {
									$sum_promotions_true_price = $trueArr['promotions_true_price'] ?? 0;
									if (!$isCart) {
										$cart['promotions_id'] = [$promotions_id];
									}
								}
							}
						}
					}
                    if ($sum_promotions_true_price) {
                        //是否有限时折扣
                        if (in_array(1, $typeArr)) {
                            $true_price = (float)bcsub((string)$price, (string)$sum_promotions_true_price, 2);
                            if ($true_price < 0) {
                                $true_price = 0;
                            }
                            //比较与用户等级、svip优惠后金额
                            if ($true_price && $cart['truePrice'] > $true_price) {
                                $cart['truePrice'] = $true_price;
                                $cart['promotions_true_price'] = $sum_promotions_true_price;
                                $cart['price_type'] = 'promotions';
                                $cart['vip_truePrice'] = 0;
                            } else { //使用了用户等级、svip价格 去掉优惠活动关联
								$cart['promotions_id'] = [];
                            }
                        } else {//svip 用户等级价格上继续优惠
                            $true_price = (float)bcsub((string)$cart['truePrice'], (string)$sum_promotions_true_price, 2);
                            $cart['truePrice'] = $true_price > 0 ? $true_price : 0;
                            $cart['promotions_true_price'] = $sum_promotions_true_price;
                            $cart['price_type'] = 'promotions';
                        }
                    }
					$usePromotionsIds = array_unique(array_merge($usePromotionsIds, $cart['promotions_id']));
					//排出不叠加 去最优的其他优惠活动金额
					foreach ($promotionsIds as $promotions_id) {
						//使用了优惠会跳过
						if (in_array($promotions_id, $cart['promotions_id'])) continue;
						$trueDetail = $computedArr[$promotions_id] ?? [];
						$trueArr = $trueDetail[$unique] ?? [];
						if (!$trueDetail || !$trueArr) {
							continue;
						}
						$true_price = $trueArr['promotions_true_price'] ?? 0;
						$sum_promotions_price = bcsub((string)($trueDetail['sum_promotions_price'] ?? '0'), (string)bcmul((string)$true_price, (string)$cart['cart_num'], 2), 2);
						$computedArr[$promotions_id]['sum_promotions_price'] = $sum_promotions_price >= 0 ? $sum_promotions_price : 0;
						//这个商品的没有使用优惠活动
						unset($computedArr[$promotions_id][$unique]);
					}
                }
            }
            //使用优惠券 
            $coupon_id = 0;
            if ($uid) {
				if ($usePromotionsIds) [$usePromotions, $giveIntegral, $giveCoupon, $giveCartList] = $this->getUsePromotiosnInfo($uid, $store_id, $usePromotionsIds, $promotionsArr, $computedArr);
                if ($couponId) {
                    [$useCounpon, $couponPrice] = $this->useCoupon($couponId, $uid, $cartList, $usePromotions);
                    $coupon_id = $couponId;
                } else {
                    //获取最优优惠券
                    if ($isCart) {
                        /** @var StoreCouponIssueServices $couponServices */
                        $couponServices = app()->make(StoreCouponIssueServices::class);
                        $useCounpon = $couponServices->getCanUseCoupon($uid, $cartList, $usePromotions);
                        $couponPrice = $useCounpon['true_coupon_price'] ?? 0;
                        $coupon_id = $useCounpon['used']['id'] ?? 0;
                    }
                }
				//计算每一件商品优惠券优惠金额
				if ($coupon_id && $useCounpon && $couponPrice) {
					/** @var StoreOrderComputedServices $computedServices */
					$computedServices = app()->make(StoreOrderComputedServices::class);
					$payPrice = $computedServices->getOrderSumPrice($cartList);
					if ($couponPrice > $payPrice) {
						$couponPrice = $payPrice;
					}
					/** @var StoreOrderCreateServices $createServices */
					$createServices = app()->make(StoreOrderCreateServices::class);
					$priceData = ['coupon_id' => $coupon_id, 'coupon_price' => $couponPrice];
					$cartList = $createServices->computeOrderProductCoupon($cartList, $priceData, $usePromotions);
				}
            }

            //获取赠送积分、优惠券、商品
            [$cartList, $computedArr, $useGivePromotionsIds] = $this->getPromotionsGive($uid, $cartList, $computedArr, $promotionsDetail, $productDetails, $promotionsArr, $isCart);
            $usePromotionsIds = array_unique(array_merge($usePromotionsIds, $useGivePromotionsIds));
            //整合返回数据
            if($usePromotionsIds) [$usePromotions, $giveIntegral, $giveCoupon, $giveCartList] = $this->getUsePromotiosnInfo($uid, $store_id, $usePromotionsIds, $promotionsArr, $computedArr);
        }
        return [$cartList, $couponPrice, $useCounpon, $usePromotions, $giveIntegral, $giveCoupon, $giveCartList];
    }

    /**
     * 实际计算商品优惠金额
     * @param int $uid
     * @param array $cartList
     * @param array $promotionsDetail
     * @param array $promotionsArr
     * @param array $productComputedArr
     * @return array
     */ 
    public function doComputeV1(int $uid, array $cartList, array $promotionsDetail, array $promotionsArr, array $productComputedArr)
    {
        $computedArr = [];
        if (!$cartList || !$promotionsDetail) {
            return $computedArr;
        }
        /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
        $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
        foreach ($promotionsDetail as $promotions_id => $productIds) {
            $promotions = $promotionsArr[$promotions_id] ?? [];
            $productCount = count($productIds);
            if (!$promotions || !$productCount || $promotions['promotions_type'] == 4) continue;
            //验证商品规格是否满足活动
            [$productIds, $uniques]= $this->checkProductCanUsePromotions($productIds, $cartList, $promotions);
            if (!$productIds) {
                continue;
            }
            //验证限量
            if ($uid) {
                $productIds = $this->checkPromotionsLimit($storeOrderCartInfoServices, $uid, (int)$promotions_id, $productIds, $promotions);
                if (!$productIds) {
                    continue;
                }
            }
            $promotions_type = (int)$promotions['promotions_type'] ?? 1;
            $this->setItem('productComputedArr', $productComputedArr)->setItem('computedArr', $computedArr);
            [$sumPrice, $sumCount, $promotionsProductArr] = $this->getPromotionsProductInfo($promotions_type, $productIds, $cartList, $uniques, (int)$promotions['product_partake_type']);
            $this->reset();
            $compute_price = $sum_promotions_true_price = $sum_promotions_price = 0;
            $data = ['is_valid' => 0, 'reach_threshold' => 0, 'differ_threshold' => 0, 'promotions_type' => $promotions['promotions_type'], 'sum_promotions_price' => 0, 'product_id' => [], 'uniques' => $uniques];
            switch ($promotions['promotions_type']) {
                case 1://限制折扣
                    $p = $promotions['promotions'][0] ?? [];
                    if ($p) {
                        $promotionsDiscount = $this->computedDiscount($p['discount'], 100, 2);
                        $sum_promotions_price = 0;
                        foreach ($promotionsProductArr as $product) {
                            $product_id = $product['product_id'];
                            $unique = $product['product_attr_unique'];
                            $price = $product['price'];
                            $promotions_true_price = (float)bcmul((string)$price, (string)$promotionsDiscount, 2);
                            if ($promotions_true_price < 0.01) {//实际优惠小于0.01 就不计算优惠
                                $promotions_true_price = 0;
                            }
                            $data[$unique] = ['product_id' => $product_id, 'uniqid' => $unique, 'price' => $price, 'promotions_true_price' => $promotions_true_price];
                            $sum_promotions_price = bcadd((string)$sum_promotions_price, (string)bcmul((string)$promotions_true_price, (string)($product['cart_num'] ?? 1), 2), 2);
                        }
                        $data['is_valid'] = 1;
                        $data['sum_promotions_price'] = $sum_promotions_price;
                    }
                    break;
                case 2://n件n折
                    $p = $promotions['promotions'][0] ?? [];
                    if ($p) {
                        $promotionsDiscount = $this->computedDiscount($p['discount'], 100, 2);
                        if ($sumCount >= $p['threshold']) {//满足
                            //商品价格升序
                            array_multisort(array_column($promotionsProductArr, 'price'), SORT_ASC, $promotionsProductArr); 
                            $minPrice = $promotionsProductArr[0]['price'] ?? 0;
                            //算出n件实际优惠金额
                            $sum_promotions_price = (float)bcmul((string)$minPrice, (string)$promotionsDiscount, 2);
                            if ($sum_promotions_price < 0.01) {
                                $sum_promotions_price = 0;
                            }
                            if ($sum_promotions_price) {
                                // $useProductArr = [];
                                // $useSumPrice = 0;
                                // $useCount = 0;
                                // //计算实际优惠的n件
                                // foreach ($promotionsProductArr as $product) {
                                //     $product_id = $product['product_id'];
                                //     $price = $product['price'];
                                //     if ($useCount < $p['threshold']) {
                                //         $useProductArr[] = $product;
                                //         $useSumPrice = bcadd((string)$useSumPrice, (string)bcmul((string)$product['price'], (string)$product['cart_num'], 2), 2);
                                //         $useCount = bcadd((string)$useCount, (string)$product['cart_num']);
                                //     }
                                // }
                                $useProductArr = $promotionsProductArr;
                                $useCount = $productCount;
                                $useSumPrice = $sumPrice;
                                $count = count($useProductArr);
                                $compute_price = 0;
                                foreach ($useProductArr as $value) {
                                    $product_id = $value['product_id'];
                                    $unique = $value['product_attr_unique'];
                                    $price = $value['price'];
                                    if ($count > 1) {
                                        $promotions_true_price = bcmul((string)bcdiv((string)$price, (string)$useSumPrice, 4), (string)$sum_promotions_price, 2);
                                        $compute_price = bcadd((string)$compute_price, (string)bcmul((string)$promotions_true_price, (string)$value['cart_num'], 2), 2);
                                    } else {
                                        $one_promotions_sum_price = bcsub((string)$sum_promotions_price, (string)$compute_price, 2);
                                        $promotions_true_price = bcdiv((string)$one_promotions_sum_price, (string)$value['cart_num'], 2);
                                    }
                                    $data[$unique] = ['product_id' => $product_id, 'uniqid' => $unique, 'price' => $price, 'promotions_true_price' => $promotions_true_price];
                                    $count--;
                                }
                                
                            }
                            $data['is_valid'] = 1;
							$data['reach_threshold'] = $p['threshold'];
                            $data['sum_promotions_price'] = $sum_promotions_price;
                        } else {
							$data['differ_discount'] = $p['discount'];
                            $data['differ_threshold'] = bcsub((string)$p['threshold'], (string)$sumCount, 0);
                        }
                    }
                    break;
                case 3://满减折
                    if ($promotions['promotions_cate'] == 1) {//阶梯 享受最高
                        $valid = $invalid = [];
                        foreach ($promotions['promotions'] as $key => $p) {
                            if (($p['threshold_type'] == 1 ? $sumPrice : $sumCount) >= $p['threshold']) {
                                $valid = $p;
                            } else {
                                $invalid = $p;
                                break;
                            }
                        }
                        if ($valid) {
                            if ($valid['discount_type'] == 1) {//免减
                                $sum_promotions_price = (string)$valid['discount'];
                            } else {//折扣
                                $promotionsDiscount = $this->computedDiscount($valid['discount'], 100, 2);
                                $sum_promotions_price = 0;
                            }
                            foreach ($promotionsProductArr as $product) {
                                $product_id = $product['product_id'];
                                $unique = $product['product_attr_unique'];
                                $price = $product['price'];
                                if ($valid['discount_type'] == 1) {
                                    if ($productCount > 1) {
                                        $promotions_true_price = bcmul((string)bcdiv((string)$price, (string)$sumPrice, 4), (string)$sum_promotions_price, 2);
                                        $compute_price = bcadd((string)$compute_price, (string)bcmul((string)$promotions_true_price, (string)$product['cart_num'], 2), 2);
                                    } else {
                                        $one_promotions_sum_price = bcsub((string)$sum_promotions_price, (string)$compute_price, 2);
                                        $promotions_true_price = bcdiv((string)$one_promotions_sum_price, (string)$product['cart_num'], 2);
                                    }
                                } else {
                                    $promotions_true_price = (float)bcmul((string)$price, (string)$promotionsDiscount, 2);
                                    if ($promotions_true_price < 0.01) {//实际优惠小于0.01 就不计算优惠
                                        $promotions_true_price = 0;
                                    }
                                    $sum_promotions_price = bcadd((string)$sum_promotions_price, (string)bcmul((string)$promotions_true_price, (string)($product['cart_num'] ?? 1), 2), 2);
                                }
                                $data[$unique] = ['product_id' => $product_id, 'uniqid' => $unique, 'price' => $price, 'promotions_true_price' => $promotions_true_price];
                                $productCount--;
                            }
                            $data['is_valid'] = 1;
							$data['reach_threshold'] = $valid['threshold'];
                            $data['sum_promotions_price'] = $sum_promotions_price;
                        }
                        if ($invalid) {
                            if ($valid) $data['is_valid'] = 2;
							if ($invalid['discount_type'] == 1) {//免减
								$data['differ_price'] = $invalid['discount'];
							} else {
								$data['differ_discount'] = $invalid['discount'];
							}
                            $data['differ_threshold'] = bcsub((string)$invalid['threshold'], (string)($invalid['threshold_type'] == 1 ? $sumPrice : $sumCount), 0);
                        }
                    } else {//循环
                        $p = $promotions['promotions'][0] ?? [];
                        $validCount = floor(bcdiv((string)($p['threshold_type'] == 1 ? $sumPrice : $sumCount), (string)$p['threshold'], 2));
                        if ($validCount) {//满足次数
                            $promotionsDiscount = $this->computedDiscount($p['discount'], 100, 2);
                            $sum_promotions_price = 0;
                            if ($p['discount_type'] == 2 && $p['threshold_type'] == 2) {//满n件 打n折
                                // $suprplusDiscount = bcsub((string)$productCount, bcmul((string)$validCount, (string)$p['threshold'], 0), 0);
                                //商品价格升序
                                array_multisort(array_column($promotionsProductArr, 'price'), SORT_ASC, $promotionsProductArr); 
                                $minPrice = $promotionsProductArr[0]['price'] ?? 0;
                                //算出n件实际优惠金额
                                $sum_promotions_price = (float)bcmul((string)$minPrice, (string)$promotionsDiscount, 2);
                                if ($sum_promotions_price < 0.01) {
                                    $sum_promotions_price = 0;
                                }
                                $sum_promotions_price = bcmul((string)$validCount, (string)$sum_promotions_price, 2);
                            } else {
                                if ($p['discount_type'] == 1) {//免减（n元、n件）
                                    $sum_promotions_price = bcmul((string)$validCount, (string)$p['discount'], 2);
                                } else {//打折
                                    if ($p['threshold_type'] == 1) {//满n元
                                        $sum_promotions_price = bcmul((string)bcmul((string)$validCount, (string)$p['threshold'], 2), (string)$promotionsDiscount, 2);
                                    }
                                }
                            }
                            $useCount = 0;
                            foreach ($promotionsProductArr as $product) {
                                $price = $product['price'];
                                $product_id = $product['product_id'];
                                $unique = $product['product_attr_unique'];
                                // if ($p['discount_type'] == 2 && $p['threshold_type'] == 2) { //满n件打n折
                                //     if ($p['threshold'] > $useCount) {
                                //         $promotions_true_price = (float)bcmul((string)$price, (string)$promotionsDiscount, 2);
                                //         if ($promotions_true_price < 0.01) {//实际优惠小于0.01 就不计算优惠
                                //             $promotions_true_price = 0;
                                //         }
                                //     } else {
                                //         $promotions_true_price = 0;
                                //     }
                                //     $sum_promotions_price = bcadd((string)$sum_promotions_price, (string)$promotions_true_price, 2);
                                //     $useCount = (int)bcadd((string)$useCount, (string)$product['cart_num']);
                                // } else {
                                    if ($productCount > 1) {
                                        $promotions_true_price = bcmul((string)bcdiv((string)$price, (string)$sumPrice, 4), (string)$sum_promotions_price, 2);
                                        $compute_price = bcadd((string)$compute_price, (string)bcmul((string)$promotions_true_price, (string)$product['cart_num'], 2), 2);
                                    } else {
                                        $one_promotions_sum_price = bcsub((string)$sum_promotions_price, (string)$compute_price, 2);
                                        $promotions_true_price = bcdiv((string)$one_promotions_sum_price, (string)$product['cart_num'], 2);
                                    }
                                // }
                                $data[$unique] = ['product_id' => $product_id, 'uniqid' => $unique, 'price' => $price, 'promotions_true_price' => $promotions_true_price];
                                $productCount--;
                            }
                            $data['is_valid'] = 1;
							$data['reach_threshold'] = $p['threshold'];
                            $data['sum_promotions_price'] = $sum_promotions_price;
                        } else {
							if ($p['discount_type'] == 1) {//免减
								$data['differ_price'] = $p['discount'];
							} else {
								$data['differ_discount'] = $p['discount'];
							}
                            $data['differ_threshold'] = bcsub((string)$p['threshold'], (string)($p['threshold_type'] == 1 ? $sumPrice : $sumCount), 0);
                        }
                    }
                    break;
                case 4://满送
                    
                    break;
                default:
                    break;
            }
            $data['give'] = ['give_integral' => 0, 'give_coupon' => [], 'give_product' => []];
            $computedArr[$promotions_id] = $data;
        }
        return $computedArr;
    }

    /**
    * 使用优惠卷
    * @param int $couponId
    * @param int $uid
    * @param array $cartInfo
    * @param array $promotions
    * @return array
     */
    public function useCoupon(int $couponId, int $uid, array $cartInfo, array $promotions)
    {
        //使用优惠劵
        $couponPrice = 0;
        $couponInfo = [];
        if ($couponId && $cartInfo) {
            /** @var StoreCouponUserServices $couponServices */
            $couponServices = app()->make(StoreCouponUserServices::class);
            $couponInfo = $couponServices->getOne([['id', '=', $couponId], ['uid', '=', $uid], ['is_fail', '=', 0], ['status', '=', 0], ['start_time', '<=', time()], ['end_time', '>=', time()]], '*', ['issue']);
            if (!$couponInfo) {
                throw new ValidateException('选择的优惠劵无效!');
            }
            $type = $couponInfo['applicable_type'] ?? 0;
            $flag = false;
            $price = 0;
            $count = 0;
            $promotionsList = [];
            if($promotions){
                $promotionsList = array_combine(array_column($promotions, 'id'), $promotions);
            }
            $isOverlay = function($cart) use ($promotionsList) {
                if (isset($cart['promotions_id']) && $cart['promotions_id']) {
                    foreach ($cart['promotions_id'] as $key => $promotions_id) {
                        $promotions = $promotionsList[$promotions_id] ?? [];
                        if ($promotions && $promotions['promotions_type'] != 4){
                            $overlay = is_string($promotions['overlay']) ? explode(',', $promotions['overlay']) : $promotions['overlay'];
                            if (!in_array(5, $overlay)) {
                                return false;
                            }
                        }
                    }
                }
                return true;
            };
            switch ($type) {
                case 0:
                case 3:
                    foreach ($cartInfo as $cart) {
                        if (!$isOverlay($cart))  continue;
                        $price = bcadd((string)$price, bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 2), 2);
                        $count++;
                    }
                    break;
                case 1://品类券
                    /** @var StoreCategoryServices $storeCategoryServices */
                    $storeCategoryServices = app()->make(StoreCategoryServices::class);
                    $cateGorys = $storeCategoryServices->getAllById((int)$couponInfo['category_id']);
                    if ($cateGorys) {
                        $cateIds = array_column($cateGorys, 'id');
                        foreach ($cartInfo as $cart) {
                            if (!$isOverlay($cart))  continue;
                            if (isset($cart['productInfo']['cate_id']) && array_intersect(explode(',', $cart['productInfo']['cate_id']), $cateIds)) {
                                $price = bcadd((string)$price, (string)bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 2), 2);
                                $count++;
                            }
                        }
                    }
                    break;
                case 2:
                    foreach ($cartInfo as $cart) {
                        if (!$isOverlay($cart))  continue;
                        if (isset($cart['product_id']) && in_array($cart['product_id'], explode(',', $couponInfo['product_id']))) {
                            $price = bcadd((string)$price, bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 2), 2);
                            $count++;
                        }
                    }
                    break;
            }
            if ($count && $couponInfo['use_min_price'] <= $price) {
                $flag = true;
            }
            if (!$flag) {
                throw new ValidateException('不满足优惠劵的使用条件!');
            }
            //满减券
            if ($couponInfo['coupon_type'] == 1) {
                $couponPrice = $couponInfo['coupon_price'];
            } else {
                if ($couponInfo['coupon_price'] <= 0) {//0折
                    $couponPrice = $price;
                } else if ($couponInfo['coupon_price'] >= 100) {
                    $couponPrice = 0;
                } else {
                    $truePrice = (float)bcmul((string)$price, bcdiv((string)$couponInfo['coupon_price'], '100', 2), 2);
                    $couponPrice = (float)bcsub((string)$price, (string)$truePrice, 2);
                }
            }
        }
        return [$couponInfo, $couponPrice];
    }

    /**
    * 实际获取优惠活动赠送赠品
    * @param int $uid
    * @param array $cartList
    * @param array $computedArr
    * @param array $promotionsDetail
    * @param array $productDetails
    * @param array $promotionsArr
    * @param bool $isCart
    * @return array
     */
    public function getPromotionsGive(int $uid, array $cartList, array $computedArr, array $promotionsDetail, array $productDetails, array $promotionsArr, bool $isCart = false)
    {
        $usePromotionsIds = [];
        if (!$cartList || !$promotionsDetail) {
            return [$cartList, $computedArr, $usePromotionsIds];
        }
        foreach ($promotionsDetail as $promotions_id => $productIds) {
            $promotions = $promotionsArr[$promotions_id] ?? [];
            $productCount = count($productIds);
            if (!$promotions || !$productCount || $promotions['promotions_type'] != 4) continue;
            //验证商品规格是否满足活动
            [$productIds, $uniques] = $this->checkProductCanUsePromotions($productIds, $cartList, $promotions);
            if (!$productIds) {
                continue;
            }
            $promotions_type = (int)$promotions['promotions_type'] ?? 1;
            [$sumPrice, $sumCount, $promotionsProductArr] = $this->getPromotionsProductInfo($promotions_type, $productIds, $cartList, $uniques, (int)$promotions['product_partake_type'], true);

            $give_product_id = [];
            $give_coupon_ids = [];
            $give_integral = 0;
            $data = ['is_valid' => 0, 'reach_threshold' => 0, 'differ_threshold' => 0, 'promotions_type' => $promotions['promotions_type'], 'sum_promotions_price' => 0, 'product_id' => []];
            if ($promotions['promotions_cate'] == 1) {//阶梯
                $valid = $invalid = [];
                foreach ($promotions['promotions'] as $key => $p) {
                    if (($p['threshold_type'] == 1 ? $sumPrice : $sumCount) >= $p['threshold']) {
                        $valid = $p;
                    } else {
                        $invalid = $p;
                        break;
                    }
                }
                if ($valid) {
                    if ($valid['give_integral']) {
                        $give_integral = bcadd((string)$give_integral, (string)$valid['give_integral'], 0);
                    }
                    if ($valid['give_coupon_id']) {
                        $coupon_ids = is_string($valid['give_coupon_id']) ? explode(',', $valid['give_coupon_id']) : $valid['give_coupon_id'];
                        $give_coupon_ids = array_merge($give_coupon_ids, $coupon_ids);
                    }
                    if ($valid['giveProducts']) {
                        foreach ($valid['giveProducts'] as $value) {
                            if (isset($give_product_id[$value['unique']])) {
                                $give_product_id[$value['unique']]['cart_num'] = $give_product_id[$value['unique']]['cart_num'] + 1;
                            } else {
                                $give_product_id[$value['unique']] = ['unique' => $value['unique'] , 'product_id' => $value['product_id'], 'cart_num' => 1];
                            }
                        }
                    }
                    $data['is_valid'] = 1;
					$data['reach_threshold'] = $valid['threshold'];
                }
                if ($invalid) {
                    if ($valid) $data['is_valid'] = 2;
                    $data['differ_threshold'] = bcsub((string)$invalid['threshold'], (string)($invalid['threshold_type'] == 1 ? $sumPrice : $sumCount), 0);
                }
            } else {//循环
                $p = $promotions['promotions'][0] ?? [];
                $validCount = floor(bcdiv((string)($p['threshold_type'] == 1 ? $sumPrice : $sumCount), (string)$p['threshold'], 2));
                if ($validCount) {//满足次数
                    if ($p['give_integral']) {
                        $give_integral = bcadd((string)$give_integral, (string)bcmul((string)$validCount, (string)$p['give_integral'], 0), 0);
                    }
                    if ($p['give_coupon_id']) {
                        $coupon_ids = is_string($p['give_coupon_id']) ? explode(',', $p['give_coupon_id']) : $p['give_coupon_id'];
                        $give_coupon_ids = array_merge($give_coupon_ids, $coupon_ids);
                    }
                    if ($p['giveProducts']) {
                        foreach ($p['giveProducts'] as $value) {
                            if (isset($give_product_id[$value['unique']])) {
                                $give_product_id[$value['unique']]['cart_num'] = bcadd((string)$give_product_id[$value['unique']]['cart_num'], (string)$validCount);
                            } else {
                                $give_product_id[$value['unique']] = ['unique' => $value['unique'] , 'product_id' => $value['product_id'], 'cart_num' => $validCount];
                            }
                        }
                    }
                    $data['is_valid'] = 1;
					$data['reach_threshold'] = $p['threshold'];
                } else {
                    $data['differ_threshold'] = bcsub((string)$p['threshold'], (string)($p['threshold_type'] == 1 ? $sumPrice : $sumCount), 0);
                }
            }
            $ids = [];
            //验证优惠券限量
            if ($give_coupon_ids) {
                foreach ($give_coupon_ids as $give_coupon_id) {
                    foreach ($promotions['promotions'] as $value) {
                        $giveCoupon = $value['giveCoupon'] ?? [];
                        if ($giveCoupon) $giveCoupon = array_combine(array_column($giveCoupon, 'coupon_id'), $giveCoupon);
                        if ($giveCoupon && ($giveCoupon[$give_coupon_id]['surplus_num'] ?? 0) >= 1) {
                            $ids[] = $give_coupon_id;
                            break;
                        }
                    }
                }
            }
            $data['give'] = ['give_integral' => $give_integral, 'give_coupon' => $ids, 'give_product' => $give_product_id];
            $computedArr[$promotions_id] = $data;
            if(!$isCart && $data['is_valid'] > 0){
                $usePromotionsIds[] = $promotions_id;
            } else {
                $usePromotionsIds[] = $promotions_id;
            }
        }
        foreach ($cartList as &$cart) {
            $product_id = $cart['product_id'] ?? 0;
            $promotionsIds = $productDetails[$product_id] ?? [];
            if (!$promotionsIds) {
                continue;
            }
            $useIds = array_intersect($promotionsIds, $usePromotionsIds);
            $cart['promotions_id'] = array_unique(array_merge($cart['promotions_id'] ?? [], $useIds));
        }
        return [$cartList, $computedArr, $usePromotionsIds];
    }

    /**
     * 生成赠送商品购物车数据
     * @param int $uid
     * @param int $promotions_id
     * @param array $giveProduct
     * @param array promotions
     * @param int $store_id
     * @return array
     */
    public function createGiveProductCart(int $uid, int $promotions_id, array $giveProduct, array $promotions, int $store_id = 0)
    {
        $cart = [];
        if ($giveProduct) {
            /** @var StoreOrderCreateServices $storeOrderCreateService */
            $storeOrderCreateService = app()->make(StoreOrderCreateServices::class);
            /** @var StoreCartServices $storeCartServices */
            $storeCartServices = app()->make(StoreCartServices::class);
            $promotionsArr = $promotions['promotions'] ?? [];
            foreach ($giveProduct as $unique => $give) {
                $product_id = $give['product_id'] ?? 0;
                $cart_num = $give['cart_num'] ?? 1;
                if (!$product_id) {
                    continue;
                }
                try {
                    if ($store_id) {
                        [$attrInfo, $product_attr_unique, $bargainPriceMin, $cart_num, $productInfo] = $storeCartServices->checkCashierProductStock($store_id, $uid, (int)$product_id, (int)$cart_num, $unique);
                    } else {
                        [$attrInfo, $product_attr_unique, $bargainPriceMin, $cart_num, $productInfo] = $storeCartServices->checkProductStock($uid, (int)$product_id, (int)$cart_num, $unique);
                    }
                } catch (\Throwable $e) {
                    continue;
                }
                $is_limit = false;
                foreach ($promotionsArr as $key => $value) {
                    $giveProducts = $value['giveProducts'] ?? [];
                    if ($giveProducts) $giveProducts = array_combine(array_column($giveProducts, 'product_id'), $giveProducts);
                    if ($giveProducts && $cart_num <= ($giveProducts[$product_id]['surplus_num'] ?? 0)) {
                        $is_limit = true;
                        break;
                    }
                }
                if (!$is_limit) {
                    continue;
                }
                
                $key = $storeOrderCreateService->getNewOrderId((string)$uid);
                $info['id'] = $key;
                $info['type'] = 0;
                $info['product_type'] = $productInfo['product_type'];
                $info['promotions_id'] = [$promotions_id];
                $info['activity_id'] = 0;
                $info['discount_product_id'] = 0;
                $info['product_id'] = $product_id;
                $info['is_gift'] = 1;
                $info['is_valid'] = 1;
                $info['product_attr_unique'] = $product_attr_unique;
                $info['cart_num'] = $cart_num;
                $info['productInfo'] = $productInfo ? $productInfo->toArray() : [];
                $info['productInfo']['express_delivery'] = false;
                $info['productInfo']['store_mention'] = false;
                $info['productInfo']['store_delivery'] = false;
                if (isset($info['productInfo']['delivery_type'])) {
                    $info['productInfo']['delivery_type'] = is_string($info['productInfo']['delivery_type']) ? explode(',', $info['productInfo']['delivery_type']) : $info['productInfo']['delivery_type'];
                    if (in_array(1, $info['productInfo']['delivery_type'])) {
                        $info['productInfo']['express_delivery'] = true;
                    }
                    if (in_array(2, $info['productInfo']['delivery_type'])) {
                        $info['productInfo']['store_mention'] = true;
                    }
                    if (in_array(3, $info['productInfo']['delivery_type'])) {
                        $info['productInfo']['store_delivery'] = true;
                    }
                }
                $info['productInfo']['attrInfo'] = $attrInfo->toArray();
                $info['attrStatus'] = $info['productInfo']['attrInfo'] ? true : false;
                $info['sum_price'] = $info['productInfo']['attrInfo']['price'] ?? $info['productInfo']['price'] ?? 0;
                $info['truePrice'] = 0;
                $info['vip_truePrice'] = 0;
                $info['trueStock'] = $info['productInfo']['attrInfo']['stock'] ?? 0;
                $info['costPrice'] = $info['productInfo']['attrInfo']['cost'] ?? 0;
                $info['limit_num'] = $giveProducts[$product_id]['limit_num'] ?? 0;

                $cart[] = $info;
            }
        }
        return $cart;
    }

    /**
     * 根据类型获取商品列表
     * @param int $type
     * @param int $uid
     * @param int $promotions_id
     * @param int $store_id
     * @param int $staff_id
     * @param int $tourist_uid
     * @return array|array[]|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTypeList(int $type, int $uid, int $promotions_id = 0)
    {
        $store_id = (int)$this->getItem('store_id', 0);
        $staff_id = (int)$this->getItem('staff_id', 0);
        $tourist_uid = (int)$this->getItem('tourist_uid', 0);
        $store_name = $this->getItem('store_name', '');
        $ids = $this->getItem('ids', []);
        $not_ids = $this->getItem('not_ids', []);
        $where = [];
        $where['type'] = 1;
        $where['store_id'] = 0;
        $where['pid'] = 0;
        $where['is_del'] = 0;
        $where['status'] = 1;
        $where['promotionsTime'] = true;
        $where['promotions_type'] = $type;
        if ($promotions_id) {
            $where['id'] = $promotions_id;
        }
        $product_where = [];
        $product_where['store_name'] = $store_name;
        $product_where['ids'] = $ids;
        $product_where['not_ids'] = $not_ids;
        //门店不展示卡密商品
        $product_where['product_type'] = [0, 2];
        $product_where['is_show'] = 1;
        $product_where['is_del'] = 0;
        $product_where['store_id'] = $store_id;
        //存在一个全部商品折扣优惠活动 直接返回商品

        if ($ids && !$this->dao->count($where + ['product_partake_type' => 1])) {
            //正选并集
            $mergeIds = function ($promotions) {
                $data = [];
                foreach ($promotions as $item) {
                    $productIds = is_string($item['product_id']) ? explode(',', $item['product_id']) : $item['product_id'];
                    $data = array_merge($data, $productIds);
                }
                return $data;
            };
            $promotions = $this->getList($where + ['product_partake_type' => 2], 'id,product_id');
            $pIds = $promotions ? $mergeIds($promotions) : [];
            $product_where['ids'] = $pIds ? array_intersect($ids, $pIds) : $ids;
            $notPromotions = $this->getList($where + ['product_partake_type' => 3], 'id,product_id');
            //反选交集
            /** @var StorePromotionsAuxiliaryServices $auxiliaryService */
            $auxiliaryService = app()->make(StorePromotionsAuxiliaryServices::class); 
            $intersectIds = function ($promotions) use ($auxiliaryService) {
                $data = [];
                foreach ($promotions as $item) {
                    $productIds = is_string($item['product_id']) ? explode(',', $item['product_id']) : $item['product_id'];
                    if(!$productIds) {
                        continue;
                    }
                    $productIds = $auxiliaryService->getColumn(['promotions_id' => $item['id'], 'type' => 1, 'is_all' => 1, 'product_id' => $productIds], 'product_id', '', true);
                    if(!$productIds) {
                        continue;
                    }
                    if ($data) {
                        $data = array_intersect($data, $productIds);
                    } else {
                        $data = $productIds;
                    }
                }
                return $data;
            };
            $product_where['not_ids'] = array_merge($product_where['not_ids'], $notPromotions ? $intersectIds($notPromotions) : []);
        }
        $list = [];
        $count = 0;
        if ($product_where['ids'] && $this->dao->count($where)) {
            /** @var StoreBranchProductServices $branchProductServices */
            $branchProductServices = app()->make(StoreBranchProductServices::class);
            return $branchProductServices->getCashierProductListV1($product_where, $uid, 0, $tourist_uid);
        }
        return compact('list', 'count');
    }

    /**
     * 获取凑单商品ids
     * @param int $promotions_id
     * @return array
     */ 
    public function collectOrderProduct(int $promotions_id)
    {
        $product_where = [];
        $promotions = $this->dao->get($promotions_id, ['*']);
        if ($promotions) {
            $promotions = $promotions->toArray();
            switch ($promotions['product_partake_type']) {
                case 1:
                    break;
                case 2:
                    $product_where['ids'] = is_string($promotions['product_id']) ? explode(',', $promotions['product_id']) : $promotions['product_id'];
                    break;
                case 3:
                    $ids = is_string($promotions['product_id']) ? explode(',', $promotions['product_id']) : $promotions['product_id'];
                    if ($ids) {//商品全部规格 不参与 才不显示该商品
                        /** @var StorePromotionsAuxiliaryServices $auxiliaryService */
                        $auxiliaryService = app()->make(StorePromotionsAuxiliaryServices::class);
                        $ids = $auxiliaryService->getColumn(['promotions_id' => $promotions['id'], 'type' => 1, 'is_all' => 1, 'product_id' => $ids], 'product_id', '', true);
                    }
                    $product_where['not_ids'] = $ids;
                    break;
            }
        }
        return $product_where;
    }

}
