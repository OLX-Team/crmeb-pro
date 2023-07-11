<template>
	<view :style="colorStyle">
		<view class="PromoterRank">
			<view class="redBg bg-color">
				<view class="header">
					<view class="rank acea-row row-bottom row-around">
						<view class="item one">
							<view class="pictrue">
								<image :src="rankInfo.avatar"></image>
							</view>
						</view>
					</view>
					<view class="extension">
						<view class="item">
							<view class="num">{{rankInfo.rank}}</view>
							<view class="text">排名</view>
						</view>
						<view class="item">
							<view class="num">{{rankInfo.week}}</view>
							<view class="text">周推广人</view>
						</view>
						<view class="item">
							<view class="num">{{rankInfo.month}}</view>
							<view class="text">月推广人</view>
						</view>
					</view>
					<view class="nav acea-row row-center-wrapper">
						<view class="item" :class="active == 0 ? 'select' : ''" @click="switchTap(0)">
							<view class="">
								周榜
							</view>
							<view class="line-btn" :class="active == 0 ? 'line-sel' : ''"></view>
						</view>
						<view class="line">
						</view>
						<view class="item" :class="active == 1 ? 'select' : ''" @click="switchTap(1)">
							<view class="">
								月榜
							</view>
							<view class="line-btn" :class="active == 1 ? 'line-sel' : ''"></view>
						</view>
					</view>
				</view>
			</view>
			<view class="time">
				统计时间：{{rankInfo.start}} ~ {{rankInfo.end}}
			</view>
			<view class="list" v-if="rankInfo.list.length">
				<view class="item acea-row row-between-wrapper" v-for="(item,index) in rankInfo.list" :key="index">
					<view class="no" v-if="index == 0">
						<image src="../static/no1.png" mode=""></image>
					</view>
					<view class="no" v-if="index == 1">
						<image src="../static/no2.png" mode=""></image>
					</view>
					<view class="no" v-if="index == 2">
						<image src="../static/no3.png" mode=""></image>
					</view>
					<view class="num" v-if="index>2">{{index+1}}</view>
					<view class="picTxt acea-row row-between-wrapper">
						<view class="pictrue">
							<image :src="item.avatar"></image>
						</view>
						<view class="text line1">{{item.nickname}}</view>
					</view>
					<view class="people font-color">{{item.count}}人</view>
				</view>
			</view>
			<view v-else>
				<emptyPage title="暂无排行数据～"></emptyPage>
			</view>
		</view>
		<home v-if="navigation"></home>
	</view>
</template>

<script>
	import {
		getRankList
	} from '@/api/user.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import emptyPage from '@/components/emptyPage.vue'
	import {
		mapGetters
	} from "vuex";
	import home from '@/components/home';
	import colors from '@/mixins/color.js';
	export default {
		components: {
			home,
			emptyPage
		},
		mixins: [colors],
		data() {
			return {
				navList: ["周榜", "月榜"],
				active: 0,
				page: 1,
				limit: 20,
				type: 'week',
				loading: false,
				loadend: false,
				rankList: [],
				Two: {},
				One: {},
				Three: {},
				rankInfo: {
					list: []
				}
			};
		},
		computed: mapGetters(['isLogin']),
		watch: {
			isLogin: {
				handler: function(newV, oldV) {
					if (newV) {
						this.getRanklist();
					}
				},
				deep: true
			}
		},
		onLoad() {
			if (this.isLogin) {
				this.getRanklist();
			} else {
				toLogin();
			}
		},
		methods: {
			onLoadFun() {
				this.getRanklist();
			},
			// 授权关闭
			authColse: function(e) {
				this.isShowAuth = e
			},
			getRanklist: function() {
				let that = this;
				if (that.loadend) return;
				if (that.loading) return;
				that.loading = true;
				getRankList({
					page: that.page,
					limit: that.limit,
					type: that.type
				}).then(res => {
					let list = res.data.list;
					that.rankInfo = res.data;
					that.rankList.push.apply(that.rankList, list);
					if (that.page == 1) {
						that.One = that.rankList.shift() || {};
						that.Two = that.rankList.shift() || {};
						that.Three = that.rankList.shift() || {};
					}
					that.loadend = list.length < that.limit;
					that.loading = false;
					that.$set(that, 'rankList', that.rankList);
					that.One = that.One;
					that.Two = that.Two;
					that.Three = that.Three;
					that.page = that.page + 1;
				}).catch(err => {
					that.loading = false;
				})
			},

			switchTap: function(index) {
				if (this.active === index) return;
				this.active = index;
				this.type = index ? 'month' : 'week';
				this.page = 1;
				this.loadend = false;
				this.$set(this, 'rankList', []);
				this.Two = {};
				this.One = {};
				this.Three = {};
				this.getRanklist();
			},
		},
		onReachBottom: function() {
			this.getRanklist();
		}
	}
