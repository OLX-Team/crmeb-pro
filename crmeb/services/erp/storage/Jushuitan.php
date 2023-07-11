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
namespace crmeb\services\erp\storage;

use crmeb\basic\BaseErp;
use crmeb\exceptions\AdminException;
use crmeb\exceptions\ErpException;
use crmeb\services\erp\storage\jushuitan\Comment;
use crmeb\services\erp\storage\jushuitan\Order;
use crmeb\services\erp\storage\jushuitan\Product;
use crmeb\services\erp\storage\jushuitan\Stock;
use EasyWeChat\Kernel\Support\Str;
use think\Collection;
use think\facade\Cache;
use think\Response;

/**
 * Class Jushuitan
 * @package crmeb\services\erp\storage
 */
class Jushuitan extends BaseErp
{

    //==================商家授权==================

    /**
     * 获取授权参数
     * @param string $state 透传数据
     * @return array
     */
    public function getAuthParams($state = ""): array
    {
        $params = [];

        //开发者应用Key
        $params["app_key"] = $this->accessToken->getAccount();

        //当前请求的时间戳【单位是秒】
        $params["timestamp"] = time();

        //透传数据 非必填
        $params["state"] = $state;

        //交互数据的编码【utf-8】目前只能传utf-8，不能不传！
        $params["charset"] = "utf-8";

        //签名
        $params["sign"] = $this->sign($params);

        //授权跳转地址
        $params["url"] = "https://openweb.jushuitan.com/auth";

        return $params;
    }

    /**
     * 获取AccessToken 用于验证授权回调是否成功
     * @return string
     * @throws \Exception
     */
    public function getAccessToken(): string
    {
        return $this->accessToken->getAccessToken();
    }

    /**
     * 设置AccessToken
     * @param $at
     * @return string
     */
    public function setAccessToken($at): string
    {
        return $this->accessToken->setAccessToken($at, 999999);
    }

    /**
     * 平台授权回调
     * @return Response
     */
    public function authCallback(): Response
    {
        $params = request()->get();
        //验证必要参数   返回失败
        if (!isset($params["app_key"]) || !isset($params["code"]) || !isset($params["sign"])) {
            return response(["code" => 504]);
        }
        $appKey = $params["app_key"];
        $code = $params["code"];        //授权码，有效期为15分钟
        $sign = $params["sign"];
        $state = isset($params["state"]) ? $params["state"] : "";      //透传数据

        //appKey是否匹配    不匹配返回成功-抛弃消息
        if ($appKey !== $this->accessToken->getAccount()) {
            return response(["code" => 0, "msg" => "appKey不匹配"]);
        }

        //sign验证失败   返回失败
        if ($sign !== $this->sign($params)) {
            return response(["code" => 505, "msg" => "签名错误"]);
        }

        //code换access_token
        $request = $this->code2accessToken($code);

        //缓存token
        $this->accessToken->setAccessToken($request["access_token"], $request["expires_in"]);
        //token提前过期时间
        $this->accessToken->setTokenExpire($request["access_token"], $request["expires_in"] - (48 * 60 * 60));
        //缓存刷新token
        $this->accessToken->setRefreshToken($request["refresh_token"]);

        return response(["code" => 0]);
    }

    /**
     * @return bool|mixed|null
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getTokenExpire()
    {
        return $this->accessToken->getTokenExpire();
    }

    /**
     * 授权临时code换access_token
     * @param $code
     * @return array
     */
    public function code2accessToken($code): array
    {
        $url = $this->accessToken->getApiUrl("/openWeb/auth/accessToken");

        //请求参数
        $params = [];

        //开发者应用Key
        $params["app_key"] = $this->accessToken->getAccount();

        //当前请求的时间戳【单位是秒】
        $params["timestamp"] = time();

        //固定值：authorization_code
        $params["grant_type"] = "authorization_code";

        //交互数据的编码【utf-8】目前只能传utf-8，不能不传！
        $params["charset"] = "utf-8";

        //授权码
        $params["code"] = $code;

        //签名
        $params["sign"] = $this->sign($params);

        try {
            $request = $this->accessToken::postRequest($url, $params);
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }
        //处理平台响应异常
        $this->checkRequestError($request);

        return $request["data"];
    }

