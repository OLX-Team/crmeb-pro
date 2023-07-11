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
namespace crmeb\services\printer\storage;

use crmeb\basic\BasePrinter;
use crmeb\services\printer\AccessToken;

/**
 * Class YiLianYun
 * @package crmeb\services\printer\storage
 */
class YiLianYun extends BasePrinter
{

    /**
     * 初始化
     * @param array $config
     * @return mixed|void
     */
    protected function initialize(array $config)
    {

    }

    /**
     * 开始打印
     * @return bool|mixed|string
     * @throws \Exception
     */
    public function startPrinter()
    {
        if (!$this->printerContent) {
            return $this->setError('Missing print');
        }
        $time = time();
        try {
            $request = $this->accessToken->postRequest($this->accessToken->getApiUrl('print/index'), [
                'client_id' => $this->accessToken->clientId,
                'access_token' => $this->accessToken->getAccessToken(),
                'machine_code' => $this->accessToken->machineCode,
                'content' => $this->printerContent,
                'origin_id' => 'crmeb' . $time,
                'sign' => strtolower(md5($this->accessToken->clientId . $time . $this->accessToken->apiKey)),
                'id' => $this->accessToken->createUuid(),
                'timestamp' => $time
            ]);
        } catch (\Exception $e) {
            return $this->setError($e->getMessage());
        }
        $this->printerContent = null;
        if ($request === false) {
            return $this->setError('request was aborted');
        }
        $request = is_string($request) ? json_decode($request, true) : $request;
        if (isset($request['error']) && $request['error'] != 0) {
            return $this->setError(isset($request['error_description']) ? $request['error_description'] : 'Accesstoken has expired');
        }
        return $request;
    }

    /**
     * 设置打印内容
     * @param array $config
     * @return YiLianYun
     */
    public function setPrinterContent(array $config): self
    {
        $timeYmd = date('Y-m-d', time());
        $timeHis = date('H:i:s', time());
        $goodsStr = '<table><tr><td>商品名称</td><td>数量</td><td>单价</td><td>金额</td></tr>';
        $product = $config['product'];
        foreach ($product as $item) {
            $goodsStr .= '<tr>';
			if ($item['is_gift']) {
				$unit_price = $price = 0;
			} else {
				if (isset($item['sum_price'])) {
					$unit_price = $item['sum_price'];
					$price = bcmul((string)$item['cart_num'], (string)$unit_price, 2);
				} else {
					$unit_price = $item['truePrice'];
					$price = bcadd((string)$item['vip_truePrice'], (string)bcmul((string)$item['cart_num'], (string)$unit_price, 4), 2);
				}
			}
            $goodsStr .= "<td>{$item['productInfo']['store_name']} | {$item['productInfo']['attrInfo']['suk']}</td><td>{$item['cart_num']}</td><td>{$unit_price}</td><td>{$price}</td>";
            $goodsStr .= '</tr>';
            unset($price, $unit_price);
        }
        $goodsStr .= '</table>';
        $orderInfo = $config['orderInfo'];
        $name = $config['name'];
		$discountPrice = (float)bcsub((string)bcadd((string)$orderInfo['total_price'], $orderInfo['pay_postage'], 2), (string)bcadd((string)$orderInfo['deduction_price'], $orderInfo['pay_price'], 2), 2);
        $this->printerContent = <<<CONTENT
<FB><center> ** {$name} **</center></FB>
<FH2><FW2>----------------</FW2></FH2>
订单编号：{$orderInfo['order_id']}\r
日    期: {$timeYmd} \r
时    间: {$timeHis}\r
姓    名: {$orderInfo['real_name']}\r
电    话: {$orderInfo['user_phone']}\r
地    址: {$orderInfo['user_address']}\r
赠送积分: {$orderInfo['gain_integral']}\r
订单备注：{$orderInfo['mark']}\r
*************商品***************\r
{$goodsStr}
********************************\r
<FH>
<LR>合计：￥{$orderInfo['total_price']},优惠: ￥{$discountPrice}</LR>
<LR>邮费：￥{$orderInfo['pay_postage']},抵扣：￥{$orderInfo['deduction_price']}</LR>
<right>实际支付：￥{$orderInfo['pay_price']}</right>           
</FH>
<FS><center> ** 完 **</center></FS>
CONTENT;
        return $this;
    }

    /**
     * 积分商城打印内容
     * @param array $config
     * @return YiLianYun
     */
    public function setIntegralPrinterContent(array $config): self
    {
        $timeYmd = date('Y-m-d', time());
        $timeHis = date('H:i:s', time());
        $goodsStr = '<table><tr><td>商品名称</td><td>数量</td><td>单价</td><td>总积分</td></tr>';
        $goodsStr .= '<tr>';
        $goodsStr .= "<td>{$config['orderInfo']['store_name']}</td><td>{$config['orderInfo']['total_num']}</td><td>{$config['orderInfo']['price']}</td><td>{$config['orderInfo']['total_price']}</td>";
        $goodsStr .= '</tr>';
        $goodsStr .= '</table>';
        $orderInfo = $config['orderInfo'];
        $name = $config['name'];
        $this->printerContent = <<<CONTENT
<FB><center> ** {$name} **</center></FB>
<FH2><FW2>----------------</FW2></FH2>
订单编号：{$orderInfo['order_id']}\r
日    期: {$timeYmd} \r
时    间: {$timeHis}\r
姓    名: {$orderInfo['real_name']}\r
电    话: {$orderInfo['user_phone']}\r
地    址: {$orderInfo['user_address']}\r
订单备注：{$orderInfo['mark']}\r
*************商品***************\r
{$goodsStr}
********************************\r
<FH>
<right>合计：{$orderInfo['total_price']}积分</right>           
</FH>
<FS><center> ** 完 **</center></FS>
CONTENT;
        return $this;
    }
}
