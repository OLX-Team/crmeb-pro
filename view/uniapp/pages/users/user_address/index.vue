<template>
	<view :style="colorStyle">
		<form @submit="formSubmit">
			<view class='addAddress'>
				<view class="pad30">
					<view class='default acea-row row-middle borderRadius15'>
						<input v-model="addressValue" type="text" placeholder="粘贴地址信息，自动拆分姓名、电话和地址" 
						placeholder-class='placeholder' style="width:100%;" 
						@blur="identify()">
					</view>
				</view>
				<view class="pad30 mt-22">
					<view class='list borderRadius15'>
						<view class='item acea-row row-between-wrapper'>
							<view class='name'>姓名</view>
							<input type='text' placeholder='请输入姓名' name='real_name' :value="userAddress.real_name"
								placeholder-class='placeholder'></input>
						</view>
						<view class='item acea-row row-between-wrapper'>
							<view class='name'>联系电话</view>
							<input type='number' maxlength="11" placeholder='请输入联系电话' name="phone" :value='userAddress.phone'
								placeholder-class='placeholder' pattern="\d*"></input>
						</view>
						<view class='item acea-row row-between-wrapper'>
							<view class='name'>所在地区</view>
							<view class="address acea-row row-between">
								<view class="addressCon acea-row" @click="changeRegion">
									<text class="picker" v-if="!addressInfo.length" style="color:#cdcdcd;">请选择地址</text>
									<view v-else>
										<text class="picker">{{addressText}}</text>
										<view class="font-num tip" v-if="isStreet">请补充县/区信息</view>
									</view>
								</view>
								<text class="iconfont icon-dizhi fontcolor" @click="chooseLocation"></text>
							</view>
						</view>
						<view class='item acea-row row-between-wrapper'>
							<view class='name'>详细地址</view>
							<view class="address">
								<input type='text' placeholder='请填写具体地址' name='detail' placeholder-class='placeholder'
									:value='userAddress.detail' class="detail"></input>
							</view>
						</view>
					</view>
				</view>
				<view class="pad30">
					<view class='default acea-row row-middle borderRadius15'>
						<checkbox-group @change='ChangeIsDefault'>
							<checkbox :checked="userAddress.is_default ? true : false" />设置为默认地址
						</checkbox-group>
					</view>
				</view>

				<button class='keepBnt bg-color' form-type="submit">立即保存</button>
				<!-- #ifdef MP -->
				<view class="wechatAddress" v-if="!id" @click="getWxAddress">导入微信地址</view>
				<!-- #endif -->
				<!-- #ifdef H5 -->
				<view class="wechatAddress" v-if="this.$wechat.isWeixin() && !id" @click="getAddress">导入微信地址</view>
				<!-- #endif -->
			</view>
		</form>
		<areaWindow ref="areaWindow" :display="display" :address="addressInfo"
			 @submit="OnChangeAddress" @changeClose="changeClose"></areaWindow>
		<home v-if="navigation"></home>
	</view>
</template>

