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

namespace app\services\product\branch;


use app\dao\product\branch\StoreBranchProductDao;
use app\services\BaseServices;
use app\services\order\StoreCartServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\product\category\StoreCategoryServices;
use app\services\user\UserServices;
use app\webscoket\SocketPush;
use crmeb\exceptions\AdminException;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;

/**
 * Class StoreBranchProductServices
 * @package app\services\product\branch
 */
class StoreBranchProductServices extends BaseServices
{

    use ServicesTrait;

    /**
     * StoreBranchProductServices constructor.
     * @param StoreBranchProductDao $dao
     */
    public function __construct(StoreBranchProductDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取收银台商品列表
     * @param array $where
     * @param int $uid
     * @param int $staff_id
     * @param int $tourist_uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCashierProductListV1(array $where, int $uid = 0, int $staff_id = 0, int $tourist_uid = 0)
    {
        [$page, $limit] = $this->getPageValue();
		$store_id = (int)($where['store_id'] ?? 0);
        $unShowProdcutIds = $this->dao->getColumn(['type' => 7, 'store_id' => $store_id], 'product_id', '', true);
        if ($unShowProdcutIds) {
            if (isset($where['not_ids']) && $where['not_ids']) {
                $where['not_ids'] = array_unique(array_merge($where['not_ids'], $unShowProdcutIds));
            } else {
                $where['not_ids'] = $unShowProdcutIds;
            }
        }
        $where['is_vip_product'] = 0;
        $where['is_presale_product'] = 0;
        if ($uid) {
            /** @var UserServices $user */
            $user = app()->make(UserServices::class);
            $userInfo = $user->getUserCacheInfo($uid);
            $is_vip = $userInfo['is_money_level'] ?? 0;
            $where['is_vip_product'] = $is_vip ? -1 : 0;
        }
        //门店不展示卡密商品
        $where['product_type'] = [0, 2];
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        $list = $storeProductServices->getSearchList($where, $page, $limit, ['*'], 'sort desc,sales desc,id desc', ['storeBranchProduct' => function ($query) use ($where) {
            $query->where('store_id', $where['store_id']);
        }]);
        $count = 0;
        if ($list) {
            $productIds = array_column($list, 'id');
            if ($uid || $tourist_uid) {
				if ($uid) {
					$tourist_uid = 0;
				}
                /** @var StoreCartServices $cartServices */
                $cartServices = app()->make(StoreCartServices::class);
                $cartNumList = $cartServices->productIdByCartNum($productIds, $uid, $staff_id, $tourist_uid, $store_id);
                $data = [];
                foreach ($cartNumList as $item) {
                    $data[$item['product_id']][] = $item['cart_num'];
                }
                $newNumList = [];
                foreach ($data as $key => $item) {
                    $newNumList[$key] = array_sum($item);
                }
                $cartNumList = $newNumList;
            } else {
                $cartNumList = [];
            }
            $product = ['image' => '', 'id' => 0, 'store_name' => '', 'spec_type' => 0, 'store_info' => '', 'keyword' => '', 'price' => 0, 'stock' => 0, 'sales' => 0];
            $list = $storeProductServices->getProduceOtherList($list, $uid, true);
            $list = $storeProductServices->getProductPromotions($list);
            /** @var StoreCouponIssueServices $couponServices */
            $couponServices = app()->make(StoreCouponIssueServices::class);
            /** @var StoreCategoryServices $storeCategoryService */
            $storeCategoryService = app()->make(StoreCategoryServices::class);
            foreach ($list as &$item) {
                $product = array_merge($product, array_intersect_key($item, $product));
                $item['product'] = $product;
                $item['product_id'] = $item['id'];
                $item['stock'] = $item['storeBranchProduct']['stock'] ?? 0;
                $item['sales'] = $item['storeBranchProduct']['sales'] ?? 0;
                $item['cart_num'] = $cartNumList[$item['id']] ?? 0;

                $cateId = $item['cate_id'];
                $cateId = explode(',', $cateId);
                $cateId = array_merge($cateId, $storeCategoryService->cateIdByPid($cateId));
                $cateId = array_diff($cateId, [0]);
                $counpons = $couponServices->getPcIssueCouponList($uid, $cateId, $item['id'], 'id,coupon_title,coupon_price,use_min_price', 0, 1, 'coupon_price desc,sort desc,id desc');
                $item['coupon'] = $counpons[0] ?? [];
            }
            $count = $storeProductServices->getCount($where);
        }
        $code = $where['store_name'];
        $attrValue = $userInfo = null;
        if ($code) {
            /** @var StoreProductAttrValueServices $attrService */
            $attrService = app()->make(StoreProductAttrValueServices::class);
            $attrValue = $attrService->getOne(['bar_code' => $code]);
            if ($attrValue) {
                $attrValue = $attrValue->toArray();
            }
            if (!$attrValue) {
                /** @var UserServices $userService */
                $userService = app()->make(UserServices::class);
                $userInfo = $userService->get(['bar_code' => $code]);
                if ($userInfo) {
                    $userInfo = $userInfo->toArray();
                    $list = [];
                    $count = 0;
                }
            }
        }
        return compact('list', 'count', 'attrValue', 'userInfo');
    }

    /**
     * 获取收银台商品列表
     * @param array $where
     * @param int $uid
     * @param int $staff_id
     * @param int $tourist_uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCashierProductList(array $where, int $uid = 0, int $staff_id = 0, int $tourist_uid = 0)
    {
        [$page, $limit] = $this->getPageValue();

        if (isset($where['field_key']) && ($where['field_key'] == 'bar_code' || !$where['field_key']) && $where['store_name']) {
            /** @var StoreProductServices $storeProductServices */
            $storeProductServices = app()->make(StoreProductServices::class);
            $where['ids'] = $storeProductServices->getColumn(['bar_code' => $where['store_name']], 'id', '', true);
            if ($where['ids']) {
                unset($where['store_name']);
            }
        }

