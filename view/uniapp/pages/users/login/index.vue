<template>
	<view class="login-wrapper" :style="colorStyle">
		<view class="login-top"></view>
		<view class="shading">
			<image :src="logoUrl" v-if="logoUrl" />
			<image src="/static/images/logo2.png" v-else />
		</view>
		<view class="whiteBg" v-if="formItem === 1">

			<view class="tips">
				<view class="tips-btn" :class="current == 1 ? 'on' : ''" @click="current = 1">
					<view>快速登录</view>
					<view :class="current == 1 ? 'line' : 'none'"></view>
				</view>
				<view class="tips-btn" :class="current == 0 ? 'on' : ''" @click="current = 0">
					<view>账号登录</view>
					<view :class="current == 0 ? 'line' : 'none'"></view>
				</view>
			</view>
			<view class="list" v-if="current !== 1">
				<form @submit.prevent="submit">
					<view class="item">
						<view class="acea-row row-middle">
							<image src="/static/images/phone_1.png" style="width: 24rpx; height: 34rpx;"></image>
							<input type="text" placeholder="输入手机号码" v-model="account" maxlength="11" required />
						</view>
					</view>
					<view class="item">
						<view class="acea-row row-middle">
							<image src="/static/images/code_1.png" style="width: 28rpx; height: 32rpx;"></image>
							<input type="password" placeholder="填写登录密码" v-model="password" required />
						</view>
					</view>
				</form>
				<navigator class="forgetPwd" hover-class="none" url="/pages/users/retrievePassword/index">
					忘记密码?
				</navigator>
			</view>
			<view class="list" v-if="current !== 0 || appLoginStatus || appleLoginStatus">
				<view class="item">
					<view class="acea-row row-middle">
						<image src="/static/images/phone_1.png" style="width: 24rpx; height: 34rpx;"></image>
						<input type="text" placeholder="输入手机号码" v-model="account" maxlength="11" />
					</view>
				</view>
				<view class="item">
					<view class="acea-row row-middle">
						<image src="/static/images/code_2.png" style="width: 28rpx; height: 32rpx;"></image>
						<input type="text" placeholder="填写验证码" maxlength="6" class="codeIput" v-model="captcha" />
						<button class="code" :disabled="disabled" :class="disabled === true ? 'on' : ''" @click="code">
							{{ text }}
						</button>
					</view>
				</view>
				<view class="item" v-if="isShowCode">
					<view class="acea-row row-middle">
						<image src="/static/images/code_2.png" style="width: 28rpx; height: 32rpx;"></image>
						<input type="text" placeholder="填写验证码" class="codeIput" v-model="codeVal" />
						<view class="code" @click="again"><img :src="codeUrl" /></view>
					</view>
				</view>
			</view>
			<view class="logon" @click="loginMobile" v-if="current !== 0">登录</view>
			<view class="logon" @click="submit" v-if="current === 0">登录</view>
			<!-- #ifdef APP-PLUS -->
			<view class="appLogin" v-if="!appLoginStatus && !appleLoginStatus">
				<view class="hds">
					<span class="line"></span>
					<p>其他方式登录</p>
					<span class="line"></span>
				</view>
				<view class="btn-wrapper">
					<view class="btn wx" @click="wxLogin">
						<span class="iconfont icon-s-weixindenglu1"></span>
					</view>
					<view class="btn pingguo" @click="appleLogin" v-if="appleShow">
						<view class="iconfont icon-s-pingguo"></view>
					</view>
				</view>
			</view>
			<!-- #endif -->
			<view class="protocol">
				<checkbox-group @change='ChangeIsDefault'>
					<checkbox :class="inAnimation?'trembling':''" @animationend='inAnimation=false'
						:checked="protocol ? true : false" />已阅读并同意 <text class="main-color"
						@click="privacy('user')">《用户协议》</text>
					与<text class="main-color" @click="privacy('privacy ')">《隐私协议》</text>
				</checkbox-group>
			</view>
		</view>
		<view class="bottom"></view>
	</view>
