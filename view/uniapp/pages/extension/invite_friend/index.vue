<template>
	<view class="">
		<view class="invite" v-if="inviteShow && loading">
			<view class="invite-header" :style="{backgroundImage:'url('+imgHost+'/statics/images/extension.jpg'+')'}">
				<view class='swipers'>
					<swiper :indicator-dots="false" autoplay="true" interval="2500" duration="500" vertical="true"
						circular="true">
						<block v-for="(item,index) in agentInfoData.list" :key='index'>
							<swiper-item @touchmove.stop="stopTouchMove">
								<view class='line1'>恭喜{{item.nickname}} <text class="color_ye">
										成功赚取{{item.price}}</text> </view>
							</swiper-item>
						</block>
					</swiper>
				</view>
			</view>
			<!-- <noticeBar class="notice" :showMsg="userList.data"></noticeBar> -->
			<view class="box">
				<view class="box-title-sty">
					<view class="box-title" :style="{backgroundImage:'url('+imgHost+'/statics/images/title-bag.png'+')'}">
						我的收益
					</view>
					<view class="benefit">
						<text class="iconfont icon-zhu"></text>
						<text>获得收益</text>
						<text class="num">{{agentInfoData.price || 0}}</text>
						<text>元</text>
					</view>
				</view>
				<view class="tab">
					<view class="item" @click="getList(0)">
						<view class="text" :class="sel == 0?'on':''">已邀请好友</view>
						<view class="line" :class="sel == 0?'on':''"></view>
					</view>
					<view class="item" @click="getList(1)">
						<view class=" text" :class="sel == 1 ?'on':''">已下单好友</view>
						<view class="line" :class="sel == 1 ?'on':''"></view>
					</view>
				</view>
				<view class="list" v-if="userList.length">
					<view class="item" v-for="(item,index) in userList" :key="index">
						<view class="item-l">
							<view class="avatar">
								<image :src="item.avatar" mode=""></image>
							</view>
							<view class="">{{item.nickname}}</view>
						</view>
						<view class="item-r">{{item.spread_time}}</view>
					</view>
					<template v-if="userList.length">
						<view class='more' @tap='showAll' v-if="userList.length < total">查看更多
							<text class='iconfont icon-xiangxia'></text>
						</view>
					</template>
				</view>
				<view class="no-thing" v-if="(!userList.length && sel == 0) || (!userList.length && sel == 1)">
					<view class="no-thing-img">
						<image src="../static/no-thing.png" mode="aspectFit"></image>
					</view>
					<view class="pl20">
						{{sel == 0?'暂无已邀请好友，快去邀请吧':'暂无下单好友，快去邀请下单吧'}}
					</view>
				</view>
			</view>
			<view class="box">
				<view class="box-title-sty white">
					<view class="box-title" :style="{backgroundImage:'url('+imgHost+'/statics/images/title-bag.png'+')'}">活动规则</view>
				</view>
				<view class="agreement" v-html="agentInfoData.agreement"></view>
			</view>
			<view class="footer">
				<view class="click">
					<image src="../static/click.png" mode=""></image>
				</view>
				<view class="cancellation flex-aj-center" @click="invite">
					立即邀请
				</view>
			</view>
		</view>
		<view class="no-invite" v-else-if="!inviteShow && loading">
			<image src="../../../static/images/no-thing.png" mode="aspectFit"></image>
			<text>商家暂未上架活动哦～</text>
		</view>
		<home v-if="navigation"></home>
	</view>
</template>

<script>
	import {
		getUserInfo,
		agentUserList,
		agentInfo
	} from '@/api/user.js';
	import colors from '@/mixins/color.js'
	import showBox from '../components/showbox.vue'
	import noticeBar from '../components/noticeBar.vue' 
	import home from '@/components/home';
	import {toLogin} from '@/libs/login.js';
	import {mapGetters} from "vuex";
	import {HTTP_REQUEST_URL} from '@/config/app';
	const app = getApp();
	export default {
		components: {
			showBox,
			noticeBar,
			home
		},
		mixins: [colors],
		data() {
			return {
				inviteShow: true,
				loading: true,
				sel: 0,
				userList: [],
				agentInfoData: {},
				page: 1,
				limit: 5,
				total: 0,
				imgHost:HTTP_REQUEST_URL,
			}
		},
		computed: mapGetters(['isLogin']),
		watch: {
			isLogin: {
				handler: function(newV, oldV) {
					if (newV) {}
				},
				deep: true
			}
		},
		onLoad(option) {
			this.type = option.type;
			if (this.isLogin) {
				this.getAgentList(0);
				this.getAgentInfo();
			} else {
				toLogin();
			}
		},
		methods: {
			//#ifdef H5
			ShareInfo(data) {
				let href = location.href;
				if (this.$wechat.isWeixin()) {
					getUserInfo().then(res => {
						href = href.indexOf('?') === -1 ? href + '?spread=' + res.data.uid : href + '&spread=' +
							res.data.uid;
						let configAppMessage = {
							desc: data.name,
							title: data.name,
							link: href,
							imgUrl: data.image
						};
						this.$wechat
							.wechatEvevt(['updateAppMessageShareData', 'updateTimelineShareData',
								'onMenuShareAppMessage',
								'onMenuShareTimeline'
							], configAppMessage)
							.then(res => {})
							.catch(err => {});
					});
				}
			},
			//#endif
			getList(index) {
				this.sel = index;
				this.userList = [];
				this.page = 1;
				this.getAgentList(index);
			},
			invite() {
				uni.navigateTo({
					url: '/pages/users/user_spread_code/index'
				})
			},
			getAgentList(type) {
				agentUserList(type, this.page, this.limit).then(res => {
					this.total = res.data.count;
					let len = res.data.list.length;
					let userListNew = [];
					let userListData = res.data.list;
					userListNew = this.userList.concat(userListData);
					this.$set(this, 'userList', userListNew);
				})
			},
			getAgentInfo() {
				agentInfo().then(res => {
					this.agentInfoData = res.data;
				})
			},
			showAll: function() {
				this.page++;
				this.getAgentList(this.sel);
			},
		}
	}