    /**
     * @param $content
     * @return Collection
     */
    protected function json($content)
    {
        if (false === $content) {
            return collect();
        }
        $data = json_decode($content, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new ErpException(sprintf('Failed to parse JSON: %s', json_last_error_msg()));
        }

        return collect($data);
    }

    /**
     * 通过接口方式授权登录聚水潭
     * @param string $account
     * @param string $password
     * @return bool
     */
    public function authLogin(string $account = null, string $password = null)
    {
        if (Cache::has('erp_login_count') && Cache::get('erp_login_count') > 10) {
            return false;
        }

        $authParams = $this->getAuthParams();

        $loginInfo = $this->accessToken->postRequest('https://api.jushuitan.com/erp/webapi/UserApi/WebLogin/Passport', json_encode([
            'ipAddress' => '',
            'uid' => '',
            'data' => [
                'account' => $account ?: $this->accessToken->getAuthAccount(),
                'j_d_3' => '',
                'password' => $password ?: $this->accessToken->getAuthPassword(),
                'v_d_144' => ''
            ],
        ]), ['Content-Type:application/json; charset=utf-8']);

        $loginInfo = $this->json($loginInfo);

        if ($loginInfo['code'] === null || $loginInfo['code'] !== 0) {

            if ($loginInfo['code'] === 10001) {
                $erpLoginCount = 0;
                if (Cache::has('erp_login_count')) {
                    $erpLoginCount = Cache::get('erp_login_count', 0);
                    $erpLoginCount++;
                }
                Cache::set('erp_login_count', $erpLoginCount, 60);
            }
            throw new ErpException($loginInfo['msg'] ?? '登录失败');
        }

        $cookie = $loginInfo['cookie'];
        $cookieData = [];
        foreach ($cookie as $k => $v) {
            $cookieData[] = $k . '=' . $v;
        }

        $res = $this->accessToken->postRequest('https://api.jushuitan.com/openWeb/auth/oauthAction', json_encode([
            'uid' => '',
            'data' => [
                'app_key' => $authParams['app_key'],
                'charset' => $authParams['charset'],
                'sign' => $authParams['sign'],
                'state' => '',
                'timestamp' => $authParams['timestamp'],
            ],
        ]), [
            'Content-Type:application/json',
            'Cookie:' . implode(';', $cookieData),
        ]);

        $res = $this->json($res);

        if (!$res) {
            throw new ErpException('请求失败');
        }
        if ($res['code'] != 0) {
            throw new ErpException($res['msg'] ?? '授权失败');
        }

        return true;
    }

    /**
     * 刷新access_token
     * @return bool
     */
    public function refreshToken(): bool
    {
        $refreshToken = $this->accessToken->getRefreshToken();
        if (empty($refreshToken)) {
            throw new AdminException("请跳转授权手动授权", 610);
        }

        $url = $this->accessToken->getApiUrl("/openWeb/auth/refreshToken");

        //请求参数
        $params = [];

        //开发者应用Key
        $params["app_key"] = $this->accessToken->getAccount();

        //当前请求的时间戳【单位是秒】
        $params["timestamp"] = time();

        //固定值：refresh_token
        $params["grant_type"] = "refresh_token";

        //交互数据的编码【utf-8】目前只能传utf-8，不能不传！
        $params["charset"] = "utf-8";

        //更新令牌
        $params["refresh_token"] = $refreshToken;

        //固定值：all
        $params["scope"] = "all";

        //签名
        $params["sign"] = $this->sign($params);

        try {
            $request = $this->accessToken::postRequest($url, $params);
        } catch (\Exception $e) {
            throw new AdminException($e->getMessage());
        }

        //处理平台响应异常
        $this->checkRequestError($request);

        //缓存token
        $this->accessToken->setAccessToken($request["access_token"], $request["expires_in"]);
        //token提前过期时间
        $this->accessToken->setTokenExpire($request["access_token"], $request["expires_in"] - (48 * 60 * 60));
        //缓存刷新token
        $this->accessToken->setRefreshToken($request["refresh_token"]);

        return true;
    }


