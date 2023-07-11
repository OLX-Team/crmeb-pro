<template>
	<view :style="colorStyle">
		<view class='commission-details'>
			<view class='search acea-row row-between-wrapper' v-if="recordType != 1 && recordType != 4">
				<view class='input'>
					<text class="iconfont icon-sousuo4"></text>
					<input placeholder='搜索用户名称' placeholder-class='placeholder' v-model="keyword" @confirm="submitForm"
						confirm-type='search' name="search"></input>
				</view>
			</view>
			<timeSlot @changeTime="changeTime"></timeSlot>
			<view class='sign-record'>
				<view class="top_num" v-if="recordType != 4 && recordList.length">
					支出：¥{{expend || 0}} &nbsp;&nbsp;&nbsp; 收入：¥{{income || 0}}
				</view>
				<view class="box">
					<block v-for="(item,index) in recordList" :key="index" v-if="recordList.length>0">
						<view class='list'>
							<view class='item'>
								<!-- <view class='data'>{{item.time}}</view> -->
								<view class='listn'>
									<!-- <block v-for="(child,indexn) in item.child" :key="indexn"> -->
									<view class='itemn1 acea-row row-between-wrapper'>
										<view>
											<view class='name line1'>
												{{item.title}}
												<!-- <text class="status_badge success" v-if="recordType == 4 && item.status == 1">审核通过</text> -->
												<text class="status_badge default" v-if="recordType == 4 && item.status == 0">待审核</text>
												<text class="status_badge error" v-if="recordType == 4 && item.status == 2">未通过</text>
												<!-- 提现记录： 0 待审核 1 通过 2 未通过 -->
											 </view>
											 <view class="mark" v-if="recordType == 4 && item.mark && item.status !== 1">{{item.mark}}</view>
											<view>{{item.add_time}}</view>
										</view>
										<view class='num font-color' v-if="item.pm == 1">+{{item.number}}</view>
										<view class='num' v-else>-{{item.number}}</view>
									</view>
									<!-- </block> -->
								</view>
							</view>
						</view>
					</block>
				</view>

				<view class='loadingicon acea-row row-center-wrapper' v-if="recordList.length">
					<text class='loading iconfont icon-jiazai' :hidden='loading==false'></text>{{loadTitle}}
				</view>
				<view v-if="recordList.length < 1 && page > 1">
					<emptyPage title='暂无数据~'></emptyPage>
				</view>
			</view>
		</view>
		<home v-if="navigation"></home>
	</view>
</template>

<script>
	import {
		moneyList,
		getSpreadInfo
	} from '@/api/user.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import {
		mapGetters
	} from "vuex";
	// #ifdef MP
	import authorize from '@/components/Authorize';
	// #endif
	import emptyPage from '@/components/emptyPage.vue'
	import home from '@/components/home';
	import colors from '@/mixins/color.js';
	import timeSlot from '@/components/timeSlot'
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
				name: '',
				keyword: '',
				type: 0,
				page: 1,
				limit: 15,
				loading: false,
				loadend: false,
				loadTitle: '加载更多',
				recordList: [],
				recordType: 0,
				recordCount: 0,
				extractCount: 0,
				times: [],
				start: 0,
				stop: 0,
				income: '',
				expend: ''
			};
		},
		computed: mapGetters(['isLogin']),
		onLoad(options) {
			if (this.isLogin) {
				this.type = options.type;
			} else {
				toLogin();
			}
		},
		onShow: function() {
			let type = this.type;
			if (type == 1) {
				uni.setNavigationBarTitle({
					title: "佣金记录"
				});
				this.name = '提现总额';
				this.recordType = 3;
				this.getRecordList();
				// this.getRecordListCount();
			} else if (type == 2) {
				uni.setNavigationBarTitle({
					title: "佣金记录"
				});
				this.name = '佣金明细';
				this.recordType = 3;
				this.getRecordList();
				// this.getRecordListCount();
			} else if (type == 4) {
				uni.setNavigationBarTitle({
					title: "提现记录"
				});
				this.name = '提现明细';
				this.recordType = 4;
				this.getRecordList();
				// this.getRecordListCount();
			} else {
				uni.showToast({
					title: '参数错误',
					icon: 'none',
					duration: 1000,
					mask: true,
					success: function(res) {
						setTimeout(function() {
							// #ifndef H5
							uni.navigateBack({
								delta: 1,
							});
							// #endif
							// #ifdef H5
							history.back();
							// #endif

						}, 1200)
					},
				});
			}
		},
		methods: {
			submitForm() {
				this.page = 1;
				this.limit = 20;
				this.loadend = false;
				this.status = false;
				this.$set(this, 'recordList', []);
				this.$set(this, 'times', []);
				this.getRecordList();
			},
			changeTime(time) {
				this.start = time.start
				this.stop = time.stop
				this.page = 1;
				this.loading = false;
				this.loadend = false;
				this.recordList = [];
				this.getRecordList();
			},
			getRecordList: function() {
				let that = this;
				let page = that.page;
				let limit = that.limit;
				let recordType = that.recordType;
				if (that.loading) return;
				if (that.loadend) return;
				that.loading = true;
				that.loadTitle = '';
				moneyList({
					keyword: this.keyword,
					start: this.start,
					stop: this.stop,
					page: page,
					limit: limit
				}, recordType).then(res => {
					this.expend = res.data.expend;
					this.income = res.data.income;
					// for (let i = 0; i < res.data.time.length; i++) {
					// 	// if (!this.times.includes(res.data.time[i])) {
					// 	this.times.push(res.data.time[i])
					// 	this.recordList.push({
					// 		time: res.data.time[i],
					// 		child: []
					// 	})
					// 	// }
					// }
					// // for (let x = 0; x < this.times.length; x++) {
					// for (let j = 0; j < res.data.list.length; j++) {
					// 	// if (this.times[x] === res.data.list[j].time_key) {

					// 	// }
					// 	this.recordList[j].child.push(res.data.list[j])
					// }
					// // }
					this.recordList = this.recordList.concat(res.data.list)
					let loadend = res.data.list.length < that.limit;
					that.loadend = loadend;
					that.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
					that.page += 1;
					that.loading = false;
				}).catch(err => {
					that.loading = false;
					that.loadTitle = '加载更多';
				})
			},
			getRecordListCount: function() {
				let that = this;
				getSpreadInfo().then(res => {
					that.recordCount = res.data.commissionCount;
					that.extractCount = res.data.extractCount;
				});
			}
		},
		onReachBottom: function() {
			this.getRecordList();
		}
	}
