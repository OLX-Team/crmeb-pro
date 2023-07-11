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
namespace app\controller\admin\v1\application\wechat;

use app\controller\admin\AuthController;
use crmeb\services\wechat\Messages;
use crmeb\services\wechat\OfficialAccount;
use think\facade\App;
use think\facade\Log;
use app\services\wechat\WechatNewsCategoryServices;
use app\services\article\ArticleServices;

/**
 * 图文信息
 * Class WechatNewsCategory
 * @package app\admin\controller\wechat
 *
 */
class WechatNewsCategory extends AuthController
{
    /**
     * 构造方法
     * Menus constructor.
     * @param App $app
     * @param WechatNewsCategoryServices $services
     */
    public function __construct(App $app, WechatNewsCategoryServices $services)
    {
        parent::__construct($app);
        $this->services = $services;
    }

    /**
     * 图文消息列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['page', 1],
            ['limit', 20],
            ['cate_name', '']
        ]);
        $list = $this->services->getAll($where);
        return $this->success($list);
    }

    /**
     * 图文详情
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $info = $this->services->get($id);
        /** @var ArticleServices $services */
        $services = app()->make(ArticleServices::class);
        $new = $services->articlesList($info['new_id']);
        if ($new) $new = $new->toArray();
        $info['new'] = $new;
        return $this->success(compact('info'));
    }

    /**
     * 删除图文
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$this->services->delete($id))
            return $this->fail('删除失败,请稍候再试!');
        else
            return $this->success('删除成功!');
    }

    /**
     * 新增或编辑保存
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['list', []],
            ['id', 0]
        ]);
        try {
            $id = [];
            $countList = count($data['list']);
            if (!$countList) return $this->fail('请添加图文');
            /** @var ArticleServices $services */
            $services = app()->make(ArticleServices::class);
            foreach ($data['list'] as $k => $v) {
                if ($v['title'] == '') return $this->fail('标题不能为空');
                if ($v['author'] == '') return $this->fail('作者不能为空');
                if ($v['content'] == '') return $this->fail('正文不能为空');
                if ($v['synopsis'] == '') return $this->fail('摘要不能为空');
                $v['status'] = 1;
                $v['add_time'] = time();
                if ($v['id']) {
                    $idC = $v['id'];
                    $services->save($v);
                    unset($v['id']);
                    $data['list'][$k]['id'] = $idC;
                    $id[] = $idC;
                } else {
                    $res = $services->save($v);
                    unset($v['id']);
                    $id[] = $res['id'];
                    $data['list'][$k]['id'] = $res['id'];
                }
            }
            $countId = count($id);
            if ($countId != $countList) {
                if ($data['id']) return $this->fail('修改失败');
                else return $this->fail('添加失败');
            } else {
                $newsCategory['cate_name'] = $data['list'][0]['title'];
                $newsCategory['new_id'] = implode(',', $id);
                $newsCategory['sort'] = 0;
                $newsCategory['add_time'] = time();
                $newsCategory['status'] = 1;
                if ($data['id']) {
                    $this->services->update($data['id'], $newsCategory, 'id');
                    return $this->success('修改成功');
                } else {
                    $this->services->save($newsCategory);
                    return $this->success('添加成功');
                }
            }
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * 发送消息
     * @param int $id
     * @param string $wechat
     * $wechat  不为空  发消息  /  空 群发消息
     */
    public function push()
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['user_ids', '']
        ]);
        if (!$data['id']) return $this->fail('参数错误');
        $list = $this->services->getWechatNewsItem($data['id']);
        $wechatNews = [];
        if ($list) {
            if (is_array($list['new']) && count($list['new'])) {
                $wechatNews['title'] = $list['new'][0]['title'];
                $wechatNews['image_input'] = $list['new'][0]['image_input'];
                $wechatNews['date'] = date('m月d日', time());
                $wechatNews['description'] = $list['new'][0]['synopsis'];
                $wechatNews['id'] = $list['new'][0]['id'];
            }
        }
        if ($data['user_ids'] != '') {//客服消息
            $wechatNews = $this->services->wechatPush($wechatNews);
            $message = Messages::newsMessage($wechatNews);
            $errorLog = [];//发送失败的用户
            $user = $this->services->getWechatUser($data['user_ids'], 'nickname,subscribe,openid', 'uid');
            if ($user) {
                foreach ($user as $v) {
                    if ($v['subscribe'] && $v['openid']) {
                        try {
                            OfficialAccount::staffService()->message($message)->to($v['openid'])->send();
                        } catch (\Exception $e) {
                            Log::error($v['nickname'] . '发送失败，原因:' . $e->getMessage());
                            $errorLog[] = $v['nickname'] . '发送失败';
                        }
                    } else {
                        $errorLog[] = $v['nickname'] . '没有关注发送失败(不是微信公众号用户)';
                    }
                }
            } else return $this->fail('发送失败，参数不正确');
            if (!count($errorLog)) return $this->success('全部发送成功');
            else return $this->success(implode(',', $errorLog) . '，剩余的发送成功');
        }

    }

    /**
     * 发送消息图文列表
     * @return mixed
     */
    public function send_news()
    {
        $where = $this->request->getMore([
            ['cate_name', ''],
            ['page', 1],
            ['limit', 10]
        ]);
        return $this->success($this->services->list($where));
    }

}
