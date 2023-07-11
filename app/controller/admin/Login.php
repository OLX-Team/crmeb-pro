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
namespace app\controller\admin;

use app\Request;
use crmeb\services\CacheService;
use think\facade\App;
use crmeb\utils\Captcha;
use app\services\system\admin\SystemAdminServices;

/**
 * 后台登陆
 * Class Login
 * @package app\controller\admin
 */
class Login
{

    /**
     * Login constructor.
     * @param App $app
     * @param SystemAdminServices $services
     */
    public function __construct(App $app, SystemAdminServices $services)
    {
        $this->services = $services;
    }

    /**
     * 验证码
     * @return $this|\think\Response
     */
    public function captcha()
    {
        return app()->make(Captcha::class)->create();
    }

    /**
     * 登陆
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login(Request $request)
    {
        [$account, $password, $imgcode] = $request->postMore([
            'account', 'pwd', ['imgcode', '']
        ], true);

        if (!app()->make(Captcha::class)->check($imgcode)) {
            return app('json')->fail('验证码错误，请重新输入');
        }

        validate(\app\validate\admin\setting\SystemAdminValidata::class)->scene('get')->check(['account' => $account, 'pwd' => $password]);

        return app('json')->success($this->services->login($account, $password, 'admin'));
    }

    /**
     * 短信验证码登录
     * @param Request $request
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function mobileLogin(Request $request)
    {
        [$phone, $code] = $request->postMore([
            'phone', 'code'
        ], true);
        if (!$code) {
            return app('json')->fail('请输入验证码');
        }
        //验证验证码
        $verifyCode = CacheService::get('code_' . $phone);
        if (!$verifyCode)
            return app('json')->fail('请先获取验证码');
        $verifyCode = substr($verifyCode, 0, 6);
        if ($verifyCode != $code) {
            CacheService::delete('code_' . $phone);
            return app('json')->fail('验证码错误');
        }
        return app('json')->success($this->services->login($phone, $code, 'admin', true));
    }

    /**
     * 短信重置密码
     * @param Request $request
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function resetPwd(Request $request)
    {
        [$phone, $code, $newPwd] = $request->postMore([
            'phone', 'code', 'new_pwd'
        ], true);
        if (!$phone) {
            return app('json')->fail('请输入手机号');
        }
        if (!check_phone($phone)) {
            return $this->fail('请输入正确的手机号!');
        }
        if (!$code) {
            return app('json')->fail('请输入验证码');
        }
        if (!$newPwd) {
            return app('json')->fail('请输入新密码');
        }
        //验证验证码
        $verifyCode = CacheService::get('code_' . $phone);
        if (!$verifyCode)
            return app('json')->fail('请先获取验证码');
        $verifyCode = substr($verifyCode, 0, 6);
        if ($verifyCode != $code) {
            CacheService::delete('code_' . $phone);
            return app('json')->fail('验证码错误');
        }
        $this->services->resetPwd($phone, $newPwd);
        return app('json')->success('重置成功，请重新登录');
    }

    /**
     * 获取后台登录页轮播图以及LOGO
     * @return mixed
     */
    public function info()
    {
        return app('json')->success($this->services->getLoginInfo());
    }
}
