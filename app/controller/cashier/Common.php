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
namespace app\controller\cashier;

use app\Request;
use app\services\order\OtherOrderServices;
use app\services\order\store\BranchOrderServices;
use app\services\order\StoreOrderServices;
use app\services\other\CityAreaServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\product\StoreProductReplyServices;
use app\services\store\SystemStoreServices;
use app\services\store\SystemStoreStaffServices;
use app\services\system\SystemMenusServices;
use app\services\user\UserRechargeServices;
use app\services\system\attachment\SystemAttachmentServices;
use crmeb\services\CacheService;
use crmeb\services\UploadService;


/**
 * 公共接口基类 主要存放公共接口
 * Class Common
 * @package app\controller\admin
 */
class Common extends AuthController
{
    /**
     * 获取logo
     * @param SystemStoreServices $storeServices
     * @return mixed
     */
    public function getLogo(SystemStoreServices $storeServices)
    {
        $store = $storeServices->get((int)$this->storeId, ['id', 'image', 'name']);
        return $this->success([
            'logo' => $store && isset($store['image']) && $store['image'] ? $store['image'] : sys_config('site_logo'),
            'logo_square' => $store && isset($store['image']) && $store['image'] ? $store['image'] : sys_config('site_logo_square'),
            'site_name' => $store && isset($store['name']) && $store['name'] ? $store['name'] : sys_config('site_name')
        ]);
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->success([
            'tengxun_map_key' => sys_config('tengxun_map_key'),
            'open_erp' => !!sys_config('erp_open')
        ]);
    }

    /**
     * @param CityAreaServices $services
     * @return mixed
     */
    public function city(CityAreaServices $services, $pid = 0)
    {
        return $this->success($services->getCityTreeList((int)$pid));
    }

