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
namespace app\controller\admin\v1\product;

use app\controller\admin\AuthController;
use app\jobs\BatchHandleJob;
use app\jobs\product\ProductSyncErp;
use app\services\other\CacheServices;
use app\services\other\queue\QueueServices;
use app\services\product\category\StoreCategoryServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrValueServices;
use crmeb\services\FileService;
use crmeb\services\SpreadsheetExcelService;
use crmeb\services\UploadService;
use think\facade\App;

/**
 * Class StoreProduct
 * @package app\controller\admin\v1\product
 */
class StoreProduct extends AuthController
{
    protected $service;

    public function __construct(App $app, StoreProductServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 显示资源列表头部
     * @return mixed
     */
    public function type_header()
    {
		$where = $this->request->getMore([
            ['store_name', ''],
            ['cate_id', ''],
            ['type', 1]
        ]);
        $list = $this->service->getHeader(0, $where);
        return $this->success(compact('list'));
    }

    /**
     * 获取退出未保存的数据
     * @param CacheServices $services
     * @return mixed
     */
    public function getCacheData(CacheServices $services)
    {
        return $this->success(['info' => $services->getDbCache($this->adminId . '_product_data', [])]);
    }

    /**
     * 1分钟保存一次产品数据
     * @param CacheServices $services
     * @return mixed
     */
    public function saveCacheData(CacheServices $services)
    {
        $data = $this->request->postMore([
            ['product_type', 0],//商品类型
            ['cate_id', []],
            ['store_name', ''],
            ['store_info', ''],
            ['keyword', ''],
            ['unit_name', '件'],
            ['recommend_image', ''],
            ['slider_image', []],
            ['is_sub', []],//佣金是单独还是默认
            ['sort', 0],
            ['sales', 0],
            ['ficti', 100],
            ['give_integral', 0],
            ['is_show', 0],
            ['is_hot', 0],
            ['is_benefit', 0],
            ['is_best', 0],
            ['is_new', 0],
            ['mer_use', 0],
            ['is_postage', 0],
            ['is_good', 0],
            ['description', ''],
            ['spec_type', 0],
            ['video_open', 0],//是否开启视频
            ['video_link', ''],//视频链接
            ['items', []],
            ['attrs', []],
            ['activity', []],
            ['coupon_ids', []],
            ['label_id', []],
            ['command_word', ''],
            ['tao_words', ''],
            ['type', 0],
            ['delivery_type', []],//物流设置
            ['freight', 1],//运费设置
            ['postage', 0],//邮费
            ['temp_id', 0],//运费模版
            ['recommend_list', []],
            ['brand_id', []],
            ['soure_link', ''],
            ['bar_code', ''],
            ['code', ''],
            ['is_support_refund', 1],//是否支持退款
            ['is_presale_product', 0],//预售商品开关
            ['presale_time', []],//预售时间
            ['presale_day', 0],//预售发货日
            ['is_vip_product', 0],//是否付费会员商品
            ['auto_on_time', 0],//自动上架时间
            ['auto_off_time', 0],//自动下架时间
            ['custom_form', []],//自定义表单
            ['store_label_id', []],//商品标签
            ['ensure_id', []],//商品保障服务区
            ['specs', []],//商品参数
            ['specs_id', 0],//商品参数ID
        ]);
        $services->setDbCache($this->adminId . '_product_data', $data, 68400);
        return $this->success();
    }

    /**
     * 删除数据缓存
     * @param CacheServices $services
     * @return mixed
     */
    public function deleteCacheData(CacheServices $services)
    {
        $services->delectDbCache($this->adminId . '_product_data');
        return $this->success();
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
        $data = $this->service->getList($where);
        return $this->success($data);
    }

    /**
     * 修改状态
     * @param string $is_show
     * @param string $id
     * @return mixed
     */
    public function set_show($is_show = '', $id = '')
    {
        $this->service->setShow([$id], $is_show);
        return $this->success($is_show == 1 ? '上架成功' : '下架成功');
    }

    /**
     * 设置批量商品上架
     * @return mixed
     */
    public function product_show()
    {
        [$ids, $all, $where] = $this->request->postMore([
            ['ids', []],
            ['all', 0],
            ['where', []],
        ], true);
        if ($all == 0) {//单页不走队列
            if (empty($ids)) return $this->fail('请选择需要上架的商品');
            $this->service->setShow($ids, 1);
            return $this->success('上架成功');
        }
        if ($all == 1) $ids = [];
        $type = 4;//商品上架
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $queueService->setQueueData($where, 'id', $ids, $type);
        //加入队列
        BatchHandleJob::dispatch(['up', $type]);
        return $this->success('后台程序已执商品上架任务!');
    }

    /**
     * 设置批量商品下架
     * @return mixed
     */
    public function product_unshow()
    {
        [$ids, $all, $where] = $this->request->postMore([
            ['ids', []],
            ['all', 0],
            ['where', []],
        ], true);
        if ($all == 0) {//单页不走队列
            if (empty($ids)) return $this->fail('请选择需要下架的商品');
            $this->service->setShow($ids, 0);
            return $this->success('下架成功');
        }
        if ($all == 1) {
            $all_ids = $this->service->getColumn(['is_show' => 1, 'is_del' => 0], 'id');
            $this->service->checkActivity($all_ids);
            $ids = [];
        }
        $type = 4;//商品下架
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $queueService->setQueueData($where, 'id', $ids, $type);
        //加入队列
        BatchHandleJob::dispatch(['down', $type]);
        return $this->success('后台程序已执商品下架任务!');
    }

    /**
     * 批量设置商品配送方式
     * @return mixed
     */
    public function setProductDeliveryType()
    {
        [$ids, $all, $deliveryType] = $this->request->postMore([
            ['ids', []],
            ['all', 0],
            ['delivery_type', []],
        ], true);
        if (!$deliveryType) {
            return $this->fail('请选择配送方式');
        }
        if ($all == 0 && empty($ids)) {
            return $this->fail('请选择需要设置的商品');
        }
        if (count($ids) == 1) {
            $product = $this->service->get($ids[0]);
            if ($product && in_array($product['product_type'], [1, 2, 3])) {
                return $this->fail('虚拟、卡密商品不需要设置配送方式');
            }
        }
        if ($all == 1) {
            $ids = $this->service->getColumn(['is_show' => 1, 'is_del' => 0], 'id');
        }
        if ($ids && !$this->service->update([['id', 'in', $ids]], ['delivery_type' => $deliveryType])) {
            return $this->fail('设置失败');
        }
        return $this->success('设置成功');
    }

    /**
     * 批量设置商品标签
     * @return mixed
     */
    public function setProductlabel()
    {
        [$ids, $all, $store_label_id] = $this->request->postMore([
            ['ids', []],
            ['all', 0],
            ['store_label_id', []],
        ], true);
        if (!$store_label_id) {
            return $this->fail('请选择商品标签');
        }
        if ($all == 0 && empty($ids)) {
            return $this->fail('请选择需要设置的商品');
        }
        if ($all == 1) {
            $ids = $this->service->getColumn(['is_show' => 1, 'is_del' => 0], 'id');
        }
        if ($ids && !$this->service->update([['id', 'in', $ids]], ['store_label_id' => implode(',', $store_label_id)])) {
            return $this->fail('设置失败');
        }
        return $this->success('设置成功');
    }

    /**
     * 批量设置商品保障服务
     * @return mixed
     */
    public function setProductEnsure()
    {
        [$ids, $all, $ensure_id] = $this->request->postMore([
            ['ids', []],
            ['all', 0],
            ['store_label_id', []],
        ], true);
        if (!$ensure_id) {
            return $this->fail('请选择商品保障服务');
        }
        if ($all == 0 && empty($ids)) {
            return $this->fail('请选择需要设置的商品');
        }
        if ($all == 1) {
            $ids = $this->service->getColumn(['is_show' => 1, 'is_del' => 0], 'id');
        }
        if ($ids && !$this->service->update([['id', 'in', $ids]], ['ensure_id' => implode(',', $ensure_id)])) {
            return $this->fail('设置失败');
        }
        return $this->success('设置成功');
    }


    /**
     * 批量设置商品参数
     * @return mixed
     */
    public function setProductSpecs()
    {
        [$ids, $all, $specs_id, $specs] = $this->request->postMore([
            ['ids', []],
            ['all', 0],
            ['specs_id', 0],
            ['specs', []],
        ], true);
        if (!$specs_id) {
            return $this->fail('请选择商品参数模版');
        }
        if (!$specs) {
            return $this->fail('请添加商品参数');
        }
        if ($all == 0 && empty($ids)) {
            return $this->fail('请选择需要设置的商品');
        }
        if ($all == 1) {
            $ids = $this->service->getColumn(['is_show' => 1, 'is_del' => 0], 'id');
        }
        if ($ids && !$this->service->update([['id', 'in', $ids]], ['specs_id' => $specs_id, 'specs' => json_encode($specs)])) {
            return $this->fail('设置失败');
        }
        return $this->success('设置成功');
    }

    /**
     * 获取规格模板
     * @return mixed
     */
    public function get_rule()
    {
        $list = $this->service->getRule();
        return $this->success($list);
    }

    /**
     * 获取商品详细信息
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_product_info($id = 0)
    {
        return $this->success($this->service->getInfo((int)$id));
    }

    /**
     * 保存新建或编辑
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function save($id)
    {
        $data = $this->request->postMore([
            ['product_type', 0],//商品类型
            ['cate_id', []],
            ['store_name', ''],
            ['store_info', ''],
            ['keyword', ''],
            ['unit_name', '件'],
            ['recommend_image', ''],
            ['slider_image', []],
            ['is_sub', []],//佣金是单独还是默认
            ['sort', 0],
            ['sales', 0],
            ['ficti', 100],
            ['give_integral', 0],
            ['is_show', 0],
            ['is_hot', 0],
            ['is_benefit', 0],
            ['is_best', 0],
            ['is_new', 0],
            ['mer_use', 0],
            ['is_postage', 0],
            ['is_good', 0],
            ['description', ''],
            ['spec_type', 0],
            ['video_open', 0],
            ['video_link', ''],
            ['items', []],
            ['attrs', []],
            ['recommend', []],//商品推荐
            ['activity', []],
            ['coupon_ids', []],
            ['label_id', []],
            ['command_word', ''],
            ['tao_words', ''],
            ['type', 0],
            ['delivery_type', []],//物流设置
            ['freight', 1],//运费设置
            ['postage', 0],//邮费
            ['temp_id', 0],//运费模版
            ['recommend_list', []],
            ['brand_id', []],
            ['soure_link', ''],
            ['bar_code', ''],
            ['code', ''],
            ['is_support_refund', 1],//是否支持退款
            ['is_presale_product', 0],//预售商品开关
            ['presale_time', []],//预售时间
            ['presale_day', 0],//预售发货日
            ['is_vip_product', 0],//是否付费会员商品
            ['auto_on_time', 0],//自动上架时间
            ['auto_off_time', 0],//自动下架时间
            ['custom_form', []],//自定义表单
            ['store_label_id', []],//商品标签
            ['ensure_id', []],//商品保障服务区
            ['specs', []],//商品参数
            ['specs_id', 0],//商品参数ID
        ]);
        $this->service->save((int)$id, $data);
        return $this->success($id ? '保存商品信息成功' : '添加商品成功!');
    }

    /**
     * 删除指定资源
     *
     * @param int $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //删除商品检测是否有参与活动
        $this->service->checkActivity($id);
        $res = $this->service->del($id);
        event('product.delete', [$id]);
        return $this->success($res);
    }

    /**
     * 生成规格列表
     * @param int $id
     * @param int $type
     * @return mixed
     */
    public function is_format_attr($id = 0, $type = 0)
    {
        $data = $this->request->postMore([
            ['attrs', []],
            ['items', []],
            ['product_type', 0]
        ]);
        if ($id > 0 && $type == 1) $this->service->checkActivity($id);
        $info = $this->service->getAttr($data, $id, $type);
        return $this->success(compact('info'));
    }

    /**
     * 获取选择的商品列表
     * @return mixed
     */
    public function search_list()
    {
        $where = $this->request->getMore([
            ['cate_id', ''],
            ['store_name', ''],
            ['type', 1],
            ['is_live', 0],
            ['is_new', ''],
            ['is_vip_product', ''],
            ['is_presale_product', ''],
            ['store_label_id', '']
        ]);
        $where['is_show'] = 1;
        $where['is_del'] = 0;
        /** @var StoreCategoryServices $storeCategoryServices */
        $storeCategoryServices = app()->make(StoreCategoryServices::class);
        if ($where['cate_id'] !== '') {
            if ($storeCategoryServices->value(['id' => $where['cate_id']], 'pid')) {
                $where['sid'] = $where['cate_id'];
            } else {
                $where['cid'] = $where['cate_id'];
            }
        }
        unset($where['cate_id']);
        $list = $this->service->searchList($where);
        return $this->success($list);
    }

    /**
     * 获取某个商品规格
     * @return mixed
     */
    public function get_attrs()
    {
        [$id, $type] = $this->request->getMore([
            [['id', 'd'], 0],
            [['type', 'd'], 0],
        ], true);
        $info = $this->service->getProductRules($id, $type);
        return $this->success(compact('info'));
    }

    /**
     * 获取运费模板列表
     * @return mixed
     */
    public function get_template()
    {
        return $this->success($this->service->getTemp());
    }

    /**
     * 获取视频上传token
     * @return mixed
     * @throws \Exception
     */
    public function getTempKeys()
    {
        $upload = UploadService::init();
        $re = $upload->getTempKeys();
        return $re ? $this->success($re) : $this->fail($upload->getError());
    }

    /**
     * 检测商品是否开活动
     * @param $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function check_activity($id)
    {
        $this->service->checkActivity($id);
        return $this->success('删除成功');
    }

    /**
     * 获取商品所有规格数据
     * @param StoreProductAttrValueServices $services
     * @param $id
     * @return mixed
     */
    public function getAttrs(StoreProductAttrValueServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少商品ID');
        }
        return $this->success($services->getProductAttrValue(['product_id' => $id, 'type' => 0]));
    }

