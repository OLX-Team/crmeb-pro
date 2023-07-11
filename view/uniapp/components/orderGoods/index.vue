<template>
	<view class="orderGoods" :class="product_type?'on':''">
		<view class='total' v-if="!split && totalNmu>0">共{{totalNmu}}件商品</view>
		<view class='total' v-if="split">
			<text>订单包裹{{index + 1}}</text>
			<view class="rig-btn" v-if="status_type === 2">
				<view class="logistics sure" @click="confirmOrder(orderId)">确认收货</view>
				<view v-if="delivery_type === 'express'" class="logistics" @click="logistics(orderId)">查看物流</view>
			</view>
			<view class="rig-btn" v-else-if="status_type === -1">
				<view class="refund">申请退款中</view>
			</view>
			<view class="rig-btn" v-else-if="status_type === -2">
				<view class="refund">已退款</view>
			</view>
			<view class="rig-btn" v-else-if="status_type === 4">
				<view class="done">已完成</view>
			</view>
		</view>

		<view class='goodWrapper'>
			<view class='list' :class="{op:!item.is_valid}"
				v-for="(item,index) in cartInfo" :key="index" @click="jumpCon(item.product_id)">
				<view class="item acea-row row-between-wrapper">
					<view class='pictrue'>
						<image :src='item.productInfo.attrInfo.image' v-if="item.productInfo.attrInfo"></image>
						<image :src='item.productInfo.image' v-else></image>
					</view>
					<view class='text'>
						<view class='acea-row row-between-wrapper'>
							<view class='name line1'>{{item.productInfo.store_name}}</view>
							<view class='num'>x {{item.cart_num}}</view>
						</view>
						<view class='attr line1' v-if="item.productInfo.attrInfo">{{item.productInfo.attrInfo.suk}}</view>
						<view class='money font-color pic'>
							<text>
								￥{{item.productInfo.attrInfo?item.productInfo.attrInfo.price:item.productInfo.price}}
							</text>
							<text class="valid" v-if="!item.is_valid">{{shippingType==0?'不送达':'不支持自提'}}</text>
						</view>
						<view class="posBnt acea-row row-middle">
							<view class="evaluate writeOff" v-if="(statusType==5 || statusType==1 || statusType==2 || statusType==3) && (deliveryType==2 || sendType=='send')">
								<text class="on" v-if="item.is_writeoff">已核销</text>
								<text class="on" v-if="!item.is_writeoff && item.surplus_num<item.cart_num">已核销{{parseInt(item.cart_num)-parseInt(item.surplus_num)}}件</text>
								<text v-if="!item.is_writeoff && item.surplus_num==item.cart_num">未核销</text>
							</view>
							<text class="refund" v-if="item.refund_num && statusType !=-2">{{item.refund_num}}件退款中</text>
						</view>
					</view>
				</view>
				<view class="button acea-row row-right">
					<view class="bnt acea-row row-center-wrapper" v-if="refund_status === 0 && item.refund_num !=item.cart_num && paid && item.is_support_refund" @click.stop="openSubcribe(item,productType)">申请退款</view>
					<view class='bnt acea-row row-center-wrapper' v-if='evaluate==3 && item.is_reply==0 && pid != -1'
						@click.stop="evaluateTap(item.unique,orderId)">评价</view>
					<view class='bnt acea-row row-center-wrapper' v-else-if="evaluate==3 && item.is_reply==1">已评价</view>
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
			<view class="more-operation" v-if="split && refund_status === 0" @click="changeOperation">
				{{operationModel?'关闭':'更多操作'}}
			</view>
			<transition name="fade" mode="out-in" v-if="split && operationModel && refund_status === 0">
				<!-- #ifdef MP -->
				<view>
					<view class="more-operation b-top" @click="openSubcribe">
						申请退款
					</view>
				</view>
				<!-- #endif -->
				<!-- #ifndef MP -->
				<navigator hover-class="none" :url="'/pages/users/goods_return/index?orderId='+ orderId"
					class='more-operation b-top'>申请退款
				</navigator>
				<!-- #endif -->
			</transition>
		</view>
	</view>
</template>

