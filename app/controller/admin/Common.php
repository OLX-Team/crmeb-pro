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
namespace app\controller\admin;

use app\services\other\CityAreaServices;
use app\services\system\SystemAuthServices;
use app\services\order\StoreOrderServices;
use app\services\product\product\StoreProductServices;
use app\services\product\product\StoreProductReplyServices;
use app\services\user\UserExtractServices;
use app\services\system\SystemMenusServices;
use app\services\user\UserServices;
use crmeb\services\CacheService;
use crmeb\services\SystemConfigService;

/**
 * 公共接口基类 主要存放公共接口
 * Class Common
 * @package app\controller\admin
 */
class Common extends AuthController
{
    /**
     * 获取logo
     * @param SystemConfigService $services
     * @return mixed
     */
    public function getLogo(SystemConfigService $services)
    {
        $data = $services->more(['site_logo', 'site_logo_square', 'site_name']);
        return $this->success($data);
    }

    /**
     * @return mixed
     */
    public function check_auth()
    {
        return app('json')->success(['status'=>1,'day'=>0,'authCode' => 'AA20220530']);
    }

    /**
     * @return mixed
     */
    public function auth()
    {
        return app('json')->success('授权通过!', ['auth' => false]);
    }

    /**
     * @return mixed
     */
    public function crmeb_verify()
    {
        $phone = $this->request->get('phone');
        return $this->__6u44FLR2RPvjtC3t7fV8mADyfrn37RZj2($phone);
    }

    /**
     * @return mixed
     */
    public function crmeb_login()
    {
        $phone = $this->request->post('phone');
        $code = $this->request->post('code');

        $res = $this->__k0dUcnKjRUs9lfEllqO9J($phone, (int)$code, true);
        if ($res['status'] !== 200) {
            return $this->fail($res['msg'] ?? '登录失败');
        }
        return $this->success('登录成功');
    }

    /**
     * @return mixed
     */
    public function crmeb_product()
    {
        $type = $this->request->get('type', '');
        $data = $this->__lskbEPbOLZ0cTaZ0XIGUusdFJsVI4yW($type);
        return app('json')->success($data['data'] ?? []);
    }

    /**
     * @return mixed
     */
    public function crmeb_order()
    {
        $phone = $this->request->post('phone', '');
        $product_type = $this->request->post('product_type', 'copyright');
        $company_name = $this->request->post('company_name', '');
        $domain_name = $this->request->post('domain_name', '');
        return $this->__nWr5aRIbtPUWlNWFfD1cctaF9G3aiC($phone, $product_type, $company_name, $domain_name);
    }

    /**
     * 支付
     * @return mixed
     */
    public function crmeb_pay()
    {
        $orderId = $this->request->post('order_id', '');
        $phone = $this->request->post('phone', '');
        if (!$phone) {
            return $this->fail('缺少参数');
        }
        return $this->__MrT6wwwNHL0HNORLW4F0qvnKAgqxa($phone, $orderId);
    }

    /**
     * 查询购买版权
     * @return mixed
     */
    public function crmeb_copyright()
    {
        $orderId = $this->request->post('order_id', '');
        $phone = $this->request->post('phone', '');
        $this->__6j3nfcwmWqrsDx8F0MjZGeQyWvLsqeFXww($phone, $orderId);
        return $this->success('查询成功');
    }

    /**
     * 获取订单
     * @param $orderId
     * @return mixed
     */
    public function crmeb_order_info($orderId)
    {
        if (!$orderId) {
            return $this->fail('缺少订单id');
        }
        $phone = $this->request->get('phone', '');
        if (!$phone) {
            return $this->fail('缺少参数');
        }
        $orderInfo = $this->__xz8MHDCmMcIbe1HkWr0RM0FGz3gnYIX($phone, $orderId);
        return $this->success($orderInfo['data'] ?? []);
    }

    /**
     * 保存版权
     * @return mixed
     */
    public function saveCopyright()
    {
        $copyright = $this->request->post('copyright');

        $this->__qsG71NREI01vix2OkjH($copyright);

        return $this->success('保存成功');
    }

    /**
     * 获取版权
     * @return mixed
     */
    public function getCopyright()
    {
        try {
            $copyright = $this->__z6uxyJQ4xYa5ee1mx5();
        } catch (\Throwable $e) {
            $copyright = false;
        }
        return $this->success(['copyright' => $copyright]);
    }

    /**
     * 申请授权
     * @return mixed
     */
    public function auth_apply(SystemAuthServices $services)
    {
        $version = get_crmeb_version();
        $data = $this->request->postMore([
            ['company_name', ''],
            ['domain_name', ''],
            ['order_id', ''],
            ['phone', ''],
            ['label', strripos($version, 'min') === false ? 3 : 2],
            ['captcha', ''],
        ]);
        if (!$data['company_name']) {
            return $this->fail('请填写公司名称');
        }
        if (!$data['domain_name']) {
            return $this->fail('请填写授权域名');
        }

        if (!$data['phone']) {
            return $this->fail('请填写手机号码');
        }
        if (!$data['order_id']) {
            return $this->fail('请填写订单id');
        }
        $datas = explode('.', $data['domain_name']);
        $n = count($datas);
        $preg = '/[\w].+\.(com|net|org|gov|edu)\.cn$/';
        if (($n > 2) && preg_match($preg, $data['domain_name'])) {
            //双后缀取后3位
            $domain_name = $datas[$n - 3] . '.' . $datas[$n - 2] . '.' . $datas[$n - 1];
        } else {
            //非双后缀取后两位
            $domain_name = $datas[$n - 2] . '.' . $datas[$n - 1];
        }
        $sec = trim(str_replace($domain_name, '', $data['domain_name']), '.');
        if ($sec) {
            if ($sec == 'www') {
                $data['domain_name'] = $domain_name;
            }
        }
        $header = $this->__k0dUcnKjRUs9lfEllqO9J($data['phone']);
        if ($header) {
            $headerData = ['Authori-zation:Bearer ' . $this->__k0dUcnKjRUs9lfEllqO9J($data['phone'])];
        } else {
            $headerData = false;
        }
        $services->authApply($data, $headerData);
        return $this->success("申请授权成功!");

    }

