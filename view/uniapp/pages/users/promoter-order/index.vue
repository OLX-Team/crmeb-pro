<template>
	<view :style="colorStyle">
		<view class="promoter-order">
			<view class='header acea-row row-center-wrapper'>
				<view class='acea-row row-between-wrapper input'>
					<text class='iconfont icon-sousuo'></text>
					<input type='text' placeholder='搜索商品名称' @confirm="searchSubmitValue" confirm-type='search'
						name="search" placeholder-class='placeholder'></input>
				</view>
			</view>
			<timeSlot @changeTime="changeTime"></timeSlot>
			<view class='list' v-if="recordList.length>0">
				<view class="top_num">
					共 <text class="main_color">{{total}}</text> 笔订单，获得佣金<text class="main_color">¥{{sum_brokerage}}</text>
				</view>
				<block v-for="(item,index) in recordList" :key="index">
					<view class='item'>
						<view class='listn'>
							<block v-for="(child,indexn) in item.child" :key="indexn">
								<view class='itenm'>
									<view class='top acea-row row-between-wrapper'>
										<view class='pictxt acea-row row-between-wrapper'>
											<view class='pictrue'>
												<image :src='child.avatar'></image>
											</view>
											<view class='text line1'>{{child.nickname}}</view>
										</view>
										<view class='money' v-if="child.type == 'brokerage'">返佣：<text
												class='font-color'>￥{{child.number}}</text></view>
										<view class='money' v-else>暂未返佣：<text
												class='font-color'>￥{{child.number}}</text></view>
									</view>
									<view class='bottom'>
										<view class="msg">
											<text class='name'>商品名称：</text>
											<view class="store_name line1">
												{{child.store_name}}
											</view>
										</view>
										<view v-if="child.type == 'brokerage'"><text
												class='name'>返佣时间：{{child.time}}</text></view>
										<view v-else><text class='name'>下单时间：{{child.time}}</text></view>
									</view>
								</view>
							</block>
						</view>
					</view>
				</block>
			</view>
			<view v-if="recordList.length == 0">
				<emptyPage title="暂无推广订单～"></emptyPage>
			</view>
		</view>
		<!-- #ifdef MP -->
		<!-- <authorize @onLoadFun="onLoadFun" :isAuto="isAuto" :isShowAuth="isShowAuth" @authColse="authColse"></authorize> -->
		<!-- #endif -->
		<home v-if="navigation"></home>
	</view>
</template>

<script>
	import {
		spreadOrder
	} from '@/api/user.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import {
		mapGetters
	} from "vuex";
	import timeSlot from '@/components/timeSlot'
	// #ifdef MP
	import authorize from '@/components/Authorize';
	// #endif
	import emptyPage from '@/components/emptyPage.vue'
	import home from '@/components/home';
	import colors from '@/mixins/color.js';
	let sysHeight = uni.getSystemInfoSync().statusBarHeight + 'px';
	export default {
		components: {
			// #ifdef MP
			authorize,
			// #endif
			emptyPage,
			home,
			timeSlot
		},
		mixins: [colors],
		data() {
			return {
				sysHeight: sysHeight,
				page: 1,
				limit: 5,
				status: false,
				recordList: [],
				times: [],
				time: '',
				recordCount: 0,
				count: 0,
				total:0,
				sum_brokerage:0,
				keyword: '',
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				start: 0,
				stop: 0
			};
		},
		computed: mapGetters(['isLogin']),
		onLoad() {
			if (this.isLogin) {
				this.getRecordOrderList();
			} else {
				toLogin();
			}
		},
		methods: {
			searchSubmitValue(e) {
				console.log(e)
				this.keyword = e ? e.detail.value : ''
				this.page = 1;
				this.limit = 20;
				this.status = false;
				this.$set(this, 'recordList', []);
				this.$set(this, 'times', []);
				this.getRecordOrderList()
			},
			changeTime(time) {
				console.log(time)
				this.start = time.start
				this.stop = time.stop
				this.searchSubmitValue()
			},
			onLoadFun() {
				this.getRecordOrderList();
			},
			// 授权关闭
			authColse: function(e) {
				this.isShowAuth = e
			},
			getRecordOrderList() {
				let that = this;
				let page = that.page;
				let limit = that.limit;
				let status = that.status;
				if (status == true) return;
				spreadOrder({
					start: this.start,
					stop: this.stop,
					keyword: this.keyword,
					page: page,
					limit: limit
				}).then(res => {
					this.total = res.data.count;
					this.sum_brokerage = res.data.sum_brokerage;
					for (let i = 0; i < res.data.time.length; i++) {
						if (!this.times.includes(res.data.time[i].time)) {
							this.times.push(res.data.time[i].time)
							this.recordList.push({
								time: res.data.time[i].time,
								count: res.data.time[i].count,
								child: []
							})
						}
					}
					for (let x = 0; x < this.times.length; x++) {
						for (let j = 0; j < res.data.list.length; j++) {
							if (this.times[x] === res.data.list[j].time_key) {
								this.recordList[x].child.push(res.data.list[j])
							}
						}
					}
					that.count = res.data.count || 0;
					that.status = res.data.list.length < 5;
					that.page += 1;
				});
			}
		},
		onReachBottom: function() {
			this.getRecordOrderList();
		}
	}
