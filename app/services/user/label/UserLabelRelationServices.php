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
declare (strict_types=1);

namespace app\services\user\label;

use app\services\BaseServices;
use app\dao\user\label\UserLabelRelationDao;
use crmeb\exceptions\AdminException;

/**
 * 用户关联标签
 * Class UserLabelRelationServices
 * @package app\services\user\label
 * @method getColumn(array $where, string $field, string $key = '') 获取某个字段数组
 * @method saveAll(array $data) 批量保存数据
 */
class UserLabelRelationServices extends BaseServices
{

    /**
     * UserLabelRelationServices constructor.
     * @param UserLabelRelationDao $dao
     */
    public function __construct(UserLabelRelationDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取某个用户标签ids
     * @param int $uid
     * @param int $store_id
     * @return array
     */
    public function getUserLabels(int $uid, int $store_id = 0)
    {
        return $this->dao->getColumn(['uid' => $uid, 'store_id' => $store_id], 'label_id', '');
    }

    /**
     * 用户设置标签
     * @param int $uid
     * @param array $labels
     */
    public function setUserLable($uids, array $labels, int $store_id = 0, bool $type = false)
    {
        if (!$labels) {
            return true;
        }
        if (!is_array($uids)) {
            $uids = [$uids];
        }
        //增加标签
        $data = [];
        foreach ($uids as $uid) {
            //用户已经存在的标签
            $user_label_ids = $this->dao->getColumn([
                ['uid', '=', $uid],
                ['store_id', '=', $store_id]
            ], 'label_id');
            //删除未选中标签
            if ($type) {
                $del_labels = array_diff($user_label_ids, $labels);
                $this->dao->delete([
                    ['uid', '=', $uid],
                    ['label_id', 'in', $del_labels],
                    ['store_id', '=', $store_id]
                ]);
            }
            $add_labels = array_diff($labels, $user_label_ids);
            if ($add_labels) {
                foreach ($add_labels as $label) {
                    $label = (int)$label;
                    if (!$label) continue;
                    $data[] = ['uid' => $uid, 'label_id' => $label, 'store_id' => $store_id];
                }
            }
        }
        if ($data) {
            if (!$this->dao->saveAll($data))
                throw new AdminException('设置标签失败');
        }

        return true;
    }

    /**
     * 取消用户标签
     * @param int $uid
     * @param array $labels
     * @return mixed
     */
    public function unUserLabel(int $uid, array $labels, int $store_id = 0)
    {
        if (!count($labels)) {
            return true;
        }
        $this->dao->delete([
            ['uid', '=', $uid],
            ['label_id', 'in', $labels],
            ['store_id', '=', $store_id]
        ]);
        return true;
    }

    /**
     * 获取用户标签
     * @param array $uids
     * @param int $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLabelList(array $uids, int $store_id = 0)
    {
        return $this->dao->getLabelList($uids, $store_id);
    }
}
