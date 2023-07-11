<template>
	<view>
		<view class="order-index">
			<view class="topBox">
				<view class="header acea-row">
					<view class="items">
						<image :src="user.avatar" mode=""></image>
						<span class="font">{{user.staff_name}}</span><span>({{manager == 0?'店员':'店长'}})</span>
					</view>
					<view class="item">
						<!-- <view class="iconfont icon-saoma"  @click="scanCode"></view> -->
						<!-- #ifdef MP || MP-WEIXIN || APP -->
						<view class="iconfont icon-saoma" @click="scanCode">
						</view>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<view v-if="isWeixin" class="iconfont icon-saoma" @click="scanCode"></view>
						<!-- #endif -->
					</view>
				</view>
				<view class="topstatus acea-row" v-if="is_manager == 1 || user.order_status == 1">
					<navigator url="/pages/admin/store/order/index?type=0" hover-class="none">
						<view class="title">{{orderlist.unpaid_count}}</view>
						<view>待付款</view>
					</navigator>
					<navigator url="/pages/admin/store/order/index?type=1" hover-class="none">
						<view class="title">{{orderlist.unshipped_count}}</view>
						<view>待配送</view>
					</navigator>
					<navigator url="/pages/admin/store/order/index?type=5" hover-class="none">
						<view class="title">{{orderlist.unwriteoff_count}}</view>
						<view>待核销</view>
					</navigator>
					<navigator url="/pages/admin/store/order/index?type=3" hover-class="none">
						<view class="title">{{orderlist.evaluated_count}}</view>
						<view>待评价</view>
					</navigator>
					<navigator url="/pages/admin/store/order/index?type=-3" hover-class="none">
						<view class="title">{{orderlist.refund_count}}</view>
						<view>退款</view>
					</navigator>
				</view>
			</view>
			
			<view class="wrapper">
				<view class="title">
					<view class="uni-list-cell-db" @click="hiddened">
						<picker @change="bindPickerChange" :range="array" @cancel="cancel" v-if="is_manager == 1">
							<span class="iconfont icon-shujutongji"></span>
							<label class="aa">{{array[index]}}</label>
							<text class='iconfont' :class='hidden==true?"icon-xiangxia":"icon-xiangshang"'></text>
						</picker>
						<view v-else><span class="iconfont icon-shujutongji"></span>数据统计</view>
					 </view>
					 <view class="tab">
					 	<view class="box" :class="detailtabs== 'today' ? 'on':''" @click="detailtab('today')">今日</view>
					 	<view class="box" :class="detailtabs== 'yesterday' ? 'on':''" @click="detailtab('yesterday')">昨日</view>
					 	<view class="box" :class="detailtabs== 'month' ? 'on':''" @click="detailtab('month')">本月</view>
					  </view>
				</view>
				<view class="list acea-row">
					<navigator v-if="manager == 1" class="item" :url="`/pages/admin/store/statistics/index?type=1&time=${detailtabs}&manager=1`" hover-class="none">
						<view class="num">{{ census.send_price }}</view>
						<view>配送订单额</view>
					</navigator>
					<navigator v-if="manager == 1" class="item" :url="`/pages/admin/store/statistics/index?type=2&time=${detailtabs}&manager=1`" hover-class="none">
						<view class="num">{{ census.send_count }}</view>
						<view>配送订单数</view>
					</navigator>
					<navigator v-if="manager == 1" class="item" :url="`/pages/admin/store/statistics/index?type=3&time=${detailtabs}&manager=1`" hover-class="none">
						<view class="num">{{ census.refund_price }}</view>
						<view>退款订单额</view>
					</navigator>
					<navigator class="item" :url="`/pages/admin/store/statistics/index?type=4&time=${detailtabs}&manager=${manager}`" hover-class="none">
						<view class="num">{{ census.cashier_price }}</view>
						<view>收银订单额</view>
					</navigator>
					<navigator class="item" :url="`/pages/admin/store/statistics/index?type=5&time=${detailtabs}&manager=${manager}`" hover-class="none">
						<view class="num">{{ census.writeoff_price }}</view>
						<view>核销订单额</view>
					</navigator>
					<navigator class="item" :url="`/pages/admin/store/statistics/index?type=6&time=${detailtabs}&manager=${manager}`" hover-class="none">
						<view class="num">{{ census.svip_price }}</view>
						<view>付费会员额</view>
					</navigator>
					<navigator class="item" :url="`/pages/admin/store/statistics/index?type=7&time=${detailtabs}&manager=${manager}`" hover-class="none">
						<view class="num">{{ census.recharge_price }}</view>
						<view>充值订单额</view>
					</navigator>
					<navigator class="item" :url="`/pages/admin/store/statistics/index?type=8&time=${detailtabs}&manager=${manager}`" hover-class="none">
						<view class="num">{{ census.spread_count }}</view>
						<view>推广用户数</view>
					</navigator>
					<navigator class="item" :url="`/pages/admin/store/statistics/index?type=9&time=${detailtabs}&manager=${manager}`" hover-class="none">
						<view class="num">{{ census.card_count }}</view>
						<view>激活会员卡</view>
					</navigator>
				</view>
			</view>
			<view class="public-wrapper">
				<view class="title">
					 <view class="uni-list-cell-db" @click="hiddened">
						<span class="iconfont icon-xiangxishuju"></span>详细数据
					 </view>
					 <view class="tab">
					 	<view v-if="manager == 1" class="box" :class="tabs== 1 ? 'on':''" @click="tab(1)">配送</view>
					 	<view class="box" :class="tabs== 2 ? 'on':''" @click="tab(2)">收银</view>
					 	<view class="box" :class="tabs== 3 ? 'on':''" @click="tab(3)">核销</view>
					 	<view class="box" :class="tabs== 4 ? 'on':''" @click="tab(4)">充值</view>
					  </view>
				</view>
				<view class="nav acea-row row-between-wrapper">
					<view class="data">日期</view>
					<view class="browse">订单数</view>
					<view class="turnover">金额</view>
				</view>
				<Loading :loaded="loaded" :loading="loading"></Loading>
				<view v-if="list.length" class="conter">
					<view class="item acea-row row-between-wrapper" v-for="(item, index) in list" :key="index">
						<view class="data">{{ item.time }}</view>
						<view class="browse">{{ item.count }}</view>
						<view class="turnover">￥{{ item.price }}</view>
					</view>
				</view>
				<view v-else class="unconter">
					<view v-if="!loading">暂无数据</view>
				</view>
			</view>
		</view>
		<home v-if="navigation"></home>
	</view>
