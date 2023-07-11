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
namespace app\listener\live;

use app\services\activity\live\LiveGoodsServices;
use app\services\activity\live\LiveRoomServices;
use crmeb\utils\Cron;
use crmeb\interfaces\ListenerInterface;
use think\facade\Log;

/**
 * 自动更新直播间状态和直播间产品状态
 * Class AutoUpdateLive
 * @package app\listener\order
 */
class AutoUpdateLive extends Cron implements ListenerInterface
{
    /**
     * @param $event
     */
    public function handle($event): void
    {
        //自动更新直播间状态和直播间产品状态
        $this->tick(1000 * 60, function () {
            //更新直播商品状态
            try {
                /** @var LiveGoodsServices $liveGoods */
                $liveGoods = app()->make(LiveGoodsServices::class);
                $liveGoods->syncGoodStatus();
            } catch (\Throwable $e) {
                Log::error('更新直播商品状态失败,失败原因:[' . class_basename($this) . ']' . $e->getMessage());
            }
            //更新直播间状态
            try {
                /** @var LiveRoomServices $liveRoom */
                $liveRoom = app()->make(LiveRoomServices::class);
                $liveRoom->syncRoomStatus();
            } catch (\Throwable $e) {
                Log::error('更新直播间状态失败,失败原因:[' . class_basename($this) . ']' . $e->getMessage());
            }
            return true;
        });

    }
}
