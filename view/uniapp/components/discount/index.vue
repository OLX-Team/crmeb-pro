<template>
	<view>
		<view class="discountInfo" :class="discountInfo.show === true ? 'on' : ''">
			<view class="title">优惠活动<text class="iconfont icon-guanbi5" @click="closeDiscount"></text></view>
			<view class="list">
				<view class="item acea-row row-top" v-for="(item,index) in discountInfo.discount" :key="index" @click="goList(item)">
					<view class="label">{{item.title}}</view>
					<view class="info">{{item.desc}}</view>
					<view class="iconfont icon-jiantou"></view>
				</view>
			</view>
		</view>
		<view class="mask" @touchmove.prevent :hidden="discountInfo.show === false" @click="closeDiscount"></view>
	</view>
</template>

<script>
	export default {
		props: {
			discountInfo: {
				type: Object,
				default: () => {}
			},
		},
		data() {
			return {};
		},
		mounted() {},
		methods: {
			closeDiscount(){
				this.$emit('myevent');
			},
			goList(item){
				uni.navigateTo({
					url: `/pages/activity/discount/index?promotions_type=${item.promotions_type}`
				})
			}
		}
	}
</script>

<style scoped lang="scss">
	.discountInfo{
		position: fixed;
		bottom: 0;
		width: 100%;
		left: 0;
		background-color: #F5F5F5;
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
				margin-bottom: 14rpx;
				background-color: #fff;
				border-radius: 12rpx;
				padding: 30rpx 20rpx;
				position: relative;
				.label{
					font-size: 20rpx;
					color: var(--view-theme);
					background-color: var(--view-minorColorT);
					border-radius: 4rpx;
					padding: 2rpx 8rpx;
				}
				.info{
					max-width: 510rpx;
					font-size: 24rpx;
					color: #666;
					margin-left: 16rpx;
				}
				.iconfont{
					font-size: 30rpx;
					position: absolute;
					top:50%;
					margin-top: -15rpx;
					right: 10rpx;
				}
			}
		}
	}
	.discountInfo.on{
		transform: translate3d(0, 0, 0);
	}
</style>