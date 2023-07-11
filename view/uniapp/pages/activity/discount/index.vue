<template>
	<view :style="colorStyle" class="discount">
		<view class="header">
			<text v-if="type == 1">限时折扣</text>
			<text v-if="type == 4">满送活动</text>
			<text v-if="type == 3">满减满折</text>
			<text v-if="type == 2">第N件N折</text>
		</view>
		<view class="list">
			<view class="item acea-row row-between-wrapper" v-for="(item,index) in list" :key="index"
				@click="goDetails(item)">
				<view class="pictrue">
					<image :src="item.image"></image>
				</view>
				<view class="text">
					<view class="conter">
						<view class="name line2">{{item.store_name}}</view>
						<view class="info acea-row row-between" v-if="type == 4" @click.stop="giftGoods(item)">
							<view class="desc line2">{{item.promotions.desc}}</view>
							<view class="iconfont icon-you"></view>
						</view>
					</view>
					<view class="bnt acea-row row-between-wrapper" v-if="type != 4">
						<view class="left">
							<view class="title">
								<!-- <view class="iconfont icon-xianshi"></view> -->
								<view class="time" v-if="type == 1">限时：</view>
								<countDown v-if="type == 1" class="time" :tip-text="' '"
									:datatime="item.promotions.stop_time" style="margin-top: 4rpx;"></countDown>
								<view class="time" v-if="type == 2">{{item.promotions.title}}</view>
								<view class="time" v-if="type == 3">
									{{item.promotions.promotions.length==1?'最高':''}}
									{{item.promotions.promotions[0].discount_type==1?'可减'+item.promotions.promotions[0].discount:'可打'+parseFloat(item.promotions.promotions[0].discount)/10}}
									{{item.promotions.promotions[0].discount_type==1?'元':'折'}}
								</view>
							</view>
							<view class="money"><text class="label">¥</text><text class="num">{{item.price}}</text><text
									class="y_money">¥{{item.ot_price}}</text></view>
						</view>
						<view class="right acea-row row-center-wrapper">立即抢购</view>
					</view>
					<view class="bntCon acea-row row-between-wrapper" v-else>
						<view class="money">
							<text class="label">¥</text>
							<text class="num">{{item.price}}</text>
							<text class="y_money">{{item.ot_price}}</text>
						</view>
						<view class="right acea-row row-center-wrapper">立即抢购</view>
					</view>
				</view>
			</view>
		</view>
		<view class='loadingicon acea-row row-center-wrapper' v-if="list.length">
			<text class='loading iconfont icon-jiazai' :hidden='loading==false'></text>{{loadTitle}}
		</view>
		<giftGoods :giftInfo="giftInfo" @myevent="myGift"></giftGoods>
		<home v-if="navigation"></home>
	</view>
</template>

<script>
	import {
		mapGetters
	} from "vuex";
	import {
		promotionsList,
		giveInfo
	} from '@/api/activity.js';
	import home from '@/components/home';
	import colors from "@/mixins/color";
	import countDown from '@/components/countDown';
	import giftGoods from '@/components/giftGoods';
	export default {
		mixins: [colors],
		data() {
			return {
				list: [],
				loading: false,
				loadend: false,
				loadTitle: '加载更多', //提示语
				page: 1,
				limit: 10,
				type: 0,
				giftInfo:{
					show:false,
					giveCoupon:[],
					giveIntegral:[],
					giveProducts:[]
				}
			}
		},
		components: {
			countDown,
			giftGoods,
			home
		},
		onLoad(option) {
			this.type = option.promotions_type;
			this.getList();
		},
		onReachBottom: function() {
			this.getList();
		},
		methods: {
			giftGoods(item){
				this.giftInfo.show = true;
				giveInfo(item.promotions.id).then(res=>{
					this.giftInfo.giveCoupon = res.data.giveCoupon;
					this.giftInfo.giveIntegral = res.data.giveIntegral;
					this.giftInfo.giveProducts = res.data.giveProducts;
				}).catch(err=>{
					return this.$util.Tips({
						title: err
					});
				})
			},
			myGift(){
				this.$set(this.giftInfo, 'show', false);
			},
			goDetails(item) {
				uni.navigateTo({
					url: `/pages/goods_details/index?id=${item.id}&promotions_type=${this.type}`
				})
			},
			getList: function() {
				let that = this
				if (this.loadend) return false;
				if (this.loading) return false;
				that.loading = true;
				that.loadTitle = '加载更多';
				promotionsList(this.type, {
					page: that.page,
					limit: that.limit
				}).then(res => {
					let list = res.data.list,
						loadend = list.length < that.limit;
					let discountList = that.$util.SplitArray(list, that.list);
					that.$set(that, 'list', discountList);
					that.loadend = loadend;
					that.loading = false;
					that.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
					that.page = that.page + 1;
				}).catch(err => {
					that.loading = false;
					that.loadTitle = '加载更多';
					return that.$util.Tips({
						title: err
					});
				});
			},
		}
	}
