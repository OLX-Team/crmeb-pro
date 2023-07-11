<template>
	<view class="order-details">
		<!-- #ifdef H5 -->
		<!-- 给header上与data上加on为退款订单-->
		<view class="nav_bar">
			订单详情
			<navigator url="/pages/work/orderList/index">
				<text class="iconfont icon-fanhui2"></text>
			</navigator>
		</view>
		<view class='header acea-row row-middle' :class='isGoodsReturn ? "on":""'>
			<view class='pictrue' v-if="isGoodsReturn==false"> 
				<image :src="orderInfo.status_pic"></image>
			</view>
			<view class='data' :class='isGoodsReturn ? "on":""'>
				<view class='state'>{{title}}</view>
				<view>{{orderInfo._add_time }}</view>
			</view>
		</view>
		<view class="address_info">
			<view class="user_top acea-row row-middle" v-if="orderInfo.real_name">
				<text class="iconfont icon-yonghu2"></text>
				<text class="order_name">{{ userInfo.nickname }}</text>
			</view>
			<view class="address" v-if="orderInfo.product_type == 0 && orderInfo.delivery_type !='fictitious'">
				<view class="user">
					<span>{{ orderInfo.real_name }}</span>
					<span class="pl34">{{ orderInfo.user_phone }}</span>
				</view>
				<view class="detail">{{orderInfo.user_address }}</view>
			</view>
		</view>
		<view class="list">
			<view class="item-info acea-row row-between row-top" v-for="(item, index) in orderInfo.cartInfo" :key="index">
				<view class="pictrue">
					<image :src="item.productInfo.image"></image>
				</view>
				<view>
					<view class="text acea-row row-between">
						<view class="name line2">{{ item.productInfo.store_name }}</view>
						<view class="money">￥{{ item.productInfo.attrInfo.price }}
							<view class="cart_num">x{{ item.cart_num }}</view>
						</view>
					</view>
					<view class="sku line1" v-if="item.productInfo.attrInfo.suk">
						{{ item.productInfo.attrInfo.suk }}
					</view>
				</view>
			</view>
			<view class="public-total">
			    共{{ orderInfo.total_num }}件商品，实付款
			    <span class="money">￥{{ orderInfo.pay_price }}</span> ( 邮费 ¥{{ orderInfo.pay_postage}})
			</view>
			<div class='wrapper' v-if='orderInfo.delivery_type=="fictitious" && orderInfo.product_type!=1'>
				<view class='item acea-row row-between'>
					<view>虚拟发货：</view>
					<view class='conter'>已发货，请注意查收</view>
				</view>
				<div class='item acea-row row-between'>
					<div>虚拟备注：</div>
					<div class='conter'>{{orderInfo.fictitious_content}}</div>
				</div>
			</div>
			<div class='wrapper' v-if="orderInfo.virtual_info && orderInfo.product_type==1">
				<div class='item acea-row row-between'>
					<div>卡密发货：</div>
					<div class='conter'>{{orderInfo.virtual_info}}</div>
				</div>
			</div>
			<!-- <div class='wrapper' style="border-bottom: 1px solid #eee;" v-if="orderInfo.custom_form && orderInfo.custom_form.length">
				<div class='item acea-row row-between' v-for="(item,index) in orderInfo.custom_form" :key="index" v-if="item.value">
					<div>{{item.title}}：</div>
					<div v-if="item.label == 'img'" class='conter'>
						<div class='pictrue' v-for="(img,indexn) in item.value" :key="indexn">
							<image :src='img'/>
						</div>
					</div>
					<div v-if="item.label != 'img'" class='conter'>{{item.value}}</div>
				</div>
			</div> -->
			<view class='wrapper'>
				<view class='item acea-row row-between'>
					<view>订单编号：</view>
					<view class='conter'> {{ orderInfo.order_id}}
					<text class="copy copy-data" @tap="copy(orderInfo.order_id)">复制</text>
					</view>
				</view>
				 <div class="item acea-row row-between">
					<div>支付时间：</div>
					<div class="conter">{{ orderInfo._pay_time }}</div>
				</div>
				<div class="item acea-row row-between">
					<div>支付状态：</div>
					<div class="conter">{{ orderInfo.paid ? '已支付' : '未支付' }}</div>
				</div>
				<div class="item acea-row row-between">
					<div>支付方式：</div>
					<div class="conter">{{ orderInfo._status?orderInfo._status._payType:'' }}</div>
				</div>
				<div class="item acea-row row-between" v-if="orderInfo.mark">
					<div v-if="isReturen == 1">退款留言：</div>
					<div v-else>买家留言：</div>
					<div class="conter">{{ orderInfo.mark }}</div>
				</div>
				<div class="item acea-row row-between" v-if="orderInfo.refund_goods_explain">
					<div>退货留言：</div>
					<div class='conter'>{{orderInfo.refund_goods_explain}}</div>
				</div>
				<div class="item acea-row row-between" v-if="orderInfo.refund_img && orderInfo.refund_img.length">
					<div>退款凭证：</div>
					<div class="conter">
						<div class="pictrue" v-for="(item,index) in orderInfo.refund_img">
						   <image :src="item" mode="aspectFill"/>
						</div>
					</div>
				</div>
				<div class="item acea-row row-between" v-if="orderInfo.refund_goods_img && orderInfo.refund_goods_img.length">
					<div>退货凭证：</div>
					<div class="conter">
						<div class="pictrue" v-for="(item,index) in orderInfo.refund_goods_img">
						   <image :src="item" mode="aspectFill"/>
						</div>
					</div>
				</div>
			</view>
			<div class="wrapper">
				<div class="item acea-row row-between">
					<div>商品总价：</div>
					<div class="conter">￥{{ orderInfo.total_price }}</div>
				</div>
				<div class="item acea-row row-between">
					<div>优惠券抵扣：</div>
					<div class="conter">-￥{{ orderInfo.coupon_price }}</div>
				</div>
				<div class="item acea-row row-between">
					<div>优惠活动金额：</div>
					<div class="conter">-￥{{ orderInfo.promotions_price }}</div>
				</div>
				<div class="item acea-row row-between">
					<div>运费：</div>
					<div class="conter">￥{{ orderInfo.pay_postage }}</div>
				</div>
				<div class="actualPay acea-row row-right">
					实付款：<span class="money font-color-red">￥{{ orderInfo.pay_price }}</span>
				</div>
			</div>
			 <div class="wrapper" v-show="orderInfo.delivery_type">
				<div class="item acea-row row-between">
					<div>配送方式：</div>
					<div class="conter" v-if="orderInfo.delivery_type === 'express'">快递</div>
					<div class="conter" v-if="orderInfo.delivery_type === 'send'">送货</div>
					<div class="conter" v-if="orderInfo.delivery_type === 'cashier'">收银台</div>
					<div class="conter" v-if="orderInfo.delivery_type === 'fictitious'">虚拟发货</div>
				</div>
				<div class="item acea-row row-between">
					<div v-if="orderInfo.delivery_type === 'express'">快递公司：</div>
					<div v-if="orderInfo.delivery_type === 'send'">送货人：</div>
					<div class="conter">{{ orderInfo.delivery_name }}</div>
				</div>
				<div class="item acea-row row-between">
					<div v-if="orderInfo.delivery_type === 'express'">快递单号：</div>
					<div v-if="orderInfo.delivery_type === 'send'">送货人电话：</div>
					<div class="conter" v-if="orderInfo.delivery_id">
						{{ orderInfo.delivery_id}}
						<span class="copy copy-data" @tap="copy(orderInfo.delivery_id)">复制</span>
					</div>
				</div>
			</div>
			<view style='height:20rpx;'></view>
			<!-- <view class='footer acea-row row-right row-middle'>
				<div class="more"></div>
					<div class="bnt cancel" @click="modify(0)" v-if="types === 0">一键改价</div>
					<div class="bnt cancel" @click="modify(2)" v-if="types === -1">立即退款</div>
					<div class="bnt cancel" @click="modify(1)">订单备注</div>
					<div class="bnt cancel"
						v-if="orderInfo.pay_type === 'offline' && orderInfo.paid === 0"
						@click="offlinePay">确认付款</div>
			</view> -->
		</view>
		<!-- #endif -->
	</view>
