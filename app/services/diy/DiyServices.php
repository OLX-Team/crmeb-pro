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

namespace app\services\diy;

use app\services\BaseServices;
use app\dao\diy\DiyDao;
use app\services\other\QrcodeServices;
use app\services\system\config\SystemGroupDataServices;
use app\services\system\config\SystemGroupServices;
use crmeb\exceptions\AdminException;
use crmeb\services\CacheService;
use crmeb\services\FileService;
use crmeb\services\SystemConfigService;

/**
 * 首页diy
 * Class DiyServices
 * @package app\services\diy
 */
class DiyServices extends BaseServices
{

    /**
     * DiyServices constructor.
     * @param DiyDao $dao
     */
    public function __construct(DiyDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取DIY列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiyList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getDiyList($where, $page, $limit, ['is_diy', 'template_name', 'id', 'title', 'name', 'type', 'add_time', 'update_time', 'status', 'cover_image']);
        $count = $this->dao->count($where + ['is_del' => 0]);
        return compact('list', 'count');
    }

    /**
     * 保存资源
     * @param int $id
     * @param array $data
     * @return int
     */
    public function saveData(int $id = 0, array $data = [])
    {
        if ($id) {
            if ($data['type'] === '') {
                unset($data['type']);
            }
            $data['update_time'] = time();
            $res = $this->dao->update($id, $data);
            if (!$res) throw new AdminException('修改失败');
        } else {
            $data['add_time'] = time();
            $data['update_time'] = time();
            $data['is_diy'] = 1;
            $res = $this->dao->save($data);
            if (!$res) throw new AdminException('保存失败');
            $id = $res->id;
        }
        event('diy.update');
        return $id;
    }

    /**
     * 删除DIY模板
     * @param int $id
     */
    public function del(int $id)
    {
        if ($id == 1) throw new AdminException('默认模板不能删除');
        $count = $this->dao->getCount(['id' => $id, 'status' => 1]);
        if ($count) throw new AdminException('该模板使用中，无法删除');
        $res = $this->dao->update($id, ['is_del' => 1]);
        if (!$res) throw new AdminException('删除失败，请稍后再试');
        event('diy.update');
    }

    /**
     * 设置模板使用
     * @param int $id
     */
    public function setStatus(int $id)
    {
        $info = $this->dao->get($id);
		if (!$info) {
			throw new AdminException('默认不存在');
		}
		if (!$info['is_show']) {
			throw new AdminException('请编辑开启页面状态后，在设置为首页');
		}
		$this->dao->update($info['type'], ['status' => 0], 'type');
		$this->dao->update($id, ['status' => 1, 'update_time' => time()]);
		event('diy.update');
		return ['status' => 1, 'msg' => '设置成功'];
    }

    /**
     * 获取页面数据
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiy(string $name)
    {
        $data = [];
        if ($name == '') {
            $info = $this->dao->getOne(['status' => 1, 'type' => 1]);
        } else {
            $info = $this->dao->getOne(['template_name' => $name]);
        }
        if ($info) {
            $info = $info->toArray();
            $data = json_decode($info['value'], true);
        }
        return $data;
    }

    /**
     * 获取diy详细数据
     * @param int $id
     * @return array|object
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getDiyInfo(int $id)
    {
        return CacheService::redisHandler('diy')->remember('diy_info_' . $id, function () use ($id) {
            $field = ['title', 'value', 'is_show', 'is_bg_color', 'color_picker', 'bg_pic', 'bg_tab_val', 'is_bg_pic', 'order_status'];
            $diyInfo = $this->dao->get($id ?: ['status' => 1, 'type' => 1, 'is_diy' => 1], $field);
            if (!$diyInfo) {
                $diyInfo = $this->dao->get(['template_name' => 'default'], $field);
            }
            if ($diyInfo) {
                $diyInfo = $diyInfo->toArray();
                $diyInfo['value'] = json_decode($diyInfo['value'], true);
                foreach ($diyInfo['value'] as &$item) {
                    if ($item['name'] == 'customerService') {
                        $item['routine_contact_type'] = SystemConfigService::get('routine_contact_type', 0);
                    }
                }
                return $diyInfo;
            } else {
                return [];
            }
        });
    }

    /**
     * 获取底部导航
     * @param string $template_name
     * @return array|mixed
     */
    public function getNavigation(string $template_name)
    {
        return CacheService::redisHandler('diy')->remember('navigation_' . $template_name, function () use ($template_name) {
            $value = $this->dao->value($template_name ? ['template_name' => $template_name] : ['status' => 1, 'type' => 1], 'value');
            if (!$value) {
                $value = $this->dao->value(['template_name' => 'default'], 'value');
            }
            $navigation = [];
            if ($value) {
                $value = json_decode($value, true);
                foreach ($value as $item) {
                    if (isset($item['name']) && strtolower($item['name']) === 'pagefoot') {
                        $navigation = $item;
                        break;
                    }
                }
            }
            return $navigation;
        });
    }

