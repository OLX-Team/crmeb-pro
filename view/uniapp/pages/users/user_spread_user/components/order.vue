<template>
	<view class="tabs">
		<view class="item" v-for="(item,index) in list" :key="index">
			<view class="item-l">
				<view class="avatar">
					<image src="../../static/1.png" mode=""></image>
				</view>
				<view class="">
					<view class="title">{{item.nickname}}</view>
					<view class="store_name line1">商品名称：{{item.store_name}}</view>
					<view class="time">下单时间：{{item.time}}</view>
				</view>

			</view>
			<view class="item-r">
				<text class="text">返佣：</text>
				<text class="red">66.60</text>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		props: ['list']
	}
</script>

<style scoped lang="scss">
	.tabs {
		background-color: #fff;

		.item {
			border-bottom: 1px solid #EEEEEE;
			color: #282828;
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 24rpx 30rpx;
			position: relative;

			.item-l {
				display: flex;
				align-items: center;

				.avatar {
					width: 54rpx;
					height: 54rpx;
					margin-right: 24rpx;

					image {
						width: 100%;
						height: 100%;
					}
				}

				.title {
					font-size: 30rpx;
				}
				.store_name{
					width: 400rpx;
				}
				.time {
					font-size: 24rpx;
					margin-top: 12rpx;
					color: #666666;
					font-weight: 400;
				}
			}

			.item-r {
				position: absolute;
				right: 20rpx;
				top: 22rpx;
				height: 100%;

				.text {
					font-size: 24rpx;
				}
			}

			.item-r .red {
				font-size: 32rpx;
				color: #E93323;
				font-weight: bold;
			}
		}
	}
</style>