    /**
     * 格式化菜单
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function menusList()
    {
        /** @var SystemMenusServices $menusServices */
        $menusServices = app()->make(SystemMenusServices::class);
        $list = $menusServices->getSearchList(3);
        $counts = $menusServices->getColumn([
            ['type', 3],
            ['is_show', '=', 1],
            ['auth_type', '=', 1],
            ['is_del', '=', 0],
            ['is_show_path', '=', 0],
        ], 'pid');
        $data = [];
        foreach ($list as $key => $item) {
            $pid = $item->getData('pid');
            $data[$key] = json_decode($item, true);
            $data[$key]['pid'] = $pid;
            if (in_array($item->id, $counts)) {
                $data[$key]['type'] = 1;
            } else {
                $data[$key]['type'] = 0;
            }
        }
        return app('json')->success(sort_list_tier($data));
    }

    /**
     * 首页运营头部统计
     * @param Request $request
     * @param BranchOrderServices $orderServices
     * @return mixed
     */
    public function homeStatics(Request $request, BranchOrderServices $orderServices)
    {
        [$time] = $request->getMore([
            ['data', '', '', 'time']
        ], true);
        $time = $orderServices->timeHandle($time);
        return app('json')->success($orderServices->homeStatics((int)$this->storeId, $time));
    }

    /**
     * 首页营业趋势图表
     * @param Request $request
     * @param BranchOrderServices $orderServices
     * @return mixed
     */
    public function operateChart(Request $request, BranchOrderServices $orderServices)
    {
        [$time] = $request->getMore([
            ['data', '', '', 'time']
        ], true);
        $time = $orderServices->timeHandle($time, true);
        return app('json')->success($orderServices->operateChart((int)$this->storeId, $time));
    }

    /**
     * 首页交易统计
     * @param Request $request
     * @param BranchOrderServices $orderServices
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderChart(Request $request, BranchOrderServices $orderServices)
    {
        [$time] = $request->getMore([
            ['data', '', '', 'time']
        ], true);
        $time = $orderServices->timeHandle($time);
        return $this->success($orderServices->orderChart((int)$this->storeId, $time));
    }

    /**
     * 首页店员统计
     * @param Request $request
     * @param SystemStoreStaffServices $staffServices
     * @return mixed
     */
    public function staffChart(Request $request, SystemStoreStaffServices $staffServices)
    {
        [$time] = $request->getMore([
            ['data', '', '', 'time']
        ], true);
        $time = $staffServices->timeHandle($time);
        return $this->success($staffServices->staffChart((int)$this->storeId, $time));
    }


    /**
     * 待办事统计
     * @return mixed
     */
    public function jnotice()
    {
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $data['ordernum'] = $orderServices->storeOrderCount((int)$this->storeId, 7);
        $store_stock = sys_config('store_stock');
        if ($store_stock < 0) $store_stock = 2;
        /** @var StoreBranchProductServices $storeServices */
        $storeServices = app()->make(StoreBranchProductServices::class);
        $data['inventory'] = $storeServices->count(['store_id' => $this->storeId, 'type' => 5, 'store_stock' => $store_stock]);//警戒库存
        /** @var StoreProductReplyServices $replyServices */
        $replyServices = app()->make(StoreProductReplyServices::class);
        $data['commentnum'] = $replyServices->replyCount((int)$this->storeId);
        $value = [];
        if ($data['ordernum'] != 0) {
            $value[] = [
                'title' => '您有' . $data['ordernum'] . '个待发货的订单',
                'type' => 'bulb',
                'url' => '/store/order/index?type=7&status=1'
            ];
        }
        if ($data['inventory'] != 0) {
            $value[] = [
                'title' => '您有' . $data['inventory'] . '个商品库存预警',
                'type' => 'information',
                'url' => '/store/product/index?type=5',
            ];
        }
        if ($data['commentnum'] != 0) {
            $value[] = [
                'title' => '您有' . $data['commentnum'] . '条评论待回复',
                'type' => 'bulb',
                'url' => '/store/product/product_reply?is_reply=0'
            ];
        }
        return $this->success($this->noticeData($value));
    }

    /**
     * 消息返回格式
     * @param array $data
     * @return array
     */
    public function noticeData(array $data): array
    {
        // 消息图标
        $iconColor = [
            // 邮件 消息
            'mail' => [
                'icon' => 'md-mail',
                'color' => '#3391e5'
            ],
            // 普通 消息
            'bulb' => [
                'icon' => 'md-bulb',
                'color' => '#87d068'
            ],
            // 警告 消息
            'information' => [
                'icon' => 'md-information',
                'color' => '#fe5c57'
            ],
            // 关注 消息
            'star' => [
                'icon' => 'md-star',
                'color' => '#ff9900'
            ],
            // 申请 消息
            'people' => [
                'icon' => 'md-people',
                'color' => '#f06292'
            ],
        ];
        // 消息类型
        $type = array_keys($iconColor);
        // 默认数据格式
        $default = [
            'icon' => 'md-bulb',
            'iconColor' => '#87d068',
            'title' => '',
            'url' => '',
            'type' => 'bulb',
            'read' => 0,
            'time' => 0
        ];
        $value = [];
        foreach ($data as $item) {
            $val = array_merge($default, $item);
            if (isset($item['type']) && in_array($item['type'], $type)) {
                $val['type'] = $item['type'];
                $val['iconColor'] = $iconColor[$item['type']]['color'] ?? '';
                $val['icon'] = $iconColor[$item['type']]['icon'] ?? '';
            }
            $value[] = $val;
        }
        return $value;
    }

    /**
     * 轮询后台扫码订单状态
     * @param Request $request
     * @param $type
     * @return mixed
     */
    public function checkOrderStatus(Request $request, $type)
    {
        [$order_id, $end_time] = $request->getMore([
            ['order_id', ''],
            ['end_time', 0],
        ], true);
        switch ($type) {
            case 1://recharge
                /** @var UserRechargeServices $userRecharge */
                $userRecharge = app()->make(UserRechargeServices::class);
                $data['status'] = $userRecharge->count(['order_id' => $order_id, 'paid' => 1]) ? true : false;
                break;
            case 2://svip
                /** @var OtherOrderServices $otherOrderServices */
                $otherOrderServices = app()->make(OtherOrderServices::class);
                $data['status'] = $otherOrderServices->count(['order_id' => $order_id, 'paid' => 1]) ? true : false;
                break;
            case 3://订单
                $storeOrderServices = app()->make(StoreOrderServices::class);
                $data['status'] = $storeOrderServices->count(['order_id' => $order_id, 'paid' => 1]) ? true : false;
                break;
            default:
                return app('json')->fail('暂不支持该类型订单查询');
        }
        $time = $end_time - time();
        $data['time'] = $time > 0 ? $time : 0;
        return app('json')->successful($data);
    }

    /**
     * 图片上传
     * @param Request $request
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function upload_image(Request $request, SystemAttachmentServices $services)
    {
        $data = $request->postMore([
            ['filename', 'file'],
        ]);
        if (!$data['filename']) return app('json')->fail('参数有误');
        if (CacheService::has('cashier_uploads_' . $request->cashierId()) && CacheService::get('cashier_uploads_' . $request->cashierId()) >= 100) return app('json')->fail('非法操作');
        $upload = UploadService::init();
        $info = $upload->to('cashier')->validate()->move($data['filename']);
        if ($info === false) {
            return app('json')->fail($upload->getError());
        }
        $res = $upload->getUploadInfo();
        $services->attachmentAdd($res['name'], $res['size'], $res['type'], $res['dir'], $res['thumb_path'], 1, (int)sys_config('upload_type', 1), $res['time'], 3);
        if (CacheService::has('cashier_uploads_' . $request->cashierId()))
            $start_uploads = (int)CacheService::get('cashier_uploads_' . $request->cashierId());
        else
            $start_uploads = 0;
        $start_uploads++;
        CacheService::set('cashier_uploads_' . $request->cashierId(), $start_uploads, 86400);
        $res['dir'] = path_to_url($res['dir']);
        if (strpos($res['dir'], 'http') === false) $res['dir'] = $request->domain() . $res['dir'];
        return app('json')->successful('图片上传成功!', ['name' => $res['name'], 'url' => $res['dir']]);
    }
}
