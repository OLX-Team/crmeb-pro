<template>
	<view :style="colorStyle">
		<view class='productList'>
			<view class='search bg-color acea-row row-between-wrapper'>
				<view v-if="promotions_type" class="iconfont icon-fanhui2 fanhui" @click="goCart"></view>
				<view class="home acea-row row-center-wrapper" @click="moreNav" v-else>
					<!-- <view class="iconfont icon-fanhui2"></view>
					<view class="line"></view> -->
					<view class="iconfont icon-gengduo2"></view>
				</view>
				<view class='input acea-row row-between-wrapper' :class="promotions_type?'on':''"><text
						class='iconfont icon-sousuo'></text>
					<input placeholder='搜索商品名称' placeholder-class='placeholder' confirm-type='search' name="search"
						:value='where.keyword' @confirm="searchSubmit"></input>
				</view>
				<view class='iconfont' v-if="!promotions_type"
					:class='is_switch==true?"icon-pailie":"icon-tupianpailie"' @click='Changswitch'>
				</view>
			</view>
			<view class='nav acea-row row-middle row-around'>
				<view class='item line1' :class='title ? "font-num":""' @click='set_where(1)'>{{title ? title:'默认'}}
				</view>
				<view class='item' @click='set_where(2)'>
					价格
					<image v-if="price==1" src='../../static/images/up.png'></image>
					<image v-else-if="price==2" src='../../static/images/down.png'></image>
					<image v-else src='../../static/images/horn.png'></image>
				</view>
				<view class='item' @click='set_where(3)'>
					销量
					<image v-if="stock==1" src='../../static/images/up.png'></image>
					<image v-else-if="stock==2" src='../../static/images/down.png'></image>
					<image v-else src='../../static/images/horn.png'></image>
				</view>
				<!-- down -->
				<!-- <view class='item' :class='nows ? "font-color":""' @click='set_where(4)'>新品</view> -->
				<view class='item' v-if="brandList.length" :class="{clored:brandArray.length>0}" @click='set_brand'>品牌
					<image src='../../static/images/xiala.png'></image>
				</view>
				<homeList :navH="navH" :goodList="goodList" :currentPage="currentPage" :sysHeight="sysHeight"
					:goodsShow="true"></homeList>
			</view>
			<view class='list acea-row row-between-wrapper' :class='is_switch==true?"":"on"' v-if="is_switch==false">
				<view class="title"><text class="label">{{promotionsInfo.title}}</text>{{promotionsInfo.desc}}</view>
				<view class='item' :class='is_switch==true?"":"on"' hover-class='none'
					v-for="(item,index) in productList" :key="index" @click="godDetail(item)">
					<view class='pictrue' :class='is_switch==true?"":"on"'>
						<image :src='item.image' :class='is_switch==true?"":"on"'></image>
						<view class="masks acea-row row-center-wrapper" v-if="item.stock<=0">
							<view class="bg">
								<view>暂时</view>
								<view>售罄</view>
							</view>
						</view>
					</view>
					<view class='text' :class='is_switch==true?"":"on"'>
						<view class="nameCon">
							<view class='name line1'>{{item.store_name}}</view>
							<text class="label"
								v-if="item.activity && item.activity.type === '1' && !promotions_type">秒杀</text>
							<text class="label"
								v-if="item.activity && item.activity.type === '2' && !promotions_type">砍价</text>
							<text class="label"
								v-if="item.activity && item.activity.type === '3' && !promotions_type">拼团</text>
							<text class="label" v-if="item.promotions.title">{{item.promotions.title}}</text>
						</view>
						<view class="vip acea-row row-middle on">
							<view class='money font-color' :class='is_switch==true?"":"on"'>￥<text
									class='num'>{{item.price}}</text></view>
							<view class='vip-money acea-row row-middle' v-if="item.vip_price && item.vip_price > 0">
								<view>￥{{item.vip_price}}</view>
								<view class="icon on" v-if="item.price_type && item.price_type == 'member'"><text
										class="iconfont icon-huangguan4"></text>SVIP</view>
								<view class="icon" v-if="item.price_type && item.price_type == 'level'"><text
										class="iconfont icon-dengjitubiao"></text>{{item.level_name}}</view>
							</view>
						</view>
						<view class='sales acea-row row-between-wrapper'>
							<view class="acea-row">
								<view>已售{{item.sales}}{{item.unit_name || '件'}}</view>
								<view class="score">评分 {{item.star}}</view>
							</view>
							<view v-if="promotions_type && item.product_type==0 && !item.custom_form.length"
								class="icon acea-row row-center-wrapper" @click.stop="joinCart(item)">
								<text class="iconfont icon-gouwuche"></text>
							</view>
						</view>
					</view>
				</view>
				<view class='loadingicon acea-row row-center-wrapper' v-if='productList.length > 0'>
					<text class='loading iconfont icon-jiazai' :hidden='loading==false'></text>{{loadTitle}}
				</view>
				<view style="height: 100rpx;width: 100%;" v-if="productList.length>3"></view>
				<view class="footer acea-row row-between-wrapper">
					<view class="acea-row row-middle">
						<view class="icon">
							<text class="iconfont icon-pinzhongqiquan"></text>
							<view class="num acea-row row-center-wrapper">{{totalNum}}</view>
						</view>
						<view class="right">
							<view class="num">小计：<text class="money">¥{{totalPrice || 0}}</text></view>
							<view v-if="this.promotions_type != 4">
								<view v-if="promotionsInfo.differ_threshold>0">
									再买{{promotionsInfo.differ_threshold}}{{promotionsInfo.threshold_type==1?'元':'件'}}<text
										v-if="promotionsInfo.differ_price || promotionsInfo.differ_discount">，
										 <text v-if="promotionsInfo.differ_price">可减{{promotionsInfo.differ_price}}元</text>
											<text v-else>可打{{parseFloat(promotionsInfo.differ_discount)/10}}折</text>
										</text>
								</view>
								<view v-else>
									<text v-if="promotionsInfo.reach_threshold">已购满{{promotionsInfo.reach_threshold}}{{promotionsInfo.threshold_type==1?'元':'件'}},</text>已减{{promotionsInfo.sum_promotions_price}}元
								</view>
							</view>
						</view>
					</view>
					<view class="bnt" @click="goCart">去购物车</view>
				</view>
			</view>
			<view class="list waterList" v-else>
				<waterfallsFlow ref="waterfallsFlow" :list="productList" @wapper-lick="godDetail">
					<!--  #ifdef  MP-WEIXIN -->
					<view v-for="(item, index) of productList" :key="index" slot="slot{{index}}">
						<view class="waterfalls">
							<view class='name line2'>{{item.store_name}}</view>
							<span class="label"
								v-if="item.activity && item.activity.type === '1' && !promotions_type">秒杀</span>
							<span class="label"
								v-if="item.activity && item.activity.type === '2' && !promotions_type">砍价</span>
							<span class="label"
								v-if="item.activity && item.activity.type === '3' && !promotions_type">拼团</span>
							<text class="label" v-if="item.promotions.title">{{item.promotions.title}}</text>
							<view class="vip acea-row row-middle">
								<view class='money font-color'>
									￥<text class='num'>{{item.price.toString().split(".")[0]}}</text>
									<text class='nums'
										v-if="item.price.toString().split('.').length>1">.{{item.price.toString().split(".")[1]}}</text>
								</view>
								<view class='vip-money acea-row row-middle' v-if="item.vip_price && item.vip_price > 0">
									<view>￥{{item.vip_price}}</view>
									<!-- 	<image src='../../static/images/vip.png' v-if="item.price_type == 'member'"></image> -->
									<view class="icon on" v-if="item.price_type && item.price_type == 'member'"><text
											class="iconfont icon-huangguan4"></text>SVIP</view>
									<view class="icon" v-if="item.price_type && item.price_type == 'level'"><text
											class="iconfont icon-dengjitubiao"></text>{{item.level_name}}</view>
								</view>
							</view>
							<view class='vip acea-row row-between-wrapper'>
								<view>已售{{item.sales}}{{item.unit_name || '件'}}</view>
								<view>评分 {{item.star}}</view>
							</view>
						</view>
					</view>
					<!--  #endif -->

					<!-- #ifndef  MP-WEIXIN -->
					<template v-slot:default="item">
						<view class="waterfalls">
							<view class='name line2'>{{item.store_name}}</view>
							<span class="label"
								v-if="item.activity && item.activity.type === '1' && !promotions_type">秒杀</span>
							<span class="label"
								v-if="item.activity && item.activity.type === '2' && !promotions_type">砍价</span>
							<span class="label"
								v-if="item.activity && item.activity.type === '3' && !promotions_type">拼团</span>
							<text class="label" v-if="item.promotions.title">{{item.promotions.title}}</text>
							<view class="vip acea-row row-middle">
								<view class='money font-color'>
									￥<text class='num'>{{item.price.toString().split(".")[0]}}</text>
									<text class='nums'
										v-if="item.price.toString().split('.').length>1">.{{item.price.toString().split(".")[1]}}</text>
								</view>
								<view class='vip-money acea-row row-middle' v-if="item.vip_price && item.vip_price > 0">
									<view>￥{{item.vip_price}}</view>
									<view class="icon on" v-if="item.price_type && item.price_type == 'member'"><text
											class="iconfont icon-huangguan4"></text>SVIP</view>
									<view class="icon" v-if="item.price_type && item.price_type == 'level'"><text
											class="iconfont icon-v"></text>{{item.level_name}}</view>
								</view>
							</view>
							<view class='vip acea-row row-between-wrapper'>
								<view>已售{{item.sales}}{{item.unit_name || '件'}}</view>
								<view>评分 {{item.star}}</view>
							</view>
						</view>
					</template>
					<!-- #endif -->
				</waterfallsFlow>
				<view class='loadingicon acea-row row-center-wrapper' v-if='productList.length > 0'>
					<text class='loading iconfont icon-jiazai' :hidden='loading==false'></text>{{loadTitle}}
				</view>
			</view>
		</view>
		<view class='noCommodity' v-if="productList.length==0 && where.page > 1">
			<view class='pictrue'>
				<image src='../../static/images/noShopper.png'></image>
			</view>
			<recommend :hostProduct="hostProduct"></recommend>
		</view>
		<productWindow :attr="attr" :isShow='1' :iSplus='1' :iScart='1' :storeInfo='storeInfo' @myevent="onMyEvent"
			@ChangeAttr="ChangeAttr" @ChangeCartNum="ChangeCartNum" @attrVal="attrVal" @iptCartNum="iptCartNum"
			@goCat="goCat" id='product-window' :is_vip="is_vip"></productWindow>
		<home v-if="navigation"></home>
		<view class="mask" @touchmove.prevent :hidden="brandtip === false" @click="closeBrand"></view>
		<view class="selectbrand" :class="brandtip === true ? 'on' : ''">
			<view class="box">
				<view class="selet">
					<view class="seletbox acea-row row-center-wrapper"
						:class="{seleton:brandArray.indexOf(item.id) != -1}" v-for="(item,index) in brandList"
						:key="index" @click="seletBrand(item.id)">{{item.brand_name}}</view>
				</view>

				<view class="btn">
					<div class="sambox reset" @click="brandReset">重置</div>
					<div class="sambox ok" @click="brandOk">确定</div>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import home from '@/components/home';
	import homeList from '@/components/homeList'
	import productWindow from '@/components/productWindow';
	import waterfallsFlow from "@/components/maramlee-waterfalls-flow/maramlee-waterfalls-flow.vue";
	import {
		getProductslist,
		getProductHot,
		brand,
		getAttr,
		postCartAdd
	} from '@/api/store.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import recommend from '@/components/recommend';
	import {
		mapGetters
	} from "vuex";
	import {
		goShopDetail
	} from '@/libs/order.js';
	import {
		getCartList,
		getCartCounts
	} from '@/api/order.js';
	import colors from '@/mixins/color.js';
	export default {
		computed: mapGetters(['uid', 'isLogin']),
		components: {
			recommend,
			home,
			homeList,
			waterfallsFlow,
			productWindow
		},
		mixins: [colors],
		data() {
			return {
				id: 0,
				productValue: [], //系统属性
				is_vip: 0, //是否是会员
				attr: {
					cartAttr: false,
					productAttr: [],
					productSelect: {}
				},
				attrValue: '', //已选属性
				navH: 22,
				sysHeight: 0,
				goodList: true,
				currentPage: false,
				brandtip: false, //品牌弹窗
				brandArray: [],
				productList: [],
				is_switch: true,
				where: {
					sid: 0,
					keyword: '',
					priceOrder: '',
					salesOrder: '',
					news: 0,
					page: 1,
					limit: 20,
					cid: 0,
					brand_id: [],
					promotions_id: 0
				},
				price: 0,
				stock: 0,
				nows: false,
				loadend: false,
				loading: false,
				loadTitle: '加载更多',
				title: '',
				hostProduct: [],
				hotPage: 1,
				hotLimit: 10,
				hotScroll: false,
				brandList: [],
				storeInfo: {},
				promotions_type: 0,
				totalPrice: 0,
				promotionsInfo: {},
				totalNum: 0
			};
		},
		onLoad: function(options) {
			this.where.cid = options.cid || 0;
			this.$set(this.where, 'sid', options.sid || 0);
			this.title = options.title || '';
			this.$set(this.where, 'keyword', options.searchValue || '');
			this.$set(this.where, 'productId', options.productId || '');
			if (options.promotions_type) {
				this.promotions_type = options.promotions_type;
				this.where.promotions_id = options.promotions_id;
				if (options.promotions_type) {
					this.is_switch = false
				}
			}
			this.get_product_list();
			this.getBrand();
		},
		onShow() {
			if (this.isLogin === false) {
				toLogin();
			} else {
				if (!this.is_switch) {
					this.getCartList();
					this.getCartNum();
				}
			}
		},
		methods: {
			getCartNum() {
				getCartCounts().then(res => {
					this.totalNum = res.data.count;
				}).catch(err => {
					return this.$util.Tips({
						title: err
					});
				})
			},
			getCartList() {
				let truePrice = 0;
				getCartList().then(res => {
					let data = res.data,
						valid = res.data.valid;
					valid.forEach(item => {
						item.promotions.forEach(z => {
							if (this.where.promotions_id == z.id) {
								this.promotionsInfo = z;
							}
						})
						item.cart.forEach(j => {
							truePrice = this.$util.$h.Add(truePrice, this.$util.$h.Mul(j.truePrice, j.cart_num));
						})
					})
					this.totalPrice = this.$util.$h.Sub(truePrice, data.deduction.coupon_price)
				}).catch(err => {
					return this.$util.Tips({
						title: err
					});
				})
			},
			goCart() {
				uni.switchTab({
					url: '/pages/order_addcart/order_addcart'
				})
			},
			/*
			 * 加入购物车
			 */
			goCat: function(news) {
				let that = this,
					productSelect = that.productValue[this.attrValue];
				//如果有属性,没有选择,提示用户选择
				if (that.attr.productAttr.length && productSelect === undefined)
					return that.$util.Tips({
						title: '产品库存不足，请选择其它属性'
					});
				if (that.attr.productSelect.cart_num <= 0) {
					that.attr.productSelect.cart_num = 1
					return that.$util.Tips({
						title: '请先选择属性'
					});
				}
				let q = {
					productId: that.id,
					cartNum: that.attr.productSelect.cart_num,
					new: 0,
					uniqueId: that.attr.productSelect !== undefined ? that.attr.productSelect.unique : ''
				};
				postCartAdd(q)
					.then(function(res) {
						that.isOpen = false;
						that.attr.cartAttr = false;
						if (news) {
							uni.navigateTo({
								url: '/pages/users/order_confirm/index?new=1&cartId=' + res.data.cartId
							});
						} else {
							that.$util.Tips({
								title: '添加购物车成功'
							});
						}
						that.getCartNum();
						that.getCartList();
						
					})
					.catch(err => {
						that.isOpen = false;
						return that.$util.Tips({
							title: err
						});
					});
			},
			/**
			 * 打开属性加入购物车
			 *
			 */
			joinCart: function(item) {
				//是否登录
				if (this.isLogin === false) {
					toLogin();
				} else {
					uni.showLoading({
						title: '加载中'
					});
					this.getAttrs(item.id);
					this.$set(this, 'id', item.id);
					this.$set(this.attr, 'cartAttr', true);
				}
			},
			// 商品详情接口；
			getAttrs(id) {
				let that = this;
				getAttr(id, 0).then(res => {
					uni.hideLoading();
					that.$set(that.attr, 'productAttr', res.data.productAttr);
					that.$set(that, 'productValue', res.data.productValue);
					that.$set(that, 'is_vip', res.data.storeInfo.is_vip);
					that.$set(that, 'storeInfo', res.data.storeInfo);
					that.DefaultSelect();
				})
			},
			/**
			 * 默认选中属性
			 *
			 */
			DefaultSelect: function() {
				let productAttr = this.attr.productAttr;
				let value = [];
				for (var key in this.productValue) {
					if (this.productValue[key].stock > 0) {
						value = this.attr.productAttr.length ? key.split(',') : [];
						break;
					}
				}
				for (let i = 0; i < productAttr.length; i++) {
					this.$set(productAttr[i], 'index', value[i]);
				}
				//sort();排序函数:数字-英文-汉字；
				let productSelect = this.productValue[value.join(',')];
				if (productSelect && productAttr.length) {
					this.$set(this.attr.productSelect, 'store_name', this.storeInfo.store_name);
					this.$set(this.attr.productSelect, 'image', productSelect.image);
					this.$set(this.attr.productSelect, 'price', productSelect.price);
					this.$set(this.attr.productSelect, 'stock', productSelect.stock);
					this.$set(this.attr.productSelect, 'unique', productSelect.unique);
					this.$set(this.attr.productSelect, 'cart_num', 1);
					this.$set(this, 'attrValue', value.join(','));
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
				} else if (!productSelect && productAttr.length) {
					this.$set(this.attr.productSelect, 'store_name', this.storeInfo.store_name);
					this.$set(this.attr.productSelect, 'image', this.storeInfo.image);
					this.$set(this.attr.productSelect, 'price', this.storeInfo.price);
					this.$set(this.attr.productSelect, 'stock', 0);
					this.$set(this.attr.productSelect, 'unique', '');
					this.$set(this.attr.productSelect, 'cart_num', 0);
					this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
					this.$set(this, 'attrValue', '');
				} else if (!productSelect && !productAttr.length) {
					this.$set(this.attr.productSelect, 'store_name', this.storeInfo.store_name);
					this.$set(this.attr.productSelect, 'image', this.storeInfo.image);
					this.$set(this.attr.productSelect, 'price', this.storeInfo.price);
					this.$set(this.attr.productSelect, 'stock', this.storeInfo.stock);
					this.$set(this.attr.productSelect, 'unique', this.storeInfo.unique || '');
					this.$set(this.attr.productSelect, 'cart_num', 1);
					this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
					this.$set(this, 'attrValue', '');
				}
			},
			/**
			 * 购物车手动填写
			 *
			 */
			iptCartNum: function(e) {
				this.$set(this.attr.productSelect, 'cart_num', e);
			},
			attrVal(val) {
				this.$set(this.attr.productAttr[val.indexw], 'index', this.attr.productAttr[val.indexw].attr_values[val
					.indexn]);
			},
			onMyEvent: function() {
				this.$set(this.attr, 'cartAttr', false);
			},
			/**
			 * 属性变动赋值
			 * 
			 */
			ChangeAttr: function(res) {
				let productSelect = this.productValue[res];
				if (productSelect && productSelect.stock > 0) {
					this.$set(this.attr.productSelect, "image", productSelect.image);
					this.$set(this.attr.productSelect, "price", productSelect.price);
					this.$set(this.attr.productSelect, "stock", productSelect.stock);
					this.$set(this.attr.productSelect, "unique", productSelect.unique);
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					this.$set(this.attr.productSelect, "cart_num", 1);
					this.$set(this, "attrValue", res);
				} else if (productSelect && productSelect.stock == 0) {
					this.$set(this.attr.productSelect, "image", productSelect.image);
					this.$set(this.attr.productSelect, "price", productSelect.price);
					this.$set(this.attr.productSelect, "stock", 0);
					this.$set(this.attr.productSelect, "unique", "");
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					this.$set(this.attr.productSelect, "cart_num", 0);
					this.$set(this, "attrValue", "");
				} else {
					this.$set(this.attr.productSelect, "image", this.storeInfo.image);
					this.$set(this.attr.productSelect, "price", this.storeInfo.price);
					this.$set(this.attr.productSelect, "stock", 0);
					this.$set(this.attr.productSelect, "unique", "");
					this.$set(this.attr.productSelect, 'vip_price', this.storeInfo.vip_price);
					this.$set(this.attr.productSelect, "cart_num", 0);
					this.$set(this, "attrValue", "");
				}
			},
			/**
			 * 购物车数量加和数量减
			 *
			 */
			ChangeCartNum: function(changeValue) {
				//changeValue:是否 加|减
				//获取当前变动属性
				let productSelect = this.productValue[this.attrValue];
				//如果没有属性,赋值给商品默认库存
				if (productSelect === undefined && !this.attr.productAttr.length) productSelect = this.attr
					.productSelect;
				//无属性值即库存为0；不存在加减；
				if (productSelect === undefined) return;
				let stock = productSelect.stock || 0;
				let num = this.attr.productSelect;
				if (changeValue) {
					num.cart_num++;
					if (num.cart_num > stock) {
						this.$set(this.attr.productSelect, 'cart_num', stock ? stock : 1);
						this.$set(this, 'cart_num', stock ? stock : 1);
					}
				} else {
					num.cart_num--;
					if (num.cart_num < 1) {
						this.$set(this.attr.productSelect, 'cart_num', 1);
						this.$set(this, 'cart_num', 1);
					}
				}
			},
			moreNav() {
				this.currentPage = !this.currentPage
			},
			// 品牌列表
			getBrand() {
				brand(this.where).then(res => {
					this.brandList = res.data
				}).catch(err => {
					return this.$util.Tips({
						title: err.msg
					});
				})
			},
			//打开品牌弹窗
			set_brand() {
				this.brandtip = true
				this.currentPage = false
			},
			//关闭品牌/新品弹窗
			closeBrand() {
				this.brandtip = false
			},
			// 品牌选择
			seletBrand(id) {
				if (this.brandArray.indexOf(id) == -1) {
					this.brandArray.push(id)
				} else {
					this.brandArray.splice(this.brandArray.indexOf(id), 1)
				}
			},
			brandReset() {
				this.brandArray = []
				this.brandOk();
			},
			brandOk() {
				this.brandtip = false;
				this.loadend = false;
				this.$set(this.where, 'page', 1);
				this.get_product_list(true);
			},
			// 去详情页
			godDetail(item) {
				this.currentPage = false
				if (this.promotions_type) {
					uni.navigateTo({
						url: `/pages/goods_details/index?id=${item.id}`
					})
				} else {
					goShopDetail(item, this.uid).then(res => {
						uni.navigateTo({
							url: `/pages/goods_details/index?id=${item.id}`
						})
					})
				}
			},
			Changswitch: function() {
				let that = this;
				this.currentPage = false
				that.is_switch = !that.is_switch
			},
			searchSubmit: function(e) {
				let that = this;
				this.currentPage = false
				that.$set(that.where, 'keyword', e.detail.value);
				that.loadend = false;
				that.$set(that.where, 'page', 1)
				this.get_product_list(true);
			},
			/**
			 * 获取我的推荐
			 */
			get_host_product: function() {
				let that = this;
				if (that.hotScroll) return
				getProductHot(
					that.hotPage,
					that.hotLimit,
				).then(res => {
					that.hotPage++
					that.hotScroll = res.data.length < that.hotLimit
					that.hostProduct = that.hostProduct.concat(res.data)
					// that.$set(that, 'hostProduct', res.data)
				});
			},
			//点击事件处理
			set_where: function(e) {
				this.currentPage = false
				switch (e) {
					case 1:
						// #ifdef H5
						return history.back();
						// #endif
						// #ifndef H5
						return uni.navigateBack({
							delta: 1,
						})
						// #endif
						break;
					case 2:
						if (this.price == 0) this.price = 1;
						else if (this.price == 1) this.price = 2;
						else if (this.price == 2) this.price = 0;
						this.stock = 0;
						break;
					case 3:
						if (this.stock == 0) this.stock = 1;
						else if (this.stock == 1) this.stock = 2;
						else if (this.stock == 2) this.stock = 0;
						this.price = 0
						break;
					case 4:
						this.nows = !this.nows;
						break;

				}
				this.loadend = false;
				this.$set(this.where, 'page', 1);
				this.get_product_list(true);
			},
			//设置where条件
			setWhere: function() {
				if (this.price == 0) this.where.priceOrder = '';
				else if (this.price == 1) this.where.priceOrder = 'asc';
				else if (this.price == 2) this.where.priceOrder = 'desc';
				if (this.stock == 0) this.where.salesOrder = '';
				else if (this.stock == 1) this.where.salesOrder = 'asc';
				else if (this.stock == 2) this.where.salesOrder = 'desc';
				this.where.news = this.nows ? 1 : 0;
			},
			//查找产品
			get_product_list: function(isPage) {
				let that = this;
				that.setWhere();
				if (that.loadend) return;
				if (that.loading) return;
				if (isPage === true) {
					if (this.is_switch) {
						that.$refs.waterfallsFlow.refresh();
					}
					that.$set(that, 'productList', []);
				}
				that.loading = true;
				that.loadTitle = '';
				that.$set(that.where, 'brand_id', that.brandArray.join(","));
				getProductslist(that.where).then(res => {
					let list = res.data;
					let productList = that.$util.SplitArray(list, that.productList);
					let loadend = list.length < that.where.limit;
					that.loadend = loadend;
					that.loading = false;
					that.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
					that.$set(that, 'productList', productList);
					that.$set(that.where, 'page', that.where.page + 1);
					if (!that.productList.length) this.get_host_product();
				}).catch(err => {
					that.loading = false;
					that.loadTitle = '加载更多';
				});
			},
		},
		onPullDownRefresh() {

		},
		onPageScroll(e) {
			this.currentPage = false
		},
		onReachBottom() {
			if (this.productList.length > 0) {
				this.get_product_list();
			} else {
				this.get_host_product();
			}

		}
	}
