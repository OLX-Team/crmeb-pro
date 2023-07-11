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
namespace app\controller\store\finance;


use app\services\store\finance\StoreFinanceFlowServices;
use think\exception\ValidateException;
use think\facade\App;
use think\facade\Config;
use app\controller\store\AuthController;
use app\services\store\finance\StoreExtractServices;


/**
 * 门店提现
 * Class StoreExtract
 * @package app\controller\store\finance
 */
class StoreExtract extends AuthController
{
    /**
     * StoreExtract constructor.
     * @param App $app
     * @param StoreExtractServices $services
     */
    public function __construct(App $app, StoreExtractServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }


    /**
     * 显示资源列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['status', ''],
            ['pay_status', ''],
            ['extract_type', ''],
            ['nireid', '', '', 'like'],
            ['data', '', '', 'time'],
        ]);
        if (isset($where['extract_type']) && $where['extract_type'] == 'wx') {
            $where['extract_type'] = 'weixin';
        }
        $where['store_id'] = $this->storeId;
        $whereData = [
            'store_id' => $this->storeId,
            'is_del' => 0,
            'trade_type'=>1,
            'no_type'=>1
        ];
        return app('json')->success($this->services->index($where,$whereData));
    }

    /**
     * 增加备注
     * @param $id
     * @return mixed
     */
    public function mark($id)
    {
        [$mark] = $this->request->getMore([
            ['mark', '']
        ], true);
        if (!$id || !$mark) {
            return app('json')->fail('缺少参数');
        }
        $storeExtract = $this->services->get((int)$id);
        if (!$storeExtract) {
            return app('json')->fail('转账记录不存在');
        }
        if (!$this->services->update($id, ['store_mark' => $mark])) {
            return app('json')->fail('备注失败');
        }
        return app('json')->success('备注成功');
    }

    /**
     * 提现申请
     * @param Request $request
     * @return mixed
     */
    public function cash()
    {
        $extractInfo = $this->request->postMore([
            ['extract_type', ''],
            ['money', 0],
            ['mark', '']
        ]);
        $extractType = Config::get('pay.extractType', []);
        //最低提现
        $store_extract_min_price = sys_config('store_extract_min_price') ?? 0;
        //最高提现
        $store_extract_max_price = sys_config('store_extract_max_price') ?? 0;
        if ($extractInfo['money'] < $store_extract_min_price)
            return app('json')->fail('最低提现' . $store_extract_min_price . '元');
        if ($extractInfo['money'] > $store_extract_max_price)
            return app('json')->fail('最高提现' . $store_extract_max_price . '元');
        //可提现金额
        /** @var StoreFinanceFlowServices $storeFinanceFlowServices */
        $storeFinanceFlowServices = app()->make(StoreFinanceFlowServices::class);
        $whereData = [
            'store_id' => $this->storeId,
            'is_del' => 0,
            'trade_type' => 1,
            'no_type' => 1
        ];
        $price_not = $storeFinanceFlowServices->getSumFinance(['store_id' => $this->storeId], $whereData);
        if ($extractInfo['money'] > $price_not) {
            throw new ValidateException($price_not > 0 ? '可提现金额为' . $price_not . '元': '暂无可提现金额');
        }
        if (!in_array($extractInfo['extract_type'], $extractType))
            return app('json')->fail('转账方式不存在');
        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', (float)$extractInfo['money'])) return app('json')->fail('转账金额输入有误');
        if ($this->services->cash((int)$this->storeId, $this->storeStaffId, $extractInfo))
            return app('json')->successful('申请转账成功!');
        else
            return app('json')->fail('转账失败');
    }
}