</template>

<script>
	import home from '@/components/home';
	import colors from '@/mixins/color.js';
	import {
		// getStatisticsInfo,
		// getStatisticsMonth,
		userInfo,
		orderInfo,
		statisticsMenuApi,
		getListApi,
		orderWriteoffInfo
	} from "@/api/admin";
	import Loading from '@/components/Loading/index.vue'
	export default {
		name: 'adminOrder',
		components: {
			Loading,
			home
		},
		mixins:[colors],
		data() {
			return {
				is_manager: 0,//判断首次进入是否为店长或店员
				manager: 0,//更改店长与店员的切换
				user: {},
				orderlist: {},
				hidden: true,
				page: 1,
				limit: 7,
				tip: 1,
				index: 0,
				detailtabs: 'today',
				 array: ['门店统计', '数据统计'],
				 tabs: 1,
				census: {},
				list: [],
				loaded: false,
				loading: false,
				verify_code: '',
				// #ifdef H5
				isWeixin: this.$wechat.isWeixin()
				// #endif
			}
		},
		onLoad() {
			this.userInfo()
		},
		methods: {
			// 用户信息
			userInfo: function() {
				userInfo().then(res=>{
					// uni.setStorageSync('SETMANAGER', res.data.is_manager)
					this.manager = res.data.is_manager
					this.is_manager = res.data.is_manager
					this.user = res.data
					if(res.data.is_manager == 0){
						this.tabs = 2
					}
					this.orderList()
					// this.getList()
					this.detailtab('today')
				})
			},
			// 订单统计
			orderList: function() {
				orderInfo({is_manager:this.manager}).then(res=>{
					this.orderlist = res.data
				})
			},
			// 统计菜单
			statisticsMenu: function(data) {
				statisticsMenuApi(data).then(res=>{
					this.census = res.data;
				})
			},
			// 详细数据列表
			getList: function() {
				let data ={
					type: this.tabs,
					is_manager : this.manager,
					page: this.page,
					limit: this.limit,
					data:this.detailtabs
				}
				// if (this.loading || this.loaded) return;
				this.loading = true;
				getListApi(data).then(res=>{
					this.loading = false
					// this.list.push(res.data);
					this.page += 1;
					this.list = this.list.concat(res.data);
					if(res.data.length < this.limit){
						this.tip = 2
					}
				})
			},
			hiddened: function(e) {
				this.hidden = !this.hidden;
			},
			cancel: function() {
				this.hidden = !this.hidden;
			},
			bindPickerChange: function(e) {	
				this.tip = 1
				this.page = 1
				this.list = []
				this.hidden = !this.hidden;//改变的事件名
				this.index = e.target.value			//将数组改变索引赋给定义的index变量
				let select = ''
				select=this.array[this.index]		//将array【改变索引】的值赋给定义的jg变量
				if(select == '数据统计'){
					this.manager = 0
					this.tabs = 2
					this.detailtab('today')
				}
				if(select == '门店统计'){
					this.manager = 1
					this.tabs = 1
					this.detailtab('today')
				}
				this.orderList()
			},
			tab: function(type) {
				this.tip = 1
				this.tabs = type
				this.page =  1;
				this.list = []
				this.getList()
			},
			//  统计菜单
			detailtab: function(type) {
				this.detailtabs = type
				let data = {
					is_manager: this.manager,
					data: type
				}
				
				this.list = []
				this.page =  1;
				this.tip = 1
				this.getList()
				this.statisticsMenu(data)
			},
			scanCode() {
				var self = this;
				// #ifdef MP || APP
				uni.scanCode({
					scanType: ["qrCode", "barCode"],
					success(res) {
						self.verify_code = res.result
						self.codeChange();
					},
					fail(res) {},
				})
				// #endif
				//#ifdef H5
				this.$wechat.wechatEvevt('scanQRCode', {
					needResult: 1,
					scanType: ["qrCode", "barCode"]
				}).then(res => {
					let result = res.resultStr;
					if(result.includes(',')){
						 result = result.split(",")[1]
					}
					this.verify_code = result
					this.codeChange();
				});
				//#endif
			},
			// 立即核销
			codeChange: function() {
				let self = this
				let ref = /^[0-9]*$/;
				if (!self.verify_code) return self.$util.Tips({
					title: '请输入核销码'
				});
				if (!ref.test(self.verify_code)) return self.$util.Tips({
					title: '请输入正确的核销码'
				});
				self.$util.Tips({
					title: '查询中'
				});
				setTimeout(() => {
					orderWriteoffInfo(1,{verify_code:self.verify_code,code_type:2}).then(res=>{
						if(res.status == 200){
							uni.navigateTo({
								url:'./scanning/index?code='+self.verify_code
							})
						}else{
							self.$util.Tips({ title: res.msg });
						}
					}).catch(err=>{
						self.$util.Tips({
							title: err
						});
					})
				}, 800);
			},
		},
		onReachBottom(){
			if(this.tip == 1){
				this.getList()
			}
			
		}
	}
