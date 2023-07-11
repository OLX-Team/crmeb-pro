// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

import request from "@/utils/request.js";

/**
 * 获取企业微信基础配置
 * 
 */
export function getWorkConfig(url) {
	return request.get('work/config?url=' + url,{},{
		noAuth: true
	});
}

/**
 * 获取企业微信配置
 * 
 */
export function getWorkAgentConfig(url) {
	return request.get('work/agentConfig?url=' + url,{},{
		noAuth: true
	});
}

/**
 * 获取客户信息详情
 * 
 */
export function getWorkAgentInfo(data) {
	return request.get('work/client/info',data,{
		noAuth: true
	});
}

/**
 * 获取客户订单列表
 * 
 */
export function getWorkOrderList(data) {
	return request.get('work/order/list',data,{
		noAuth: true
	});
}

/**
 * 获取客户订单详情
 * 
 */
export function getWorkOrderInfo(id,data) {
	return request.get(`work/order/info/${id}`,data,{
		noAuth: true
	});
}

/**
 * 购买商品记录
 * 
 */
export function getWorkCartList(data) {
	return request.get(`work/product/cart_list`,data,{
		noAuth: true
	});
}

/**
 * 浏览记录商品记录
 * 
 */
export function getWorkVisitInfo(data) {
	return request.get(`work/product/visit_list`,data,{
		noAuth: true
	});
}

/**
 * 获取客户群详情
 * 
 */
export function getWorkGroupInfo(data) {
	return request.get(`work/groupInfo`,data,{
		noAuth: true
	});
}

/**
 * 获取群成员列表
 * 
 */
export function getWorkGroupMember(id,data) {
	return request.get(`work/groupMember/${id}`,data,{
		noAuth: true
	});
}