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

use app\jobs\user\UserLabelJob;
use app\services\BaseServices;
use app\dao\user\label\UserLabelDao;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use crmeb\services\wechat\Work;
use FormBuilder\Factory\Iview;
use think\exception\ValidateException;
use think\facade\Route as Url;

/**
 * 用户标签
 * Class UserLabelServices
 * @package app\services\user\label
 * Class UserLabelServices
 * @package app\services\user
 * @method getColumn(array $where, string $field, string $key = '') 获取某个字段数组
 */
class UserLabelServices extends BaseServices
{

    /**
     * UserLabelServices constructor.
     * @param UserLabelDao $dao
     */
    public function __construct(UserLabelDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取某一本标签
     * @param $id
     * @return array|\think\Model|null
     */
    public function getLable($id)
    {
        return $this->dao->get($id);
    }

    /**
     * 获取所有用户标签
     * @param array $where
     * @param array|string[] $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLabelList(array $where = [], array $field = ['*'])
    {
        return $this->dao->getList(0, 0, $where, $field);
    }

    /**
     * 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($page, $limit, $where);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 添加修改标签表单
     * @param int $id
     * @param int $type
     * @param int $store_id
     * @param int $label_cate
     * @return mixed
     */
    public function add(int $id, int $type = 1, int $store_id = 0, int $label_cate = 0)
    {
        $label = $this->getLable($id);
        $field = array();
        /** @var UserLabelCateServices $service */
        $service = app()->make(UserLabelCateServices::class);
        $options = [];
        foreach ($service->getLabelCateAll($type, $store_id) as $item) {
            $options[] = ['value' => $item['id'], 'label' => $item['name']];;
        }
        if (!$label) {
            $title = '添加标签';
            $field[] = Form::select('label_cate', '标签分类', $label_cate)->setOptions($options)->filterable(true)->appendValidate(Iview::validateInt()->message('请选择标签分类')->required());
            $field[] = Form::input('label_name', '标签名称', '')->maxlength(20)->required('请填写标签名称');
        } else {
            $title = '修改标签';
            $field[] = Form::select('label_cate', '分类', (int)$label->getData('label_cate'))->setOptions($options)->filterable(true)->appendValidate(Iview::validateInt()->message('请选择标签分类')->required());
            $field[] = Form::hidden('id', $label->getData('id'));
            $field[] = Form::input('label_name', '标签名称', $label->getData('label_name'))->maxlength(20)->required('请填写标签名称');
        }
        return create_form($title, $field, Url::buildUrl('/user/user_label/save'), 'POST');
    }

    /**
     * 保存标签表单数据
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function save(int $id, array $data, int $type = 1, $store_id = 0)
    {
        if (!$data['label_cate']) {
            throw new ValidateException('请选择标签分类');
        }
        $data['type'] = $type;
        if ($store_id) $data['store_id'] = $store_id;
        $levelName = $this->dao->getOne(['label_name' => $data['label_name'], 'type' => $type, 'store_id' => $store_id]);
        if ($id) {
            if (!$this->getLable($id)) {
                throw new AdminException('数据不存在');
            }
            if ($levelName && $id != $levelName['id']) {
                throw new AdminException('该标签已经存在');
            }
            if ($this->dao->update($id, $data)) {
                return true;
            } else {
                throw new AdminException('修改失败或者您没有修改什么！');
            }
        } else {
            unset($data['id']);
            if ($levelName) {
                throw new AdminException('该标签已经存在');
            }
            if ($this->dao->save($data)) {
                return true;
            } else {
                throw new AdminException('添加失败！');
            }
        }
    }

    /**
     * 删除
     * @param $id
     * @throws \Exception
     */
    public function delLabel(int $id)
    {
        if ($this->getLable($id)) {
            if (!$this->dao->delete($id)) {
                throw new AdminException('删除失败,请稍候再试!');
            }
        }
        return true;
    }

    /**
     * 同步标签
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function authWorkClientLabel()
    {
        /** @var UserLabelCateServices $cateService */
        $cateService = app()->make(UserLabelCateServices::class);
        $data = $cateService->getLabelList(['group' => 0, 'owner_id' => 0, 'store_id' => 0]);
        if ($data['list']) {
            foreach ($data['list'] as $item) {
                UserLabelJob::dispatchDo('authLabel', [$item['id'], $item['name']]);
            }
            return UserLabelJob::dispatchSece(count($data['list']) + 1, 'authWorkLabel');
        }
        return true;
    }

