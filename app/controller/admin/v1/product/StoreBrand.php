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
namespace app\controller\admin\v1\product;

use app\controller\admin\AuthController;
use app\services\product\brand\StoreBrandServices;
use think\facade\App;

/**
 * 商品品牌控制器
 * Class StoreCategory
 * @package app\admin\controller\system
 */
class StoreBrand extends AuthController
{
    /**
     * @var StoreBrandServices
     */
    protected $service;

    /**
     * StoreCategory constructor.
     * @param App $app
     * @param StoreBrandServices $service
     */
    public function __construct(App $app, StoreBrandServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 品牌列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['brand_name', ''],
            ['pid', 0]
        ]);
        $where['is_del'] = 0;
        $data = $this->service->getList($where);
        return $this->success($data);
    }

    /**
     * 获取品牌cascader格式数据
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cascader_list($type = 1)
    {
        return $this->success($this->service->cascaderList($type));
    }

    /**
     * 修改状态
     * @param string $is_show
     * @param string $id
     */
    public function set_show($is_show = '', $id = '')
    {
        if ($is_show == '' || $id == '') return $this->fail('缺少参数');
        $this->service->setShow($id, $is_show);
        return $this->success($is_show == 1 ? '显示成功' : '隐藏成功');
    }

    /**
     * 保存新增品牌
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['fid', []],
            ['brand_name', ''],
            ['sort', 0],
            ['is_show', 0]
        ]);
        if (!$data['brand_name']) {
            return $this->fail('请输入品牌名称');
        }
        if (iconv_strlen($data['brand_name'], 'UTF-8') > 10) {
            return $this->fail('品牌名称过长');
        }
        $this->service->createData($data);
        return $this->success('添加品牌成功!');
    }

    /**
     * 更新品牌
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['fid', []],
            ['brand_name', ''],
            ['sort', 0],
            ['is_show', 0]
        ]);
        if (!$data['brand_name']) {
            return $this->fail('请输入品牌名称');
        }
        if (iconv_strlen($data['brand_name'], 'UTF-8') > 10) {
            return $this->fail('品牌名称过长');
        }
        $this->service->editData($id, $data);
        return $this->success('修改成功!');
    }

    /**
     * 删除品牌
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->service->del((int)$id);
        return $this->success('删除成功!');
    }
}
