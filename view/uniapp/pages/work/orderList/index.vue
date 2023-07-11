<template>
	<view class="my-order">
		<!-- #ifdef H5 -->
		<view class="nav acea-row row-around">
			<view class="item" :class="orderStatus === 0 ? 'on' : ''" @click="statusClick(0)">
				<view>待付款</view>
			</view>
			<view class="item" :class="orderStatus == 1 ? 'on' : ''" @click="statusClick(1)">
				<view>待发货</view>
			</view>
			<view class="item" :class="orderStatus == 2 ? 'on' : ''" @click="statusClick(2)">
				<view>待收货</view>
			</view>
			<view class="item" :class="orderStatus == 3 ? 'on' : ''" @click="statusClick(3)">
				<view>待评价</view>
			</view>
			<view class="item" :class="orderStatus == 4 ? 'on' : ''" @click="statusClick(4)">
				<view>已完成</view>
			</view>
			<view class="item" :class="orderStatus == -3 ? 'on' : ''" @click="statusClick(-3)">
				<view>退款</view>
			</view>
		</view>
		<Loading :loaded="loaded" :loading="loading"></Loading>
		<view class="list">
			<view class="item" v-for="(item,index) in orderList" :key="index" @click="goOrderDetails(item.id)">
				<view class="title acea-row row-between row-middle">
					<view>
						<view class="order_no">订单号：{{item.order_id}}</view>
						<view class="create_time">下单时间：{{item._add_time}}</view>
					</view>
					<view class="sign" v-if="orderStatus == -3 && item.refund_type == 6">已退款</view>
					<view class="sign" v-if="orderStatus == -3 && item.refund_type == 1">仅退款</view>
					<view class="sign" v-if="orderStatus == -3 && item.refund_type == 2">退货退款</view>
					<view class="sign" v-if="orderStatus == -3 && item.refund_type == 3">拒绝</view>
					<view class="sign" v-if="orderStatus == -3 && item.refund_type == 4">同意退货</view>
					<view class="sign" v-if="orderStatus == -3 && item.refund_type == 5">已退货</view>
					<view class="sign" v-else>{{orderStatus | typeMsg}}</view>
				</view>
				<view class="item-info acea-row row-between row-top" v-for="(val, index1) in item.cartInfo" :key="index1">
					<view class="pictrue">
						<image :src="val.productInfo.image"></image>
					</view>
					<view>
						<view class="text acea-row row-between">
							<view class="name line2">{{ val.productInfo.store_name }}</view>
							<view class="money">￥{{ val.productInfo.attrInfo.price }}
								<view>x{{ val.cart_num }}</view>
							</view>
						</view>
						<view class="sku line1" v-if="val.productInfo.attrInfo.suk">
							{{ val.productInfo.attrInfo.suk }}
						</view>
					</view>
				</view>
				<view class="totalPrice">
					 共{{ item.total_num }}件商品，总金额
					<text class="money">￥{{ item.pay_price }}</text>
				</view>
			</view>
		</view>
		<view class="ht100"></view>
		<tNav :active="1"></tNav>
		<!-- #endif -->
	</view>
</template>
<script>
	// #ifdef H5
	import {getWorkOrderList} from "@/api/work.js"
	import Loading from '@/components/Loading/index.vue';
	import tNav from '../components/tabNav.vue';
	import {wx} from "@/utils/agent.js"
	export default{
		data() {
			return {
				userId:"",
				loaded: false,
				loading: false, //是否加载中
				loadend: false, //是否加载完毕
				loadTitle: '加载更多', //提示语
				orderList: [], //订单数组
				orderStatus: 0, //订单状态
				page: 1,
				limit: 10,
			}
		},
		components:{Loading,tNav},
		filters:{
			typeMsg(value){
				const statusMap = {
					0: "待付款",
					1: "待发货",
					2: "待收货",
					3: "待评价",
					4: "已完成",
				};
				return statusMap[value];
			}
		},
		onLoad() {
			this.userId = this.$Cache.get('work_user_id')
			this.getList();
		},
		methods:{
			getUserID(){
				wx.invoke('getContext', {}, (res)=>{
					if(res.err_msg == "getContext:ok"){
						let entry  = res.entry ; 
						//返回进入H5页面的入口类型，
						//目前有normal、contact_profile、single_chat_tools、group_chat_tools、chat_attachment
						wx.invoke('getCurExternalContact', {entry}, (response)=>{
							if(response.err_msg == "getCurExternalContact:ok"){
								//返回当前外部联系人userId
								this.userId = response.userId;
								this.getList();
							}
						});
					}
				});
			},
			statusClick(index){
				if(this.loading) return
				if (index === this.orderStatus) return;
				this.orderStatus = index;
				this.loadend = false;
				this.page = 1;
				this.$set(this, 'orderList', []);
				this.getList();
			},
			getList(){
				let that = this;
				let params = {};
				if (that.loadend) return;
				if (that.loading) return;
				that.loading = true;
				that.loadTitle = '加载更多';
				params = {
					userid:that.userId,
					page:that.page,
					limit:that.limit,
					type:that.orderStatus
				}
				getWorkOrderList(params).then(res=>{
					let list = res.data || [];
					let loadend = list.length < that.limit;
					that.orderList = that.$util.SplitArray(list, that.orderList);
					that.$set(that, 'orderList', that.orderList);
					that.loadend = loadend;
					that.loading = false;
					that.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
					that.page = that.page + 1;
				}).catch(err=>{
					return that.$util.Tips({
						title: err
					});
				})
			},
			goOrderDetails(id){
				uni.navigateTo({
					url:'/pages/work/orderDetail/index?id=' + id
				})
			}
		},
		onReachBottom: function() {
			this.getList();
		}
	}
	// #endif
