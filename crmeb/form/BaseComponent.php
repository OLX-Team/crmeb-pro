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

namespace crmeb\form;

/**
 * 基础组件集成
 * Class BaseComponent
 * @package crmeb\form
 */
abstract class BaseComponent
{

    /**
     * @var bool
     */
    protected $init = false;

    /**
     * @var array
     */
    protected $rule = [];

    /**
     * 数据库验证
     * @var array
     */
    protected $validate = [];

    /**
     * @var CommonRule
     */
    protected $validataRule;

    /**
     * 是否实例化
     */
    protected function init()
    {
        if (!$this->init) {
            $this->validataRule = new CommonRule;
            $this->init = true;
        }
    }

    /**
     * 多个验证规则
     * @param array $validate
     * @return $this
     */
    public function validates(array $validate)
    {
        $this->validate = $validate;
        return $this;
    }

    /**
     * 单个验证规则
     * @param CommonRule $validate
     * @return $this
     */
    public function validate(CommonRule $validate)
    {
        $this->validate[] = $validate;
        return $this;
    }


    /**
     * 是否必填
     * @return $this
     */
    public function required()
    {
        $this->init();
        $this->validataRule->required();
        return $this;
    }

    /**
     * 设置提示消息
     * @param string $message
     * @return $this
     */
    public function message(string $message)
    {
        $this->init();
        $this->validataRule->message($message);
        return $this;
    }

    /**
     *  数据写入
     */
    protected function before()
    {
        if (!$this->validate && $this->validataRule instanceof CommonRule) {
            if (!$this->validataRule->getMessage() && $this->rule['title']) {
                $this->validataRule->message('请输入' . $this->rule['title']);
            }
            $this->validate[] = $this->validataRule->toArray();
        }
        $validate = [];
        foreach ($this->validate as $item) {
            if ($item instanceof CommonRule) {
                $validate[] = $item->toArray();
            } else {
                $validate[] = $item;
            }
        }
        $this->rule['validate'] = $validate;
    }
}
