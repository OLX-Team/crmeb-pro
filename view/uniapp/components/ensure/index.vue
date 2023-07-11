<template>
	<view>
		<view class="ensure" :class="ensureInfo.show === true ? 'on' : ''">
			<view class="title">保障声明<text class="iconfont icon-guanbi5" @click="closeEnsure"></text></view>
			<view class="list">
				<view class="item acea-row" v-for="(item,index) in ensureInfo.ensure" :key="index">
					<view class="pictrue">
						<image :src="item.image"></image>
					</view>
					<view class="text">
						<view class="name">{{item.name}}</view>
						<view>{{item.desc}}</view>
					</view>
				</view>
			</view>
			<view class="bnt" @click="closeEnsure">完成</view>
		</view>
		<view class="mask" @touchmove.prevent :hidden="ensureInfo.show === false" @click="closeEnsure"></view>
	</view>
</template>

<script>
	export default {
		props: {
			ensureInfo: {
				type: Object,
				default: () => {}
			},
		},
		data() {
			return {};
		},
		mounted() {},
		methods: {
			closeEnsure(){
				this.$emit('myevent');
			}
		}
	}
</script>

<style scoped lang="scss">
	.ensure{
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
					width: 36rpx;
					height: 36rpx;
					border-radius: 50%;
					margin-right: 30rpx;
					image{
						width: 100%;
						height: 100%;
						border-radius: 50%;
					}
				}
				.text{
					width: 618rpx;
					color: #999999;
					font-size: 28rpx;
					.name{
						color: #333333;
						font-weight: bold;
						margin-bottom: 20rpx;
					}
				}
			}
		}
		.bnt{
			width: 690rpx;
			height: 86rpx;
			text-align: center;
			line-height: 86rpx;
			border-radius: 43rpx;
			background-color: var(--view-theme);
			font-size: 30rpx;
			color: #fff;
			margin: 0 auto;
		}
	}
	.ensure.on{
		transform: translate3d(0, 0, 0);
	}
</style>
