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

namespace app\services\product\product;


use app\dao\product\product\StoreProductUnitDao;
use app\services\BaseServices;
use crmeb\services\FormBuilder;
use think\exception\ValidateException;


/**
 * 商品单位
 * Class StoreProductUnitServices
 * @package app\services\product\product
 */
class StoreProductUnitServices extends BaseServices
{
    /**
     * StoreProductUnitServices constructor.
     * @param StoreProductUnitDao $dao
     */
    public function __construct(StoreProductUnitDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取所有商品单位列表
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllUnitList(array $where, string $field = '*')
    {
        return $this->dao->getList($where, $field);
    }

    /**
     * 获取商品单位
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUnitList(array $where, string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, $field, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }


    /**
     * 创建表单
     * @param array $cataData
     * @return array
     */
    public function unitCateForm(array $cataData = [])
    {
        $f[] = FormBuilder::input('name', '单位名称', $cataData['name'] ?? '')->maxlength(15)->required();
        $f[] = FormBuilder::number('sort', '排序', (int)($cataData['sort'] ?? 0))->min(0);
        return $f;
    }

    /**
     * 创建表单
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createForm()
    {
        return create_form('添加商品单位', $this->unitCateForm(), $this->url('/product/unit'), 'POST');
    }

    /**
     * 修改分类标签表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateForm(int $id)
    {
        $unit = $this->dao->get($id);
        if (!$unit) {
            throw new ValidateException('分类标签没有查到');
        }
        return create_form('编辑商品单位', $this->unitCateForm($unit->toArray()), $this->url('/product/unit/' . $id), 'PUT');
    }


}
