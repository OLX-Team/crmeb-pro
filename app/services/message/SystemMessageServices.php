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

namespace app\services\message;

use app\dao\message\SystemMessageDao;
use app\services\BaseServices;
use think\exception\ValidateException;

/**
 * 站内信
 * Class SystemMessageServices
 * @package app\services\message
 */
class SystemMessageServices extends BaseServices
{

    /**
     * SystemMessageServices constructor.
     * @param SystemMessageDao $dao
     */
    public function __construct(SystemMessageDao $dao)
    {
        $this->dao = $dao;
    }

    public function getMessageSystemList($uid)
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_del'] = 0;
        $where['uid'] = $uid;
        $list = $this->dao->getMessageList($where, '*', $page, $limit);
        $count = $this->dao->getCount($where);
        if (!$list) return ['list' => [], 'count' => 0];
        foreach ($list as &$item) {
            $item['add_time'] = time_tran($item['add_time']);
        }
        $message = $this->dao->count(['uid' => $uid, 'look' => 0, 'is_del' => 0]);
        return ['list' => $list, 'count' => $count, 'service_num' => $message];
    }

    public function getInfo($where)
    {
        $info = $this->dao->getOne($where);
        if (!$info || $info['is_del'] == 1) {
            throw new ValidateException('数据不存在');
        }
        $info = $info->toArray();
        if ($info['look'] == 0) {
            $this->update($info['id'], ['look' => 1]);
        }
        $info['add_time'] = time_tran($info['add_time']);
        return $info;
    }

    /**
     * 站内信发放
     * @param int $uid
     * @param array $noticeInfo
     * @return \crmeb\basic\BaseModel|\think\Model
     */
    public function systemSend(int $uid, array $noticeInfo)
    {
        $data = [];
        $data['mark'] = $noticeInfo['mark'];
        $data['uid'] = $uid;
        $data['title'] = $noticeInfo['title'];
        $data['content'] = $noticeInfo['content'];
        $data['type'] = 1;
        $data['add_time'] = time();
        return $this->dao->save($data);
    }
}
