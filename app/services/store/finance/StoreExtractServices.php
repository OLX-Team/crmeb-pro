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

namespace app\services\store\finance;


use app\dao\store\finance\StoreExtractDao;
use app\dao\store\StoreUserDao;
use app\services\BaseServices;
use app\services\store\SystemStoreServices;
use app\services\store\SystemStoreStaffServices;
use app\services\system\admin\SystemAdminServices;
use crmeb\services\FormBuilder as Form;
use think\exception\ValidateException;
use think\facade\Route as Url;

/**
 * 门店提现
 * Class StoreExtractServices
 * @package app\services\store\finance
 */
class StoreExtractServices extends BaseServices
{
    /**
     * 构造方法
     * StoreUser constructor.
     * @param StoreUserDao $dao
     */
    public function __construct(StoreExtractDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取一条提现记录
     * @param int $id
     * @param array $field
     * @return array|\think\Model|null
     */
    public function getExtract(int $id, array $field = [])
    {
        return $this->dao->get($id, $field);
    }

    /**
     * 显示资源列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index(array $where, array $whereData = [])
    {
        $list = $this->getStoreExtractList($where);
        //待审核金额
        $where['status'] = 0;
        $extract_statistics['price'] = $this->dao->getExtractMoneyByWhere($where, 'extract_price');

        //待转账金额
        $where['status'] = 1;
        $where['pay_status'] = 0;
        $extract_statistics['unPayPrice'] = $this->dao->getExtractMoneyByWhere($where, 'extract_price');
        //累计提现
        $where['status'] = 1;
        $where['pay_status'] = 1;
        $extract_statistics['paidPrice'] = $this->dao->getExtractMoneyByWhere($where, 'extract_price');
        $extract_statistics['price_count'] = 0;
        //未提现金额
        /** @var StoreFinanceFlowServices $storeFinanceFlowServices */
        $storeFinanceFlowServices = app()->make(StoreFinanceFlowServices::class);
		$price_not = $storeFinanceFlowServices->getSumFinance(['store_id' => isset($where['store_id']) && $where['store_id'] ? $where['store_id'] : 0], $whereData);
        $extract_statistics['price_not'] = max($price_not, 0);
//        $extract_statistics['price_not'] = $extract_statistics['price_count'] > $extract_statistics['paidPrice'] ? bcsub((string)$extract_statistics['price_count'], (string)$extract_statistics['paidPrice'], 2) : 0.00;
        return compact('extract_statistics', 'list');
    }


    /**
     * 获取提现列表
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStoreExtractList(array $where, string $field = '*')
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getExtractList($where, $field, ['store'], $page, $limit);
        if ($list) {
            /** @var SystemAdminServices $adminServices */
            $adminServices = app()->make(SystemAdminServices::class);
            $adminIds = array_unique(array_column($list, 'admin_id'));
            $adminInfos = [];

            if ($adminIds) $adminInfos = $adminServices->getColumn([['id', 'in', $adminIds]], 'id,real_name', 'id');
            foreach ($list as &$item) {
                $item['add_time'] = $item['add_time'] ? date("Y-m-d H:i:s", $item['add_time']) : '';
                $item['admin_name'] = $item['admin_id'] ? ($adminInfos[$item['admin_id']]['real_name'] ?? '') : '';
            }
        }

        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 提现申请
     * @param int $store_id
     * @param int $store_staff_id
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cash(int $store_id, int $store_staff_id, array $data)
    {
        /** @var SystemStoreServices $systemStore */
        $systemStore = app()->make(SystemStoreServices::class);
        $storeInfo = $systemStore->getStoreInfo($store_id);
        /** @var SystemStoreStaffServices $systemStoreStaff */
        $systemStoreStaff = app()->make(SystemStoreStaffServices::class);
        $staffInfo = $systemStoreStaff->getStaffInfo($store_staff_id);

        $userExtractMinPrice = sys_config('user_extract_min_price');
        if ($data['money'] < $userExtractMinPrice) {
            throw new ValidateException('提现金额不能小于' . $userExtractMinPrice . '元');
        }
        $insertData = [];
        switch ($data['extract_type']) {
            case 'bank':
                if (!$storeInfo['bank_code'] || !$storeInfo['bank_address']) {
                    throw new ValidateException('请先设置提现银行与开户行信息');
                }
                $insertData['bank_code'] = $storeInfo['bank_code'];
                $insertData['bank_address'] = $storeInfo['bank_address'];
                break;
            case 'alipay':
                if (!$storeInfo['alipay_account'] || !$storeInfo['alipay_qrcode_url']) {
                    throw new ValidateException('请先设置提现支付宝信息');
                }
                $insertData['alipay_account'] = $storeInfo['alipay_account'];
                $insertData['qrcode_url'] = $storeInfo['alipay_qrcode_url'];
                break;
            case 'weixin':
                if (!$storeInfo['wechat'] || !$storeInfo['wechat_qrcode_url']) {
                    throw new ValidateException('请先设置提现微信信息');
                }
                $insertData['wechat'] = $storeInfo['wechat'];
                $insertData['qrcode_url'] = $storeInfo['wechat_qrcode_url'];
                break;
            default:
                throw new ValidateException('暂不支持该类型提现');
                break;
        }
        $insertData['store_id'] = $storeInfo['id'];
        $insertData['store_staff_id'] = $staffInfo['id'];
        $insertData['extract_type'] = $data['extract_type'];
        $insertData['extract_price'] = $data['money'];
        $insertData['add_time'] = time();
        $insertData['store_mark'] = $data['mark'];
        $insertData['status'] = 0;
        if (!$this->dao->save($insertData)) {
            return false;
        }
        return true;
    }


    /**
     * 拒绝
     * @param $id
     * @return mixed
     */
    public function refuse(int $id, string $message, int $adminId)
    {
        $extract = $this->getExtract($id);
        if (!$extract) {
            throw new ValidateException('操作记录不存在!');
        }
        if ($extract->status == 1) {
            throw new ValidateException('已经提现,错误操作');
        }
        if ($extract->status == -1) {
            throw new ValidateException('您的提现申请已被拒绝,请勿重复操作!');
        }
        if ($this->dao->update($id, ['fail_time' => time(), 'fail_msg' => $message, 'status' => -1, 'admin_id' => $adminId])) {
            return true;
        } else {
            throw new ValidateException('操作失败!');
        }
    }

    /**
     * 通过
     * @param $id
     * @return mixed
     */
    public function adopt(int $id, int $adminId)
    {
        $extract = $this->getExtract($id);
        if (!$extract) {
            throw new ValidateException('操作记录不存!');
        }
        if ($extract->status == 1) {
            throw new ValidateException('您已提现,请勿重复提现!');
        }
        if ($extract->status == -1) {
            throw new ValidateException('您的提现申请已被拒绝!');
        }
        if ($this->dao->update($id, ['status' => 1, 'admin_id' => $adminId])) {
            return true;
        } else {
            throw new ValidateException('操作失败!');
        }
    }

    /**
     * 转账页面
     * @param int $id
     * @return string
     */
    public function add_transfer(int $id)
    {
        $field = array();
        $title = '转账信息';
//        $field[] = Form::hidden('id', $id);
        $field[] = Form::input('voucher_title', '转账说明', '')->maxlength(30)->required();
        $field[] = Form::frameImage('voucher_image', '转账凭证', Url::buildUrl('admin/widget.images/index', array('fodder' => 'voucher_image')), '')->icon('ios-add')->width('960px')->height('505px')->modal(['footer-hide' => true]);
        return create_form($title, $field, Url::buildUrl('/store/extract/save_transfer/' . $id), 'POST');
    }
}