<script>
	import {
		openOrderRefundSubscribe
	} from '@/utils/SubscribeMessage.js';
	export default {
		props: {
			productType:{
				type: Number,
				default: 0,
			},
			product_type:{
				type: Number,
				default: 0,
			},
			evaluate: {
				type: Number,
				default: 0,
			},
			paid: {
				type: Number,
				default: 0
			},
			// 订单状态
			statusType: {
				type: Number,
				default: 0,
			},
			// 配送方式
			deliveryType: {
				type: Number,
				default: 0,
			},
			// 送货方式
			sendType: {
				type: String,
				default: '',
			},
			cartInfo: {
				type: Array,
				default: function() {
					return [];
				}
			},
			giveData:{
				type:Object,
				default: function() {
					return [];
				}
			},
			giveCartInfo:{
				type: Array,
				default: function() {
					return [];
				}
			},
			orderId: {
				type: String,
				default: '',
			},
			delivery_type: {
				type: String,
				default: '',
			},
			shippingType: {
				type: Number,
				default: 0,
			},
			id:{
				type: Number,
				default: 0,
			},
			oid:{
				type: Number,
				default: 0,
			},
			jump: {
				type: Boolean,
				default: false,
			},
			split: {
				type: Boolean,
				default: false,
			},
			jumpDetail: {
				type: Boolean,
				default: false,
			},
			index: {
				type: Number,
				default: 0,
			},
			pid: {
				type: Number,
				default: 0,
			},
			refund_status: {
				type: Number,
				default: -1,
			},
			status_type: {
				type: Number,
				default: 0,
			}
		},
		data() {
			return {
				totalNmu: '',
				operationModel: false,
				status: ""
			};
		},
		watch: {
			cartInfo: function(nVal, oVal) {
				let num = 0
				nVal.forEach((item, index) => {
					num += item.cart_num
				})
				this.totalNmu = num
			}
		},
		methods: {
			evaluateTap: function(unique, orderId) {
				uni.navigateTo({
					url: "/pages/users/goods_comment_con/index?unique=" + unique + "&uni=" + orderId
				})
			},
			jumpCon: function(id) {
				if (this.jump) {
					uni.navigateTo({
						url: `/pages/goods_details/index?id=${id}`
					})
				} else if (this.jumpDetail) {
					uni.navigateTo({
						url: `/pages/users/order_details/index?order_id=${this.orderId}`
					})
				}
			},
			logistics(order_id) {
				uni.navigateTo({
					url: '/pages/users/goods_logistics/index?orderId=' + order_id
				})
			},
			confirmOrder(orderId) {
				this.$emit('confirmOrder', orderId)
			},
			changeOperation() {
				this.operationModel = !this.operationModel
			},
			openSubcribe: function(item,productType) {
				let cartIds = [
						{
							cart_id:item.id,
							cart_num:parseInt(item.cart_num) - parseInt(item.refund_num)
						}
				]
				cartIds = JSON.stringify(cartIds);
				let page = `/pages/users/goods_return/index?orderId=`+this.orderId+ '&id=' + this.oid+ '&cartIds='+ cartIds+'&productType='+this.productType;
				// #ifdef MP
				uni.showLoading({
					title: '正在加载',
				})
				openOrderRefundSubscribe().then(res => {
					uni.hideLoading();
					uni.navigateTo({
						url: page,
					});
				}).catch(() => {
					uni.hideLoading();
				});
				// #endif
				// #ifndef MP
				uni.navigateTo({
					url: page
				})
				// #endif
			}
		}
	}
</script>

<style scoped lang="scss">
	.giveGoods{
		.item{
			padding: 14rpx 30rpx 14rpx 0;
			margin-left: 30rpx;
			border-bottom: 1px solid #eee;
			.picTxt{
				.pictrue{
					width: 76rpx;
					height: 76rpx;
					border-radius: 6rpx;
					background-color: #F5F5F5;
					color: var(--view-theme);
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
	.goodWrapper .list .button{
		margin-bottom: 16rpx;
		.bnt{
			font-size: 24rpx;
			color: #666;
			width: 140rpx;
			height: 48rpx;
			border-radius: 23rpx;
			border: 1rpx solid #CCCCCC;
			margin-left: 20rpx;
		}
	}
	.goodWrapper .item .text .posBnt{
		position: absolute;
		right: 0;
		bottom: -5rpx
	}
	.goodWrapper .item .text .refund{
		font-size: 24rpx;
		color: #E93323;
		margin-left: 20rpx;
	}
	.goodWrapper .item .text .writeOff{
		border: 0;
		width: unset;
		font-size: 20rpx;
		color: #1890FF;
	}
	.goodWrapper .item .text .writeOff .on{
		color: #999;
	}
	.fontcolor {
		color: var(--view-theme);
	}

	.orderGoods {
		background-color: #fff;
		margin-top: 12rpx;
		&.on{
			margin-top: 0;
		}
	}

	.orderGoods .total {
		display: flex;
		justify-content: space-between;
		align-items: center;
		width: 100%;
		// height: 86rpx;
		padding: 0 30rpx;
		border-bottom: 2rpx solid #f0f0f0;
		font-size: 30rpx;
		color: #282828;
		line-height: 86rpx;
		box-sizing: border-box;

		.rig-btn {
			display: flex;
			align-items: center;

			.refund {
				font-size: 26rpx;
				color: var(--view-theme);
			}

			.done {
				font-size: 26rpx;
				color: #F19D2F;
			}
		}

		.logistics {
			// height: 46rpx;
			line-height: 30rpx;
			color: #999999;
			font-size: 20rpx;
			border: 1px solid;
			border-radius: 30rpx;
			padding: 6rpx 12rpx;
			margin-left: 10rpx;
		}

		.sure {
			color: var(--view-theme);
			border: 1px solid var(--view-theme);
		}
	}

	.more-operation {
		display: flex;
		justify-content: center;
		align-items: center;
		padding: 10rpx 0;
		color: #bbb;
	}

	.b-top {
		margin-left: 30rpx;
		margin-right: 30rpx;
		border-top: 1px solid #f0f0f0
	}

	.fade-enter-active,
	.fade-leave-active {
		transition: all 0.1s;
	}

	.fade-enter,
	.fade-leave-to

	/* .fade-leave-active below version 2.1.8 */
		{
		opacity: 0;
		transform: translateY(-10px);
	}

	.op {
		opacity: 0.5;
	}

	.pic {
		display: flex;
		justify-content: space-between;
	}

	.valid {
		font-size: 24rpx;
	}
</style>
