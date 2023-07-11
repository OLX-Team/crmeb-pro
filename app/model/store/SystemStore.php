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

namespace app\model\store;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 门店列表
 * Class SystemStore
 * @package app\model\store
 */
class SystemStore extends BaseModel
{
    use ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'system_store';

    /**
     * 经纬度获取器
     * @param $value
     * @param $data
     * @return string
     */
    public static function getLatlngAttr($value, $data)
    {
        return $data['latitude'] . ',' . $data['longitude'];
    }

    /**
     * 店铺类型搜索器
     * @param Model $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value !== '') {
            switch ((int)$value) {
                case 1://营业中
                case 0://休息中
                    $query->where(['is_del' => 0, 'is_show' => 1]);
                    break;
                case -1://已停业
                    $query->where(['is_del' => 0, 'is_show' => 0]);
                    break;
                default:
                    $query->where(['is_del' => 0]);
                    break;
            }
        }
    }

    /**
     * is_del搜索器
     * @param $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '') $query->where('is_del', $value);
    }

    /**
     * is_store搜索器
     * @param $query
     * @param $value
     */
    public function searchIsStoreAttr($query, $value)
    {
        if ($value !== '') $query->where('is_store', $value);
    }

    /**
     * 手机号,id,昵称搜索器
     * @param Model $query
     * @param $value
     */
    public function searchKeywordsAttr($query, $value)
    {
        if ($value != '') {
            $query->where('id|name|introduction|phone|detailed_address|address', 'LIKE', "%$value%");
        }
    }


}
