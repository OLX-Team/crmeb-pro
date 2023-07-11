<template>
	<view class="orderPay" :style="colorStyle">
		<view class="header">
			<view class="orderNum">订单编号 {{orderInfo.order_id}}</view>
			<view class="money">
				¥
				<text class="num">{{orderInfo.pay_price}}</text>
			</view>
		</view>
		<view class="list" v-if="cartInfo.length">
			<view class="title acea-row row-between-wrapper">
				<view>商品列表</view>
				<view class="total">共<text class="num">{{cartInfo.length}}</text>件商品</view>
			</view>
			<view class="item acea-row row-between-wrapper" v-for="(item, index) in cartInfo" :key="index">
				<view class="pictrue">
					<image :src='item.productInfo.attrInfo.image' v-if="item.productInfo.attrInfo"></image>
					<image :src='item.productInfo.image' v-else></image>
				</view>
				<view class="picTxt">
					<view class="acea-row row-between-wrapper">
						<view class="name line1">{{item.productInfo.store_name}}</view>
						<view class="num">x {{item.cart_num}}</view>
					</view>
					<view class='info line1' v-if="item.productInfo.attrInfo">{{item.productInfo.attrInfo.suk}}</view>
					<view class="money" v-if="item.productInfo.attrInfo">¥{{item.productInfo.attrInfo.price}}</view>
				</view>
			</view>
		</view>
		<view class="bnt acea-row row-center-wrapper" @tap.stop="goPay">立即支付</view>
		<payment :payMode="cartArr" :pay_close="pay_close" :totalPrice="orderInfo.pay_price" @onChangeFun="onChangeFun"
			:order_id="orderInfo.order_id"></payment>
	</view>
</template>

<script>
	import colors from '@/mixins/color.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import {
		payCashier
	} from '@/api/order.js';
	import {
		mapGetters
	} from 'vuex';
	import {
		getUserInfo
	} from '@/api/user.js';
	import payment from '@/components/payment';
	export default {
		computed: mapGetters(['isLogin']),
		components: {
			payment
		},
		mixins: [colors],
		data() {
			return {
				storeId: 0,
				orderInfo: {},
				cartInfo: [],
				pay_close: false,
				//支付方式
				cartArr: [{
						"name": "微信支付",
						"icon": "icon-weixin2",
						value: 'weixin',
						title: '使用微信快捷支付',
						payStatus: 1,
					},
					{
						"name": "支付宝支付",
						"icon": "icon-zhifubao",
						value: 'alipay',
						title: '使用线上支付宝支付',
						payStatus: 1,
					},
					{
						"name": "余额支付",
						"icon": "icon-yuezhifu",
						value: 'yue',
						title: '可用余额:',
						payStatus: 1,
					}
					// {
					// 	"name": "线下支付",
					// 	"icon": "icon-yuezhifu1",
					// 	value: 'offline',
					// 	title: '选择线下付款方式',
					// 	payStatus: 2,
					// }
				]
			}
		},
		onLoad(options) {
			// #ifdef APP-PLUS || H5
			if (options.store_id) {
				this.storeId = options.store_id
			}
			// #endif
			// #ifdef MP
			if (options.scene) {
				let value = this.$util.getUrlParams(decodeURIComponent(options.scene));
				if (value.store_id) this.storeId = value.store_id;
			}
			// #endif
		},
		onShow() {
			if (this.isLogin) {
				this.orderList();
				this.getUserInfo();
			} else {
				toLogin();
			}
		},
		methods: {
			orderList() {
				payCashier(this.storeId).then(res => {
					this.orderInfo = res.data;
					this.cartInfo = res.data.cartInfo;
					//微信支付是否开启
					this.cartArr[0].payStatus = res.data.pay_weixin_open || 0
					//支付宝是否开启
					this.cartArr[1].payStatus = 0;
					//#ifdef MP
					this.cartArr[1].payStatus = 0;
					//#endif
					this.cartArr[2].payStatus = res.data.yue_pay_status == 1?res.data.yue_pay_status:0;
				}).catch(err => {
					return this.$util.Tips({
						title: err
					});
				})
			},
			/**
			 * 事件回调
			 *
			 */
			onChangeFun: function(e) {
				let opt = e;
				let action = opt.action || null;
				let value = opt.value != undefined ? opt.value : null;
				action && this[action] && this[action](value);
			},
			payClose: function() {
				this.pay_close = false;
			},
			goPay() {
				this.pay_close = true;
			},
			/**
			 * 支付成功回调
			 * 
			 */
			pay_complete: function() {
				this.pay_close = false;
				uni.navigateTo({
					url: '/pages/order_pay_status/index?order_id=' + this.orderInfo.order_id + '&msg=' +
						'支付成功' +
						'&type=3' + '&totalPrice=' + this.orderInfo.pay_price
				})
			},
			/**
			 * 支付失败回调
			 * 
			 */
			pay_fail: function() {
				this.pay_close = false;
				uni.navigateTo({
					url: '/pages/order_pay_status/index?order_id=' + this.orderInfo.order_id + '&msg=' +
						'取消支付' +
						'&type=3' + '&totalPrice=' + this.orderInfo.pay_price
				})
			},
			/**
			 * 获取用户信息
			 * 
			 */
			getUserInfo: function() {
				let that = this;
				getUserInfo().then(res => {
					that.cartArr[2].number = res.data.now_money;
					that.$set(that, 'cartArr', that.cartArr);
				})
			}
		}
	}
</script>

<style lang="scss">
	.orderPay {
		.bnt {
			width: 690rpx;
			height: 86rpx;
			border-radius: 43rpx;
			background-color: var(--view-theme);
			font-size: 32rpx;
			color: #fff;
			position: fixed;
			bottom: 50rpx;
			left: 50%;
			margin-left: -345rpx;
		}

		.list {
			background-color: #fff;
			border-radius: 20rpx 20rpx 0 0;
			margin-top: -30rpx;

			.title {
				height: 99rpx;
				font-size: 30rpx;
				color: #333;
				padding: 0 30rpx;
				border-bottom: 1rpx solid #F0F0F0;

				.total {
					font-size: 30rpx;
					color: #999999;

					.num {
						color: var(--view-theme);
						margin: 0 8rpx;
					}
				}
			}

			.item {
				margin-left: 30rpx;
				padding-right: 30rpx;
				border-bottom: 1rpx solid #F0F0F0;
				height: 180rpx;

				.pictrue {
					width: 130rpx;
					height: 130rpx;
					border-radius: 6rpx;

					image {
						width: 100%;
						height: 100%;
						border-radius: 6rpx;
					}
				}

				.picTxt {
					width: 538rpx;

					.name {
						width: 490rpx;
						font-size: 28rpx;
						color: #333;
					}

					.num {
						font-size: 26rpx;
						color: #999;
					}

					.info {
						font-size: 20rpx;
						color: #999;
						margin: 10rpx 0 15rpx 0;
					}

					.money {
						font-size: 26rpx;
						color: var(--view-theme);
					}
				}
			}
		}

		.header {
			width: 100%;
			height: 320rpx;
			background-color: var(--view-theme);
			text-align: center;
			padding-top: 70rpx;

			.orderNum {
				color: #FFFFFF;
				opacity: 0.8;
				font-size: 26rpx;
			}

			.money {
				font-size: 42rpx;
				font-weight: 500;
				color: #fff;
				margin-top: 40rpx;

				.num {
					font-size: 68rpx;
				}
			}
		}
	}
</style>
