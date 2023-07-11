<template>
	<view class="order" ref="container">
		<view class="navs">
			<view class="item" :class="type == '0' ? 'on' : ''" @click="settype('0')">待付款</view>
			<view class="item" :class="type == '1' ? 'on' : ''" @click="settype('1')">待配送</view>
			<view class="item" :class="type == '5' ? 'on' : ''" @click="settype('5')">待核销</view>
			<view class="item" :class="type == '3' ? 'on' : ''" @click="settype('3')">待评价</view>
			<view class="item" :class="type == '-3' ? 'on' : ''" @click="settype('-3')">退款</view>
			<view class="item" :class="type == '' ? 'on' : ''" @click="settype('')">全部</view>
		</view>
		<view class="cards">
			<view v-if="orderList.length">
				<view class="content" v-for="(item,index) in orderList">
					<view class="content_top pad" @click="toDetail(item)">
						<view class="content_top_left">
							<p>订单号：{{ item.order_id }}</p>
							<p class="time">下单时间：{{ item.add_time }}</p>
						</view>
						<view class="state" :class="(item.refund_status==0 && type !== 0 && item.refund.length)?'on':''">
							{{item.refund_status==1?'退款中':item.refund_status==2?'已退款':item.refund_status==3?'拒绝退款':item.status_name.status_name}}
							<text v-if="item.refund_status == 0 && type !== 0 && item.refund.length">{{item.is_all_refund?'，退款中':'，部分退款中'}}</text>
						</view>
					</view>
					<view class="content_font"></view>
					<view  v-for="(val, key) in item._info" :key="key" @click="toDetail(item)">
						<view  class="content_box acea-row row-between">
							<image :src="val.cart_info.productInfo.attrInfo?val.cart_info.productInfo.attrInfo.image:val.cart_info.productInfo.image" mode=""></image>
							<view class="content_box_title acea-row row-between">
								<view class="text_left acea-row row-between row-column">
									<view class="textbox line2"><text class="label" v-if="val.cart_info.is_gift">[赠品]</text>{{ val.cart_info.productInfo.store_name }}</view>
									<view class="attribute line1" v-if="val.cart_info.productInfo.attrInfo">属性：{{ val.cart_info.productInfo.attrInfo.suk }}</view>
								</view>
								<view class="text_right">
									<view>¥ {{ val.cart_info.productInfo.attrInfo?val.cart_info.productInfo.attrInfo.price:val.cart_info.productInfo.price }} </view>
									<view>x {{ val.cart_info.cart_num }}</view>
									<view class="info" v-if="val.cart_info.refund_num && item.refund_type !=6">{{val.cart_info.refund_num}}件退款中</view>
								</view>
							</view>
						</view>
					</view>
					<view class="content_bottom">共{{ item.total_num }}件商品，实付款：<span class="money">￥{{ item.pay_price }}</span>( 邮费 ¥{{
				        item.pay_postage
				      }}
						)</view>
					<view class="content_btn">
						<view v-if="item._status == 1" class='box' :class="openErp?'on':''" @click="modify(item, 0)">一键改价</view>
						<view class='box' @click="modify(item, 1)">订单备注</view>
						<view v-if="item._status == 1 && item.pay_type == 'offline'" class='box' :class="openErp?'on':''" @click="offlinePay(item)">确认付款</view>
						<!--  && (item.split == null || item.split.length==0)  有子订单时加的这个条件  -->
						<view class="box boxblue" :class="openErp?'on':''" @click="modify(item, 2, 1)" v-if="(item.refund_type == 0 || item.refund_type == 1 || item.refund_type == 5) && type == -3 && parseFloat(item.pay_price) >= 0">
							立即退款
						</view>
						<view class="box boxblue" :class="openErp?'on':''" @click="modify(item, 2, 0)" v-if="type == -3 && item.refund_type == 2">同意退货</view>
						<view class="wait" :class="openErp?'on':''" v-if="type == -3 && item.refund_type == 4">待用户发货</view>
						<view v-if="type == 1 && (item.shipping_type === 1 || item.shipping_type === 3) && (item.pinkStatus === null || item.pinkStatus === 2)" class='box boxblue' :class="openErp?'on':''" @click="goDelivery(item)">去发货</view>
					<!-- v-if="item._status == 2" -->
					</view>
				</view>
			</view>
			<view v-else class="nothing">
				<image v-if="!loading" src="../../../../static/images/no-thing.png" alt="">
				<view v-if="!loading">暂无记录</view>
			</view>
			
			<Loading :loaded="loaded" :loading="loading"></Loading>
			<PriceChange :change="change" :orderInfo="orderInfo" :isRefund="isRefund" v-on:closechange="changeclose($event)" v-on:savePrice="savePrice"
			 :status="status"></PriceChange>
		</view>
	</view>
