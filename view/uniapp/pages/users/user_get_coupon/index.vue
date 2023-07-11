<template>
	<view class="getCoupons" :style="colorStyle">
		<view v-if="count > 1" class="header acea-row row-around">
			<view class="item" :class="type==index?'on':''" v-if="item.count" v-for="(item,index) in navList" :key="index" @click="setType(index)">{{ item.name }}</view>
		</view>
		<view v-if="couponsList.length" class="list" :class="count<=1?'on':''">
			<view class="item acea-row row-between-wrapper" v-for="(item,index) in couponsList" :key="index" :class="{svip: item.receive_type === 4}">
				<view class="left" v-if="item.products.length>1 || item.products.length == 0">
					<view class="name line1" :class='item.is_use ? "moneyGray" : ""'>
						<text class="label" :class="item.is_use ? 'bg-color-huic' : ''" v-if="item.type === 0">通用券</text>
						<text class="label" :class="item.is_use ? 'bg-color-huic' : ''" v-else-if="item.type === 1">品类券</text>
						<text class="label" :class="item.is_use ? 'bg-color-huic' : ''" v-else>商品券</text>
						{{ item.title }}
					</view>
					<view class="pictrueList acea-row" v-if="item.products.length>1">
						<view class="itemn" v-for="(j,indexn) in item.products" :key="indexn" v-if="indexn<3" @click="goDetails(j)">
							<view class="pictrue">
								<image :src="j.image"></image>
							</view>
							<view class="money">¥{{j.price}}</view>
						</view>
					</view>
					<view v-else class="time" :class='item.is_use ? "moneyGray" : ""'>
						<view v-if="item.coupon_time">领取后{{item.coupon_time}}天内可用</view>
						<view v-else>{{ item.start_time ? item.start_time + '-' : '' }}{{ item.end_time }}</view>
					</view>
				</view>
				<view class="left acea-row row-middle" v-else @click="goDetails(item.products[0])">
					<view class="pictrues">
						<image :src="item.products[0].image"></image>
					</view>
					<view class="text">
						<view class="top">
							<view class="title">{{ item.title }}</view>
							<view class="acea-row">
								<view class="label" :class="item.is_use ? 'bg-color-huic' : ''" v-if="item.type === 0">通用券</view>
								<view class="label" :class="item.is_use ? 'bg-color-huic' : ''" v-else-if="item.type === 1">品类券</view>
								<view class="label" :class="item.is_use ? 'bg-color-huic' : ''" v-else>商品券</view>
							</view>
						</view>
						<view class="money">¥{{item.products[0].price}}</view>
					</view>
				</view>
				<view class="right" :class='item.is_use ? "moneyGray" : ""'>
					<view class="iconfont icon-yilingqu" v-if="item.is_use == true"></view>
					<view><text class="label" v-if="item.coupon_type==1">¥</text><text class="num">{{item.coupon_type==1?item.coupon_price:parseFloat(item.coupon_price)/10}}</text><text class="label" v-if="item.coupon_type!=1">折</text></view>
					<view v-if="item.use_min_price > 0">满{{item.use_min_price}}可用</view>
					<view v-else>无门槛券</view>
					<view class="bnt acea-row row-center-wrapper bg-color-huic" v-if="item.is_use == true">已领取</view>
					<view class="bnt acea-row row-center-wrapper bg-color-huic" v-else-if="item.is_use == 2">已领完</view>
					<view class="bnt acea-row row-center-wrapper" v-else @click="getCoupon(item.id, index)">立即领取</view>
					<view class="labelVip" v-if="item.receive_type === 4 && !item.is_use">
						<image src="../static/vipLable.png"></image>
					</view>
				</view>
			</view>
		</view>
		<view class='loadingicon acea-row row-center-wrapper' v-if="couponsList.length">
			<text class='loading iconfont icon-jiazai' :hidden='loading==false'></text>{{loadTitle}}
		</view>
		<view class='noCommodity' v-else-if="!couponsList.length && page === 2">
			<view class='pictrue'>
				<image src='/static/images/noCoupon.png'></image>
			</view>
		</view>
		<home v-if="navigation"></home>
	</view>