<script>
	import {
		editAddress,
		getAddressDetail,
		getGeocoder,
		getCityList
	} from '@/api/user.js';
	import {
		getCityData
	} from '@/api/api.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import {
		mapGetters
	} from "vuex";

	// #ifdef MP
	import authorize from '@/components/Authorize';
	// #endif
	import home from '@/components/home';
	import colors from '@/mixins/color.js';
	import areaWindow from '@/components/areaWindow';
	import AddressParse from '../static/zh-address-parse.min.js'
	export default {
		components: {
			areaWindow,
			// #ifdef MP
			authorize,
			// #endif
			home,
		},
		mixins:[colors],
		data() {
			return {
				cartId: '', //购物车id
				pinkId: 0, //拼团id
				couponId: 0, //优惠券id
				id: 0, //地址id
				userAddress: {
					is_default: false
				}, //地址详情
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				district: [],
				news: '',
				noCoupon: 0,
				display: false,
				addressInfo:[],
				addressVal:'',
				latitude:'',
				longitude:'',
				city_id:0,
				isStreet:0,
				addressValue:""
			};
		},
		computed: {...mapGetters(['isLogin']),
			addressText(){
				return this.addressInfo.map(v=>v.label).join('/');
			}
		},
		watch: {
			isLogin: {
				handler: function(newV, oldV) {
					if (newV) {
						this.getUserAddress();
					}
				},
				deep: true
			}
		},
		onLoad(options) {
			if (this.isLogin) {
				this.cartId = options.cartId || '';
				this.pinkId = options.pinkId || 0;
				this.couponId = options.couponId || 0;
				this.id = options.id || 0;
				this.noCoupon = options.noCoupon || 0;
				this.news = options.new || '';
				uni.setNavigationBarTitle({
					title: options.id ? '修改地址' : '添加地址'
				})
				this.getUserAddress();
				// this.getCityList();
			} else {
				toLogin();
			}
		},
		methods: {
			changeRegion(){
				this.display = true;
			},
			OnChangeAddress(address){
				this.latitude = ''
				this.longitude = ''
				this.isStreet = 0
				this.addressInfo = address;
			},
			// 地址数据
			// getCityList: function() {
			// 	let that = this;
			// 	getCityData(0).then(res => {
			// 		this.district = res.data
			// 	})
			// },
			// 关闭地址弹窗；
			changeClose: function() {
				this.display = false;
			},
			getUserAddress: function() {
				if (!this.id) return false;
				let that = this;
				getAddressDetail(this.id).then(res => {
					let region = [{label:res.data.province}, {label:res.data.city}, {label:res.data.district}, {label:res.data.street}];
					that.$set(that, 'userAddress', res.data);
					that.addressInfo = res.data.city_list;
					that.latitude = res.data.latitude;
					that.longitude = res.data.longitude;
					that.city_id = res.data.city_id;
				});
			},
			// 获取选中位置
			chooseLocation: function() {
				let self = this;
				uni.chooseLocation({
					success: (res) => {
						let latitude, longitude;
						latitude = res.latitude.toString();
						longitude = res.longitude.toString();
						this.latitude = res.latitude
						this.longitude = res.longitude
						getGeocoder({
							lat: latitude,
							long: longitude
						}).then(res => {
							const data = res.data; 
							getCityList(data.address_component.province+'/'+data.address_component.city+'/'+data.address_component.district+'/'+(!data.address_reference.town ? '' : data.address_reference.town.title)).then(res=>{
								self.addressInfo = res.data;
								self.userAddress.detail = data.formatted_addresses.recommend;
							}).catch(err => {
								self.$util.Tips({
									title: err
								});
							});
						})
					},
					fail: (err)=>{
						console.log(err)
					}
				})
			},
			// 自动定位
			selfLocation() {
				let self = this
				uni.showLoading({
					title: '定位中',
					mask: true,
				});
				uni.getLocation({
					type: 'gcj02',
					success: (res) => {
						let latitude, longitude;
						latitude = res.latitude.toString();
						longitude = res.longitude.toString();
						this.latitude = res.latitude
						this.longitude = res.longitude
						getGeocoder({
							lat: latitude,
							long: longitude
						}).then(res => {
							const data = res.data;
							getCityList(data.address_component.province+'/'+data.address_component.city+'/'+data.address_component.district+'/'+(!data.address_reference.town ? '' : data.address_reference.town.title)).then(res=>{
								self.addressInfo = res.data;
								self.userAddress.detail = data.formatted_addresses.recommend;
								uni.hideLoading();
							})
						})
					},
					fail: (res) => {
						uni.hideLoading();
						uni.showToast({
							title: res,
							icon: 'none',
							duration: 1000
						});
					}
				});
			},
			// 导入共享地址（小程序）
			getWxAddress: function() {
				let that = this;
				uni.authorize({
					scope: 'scope.address',
					success: function(res) {
						uni.chooseAddress({
							success: function(res) {
								getCityList(res.provinceName+'/'+res.cityName+'/'+res.countyName).then(res=>{
									that.addressInfo = res.data;
								})
								that.userAddress.real_name = res.userName;
								that.userAddress.phone = res.telNumber;
								that.userAddress.detail = res.detailInfo;	
								that.isStreet = 1;
							},
							fail: function(res) {
								if (res.errMsg == 'chooseAddress:cancel') return that.$util
									.Tips({
										title: '取消选择'
									});
							},
						})
					},
					fail: function(res) {
						uni.showModal({
							title: '您已拒绝导入微信地址权限',
							content: '是否进入权限管理，调整授权？',
							success(res) {
								if (res.confirm) {
									uni.openSetting({
										success: function(res) {}
									});
								} else if (res.cancel) {
									return that.$util.Tips({
										title: '已取消！'
									});
								}
							}
						})
					},
				})
			},
			// 导入共享地址（微信）；
			getAddress() {
				let that = this;
				that.$wechat.openAddress().then(res => {
					getCityList(res.provinceName+'/'+res.cityName+'/'+res.countryName).then(res=>{
						that.addressInfo = res.data;
					})
					that.userAddress.real_name = res.userName;
					that.userAddress.phone = res.telNumber;
					that.userAddress.detail = res.detailInfo;
					that.isStreet = 1;
				}).catch(err => {
					that.$util.Tips({
						title: err
					});
				});
			},
			/**
			 * 提交用户添加地址
			 * 
			 */
			formSubmit: function(e) {
				let that = this,
					value = e.detail.value;
				if (!value.real_name) return that.$util.Tips({
					title: '请填写收货人姓名'
				});
				if (!value.phone) return that.$util.Tips({
					title: '请填写联系电话'
				});
				if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(value.phone)) return that.$util.Tips({
					title: '请输入正确的手机号码'
				});
				if (!that.addressInfo.length) return that.$util.Tips({
					title: '请选择所在地区'
				});
				// if (that.region[0] == '省') return that.$util.Tips({
				// 	title: '请选择所在地区'
				// });
				if (!value.detail) return that.$util.Tips({
					title: '请填写详细地址'
				});
				// if(!that.longitude && !that.latitude) return that.$util.Tips({title:'请定位你的经纬度'})
				value.id = that.id;
				let regionArray = that.addressInfo;
				value.address = {
					province: regionArray[0].label,
					city: regionArray[1].label,
					district: regionArray[2].label,
					street: regionArray[3]?regionArray[3].label:'',
					city_id: regionArray[regionArray.length-1].id?regionArray[regionArray.length-1].id:that.city_id,
				};
				value.is_default = that.userAddress.is_default ? 1 : 0;
				// 经度
				value.longitude = that.longitude;
				// 纬度
				value.latitude = that.latitude;
				uni.showLoading({
					title: '保存中',
					mask: true
				})
				editAddress(value).then(res => {
					if (that.id)
						that.$util.Tips({
							title: '修改成功',
							icon: 'success'
						});
					else
						that.$util.Tips({
							title: '添加成功',
							icon: 'success'
						});
					setTimeout(function() {
						if (that.cartId) {
							let cartId = that.cartId;
							let pinkId = that.pinkId;
							let couponId = that.couponId;
							that.cartId = '';
							that.pinkId = '';
							that.couponId = '';
							uni.navigateTo({
								url: '/pages/users/order_confirm/index?new=' + that.news +
									'&cartId=' + cartId + '&addressId=' + (that.id ? that.id :
										res.data.id) + '&pinkId=' + pinkId + '&couponId=' +
									couponId +
									'&noCoupon=' + that
									.noCoupon
							});
						} else {
							// #ifdef H5
							return history.back();
							// #endif
							// #ifndef H5
							return uni.navigateBack({
								delta: 1,
							})
							// #endif
						}
					}, 1000);
				}).catch(err => {
					return that.$util.Tips({
						title: err
					});
				})
			},
			ChangeIsDefault: function(e) {
				this.$set(this.userAddress, 'is_default', !this.userAddress.is_default);
			},
			identify(){
				const options = {
				  type: 0, // 哪种方式解析，0：正则，1：树查找
				  textFilter: [], // 预清洗的字段
				  nameMaxLength: 4, // 查找最大的中文名字长度
				}
				const parseResult = AddressParse(this.addressValue.trim(), options)
				// type参数0表示使用正则解析，1表示采用树查找, textFilter地址预清洗过滤字段。
				if(this.addressValue.trim()){
					getCityList(parseResult.province+'/'+parseResult.city+'/'+parseResult.area).then(res=>{
						this.addressInfo = res.data;
						this.userAddress.phone = parseResult.phone;
						this.userAddress.real_name = parseResult.name;
						this.userAddress.detail = parseResult.detail;
					}).catch(err=>{
						return this.$util.Tips({
							title: err
						});
					})
				}
			}
		}
	}