    //==================内部方法==================

    /**
     * 发送post请求并处理异常
     * @param $url
     * @param $params
     * @return mixed
     */
    public function postRequest($url, $params)
    {
        //请求平台接口
        $request = $this->accessToken->postRequest($url, $params);
        $this->checkRequestError($request);

        return $request;
    }

    /**
     * 检测平台响应异常 并将响应转换为数组
     * @param $request
     */
    protected function checkRequestError(&$request)
    {
        if ($request === false || empty($request)) {
            throw new AdminException('平台请求失败，请稍后重试');
        }
        $request = is_string($request) ? json_decode($request, true) : $request;
        if (empty($request) || !isset($request['code'])) {
            throw new AdminException('平台请求失败，请稍后重试！');
        }
        if (intval($request['code']) === 100) {
            throw new AdminException("请重新授权", 610);
        }
        if ($request['code'] != 0) {
            throw new AdminException(isset($request['msg']) ? '平台错误：' . $request['msg'] : '平台错误：发生异常，请稍后重试');
        }
    }

    /**
     * 拼装请求参数
     * @param array $biz
     * @return array
     * @throws \Exception
     * @throws \Exception
     */
    public function getParams(array $biz = []): array
    {
        //请求参数
        $params = [];

        $accessToken = null;
        try {
            $accessToken = $this->accessToken->getAccessToken();
        } catch (\Throwable $e) {
        }

        //刷新token
        if (!$accessToken) {
            $this->refreshToken();
            $accessToken = $this->accessToken->getAccessToken();
        }

        if (!$accessToken) {
            throw new ErpException('缺少access_token,请手动登录聚水潭开放平台进行授权登录');
        }

        //商户授权token值
        $params["access_token"] = $accessToken;

        //开发者应用Key
        $params["app_key"] = $this->accessToken->getAccount();

        //当前请求的时间戳【单位是秒】
        $params["timestamp"] = time();

        //接口版本，当前版本为【2】,目前只能传2，不能不传！
        $params["version"] = "2";

        //交互数据的编码【utf-8】目前只能传utf-8，不能不传！
        $params["charset"] = "utf-8";

        //业务请求参数，格式为jsonString
        if (empty($biz)) {
            $biz = new \ArrayObject();
        }
        $params["biz"] = json_encode($biz, JSON_UNESCAPED_UNICODE);

        //签名
        $params["sign"] = $this->sign($params);

        return $params;
    }

    /**
     * 计算签名
     * @param array $params
     * @return string
     */
    protected function sign(array $params): string
    {
        if (empty($params)) {
            return "";
        }

        //1.将请求参数中除 sign 外的多个键值对，根据键按照字典序排序
        ksort($params);

        //按照 "key1value1key2value2..." 的格式拼成一个字符串。
        $str = "";
        foreach ($params as $k => $v) {
            if ($k == null || $k == "" || $k == "sign" || $v == "") {
                continue;
            }
            if (is_array($v) || is_object($v)) {
                $v = json_encode($v, JSON_UNESCAPED_UNICODE);
            }
            $str .= $k . $v;
        }

        //2.将 app_secret 拼接在 1 中排序后的字符串前面得到待签名字符串
        $str = $this->accessToken->getSecret() . $str;

        //3.使用 MD5 算法加密待加密字符串并转为小写
        return bin2hex(md5($str, true));
    }

    /**
     * @param string $type
     * @return Stock|Order|Product|Comment
     */
    public function serviceDriver(string $type = '')
    {
        $namespace = '\\crmeb\\services\\erp\\storage\\jushuitan\\';
        $class = strpos($type, '\\') ? $type : $namespace . Str::studly($type);
        if (!class_exists($class)) {
            throw new \RuntimeException('class not exists: ' . $class);
        }

        return \think\Container::getInstance()->invokeClass($class, [$this->accessToken, $this]);
    }
}
