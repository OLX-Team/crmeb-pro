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
namespace app\controller\store\export;


use app\controller\store\AuthController;
use app\services\other\export\ExportServices;
use app\services\store\finance\StoreFinanceFlowServices;
use think\facade\App;

/**
 * 导出excel类
 * Class ExportExcel
 * @package app\controller\store\export
 */
class ExportExcel extends AuthController
{
    /**
     * @var ExportServices
     */
    protected $service;

    /**
     * ExportExcel constructor.
     * @param App $app
     * @param ExportServices $services
     */
    public function __construct(App $app, ExportServices $services)
    {
        parent::__construct($app);
        $this->service = $services;
    }

    /**
     * 门店账单下载
     * @param StoreFinanceFlowServices $services
     * @return mixed
     */
    public function financeRecord(StoreFinanceFlowServices $services)
    {
        [$ids] = $this->request->getMore([
            ['ids', '']
        ], true);
        $where['id'] = $ids ? explode(',', $ids) : [];
        $where['is_del'] = 0;
        $where['store_id'] = $this->storeId;
        $where['no_type'] = 1;
        $data = $services->getList($where);
        return $this->success($this->service->financeRecord($data['list'] ?? []));
    }

}
