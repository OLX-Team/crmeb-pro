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


use app\dao\product\product\StoreProductRelationDao;
use app\services\BaseServices;
use app\jobs\product\ProductLogJob;
use crmeb\services\CacheService;
use crmeb\traits\ServicesTrait;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;

/**
 * Class StoreProductRelationService
 * @package app\services\product\product
 */
class StoreProductRelationServices extends BaseServices
{
    use ServicesTrait;

    const CATEGORY_PRODUCT = 'product';//商品
    const CATEGORY_REPLY = 'reply';//评价
    const CATEGORY_COMMENT = 'comment';//评价回复

    const TYPE_COLLECT = 'collect';//收藏
    const TYPE_LIKE = 'like';//点赞

    /**
     * StoreProductRelationServices constructor.
     * @param StoreProductRelationDao $dao
     */
    public function __construct(StoreProductRelationDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 用户是否点赞或收藏商品
     * @param array $where
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function isProductRelation(array $where)
    {
        $res = $this->dao->getOne($where);
        if ($res) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取用户收藏数量
     * @param int $uid
     * @param int $relationId
     * @param string $type
     * @param string $category
     * @return int
     */
    public function getUserCount(int $uid, int $relationId = 0, string $type = self::TYPE_COLLECT, string $category = self::CATEGORY_PRODUCT)
    {
        $where = ['uid' => $uid, 'tye' => $type, 'category' => $category];
        if ($relationId) {
            $where['relation_id'] = $relationId;
        }
        $name = $uid . '_' . $type . '_' . $category . '_' . $relationId;
        $type = 'relation_' . fmod((float)$uid, (float)10);
        return CacheService::redisHandler($type)->remember($name, function () use ($where) {
            return $this->dao->count($where);
        }, 3000);
    }

    /**
     * @param int $uid
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserCollectProduct(int $uid)
    {
        $where['uid'] = $uid;
        $where['type'] = 'collect';
        $where['category'] = self::CATEGORY_PRODUCT;
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, 'relation_id,category', $page, $limit);
        foreach ($list as $k => $product) {
            if (isset($product['product']) && isset($product['product']['id'])) {
                $list[$k]['product_id'] = $product['relation_id'];
                $list[$k]['pid'] = $product['product']['id'] ?? 0;
                $list[$k]['store_name'] = $product['product']['store_name'] ?? 0;
                $list[$k]['price'] = $product['product']['price'] ?? 0;
                $list[$k]['ot_price'] = $product['product']['ot_price'] ?? 0;
                $list[$k]['sales'] = $product['product']['sales'] ?? 0;
                $list[$k]['image'] = get_thumb_water($product['product']['image'] ?? 0);
                $list[$k]['is_del'] = $product['product']['is_del'] ?? 0;
                $list[$k]['is_show'] = $product['product']['is_show'] ?? 0;
                $list[$k]['is_fail'] = $product['product']['is_del'] && $product['product']['is_show'];
            } else {
                unset($list[$k]);
            }
        }
        return $list;
    }

    /**
     * 添加点赞 收藏
     * @param int $uid
     * @param int $productIds
     * @param string $relationType
     * @param string $category
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function productRelation(int $uid, array $productIds, string $relationType, string $category = self::CATEGORY_PRODUCT)
    {
        $relationType = strtolower($relationType);
        $category = strtolower($category);
        $relationId = $this->dao->getColumn([['uid', '=', $uid], ['relation_id', 'IN', $productIds], ['type', '=', $relationType], ['category', '=', $category]], 'relation_id');
        $data = ['uid' => $uid, 'add_time' => time(), 'type' => $relationType, 'category' => $category];
        $dataAll = [];
        foreach ($productIds as $key => $product_id) {
            if (in_array($product_id, $relationId)) {
                continue;
            }
            $data['relation_id'] = $product_id;
            $dataAll[] = $data;
        }
        if ($dataAll) {
            if (!$this->dao->saveAll($dataAll)) {
                throw new ValidateException('添加失败');
            }
        }
        if ($category == 'product') {
            //收藏记录
            ProductLogJob::dispatch(['collect', ['uid' => $uid, 'relation_id' => $productIds, 'product_id' => $productIds]]);
        }

        return true;
    }

    /**
     * 取消 点赞 收藏
     * @param int $uid
     * @param array $productId
     * @param string $relationType
     * @param string $category
     * @return bool
     * @throws \Exception
     */
    public function unProductRelation(int $uid, array $productId, string $relationType, string $category = self::CATEGORY_PRODUCT)
    {
        $relationType = strtolower($relationType);
        $category = strtolower($category);
        $storeProductRelation = $this->dao->delete(['uid' => $uid, 'relation_id' => $productId, 'type' => $relationType, 'category' => $category]);
        if (!$storeProductRelation) throw new ValidateException('取消失败');
        return true;
    }

}
