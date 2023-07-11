<template>
	<view>
		<view class="cartDiscount" :class="discountInfo.discount === true ? 'on' : ''">
			<view class="title">优惠明细<text class="iconfont icon-guanbi5" @click="closeDiscount"></text></view>
			<view class="list">
				<view class="item acea-row row-between-wrapper">
					<view>商品总价：</view>
					<view>￥{{selectCountPrice}}</view>
				</view>
				<view class="item acea-row row-between-wrapper">
					<view>优惠券抵扣：</view>
					<view class="font-color">-￥{{discountInfo.deduction.coupon_price}}</view>
				</view>
				<view class="coupon acea-row row-between-wrapper">
					<view>{{discountInfo.coupon.coupon_title}}</view>
					<view>-￥{{discountInfo.coupon.true_coupon_price}}</view>
				</view>
				<view class="bottom">
					<view class="item acea-row row-between-wrapper">
						<view>共优惠：</view>
						<view class="font-color">-￥{{discountInfo.deduction.coupon_price}}</view>
					</view>
					<view class="item acea-row row-between-wrapper">
						<view class="total">合计：</view>
						<view class="money">￥{{$util.$h.Sub(selectCountPrice,discountInfo.deduction.coupon_price)<=0?0:$util.$h.Sub(selectCountPrice,discountInfo.deduction.coupon_price)}}</view>
					</view>
				</view>
			</view>
		</view>
		<view class="mask" @touchmove.prevent :hidden="discountInfo.discount === false" @click="closeDiscount"></view>
	</view>
</template>

<script>
	export default {
		props: {
			discountInfo: {
				type: Object,
				default: () => {}
			},
			selectCountPrice: {
				type: Number,
				default: 0
			}
		},
		data() {
			return {};
		},
		mounted() {},
		methods: {
			closeDiscount(){
				this.$emit('myevent');
			}
		}
	}
</script>

<style scoped lang="scss">
	.cartDiscount{
		position: fixed;
		bottom: 0;
		width: 100%;
		left: 0;
		background-color: #fff;
		z-index: 9;
		border-radius: 24rpx 24rpx 0 0;
		transform: translate3d(0, 100%, 0);
		transition: all .3s cubic-bezier(.25, .5, .5, .9);
		padding-bottom: 200rpx;
		padding-bottom: calc(200rpx+ constant(safe-area-inset-bottom)); ///兼容 IOS<11.2/
		padding-bottom: calc(200rpx + env(safe-area-inset-bottom)); ///兼容 IOS>11.2/
		.title{
			font-size: 32rpx;
			color: #282828;
			text-align: center;
			position: relative;
			background-color: #F5F5F5;
			height: 120rpx;
			line-height: 120rpx;
			border-radius: 24rpx 24rpx 0 0;
			.iconfont{
				position: absolute;
				right: 30rpx;
				top:0;
				font-size: 36rpx;
			}
		}
		.list{
			max-height: 600rpx;
			overflow-x: hidden;
			overflow-y: auto;
			padding-top: 40rpx;
			.coupon{
				width: 692rpx;
				height: 70rpx;
				background: #F5F5F5;
				border-radius: 12rpx;
				font-size: 24rpx;
				padding: 0 24rpx;
				margin: 0 auto;
			}
			.bottom{
				border-top: 2rpx dotted #EEEEEE;
				margin-top: 30rpx;
				padding: 30rpx 30rpx 0 30rpx;
				.total{
					font-size: 30rpx;
					font-weight: 600;
				}
				.money{
					font-size: 36rpx;
					font-weight: 600;
				}
			}
			.item{
				margin: 0 30rpx 30rpx 30rpx;
			}
		}
	}
	.cartDiscount.on{
		transform: translate3d(0, 0, 0);
	}
</style>
