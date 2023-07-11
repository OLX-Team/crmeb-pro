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
namespace crmeb\services\template\storage;

use app\services\message\TemplateMessageServices;
use crmeb\basic\BaseMessage;
use crmeb\services\CacheService;
use crmeb\services\wechat\OfficialAccount;
use EasyWeChat\Kernel\Support\Collection;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use think\facade\Log;

/**
 * 公众号模板消息
 * Class Wechat
 * @package crmeb\services\template\storage
 */
class Wechat extends BaseMessage
{
    /**
     * 初始化
     * @param array $config
     * @return mixed|void
     */
    protected function initialize(array $config)
    {
        parent::initialize($config);
    }

    /**
     * @param string $templateId
     * @return mixed
     * @throws \throwable
     */
    public function getTempId(string $templateId)
    {
        /** @var TemplateMessageServices $services */
        $services = app()->make(TemplateMessageServices::class);
        return CacheService::handler('TEMPLATE')->remember('wechat_' . $templateId, function () use ($services, $templateId) {
            return $services->getTempId($templateId, 1);
        });
    }

    /**
     * 发送消息
     * @param string $templateId
     * @param array $data
     * @return bool|mixed
     * @throws GuzzleException
     */
    public function send(string $templateId, array $data = [])
    {
        $templateId = $this->getTemplateCode($templateId);
        if (!$templateId) {
            return $this->setError('Template number does not exist');
        }
        $tempid = $this->getTempId($templateId);
        if (!$tempid) {
            return $this->setError('Template ID does not exist');
        }
        if (!$this->openId) {
            return $this->setError('Openid does not exist');
        }
        try {
            $res = OfficialAccount::sendTemplate($this->openId, $tempid, $data, $this->toUrl, $this->color);
            $this->clear();
            return $res;
        } catch (\Exception $e) {
            $this->isLog() && Log::error('发送给openid为:' . $this->openId . '微信模板消息失败,模板id为:' . $tempid . ';错误原因为:' . $e->getMessage());
            return $this->setError($e->getMessage());
        }
    }

    /**
     * 获取所有模板
     * @return array|Collection|mixed|object|ResponseInterface|string
     * @throws GuzzleException
     */
    public function list()
    {
        return OfficialAccount::getPrivateTemplates();
    }

    /**
     * 添加模板消息
     * @param string $shortId
     * @return array|Collection|mixed|object|ResponseInterface|string
     * @throws GuzzleException
     */
    public function add(string $shortId)
    {
        return OfficialAccount::addTemplateId($shortId);
    }

    /**
     * 删除模板消息
     * @param string $templateId
     * @return array|Collection|mixed|object|ResponseInterface|string
     * @throws GuzzleException
     */
    public function delete(string $templateId)
    {
        return OfficialAccount::deleleTemplate($templateId);
    }

    /**
     * 返回所有支持的行业列表
     * @return array|Collection|object|ResponseInterface|string
     */
    public function getIndustry()
    {
        return OfficialAccount::getIndustry();
    }
}
