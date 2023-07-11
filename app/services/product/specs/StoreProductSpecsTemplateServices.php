<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
namespace app\services\product\specs;

use app\dao\other\CategoryDao;
use app\services\BaseServices;

/**
 * 商品参数模版
 * Class StoreProductSpecsTemplateServices
 * @package app\services\product\specs
 * @method getCateList(array $where, int $page = 0, int $limit = 0, array $field = ['*'], array $with = []) 获取分类
 */
class StoreProductSpecsTemplateServices extends BaseServices
{

    /**
     * 在分类库中3
     */
    const GROUP = 3;

    /**
     * UserLabelCateServices constructor.
     * @param CategoryDao $dao
     */
    public function __construct(CategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取所有参数模版
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProductSpecsTemplateList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $where = array_merge($where, ['type' => 1, 'store_id' => 0, 'group' => 3]);
        $count = $this->dao->count($where);
        $list = $this->dao->getCateList($where, $page, $limit, ['*']);
        if ($list) {
            foreach ($list as &$item) {
                $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
            }
        }
        return compact('list', 'count');
    }


}