    /**
     * 快速修改商品规格库存
     * @param StoreProductAttrValueServices $services
     * @param $id
     * @return mixed
     */
    public function saveProductAttrsStock(StoreProductAttrValueServices $services, $id)
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
        return $this->success(['stock' => $services->saveProductAttrsStock((int)$id, $attrs)]);
    }

    /**
     * 导入卡密
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function import_card()
    {
        $data = $this->request->getMore([
            ['file', ""]
        ]);
        if (!$data['file']) return app('json')->fail('请上传文件');
        $file = public_path() . substr($data['file'], 1);
        /** @var FileService $readExcelService */
        $readExcelService = app()->make(FileService::class);
        $cardData = $readExcelService->readProductCardExcel($file, 2);
        return app('json')->success($cardData);
    }

    /**
     * 导入erp商品
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function import_erp_product()
    {
        [$path] = $this->request->postMore([
            ['path', ""]
        ], true);
        if (!$path) {
            return $this->fail('请上传表格');
        }
        $path = public_path() . substr($path, 1);

        $data = SpreadsheetExcelService::getExcelData($path, ['spu' => 'A']);
        $spus = [];
        foreach ($data as $item) {
            $spus[] = $item['spu'];
        }
        $spus = array_unique(array_filter($spus));
        foreach ($spus as $spu) {
            ProductSyncErp::dispatchDo('productFromErp', [$spu]);
        }
        unlink($path);
        return $this->success('已加入消息队列进行同步');
    }
}