</template>
<script>
	// #ifdef H5
	import {getWorkOrderInfo} from "@/api/work.js";
	export default{
		data() {
			return {
				userId:"",
				title:"请等待",
				isGoodsReturn:false,
				types:"",
				isReturen:0,
				orderInfo:{},
				userInfo:{}
			}
		},
		onLoad(e) {
			if(e){
				this.order_id = e.id;
				this.userId = this.$Cache.get('work_user_id')
				this.getInfo();
			}
		},
		methods:{
			getInfo(){
				let that = this;
				getWorkOrderInfo(this.order_id,{userid:this.userId,}).then(res=>{
					that.orderInfo = res.data.orderInfo;
					that.userInfo = res.data.userInfo;
					that.types = res.data.orderInfo._status._type;
					that.title = res.data.orderInfo._status._title;
					
				}).catch(err=>{
					return that.$util.Tips({
						title: err
					});
				})
			},
			copy(value){
				let that = this;
				uni.setClipboardData({
					data: value
				});
			}
		}
	}
	// #endif
</script>
<style lang="scss">
	/* #ifdef H5 */
	.order-details .nav_bar{
		height: 88rpx;
		line-height: 88rpx;
		background: #1890FF;
		color: #fff;
		font-size: 32rpx;
		text-align: center;
		position: relative;
		.icon-fanhui2{
			position: absolute;
			left: 12rpx;
			top: 50%;
			transform: translate(0, -50%);
		}
	}
	.order-details .header {
		padding: 0 30rpx;
		height: 150rpx;
		display: flex;
		align-items: center;
		flex-wrap: nowrap;
		background: #1890FF;
	}
	
	.order-details .header.on {
		background-color: #666 !important;
	}
	
	.order-details .header .pictrue {
		width: 110rpx;
		height: 110rpx;
	}
	
	.order-details .header .pictrue image {
		width: 100%;
		height: 100%;
	}
	
	.order-details .header .data {
		color: rgba(255, 255, 255, 0.8);
		font-size: 24rpx;
		margin-left: 27rpx;
	}
	
	.order-details .header .data.on {
		margin-left: 0;
	}
	
	.order-details .header .data .state {
		font-size: 30rpx;
		font-weight: bold;
		color: #fff;
		margin-bottom: 7rpx;
	}
	
	.order-details .header .data .time {
		margin-left: 20rpx;
	}
	.order-details .address_info{
		width: 710rpx;
		margin: 20rpx auto 0;
		background: #FFFFFF;
		border-radius: 12rpx;
		background-image: url(../../../static/images/line.jpg);
		background-repeat: no-repeat;
		background-position: bottom;
		.user_top{
			height: 88rpx;
			padding: 0 34rpx 0;
			box-sizing: border-box;
			border-bottom: 1px solid #f5f5f5;
			.iconfont {
				color: #1890FF;
				font-size: 44rpx;
			}
			.order_name{
				font-size: 28rpx;
				font-family: PingFangSC-Regular, PingFang SC;
				font-weight: 400;
				color: rgba(0, 0, 0, 0.85);
				padding-left: 20rpx;
			}
		}
		.address{
			.user{
				color: rgba(0, 0, 0, 0.85);
				font-size: 28rpx;
				padding: 26rpx 24rpx 12rpx;
				box-sizing: border-box;
			}
			.detail{
				color: #666666;
				font-size: 28rpx;
				padding: 0 24rpx 30rpx;
			}
			.pl34{
				padding-left: 34rpx;
			}
		}
	}
	.list{
		width: 710rpx;
		margin: 20rpx auto 0;
	}
	.item-info {
		padding: 30rpx 24rpx 30rpx;
		background: #fff;
	}
	
	.item-info .pictrue {
		width: 140rpx;
		height: 140rpx;
	}
	
	.item-info .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 8rpx;
	}
	
	.item-info .text {
		width: 486rpx;
		font-size: 28rpx;
		color: #999;
	}
	
	.item-info .text .name {
		width: 306rpx;
		color: rgba(0, 0, 0, 0.85);
		font-size: 28rpx;
		height: 80rpx;
		line-height: 40rpx;
		margin-left: 22rpx;
	}
	.sku{
		width: 306rpx;
		margin: 26rpx 0 0 22rpx;
		font-size: 24rpx;
		color: #666;
	}
	.item-info .text .money {
		width: 150rpx;
		text-align: right;
		color: rgba(0, 0, 0, 0.85);
	}
	.cart_num{
		height: 40rpx;
		font-size: 28rpx;
		font-weight: 400;
		color: rgba(0, 0, 0, 0.85);
		line-height: 40rpx;
		margin-top: 8rpx;
	}
	.item-info .text .money .return{
		margin-top: 10rpx;
		font-size: 24rpx;
	}
	
	.totalPrice {
		font-size: 26rpx;
		color: #282828;
		text-align: right;
		margin: 27rpx 0 0 30rpx;
		padding: 0 30rpx 30rpx 0;
		border-bottom: 1rpx solid #eee;
	}
	
	.totalPrice .money {
		font-size: 28rpx;
		font-weight: bold;
		color: #F5222D;
	}
	.public-total {
		font-size: 28rpx;
		color: #282828;
		height: 92rpx;
		line-height: 92rpx;
		text-align: right;
		padding: 0 24rpx;
		background-color: #fff;
	}

	.public-total .money {
		color: #F5222D;
	}
	.wrapper {
		background-color: #fff;
		padding: 36rpx 24rpx 16rpx;
		margin-top: 20rpx;
		border-radius: 12rpx;
	}
	
	.wrapper .item {
		font-size: 28rpx;
		color: rgba(0, 0, 0, 0.85);
		padding-bottom: 20rpx;
	}
	.wrapper .item .conter {
		color: #868686;
		display: flex;
		flex-wrap: nowrap;
		justify-content: flex-end;
		text-align: right;
		.pictrue{
			width: 140rpx;
			height: 140rpx;
			margin-left: 20rpx;
			image{
				width: 100%;
				height: 100%;
				border-radius: 12rpx;
			}
		}
	}
	.footer {
		width: 100%;
		height: 100rpx;
		position: fixed;
		bottom: 0;
		left: 0;
		background-color: #fff;
		padding: 0 30rpx;
		box-sizing: border-box;
	
		.more {
			position: absolute;
			left: 30rpx;
			font-size: 26rpx;
			color: #333;
	
			.icon-xiangshang {
				margin-left: 6rpx;
				font-size: 22rpx;
			}
		}
	
		.more-box {
			color: #333;
			position: absolute;
			left: 30rpx;
			bottom: 110rpx;
			background-color: #fff;
			padding: 18rpx 24rpx;
			border-radius: 4rpx;
			font-size: 28rpx;
			-webkit-box-shadow: 0px 0px 3px 0px rgba(200, 200, 200, 0.75);
			-moz-box-shadow: 0px 0px 3px 0px rgba(200, 200, 200, 0.75);
			box-shadow: 0px 0px 3px 0px rgba(200, 200, 200, 0.75);
	
			.more-btn {
				color: #333;
				padding: 4rpx;
				z-index: 9999;
			}
		}
	
		.more-box:before {
			content: "";
			width: 0rpx;
			height: 0rpx;
			border-top: 10rpx solid #fff;
			border-bottom: 10rpx solid transparent;
			border-left: 10rpx solid #fff;
			position: absolute;
			bottom: -10rpx;
			left: 0px;
	
		}
	}
	.footer .bnt {
		width: 176rpx;
		height: 60rpx;
		text-align: center;
		line-height: 60rpx;
		border-radius: 50rpx;
		color: #fff;
		font-size: 27rpx;
	}
	
	.footer .bnt.refundBnt {
		width: 210rpx;
	}
	
	.footer .bnt.cancel {
		color: #666;
		border: 1rpx solid #ccc;
	}
	
	.footer .bnt~.bnt {
		margin-left: 18rpx;
	}
	.copy {
		font-size: 20rpx;
		color: #333;
		border-radius: 3rpx;
		border: 1rpx solid #666;
		padding: 3rpx 15rpx;
		margin-left: 24rpx;
		white-space: nowrap;
	}
	/* #endif */
</style>