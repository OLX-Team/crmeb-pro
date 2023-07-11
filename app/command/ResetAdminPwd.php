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


namespace app\command;


use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use app\services\system\admin\SystemAdminServices;

/**
 * 重置密码
 * Class ResetAdminPwd
 * @package app\command
 */
class ResetAdminPwd extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('reset:password')
            ->addArgument('root', Argument::OPTIONAL, '管理员账号', 'admin')
            ->addOption('pwd', null, Option::VALUE_REQUIRED, '重置密码', '123456')
            ->setDescription('the update resetPwd command');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return int|void|null
     */
    protected function execute(Input $input, Output $output)
    {
        $account = $input->getArgument('root');
        if ($input->hasOption('pwd')) {
            $pwd = $input->getOption('pwd');
        }
        /** @var SystemAdminServices $systemAdminServices */
        $systemAdminServices = app()->make(SystemAdminServices::class);
        $admin = $systemAdminServices->get(['account' => $account, 'status' => 1, 'is_del' => 0]);

        if (!$admin) {
            $output->warning('管理员账号不存在');
        } else {
            $pwd_ = $systemAdminServices->passwordHash($pwd);
            $admin->pwd = $pwd_;
            $admin->save();
            $output->info('账号：' . $account . '；密码已重置:' . $pwd);
        }
    }

}
