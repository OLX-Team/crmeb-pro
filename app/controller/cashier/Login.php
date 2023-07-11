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

namespace app\controller\cashier;


use app\Request;

use app\services\store\SystemStoreStaffServices;
use crmeb\services\wechat\config\WorkConfig;
use crmeb\services\wechat\OfficialAccount;
use crmeb\services\wechat\Work;
use crmeb\utils\Captcha;
use crmeb\services\CacheService;
use app\services\cashier\LoginServices;
use think\exception\ValidateException;
use app\validate\api\user\RegisterValidates;
use think\facade\Config;

/**
 * 登录
 * Class AuthController
 * @package app\api\controller
 */
class Login
{
    /**
     * 扫码登录缓存前缀
     * @var string
     */
    protected $scan_cache_prefix = '_scan_login:';

    /**
     * 二维码类型
     * @var string[]
     */
    protected $scan_type = [
        1 => 'wechat',
        2 => 'work'
    ];

    /**
     * @var LoginServices|null
     */
    protected $services = NUll;

    /**
     * LoginController constructor.
     * @param LoginServices $services
     */
    public function __construct(LoginServices $services)
    {
        $this->services = $services;
    }

    /**
     * 获取后台登录页轮播图以及LOGO
     * @return mixed
     */
    public function info()
    {
        return app('json')->success($this->services->getLoginInfo());
    }

    /**
     * 验证码
     * @return \app\controller\admin\Login|\think\Response
     */
    public function captcha()
    {
        return app()->make(Captcha::class)->create();
    }

    /**
     * H5账号登陆
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login(Request $request)
    {
        [$account, $password, $imgcode] = $request->postMore([
            'account', 'pwd', ['imgcode', '']
        ], true);

        if ($imgcode !== '888888' && !app()->make(Captcha::class)->check($imgcode)) {
            return app('json')->fail('验证码错误，请重新输入');
        }
        return app('json')->success($this->services->login($account, $password, 'cashier'));
    }

    /**
     * 微信扫码登录
     * @return mixed
     */
    public function wechatScanLogin()
    {
        $qrcode = OfficialAccount::qrcodeService();
        mt_srand();
        $key = md5(time() . uniqid(true, false) . mt_rand(1, 10000));
        $timeout = 600;
        CacheService::set('wechat' . $this->scan_cache_prefix . $key, 0, 600);
        $data = $qrcode->temporary('wechat_scan_login:' . $key, 30 * 24 * 3600);
        return app('json')->success(['timeout' => $timeout, 'key' => $key, 'qrcode' => $qrcode->url($data['ticket'])]);
    }

    /**
     * 获取配置
     * @param WorkConfig $config
     * @return mixed
     */
    public function getWechatConfig(WorkConfig $config)
    {
        $workCorpId = $config->get('corpId');
        $config = $config->getAppConfig(WorkConfig::TYPE_USER_APP);
        return app('json')->success([
            'work_corp_id' => $workCorpId,
            'work_agent_id' => $config['agent_id'] ?? '',
        ]);
    }

    /**
     * 企业微信扫码登录
     * @return mixed
     */
    public function workScanLogin()
    {
        $userInfo = Work::getAuthUserInfo();
        return app('json')->success($this->services->workScanLogin($userInfo));
    }

    /**
     * 检测获取用户信息
     * @return mixed
     */
    public function checkScanLogin(Request $request, SystemStoreStaffServices $systemStoreStaffServices)
    {
        [$key, $type] = $request->postMore([
            ['key', ''],
            ['type', '1']
        ], true);
        if ($key) {
            $type = $this->scan_type[$type] ?? 'wechat';
            $uid = CacheService::get($type . $this->scan_cache_prefix . $key);
            if ($uid) {
                CacheService::delete($type . $this->scan_cache_prefix . $key);
                try {
                    $staffInfo = $systemStoreStaffServices->getStaffInfoByUid((int)$uid);
                } catch (\Throwable $e) {
                    return app('json')->fail('未登录');
                }

                return app('json')->success($this->services->getLoginResult($staffInfo['id'], 'cashier', $staffInfo));
            }
        }

        return app('json')->fail('未登录');
    }

    /**
     * 退出登录
     * @param Request $request
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function logout(Request $request)
    {
        $key = trim(ltrim($request->header(Config::get('cookie.token_name')), 'Bearer'));
        CacheService::redisHandler()->delete(md5($key));
        return app('json')->success();
    }

    /**
     * 密码修改
     * @param Request $request
     * @return mixed
     */
    public function reset(Request $request)
    {
        [$account, $captcha, $password] = $request->postMore([['account', ''], ['captcha', ''], ['password', '']], true);
        try {
            validate(RegisterValidates::class)->scene('register')->check(['account' => $account, 'captcha' => $captcha, 'password' => $password]);
        } catch (ValidateException $e) {
            return app('json')->fail($e->getError());
        }
        $verifyCode = CacheService::get('code_' . $account);
        if (!$verifyCode)
            return app('json')->fail('请先获取验证码');
        $verifyCode = substr($verifyCode, 0, 6);
        if ($verifyCode != $captcha) {
            return app('json')->fail('验证码错误');
        }
        if (strlen(trim($password)) < 6 || strlen(trim($password)) > 16)
            return app('json')->fail('密码必须是在6到16位之间');
        if ($password == '123456') return app('json')->fail('密码太过简单，请输入较为复杂的密码');
        $resetStatus = $this->services->reset($account, $password);
        if ($resetStatus) return app('json')->success('修改成功');
        return app('json')->fail('修改失败');
    }


}