</script>

<style scoped lang="scss">
	.home {
		color: #333;
		width: 56rpx;
		height: 56rpx;
		background: rgba(255, 255, 255, 1);
		border: 1px solid rgba(0, 0, 0, 0.1);
		border-radius: 40rpx;
		font-size: 33rpx;

		&.on {
			background: unset;
			color: #333;
		}

		&.homeIndex {
			width: 98rpx;
		}
	}

	.home .iconfont {
		width: 60rpx;
		text-align: center;
		font-size: 28rpx;
		font-weight: bold;
	}

	.home .line {
		width: 1rpx;
		height: 34rpx;
		background: #B3B3B3;
	}

	.home .icon-xiangzuo {
		font-size: 28rpx;
	}

	.clored {
		color: var(--view-theme);
		font-weight: 600;

		.icon-gou {
			font-weight: 400 !important;
		}
	}

	.selectbrand {
		position: fixed;
		background-color: #FFFFFF;
		z-index: 8;
		width: 100%;
		top: 170rpx;
		left: 0;
		max-height: 860rpx;
		overflow: hidden;
		overflow-y: auto;
		border-radius: 0 0 24rpx 24rpx;
		transform: translate3d(0, -100%, 0);
		transition: all .2s cubic-bezier(.9, .5, .5, .25);

		// padding-top: 200rpx;
		// padding-top: calc(200rpx+ constant(safe-area-inset-bottom)); ///兼容 IOS<11.2/
		// padding-top: calc(200rpx + env(safe-area-inset-bottom)); ///兼容 IOS>11.2/
		.box {
			.selet {
				display: flex;
				flex-wrap: wrap;
				padding: 20rpx 20rpx 0 20rpx;
				max-height: 520rpx;
				overflow-x: hidden;
				overflow-y: auto;

				.seletbox {
					width: 216rpx;
					height: 68rpx;
					border-radius: 34rpx;
					background-color: #F5F5F5;
					margin-bottom: 30rpx;
					text-align: center;
					font-size: 24rpx;
					margin-right: 28rpx;

					&:nth-child(3n) {
						margin-right: 0;
					}
				}

				.seleton {
					border: 1px solid var(--view-theme);
					background: var(--view-minorColorT);
					color: var(--view-theme);
				}
			}

			.btn {
				display: flex;
				justify-content: space-between;
				text-align: center;
				padding: 30rpx 20rpx 40rpx;

				.sambox {
					width: 328rpx;
					height: 64rpx;
					border-radius: 38rpx;
					border: 2rpx solid var(--view-theme);
					color: #FFFFFF;
					line-height: 64rpx;
				}

				.reset {
					color: var(--view-theme);
				}

				.ok {
					background: var(--view-theme);
				}
			}

			.tlebox {
				width: 100%;
				height: 88rpx;
				line-height: 88rpx;
				display: flex;
				justify-content: space-between;
				padding: 0 40rpx 0 36rpx;
				margin-left: 20rpx;
				border-bottom: 2rpx solid #EEEEEE;

			}

			.tlebox:last-child {
				border-bottom: 0;
			}
		}
	}

	.selectbrand.on {
		transform: translate3d(0, 0, 0);
	}

	.productList .search {
		width: 100%;
		height: 86rpx;
		padding-left: 23rpx;
		box-sizing: border-box;
		position: fixed;
		left: 0;
		top: 0;
		z-index: 9;

		.fanhui {
			color: #fff;
		}
	}

	.productList .search .input {
		width: 560rpx;
		height: 60rpx;
		background-color: #fff;
		border-radius: 50rpx;
		padding: 0 20rpx;
		box-sizing: border-box;

		&.on {
			width: 652rpx;
			margin-right: 30rpx;

			input {
				width: 560rpx;
			}
		}
	}

	.productList .search .input input {
		width: 466rpx;
		height: 100%;
		font-size: 26rpx;
	}

	.productList .search .input .placeholder {
		color: #999;
	}

	.productList .search .input .iconfont {
		font-size: 35rpx;
		color: #555;
	}

	.productList .search .icon-pailie,
	.productList .search .icon-tupianpailie {
		color: #fff;
		width: 62rpx;
		font-size: 40rpx;
		height: 86rpx;
		line-height: 86rpx;
	}

	.productList .nav {
		height: 86rpx;
		color: #454545;
		position: fixed;
		left: 0;
		width: 100%;
		font-size: 28rpx;
		background-color: #fff;
		margin-top: 86rpx;
		top: 0;
		z-index: 9;
	}

	.productList .nav .item {
		width: 25%;
		text-align: center;
	}

	.productList .nav .item.font-color {
		font-weight: bold;
	}

	.productList .nav .item image {
		width: 15rpx;
		height: 19rpx;
		margin-left: 10rpx;
	}

	.productList .list {
		padding: 0 20rpx;
		margin-top: 172rpx;

	}

	.productList .list.waterList {
		margin-top: 192rpx;
	}

	.productList .list.on {
		background-color: #fff;
		border-top: 1px solid #f6f6f6;

		.title {
			font-size: 22rpx;
			color: #333333;
			margin-top: 30rpx;

			.label {
				border-radius: 4rpx;
				padding: 2rpx 8rpx;
				background-color: var(--view-minorColorT);
				font-size: 20rpx;
				color: var(--view-theme);
				margin-right: 8rpx;
			}
		}

		.footer {
			width: 100%;
			height: 96rpx;
			background-color: #fff;
			position: fixed;
			bottom: 0;
			left: 0;
			box-shadow: 0 -4rpx 32rpx 0 rgba(0, 0, 0, 0.08);
			padding: 0 20rpx 0 32rpx;
			box-sizing: border-box;
			bottom: calc(0rpx+ constant(safe-area-inset-bottom)); ///兼容 IOS<11.2/
			bottom: calc(0rpx + env(safe-area-inset-bottom)); ///兼容 IOS>11.2/

			.right {
				color: #888888;
				font-size: 20rpx;
				margin-left: 32rpx;

				.num {
					font-size: 28rpx;
					color: #333333;
					font-weight: 400;

					.money {
						font-weight: 500;
					}
				}
			}

			.icon {
				width: 72rpx;
				height: 72rpx;
				background: #F5F5F5;
				border-radius: 50%;
				text-align: center;
				line-height: 72rpx;
				position: relative;

				.iconfont {
					font-size: 38rpx;
				}

				.num {
					min-width: 32rpx;
					background-color: #fff;
					color: var(--view-theme);
					border-radius: 15px;
					position: absolute;
					right: -14rpx;
					top: 0;
					font-size: 10px;
					padding: 0 8rpx;
					height: 34rpx;
					line-height: 31rpx;
					border: 1px solid var(--view-theme);
				}
			}

			.bnt {
				width: 192rpx;
				height: 64rpx;
				background: var(--view-theme);
				border-radius: 40rpx;
				color: #FFFFFF;
				font-size: 28rpx;
				text-align: center;
				line-height: 64rpx;
			}
		}
	}

	.productList .list .item {
		width: 345rpx;
		margin-top: 20rpx;
		background-color: #fff;
		border-radius: 20rpx;

		.text {
			&.on {
				.nameCon {
					height: 84rpx;
				}

				.name {
					margin-bottom: 4rpx;
				}

				.label {
					font-size: 20rpx;
					color: var(--view-theme);
					border-radius: 4rpx;
					border: 1px solid var(--view-theme);
					padding: 0 6rpx;
					margin-right: 10rpx;
				}

				.sales {
					color: #999999;
					font-size: 22rpx;
					margin-top: 10rpx;

					.score {
						margin-left: 24rpx;
					}

					.icon {
						width: 48rpx;
						height: 48rpx;
						border-radius: 50%;
						border: 1px solid var(--view-theme);
						font-size: 20rpx;
						color: var(--view-theme);
					}
				}
			}
		}
	}

	.productList .list .item.on {
		width: 100%;
		display: flex;
		border-bottom: 1rpx solid #f6f6f6;
		padding: 30rpx 0;
		margin: 0;
	}

	.productList .list .item .pictrue {
		position: relative;
		width: 100%;
		height: 345rpx;
	}

	.productList .list .item .pictrue.on {
		width: 180rpx;
		height: 180rpx;
		position: relative;

		.masks {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(0, 0, 0, 0.2);
			border-radius: 10rpx;

			.bg {
				width: 110rpx;
				height: 110rpx;
				background: #000000;
				opacity: 0.6;
				color: #fff;
				font-size: 22rpx;
				border-radius: 50%;
				padding: 22rpx 0;
				text-align: center;
			}
		}
	}

	.productList .list .item .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 20rpx 20rpx 0 0;
	}

	.productList .list .item .pictrue image.on {
		border-radius: 6rpx;
	}

	.productList .list .item .text {
		padding: 20rpx 17rpx 26rpx 17rpx;
		font-size: 30rpx;
		color: #222;
	}

	.productList .list .item .text.on {
		width: 508rpx;
		padding: 0 0 0 22rpx;
	}

	.productList .list .item .text .money {
		font-size: 26rpx;
		font-weight: bold;
		margin-top: 8rpx;
	}

	.productList .list .item .text .money.on {
		margin-top: 0;
		margin-right: 5rpx;
	}

	.productList .list .item .text .money .num {
		font-size: 34rpx;
	}

	.productList .list .item .text .vip {
		font-size: 22rpx;
		color: #aaa;
		margin-top: 7rpx;
	}

	.productList .list .item .text .vip.on {
		margin-top: 12rpx;
	}

	.productList .list .item .text .vip .vip-money {
		font-size: 24rpx;
		color: #282828;
		font-weight: 600;
	}

	.productList .list .vip .vip-money .icon {
		font-size: 15rpx;
		background: #FF9500;
		color: #fff;
		border-radius: 18rpx;
		padding: 1rpx 6rpx;
		margin-left: 10rpx;
		min-width: 60rpx;

		.iconfont {
			font-size: 15rpx;
			margin-right: 5rpx;
		}

		&.on {
			background: #333;
			color: #FDDAA4;
			min-width: unset;
		}
	}

	.productList .list .item .text .vip .vip-money image {
		width: 46rpx;
		height: 21rpx;
		margin-left: 4rpx;
	}

	.noCommodity {
		background-color: #fff;
		padding-bottom: 30rpx;
	}

	.waterfalls {
		padding: 10rpx 16rpx 16rpx 16rpx;
		color: #222;

		.name {
			font-size: 28rpx;
		}

		.label {
			font-size: 20rpx;
			color: var(--view-theme);
			border-radius: 4rpx;
			border: 1px solid var(--view-theme);
			padding: 0 6rpx;
			display: inline-block;
			margin-top: 10rpx;
			margin-right: 10rpx;
		}

		.money {
			font-size: 26rpx;
			font-weight: 700;

			.num {
				font-size: 34rpx;
			}

			.nums {
				font-size: 28rpx;
			}
		}

		.vip {
			font-size: 22rpx;
			color: #aaa;
			margin-top: 6rpx;

			.vip-money {
				font-size: 24rpx;
				color: #282828;
				font-weight: bold;

				image {
					width: 46rpx;
					height: 21rpx;
					margin-left: 4rpx;
				}
			}
		}
	}
</style>
