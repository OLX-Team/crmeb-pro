<template>
	<view>
		<view v-if="footer == 'tongji'" class="order-index" ref="container">
			<view class="header acea-row">
				<view class="items">
					<image :src="user.avatar" mode=""></image>
					<span class="font">{{ user.user_nickname }}</span><span>( 配送员 )</span>
				</view>
				<!-- #ifdef MP || APP-->
				<view class="item">
					<view class="iconfont icon-saoma ite" @click="to"></view>
				</view>
				<!-- #endif -->
				<!-- #ifdef H5 -->
				<view v-if="isWeixin" class="item">
					<view class="iconfont icon-saoma ite" @click="to"></view>
				</view>
				<!-- #endif -->
				
			</view>
			<view class="wrapper">
				<view class="title">
					<view class="uni-list-cell-db" @click="hiddened">
						<picker @change="bindPickerChange" :range="arrays" @cancel="cancel">
							<!-- <span class="iconfont icon-shujutongji"></span> -->
							<label class="aa">{{array[index].name}}</label>
							<text class='iconfont' :class='hidden==true?"icon-xiangxia":"icon-xiangshang"'></text>
						</picker>
					 </view>
					 <view class="tab">
					 	<view class="box" :class="detailtabs== 'today' ? 'on':''" @click="detailtab('today')">今日</view>
					 	<view class="box" :class="detailtabs== 'yesterday' ? 'on':''" @click="detailtab('yesterday')">昨日</view>
					 	<view class="box" :class="detailtabs== 'month' ? 'on':''" @click="detailtab('month')">本月</view>
					  </view>
				</view>
				<Loading :loaded="loaded" :loading="loading"></Loading>
				<view class="list acea-row" v-if="!loading">
					<view class="item">
						<view class="num">{{ census.unsend }}</view>
						<view>待配送订单</view>
					</view>
					<view class="item">
						<view class="num">{{ census.send }}</view>
						<view>已配送订单</view>
					</view>
					<view class="item">
						<view class="num">{{ census.send_price }}</view>
						<view>完成配送额</view>
					</view>
				</view>
			</view>
			<view class="public-wrapper">
				<view class="title">
					 <view class="uni-list-cell-db" @click="hiddened">
						配送数据
					 </view>
				</view>
				<view class="nav acea-row row-between-wrapper">
					<view class="data">日期</view>
					<view class="browse">订单数</view>
					<view class="turnover">金额</view>
				</view>
				<Loading :loaded="loaded" :loading="loading"></Loading>
				<view v-if="list[0]" class="conter">
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
		<view v-if="footer == 'list'" class="order-index">
			<view class="tongji-index">
				<view class="header acea-row">
					<view class="items">
						<image :src="user.avatar" mode=""></image>
						<span class="font">{{ user.user_nickname }}</span><span>( 配送员 )</span>
					</view>
					<view class="item">
						<!-- #ifdef MP || MP-WEIXIN -->
						<view class="item">
							<view class="iconfont icon-saoma ite" @click="to"></view>
						</view>
						<!-- #endif -->
						<!-- #ifdef H5 -->
						<view v-if="isWeixin" class="item">
							<view class="iconfont icon-saoma ite" @click="to"></view>
						</view>
						<!-- #endif -->
					</view>
				</view>
				<view class="tab">
					<view><span class="box" :class="type == '1'?'on':''" @click="tab('1')">待配送（ {{ count.unsend }} ）</span></view>
					<view><span class="box" :class="type == '2'?'on':''" @click="tab('2')">已配送（ {{ count.send }} ）</span></view>
				</view>
				<view>
					<view v-if="orderlist.length != 0" class="content" v-for="(item,index) in orderlist"  @click="jump(item.id)">
						<view class="content_top pad">
							<!-- item.order_id -->
							<view class="content_top_left">{{ item.add_time }}</view>
							<view v-if="item.store" class="content_top_right line1">{{item.store.name}}</view>
						</view>
						<view class="content_font pad">
							<p v-if="item.store" class="txt">门店地址：{{item.store.detailed_address}}</p>
							<p class="txt">配送地址：{{item.user_address}}</p>
						</view>
						<view  class="content_box"   v-for="(val, key) in item._info" :key="key">
							<image :src="val.cart_info.productInfo.image" mode=""></image>
							<view  class="content_box_title">
								<view class="txt">
									<view class="textbox"><text class="icon-color" v-if="val.cart_info.is_gift">[赠品]</text>{{ val.cart_info.productInfo.store_name }}</view>
									<view>x {{ val.cart_info.cart_num }}</view>
								</view>
								<p class="attribute">属性：{{ val.cart_info.productInfo.attrInfo.suk }}</p>
								<p>¥ {{ val.cart_info.productInfo.attrInfo.price }} </p>
							</view>
						</view>
						<view class="content_bottom">
							<view></view>
							<view>共{{ item.total_num }}件商品，订单实付：<span class="money">￥{{ item.pay_price }}</span></view>
						</view>
					</view>
					<view v-if="!orderlist.length && !loading" class="nothing">
						<image src="../../../static/images/no-thing.png" mode=""></image>
						<view>暂无数据</view>
					</view>
				</view>				
				<Loading :loaded="loaded" :loading="loading"></Loading>
			</view>
		</view>
		<view class="footer">
			<view class="tab" :class="footer == 'list'?'on':''" @click="footerTab('list')">
				<view class="iconfont icon-dingdan"></view>
				<view class="font">订单列表</view>
			</view>
			<view class="tab" :class="footer == 'tongji'?'on':''" @click="footerTab('tongji')">
				<view class="iconfont icon-tongji"></view>
				<view  class="font">数据统计</view>
			</view>
		</view>
	</view>
