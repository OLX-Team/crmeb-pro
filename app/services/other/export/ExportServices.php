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

namespace app\services\other\export;

use app\services\BaseServices;
use app\jobs\system\ExportExcelJob;
use app\services\pay\PayServices;
use crmeb\services\SpreadsheetExcelService;

/**
 * 导出
 * Class ExportServices
 * @package app\services\other\export
 */
class ExportServices extends BaseServices
{
    /**
     * 不分页拆分处理最大条数
     * @var int
     */
    public $maxLimit = 1000;
    /**
     * 分页导出每页条数
     * @var int
     */
    public $limit = 1000;

    /**
     * 真实请求导出
     * @param $header excel表头
     * @param $title 标题
     * @param array $export 填充数据
     * @param string $filename 保存文件名称
     * @param string $suffix 保存文件后缀
     * @param bool $is_save true|false 是否保存到本地
     * @return mixed
     */
    public function export(array $header, array $title_arr, array $export = [], string $filename = '', string $suffix = 'xlsx', bool $is_save = true)
    {
        $path = [];
        $exportNum = count($export);
        $limit = $this->maxLimit;
        if ($exportNum < $limit) {
            $title = isset($title_arr[0]) && !empty($title_arr[0]) ? $title_arr[0] : '导出数据';
            $name = isset($title_arr[1]) && !empty($title_arr[1]) ? $title_arr[1] : '导出数据';
            $info = isset($title_arr[2]) && !empty($title_arr[2]) ? $title_arr[2] : date('Y-m-d H:i:s', time());
            $filePath = SpreadsheetExcelService::instance()->setExcelHeader($header)
                ->setExcelTile($title, $name, $info)
                ->setExcelContent($export)
                ->excelSave($filename, $suffix, $is_save);
            $path[] = sys_config('site_url') . $filePath;
        } else {
            $data = [];
            $i = $j = 0;
            $basePath = sys_config('site_url') . '/phpExcel/';
            foreach ($export as $item) {
                $data[] = $item;
                $i++;
                if ($limit <= 1 || $i == $exportNum) {
                    if ($j > 0) {
                        $filename .= '_' . $j;
                        $header = [];
                        $title_arr = [];
                    }
                    //加入队列
                    ExportExcelJob::dispatch([$data, $filename, $header, $title_arr, $suffix, $is_save]);
                    $path[] = $basePath . $filename . '.' . $suffix;
                    $data = [];
                    $limit = $this->limit + 1;
                    $j++;
                }
                $limit--;
            }
        }
        return $path;
    }

