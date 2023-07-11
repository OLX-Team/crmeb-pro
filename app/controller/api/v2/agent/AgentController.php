<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
namespace app\controller\api\v2\agent;

use app\Request;
use app\services\other\AgreementServices;
use app\services\user\UserBrokerageServices;
use app\services\user\UserServices;

class AgentController
{
    /**
     * 获取用户推广用户列表
     * @param Request $request
     * @param UserServices $userServices
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function agentUserList(Request $request, UserServices $userServices)
    {
        [$type] = $request->getMore([
            ['type', 0]
        ], true);
        $uid = $request->uid();
        return app('json')->successful($userServices->agentUserList($uid, $type));
    }

    /**
     * 获取用户推广获得收益，佣金轮播，分销规则
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function agentInfo(Request $request)
    {
        /** @var AgreementServices $agreementService */
        $agreementService = app()->make(AgreementServices::class);
        /** @var UserBrokerageServices $userBrokerageServices */
        $userBrokerageServices = app()->make(UserBrokerageServices::class);
        $data['agreement'] = $agreementService->getAgreementBytype(2)['content'] ?? '';
        $data['price'] = $userBrokerageServices->sum(['uid' => $request->uid(), 'pm' => 1, 'not_type' => ['extract_fail', 'refund']], 'number', true);
        $list = $userBrokerageServices->getList(['pm' => 1, 'not_type' => ['extract_fail', 'refund']], '*', 1, 10, [], ['user']);
        foreach ($list as $item) {
            $data['list'][] = [
                'nickname' => $item['user']['nickname'] ?? '',
                'price' => $item['number']
            ];
        }
        return app('json')->successful($data);
    }
}
