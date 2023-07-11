<template>
	<view>
		<view class="gift" :class="giftInfo.show === true ? 'on' : ''">
			<view class="title">查看赠品<text class="iconfont icon-guanbi5" @click="closeGift"></text></view>
			<view class="list">
				<view class="item acea-row row-between-wrapper" v-for="(item,index) in giftInfo.giveProducts" :key="index">
					<view class="pictrue">
						<image :src="item.image"></image>
					</view>
					<view class="text">
						<view class="name line1">{{item.store_name}}</view>
						<view class="info line1">{{item.suk}}</view>
						<view class="info line1">{{item.threshold_title}}</view>
						<view class="bottom acea-row row-between-wrapper">
							<view class="money">¥<text class="num">{{item.price}}</text></view>
							<view class="limit">x{{item.limit_num}}</view>
						</view>
					</view>
				</view>
				<view class="item acea-row row-between-wrapper" v-for="(item,index) in giftInfo.giveCoupon" :key="index">
					<view class="pictrue on acea-row row-center-wrapper">
						<view class="iconfont icon-youhuiquan2"></view>
					</view>
					<view class="text">
						<view class="name acea-row row-middle">
							<view class="lable">{{item.coupon_type==1?'品类券':item.coupon_type==2?'商品券':'通用券'}}</view>
							<view class="names line1">{{item.coupon_title}}</view>
						</view>
						<view class="info">{{item.threshold_title}}</view>
						<view class="bottom on acea-row row-between-wrapper">
							<view class="money"><text v-if="item.coupon_type==1">¥</text><text class="num">{{item.coupon_type==1?item.coupon_price:parseFloat(item.coupon_price)/10}}</text><text v-if="item.coupon_type==2">折</text></view>
							<view class="limit">x{{item.limit_num}}</view>
						</view>
					</view>
				</view>
				<view class="item item acea-row row-between-wrapper" v-for="(item,index) in giftInfo.giveIntegral" :key="index">
					<view class="pictrue ons acea-row row-center-wrapper">
						<view class="iconfont icon-jifen"></view>
					</view>
					<view class="text">
						<view class="name line1">赠送积分</view>
						<view class="info">{{item.threshold_title}}</view>
						<view class="bottom on acea-row row-between-wrapper">
							<view class="money"><text class="num">{{item.give_integral}}</text>积分</view>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class="mask" @touchmove.prevent :hidden="giftInfo.show === false" @click="closeGift"></view>
	</view>
</template>

<script>
	export default {
		props: {
			giftInfo: {
				type: Object,
				default: () => {}
			},
		},
		data() {
			return {};
		},
		mounted() {},
		methods: {
			closeGift(){
				this.$emit('myevent');
			}
		}
	}
</script>

<style scoped lang="scss">
	.gift{
		position: fixed;
		bottom: 0;
		width: 100%;
		left: 0;
		background-color: #fff;
		z-index: 280;
		border-radius: 16rpx 16rpx 0 0;
		transform: translate3d(0, 100%, 0);
		transition: all .3s cubic-bezier(.25, .5, .5, .9);
		padding-bottom: 22rpx;
		padding-bottom: calc(22rpx+ constant(safe-area-inset-bottom)); ///兼容 IOS<11.2/
		padding-bottom: calc(22rpx + env(safe-area-inset-bottom)); ///兼容 IOS>11.2/
		.title{
			font-size: 32rpx;
			color: #282828;
			text-align: center;
			margin: 38rpx 0 36rpx 0;
			position: relative;
			.iconfont{
				position: absolute;
				right: 30rpx;
				top:0;
				font-size: 36rpx;
			}
		}
		.list{
			height: 750rpx;
			margin: 0 30rpx;
			overflow-x: hidden;
			overflow-y: auto;
			.item{
				margin-bottom: 52rpx;
				.pictrue{
					width: 150rpx;
					height: 150rpx;
					border-radius: 10rpx;
					&.on{
						background-color: var(--view-minorColorT);
					}
					&.ons{
						background-color: rgba(254, 150, 15, 0.1);
						.iconfont{
							color: #FE960F!important;
						}
					}
					.iconfont{
						font-size: 75rpx;
						color: var(--view-theme);
					}
					image{
						width: 100%;
						height: 100%;
						border-radius: 10rpx;
					}
				}
				.text{
					width: 520rpx;
					color: #999999;
					font-size: 28rpx;
					.name{
						color: #333333;
						.lable{
							font-size: 18rpx;
							color: var(--view-theme);
							border:1rpx solid var(--view-theme);
							background-color: var(--view-minorColorT);
							border-radius: 15rpx;
							padding: 1rpx 6rpx;
							margin-right: 8rpx;
						}
						.names{
							width: 420rpx;
						}
					}
					.info{
						font-size: 24rpx;
						margin-top: 6rpx;
					}
					.money{
						color: var(--view-theme);
						font-size: 24rpx;
						.num{
							font-size: 36rpx;
						}
					}
					.bottom{
						margin-top: 8rpx;
						&.on{
							margin-top: 32rpx;
						}
					}
					.limit{
						font-size: 24rpx;
					}
				}
			}
		}
	}
	.gift.on{
		transform: translate3d(0, 0, 0);
	}
</style>