</script>

<style scoped lang="scss">
	.fontcolor{
		color: var(--view-theme);
	}
	.addAddress .list {
		background-color: #fff;
	}

	.addAddress .list .item {
		padding: 30rpx;
		border-top: 1rpx solid #eee;
		position: relative;
	}
	
	.addAddress .list .item .detail{
		width: 368rpx;
	}
	
	.addAddress .list .item .location{
		position: absolute;
		right: 46rpx;
		top: 50%;
		margin-top: -40rpx!important;
		font-size: 24rpx;
		text-align: center;
	}
	
	.addAddress .list .item .icon-dizhi{
		 font-size: 36rpx!important;
	}

	.addAddress .list .item .name {
		width: 195rpx;
		font-size: 30rpx;
		color: #333;
	}

	.addAddress .list .item .address {
		// width: 412rpx;
		flex: 1;
		margin-left: 20rpx;
	}
	
	.addAddress .list .item .address .addressCon{
		width: 360rpx;
	}
	
	.addAddress .list .item .address .addressCon .tip{
		font-size: 21rpx;
		margin-top: 4rpx;
	}

	.addAddress .list .item input {
		// width: 475rpx;
		flex: 1;
		font-size: 30rpx;
	}

	.placeholder {
		color: #ccc;
	}

	// .addAddress .list .item {
	// 	width: 475rpx;
	// }

	.addAddress .list .item .picker {
		width: 430rpx;
		font-size: 30rpx;
	}

	.addAddress .list .item .iconfont {
		font-size: 30rpx;
		margin-top: 4rpx;
	}

	.addAddress .default {
		padding: 0 30rpx;
		height: 90rpx;
		background-color: #fff;
		margin-top: 23rpx;
	}

	.addAddress .default checkbox {
		margin-right: 15rpx;
	}

	.addAddress .keepBnt {
		width: 690rpx;
		height: 86rpx;
		border-radius: 50rpx;
		text-align: center;
		line-height: 86rpx;
		margin: 50rpx auto;
		font-size: 32rpx;
		color: #fff;
	}

	.addAddress .wechatAddress {
		width: 690rpx;
		height: 86rpx;
		border-radius: 50rpx;
		text-align: center;
		line-height: 86rpx;
		margin: 0 auto;
		font-size: 32rpx;
		color: var(--view-theme);
		border: 1px solid var(--view-theme);
	}
	.mt-22{
		margin-top: 22rpx;
	}
</style>
