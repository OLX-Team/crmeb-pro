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

namespace app\dao\product\brand;

use app\dao\BaseDao;
use app\model\product\brand\StoreBrand;

/**
 * Class StoreCategoryDao
 * @package app\dao\product\product
 */
class StoreBrandDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return StoreBrand::class;
    }

    /**
 	* 获取品牌列表
	* @param array $where
	* @param array $with
	* @param array $field
	* @param int $page
	* @param int $limit
	* @return array
	* @throws \think\db\exception\DataNotFoundException
	* @throws \think\db\exception\DbException
	* @throws \think\db\exception\ModelNotFoundException
	 */
    public function getList(array $where, array $with = [], array $field = ['*'], int $page = 0, int $limit = 0)
    {
        return $this->search($where)->field($field)
            ->when(in_array('product', $with), function ($query) use ($with) {
				$with = array_merge(array_diff($with, ['product']), ['product' => function ($que) {
                    $que->field(['brand_id', "count(`brand_id`) as brand_num"])->group('brand_id');
                }]);
                $query->with($with);
            })->when($page && $limit, function ($query) use($page, $limit) {
				$query->page($page, $limit);
			})->order('sort desc,id desc')->select()->toArray();
    }


}
