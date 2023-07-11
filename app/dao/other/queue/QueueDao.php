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

namespace app\dao\other\queue;


use app\dao\BaseDao;
use app\model\other\queue\Queue;

/**
 * 队列
 * Class QueueDao
 * @package app\dao\other
 */
class QueueDao extends BaseDao
{

    /**
     * @return string
     */
    public function setModel(): string
    {
        return Queue::class;
    }


    /**
     * 队列任务列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, int $page = 0, int $limit = 0, string $order = '')
    {
        foreach ($where as $k => $v) {
            if ($v == "") unset($where[$k]);
        }
        return $this->search($where)
            ->order(($order ? $order . ' ,' : '') . 'add_time desc')
            ->page($page, $limit)->select()->toArray();
    }

    /**
     * 获取单个队列详情
     * @param array $where
     * @return array|bool|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getQueueOne(array $where)
    {
        if (!$where) return false;
        return $this->search($where)->order('id desc')->find();
    }

    /**
     * 加入队列数据表
     * @param string $queueName
     * @param int $queueDataNum
     * @param int $type
     * @param string $redisKey
     * @param string $source
     * @return mixed
     */
    public function addQueueList(string $queueName, int $queueDataNum, int $type, string $redisKey, string $source = "admin")
    {
        $data = [
            'type' => $type,
            'source' => $source,
            'execute_key' => $redisKey ? $redisKey : '',
            'title' => $queueName,
            'status' => 0,
            'surplus_num' => $queueDataNum,
            'total_num' => $queueDataNum,
            'add_time' => time(),
        ];
        return $this->getModel()->insertGetId($data);
    }


    /**
     * 将队列置为正在执行状态
     * @param $queueInValue
     * @param $queueId
     * @return bool|mixed
     */
    public function setQueueDoing($queueInValue, $queueId, bool $is_again = false)
    {
        $saveData['queue_in_value'] = is_array($queueInValue) ? json_encode($queueInValue) : $queueInValue;
        $saveData['status'] = 1;
        if ($is_again) {
            $saveData['again_time'] = time();
        } else {
            $saveData['first_time'] = time();
        }
        return $this->getModel()->update($saveData, ['id' => $queueId]);
    }

    /**
     * 停止队列
     * @param $queueId
     * @return \crmeb\basic\BaseModel
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function stopWrongQueue($queueId)
    {
        $queueInfo = $this->getModel()->where(['id' => $queueId])->find();
        if (!$queueInfo) return false;
        return $this->getModel()->update(['id' => $queueId], ['status' => 3]);
    }

}
