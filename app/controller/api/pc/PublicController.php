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

namespace app\controller\api\pc;


use app\Request;
use app\services\other\CityAreaServices;
use app\services\pc\PublicServices;
use crmeb\services\SystemConfigService;

class PublicController
{
    protected $services;

    public function __construct(PublicServices $services)
    {
        $this->services = $services;
    }

    /**
     * 获取城市数据
     * @param Request $request
     * @return mixed
     */
    public function getCity(Request $request, CityAreaServices $services)
    {
        [$pid] = $request->getMore([
            [['pid', 'd'], 0],
        ], true);
        return app('json')->success($services->getCityTreeList((int)$pid));
    }

    /**
     * 获取公司信息
     * @return mixed
     */
    public function getCompanyInfo()
    {
        $data = SystemConfigService::more(['contact_number', 'links_open', 'links_list', 'company_address', 'copyright', 'record_No', 'site_name', 'site_keywords', 'site_description', 'pc_logo']);
        $logoUrl = $data['pc_logo'] ?? '';
        if (strstr($logoUrl, 'http') === false && $logoUrl) {
            $logoUrl = sys_config('site_url') . $logoUrl;
        }
        $logoUrl = str_replace('\\', '/', $logoUrl);
        $data['logoUrl'] = $logoUrl;
        if ($data['links_open']) {
            $linksList = $data['links_list'];
            $linksList = is_array($linksList) ? $linksList : [];
            $edition = $volume = [];
            foreach ($linksList as $key => $row) {
                $volume[$key] = $row['sort'];
                $edition[$key] = $row['url'];
            }
            array_multisort($volume, SORT_DESC, $edition, SORT_ASC, $linksList);
            $data['links_list'] = $linksList;
        } else {
            $data['links_list'] = [];
        }
        return app('json')->successful($data);
    }

    /**
     * 获取关注微信二维码
     * @return mixed
     */
    public function getWechatQrcode()
    {
        $data['wechat_qrcode'] = sys_config('wechat_qrcode');
        return app('json')->successful($data);
    }
}
