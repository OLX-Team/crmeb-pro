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
namespace app\services\product\product;

use app\dao\product\product\StoreProductDao;
use app\jobs\product\ProductStockTips;
use app\Request;
use app\services\activity\discounts\StoreDiscountsProductsServices;
use app\services\activity\discounts\StoreDiscountsServices;
use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\combination\StoreCombinationServices;
use app\services\activity\promotions\StorePromotionsServices;
use app\services\activity\seckill\StoreSeckillServices;
use app\services\BaseServices;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\order\StoreCartServices;
use app\services\other\QrcodeServices;
use app\services\product\category\StoreCategoryServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\brand\StoreBrandServices;
use app\services\other\queue\QueueServices;
use app\services\product\ensure\StoreProductEnsureServices;
use app\services\product\label\StoreProductLabelServices;
use app\services\product\sku\StoreProductAttrResultServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\product\sku\StoreProductRuleServices;
use app\services\product\shipping\ShippingTemplatesServices;
use app\services\product\sku\StoreProductVirtualServices;
use app\services\product\specs\StoreProductSpecsServices;
use app\services\user\label\UserLabelServices;
use app\services\user\level\SystemUserLevelServices;
use app\services\user\member\MemberCardServices;
use app\services\user\UserSearchServices;
use app\services\user\UserServices;
use app\jobs\product\ProductLogJob;
use crmeb\exceptions\AdminException;
use crmeb\services\GroupDataService;
use crmeb\services\SystemConfigService;
use crmeb\traits\ServicesTrait;
use crmeb\traits\OptionTrait;
use think\exception\ValidateException;
use think\facade\Config;

/**
 * Class StoreProductService
 * @package app\services\product\product
 * @method getSearchList(array $where, int $page = 0, int $limit = 0, ?array $field = ['*'], string $order = '', array $with = ['couponId', 'description']) 获取列表
 * @method getCid(int $page, int $limit) 获取一级分类ID
 * @method getUseEnsureCount(int $ensure_id) 获取使用某服务保障商品数量
 */
class StoreProductServices extends BaseServices
{
    use OptionTrait, ServicesTrait;

    /**
     * StoreProductServices constructor.
     * @param StoreProductDao $dao
     */
    public function __construct(StoreProductDao $dao)
    {
        $this->dao = $dao;
    }

    /**
 	* 获取顶部标签
	* @param int $store_id
	* @param array $where
	* @return array[]
	 */
    public function getHeader(int $store_id = 0, array $where = [])
    {
        if ($store_id) {
            $where['product_type'] = [0, 2];
            /** @var StoreBranchProductServices $storeBranchProductServics */
            $storeBranchProductServics = app()->make(StoreBranchProductServices::class);
            //仓库中的商品
            $forsale = $storeBranchProductServics->count(['type' => 2, 'store_id' => $store_id] + $where);
            //出售中的商品
            $onsale = $this->dao->getCount(['type' => 1] + $where);
            $onsale = $onsale < $forsale ? 0 : $onsale - $forsale;
            //已经售馨商品
            $outofstock = $storeBranchProductServics->count(['type' => 4, 'store_id' => $store_id] + $where);
            //警戒库存商品
            $policeforce = $storeBranchProductServics->count(['type' => 5, 'store_id' => $store_id, 'store_stock' => sys_config('store_stock') > 0 ? sys_config('store_stock') : 2] + $where);
            //回收站的商品
            $plat_recycle = $this->dao->getCount(['type' => 6] + $where);
            //仓库中的商品
            $plat_forsale = $this->dao->getCount(['type' => 2] + $where);
            $recycle = $plat_recycle + $plat_forsale;

        } else {
            //出售中的商品
            $onsale = $this->dao->getCount(['type' => 1]+ $where);
            //已经售馨商品
            $outofstock = $this->dao->getCount(['type' => 4] + $where);
            //警戒库存商品
            $policeforce = $this->dao->getCount(['type' => 5, 'store_stock' => sys_config('store_stock') > 0 ? sys_config('store_stock') : 2] + $where);
            //仓库中的商品
            $forsale = $this->dao->getCount(['type' => 2] + $where);
            //回收站的商品
            $recycle = $this->dao->getCount(['type' => 6] + $where);
        }
        return [
            ['type' => 1, 'name' => '销售中', 'count' => $onsale],
            ['type' => 2, 'name' => '仓库中', 'count' => $forsale],
            ['type' => 4, 'name' => '已售罄', 'count' => $outofstock],
            ['type' => 5, 'name' => '库存预警', 'count' => $policeforce],
            ['type' => $store_id ? 7 : 6, 'name' => $store_id ? '平台下架/删除' : '回收站', 'count' => $recycle]
        ];
    }

    /**
     * 获取列表
     * @param $where
     * @return array
     */
    public function getList(array $where)
    {
        $store_stock = sys_config('store_stock', 0);
        $where['store_stock'] = $store_stock > 0 ? $store_stock : 2;
        [$page, $limit] = $this->getPageValue();
        $cateIds = [];
        if (isset($where['cate_id']) && $where['cate_id']) {
            /** @var StoreCategoryServices $storeCategory */
            $storeCategory = app()->make(StoreCategoryServices::class);
            $cateIds = $storeCategory->getColumn(['pid' => $where['cate_id']], 'id');
        }
        if ($cateIds) {
            $cateIds[] = $where['cate_id'];
            $where['cate_id'] = $cateIds;
        }
        $order_string = '';
        $order_arr = ['asc', 'desc'];
        if (isset($where['sales']) && in_array($where['sales'], $order_arr)) {
            $order_string = 'sales ' . $where['sales'];
        }
        //门店不展示卡密商品
        //门店不展示卡密商品
        if (isset($where['store_id']) && $where['store_id']) {
            $where['product_type'] = [0, 2];
            if (in_array($where['type'], [1, 2, 4, 5])) {
                /** @var StoreBranchProductServices $storeBranchProductServics */
                $storeBranchProductServics = app()->make(StoreBranchProductServices::class);
                $branch_where = [];
                $branch_where['store_id'] = $where['store_id'];
                $branch_where['type'] = $where['type'] == 1 ? 2 : $where['type'];
                if ($where['type'] == 5) {
                    $branch_where['store_stock'] = $where['store_stock'];
                }
                $where['ids'] = $storeBranchProductServics->getColumn($branch_where, 'product_id', '', true);
                if ($where['type'] != 1 && !$where['ids']) {
                    $list = [];
                    $count = 0;
                    return compact('list', 'count');
                }
                if ($where['type'] != 1)
                    unset($where['type']);
            } else if ($where['type'] == 7) {
                unset($where['store_id']);
            }
        }
        $count = $this->dao->getCount($where);
        //页面搜索，第二页之后没有结果，强制返回第一页数据
        if ($count <= $limit && $page !== 1) {
            $page = 1;
        }
        $list = $this->dao->getList($where, $page, $limit, $order_string);
        $cateIds = implode(',', array_column($list, 'cate_id'));
        /** @var StoreCategoryServices $categoryService */
        $categoryService = app()->make(StoreCategoryServices::class);
        $cateList = $categoryService->getCateParentAndChildName($cateIds);
        foreach ($list as &$item) {
            $item['branch_sales'] = $item['branch_sales'] ?? 0;
            $item['branch_stock'] = $item['branch_stock'] ?? 0;
            $item['is_show'] = $item['branch_is_show'] ?? $item['is_show'];
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
            $item['stock_attr'] = $item['stock'] > 0 ? true : false;//库存
        }
        return compact('list', 'count');
    }

    /**
     * 设置商品上下架
     * @param $ids
     * @param $is_show
     */
    public function setShow(array $ids, int $is_show)
    {
        if ($is_show == 0) {
            //下架检测是否有参与活动商品
            $this->checkActivity($ids);
        } else {
            $count = $this->dao->getCount(['ids' => $ids, 'is_del' => 1]);
            if ($count) throw new AdminException('回收站商品无法直接上架，请先恢复商品');
        }
        /** @var StoreCartServices $cartService */
        $cartService = app()->make(StoreCartServices::class);
        $cartService->batchUpdate($ids, ['status' => $is_show], 'product_id');
        $update = ['is_show' => $is_show];
        if ($is_show) {//手动上架 清空定时下架状态
            $update['auto_off_time'] = 0;
        }
        $this->dao->batchUpdate($ids, $update);
        /** @var StoreProductCateServices $storeProductCateServices */
        $storeProductCateServices = app()->make(StoreProductCateServices::class);
        $storeProductCateServices->batchUpdate($ids, ['status' => $is_show], 'product_id');
        //门店商品处理
        /** @var StoreBranchProductServices $storeBranchProductServices */
        $storeBranchProductServices = app()->make(StoreBranchProductServices::class);
        $storeBranchProductServices->batchUpdate($ids, ['is_del' => $is_show ? 0 : 1], 'product_id');
        event('product.status', [$ids, $is_show]);
        return true;
    }

    /**
     * 队列批量上下架
     * @param array $ids
     * @param int $is_show
     * @param $redisKey
     * @param $queueId
     */
    public function setBatchShow(array $ids, int $is_show, $redisKey, $queueId)
    {
        $res = $this->dao->batchUpdate($ids, ['is_show' => $is_show]);
        /** @var StoreProductCateServices $storeProductCateServices */
        $storeProductCateServices = app()->make(StoreProductCateServices::class);
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $re = true;
        if ($is_show == 0) {
            try {
                //下架检测是否有参与活动商品
                $re = $this->checkActivity($ids);
                //改变购物车中状态
                $storeProductCateServices->batchUpdate($ids, ['status' => $is_show], 'product_id');
                /** @var StoreCartServices $cartService */
                $cartService = app()->make(StoreCartServices::class);
                foreach ($ids as $id) {
                    $cartService->changeStatus($id, 1);
                }
            } catch (\Throwable $e) {
                $re = false;
            }
        }
        if ($re) $storeProductCateServices->batchUpdate($ids, ['status' => $is_show], 'product_id');
        $queueService->doSuccessSremRedis($ids, $redisKey, $queueId['type']);
        if (!$res) {
            $queueService->addQueueFail($queueId['id'], $redisKey);
        }
        return true;
    }

    /**
     * 获取规格模板
     * @return array
     */
    public function getRule()
    {
        /** @var StoreProductRuleServices $storeProductRuleServices */
        $storeProductRuleServices = app()->make(StoreProductRuleServices::class);
        $list = $storeProductRuleServices->getList()['list'];
        foreach ($list as &$item) {
            $item['rule_value'] = json_decode($item['rule_value'], true);
        }
        return $list;
    }