    /**
     * 首页头部统计数据
     * @return mixed
     */
    public function homeStatics()
    {
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $info = $orderServices->homeStatics();
        return $this->success(compact('info'));
    }

    /**
     * 订单图表
     */
    public function orderChart()
    {
        $cycle = $this->request->param('cycle') ?: 'thirtyday';//默认30天
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $chartdata = $orderServices->orderCharts($cycle);
        return $this->success($chartdata);
    }

    /**
     * 用户图表
     */
    public function userChart()
    {
        /** @var UserServices $uServices */
        $uServices = app()->make(UserServices::class);
        $chartdata = $uServices->userChart();
        return $this->success($chartdata);
    }

    /**
     * 交易额排行
     * @return mixed
     */
    public function purchaseRanking()
    {
//        /** @var StoreProductAttrValueServices $valueServices */
//        $valueServices = app()->make(StoreProductAttrValueServices::class);
//        $list = $valueServices->purchaseRanking();
        $list = [];
        return $this->success(compact('list'));
    }

    /**
     * 待办事统计
     * @return mixed
     */
    public function jnotice()
    {
        /** @var StoreOrderServices $orderServices */
        $orderServices = app()->make(StoreOrderServices::class);
        $data['ordernum'] = $orderServices->storeOrderCount();
        $store_stock = sys_config('store_stock');
        if ($store_stock < 0) $store_stock = 2;
        /** @var StoreProductServices $storeServices */
        $storeServices = app()->make(StoreProductServices::class);
        $data['inventory'] = $storeServices->count(['type' => 5, 'store_stock' => $store_stock]);//警戒库存
        /** @var StoreProductReplyServices $replyServices */
        $replyServices = app()->make(StoreProductReplyServices::class);
        $data['commentnum'] = $replyServices->replyCount();
        /** @var UserExtractServices $extractServices */
        $extractServices = app()->make(UserExtractServices::class);
        $data['reflectnum'] = $extractServices->userExtractCount();//提现
        $data['newOrderId'] = $orderServices->newOrderId(1);
        if (count($data['newOrderId'])) $orderServices->update([['order_id', 'IN', $data['newOrderId']]], ['is_remind' => 1]);
        $value = [];
        if ($data['ordernum'] != 0) {
            $value[] = [
                'title' => '您有' . $data['ordernum'] . '个待发货的订单',
                'type' => 'bulb',
                'url' => '/admin/order/list?status=1'
            ];
        }
        if ($data['inventory'] != 0) {
            $value[] = [
                'title' => '您有' . $data['inventory'] . '个商品库存预警',
                'type' => 'information',
                'url' => '/admin/product/product_list?type=5',
            ];
        }
        if ($data['commentnum'] != 0) {
            $value[] = [
                'title' => '您有' . $data['commentnum'] . '条评论待回复',
                'type' => 'bulb',
                'url' => '/admin/product/product_reply?is_reply=0'
            ];
        }
        if ($data['reflectnum'] != 0) {
            $value[] = [
                'title' => '您有' . $data['reflectnum'] . '个提现申请待审核',
                'type' => 'bulb',
                'url' => '/admin/finance/user_extract/index?status=0',
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
     * 格式化菜单
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function menusList()
    {
        $cahcheKey = md5('admin_common_menu_list');
        $list = CacheService::redisHandler()->get($cahcheKey);
        if (!$list) {
            /** @var SystemMenusServices $menusServices */
            $menusServices = app()->make(SystemMenusServices::class);
            $menus = $menusServices->getSearchList();
            $counts = $menusServices->getColumn([
                ['is_show', '=', 1],
                ['auth_type', '=', 1],
                ['is_del', '=', 0],
                ['is_show_path', '=', 0],
            ], 'pid');
            $data = [];
            foreach ($menus as $key => $item) {
                $pid = $item->getData('pid');
                $data[$key] = json_decode($item, true);
                $data[$key]['pid'] = $pid;
                if (in_array($item->id, $counts)) {
                    $data[$key]['type'] = 1;
                } else {
                    $data[$key]['type'] = 0;
                }
            }
            $list = sort_list_tier($data);
            CacheService::redisHandler()->set($cahcheKey, $list, 86400);
        }
        return app('json')->success($list);
    }

    /**
     * @param CityAreaServices $services
     * @return mixed
     */
    public function city(CityAreaServices $services)
    {
        $pid = $this->request->get('pid', 0);
        return $this->success($services->getCityTreeList((int)$pid));
    }
}