</template>
<script>
	import { getOrderlistApi, getRefundMarkApi, getRefundlistApi, getOrderreMarkApi, getOrderPriceApi, getOrderOfflineApi, OrderRefund, storeRefundAgree } from '@/api/admin'
	import { erpConfig } from "@/api/esp.js";
	import PriceChange from '@/components/PriceChange/index'
	import Loading from '@/components/Loading/index.vue'
	import { isMoney } from '@/utils/validate.js'
	export default {
		name: "order",
		components: {
			Loading,
			PriceChange
		},
		props: {},
		data: function() {
			return {
				openErp:false,
				orderList: [],
				type: '',
				page: 1,
				limit: 15,
				loading: false,
				loaded: false,
				change: false,
				orderInfo: {},
				status: "",
				isRefund:0 //1是仅退款;0是退货退款
			};
		},
		onLoad: function(options) {
			this.type = options.type;
			this.getErpConfig();
		},
		onShow(){
			this.settype(this.type);
		},
		computed: {
		},
		methods: {
			goDelivery(item){
				if(this.openErp) return
				uni.navigateTo({
					url: '/pages/admin/store/deliverGoods/index?id='+item.order_id+'&listId='+item.id+'&totalNum='+item.total_num+'&orderStatus='+item._status+'&comeType=1&productType='+item.product_type
				})
			},
			getErpConfig(){
				erpConfig().then(res=>{
					this.openErp = res.data.open_erp;
				}).catch(err=>{
					this.$util.Tips({
						title: err
					})
				})
			},
			settype:function(type) {
				this.type = type
				this.init()
				this.getList();
			},
			// 初始化
			init: function() {
				this.orderList = [];
				this.page = 1;
				this.loaded = false;
				this.loading = false;
				// this.getList();
			},
			getList: function(){
				if (this.loading || this.loaded) return;
				this.loading = true
				let data = {
					status: this.type,
					page: this.page,
					limit: this.limit
				}
				let obj = ''
				if(this.type == -3){
					obj = getRefundlistApi(data);
				}else{
					obj = getOrderlistApi(data);
				}
				obj.then(res=>{
					this.loading = false
					this.loaded = res.data.length < this.limit
					this.orderList.push.apply(this.orderList, res.data);
					this.page += 1
				})
			},
			onReachBottom(){
				this.getList()
			},
			toDetail(item){
				
				uni.navigateTo({
					url:`/pages/admin/store/orderDetail/index?id=${item.id}&types=${this.type}`
				})
				// console.log(item.order_id)
			},
			// 商品操作
			modify: function(item, status,type) {
				if(this.openErp && status !=1) return
				this.change = true;
				this.status = status.toString();
				this.orderInfo = item;
				if(status==2){
					this.isRefund = type
				}
			},
			changeclose: function(msg) {
				this.change = msg;
			},
			//确定付款
			offlinePay: function(item) {
				if(this.openErp) return
			  getOrderOfflineApi({ order_id: item.order_id }).then(
				res => {
				  this.$util.Tips({title:res.msg,icon:"success"});
				  this.init();
					this.getList();
				},
				error => {
				  this.$util.Tips(error);
				}
			  );
			},
			objOrderRefund(data){
				let that = this;
				OrderRefund(data).then(
					res => {
						that.change = false;
						that.$util.Tips({title: res.msg});
						that.init();
						that.getList();
					},
					err => {
						that.change = false;
						that.$util.Tips({title: err});
					}
				);
			},
			async savePrice(opt) {
				let that = this,
					data = {},
					price = opt.price,
					refund_price = opt.refund_price,
					refund_status = that.orderInfo.refund_status,
					remark = opt.remark;
				data.order_id = that.orderInfo.order_id;
				if (that.status == 0) {
					if(!isMoney(price)){
						return that.$util.Tips({title: '请输入正确的金额'});
					}
					data.price = price;
					getOrderPriceApi(data).then(
						res => {
							that.change = false;
							that.$util.Tips({
								title:'改价成功',
								icon:'success'
							})
							this.init();
							this.getList()
						},
						err => {
							that.change = false;
							that.$util.Tips({
								title:'改价失败',
								icon:'none'
							})
						}
					);
				} else if (that.status == 2) {
					if(this.isRefund){
						if(!isMoney(refund_price)){
							return that.$util.Tips({title: '请输入正确的金额'});
						}
						data.price = refund_price;
						data.type = opt.type;
						this.objOrderRefund(data);
						// OrderRefund(data).then(
						// 	res => {
						// 		that.change = false;
						// 		that.$util.Tips({title: res.msg});
						// 		that.init();
						// 		that.getList();
						// 	},
						// 	err => {
						// 		that.change = false;
						// 		that.$util.Tips({title: err});
						// 	}
						// );
					}else{
						if(opt.type==1){
							storeRefundAgree(this.orderInfo.id).then(res=>{
								that.change = false;
								that.$util.Tips({
									title: res.msg
								});
								that.init();
								that.getList();
							}).catch(err=>{
								that.change = false;
								that.$util.Tips({
									title: err
								});
							})
						}else{
							data.type = opt.type;
							this.objOrderRefund(data);
						}
					}
				} else {
					if(!remark){
						return this.$util.Tips({
							title:'请输入备注'
						})
					}
					data.remark = remark;
					let obj = '';
					if(that.type == -3){
						obj = getRefundMarkApi(data);
					}else{
						obj = getOrderreMarkApi(data);
					}
					obj.then(res=>{
							that.change = false;
							this.$util.Tips({
							title:res.msg,
							icon:'success'
							})
					}).catch(err=>{
						 that.change = false;
						 that.$util.Tips({title: err});
					})
				}
			}
		}
	}
