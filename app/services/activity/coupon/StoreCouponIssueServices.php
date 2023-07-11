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

namespace app\services\activity\coupon;

use app\services\BaseServices;
use app\dao\activity\coupon\StoreCouponIssueDao;
use app\services\order\StoreCartServices;
use app\services\other\queue\QueueServices;
use app\services\product\category\StoreCategoryServices;
use app\services\product\product\StoreProductServices;
use app\services\user\member\MemberCardServices;
use app\services\user\member\MemberRightServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder;
use think\exception\ValidateException;

/**
 *
 * Class StoreCouponIssueServices
 * @package app\services\activity\coupon
 * @method getUserIssuePrice(string $price) 获取金大于额的优惠卷金额
 * @method getCouponInfo($id)
 * @method getGiveCoupon(array $where, string $field = '*')
 * @method getColumn(array $where, string $field, ?string $key)
 * @method productCouponList(array $where, string $field)
 * @method getPcIssueCouponList(int $uid, array $cate_ids = [], int $product_id = 0, string $filed = '*', int $page = 0, int $limit = 0, string $sort = 'sort desc,id desc')
 * @method getValidGiveCoupons(array $ids, string $field = '*' , int $page = 0, int $limit = 0)
 */
class StoreCouponIssueServices extends BaseServices
{

    public $_couponType = [0 => "通用券", 1 => "品类券", 2 => '商品券'];

