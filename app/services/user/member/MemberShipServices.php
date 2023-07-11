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

namespace app\services\user\member;

use app\dao\user\member\MemberShipDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use think\exception\ValidateException;

/**
 * Class MemberShipServices
 * @package app\services\user\member
 * @method value(array $where, ?string $field)
 */
class MemberShipServices extends BaseServices
{

    public function __construct(MemberShipDao $memberShipDao)
    {
        $this->dao = $memberShipDao;
    }

    /**
     * 获取单个付费会员信息
     * @param int $id
     * @param string $field
     * @return array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMemberInfo(int $id, string $field = '*')
    {
        $member = $this->dao->getOne(['id' => $id, 'is_del' => 0], $field);
        if (!$member) {
            throw new ValidateException('该会员类型不存在或已删除');
        }
        return $member;
    }

    /**
     * 后台获取会员类型
     * @param array $where
     * @return array
     */
    public function getSearchList(array $where = [])
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getSearchList($where, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');

    }

    /**
     * 获取会员卡api接口
     * @return mixed
     */
    public function getApiList(array $where)
    {
        return $this->dao->getApiList($where);
    }

    /**
     * 卡类型编辑保存
     * @param int $id
     * @param array $data
     */
    public function save(int $id, array $data)
    {
        if (!$data['type']) throw new AdminException("会员卡类型缺失");
        if ($data['type'] == "ever") {
            $data['vip_day'] = -1;
        } else {
            if (!$data['vip_day']) throw new AdminException("请填写体验天数");
            if ($data['vip_day'] < 0) throw new AdminException("体验天数不能为负数");
        }
        if ($data['type'] == "free") {
//            $data['price'] = 0.00;
            $data['pre_price'] = 0.00;
        } else {
            if ($data['pre_price'] == 0 || $data['price'] == 0) throw new AdminException("请填写价格");
        }
        if ($data['pre_price'] < 0 || $data['price'] < 0) throw new AdminException("价格不能为负数");
        if ($data['pre_price'] > $data['price']) throw new AdminException("优惠价不能大于原价");
        if ($id) {
            return $this->dao->update($id, $data);
        } else {
            return $this->dao->save($data);
        }
    }

    /**
     * 获取卡会员天数
     * @param array $where
     * @return mixed
     */
    public function getVipDay(array $where)
    {
        return $this->dao->value($where, 'vip_day');
    }

    /**
     * 修改会员类型状态
     * @param $id
     * @param $is_del
     * @return bool
     */
    public function setStatus($id, $is_del)
    {
        $res = $this->dao->update($id, ['is_del' => $is_del]);
        if ($res) return true;
        return false;
    }

    /**
     * 查询会员类型select
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getShipSelect()
    {
        $menus = [];
        foreach ($this->dao->getSearchList(['is_del' => 0], 0, 0, ['id', 'title']) as $menu) {
            $menus[] = ['value' => $menu['id'], 'label' => $menu['title']];
        }
        return $menus;
    }
}
