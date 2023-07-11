<template>
	<view class="order-details pos-order-details">
		<view class="header acea-row row-middle">
			<view class="state">{{title}}</view>
			<view class="data">
				<view class="order-num">订单号：{{orderInfo.order_id}}</view>
				<view>
					<span class="time">下单时间：{{orderInfo._add_time}}</span>
				</view>
			</view>
		</view>
		<view class="wrapper card mar" v-if="orderInfo">
			<view class="left">
				<view v-if="orderInfo.store">
					<view class="yuan">取</view>
					<view class="line2"></view>
				</view>
				<view v-else style="margin-top: 28rpx;"></view>
				<view class="yuan orange">送</view>
			</view>
			<view class="addres">
				<view v-if="orderInfo.store" class="box marlt martop">
					<view>
						<view class="text">{{orderInfo.store.name}}</view>
						<view class="acea-row row-between-wrapper" @click="showMaoLocation(system_store.latitude,system_store.longitude,system_store.name,system_store.detailed_address)">
							<span class="txt">{{ orderInfo.store.detailed_address }}</span>
							<span class="iconfont icon-xiangyou"></span>
						</view>
					</view>
					<view>
						<view class="iconfont  icon-tonghua marrt" @click="makePhone(system_store.phone)"></view>
					</view>
				</view>
				<view class="box  marlt">
					<view>
						<view class="text">{{orderInfo.nickname}}</view>
						<view class="acea-row row-between-wrapper" @click.stop="showMaoLocation(orderInfo.latitude,orderInfo.longitude,orderInfo.nickname,orderInfo.user_address)">
							<span class="txt">{{ orderInfo.user_address}}</span>
							<span class="iconfont icon-xiangyou"></span>
						</view>
					</view>
					<view>
						<view class="iconfont icon-tonghua marrt" @click.stop="makePhone(orderInfo.user_phone)"></view>
					</view>
				</view>
			</view>
			
		</view>
		<view class="card">
			<view class="pos-order-goods">
				<!-- <navigator :url="`/pages/goods_details/index?id=${item.productInfo.id}`" hover-class="none"
					class="goods acea-row row-between-wrapper" v-for="(item, index) in orderInfo.cartInfo" :key="index">
					<view class="picTxt acea-row row-between-wrapper">
						<view class="pictrue">
							<image :src="item.productInfo.image" />
						</view>
						<view class="text acea-row row-between row-column">
							<view class="info line2">
								{{ item.productInfo.store_name }}
							</view>
							<view class="attr">{{ item.productInfo.attrInfo.suk }}</view>
						</view>
					</view>
					<view class="money">
						<view class="x-money">￥{{ item.productInfo.price }}</view>
						<view class="num">x{{ item.cart_num }}</view>
						<view class="y-money">￥{{ item.productInfo.ot_price }}</view>
					</view>
				</navigator> -->
				<view class="goods acea-row row-between-wrapper" v-for="(item, index) in orderInfo.cartInfo" :key="index">
					<view class="picTxt acea-row row-between-wrapper">
						<view class="pictrue">
							<image :src="item.productInfo.image" />
						</view>
						<view class="text acea-row row-between row-column">
							<view class="info line2">
								{{ item.productInfo.store_name }}
							</view>
							<view class="attr">{{ item.productInfo.attrInfo.suk }}</view>
						</view>
					</view>
					<view class="money">
						<view class="x-money">￥{{ item.productInfo.attrInfo.price }}</view>
						<view class="num">x{{ item.cart_num }}</view>
						<view class="writeOff" v-if="orderInfo._status._type == 2">
							<text class="on" v-if="item.is_writeoff">已核销</text>
							<text v-if="!item.is_writeoff && item.surplus_num<item.cart_num">已核销{{parseInt(item.cart_num)-parseInt(item.surplus_num)}}件</text>
							<text v-if="!item.is_writeoff && item.surplus_num==item.cart_num">未核销</text>
						</view>
					</view>
				</view>
				<view class="giveGoods">
					<view class="item acea-row row-between-wrapper" v-for="(item,index) in giveCartInfo" :key="index">
						<view class="picTxt acea-row row-middle">
							<view class="pictrue">
								<image :src="item.productInfo.attrInfo.image" v-if="item.productInfo.attrInfo"></image>
								<image :src="item.productInfo.image" v-else></image>
							</view>
							<view class="texts">
								<view class="name line1">[赠品]{{item.productInfo.store_name}}</view>
								<view class="limit line1" v-if="item.productInfo.attrInfo">{{item.productInfo.attrInfo.suk}}</view>
							</view>
						</view>
						<view class="num">x{{item.cart_num}}</view>
					</view>
					<view class="item acea-row row-between-wrapper" v-for="(item,index) in giveData.give_coupon" :key="index" v-if="giveData.give_coupon.length">
						<view class="picTxt acea-row row-middle">
							<view class="pictrue acea-row row-center-wrapper">
								<text class="iconfont icon-pc-youhuiquan"></text>
							</view>
							<view class="texts">
								<view class="line1">[赠品]{{item.coupon_title}}</view>
							</view>
						</view>
					</view>
					<view class="item acea-row row-between-wrapper" v-if="giveData.give_integral>0">
						<view class="picTxt acea-row row-middle">
							<view class="pictrue acea-row row-center-wrapper">
								<text class="iconfont icon-pc-jifen"></text>
							</view>
							<view class="texts">
								<view class="line1">[赠品]{{giveData.give_integral}}积分</view>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="public-total">
				共{{ orderInfo.total_num }}件商品，应支付
				<span class="money">￥{{ sum }}</span>
			</view>
		</view>
		<view class="wrapper topnone card">
			<view class="title">
				<view class="font">订单信息</view>
			</view>
			<view class="item acea-row row-between">
				<view>订单编号：</view>
				<view class="conter acea-row row-middle row-right">
					{{ orderInfo.order_id
          }}
					<!-- #ifdef H5 -->
					<span class="copy copy-data" :data-clipboard-text="orderInfo.order_id">复制</span>
					<!-- #endif -->
					<!-- #ifdef MP -->
					<span class="copy copy-data" @click="copyNum(orderInfo.order_id)">复制</span>
					<!-- #endif -->
				</view>
			</view>
			<view class="item acea-row row-between">
				<view>下单时间：</view>
				<view class="conter">{{ orderInfo._add_time }}</view>
			</view>
			<view class="item acea-row row-between">
				<view>支付状态：</view>
				<view class="conter">
					{{ orderInfo.paid == 1 ? "已支付" : "未支付" }}
				</view>
			</view>
			<view class="item acea-row row-between">
				<view>支付方式：</view>
				<view class="conter">{{ payType }}</view>
			</view>
			<view class="item acea-row row-between">
				<view>买家留言：</view>
				<view class="conter">{{ orderInfo.mark }}</view>
			</view>
		</view>
		<customForm :customForm="orderInfo.custom_form"></customForm>
		<view class="wrapper topnone card">
			<view class="title">
				<view class="font">支付信息</view>
			</view>
			<view class="item acea-row row-between">
				<view>商品总价：</view>
				<!-- <view class="conter">￥{{ orderInfo.total_price }}</view> -->
				<view class="conter">￥{{ (parseFloat(orderInfo.total_price)+parseFloat(orderInfo.vip_true_price)).toFixed(2) }}</view>
			</view>
			<view class="item acea-row row-between" v-if="orderInfo.coupon_price>0">
				<view>优惠券抵扣：</view>
				<view class="conter">-￥{{ orderInfo.coupon_price }}</view>
			</view>
			<view v-if="orderInfo.pay_postage>0" class="item acea-row row-between">
				<view>运费：</view>
				<view class="conter">￥{{ orderInfo.pay_postage }}</view>
			</view>
			<view class="item acea-row row-between" v-if="orderInfo.deduction_price>0">
				<view>积分抵扣金额：</view>
				<view class="conter">-￥{{ orderInfo.deduction_price }}</view>
			</view>
			<view class="item acea-row row-between" v-if="orderInfo.vip_true_price>0">
				<view>会员商品优惠：</view>
				<view class="conter">-￥{{ orderInfo.vip_true_price }}</view>
			</view>
			<view class='item acea-row row-between' v-for="(item,index) in orderInfo.promotions_detail" :key="index" v-if="parseFloat(item.promotions_price)">
				<view>{{item.title}}：</view>
				<view class='conter'>-￥{{parseFloat(item.promotions_price).toFixed(2)}}</view>
			</view>
			<view class="actualPay acea-row row-right">
				实付款：<span class="money">￥{{ orderInfo.pay_price }}</span>
			</view>
			
		</view>
		<view style="height:120upx;"></view>
	</view>