</script>

<style scoped lang="scss">
	.commission-details .search {
		width: 100%;
		background-color: var(--view-theme);
		border-bottom: 1px solid #f2f2f2;
		height: 86rpx;
		padding: 0 30rpx;
		box-sizing: border-box;
	}

	.commission-details .search .input {
		width: 100%;
		height: 60rpx;
		border-radius: 50rpx;
		background-color: #f5f5f5;
		position: relative;
	}

	.commission-details .search .input input {
		height: 100%;
		font-size: 26rpx;
		width: 100%;
		padding-left: 60rpx;
	}

	.box {
		border-radius: 14rpx;
		margin: 0 30rpx;
		overflow: hidden;
	}

	.commission-details .search .input .placeholder {
		color: #bbb;
	}

	.commission-details .search .input .iconfont {
		position: absolute;
		left: 28rpx;
		color: #999;
		font-size: 28rpx;
		top: 50%;
		transform: translateY(-50%);
	}

	.sign-record {
		margin-top: 20rpx;
	}

	.commission-details .promoterHeader .headerCon .money {
		font-size: 36rpx;
	}

	.commission-details .promoterHeader .headerCon .money .num {
		font-family: 'Guildford Pro';
	}

	.top_num {
		padding: 10rpx 30rpx 30rpx 30rpx;
		font-size: 26rpx;
		color: #666;
	}

	.radius15 {
		border-radius: 14rpx 14rpx 0 0;
	}
	.sign-record .list .item .listn .itemn1{border-bottom:1rpx solid #eee;padding:22rpx 24rpx;}
	.sign-record .list .item .listn .itemn1 .name{width:390rpx;font-size:28rpx;color:#282828;margin-bottom:10rpx;}
	.sign-record .list .item .listn .itemn1 .num{font-size:36rpx;font-family: 'Guildford Pro';color:#16ac57;}
	.sign-record .list .item .listn .itemn1 .num.font-color{color:#e93323!important;}
	.mark{
		margin-bottom: 10rpx;
	}
	.status_badge{
		display: inline-block;
		height: 30rpx;
		border-radius: 4rpx;
		font-size: 20rpx;
		line-height: 30rpx;
		font-family: PingFangSC-Regular, PingFang SC;
		font-weight: 400;
		margin-left:12rpx;
		padding:0 6rpx 0;
	}
	.success{
		background: rgba(24, 144, 255, .1);
		color: #1890FF;
	}
	.default{
		background: #f5f5f5;
		color: #282828;;
	}
	.error{
		background: rgba(233, 51, 35, .1);
		color: #E93323;
	}
</style>