    /**
     * 获取换色/分类数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getColorChange(string $name)
    {
        return $this->dao->value(['template_name' => $name, 'type' => 3], 'value');
    }

    /**
     * 取单个diy小程序预览二维码
     * @param int $id
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRoutineCode(int $id)
    {
        $diy = $this->dao->getOne(['id' => $id, 'is_del' => 0]);
        if (!$diy) {
            throw new AdminException('数据不存在');
        }
        $type = $diy['status'] ? 8 : 6;
        /** @var QrcodeServices $QrcodeService */
        $QrcodeService = app()->make(QrcodeServices::class);
        $image = $QrcodeService->getRoutineQrcodePath($id, 0, $type, [], true);
        return $image;
    }

    /**
     * 获取个人中心数据
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMemberData()
    {
        $info = $this->dao->get(['template_name' => 'member', 'type' => 3]);
        $status = (int)$info['value'];
        $order_status = $info['order_status'] ? (int)$info['order_status'] : 1;
        $menu_status = $info['menu_status'] ? (int)$info['menu_status'] : 1;
        $service_status = $info['service_status'] ? (int)$info['service_status'] : 1;
        $my_banner_status = !!((int)$info['my_banner_status'] ?? 1);
        $color_change = (int)$this->getColorChange('color_change');
        /** @var SystemGroupDataServices $systemGroupDataServices */
        $systemGroupDataServices = app()->make(SystemGroupDataServices::class);
        /** @var SystemGroupServices $systemGroupServices */
        $systemGroupServices = app()->make(SystemGroupServices::class);
        $menus_gid = $systemGroupServices->value(['config_name' => 'routine_my_menus'], 'id');
        $banner_gid = $systemGroupServices->value(['config_name' => 'routine_my_banner'], 'id');
        $routine_my_menus = $systemGroupDataServices->getGroupDataList(['gid' => $menus_gid]);
        $routine_my_menus = $routine_my_menus['list'] ?? [];
        $url = ['/kefu/mobile_list', '/pages/store_spread/index', '/pages/admin/order_cancellation/index', '/pages/admin/order/index'];
        foreach ($routine_my_menus as &$item) {
            if (!isset($item['type']) || !$item['type']) {
                $item['type'] = in_array($item['url'], $url) ? 2 : 1;
            }
        }
        $routine_my_banner = $systemGroupDataServices->getGroupDataList(['gid' => $banner_gid]);
        $routine_my_banner = $routine_my_banner['list'] ?? [];
        return compact('status', 'order_status', 'menu_status', 'service_status', 'routine_my_menus', 'routine_my_banner', 'color_change', 'my_banner_status');
    }

    /**
     * 保存个人中心数据配置
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function memberSaveData(array $data)
    {
        /** @var SystemGroupDataServices $systemGroupDataServices */
        $systemGroupDataServices = app()->make(SystemGroupDataServices::class);
        if (!$data['status']) throw new AdminException('参数错误');
        $info = $this->dao->get(['template_name' => 'member', 'type' => 3]);
        $routine_my_banner = $data['routine_my_banner'] ?? [];
        $routine_my_menus = $data['routine_my_menus'] ?? [];
        $data['value'] = $data['status'];
        $data['my_banner_status'] = $data['my_banner_status'] && $data['routine_my_banner'] ? 1 : 0;
        unset($data['status'], $data['routine_my_banner'], $data['routine_my_menus']);
        if ($info) {
            $data['update_time'] = time();
            if (!$this->dao->update($info['id'], $data)) {
                throw new AdminException('编辑保存失败');
            }
        } else {
            throw new AdminException('个人中心模板不存在');
        }
        $systemGroupDataServices->saveAllData($routine_my_banner, 'routine_my_banner');
        $systemGroupDataServices->saveAllData($routine_my_menus, 'routine_my_menus');
        return true;
    }
}
