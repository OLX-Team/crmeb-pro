<template>
	<view :style="colorStyle">
		<view class="promoter-list">
			<!-- <pageHeader title="推广人列表"></pageHeader> -->
			<view class='search acea-row row-between-wrapper'>
				<view class='input'><input placeholder='点击搜索会员名称' placeholder-class='placeholder' v-model="keyword"
						@confirm="submitForm" confirm-type='search' name="search"></input></view>
				<!-- <button class='iconfont icon-sousuo2' @click="submitForm"></button> -->
			</view>
			<view class='nav acea-row row-around'>
				<view :class="grade == 0 ? 'item on' : 'item'" @click='setType(0)'>一级({{total}})</view>
				<view :class="grade == 1 ? 'item on' : 'item'" @click='setType(1)'>二级({{totalLevel}})</view>
			</view>
			<timeSlot @changeTime="changeTime"></timeSlot>
			<view class='list' v-if="recordList.length">
				<view class="top_num">
					共 <text class="main_color">{{count}}</text> 位推广人，获得佣金<text class="main_color">¥{{price}}</text>
				</view>
				<block v-for="(item,index) in recordList" :key="index">
					<view class='item acea-row row-between-wrapper' :class="index == 0 ? 'radius15' : ''">
						<view class="picTxt acea-row row-between-wrapper">
							<view class='pictrue'>
								<image :src='item.avatar'></image>
							</view>
							<view class='text'>
								<view class='name line1'>{{item.nickname}}</view>
								<view>加入时间: {{item.time}}</view>
							</view>
						</view>
						<view class="right">
							<view><text class='num font-num'>{{item.childCount ? item.childCount : 0}}</text>人</view>
							<view><text class="num">{{item.orderCount ? item.orderCount : 0}}</text>单</view>
							<view><text class="num">{{item.numberCount ? item.numberCount : 0}}</text>元</view>
						</view>
					</view>
				</block>
			</view>
			<view v-if="recordList.length == 0">
				<emptyPage title="暂无数据～"></emptyPage>
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
		spreadPeople
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
	import timeSlot from '@/components/timeSlot'
	import pageHeader from '@/components/pageHeader/index.vue'
	import colors from '@/mixins/color.js';
	export default {
		components: {
			// #ifdef MP
			authorize,
			// #endif
			home,
			timeSlot,
			pageHeader,
			emptyPage
		},
		mixins: [colors],
		data() {
			return {
				total: 0,
				totalLevel: 0,
				teamCount: 0,
				page: 1,
				limit: 20,
				keyword: '',
				sort: '',
				grade: 0,
				status: false,
				recordList: [],
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				start: 0,
				stop: 0,
				count:0,
				price:0
			};
		},
		computed: mapGetters(['isLogin']),
		onLoad() {
			if (this.isLogin) {
				this.userSpreadNewList();
			} else {
				toLogin();
			}
		},
		onShow: function() {
			if (this.is_show) this.userSpreadNewList();
		},
		onHide: function() {
			this.is_show = true;
		},
		methods: {
			changeTime(time) {
				console.log(time)
				this.start = time.start
				this.stop = time.stop
				this.submitForm()
			},
			onLoadFun(e) {
				this.userSpreadNewList();
			},
			// 授权关闭
			authColse: function(e) {
				this.isShowAuth = e
			},
			setSort(sort) {
				let that = this;
				that.sort = sort;
				that.page = 1;
				that.limit = 20;
				that.status = false;
				that.$set(that, 'recordList', []);
				that.userSpreadNewList();
			},
			// setKeyword: function(e) {
			// 	this.keyword = e.detail.value;
			// },
			submitForm: function() {
				this.page = 1;
				this.limit = 20;
				this.status = false;
				this.$set(this, 'recordList', []);
				this.userSpreadNewList();
			},

			setType: function(grade) {
				if (this.grade != grade) {
					this.grade = grade;
					this.page = 1;
					this.limit = 20;
					this.keyword = '';
					this.sort = '';
					this.status = false;
					this.$set(this, 'recordList', []);
					this.userSpreadNewList();
				}
			},
			userSpreadNewList: function() {
				let that = this;
				let page = that.page;
				let limit = that.limit;
				let status = that.status;
				let keyword = that.keyword;
				let sort = that.sort;
				let grade = that.grade;
				let recordList = that.recordList;
				let recordListNew = [];
				if (status == true) return;
				spreadPeople({
					start: this.start,
					stop: this.stop,
					page: page,
					limit: limit,
					keyword: keyword,
					grade: grade,
					sort: sort,
				}).then(res => {
					let len = res.data.list.length;
					let recordListData = res.data.list;
					recordListNew = recordList.concat(recordListData);
					that.total = res.data.total;
					that.totalLevel = res.data.totalLevel;
					that.teamCount = res.data.count;
					that.status = limit > len;
					that.page = page + 1;
					that.$set(that, 'recordList', recordListNew);
					that.count = res.data.count;
					that.price = res.data.price;
				});
			}
		},
		onReachBottom: function() {
			this.userSpreadNewList();
		}
	}