</script>

<style scoped lang="scss">
	.sys-title {
		z-index: 10;
		position: relative;
		height: 40px;
		line-height: 40px;
		font-size: 30rpx;
		color: #fff;
		background-color: var(--view-theme);
		// #ifdef MP || APP-PLUS
		text-align: center;
		// #endif
	}

	.sys-head {
		background-color: #fff;
	}

	.promoter-order .header {
		width: 100%;
		height: 96rpx;
		background-color: var(--view-theme);
		border-bottom: 1rpx solid #f5f5f5;
	}

	.promoter-order .header .input {
		width: 700rpx;
		height: 60rpx;
		background-color: #f5f5f5;
		border-radius: 50rpx;
		box-sizing: border-box;
		padding: 0 25rpx;
	}

	.promoter-order .header .input .iconfont {
		font-size: 35rpx;
		color: #555;
	}

	.promoter-order .header .input .placeholder {
		color: #999;
	}

	.promoter-order .header .input input {
		font-size: 26rpx;
		height: 100%;
		width: 597rpx;
	}

	.promoter-order .list .item .title {
		height: 133rpx;
		padding: 0 30rpx;
		font-size: 26rpx;
		color: #999;
	}

	.promoter-order .list .item .title .data {
		font-size: 28rpx;
		color: #282828;
		margin-bottom: 5rpx;
	}

	.promoter-order .list .item .listn .itenm {
		background-color: #fff;
		margin: 20rpx 30rpx;
		border-radius: 14rpx;
	}

	.promoter-order .list .item .listn .itenm~.itenm {
		margin-top: 12rpx;
	}

	.promoter-order .list .item .listn .itenm .top {
		margin-left: 30rpx;
		padding-right: 30rpx;
		border-bottom: 1rpx solid #eee;
		height: 100rpx;
	}

	.promoter-order .list .item .listn .itenm .top .pictxt {
		width: 320rpx;
	}

	.promoter-order .list .item .listn .itenm .top .pictxt .text {
		width: 230rpx;
		font-size: 30rpx;
		color: #282828;
	}

	.promoter-order .list .item .listn .itenm .top .pictxt .pictrue {
		width: 66rpx;
		height: 66rpx;
	}

	.promoter-order .list .item .listn .itenm .top .pictxt .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 50%;
		border: 3rpx solid #fff;
		box-sizing: border-box;
	}

	.promoter-order .list .item .listn .itenm .top .money {
		font-size: 28rpx;
	}

	.promoter-order .list .item .listn .itenm .bottom {
		padding: 20rpx 30rpx;
		font-size: 28rpx;
		color: #666;
		line-height: 1.6;
		.msg{
			display: flex;
			align-items: center;
		}
	}

	.promoter-order .list .item .listn .itenm .bottom .name {
		color: #999999;
	}

	.promoter-order .list .item .listn .itenm .bottom .store_name {
		width: 450rpx;
		color: #666666;
	}
	.top_num{
		padding: 30rpx 30rpx 0;
		font-size: 26rpx;
		color: #666;
	}
	.main_color{
		color: #E93323;
	}
</style>
