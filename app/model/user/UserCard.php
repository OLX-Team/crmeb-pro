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

namespace app\model\user;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 用户领取卡券
 * Class UserCard
 * @package app\model\user
 */
class UserCard extends BaseModel
{
    use ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'user_card';

    /**
     * 用户
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid')->field('uid,nickname,avatar')->bind([
            'nickname' => 'nickname',
            'avatar' => 'avatar'
        ]);
    }

    /**
     * uid
     * @param $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        if ($value !== '') $query->where('uid', $value);
    }

    /**
     * wechat_card_id
     * @param $query
     * @param $value
     */
    public function searchWechatCardIdAttr($query, $value)
    {
        if (in_array($value)) {
            $query->whereIn('wechat_card_id', $value);
        } else {
            if ($value !== '') $query->where('wechat_card_id', $value);
        }
    }

    /**
     * card_id
     * @param $query
     * @param $value
     */
    public function searchCardIdAttr($query, $value)
    {
        if ($value) $query->where('card_id', $value);
    }

    /**
     * 门店ID
     * @param $query
     * @param $value
     */
    public function searchStoreIdAttr($query, $value)
    {
        if ($value !== '') {
            if ($value == -1) {//所有门店
                $query->where('store_id', '>', 0);
            } else {
                $query->where('store_id', $value);
            }
        }
    }

    /**
     * 门店店员ID
     * @param $query
     * @param $value
     */
    public function searchStaffIdAttr($query, $value)
    {
        if ($value) $query->where('staff_id', $value);
    }

    /**
     * openid
     * @param $query
     * @param $value
     */
    public function searchOpenidAttr($query, $value)
    {
        if ($value) $query->where('openid', $value);
    }

    /**
     * is_submit
     * @param $query
     * @param $value
     */
    public function searchIsSubmitAttr($query, $value)
    {
        if ($value !== '') $query->where('is_submit', $value);
    }

    /**
     * is_del
     * @param $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '') $query->where('is_del', $value);
    }


}
