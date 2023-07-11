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
 * Class GroupTemplateValidata
 * @package app\validate\admin\work
 */
class GroupTemplateValidata extends Validate
{

    /**
     * @var array
     */
    protected $rule = [
        'type' => 'require',
        'name' => 'require',
        'client_type' => 'require',
        'template_type' => 'require',
        'where_time' => 'require',
        'welcome_words' => 'require',
    ];

    /**
     * @var array
     */
    protected $message = [
        'type.require' => '群发类型',
        'name.require' => '群发名称',
        'client_type.require' => '选择客户类型',
        'template_type.require' => '选择发送类型',
        'welcome_words.require' => '请填写群发内容',
        'where_time.require' => '请选择添加时间',
    ];

    /**
     * @var array
     */
    protected $scene = [

    ];
}
