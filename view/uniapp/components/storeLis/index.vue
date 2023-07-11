<template>
	<view class="list">
		<view class="item" v-for="(item, index) in storeList" :key="index" v-if="index<num">
			<view class="name line1"><text class="iconfont icon-shangjiadingdan"></text>{{item.name}}</view>
			<view class="address line1"><text class="font-num" v-if="item.range">距您{{ item.range }}km</text><text class="spot" v-if="item.range">·</text>{{ item.address }}{{ ", " + item.detailed_address }}</view>
		</view>
	</view>
</template>
<script>
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
	import {
		mapGetters
	} from "vuex";
	const LONGITUDE = "user_longitude";
	const LATITUDE = "user_latitude";
	const MAPKEY = "mapKey";
	export default {
		name: "storeList",
		props: {
			num : {
				type: Number,
				default: 1,
			},
			ids: {
				type: Number,
				default: 0,
			}
		},
		data() {
			return {
				page: 1,
				limit: 20,
				loaded: false,
				loading: false,
				storeList: [],
				system_store: {},
				user_latitude: 0,
				user_longitude: 0
			};
		},
		created() {
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
						address: `${e.address}-${e.detailed_address}`,
					}).then(res => {
					})
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
			// 获取门店列表数据
			getList: function() {
				if (this.loading || this.loaded) return;
				this.loading = true;
				let data = {
					latitude: this.user_latitude || "", //纬度
					longitude: this.user_longitude || "", //经度
					page: this.page,
					limit: this.limit,
					product_id: this.ids
				};
				storeListApi(data)
					.then(res => {
						this.loading = false;
						this.loaded = res.data.list.length < this.limit;
						this.storeList.push.apply(this.storeList, res.data.list.list);
						if(this.page == 1){
							this.$emit('getStoreList',this.storeList.length)
						}
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
		width: 508rpx;
		margin-left: 28rpx;
		.item{
			font-size: 26rpx;
			color: #333;
			.iconfont{
				font-size: 30rpx;
				margin-right: 10rpx;
				color: #333;
			}
			.address{
				font-size: 22rpx;
				margin-top: 10rpx;
				.spot{
					margin: 0 20rpx;
				}
			}
		}
	}
</style>
