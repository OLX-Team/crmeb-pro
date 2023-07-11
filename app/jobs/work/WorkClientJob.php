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

namespace app\jobs\work;


use app\services\work\WorkClientServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 同步客户
 * Class WorkClientJob
 * @package app\jobs\work
 */
class WorkClientJob extends BaseJobs
{

    use QueueTrait;

    /**
     * @param $page
     * @param $cursor
     * @return bool
     */
    public function authClient($page, $cursor)
    {
        /** @var WorkClientServices $make */
        $make = app()->make(WorkClientServices::class);
        $make->authGetExternalcontact($page, $cursor);
        return true;
    }

    /**
     * 同步客户信息
     * @param $corpId
     * @param $externalUserID
     * @param $userId
     */
    public function saveClientInfo($corpId, $externalUserID, $userId)
    {
        /** @var WorkClientServices $make */
        $make = app()->make(WorkClientServices::class);
        $make->saveOrUpdateClient($corpId, $externalUserID, $userId);
        return true;
    }

    /**
     * 设置客户标签
     * @param $markTag
     * @return bool
     */
    public function setLabel($markTag)
    {
        /** @var WorkClientServices $make */
        $make = app()->make(WorkClientServices::class);
        $make->setClientMarkTag($markTag);
        return true;
    }
}
