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
use app\services\product\product\StoreProductReplyCommentServices;
use app\services\product\product\StoreProductReplyServices;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\App;

/**
 * 评论管理 控制器
 * Class StoreProductReply
 * @package app\admin\controller\store
 */
class StoreProductReply extends AuthController
{
    /**
     * StoreProductReply constructor.
     * @param App $app
     * @param StoreProductReplyServices $service
     */
    public function __construct(App $app, StoreProductReplyServices $service)
    {
        parent::__construct($app);
        $this->services = $service;
    }

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['is_reply', ''],
            ['store_name', ''],
            ['account', ''],
            ['data', ''],
            ['product_id', 0]
        ]);
        $list = $this->services->sysPage($where);
        return $this->success($list);
    }

    /**
     * 删除评论
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        $this->services->del($id);
        return $this->success('删除成功!');
    }

    /**
     * 管理员回复评论
     * @param $id
     * @return mixed
     */
    public function set_reply($id)
    {
        [$content] = $this->request->postMore([
            ['content', '']
        ], true);
        $this->services->setReply($id, $content);
        return $this->success('回复成功!');
    }

    /**
     * 获取管理员评论
     * @param StoreProductReplyCommentServices $services
     * @param $id
     * @return mixed
     */
    public function getReply(StoreProductReplyCommentServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $where = ['reply_id' => $id, 'uid' => 0, 'store_id' => 0];
        $commentInfo = $services->get($where);
        if ($commentInfo) {
            return $this->success($commentInfo->toArray());
        } else {
            return $this->success(['content' => '']);
        }
    }

    /**
     * 创建虚拟评论表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function fictitious_reply()
    {
        list($product_id) = $this->request->postMore([
            ['product_id', 0],
        ], true);
        return $this->success($this->services->createForm($product_id));
    }

    /**
     * 保存虚拟评论
     * @return mixed
     */
    public function save_fictitious_reply()
    {
        $data = $this->request->postMore([
            ['image', ''],
            ['nickname', ''],
            ['avatar', ''],
            ['comment', ''],
            ['pics', []],
            ['product_score', 0],
            ['service_score', 0],
            ['product_id', 0],
            ['add_time', 0]
        ]);
        if (!$data['product_id']) {
            $data['product_id'] = $data['image']['product_id'] ?? '';
        }
        $this->validate(['product_id' => $data['product_id'], 'nickname' => $data['nickname'], 'avatar' => $data['avatar'], 'comment' => $data['comment'], 'product_score' => $data['product_score'], 'service_score' => $data['service_score']], \app\validate\admin\product\StoreProductReplyValidate::class, 'save');
        $this->services->saveReply($data);
        return $this->success('添加成功!');
    }

    /**
     * 获取评论回复列表
     * @param StoreProductReplyCommentServices $services
     * @param $id
     * @return mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getComment(StoreProductReplyCommentServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $time = $this->request->get('time', '');
        return $this->success($services->getReplCommenList((int)$id, $time));
    }

    /**
     * 管理员二级回复
     * @param StoreProductReplyCommentServices $services
     * @param $replyId
     * @param $id
     * @return mixed
     */
    public function saveComment(StoreProductReplyCommentServices $services, $replyId, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }

        $data = $this->request->postMore([
            ['content', ''],
        ]);
        if (!$data['content']) {
            return app('json')->fail('缺少回复内容');
        }
        $data['reply_id'] = $replyId;
        $data['create_time'] = time();
        $data['uid'] = 0;
        $data['pid'] = $id;
        $where = ['uid' => 0, 'pid' => $id, 'reply_id' => $replyId];
        if ($services->count($where)) {
            $services->update($where, ['content' => $data['content'], 'update_time' => time()]);
        } else {
            $services->save($data);
        }
        return $this->success('保存成功');
    }

    /**
     * 删除用户回复
     * @param StoreProductReplyCommentServices $services
     * @param $id
     * @return mixed
     */
    public function deleteComment(StoreProductReplyCommentServices $services, $id)
    {
        if (!$id) {
            return $this->fail('缺少参数');
        }
        $services->transaction(function () use ($id, $services) {
            $services->delete($id);
            $services->delete(['pid' => $id]);
        });
        return $this->success('删除成功');
    }
}