</template>
<script>
	// #ifdef H5
	import ClipboardJS from "@/plugin/clipboard/clipboard.js";
	// #endif
	import customForm from "@/components/customForm";
	import {
		OrderDetail
		// getAdminOrderDetail,
		// setAdminOrderPrice,
		// setAdminOrderRemark,
		// setOfflinePay,
		// setOrderRefund
	} from "@/api/admin";
	// import { required, num } from "@utils/validate";
	// import { validatorDefaultCatch } from "@utils/dialog";
	import {
		isMoney
	} from '@/utils/validate.js'

	export default {
		name: "orderDetail",
		components: {
			customForm
		},
		props: {},
		data: function() {
			return {
				giveData:{
					give_integral:0,
					give_coupon:[]
				},
				giveCartInfo:[],
				order: false,
				change: false,
				order_id: "",
				orderInfo: '',
				status: "",
				title: "",
				payType: "",
				types: "",
				clickNum: 1,
				goname: '',
				system_store: '',
				sum: 0
			};
		},
		watch: {
			"$route.params.oid": function(newVal) {
				let that = this;
				if (newVal != undefined) {
					that.order_id = newVal;
					that.getIndex();
				}
			}
		},
		onLoad: function(option) {
			let self = this
			this.order_id = option.id;
			this.goname = option.goname
			this.getIndex();
			// #ifdef H5
			this.$nextTick(function() {
				var clipboard = new ClipboardJS('.copy-data');
				// var copybtn = document.getElementsByClassName("copy-data");
				// var clipboard = new Clipboard(copybtn);
				clipboard.on('success', function(e) {
					self.$util.Tips({
						title: '复制成功'
					})
				});
				clipboard.on('error', function(e) {
					self.$util.Tips({
						title: '复制失败'
					})
				});
			});
			// #endif

		},
		methods: {
			more: function() {
				this.order = !this.order;
			},
			modify: function(status) {
				this.change = true;
				this.status = status;
			},
			changeclose: function(msg) {
				this.change = msg;
			},
			getIndex: function() {
				let that = this;
				OrderDetail(that.order_id).then(
					res => {
						that.sum = (parseFloat(res.data.total_price)+parseFloat(res.data.vip_true_price)).toFixed(2)
						that.types = res.data._status._type;
						that.title = res.data._status._title;
						that.payType = res.data._status._payType;
						this.system_store = res.data.store
						that.giveData.give_coupon = res.data.give_coupon;
						that.giveData.give_integral = res.data.give_integral;
						let cartObj = [],giftObj = [];
						res.data.cartInfo.forEach((item, index) => {
							if(item.is_gift == 1){
								giftObj.push(item)
							}else{
								cartObj.push(item)
							}
						});
						res.data.cartInfo = cartObj;
						that.$set(that, 'giveCartInfo', giftObj);
						that.orderInfo = res.data;
					},
					err => {
						// that.$util.Tips({
						// 	title: err
						// }, {
						// 	tab: 3,
						// 	url: 1
						// });
					}
				);
			},
			//打开地图
			showMaoLocation: function(latitude,longitude,name,detailed_address) {
				if (!latitude || !longitude) return this.$util.Tips({
					title: '缺少经纬度信息无法查看地图！'
				});
				uni.openLocation({
					latitude: parseFloat(latitude),
					longitude: parseFloat(longitude),
					scale: 8,
					name: name,
					address:  detailed_address,
					success: function() {
			
					},
				});
			},
			//拨打电话
			makePhone: function(phone) {
				uni.makePhoneCall({
					phoneNumber: phone
				})
			},
			// #ifdef MP
			copyNum(id) {
				uni.setClipboardData({
					data: id,
					success: function() {}
				});
			},
			// #endif
			// #ifdef H5
			webCopy(item, index) {
				let items = item
				let indexs = index
				let self = this

				if (self.clickNum == 1) {
					self.clickNum += 1
					self.webCopy(items, indexs)
				}
			}
			// #endif
		}
	};
