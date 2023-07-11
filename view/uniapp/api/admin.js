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
 * 统计数据
 */
export function getStatisticsInfo() {
	return request.get("admin/order/statistics", {}, {
		login: true
	});
}
/**
 * 订单月统计
 */
export function getStatisticsMonth(where) {
	return request.get("admin/order/data", where, {
		login: true
	});
}
/**
 * 订单月统计
 */
export function getAdminOrderList(where) {
	return request.get("admin/order/list", where, {
		login: true
	});
}
/**
 * 订单改价
 */
export function setAdminOrderPrice(data) {
	return request.post("admin/order/price", data, {
		login: true
	});
}
/**
 * 订单备注
 */
export function setAdminOrderRemark(data) {
	return request.post("admin/order/remark", data, {
		login: true
	});
}
/**
 * 订单备注（退款）
 */
export function setAdminRefundRemark(data) {
	return request.post("admin/refund_order/remark", data, {
		login: true
	});
}
/**
 * 订单详情
 */
export function getAdminOrderDetail(orderId) {
	return request.get("admin/order/detail/" + orderId, {}, {
		login: true
	});
}
/**
 * 订单详情(退款)
 */
export function getAdminRefundDetail(orderId) {
	return request.get("admin/refund_order/detail/" + orderId, {}, {
		login: true
	});
}

/**
 * 订单发货信息获取
 */
export function getAdminOrderDelivery(orderId) {
	return request.get(
		"admin/order/delivery/gain/" + orderId, {}, {
			login: true
		}
	);
}

/**
 * 订单发货保存
 */
export function setAdminOrderDelivery(id, data) {
	return request.post("admin/order/delivery/keep/" + id, data, {
		login: true
	});
}
/**
 * 订单统计图
 */
export function getStatisticsTime(data) {
	return request.get("admin/order/time", data, {
		login: true
	});
}
/**
 * 线下付款订单确认付款
 */
export function setOfflinePay(data) {
	return request.post("admin/order/offline", data, {
		login: true
	});
}
/**
 * 订单确认退款
 */
export function setOrderRefund(data) {
	return request.post("admin/order/refund", data, {
		login: true
	});
}

/**
 * 获取快递公司
 * @returns {*}
 */
export function getLogistics(data) {
	return request.get("logistics", data, {
		login: false
	});
}

/**
 * 订单核销
 * @returns {*}
 */
export function orderVerific(verify_code, is_confirm) {
	return request.post("order/order_verific", {
		verify_code,
		is_confirm
	});
}

/**
 * 获取物流公司模板
 * @returns {*}
 */
export function orderExportTemp(data) {
	return request.get("admin/order/export_temp", data);
}

/**
 * 获取订单打印默认配置
 * @returns {*}
 */
export function orderDeliveryInfo() {
	return request.get("admin/order/delivery_info");
}

/**
 * 配送员列表
 * @returns {*}
 */
export function orderOrderDelivery() {
	return request.get("admin/order/delivery");
}


// 门店

/**
 * 用户信息
 */
export function userInfo() {
	return request.get("store/staff/info");
}


/**
 * 门店中心-订单统计
 */
export function orderInfo(data) {
	return request.get("store/order/statistics",data);
}



/**
 * 门店中心-统计菜单
 */
export function statisticsMenuApi(data) {
	return request.get("store/staff/statistics",data);
}



/**
 * 门店中心-详细数据列表
 */
export function getListApi(data) {
	return request.get("store/order/data",data);
}


/**
 * 门店中心-数据详情-列表
 */
export function getStatisticsListApi(type,data) {
	return request.get("store/staff/data/"+type,data);
}


/**
 * 门店中心-订单管理列表
 */
export function getOrderlistApi(data) {
	return request.get("store/order/list",data);
}

/**
 * 门店中心-订单管理列表(退款)
 */
export function getRefundlistApi(data) {
	return request.get("store/refund/list",data);
}

/**
 * 门店中心-订单管理备注
 */
export function getOrderreMarkApi(data) {
	return request.post("store/order/remark",data);
}

