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

namespace crmeb\services\wechat\groupChat;

use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use EasyWeChat\Work\ExternalContact\Client as WorkClient;
use GuzzleHttp\Exception\GuzzleException;

/**
 * 客户群聊配置
 * Class Client
 * @package crmeb\services\wechat\groupChat
 */
class Client extends WorkClient
{


    /**
     * 配置客户群进群方式
     * @param string $roomName 自动建群的群名前缀
     * @param array $chatIdList 使用该配置的客户群ID列表
     * @param string $state 企业自定义的state参数
     * @param int $autoCreateRoom 当群满了后，是否自动新建群。0-否；1-是。 默认为1
     * @param string|null $remark 联系方式的备注信息
     * @param int $scene 场景。1 - 群的小程序插件 2 - 群的二维码插件
     * @param int $roomBaseId 自动建群的群起始序号
     * @return array
     * @throws GuzzleException
     * @throws InvalidConfigException
     */
    public function addJoinWay(string $roomName, array $chatIdList, string $state, int $autoCreateRoom = 1, int $roomBaseId = 1, string $remark = null, int $scene = 2)
    {
        $data = [
            'scene' => $scene,
            'remark' => $remark,
            'chat_id_list' => $chatIdList,
            'auto_create_room' => $autoCreateRoom,
            'room_base_name' => $roomName,
            'room_base_id' => $roomBaseId,
            'state' => $state
        ];

        return $this->httpPostJson('cgi-bin/externalcontact/groupchat/add_join_way', $data);
    }

    /**
     * 更新客户群进群方式配置
     * @param string $configId
     * @param string $roomBaseName
     * @param array $chatIdList
     * @param string $state
     * @param int $autoCreateRoom
     * @param string|null $remark
     * @param int $scene
     * @param int $roomBaseId
     * @return array
     * @throws GuzzleException
     * @throws InvalidConfigException
     */
    public function updateJoinWay(string $configId, string $roomBaseName, array $chatIdList, string $state, int $autoCreateRoom = 1, int $roomBaseId = 1, string $remark = null, int $scene = 2)
    {
        $data = [
            'config_id' => $configId,
            'scene' => $scene,
            'remark' => $remark,
            'auto_create_room' => $autoCreateRoom,
            'room_base_name' => $roomBaseName,
            'room_base_id' => $roomBaseId,
            'chat_id_list' => $chatIdList,
            'state' => $state,
        ];
        return $this->httpPostJson('cgi-bin/externalcontact/groupchat/update_join_way', $data);
    }

    /**
     * 获取客户群进群方式配置
     * @param string $configId
     * @return array
     * @throws GuzzleException
     * @throws InvalidConfigException
     */
    public function getJoinWay(string $configId)
    {
        return $this->httpPostJson('cgi-bin/externalcontact/groupchat/get_join_way', ['config_id' => $configId]);
    }

    /**
     * 删除客户群进群方式配置
     * @param string $configId
     * @return array
     * @throws GuzzleException
     * @throws InvalidConfigException
     */
    public function deleteJoinWay(string $configId)
    {
        return $this->httpPostJson('cgi-bin/externalcontact/groupchat/del_join_way', ['config_id' => $configId]);
    }


}