</script>
<style lang="scss">
	/* #ifdef H5 */
	.nav {
		width: 750rpx;
		height: 92rpx;
		background: #FFFFFF;
	}
	.nav .item {
		text-align: center;
		line-height: 92rpx;
		font-size: 28rpx;
		font-family: PingFangSC-Regular, PingFang SC;
		font-weight: 400;
		color: #333333;
	}
	.nav .item.on {
		font-weight: 400;
		color: #1890FF;
	}
	.list {
		width: 690rpx;
		margin: 28rpx auto 0;
	}
	
	.list .item {
		background-color: #fff;
		border-radius: 12rpx;
		margin-bottom: 28rpx;
	}
	.my-order .list .item .title {
		height: 110rpx;
		padding: 16rpx 24rpx 18rpx;
		box-sizing: border-box;
		border-bottom: 1rpx solid #eee;
		.order_no{
			font-size: 30rpx;
			font-family: PingFangSC-Medium, PingFang SC;
			font-weight: 500;
			color: rgba(0, 0, 0, 0.85);
			line-height: 42rpx;
		}
		.create_time{
			font-size: 24rpx;
			font-family: PingFangSC-Regular, PingFang SC;
			font-weight: 400;
			color: #666666;
			line-height: 34rpx;
		}
	}
	
	.my-order .list .item .title .sign {
		color: #1890FF;
	}
	
	.my-order .list .item .item-info {
		padding: 0 30rpx;
		margin-top: 22rpx;
	}
	
	.my-order .list .item .item-info .pictrue {
		width: 140rpx;
		height: 140rpx;
	}
	
	.my-order .list .item .item-info .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 8rpx;
	}
	
	.my-order .list .item .item-info .text {
		width: 486rpx;
		font-size: 28rpx;
		color: #999;
	}
	
	.my-order .list .item .item-info .text .name {
		width: 306rpx;
		color: rgba(0, 0, 0, 0.85);
		font-size: 28rpx;
		height: 80rpx;
		line-height: 40rpx;
		margin-left: 22rpx;
	}
	.my-order .list .item .sku{
		width: 280rpx;
		margin: 26rpx 0 0 22rpx;
		font-size: 24rpx;
		color: #666;
	}
	.my-order .list .item .item-info .text .money {
		text-align: right;
		width: 150rpx;
	}
	
	.my-order .list .item .item-info .text .money .return{
		margin-top: 10rpx;
		font-size: 24rpx;
	}
	
	.my-order .list .item .totalPrice {
		font-size: 26rpx;
		color: #282828;
		text-align: right;
		margin: 27rpx 0 0 30rpx;
		padding: 0 30rpx 30rpx 0;
		border-bottom: 1rpx solid #eee;
	}
	
	.my-order .list .item .totalPrice .money {
		font-size: 28rpx;
		font-weight: bold;
		color: #F5222D;
	}
	
	.my-order .list .item .bottom {
		height: 107rpx;
		padding: 0 30rpx;
	}
	
	.my-order .list .item .bottom .bnt {
		width: 176rpx;
		height: 60rpx;
		text-align: center;
		line-height: 60rpx;
		color: #fff;
		border-radius: 50rpx;
		font-size: 27rpx;
	}
	
	.my-order .list .item .bottom .bnt.cancelBnt {
		border: 1rpx solid #ddd;
		color: #aaa;
	}
	
	.my-order .list .item .bottom .bnt~.bnt {
		margin-left: 17rpx;
	}
	.ht100{
		height: 120rpx;
	}
	/* #endif */ 
</style>