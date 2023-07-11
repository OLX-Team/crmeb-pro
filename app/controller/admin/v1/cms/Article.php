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
namespace app\controller\admin\v1\cms;

use app\controller\admin\AuthController;
use app\services\article\ArticleServices;
use think\facade\App;

/**
 * 文章管理
 * Class Article
 * @package app\controller\admin\v1\cms
 */
class Article extends AuthController
{
    /**
     * @var ArticleServices
     */
    protected $service;

    /**
     * Article constructor.
     * @param App $app
     * @param ArticleServices $service
     */
    public function __construct(App $app, ArticleServices $service)
    {
        parent::__construct($app);
        $this->service = $service;
    }

    /**
     * 获取列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['title', ''],
            ['pid', 0, '', 'cid'],
        ]);
        $data = $this->service->getList($where);
        return $this->success($data);
    }

    /**
     * 保存文章数据
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['cid', ''],
            ['title', ''],
            ['author', ''],
            ['image_input', ''],
            ['synopsis', 0],
            ['share_title', ''],
            ['share_synopsis', ''],
            ['sort', 0],
            ['url', ''],
            ['is_banner', 0],
            ['is_hot', 0],
            ['status', 1]
        ]);
        $data['content'] = $this->request->param('content','');
        $this->service->save($data);
        return $this->success('添加成功!');
    }

    /**
     * 获取单个文章数据
     * @param $id
     * @return mixed
     */
    public function read($id)
    {
        if ($id) {
            $info = $this->service->read($id);
            return $this->success($info);
        } else {
            return $this->fail('参数错误');
        }

    }

    /**
     * 删除文章
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function delete($id)
    {
        if ($id) {
            $this->service->del($id);
            return $this->success('删除成功!');
        } else {
            return $this->fail('参数错误');
        }
    }

    /**
     * 文章关联商品
     * @param int $id
     * @return mixed
     */
    public function relation($id)
    {
        if (!$id) return $this->fail('缺少参数');
        list($product_id) = $this->request->postMore([
            ['product_id', 0]
        ], true);
        $res = $this->service->bindProduct($id, $product_id);
        if ($res) {
            return $this->success('关联成功');
        } else {
            return $this->fail('关联失败');
        }
    }

    /**
     * 取消商品关联
     * @param int $id
     * @return mixed
     */
    public function unrelation($id)
    {
        if (!$id) return $this->fail('缺少参数');
        $res = $this->service->bindProduct($id);
        if ($res) {
            return $this->success('取消关联成功！');
        } else {
            return $this->fail('取消失败');
        }
    }
}
