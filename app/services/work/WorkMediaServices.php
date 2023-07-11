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

namespace app\services\work;


use app\dao\work\WorkMediaDao;
use app\services\BaseServices;
use crmeb\basic\BaseModel;
use crmeb\services\FileService;
use crmeb\services\wechat\Work;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use GuzzleHttp\Exception\GuzzleException;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\Model;

/**
 * 企业微信素材
 * Class WorkMediaServices
 * @package app\services\work
 */
class WorkMediaServices extends BaseServices
{

    /**
     * WorkMediaServices constructor.
     * @param WorkMediaDao $dao
     */
    public function __construct(WorkMediaDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取附件资源
     * @param string $url
     * @param string $type
     * @param int $uploadType
     * @return array|bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws GuzzleException
     * @throws InvalidConfigException
     * @throws ModelNotFoundException
     */
    public function getPathMediaInfo(string $url, string $type, int $uploadType = 0)
    {
        $pathInfo = parse_url($url);
        $path = $pathInfo['path'];
        $mediaInfo = [];
        $md5Path = md5($path);
        $info = $this->dao->get(['md5_path' => $md5Path, 'type' => $type, 'upload_type' => $uploadType], ['media_id', 'url', 'temporary']);
        if ($info) {
            if ($info->temporary && $info->media_id) {
                $mediaInfo = $info->toArray();
            }
            if (((int)$info->temporary) === 0) {
                $mediaInfo = $info->toArray();
            }
        }

        if (!$mediaInfo) {
            $pathUrl = public_path() . $path;
            if (is_file($pathUrl)) {
                $uploadInfo = $this->mediaUpload($uploadType, $pathUrl, $type, $md5Path, $info);
                $mediaInfo['media_id'] = $uploadInfo['media_id'];
            } else {
                //获取文件内容
                $stream = file_get_contents($url);

                //创建文件路径
                $dir = public_path() . 'uploads' . DS . 'temp';
                try {
                    FileService::mkDir($dir);
                } catch (\Throwable $e) {
                    throw new ValidateException($e->getMessage());
                }

                //把文件流保存到本地
                $pathUrl = $dir . DS . basename($url);
                file_put_contents($pathUrl, $stream);

                //上传到素材附件
                $uploadInfo = $this->mediaUpload($uploadType, $pathUrl, $type, $md5Path, $info);
                unlink($pathUrl);

                $mediaInfo['media_id'] = $uploadInfo['media_id'];

            }
        }
        return $mediaInfo;
    }

    /**
     * 上传临时素材
     * @param int $uploadType
     * @param string $pathUrl
     * @param string $type
     * @param string $md5Path
     * @param $info
     * @return BaseModel|Model
     * @throws GuzzleException
     * @throws InvalidConfigException
     */
    public function mediaUpload(int $uploadType, string $pathUrl, string $type, string $md5Path, $info)
    {
        if ($uploadType) {
            $resMedia = Work::mediaUploadAttachment($pathUrl, $type);
        } else {
            $resMedia = Work::mediaUpload($pathUrl, $type);
        }
        if ($info) {
            $info->media_id = $resMedia['media_id'];
            $info->valid_time = (int)$resMedia['created_at'] + 60 * 60 * 24 * 3 - 60;//3天有效期
            $info->save();
            return $info;
        } else {
            return $this->dao->save([
                'path' => $pathUrl,
                'md5_path' => $md5Path,
                'type' => $type,
                'upload_type' => $uploadType,
                'media_id' => $resMedia['media_id'],
                'valid_time' => (int)$resMedia['created_at'] + 60 * 60 * 24 * 3 - 60,//3天有效期
                'create_time' => time(),
                'temporary' => 1
            ]);
        }
    }


    /**
     * 获取欢迎语
     * @param array $welcome
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws GuzzleException
     * @throws InvalidConfigException
     * @throws ModelNotFoundException
     */
    public function resolvingWelcome(array $welcome, int $uploadType = 0)
    {
        //清除过期的附件
        $this->dao->deleteValidFile();

        $attachments = [];
        foreach ($welcome['attachments'] as $item) {
            switch ($item['msgtype']) {
                case 'image':
                    $mediaInfo = $this->getPathMediaInfo($item['image']['pic_url'], 'image', $uploadType);
                    if (!empty($mediaInfo['media_id'])) {
                        $item['image']['media_id'] = $mediaInfo['media_id'];
                        unset($item['image']['pic_url']);
                        $attachments[] = $item;
                    }
                    break;
                case 'link':

                    break;
                case 'miniprogram':
                    $mediaInfo = $this->getPathMediaInfo($item['miniprogram']['pic_url'], 'image', $uploadType);
                    if (!empty($mediaInfo['media_id'])) {
                        $item['miniprogram']['pic_media_id'] = $mediaInfo['media_id'];
                        unset($item['miniprogram']['pic_url']);
                        $attachments[] = $item;
                    }
                    break;
                case 'video':
                    $mediaInfo = $this->getPathMediaInfo($item['video']['pic_url'], 'video', $uploadType);
                    if (!empty($mediaInfo['media_id'])) {
                        $item['video']['media_id'] = $mediaInfo['media_id'];
                        unset($item['video']['pic_url']);
                        $attachments[] = $item;
                    }
                    break;
                case 'file':
                    $mediaInfo = $this->getPathMediaInfo($item['file']['pic_url'], 'file', $uploadType);
                    if (!empty($mediaInfo['media_id'])) {
                        $item['file']['media_id'] = $mediaInfo['media_id'];
                        unset($item['file']['pic_url']);
                        $attachments[] = $item;
                    }
                    break;
            }
        }

        return [
            'text' => [
                'content' => $welcome['text']['content'],
            ],
            'attachments' => $attachments
        ];
    }
}