</script>

<style lang="scss" scoped>
	/*订单首页*/
	.order-index .topBox{
		padding-bottom: 40upx;
		// height: 360upx;
		background: linear-gradient(270deg, #4276F6 0%, #00ACF8 100%);
	}
	.order-index .header {
		box-sizing: border-box;
		display: flex;
		background-size: 100% 100%;
		width: 100%;
		// height: 120upx;
		padding: 0upx 30upx 0upx 30upx;
		justify-content: space-between;
	}
	
	.order-index .header .icon-saoma{
		font-size: 40rpx;
		padding: 30rpx 20rpx 30rpx 80rpx;
	}

	.order-index .header .item,.order-index .header .items {
		font-size: 24upx;
		color: #fff;
		height: 120upx;
		display: flex;
		align-items: center;
	}
	.order-index .header .item .font,.order-index .header .items .font{
		display: inline-block;
		margin-left: 16upx;
		margin-right: 16upx;
		font-size: 30upx;
		
	}
	.order-index .header .items image{
		width: 64upx;
		height: 64upx;
		border-radius: 60upx;
		border: 2upx solid #FFFFFF;
	}
	
	.order-index .topstatus{
		padding: 0upx 56upx 30upx;
		display: flex;
		justify-content: space-between;
		color: #FFFFFF;
		text-align: center;
		font-size: 24upx;
		font-weight: 400;
		box-sizing: border-box;
		// background: linear-gradient(270deg, #4276F6 0%, #00ACF8 100%);
		.title{
			font-size: 40upx;
			margin-bottom: 6upx;
		}
	}
	
	.order-index .wrapper {
		width: 690upx;
		background-color: #fff;
		border-radius: 10upx;
		margin: -46upx auto 0 auto;
		padding-top: 25upx;
		
	}
	
	.order-index .wrapper .title .iconfont {
		color: #2291f8;
		font-size: 40upx;
		margin-right: 13upx;
		vertical-align: middle;
	}

	.order-index .wrapper .title {
		font-size: 30upx;
		color: #282828;
		padding: 0 30upx;
		margin-bottom: 40upx;
		display: flex;
		justify-content: space-between;
		.uni-list-cell-db .iconfont{
			font-size: 24upx ;
			color: #999 ;
			margin-left: 14upx;
		}
		.tab{
			width: 240upx;
			height: 48upx;
			background: #F5F5F5;
			border-radius: 24upx;
			display: flex;
			justify-content: space-between;
			font-weight: 400;
			color: #999999;
			font-size: 24upx;
			.box{
				width: 82upx;
				height: 48upx;
				border-radius: 24upx;
				text-align: center;
				line-height: 48upx;
			}
			.on{
				background: #1890FF;
				color: #FFFFFF;
			}
			
		}
	}


	.order-index .wrapper .list .item {
		width: 33.33%;
		text-align: center;
		font-size: 24upx;
		color: #999;
		margin-bottom: 45upx;
	}

	.order-index .wrapper .list .item .num {
		font-size: 40upx;
		color: #333;
	}

	.public-wrapper .title {
		font-size: 30upx;
		color: #282828;
		padding: 0 30upx;
		margin-bottom: 20upx;
		font-size: 30upx;
		// margin-bottom: 40upx;
		display: flex;
		justify-content: space-between;
		.uni-list-cell-db .iconfont{
			font-size: 24upx ;
			color: #999 ;
			margin-left: 14upx;
		}
		.tab{
			// width: 240upx;
			height: 48upx;
			background: #F5F5F5;
			border-radius: 24upx;
			display: flex;
			justify-content: space-between;
			font-weight: 400;
			color: #999999;
			font-size: 24upx;
			.box{
				width: 82upx;
				height: 48upx;
				border-radius: 24upx;
				text-align: center;
				line-height: 48upx;
			}
			.on{
				background: #1890FF;
				color: #FFFFFF;
			}
			
		}
	}

	.public-wrapper .title .iconfont {
		color: #2291f8;
		font-size: 40upx;
		margin-right: 13upx;
		vertical-align: middle;
	}

	.public-wrapper {
		margin: 18upx auto 0 auto;
		width: 690upx;
		background-color: #fff;
		border-radius: 10upx;
		padding-top: 25upx;
		// padding-bottom: 25upx;
	}

	.public-wrapper .nav {
		padding: 0 30upx;
		height: 70upx;
		line-height: 70upx;
		font-size: 24upx;
		color: #999;
	}

	.public-wrapper .data {
		width: 210upx;
		text-align: left;
	}

	.public-wrapper .browse {
		width: 192upx;
		text-align: left;
	}

	.public-wrapper .turnover {
		width: 227upx;
		text-align: right;
	}

	.public-wrapper .conter {
		padding: 0 30upx;
		margin-bottom: 40upx;
	}

	.public-wrapper .conter .item {
		border-bottom: 1px solid #f7f7f7;
		height: 70upx;
		font-size: 24upx;
	}

	.public-wrapper .conter .item .turnover {
		color: #000000;
		font-weight: 400;
	}
	.public-wrapper .unconter{
		text-align: center;
		color: #999;
		padding: 25upx;
	}
</style>
