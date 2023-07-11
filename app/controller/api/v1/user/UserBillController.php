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
namespace app\controller\api\v1\user;


use app\Request;
use app\services\other\QrcodeServices;
use app\services\system\attachment\SystemAttachmentServices;
use app\services\system\config\SystemConfigServices;
use app\services\user\UserBillServices;
use crmeb\services\UploadService;
use crmeb\services\wechat\MiniProgram;

/**
 * 账单类
 * Class UserBillController
 * @package app\api\controller\user
 */
class UserBillController
{
    protected $services = NUll;

    /**
     * UserBillController constructor.
     * @param UserBillServices $services
     */
    public function __construct(UserBillServices $services)
    {
        $this->services = $services;
    }

    /**
     * 推广佣金明细
     * @param Request $request
     * @param $type 0 全部  1 消费  2 充值  3 返佣  4 提现
     * @return mixed
     */
    public function spread_commission(Request $request, $type)
    {
        $uid = (int)$request->uid();
        return app('json')->successful($this->services->getUserBillList($uid, $type));
    }

    /**
     * 获取小程序二维码
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRoutineCode(Request $request)
    {
        $user = $request->user();
        /** @var SystemAttachmentServices $systemAttachment */
        $systemAttachment = app()->make(SystemAttachmentServices::class);
        //小程序
        $name = $user['uid'] . '_' . $user['is_promoter'] . '_user_routine.jpg';
        $imageInfo = $systemAttachment->getInfo(['name' => $name]);
        //检测远程文件是否存在
        if (isset($imageInfo['att_dir']) && strstr($imageInfo['att_dir'], 'http') !== false && curl_file_exist($imageInfo['att_dir']) === false) {
            $imageInfo = null;
            $systemAttachment->delete(['name' => $name]);
        }
        $siteUrl = sys_config('site_url');
        if (!$imageInfo) {
            /** @var QrcodeServices $qrCode */
            $qrCode = app()->make(QrcodeServices::class);
            $resForever = $qrCode->qrCodeForever($user['uid'], 'spread', '', '');
            $resCode = MiniProgram::appCodeUnlimit($resForever->id, '', 280);
            if ($resCode) {
                $res = ['res' => $resCode, 'id' => $resForever->id];
            } else {
                $res = false;
            }
            if (!$res) return app('json')->fail('二维码生成失败');
            $uploadType = (int)sys_config('upload_type', 1);
            $upload = UploadService::init($uploadType);
            $uploadRes = $upload->to('routine/spread/code')->validate()->setAuthThumb(false)->stream((string)$res['res'], $name);
            if ($uploadRes === false) {
                return app('json')->fail($upload->getError());
            }
            $imageInfo = $upload->getUploadInfo();
            $imageInfo['image_type'] = $uploadType;
            $systemAttachment->attachmentAdd($imageInfo['name'], $imageInfo['size'], $imageInfo['type'], $imageInfo['dir'], $imageInfo['thumb_path'], 1, $imageInfo['image_type'], $imageInfo['time'], 2);
            $qrCode->setQrcodeFind($res['id'], ['status' => 1, 'url_time' => time(), 'qrcode_url' => $imageInfo['dir']]);
            $urlCode = $imageInfo['dir'];
        } else $urlCode = $imageInfo['att_dir'];
        if ($imageInfo['image_type'] == 1) $urlCode = $siteUrl . $urlCode;
        return app('json')->success(['url' => $urlCode]);
    }

    /**
     * 获取海报详细信息
     * @return mixed
     */
    public function getSpreadInfo(Request $request)
    {
        /** @var SystemConfigServices $systemConfigServices */
        $systemConfigServices = app()->make(SystemConfigServices::class);
        $spreadBanner = $systemConfigServices->getSpreadBanner() ?? [];
        $bannerCount = count($spreadBanner);
        $routineSpreadBanner = [];
        if ($bannerCount) {
            foreach ($spreadBanner as $item) {
                $routineSpreadBanner[] = ['pic' => $item];
            }
        }
        if (sys_config('share_qrcode', 0) && request()->isWechat()) {
            /** @var QrcodeServices $qrcodeService */
            $qrcodeService = app()->make(QrcodeServices::class);
            if (sys_config('spread_share_forever', 0)) {
                $qrcode = $qrcodeService->getForeverQrcode('spread', $request->uid())->url;
            } else {
                $qrcode = $qrcodeService->getTemporaryQrcode('spread', $request->uid())->url;
            }
        } else {
            $qrcode = '';
        }
        return app('json')->success([
            'spread' => $routineSpreadBanner,
            'qrcode' => $qrcode,
            'nickname' => $request->user('nickname'),
            'site_name' => sys_config('site_name')
        ]);
    }

    /**
     * 积分记录
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function integral_list(Request $request)
    {
        $uid = (int)$request->uid();
        $data = $this->services->getIntegralList($uid);
        return app('json')->successful($data['list'] ?? []);
    }
}
