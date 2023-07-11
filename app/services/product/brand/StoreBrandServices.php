<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
namespace app\services\product\brand;

use app\dao\product\brand\StoreBrandDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;

/**
 * 商品品牌
 * Class StoreBrandServices
 * @package app\services\product\brand
 * @method value(array $where, string $field) 获取某个字段的值
 * @method getColumn(array $where, string $field, string $key = '') 获取某个字段数组
 */
class StoreBrandServices extends BaseServices
{
    public function __construct(StoreBrandDao $dao)
    {
        $this->dao = $dao;
    }

	/**
     * 获取品牌列表
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTreeList($where)
    {
        $list = $this->dao->getList($where, ['product']);
        if (!empty($list) && $where['brand_name'] !== '') {
            $fid = [];
            foreach ($list as $item) {
                $fid = array_merge($fid, explode(',', $item['fid']));
            }
            $pids = array_unique(array_filter($fid));
            $parentList = $this->dao->getList(['id' => $pids], ['product']);
            $list = array_merge($list, $parentList);
            foreach ($list as $key => $item) {
                $arr = $list[$key];
                unset($list[$key]);
                if (!in_array($arr, $list)) {
                    $list[] = $arr;
                }
            }
        }
        foreach ($list as &$item) {
            $item['brand_num'] = $item['product'][0]['brand_num'] ?? 0;
            $item['fid'] = $item['fid'] ? array_map('intval', explode(',', $item['fid'])) : [];
            $item['type'] = count($item['fid']) < 2 ? 1 : 0;
            //添加子品牌fid
            if ($item['type'] == 1) {
                $item['fid_son'] = $item['fid'];
                array_push($item['fid_son'], $item['id']);
            }
            unset($item['product']);
        }
        $list = get_tree_children($list);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取品牌列表
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where)
    {
        $list = $this->dao->getList($where, ['product', 'children']);
		$count = $this->dao->count($where);
        if ($list) {
			foreach ($list as &$item) {
				$item['brand_num'] = $item['product'][0]['brand_num'] ?? 0;
				$item['fid'] = $item['fid'] ? array_map('intval', explode(',', $item['fid'])) : [];
				$item['type'] = count($item['fid']) < 2 ? 1 : 0;
				//添加子品牌fid
				if ($item['type'] == 1) {
					$item['fid_son'] = $item['fid'];
					array_push($item['fid_son'], $item['id']);
				}
				if (isset($item['children']) && $item['children']) {
					$item['children'] = [];
					$item['loading'] = false;
					$item['_loading'] = false;
				} else {
					unset($item['children']);
				}
				unset($item['product']);
			}
        }
        return compact('list', 'count');
    }

    /**
     * 获取品牌cascader
     * @param string $show
     * @param int $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cascaderList($type = 1)
    {
        $where = [];
        if ($type == 1) {
            $top = true;
        } else {
            $top = false;
        }
        $menus = [];
        $where['is_del'] = 0;
		$where['is_show'] = 1;
        $list = get_tree_children($this->dao->getList($where, [], ['id as value', 'brand_name as label', 'pid']), 'children', 'value');
        if ($top) {
            $menus = [['value' => 0, 'label' => '顶级品牌']];
            foreach ($list as &$item) {
                if (isset($item['children']) && $item['children']) {
                    foreach ($item['children'] as &$val) {
                        if (isset($val['children']) && $val['children']) {
                            unset($val['children']);
                        }
                    }
                }
            }
        }
        $menus = array_merge($menus, $list);
        return $menus;
    }

    /**
     * 设置品牌状态
     * @param $id
     * @param $is_show
     */
    public function setShow(int $id, int $is_show)
    {
        $res = $this->dao->update($id, ['is_show' => $is_show]);
//        $res = $res && $this->dao->update($id, ['is_show' => $is_show], 'pid');
        if (!$res) {
            throw new AdminException('设置失败');
        }
    }

    /**
     * 保存新增数据
     * @param $data
     */
    public function createData($data)
    {
        $data['pid'] = end($data['fid']);
        if ($this->dao->getOne(['brand_name' => $data['brand_name'], 'pid' => $data['pid']])) {
            throw new AdminException('该品牌已经存在');
        }
        $data['fid'] = implode(',', $data['fid']);
        $data['add_time'] = time();
        $res = $this->dao->save($data);
        if (!$res) throw new AdminException('添加失败');
    }

    /**
     * 保存修改数据
     * @param $id
     * @param $data
     */
    public function editData($id, $data)
    {
        $cate = $this->dao->getOne(['id' => $id]);
        if (!$cate) {
            throw new AdminException('该品牌不存在');
        }
        $data['pid'] = end($data['fid']) ?? 0;
        $data['fid'] = implode(',', $data['fid']);
        $cate = $this->dao->getOne(['pid' => $data['pid'], 'brand_name' => $data['brand_name']]);
        if ($cate && $cate['id'] != $id) {
            throw new AdminException('该品牌已经存在');
        }
        $res = $this->dao->update($id, $data);
        if (!$res) throw new AdminException('修改失败');
    }

    /**
     * 删除数据
     * @param int $id
     */
    public function del(int $id)
    {
        if ($this->dao->count(['pid' => $id])) {
            throw new AdminException('请先删除子品牌!');
        }
        $res = $this->dao->delete($id);
        if (!$res) throw new AdminException('删除失败');
    }
}