</script>

<style lang="scss">
	.giveGoods{
		.item{
			padding: 14rpx 0;
			border-top: 1px solid #eee;
			.picTxt{
				.pictrue{
					width: 76rpx;
					height: 76rpx;
					border-radius: 6rpx;
					background-color: #F5F5F5;
					color: #2a7efb;
					.iconfont{
						font-size: 34rpx;
					}
					image{
						width: 100%;
						height: 100%;
						border-radius: 6rpx;
					}
					margin-right: 16rpx;
				}
				.texts{
					width: 360rpx;
					color: #999999;
					font-size: 20rpx;
					.name{
						color: #333;
					}
					.limit{
						font-size: 20rpx;
						margin-top: 4rpx;
					}
				}
			}
			.num{
				color: #999999;
				font-size: 20rpx;
			}
		}
	}
	/*商户管理订单详情*/
	.pos-order-details .header {
		background: linear-gradient(to right, #2291f8 0%, #1cd1dc 100%);
		background: -webkit-linear-gradient(to right, #2291f8 0%, #1cd1dc 100%);
		background: -moz-linear-gradient(to right, #2291f8 0%, #1cd1dc 100%);
	}

	.pos-order-details .header .state {
		font-size: 36upx;
		color: #fff;
	}

	.pos-order-details .header .data {
		margin-left: 35upx;
		font-size: 28upx;
	}

	.pos-order-details .header .data .order-num {
		font-size: 30upx;
		margin-bottom: 8upx;
	}

	.pos-order-details .remarks {
		width: 100%;
		height: 86upx;
		background-color: #fff;
		padding: 0 30upx;
	}

	.pos-order-details .remarks .iconfont {
		font-size: 40upx;
		color: #2a7efb;
	}

	.pos-order-details .remarks input {
		width: 630upx;
		height: 100%;
		font-size: 30upx;
	}

	.pos-order-details .remarks input::placeholder {
		color: #666;
	}

	.pos-order-details .orderingUser {
		font-size: 26upx;
		color: #282828;
		padding: 0 30upx;
		height: 67upx;
		background-color: #fff;
		margin-top: 16upx;
		border-bottom: 1px solid #f5f5f5;
	}

	.pos-order-details .orderingUser .iconfont {
		font-size: 40upx;
		color: #2a7efb;
		margin-right: 15upx;
	}

	.pos-order-details .address {
		margin-top: 0;
	}

	.pos-order-details .pos-order-goods {
		/* margin-top: 17upx; */
	}

	.pos-order-details .footer .more {
		font-size: 27upx;
		color: #aaa;
		width: 100upx;
		height: 64upx;
		text-align: center;
		line-height: 64upx;
		margin-right: 25upx;
		position: relative;
	}

	.pos-order-details .footer .delivery {
		background: linear-gradient(to right, #2291f8 0%, #1cd1dc 100%);
		background: -webkit-linear-gradient(to right, #2291f8 0%, #1cd1dc 100%);
		background: -moz-linear-gradient(to right, #2291f8 0%, #1cd1dc 100%);
	}

	.pos-order-details .footer .more .order .arrow {
		width: 0;
		height: 0;
		border-left: 11upx solid transparent;
		border-right: 11upx solid transparent;
		border-top: 20upx solid #e5e5e5;
		position: absolute;
		left: 15upx;
		bottom: -18upx;
	}

	.pos-order-details .footer .more .order .arrow:before {
		content: '';
		width: 0;
		height: 0;
		border-left: 9upx solid transparent;
		border-right: 9upx solid transparent;
		border-top: 19upx solid #fff;
		position: absolute;
		left: -10upx;
		bottom: 0;
	}

	.pos-order-details .footer .more .order {
		width: 200upx;
		background-color: #fff;
		border: 1px solid #eee;
		border-radius: 10upx;
		position: absolute;
		top: -200upx;
		z-index: 9;
	}

	.pos-order-details .footer .more .order .item {
		height: 77upx;
		line-height: 77upx;
	}

	.pos-order-details .footer .more .order .item~.item {
		border-top: 1px solid #f5f5f5;
	}

	.pos-order-details .footer .more .moreName {
		width: 100%;
		height: 100%;
	}

	/*订单详情*/
	.order-details .header {
		padding: 0 30upx 36upx 30upx;
		height: 220upx;
	}

	.order-details .header.on {
		background-color: #666 !important;
	}

	.order-details .header .pictrue {
		width: 110upx;
		height: 110upx;
	}

	.order-details .header .pictrue image {
		width: 100%;
		height: 100%;
	}

	.order-details .header .data {
		color: rgba(255, 255, 255, 0.8);
		font-size: 24upx;
		margin-left: 27upx;
	}

	.order-details .header.on .data {
		margin-left: 0;
	}

	.order-details .header .data .state {
		font-size: 30upx;
		font-weight: bold;
		color: #fff;
		margin-bottom: 7upx;
	}

	/* .order-details .header .data .time{margin-left:20upx;} */
	.order-details .nav {
		background-color: #fff;
		font-size: 26upx;
		color: #282828;
		padding: 25upx 0;
	}

	.order-details .nav .navCon {
		padding: 0 40upx;
	}

	.order-details .nav .navCon .on {
		font-weight: bold;
		color: #e93323;
	}

	.order-details .nav .progress {
		padding: 0 65upx;
		margin-top: 10upx;
	}

	.order-details .nav .progress .line {
		width: 100upx;
		height: 2upx;
		background-color: #939390;
	}

	.order-details .nav .progress .iconfont {
		font-size: 25upx;
		color: #939390;
		margin-top: -2upx;
		width: 30upx;
		height: 30upx;
		line-height: 33upx;
		text-align: center;
		margin-right: 0 !important;
	}

	.order-details .address {
		font-size: 26upx;
		color: #868686;
		background-color: #fff;
		padding: 25upx 30upx 30upx 30upx;
	}

	.order-details .address .name {
		font-size: 30upx;
		color: #282828;
		margin-bottom: 0.1rem;
	}

	.order-details .address .name .phone {
		margin-left: 40upx;
	}

	.order-details .line {
		width: 100%;
		height: 3upx;
	}

	.order-details .line image {
		width: 100%;
		height: 100%;
		display: block;
	}

	.order-details .wrapper {
		background-color: #fff;
		margin-top: 12upx;
		padding: 30upx;
	}
	.order-details .topnone{padding-top: 0upx;}
	.order-details .wrapper .title{
		height: 100upx;
		display: flex;
		justify-content: space-between;
		align-items: center;
		border-bottom: 1upx solid #EEEEEE;
		margin-bottom: 34upx;
		
	}
	.order-details .wrapper .title .font{
		font-size: 32upx;
		font-weight: 600;
		color: #282828;
	}
	
	.order-details .wrapper .title .mapbtn{
		width: 176upx;
		height: 56upx;
		border: 1upx solid #1890FF;
		border-radius: 28upx;
		text-align: center;
		line-height: 50upx;
		color: #1890FF;
		font-size: 26upx;
	}
	
	.order-details .wrapper .item {
		font-size: 28upx;
		color: #282828;
	}

	.order-details .wrapper .item~.item {
		margin-top: 20upx;
	}

	.order-details .wrapper .item .conter {
		color: #868686;
		/* width: 500upx; */
		text-align: right;
	}

	.order-details .wrapper .item .conter .copy {
		font-size: 20rpx;
		color: #333;
		border-radius: 3rpx;
		border: 1px solid #666;
		padding: 0rpx 15rpx;
		margin-left: 10rpx;
		height: 40rpx;
		line-height: 38upx;
	}

	.order-details .wrapper .actualPay {
		border-top: 1upx solid #eee;
		margin-top: 30upx;
		padding-top: 30upx;
	}

	.order-details .wrapper .actualPay .money {
		font-weight: bold;
		font-size: 30upx;
		color: #e93323;
	}

	.order-details .footer {
		width: 100%;
		height: 100upx;
		position: fixed;
		bottom: 0;
		left: 0;
		background-color: #fff;
		padding: 0 30upx;
		border-top: 1px solid #eee;
	}

	.order-details .footer .bnt {
		width: auto;
		height: 60upx;
		line-height: 60upx;
		text-align: center;
		line-height: upx;
		border-radius: 50upx;
		color: #fff;
		font-size: 27upx;
		padding: 0 3%;
	}

	.order-details .footer .bnt.cancel {
		color: #aaa;
		border: 1px solid #ddd;
	}

	.order-details .footer .bnt.default {
		color: #444;
		border: 1px solid #444;
	}

	.order-details .footer .bnt~.bnt {
		margin-left: 18upx;
	}

	.pos-order-goods {
		padding: 30upx 30upx 0 30upx;
		background-color: #fff;
	}

	.pos-order-goods .goods {
		height: 185upx;
	}

	.pos-order-goods .goods~.goods {
		border-top: 1px dashed #e5e5e5;
	}

	.pos-order-goods .goods .picTxt {
		/* width: 515upx; */
	}

	.pos-order-goods .goods .picTxt .pictrue {
		width: 140upx;
		height: 140upx;
	}

	.pos-order-goods .goods .picTxt .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 6upx;
	}

	.pos-order-goods .goods .picTxt .text {
		width: 280upx;
		display: flex;
		justify-content: space-between;
		flex-direction: column;
		height: 130upx;
		margin-left: 20upx;
	}

	.pos-order-goods .goods .picTxt .text .info {
		font-size: 28upx;
		color: #282828;
	}

	.pos-order-goods .goods .picTxt .text .attr {
		font-size: 24upx;
		color: #999;
		width: 100%;
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
	}

	.pos-order-goods .goods .money {
		/* width: 164upx; */
		text-align: right;
		font-size: 28upx;
	}

	.pos-order-goods .goods .money .x-money {
		color: #282828;
	}

	.pos-order-goods .goods .money .num {
		margin: 5upx 0;
	}

	.pos-order-goods .goods .money .writeOff {
		color: #999;
		margin-top: 20upx;
		font-size: 24upx;
		color: #1890FF;
	}
	
	.pos-order-goods .goods .money .writeOff .on{
		color: #FF7E00;
	}
	
	.public-total {
		font-size: 28upx;
		color: #282828;
		border-top: 1px solid #eee;
		height: 92upx;
		line-height: 92upx;
		text-align: right;
		padding: 0 30upx;
		background-color: #fff;
	}

	.public-total .money {
		color: #ff4c3c;
	}

	.copy-data {
		font-size: 10px;
		color: #333;
		-webkit-border-radius: 1px;
		border-radius: 1px;
		border: 1px solid #666;
		padding: 0px 7px;
		margin-left: 12px;
		height: 20px;
	}
</style>
<style lang="scss" scoped>
	.card{
		margin: 20upx 28upx;
		border-radius: 12upx;
	}
	.mar{
		margin-top: -40upx !important;
		// height: 292upx;
		background: #FFFFFF;
		border-radius: 18upx;
		padding: 46upx 36upx !important;
		display: flex;
		justify-content: flex-start;
		.left{
			width: 42upx;
			.yuan{
				width: 42upx;
				height: 42upx;
				background: #EEEEEE;
				border-radius: 22upx;
				font-size: 22upx;
				text-align: center;
				line-height: 42upx;
				color: #000000;
			}
			.orange{background-color: #FFC641;}
			.line2{
				width: 2upx;
				height: 66upx;
				background: #D8D8D8;
				margin: 10upx;
				margin-left: 20upx;
			}
		}
		.addres{
			font-size: 28upx;
			.box{
				display: flex;
				align-items: center;
				justify-content: space-between;
			}
			.martop{margin-bottom: 50upx;}
			.marlt{margin-left: 34upx;}
			.marrt{margin-left: 24upx;margin-top: 22upx;font-size: 50upx;}
			.txt{
				color: #666666;
				font-size: 20upx;
				width: 440upx;
				display: inline-block;
				overflow: hidden;
				white-space: nowrap;
				text-overflow: ellipsis;
				word-break: break-all;
				margin-top: 6upx;
			}
			.text{
				color: #666666;
				width: 440upx;
				display: inline-block;
				overflow: hidden;
				white-space: nowrap;
				text-overflow: ellipsis;
				word-break: break-all;
			}
		}
	}
</style>
