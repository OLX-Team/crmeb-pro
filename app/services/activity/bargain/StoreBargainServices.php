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

namespace app\services\activity\bargain;

use app\dao\activity\bargain\StoreBargainDao;
use app\Request;
use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use app\services\other\QrcodeServices;
use app\services\product\branch\StoreBranchProductServices;
use app\services\product\ensure\StoreProductEnsureServices;
use app\services\product\label\StoreProductLabelServices;
use app\services\product\product\StoreDescriptionServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrResultServices;
use app\services\product\sku\StoreProductAttrServices;
use app\services\product\sku\StoreProductAttrValueServices;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\user\UserServices;
use app\jobs\product\ProductLogJob;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use crmeb\services\UploadService;
use crmeb\services\UtilService;
use crmeb\services\wechat\MiniProgram;
use GuzzleHttp\Psr7\Utils;
use think\exception\ValidateException;

/**
 * Class StoreBargainServices
 * @package app\services\activity\bargain
 * @method get(int $id, array $field) 获取一条数据
 * @method getBargainIdsArray(array $ids, array $field)
 * @method sum(array $where, string $field)
 * @method update(int $id, array $data)
 * @method addBargain(int $id, string $field)
 * @method value(array $where, string $field)
 * @method validWhere()
 * @method getList(array $where, int $page = 0, int $limit = 0) 获取砍价列表
 */
class StoreBargainServices extends BaseServices
{

    const DRNCCGFB = '$2y$10';