</script>

<style scoped lang="scss">
	.discount {
		.header {
			width: 100%;
			height: 280rpx;
			background: url(../static/bg.png) no-repeat;
			background-size: 100% 100%;
			background-color: var(--view-theme);
			font-size: 56rpx;
			color: #fff;
			text-align: center;
			padding-top: 40rpx;
		}

		.list {
			margin-top: -118rpx;

			.item {
				width: 710rpx;
				height: 280rpx;
				background-color: #fff;
				border-radius: 16rpx;
				margin: 0 auto 18rpx auto;
				padding: 20rpx;

				.pictrue {
					width: 240rpx;
					height: 240rpx;
					border-radius: 16rpx;

					image {
						width: 100%;
						height: 100%;
						border-radius: 16rpx;
					}
				}

				.text {
					width: 416rpx;

					.conter {
						height: 150rpx;
					}

					.info {
						font-size: 20rpx;
						margin-top: 5rpx;
						color: var(--view-theme);
						.desc{
							width: 380rpx;
						}
						.iconfont{
							font-size: 18rpx;
							padding-top: 5rpx;
						}
					}

					.bntCon {
						width: 416rpx;
						height: 88rpx;

						.money {
							.label {
								font-size: 24rpx;
								font-weight: bold;
								color: var(--view-theme);
							}

							.y_money {
								color: #999999;
								font-size: 20rpx;
								text-decoration: line-through;
								margin-left: 6rpx;
							}

							.num {
								font-size: 32rpx;
								color: var(--view-theme);
								text-decoration: none;
								font-weight: bold;
							}
						}

						.right {
							width: 156rpx;
							height: 66rpx;
							border-radius: 34rpx;
							background: linear-gradient(135deg, var(--view-minorColor) 0%, var(--view-theme) 100%);
							font-size: 26rpx;
							color: #fff;
						}
					}

					.bnt {
						width: 416rpx;
						height: 88rpx;
						border-radius: 8rpx;
						background-color: var(--view-minorColorT);

						.left {
							padding-left: 12rpx;

							.time {
								display: inline-block;
								vertical-align: middle;
							}

							.title {
								font-size: 18rpx;
								color: var(--view-theme);

								.iconfont {
									font-size: 24rpx;
									margin-right: 8rpx;
									display: inline-block;
									vertical-align: bottom;
								}
							}

							.money {
								width: 261rpx;
								overflow: hidden;

								.label {
									font-size: 24rpx;
									font-weight: bold;
									color: var(--view-theme);
								}

								.y_money {
									color: #999999;
									font-size: 20rpx;
									text-decoration: line-through;
									margin-left: 6rpx;
								}

								.num {
									font-size: 32rpx;
									color: var(--view-theme);
									text-decoration: none;
									font-weight: bold;
								}
							}
						}

						.right {
							width: 134rpx;
							height: 100%;
							border-radius: 8rpx;
							background: linear-gradient(135deg, var(--view-minorColor) 0%, var(--view-theme) 100%);
							color: #fff;
							font-size: 26rpx;
						}
					}
				}
			}
		}
	}
</style>
