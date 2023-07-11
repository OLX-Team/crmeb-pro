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

namespace app\services\activity\bargain;

use app\services\BaseServices;
use app\dao\activity\bargain\StoreBargainUserHelpDao;
use app\services\user\UserServices;
use crmeb\traits\ServicesTrait;
use think\exception\ValidateException;

/**
 * Class StoreBargainUserHelpServices
 * @package app\services\activity\bargain
 * @method getHelpAllCount(array $where)
 * @method count(array $where)
 * @method getBargainPeople(array $where, string $field = '*', string $key = 'bargain_id') 获取每个商品参与砍价人数
 */
class StoreBargainUserHelpServices extends BaseServices
{

    use ServicesTrait;

    const MHLJEWVH = 'xI/Rha';

    /**
     * StoreBargainUserHelpServices constructor.
     * @param StoreBargainUserHelpDao $dao
     */
    public function __construct(StoreBargainUserHelpDao $dao)
    {
        $this->dao = $dao;
    }


    /**
     * 获取砍价帮列表
     * @param int $bid
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getHelpList(int $bid, int $page = 0, int $limit = 0)
    {
        $list = $this->dao->getHelpList($bid, $page, $limit);
        return array_values($list);
    }

    /**
     * 获取砍价金额
     * @param int $uid
     * @param int $bargainId
     * @param int $bargainUserUid
     * @return array
     */
    public function getPrice(int $uid, int $bargainId, int $bargainUserUid)
    {
        if (!$bargainId || !$bargainUserUid) throw new ValidateException('参数错误');
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        $bargainUserTable = $bargainUserService->get(['bargain_id' => $bargainId, 'uid' => $bargainUserUid, 'is_del' => 0], ['id', 'status', 'bargain_price_min', 'bargain_price', 'price']);// 获取用户参与砍价表编号
        if (!$bargainUserTable) {
            throw new ValidateException('砍价信息没有查询到');
        }
        if (bcsub($bargainUserTable['bargain_price'], $bargainUserTable['price'], 2) == $bargainUserTable['bargain_price_min']) {
            $status = true;
        } else {
            $status = false;
        }
        $price = $this->dao->value(['uid' => $uid, 'bargain_id' => $bargainId, 'bargain_user_id' => $bargainUserTable['id']], 'price');
        return ['price' => $price, 'status' => $status];
    }

    /**
     * 判断是否能砍价
     * @param $bargainId
     * @param $bargainUserTableId
     * @param $uid
     * @return bool
     */
    public function isBargainUserHelpCount($bargainId, $bargainUserTableId, $uid)
    {
        $count = $this->dao->count(['bargain_id' => $bargainId, 'bargain_user_id' => $bargainUserTableId, 'uid' => $uid]);
        if (!$count) return true;
        else return false;
    }

    /**
     *  帮忙砍价
     * @param $bargainId
     * @param $bargainUserTableId
     * @param $uid
     * @param array $bargainInfo
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setBargainUserHelp($bargainId, $bargainUserTableId, $uid, $bargainInfo = [])
    {
        if (!$bargainInfo) {
            /** @var StoreBargainServices $bargainService */
            $bargainService = app()->make(StoreBargainServices::class);
            $bargainInfo = $bargainService->get($bargainId);
        }
        /** @var StoreBargainUserServices $bargainUserService */
        $bargainUserService = app()->make(StoreBargainUserServices::class);
        [$coverPrice, $alreadyPrice, $surplusPrice, $pricePercent, $bargainUserInfo] = $bargainUserService->getSurplusPrice($bargainUserTableId);
        if (0.00 === (float)$surplusPrice) throw new ValidateException('砍价已经完成');

        $data['uid'] = $uid;
        $data['bargain_id'] = $bargainId;
        $data['bargain_user_id'] = $bargainUserTableId;
        $data['add_time'] = time();
        if ($bargainUserInfo['uid'] == $uid) {
            $data['type'] = 1;
        } else {
            //帮砍次数限制
            $count = $this->dao->count(['uid' => $uid, 'bargain_id' => $bargainId, 'type' => 0]);
            if ($count >= $bargainInfo->bargain_num) throw new ValidateException('您不能再帮砍此件商品');
            $data['type'] = 0;
        }
        $people = $this->dao->count(['bargain_user_id' => $bargainUserTableId]);//已经参与砍价的人数
        $surplusPeople = $bargainInfo->people_num - $people;
        if ($surplusPeople == 1) {
            $data['price'] = $surplusPrice;
        } else {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->get($uid);
            $data['price'] = $this->randomFloat($surplusPrice, $bargainInfo->people_num, $surplusPeople, $userInfo->add_time == $userInfo->last_time && !$this->dao->count(['uid' => $uid]));
        }
        $price = bcadd((string)$alreadyPrice, (string)$data['price'], 2);
        $bargainUserData['price'] = $price;
        $this->transaction(function () use ($bargainUserService, $bargainUserTableId, $bargainUserData, $data) {
            $res1 = $bargainUserService->update($bargainUserTableId, $bargainUserData);
            $res2 = $this->dao->save($data);
            $res = $res1 && $res2;
            if (!$res) throw new ValidateException('砍价失败');
        });
        return bcsub((string)$surplusPrice, (string)$data['price'], 2);
    }

    /**
     * 随机金额
     * @param $price
     * @param $people
     * @param $type
     * @return string
     */
    public function randomFloat($price, $sum_people, $people, $type = false)
    {
        $max_percent = bcmul((string)bcdiv((string)$people, (string)$sum_people, 2), '100', 0);
        $min_percent = bcmul((string)bcdiv((string)($people - 1), (string)$sum_people, 2), '100', 0);
        //按照人数计算保留金额
        $retainPrice = bcmul((string)$people, '0.01', 2);
        //实际剩余金额
        $price = bcsub((string)$price, $retainPrice, 2);
        //计算比例
        if ($type) {
            $percent = '0.5';
        } else {
            mt_srand();
            $percent = bcdiv((string)mt_rand(10, 30), '100', 2);
        }
        //实际砍掉金额
        $cutPrice = bcmul($price, $percent, 2);
        //如果计算出来为0，默认砍掉0.01
        return $cutPrice != '0.00' ? $cutPrice : '0.01';
    }

    /**
     * 获取砍价商品已砍人数
     * @return array
     */
    public function getNums()
    {
        $nums = $this->dao->getNums();
        $dat = [];
        foreach ($nums as $item) {
            $dat[$item['bargain_user_id']] = $item['num'];
        }
        return $dat;
    }
}