</script>

<style lang="scss" scoped>
	@import "../static/click.css";

	.invite {
		background-color: #E74435;
		min-height: 100vh;
		padding: 0 0 80rpx 0;

		.invite-header {
			width: 100%;
			height: 584rpx;
			margin: 0;
			background-repeat: no-repeat;
			background-size: 100% 100%;
			z-index: 1;

			.swipers {
				width: 544rpx;
				height: 40rpx;
				line-height: 40rpx;
				background: rgba(183, 4, 0, 1);
				border-radius: 24rpx;
				margin: auto;
				overflow: hidden;
				position: absolute;
				left: 50%;
				top: 14%;
				transform: translate(-50%);

				/* 50%为自身尺寸的一半 */
				.line1 {
					text-align: center;
				}

				swiper {
					height: 100%;
					width: 100%;
					overflow: hidden;
					font-size: 24rpx;
					color: #FFFFFF;
				}

				.color_ye {
					color: #FFE39F;
				}
			}
		}

		.notice {
			position: absolute;
		}

		.box {
			margin: -100rpx 30rpx 160rpx 30rpx;
			width: 690rpx;
			background-color: #fff;
			z-index: 999;
			border-radius: 12rpx;

			.agreement {
				padding: 0 30rpx 30rpx 30rpx;
				word-wrap: break-word;
				text-align: justify;
				/deep/p{
					margin-bottom: 8rpx;
					line-height: 60rpx;
				}
			}

			.box-title-sty {
				background-color: #FEF7F6;
				padding-bottom: 42rpx;
				border-radius: 12rpx 12rpx 0 0;



				.benefit {
					text-align: center;
					color: #333333;
					font-size: 24rpx;

					.icon-zhu {
						color: #E93323;
						padding-right: 16rpx;
					}

					.num {
						color: #E93323;
						font-size: 54rpx;
						padding: 0 8rpx;
						font-weight: bold;
					}
				}
			}

			.white {
				background-color: #fff;
			}

			.tab {
				display: flex;
				padding: 36rpx;
				color: #E93323;
				font-size: 32rpx;

				.item {
					width: 100%;
					text-align: center;

					.line {
						margin: 20rpx auto 0 auto;
						width: 106rpx;
						height: 2px;
						border-radius: 1px;


					}

					.line.on {
						background: #E93323;
					}

					.on {
						font-weight: bold;
					}
				}
			}

			.no-thing {
				display: flex;
				align-items: center;
				justify-content: center;
				padding: 16rpx 0 52rpx;
				color: #333;

				.no-thing-img {
					width: 48rpx;
					height: 48rpx;

					image {
						width: 100%;
						height: 100%;
					}
				}

				.pl20 {
					padding-left: 20rpx;
				}
			}

			.list {
				.item {
					display: flex;
					justify-content: space-between;
					align-items: center;
					padding: 14rpx 30rpx;

					.item-l {
						display: flex;
						align-items: center;
						color: #333333;
						font-size: 28rpx;

						.avatar {
							width: 56rpx;
							height: 56rpx;
							margin-right: 18rpx;

							image {
								width: 100%;
								height: 100%;
								border-radius: 50%;
							}
						}
					}

					.item-r {
						color: #999999;
						font-size: 22rpx;
					}
				}

				.more {
					font-size: 24rpx;
					color: #282828;
					text-align: center;
					height: 90rpx;
					line-height: 90rpx;
				}
			}

			.box-title {
				transform: translateY(-20rpx);
				margin: 0 auto;
				width: 380rpx;
				height: 76rpx;
				background-repeat: no-repeat;
				background-size: cover;
				color: #fff;
				display: flex;
				align-items: center;
				justify-content: center;
				font-size: 36rpx;
				font-weight: 500;
			}
		}

		.footer {
			text-align: center;
			z-index: 999;
			width: 100%;
			background-color: #E93323;
			position: fixed;
			padding: 36rpx 30rpx;
			box-sizing: border-box;
			bottom: 0rpx;

			.trip {
				color: #999999;
				font-size: 24rpx;
				margin: 24rpx 0;
			}

			.click {
				width: 66rpx;
				height: 74rpx;
				position: absolute;
				right: 44rpx;
				bottom: 8rpx;

				image {
					width: 100%;
					height: 100%;
				}
			}

			.cancellation {
				height: 45px;
				color: #E93323;
				font-weight: bold;
				font-size: 36rpx;
				background: linear-gradient(180deg, #FFFCF6 0%, #FFE297 100%);
				border-radius: 25px;
			}
		}
	}

	// .mask {
	// 	position: fixed;
	// 	top: 0;
	// 	left: 0;
	// 	right: 0;
	// 	bottom: 0;
	// 	background-color: rgba(0, 0, 0, 0.8);
	// 	z-index: 9;
	// }

	// .share-box {
	// 	z-index: 1300;
	// 	position: fixed;
	// 	left: 0;
	// 	top: 0;
	// 	width: 100%;
	// 	height: 100%;

	// 	image {
	// 		width: 100%;
	// 		height: 100%;
	// 	}
	// }



	.no-invite {
		display: flex;
		justify-content: center;
		flex-direction: column;
		align-items: center;
		font-size: 28rpx;
		color: #ccc;
	}

	[v-cloak] {
		display: none;
	}
</style>
