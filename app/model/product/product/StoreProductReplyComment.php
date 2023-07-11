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

namespace app\model\product\product;


use app\model\user\User;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\model\relation\HasOne;

/**
 * 商品评价回复表
 * Class StoreProductReplyComment
 * @package app\model\product\product
 */
class StoreProductReplyComment extends BaseModel
{

    use ModelTrait;

    /**
     * @var string
     */
    protected $name = 'store_product_reply_comment';

    /**
     * @var string
     */
    protected $key = 'id';

    /**
     * @var bool
     */
    protected $autoWriteTimestamp = false;

    /**
     * @return HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid')->field(['uid', 'avatar', 'nickname']);
    }

    /**
     * @return HasOne
     */
    public function children()
    {
        return $this->hasOne(self::class, 'pid', 'id');
    }

    /**
     * 点赞关联
     * @return \think\model\relation\HasOne
     */
    public function productRelation()
    {
        return $this->hasOne(StoreProductRelation::class, 'relation_id', 'id');
    }

    /**
     * 评论列表搜索
     * @param $query
     * @param $value
     */
    public function searchReplyIdAttr($query, $value)
    {
        $query->where('reply_id', $value);
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        $query->where('uid', $value);
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchNotUidAttr($query, $value)
    {
        $query->where('uid', '<>', $value);
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchStoreIdAttr($query, $value)
    {
        $query->where('store_id', $value);
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchPidAttr($query, $value)
    {
        $query->where('pid', $value);
    }

    /**
     * 时间搜索
     * @param $query
     * @param $value
     */
    public function searchCreatetimeAttr($query, $value)
    {
        $this->searchTimeAttr($query, $value, ['timeKey' => 'create_time']);
    }

    /**
     * @param $value
     * @return false|string
     */
    public function getCreateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * @param $value
     * @return false|string
     */
    public function getUpdateTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }
}
