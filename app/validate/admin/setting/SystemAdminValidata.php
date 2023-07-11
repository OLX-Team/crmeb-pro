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
namespace app\validate\admin\setting;

use think\Validate;

class SystemAdminValidata extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'account' => ['require', 'alphaDash'],
        'conf_pwd' => 'require',
        'pwd' => 'require',
        'real_name' => 'require',
        'roles' => ['require', 'array'],
        'phone' => ['require','mobile'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'account.require' => '请填写管理员账号',
        'account.alphaDash' => '管理员账号应为数字和字母',
        'conf_pwd.require' => '请输入确认密码',
        'pwd.require' => '请输入密码',
        'real_name.require' => '请输管理员姓名',
        'roles.require' => '请选择管理员身份',
        'roles.array' => '身份必须为数组',
        'phone.require' => '请填写管理员电话',
        'phone.mobile' => '电话格式不正确',
    ];

    protected $scene = [
        'get' => ['account', 'pwd'],
        'update' => ['account', 'roles', 'real_name', 'phone'],
        'save' => ['account', 'pwd', 'conf_pwd', 'roles', 'real_name', 'phone'],
    ];


}
