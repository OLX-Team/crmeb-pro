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
namespace app\controller\api\v1\store;

use app\Request;
use app\services\order\StoreCartServices;
use app\services\activity\discounts\StoreDiscountsServices;
use crmeb\services\CacheService;

/**
 * 购物车类
 * Class StoreCartController
 * @package app\api\controller\store
 */
class StoreCartController
{
    protected $services;

    public function __construct(StoreCartServices $services)
    {
        $this->services = $services;
    }

    /**
     * 购物车 列表
     * @param Request $request
     * @return mixed
     */
    public function lst(Request $request)
    {
        [$status] = $request->postMore([
            ['status', 1],//购物车商品状态
        ], true);
        $result = $this->services->getUserCartList($request->uid(), $status);
        $result['valid'] = $this->services->getReturnCartList($result['valid'], $result['promotions']);
        unset($result['promotions']);
        return app('json')->successful($result);
    }

    /**
     * 购物车 添加
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add(Request $request)
    {
        $where = $request->postMore([
            ['productId', 0],//普通商品编号
            [['cartNum', 'd'], 1], //购物车数量
            ['uniqueId', ''],//属性唯一值
            [['new', 'd'], 0],// 1 加入购物车直接购买  0 加入购物车
            [['is_new', 'd'], 0],// 1 加入购物车直接购买  0 加入购物车
            [['combinationId', 'd'], 0],//拼团商品编号
            [['secKillId', 'd'], 0],//秒杀商品编号
            [['bargainId', 'd'], 0],//砍价商品编号
            [['discountId', 'd'], 0],//优惠套餐编号
            ['discountInfos', []],//优惠套餐商品信息
        ]);
        if ($where['is_new'] || $where['new']) $new = true;
        else $new = false;
        if (!$where['productId'] && !$where['discountId']) {
            return app('json')->fail('参数错误');
        }
        $type = 0;
        $uid = (int)$request->uid();
        $activityId = 0;
        if ($where['discountId']) {
            /** @var StoreDiscountsServices $discountService */
            $discountService = app()->make(StoreDiscountsServices::class);
            $discounts = $discountService->get((int)$where['discountId']);
            if (!$discounts) {
                return app('json')->fail('套餐商品未找到！');
            }
            //套餐限量
            if ($discounts['is_limit']) {
                if ($discounts['limit_num'] <= 0) {
                    return app('json')->fail('套餐限量不足');
                }
                if (!CacheService::checkStock(md5($discounts['id']), 1, 5)) {
                    return app('json')->fail('套餐限量不足');
                }
            }
            $res = [];
            $activityId = (int)$where['discountId'];
            foreach ($where['discountInfos'] as $info) {
                $res[] = $this->services->setCart($uid, (int)$info['product_id'], 1, $info['unique'], 5, $new, $activityId, (int)$info['id']);
            }
        } else {
            if ($where['secKillId']) {
                $type = 1;
                $activityId = $where['secKillId'];
            } elseif ($where['bargainId']) {
                $type = 2;
                $activityId = $where['bargainId'];
            } elseif ($where['combinationId']) {
                $type = 3;
                $activityId = $where['combinationId'];
            }
            $res = $this->services->setCart($uid, (int)$where['productId'], (int)$where['cartNum'], $where['uniqueId'], $type, $new, (int)$activityId);
        }
        if (!$res) return app('json')->fail('添加失败');
        else  return app('json')->successful('ok', ['cartId' => $res]);
    }

    /**
     * 购物车 删除商品
     * @param Request $request
     * @return mixed
     */
    public function del(Request $request)
    {
        $where = $request->postMore([
            ['ids', ''],//购物车编号
        ]);
        $where['ids'] = is_array($where['ids']) ? $where['ids'] : explode(',', $where['ids']);
        if (!count($where['ids']))
            return app('json')->fail('参数错误!');
        if ($this->services->removeUserCart((int)$request->uid(), $where['ids']))
            return app('json')->successful();
        return app('json')->fail('清除失败！');
    }

    /**
     * 购物车 修改商品数量
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function num(Request $request)
    {
        $where = $request->postMore([
            ['id', 0],//购物车编号
            ['number', 0],//购物车编号
        ]);
        if (!$where['id'] || !$where['number'] || !is_numeric($where['id']) || !is_numeric($where['number'])) return app('json')->fail('参数错误!');
        $res = $this->services->changeUserCartNum($where['id'], $where['number'], $request->uid());
        if ($res) return app('json')->successful();
        else return app('json')->fail('修改失败');
    }

    /**
     * 购物车 统计 数量 价格
     * @param Request $request
     * @return mixed
     */
    public function count(Request $request)
    {
        [$numType] = $request->postMore([
            ['numType', true],//购物车编号
        ], true);
        $uid = (int)$request->uid();
        return app('json')->success('ok', $this->services->getUserCartCount($uid, $numType));
    }

    /**
     * 购物车重选
     * @param Request $request
     * @return mixed
     */
    public function reChange(Request $request)
    {
        [$cart_id, $product_id, $unique] = $request->postMore([
            ['cart_id', 0],
            ['product_id', 0],
            ['unique', '']
        ], true);
        $this->services->modifyCart($cart_id, $product_id, $unique);
        return app('json')->success('重选成功');
    }
}
