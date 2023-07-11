<?php


namespace app\jobs\order;


use app\jobs\store\StoreFinanceJob;
use app\services\message\NoticeService;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderServices;
use app\services\product\branch\StoreBranchProductAttrValueServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\store\StoreUserServices;
use app\services\store\SystemStoreServices;
use app\webscoket\SocketPush;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 门店分配订单
 * Class SpliteStoreOrderJob
 * @package app\jobs
 */
class SpliteStoreOrderJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 门店分配订单
     * @param $orderInfo
     * @return bool
     */
    public function doJob($orderInfo)
    {
        if (!$orderInfo) {
            return true;
        }
        //整单分给门店
        /** @var StoreOrderServices $storeOrderServices */
        $storeOrderServices = app()->make(StoreOrderServices::class);
        $order = $storeOrderServices->get($orderInfo['id']);
        if (!$order) {
            return true;
        }
        $orderInfo = $order->toArray();
        //已经分配或者门店自提
        if (!isset($orderInfo['shipping_type']) || $orderInfo['shipping_type'] != 1 || $orderInfo['store_id'] > 0) {
            $this->splitAfter($orderInfo);
            return true;
        }
        //卡密商品
        if (in_array($orderInfo['product_type'], [1, 2, 3])) {
            $this->splitAfter($orderInfo);
            return true;
        }

        try {
            [$longitude, $latitude] = explode(' ', $orderInfo['user_location']);
            /** @var SystemStoreServices $storeServices */
            $storeServices = app()->make(SystemStoreServices::class);
            //没有经纬度按地址信息搜索门店
            if (!$longitude || !$latitude) {
                $storeList = [];
                if (isset($orderInfo['user_address']) && $orderInfo['user_address']) {
                    $addressInfo = explode(' ', $orderInfo['user_address']);
                    $street = $addressInfo[3] ?? '';//街道
                    $district = $addressInfo[2] ?? '';//区
                    if ($street && $district) {
                        $storeList = $storeServices->getStoreByAddressInfo($district . '/' . $street);
                    } elseif ($street) {
                        $storeList = $storeServices->getStoreByAddressInfo($street);
                    } elseif ($district) {
                        $storeList = $storeServices->getStoreByAddressInfo($district);
                    }
                }
            } else {
                //距离排序门店列表
                $storeList = $storeServices->getDistanceShortStoreList($latitude, $longitude, 'id,valid_range,longitude,latitude');
            }
            //满足情况门店
            $storeId = 0;
            if ($storeList) {
                $oid = (int)$orderInfo['id'];
                /** @var StoreOrderCartInfoServices $storeOrderCartInfoServices */
                $storeOrderCartInfoServices = app()->make(StoreOrderCartInfoServices::class);
                $cart_info = $storeOrderCartInfoServices->getSplitCartList($oid, 'cart_info');
                if (!$cart_info) {
                    $this->splitAfter($orderInfo);
                    return true;
                }

                $attrUniques = [];
                $attrUniquesArr = [];
                /** @var StoreProductAttrValueServices $skuValueServices */
                $skuValueServices = app()->make(StoreProductAttrValueServices::class);
                foreach ($cart_info as $cart) {
                    if (isset($cart['productInfo']['store_delivery']) && !$cart['productInfo']['store_delivery']) {//有商品不支持门店配送
                        $this->splitAfter($orderInfo);
                        return true;
                    }
                    $type = $cart['type'];
                    switch ($type) {
                        case 0:
                        case 6:
                            $attrUniques[] = $cart['product_attr_unique'];
                            $attrUniquesArr[$cart['product_attr_unique']] = $cart['product_attr_unique'];
                            break;
                        case 1:
                        case 2:
                        case 3:
                        case 5:
                            $suk = $skuValueServices->value(['unique' => $cart['product_attr_unique'], 'product_id' => $cart['activity_id'], 'type' => $type], 'suk');
                            $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $cart['product_id'], 'type' => 0], 'unique');
                            $attrUniquesArr[$cart['product_attr_unique']] = $productUnique;
                            $attrUniques[] = $productUnique;
                            break;
                    }
                }
                $productIds = array_unique(array_column($cart_info, 'product_id'));
                /** @var StoreBranchProductServices $branchProductServics */
                $branchProductServics = app()->make(StoreBranchProductServices::class);
                $productCount = count($productIds);
                /** @var StoreBranchProductAttrValueServices $storeValueService */
                $storeValueService = app()->make(StoreBranchProductAttrValueServices::class);
                foreach ($storeList as $store) {
                    $is_show = $productCount == $branchProductServics->getCount([['product_id', 'IN', $productIds], ['is_show', '=', 1], ['is_del', '=', 0], ['store_id', '=', $store['id']]]);//商品没下架 && 库存足够
                    if (!$is_show) {
                        continue;
                    }
                    $allStock = $storeValueService->getColumn([['unique', 'in', $attrUniques], ['store_id', '=', $store['id']]], 'stock', 'unique');
                    if (!$allStock) {
                        continue;
                    }
                    $stock = true;
                    foreach ($cart_info as $item) {
                        if ($item['cart_num'] > ($allStock[$attrUniquesArr[$item['product_attr_unique']]] ?? 0)) {
                            $stock = false;
                            break;
                        }
                    }
                    if ($is_show && $stock) {
                        $storeId = $store['id'];
                        break;
                    }
                }
                if ($storeId) {

                    $storeOrderServices->transaction(function () use ($oid, $storeId, $cart_info, $orderInfo, $storeOrderServices, $branchProductServics) {
                        //修改订单信息
                        $storeOrderServices->update($oid, ['shipping_type' => 3, 'store_id' => $storeId]);
                        //扣门店库存
                        //返还平台库存
                        $branchProductServics->regressionBranchProductStock($orderInfo, $cart_info, 0, 1, $storeId);
                    });
                    //向门店后台发送新订单消息
                    try {
                        SocketPush::store()->to($orderInfo['store_id'])->type('NEW_ORDER')->data(['order_id' => $orderInfo['order_id']])->push();
                    } catch (\Throwable $e) {
                        Log::error('向后台发送新订单消息失败,失败原因:' . $e->getMessage());
                    }
                }
            }
            $orderInfo['store_id'] = $storeId;
            $this->splitAfter($orderInfo);
        } catch (\Throwable $e) {
            Log::error('自动拆分门店订单失败，原因：' . $e->getMessage() . $e->getFile() . $e->getLine());
        }

        return true;
    }

    /**
     * 订单分配完成后置方法
     * @param $orderInfo
     */
    public function splitAfter($orderInfo, bool $only_print = false)
    {
        //分配好向用户设置标签
        OrderJob::dispatchDo('setUserLabel', [$orderInfo]);
        //分配门店账单流水
        StoreFinanceJob::dispatch([$orderInfo, 1]);
        if ($only_print) {
            /** @var NoticeService $NoticeService */
            $NoticeService = app()->make(NoticeService::class);
            $NoticeService->orderPrint($orderInfo);
        } else {
            //分配完成用户推送消息事件（门店小票打印）
            event('notice.notice', [$orderInfo, 'order_pay_success']);
        }
        if (isset($orderInfo['store_id']) && $orderInfo['store_id']) {
            //记录门店用户
            /** @var StoreUserServices $storeUserServices */
            $storeUserServices = app()->make(StoreUserServices::class);
            $storeUserServices->setStoreUser((int)$orderInfo['uid'], (int)$orderInfo['store_id']);
            event('notice.notice', [$orderInfo, 'admin_pay_success_code']);
        }
        return true;
    }

}
