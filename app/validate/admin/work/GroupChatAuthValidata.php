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

namespace app\validate\admin\work;


use think\Validate;

/**
 * 自动拉群
 * Class GroupChatAuthValidata
 * @package app\validate\admin\work
 */
class GroupChatAuthValidata extends Validate
{

    /**
     * @var array
     */
    protected $rule = [
        'name' => 'require',
        'chat_id' => 'require',
    ];

    /**
     * @var array
     */
    protected $message = [
        'name.require' => '请填写二维码名称',
        'chat_id.require' => '请选择群聊',
    ];
}
