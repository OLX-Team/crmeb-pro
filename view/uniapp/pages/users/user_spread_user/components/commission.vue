<template>
	<view class="tabs">
		<view class="item" v-for="(item,index) in list" :key="index">
			<view class="item-l">
				<view class="title">{{item.title}}</view>
				<view class="time">{{item.add_time}}</view>
			</view>
			<view class="item-r" :class="item.pm ?'red':'gre'">{{item.pm ?'+':'-'}}
				<text class="fw" :class="item.pm ?'red':'gre'">{{item.number}}</text>
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

			.item-l {
				.title {}

				.time {
					margin-top: 12rpx;
					color: #999999;
				}
			}

			.item-r {
				font-size: 32rpx;

				.fw {
					font-weight: bold;
				}
			}


		}

		.item .red {
			color: #E93323;
		}

		.item .gre {
			color: #16AC57;
		}
	}
</style>