/**
 * 门店中心-订单管理备注（退款）
 */
export function getRefundMarkApi(data) {
	return request.post("store/refund/remark",data);
}

/**
 * 门店中心-订单管理改价
 */
export function getOrderPriceApi(data) {
	return request.post("store/order/price",data);
}

/**
 * 门店中心-订单管理确定付款
 */
export function getOrderOfflineApi(data) {
	return request.post("store/order/offline",data);
}


/**
 * 门店中心-去发货-用户
 */
export function getOrderDeliveryinfoApi(id) {
	return request.get("store/order/delivery_info/"+id);
}

/**
 * 门店中心-去发货-获取快递公司
 */
export function getOrderExportApi(data) {
	return request.get("store/order/export_all", data, {
		login: false
	});
}
/**
 * 门店中心-去发货-获取物流公司模板
 * @returns {*}
 */
export function getOrderExportTemp(data) {
	return request.get("store/order/export_temp", data);
}

/**
 * 门店中心-去发货-订单发货保存
 */
export function setOrderDelivery(id, data) {
	return request.post("store/order/delivery/" + id, data, {
		login: true
	});
}
/**
 * 门店中心-去发货-获取配送员列表
 * @returns {*}
 */
export function getOrderDelivery() {
	return request.get("store/delivery/list");
}
/**
 * 门店中心-订单确认退款
 */
export function OrderRefund(data) {
	return request.post("store/order/refund", data, {
		login: true
	});
}

/**
 * 门店中心-订单详情
 */
export function OrderDetail(id) {
	return request.get("store/order/detail/"+id);
}

/**
 * 门店中心-订单详情（退款）
 */
export function refundDetail(id) {
	return request.get("store/refund/detail/"+id);
}


/**
 * 配送员-获取用户信息
 */
export function deliveryInfo(id) {
	return request.get("store/delivery/info");
}


/**
 * 配送员-获取配送统计数据
 */
export function deliveryStatistics(data) {
	return request.get("store/delivery/statistics",data);
}


/**
 * 配送员-获取配送统计数据列表
 */
export function deliveryList(data) {
	return request.get("store/delivery/data",data);
}



/**
 * 配送员-获取订单列表数据列表
 */
export function deliveryOrderList(data) {
	return request.get("store/delivery/order",data);
}


/**
 * 门店中心-订单取消、删除
 */
export function OrderDel(id) {
	return request.delete("store/order/del/"+id);
}


/**
 * 门店中心-订单取消、取消
 */
export function OrderCancel(id) {
	return request.post("store/order/cancel/"+id);
}


/**
 * 配送员-扫码核销获取订单信息
 */
export function orderWriteoffInfo(type,data) {
	return request.get("store/order/writeoff_info/"+type,data);
}




/**
 * 配送员-核销订单获取商品信息
 */
export function orderCartInfo(type,data) {
	return request.get("store/order/cart_info/"+type,data);
}


/**
 * 配送员-订单核销
 */
export function orderWriteoff(type,data) {
	return request.post("store/order/writeoff/"+type,data);
}

/**
 * 统计管理-获取订单可拆分商品列表
 */
export function orderSplitInfo(id) {
	return request.get("admin/order/split_cart_info/"+id);
}

/**
 * 统计管理-提交
 */
export function orderSplitDelivery(id,data) {
	return request.put("admin/order/split_delivery/"+id,data);
}

/**
 * 统计管理-退货退款
 */
export function orderRefundAgree(id) {
	return request.post("admin/order/refund_agree/"+id);
}

/**
 * 门店中心-获取订单可拆分商品列表
 */
export function storeSplitInfo(id) {
	return request.get("store/order/split_cart_info/"+id);
}

/**
 * 门店中心-提交
 */
export function storeSplitDelivery(id,data) {
	return request.put("store/order/split_delivery/"+id,data);
}

/**
 * 门店中心-退货退款
 */
export function storeRefundAgree(id) {
	return request.post("store/order/refund_agree/"+id);
}

/**
 * 平台-退款列表
 */
export function adminRefundList(data) {
	return request.get("admin/refund_order/list",data);
}



