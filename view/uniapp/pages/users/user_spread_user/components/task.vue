<template>
	<view>
		<view :class="{ mask: invShow }" @touchmove.stop.prevent @click="invClose"></view>
		<view class="popup" :class="{ on: invShow }">
			<view class="popup-hd">快速升级技巧<text class="iconfont icon-guanbi" @click="invClose"></text></view>
			<scroll-view class="popup-bd" scroll-y="true">
				<view class="section-bd acea-row">
					<view class="item acea-row row-middle" v-for="(item,index) in task" :key='item.id'>
						<view class="img">
							<img :src="item.image" alt="">
						</view>
						<view class="text">
							<view class="title">
								<view class="name line2">
									{{item.name}}
									<text class="iconfont icon-wenti" @click="opHelp(index)"></text>
								</view>

								<text class="mark">{{item.finish?'已完成':'未完成'}}</text>
							</view>
							<view class="process">
								<view
									:style="{width: `${Math.floor((item.new_number / item.number) > 1 ? 100 : item.new_number / item.number* 100)}%`}"
									class="fill"></view>
							</view>
							<view class="info-box">
								<view class="link" hover-class="none">
									<text class="new-number">{{item.new_number}}</text>
									/{{item.number}}
								</view>
							</view>
						</view>
						<view class="jump" @click="jumpIndex">
								去完成
						</view>
					</view>
				</view>
			</scroll-view>
		</view>
		<view class='growthValue' :class='growthValue==false?"on":""'>
			<text class='iconfont icon-guanbi3' @click='growthValue = true'></text>
			<view class='conter'>{{illustrate}}</view>
		</view>
		<view class='mask' :hidden='growthValue' @click='growthValueClose'></view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				invId: 0,
				growthValue: true,
				illustrate: ''
			}
		},
		props: {
			invShow: {
				type: Boolean,
				default: false
			},
			task: {
				type: Array,
				default () {
					return [];
				}
			},

		},
		methods: {
			invClose(state) {
				this.$emit('inv-close');
			},
			invChange(e) {
				if (this.isOrder) {
					this.invId = e.detail.value
				} else {
					this.$emit('inv-change', e.detail.value);
				}
			},
			/**
			 * 关闭说明
			 */
			growthValueClose() {
				this.growthValue = true;
			},
			/**
			 * 打开说明
			 */
			opHelp(index) {
				this.growthValue = false;
				this.$emit('inv-close');
				this.illustrate = this.task[index].desc;
			},
			invSub() {
				this.$emit('inv-change', this.invId || this.invChecked);
			},
			invCancel() {
				this.$emit('inv-cancel');
			},
			jumpIndex(){
				uni.switchTab({
					url:'/pages/index/index'
				})
			}
		},
	}
</script>

<style lang="scss" scoped>
	/deep/uni-radio .uni-radio-input {
		margin-right: 0;
	}

	.popup {
		position: fixed;
		bottom: 0;
		left: 0;
		z-index: 9;
		width: 100%;
		border-top-left-radius: 16rpx;
		border-top-right-radius: 16rpx;
		background-color: #fff;
		transform: translateY(100%);
		transition: 0.3s;
	}

	.popup.on {
		transform: translateY(0);
	}

	.popup-hd {
		position: relative;
		height: 129rpx;
		font-size: 32rpx;
		line-height: 129rpx;
		text-align: center;
		color: #000000;

		.iconfont {
			position: absolute;
			top: 50%;
			right: 30rpx;
			transform: translateY(-50%);
			font-size: 32rpx;
			color: #707070;
		}
	}

	.popup-bd {
		height: 600rpx;
		box-sizing: border-box;

		.section-bd {
			padding: 30rpx;

			.item {
				width: 100%;
				padding: 10px 25rpx;
				background-color: #fff;
				.img{
					width: 90rpx;
					height: 90rpx;
					margin-right: 24rpx;
					img{
						width: 100%;
						height: 100%;
					}
				}
				.name {
					font-size: 28rpx;
				}

				~.item {
					margin-top: 24rpx;
				}
				.jump{
					background-color: #E93323;
					color: #fff;
					border-radius: 30rpx;
					font-size: 24rpx;
					padding: 10rpx 20rpx;
					margin-left: 100rpx;
				}
			}

			.text {
				flex: 1;
			}

			.title {
				font-weight: bold;
				font-size: 28rpx;
				color: #282828;
				display: flex;
				justify-content: space-between;

				.icon-wenti {
					color: #999;
					margin-left: 10rpx;
				}

				.mark {
					text-align: right;
					margin-left: 20rpx;
					font-weight: normal;
					font-size: 24rpx;
					color: #999999;
					white-space: nowrap;
				}
			}

			.process {
				height: 12rpx;
				border-radius: 6rpx;
				margin: 14rpx 0;
				background-color: #EEEEEE;

				.fill {
					height: 100%;
					border-radius: 6rpx;
					background-color: #E7B667;
				}
			}

			.info-box {
				display: flex;
				justify-content: space-between;
				align-items: center;
			}

			.info {
				font-size: 22rpx;
				color: #999999;
			}

			.link {
				font-size: 26rpx;
				color: #999999;

				.new-number {
					color: #C6985C;
				}
			}
		}
	}

	.popup-ft {
		padding: 14rpx 30rpx 44rpx;

		.navigator {
			height: 86rpx;
			border-radius: 43rpx;
			background-color: var(--view-theme);
			font-size: 30rpx;
			line-height: 86rpx;
			text-align: center;
			color: #FFFFFF;

			.iconfont {
				margin-right: 14rpx;
				font-size: 30rpx;
			}
		}

		.button {
			height: 86rpx;
			border: 1rpx solid var(--view-theme);
			border-radius: 43rpx;
			margin-top: 26rpx;
			font-size: 30rpx;
			line-height: 84rpx;
			color: var(--view-theme);
		}
	}

	.empty {
		padding-top: 58rpx;
		font-size: 26rpx;
		text-align: center;
		color: #999999;

		.image {
			width: 400rpx;
			height: 260rpx;
			margin-bottom: 20rpx;
		}
	}

	.growthValue {
		background-color: #fff;
		border-radius: 16rpx;
		position: fixed;
		top: 266rpx;
		left: 50%;
		width: 560rpx;
		min-height: 440rpx;
		margin-left: -280rpx;
		z-index: 999;
		transform: translate3d(0, -200%, 0);
		transition: all .3s cubic-bezier(.25, .5, .5, .9);
	}

	.growthValue.on {
		transform: translate3d(0, 0, 0);
	}

	.growthValue .pictrue {
		width: 100%;
		height: 257rpx;
		position: relative;
	}

	.growthValue .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 16rpx 16rpx 0 0;
	}

	.growthValue .conter {
		padding: 0 35rpx;
		font-size: 30rpx;
		color: #333;
		margin-top: 58rpx;
		line-height: 1.5;
		height: 350rpx;
		overflow: auto;
	}

	.growthValue .iconfont {
		position: absolute;
		font-size: 65rpx;
		color: #fff;
		bottom: -90rpx;
		left: 50%;
		transform: translateX(-50%);
	}
</style>
