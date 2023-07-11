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


namespace crmeb\utils;

use think\swoole\Manager;
use Swoole\Timer;
use think\facade\Log;

/**
 * Cron定时执行
 * Class Cron
 * @package crmeb\utils
 */
class Cron
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var int
     */
    protected $workerId = 0;

    /**
     * @var
     */
    protected $timer;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * Cron constructor.
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
        $this->debug = env('APP_DEBUG', false);

    }

    /**
     * @param int $workerId
     * @return Cron
     */
    public function setWorkerId(int $workerId)
    {
        $this->workerId = $workerId;
        return $this;
    }

    /**
     * 沙盒运行
     * @param callable $callable
     */
    protected function runInSandbox(callable $callable)
    {
        $this->manager->runWithBarrier([$this->manager, 'runInSandbox'], function () use ($callable) {
            try {
                $callable();
            } catch (\Throwable $e) {
                $this->debug && Log::error($e->getMessage());
            }
        });
    }

    /**
     * 添加启动定时任务
     * @param int $ms
     * @param callable $callable
     * @return mixed|null
     */
    public function tick(int $ms, callable $callable)
    {
        if ($this->workerId === $this->manager->getWorkerId()) {
            return Timer::tick($ms, function () use ($callable) {
                $this->runInSandbox($callable);
            });
        } else {
            return null;
        }
    }

    /**
     * 每天的某时某分运行一次
     * minuteTick('12:15',fun()) 例如: 12:15 会在当天的中午12点15分钟运行一次
     * @param string $time
     * @param callable $callable
     * @return mixed|null
     */
    public function minuteTick(string $time, callable $callable)
    {
        return $this->tick(1000 * 60, function () use ($callable, $time) {
            $nowTime = date('H:i');
            if ($nowTime === $time) {
                $callable();
            }
        });
    }

    /**
     * 一次执行
     * @param int $ms
     * @param callable $callable
     */
    public function after(int $ms, callable $callable)
    {
        if ($this->workerId === $this->manager->getWorkerId()) {
            Timer::after($ms, function () use ($callable) {
                $this->runInSandbox($callable);
            });
        }
    }

    /**
     * 清除定时任务
     * @param int $timer
     */
    public function clear(int $timer)
    {
        Timer::clear($timer);
    }

    /**
     * 清除所有定时任务
     */
    public function clearAll()
    {
        Timer::clearAll();
    }
}