</script>

<style scoped lang="scss">
	.PromoterRank .redBg {
		background-image: url('../static/ranking.png');
		background-repeat: no-repeat;
		background-size: 100% 100%;
	}

	.PromoterRank .header {
		// background: url("data:image/jpeg;base64,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") no-repeat;
		width: 100%;
		height: 378rpx;
		position: relative;
	}

	.PromoterRank .header {
		.extension {
			display: flex;
			justify-content: space-around;
			position: absolute;
			width: 100%;
			bottom: 90rpx;
			padding: 15rpx 0;
			color: #fff;

			.item {
				width: 100%;
				text-align: center;

				.num {
					font-size: 32rpx;
					font-weight: bold;
					margin-bottom: 10rpx;
				}

				.text {
					font-size: 24rpx;
					font-weight: 400;

				}
			}
		}
	}

	.PromoterRank .nav {
		width: 100%;
		height: 90rpx;
		background: rgba(0, 0, 0, 0.1);
		font-size: 30rpx;
		color: #fff;
		margin: 0 auto;
		position: absolute;
		bottom: 0;

		.line {
			width: 1px;
			height: 42rpx;
			background: #FFFFFF;
			opacity: 0.3;
		}

		.line-sel {
			width: 58rpx;
			height: 4rpx;
			background: #FFFFFF;
			border-radius: 4rpx;
			margin: 0 auto;
		}
	}

	.PromoterRank .nav .item {
		flex: 1;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-direction: column;
		height: 100%;
		text-align: center;
		color: #F2F2F2;

		.line-btn {
			margin-top: 10rpx;
		}
	}

	.PromoterRank .nav .item.select {
		color: #fff;
		font-weight: bold;
	}

	.PromoterRank .header .rank {
		padding: 54rpx 0rpx;
	}

	.PromoterRank .header .rank .item .pictrue {
		background-repeat: no-repeat;
		background-size: 100% 100%;
		position: relative;
		margin: 0 auto;
	}


	.PromoterRank .header .rank .item .pictrue image {
		position: absolute;
		display: block;
		bottom: 2rpx;
		border-radius: 50%;
		left: 50%;
		margin-left: -50rpx;
	}

	.PromoterRank .header .rank .item.one .pictrue {
		width: 100rpx;
		height: 100rpx;
	}

	.PromoterRank .header .rank .item.one .pictrue image {
		width: 100rpx;
		height: 100rpx;
		margin-left: -50rpx;
		margin-top: 10rpx;
	}

	.time {
		padding: 34rpx 30rpx;
		color: #999999;
		background-color: #fff;
	}


	.PromoterRank .list {
		// width: 710rpx;
		background-color: #fff;
		// border-radius: 20rpx;
		// margin: -60rpx auto 0 auto;
		padding: 0 30rpx;

		.no {
			width: 52rpx;
			height: 52rpx;

			image {
				width: 100%;
				height: 100%;
			}
		}
	}

	.PromoterRank .list .item {
		border-bottom: 1px solid #f3f3f3;
		height: 101rpx;
		font-size: 28rpx;
	}

	.PromoterRank .list .item .num {
		color: #666;
		width: 70rpx;
	}

	.PromoterRank .list .item .picTxt {
		width: 350rpx;
	}

	.PromoterRank .list .item .picTxt .pictrue {
		width: 68rpx;
		height: 68rpx;
	}

	.PromoterRank .list .item .picTxt .pictrue image {
		width: 100%;
		height: 100%;
		display: block;
		border-radius: 50%;
	}

	.PromoterRank .list .item .picTxt .text {
		width: 262rpx;
		color: #333;
	}

	.PromoterRank .list .item .people {
		width: 175rpx;
		text-align: right;
	}
</style>