    /**
     * StoreCombinationServices constructor.
     * @param StoreBargainDao $dao
     */
    public function __construct(StoreBargainDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 判断砍价商品是否开启
     * @param int $bargainId
     * @return int|string
     */
    public function validBargain($bargainId = 0)
    {
        $where = [];
        $time = time();
        $where[] = ['is_del', '=', 0];
        $where[] = ['status', '=', 1];
        $where[] = ['start_time', '<', $time];
        $where[] = ['stop_time', '>', $time - 85400];
        if ($bargainId) $where[] = ['id', '=', $bargainId];
        return $this->dao->getCount($where);
    }

    /**
     * 获取后台列表
     * @param array $where
     * @return array
     */
    public function getStoreBargainList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $page, $limit);
        $count = $this->dao->count($where);
        /** @var StoreBargainUserServices $storeBargainUserServices */
        $storeBargainUserServices = app()->make(StoreBargainUserServices::class);
        $ids = array_column($list, 'id');
        $countAll = $storeBargainUserServices->getAllCount([['bargain_id', 'in', $ids]]);
        $countSuccess = $storeBargainUserServices->getAllCount([
            ['status', '=', 3],
            ['bargain_id', 'in', $ids]
        ]);
        /** @var StoreBargainUserHelpServices $storeBargainUserHelpServices */
        $storeBargainUserHelpServices = app()->make(StoreBargainUserHelpServices::class);
        $countHelpAll = $storeBargainUserHelpServices->getHelpAllCount([['bargain_id', 'in', $ids]]);
        foreach ($list as &$item) {
            $item['count_people_all'] = $countAll[$item['id']] ?? 0;//参与人数
            $item['count_people_help'] = $countHelpAll[$item['id']] ?? 0;//帮忙砍价人数
            $item['count_people_success'] = $countSuccess[$item['id']] ?? 0;//砍价成功人数
            $item['stop_status'] = $item['stop_time'] < time() ? 1 : 0;
//            if ($item['status']) {
            if ($item['start_time'] > time())
                $item['start_name'] = '未开始';
            else if ($item['stop_time'] < time())
                $item['start_name'] = '已结束';
            else if ($item['stop_time'] > time() && $item['start_time'] < time()) {
                $item['start_name'] = '进行中';
            }
//            } else $item['start_name'] = '已结束';
        }
        return compact('list', 'count');
    }

    /**
     * 保存数据
     * @param int $id
     * @param array $data
     */
    public function saveData(int $id, array $data)
    {
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        $productInfo = $storeProductServices->getOne(['is_show' => 1, 'is_del' => 0, 'id' => $data['product_id']]);
        if (!$productInfo) {
            throw new AdminException('原商品已下架或移入回收站');
        }
        if ($productInfo['is_vip_product'] || $productInfo['is_presale_product']) {
            throw new AdminException('该商品是预售或svip专享');
        }
        $data['product_type'] = $productInfo['product_type'];
        $custom_form = $productInfo['custom_form'] ?? [];
        $data['custom_form'] = is_array($custom_form) ? json_encode($custom_form) : $custom_form;
        $store_label_id = $productInfo['store_label_id'] ?? [];
        $data['store_label_id'] = is_array($store_label_id) ? implode(',', $store_label_id) : $store_label_id;
        $ensure_id = $productInfo['ensure_id'] ?? [];
        $data['ensure_id'] = is_array($ensure_id) ? implode(',', $ensure_id) : $ensure_id;
        $specs = $productInfo['specs'] ?? [];
        $data['specs'] = is_array($specs) ? json_encode($specs) : $specs;
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
        $description = $data['description'];
        $detail = $data['attrs'];
        $items = $data['items'];
        $data['start_time'] = strtotime($data['section_time'][0]);
        $data['stop_time'] = strtotime($data['section_time'][1]);
        $data['image'] = $data['images'][0] ?? '';
        $data['images'] = json_encode($data['images']);
        $data['stock'] = $detail[0]['stock'];
        $data['quota'] = $detail[0]['quota'];
        $data['quota_show'] = $detail[0]['quota'];
        $data['price'] = $detail[0]['price'];
        $data['min_price'] = $detail[0]['min_price'];

        if ($detail[0]['min_price'] < 0 || $detail[0]['price'] <= 0 || $detail[0]['min_price'] === '' || $detail[0]['price'] === '') throw new ValidateException('金额不能小于0');
        if ($detail[0]['min_price'] >= $detail[0]['price']) throw new ValidateException('砍价最低价不能大于或等于起始金额');
        if ($detail[0]['quota'] > $detail[0]['stock']) throw new ValidateException('限量不能超过商品库存');

        //按照能砍掉的金额计算最大设置人数，并判断填写的砍价人数是否大于最大设置人数
        $bNum = bcmul(bcsub((string)$data['price'], (string)$data['min_price'], 2), '100');
        if ($data['people_num'] > $bNum) throw new ValidateException('砍价人数不能大于' . $bNum . '人');

        unset($data['section_time'], $data['description'], $data['attrs'], $data['items'], $detail[0]['min_price'], $detail[0]['_index'], $detail[0]['_rowKey']);
        /** @var StoreDescriptionServices $storeDescriptionServices */
        $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        $this->transaction(function () use ($id, $data, $description, $detail, $items, $storeDescriptionServices, $storeProductAttrServices) {
            if ($id) {
                $res = $this->dao->update($id, $data);
                if (!$res) throw new AdminException('修改失败');
            } else {
                $data['add_time'] = time();
                $res = $this->dao->save($data);
                if (!$res) throw new AdminException('添加失败');
                $id = (int)$res->id;
            }
            $storeDescriptionServices->saveDescription((int)$id, $description, 2);
            $skuList = $storeProductAttrServices->validateProductAttr($items, $detail, (int)$id, 2);
            $valueGroup = $storeProductAttrServices->saveProductAttr($skuList, (int)$id, 2);

            $res = true;
            foreach ($valueGroup as $item) {
                $res = $res && CacheService::setStock($item['unique'], (int)$item['quota_show'], 2);
            }
            if (!$res) {
                throw new AdminException('占用库存失败');
            }
        });
    }

    /**
     * 获取砍价详情
     * @param int $id
     * @return array|\think\Model|null
     */
    public function getInfo(int $id)
    {
        $info = $this->dao->get($id);
        if ($info) {
            if ($info['start_time'])
                $start_time = date('Y-m-d H:i:s', $info['start_time']);

            if ($info['stop_time'])
                $stop_time = date('Y-m-d H:i:s', $info['stop_time']);
            if (isset($start_time) && isset($stop_time))
                $info['section_time'] = [$start_time, $stop_time];
            else
                $info['section_time'] = [];
            unset($info['start_time'], $info['stop_time']);
        }

        $info['give_integral'] = intval($info['give_integral']);
        $info['price'] = floatval($info['price']);
        $info['postage'] = floatval($info['postage']);
        $info['cost'] = floatval($info['cost']);
        $info['bargain_max_price'] = floatval($info['bargain_max_price']);
        $info['bargain_min_price'] = floatval($info['bargain_min_price']);
        $info['min_price'] = floatval($info['min_price']);
        $info['weight'] = floatval($info['weight']);
        $info['volume'] = floatval($info['volume']);
        if (!$info['delivery_type']) {
            $info['delivery_type'] = [1];
        }
        if ($info['postage']) {
            $info['freight'] = 2;
        } elseif ($info['temp_id']) {
            $info['freight'] = 3;
        } else {
            $info['freight'] = 1;
        }
        /** @var StoreDescriptionServices $storeDescriptionServices */
        $storeDescriptionServices = app()->make(StoreDescriptionServices::class);
        $info['description'] = $storeDescriptionServices->getDescription(['product_id' => $id, 'type' => 2]);
        $info['attrs'] = $this->attrList($id, $info['product_id']);
        return $info;
    }

    /**
     * 获取规格
     * @param int $id
     * @param int $pid
     * @return mixed
     */
    public function attrList(int $id, int $pid)
    {
        /** @var StoreProductAttrResultServices $storeProductAttrResultServices */
        $storeProductAttrResultServices = app()->make(StoreProductAttrResultServices::class);
        $bargainResult = $storeProductAttrResultServices->value(['product_id' => $id, 'type' => 2], 'result');
        $items = json_decode($bargainResult, true)['attr'];
        $productAttr = $this->getattr($items, $pid, 0);
        $bargainAttr = $this->getattr($items, $id, 2);
        foreach ($productAttr as $pk => $pv) {
            foreach ($bargainAttr as &$sv) {
                if ($pv['detail'] == $sv['detail']) {
                    $productAttr[$pk] = $sv;
                }
            }
            $productAttr[$pk]['detail'] = json_decode($productAttr[$pk]['detail']);
        }
        $attrs['items'] = $items;
        $attrs['value'] = $productAttr;
        foreach ($items as $key => $item) {
            $header[] = ['title' => $item['value'], 'key' => 'value' . ($key + 1), 'align' => 'center', 'minWidth' => 80];
        }
        $header[] = ['title' => '图片', 'slot' => 'pic', 'align' => 'center', 'minWidth' => 120];
        $header[] = ['title' => '砍价起始金额', 'slot' => 'price', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '砍价最低价', 'slot' => 'min_price', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '成本价', 'key' => 'cost', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '原价', 'key' => 'ot_price', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '库存', 'key' => 'stock', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '限量', 'slot' => 'quota', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '重量(KG)', 'key' => 'weight', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '体积(m³)', 'key' => 'volume', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '商品条形码', 'key' => 'bar_code', 'align' => 'center', 'minWidth' => 80];
        $header[] = ['title' => '商品编号', 'key' => 'code', 'align' => 'center', 'minWidth' => 80];
        $attrs['header'] = $header;
        return $attrs;
    }

    /**
     * 获取规格
     * @param $attr
     * @param $id
     * @param $type
     * @return array
     */
    public function getattr($attr, $id, $type)
    {
        /** @var StoreProductAttrValueServices $storeProductAttrValueServices */
        $storeProductAttrValueServices = app()->make(StoreProductAttrValueServices::class);
        $value = attr_format($attr)[1];
        $valueNew = [];
        $count = 0;
        if ($type == 2) {
            $min_price = $this->dao->value(['id' => $id], 'min_price');
        } else {
            $min_price = 0;
        }
        foreach ($value as $key => $item) {
            $detail = $item['detail'];
//            sort($item['detail'], SORT_STRING);
            $suk = implode(',', $item['detail']);
            $sukValue = $storeProductAttrValueServices->getSkuArray(['product_id' => $id, 'type' => $type, 'suk' => $suk], 'bar_code,code,cost,price,ot_price,stock,image as pic,weight,volume,brokerage,brokerage_two,quota,quota_show', 'suk');
            if (count($sukValue)) {
                foreach (array_values($detail) as $k => $v) {
                    $valueNew[$count]['value' . ($k + 1)] = $v;
                }
                $valueNew[$count]['detail'] = json_encode($detail);
                $valueNew[$count]['pic'] = $sukValue[$suk]['pic'] ?? '';
                $valueNew[$count]['price'] = $sukValue[$suk]['price'] ? floatval($sukValue[$suk]['price']) : 0;
                $valueNew[$count]['min_price'] = $min_price ? floatval($min_price) : 0;
                $valueNew[$count]['cost'] = $sukValue[$suk]['cost'] ? floatval($sukValue[$suk]['cost']) : 0;
                $valueNew[$count]['ot_price'] = isset($sukValue[$suk]['ot_price']) ? floatval($sukValue[$suk]['ot_price']) : 0;
                $valueNew[$count]['stock'] = $sukValue[$suk]['stock'] ? intval($sukValue[$suk]['stock']) : 0;
//                $valueNew[$count]['quota'] = $sukValue[$suk]['quota'] ? intval($sukValue[$suk]['quota']) : 0;
                $valueNew[$count]['quota'] = isset($sukValue[$suk]['quota_show']) && $sukValue[$suk]['quota_show'] ? intval($sukValue[$suk]['quota_show']) : 0;
                $valueNew[$count]['code'] = $sukValue[$suk]['code'] ?? '';
                $valueNew[$count]['bar_code'] = $sukValue[$suk]['bar_code'] ?? '';
                $valueNew[$count]['weight'] = $sukValue[$suk]['weight'] ? floatval($sukValue[$suk]['weight']) : 0;
                $valueNew[$count]['volume'] = $sukValue[$suk]['volume'] ? floatval($sukValue[$suk]['volume']) : 0;
                $valueNew[$count]['brokerage'] = $sukValue[$suk]['brokerage'] ? floatval($sukValue[$suk]['brokerage']) : 0;
                $valueNew[$count]['brokerage_two'] = $sukValue[$suk]['brokerage_two'] ? floatval($sukValue[$suk]['brokerage_two']) : 0;
                $valueNew[$count]['opt'] = $type != 0 ? true : false;
                $count++;
            }
        }
        return $valueNew;
    }

    /**
     * 砍价列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBargainList()
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->BargainList($page, $limit);
        if ($list) {
            $bargainIds = array_column($list, 'id');
            /** @var StoreBargainUserHelpServices $bargainHelp */
            $bargainHelp = app()->make(StoreBargainUserHelpServices::class);
            $bargainPeople = $bargainHelp->getBargainPeople([['bargain_id', 'in', $bargainIds], ['type', '=', 1]], 'bargain_id,count(*) as people', 'bargain_id');
            foreach ($list as &$item) {
                $item['people'] = $bargainPeople[$item['id']]['people'] ?? 0;
                $item['price'] = floatval($item['price']);
            }
        }
        return $list;
    }

    /**
     * 获取单条砍价
     * @param Request $request
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBargain(Request $request, int $id)
    {
        $bargain = $this->dao->getOne(['id' => $id], '*', ['description']);
        if (!$bargain) throw new ValidateException('砍价商品不存在');
        $this->dao->addBargain($id, 'look');
        $bargain['time'] = time();
        if ($bargain['stop_time'] < time()) throw new ValidateException('砍价已结束');
        $user = $request->user();
        $data['userInfo']['uid'] = $user['uid'];
        $data['userInfo']['nickname'] = $user['nickname'];
        $data['userInfo']['avatar'] = $user['avatar'];

        //品牌名称
        /** @var StoreProductServices $storeProductServices */
        $storeProductServices = app()->make(StoreProductServices::class);
        $bargain['brand_name'] = $storeProductServices->productIdByBrandName($bargain['product_id']);
        $bargain['store_label'] = $storeInfo['ensure'] = [];
        if ($bargain['store_label_id']) {
            /** @var StoreProductLabelServices $storeProductLabelServices */
            $storeProductLabelServices = app()->make(StoreProductLabelServices::class);
            $bargain['store_label'] = $storeProductLabelServices->getColumn([['id', 'in', $bargain['store_label_id']]], 'id,label_name');
        }
        if ($bargain['ensure_id']) {
            /** @var StoreProductEnsureServices $storeProductEnsureServices */
            $storeProductEnsureServices = app()->make(StoreProductEnsureServices::class);
            $bargain['ensure'] = $storeProductEnsureServices->getColumn([['id', 'in', $bargain['ensure_id']]], 'id,name,image,desc');
        }
        /** @var StoreProductAttrServices $storeProductAttrServices */
        $storeProductAttrServices = app()->make(StoreProductAttrServices::class);
        [$productAttr, $productValue] = $storeProductAttrServices->getProductAttrDetail($id, $user->uid, 0, 2, $bargain['product_id']);
        foreach ($productValue as $v) {
            $bargain['attr'] = $v;
        }

        $data['bargain'] = get_thumb_water($bargain);
        $bargainNew = get_thumb_water($bargain, 'small');
        $data['bargain']['small_image'] = $bargainNew['image'];

        /** @var StoreOrderServices $orderService */
        $orderService = app()->make(StoreOrderServices::class);
        $data['bargainSumCount'] = $orderService->count(['type' => 2, 'activity_id' => $id, 'uid' => $user['uid']]);//只要用户生成订单，就算作用电一次砍价的机会

        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $data['userBargainStatus'] = $bargainUserService->count(['bargain_id' => $id, 'uid' => $user->uid, 'is_del' => 0]);

        $data['site_name'] = sys_config('site_name');
        $data['share_qrcode'] = sys_config('share_qrcode', 0);
        //浏览记录
        ProductLogJob::dispatch(['visit', ['uid' => $user['uid'], 'id' => $id, 'product_id' => $bargain['product_id']], 'bargain']);
        return $data;
    }

    /**
     * 验证砍价是否能支付
     * @param int $bargainId
     * @param int $uid
     */
    public function checkBargainUser(int $bargainId, int $uid)
    {
        /** @var StoreBargainUserServices $bargainUserServices */
        $bargainUserServices = app()->make(StoreBargainUserServices::class);
        $bargainUserInfo = $bargainUserServices->getOne(['uid' => $uid, 'bargain_id' => $bargainId, 'status' => 1, 'is_del' => 0]);
        if (!$bargainUserInfo)
            throw new ValidateException('砍价失败');
        $bargainUserTableId = $bargainUserInfo['id'];
        if ($bargainUserInfo['bargain_price_min'] < bcsub((string)$bargainUserInfo['bargain_price'], (string)$bargainUserInfo['price'], 2)) {
            throw new ValidateException('砍价未成功');
        }
        if ($bargainUserInfo['status'] == 3)
            throw new ValidateException('砍价已支付');

        /** @var StoreBargainServices $bargainService */
        $bargainService = app()->make(StoreBargainServices::class);
        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);
        $res = $attrValueServices->getOne(['product_id' => $bargainId, 'type' => 2]);
        if (!$bargainService->validBargain($bargainId) || !$res) {
            throw new ValidateException('该商品已下架或删除');
        }
        $StoreBargainInfo = $bargainService->get($bargainId);
        if (1 > $res['quota']) {
            throw new ValidateException('该商品库存不足');
        }
        $product_stock = $attrValueServices->value(['product_id' => $StoreBargainInfo['product_id'], 'suk' => $res['suk'], 'type' => 0], 'stock');
        if ($product_stock < 1) {
            throw new ValidateException('该商品库存不足');
        }
        return true;
    }

    /**
     * 修改砍价状态
     * @param int $bargainId
     * @param int $uid
     * @param int $bargainUserTableId
     * @return false
     */
    public function setBargainUserStatus(int $bargainId, int $uid, int $bargainUserTableId = 0)
    {
        if (!$bargainId || !$uid) return false;
        if (!$bargainUserTableId) {
            /** @var StoreBargainUserServices $bargainUserServices */
            $bargainUserServices = app()->make(StoreBargainUserServices::class);
            $bargainUserInfo = $bargainUserServices->getOne(['uid' => $uid, 'bargain_id' => $bargainId, 'status' => 1, 'is_del' => 0]);
            if (!$bargainUserInfo)
                throw new ValidateException('砍价失败');
            $bargainUserTableId = $bargainUserInfo['id'];
        }
        /** @var StoreBargainUserServices $bargainUserServices */
        $bargainUserServices = app()->make(StoreBargainUserServices::class);
        $bargainUser = $bargainUserServices->getOne(['id' => $bargainUserTableId, 'uid' => $uid, 'bargain_id' => $bargainId, 'status' => 1], 'price,bargain_price,bargain_price_min');
        if (!$bargainUser) {
            return false;
        }
        $userPrice = $bargainUser['price'];
        $price = bcsub($bargainUser['bargain_price'], $bargainUser['bargain_price_min'], 2);
        if (bcsub($price, $userPrice, 2) > 0) {
            return false;
        }
        return $bargainUserServices->updateBargainStatus($bargainUserTableId);
    }

    /**
     * 参与砍价
     * @param int $uid
     * @param int $bargainId
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setBargain(int $uid, int $bargainId)
    {
        if (!$bargainId) throw new ValidateException('参数错误');
        $bargainInfo = $this->dao->getOne([
            ['is_del', '=', 0],
            ['status', '=', 1],
            ['start_time', '<', time()],
            ['stop_time', '>', time()],
            ['id', '=', $bargainId],
        ]);
        if (!$bargainInfo) throw new ValidateException('砍价已结束');
        $bargainInfo = $bargainInfo->toArray();
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $count = $bargainUserService->count(['bargain_id' => $bargainId, 'uid' => $uid, 'is_del' => 0, 'status' => 1]);
        if ($count === false) {
            throw new ValidateException('参数错误');
        } elseif ($count) {
            return 'SUCCESSFUL';
        } else {
            $count = $bargainUserService->count(['uid' => $uid, 'bargain_id' => $bargainId, 'type' => 1]);
            if ($count >= $bargainInfo['num']) throw new ValidateException('您不能再发起此件商品砍价');
            $res = $bargainUserService->setBargain($bargainId, $uid, $bargainInfo);
        }
        if (!$res) {
            throw new ValidateException('参与失败');
        } else {
            return 'SUCCESS';
        }
    }

    /**
     * @param Request $request
     * @param int $bargainId
     * @param int $bargainUserUid
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setHelpBargain(int $uid, int $bargainId, int $bargainUserUid)
    {

        if (!$bargainId || !$bargainUserUid) throw new ValidateException('参数错误');
        $bargainInfo = $this->dao->getOne([
            ['is_del', '=', 0],
            ['status', '=', 1],
            ['start_time', '<', time()],
            ['stop_time', '>', time()],
            ['id', '=', $bargainId],
        ]);
        if (!$bargainInfo) throw new ValidateException('砍价已结束');
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $bargainUserTableId = $bargainUserService->getBargainUserTableId($bargainId, $bargainUserUid);
        if (!$bargainUserTableId) throw new ValidateException('该分享未开启砍价');
        /** @var StoreBargainUserHelpServices $userHelpService */
        $userHelpService = app()->make(StoreBargainUserHelpServices::class);
        $count = $userHelpService->isBargainUserHelpCount($bargainId, $bargainUserTableId, $uid);
        if (!$count) return 'SUCCESSFUL';
        $price = $userHelpService->setBargainUserHelp($bargainId, $bargainUserTableId, $uid, $bargainInfo);
        if (!(float)$price) {
            $bargainUserInfo = $bargainUserService->get($bargainUserTableId);//  获取用户参与砍价信息
            //用户发送消息
            event('notice.notice', [['uid' => $bargainUserUid, 'bargainInfo' => $bargainInfo, 'bargainUserInfo' => $bargainUserInfo,], 'bargain_success']);
        }
        return 'SUCCESS';
    }

    /**
     * 减库存加销量
     * @param int $num
     * @param int $bargainId
     * @param string $unique
     * @param int $store_id
     * @return bool
     */
    public function decBargainStock(int $num, int $bargainId, string $unique, int $store_id = 0)
    {
        $product_id = $this->dao->value(['id' => $bargainId], 'product_id');
        $productUnique = '';
        if ($unique) {
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            //减去砍价商品sku的库存增加销量
            $res = false !== $skuValueServices->decProductAttrStock($bargainId, $unique, $num, 2);
            //减去砍价商品的库存和销量
            $res = $res && $this->dao->decStockIncSales(['id' => $bargainId, 'type' => 2], $num);
            //减掉普通商品sku的库存加销量
            $suk = $skuValueServices->value(['unique' => $unique, 'product_id' => $bargainId, 'type' => 2], 'suk');
            $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $product_id, 'type' => 0], 'unique');
