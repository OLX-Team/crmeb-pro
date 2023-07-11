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


use app\dao\product\label\StoreProductLabelDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder;
use think\facade\Route as Url;

/**
 * 商品标签
 * Class StoreProductLabelServices
 * @package app\services\product\label
 */
class StoreProductLabelServices extends BaseServices
{
    /**
     * StoreProductLabelServices constructor.
     * @param StoreProductLabelDao $dao
     */
    public function __construct(StoreProductLabelDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取商品标签表单
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLabelForm()
    {
        /** @var StoreProductLabelCateServices $service */
        $service = app()->make(StoreProductLabelCateServices::class);
        $options = $service->getAllProductLabelCate();
        $data = [];
        foreach ($options as $option) {
            $data[] = ['label' => $option['name'], 'value' => $option['id']];
        }
        $rule = [
            FormBuilder::select('label_cate', '标签分组')->options($data),
            FormBuilder::input('label_name', '标签名称')->maxlength(20),
        ];
        return create_form('添加商品标签', $rule, Url::buildUrl('/product/label/0'), 'POST');
    }
}
