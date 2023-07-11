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

namespace crmeb\services\wechat;

use EasyWeChat\Kernel\Support\Collection;

/**
 * 微信错误
 * Class WechatResponse
 * @package crmeb\services\wechat
 */
class WechatResponse extends Collection
{

    /**
     * @var \Throwable
     */
    protected $e;

    /**
     * @var string
     */
    protected $response;

    /**
     * 是否抛出默认错误
     * @var bool
     */
    protected $error = true;

    /**
     * WechatResponse constructor.
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        parent::__construct($items);

        $this->wechatError();
    }

    /**
     * 错误统一处理
     */
    public function wechatError()
    {
        if (!$this->error) {
            return;
        }
        if (isset($this->items['errcode']) && 0 !== $this->items['errcode']) {
            throw new WechatException(
                ErrorMessage::getWorkMessage(
                    $this->items['errcode'] ?? 0,
                    $this->items['errmsg'] ?? null
                )
            );
        }
    }

    /**
     * @param bool $boole
     * @return $this
     */
    public function serError(bool $boole)
    {
        $this->error = $boole;
        return $this;
    }

    /**
     * 正确处理
     * @param callable $then
     * @param bool $error
     * @return $this
     */
    public function then(callable $then, bool $error = null)
    {
        $error = $error ?: $this->error;
        if (0 !== $this->items['errcode'] && $error) {
            throw new WechatException($this->items['errmsg']);
        }
        try {
            $this->response = $then(new static($this->items));
        } catch (\Throwable $e) {
            $this->e = $e;
        }
        return $this;
    }

    /**
     * 异常处理
     * @param callable $catch
     * @return $this
     */
    public function catch(callable $catch)
    {

        if (!$this->e) {
            $this->e = new WechatException('success');
        }

        $catch($this->e, $this->items);

        return $this;
    }

    /**
     * 获取返回值
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }
}
