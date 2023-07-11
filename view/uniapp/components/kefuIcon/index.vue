<template>
	<!-- #ifdef APP-PLUS || H5 -->
	<navigator :url="'/pages/customer_list/chat?productId='+ids" hover-class="none" class="acea-row row-center-wrapper cartf iconfont icon-kefu3" :style="{ top: top + 'px'}" @touchmove.stop.prevent="setTouchMove" v-if="customerInfo.customer_type==0"></navigator>
	<view class="acea-row row-center-wrapper cartf iconfont icon-kefu3" :style="{ top: top + 'px'}" @touchmove.stop.prevent="setTouchMove" @click="call" v-else-if="customerInfo.customer_type==1"></view>
	<view class="acea-row row-center-wrapper cartf iconfont icon-kefu3" :style="{ top: top + 'px'}" @touchmove.stop.prevent="setTouchMove" @click="licks" v-else-if="customerInfo.customer_type==2"></view>
	<!-- #endif -->
	<!-- #ifdef MP -->
	<view v-if="routineContact == 0">
		<navigator :url="'/pages/customer_list/chat?productId='+ids" hover-class="none" class="acea-row row-center-wrapper cartf iconfont icon-kefu3" :style="{ top: top + 'px'}" @touchmove.stop.prevent="setTouchMove" v-if="customerInfo.customer_type==0"></navigator>
		<view class="acea-row row-center-wrapper cartf iconfont icon-kefu3" :style="{ top: top + 'px'}" @touchmove.stop.prevent="setTouchMove" @click="call" v-else-if="customerInfo.customer_type==1"></view>
		<view class="acea-row row-center-wrapper cartf iconfont icon-kefu3" :style="{ top: top + 'px'}" @touchmove.stop.prevent="setTouchMove" @click="licks" v-else-if="customerInfo.customer_type==2"></view>
	</view>
	<button class="acea-row row-center-wrapper cartf iconfont icon-kefu3" open-type='contact' :style="{ top: top + 'px'}" @touchmove.stop.prevent="setTouchMove" v-else-if="routineContact==1 && !goodsCon"></button>
	<button class="acea-row row-center-wrapper cartf iconfont icon-kefu3" open-type='contact' :send-message-title="storeInfo.store_name" :send-message-img="storeInfo.image" :send-message-path="`/pages/goods_details/index?id=${storeInfo.id}`" show-message-card :style="{ top: top + 'px'}" @touchmove.stop.prevent="setTouchMove" v-else-if="routineContact==1 && goodsCon"></button>
	<!-- #endif -->
</template>

<script>
	let app = getApp();
	import {
		mapGetters
	} from "vuex";
	import {
		customerType
	} from '@/api/api.js';
	export default {
		name: "kefuIcon", 
		props: {
			ids: {
				type: Number,
				default: 0
			},
			routineContact: {
				type: Number,
				default: 0
			},
			storeInfo: {
				type: Object,
				default () {
					return {};
				}
			},
			goodsCon: {
				type: Number,
				default: 0
			}
		},
		computed: mapGetters(['userInfo']),
		data: function() {
			return {
				top: "480",
				customerInfo:{}
			};
		},
		mounted() {
			// #ifdef H5
			this.top =  parseFloat(window.innerHeight) -200
			// #endif
			this.getCustomerType();
		},
		methods: {
			setTouchMove(e) {
				let that = this;
				if (e.touches[0].clientY < 480 && e.touches[0].clientY > 66) {
					that.top = e.touches[0].clientY
				}
			},
			licks(){
				let userInfo = {}
				if(typeof this.userInfo === 'string'){
					userInfo = JSON.parse(this.userInfo)
				}else{
					userInfo = this.userInfo
				}
				let href = this.customerInfo.customer_url;	
				let hrefO = href + '?uid='+userInfo.uid+'&nickName='+userInfo.nickname+'&phone='+userInfo.phone+'&sex='+userInfo.sex+'&avatar='+userInfo.avatar+'&openid='+userInfo.openid;
				let hrefT = href + '&uid='+userInfo.uid+'&nickName='+userInfo.nickname+'&phone='+userInfo.phone+'&sex='+userInfo.sex+'&avatar='+userInfo.avatar+'&openid='+userInfo.openid;
				let url = encodeURIComponent(href.indexOf('?') === -1?hrefO:hrefT);
				uni.navigateTo({
					url: `/pages/annex/web_view/index?url=${url}`
				});
			},
			call() {
				uni.makePhoneCall({
					phoneNumber: this.customerInfo.customer_phone,
				});
			},
			getCustomerType(){
				customerType().then(res=>{
					this.customerInfo = res.data;
				}).catch(err=>{
					this.$util.Tips({
						title: err
					});
				})
			}
		},
		created() {
		}
	};
</script>

<style lang="scss">
	.cartf{
		width: 96rpx;
		height: 96rpx;
		background: #FFFFFF;
		box-shadow: 0 3rpx 16rpx rgba(0, 0, 0, 0.08);
		border-radius: 50%;
		font-size: 47rpx;
		color: #666;
		position: fixed;
		right: 15rpx;
		z-index: 9;
	}
</style>
