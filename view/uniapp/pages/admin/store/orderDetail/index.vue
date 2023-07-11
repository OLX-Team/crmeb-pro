<template>
	<view class="order-details pos-order-details">
		<view class="header acea-row row-middle">
			<view class="state">{{ title }}</view>
			<view class="data">
				<view class="order-num">订单号：{{ orderInfo.order_id }}</view>
				<view>
					<span class="time">下单时间：{{ orderInfo._add_time }}</span>
				</view>
			</view>
		</view>
		<view class="remarks acea-row" v-if="goname != 'looks'">
			<span class="iconfont icon-zhinengkefu-"></span>
			<div class="tip on" v-if="orderInfo.remark" @click="modify('1')">{{orderInfo.remark}}</div>
			<div class="tip" v-else @click="modify('1')">订单未备注，点击添加备注信息</div>
		<!-- 	<input class="line1" style="text-align: left;" :value="
          orderInfo.remark ? orderInfo.remark : '订单未备注，点击添加备注信息'
        " disabled @click="modify('1')" /> -->
		</view>
		<view class="orderingUser acea-row row-middle">
			<span class="iconfont icon-yonghu2"></span>{{ orderInfo.uid?orderInfo.nickname:'游客' }}
		</view>
		<view class="address" v-if="orderInfo.real_name && orderInfo.user_phone && orderInfo.user_address">
			<view class="name">
				{{ orderInfo.real_name }}
				<span class="phone">{{ orderInfo.user_phone }}</span>
				<!-- #ifdef H5 -->
				<span class="copy copy-data"
					:data-clipboard-text="`${orderInfo.real_name} ${orderInfo.user_phone} ${orderInfo.user_address}`">复制</span>
				<!-- #endif -->
				<!-- #ifdef MP -->
				<span class="copy copy-data"
					@click="copyNum(`${orderInfo.real_name} ${orderInfo.user_phone} ${orderInfo.user_address}`)">复制</span>
				<!-- #endif -->
			</view>
			<view>{{ orderInfo.user_address }}</view>
		</view>
		<view class="line">
			<image src="/static/images/line.jpg" />
		</view>
		<!-- 拆单时 -->
		<view v-for="(j, indexw) in orderInfo.split" v-if="orderInfo.split && orderInfo.split.length">
			<view class="splitTitle acea-row row-between-wrapper">
				<view>订单包裹{{indexw + 1}}</view>
				<view class="title">{{j._status._title}}</view>
			</view>
			<view class="pos-order-goods">
				<navigator :url="`/pages/admin/orderDetail/index?id=${j.order_id}`" hover-class="none"
					class="goods acea-row row-between-wrapper" v-for="(item, index) in j.cartInfo" :key="index">
					<view class="picTxt acea-row row-between-wrapper">
						<view class="pictrue">
							<image :src="item.productInfo.attrInfo?item.productInfo.attrInfo.image:item.productInfo.image" />
						</view>
						<view class="text acea-row row-between row-column">
							<view class="info line2">
								{{ item.productInfo.store_name }}
							</view>
							<view class="attr">{{ item.productInfo.attrInfo.suk }}</view>
						</view>
					</view>
					<view class="money">
						<view class="x-money">￥{{ item.productInfo.attrInfo?item.productInfo.attrInfo.price:item.productInfo.price }}</view>
						<view class="num">x{{ item.cart_num }}</view>
						<!-- <view class="y-money">￥{{ item.productInfo.ot_price }}</view> -->
					</view>
				</navigator>
			</view>
		</view>
		<!-- 结束 -->
		<!-- 未拆单时，正常单 -->
		<view class="pos-order-goods split" v-if="orderInfo.cartInfo && orderInfo.cartInfo.length">
			<view class="title acea-row row-between-wrapper" v-if="orderInfo.status == 4">
				<text>共{{totalNmu}}件商品</text>
			<!-- 	<navigator class="bnt" :url="'/pages/admin/store/deliverGoods/index?id='+orderInfo.order_id+'&listId='+orderInfo.id+'&totalNum='+orderInfo.total_num+'&orderStatus='+orderInfo.status+'&comeType=2'">去发货</navigator> -->
			</view>
			<view class="goods acea-row row-between-wrapper" v-for="(item, index) in orderInfo.cartInfo" :key="index">
				<view class="picTxt acea-row row-between-wrapper">
					<view class="pictrue">
						<image :src="item.productInfo.attrInfo?item.productInfo.attrInfo.image:item.productInfo.image" />
					</view>
					<view class="text acea-row row-between row-column">
						<view class="info line2">
							{{ item.productInfo.store_name }}
						</view>
						<view class="attr">{{ item.productInfo.attrInfo.suk }}</view>
					</view>
				</view>
				<view class="money">
					<view class="x-money">￥{{ item.productInfo.attrInfo?item.productInfo.attrInfo.price:item.productInfo.price }}</view>
					<view class="num">x {{ item.cart_num }}</view>
					<view class="acea-row row-right">
						<view class="writeOff" v-if="item.refund_num && orderInfo.refund_type != 6">{{item.refund_num}}件退款中</view>
						<view class="writeOff" v-if="(orderInfo._status._type==1 || orderInfo._status._type==5) && orderInfo.shipping_type == 2">
							<text v-if="item.refund_num">，</text>
							<text class="on" v-if="item.is_writeoff">已核销</text>
							<text v-if="!item.is_writeoff && item.surplus_num<item.cart_num">核销{{parseInt(item.cart_num)-parseInt(item.surplus_num)}}件</text>
							<text v-if="!item.is_writeoff && item.surplus_num==item.cart_num">未核销</text>
						</view>
					</view>
					<!-- <view class="y-money">￥{{ item.productInfo.ot_price }}</view> -->
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
		<view class="public-total" v-if="!orderInfo.split || !orderInfo.split.length">
			共{{ orderInfo.total_num }}件商品，商品总价
			<span class="money">￥{{ sum }}</span> ( 邮费 ¥{{
        orderInfo.pay_postage
      }}
			)
		</view>
		<!-- 结束 -->
		<!-- <view v-if="types == 1" class="wrapper topnone">
			<view class="title">
				<view class="font">配送员信息</view>
				<view class="mapbtn"><span class="iconfont icon-weizhi"></span>查看地址</view>
			</view>
			<view class="item acea-row row-between">
				<view>联系人：</view>
				<view class="conter">啊哈啊</view>
			</view>
			<view class="item acea-row row-between">
				<view>联系电话：</view>
				<view class="conter">13007466338</view>
			</view>
		</view> -->
		<customForm :customForm="orderInfo.custom_form"></customForm>
		<view class="wrapper">
			<!-- <view class="title">
				<view class="font">订单信息</view>
			</view> -->
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
			<view class="item acea-row row-between" v-if="orderInfo.mark">
				<view v-if="statusType == -3">退款留言：</view>
				<view v-else>买家留言：</view>
				<view class="conter">{{ orderInfo.mark }}</view>
			</view>
			<view class="item acea-row row-between" v-if="orderInfo.refund_goods_explain">
				<view>退货留言：</view>
				<view class='conter'>{{orderInfo.refund_goods_explain}}</view>
			</view>
			<view class="item acea-row row-between" v-if="orderInfo.refund_img && orderInfo.refund_img.length">
				<view>退款凭证：</view>
				<view class="conter">
					<view class="pictrue" v-for="(item,index) in orderInfo.refund_img">
					   <image :src="item" mode="aspectFill" @click='getpreviewImage(index,1)'></image>
					</view>
				</view>
			</view>
			<view class="item acea-row row-between" v-if="orderInfo.refund_goods_img && orderInfo.refund_goods_img.length">
				<view>退货凭证：</view>
				<view class="conter">
					<view class="pictrue" v-for="(item,index) in orderInfo.refund_goods_img">
					   <image :src="item" mode="aspectFill" @click='getpreviewImage(index,0)'></image>
					</view>
				</view>
			</view>
		</view>
		<view class="wrapper">
			<view class="item acea-row row-between">
				<view>商品总价：</view>
				<view class="conter">￥{{(parseFloat(orderInfo.total_price)+parseFloat(orderInfo.vip_true_price)).toFixed(2)}}</view>
			</view>
			<view v-if="orderInfo.coupon_price>0" class="item acea-row row-between">
				<view>优惠券抵扣：</view>
				<view class="conter">-￥{{ orderInfo.coupon_price }}</view>
			</view>
			<view v-if="orderInfo.pay_postage>0" class="item acea-row row-between">
				<view>运费：</view>
				<view class="conter">￥{{ orderInfo.pay_postage }}</view>
			</view>
			<view v-if="orderInfo.deduction_price>0" class="item acea-row row-between">
				<view>积分抵扣金额：</view>
				<view class="conter">-￥{{ orderInfo.deduction_price }}</view>
			</view>
			<view v-if="orderInfo.vip_true_price>0" class="item acea-row row-between">
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
		<view class="wrapper" v-if="
		    orderInfo.delivery_type != 'fictitious' && orderInfo._status._type === 2 && (!orderInfo.split || !orderInfo.split.length)
		  ">
			<view class="item acea-row row-between">
				<view>配送方式：</view>
				<view class="conter" v-if="orderInfo.delivery_type === 'express'">
					快递
				</view>
				<view class="conter" v-if="orderInfo.delivery_type === 'send'">送货</view>
			</view>
			<view class="item acea-row row-between">
				<view v-if="orderInfo.delivery_type === 'express'">快递公司：</view>
				<view v-if="orderInfo.delivery_type === 'send'">送货人：</view>
				<view class="conter">{{ orderInfo.delivery_name }}</view>
			</view>
			<view class="item acea-row row-between">
				<view v-if="orderInfo.delivery_type === 'express'">快递单号：</view>
				<view v-if="orderInfo.delivery_type === 'send'">送货人电话：</view>
				<view class="conter">
					{{ orderInfo.delivery_id}}
					<!-- #ifdef H5 -->
					<span class="copy copy-data" :data-clipboard-text="orderInfo.delivery_id">复制</span>
					<!-- #endif -->
					<!-- #ifdef MP -->
					<span class="copy copy-data" @click="copyNum(orderInfo.delivery_id)">复制</span>
					<!-- #endif -->
				</view>
			</view>
		</view>
		<view style="height:120upx;"></view>
		<view class="footer acea-row row-right row-middle">
			<view v-if="(types == 0 || types == 9) && orderInfo.is_del == 0 && !openErp" class="more btn cancel" @click="more">
				更多
				<view class="more-box" v-if="moreBtn">
					<view class="more-btn" @click="del">删除订单</view>
					<view class="more-btn" @click="cancel">取消订单</view>
				</view>	
			</view>
			
			<view class="bnt cancel" :class="openErp?'on':''" @click="modify('0')" v-if="types == 0 || types == 9">
				一键改价
			</view>
			<!-- <view class="bnt cancel" @click="modify('0')" v-if="types == -1">
				立即退款
			</view> -->
			<view class="bnt cancel" :class="openErp?'on':''" @click="modify('2',1)" v-if="(!orderInfo.refund || !orderInfo.refund.length) && (orderInfo.refund_type == 0 || orderInfo.refund_type == 1 || orderInfo.refund_type == 5) && orderInfo.paid && parseFloat(orderInfo.pay_price) >= 0">
				立即退款
			</view>
			<view class="bnt cancel" :class="openErp?'on':''" @click="modify('2',0)" v-if="orderInfo.refund_type == 2">
				同意退货
			</view>
			<view class="wait" :class="openErp?'on':''" v-if="orderInfo.refund_type == 4">待用户发货</view>
			<view class="bnt cancel" @click="modify('1')">订单备注</view>
			<view v-if="orderInfo.paid === 0 && types == 9"  class="bnt delivery" :class="openErp?'on':''" @click="offlinePay">
				确认付款
			</view>
			<view class="bnt delivery" :class="openErp?'on':''" v-if="types == 1 && (orderInfo.shipping_type === 1 || orderInfo.shipping_type === 3) && (orderInfo.pinkStatus === null || orderInfo.pinkStatus === 2)" @click="goDelivery(orderInfo)">去发货</view>
		</view>
		<PriceChange :change="change" :orderInfo="orderInfo" :isRefund="isRefund" v-on:closechange="changeclose($event)"
			v-on:savePrice="savePrice" :status="status"></PriceChange>
	</view>
