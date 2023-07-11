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

namespace crmeb\services;

use think\facade\Config;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Class UtilService
 * @package crmeb\services
 */
class UtilService
{
    /**
     *  获取小程序二维码是否生成
     * @param $url
     * @return array
     */
    public static function remoteImage($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        $result = json_decode($result, true);
        if (is_array($result)) return ['status' => false, 'msg' => $result['errcode'] . '---' . $result['errmsg']];
        return ['status' => true];
    }

    /**
     *  修改 https 和 http 移动到common
     * @param $url $url 域名
     * @param int $type 0 返回https 1 返回 http
     * @return string
     */
    public static function setHttpType($url, $type = 0)
    {
        $domainTop = substr($url, 0, 5);
        if ($type) {
            if ($domainTop == 'https') $url = 'http' . substr($url, 5, strlen($url));
        } else {
            if ($domainTop != 'https') $url = 'https:' . substr($url, 5, strlen($url));
        }
        return $url;
    }


    /**
     * 获取二维码
     * @param $url
     * @param $name
     * @return array|bool|string
     */
    public static function getQRCodePath($url, $name)
    {
        if (!strlen(trim($url)) || !strlen(trim($name))) return false;
        try {
            $uploadType = sys_config('upload_type');
            // 没有选择默认使用本地上传
            if (!$uploadType) $uploadType = 1;
            $uploadType = (int)$uploadType;
            $siteUrl = sys_config('site_url');
            if (!$siteUrl) return '请前往后台设置->系统设置->网站域名 填写您的域名格式为：http://域名';
            $info = [];
            $outfiles = Config::get('qrcode.cache_dir');
            $root_outfile = root_path('public/' . $outfiles);
            if (!is_dir($root_outfile))
                mkdir($root_outfile, 0777, true);

            // Create QR code
            $writer = new PngWriter();
            $qrCode = QrCode::create($url)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
                ->setSize(300)
                ->setMargin(10)
                ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->setForegroundColor(new Color(0, 0, 0))
                ->setBackgroundColor(new Color(255, 255, 255));
            $writer->write($qrCode)->saveToFile($root_outfile . $name);

            if ($uploadType === 1) {
                $info["code"] = 200;
                $info["name"] = $name;
                $info["dir"] = '/' . $outfiles . '/' . $name;
                $info["time"] = time();
                $info['size'] = 0;
                $info['type'] = 'image/png';
                $info["image_type"] = 1;
                $info['thumb_path'] = '/' . $outfiles . '/' . $name;
                return $info;
            } else {
                $upload = UploadService::init($uploadType);
                $content = file_get_contents($root_outfile . $name);

                $res = $upload->to($outfiles)->validate()->setAuthThumb(false)->stream($content, $name);
                if ($res === false) {
                    return $upload->getError();
                }
                $info = $upload->getUploadInfo();
                $info['image_type'] = $uploadType;
                return $info;
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