</template>
<script>
	import dayjs from "@/plugin/dayjs/dayjs.min.js";
	import sendVerifyCode from "@/mixins/SendVerifyCode";
	import {
		loginH5,
		loginMobile,
		registerVerify,
		register,
		getCodeApi,
		getUserInfo,
		appleLogin
	} from "@/api/user";
	import attrs, {
		required,
		alpha_num,
		chs_phone
	} from "@/utils/validate";
	import {
		validatorDefaultCatch
	} from "@/utils/dialog";
	import {
		getLogo
	} from "@/api/public";
	// import cookie from "@/utils/store/cookie";
	import {
		VUE_APP_API_URL
	} from "@/utils";
	// #ifdef APP-PLUS
	import {
		wechatAppAuth
	} from '@/api/api.js'
	// #endif
	const BACK_URL = "login_back_url";
	import colors from '@/mixins/color.js';
	export default {
		name: "Login",
		mixins: [sendVerifyCode, colors],
		data: function() {
			return {
				inAnimation: false,
				protocol: false,
				navList: ["快速登录", "账号登录"],
				current: 1,
				account: "",
				password: "",
				captcha: "",
				formItem: 1,
				type: "login",
				logoUrl: "",
				keyCode: "",
				codeUrl: "",
				codeVal: "",
				isShowCode: false,
				appLoginStatus: false, // 微信登录强制绑定手机号码状态
				appUserInfo: null, // 微信登录保存的用户信息
				appleLoginStatus: false, // 苹果登录强制绑定手机号码状态
				appleUserInfo: null,
				appleShow: false, // 苹果登录版本必须要求ios13以上的
				keyLock: true
			};
		},
		watch: {
			formItem: function(nval, oVal) {
				if (nval == 1) {
					this.type = 'login'
				} else {
					this.type = 'register'
				}
			}
		},
		onLoad() {
			let self = this
			uni.getSystemInfo({
				success: (res) => {
					if (res.platform.toLowerCase() == 'ios' && this.getSystem(res.system)) {
						self.appleShow = true
					}
				}
			});
		},
		mounted: function() {
			// this.getCode();
			this.getLogoImage();
		},
		methods: {

			changeMsg() {
				this.inAnimation = true;
			},
			ChangeIsDefault(e) {
				this.$set(this, 'protocol', !this.protocol);
			},
			// IOS 版本号判断
			getSystem(system) {
				let str
				system.toLowerCase().indexOf('ios') === -1 ? str = system : str = system.split(' ')[1]
				if (str.indexOf('.'))
					return str.split('.')[0] >= 13
				return str >= 13
			},
			// 苹果登录
			appleLogin() {
				let self = this
				this.account = ''
				this.captcha = ''
				if (!self.protocol) {
					this.inAnimation = true
					return self.$util.Tips({
						title: '请先阅读并同意协议'
					});
				}
				uni.showLoading({
					title: '登录中'
				})
				uni.login({
					provider: 'apple',
					timeout: 10000,
					success(loginRes) {
						uni.getUserInfo({
							provider: 'apple',
							success: function(infoRes) {
								self.appleUserInfo = infoRes.userInfo
								self.appleLoginApi()
							},
							fail() {
								uni.showToast({
									title: '获取用户信息失败',
									icon: 'none',
									duration: 2000
								})
							},
							complete() {
								uni.hideLoading()
							}
						});
					},
					fail(error) {
						console.log(error)
					}
				})
			},
			// 苹果登录Api
			appleLoginApi() {
				let self = this
				appleLogin({
					openId: self.appleUserInfo.openId,
					email: self.appleUserInfo.email || '',
					phone: this.account,
					captcha: this.captcha
				}).then(({
					data
				}) => {
					if (data.isbind) {
						uni.showModal({
							title: '提示',
							content: '请绑定手机号后，继续操作',
							showCancel: false,
							success: function(res) {
								if (res.confirm) {
									self.current = 1
									self.appleLoginStatus = true
								}
							}
						});
					} else {
						self.$store.commit("LOGIN", {
							'token': data.token,
							'time': data.expires_time - self.$Cache.time()
						});
						let backUrl = self.$Cache.get(BACK_URL) || "/pages/index/index";
						self.$Cache.clear(BACK_URL);
						self.$store.commit("SETUID", data.userInfo.uid);
						self.$store.commit("UPDATE_USERINFO", data.userInfo);
						uni.reLaunch({
							url: backUrl
						});
					}
				}).catch(error => {
					uni.showModal({
						title: '提示',
						content: `错误信息${error}`,
						success: function(res) {
							if (res.confirm) {
								console.log('用户点击确定');
							} else if (res.cancel) {
								console.log('用户点击取消');
							}
						}
					});
				})
			},
			// App微信登录
			wxLogin() {
				if (!this.protocol) {
					this.inAnimation = true
					return this.$util.Tips({
						title: '请先阅读并同意协议'
					});
				}
				let self = this
				this.account = ''
				this.captcha = ''
				uni.showLoading({
					title: '登录中'
				})
				uni.login({
					provider: 'weixin',
					success: function(loginRes) {
						// 获取用户信息
						uni.getUserInfo({
							provider: 'weixin',
							success: function(infoRes) {
								self.appUserInfo = infoRes.userInfo
								self.wxLoginApi()
							},
							fail() {
								uni.showToast({
									title: '获取用户信息失败',
									icon: 'none',
									duration: 2000
								})
							},
							complete() {
								uni.hideLoading()
							}
						});
					},
					fail() {
						uni.showToast({
							title: '登录失败',
							icon: 'none',
							duration: 2000
						})
					}
				});
			},

			wxLoginApi() {
				let self = this
				wechatAppAuth({
					userInfo: self.appUserInfo,
					phone: this.account,
					code: this.captcha
				}).then(({
					data
				}) => {
					if (data.isbind) {
						uni.showModal({
							title: '提示',
							content: '请绑定手机号后，继续操作',
							showCancel: false,
							success: function(res) {
								if (res.confirm) {
									self.current = 1
									self.appLoginStatus = true
								}
							}
						});
					} else {
						self.$store.commit("LOGIN", {
							'token': data.token,
							'time': data.expires_time - self.$Cache.time()
						});
						let backUrl = self.$Cache.get(BACK_URL) || "/pages/index/index";
						self.$Cache.clear(BACK_URL);
						self.$store.commit("SETUID", data.userInfo.uid);
						self.$store.commit("UPDATE_USERINFO", data.userInfo);
						uni.reLaunch({
							url: backUrl
						});
					}
				}).catch(error => {
					uni.showModal({
						title: '提示',
						content: `错误信息${error}`,
						success: function(res) {
							if (res.confirm) {
								console.log('用户点击确定');
							} else if (res.cancel) {
								console.log('用户点击取消');
							}
						}
					});
				})
			},
			again() {
				this.codeUrl =
					VUE_APP_API_URL +
					"/sms_captcha?" +
					"key=" +
					this.keyCode +
					Date.parse(new Date());
			},
			code() {
				let that = this
				if (!that.protocol) {
					this.inAnimation = true
					return that.$util.Tips({
						title: '请先阅读并同意协议'
					});
				}
				if (!that.account) return that.$util.Tips({
					title: '请填写手机号码'
				});
				if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(that.account)) return that.$util.Tips({
					title: '请输入正确的手机号码'
				});
				getCodeApi()
					.then(res => {
						that.keyCode = res.data.key;
						that.getCode();
					})
					.catch(res => {
						that.$util.Tips({
							title: res
						});
					});
			},
			async getLogoImage() {
				let that = this;
				getLogo(2).then(res => {
					that.logoUrl = res.data.logo_url;
				});
			},
			async loginMobile() {
				let that = this;
				if (!that.protocol) {
					this.inAnimation = true
					return that.$util.Tips({
						title: '请先阅读并同意协议'
					});
				}
				if (!that.account) return that.$util.Tips({
					title: '请填写手机号码'
				});
				if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(that.account)) return that.$util.Tips({
					title: '请输入正确的手机号码'
				});
				if (!that.captcha) return that.$util.Tips({
					title: '请填写验证码'
				});
				if (!/^[\w\d]+$/i.test(that.captcha)) return that.$util.Tips({
					title: '请输入正确的验证码'
				});
				if (that.appLoginStatus) {
					that.wxLoginApi()
				} else if (that.appleLoginStatus) {
					that.appleLoginApi()
				} else {
					if (this.keyLock) {
						this.keyLock = !this.keyLock
					} else {
						return that.$util.Tips({
							title: '请勿重复点击'
						});
					}
					loginMobile({
							phone: that.account,
							captcha: that.captcha,
							spread: that.$Cache.get("spread")
						})
						.then(res => {
							let data = res.data;
							that.$store.commit("LOGIN", {
								'token': data.token,
								'time': data.expires_time - this.$Cache.time()
							});
							let backUrl = that.$Cache.get(BACK_URL) || "/pages/index/index";
							that.$Cache.clear(BACK_URL);
							getUserInfo().then(res => {
								this.keyLock = true
								that.$store.commit("SETUID", res.data.uid);
								that.$store.commit("UPDATE_USERINFO", res.data);
								if (backUrl.indexOf('/pages/users/login/index') !== -1) {
									backUrl = '/pages/index/index';
								}
								uni.reLaunch({
									url: backUrl
								});
							})
						})
						.catch(res => {
							this.keyLock = true
							that.$util.Tips({
								title: res
							});
						});
				}

			},
			async register() {
				let that = this;
				if (!that.account) return that.$util.Tips({
					title: '请填写手机号码'
				});
				if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(that.account)) return that.$util.Tips({
					title: '请输入正确的手机号码'
				});
				if (!that.captcha) return that.$util.Tips({
					title: '请填写验证码'
				});
				if (!/^[\w\d]+$/i.test(that.captcha)) return that.$util.Tips({
					title: '请输入正确的验证码'
				});
				if (!that.password) return that.$util.Tips({
					title: '请填写密码'
				});
				if (/^([0-9]|[a-z]|[A-Z]){0,6}$/i.test(that.password)) return that.$util.Tips({
					title: '您输入的密码过于简单'
				});
				register({
						account: that.account,
						captcha: that.captcha,
						password: that.password,
						spread: that.$Cache.get("spread")
					})
					.then(res => {
						that.$util.Tips({
							title: res
						});
						that.formItem = 1;
					})
					.catch(res => {
						that.$util.Tips({
							title: res
						});
					});
			},
			async getCode() {
				let that = this;
				if (!that.account) return that.$util.Tips({
					title: '请填写手机号码'
				});
				if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(that.account)) return that.$util.Tips({
					title: '请输入正确的手机号码'
				});
				if (that.formItem == 2) that.type = "register";

				await registerVerify({
						phone: that.account,
						type: that.type,
						key: that.keyCode,
						code: that.codeVal
					})
					.then(res => {
						that.$util.Tips({
							title: res.msg
						});
						that.sendCode();
					})
					.catch(res => {
						that.$util.Tips({
							title: res
						});
					});
			},
			navTap: function(index) {
				this.current = index;
			},
			async submit() {
				let that = this;
				if (!that.protocol) {
					this.inAnimation = true
					return that.$util.Tips({
						title: '请先阅读并同意协议'
					});
				}
				if (!that.account) return that.$util.Tips({
					title: '请填写账号'
				});
				if (!/^[\w\d]{5,16}$/i.test(that.account)) return that.$util.Tips({
					title: '请输入正确的账号'
				});
				if (!that.password) return that.$util.Tips({
					title: '请填写密码'
				});
				if (this.keyLock) {
					this.keyLock = !this.keyLock
				} else {
					return that.$util.Tips({
						title: '请勿重复点击'
					});
				}
				loginH5({
						account: that.account,
						password: that.password,
						spread: that.$Cache.get("spread")
					})
					.then(({
						data
					}) => {
						that.$store.commit("LOGIN", {
							'token': data.token,
							'time': data.expires_time - this.$Cache.time()
						});
						let backUrl = that.$Cache.get(BACK_URL) || "/pages/index/index";
						that.$Cache.clear(BACK_URL);
						getUserInfo().then(res => {
							this.keyLock = true
							that.$store.commit("SETUID", res.data.uid);
							that.$store.commit("UPDATE_USERINFO", res.data);
							uni.reLaunch({
								url: backUrl
							});
						}).catch(error => {
							this.keyLock = true
						})
					})
					.catch(e => {
						this.keyLock = true
						that.$util.Tips({
							title: e
						});
					});
			},
			privacy(type) {
				uni.navigateTo({
					url: "/pages/users/privacy/index?type=" + type
				})
			}
		}
	};