</script>

<style scoped lang="scss">
	.promoter-list .nav {
		background-color: #fff;
		height: 86rpx;
		line-height: 86rpx;
		font-size: 28rpx;
		color: #282828;
		border-bottom: 1rpx solid #eee;
	}

	.promoter-list .nav .item.on {
		border-bottom: 5rpx solid var(--view-theme);
		color: var(--view-theme);
	}

	.promoter-list .search {
		width: 100%;
		background-color: var(--view-theme);
		border-bottom: 1px solid #f2f2f2;
		height: 86rpx;
		padding: 0 30rpx;
		box-sizing: border-box;
	}

	.promoter-list .search .input {
		width: 100%;
		height: 60rpx;
		border-radius: 50rpx;
		background-color: #f5f5f5;
		position: relative;
	}

	.promoter-list .search .input input {
		height: 100%;
		font-size: 26rpx;
		width: 100%;
		padding-left: 30rpx;
	}

	.promoter-list .search .input .placeholder {
		color: #bbb;
	}

	.promoter-list .search .input .iconfont {
		position: absolute;
		right: 28rpx;
		color: #999;
		font-size: 28rpx;
		top: 50%;
		transform: translateY(-50%);
	}

	.promoter-list .search .iconfont {
		font-size: 45rpx;
		color: #515151;
		background-color: var(--view-theme);
		width: 110rpx;
		height: 60rpx;
		line-height: 60rpx;
	}

	.promoter-list .list {
		margin-top: 12rpx;
	}

	.promoter-list .list .sortNav {
		background-color: #fff;
		height: 76rpx;
		border-bottom: 1rpx solid #eee;
		color: #333;
		font-size: 28rpx;
	}

	.promoter-list .list .sortNav .sortItem {
		text-align: center;
		flex: 1;
	}

	.promoter-list .list .sortNav .sortItem image {
		width: 24rpx;
		height: 24rpx;
		margin-left: 6rpx;
		vertical-align: -3rpx;
	}

	.promoter-list .list .item {
		background-color: #fff;
		border-bottom: 1rpx solid #eee;
		height: 152rpx;
		padding: 0 30rpx 0 20rpx;
		font-size: 24rpx;
		color: #666;
		margin: 0 30rpx;
	}

	.promoter-list .list .item .picTxt {
		width: 440rpx;
	}

	.promoter-list .list .item .picTxt .pictrue {
		width: 106rpx;
		height: 106rpx;
		border-radius: 50%;
	}

	.promoter-list .list .item .picTxt .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 50%;
		border: 3rpx solid #fff;
		box-shadow: 0 0 10rpx #aaa;
		box-sizing: border-box;
	}

	.promoter-list .list .item .picTxt .text {
		width: 304rpx;
		font-size: 24rpx;
		color: #666;
	}

	.promoter-list .list .item .picTxt .text .name {
		font-size: 28rpx;
		color: #333;
		margin-bottom: 13rpx;
	}

	.promoter-list .list .item .right {
		width: 190rpx;
		text-align: right;
		font-size: 22rpx;
		color: #333;
	}

	.promoter-list .list .item .right .num {
		margin-right: 7rpx;
	}
	.top_num{
		padding: 30rpx;
		font-size: 26rpx;
		color: #666;
	}
	.main_color{
		color: #E93323;
	}
	.radius15{
		border-radius: 14rpx 14rpx 0 0;
	}
</style>
