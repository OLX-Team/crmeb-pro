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
namespace app\model\store;

use app\model\user\User;
use app\model\work\WorkMember;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 店员模型
 * Class SystemStoreStaff
 * @package app\model\store
 */
class SystemStoreStaff extends BaseModel
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
    protected $name = 'system_store_staff';
    /**
     * @var string[]
     */
    protected $hidden = [
        'last_ip'
    ];

    /**
     * @return \think\model\relation\HasOne
     */
    public function workMember()
    {
        return $this->hasOne(WorkMember::class, 'uid', 'uid');
    }

    /**
     * 规则修改器
     * @param $value
     * @return string
     */
    public function setRolesAttr($value)
    {
        if ($value) {
            return is_array($value) ? implode(',', $value) : $value;
        }
        return '';
    }

    /**
     * @param $value
     * @return array|false|string[]
     */
    public function getRolesAttr($value)
    {
        if ($value) {
            return is_string($value) ? explode(',', $value) : $value;
        }
        return [];
    }

    /**
     * user用户表一对一关联
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid', false)->field(['uid', 'nickname', 'delete_time'])->bind([
            'nickname' => 'nickname',
            'delete_time' => 'delete_time',
        ]);
    }

    /**
     * 门店表一对一关联
     * @return \think\model\relation\HasOne
     */
    public function store()
    {
        return $this->hasOne(SystemStore::class, 'id', 'store_id')->field(['id', 'name'])->bind([
            'name' => 'name'
        ]);
    }

    /**
     * 时间戳获取器转日期
     * @param $value
     * @return false|string
     */
    public static function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public function searchAccountAttr($query, $value)
    {
        if ($value) $query->where('account', $value);
    }

    public function searchPhoneAttr($query, $value)
    {
        if ($value) $query->where('phone', $value);
    }

    /**
     * 权限规格状态搜索器
     * @param Model $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value != '') {
            $query->where('status', $value);
        }
    }

    /**
     * 权限等级搜索器
     * @param Model $query
     * @param $value
     */
    public function searchLevelAttr($query, $value)
    {
        if (is_array($value)) {
            $query->where('level', $value[0], $value[1]);
        } else {
            $query->where('level', $value);
        }
    }

    /**
     * 是否有核销权限搜索器
     * @param Model $query
     * @param $value 用户uid
     */
    public function searchIsStatusAttr($query, $value)
    {
        $query->where(['uid' => $value, 'status' => 1, 'verify_status' => 1]);
    }

    /**
     * uid搜索器
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        $query->where('uid', $value);
    }

    /**
     * 门店id搜索器
     * @param Model $query
     * @param $value
     */
    public function searchStoreIdAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('store_id', $value);
        }
    }

    /**
     * 角色
     * @param $query
     * @param $value
     */
    public function searchRolesAttr($query, $value)
    {
        if ($value) {
            $query->where('find_in_set(' . $value . ',`roles`)');
        }
    }

    /**
     * 是否是管理员
     * @param $query
     * @param $value
     */
    public function searchIsAdminAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_admin', $value);
        }
    }

    /**
     * 是否删除
     * @param $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_del', $value);
        }
    }

    /**
     * 是否接收通知
     * @param $query
     * @param $value
     */
    public function searchNotifyAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('notify', $value);
        }
    }
}
