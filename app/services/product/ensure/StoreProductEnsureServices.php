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
namespace app\services\product\ensure;

use app\dao\product\ensure\StoreProductEnsureDao;
use app\services\BaseServices;
use app\services\product\product\StoreProductServices;
use crmeb\services\FormBuilder as Form;
use FormBuilder\Factory\Iview;
use think\facade\Route as Url;

/**
 * 商品保障服务
 * Class StoreProductEnsureServices
 * @package app\services\product\ensure
 */
class StoreProductEnsureServices extends BaseServices
{

    /**
     * StoreProductEnsureServices constructor.
     * @param StoreProductEnsureDao $dao
     */
    public function __construct(StoreProductEnsureDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取保障服务列表（带标签）
     * @param array $where
     * @return array
     */
    public function getEnsureList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, '*', $page, $limit);
        if ($list) {
            /** @var StoreProductServices $storeProductServices */
            $storeProductServices = app()->make(StoreProductServices::class);
            foreach ($list as &$item) {
                $item['product_count'] = $storeProductServices->getUseEnsureCount((int)$item['id']);
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }


    /**
     * 创建新增表单
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createForm()
    {
        return create_form('添加保障服务', $this->form(), Url::buildUrl('/product/ensure'), 'POST');
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
        return create_form('编辑保障服务', $this->form($info), $this->url('/product/ensure/' . $id), 'PUT');
    }

    /**
     * 生成表单参数
     * @param array $info
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function form($info = [])
    {
        $f[] = Form::input('name', '保障服务条款', $info['name'] ?? '')->maxlength(100)->required();
        $f[] = Form::textarea('desc', '内容描述', $info['desc'] ?? '')->required();
        $f[] = Form::frameImage('image', '图标(建议尺寸：100px*100px)', Url::buildUrl('admin/widget.images/index', array('fodder' => 'image')), $info['image'] ?? '')->icon('ios-add')->width('960px')->appendValidate(Iview::validateStr()->message('请选择图标(建议尺寸：100px*100px)')->required())->height('505px')->modal(['footer-hide' => true]);
        $f[] = Form::number('sort', '排序', (int)($info['sort'] ?? 0))->min(0)->min(0);
        return $f;
    }
}
