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
 * 获取产品详情
 * @param int id
 * 
 */
export function getProductDetail(id,data) {
	return request.get('product/detail/' + id, data, {
		noAuth: true
	});
}

/**
 * 产品分享二维码 推广员
 * @param int id
 */
// #ifdef H5  || APP-PLUS
export function getProductCode(id) {
	return request.get('product/code/' + id, {});
}
// #endif
// #ifdef MP
export function getProductCode(id) {
	return request.get('product/code/' + id, {
		user_type: 'routine'
	});
}
// #endif

/**
 * 添加收藏
 * @param int id
 * @param string category product=普通产品,product_seckill=秒杀产品
 */
export function collectAdd(id, category) {
	return request.post('collect/add', {
		id: id,
		'category': category === undefined ? 'product' : category
	});
}

/**
 * 删除收藏产品
 * @param int id
 * @param string category product=普通产品,product_seckill=秒杀产品
 */
export function collectDel(id, category) {
	return request.post('collect/del', {
		id: id,
		category: category === undefined ? 'product' : category
	});
}

/**
 * 购车添加
 * 
 */
export function postCartAdd(data) {
	return request.post('cart/add', data);
}

/**
 * 获取分类列表
 * 
 */
export function getCategoryList() {
	return request.get('category', {}, {
		noAuth: true
	});
}

/**
 * 获取产品列表
 * @param object data
 */
export function getProductslist(data) {
	return request.get('products', data, {
		noAuth: true
	});
}



/**
 * 获取推荐产品
 * 
 */
export function getProductHot(page, limit) {
	return request.get("product/hot", {
		page: page === undefined ? 1 : page,
		limit: limit === undefined ? 4 : limit
	}, {
		noAuth: true
	});
}
/**
 * 批量收藏
 * 
 * @param object id  产品编号 join(',') 切割成字符串
 * @param string category 
 */
export function collectAll(id, category) {
	return request.post('collect/all', {
		id: id,
		category: category === undefined ? 'product' : category
	});
}

/**
 * 首页产品的轮播图和产品信息
 * @param int type 
 * 
 */
export function getGroomList(type, data) {
	return request.get('groom/list/' + type, data, {
		noAuth: true
	});
}

/**
 * 获取收藏列表
 * @param object data
 */
export function getCollectUserList(data) {
	return request.get('collect/user', data)
}

/**
 * 获取浏览记录列表
 * @param object data
 */
export function getVisitList(data) {
	return request.get('user/visit_list', data)
}

/**
 * 获取浏览记录列表-删除
 * @param object data
 */
export function deleteVisitList(data) {
	return request.delete('user/visit', data)
}

/**
 * 获取产品评论
 * @param int id
 * @param object data
 * 
 */
export function getReplyList(id, data) {
	return request.get('v2/reply/list/' + id, data,{noAuth: true})
}

/**
 * 产品评价数量和好评度
 * @param int id
 */
export function getReplyConfig(id) {
	return request.get('reply/config/' + id,{},{noAuth: true});
}

/**
 * 评论点赞
 * @param int id
 */
export function getReplyPraise(id) {
	return request.post('reply/reply_praise/' + id);
}

/**
 * 取消评论点赞
 * @param int id
 */
export function getUnReplyPraise(id) {
	return request.post('reply/un_reply_praise/' + id);
}

/**
 * 获取评论详情
 * @param int id
 */
export function getReplyInfo(id) {
	return request.get('reply/info/' + id);
}

/**
 * 获取评论回复列表
 * @param int id
 */
export function getReplyComment(id,data) {
	return request.get('reply/comment/' + id,data);
}

/**
 * 评论回复点赞
 * @param int id
 */
export function postReplyPraise(id) {
	return request.post('reply/praise/' + id);
}

/**
 * 取消评论回复点赞
 * @param int id
 */
export function postUnReplyPraise(id) {
	return request.post('reply/un_praise/' + id);
}

/**
 * 保存商品评价回复
 * @param int id
 */
export function replyComment(id,data) {
	return request.post('reply/comment/' + id,data);
}

/**
 * 获取搜索关键字获取
 * 
 */
export function getSearchKeyword() {
	return request.get('search/keyword', {}, {
		noAuth: true
	});
}

/**
 * 门店列表
 * @returns {*}
 */
export function storeListApi(data) {
	return request.get("store_list", data, {
		noAuth: true
	});
}

/**
 * 套餐列表
 * @param int id
 * 
 */
export function storeDiscountsList(id) {
	return request.get('store_discounts/list/' + id, {}, {
		noAuth: true
	});
}

/**
 * 购车添加、减少、修改
 * 
*/
export function postCartNum(data) {
  return request.post('v2/set_cart_num', data);
}

/**
 * 获取首页的属性
 * @returns {*}
 */
export function getAttr(id,type) {
  return request.get("v2/get_attr/"+id+"/"+type);
}

/**
 * 获取店员推广微信会员卡二维码
 */
export function storeCardApi() {
	return request.get("store/staff/card/code");
}

/**
 * 分类列表-品牌列表
 */
export function brand(data) {
	return request.get("brand",data,{
		noAuth: true
	});
}