</script>
<style>
	page {}
</style>
<style lang="scss">
	/deep/uni-checkbox .uni-checkbox-input{
		margin-top: -6rpx;
	}
	.appLogin {
		margin-top: 60rpx;

		.hds {
			display: flex;
			justify-content: center;
			align-items: center;
			font-size: 24rpx;
			color: #B4B4B4;

			.line {
				width: 68rpx;
				height: 1rpx;
				background: #CCCCCC;
			}

			p {
				margin: 0 20rpx;
			}
		}

		.btn-wrapper {
			display: flex;
			align-items: center;
			justify-content: center;
			margin-top: 30rpx;

			.btn {
				display: flex;
				align-items: center;
				justify-content: center;
				width: 68rpx;
				height: 68rpx;
				border-radius: 50%;
			}

			.apple-btn {
				display: flex;
				align-items: center;
				justify-content: center;
				width: 246rpx;
				height: 66rpx;
				margin-left: 30rpx;
				background: #EAEAEA;
				border-radius: 34rpx;
				font-size: 24rpx;

				.icon-s-pingguo {
					color: #333;
					margin-right: 10rpx;
					font-size: 34rpx;
				}
			}

			.iconfont {
				font-size: 40rpx;
				color: #fff;
			}

			.wx {
				background-color: #61C64F;
			}

			.mima {
				background-color: #28B3E9;
			}

			.yanzheng {
				background-color: #F89C23;
			}

			.pingguo {
				margin-left: 60rpx;
				background-color: #000;
			}

		}
	}

	.main-color {
		color: var(--view-theme);
	}

	.code img {
		width: 100%;
		height: 100%;
	}

	.acea-row.row-middle {
		input {
			margin-left: 20rpx;
			display: block;
		}
	}

	.login-wrapper {
		.login-top {
			height: 358rpx;
			background-color: var(--view-theme);
			background-image: url(../static/login.png);
			background-size: cover;
			background-repeat: no-repeat;

			image {
				width: 101%;
				height: 100%;
			}
		}

		.shading {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 100%;
			margin-top: -230rpx;
			position: relative;

			image {
				width: 180rpx;
				height: 180rpx;
				top: 40rpx;
			}
		}

		.whiteBg {
			background-color: #fff;
			margin: -30rpx 56rpx 0rpx 56rpx;
			box-shadow: 0px 2px 14px 0px rgba(0, 0, 0, 0.08);
			border-radius: 8px;
			padding: 60rpx;

			.tips {
				display: flex;
				align-items: center;
				justify-content: center;
				height: 50rpx;
				margin: 40rpx;
				color: #999;

				.tips-btn {
					margin: 0 31rpx 0 48rpx;
					color: #999999;
					font-weight: bold;
					font-size: 32rpx;


					/* Safari 与 Chrome */
					.line {
						width: 80rpx;
						height: 6rpx;
						background: linear-gradient(135deg, var(--view-minorColor) 0%, var(--view-theme) 100%);
						border-radius: 2px;
						margin: 10rpx auto 0 auto;
						animation: myfirst .3s;
						-webkit-animation: myfirst .3s;
					}

					.none {
						width: 80rpx;
						background: #fff;
						height: 6rpx;
					}
				}

				@keyframes myfirst {
					0% {
						width: 0rpx;
					}

					100% {
						width: 80rpx;
					}
				}

				@-webkit-keyframes myfirst

				/* Safari 与 Chrome */
					{
					0% {
						width: 0rpx;
					}

					100% {
						width: 80rpx;
					}
				}

				.tips-btn.on {
					font-size: 36rpx;
					color: var(--view-theme);
				}
			}

			.list {
				border-radius: 16rpx;
				overflow: hidden;

				.forgetPwd {
					text-align: right;
					margin-top: 10rpx;
					color: #666666;
					font-size: 24rpx;
				}

				.item {
					border-bottom: 1px solid #F0F0F0;
					background: #fff;

					.row-middle {
						position: relative;
						padding: 16rpx 25rpx;

						input {
							flex: 1;
							font-size: 28rpx;
							height: 80rpx;
						}

						.code {
							position: absolute;
							right: 30rpx;
							top: 50%;
							color: var(--view-theme);
							font-size: 26rpx;
							transform: translateY(-50%);
						}
					}
				}
			}

			.logon {
				display: flex;
				align-items: center;
				justify-content: center;
				width: 100%;
				height: 86rpx;
				margin-top: 48rpx;
				background: linear-gradient(135deg, var(--view-minorColor) 0%, var(--view-theme) 100%);
				border-radius: 120rpx;
				color: #FFFFFF;
				font-size: 30rpx;
			}
		}

		.protocol {
			margin-top: 40rpx;
			color: #999999;
			font-size: 24rpx;
		}

		.trembling {
			animation: shake 0.6s;
		}

		@keyframes shake {

			0%,
			100% {
				-webkit-transform: translateX(0);
			}

			10%,
			30%,
			50%,
			70%,
			90% {
				-webkit-transform: translateX(-5rpx);
			}

			20%,
			40%,
			60%,
			80% {
				-webkit-transform: translateX(5rpx);
			}
		}

		@-o-keyframes shake {

			/* Opera */
			0%,
			100% {
				-webkit-transform: translateX(0);
			}

			10%,
			30%,
			50%,
			70%,
			90% {
				-webkit-transform: translateX(-5rpx);
			}

			20%,
			40%,
			60%,
			80% {
				-webkit-transform: translateX(5rpx);
			}
		}

		@-webkit-keyframes shake {

			/* Safari 和 Chrome */
			0%,
			100% {
				-webkit-transform: translateX(0);
			}

			10%,
			30%,
			50%,
			70%,
			90% {
				-webkit-transform: translateX(-5rpx);
			}

			20%,
			40%,
			60%,
			80% {
				-webkit-transform: translateX(5rpx);
			}
		}

		@-moz-keyframes shake {

			/* Firefox */
			0%,
			100% {
				-moz-transform: translateX(0);
			}

			10%,
			30%,
			50%,
			70%,
			90% {
				-moz-transform: translateX(-5rpx);
			}

			20%,
			40%,
			60%,
			80% {
				-moz-transform: translateX(5rpx);
			}
		}
	}
</style>
