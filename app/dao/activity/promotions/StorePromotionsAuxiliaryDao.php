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

namespace app\dao\activity\promotions;


use app\dao\BaseDao;
use app\model\activity\promotions\StorePromotions;
use app\model\activity\promotions\StorePromotionsAuxiliary;


/**
 * 优惠活动辅助表
 */
class StorePromotionsAuxiliaryDao extends BaseDao
{

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return StorePromotionsAuxiliary::class;
    }

	public function joinModel(): string
    {
        return StorePromotions::class;
    }

 	/**
     * 关联模型
     * @param string $alias
     * @param string $join_alias
     * @return \crmeb\basic\BaseModel
     */
    public function getJoinModel(string $alias = 'a', string $join_alias = 'p', $join = 'left')
    {
        $this->alias = $alias;
        $this->joinAlis = $join_alias;
        /** @var StorePromotions $storePromotions */
        $storePromotions = app()->make($this->joinModel());
        $table = $storePromotions->getName();
        return parent::getModel()->alias($alias)->join($table . ' ' . $join_alias, $alias . '.promotions_id = ' . $join_alias . '.id', $join);
    }

	/**
 	* 获取ids
	* @param array $product_id
	* @param int $product_partake_type
	* @param string $field
	* @return array
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	 */
	public function getProductsPromotionsIds(array $product_id, int $product_partake_type = 2, string $field = '')
	{
		$time = time();
		return $this->getJoinModel()->field($field)
			->where($this->joinAlis . '.type', 1)
			->where($this->joinAlis . '.status', 1)
			->where($this->joinAlis . '.is_del', 0)
			->where($this->joinAlis . '.start_time', '<=', $time)
			->where($this->joinAlis . '.stop_time', '>=', $time)
			->where($this->alias . '.type', 1)
			->when(in_array($product_partake_type, [2, 3]), function ($query) use ($product_partake_type, $product_id) {
				if ($product_partake_type == 2) {
           			 $query->where(function ($q) use ($product_id) {
						$q->whereOr( $this->joinAlis.'.product_partake_type', 1)
						->whereOr(function ($w) use ($product_id) {
							$w->where($this->joinAlis.'.product_partake_type', 2)->where(function ($p) use ($product_id) {
									if (is_array($product_id)) {
										$p->whereIn($this->alias.'.product_id', $product_id);
									} else {
										$p->where($this->alias.'.product_id', $product_id);
									}
								});
						})->whereOr(function ($e) use ($product_id) {
							$e->where($this->joinAlis.'.product_partake_type', 3)->where($this->alias.'.is_all', 1)->where(function ($p) use ($product_id) {
									if (is_array($product_id)) {
										$p->whereNotIn($this->alias.'.product_id', $product_id);
									} else {
										$p->where($this->alias.'.product_id', '<>', $product_id);
									}
								});
							});
					});
				} else {
					$query->where($this->alias.'.product_partake_type', 3)->where($this->alias.'.is_all', 1)->where(function ($p) use ($product_id){
						if(is_array($product_id)){
							$p->whereNotIn($this->alias.'.product_id', $product_id);
						}else{
							$p->where($this->alias.'.product_id', $product_id);
						}
					});
				}
			})->select()->toArray();
	}
}