</template>

<script>
	import {
		getCoupons,
		setCouponReceive
	} from '@/api/api.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import {
		mapGetters
	} from "vuex";
	import home from '@/components/home';
	import colors from '@/mixins/color.js';
	export default {
		components: {
			home
		},
		mixins:[colors],
		data() {
			return {
				couponsList: [],
				loading: false,
				loadend: false,
				loadTitle: '加载更多', //提示语
				page: 1,
				limit: 20,
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				type: 0,
				navList: [{
						name: '通用券',
						count: 0
					},
					{
						name: '品类券',
						count: 0
					},
					{
						name: '商品券',
						count: 0
					},
				],
				count: 0
			};
		},
		computed: mapGetters(['isLogin']),
		watch: {
			isLogin: {
				handler: function(newV, oldV) {
					if (newV) {
						this.getUseCoupons();
					}
				},
				deep: true
			}
		},
		onLoad() {
			if (this.isLogin) {
				this.getUseCoupons();
			} else {
				toLogin();
			}
		},
		/**
		 * 页面上拉触底事件的处理函数
		 */
		onReachBottom: function() {
			this.getUseCoupons();
		},
		methods: {
			goDetails(item){
				uni.navigateTo({
					url: '/pages/goods_details/index?id=' + item.id
				})
			},
			// 授权关闭
			authColse: function(e) {
				this.isShowAuth = e;
			},
			getCoupon: function(id, index) {
				let that = this;
				let list = that.couponsList;
				//领取优惠券
				setCouponReceive(id).then(function(res) {
					list[index].is_use = true;
					that.$set(that, 'couponsList', list);
					that.$util.Tips({
						title: '领取成功'
					});
				}).catch(error => {
					return that.$util.Tips({
						title: error
					});
				})
			},
			/**
			 * 获取领取优惠券列表
			 */
			getUseCoupons: function() {
				let that = this
				if (this.loadend) return false;
				if (this.loading) return false;
				that.loading = true;
				that.loadTitle = '加载更多';
				getCoupons({
					type: that.type,
					page: that.page,
					limit: that.limit
				}).then(res => {
					let list = res.data.list,
						loadend = list.length < that.limit;
					let couponsList = that.$util.SplitArray(list, that.couponsList);
					res.data.count.forEach((value, index) => {
						that.navList[index].count = value;
						if (value) {
							that.count++;
						}
					});
					that.$set(that, 'couponsList', couponsList);
					that.loadend = loadend;
					that.loading = false;
					that.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
					that.page = that.page + 1;
				}).catch(err => {
					that.loading = false;
					that.loadTitle = '加载更多';
				});
			},
			setType: function(type) {
				if (this.type !== type) {
					this.type = type;
					this.couponsList = [];
					this.page = 1;
					this.loadend = false;
					this.getUseCoupons();
				}
			}
		}
	};
</script>

