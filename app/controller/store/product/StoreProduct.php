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
namespace app\controller\store\product;


use app\jobs\store\SynchStocksJob;
use app\services\product\branch\StoreBranchProductAttrValueServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\category\StoreCategoryServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\user\label\UserLabelCateServices;
use app\services\user\label\UserLabelServices;
use think\facade\App;
use app\controller\store\AuthController;

/**
 * Class StoreProduct
 * @package app\controller\admin\v1\product
 */
class StoreProduct extends AuthController
{
    protected $services;

    public function __construct(App $app, StoreProductServices $service)
    {
        parent::__construct($app);
        $this->services = $service;
    }

    /**
     * 显示资源列表头部
     * @return mixed
     */
    public function type_header()
    {
        $list = $this->services->getHeader((int)$this->storeId);
        return app('json')->success(compact('list'));
    }

    /**
     * 显示资源列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['store_name', ''],
            ['cate_id', ''],
            ['type', 1],
            ['sales', 'normal']
        ]);
        $where['store_id'] = $this->request->storeId();
        $data = $this->services->getList($where);
        return app('json')->success($data);
    }

    /**
     * 获取分类cascader格式数据
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cascader_list(StoreCategoryServices $services)
    {
        return app('json')->success($services->cascaderList(1, 1));
    }

    /**
     * 获取商品详细信息
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function read($id = 0)
    {
        return app('json')->success($this->services->getInfo((int)$id));
    }

    /**
     * 获取编辑详情
     * @param int $id
     * @return mixed
     */
    public function edit($id = 0)
    {
        [$id] = $this->request->getMore([
            [['id', 'd'], 0],
        ], true);
        $store_id = $this->request->storeId();
        return app('json')->success($this->services->getStoreBranchInfo((int)$id, (int)$store_id));
    }

    /**
     * 保存编辑
     * @param int $id
     * @param StoreBranchProductServices $services
     * @return mixed
     */
    public function update($id = 0, StoreBranchProductAttrValueServices $services)
    {
        $data = $this->request->postMore([
            ['attrs', []],
            ['label_id', []],
            ['is_show', 1]
        ]);
        $storeId = $this->request->storeId();
        $services->updataAll((int)$id, (array)$data, (int)$storeId);
        return app('json')->success('保存商品信息成功');
    }

    /**
     * 门店同步库存
     * @return mixed
     */
    public function synchStocks()
    {
        [$ids] = $this->request->postMore([
            ['ids', []]
        ], true);
        if (!count($ids)) return $this->fail('请选择商品');
        $storeId = $this->request->storeId();
        //拆分大数组
        $idsArr = array_chunk($ids, 5);
        foreach ($idsArr as $syncIds) {
            //加入同步
            SynchStocksJob::dispatch([$syncIds, $storeId]);
        }
        return app('json')->success('库存同步已加入队列执行，请稍后查看');
    }

    /**
     * 获取关联用户标签列表
     * @param UserLabelServices $service
     * @return mixed
     */
    public function getUserLabel(UserLabelCateServices $userLabelCateServices, UserLabelServices $service)
    {
        $cate = $userLabelCateServices->getLabelCateAll((int)$this->type, (int)$this->storeId);
        $data = [];
        $label = [];
        if ($cate) {
            foreach ($cate as $value) {
                $data[] = [
                    'id' => $value['id'] ?? 0,
                    'value' => $value['id'] ?? 0,
                    'label_cate' => 0,
                    'label_name' => $value['name'] ?? '',
                    'label' => $value['name'] ?? '',
                    'store_id' => $value['store_id'] ?? 0,
                    'type' => $value['type'] ?? 1,
                ];
            }
            $label = $service->getColumn(['type' => $this->type, 'store_id' => $this->storeId], '*');
            if ($label) {
                foreach ($label as &$item) {
                    $item['label'] = $item['label_name'];
                    $item['value'] = $item['id'];
                }
            }
        }
        return app('json')->success($service->get_tree_children($data, $label));
    }

    /**
     * 修改状态
     * @param string $is_show
     * @param string $id
     * @return mixed
     */
    public function set_show($is_show = '', $id = '', StoreBranchProductServices $services)
    {
        if (!$id) return $this->fail('缺少商品ID');
        $services->setShow($this->request->storeId(), $id, $is_show);
        return $this->success($is_show == 1 ? '上架成功' : '下架成功');
    }

    /**
     * 获取商品所有规格数据
     * @param StoreBranchProductAttrValueServices $services
     * @param $id
     * @return mixed
     */
    public function getAttrs(StoreBranchProductAttrValueServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少商品ID');
        }
        return $this->success($services->getStoreProductAttr((int)$id, (int)$this->request->storeId()));
    }


    /**
     * 快速修改商品规格库存
     * @param StoreBranchProductAttrValueServices $services
     * @param $id
     * @return mixed
     */
    public function saveProductAttrsStock(StoreBranchProductAttrValueServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少商品ID');
        }
        [$attrs] = $this->request->getMore([
            ['attrs', []]
        ], true);
        if (!$attrs) {
            return $this->fail('请重新修改规格库存');
        }
        foreach ($attrs as $attr) {
            if (!isset($attr['unique']) || !isset($attr['pm']) || !isset($attr['stock'])) {
                return $this->fail('请重新修改规格库存');
            }
        }
        return $this->success(['stock' => $services->saveProductAttrsStock((int)$id, (int)$this->request->storeId(), $attrs)]);
    }
}
