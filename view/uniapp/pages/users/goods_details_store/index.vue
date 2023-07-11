<template>
	<view :style="colorStyle">
		<view class="list">
			<view class="item" v-for="(item, index) in storeList" :key="index" @click.stop="checked(item)">
				<view class="name line1">{{ item.name }}</view>
				<view class="address acea-row row-top row-between">
					<view class="iconfont icon-dingwei1"></view>
					<view class="right">
						<text v-if="item.range">距离您{{ item.range }}km</text>
						<text v-else>查看地图</text>
						<text class="line"></text>{{ item.address }}{{ ", " + item.detailed_address }}</view>
				</view>
				<view class="time address acea-row row-top row-between">
					<view class="iconfont icon-yingyeshijian"></view>
					<view class="right">营业时间：{{item.day_time}}</view>
				</view>
				<view class="bottom acea-row row-center-wrapper">
					<view class="bnt" @click.stop="showMaoLocation(item)"><text class="iconfont icon-daohangdaodian"></text>导航到店</view>
					<!-- #ifdef H5 -->
					<a class="bnt" :href="'tel:' + item.phone"><text class="iconfont icon-zhidianzixun"></text>致电咨询</a>
					<!-- #endif -->
					<!-- #ifdef MP || APP-PLUS -->
					<view class="bnt" @click.stop="call(item.phone)"><text class="iconfont icon-zhidianzixun"></text>致电咨询</view>
					<!-- #endif -->
				</view>
			</view>
		</view>
		<Loading :loaded="loaded" :loading="loading"></Loading>
	</view>
</template>

<script>
	import Loading from "@/components/Loading";
	import {
		storeListApi
	} from "@/api/store";
	import {
		isWeixin
	} from "@/utils/index";
	// #ifdef H5
	import {
		wechatEvevt,
		wxShowLocation
	} from "@/libs/wechat";
	// #endif
	import colors from "@/mixins/color";
	import {
		mapGetters
	} from "vuex";
	const LONGITUDE = "user_longitude";
	const LATITUDE = "user_latitude";
	const MAPKEY = "mapKey";
	export default {
		name: "storeList",
		components: {
			Loading
		},
		mixins: [colors],
		data() {
			return {
				page: 1,
				limit: 20,
				loaded: false,
				loading: false,
				storeList: [],
				system_store: {},
				user_latitude: 0,
				user_longitude: 0,
				comeType: 0,
				cartId: '',
				new: '',
				pinkId: '',
				couponId: ''
			};
		},
		onLoad(options) {
			if (options.cartId) {
				this.cartId = options.cartId;
				this.new = options.new;
				this.pinkId = options.pinkId;
				this.couponId = options.couponId;
			}
			this.comeType = options.type
			try {
				this.user_latitude = uni.getStorageSync('user_latitude');
				this.user_longitude = uni.getStorageSync('user_longitude');
			} catch (e) {}
		},
		mounted() {
			if (this.user_latitude && this.user_longitude) {
				this.getList();
			} else {
				this.selfLocation();
			}
		},
		methods: {
			call(phone) {
				uni.makePhoneCall({
					phoneNumber: phone,
				});
			},
			selfLocation() {
				let self = this
				// #ifdef H5
				if (self.$wechat.isWeixin()) {
					self.$wechat.location().then(res => {
						this.user_latitude = res.latitude;
						this.user_longitude = res.longitude;
						uni.setStorageSync('user_latitude', res.latitude);
						uni.setStorageSync('user_longitude', res.longitude);
						self.getList();
					})
				} else {
					// #endif	
					uni.getLocation({
						type: 'wgs84',
						success: (res) => {
							try {
								this.user_latitude = res.latitude;
								this.user_longitude = res.longitude;
								uni.setStorageSync('user_latitude', res.latitude);
								uni.setStorageSync('user_longitude', res.longitude);
							} catch {}
							self.getList();
						},
						complete: function() {
							self.getList();
						}
					});
					// #ifdef H5	
				}
				// #endif
			},
			showMaoLocation(e) {
				let self = this;
				// #ifdef H5
				if (self.$wechat.isWeixin()) {
					self.$wechat.seeLocation({
						latitude: Number(e.latitude),
						longitude: Number(e.longitude),
						name: e.name,
						scale: 13,
						address: `${e.address}-${e.detailed_address}`,
					}).then(res => {})
				} else {
					// #endif	
					uni.openLocation({
						latitude: Number(e.latitude),
						longitude: Number(e.longitude),
						name: e.name,
						address: `${e.address}-${e.detailed_address}`,
						success: function() {
							Number
						}
					});
					// #ifdef H5	
				}
				// #endif
			},
			// 选中门店
			checked(e) {
				if (this.comeType) {
					uni.$emit("handClick", {
						address: e,
						cartId: this.cartId,
						new: this.new,
						pinkId: this.pinkId,
						couponId: this.couponId
					});
					uni.navigateBack();
				}
			},
			// 获取门店列表数据
			getList: function() {
				if (this.loading || this.loaded) return;
				this.loading = true;
				let data = {
					latitude: this.user_latitude || "", //纬度
					longitude: this.user_longitude || "", //经度
					page: this.page,
					limit: this.limit
				};
				storeListApi(data)
					.then(res => {
						this.loading = false;
						this.loaded = res.data.list.length < this.limit;
						this.storeList.push.apply(this.storeList, res.data.list.list);
						this.page = this.page + 1;
					})
					.catch(err => {
						this.$util.Tips({
							title: err
						})
					});
			}
		},
		onReachBottom() {
			this.getList();
		}
	};
</script>

<style lang="scss">
	.list{
		padding-top: 24rpx;
		.item{
			width: 690rpx;
			background-color: #fff;
			border-radius: 12rpx;
			margin: 0 auto 24rpx auto;
			padding: 28rpx 28rpx 0 28rpx;
			box-sizing: border-box;
			.name{
				font-size: 28rpx;
				color: #333;
				font-weight: 600;
				position: relative;
				padding-left: 12rpx;
				
				&::after{
					position: absolute;
					width: 4rpx;
					height: 28rpx;
					background-color: #E93323;
					content: ' ';
					left:0;
					top:50%;
					margin-top: -14rpx;
				}
			}
			.address{
				font-size: 24rpx;
				color: #666;
				margin-top: 24rpx;
				.iconfont{
					font-size: 30rpx;
					width: 38rpx;
					height: 38rpx;
					line-height: 38rpx;
				}
				.right{
					width: 594rpx;
					.line{
						width: 2rpx;
						height: 20rpx;
						background-color: #ccc;
						display: inline-block;
						margin: 0 20rpx;
					}
				}
				&.time{
					margin-top: 20rpx;
					.iconfont{
						font-size: 28rpx;
					}
				}
			}
			.bottom{
				height: 92rpx;
				border-top: 1rpx solid #eee;
				margin-top: 30rpx;
				position: relative;
				&::after{
					position: absolute;
					content: ' ';
					width: 1rpx;
					height: 60rpx;
					background-color: #eee;
				}
				.bnt{
					width: 314rpx;
					text-align: center;
					font-size: 26rpx;
					color: #333;
					text-decoration: none;
					.iconfont{
						font-size: 35rpx;
						margin-right: 12rpx;
						display: inline-block;
						vertical-align: bottom;
					}
				}
			}
		}
	}
</style>