    /**
     * StoreCouponIssueServices constructor.
     * @param StoreCouponIssueDao $dao
     */
    public function __construct(StoreCouponIssueDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取已发布列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCouponIssueList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_del'] = 0;
        $list = $this->dao->getList($where, $page, $limit);
        foreach ($list as &$item) {
            if (!$item['coupon_time']) {
                $item['coupon_time'] = ceil(($item['end_use_time'] - $item['start_use_time']) / '86400');
            }
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 获取会员优惠券列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMemberCouponIssueList(array $where)
    {
        return $this->dao->getApiIssueList($where);
    }

    /**
     * 新增优惠券
     * @param $data
     * @return bool
     */
    public function saveCoupon($data)
    {
        $data['start_use_time'] = strtotime((string)$data['start_use_time']);
        $data['end_use_time'] = strtotime((string)$data['end_use_time']);
        $data['start_time'] = strtotime((string)$data['start_time']);
        $data['end_time'] = strtotime((string)$data['end_time']);
        $data['title'] = $data['coupon_title'];
        $data['remain_count'] = $data['total_count'];
        $data['add_time'] = time();
        $res = $this->dao->save($data);
        if ($data['product_id'] !== '' && $res) {
            $productIds = explode(',', $data['product_id']);
            $couponData = [];
            foreach ($productIds as $product_id) {
                $couponData[] = ['product_id' => $product_id, 'coupon_id' => $res->id];
            }
            /** @var StoreCouponProductServices $storeCouponProductService */
            $storeCouponProductService = app()->make(StoreCouponProductServices::class);
            $storeCouponProductService->saveAll($couponData);
        }
        if (!$res) throw new AdminException('添加优惠券失败!');
        return true;
    }


    /**
     * 修改状态
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createForm(int $id)
    {
        $issueInfo = $this->dao->get($id);
        if (-1 == $issueInfo['status'] || 1 == $issueInfo['is_del']) return $this->fail('状态错误,无法修改');
        $f = [FormBuilder::radio('status', '是否开启', $issueInfo['status'])->options([['label' => '开启', 'value' => 1], ['label' => '关闭', 'value' => 0]])];
        return create_form('状态修改', $f, $this->url('/marketing/coupon/released/status/' . $id), 'PUT');
    }

    /**
     * 领取记录
     * @param int $id
     * @return array
     */
    public function issueLog(int $id)
    {
        $coupon = $this->dao->get($id);
        if (!$coupon) {
            throw new ValidateException('优惠券不存在');
        }
        if ($coupon['receive_type'] != 4) {
            /** @var StoreCouponIssueUserServices $storeCouponIssueUserService */
            $storeCouponIssueUserService = app()->make(StoreCouponIssueUserServices::class);
            return $storeCouponIssueUserService->issueLog(['issue_coupon_id' => $id]);
        } else {//会员券
            /** @var StoreCouponUserServices $storeCouponUserService */
            $storeCouponUserService = app()->make(StoreCouponUserServices::class);
            return $storeCouponUserService->issueLog(['cid' => $id]);
        }

    }

    /**
     * 关注送优惠券
     * @param int $uid
     */
    public function userFirstSubGiveCoupon(int $uid)
    {
        $couponList = $this->dao->getGiveCoupon(['receive_type' => 2]);
        if ($couponList) {
            $couponData = [];
            $issueUserData = [];
            $time = time();
            $ids = array_column($couponList, 'id');
            /** @var StoreCouponIssueUserServices $issueUser */
            $issueUser = app()->make(StoreCouponIssueUserServices::class);
            $userCouponIds = $issueUser->getColumn([['uid', '=', $uid], ['issue_coupon_id', 'in', $ids]], 'issue_coupon_id') ?? [];
            foreach ($couponList as $item) {
                if (!$userCouponIds || !in_array($item['id'], $userCouponIds)) {
                    $data['cid'] = $item['id'];
                    $data['uid'] = $uid;
                    $data['coupon_title'] = $item['title'];
                    $data['coupon_price'] = $item['coupon_price'];
                    $data['use_min_price'] = $item['use_min_price'];
                    if ($item['coupon_time']) {
                        $data['add_time'] = $time;
                        $data['end_time'] = $data['add_time'] + $item['coupon_time'] * 86400;
                    } else {
                        $data['add_time'] = $item['start_use_time'];
                        $data['end_time'] = $item['end_use_time'];
                    }
                    $data['type'] = 'get';
                    $issue['uid'] = $uid;
                    $issue['issue_coupon_id'] = $item['id'];
                    $issue['add_time'] = $time;
                    $issueUserData[] = $issue;
                    $couponData[] = $data;
                    unset($data);
                    unset($issue);
                }
            }
            if ($couponData) {
                /** @var StoreCouponUserServices $storeCouponUser */
                $storeCouponUser = app()->make(StoreCouponUserServices::class);
                if (!$storeCouponUser->saveAll($couponData)) {
                    throw new AdminException('发劵失败');
                }
            }
            if ($issueUserData) {
                if (!$issueUser->saveAll($issueUserData)) {
                    throw new AdminException('发劵失败');
                }
            }
        }
        return true;
    }

    /**
     * 订单金额达到预设金额赠送优惠卷
     * @param $uid
     */
    public function userTakeOrderGiveCoupon($uid, $total_price)
    {
        $couponList = $this->dao->getGiveCoupon([['is_full_give', '=', 1], ['full_reduction', '<=', $total_price]]);
        if ($couponList) {
            $couponData = $issueUserData = [];
            $time = time();
            $ids = array_column($couponList, 'id');
            /** @var StoreCouponIssueUserServices $issueUser */
            $issueUser = app()->make(StoreCouponIssueUserServices::class);
            $userCouponIds = $issueUser->getColumn([['uid', '=', $uid], ['issue_coupon_id', 'in', $ids]], 'issue_coupon_id') ?? [];
            foreach ($couponList as $item) {
                if ($total_price >= $item['full_reduction'] && (!$userCouponIds || !in_array($item['id'], $userCouponIds))) {
                    $data['cid'] = $item['id'];
                    $data['uid'] = $uid;
                    $data['coupon_title'] = $item['title'];
                    $data['coupon_price'] = $item['coupon_price'];
                    $data['use_min_price'] = $item['use_min_price'];
                    if ($item['coupon_time']) {
                        $data['add_time'] = $time;
                        $data['end_time'] = $data['add_time'] + $item['coupon_time'] * 86400;
                    } else {
                        $data['add_time'] = $item['start_use_time'];
                        $data['end_time'] = $item['end_use_time'];
                    }
                    $data['type'] = 'get';
                    $issue['uid'] = $uid;
                    $issue['issue_coupon_id'] = $item['id'];
                    $issue['add_time'] = $time;
                    $issueUserData[] = $issue;
                    $couponData[] = $data;
                    unset($data);
                    unset($issue);
                }
            }
            if ($couponData) {
                /** @var StoreCouponUserServices $storeCouponUser */
                $storeCouponUser = app()->make(StoreCouponUserServices::class);
                if (!$storeCouponUser->saveAll($couponData)) {
                    throw new AdminException('发劵失败');
                }
            }
            if ($issueUserData) {
                if (!$issueUser->saveAll($issueUserData)) {
                    throw new AdminException('发劵失败');
                }
            }
        }
        return true;
    }

    /**
     * 下单之后赠送
     * @param $uid
     */
    public function orderPayGiveCoupon($uid, $coupon_issue_ids)
    {
        if (!$coupon_issue_ids) return [];
        $couponList = $this->dao->getGiveCoupon([['id', 'IN', $coupon_issue_ids]]);
        $couponData = $issueUserData = [];
        if ($couponList) {
            $time = time();
            $ids = array_column($couponList, 'id');
            /** @var StoreCouponIssueUserServices $issueUser */
            $issueUser = app()->make(StoreCouponIssueUserServices::class);
            $userCouponIds = $issueUser->getColumn([['uid', '=', $uid], ['issue_coupon_id', 'in', $ids]], 'issue_coupon_id') ?? [];
            foreach ($couponList as $item) {
                if (!$userCouponIds || !in_array($item['id'], $userCouponIds)) {
                    $data['cid'] = $item['id'];
                    $data['uid'] = $uid;
                    $data['coupon_title'] = $item['title'];
                    $data['coupon_price'] = $item['coupon_price'];
                    $data['use_min_price'] = $item['use_min_price'];
                    if ($item['coupon_time']) {
                        $data['add_time'] = $time;
                        $data['end_time'] = $data['add_time'] + $item['coupon_time'] * 86400;
                    } else {
                        $data['add_time'] = $item['start_use_time'];
                        $data['end_time'] = $item['end_use_time'];
                    }
                    $data['type'] = 'get';
                    $issue['uid'] = $uid;
                    $issue['issue_coupon_id'] = $item['id'];
                    $issue['add_time'] = $time;
                    $issueUserData[] = $issue;
                    $couponData[] = $data;
                    unset($data);
                    unset($issue);
                }
            }
            if ($couponData) {
                /** @var StoreCouponUserServices $storeCouponUser */
                $storeCouponUser = app()->make(StoreCouponUserServices::class);
                if (!$storeCouponUser->saveAll($couponData)) {
                    throw new AdminException('发劵失败');
                }
            }
            if ($issueUserData) {
                if (!$issueUser->saveAll($issueUserData)) {
                    throw new AdminException('发劵失败');
                }
            }
        }
        return $couponData;
    }

    /**
     * 获取优惠券列表
     * @param int $uid
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getIssueCouponList(int $uid, array $where, bool $is_product = false)
    {
        [$page, $limit] = $this->getPageValue();
        if ($where['product_id'] == 0) {
            $typeId = 0;
            $cateId = 0;
            if ($where['type'] == -1) { // PC端获取优惠券
                $list = $this->dao->getPcIssueCouponList($uid);
            } else {
                $list = $this->dao->getIssueCouponList($uid, (int)$where['type'], $typeId, $page, $limit);
                if (!$list) $list = $this->dao->getIssueCouponList($uid, 1, $typeId, $page, $limit);
                if (!$list) $list = $this->dao->getIssueCouponList($uid, 2, $typeId, $page, $limit);
            }
        } else {
            /** @var StoreProductServices $storeProductService */
            $storeProductService = app()->make(StoreProductServices::class);
            /** @var StoreCategoryServices $storeCategoryService */
            $storeCategoryService = app()->make(StoreCategoryServices::class);

            $cateId = $storeProductService->value(['id' => $where['product_id']], 'cate_id');
            $cateId = explode(',', $cateId);
            $cateId = array_merge($cateId, $storeCategoryService->cateIdByPid($cateId));
            $cateId = array_diff($cateId, [0]);
            if ($where['type'] == -1) { // PC端获取优惠券
                $list = $this->dao->getPcIssueCouponList($uid, $cateId, (int)$where['product_id']);
            } else {
                if ($where['type'] == 1) {
                    $typeId = $cateId;
                } elseif ($where['type'] == 2) {
                    $typeId = $where['product_id'];
                } else {
                    $typeId = 0;
                }
                $list = $this->dao->getIssueCouponList($uid, (int)$where['type'], $typeId, $page, $limit);
            }
        }
        if ($list) {
            $limit = 3;
            $field = ['id', 'image', 'store_name', 'price', 'IFNULL(sales,0) + IFNULL(ficti,0) as sales'];
            /** @var StoreProductServices $productServices */
            $productServices = app()->make(StoreProductServices::class);
            /** @var StoreCategoryServices $categoryServices */
            $categoryServices = app()->make(StoreCategoryServices::class);
            $category = $categoryServices->getColumn([], 'pid,cate_name', 'id');
            foreach ($list as &$item) {
                $item['coupon_price'] = floatval($item['coupon_price']);
                $item['use_min_price'] = floatval($item['use_min_price']);
                $item['is_use'] = $uid ? isset($item['used']) : false;
                if (!$item['end_time']) {
                    $item['start_time'] = '';
                    $item['end_time'] = '不限时';
                } else {
                    $item['start_time'] = date('Y/m/d', $item['start_time']);
                    $item['end_time'] = $item['end_time'] ? date('Y/m/d', $item['end_time']) : date('Y/m/d', time() + 86400);
                }
                if (!$is_product) continue;
                $product_where = [];
                $product_where['is_show'] = 1;
                $product_where['is_del'] = 0;
                $product_where['use_min_price'] = $item['use_min_price'];
                switch ($item['type']) {
                    case 0:
                    case 3:
                        break;
                    case 1://品类券
                        $product_where['salesOrder'] = 'desc';
                        $category_type = $category[$item['category_id']]['pid'] == 0 ? 1 : 2;
                        if ($category_type == 1) {
                            $product_where['cid'] = $item['category_id'];
                        } else {
                            $product_where['sid'] = $item['category_id'];
                        }
                        break;
                    case 2://商品劵
                        $product_where['salesOrder'] = 'desc';
                        $product_where['ids'] = $item['product_id'];
                        break;
                    default:
                        $item['products'] = [];
                }
                $item['products'] = $productServices->getSearchList($product_where, 0, $limit, $field, $item['type'] == 0 ? 'rand' : '', []);
            }
        }
        $data['list'] = $list;
        $data['count'] = $this->dao->getIssueCouponCount($where['product_id'], $cateId);
        return $data;
    }

