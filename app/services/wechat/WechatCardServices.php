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

namespace app\services\wechat;


use app\dao\wechat\WechatCardDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\DownloadImageService;
use crmeb\services\FormBuilder;
use crmeb\services\SystemConfigService;
use crmeb\services\wechat\OfficialAccount;
use think\facade\Log;

/**
 * 微信卡券
 * Class WechatCardServices
 * @package app\services\wechat
 */
class WechatCardServices extends BaseServices
{
    protected $builder;

    protected $code_type = [
        'CODE_TYPE_NONE' => '文本',
        'CODE_TYPE_BARCODE' => '一维码',
        'CODE_TYPE_QRCODE' => '二维码',
        'CODE_TYPE_ONLY_QRCODE' => '仅显示二维码',
        'CODE_TYPE_ONLY_BARCODE' => '仅显示一维码',
        'CODE_TYPE_NONE' => '不显示任何码型'
    ];

    /**
     * 构造方法
     * WechatCardServices constructor.
     * @param WechatCardDao $dao
     */
    public function __construct(WechatCardDao $dao, FormBuilder $formBuilder)
    {
        $this->dao = $dao;
        $this->builder = $formBuilder;
    }


    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $count = $this->dao->count($where);
        $list = $this->dao->getList($where, $page, $limit);
        return compact('count', $list);
    }

    /**
     * 获取微信会员卡信息
     * @return array
     */
    public function getInfo()
    {
        $data = [];
        try {
            $data['color'] = OfficialAccount::getCardColors()['colors'] ?? [];
        } catch (\Throwable $e) {
            $data['color'] = [];
        }
        $info = $this->dao->getOne(['card_type' => 'member_card', 'status' => 1, 'is_del' => 0]);
        $data['info'] = ['custom_cell' => []];
        $data['selet'] = 0;
        if ($info) {
            $info = $info->toArray();
            $info['custom_cell'] = $info['especial']['custom_cell'] ?? [];
            $data['info'] = $info;
            $data['selet'] = $info['background_pic_url'] ? 1 : 0;

        }
        return $data;
    }

    public function createForm($formData)
    {
        $f[] = $this->builder->input('brand_name', '商户名称', $formData['brand_name'] ?? '')->required('请填写名称');
        $f[] = $this->builder->input('title', '卡券名称', $formData['title'] ?? '')->required('请填写卡券名称');
        $f[] = $this->builder->input('service_phone', '电话', $formData['service_phone'] ?? '')->required('请填写电话');
        $f[] = $this->builder->select('code_type', 'CODE展示类型', $formData['code_type'])->setOptions(FormBuilder::setOptions($this->code_type))->multiple(true)->required('请选择CODE展示类型');
        $f[] = $this->builder->input('color', '卡券颜色', $formData['color'] ?? '')->required('请填写卡券颜色');
        $f[] = $this->builder->frameImage('logo_url', 'LOGO', $this->url('admin/widget.images/index', ['fodder' => 'logo_url'], true), $formData['logo_url'] ?? '')->info('建议300*300')->icon('ios-add')->width('960px')->height('550px')->modal(['footer-hide' => true]);
        $f[] = $this->builder->frameImage('background_pic_url', '背景图', $this->url('admin/widget.images/index', ['fodder' => 'background_pic_url'], true), $formData['background_pic_url'] ?? '')->info('建议1000*600')->icon('ios-add')->width('960px')->height('550px')->modal(['footer-hide' => true]);
        $f[] = $this->builder->input('notice', '提示', $formData['notice'] ?? '')->required('请填写提示信息');
        $f[] = $this->builder->input('description', '描述', $formData['description'] ?? '')->required('请填写描述');
        $f[] = $this->builder->textarea('prerogative', '卡券特权说明', $formData['notice'] ?? '')->info('会员卡特权说明,限制1024汉字')->required('请填写卡券特权说明');
        return create_form('微信卡券添加', $f, $this->url('/wechat/card/0'), 'post');
    }

    /**
     * 添加｜编辑微信卡券
     * @param int $id
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function save(array $data)
    {
        $card = $this->dao->getOne(['card_type' => 'member_card', 'status' => 1, 'is_del' => 0]);
        if ($card) {
            [$cardInfo, $especial] = $this->wxCreate($data, $card['card_id']);
            unset($data['custom_cell']);
            $data['especial'] = $especial;
            if (!$this->dao->update($card['id'], $data)) {
                throw new AdminException('修改失败');
            }
        } else {
            [$cardInfo, $especial] = $this->wxCreate($data);
            $this->activateUserForm($cardInfo['card_id']);
            unset($data['custom_cell']);
            $data['card_id'] = $cardInfo['card_id'];
            $data['especial'] = $especial;
            if (!$this->dao->save($data)) {
                throw new AdminException('添加失败');
            }
        }
        return true;
    }

    /**
     * 微信卡券添加｜编辑
     * @param array $card
     * @param string $card_id
     * @param string $card_type
     * @return array
     * @throws \EasyWeChat\Core\Exceptions\InvalidArgumentException
     */
    public function wxCreate(array $card, string $card_id = '', string $card_type = 'member_card')
    {
        $baseUrl = sys_config('site_url');
        try {
            $logo = root_path() . 'public' . app()->make(DownloadImageService::class)->downloadImage($card['logo_url'])['path'];
            $background = $card['background_pic_url'] ? (root_path() . 'public' . app()->make(DownloadImageService::class)->downloadImage($card['background_pic_url'])['path']) : '';
        } catch (\Throwable $e) {
            Log::error('添加会员卡券封面图出错误，原因：' . $e->getMessage());
            $logo = root_path() . 'public' . $card['logo_url'];
            $background = root_path() . 'public' . $card['background_pic_url'];
        }
        $base_info = [
            'logo_url' => OfficialAccount::temporaryUpload($logo)->media_id,
            'brand_name' => $card['brand_name'],
            'code_type' => 'CODE_TYPE_NONE',
            'title' => $card['title'],
            'color' => $card['color'] ?: 'Color010',
            'notice' => $card['notice'],
            'service_phone' => $card['service_phone'],
            'description' => $card['description'],
            'use_custom_code' => false,
            'can_share' => true,
            'can_give_friend' => false,
            'get_limit' => 1,
            'date_info' => ['type' => 'DATE_TYPE_PERMANENT'],
            'sku' => ['quantity' => 50000000],
            'promotion_url' => $baseUrl . '/pages/users/user_spread_user/index',
            'promotion_url_name' => '推荐给朋友',
            'promotion_url_sub_title' => ''
        ];
        if ($card['center_title']) {
            $base_info = array_merge($base_info, [
                'center_title' => $card['center_title'],
                'center_sub_title	' => $card['center_sub_title'],
                'center_url' => $card['center_url'],
            ]);
        }
        $integral = SystemConfigService::more(['integral_max_num', 'integral_ratio', 'order_give_integral']);
        $especial = [
//            'name' => $card['title'],//入口名称
//            'tips' => $card['description'],//入口提示语
//            'url' => $baseUrl,//入口跳转地址
            'supply_bonus' => true,//显示积分
            'bonus_rule' => [
                'cost_money_unit' => 100,//消费金额。以分为单位
                'increase_bonus' => $integral['order_give_integral'],//对应增加的积分
                'max_increase_bonus' => $integral['integral_max_num'],//用户单次可获取的积分上限
                'cost_bonus_unit' => 1,//每使用X积分
                'reduce_money' => bcmul($integral['integral_ratio'], '100', 0),//抵扣xx元，（这里以分为单位）
            ],
            'supply_balance' => false,//是否支持充值
//            'activate_url' => $baseUrl,
            'bonus_url' => $baseUrl . '/pages/users/user_integral/index',//积分详情跳转链接
            'prerogative' => $card['prerogative'],
//            'auto_activate' => false,
            'wx_activate' => true,
            'background_pic_url' => $background ? OfficialAccount::temporaryUpload($background)->media_id : '',
            'custom_field2' => [
                'name_type' => 'FIELD_NAME_TYPE_TIMS',//消费次数
                'url' => $baseUrl . '/pages/users/order_list/index'
            ],
            'custom_field3' => [
                'name_type' => 'FIELD_NAME_TYPE_COUPON',//优惠券
                'url' => $baseUrl . '/pages/users/user_coupon/index'
            ],
        ];
        if ($card['custom_cell']) {
            $cell_data = [];
            $i = 1;
            foreach ($card['custom_cell'] as $item) {
                $cell_data['custom_cell' . $i] = $item;
                $i++;
            }
            $especial = array_merge($especial, $cell_data);
        }
        @unlink($logo);
        @unlink($background);
        if ($card_id) {
            unset($especial['bonus_rule']);
            unset($base_info['brand_name'], $base_info['sku'], $base_info['use_custom_code']);
            return [OfficialAccount::updateCard($card_id, $card_type, $base_info, $especial), array_merge($especial, ['custom_cell' => $card['custom_cell']])];
        } else {
            return [OfficialAccount::createCard($card_type, $base_info, $especial), array_merge($especial, ['custom_cell' => $card['custom_cell']])];
        }

    }


    /**
     * 创建会员卡激活表单
     * @param string $cardId
     * @return \EasyWeChat\Support\Collection
     */
    public function activateUserForm(string $cardId)
    {
        $requireForm = [
            'required_form' => [
				'can_modify' => false,
                'common_field_id_list' => [
                    'USER_FORM_INFO_FLAG_MOBILE'
                ]
            ]
        ];
        $optionFrom = [
            'optional_form' => [
				'can_modify' => false,
                'common_field_id_list' => [
                    'USER_FORM_INFO_FLAG_BIRTHDAY'
                ]
            ]
        ];
        return OfficialAccount::cardActivateUserForm($cardId, $requireForm, $optionFrom);
    }

}
