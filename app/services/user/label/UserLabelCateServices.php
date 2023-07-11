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

namespace app\services\user\label;


use app\dao\other\CategoryDao;
use app\services\BaseServices;
use crmeb\services\CacheService;
use crmeb\services\FormBuilder;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;
use think\Model;

/**
 * 用户标签分类
 * Class UserLabelCateServices
 * @package app\services\user\label
 * @method delete($id, ?string $key = null) 删除
 * @method update($id, array $data, ?string $key = null) 更新数据
 * @method save(array $data) 保存数据
 * @method array|Model|null get($id, ?array $field = [], ?array $with = []) 获取一条数据
 * @method getAll(array $with = []) 获取全部标签分类
 */
class UserLabelCateServices extends BaseServices
{

    use ServicesTrait;

    /**
     * 标签分类缓存
     * @var string
     */
    protected $cacheName = 'label_list_all';

    /**
     * UserLabelCateServices constructor.
     * @param CategoryDao $dao
     */
    public function __construct(CategoryDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取标签分类
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLabelList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getCateList($where, $page, $limit);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取tree数据
     * @param array $where
     * @param array|string[] $field
     * @param array $with
     * @return array
     */
    public function getLabelTree(array $where, array $field = ['*'], array $with = [])
    {
        return $this->dao->getCateList($where, 0, 0, $field, $with);
    }

    /**
     * 删除分类缓存
     * @param int $type
     * @param int $store_id
     * @return bool
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteCateCache(int $type = 1, int $store_id = 0)
    {
        $key = $this->cacheName . '_' . $type . '_' . $store_id;
        return CacheService::delete($key);
    }

    /**
     * 获取标签全部分类
     * @param int $type
     * @param int $store_id
     * @return bool|mixed|null
     */
    public function getLabelCateAll(int $type = 1, int $store_id = 0)
    {
        $key = $this->cacheName . '_' . $type . '_' . $store_id;
        return CacheService::get($key, function () use ($type, $store_id) {
            return $this->dao->getCateList(['type' => $type, 'owner_id' => 0, 'store_id' => $store_id, 'group' => 0]);
        });
    }

    /**
     * 标签分类表单
     * @param array $cataData
     * @return mixed
     */
    public function labelCateForm(array $cataData = [])
    {
        $f[] = FormBuilder::input('name', '分类名称', $cataData['name'] ?? '')->maxlength(20)->required();
        $f[] = FormBuilder::number('sort', '排序', (int)($cataData['sort'] ?? 0))->min(0);
        return $f;
    }

    /**
     * 创建表单
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createForm()
    {
        return create_form('添加标签分类', $this->labelCateForm(), $this->url('/user/user_label_cate'), 'POST');
    }

    /**
     * 修改分类标签表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function updateForm(int $id)
    {
        $labelCate = $this->dao->get($id);
        if (!$labelCate) {
            throw new ValidateException('分类标签没有查到');
        }
        return create_form('编辑标签分类', $this->labelCateForm($labelCate->toArray()), $this->url('user/user_label_cate/' . $id), 'PUT');
    }

    /**
     * 用户标签列表
     * @param int $uid
     * @param int $type
     * @param int $store_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserLabel(int $uid = 0, int $type = 1, int $store_id = 0)
    {
        $list = $this->dao->getAll(['group' => 0, 'type' => $type, 'store_id' => $store_id], ['label']);
        $labelIds = [];
        if ($uid) {
            /** @var UserLabelRelationServices $services */
            $services = app()->make(UserLabelRelationServices::class);
            $labelIds = $services->getUserLabels($uid, $store_id);
        }
        foreach ($list as $key => &$item) {
            if (is_array($item['label'])) {
                if (!$item['label']) {
                    unset($list[$key]);
                    continue;
                }
                foreach ($item['label'] as &$value) {
                    if (in_array($value['id'], $labelIds)) {
                        $value['disabled'] = true;
                    } else {
                        $value['disabled'] = false;
                    }
                }
            }

        }
        return array_merge($list);
    }
}
