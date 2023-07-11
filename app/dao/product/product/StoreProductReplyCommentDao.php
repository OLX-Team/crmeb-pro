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

namespace app\dao\product\product;


use app\dao\BaseDao;
use app\model\product\product\StoreProductReplyComment;
use crmeb\traits\SearchDaoTrait;

/**
 * Class StoreProductReplyCommentDao
 * @package app\dao\product\product
 */
class StoreProductReplyCommentDao extends BaseDao
{

    use SearchDaoTrait;

    /**
     * @return string
     */
    protected function setModel(): string
    {
        return StoreProductReplyComment::class;
    }

    /**
     * 更新点赞
     * @param int $id
     * @return mixed
     */
    public function updatePraise(int $id)
    {
        return $this->getModel()->where('id', $id)->inc('praise', 1)->update();
    }

    /**
     * 获取评论回复条数
     * @param array $replyId
     * @return mixed
     */
    public function getReplyCommentCountList(array $replyId)
    {
        return $this->getModel()->whereIn('reply_id', $replyId)->where('pid', 0)->group('reply_id')->field(['reply_id', 'count(*) as sum'])->select()->toArray();
    }
}
