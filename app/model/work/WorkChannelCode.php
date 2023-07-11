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

namespace app\model\work;


use app\model\other\Category;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\model\concern\SoftDelete;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * 企业微信渠道码
 * Class WorkChannelCode
 * @package app\model\work
 */
class WorkChannelCode extends BaseModel
{

    use ModelTrait, SoftDelete;

    /**
     * @var string
     */
    protected $name = 'work_channel_code';

    /**
     * @var string
     */
    protected $key = 'id';

    /**
     * @var string
     */
    protected $autoWriteTimestamp = 'int';

    /**
     * @return HasMany
     */
    public function cycle()
    {
        return $this->hasMany(WorkChannelCycle::class, 'channel_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function useridLimit()
    {
        return $this->hasMany(WorkChannelLimit::class, 'channel_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'cate_id');
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setReserveUseridAttr($value)
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getReserveUseridAttr($value)
    {
        return json_decode($value, true);
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setUseridsAttr($value)
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getuseridsAttr($value)
    {
        return json_decode($value, true);
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setWelcomeWordsAttr($value)
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getWelcomeWordsAttr($value)
    {
        return json_decode($value, true);
    }

    /**
     * @param $value
     * @return false|string
     */
    public function setLabelIdAttr($value)
    {
        return json_encode($value);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function getLabelIdAttr($value)
    {
        return json_decode($value, true);
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchNameAttr($query, $value)
    {
        $query->where('name', 'like', '%' . $value . '%');
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if ('' !== $value) {
            $query->where('type', $value);
        }
    }

    /**
     * @param $query
     * @param $value
     */
    public function searchIdAttr($query, $value)
    {
        if (is_array($value)) {
            $query->where('id', 'in', $value);
        } else {
            $query->where('id', $value);
        }
    }

    /**
     * 分类搜索
     * @param $query
     * @param $value
     */
    public function searchCateIdAttr($query, $value)
    {
        if ('' !== $value) {
            $query->where('cate_id', $value);
        }
    }
}
