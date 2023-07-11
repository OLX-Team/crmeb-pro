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
namespace app\services\product\label;

use app\dao\other\CategoryDao;
use app\services\BaseServices;
use crmeb\services\FormBuilder as Form;
use think\facade\Route as Url;

/**
 * 商品标签分类
 * Class StoreProductLabelCateServices
 * @package app\services\product\brand
 */
class StoreProductLabelCateServices extends BaseServices
{

    /**
     * 在分类库中2
     */
    const GROUP = 2;

    /**
     * UserLabelCateServices constructor.
     * @param CategoryDao $dao
     */
    public function __construct(CategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取标签组列表（带标签）
     * @param array $where
     * @return array
     */
    public function getProductLabelCateList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $count = $this->dao->count($where);
        $list = $this->dao->getCateList($where, $page, $limit, ['*'], ['productLabel']);
        if ($list) {
            foreach ($list as &$item) {
                $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '';
            }
        }
        return compact('list', 'count');
    }

    /**
     * 获取所有的商品标签分类
     * @param int $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllProductLabelCate(int $store_id = 0)
    {
        $where = [
            'store_id' => $store_id,
            'group' => self::GROUP
        ];
        return $this->dao->getAll($where);
    }

    /**
     * 创建新增表单
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createForm()
    {
        return create_form('添加标签组', $this->form(), Url::buildUrl('/product/label_cate'), 'POST');
    }

    /**
     * 创建编辑表单
     * @param $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function editForm(int $id)
    {
        $info = $this->dao->get($id);
        return create_form('编辑标签组', $this->form($info), $this->url('/product/label_cate/' . $id), 'PUT');
    }

    /**
     * 生成表单参数
     * @param array $info
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function form($info = [])
    {
        $f[] = Form::input('name', '标签组名称', $info['name'] ?? '')->maxlength(30)->required();
        $f[] = Form::number('sort', '排序', (int)($info['sort'] ?? 0))->min(0)->min(0);
        return $f;
    }
}
