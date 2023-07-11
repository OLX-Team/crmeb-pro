<template>
	<view class="tabs">
		<view class="item" v-for="(item,index) in list" :key="index">
			<view class="item-l">
				<view class="avatar">
					<image :src="item.avatar" mode=""></image>
				</view>
				<view class="">
					<view class="title">{{item.nickname}}</view>
					<view class="time">加入时间：{{item.time}}</view>
				</view>

			</view>
			<view class="item-r">
				<view class="">
					<text class="red">
						{{item.childCount ? item.childCount : 0}}
					</text>
					人
				</view>
				<view class="text">{{item.orderCount ? item.orderCount : 0}} 单</view>
				<view class="text">{{item.numberCount ? item.numberCount : 0}} 元</view>
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

				.time {
					font-size: 24rpx;
					margin-top: 12rpx;
					color: #666666;
					font-weight: 400;
				}
			}

			.item-r {
				font-size: 24rpx;
				text-align: right;

				.text {
					margin-top: 6px;
					font-size: 24rpx;
				}
			}

			.item-r .red {
				color: #E93323;
			}
		}
	}
</style>