    public function getStoreBranchInfo(int $id, int $store_id)
    {
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        /** @var StoreProductAttrServices $storeProductAttrService */
        $storeProductAttrService = app()->make(StoreProductAttrServices::class);
        /** @var UserLabelServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelServices::class);
        $productInfo = $this->dao->getBranchProduct(['id' => $id, 'store_id' => $store_id]);
        $productInfo = $productInfo ? $productInfo->toArray() : [];
        $productAttr = $storeProductAttrService->getProductAttr(['product_id' => $id, 'type' => 0]);
        $attr = $storeProductAttrValueServices->storeBranchAttr(['store_id' => $store_id, 'product_id' => $id, 'type' => 0]);
        $sukValue = [];
        foreach ($attr as $key => $item) {
            $sukValue[$item['suk']] = $item;
        }
        $productInfo['sales'] = $productInfo['storeBranchProduct']['sales'] ?? 0;
        $productInfo['stock'] = $productInfo['storeBranchProduct']['stock'] ?? 0;
        $productInfo['is_show'] = $productInfo['storeBranchProduct']['is_show'] ?? $productInfo['is_show'];
        $label_id = $productInfo['storeBranchProduct']['label_id'] ?? '';
        $label_id = explode(',', $label_id);
        $productInfo['label_id'] = $userLabelServices->getLabelList(['ids' => $label_id], ['id', 'label_name']);
        if (!$productAttr) return [];
        $attr = [];
        foreach ($productAttr as $key => $value) {
            $attr[$key]['value'] = $value['attr_name'];
            $attr[$key]['detailValue'] = '';
            $attr[$key]['attrHidden'] = true;
            $attr[$key]['detail'] = $value['attr_values'];
        }
        $value = attr_format($attr)[1];
        $valueNew = [];
        $count = 0;
        foreach ($value as $key => $item) {
            $detail = $item['detail'];
            $suk = implode(',', $item['detail']);
            if (!isset($sukValue[$suk])) continue;
            foreach (array_keys($detail) as $k => $title) {
                $header[$k]['title'] = $title;
                $header[$k]['align'] = 'center';
                $header[$k]['minWidth'] = 80;
            }
            $valueNew[$count]['value'] = '';
            foreach (array_values($detail) as $k => $v) {
                $valueNew[$count]['value' . ($k + 1)] = $v;
                $header[$k]['key'] = 'value' . ($k + 1);
                $valueNew[$count]['value'] .= $valueNew[$count]['value'] == '' ? $v : '，' . $v;
            }
            $valueNew[$count]['detail'] = $detail;
            $valueNew[$count]['pic'] = $sukValue[$suk]['image'];
            $valueNew[$count]['unique'] = $sukValue[$suk]['unique'];
            $valueNew[$count]['price'] = floatval($sukValue[$suk]['price']);
            $valueNew[$count]['cost'] = floatval($sukValue[$suk]['cost']);
            $valueNew[$count]['ot_price'] = floatval($sukValue[$suk]['ot_price']);
            if ($store_id) {
                $valueNew[$count]['stock'] = $sukValue[$suk]['branch_stock'] ? intval($sukValue[$suk]['branch_stock']) : 0;
            } else {
                $valueNew[$count]['stock'] = intval($sukValue[$suk]['stock']);
            }
            $valueNew[$count]['quota'] = intval($sukValue[$suk]['quota']);
            $valueNew[$count]['bar_code'] = $sukValue[$suk]['bar_code'];
            $valueNew[$count]['code'] = $sukValue[$suk]['code'];
            $valueNew[$count]['weight'] = $sukValue[$suk]['weight'] ? floatval($sukValue[$suk]['weight']) : 0;
            $valueNew[$count]['volume'] = $sukValue[$suk]['volume'] ? floatval($sukValue[$suk]['volume']) : 0;
            $valueNew[$count]['brokerage'] = $sukValue[$suk]['brokerage'] ? floatval($sukValue[$suk]['brokerage']) : 0;
            $valueNew[$count]['brokerage_two'] = $sukValue[$suk]['brokerage_two'] ? floatval($sukValue[$suk]['brokerage_two']) : 0;
            $count++;
        }
        $header[] = ['title' => '图片', 'slot' => 'pic', 'align' => 'center', 'minWidth' => 120];
        $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '原价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '库存', 'slot' => 'stock', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '限量', 'key' => 'quota', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '重量(KG)', 'key' => 'weight', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '体积(m³)', 'key' => 'volume', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '商品条形码', 'key' => 'bar_code', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '商品编码', 'key' => 'code', 'align' => 'center', 'minWidth' => 80];
        $attrs = ['items' => $attr, 'attrs' => $valueNew, 'header' => $header];
        return ['storeInfo' => $productInfo, 'attrs' => $attrs];
    }

    /**
     * 获取商品详情
     * @param int $id
     * @return array|\think\Model|null
     */
    public function getInfo(int $id)
    {
        /** @var StoreCategoryServices $storeCatecoryService */
        $storeCatecoryService = app()->make(StoreCategoryServices::class);
        /** @var StoreDescriptionServices $storeDescriptionServices */
        $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
        /** @var StoreProductAttrResultServices $storeProductAttrResultServices */
        $storeProductAttrResultServices = app()->make(StoreProductAttrResultServices::class);
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        /** @var StoreProductCouponServices $storeProductCouponServices */
        $storeProductCouponServices = app()->make(StoreProductCouponServices::class);
        /** @var StoreCouponIssueServices $storeCouponIssueServices */
        $storeCouponIssueServices = app()->make(StoreCouponIssueServices::class);
        /** @var UserLabelServices $userLabelServices */
        $userLabelServices = app()->make(UserLabelServices::class);
        $data['tempList'] = $this->getTemp();
        $data['cateList'] = $storeCatecoryService->cascaderList(1);
        $productInfo = $this->dao->getInfo($id);
        if ($productInfo) $productInfo = $productInfo->toArray();
        else throw new ValidateException('商品不存在');
        $couponIds = $productInfo['coupons'] ? array_column($productInfo['coupons'], 'issue_coupon_id') : [];
        $is_sub = $recommend = [];
        if ($productInfo['is_sub'] == 1) array_push($is_sub, 1);
        if ($productInfo['is_vip'] == 1) array_push($is_sub, 0);
        if ($productInfo['is_hot'] == 1) array_push($recommend, 'is_hot');
        if ($productInfo['is_benefit'] == 1) array_push($recommend, 'is_benefit');
        if ($productInfo['is_new'] == 1) array_push($recommend, 'is_new');
        if ($productInfo['is_good'] == 1) array_push($recommend, 'is_good');
        if ($productInfo['is_best'] == 1) array_push($recommend, 'is_best');
        $productInfo['is_sub'] = $is_sub;
        $productInfo['recommend'] = $recommend;
        $productInfo['price'] = floatval($productInfo['price']);
        $productInfo['postage'] = floatval($productInfo['postage']);
        $productInfo['ot_price'] = floatval($productInfo['ot_price']);
        $productInfo['vip_price'] = floatval($productInfo['vip_price']);
        $productInfo['cost'] = floatval($productInfo['cost']);
        $productInfo['brand_id'] = $productInfo['brand_com'] ? array_map('intval', explode(',', $productInfo['brand_com'])) : [];
        $productInfo['video_open'] = $productInfo['video_link'] != '' ? true : false;
        $productInfo['coupons'] = $storeCouponIssueServices->productCouponList([['id', 'in', $couponIds]], 'title,id');
        $productInfo['cate_id'] = is_array($productInfo['cate_id']) ? $productInfo['cate_id'] : explode(',', $productInfo['cate_id']);
        if ($productInfo['label_id']) {
            $label_id = is_array($productInfo['label_id']) ? $productInfo['label_id'] : explode(',', $productInfo['label_id']);
            $productInfo['label_id'] = $userLabelServices->getLabelList(['ids' => $label_id], ['id', 'label_name']);
        } else {
            $productInfo['label_id'] = [];
        }
        if ($productInfo['store_label_id']) {
            /** @var StoreProductLabelServices $storeProductLabelServices */
            $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
            $productInfo['store_label_id'] = $storeProductLabelServices->getColumn([['id', 'in', $productInfo['store_label_id']]], 'id,label_name');
        } else {
            $productInfo['store_label_id'] = [];
        }
        $productInfo['give_integral'] = floatval($productInfo['give_integral']);
        $productInfo['presale_time'] = $productInfo['presale_start_time'] == 0 ? [] : [date('Y-m-d H:i:s', $productInfo['presale_start_time']), date('Y-m-d H:i:s', $productInfo['presale_end_time'])];
        $productInfo['auto_on_time'] = $productInfo['is_show'] ? '' : ($productInfo['auto_on_time'] ? date('Y-m-d H:i:s', $productInfo['auto_on_time']) : '');
        $productInfo['auto_off_time'] = !$productInfo['is_show'] ? '' : ($productInfo['auto_off_time'] ? date('Y-m-d H:i:s', $productInfo['auto_off_time']) : '');
        $productInfo['description'] = $storeDescriptionServices->getDescription(['product_id' => $id, 'type' => 0]);
        $productInfo['custom_form'] = is_string($productInfo['custom_form']) ? json_decode($productInfo['custom_form'], true) : $productInfo['custom_form'];
        //无属性添加默认属性
        if (!$storeProductAttrResultServices->getResult(['product_id' => $id, 'type' => 0])) {
            $attr = [
                [
                    'value' => '规格',
                    'detailValue' => '',
                    'attrHidden' => '',
                    'detail' => ['默认']
                ]
            ];
            $detail[0] = [
                'value1' => '默认',
                'detail' => ['规格' => '默认'],
                'pic' => $productInfo['image'],
                'price' => $productInfo['price'],
                'cost' => $productInfo['cost'],
                'ot_price' => $productInfo['ot_price'],
                'stock' => $productInfo['stock'],
                'bar_code' => '',
                'weight' => 0,
                'volume' => 0,
                'brokerage' => 0,
                'brokerage_two' => 0,
                'code' => 0,
            ];
            /** @var StoreProductAttrServices $storeProductAttrServices */
            $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
            $skuList = $storeProductAttrServices->validateProductAttr($attr, $detail, $id);
            $storeProductAttrServices->saveProductAttr($skuList, $id, 0);
            $this->dao->update($id, ['spec_type' => 0]);
        }
        if ($productInfo['spec_type'] == 1) {
            $result = $storeProductAttrResultServices->getResult(['product_id' => $id, 'type' => 0]);
            foreach ($result['value'] as $k => $v) {
                $num = 1;
                foreach ($v['detail'] as $dv) {
                    $result['value'][$k]['value' . $num] = $dv;
                    $num++;
                }
            }
            $productInfo['items'] = $result['attr'];
            $productInfo['attrs'] = $result['value'];
            $productInfo['attr'] = ['pic' => '', 'vip_price' => 0, 'price' => 0, 'cost' => 0, 'ot_price' => 0, 'stock' => 0, 'bar_code' => '', 'weight' => 0, 'volume' => 0, 'brokerage' => 0, 'brokerage_two' => 0, 'code' => ''];
        } else {
            /** @var StoreProductVirtualServices $virtualService */
            $virtualService = app()->make(StoreProductVirtualServices::class);
            $result = $storeProductAttrValueServices->getOne(['product_id' => $id, 'type' => 0]);
            $productInfo['items'] = [];
            $productInfo['attrs'] = [];
            $productInfo['attr'] = [
                'pic' => $result['image'] ?? '',
                'vip_price' => $result['vip_price'] ? floatval($result['vip_price']) : 0,
                'price' => $result['price'] ? floatval($result['price']) : 0,
                'cost' => $result['cost'] ? floatval($result['cost']) : 0,
                'ot_price' => $result['ot_price'] ? floatval($result['ot_price']) : 0,
                'stock' => $result['stock'] ? floatval($result['stock']) : 0,
                'bar_code' => $result['bar_code'] ?? '',
                'code' => $result['code'] ?? '',
                'virtual_list' => $virtualService->getArr($result['unique'], $id),
                'weight' => $result['weight'] ? floatval($result['weight']) : 0,
                'volume' => $result['volume'] ? floatval($result['volume']) : 0,
                'brokerage' => $result['brokerage'] ? floatval($result['brokerage']) : 0,
                'brokerage_two' => $result['brokerage_two'] ? floatval($result['brokerage_two']) : 0,
                'disk_info' => $result['disk_info']
            ];
        }
        if ($productInfo['activity']) {
            $activity = explode(',', $productInfo['activity']);
            foreach ($activity as $k => $v) {
                if ($v == 1) {
                    $activity[$k] = '秒杀';
                } elseif ($v == 2) {
                    $activity[$k] = '砍价';
                } elseif ($v == 3) {
                    $activity[$k] = '拼团';
                } elseif ($v == 0) {
                    $activity[$k] = '默认';
                }
            }
            $productInfo['activity'] = $activity;
        } else {
            $productInfo['activity'] = ['默认', '秒杀', '砍价', '拼团'];
        }
        //推荐产品
        $recommend_list = [];
        if ($productInfo['recommend_list'] != '') {
            $productInfo['recommend_list'] = explode(',', $productInfo['recommend_list']);
            if (count($productInfo['recommend_list'])) {
                $images = $this->getColumn([['id', 'in', $productInfo['recommend_list']]], 'image', 'id');
                foreach ($productInfo['recommend_list'] as $item) {
                    $recommend_list[] = [
                        'product_id' => $item,
                        'image' => $images[$item]
                    ];
                }
            }
        }
        $productInfo['recommend_list'] = $recommend_list;
        $data['productInfo'] = $productInfo;
        return $data;
    }

    /**
     * 获取运费模板列表
     * @return array
     */
    public function getTemp()
    {
        /** @var ShippingTemplatesServices $shippingTemplatesServices */
        $shippingTemplatesServices = app()->make(ShippingTemplatesServices::class);
        return $shippingTemplatesServices->getSelectList();
    }

    /**
     * 获取商品规格
     * @param array $data
     * @param int $id
     * @param int $type
     * @return array
     */
    public function getAttr(array $data, int $id, int $type, int $store_id = 0)
    {

        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $productInfo = [];
        if ($id) {
            $productInfo = $this->dao->get($id);
            if (!$productInfo) {
                throw new ValidateException('商品不存在');
            }
        }
        /** @var StoreProductVirtualServices $virtualService */
        $virtualService = app()->make(StoreProductVirtualServices::class);
        $attr = $data['attrs'];
        $product_type = $productInfo['product_type'] ?? $data['product_type'] ?? 0; //商品类型
        $value = attr_format($attr)[1];
        $valueNew = [];
        $count = 0;
        foreach ($value as $key => $item) {
            $detail = $item['detail'];
            foreach ($detail as $v => $d) {
                $detail[$v] = trim($d);
            }
//            sort($item['detail'], SORT_STRING);
            $suk = implode(',', $detail);
            $types = 1;
            if ($id) {
                $sukValue = $storeProductAttrValueServices->getSkuArray(['product_id' => $id, 'type' => 0, 'suk' => $suk], 'unique,bar_code,code,cost,price,ot_price,stock,image as pic,weight,volume,brokerage,brokerage_two,vip_price,disk_info', 'suk');
                if (!$sukValue) {
                    if ($type == 0) $types = 0; //编辑商品时，将没有规格的数据不生成默认值
                    $sukValue[$suk]['pic'] = '';
                    $sukValue[$suk]['price'] = 0;
                    $sukValue[$suk]['cost'] = 0;
                    $sukValue[$suk]['ot_price'] = 0;
                    $sukValue[$suk]['stock'] = 0;
                    $sukValue[$suk]['bar_code'] = '';
                    $sukValue[$suk]['code'] = '';
                    if (in_array($product_type, [1, 2])) {
                        if ($product_type == 1) {
                            $sukValue[$suk]['virtual_list'] = [];
                        } elseif ($product_type == 2) {
                            $sukValue[$suk]['coupon_id'] = 0;
                        }
                    }
                    $sukValue[$suk]['weight'] = 0;
                    $sukValue[$suk]['volume'] = 0;
                    $sukValue[$suk]['brokerage'] = 0;
                    $sukValue[$suk]['brokerage_two'] = 0;
                }
//                if($store_id !== 0){
//                    $branchValue = $branchProductAttrValueServices->get(['product_id' => $id, 'type' => 0, 'unique' => $sukValue['unique'],'store_id'=>$store_id]);
//                }
            } else {
                $sukValue[$suk]['pic'] = '';
                $sukValue[$suk]['price'] = 0;
                $sukValue[$suk]['cost'] = 0;
                $sukValue[$suk]['ot_price'] = 0;
                $sukValue[$suk]['stock'] = 0;
                $sukValue[$suk]['bar_code'] = '';
                $sukValue[$suk]['code'] = '';
                if (in_array($product_type, [1, 2])) {
                    if ($product_type == 1) {
                        $sukValue[$suk]['virtual_list'] = [];
                    } elseif ($product_type == 2) {
                        $sukValue[$suk]['coupon_id'] = 0;
                    }
                }
                $sukValue[$suk]['weight'] = 0;
                $sukValue[$suk]['volume'] = 0;
                $sukValue[$suk]['brokerage'] = 0;
                $sukValue[$suk]['brokerage_two'] = 0;
            }
            if ($types) { //编辑商品时，将没有规格的数据不生成默认值
                foreach (array_keys($detail) as $k => $title) {
                    $header[$k]['title'] = $title;
                    $header[$k]['align'] = 'center';
                    $header[$k]['minWidth'] = 130;
                }
                $values = '';
                foreach (array_values($detail) as $k => $v) {
                    $valueNew[$count]['value' . ($k + 1)] = $v;
                    $header[$k]['slot'] = 'value' . ($k + 1);
                    $values .= $v . ',';
                }
                $valueNew[$count]['values'] = substr($values, 0, strlen($values) - 1);
                $valueNew[$count]['detail'] = $detail;
                $valueNew[$count]['pic'] = $sukValue[$suk]['pic'] ?? '';
                $valueNew[$count]['price'] = $sukValue[$suk]['price'] ? floatval($sukValue[$suk]['price']) : 0;
                $valueNew[$count]['cost'] = $sukValue[$suk]['cost'] ? floatval($sukValue[$suk]['cost']) : 0;
                $valueNew[$count]['ot_price'] = isset($sukValue[$suk]['ot_price']) ? floatval($sukValue[$suk]['ot_price']) : 0;
                $valueNew[$count]['vip_price'] = isset($sukValue[$suk]['vip_price']) ? floatval($sukValue[$suk]['vip_price']) : 0;
                $valueNew[$count]['stock'] = $sukValue[$suk]['stock'] ? intval($sukValue[$suk]['stock']) : 0;
                $valueNew[$count]['bar_code'] = $sukValue[$suk]['bar_code'] ?? '';
                $valueNew[$count]['code'] = $sukValue[$suk]['code'] ?? '';
                if ($product_type == 1 && !$type) {
                    $valueNew[$count]['virtual_list'] = isset($sukValue[$suk]['unique']) && $sukValue[$suk]['unique'] ? $virtualService->getArr($sukValue[$suk]['unique'], $id) : [];
                    $valueNew[$count]['disk_info'] = $sukValue[$suk]['disk_info'] ?? '';
                }
                $valueNew[$count]['weight'] = floatval($sukValue[$suk]['weight']) ?? 0;
                $valueNew[$count]['volume'] = floatval($sukValue[$suk]['volume']) ?? 0;
                $valueNew[$count]['brokerage'] = floatval($sukValue[$suk]['brokerage']) ?? 0;
                $valueNew[$count]['brokerage_two'] = floatval($sukValue[$suk]['brokerage_two']) ?? 0;
                $count++;
            }
        }
        $header[] = ['title' => '图片', 'slot' => 'pic', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '售价', 'slot' => 'price', 'align' => 'center', 'minWidth' => 120];
        $header[] = ['title' => '成本价', 'slot' => 'cost', 'align' => 'center', 'minWidth' => 140];
        $header[] = ['title' => '原价', 'slot' => 'ot_price', 'align' => 'center', 'minWidth' => 140];
//        $header[] = ['title' => '会员价', 'slot' => 'vip_price', 'align' => 'center', 'minWidth' => 140];
        $header[] = ['title' => '库存', 'slot' => 'stock', 'align' => 'center', 'minWidth' => 140];
        $header[] = ['title' => '商品条形码', 'slot' => 'bar_code', 'align' => 'center', 'minWidth' => 140];
        $header[] = ['title' => '商品编码', 'slot' => 'code', 'align' => 'center', 'minWidth' => 140];
        switch ($product_type) {
            case 0:
                $header[] = ['title' => '重量(KG)', 'slot' => 'weight', 'align' => 'center', 'minWidth' => 140];
                $header[] = ['title' => '体积(m³)', 'slot' => 'volume', 'align' => 'center', 'minWidth' => 140];
                break;
            case 1://卡密
                $header[] = ['title' => '卡密商品', 'slot' => 'fictitious', 'align' => 'center', 'minWidth' => 140];
                break;
            case 2://优惠券
                break;
            case 3://虚拟商品
                break;
            default:
                break;
        }
        $header[] = ['title' => '操作', 'slot' => 'action', 'align' => 'center', 'minWidth' => 70];
        return ['attr' => $attr, 'value' => $valueNew, 'header' => $header, 'product_type' => $product_type];
    }

    /**
     * SPU
     * @return string
     */
    public function createSpu()
    {
        mt_srand();
        return substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8) . str_pad((string)mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * 新增编辑商品
     * @param int $id
     * @param array $data
     */
    public function save(int $id, array $data)
    {
        if (count($data['cate_id']) < 1) throw new AdminException('请选择商品分类');
        if (!$data['store_name']) throw new AdminException('请输入商品名称');
        if (count($data['slider_image']) < 1) throw new AdminException('请上传商品轮播图');
        if (in_array($data['product_type'], [1, 2, 3])) {
            $data['freight'] = 2;
            $data['temp_id'] = 0;
            $data['postage'] = 0;
        } else {
            if ($data['freight'] == 1) {
                $data['temp_id'] = 0;
                $data['postage'] = 0;
            } elseif ($data['freight'] == 2) {
                $data['temp_id'] = 0;
            } elseif ($data['freight'] == 3) {
                $data['postage'] = 0;
            }
            if ($data['freight'] == 2 && !$data['postage']) {
                throw new AdminException('请设置运费金额');
            }
            if ($data['freight'] == 3 && !$data['temp_id']) {
                throw new AdminException('请选择运费模版');
            }
        }
        // 开启ERP后商品编码验证
        $isOpen = sys_config('erp_open');
        if ($isOpen && empty($data['code'])) {
            throw new AdminException('请输入商品编码');
        }
        $data['is_hot'] = $data['is_benefit'] = $data['is_new'] = $data['is_good'] = $data['is_best'] = 0;
        foreach ($data['recommend'] as $item) {
            $data[$item] = 1;
        }
        $detail = $data['attrs'];
        $attr = $data['items'];
        $cate_id = $data['cate_id'];
        $coupon_ids = $data['coupon_ids'];
        $description = $data['description'];
        $type = $data['type'];
        //品牌
        $data['brand_com'] = $data['brand_id'] ? implode(',', $data['brand_id']) : '';
        $data['brand_id'] = $data['brand_id'] ? end($data['brand_id']) : 0;
        $data['is_vip'] = in_array(0, $data['is_sub']) ? 1 : 0;
        $data['is_sub'] = in_array(1, $data['is_sub']) ? 1 : 0;
        $data['product_type'] = intval($data['product_type']);
        $data['is_vip_product'] = intval($data['is_vip_product']);
        $data['is_presale_product'] = intval($data['is_presale_product']);
        $data['presale_start_time'] = $data['is_presale_product'] ? strtotime($data['presale_time'][0]) : 0;
        $data['presale_end_time'] = $data['is_presale_product'] ? strtotime($data['presale_time'][1]) : 0;
        if ($data['presale_start_time'] && $data['presale_start_time'] < time()) {
            throw new AdminException('预售开始时间不能小于当前时间');
        }
        if ($data['presale_end_time'] && $data['presale_end_time'] < time()) {
            throw new AdminException('预售结束时间不能小于当前时间');
        }
        $data['auto_on_time'] = $data['auto_on_time'] ? strtotime($data['auto_on_time']) : 0;
        $data['auto_off_time'] = $data['auto_off_time'] ? strtotime($data['auto_off_time']) : 0;
        if ($data['auto_on_time']) {
            $data['is_show'] = 0;
        }
        $data['custom_form'] = json_encode($data['custom_form']);
        $storeLabelId = $data['store_label_id'];
        if ($data['store_label_id']) {
            $data['store_label_id'] = is_array($data['store_label_id']) ? implode(',', $data['store_label_id']) : $data['store_label_id'];
        } else {
            $data['store_label_id'] = '';
        }
        if ($data['ensure_id']) {
            $data['ensure_id'] = is_array($data['ensure_id']) ? implode(',', $data['ensure_id']) : $data['ensure_id'];
        } else {
            $data['ensure_id'] = '';
        }
        if (!$data['specs_id']) {
            $data['specs'] = '';
        }
        if ($data['specs']) {
            $specs = [];
            if (is_array($data['specs'])) {
                /** @var StoreProductSpecsServices $storeProductSpecsServices */
                $storeProductSpecsServices = app()->make(StoreProductSpecsServices::class);
                foreach ($data['specs'] as $item) {
                    $specs[] = $storeProductSpecsServices->checkSpecsData($item);
                }
                $data['specs'] = json_encode($specs);
            }
        } else {
            $data['specs'] = '';
        }
        if ($data['spec_type'] == 0) {
            $attr = [
                [
                    'value' => '规格',
                    'detailValue' => '',
                    'attrHidden' => '',
                    'detail' => ['默认']
                ]
            ];
            $detail[0]['value1'] = '默认';
            $detail[0]['detail'] = ['规格' => '默认'];
        }
        foreach ($detail as &$item) {
            if ($isOpen && empty($item['code'])) {
                throw new AdminException('请输入【' . $item['values'] . '】商品编码');
            }
            $item['product_type'] = $data['product_type'];
            if ($data['is_sub'] == 0) {
                $item['brokerage'] = 0;
                $item['brokerage_two'] = 0;
            }
            if (($item['brokerage'] + $item['brokerage_two']) > $item['price']) {
                throw new AdminException('一二级返佣相加不能大于商品售价');
            }
        }
        foreach ($data['activity'] as $k => $v) {
            if ($v == '秒杀') {
                $data['activity'][$k] = 1;
            } elseif ($v == '砍价') {
                $data['activity'][$k] = 2;
            } elseif ($v == '拼团') {
                $data['activity'][$k] = 3;
            } else {
                $data['activity'][$k] = 0;
            }
        }
        $data['activity'] = implode(',', $data['activity']);
        $data['recommend_list'] = count($data['recommend_list']) ? implode(',', $data['recommend_list']) : '';
        $data['price'] = min(array_column($detail, 'price'));
        $data['ot_price'] = min(array_column($detail, 'ot_price'));
        $data['cost'] = min(array_column($detail, 'cost'));
        if (!$data['cost']) {
            $data['cost'] = 0;
        }
        $data['cate_id'] = implode(',', $data['cate_id']);
        $data['label_id'] = implode(',', $data['label_id']);
        $data['image'] = $data['slider_image'][0];//封面图
        $slider_image = $data['slider_image'];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['stock'] = array_sum(array_column($detail, 'stock'));
        unset($data['description'], $data['coupon_ids'], $data['items'], $data['attrs'], $data['type'], $data['recommend']);
        /** @var StoreDescriptionServices $storeDescriptionServices */
        $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        /** @var StoreProductCouponServices $storeProductCouponServices */
        $storeProductCouponServices = app()->make(StoreProductCouponServices::class);
        /** @var StoreDiscountsProductsServices $storeDiscountProduct */
        $storeDiscountProduct = app()->make(StoreDiscountsProductsServices::class);
        /** @var StoreProductVirtualServices $productVirtual */
        $productVirtual = app()->make(StoreProductVirtualServices::class);
        //同一链接不多次保存
        if (!$id && $data['soure_link']) {
            $productInfo = $this->dao->getOne(['soure_link' => $data['soure_link'], 'is_del' => 0], 'id');
            if ($productInfo) $id = (int)$productInfo['id'];
        }
        [$skuList, $id, $is_new] = $this->transaction(function () use ($id, $storeLabelId, $data, $description, $cate_id, $storeDescriptionServices, $storeProductAttrServices, $storeProductCouponServices, $detail, $attr, $coupon_ids, $storeDiscountProduct, $productVirtual, $slider_image) {

            if ($id) {
                //上下架处理
                $this->setShow([$id], $data['is_show']);
                $oldInfo = $this->get($id)->toArray();
                if ($oldInfo['product_type'] != $data['product_type']) {
                    throw new AdminException('商品类型不能切换！');
                }
                unset($data['sales']);
                $res = $this->dao->update($id, $data);
                if (!$res) throw new AdminException('修改失败');
                // 修改优惠套餐商品的运费模版id
                $storeDiscountProduct->update(['product_id' => $id], ['temp_id' => $data['temp_id']]);
                if (!empty($coupon_ids)) {
                    $storeProductCouponServices->setCoupon($id, $coupon_ids);
                } else {
                    $storeProductCouponServices->delete(['product_id' => $id]);
                }
                $is_new = 1;
            } else {
                $data['add_time'] = time();
                $data['code_path'] = '';
                $data['spu'] = $this->createSpu();
                $res = $this->dao->save($data);
                if (!$res) throw new AdminException('添加失败');
                $id = (int)$res->id;
                if (!empty($coupon_ids)) $storeProductCouponServices->setCoupon($id, $coupon_ids);
                $is_new = 0;
            }
            //商品详情
            $storeDescriptionServices->saveDescription($id, $description);

            $skuList = $storeProductAttrServices->validateProductAttr($attr, $detail, $id, 0, (int)$data['is_vip']);
            foreach ($skuList['valueGroup'] as &$item) {
                if (!isset($item['sum_stock']) || !$item['sum_stock']) $item['sum_stock'] = $item['stock'] ?? 0;
            }

            $proudctVipPrice = 0;
            $detailTemp = array_column($skuList['valueGroup'], 'vip_price');
            if ($detailTemp) {
                $proudctVipPrice = min($detailTemp);
            }
            $this->dao->update($id, ['vip_price' => $proudctVipPrice]);

            $valueGroup = $storeProductAttrServices->saveProductAttr($skuList, $id);
            if (!$valueGroup) throw new AdminException('添加失败！');
            if ($data['product_type'] == 1) {
                $productVirtual->saveProductVirtual($id, $valueGroup);
            }
            return [$skuList, $id, $is_new];
        });
        $data['type'] = $type;
        event('product.create', [$id, $data, $cate_id, $storeLabelId, $skuList, $is_new, $slider_image, $description]);
    }

    /**
     * 放入回收站
     * @param int $id
     * @return string
     */
    public function del(int $id)
    {
        if (!$id) throw new AdminException('参数不正确');
        $productInfo = $this->dao->get($id);
        if (!$productInfo) throw new AdminException('商品数据不存在');
        /** @var StoreBranchProductServices $branchProductServics */
        $branchProductServics = app()->make(StoreBranchProductServices::class);
        if ($productInfo['is_del'] == 1) {
            $data['is_del'] = 0;
            $res = $this->dao->update($id, $data);
            //门店商品处理
            $branchProductServics->update(['product_id' => $id], ['is_del' => 0]);
            if (!$res) throw new AdminException('恢复失败,请稍候再试!');
            return '成功恢复商品';
        } else {
            $data['is_del'] = 1;
            $data['is_show'] = 0;
            $res = $this->dao->update($id, $data);
            /** @var StoreProductCateServices $storeProductCateServices */
            $storeProductCateServices = app()->make(StoreProductCateServices::class);
            $storeProductCateServices->update(['product_id' => $id], ['status' => 0]);
            //门店商品处理
            $branchProductServics->update(['product_id' => $id], ['is_del' => 1]);
            if (!$res) throw new AdminException('删除失败,请稍候再试!');
            return '成功移到回收站';
        }
    }

    /**
     * 获取选择的商品列表
     * @param array $where
     * @param bool $isStock
     * @param int $limit
     * @return array
     */
    public function searchList(array $where, bool $isStock = false, int $limit = 0)
    {
        $store_stock = sys_config('store_stock');
        $where['store_stock'] = $store_stock > 0 ? $store_stock : 2;
        $data = $this->getProductList($where, $isStock, $limit);
        if ($data['list']) {
            $cateIds = implode(',', array_column($data['list'], 'cate_id'));
            /** @var StoreCategoryServices $storeCategoryServices */
            $storeCategoryServices = app()->make(StoreCategoryServices::class);
            $cateList = $storeCategoryServices->getCateArray($cateIds);
            /** @var StoreProductLabelServices $storeProductLabelServices */
            $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
            foreach ($data['list'] as &$item) {
                $cateName = array_filter($cateList, function ($val) use ($item) {
                    if (in_array($val['id'], explode(',', $item['cate_id']))) {
                        return $val;
                    }
                });
                $item['cate_name'] = implode(',', array_column($cateName, 'cate_name'));
                $item['give_integral'] = floatval($item['give_integral']);
                $item['price'] = floatval($item['price']);
                $item['vip_price'] = floatval($item['vip_price']);
                $item['ot_price'] = floatval($item['ot_price']);
                $item['postage'] = floatval($item['postage']);
                $item['cost'] = floatval($item['cost']);
                $item['delivery_type'] = is_string($item['delivery_type']) ? explode(',', $item['delivery_type']) : $item['delivery_type'];
                $item['store_label'] = '';
                if ($item['store_label_id']) {
                    $storeLabelList = $storeProductLabelServices->getColumn([['store_id', '=', 0], ['type', '=', 1], ['id', 'IN', $item['store_label_id']]], 'id,label_name');
                    $item['store_label'] = $storeLabelList ? implode(',', array_column($storeLabelList, 'label_name')) : '';
                }
            }
        }
        return $data;
    }

    /**
     * 后台获取商品列表展示
     * @param array $where
     * @param bool $isStock
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductList(array $where, bool $isStock = true, int $limit = 0)
    {
        $field = ['*'];
        if ($isStock) {
            $prefix = Config::get('database.connections.' . Config::get('database.default') . '.prefix');
            $field = [
                '*',
                '(SELECT count(*) FROM `' . $prefix . 'store_product_relation` WHERE `relation_id` = `' . $prefix . 'store_product`.`id` AND `type` = \'collect\') as collect',
                '(SELECT count(*) FROM `' . $prefix . 'store_product_relation` WHERE `relation_id` = `' . $prefix . 'store_product`.`id` AND `type` = \'like\') as likes',
                '(SELECT SUM(stock) FROM `' . $prefix . 'store_product_attr_value` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `type` = 0) as stock',
//                '(SELECT SUM(sales) FROM `' . $prefix . 'store_product_attr_value` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `type` = 0) as sales',
                '(SELECT count(*) FROM `' . $prefix . 'store_visit` WHERE `product_id` = `' . $prefix . 'store_product`.`id` AND `product_type` = \'product\') as visitor',
            ];
        }
        if ($limit) {
            [$page] = $this->getPageValue();
        } else {
            [$page, $limit] = $this->getPageValue();
        }
        $list = $this->dao->getSearchList($where, $page, $limit, $field, '', ['attrValue']);
        $count = $this->dao->getCount($where);
        return compact('count', 'list');
    }

    /**
     * 获取商品规格
     * @param int $id
     * @param int $type
     * @return array
     */
    public function getProductRules(int $id, int $type = 0)
    {
        $productInfo = $this->dao->get($id);
        if (!$productInfo) {
            throw new ValidateException('商品不存在');
        }
        $product_type = $productInfo['product_type'] ?? $data['product_type'] ?? 0; //商品类型
        /** @var StoreProductAttrServices $storeProductAttrService */
        $storeProductAttrService = app()->make(StoreProductAttrServices::class);
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $productAttr = $storeProductAttrService->getProductAttr(['product_id' => $id, 'type' => 0]);
        if (!$productAttr) return [];
        $attr = [];
        foreach ($productAttr as $key => $value) {
            $attr[$key]['value'] = $value['attr_name'];
            $attr[$key]['detailValue'] = '';
            $attr[$key]['attrHidden'] = true;
            $attr[$key]['detail'] = $value['attr_values'];
        }
        $value = attr_format($attr)[1];
        $valueNew = [];
        $count = 0;
//        $sukValue = $storeProductAttrValueServices->getSkuArray(['product_id' => $id, 'type' => $type]);
        $sukValue = $sukDefaultValue = $storeProductAttrValueServices->getSkuArray(['product_id' => $id, 'type' => 0]);
        foreach ($value as $key => $item) {
            $detail = $item['detail'];
//            sort($item['detail'], SORT_STRING);
            $suk = implode(',', $item['detail']);
            if (!isset($sukDefaultValue[$suk])) continue;
//            if (!isset($sukValue[$suk])) {
//                $sukValue[$suk] = $sukDefaultValue[$suk];
//            }
            foreach (array_keys($detail) as $k => $title) {
                $header[$k]['title'] = $title;
                $header[$k]['align'] = 'center';
                $header[$k]['minWidth'] = 80;
            }
            $valueNew[$count]['value'] = '';
            foreach (array_values($detail) as $k => $v) {
                $valueNew[$count]['value' . ($k + 1)] = $v;
                $header[$k]['key'] = 'value' . ($k + 1);
                $valueNew[$count]['value'] .= $valueNew[$count]['value'] == '' ? $v : '，' . $v;
            }
            if ($type == 4) $valueNew[$count]['product_id'] = $sukValue[$suk]['product_id'];
            $valueNew[$count]['detail'] = $detail;
            $valueNew[$count]['pic'] = $sukValue[$suk]['pic'];
            $valueNew[$count]['price'] = floatval($sukValue[$suk]['price']);
            if ($type == 2) $valueNew[$count]['min_price'] = 0;
            if ($type == 3) $valueNew[$count]['r_price'] = floatval($sukValue[$suk]['price']);
            if ($type == 0) $valueNew[$count]['p_price'] = floatval($sukValue[$suk]['price']);
            $valueNew[$count]['cost'] = floatval($sukValue[$suk]['cost']);
            $valueNew[$count]['ot_price'] = floatval($sukValue[$suk]['ot_price']);
            $valueNew[$count]['stock'] = intval($sukValue[$suk]['stock']);
            $valueNew[$count]['quota'] = intval($sukValue[$suk]['quota']);
            $valueNew[$count]['bar_code'] = $sukValue[$suk]['bar_code'] ?? '';
            $valueNew[$count]['code'] = $sukValue[$suk]['code'] ?? '';
            $valueNew[$count]['weight'] = $sukValue[$suk]['weight'] ? floatval($sukValue[$suk]['weight']) : 0;
            $valueNew[$count]['volume'] = $sukValue[$suk]['volume'] ? floatval($sukValue[$suk]['volume']) : 0;
            $valueNew[$count]['brokerage'] = $sukValue[$suk]['brokerage'] ? floatval($sukValue[$suk]['brokerage']) : 0;
            $valueNew[$count]['brokerage_two'] = $sukValue[$suk]['brokerage_two'] ? floatval($sukValue[$suk]['brokerage_two']) : 0;
            $count++;
        }
        $header[] = ['title' => '图片', 'slot' => 'pic', 'align' => 'center', 'minWidth' => 120];
        if ($type == 1) {
            $header[] = ['title' => '秒杀价', 'key' => 'price', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '原价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
        } elseif ($type == 2) {
            $header[] = ['title' => '砍价起始金额', 'slot' => 'price', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '砍价最低价', 'slot' => 'min_price', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '原价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
        } elseif ($type == 3) {
            $header[] = ['title' => '拼团价', 'key' => 'price', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '日常售价', 'key' => 'r_price', 'align' => 'center', 'minWidth' => 80];
        } elseif ($type == 4) {
            $header[] = ['title' => '兑换积分', 'key' => 'price', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        } else {
            $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '原价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
            $header[] = ['title' => '售价', 'key' => 'p_price', 'align' => 'center', 'minWidth' => 80];
        }
        $header[] = ['title' => '库存', 'key' => 'stock', 'align' => 'center', 'minWidth' => 80];
        if ($type == 2) {
            $header[] = ['title' => '限量', 'slot' => 'quota', 'align' => 'center', 'minWidth' => 80];
        } else if ($type == 4) {
            $header[] = ['title' => '兑换次数', 'key' => 'quota', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        } else {
            $header[] = ['title' => '限量', 'key' => 'quota', 'type' => 1, 'align' => 'center', 'minWidth' => 80];
        }
        $header[] = ['title' => '重量(KG)', 'key' => 'weight', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '体积(m³)', 'key' => 'volume', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '商品条形码', 'key' => 'bar_code', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '商品编码', 'key' => 'code', 'align' => 'center', 'minWidth' => 80];
        return ['items' => $attr, 'attrs' => $valueNew, 'header' => $header, 'product_type' => $product_type];
    }

    /**
     * 检查商品是否有活动
     * @param  $id
     * @return bool
     */
    public function checkActivity($id = 0)
    {
        if ($id) {
            /** @var StoreSeckillServices $storeSeckillService */
            $storeSeckillService = app()->make(StoreSeckillServices::class);
            /** @var StoreBargainServices $storeBargainService */
            $storeBargainService = app()->make(StoreBargainServices::class);
            /** @var StoreCombinationServices $storeCombinationService */
            $storeCombinationService = app()->make(StoreCombinationServices::class);
            $res1 = $storeSeckillService->count(['product_id' => $id, 'is_del' => 0, 'status' => 1, 'seckill_time' => 1]);
            $res2 = $storeBargainService->count(['product_id' => $id, 'is_del' => 0, 'status' => 1, 'bargain_time' => 1]);
            $res3 = $storeCombinationService->count(['product_id' => $id, 'is_del' => 0, 'is_show' => 1, 'pinkIngTime' => 1]);
            if ($res1 || $res2 || $res3) throw new AdminException('商品有活动开启，无法进行此操作');
        }
        return true;
    }

    /**
     * 保存
     * @param array $data
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->dao->save($data);
    }

    /**
     * 前台获取商品列表
     * @param array $where
     * @param int $uid
     * @param int $promotions_type
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getGoodsList(array $where, int $uid, int $promotions_type = 0)
    {
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        $where['star'] = 1;
        if (isset($where['store_name']) && $where['store_name']) {
            $keyword = $where['store_name'];
            /** @var UserSearchServices $userSearchServices */
            $userSearchServices = app()->make(UserSearchServices::class);
            $searchIds = $userSearchServices->vicSearch($uid, $keyword, $where);
            if ($searchIds) {//之前查询结果记录
                $where['ids'] = $searchIds;
                unset($where['store_name']);
            } else {//分词查询
            }
        }
        //优惠活动凑单
        if (isset($where['promotions_id']) && $where['promotions_id']) {
            /** @var StorePromotionsServices $storePromotionsServices */
            $storePromotionsServices = app()->make(StorePromotionsServices::class);
            $promotionsWhere = $storePromotionsServices->collectOrderProduct((int)$where['promotions_id']);
            $where = array_merge($where, $promotionsWhere);
        }
        unset($where['promotions_id']);
        if (isset($where['productId']) && $where['productId'] !== '') {
            $where['ids'] = explode(',', $where['productId']);
            $where['ids'] = array_unique(array_map('intval', $where['ids']));
            unset($where['productId']);
        }
        // $where['is_vip_product'] = -1;
        $discount = 100;
        $level_name = '';
        if (!$promotions_type && $uid) {
            /** @var UserServices $user */
            $user = app()->make(UserServices::class);
            $userInfo = $user->getUserCacheInfo($uid);
            $is_vip = $userInfo['is_money_level'] ?? 0;
            $where['is_vip_product'] = $is_vip ? -1 : 0;
            //用户等级是否开启
            /** @var SystemUserLevelServices $systemLevel */
            $systemLevel = app()->make(SystemUserLevelServices::class);
            $levelInfo = $systemLevel->getLevel((int)($userInfo['level'] ?? 0));
            if (sys_config('member_func_status', 1) && $levelInfo) {
                $discount = $levelInfo['discount'] ?? 100;
            }
            $level_name = $levelInfo['name'] ?? '';
        }

        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getSearchList($where, $page, $limit, ['id,product_type,store_name,cate_id,image,IFNULL(sales, 0) + IFNULL(ficti, 0) as sales,price,stock,activity,ot_price,spec_type,recommend_image,unit_name,is_vip,vip_price,is_presale_product,is_vip_product,custom_form']);
        if ($list) {
            /** @var MemberCardServices $memberCardService */
            $memberCardService = app()->make(MemberCardServices::class);
            $vipStatus = $memberCardService->isOpenMemberCardCache('vip_price') && sys_config('svip_price_status', 1);
            foreach ($list as &$item) {
                $minData = $this->getMinPrice($uid, $item, $discount);
                $item['price_type'] = $minData['price_type'] ?? '';
                $item['level_name'] = $level_name;
                $item['vip_price'] = $minData['vip_price'] ?? 0;
                if ($item['price_type'] == 'member' && (!$item['is_vip'] || !$vipStatus)) {
                    $item['vip_price'] = 0;
                }
                $item['custom_form'] = is_string($item['custom_form']) ? json_decode($item['custom_form'], true) : $item['custom_form'];
                $item['cart_button'] = $item['product_type'] > 0 || $item['is_presale_product'] || $item['custom_form'] ? 0 : 1;

                if (count($item['star'])) {
                    $item['star'] = bcdiv((string)array_sum(array_column($item['star'], 'product_score')), (string)count($item['star']), 1);
                } else {
                    $item['star'] = '3.0';
                }
            }
            $list = $this->getActivityList($list);
            $list = $this->getProduceOtherList($list, $uid, isset($where['type']) && !!$where['type']);
            $list = $this->getProductPromotions($list, $promotions_type);
        }
        return $list;
    }

    /**
     * 搜索获取商品品牌列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBrandList(array $where, int $uid = 0)
    {
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        if (isset($where['store_name']) && $where['store_name']) {
            $keyword = $where['store_name'];
            /** @var UserSearchServices $userSearchServices */
            $userSearchServices = app()->make(UserSearchServices::class);
            $searchIds = $userSearchServices->vicSearch($uid, $keyword, $where);
            if ($searchIds) {//之前查询结果记录
                $where['ids'] = $searchIds;
                unset($where['store_name']);
            } else {//分词查询
            }
        }
        if ($where['productId'] !== '') {
            $where['ids'] = explode(',', $where['productId']);
            $where['ids'] = array_unique(array_map('intval', $where['ids']));
            unset($where['productId']);
        }
        $brandIds = $this->dao->getColumnList($where);
        $brandIds = array_unique(array_filter($brandIds));
        /** @var StoreBrandServices $storeBrandServices */
        $storeBrandServices = app()->make(StoreBrandServices::class);
        $brandColumn = $storeBrandServices->dao->getList(['id' => $brandIds, 'is_del' => 0, 'is_show' => 1], [], ['id', 'brand_name']);
        return $brandColumn ?? [];
    }

    /**
     * 获取商品所在优惠活动
     * @param array $list
     * @param int $promotions_type
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductPromotions(array $list, int $promotions_type = 0)
    {
        if (!$list) {
            return $list;
        }
        $productIds = array_column($list, 'id');
        /** @var StorePromotionsServices $storePromotionsServices */
        $storePromotionsServices = app()->make(StorePromotionsServices::class);
        [$promotionsArr, $productDetails, $promotionsDetail] = $storePromotionsServices->getProductsPromotionsDetail($productIds, $promotions_type);
        $promotionsArr = array_combine(array_column($promotionsArr, 'id'), $promotionsArr);
        foreach ($list as &$item) {
            $item['product_id'] = $item['id'];
            $promotionsIds = $productDetails[$item['id']] ?? [];
            $item['promotions'] = [];
            if ($promotionsIds) {
                foreach ($promotionsIds as $id) {
                    $promotions = $promotionsArr[$id] ?? [];
                    if (!$promotions_type) {//无指定优惠类型
                        if ($item['promotions']) {
                            if (($promotions['promotions_type'] ?? 0) <= ($item['promotions']['promotions_type'] ?? 0)) {
                                if (($promotions['promotions_type']['discount'] ?? 0) > ($item['promotions']['discount'] ?? 0)) {
                                    $item['promotions'] = $promotions;
                                }
                            } else {
                                break;
                            }
                        } else {
                            $item['promotions'] = $promotions;
                        }
                    } else {
                        $item['promotions'] = $promotions;
                        break;
                    }
                }
            }
        }
        return $list;
    }

    /**
     * 获取某些模板所需得购物车数量
     * @param array $list
     * @param int $uid
     * @param bool $type
     * @return array
     */
    public function getProduceOtherList(array $list, int $uid, bool $type = true)
    {
        if (!$type || !$list) {
            return $list;
        }
        $productIds = array_column($list, 'id');
        if ($productIds) {
            /** @var StoreProductAttrValueServices $services */
            $services = app()->make(StoreProductAttrValueServices::class);
            $attList = $services->getSkuArray([
                'product_id' => $productIds,
                'type' => 0
            ], 'count(*)', 'product_id');
			$store_id = (int)$this->getItem('store_id', 0);
            $staff_id = (int)$this->getItem('staff_id', 0);
            $tourist_uid = (int)$this->getItem('tourist_uid', 0);
            if ($uid || $tourist_uid) {
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
            foreach ($list as &$item) {
                if (isset($item['spec_type']) && $item['spec_type']) {
                    $item['is_att'] = isset($attList[$item['id']]) && $attList[$item['id']];
                } else {
                    $item['is_att'] = false;
                }
                $item['cart_num'] = $cartNumList[$item['id']] ?? 0;
            }
        }
        return $list;
    }

    /**
     * 获取商品活动标签
     * @param array $list
     * @param array $productIds
     * @return array
     */
    public function getActivityList(array $list, bool $status = true)
    {
        if (!$list) return [];
        if ($status) {
            $productIds = array_column($list, 'id');
        } else {
            $productIds = [$list['id']];
            $list = [$list];
        }
        /** @var StoreSeckillServices $storeSeckillService */
        $storeSeckillService = app()->make(StoreSeckillServices::class);
        /** @var StoreBargainServices $storeBargainServices */
        $storeBargainServices = app()->make(StoreBargainServices::class);
        /** @var StoreCombinationServices $storeCombinationServices */
        $storeCombinationServices = app()->make(StoreCombinationServices::class);
        $seckillIdsList = $storeSeckillService->getSeckillIdsArray($productIds, ['id', 'time_id', 'product_id']);
        $pinkIdsList = $storeCombinationServices->getPinkIdsArray($productIds, ['id']);
        $bargrainIdsList = $storeBargainServices->getBargainIdsArray($productIds, ['id']);
        foreach ($list as &$item) {
            $seckillId = array_filter($seckillIdsList, function ($val) use ($item) {
                if ($val['product_id'] === $item['id']) {
                    return $val;
                }
            });
            $item['activity'] = $this->activity($item['activity'],
                $item['id'],
                $pinkIdsList[$item['id']] ?? 0,
                $seckillId,
                $bargrainIdsList[$item['id']] ?? 0,
                $status);
            if (isset($item['couponId'])) {
                $item['checkCoupon'] = count($item['couponId']) ? true : false;
                unset($item['couponId']);
            } else {
                $item['checkCoupon'] = false;
            }
        }
        if ($status) {
            return $list;
        } else {
            return $list[0]['activity'];
        }
    }

    /**
     * 获取商品在此时段活动优先类型
     * @param string $activity
     * @param int $id
     * @param int $combinationId
     * @param array $seckillId
     * @param int $bargainId
     * @param bool $status
     * @return array
     */
    public function activity(string $activity, int $id, int $combinationId, array $seckillId, int $bargainId, bool $status = true)
    {
        if (!$activity) {
            $activity = '0,1,2,3';//如果老商品没有活动顺序，默认活动顺序，秒杀-砍价-拼团
        }
        $activity = explode(',', $activity);
        if ($activity[0] == 0 && $status) return [];
        $activityId = [];
        $time = 0;
        if ($seckillId) {
            foreach ($seckillId as $v) {
                $timeInfo = GroupDataService::getDataNumber((int)$v['time_id']);
                if ($timeInfo && isset($timeInfo['time']) && isset($timeInfo['continued'])) {
                    if (date('H') >= $timeInfo['time'] && date('H') < ($timeInfo['time'] + $timeInfo['continued'])) {
                        $activityId[1] = $v['id'];
                        $time = strtotime(date("Y-m-d"), time()) + 3600 * ($timeInfo['time'] + $timeInfo['continued']);
                    }
                }
            }
        }
        if ($bargainId) $activityId[2] = $bargainId;
        if ($combinationId) $activityId[3] = $combinationId;
        $data = [];
        foreach ($activity as $k => $v) {
            if (array_key_exists($v, $activityId)) {
                if ($status) {
                    $data['type'] = $v;
                    $data['id'] = $activityId[$v];
                    if ($v == 1) $data['time'] = $time;
                    break;
                } else {
                    if ($v != 0) {
                        $arr['type'] = $v;
                        $arr['id'] = $activityId[$v];
                        if ($v == 1) $arr['time'] = $time;
                        $data[] = $arr;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 获取热门商品
     * @param array $where
     * @param string $order
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProducts(array $where, string $order = '', int $num = 0)
    {
        [$page, $limit] = $this->getPageValue();
        if ($num) {
            $page = 1;
            $limit = $num;
        }
        $list = $this->dao->getSearchList($where, $page, $limit, ['id,store_name,cate_id,image,IFNULL(sales, 0) + IFNULL(ficti, 0) as sales,price,stock,activity,unit_name'], $order);
        $list = $this->getActivityList($list);
        return $list;
    }

    /**
     * 获取商品详情
     * @param Request $request
     * @param int $id
     * @param int $type
     * @param int $promotions_type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function productDetail(Request $request, int $id, int $type, int $promotions_type = 0)
    {
        $uid = (int)$request->uid();
        $data['uid'] = $uid;
        $storeInfo = $this->dao->getOne(['id' => $id, 'is_show' => 1, 'is_del' => 0], '*', ['description', 'brand']);
        if (!$storeInfo) {
            throw new ValidateException('商品不存在');
        } else {
            $storeInfo = $storeInfo->toArray();
        }
        $storeInfo['brand_name'] = $storeInfo['brand_name'] ?? '';
        $storeInfo['store_label'] = $storeInfo['ensure'] = [];
        if ($storeInfo['store_label_id']) {
            /** @var StoreProductLabelServices $storeProductLabelServices */
            $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
            $storeInfo['store_label'] = $storeProductLabelServices->getColumn([['id', 'in', $storeInfo['store_label_id']]], 'id,label_name');
        }
        if ($storeInfo['ensure_id']) {
            /** @var StoreProductEnsureServices $storeProductEnsureServices */
            $storeProductEnsureServices = app()->make(StoreProductEnsureServices::class);
            $storeInfo['ensure'] = $storeProductEnsureServices->getColumn([['id', 'in', $storeInfo['ensure_id']], ['status', '=', 1]], 'id,name,image,desc');
        }
        /** @var StorePromotionsServices $storePromotionsServices */
        $storePromotionsServices = app()->make(StorePromotionsServices::class);
        $storeInfo['promotions'] = $storePromotionsServices->getProductsPromotions([$id], $promotions_type, '*', [], 'promotions_type');
        $discount = isset($storeInfo['promotions'][0]['promotions_type']) && $storeInfo['promotions'][0]['promotions_type'] == 1 ? $storeInfo['promotions'][0]['discount'] : -1;

        $configData = SystemConfigService::more(['site_url', 'tengxun_map_key', 'store_self_mention', 'routine_contact_type', 'site_name', 'share_qrcode']);
        $siteUrl = $configData['site_url'] ?? '';
        $storeInfo['image'] = set_file_url($storeInfo['image'], $siteUrl);
        $storeInfo['image_base'] = set_file_url($storeInfo['image'], $siteUrl);
        $storeInfo['fsales'] = $storeInfo['ficti'] + $storeInfo['sales'];
        /** @var QrcodeServices $qrcodeService */
        $qrcodeService = app()->make(QrcodeServices::class);
        $storeInfo['code_base'] = $qrcodeService->getWechatQrcodePath($id . '_product_detail_wap.jpg', '/pages/goods_details/index?id=' . $id);
        /** @var StoreProductRelationServices $storeProductRelationServices */
        $storeProductRelationServices = app()->make(StoreProductRelationServices::class);
        $storeInfo['userCollect'] = $storeProductRelationServices->isProductRelation(['uid' => $uid, 'relation_id' => $id, 'type' => 'collect', 'category' => StoreProductRelationServices::CATEGORY_PRODUCT]);
        $storeInfo['userLike'] = 0;

        //预售相关
        if ($storeInfo['is_presale_product']) {
            if ($storeInfo['presale_start_time'] > time()) {
                $storeInfo['presale_pay_status'] = 1;
            } elseif ($storeInfo['presale_start_time'] <= time() && $storeInfo['presale_end_time'] > time()) {
                $storeInfo['presale_pay_status'] = 2;
            } elseif ($storeInfo['presale_end_time'] < time()) {
                $storeInfo['presale_pay_status'] = 3;
            } else {
                $storeInfo['presale_pay_status'] = 0;
            }
        } else {
            $storeInfo['presale_pay_status'] = 0;
        }
        $storeInfo['presale_start_time'] = $storeInfo['presale_start_time'] ? date('Y-m-d H:i', $storeInfo['presale_start_time']) : '';
        $storeInfo['presale_end_time'] = $storeInfo['presale_end_time'] ? date('Y-m-d H:i', $storeInfo['presale_end_time']) : '';

        $storeInfo['custom_form'] = is_string($storeInfo['custom_form']) ? json_decode($storeInfo['custom_form'], true) : $storeInfo['custom_form'];
        //有自定义表单或预售或虚拟不展示加入购物车按钮
        $storeInfo['cart_button'] = $storeInfo['custom_form'] || $storeInfo['is_presale_product'] || $storeInfo['product_type'] > 0 ? 0 : 1;


        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        [$productAttr, $productValue] = $storeProductAttrServices->getProductAttrDetail($id, $uid, $type, 0, 0, $storeInfo, $discount);
        //无属性添加默认属性
        if (empty($productValue)) {
            $attr = [
                [
                    'value' => '规格',
                    'detailValue' => '',
                    'attrHidden' => '',
                    'detail' => ['默认']
                ]
            ];
            $detail[0] = [
                'value1' => '默认',
                'detail' => ['规格' => '默认'],
                'pic' => $storeInfo['image'],
                'price' => $storeInfo['price'],
                'cost' => $storeInfo['cost'],
                'ot_price' => $storeInfo['ot_price'],
                'stock' => $storeInfo['stock'],
                'bar_code' => '',
                'code' => '',
                'weight' => 0,
                'volume' => 0,
                'brokerage' => 0,
                'brokerage_two' => 0,
            ];
            $skuList = $storeProductAttrServices->validateProductAttr($attr, $detail, $id);
            $storeProductAttrServices->saveProductAttr($skuList, $id, 0);
        }
        $attrValue = $productValue;
        if (!$storeInfo['spec_type']) {
            $productAttr = [];
            $productValue = [];
        }
        $data['productAttr'] = $productAttr;
        $data['productValue'] = $productValue;
        $data['storeInfo'] = get_thumb_water($storeInfo, 'big', ['image', 'slider_image']);
        $storeInfoNew = get_thumb_water($storeInfo, 'small', ['image']);
        $data['storeInfo']['small_image'] = $storeInfoNew['image'];
        $data['storeInfo'] = array_merge($data['storeInfo'], $this->getMinPrice($uid, $data['storeInfo']));
        if (!$this->vipIsOpen(!!$storeInfo['is_vip'])) {
            $data['storeInfo']['vip_price'] = 0;
        }
        $data['priceName'] = 0;
        if ($uid) {
            $data['priceName'] = $this->getPacketPrice($storeInfo, $attrValue, $uid);
        }
        /** @var StoreProductReplyServices $storeProductReplyService */
        $storeProductReplyService = app()->make(StoreProductReplyServices::class);
        $reply = $storeProductReplyService->getRecProductReply($id);
        $data['reply'] = $reply ? get_thumb_water($reply, 'small', ['pics']) : [];
        [$replyCount, $goodReply, $replyChance] = $storeProductReplyService->getProductReplyData($id);
        $data['replyChance'] = $replyChance;
        $data['replyCount'] = $replyCount;
        $data['mer_id'] = 0;
        $data['activity'] = $this->getActivityList($data['storeInfo'], false);
        /** @var StoreCouponIssueServices $couponService */
        $couponService = app()->make(StoreCouponIssueServices::class);
        $data['coupons'] = $couponService->getIssueCouponList($uid, ['product_id' => $id, 'type' => -1])['list'];
        /** @var StoreDiscountsServices $discountsServices */
        $discountsServices = app()->make(StoreDiscountsServices::class);
        $data['discounts_products'] = $discountsServices->getDiscounts($id, $uid);
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        $is_vip = $userServices->value(['uid' => $uid], 'is_money_level');
        $where['is_vip_product'] = $is_vip ? -1 : 0;
        //推荐
        if ($storeInfo['recommend_list'] != '') {
            $recommend_list = explode(',', $storeInfo['recommend_list']);
            $data['good_list'] = get_thumb_water($this->getProducts(['ids' => $recommend_list, 'is_del' => 0, 'is_show' => 1] + $where, '', 12));
        } else {
            $data['good_list'] = get_thumb_water($this->getProducts(['is_good' => 1, 'is_del' => 0, 'is_show' => 1] + $where, 'rand()', 12));
        }
        $data['mapKey'] = $configData['tengxun_map_key'] ?? '';
        $data['store_self_mention'] = (int)($configData['store_self_mention'] ?? 0);//门店自提是否开启
        $data['routine_contact_type'] = $configData['routine_contact_type'] ?? 0;
        $data['site_name'] = $configData['site_name'] ?? '';
        $data['share_qrcode'] = $configData['share_qrcode'] ?? 0;
        //浏览记录
        ProductLogJob::dispatch(['visit', ['uid' => $uid, 'id' => $id, 'product_id' => $id], 'product']);
        return $data;
    }

    /**
     * 是否开启vip
     * @param bool $vip
     * @return bool
     */
    public function vipIsOpen(bool $vip = false, $vipStatus = -1)
    {
        if (!$vip) {
            return false;
        }
        $member_status = sys_config('member_card_status', 1);
        if (!$member_status) {
            return false;
        }
        if ($vipStatus == -1) {
            /** @var MemberCardServices $memberCardService */
            $memberCardService = app()->make(MemberCardServices::class);
            $vipStatus = $memberCardService->isOpenMemberCardCache('vip_price', false, $member_status);
        }
        return $vipStatus && $member_status && $vip && sys_config('svip_price_status', 1);
    }

    /**
     * 获取商品分销佣金最低和最高
     * @param $storeInfo
     * @param $productValue
     * @param int $uid
     * @return int|string
     */
    public function getPacketPrice($storeInfo, $productValue, int $uid)
    {
        if (!count($productValue)) {
            return 0;
        }
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        if (!$userServices->checkUserPromoter($uid)) {
            return 0;
        }
        if (isset($storeInfo['is_sub']) && $storeInfo['is_sub'] == 1) {
            $maxPrice = (float)max(array_column($productValue, 'brokerage'));
            $minPrice = (float)min(array_column($productValue, 'brokerage'));
        } else {
            $maxPrice = max(array_column($productValue, 'price'));
            $minPrice = min(array_column($productValue, 'price'));
            $store_brokerage_ratio = sys_config('store_brokerage_ratio');
            $store_brokerage_ratio = (string)bcdiv((string)$store_brokerage_ratio, '100', 2);
            $maxPrice = (float)bcmul($store_brokerage_ratio, (string)$maxPrice, 0);
            $minPrice = (float)bcmul($store_brokerage_ratio, (string)$minPrice, 0);
        }
        if ($minPrice == 0 && $maxPrice == 0) {
            $priceName = 0;
        } else if ($minPrice == 0 && $maxPrice)
            $priceName = $maxPrice;
        else if ($maxPrice == 0 && $minPrice)
            $priceName = $minPrice;
        else if ($maxPrice == $minPrice && $minPrice)
            $priceName = $maxPrice;
        else
            $priceName = $minPrice . '~' . $maxPrice;
        return strlen(trim($priceName)) ? $priceName : 0;
    }

    /**
     * 获取商品用户等级、svip最低价格，优惠类型
     * @param int $uid
     * @param $productInfo
     * @param $discount
     * @param $level_name
     * @return array
     */
    public function getMinPrice(int $uid, $productInfo, $discount = null, $level_name = null)
    {
        $level_name = '';
        $vip_price = 0;
        $price_type = '';
        if ($productInfo) {
            if (is_null($discount)) {
                $discount = 100;
                if ($uid) {
                    /** @var UserServices $user */
                    $user = app()->make(UserServices::class);
                    $userInfo = $user->getUserCacheInfo($uid);
                    //用户等级是否开启
                    /** @var SystemUserLevelServices $systemLevel */
                    $systemLevel = app()->make(SystemUserLevelServices::class);
                    $levelInfo = $systemLevel->getLevel((int)($userInfo['level'] ?? 0));
                    if (sys_config('member_func_status', 1) && $levelInfo) {
                        $discount = $levelInfo['discount'] ?? 100;
                    }
                    $level_name = $levelInfo['name'] ?? '';
                }
            }
            if ($discount != 100 || $productInfo['is_vip']) {
                $level_price = (float)bcmul((string)bcdiv((string)$discount, '100', 2), (string)$productInfo['price'], 2);
                if ($discount != 100 && $productInfo['is_vip']) {
                    if ($level_price < $productInfo['vip_price']) {
                        $price_type = 'level';
                        $vip_price = $level_price;
                    } else {
                        $price_type = 'member';
                        $vip_price = $productInfo['vip_price'];
                    }
                } else if ($discount != 100 && !$productInfo['is_vip']) {
                    $price_type = 'level';
                    $vip_price = $level_price;
                } else if ($discount == 100 && $productInfo['is_vip']) {
                    $price_type = 'member';
                    $vip_price = $productInfo['vip_price'];
                }
            }

        }
        return compact('level_name', 'vip_price', 'price_type');
    }

    /**
     * 计算商品优惠后金额、优惠价格
     * @param $price
     * @param int $uid
     * @param $userInfo
     * @param $vipStatus
     * @param int $discount
     * @param float $vipPrice
     * @param int $is_vip
     * @param false $is_show
     * @return array  [优惠后的总金额,优惠金额]
     */
    public function setLevelPrice($price, int $uid, $userInfo, $vipStatus, $discount = 0, $vipPrice = 0.00, $is_vip = 0, $is_show = false)
    {
        if (!(float)$price) return [(float)$price, (float)$price, ''];
        if (!$vipStatus) $is_vip = 0;
        //已登录
        if ($uid) {
            if (!$userInfo) {
                /** @var UserServices $user */
                $user = app()->make(UserServices::class);
                $userInfo = $user->getUserCacheInfo($uid);
            }
            if ($discount === 0) {
                /** @var SystemUserLevelServices $systemLevel */
                $systemLevel = app()->make(SystemUserLevelServices::class);
                $discount = $systemLevel->getDiscount($uid, (int)$userInfo['level']);
            }
        } else {
            //没登录
            $discount = 100;
        }
        $discount = bcdiv((string)$discount, '100', 2);
        //执行减去会员优惠金额
        [$truePrice, $vip_truePrice, $type] = $this->isPayLevelPrice($uid, $userInfo, $vipStatus, $price, $discount, $vipPrice, $is_vip, $is_show);
        //返回优惠后的总金额
        $truePrice = $truePrice < 0.01 ? 0.01 : $truePrice;
        //优惠的金额
        $vip_truePrice = $vip_truePrice == $price ? bcsub((string)$vip_truePrice, '0.01', 2) : $vip_truePrice;
        return [(float)$truePrice, (float)$vip_truePrice, $type];
    }

    /**
     * 获取会员价格（付费会员价格和购买商品会员价格）
     * @param int $uid
     * @param $userInfo
     * @param $vipStatus
     * @param $price
     * @param string $discount
     * @param float $payVipPrice
     * @param int $is_vip
     * @param false $is_show
     * @return array
     */
    public function isPayLevelPrice(int $uid, $userInfo, $vipStatus, $price, string $discount, $payVipPrice = 0.00, $is_vip = 0, $is_show = false)
    {
        //is_vip == 0表示会员价格不启用，展示为零
        if ($is_vip == 0) $payVipPrice = 0;
        if (!$userInfo && $uid) {
            //检测用户是否是付费会员
            /** @var  UserServices $userService */
            $userService = app()->make(UserServices::class);
            $userInfo = $userService->getUserCacheInfo($uid);
        }
        $noPayVipPrice = ($discount && $discount != 0.00) ? bcmul((string)$discount, (string)$price, 2) : $price;
        if ($payVipPrice < $noPayVipPrice && $payVipPrice > 0) {
            $vipPrice = $payVipPrice;
            $type = 'member';
        } else {
            $vipPrice = $noPayVipPrice;
            $type = 'level';
        }
        //如果$isSingle==true 返回优惠后的总金额，否则返回优惠的金额
        if ($vipStatus && $is_vip == 1) {
            //$is_show == false 是计算支付价格，true是展示
            if (!$is_show) {
                return [$vipPrice, bcsub((string)$price, (string)$vipPrice, 2), $type];
            } else {
                if ($userInfo && isset($userInfo['is_money_level']) && $userInfo['is_money_level'] > 0) {
                    return [$vipPrice, bcsub((string)$price, (string)$vipPrice, 2), $type];
                } else {
                    $type = 'level';
                    return [$noPayVipPrice, bcsub((string)$price, (string)$noPayVipPrice, 2), $type];
                }
            }
        } else {
            $type = 'level';
            return [(float)$noPayVipPrice, (float)bcsub((string)$price, (string)$noPayVipPrice, 2), $type];
        }
    }

    /**
     * 商品列表
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
        return $this->dao->getProductLimit($where, $limit, $field);
    }

    /**
     * 通过条件获取商品列表
     * @param $where
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductListByWhere($where, $field)
    {
        return $this->dao->getProductListByWhere($where, $field);
    }

    /**
     * 根据指定id获取商品列表
     * @param array $ids
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductColumn(array $ids, string $field = '')
    {
        $productData = [];
        $productInfoField = 'id,image,price,ot_price,vip_price,postage,give_integral,sales,stock,store_name,unit_name,is_show,is_del,is_postage,cost,is_sub,temp_id';
        if (!empty($ids)) {
            $productAll = $this->dao->idByProductList($ids, $field ?: $productInfoField);
            if (!empty($productAll))
                $productData = array_combine(array_column($productAll, 'id'), $productAll);
        }
        return $productData;
    }

    /**
     * 商品是否存在
     * @param $productId
     * @return bool
     */
    public function isValidProduct(int $productId)
    {
        return $this->dao->getOne(['id' => $productId, 'is_del' => 0, 'is_show' => 1]);
    }

    /**
     * 获取商品库存
     * @param int $productId
     * @param string $uniqueId
     * @return int|mixed
     */
    public function getProductStock(int $productId, string $uniqueId = '')
    {
        /** @var  StoreProductAttrValueServices $StoreProductAttrValue */
        $StoreProductAttrValue = app()->make(StoreProductAttrValueServices::class);
        return $uniqueId == '' ?
            $this->dao->value(['id' => $productId], 'stock') ?: 0
            : $StoreProductAttrValue->uniqueByStock($uniqueId);
    }

    /**
     * cardInfo减销量,加库存
     * @param $num
     * @param $productId
     * @param string $unique
     * @return bool
     */
    public function incProductStockByCart(array $cartInfo)
    {
        $res = true;
        foreach ($cartInfo as $cart) {
            $unique = isset($cart['productInfo']['attrInfo']) ? $cart['productInfo']['attrInfo']['unique'] : '';
            $res = $res && $this->incProductStock((int)$cart['cart_num'], (int)$cart['productInfo']['id'], $unique);
        }
        return $res;
    }

    /**
     * 减库存,加销量
     * @param $num
     * @param $productId
     * @param string $unique
     * @return bool
     */
    public function decProductStock(int $num, int $productId, string $unique = '')
    {
        $res = true;
        if ($unique) {
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            $res = $res && $skuValueServices->decProductAttrStock($productId, $unique, $num);
        }
        $res = $res && $this->dao->decStockIncSales(['id' => $productId], $num);
        if ($res) {
            $this->workSendStock($productId);
        }
        return $res;
    }

    /**
     * 减销量，加库存
     * @param int $num
     * @param int $productId
     * @param string $unique
     * @return bool
     */
    public function incProductStock(int $num, int $productId, string $unique = '')
    {
        $res = true;
        if ($unique) {
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            $res = $res && $skuValueServices->incProductAttrStock($productId, $unique, $num);
        }
        $res = $res && $this->dao->incStockDecSales(['id' => $productId], $num);
        return $res;
    }

    /**
     * 库存预警发送消息
     * @param int $productId
     */
    public function workSendStock(int $productId)
    {
        ProductStockTips::dispatch([$productId]);
    }

    /**
     * 获取推荐商品
     * @param int $uid
     * @param $field
     * @param int $num
     * @param string $type
     * @return array|array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \throwable
     */
    public function getRecommendProduct(int $uid, $field, int $num = 0, string $type = 'mid')
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_vip_product'] = -1;
        if ($uid) {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $is_vip = $userServices->value(['uid' => $uid], 'is_money_level');
            $where['is_vip_product'] = $is_vip ? -1 : 0;
        }
        $list = $this->dao->getRecommendProduct($where, $field, $num, $page, $limit);
        if ($list) {
            $list = get_thumb_water($list, $type);
            $list = $this->getActivityList($list);
            /** @var MemberCardServices $memberCardService */
            $memberCardService = app()->make(MemberCardServices::class);
            $vipStatus = $memberCardService->isOpenMemberCardCache('vip_price', false) && sys_config('svip_price_status', 1);;
            foreach ($list as &$item) {
                if (!($vipStatus && $item['is_vip'])) {
                    $item['vip_price'] = 0;
                }
            }
        }
        return $list;
    }

    /**
     * 商品名称 图片
     * @param array $productIds
     * @return array
     */
    public function getProductArray(array $where, string $field, string $key)
    {
        return $this->dao->getColumn($where, $field, $key);
    }

    /**
     * 获取商品详情
     * @param int $productId
     * @param string $field
     * @param array $with
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductInfo(int $productId, string $field = '*', array $with = [])
    {
        return $this->dao->getOne(['is_del' => 0, 'is_show' => 1, 'id' => $productId], $field, $with);
    }

    /** 生成商品复制口令关键字
     * @param int $productId
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductWords(int $productId)
    {
        $productInfo = $this->dao->getOne(['is_del' => 0, 'is_show' => 1, 'id' => $productId]);
        $keyWords = "";
        if ($productInfo) {
            $oneKey = "crmeb-fu致文本 Http:/ZБ";
            $twoKey = "Б轉移至☞" . sys_config('site_name') . "☜";
            $threeKey = "【" . $productInfo['store_name'] . "】";
            $mainKey = base64_encode($productId);
            $keyWords = $oneKey . $mainKey . $twoKey . $threeKey;
        }
        return $keyWords;
    }

    /**
     * 通过商品id获取商品分类
     * @param array $productId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function productIdByProductCateName(array $productId)
    {
        $data = $this->dao->productIdByCateId($productId);
        $cateData = [];
        foreach ($data as $item) {
            $cateData[$item['id']] = implode(',', array_map(function ($i) {
                return $i['cate_name'];
            }, $item['cateName']));
        }
        return $cateData;
    }

    /**
     * 根据商品id获取品牌名称
     * @param $prductId
     * @return mixed
     */
    public function productIdByBrandName($prductId)
    {
        /** @var StoreBrandServices $storeBrandServices */
        $storeBrandServices = app()->make(StoreBrandServices::class);
        $brand_id = $this->dao->value(['id' => $prductId], 'brand_id');
        $name = $storeBrandServices->value(['id' => $brand_id, 'is_show' => 1, 'is_del' => 0], 'brand_name');
        return $name ?? '';
    }

    /**
     * 自动上下架
     * @return bool
     */
    public function autoUpperShelves()
    {
        $this->dao->overUpperShelves(1);
        $this->dao->overUpperShelves(0);
        return true;
    }

    /**
     * 获取预售列表
     * @param int $uid
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getPresaleList(int $uid, array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $data = $this->dao->getPresaleList($where, $page, $limit);
        if ($data['list']) {
            /** @var StoreCategoryServices $storeCategoryService */
            $storeCategoryService = app()->make(StoreCategoryServices::class);
            /** @var StoreCouponIssueServices $couponServices */
            $couponServices = app()->make(StoreCouponIssueServices::class);
            foreach ($data['list'] as &$item) {
                $item['custom_form'] = is_string($item['custom_form']) ? json_decode($item['custom_form'], true) : $item['custom_form'];

                $cateId = $item['cate_id'];
                $cateId = explode(',', $cateId);
                $cateId = array_merge($cateId, $storeCategoryService->cateIdByPid($cateId));
                $cateId = array_diff($cateId, [0]);
                $counpons = $couponServices->getPcIssueCouponList($uid, $cateId, $item['id'], 'id,coupon_title,coupon_price,use_min_price', 0, 1, 'coupon_price desc,sort desc,id desc');
                $item['coupon'] = $counpons[0] ?? [];
            }
        }
        return $data;
    }
}
