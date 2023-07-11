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

namespace app\dao\other;


use app\dao\BaseDao;
use app\model\other\Category;

/**
 * 分类
 * Class CategoryDao
 * @package app\dao\other
 */
class CategoryDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return Category::class;
    }

    public function search(array $where = [])
    {
        return parent::search($where)->when(isset($where['name']) && $where['name'], function ($query) use ($where) {
            $query->where(function ($q) use ($where) {
                $q->whereOr('id|name', 'like', '%' . $where['name'] . '%')
                    ->when(!empty($where['product_label']), function ($query) use ($where) {
                        $query->whereOr('id', 'IN', function ($l) use ($where) {
                            $l->name('store_product_label')->whereLike('id|label_name', '%' . $where['name'] . '%')->field('label_cate');
                        });
                    });
            });
        })->when(!empty($where['nowName']), function ($query) use ($where) {
            $query->where('name', $where['nowName']);
        });
    }

    /**
     * 获取分类
     * @param array $where
     * @param int $page
     * @param int $limit
     * @param array|string[] $field
     * @param array $with
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCateList(array $where, int $page = 0, int $limit = 0, array $field = ['*'], array $with = [])
    {
        $other = false;
        if (isset($where['other'])) {
            $other = true;
            unset($where['other']);
        }
        return $this->search($where)->field($field)->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->when($other, function ($query) {
            $query->where('other', '<>', '');
        })->order('sort DESC,id DESC')->select()->toArray();
    }

    /**
     * 获取全部标签分类
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAll(array $where = [], array $with = [])
    {
        return $this->search($where)->when(count($with), function ($query) use ($with) {
            $query->with($with);
        })->order('sort DESC,id DESC')->select()->toArray();
    }
}
