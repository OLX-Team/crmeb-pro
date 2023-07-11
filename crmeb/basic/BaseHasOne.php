<?php
declare (strict_types = 1);
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace crmeb\basic;


use think\Model;
use think\model\relation\HasOne;

/**
 * HasOne 关联类
 */
class BaseHasOne extends HasOne
{

	/**
     * 架构函数
     * @access public
     * @param  Model  $parent     上级模型对象
     * @param  string $model      模型名
     * @param  string $foreignKey 关联外键
     * @param  string $localKey   当前模型主键
     */
    public function __construct(Model $parent, string $model, string $foreignKey, string $localKey, bool $softDelete)
    {
        $this->parent     = $parent;
        $this->model      = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey   = $localKey;
        $this->query      = $softDelete ? (new $model)->db() : (new $model)->withTrashed();

        if (get_class($parent) == $model) {
            $this->selfRelation = true;
        }
    }
}