    /**
     * 同步平台标签到企业微信客户
     * @param int $cateId
     * @param string $groupName
     * @return bool
     */
    public function addCorpClientLabel(int $cateId, string $groupName)
    {
        try {
            $list = $this->dao->getList(0, 0, ['not_tag_id' => 1, 'type' => 1, 'label_cate' => $cateId], ['label_name as name', 'id']);
            if (!$list) {
                return true;
            }
            $data = [];
            foreach ($list as $item) {
                $data[] = ['name' => $item['name']];
            }

            $res = Work::addCorpTag($groupName, $data);
            /** @var UserLabelCateServices $categoryService */
            $categoryService = app()->make(UserLabelCateServices::class);
            $categoryService->update($cateId, ['other' => $res['tag_group']['group_id']]);
            foreach ($res['tag_group']['tag'] ?? [] as $item) {
                foreach ($list as $value) {
                    if ($item['name'] == $value['name']) {
                        $this->dao->update($value['id'], ['tag_id' => $item['id']]);
                    }
                }
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 客户标签同步到平台
     * @param array $tagIds
     * @param array $group
     * @return bool
     */
    public function authWorkLabel(array $tagIds = [], array $group = [])
    {
        $res = Work::getCorpTags($tagIds, $group);
        $tagGroup = $res['tag_group'] ?? [];
        $cateData = [];
        $labelData = [];
        $groupIds = [];
        /** @var UserLabelCateServices $cateService */
        $cateService = app()->make(UserLabelCateServices::class);
        $this->transaction(function () use ($tagGroup, $cateData, $cateService, $labelData, $groupIds) {
            foreach ($tagGroup as $item) {
                if ($id = $cateService->value(['other' => $item['group_id']], 'id')) {
                    $cateService->update(['id' => $id], ['name' => $item['group_name'], 'other' => $item['group_id'], 'sort' => $item['order']]);
                } else {
                    $cateData[] = [
                        'name' => $item['group_name'],
                        'sort' => $item['order'],
                        'add_time' => $item['create_time'],
                        'other' => $item['group_id'],
                        'group' => 0
                    ];
                }
                $groupIds[] = $item['group_id'];
                foreach ($item['tag'] as $tag) {
                    if ($labelId = $this->dao->value(['tag_id' => $tag['id']], 'id')) {
                        $this->dao->update($labelId, ['tag_id' => $tag['id']]);
                    } else {
                        $labelData[$item['group_id']][] = [
                            'label_name' => $tag['name'],
                            'type' => 1,
                            'tag_id' => $tag['id'],
                        ];
                    }
                }
            }
            if ($cateData) {
                $cateService->saveAll($cateData);
            }
            $cateIds = $cateService->getColumn([
                ['other', 'in', $groupIds],
                ['type', '=', 1],
                ['owner_id', '=', 0],
                ['group', '=', 0],
            ], 'id', 'other');
            if ($labelData) {
                $saveData = [];
                foreach ($labelData as $groupId => $labels) {
                    $cateId = $cateIds[$groupId];
                    foreach ($labels as $label) {
                        $label['label_cate'] = $cateId;
                        $saveData[] = $label;
                    }
                }
                $this->dao->saveAll($saveData);
            }
        });
        $cateService->deleteCateCache();
        return true;
    }

    /**
     * 获取同步企业微信的标签数据
     * @return array
     */
    public function getWorkLabel()
    {
        /** @var UserLabelCateServices $cateService */
        $cateService = app()->make(UserLabelCateServices::class);
        $list = $cateService->getLabelTree(['type' => 1, 'owner_id' => 0, 'group' => 0, 'other' => true], ['name', 'id', 'other as value'], [
            'label' => function ($query) {
                $query->where('tag_id', '<>', '')->where('type', 1)->field(['id', 'label_cate', 'tag_id as value', 'label_name as label']);
            }
        ]);
        foreach ($list as &$item) {
            $label = $item['label'];
            $item['children'] = $label;
            unset($item['label']);
            $item['label'] = $item['name'];
        }
        return $list;
    }

    /**
     * 企业微信创建客户标签事件
     * @param string $corpId
     * @param string $strId
     * @param string $type
     * @return bool
     */
    public function createUserLabel(string $corpId, string $strId, string $type)
    {
        return $this->authWorkLabel($type === 'tag' ? [$strId] : [], $type === 'tag_group' ? [$strId] : []);
    }

    /**
     * 企业微信更新客户标签事件
     * @param string $corpId
     * @param string $strId
     * @param string $type
     * @return bool
     */
    public function updateUserLabel(string $corpId, string $strId, string $type)
    {
        return $this->authWorkLabel($type === 'tag' ? [$strId] : [], $type === 'tag_group' ? [$strId] : []);
    }

    /**
     * 删除标签
     * @param string $corpId
     * @param string $strId
     * @param string $type
     */
    public function deleteUserLabel(string $corpId, string $strId, string $type)
    {
        if ('tag' === $type) {
            $this->dao->delete(['tag_id' => $strId]);
        } else if ('tag_group' === $type) {
            /** @var UserLabelCateServices $cateService */
            $cateService = app()->make(UserLabelCateServices::class);
            $cateInfo = $cateService->get(['type' => 1, 'owner_id' => 0, 'group' => 0, 'other' => $strId]);
            if ($cateInfo) {
                $this->dao->delete(['label_cate' => $cateInfo->id, 'type' => 1]);
                $cateInfo->delete();
            }
        }
    }

}