</script>
<style lang="scss" scoped>
	.nothing{
		text-align: center;
		color: #cfcfcf;
		
	}
	.color1{color: #FF7E00;}
	.color2{color: #1890FF;}
	/*交易额统计*/
	.order .navs {
		width: 100%;
		height: 96upx;
		background-color: #fff;
		line-height: 96upx;
		position: fixed;
		top: 0;
		left: 0;
		z-index: 9;
		display: flex;
		justify-content: space-between;
		padding: 0upx 26upx;
	}

	

	.order .navs .item {
		font-size: 32upx;
		color: #282828;
		display: inline-block;
	}

	.order .navs .item.on {
		color: #2291f8;
	}

	.order .navs .item .iconfont {
		font-size: 25upx;
		margin-left: 13upx;
	}

	.order .cards{
		padding-top: 96upx;
		.content{
				 margin: 28upx auto 16upx auto;
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
					 border-bottom: 2upx solid #F5F5F5;
					 .state{
						 color: #2291f8;
						 &.on{
							 font-size: 24rpx;
							 width: 150rpx;
							 text-align: right;
						 }
					 }
					 .content_top_left{
						 font-weight: bold;
						 font-size: 30upx;
						 .time{
							 color: #666666;
							 font-size: 24upx;
						 }
					 }
					 .content_top_right{
						 font-size: 30upx;
						 // color: #1890FF;
						 padding: 6upx 10upx;
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
					 // background: #F5F5F5;
					 border-radius: 8upx;
					 // margin: 0upx 20upx 22upx;
					 padding: 27upx;
					 padding-right: 22upx;
					 display: flex;
					 justify-content: start;
					 image{
						 width: 140upx;
						 height: 140upx;
						 border-radius: 8upx;
					 }
					 .content_box_title{
						 margin-left: 18upx;
						 font-size: 20upx;
						 font-weight: 400;
						 width: 480rpx;
						 display: flex;
						 justify-content: space-between;
						 font-size: 24upx;
						 .textbox{
						 		text-overflow: ellipsis;
						 		overflow: hidden;
								.label{
									color: #F5222D;
								}
						 }
						 .text_left{
							 width: 320rpx;
						 }
						 .text_right{
						 		text-align: right;
								.info{
										margin-top: 44rpx;
										font-size: 24rpx;
								}						 
						  }
						 .attribute{
							 color: #999999;
							 width: 340upx;
							 text-overflow: ellipsis;
							 overflow: hidden;
						 }
					 }
				 }
				 .content_bottom{
					 text-align: right;
					 font-size: 22upx;
					 padding: 0upx 20upx;
					 color: #666666;
					 .money{
						 font-size: 26upx;
						 color: #F5222D;
					 }
				 }
				 .content_btn{
					 display: flex;
					 padding: 28upx 28upx;
					 padding-bottom: 0upx;
					 justify-content: flex-end;
					 .wait{
						 margin-left: 20rpx;
						 height: 60rpx;
						 line-height: 60rpx;
					 }
					 .box{
						 width: 172upx;
						 height: 60upx;
						 border-radius: 50upx;
						 border: 2upx solid #EEEEEE;
						 text-align: center;
						 line-height: 60upx;
						 color: #666666;
						 margin: 0 10upx;
						 &.on{
							 color: #c5c8ce!important;
							 background-color: #f7f7f7!important;
							 border-color: #dcdee2!important;
						 }
					 }
					 .boxblue{
						 background-color: #1890FF;
						 color: #FFFFFF;
				 }
				 }
			 }
		
	}
</style>