    /**
     * 给用户发送优惠券
     * @param $id
     * @param $user
     * @param bool $more
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function issueUserCoupon($id, $user, bool $more = false)
    {
        $issueCouponInfo = $this->dao->getInfo((int)$id);
        $uid = $user['uid'];
        if (!$issueCouponInfo) throw new ValidateException('领取的优惠劵已领完或已过期!');
        /** @var MemberRightServices $memberRightService */
        $memberRightService = app()->make(MemberRightServices::class);
        if ($issueCouponInfo->receive_type == 4 && (!$user['is_money_level'] || !$memberRightService->getMemberRightStatus("coupon"))) {
            if (!$user['is_money_level']) throw new ValidateException('您不是付费会员!');
            if (!$memberRightService->getMemberRightStatus("coupon")) throw new ValidateException('暂时无法领取!');
        }
        /** @var StoreCouponIssueUserServices $issueUserService */
        $issueUserService = app()->make(StoreCouponIssueUserServices::class);
        /** @var StoreCouponUserServices $couponUserService */
        $couponUserService = app()->make(StoreCouponUserServices::class);
        //是否多次领取
        if (!$more) {
            if ($issueUserService->getOne(['uid' => $uid, 'issue_coupon_id' => $id])) {
                throw new ValidateException('已领取过该优惠劵!');
            }
        }
        if ($issueCouponInfo->remain_count <= 0 && !$issueCouponInfo->is_permanent) throw new ValidateException('抱歉优惠券已经领取完了！');
        $userCounpon = $this->transaction(function () use ($issueUserService, $uid, $id, $couponUserService, $issueCouponInfo) {
            $issueUserService->save(['uid' => $uid, 'issue_coupon_id' => $id, 'add_time' => time()]);
            $res = $couponUserService->addUserCoupon($uid, $issueCouponInfo, "get");
            if ($issueCouponInfo['total_count'] > 0) {
                $issueCouponInfo['remain_count'] -= 1;
                $issueCouponInfo->save();
            }
            return $res;
        });
        return $userCounpon;
    }

    /**
     * 会员发放优惠期券
     * @param $id
     * @param $uid
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function memberIssueUserCoupon($id, $uid)
    {
        $issueCouponInfo = $this->dao->getInfo((int)$id);
        if ($issueCouponInfo) {
            /** @var StoreCouponIssueUserServices $issueUserService */
            $issueUserService = app()->make(StoreCouponIssueUserServices::class);
            /** @var StoreCouponUserServices $couponUserService */
            $couponUserService = app()->make(StoreCouponUserServices::class);
            if ($issueCouponInfo->remain_count >= 0 || $issueCouponInfo->is_permanent) {
                $this->transaction(function () use ($issueUserService, $uid, $id, $couponUserService, $issueCouponInfo) {
                    //$issueUserService->save(['uid' => $uid, 'issue_coupon_id' => $id, 'add_time' => time()]);
                    $couponUserService->addMemberUserCoupon($uid, $issueCouponInfo, "send");
                    // 如果会员劵需要限制数量时打开
                    if ($issueCouponInfo['total_count'] > 0) {
                        $issueCouponInfo['remain_count'] -= 1;
                        $issueCouponInfo->save();
                    }
                });
            }

        }

    }

    /**
     * 用户优惠劵列表
     * @param int $uid
     * @param $types
     * @return array
     */
    public function getUserCouponList(int $uid, $types)
    {
        /** @var StoreCouponUserServices $storeConponUser */
        $storeConponUser = app()->make(StoreCouponUserServices::class);
        return $storeConponUser->getUserCounpon($uid, $types);
    }

    /**
     * 后台发送优惠券
     * @param $coupon
     * @param $user
     * @return bool
     */
    public function setCoupon($coupon, $user, $redisKey, $queueId)
    {
        if (!$redisKey || !$queueId || !$user) return false;
        $data = [];
        $issueData = [];
        /** @var StoreCouponUserServices $storeCouponUser */
        $storeCouponUser = app()->make(StoreCouponUserServices::class);
        /** @var StoreCouponIssueUserServices $storeCouponIssueUser */
        $storeCouponIssueUser = app()->make(StoreCouponIssueUserServices::class);
        /** @var QueueServices $queueService */
        $queueService = app()->make(QueueServices::class);
        $uids = $storeCouponIssueUser->getColumn(['issue_coupon_id' => $coupon['id']], 'uid');
        $i = 0;
        $time = time();
        foreach ($user as $k => $v) {
            if (in_array($v, $uids)) {
                continue;
            } else {
                $data[$i]['cid'] = $coupon['id'];
                $data[$i]['uid'] = $v;
                $data[$i]['coupon_title'] = $coupon['title'];
                $data[$i]['coupon_price'] = $coupon['coupon_price'];
                $data[$i]['use_min_price'] = $coupon['use_min_price'];
                $data[$i]['add_time'] = $time;
                if ($coupon['coupon_time']) {
                    $data[$i]['start_time'] = $time;
                    $data[$i]['end_time'] = $time + $coupon['coupon_time'] * 86400;
                } else {
                    $data[$i]['start_time'] = $coupon['start_use_time'];
                    $data[$i]['end_time'] = $coupon['end_use_time'];
                }
                $data[$i]['type'] = 'send';
                $issueData[$i]['uid'] = $v;
                $issueData[$i]['issue_coupon_id'] = $coupon['id'];
                $issueData[$i]['add_time'] = time();
                $i++;
            }
        }
        $res = $this->transaction(function () use ($data, $issueData, $storeCouponUser, $storeCouponIssueUser) {
            $res1 = $res2 = true;
            if ($data) {
                $res1 = $storeCouponUser->saveAll($data);
            }
            if ($issueData) {
                $res2 = $storeCouponIssueUser->saveAll($issueData);
            }
            return $res1 && $res2;
        });
        if (!$res) {
            //发券失败后将队列状态置为失败
            $queueService->setQueueFail($queueId['id'], $redisKey);
        } else {
            //发券成功的用户踢出集合
            $queueService->doSuccessSremRedis($user, $redisKey, $queueId['type']);
        }
        return true;
    }

    /**
     * 获取下单可使用的优惠券列表
     * @param int $uid
     * @param $cartId
     * @param string $price
     * @param bool $new
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function beUsableCouponList(int $uid, $cartId, bool $new)
    {
        /** @var StoreCartServices $services */
        $services = app()->make(StoreCartServices::class);
        $cartGroup = $services->getUserProductCartListV1($uid, $cartId, $new, [], 1, 0, 0, true);
        /** @var StoreCouponUserServices $coupServices */
        $coupServices = app()->make(StoreCouponUserServices::class);
        return $coupServices->getUsableCouponList($uid, $cartGroup);
    }

    /**获取单个优惠券类型
     * @param array $where
     * @return mixed
     */
    public function getOne(array $where)
    {
        if (!$where) throw new AdminException('参数缺失!');
        return $this->dao->getOne($where);

    }

    /**
     * 俩时间相差月份
     * @param $date1
     * @param $date2
     * @return float|int
     */
    public function getMonthNum($date1, $date2)
    {
        $date1_stamp = strtotime($date1);
        $date2_stamp = strtotime($date2);
        list($date_1['y'], $date_1['m']) = explode("-", date('Y-m', $date1_stamp));
        list($date_2['y'], $date_2['m']) = explode("-", date('Y-m', $date2_stamp));
        return abs($date_1['y'] - $date_2['y']) * 12 + $date_2['m'] - $date_1['m'];
    }

    /**
     * 给会员发放优惠券
     * @param $uid
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendMemberCoupon($uid, $couponId = 0)
    {
        if (!$uid) return false;
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        /** @var StoreCouponUserServices $couponUserService */
        $couponUserService = app()->make(StoreCouponUserServices::class);
        /** @var MemberRightServices $memberRightService */
        $memberRightService = app()->make(MemberRightServices::class);
        /** @var MemberCardServices $memberCardService */
        $memberCardService = app()->make(MemberCardServices::class);
        //看付费会员是否开启
        $isOpenMember = $memberCardService->isOpenMemberCardCache();
        if (!$isOpenMember) return false;
        $userInfo = $userService->getUserInfo((int)$uid);
        //看是否会员过期
        if ($userInfo['is_ever_level'] == 0 && $userInfo['is_money_level'] > 0 && $userInfo['overdue_time'] < time()) {
            return false;
        }
        //看是否开启会员送券
        $isSendCoupon = $memberRightService->getMemberRightStatus("coupon");
        if (!$isSendCoupon) return false;
        if ($userInfo && (($userInfo['is_money_level'] > 0) || $userInfo['is_ever_level'] == 1)) {
            $monthNum = $this->getMonthNum(date('Y-m-d H:i:s', time()), date('Y-m-d H:i:s', $userInfo['overdue_time']));
            if ($couponId) {//手动点击领取
                $couponWhere['id'] = $couponId;
                $couponInfo = $this->getMemberCouponIssueList($couponWhere);
            } else {//主动批量发放
                $couponWhere['status'] = 1;
                $couponWhere['receive_type'] = 4;
                $couponWhere['is_del'] = 0;
                $couponInfo = $this->getMemberCouponIssueList($couponWhere);
            }
            if ($couponInfo) {
                $couponIds = array_column($couponInfo, 'id');
                $couponUserMonth = $couponUserService->memberCouponUserGroupBymonth(['uid' => $uid, 'couponIds' => $couponIds]);
                $getTime = array();
                if ($couponUserMonth) {
                    $getTime = array_column($couponUserMonth, 'num', 'time');
                }
                // 判断这个月是否领取过,而且领全了
                //if (in_array(date('Y-m', time()), $getTime)) return false;
                $timeKey = date('Y-m', time());
                if (array_key_exists($timeKey, $getTime) && $getTime[$timeKey] == count($couponIds)) return false;
                //判断是否领完所有月份
                if (count($getTime) >= $monthNum && (array_key_exists($timeKey, $getTime) && $getTime[$timeKey] == count($couponIds)) && $userInfo['is_ever_level'] != 1 && $monthNum > 0) return false;
                foreach ($couponInfo as $cv) {
                    //看之前是否手动领取过某一张，领取过就不再领取。
                    $couponUser = $couponUserService->getUserCounponByMonth(['uid' => $uid, 'cid' => $cv['id']]);
                    if (!$couponUser) {
                        $this->memberIssueUserCoupon($cv['id'], $uid);
                    }
                }

            }
        }
        return true;
    }

    /**
     * 获取今日新增优惠券
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTodayCoupon($uid)
    {
        $list = $this->dao->getTodayCoupon($uid);
        foreach ($list as $key => &$item) {
            $item['start_time'] = $item['start_time'] ? date('Y/m/d', $item['start_time']) : 0;
            $item['end_time'] = $item['end_time'] ? date('Y/m/d', $item['end_time']) : 0;
            $item['coupon_price'] = floatval($item['coupon_price']);
            $item['use_min_price'] = floatval($item['use_min_price']);
            if (isset($item['used']) && $item['used']) {
                unset($list[$key]);
            }
        }
        return array_merge($list);
    }

    /**
     * 获取新人券
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getNewCoupon()
    {
        $list = $this->dao->getNewCoupon();
        foreach ($list as &$item) {
            $item['start_time'] = $item['start_time'] ? date('Y/m/d', $item['start_time']) : 0;
            $item['end_time'] = $item['end_time'] ? date('Y/m/d', $item['end_time']) : 0;
            $item['coupon_price'] = floatval($item['coupon_price']);
            $item['use_min_price'] = floatval($item['use_min_price']);
        }
        return $list;
    }

    /**
     * 获取可以使用优惠券
     * @param int $uid
     * @param array $cartList
     * @param array $promotions
     * @param bool $isMax
     * @return array
     */
    public function getCanUseCoupon(int $uid, array $cartList = [], array $promotions = [], bool $isMax = true)
    {
        $userInfo = [];
        if ($uid) {
            /** @var UserServices $userService */
            $userService = app()->make(UserServices::class);
            $userInfo = $userService->getUserCacheInfo($uid);
        }
        if ($userInfo && isset($userInfo['is_money_level']) && $userInfo['is_money_level'] > 0) {
            $where = ['receive_type' => [1, 4]];
        } else {
            $where = ['receive_type' => 1];
        }
        /** @var StoreCouponUserServices $coupServices */
        $coupServices = app()->make(StoreCouponUserServices::class);
        //用户持有有效券
        $userCoupons = $coupServices->getUserAllCoupon($uid);
        if ($userCoupons) {
            $where['not_id'] = array_column($userCoupons, 'cid');
        }
        $counpons = $this->dao->getValidList($where, 0, 0, ['used' => function ($query) use ($uid) {
                        $query->where('uid', $uid);
                    }]);
        //用户已经领取的 && 没有领取的
        $counpons = array_merge($counpons, $userCoupons);
        $result = [];
        if ($counpons) {
            $promotionsList = [];
            if($promotions){
                $promotionsList = array_combine(array_column($promotions, 'id'), $promotions);
            }
            $isOverlay = function($cart) use ($promotionsList) {
                if (isset($cart['promotions_id']) && $cart['promotions_id']) {
                    foreach ($cart['promotions_id'] as $key => $promotions_id) {
                        $promotions = $promotionsList[$promotions_id] ?? [];
                        if ($promotions && $promotions['promotions_type'] != 4){
                            $overlay = is_string($promotions['overlay']) ? explode(',', $promotions['overlay']) : $promotions['overlay'];
                            if (!in_array(5, $overlay)) {
                                return false;
                            }
                        }
                    }
                }
                return true;
            };
            foreach ($counpons as $coupon) {
                if (isset($coupon['used']) && $coupon['used']) {//所有优惠券中 已经领取跳过 
                    continue;
                }
                $price = 0;
                $count = 0;
                if (isset($coupon['applicable_type'])) {//已经领取 有效优惠券
                    $coupon['type'] = $coupon['applicable_type'];
                    $coupon['used'] = ['id' => $coupon['id'], 'cid' => $coupon['cid']];
                }
                //&& in_array($promotions['promotions_type'], $overlay)
                switch ($coupon['type']) {
                    case 0:
                    case 3:
                        foreach ($cartList as $cart) {
                            if (!$isOverlay($cart))  continue;
                            $price = bcadd((string)$price, (string)bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 2), 2);
                            $count++;
                        }
                        break;
                    case 1://品类券
                        /** @var StoreCategoryServices $storeCategoryServices */
                        $storeCategoryServices = app()->make(StoreCategoryServices::class);
                        $cateGorys = $storeCategoryServices->getAllById((int)$coupon['category_id']);
                        if ($cateGorys) {
                            $cateIds = array_column($cateGorys, 'id');
                            foreach ($cartList as $cart) {
                                if (!$isOverlay($cart))  continue;
                                if (isset($cart['productInfo']['cate_id']) && array_intersect(explode(',', $cart['productInfo']['cate_id']), $cateIds)) {
                                    $price = bcadd((string)$price, (string)bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 2), 2);
                                    $count++;
                                }
                            }
                        }
                        break;
                    case 2:
                        foreach ($cartList as $cart) {
                            if (!$isOverlay($cart))  continue;
                            if (isset($cart['product_id']) && in_array($cart['product_id'], explode(',', $coupon['product_id']))) {
                                $price = bcadd((string)$price, (string)bcmul((string)$cart['truePrice'], (string)$cart['cart_num'], 2), 2);
                                $count++;
                            }
                        }
                        break;
                }
                if ($count && $coupon['use_min_price'] <= $price) {
                    //满减券
                    if ($coupon['coupon_type'] == 1) {
                        $couponPrice = $coupon['coupon_price'];
                    } else {
                        if ($coupon['coupon_price'] <= 0) {//0折
                            $couponPrice = $price;
                        } else if ($coupon['coupon_price'] >= 100) {
                            $couponPrice = 0;
                        } else {
                            $truePrice = (float)bcmul((string)$price, bcdiv((string)$coupon['coupon_price'], '100', 2), 2);
                            $couponPrice = (float)bcsub((string)$price, (string)$truePrice, 2);
                        }
                    }
                    $coupon['coupon_price'] = floatval($coupon['coupon_price']);
                    $coupon['use_min_price'] = floatval($coupon['use_min_price']);
                    $coupon['true_coupon_price'] = $couponPrice;
                    $result[] = $coupon;
                }
            }
        }
        if ($result) {
            $trueCouponPrice = array_column($result, 'true_coupon_price');
            array_multisort($trueCouponPrice, SORT_DESC, $result); 
        }
        //最优的一个
        if ($isMax) {
            $useCoupon = $result[0] ?? [];
            return $useCoupon;
        }
        return $result;
    }
}
