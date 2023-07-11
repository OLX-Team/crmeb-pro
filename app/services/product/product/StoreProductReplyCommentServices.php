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


use app\dao\product\product\StoreProductReplyCommentDao;
use app\services\BaseServices;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;

/**
 * Class StoreProductReplyCommentServices
 * @package app\services\product\product
 * @method updatePraise(int $id) 更新点赞
 * @method delete($id, ?string $key = null) 删除
 * @method getReplyCommentCountList(array $replyId) 获取评论回复条数
 */
class StoreProductReplyCommentServices extends BaseServices
{
    use ServicesTrait;

    /**
     * StoreProductReplyCommentServices constructor.
     * @param StoreProductReplyCommentDao $dao
     */
    public function __construct(StoreProductReplyCommentDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取评论回复列表
     * @param int $replyId
     * @param string $time
     * @param int $uid
     * @param bool $notUid
     * @return array
     */
    public function getReplCommenList(int $replyId, string $time = '', int $uid = 0, bool $notUid = true)
    {
        [$page, $limit] = $this->getPageValue();
        $where = ['reply_id' => $replyId, 'pid' => 0, 'notUid' => 0, 'create_time' => $time];
        if (false === $notUid) {
            unset($where['notUid']);
        }
        $list = $this->dao->getDataList($where, ['*'], $page, $limit, ['praise' => 'desc', 'create_time' => 'desc'], [
            'user' => function ($query) {
                $query->field('uid,avatar,nickname,is_money_level');
            },
            'children' => function ($query) use ($uid) {
                $query->with([
                    'user' => function ($query) {
                        $query->field('uid,avatar,nickname,is_money_level');
                    },
                    'productRelation' => function ($query) use ($uid) {
                        $query->where('uid', $uid)->where('type', StoreProductRelationServices::TYPE_LIKE)
                            ->where('category', StoreProductRelationServices::CATEGORY_COMMENT)
                            ->field(['uid', 'relation_id']);
                    }
                ]);
            },
            'productRelation' => function ($query) use ($uid) {
                $query->where('uid', $uid)->where('type', StoreProductRelationServices::TYPE_LIKE)
                    ->where('category', StoreProductRelationServices::CATEGORY_COMMENT)
                    ->field(['uid', 'relation_id']);
            }
        ]);
        $count = $this->dao->count($where);
        $siteLogoSquare = sys_config('site_logo_square');
        $siteName = sys_config('site_name');
        foreach ($list as &$item) {
            if (!isset($item['user']) && $item['uid'] === 0) {
                $item['user'] = ['nickname' => $siteName, 'avatar' => $siteLogoSquare];
            }
            if (isset($item['children']) && !isset($item['children']['user']) && $item['children']['uid'] === 0) {
                $item['children']['user'] = ['nickname' => $siteName, 'avatar' => $siteLogoSquare];
            }
            if ($uid) {
                $item['is_praise'] = !empty($item['productRelation']);
                if (isset($item['children'])) {
                    $item['children']['is_praise'] = !empty($item['children']['productRelation']);
                }
            } else {
                $item['is_praise'] = false;
                if (isset($item['children'])) {
                    $item['children']['is_praise'] = false;
                }
            }
        }
        return compact('list', 'count');
    }

    /**
     * 保存回复
     * @param int $uid
     * @param int $replyId
     * @param string $content
     * @return \crmeb\basic\BaseModel|\think\Model
     */
    public function saveComment(int $uid, int $replyId, string $content)
    {
        return $this->dao->save(['uid' => $uid, 'reply_id' => $replyId, 'content' => $content, 'create_time' => time()]);
    }

    /**
     * 点赞回复
     * @param int $id
     * @param int $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function commentPraise(int $id, int $uid)
    {
        $commentInfo = $this->dao->get($id, ['id', 'praise']);
        if (!$commentInfo) {
            throw new ValidateException('回复不存在');
        }
        $commentInfo->praise++;
        /** @var StoreProductRelationServices $service */
        $service = app()->make(StoreProductRelationServices::class);
        $res = $service->getUserCount($uid, $id, StoreProductRelationServices::TYPE_LIKE, StoreProductRelationServices::CATEGORY_COMMENT);
        if ($res) {
            return true;
        }
        $this->transaction(function () use ($id, $uid, $service, $commentInfo) {
            $res = $service->save([
                'uid' => $uid,
                'relation_id' => $id,
                'type' => StoreProductRelationServices::TYPE_LIKE,
                'category' => StoreProductRelationServices::CATEGORY_COMMENT,
                'add_time' => time()
            ]);
            $res = $res && $commentInfo->save();
            if (!$res) {
                throw new ValidateException('点赞失败');
            }
        });
        event('product.reply.update', [$uid]);
        return true;
    }

    /**
     * 取消回复点赞
     * @param int $id
     * @param int $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function unCommentPraise(int $id, int $uid)
    {
        $commentInfo = $this->dao->get($id, ['id', 'praise']);
        if (!$commentInfo) {
            throw new ValidateException('回复不存在');
        }
        $commentInfo->praise--;
        /** @var StoreProductRelationServices $service */
        $service = app()->make(StoreProductRelationServices::class);
        $this->transaction(function () use ($id, $uid, $service, $commentInfo) {
            $res = $service->delete([
                'uid' => $uid,
                'relation_id' => $id,
                'type' => StoreProductRelationServices::TYPE_LIKE,
                'category' => StoreProductRelationServices::CATEGORY_COMMENT
            ]);
            $res = $res && $commentInfo->save();
            if (!$res) {
                throw new ValidateException('点赞失败');
            }
        });
        event('product.reply.update', [$uid]);
        return true;
    }
}