        $list = $this->dao->getSerach($where)
            ->page($page, $limit)
            ->with(['product' => function ($query) {
                $query->field(['image', 'id', 'store_name', 'spec_type', 'store_info', 'keyword', 'price', 'stock', 'sales']);
            }])
            ->order('sort desc,id desc')
            ->select()
            ->toArray();
        $productIds = array_column($list, 'product_id');
        if ($uid) {
            /** @var StoreCartServices $cartServices */
            $cartServices = app()->make(StoreCartServices::class);
            $cartNumList = $cartServices->productIdByCartNum($productIds, $uid, $staff_id, $tourist_uid);
            $data = [];
            foreach ($cartNumList as $item) {
                $data[$item['product_id']][] = $item['cart_num'];
            }
            $newNumList = [];
            foreach ($data as $key => $item) {
                $newNumList[$key] = array_sum($item);
            }
            $cartNumList = $newNumList;
        } else {
            $cartNumList = [];
        }
        if ($list) {
            /** @var StoreProductServices $storeProductServices */
            $storeProductServices = app()->make(StoreProductServices::class);
            $list = $storeProductServices->getProduceOtherList($list, $uid, true);
            $list = $storeProductServices->getProductPromotions($list);
            /** @var StoreCouponIssueServices $couponServices */
            $couponServices = app()->make(StoreCouponIssueServices::class);
            /** @var StoreCategoryServices $storeCategoryService */
            $storeCategoryService = app()->make(StoreCategoryServices::class);
            foreach ($list as &$item) {
                $product = $item['product'];
                unset($product['id']);
                $item = array_merge($item, $product);
                // $item['image'] = $item['product']['image'];
                // $item['store_name'] = $item['product']['store_name'];
                // $item['store_info'] = $item['product']['store_info'];
                // $item['keyword'] = $item['product']['keyword'];
                // $item['price'] = $item['product']['price'];
                $item['cart_num'] = $cartNumList[$item['product_id']] ?? 0;

                $cateId = $item['cate_id'];
                $cateId = explode(',', $cateId);
                $cateId = array_merge($cateId, $storeCategoryService->cateIdByPid($cateId));
                $cateId = array_diff($cateId, [0]);
                $counpons = $couponServices->getPcIssueCouponList($uid, $cateId, $item['id'], 'id,coupon_title,coupon_price,use_min_price', 0, 1, 'coupon_price desc,sort desc,id desc');
                $item['coupon'] = $counpons[0] ?? [];
            }
        }
        $count = $this->dao->getSerach($where)->count();
        return compact('list', 'count');
    }

    /**
     * 获取商品详情
     * @param int $storeId
     * @param int $id
     * @param int $uid
     * @param int $touristUid
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductDetail(int $storeId, int $id, int $uid, int $touristUid)
    {
        /** @var StoreProductServices $productService */
        $productService = app()->make(StoreProductServices::class);
        $storeInfo = $productService->getOne(['id' => $id, 'is_show' => 1, 'is_del' => 0], '*');
        if (!$storeInfo) {
            throw new ValidateException('商品不存在');
        } else {
            $storeInfo = $storeInfo->toArray();
        }
        $siteUrl = sys_config('site_url');
        $storeInfo['image'] = set_file_url($storeInfo['image'], $siteUrl);
        $storeInfo['image_base'] = set_file_url($storeInfo['image'], $siteUrl);
        $storeInfo['fsales'] = $storeInfo['ficti'] + $storeInfo['sales'];

        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        $storeProductAttrServices->setItem('touristUid', $touristUid);
        [$productAttr, $productValue] = $storeProductAttrServices->getProductAttrDetail($id, $uid, 1, 0, 0, $storeInfo);
        $storeProductAttrServices->reset();

        if (!$storeInfo['spec_type']) {
            $productAttr = [];
            $productValue = [];
        }
        /** @var StoreBranchProductAttrValueServices $attrService */
        $attrService = app()->make(StoreBranchProductAttrValueServices::class);
        $attrValue = $attrService->getProductAttrUnique($storeId, $id);
        foreach ($productValue as &$value) {
            $value['stock'] = $attrValue[$value['unique']] ?? 0;
        }
        $data['productAttr'] = $productAttr;
        $data['productValue'] = $productValue;
        $data['storeInfo'] = get_thumb_water($storeInfo, 'big', ['image', 'slider_image']);

        return $data;
    }

    /**
     * 保存或者修改门店数据
     * @param int $id
     * @param int $storeId
     * @param int $stock
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveStoreProduct(int $id, int $storeId, int $stock, array $data = [])
    {
        /** @var StoreProductServices $service */
        $service = app()->make(StoreProductServices::class);
        $productData = $service->get($id, ['store_name', 'image', 'sort', 'store_info', 'keyword', 'bar_code', 'cate_id', 'is_show']);
        if (!$productData) {
            throw new ValidateException('商品不穿在');
        }
        $productData = $productData->toArray();
        $productInfo = $this->dao->get(['product_id' => $id, 'store_id' => $storeId]);
        if ($productInfo) {
            $productInfo->label_id = isset($data['label_id']) ? implode(',', $data['label_id']) : '';
            $productInfo->is_show = $data['is_show'] ?? 1;
            $productInfo->stock = $stock;
            $productInfo->image = $productData['image'];
            $productInfo->sort = $productData['sort'];
            $productInfo->store_name = $productData['store_name'];
            $productInfo->store_info = $productData['store_info'];
            $productInfo->keyword = $productData['keyword'];
            $productInfo->bar_code = $productData['bar_code'];
            $productInfo->cate_id = $productData['cate_id'];
            $productInfo->save();
        } else {
            $product = [];
            $product['product_id'] = $id;
            $product['label_id'] = isset($data['label_id']) ? implode(',', $data['label_id']) : '';
            $product['is_show'] = $data['is_show'] ?? 1;
            $product['store_id'] = $storeId;
            $product['stock'] = $stock;
            $product['image'] = $productData['image'];
            $product['sort'] = $productData['sort'];
            $product['store_name'] = $productData['store_name'];
            $product['store_info'] = $productData['store_info'];
            $product['keyword'] = $productData['keyword'];
            $product['bar_code'] = $productData['bar_code'];
            $product['cate_id'] = $productData['cate_id'];
            $product['add_time'] = time();
            $this->dao->save($product);
        }
        return true;
    }

    /**
     * 商品是否存在
     * @param $productId
     * @param $storeId
     * @return bool
     */
    public function isValidProduct(int $productId, int $storeId)
    {
        return $this->dao->getOne(['product_id' => $productId, 'store_id' => $storeId, 'is_del' => 0, 'is_show' => 1]);
    }

    /**
     * 获取商品库存
     * @param int $productId
     * @param string $uniqueId
     * @return int|mixed
     */
    public function getProductStock(int $productId, int $storeId, string $uniqueId = '')
    {
        /** @var  StoreBranchProductAttrValueServices $StoreProductAttrValue */
        $StoreProductAttrValue = app()->make(StoreBranchProductAttrValueServices::class);
        return $uniqueId == '' ?
            $this->dao->value(['id' => $productId], 'stock') ?: 0
            : $StoreProductAttrValue->uniqueByStock($uniqueId, $storeId);
    }

    /**
     * 回退｜扣除，门店、平台原商品库存
     * @param $order
     * @param array $cartInfo
     * @param int $platDec
     * @param int $storeDec
     * @return bool
     */
    public function regressionBranchProductStock($order, $cartInfo = [], int $platDec = 0, int $storeDec = 0, int $store_id = 0)
    {
        if (!$order || !$cartInfo) return true;
        /** @var StoreProductServices $services */
        $services = app()->make(StoreProductServices::class);
        /** @var StoreBranchProductServices $storeBranchService */
        $storeBranchService = app()->make(StoreBranchProductServices::class);
        /** @var StoreProductAttrValueServices $skuValueServices */
        $skuValueServices = app()->make(StoreProductAttrValueServices::class);
        $activity_id = (int)$order['activity_id'];
        $store_id = $store_id ? $store_id : ((int)$order['store_id'] ?? 0);
        $res = true;
        foreach ($cartInfo as $cart) {
            //增库存减销量
            $unique = isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '';
            $cart_num = (int)$cart['cart_num'];
            $product_id = (int)$cart['product_id'];
            switch ($order['type']) {
                case 0://普通
                case 6://预售
                    $productUnique = $unique;
                    break;
                case 1://秒杀
                case 2://砍价
                case 3://拼团
                case 5://套餐
                    $suk = $skuValueServices->value(['unique' => $unique, 'product_id' => $activity_id, 'type' => $order['type']], 'suk');
                    $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $product_id, 'type' => 0], 'unique');
                    break;
                default:
                    $productUnique = $unique;
                    break;
            }
            switch ($platDec) {
                case -1://不执行
                    break;
                case 0://减销量、加库存
                    $res = $res && $services->incProductStock($cart_num, $product_id, $productUnique);
                    break;
                case 1://增加销量、减库存
                    $res = $res && $services->decProductStock($cart_num, $product_id, $productUnique);
                    break;
            }
            switch ($storeDec) {
                case -1://不执行
                    break;
                case 0://减销量、加库存
                    $res = $res && $storeBranchService->updataDecStock($cart_num, $product_id, $store_id, $productUnique, false);
                    break;
                case 1://增加销量、减库存
                    $res = $res && $storeBranchService->updataDecStock($cart_num, $product_id, $store_id, $productUnique);
                    break;
            }
        }

        return $res;
    }

    /**
     * 加库存,减销量
     * @param $num
     * @param $productId
     * @param string $unique
     * @return bool
     */
    public function incProductStock(array $cartInfo, int $storeId)
    {
        $res = true;
        foreach ($cartInfo as $cart) {
            $unique = isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '';
            $res = $res && $this->updataDecStock((int)$cart['cart_num'], (int)$cart['productInfo']['id'], $storeId, $unique, false);
        }
        return $res;
    }

    /**
     * 修改库存
     * @param array $cartInfo
     * @param int $storeId
     * @param bool $dec
     * @return bool
     */
    public function decProductStock(array $cartInfo, int $storeId, bool $dec = true)
    {
        $res = true;
        foreach ($cartInfo as $cart) {
            $unique = isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '';
            $res = $res && $this->updataDecStock((int)$cart['cart_num'], (int)$cart['productInfo']['id'], $storeId, $unique, $dec);
        }
        return $res;
    }


    /**
     * 修改库存
     * @param int $num
     * @param int $productId
     * @param int $storeId
     * @param $unique
     * @param bool $dec
     * @return bool
     */
    public function updataDecStock(int $num, int $productId, int $storeId, $unique, bool $dec = true)
    {
        $res = true;
        if ($dec) {
            if ($unique) {
                /** @var StoreBranchProductAttrValueServices $skuValueServices */
                $skuValueServices = app()->make(StoreBranchProductAttrValueServices::class);
                $res = $res && $skuValueServices->decProductAttrStock($storeId, $productId, $unique, $num, 0);
            }
            $res = $res && $this->dao->decStockIncSales(['store_id' => $storeId, 'product_id' => $productId], $num);
            if ($res) {
//                $this->workSendStock($productId, $storeId);
            }
        } else {
            if ($unique) {
                /** @var StoreBranchProductAttrValueServices $skuValueServices */
                $skuValueServices = app()->make(StoreBranchProductAttrValueServices::class);
                $res = $res && $skuValueServices->incProductAttrStock($storeId, $productId, $unique, $num, 0);
            }
            $res = $res && $this->dao->incStockDecSales(['store_id' => $storeId, 'product_id' => $productId], $num);
        }
        return $res;
    }

    /**
     * 库存预警发送消息
     * @param int $productId
     * @param int $storeId
     */
    public function workSendStock(int $productId, int $storeId)
    {
        $stock = $this->dao->value(['id' => $productId], 'stock');
        $store_stock = sys_config('store_stock') ?? 0;//库存预警界限
        if ($store_stock >= $stock) {
            try {
                SocketPush::store()->type('STORE_STOCK')->to($storeId)->data(['id' => $productId])->push();
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * 上下架
     * @param int $store_id
     * @param int $id
     * @param int $is_show
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setShow(int $store_id, int $id, int $is_show)
    {
        $info = $this->dao->get(['store_id' => $store_id, 'product_id' => $id]);
        if ($info) {
            $res = $this->update($info['id'], ['is_show' => $is_show]);
            if (!$res) throw new AdminException('操作失败！');
        } else {
            /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
            $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
            /** @var StoreBranchProductAttrValueServices $storeBranchProductAttrValueServices */
            $storeBranchProductAttrValueServices = app()->make(StoreBranchProductAttrValueServices::class);
            $attr = $storeProductAttrValueServices->getProductAttrValue(['type' => 0, 'product_id' => $id]);
            if (!$attr) throw new AdminException('该商品规格不存在！');
            foreach ($attr as &$item) {
                $item['stock'] = 0;
                $item['sales'] = 0;
            }
            $data = [];
            $data['attrs'] = $attr;
            $data['label_id'] = [];
            $data['is_show'] = $is_show;
            $storeBranchProductAttrValueServices->updataAll($id, $data, $store_id);
        }
    }

    /**
     * 门店同步库存
     * @param $ids
     * @param $storeId
     * @return mixed
     */
    public function synchStocks($ids, $storeId)
    {
        /** @var StoreProductServices $productServices */
        $productServices = app()->make(StoreProductServices::class);
        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);
        /** @var StoreBranchProductAttrValueServices $services */
        $branchAttrValueServices = app()->make(StoreBranchProductAttrValueServices::class);
        $productAllData = $productServices->getColumn([['id', 'in', $ids]], 'id,image,store_name,store_info,keyword,bar_code,cate_id,stock,label_id', 'id');
        $productBranchData = $this->dao->getColumn([['product_id', 'in', $ids], ['store_id', '=', $storeId]], 'product_id');
        $allData = $attrValueServices->getColumn([['product_id', 'in', $ids], ['type', '=', 0]], 'product_id,unique,stock,bar_code', 'unique');
        $branchData = $branchAttrValueServices->getColumn([['product_id', 'in', $ids], ['store_id', '=', $storeId]], 'unique');
        return $this->transaction(function () use ($allData, $branchData, $productAllData, $productBranchData, $storeId, $branchAttrValueServices) {
            $data = [];
            $res = true;
            $datas = [];
            foreach ($productAllData as $keys => $items) {
                if (in_array($keys, $productBranchData)) {
                    $res = $res && $this->dao->update(['product_id' => $keys, 'store_id' => $storeId], [
                            'stock' => $items['stock'],
                            'image' => $items['image'],
                            'store_name' => $items['store_name'],
                            'store_info' => $items['store_info'],
                            'keyword' => $items['keyword'],
                            'bar_code' => $items['bar_code'],
                            'cate_id' => $items['cate_id'],
                            'label_id' => $items['label_id'],
                        ]);
                } else {
                    $datas[] = [
                        'product_id' => $items['id'],
                        'image' => $items['image'],
                        'store_name' => $items['store_name'],
                        'store_info' => $items['store_info'],
                        'keyword' => $items['keyword'],
                        'bar_code' => $items['bar_code'],
                        'cate_id' => $items['cate_id'],
                        'label_id' => $items['label_id'],
                        'store_id' => $storeId,
                        'stock' => $items['stock'],
                        'add_time' => time()
                    ];
                }
            }
            if ($datas) {
                $res = $res && $this->dao->saveAll($datas);
            }
            foreach ($allData as $key => $item) {
                if (in_array($key, $branchData)) {
                    $res = $res && $branchAttrValueServices->update(['unique' => $key, 'store_id' => $storeId], ['stock' => $item['stock']]);
                } else {
                    $data[] = [
                        'product_id' => $item['product_id'],
                        'store_id' => $storeId,
                        'unique' => $item['unique'],
                        'stock' => $item['stock'],
                        'bar_code' => $item['bar_code']
                    ];
                }
            }
            if ($data) {
                $res = $res && $branchAttrValueServices->saveAll($data);
            }
            if (!$res) throw new ValidateException('同步库存失败!');
            return $res;
        });
    }
}
