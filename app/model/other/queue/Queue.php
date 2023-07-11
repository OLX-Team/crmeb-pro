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
namespace app\model\other\queue;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;
use think\Model;

/**
 * 队列model
 * Class Queue
 * @package app\model\other\queue
 */
class Queue extends BaseModel
{
    use ModelTrait;

    const EXPIRE = 0;

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'queue_list';

    /**
     * 缓存KEY搜索器
     * @param Model $query
     * @param $value
     * @param $data
     */
    public function searchExecuteKeyAttr($query, $value, $data)
    {
        if ($value) $query->where('execute_key', $value);
    }

    /**
     * 队列类型搜索器
     * @param $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                if ($value) $query->whereIn('type', $value);
            } else {
                if ($value) $query->where('type', $value);
            }
        }
    }

    /**
     * 队列主键搜索器
     * @param $query
     * @param $value
     */
    public function searchIdAttr($query, $value)
    {
        if ($value) $query->where('id', $value);
    }

    /**
     * 队列状态搜索器
     * @param $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value) {
            if (is_array($value)) {
                $query->whereIn('status', $value);
            } else {
                $query->where('status', $value);
            }
        }
    }
}