<style scoped lang="scss">
	.getCoupons{
		.bg-color-huic{
			color: #ccc!important;
			background: #F1F1F1!important;
		}
		.header{
			background-color: var(--view-theme);
			height: 200rpx;
			border-radius: 0 0 70rpx 70rpx;
			.item{
				width: 138rpx;
				height: 54rpx;
				border-radius: 28rpx;
				text-align: center;
				line-height: 54rpx;
				color: #fff;
				margin-top: 38rpx;
				&.on{
					background-color: #fff;
					color: var(--view-theme);
				}
			}
		}
		.list{
			margin-top: -70rpx;
			&.on{
				margin-top: 24rpx;
				.item{
					&::after{
						background-color: #F2F2F2!important;
					}
				}
			}
			.item{
				width: 690rpx;
				height: 240rpx;
				border-radius: 18rpx;
				background-color: #fff;
				margin: 0 auto 24rpx auto;
				padding: 22rpx 0 18rpx 28rpx;
				overflow: hidden;
				position: relative;
				&::after{
					content: ' ';
					width: 30rpx;
					height: 30rpx;
					border-radius: 50%;
					position: absolute;
					left:65.5%;
					top:-14rpx;
					background-color: #F2F2F2;
				}
				&::before{
					content: ' ';
					width: 30rpx;
					height: 30rpx;
					border-radius: 50%;
					position: absolute;
					left:65.5%;
					bottom:-14rpx;
					background-color: #F2F2F2;
				}
				&:first-child{
					&::after{
						background-color: var(--view-theme);
					}
				}
				.left{
					width: 440rpx;
					border-right: 1rpx dashed #eee;
					.time{
						color: #666666;
						font-size: 22rpx;
						margin-top: 90rpx;
						&.moneyGray{
							color: #ccc;
						}
					}
					.pictrues{
						width: 172rpx;
						height: 172rpx;
						border-radius: 12rpx;
						margin-right: 20rpx;
						image{
							width: 100%;
							height: 100%;
							border-radius: 12rpx;
						}
					}
					.text{
						width: 234rpx;
						.money{
							color: #999999;
							font-size: 20rpx;
							margin-top: 8rpx;
						}
						.top{
							height: 130rpx;
							.title{
								font-size: 26rpx;
								color: #333;
							}
							.label{
								background-color: var(--view-minorColorT);
								padding: 4rpx 12rpx;
								border-radius: 20rpx;
								color: var(--view-theme);
								font-size: 18rpx;
								margin-top: 8rpx;
							}
						}
					}
					.pictrueList{
						margin-top: 20rpx;
						.itemn{
							width: 120rpx;
							margin-right: 24rpx;
							.money{
								text-align: center;
								color: #999;
								font-size: 20rpx;
								margin-top: 8rpx;
							}
							.pictrue{
								width: 100%;
								height: 120rpx;
								border-radius: 8rpx;
								image{
									width: 100%;
									height: 100%;
									border-radius: 8rpx;
								}
							}
						}
					}
					.name{
						font-size: 24rpx;
						&.moneyGray{
							color: #ccc;
						}
						.label{
							background-color: var(--view-minorColorT);
							padding: 4rpx 12rpx;
							border-radius: 20rpx;
							color: var(--view-theme);
							font-size: 18rpx;
							margin-right: 8rpx;
						}
					}
				}
				.right{
					width: 212rpx;
					text-align: center;
					font-size: 24rpx;
					color: var(--view-theme);
					position: relative;
					.icon-yilingqu{
						position: absolute;
						right: -14rpx;
						top:-74rpx;
						font-size: 100rpx;
						z-index:0;
					}
					.labelVip{
						width: 128rpx;
						height: 82rpx;
						position: absolute;
						right: 10rpx;
						bottom: -40rpx;
						image{
							width: 100%;
							height: 100%;
						}
					}
					.label{
						font-size: 26rpx;
						font-weight: 600;
					}
					.num{
						font-size: 50rpx;
						font-weight: 600;
					}
					.bnt{
						width: 138rpx;
						height: 44rpx;
						background: linear-gradient(135deg, var(--view-minorColor) 0%, var(--view-theme) 100%);
						border-radius: 24rpx;
						color: #fff;
						font-size: 24rpx;
						margin: 16rpx auto 0 auto;
						position: relative;
						z-index:1;
					}
					&.moneyGray{
						color: #CCCCCC;
					}
				}
				&.svip{
					.right{
						color: #D98C2B;
						.bnt{
							background: linear-gradient(90deg, #F1BE52 0%, #E9A655 100%);
						}
						&.moneyGray{
							color: #CCCCCC;
						}
					}
					.name{
						.label{
							background-color: #FFEFCD;
							color: #D18E00;
						}
					}
				}
			}
		}
	}
</style>