//            if ($productUnique) {
//                $res = $res && $skuValueServices->decProductAttrStock($product_id, $productUnique, $num);
//            }
        } else {
            //减去砍价商品的库存和销量
            $res = false !== $this->dao->decStockIncSales(['id' => $bargainId, 'type' => 2], $num);
        }
        if (!$store_id) {
            /** @var StoreProductServices $services */
            $services = app()->make(StoreProductServices::class);
            //减掉普通商品的库存加销量
            $res = $res && $services->decProductStock($num, $product_id, $productUnique);
        } else {
            /** @var StoreBranchProductServices $storeBranchService */
            $storeBranchService = app()->make(StoreBranchProductServices::class);
            //修改门店库存
            $storeBranchService->updataDecStock($num, $product_id, $store_id, $productUnique);
        }
        return $res;
    }

    /**
     * 减销量加库存
     * @param int $num
     * @param int $bargainId
     * @param string $unique
     * @param int $store_id
     * @return bool
     */
    public function incBargainStock(int $num, int $bargainId, string $unique, int $store_id = 0)
    {
        $product_id = $this->dao->value(['id' => $bargainId], 'product_id');
        $productUnique = '';
        if ($unique) {
            /** @var StoreProductAttrValueServices $skuValueServices */
            $skuValueServices = app()->make(StoreProductAttrValueServices::class);
            //减去砍价商品sku的销量,增加库存和限购数量
            $res = false !== $skuValueServices->incProductAttrStock($bargainId, $unique, $num, 2);
            //减去砍价商品的销量,增加库存
            $res = $res && $this->dao->incStockDecSales(['id' => $bargainId, 'type' => 2], $num);
            //减掉普通商品sku的销量,增加库存
            $suk = $skuValueServices->value(['unique' => $unique, 'product_id' => $bargainId, 'type' => 2], 'suk');
            $productUnique = $skuValueServices->value(['suk' => $suk, 'product_id' => $product_id, 'type' => 0], 'unique');
//            if ($productUnique) {
//                $res = $res && $skuValueServices->incProductAttrStock($product_id, $productUnique, $num);
//            }
        } else {
            //减去砍价商品的销量,增加库存
            $res = false !== $this->dao->incStockDecSales(['id' => $bargainId, 'type' => 2], $num);
        }
        if (!$store_id) {
            /** @var StoreProductServices $services */
            $services = app()->make(StoreProductServices::class);
            //减掉普通商品的销量加库存
            $res = $res && $services->incProductStock($num, $product_id, $productUnique);
        } else {
            /** @var StoreBranchProductServices $storeBranchService */
            $storeBranchService = app()->make(StoreBranchProductServices::class);
            //修改门店库存
            $storeBranchService->updataDecStock($num, $product_id, $store_id, $productUnique, false);
        }
        return $res;
    }

    /**
     * 获取砍价海报信息
     * @param int $bargainId
     * @param $user
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function posterInfo(int $bargainId, $user)
    {
        $storeBargainInfo = $this->dao->get($bargainId, ['title', 'image', 'price']);
        if (!$storeBargainInfo) {
            throw new ValidateException('砍价信息没有查到');
        }
        /** @var StoreBargainUserServices $services */
        $services = app()->make(StoreBargainUserServices::class);
        $bargainUser = $services->get(['bargain_id' => $bargainId, 'uid' => $user['uid']], ['price', 'bargain_price_min']);
        if (!$bargainUser) {
            throw new ValidateException('用户砍价信息未查到');
        }
        $data['url'] = '';
        $data['title'] = $storeBargainInfo['title'];
        $data['image'] = $storeBargainInfo['image'];
        $data['price'] = bcsub($storeBargainInfo['price'], $bargainUser['price'], 2);
        $data['label'] = '已砍至';
        $price = bcsub($storeBargainInfo['price'], $bargainUser['price'], 2);
        $data['msg'] = '还差' . (bcsub($price, $bargainUser['bargain_price_min'], 2)) . '元即可砍价成功';
        //只有在小程序端，才会生成二维码
        if (\request()->isRoutine()) {
            try {
                /** @var SystemAttachmentServices $systemAttachmentServices */
                $systemAttachmentServices = app()->make(SystemAttachmentServices::class);
                //小程序
                $name = $bargainId . '_' . $user['uid'] . '_' . $user['is_promoter'] . '_bargain_share_routine.jpg';
                $siteUrl = sys_config('site_url');
                $imageInfo = $systemAttachmentServices->getInfo(['name' => $name]);
                if (!$imageInfo) {
                    $valueData = 'id=' . $bargainId . '&bargain=' . $user['uid'];
                    /** @var UserServices $userServices */
                    $userServices = app()->make(UserServices::class);
                    if ($userServices->checkUserPromoter((int)$user['uid'], $user)) {
                        $valueData .= '&spread=' . $user['uid'];
                    }
                    $res = MiniProgram::appCodeUnlimit($valueData, 'pages/activity/goods_bargain_details/index', 280);
                    if (!$res) throw new ValidateException('二维码生成失败');
                    $uploadType = (int)sys_config('upload_type', 1);
                    $upload = UploadService::init($uploadType);
                    $res = (string)Utils::streamFor($res);
                    $res = $upload->to('routine/activity/bargain/code')->validate()->setAuthThumb(false)->stream($res, $name);
                    if ($res === false) {
                        throw new ValidateException($upload->getError());
                    }
                    $imageInfo = $upload->getUploadInfo();
                    $imageInfo['image_type'] = $uploadType;
                    if ($imageInfo['image_type'] == 1) $remoteImage = UtilService::remoteImage($siteUrl . $imageInfo['dir']);
                    else $remoteImage = UtilService::remoteImage($imageInfo['dir']);
                    if (!$remoteImage['status']) throw new ValidateException($remoteImage['msg']);
                    $systemAttachmentServices->save([
                        'name' => $imageInfo['name'],
                        'att_dir' => $imageInfo['dir'],
                        'satt_dir' => $imageInfo['thumb_path'],
                        'att_size' => $imageInfo['size'],
                        'att_type' => $imageInfo['type'],
                        'image_type' => $imageInfo['image_type'],
                        'module_type' => 2,
                        'time' => time(),
                        'pid' => 1,
                        'type' => 1
                    ]);
                    $url = $imageInfo['dir'];
                } else $url = $imageInfo['att_dir'];
                if ($imageInfo['image_type'] == 1) {
                    $data['url'] = $siteUrl . $url;
                } else {
                    $data['url'] = $url;
                }
            } catch (\Throwable $e) {
            }
        } else {
            if (sys_config('share_qrcode', 0) && request()->isWechat()) {
                /** @var QrcodeServices $qrcodeService */
                $qrcodeService = app()->make(QrcodeServices::class);
                $data['url'] = $qrcodeService->getTemporaryQrcode('bargain-' . $bargainId . '-' . $user['uid'], $user['uid'])->url;
            }
        }
        return $data;
    }

    /**
     * 验证砍价下单库存限量
     * @param int $uid
     * @param int $bargainId
     * @param int $cartNum
     * @param string $unique
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkBargainStock(int $uid, int $bargainId, int $cartNum = 1, string $unique = '')
    {
        if (!$this->validBargain($bargainId)) {
            throw new ValidateException('该商品已下架或删除');
        }
        /** @var StoreProductAttrValueServices $attrValueServices */
        $attrValueServices = app()->make(StoreProductAttrValueServices::class);
        $attrInfo = $attrValueServices->getOne(['product_id' => $bargainId, 'type' => 2]);
        if (!$attrInfo || $attrInfo['product_id'] != $bargainId) {
            throw new ValidateException('请选择有效的商品属性');
        }
        $productInfo = $this->dao->get($bargainId, ['*', 'title as store_name']);
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $bargainUserInfo = $bargainUserService->getOne(['uid' => $uid, 'bargain_id' => $bargainId, 'status' => 1, 'is_del' => 0]);
        if (!$bargainUserInfo || $bargainUserInfo['bargain_price_min'] < bcsub((string)$bargainUserInfo['bargain_price'], (string)$bargainUserInfo['price'], 2)) {
            throw new ValidateException('砍价未成功');
        }
        $unique = $attrInfo['unique'];
        if ($cartNum > $attrInfo['quota']) {
            throw new ValidateException('该商品库存不足' . $cartNum);
        }
        return [$attrInfo, $unique, $productInfo, $bargainUserInfo];
    }
}
