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

use app\Request;
use app\services\BaseServices;
use app\dao\activity\bargain\StoreBargainUserDao;

/**
 * Class StoreBargainUserServices
 * @package app\services\activity\bargain
 * @method getAllCount(array $where)
 * @method count(array $where)
 * @method value(array $where, ?string $field)
 * @method getBargainUserTableId(int $bargainId, int $bargainUserUid)
 * @method update(int $bargainId, array $data)
 * @method getOne(array $where, ?string $field = '*', array $with = [])
 * @method updateBargainStatus(int $id, ?int $status = 3)
 */
class StoreBargainUserServices extends BaseServices
{

    const EPTQRWNF = 'qbhzEE';

    /**
     * StoreBargainUserServices constructor.
     * @param StoreBargainUserDao $dao
     */
    public function __construct(StoreBargainUserDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取砍价
     * @param Request $request
     * @param int $bargainId
     * @param int $bargainUserUid
     * @return mixed
     */
    public function helpCount(Request $request, int $bargainId, int $bargainUserUid)
    {
        $bargainUserTableId = $this->dao->value(['bargain_id' => $bargainId, 'uid' => $bargainUserUid, 'is_del' => 0, 'status' => 1]);// 获取用户参与砍价表编号
        $data['userBargainStatus'] = false;
        if ($bargainUserTableId) {
            $data['userBargainStatus'] = $this->isBargainUserHelpCount($bargainId, $request->uid(), $bargainUserTableId);
            /** @var StoreBargainUserHelpServices $helpService */
            $helpService = app()->make(StoreBargainUserHelpServices::class);
            $count = $helpService->count(['bargain_user_id' => $bargainUserTableId, 'bargain_id' => $bargainId]);// 获取砍价帮总人数
            [$coverprice, $alreadyPrice, $price, $pricePercent] = $this->getSurplusPrice($bargainUserTableId);
            $data['count'] = $count;
            $data['price'] = $price;
            $data['status'] = $this->dao->value(['id' => $bargainUserTableId], 'status') ?? 0;
            $data['alreadyPrice'] = $alreadyPrice;
            $data['pricePercent'] = $pricePercent > 10 ? $pricePercent : 10;
        } else {
            /** @var StoreBargainServices $bargainService */
            $bargainService = app()->make(StoreBargainServices::class);
            $data['count'] = 0;
            $data['price'] = $bargainService->value(['id' => $bargainId], 'price - min_price');
            $data['status'] = $this->dao->value(['id' => $bargainUserTableId], 'status') ?? 0;
            $data['alreadyPrice'] = 0;
            $data['pricePercent'] = 0;
        }
        return $data;
    }

    /**
     * 获取砍价状态
     * @param int $bargainId
     * @param int $bargainUserUid
     * @param int $bargainUserHelpUid
     * @param $bargainUserTableId
     * @return bool
     */
    public function isBargainUserHelpCount($bargainId, $bargainUserHelpUid, $bargainUserTableId)
    {
        /** @var StoreBargainUserHelpServices $userHelp */
        $userHelp = app()->make(StoreBargainUserHelpServices::class);
        $count = $userHelp->count(['bargain_id' => $bargainId, 'bargain_user_id' => $bargainUserTableId, 'uid' => $bargainUserHelpUid]);
        if (!$count) return true;
        else return false;
    }

    /**
     * 获取砍价剩余金额 或者 砍价百分比
     * @param $bargainUserTableId
     * @return array|float|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSurplusPrice($bargainUserTableId)
    {
        $bargainUserInfo = $this->dao->get($bargainUserTableId);
        $coverPrice = $alreadyPrice = $surplusPrice = 0;
        $pricePercent = 100;
        if ($bargainUserInfo) {
            // 获取用户可以砍掉的金额  好友砍价之后获取砍价金额
            $coverPrice = (float)bcsub((string)$bargainUserInfo['bargain_price'], (string)$bargainUserInfo['bargain_price_min'], 2);
            // 用户已经砍掉的价格 好友砍价之后获取用户已经砍掉的价格
            $alreadyPrice = $this->dao->value(['id' => $bargainUserTableId], 'price');
            // 用户剩余要砍掉的价格
            $surplusPrice = (float)bcsub((string)$coverPrice, (string)$alreadyPrice, 2);
            if ($alreadyPrice) {//进度条百分比
                $pricePercent = (int)bcmul((string)bcdiv((string)$alreadyPrice, (string)$coverPrice, 2), '100', 0);
            }
        }
        return [$coverPrice, $alreadyPrice, $surplusPrice, $pricePercent, $bargainUserInfo];
    }

    /**
     * 添加砍价信息
     * @param int $bargainId
     * @param int $bargainUserUid
     * @param array $bargainInfo
     * @return mixed
     */
    public function setBargain(int $bargainId, int $bargainUserUid, array $bargainInfo)
    {
        $data['bargain_id'] = $bargainId;
        $data['uid'] = $bargainUserUid;
        $data['bargain_price_min'] = $bargainInfo['min_price'];
        $data['bargain_price'] = $bargainInfo['price'];
        $data['price'] = 0;
        $data['status'] = 1;
        $data['is_del'] = 0;
        $data['add_time'] = time();
        return $this->dao->save($data);
    }


    /**
     * 修改砍价状态
     * @param $uid
     * @return bool
     */
    public function editBargainUserStatus($uid)
    {
        $currentBargain = $this->dao->getColumn(['uid' => $uid, 'is_del' => 0, 'status' => 1], 'bargain_id');
        /** @var StoreBargainServices $bargainService */
        $bargainService = app()->make(StoreBargainServices::class);
        $bargainProduct = $bargainService->validWhere()->column('id');
        $closeBargain = [];
        foreach ($currentBargain as $key => &$item) {
            if (!in_array($item, $bargainProduct)) {
                $closeBargain[] = $item;
            }
        }// 获取已经结束的砍价商品
        if (count($closeBargain)) $this->dao->update([['uid', '=', $uid], ['status', '=', 1], ['bargain_id', 'in', implode(',', $closeBargain)]], ['status' => 2]);
    }


    /**
     *  获取用户的砍价商品
     * @param int $bargainUserUid $bargainUserUid  开启砍价用户编号
     * @return array
     */
    public function getBargainUserAll(int $bargainUserUid)
    {
        if (!$bargainUserUid) return [];
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->userAll($bargainUserUid, $page, $limit);
        foreach ($list as &$item) {
            $item['residue_price'] = bcsub((string)$item['bargain_price'], (string)$item['price'], 2);
        }
        return $list;
    }

    /**
     * 取消砍价
     * @param $bargainId
     * @param $uid
     * @return mixed
     */
    public function cancelBargain($bargainId, $uid)
    {
        $status = $this->dao->getBargainUserStatus($bargainId, $uid);
        if ($status != 1) return app('json')->fail('状态错误');
        $id = $this->dao->value(['bargain_id' => $bargainId, 'uid' => $uid, 'is_del' => 0], 'id');

        return $this->dao->update($id, ['is_del' => 1, 'status' => 2]);
    }

    /**
     * 下架删除砍价时修改砍价状态 砍价失败
     * @param $bargain_id
     */
    public function userBargainStatusFail($bargain_id)
    {
        $this->dao->delete(['bargain_id' => $bargain_id, 'status' => 1]);
        /** @var StoreBargainUserHelpServices $service */
        $service = app()->make(StoreBargainUserHelpServices::class);
        $service->delete(['bargain_id' => $bargain_id]);
    }

    /**
     * 砍价列表
     * @param $where
     * @return array
     */
    public function bargainUserList($where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->bargainUserList($where, $page, $limit);
        $count = $this->dao->count($where);
        /** @var StoreBargainUserHelpServices $bargainUserHelpService */
        $bargainUserHelpService = app()->make(StoreBargainUserHelpServices::class);
        $nums = $bargainUserHelpService->getNums();
        foreach ($list as &$item) {
            $item['num'] = $item['people_num'] - ($nums[$item['id']] ?? 0);
            $item['now_price'] = bcsub((string)$item['bargain_price'], (string)$item['price'], 2);
            $item['add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', (int)$item['add_time']) : '';
            $item['datatime'] = $item['datatime'] ? date('Y-m-d H:i:s', (int)$item['datatime']) : '';
        }
        return compact('list', 'count');
    }
}