    /**
     * 用户资金导出
     * @param array $data
     * @param int $type 1:直接返回数据前端生成excel 2：后台生成excel
     * @return array|mixed
     */
    public function userFinance($data = [], $type = 1)
    {
        $header = ['会员ID', '昵称', '金额', '类型', '备注', '创建时间'];
        $title = ['资金监控', '资金监控', date('Y-m-d H:i:s', time())];
        $filename = '资金监控_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $value) {
                $one_data = [
                    'uid' => $value['uid'],
                    'nickname' => $value['nickname'],
                    'pm' => $value['pm'] == 0 ? '-' . $value['number'] : $value['number'],
                    'title' => $value['title'],
                    'mark' => $value['mark'],
                    'add_time' => $value['add_time'],
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 用户佣金导出
     * @param $data 导出数据
     */
    public function userCommission($data = [], $type = 1)
    {
        $header = ['昵称/姓名', '总佣金金额', '账户余额', '账户佣金', '提现到账佣金', '时间'];
        $title = ['佣金记录', '佣金记录' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '佣金记录_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $value) {
                $one_data = [
                    'nickname' => $value['nickname'],
                    'sum_number' => $value['sum_number'],
                    'now_money' => $value['now_money'],
                    'brokerage_price' => $value['brokerage_price'],
                    'extract_price' => $value['extract_price'],
                    'time' => $value['time']
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 用户积分导出
     * @param $data 导出数据
     */
    public function userPoint($data = [], $type = 1)
    {
        $header = ['编号', '标题', '变动前积分', '积分变动', '备注', '用户微信昵称', '添加时间'];
        $title = ['积分日志', '积分日志' . time(), '生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '积分日志_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $key => $item) {
                $one_data = [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'balance' => $item['balance'],
                    'number' => $item['number'],
                    'mark' => $item['mark'],
                    'nickname' => $item['nickname'],
                    'add_time' => $item['add_time'],
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 用户充值导出
     * @param $data 导出数据
     */
    public function userRecharge($data = [], $type = 1)
    {
        $header = ['昵称/姓名', '充值金额', '是否支付', '充值类型', '支付时间', '是否退款', '添加时间'];
        $title = ['充值记录', '充值记录' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '充值记录_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $item) {
                switch ($item['recharge_type']) {
                    case 'routine':
                        $item['_recharge_type'] = '小程序充值';
                        break;
                    case 'weixin':
                        $item['_recharge_type'] = '公众号充值';
                        break;
                    case 'balance':
                        $item['_recharge_type'] = '佣金转入';
                        break;
                    case 'store':
                        $item['_recharge_type'] = '门店余额充值';
                        break;
                    default:
                        $item['_recharge_type'] = '其他充值';
                        break;
                }
                $item['_pay_time'] = $item['pay_time'] ? date('Y-m-d H:i:s', $item['pay_time']) : '暂无';
                $item['_add_time'] = $item['add_time'] ? date('Y-m-d H:i:s', $item['add_time']) : '暂无';
                $item['paid_type'] = $item['paid'] ? '已支付' : '未支付';

                $one_data = [
                    'nickname' => $item['nickname'],
                    'price' => $item['price'],
                    'paid_type' => $item['paid_type'],
                    '_recharge_type' => $item['_recharge_type'],
                    '_pay_time' => $item['_pay_time'],
                    'paid' => $item['paid'] == 1 && $item['refund_price'] == $item['price'] ? '已退款' : '未退款',
                    '_add_time' => $item['_add_time']
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 用户推广导出
     * @param $data 导出数据
     */
    public function userAgent($data = [], $type = 1)
    {
        $header = ['用户编号', '昵称', '电话号码', '推广用户数量', '订单数量', '推广订单金额', '佣金金额', '已提现金额', '提现次数', '未提现金额', '上级推广人'];
        $title = ['推广用户', '推广用户导出' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '推广用户_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $index => $item) {
                $one_data = [
                    'uid' => $item['uid'],
                    'nickname' => $item['nickname'],
                    'phone' => $item['phone'],
                    'spread_count' => $item['spread_count'],
                    'order_count' => $item['order_count'],
                    'order_price' => $item['order_price'],
                    'brokerage_money' => $item['brokerage_money'],
                    'extract_count_price' => $item['extract_count_price'],
                    'extract_count_num' => $item['extract_count_num'],
                    'brokerage_price' => $item['brokerage_price'],
                    'spread_name' => $item['spread_name'],
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 微信用户导出
     * @param $data 导出数据
     */
    public function wechatUser($data = [], $type = 1)
    {
        $header = ['名称', '性别', '地区', '是否关注公众号'];
        $title = ['微信用户导出', '微信用户导出' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '微信用户导出_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $index => $item) {
                $one_data = [
                    'nickname' => $item['nickname'],
                    'sex' => $item['sex'],
                    'address' => $item['country'] . $item['province'] . $item['city'],
                    'subscribe' => $item['subscribe'] == 1 ? '关注' : '未关注',
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 订单资金导出
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function orderFinance($data = [], $type = 1)
    {
        $header = ['时间', '营业额(元)', '支出(元)', '成本', '优惠', '积分抵扣', '盈利(元)'];
        $title = ['财务统计', '财务统计', date('Y-m-d H:i:s', time())];
        $filename = '财务统计_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $info) {
                $time = $info['pay_time'];
                $price = $info['total_price'] + $info['pay_postage'];
                $zhichu = $info['coupon_price'] + $info['deduction_price'] + $info['cost'];
                $profit = ($info['total_price'] + $info['pay_postage']) - ($info['coupon_price'] + $info['deduction_price'] + $info['cost']);
                $deduction = $info['deduction_price'];//积分抵扣
                $coupon = $info['coupon_price'];//优惠
                $cost = $info['cost'];//成本
                $one_data = compact('time', 'price', 'zhichu', 'cost', 'coupon', 'deduction', 'profit');
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 砍价活动导出
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function storeBargain($data = [], $type = 1)
    {
        $header = ['砍价活动名称', '砍价活动简介', '砍价金额', '砍价最低价',
            '用户每次砍价的次数', '砍价状态', '砍价开启时间', '砍价结束时间', '销量', '库存', '返多少积分', '添加时间'];
        $title = ['砍价商品导出', '商品信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '砍价商品导出_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $index => $item) {
                $one_data = [
                    'title' => $item['title'],
                    'info' => $item['info'],
                    'price' => '￥' . $item['price'],
                    'bargain_max_price' => '￥' . $item['min_price'],
                    'bargain_num' => $item['bargain_num'],
                    'status' => $item['status'] ? '开启' : '关闭',
                    'start_time' => empty($item['start_time']) ? '' : date('Y-m-d H:i:s', (int)$item['start_time']),
                    'stop_time' => empty($item['stop_time']) ? '' : date('Y-m-d H:i:s', (int)$item['stop_time']),
                    'sales' => $item['sales'],
                    'stock' => $item['stock'],
                    'give_integral' => $item['give_integral'],
                    'add_time' => empty($item['add_time']) ? '' : $item['add_time'],
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 拼团导出
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function storeCombination($data = [], $type = 1)
    {
        $header = ['编号', '拼团名称', '原价', '拼团价', '库存', '拼团人数', '参与人数', '成团数量', '销量', '商品状态', '结束时间'];
        $title = ['拼团商品导出', '商品信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '拼团商品导出_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $item) {
                $one_data = [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'ot_price' => $item['ot_price'],
                    'price' => $item['price'],
                    'stock' => $item['stock'],
                    'people' => $item['count_people'],
                    'count_people_all' => $item['count_people_all'],
                    'count_people_pink' => $item['count_people_pink'],
                    'sales' => $item['sales'] ?? 0,
                    'is_show' => $item['is_show'] ? '开启' : '关闭',
                    'stop_time' => empty($item['stop_time']) ? '' : date('Y/m/d H:i:s', (int)$item['stop_time'])
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 秒杀活动导出
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function storeSeckill($data = [], $type = 1)
    {
        $header = ['编号', '活动标题', '活动简介', '原价', '秒杀价', '库存', '销量', '秒杀状态', '结束时间', '状态'];
        $title = ['秒杀商品导出', ' ', ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '秒杀商品导出_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $item) {
                if ($item['status']) {
                    if ($item['start_time'] > time())
                        $item['start_name'] = '活动未开始';
                    else if ($item['stop_time'] < time())
                        $item['start_name'] = '活动已结束';
                    else if ($item['stop_time'] > time() && $item['start_time'] < time())
                        $item['start_name'] = '正在进行中';
                } else {
                    $item['start_name'] = '活动已结束';
                }
                $one_data = [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'info' => $item['info'],
                    'ot_price' => $item['ot_price'],
                    'price' => $item['price'],
                    'stock' => $item['stock'],
                    'sales' => $item['sales'],
                    'start_name' => $item['start_name'],
                    'stop_time' => $item['stop_time'] ? date('Y-m-d H:i:s', $item['stop_time']) : '/',
                    'status' => $item['status'] ? '开启' : '关闭',
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 导出商品卡号、卡密模版
     * @param int $type
     * @return array|mixed
     */
    public function storeProductCardTemplate($type = 1)
    {
        $header = ['卡号', '卡密'];
        $title = ['商品卡密模版', '商品密' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '商品卡密模版_' . date('YmdHis', time());

        if ($type == 1) {
            $export = [];
            $filekey = ['card_no', 'card_pwd'];
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, [], $filename);
        }
    }

    /**
     * 商品导出
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function storeProduct($data = [], $type = 1)
    {
        $header = ['商品名称', '商品简介', '商品分类', '价格', '库存', '销量', '浏览量'];
        $title = ['商品导出', '商品信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '商品导出_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $index => $item) {
                $one_data = [
                    'store_name' => $item['store_name'],
                    'store_info' => $item['store_info'],
                    'cate_name' => $item['cate_name'],
                    'price' => '￥' . $item['price'],
                    'stock' => $item['stock'],
                    'sales' => $item['sales'],
                    'visitor' => $item['visitor'],
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 商铺订单导出
     * @param array $data
     * @param string $type
     * @param int $export_type
     * @return array|mixed
     */
    public function storeOrder($data = [], $type = "", $export_type = 1)
    {
        if (!$type) {
            $header = ['订单ID', '订单编号', '性别', '电话', '收货人姓名', '收货人电话', '收货地址', '商品信息', '商品总数',
                '总价格', '实际支付', '邮费', '会员优惠金额', '优惠卷金额', '积分抵扣金额', '支付状态', '支付时间', '订单状态', '下单时间', '用户备注'];
            $title = ['订单导出', '订单信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
            $filename = '订单导出_' . date('YmdHis', time());
        } else {
            $header = ['订单ID', '订单编号', '物流公司', '物流编码', '物流单号', '发货地址', '收货人姓名', '收货人电话', '订单实付金额', '商品数量*售价', '商品ID', '商品名称', '商品规格', '商家备注', '订单成交时间'];
            $title = ['发货单导出', '订单信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
            $filename = '发货单导出_' . date('YmdHis', time());
        }
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $item) {
                if (!$type) {
                    if ($item['paid'] == 1) {
                        switch ($item['pay_type']) {
                            case PayServices::WEIXIN_PAY:
                                $item['pay_type_name'] = '微信支付';
                                break;
                            case PayServices::YUE_PAY:
                                $item['pay_type_name'] = '余额支付';
                                break;
                            case PayServices::OFFLINE_PAY:
                                $item['pay_type_name'] = '线下支付';
                                break;
                            case PayServices::ALIAPY_PAY:
                                $item['pay_type_name'] = '支付宝支付';
                                break;
                            case PayServices::CASH_PAY:
                                $item['pay_type_name'] = '现金支付';
                                break;
                            default:
                                $item['pay_type_name'] = '其他支付';
                                break;
                        }
                    } else {
                        switch ($item['pay_type']) {
                            default:
                                $item['pay_type_name'] = '未支付';
                                break;
                            case 'offline':
                                $item['pay_type_name'] = '线下支付';
                                $item['pay_type_info'] = 1;
                                break;
                        }
                    }

                    if ($item['paid'] == 0 && $item['status'] == 0) {
                        $item['status_name'] = '未支付';
                    } else if ($item['paid'] == 1 && $item['status'] == 4 && in_array($item['shipping_type'], [1, 3]) && $item['refund_status'] == 0) {
                        $item['status_name'] = '部分发货';
                    } else if ($item['paid'] == 1 && $item['status'] == 5 && $item['shipping_type'] == 2 && $item['refund_status'] == 0) {
                        $item['status_name'] = '部分核销';
                    } else if ($item['paid'] == 1 && $item['refund_status'] == 1) {
                        $item['status_name'] = '申请退款';
                    } else if ($item['paid'] == 1 && $item['refund_status'] == 2) {
                        $item['status_name'] = '已退款';
                    } else if ($item['paid'] == 1 && $item['refund_status'] == 4) {
                        $item['status_name'] = '退款中';
                    } else if ($item['paid'] == 1 && $item['status'] == 0 && in_array($item['shipping_type'], [1, 3]) && $item['refund_status'] == 0) {
                        $item['status_name'] = '未发货';
                    } else if ($item['paid'] == 1 && in_array($item['status'], [0, 1]) && $item['shipping_type'] == 2 && $item['refund_status'] == 0) {
                        $item['status_name'] = '未核销';
                    } else if ($item['paid'] == 1 && in_array($item['status'], [1, 5]) && in_array($item['shipping_type'], [1, 3]) && $item['refund_status'] == 0) {
                        $item['status_name'] = '待收货';
                    } else if ($item['paid'] == 1 && $item['status'] == 2 && $item['refund_status'] == 0) {
                        $item['status_name'] = '待评价';
                    } else if ($item['paid'] == 1 && $item['status'] == 3 && $item['refund_status'] == 0) {
                        $item['status_name'] = '已完成';
                    } else if ($item['paid'] == 1 && $item['refund_status'] == 3) {
                        $item['status_name'] = '部分退款';
                    }
                    $goodsName = [];
                    $vip_sum_price = 0;
                    foreach ($item['_info'] as $k => $v) {
                        $suk = '';
                        if (isset($v['productInfo']['attrInfo'])) {
                            if (isset($v['productInfo']['attrInfo']['suk'])) {
                                $suk = '(' . $v['productInfo']['attrInfo']['suk'] . ')';
                            }
                        }
                        if (isset($v['productInfo']['store_name'])) {
                            $goodsName[] = implode(' ',
                                [
                                    $v['productInfo']['store_name'],
                                    $suk,
                                    "[{$v['cart_num']} * {$v['truePrice']}]",
                                ]);
                        }
                        $vip_sum_price = bcadd((string)$vip_sum_price, bcmul($v['vip_truePrice'], $v['cart_num'] ? $v['cart_num'] : 1, 4), 2);
                    }
                    if ($item['sex'] == 1) $sex_name = '男';
                    else if ($item['sex'] == 2) $sex_name = '女';
                    else $sex_name = '未知';

                    $one_data = [
                        'id' => $item['id'],
                        'order_id' => $item['order_id'],
                        'sex' => $sex_name,
                        'phone' => $item['user_phone'],
                        'real_name' => $item['real_name'],
                        'user_phone' => $item['user_phone'],
                        'user_address' => $item['user_address'],
                        'goods_name' => $goodsName ? implode("\n", $goodsName) : '',
                        'total_num' => $item['total_num'],
                        'total_price' => bcadd((string)$item['total_price'], (string)$vip_sum_price, 2),
                        'pay_price' => $item['pay_price'],
                        'pay_postage' => $item['pay_postage'],
                        'vip_sum_price' => $vip_sum_price,
                        'coupon_price' => $item['coupon_price'],
                        'deduction_price' => $item['deduction_price'] ?? 0,
                        'pay_type_name' => $item['pay_type_name'],
                        'pay_time' => $item['pay_time'] > 0 ? date('Y/m-d H:i', (int)$item['pay_time']) : '暂无',
                        'status_name' => $item['status_name'] ?? '未知状态',
                        'add_time' => empty($item['add_time']) ? 0 : date('Y-m-d H:i:s', (int)$item['add_time']),
                        'mark' => $item['mark']
                    ];
                } else {
                    if (isset($item['pinkStatus']) && $item['pinkStatus'] != 2) {
                        continue;
                    }
					if (isset($item['refund']) && $item['refund']) {
                        continue;
                    }
                    $goodsName = [];
                    $g = 0;
                    foreach ($item['_info'] as $k => $v) {
                        $goodsName['cart_num'][$g] = $v['cart_num'] . ' * ' . ($v['productInfo']['attrInfo']['price'] ?? $v['productInfo']['price'] ?? 0.00);
                        $goodsName['product_id'][$g] = $v['product_id'];
                        $suk = $barCode = $code = '';
                        if (!empty($v['productInfo']['attrInfo']['bar_code'])) {
                            $barCode = $v['productInfo']['attrInfo']['bar_code'];
                        }
                        if (!empty($v['productInfo']['attrInfo']['code'])) {
                            $code = $v['productInfo']['attrInfo']['code'];
                        }
                        if (isset($v['productInfo']['attrInfo'])) {
                            if (isset($v['productInfo']['attrInfo']['suk'])) {
                                $suk = '(' . $v['productInfo']['attrInfo']['suk'] . '|条码:' . $barCode . '|编码:' . $code . ')';
                            }
                        }
                        $name = [];
                        if (isset($v['productInfo']['store_name'])) {
                            $name[] = implode(' ',
                                [
                                    $v['productInfo']['store_name'],
                                    $suk,
                                    "[{$v['cart_num']} * {$v['truePrice']}]",
                                ]);
                        }
                        $goodsName['goods_name'][$g] = implode(' ', $name);
                        $goodsName['attr'][$g] = $v['productInfo']['attrInfo']['suk'] ?? '';
                        $g++;
                    }
                    $one_data = [
                        'id' => $item['id'],
                        'order_id' => $item['order_id'],
                        'a' => "",
                        'b' => "",
                        'c' => "",
                        'user_address' => $item['user_address'],
                        'real_name' => $item['real_name'],
                        'user_phone' => $item['user_phone'],
                        'pay_price' => $item['pay_price'],
                        'cart_num' => isset($goodsName['cart_num']) ? implode("\n", $goodsName['cart_num']) : '',
                        'product_id' => isset($goodsName['product_id']) ? implode("\n", $goodsName['product_id']) : '',
                        'goods_name' => isset($goodsName['goods_name']) ? implode("\n", $goodsName['goods_name']) : '',
                        'attr' => isset($goodsName['attr']) ? implode("\n", $goodsName['attr']) : '',
                        'remark' => $item['remark'],
                        'pay_time' => $item['pay_time'] ? date('Y-m-d H:i:s', (int)$item['pay_time']) : '暂无',
                    ];
                }
                if ($export_type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($export_type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * @param string $str
     * @return false|string|string[]|null
     */
    public function strToUtf8($str = '')
    {

        $current_encode = mb_detect_encoding($str, array("ASCII", "GB2312", "GBK", 'BIG5', 'UTF-8'));

        $encoded_str = mb_convert_encoding($str, 'UTF-8', $current_encode);

        return $encoded_str;

    }

    /**
     * 商铺自提点导出
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function storeMerchant($data = [], $type = 1)
    {
        $header = ['提货点名称', '提货点', '地址', '营业时间', '状态'];
        $title = ['提货点导出', '提货点信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '提货点导出_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $index => $item) {
                $one_data = [
                    'name' => $item['name'],
                    'phone' => $item['phone'],
                    'address' => $item['address'] . '' . $item['detailed_address'],
                    'day_time' => $item['day_time'],
                    'is_show' => $item['is_show'] ? '开启' : '关闭'
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 会员卡导出
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function memberCard($data = [], $type = 1)
    {
        $header = ['会员卡号', '密码', '领取人', '领取人手机号', '领取时间', '是否使用'];
        $title = ['会员卡导出', '会员卡导出' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = $data['title'] ? ("卡密会员_" . trim(str_replace(["\r\n", "\r", "\\", "\n", "/", "<", ">", "=", " "], '', $data['title']))) : "";
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data['data'] as $index => $item) {
                $one_data = [
                    'card_number' => $item['card_number'],
                    'card_password' => $item['card_password'],
                    'user_name' => $item['user_name'],
                    'user_phone' => $item['user_phone'],
                    'use_time' => $item['use_time'],
                    'use_uid' => $item['use_uid'] ? '已领取' : '未领取'
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 批量任务发货记录导出
     * @param array $data
     * @param $queueType
     * @param int $type
     * @return array|mixed
     */
    public function batchOrderDelivery($data = [], $queueType, $type = 1)
    {
        if (in_array($queueType, [7, 8])) {
            $header = ['订单ID', '物流公司', '物流单号', '处理状态', '异常原因'];
        }
        if ($queueType == 9) {
            $header = ['订单ID', '配送员姓名', '配送员电话', '处理状态', '异常原因'];
        }
        if ($queueType == 10) {
            $header = ['订单ID', '虚拟发货内容', '处理状态', '异常原因'];
        }
        $title = ['发货记录导出', '发货记录导出' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '批量任务发货记录_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $index => $item) {
                if (in_array($queueType, [7, 8, 9])) {
                    $one_data = [
                        'order_id' => $item['order_id'],
                        'delivery_name' => $item['delivery_name'],
                        'delivery_id' => $item['delivery_id'],
                        'status_cn' => $item['status_cn'],
                        'error' => $item['error'],
                    ];
                } else {
                    $one_data = [
                        'order_id' => $item['order_id'],
                        'fictitious_content' => $item['fictitious_content'],
                        'status_cn' => $item['status_cn'],
                        'error' => $item['error'],
                    ];
                }
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 物流公司对照表
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function expressList($data = [], $type = 1)
    {
        $header = ['物流公司名称', '物流公司编码'];
        $title = ['物流公司对照表导出', '物流公司对照表导出' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '物流公司对照表_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $index => $item) {
                $one_data = [
                    'name' => $item['name'],
                    'code' => $item['code'],
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 交易统计
     * @param array $data
     * @param string $tradeTitle
     * @param int $type
     * @return array|mixed
     */
    public function tradeData($data = [], $tradeTitle = "交易统计", $type = 1)
    {
        $header = ['时间'];
        $title = [$tradeTitle, $tradeTitle, ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = $tradeTitle . '_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $headerArray = array_column($data['series'], 'name');
            $header = array_merge($header, $headerArray);
            $export = [];
            foreach ($data['series'] as $index => $item) {
                foreach ($data['x'] as $k => $v) {
                    $export[$v]['time'] = $v;
                    $export[$v][] = $item['value'][$k];
                }
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }


    /**
     * 商品统计
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function productTrade($data = [], $type = 1)
    {
        $header = ['日期/时间', '商品浏览量', '商品访客数', '加购件数', '下单件数', '支付件数', '支付金额', '成本金额', '退款金额', '退款件数', '访客-支付转化率'];
        $title = ['商品统计', '商品统计' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '商品统计_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $value) {
                $one_data = [
                    'time' => $value['time'],
                    'browse' => $value['browse'],
                    'user' => $value['user'],
                    'cart' => $value['cart'],
                    'order' => $value['order'],
                    'payNum' => $value['payNum'],
                    'pay' => $value['pay'],
                    'cost' => $value['cost'],
                    'refund' => $value['refund'],
                    'refundNum' => $value['refundNum'],
                    'changes' => $value['changes'] . '%'
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 用户统计
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function userTrade($data = [], $type = 1)
    {
        $header = ['日期/时间', '访客数', '浏览量', '新增用户数', '成交用户数', '访客-支付转化率', '付费会员数', '充值用户数', '客单价'];
        $title = ['用户统计', '用户统计' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '用户统计_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $value) {
                $one_data = [
                    'time' => $value['time'],
                    'user' => $value['user'],
                    'browse' => $value['browse'],
                    'new' => $value['new'],
                    'paid' => $value['paid'],
                    'changes' => $value['changes'] . '%',
                    'vip' => $value['vip'],
                    'recharge' => $value['recharge'],
                    'payPrice' => $value['payPrice'],
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }


    /**
     * 导出积分兑换订单
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function storeIntegralOrder($data = [], $type = 1)
    {
        $header = ['订单号', '电话', '收货人姓名', '收货人电话', '收货地址', '商品信息', '订单状态', '下单时间', '用户备注'];
        $title = ['积分兑换订单导出', '订单信息' . time(), ' 生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '积分兑换订单导出_' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $item) {
                $one_data = [
                    'order_id' => $item['order_id'],
                    'phone' => $item['user_phone'],
                    'real_name' => $item['real_name'],
                    'user_phone' => $item['user_phone'],
                    'user_address' => $item['user_address'],
                    'goods_name' => $item['store_name'],
                    'status_name' => $item['status_name'] ?? '未知状态',
                    'add_time' => $item['add_time'],
                    'mark' => $item['mark']
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 门店账单导出
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function financeRecord($data = [], $name = '账单导出', $type = 1)
    {
        $header = ['交易单号', '关联订单', '交易时间', '交易金额', '支出收入', '交易人', '关联店员', '交易类型', '支付方式'];
        $title = [$name, $name . time(), '生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = $name . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $key => $item) {
                $one_data = [
                    'order_id' => $item['order_id'],
                    'link_id' => $item['link_id'],
                    'trade_time' => $item['trade_time'],
                    'number' => $item['number'],
                    'pm' => $item['pm'] == 1 ? '收入' : '支出',
                    'user_nickname' => $item['user_nickname'],
                    'staff_name' => $item['staff_name'] ?? '',
                    'type_name' => $item['type_name'],
                    'pay_type_name' => $item['pay_type_name'],
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function vipOrder(array $data, int $type = 1)
    {
        $header = ['订单号', '用户名', '手机号', '会员类型', '有效期限', '支付金额', '支付方式', '购买时间', '到期时间'];
        $title = ['会员订单', '会员订单' . time(), '生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '会员订单' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $key => $item) {
                $one_data = [
                    'order_id' => $item['order_id'],
                    'nickname' => $item['user']['nickname'],
                    'phone' => $item['user']['phone'],
                    'member_type' => $item['member_type'],
                    'vip_day' => $item['vip_day'],
                    'pay_price' => $item['pay_price'],
                    'pay_type' => $item['pay_type'],
                    'pay_time' => $item['pay_time'],
                    'overdue_time' => $item['overdue_time']
                ];
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }

    /**
     * 发票导出
     * @param array $data
     * @param int $type
     * @return array|mixed
     */
    public function invoiceRecord(array $data, int $type = 1)
    {
        $header = ['订单号', '订单金额', '发票类型', '发票抬头类型', '下单时间', '开票状态', '订单状态'];
        $title = ['发票导出', '发票导出' . time(), '生成时间：' . date('Y-m-d H:i:s', time())];
        $filename = '发票导出' . date('YmdHis', time());
        $export = [];
        $filekey = [];
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $key => $item) {
                $one_data = [
                    'order_id' => $item['order_id'],
                    'pay_price' => $item['pay_price'],
                    'type' => $item['type'] == 1 ? '电子普通发票' : '纸质专用发票',
                    'header_type' => $item['header_type'] == 1 ? '个人' : '企业',
                    'add_time' => $item['add_time'],
                    'is_invoice' => $item['is_invoice'] == 1 ? '已开票' : '未开票'
                ];
                if ($item['refund_status'] > 0) {
                    if ($item['refund_status'] == 1) {
                        $one_data['status'] = '退款中';
                    } else {
                        $one_data['status'] = '已退款';
                    }
                } else {
                    if ($item['status'] == 0) {
                        $one_data['status'] = '未发货';
                    } elseif ($item['status'] == 1) {
                        $one_data['status'] = '待收货';
                    } elseif ($item['status'] == 2) {
                        $one_data['status'] = '待评价';
                    } elseif ($item['status'] == 3) {
                        $one_data['status'] = '已完成';
                    }
                }
                if ($type == 1) {
                    $export[] = $one_data;
                    if ($i == 0) {
                        $filekey = array_keys($one_data);
                    }
                } else {
                    $export[] = array_values($one_data);
                }
                $i++;
            }
        }
        if ($type == 1) {
            return compact('header', 'filekey', 'export', 'filename');
        } else {
            return $this->export($header, $title, $export, $filename);
        }
    }
}