</template>

<script>
	import {
		// getStatisticsInfo,
		// getStatisticsMonth,
		deliveryInfo,
		deliveryStatistics,
		deliveryList,
		deliveryOrderList,
		orderWriteoffInfo
	} from "@/api/admin";
	import Loading from '@/components/Loading/index.vue'
	export default {
		name: 'adminOrder',
		components: {
			Loading
		},
		data() {
			return {
				hidden: true,
				index: 0,
				detailtabs: 'today',
				footer: 'list',
				type: '1',
				arrays: [], //展示下拉时的数据
				array: [], //下拉时选择的数据
				storeInfoid: 0, //下拉时选择的数据ID 
				census: {},
				list: [],
				orderlist: [],
				count: {},
				page: 1,
				limit: 15,
				loaded: false,
				loading: false,
				user: {},
				ids: '',
				verify_code: '',
				// #ifdef H5
				isWeixin: this.$wechat.isWeixin()
				// #endif
			}
		},
		onLoad() {
			// this.getIndex();
			// this.init()
			this.userInfo();
			this.footerTab('list')
			
			// this.$scroll(this.$refs.container, () => {
			// 	!this.loading && this.getList();
			// });
		},
		methods: {
			userInfo(){
				deliveryInfo().then(res=>{
					this.user = res.data
					this.array = res.data.store_info.map(a => a);
					this.arrays = res.data.store_info.map(a => a.name);
					let obj = {
						id: 0,
						name: '全部'
					}
					this.array.unshift(obj);
					this.arrays.unshift(obj.name);
				})
			},
			getStatistics(){
				let data = {
					store_id: this.storeInfoid,
					data: this.detailtabs
				}
				deliveryStatistics(data).then(res=>{
					this.census = res.data
				})
			},
			deliveryLists(){
				let data = {
					page: this.page,
					limit: this.limit,
					store_id: this.storeInfoid,
					data: this.detailtabs
				}
				if (this.loading || this.loaded) return;
				this.loading = true
				deliveryList(data).then(res=>{
					this.loading = false
					this.loaded = res.data.length < this.limit
					this.page += 1
					// this.list = res.data
					this.list.push.apply(this.list, res.data);
				})
			},
			getOrderList(){
				if (this.loading || this.loaded) return;
				this.loading = true
				deliveryOrderList({type:this.type,page:this.page,limit:this.limit}).then(res=>{
					this.loading = false
					this.count = res.data.data
					this.loaded = res.data.list.length < this.limit
					this.page += 1
					this.orderlist.push.apply(this.orderlist, res.data.list);
				})
			},
			init: function() {
				this.orderlist = [];
				this.list = [];
				this.page = 1;
				this.loaded = false;
				this.loading = false;
			},
			to(){
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
				if (!this.verify_code) return self.$util.Tips({
					title: '请输入核销码'
				});
				if (!ref.test(this.verify_code)) return self.$util.Tips({
					title: '请输入正确的核销码'
				});
				self.$util.Tips({
					title: '查询中'
				});
				setTimeout(() => {
					orderWriteoffInfo(2,{verify_code:self.verify_code,code_type:2}).then(res=>{
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
			footerTab: function(type) {
				this.footer = type
				this.init()
				if(type == 'tongji'){
					this.deliveryLists()
					this.getStatistics()
				}
				if(type == 'list'){
					this.getOrderList()
				}
				
			},
			tab(type){
				this.type = type
				this.init()
				this.getOrderList()
			},
			hiddened: function(e) {
				this.hidden = !this.hidden;
			},
			cancel: function() {
				this.hidden = !this.hidden;
			},
			bindPickerChange: function(e) {	
				this.hidden = !this.hidden;
				this.index = e.target.value	
				this.storeInfoid = this.array[this.index].id
				this.init()
				this.getStatistics()
				this.deliveryLists()
			},
			detailtab: function(type) {
				this.detailtabs = type
				this.init()
				this.deliveryLists()
				this.getStatistics()
			},
			jump: function(id){
				uni.navigateTo({
					url:'orderDetail/index?id='+id
				})
			}
		},
		onReachBottom(){
			if(this.footer == 'tongji'){
				this.deliveryList()
			}
			if(this.footer == 'list'){
				this.getOrderList()
			}
		}
	}
</script>

<style lang="scss" scoped>
	.nothing{
		width: 80%; 
		margin: 0 auto; 
		margin-top: 30px;
		text-align: center;
		color: #cfcfcf;
	}
	.ite{
		font-size: 20px;
	}
	.footer{
		display: flex;
		justify-content: space-around;
		width: 100%;
		text-align: center;
		height: 100upx;
		background: #FFFFFF;
		position: fixed;
		bottom: 0;
		left: 0;
		.tab{
			display: flex;
			flex-direction: column;
			color: #999999;
			margin-top: 16upx;
			
		}
		.on{
			color: #1890FF;
			.font{
				color: #3B3B3B;
			}
		}
	}
	.tongji-index{
		padding-bottom: 160upx;
		 .header {
			 background-size: 100% 100%;
			 width: 100%;
			 height: 186upx !important;
			 padding: 20upx 30upx 0 30upx !important;
			 box-sizing: border-box;
			 background: linear-gradient(270deg, #4276F6 0%, #00ACF8 100%);
			 display: flex;
			 justify-content: space-between;
			 .items, .item{
				 font-size: 24upx;
				 color: #fff;
				 height: 120upx;
				 display: flex;
				 align-items: center;
				 image{
					 width: 64upx;
					 height: 64upx;
					 border-radius: 60upx;
					 border: 2upx solid #FFFFFF;
				 }
				 .font{
					 display: inline-block;
					 margin-left: 16upx;
					 margin-right: 16upx;
					 font-size: 30upx;
				 }
			 }
		 }
		 .tab{
			 width: 694upx;
			 height: 86upx;
			 background: #FFFFFF;
			 border-radius: 12upx;
			 margin: -40upx auto 0 auto;
			 display: flex;
			 justify-content: space-around;
			 text-align: center;
			 align-items: center;
			 .box{
				 display: block;
				 height: 62upx;
				 margin-top: 26upx;
			 }
			 .on{
				 border-bottom: 4upx solid #1890FF;
				 color: #1890FF;
			 }
		 }
		 .content{
			 margin: 16upx auto 16upx auto;
			 width: 694upx;
			 // height: 428upx;
			 padding-bottom: 20upx;
			 background: #FFFFFF;
			 border-radius: 12upx;
			 .pad{padding: 20upx 20upx 22upx;}
			 .content_top{
				 // height: 78upx;
				 align-items: center;
				 font-weight: 400;
				 display: flex;
				 justify-content: space-between;
				 border-bottom: 2upx solid #F5F5F5;;
				 .content_top_left{
					 font-size: 26upx;
				 }
				 .content_top_right{
					 font-size: 18upx;
					 color: #1890FF;
					 border: 2upx solid #1890FF;
					 padding: 6upx 10upx;
					 max-width: 300upx;
				 }
			 }
			 .content_font{
				 font-size: 24upx;
				 color: #666666;
				 font-weight: 400;
				 .txt{margin-bottom: 14upx;}
			 }
			 .content_box{
				 // height: 70px;
				 background: #F5F5F5;
				 border-radius: 8upx;
				 margin: 0upx 20upx 22upx;
				 padding: 14upx;
				 padding-right: 22upx;
				 display: flex;
				 justify-content: start;
				 image{
					 width: 112upx;
					 height: 112upx;
					 border-radius: 8upx;
				 }
				 .content_box_title{
					 flex: 1;
					 margin-left: 18upx;
					 font-size: 20upx;
					 font-weight: 400;
					 .txt{
						 display: flex;
						 justify-content: space-between;
						 font-size: 24upx;
						 .textbox{
							 width: 408upx;
							 white-space: nowrap;
							 text-overflow: ellipsis;
							 overflow: hidden;
							 word-break: break-all;
						 }
					 }
					 .attribute{
						 color: #999999;
						 margin: 4upx 0upx 10upx;
					 }
				 }
			 }
			 .content_bottom{
				 display: flex;
				 justify-content: space-between;
				 font-size: 22upx;
				 padding: 0upx 20upx;
				 color: #666666;
				 .money{
					 font-size: 26upx;
					 color: #F5222D;
				 }
			 }
		 }
	}
	/*订单首页*/
	.order-index{position: relative;}
	.order-index .header {
		background-size: 100% 100%;
		width: 100%;
		height: 302upx;
		padding: 45upx 30upx 0 30upx;
		box-sizing: border-box;
		background: linear-gradient(270deg, #4276F6 0%, #00ACF8 100%);
		display: flex;
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
	.order-index .wrapper {
		width: 690upx;
		background-color: #fff;
		border-radius: 10upx;
		margin: -115upx auto 0 auto;
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

	.public-wrapper .title .iconfont {
		color: #2291f8;
		font-size: 40upx;
		margin-right: 13upx;
		vertical-align: middle;
	}

	.public-wrapper {
		margin: 18upx auto 0 auto;
		margin-bottom: 160upx;
		width: 690upx;
		background-color: #fff;
		border-radius: 10upx;
		padding-top: 25upx;
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