</template>
<script>
	import PriceChange from "@/components/PriceChange";
	import customForm from "@/components/customForm";
	// #ifdef H5
	import ClipboardJS from "@/plugin/clipboard/clipboard.js";
	// #endif
	import {
		OrderDetail,
		refundDetail,
		getOrderreMarkApi,
		getRefundMarkApi,
		getOrderPriceApi,
		getOrderOfflineApi,
		OrderDel,
		OrderCancel,
		setOrderRefund,
		storeRefundAgree
	} from "@/api/admin";
	import { erpConfig } from "@/api/esp.js";
	// import { required, num } from "@utils/validate";
	// import { validatorDefaultCatch } from "@utils/dialog";
	import {
		isMoney
	} from '@/utils/validate.js'

	export default {
		name: "orderDetail",
		components: {
			PriceChange,
			customForm
		},
		props: {},
		data: function() {
			return {
				openErp:false,
				giveData:{
					give_integral:0,
					give_coupon:[]
				},
				giveCartInfo:[],
				totalNmu:0,
				order: false,
				change: false,
				order_id: "",
				orderInfo: {
					_status: {}
				},
				status: "",
				title: "",
				payType: "",
				types: "",
				statusType:'',
				clickNum: 1,
				goname: '',
				moreBtn: false,
				sum: 0,
				isRefund:0, //1是仅退款;0是同意退货退款
			};
		},
		onLoad: function(option) {
			let self = this
			this.order_id = option.id;
			this.goname = option.goname
			this.statusType = option.types
			this.getErpConfig();
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
			goDelivery(orderInfo){
				if(this.openErp) return
				uni.navigateTo({
					url: '/pages/admin/store/deliverGoods/index?id='+orderInfo.order_id+'&listId='+orderInfo.id+'&totalNum='+orderInfo.total_num+'&orderStatus='+orderInfo.status+'&comeType=2&productType='+orderInfo.product_type
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
			getpreviewImage: function(index,num) {
				uni.previewImage({
					urls: num?this.orderInfo.refund_img:this.orderInfo.refund_goods_img,
					current: num?this.orderInfo.refund_img[index]:this.orderInfo.refund_goods_img[index]
				});
			},
			more: function() {
				// this.order = !this.order;
				this.moreBtn = !this.moreBtn
			},
			del :function() {
				OrderDel(this.order_id).then(res=>{
					this.$util.Tips({
						title: res.msg,
						icon: 'success'
					})
				}).catch(err=>{
					this.$util.Tips({
						title: err,
						icon: 'error'
					})
				})
			},
			cancel: function() {
				console.log(this.order_id)
				OrderCancel(this.order_id).then(res=>{
					this.$util.Tips({
						title: res.msg,
						icon: 'success'
					})
				}).catch(err=>{
					this.$util.Tips({
						title: err,
						icon: 'error'
					})
				})
			},
			modify: function(status,type) {
				if(this.openErp && status !=1) return
				this.change = true;
				this.status = status;
				if(status==2){
					this.isRefund = type
				}
			},
			changeclose: function(msg) {
				this.change = msg;
			},
			getIndex: function() {
				let that = this;
				let obj = '';
				if(that.statusType == -3){
					obj = refundDetail(that.order_id)
				}else{
					obj = OrderDetail(that.order_id);
				}
				obj.then(
					res => {
						let num = 0;
						that.sum = res.data.pay_price;
						that.types = res.data._status._type;
						that.title = res.data._status._title;
						that.payType = res.data._status._payType;
						that.giveData.give_coupon = res.data.give_coupon;
						that.giveData.give_integral = res.data.give_integral;
						let cartObj = [],giftObj = [];
						res.data.cartInfo.forEach((item, index) => {
							num += item.cart_num
							if(item.is_gift == 1){
								giftObj.push(item)
							}else{
								cartObj.push(item)
							}
						});
						this.totalNmu = num;
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
			objOrderRefund(data){
				let that = this;
				setOrderRefund(data).then(
					res => {
						that.change = false;
						that.$util.Tips({
							title: res.msg
						});
						that.getIndex();
					},
					err => {
						that.change = false;
						that.$util.Tips({
							title: err
						});
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
					if (!isMoney(price)) {
						return that.$util.Tips({
							title: '请输入正确的金额'
						});
					}
					data.price = price;
					getOrderPriceApi(data).then(
						function() {
							that.change = false;
							that.$util.Tips({
								title: '改价成功',
								icon: 'success'
							})
							that.getIndex();
						},
						function() {
							that.change = false;
							that.$util.Tips({
								title: '改价失败',
								icon: 'none'
							})
						}
					);
				} else if (that.status == 2) {
					if(this.isRefund){
						if (!isMoney(refund_price)) {
							return that.$util.Tips({
								title: '请输入正确的金额'
							});
						}
						data.price = refund_price;
						data.type = opt.type;
						this.objOrderRefund(data);
					}else{
						if(opt.type==1){
							storeRefundAgree(this.orderInfo.id).then(res=>{
								that.change = false;
								that.$util.Tips({
									title: res.msg
								});
								that.getIndex();
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
					if (!remark) {
						return this.$util.Tips({
							title: '请输入备注'
						})
					}
					data.remark = remark;
					let obj = '';
					if(that.statusType == -3){
						obj = getRefundMarkApi(data);
					}else{
						obj = getOrderreMarkApi(data);
					}
					obj.then(
						res => {
							that.change = false;
							this.$util.Tips({
								title: res.msg,
								icon: 'success'
							})
							that.getIndex();
						},
						err => {
							that.change = false;
							that.$util.Tips({
								title: err
							});
						}
					);
				}
			},
			offlinePay: function() {
				if(this.openErp) return
				getOrderOfflineApi({
					order_id: this.orderInfo.order_id
				}).then(
					res => {
						this.$util.Tips({
							title: res.msg,
							icon: 'success'
						});
						this.getIndex();
					},
					err => {
						this.$util.Tips({
							title: err
						});
					}
				);
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

<style lang="scss" scoped>
	.giveGoods{
		.item{
			padding: 14rpx 30rpx 14rpx 0;
			margin-left: 30rpx;
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
	.splitTitle{
		width: 100%;
		height: 80rpx;
		line-height: 80rpx;
		background-color: #fff;
		margin-top: 17rpx;
		border-bottom: 1px solid #e5e5e5;
		padding: 0 30rpx;
	}
	.splitTitle .title{
		color: #2291f8;
	}
	/*商户管理订单详情*/
	.pos-order-details .header {
    background: linear-gradient(270deg, #1cd1dc 0%, #2291f8 100%);
	}

	.pos-order-details .header .state {
		font-size: 26upx;
		color: #fff;
	}

	.pos-order-details .header .data {
		margin-left: 35upx;
		font-size: 28upx;
	}

	.pos-order-details .header .data .order-num {
		font-size: 22upx;
		margin-bottom: 8upx;
	}

	.pos-order-details .remarks {
		width: 100%;
		background-color: #fff;
		padding: 20upx 30upx;
	}
	
	.pos-order-details .remarks .tip{
		font-size: 30upx;
		color: #CCCCCC;
		margin-left: 20upx;
		width: 620upx;
	}
	
	.pos-order-details .remarks .tip.on{
		color: #333;
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

	// .pos-order-details .pos-order-goods {
	// 	margin-top: 17upx;
	// }

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
		padding: 0 30upx;
		height: 150upx;
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
		width: 460rpx;
		display: flex;
		flex-wrap: nowrap;
		justify-content: flex-end;
		text-align: right;
		.pictrue{
			width: 80rpx;
			height: 80rpx;
			margin-left: 6rpx;
			image{
				width: 100%;
				height: 100%;
				border-radius: 6rpx;
			}
		}
	}

	.order-details .wrapper .item .conter .copy {
		font-size: 20rpx;
		color: #333;
		border-radius: 3rpx;
		border: 1px solid #666;
		padding: 0rpx 15rpx;
		margin-left: 24rpx;
		// height: 40rpx;
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
	
	.order-details .footer .wait{
		color: #2a7efb;
		margin-right: 30rpx;
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
		&.on{
			color: #c5c8ce!important;
			background: #f7f7f7!important;
			border: 1px solid #dcdee2!important;
		}
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
		padding: 0 30upx;
		background-color: #fff;
	}
	
	.pos-order-goods.split{
		margin-top: 15upx;
		padding: 0;
	}
	
	.pos-order-goods .title{
		height: 80upx;
		border-bottom: 1px solid #e5e5e5;
		padding: 0 30upx;
	}
	
	.pos-order-goods .bnt{
		padding: 7upx 20upx;
		border: 1px solid #2a7efb;
		color: #2a7efb;
		border-radius: 30upx;
	}

	.pos-order-goods .goods {
		height: 185upx;
	}
	
	.pos-order-goods.split .goods{
		padding: 0 30upx;
	}

	.pos-order-goods .goods~.goods {
		border-top: 1px dashed #e5e5e5;
	}

	.pos-order-goods .goods .picTxt {
		width: 430upx;
	}

	.pos-order-goods .goods .picTxt .pictrue {
		width: 130upx;
		height: 130upx;
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
	}

	.pos-order-goods .goods .picTxt .text .info {
		font-size: 28upx;
		color: #282828;
	}
	
	.pos-order-goods .goods .picTxt .text .info .label{
		color: #ff4c3c;
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
		width: 248upx;
		height: 130upx;
		text-align: right;
		font-size: 28upx;
	}
	
	.pos-order-goods .goods .money .writeOff{
		font-size: 24upx;
		margin-top: 17upx;
		color: #1890FF;
	}
	
	.pos-order-goods .goods .money .writeOff .on{
		color: #FF7E00;
	}

	.pos-order-goods .goods .money .x-money {
		color: #282828;
	}

	.pos-order-goods .goods .money .num {
		color: #ff9600;
		margin: 5upx 0;
	}

	.pos-order-goods .goods .money .y-money {
		color: #999;
		text-decoration: line-through;
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
		// height: 20px;
	}
	.more-box {
		color: #333;
		position: absolute;
		width: 180rpx;
		left: 0rpx;
		bottom: 110rpx;
		background-color: #fff;
		padding: 10rpx;
		border-radius: 4rpx;
		font-size: 24rpx;
		-webkit-box-shadow: 0px 0px 3px 0px rgba(200, 200, 200, 0.75);
		-moz-box-shadow: 0px 0px 3px 0px rgba(200, 200, 200, 0.75);
		box-shadow: 0px 0px 3px 0px rgba(200, 200, 200, 0.75);
	
		.more-btn {
			color: #aaa;
			padding: 4rpx;
			z-index: 9999;
			border-bottom: 2rpx solid #f7f1f1 !important;
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
</style>
