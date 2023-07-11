<template>
	<view :style="colorStyle">
		<view class='my-promotion'>
			<view class="header header-height">
				<view class="user-msg">
					<view class="acator">
						<image :src="userInfo.avatar" mode=""></image>
					</view>
					<view class="msg">
						<view class="name">{{userInfo.nickname}}</view>
						<view class="process" v-if="levelList.length">
							<view :style="{width: `${speedAll}%`}" class="fill"></view>
						</view>
						<view class="level-info" v-if="levelList.length">
							<text class="mr20">一级上浮 {{levelInfo.one_brokerage}}%</text>
							<text>二级上浮 {{levelInfo.two_brokerage}}%</text>
						</view>
						<view v-else class="level-info">普通用户</view>
					</view>
					<view class="invite" @click="jumbPath(5)">
						<view class="poster-in">
							<image src="../static/gz.png" mode=""></image>
						</view>
						<text class="text">活动规则</text>
					</view>
				</view>
				<view class="tesk" v-if="levelList.length">
					<view class="tesk-l">
						<view class="upgrade">
							<image src="../static/sj.png" mode=""></image>
						</view>
						<view class="line"></view>
						<view class="">
							<view class="fx-leavel" @click="jumbPath(10)">
								<view class="">
									{{levelInfo.name}}
								</view>
								<view class="point"></view>
							</view>
							<view class="fx-trip">
								下单、邀请好友等均可提高等级
							</view>
						</view>
					</view>
					<view class="tesk-r" @click="taskShow = true">
						做任务
					</view>
				</view>
			</view>
			<view class="price-box" :class="!headerStatus ? 'header-height':''">
				<view class="box-top">
					<view class="" @click="jumbPath(6)">
						可提现金额
						<text class="iconfont icon-xiangyou"></text>
					</view>
					<view class="" @click="jumbPath(7)">
						提现记录
						<text class="iconfont icon-xiangyou"></text>
					</view>
				</view>
				<view class="com-count" @click="jumbPath(6)">
					{{userInfo.commissionCount}}
				</view>
				<view class="box-btn">
					<view class="item">
						<view class="text">
							待提现佣金
						</view>
						<view class="num">
							{{userInfo.brokerage_price}}
						</view>
					</view>
					<view class="item in">
						<view class="item-cn">
							<view class="text">
								已提现佣金
							</view>
							<view class="num">
								{{userInfo.extract_price}}
							</view>
						</view>

					</view>
					<view class="item in">
						<view class="item-cn">
							<view class="text">
								冻结佣金
							</view>
							<view class="num">
								{{userInfo.broken_commission}}
							</view>
						</view>
					</view>
				</view>
				<view class="btn" @click="jumbPath(0)">
					立即提现
				</view>
			</view>
			<view class="statistics">
				<view class="item mb" @click="jumbPath(9)">
					<view class="img">
						<text class="iconfont icon-wodetuandui"></text>
					</view>
					<view class="item-r">
						<view class="text">我的团队</view>
						<view class="trip">{{userInfo.spread_count || 0}}人</view>
					</view>
				</view>
				<view class="item mb" @click="jumbPath(8)">
					<view class="img">
						<text class="iconfont icon-fenxiaodingdan"></text>
					</view>
					<view class="item-r">
						<view class="text">分销订单</view>
						<view class="trip">{{userInfo.spread_order_count || 0}}笔</view>
					</view>
				</view>
				<view class="item mb" @click="jumbPath(5)">
					<view class="img">
						<text class="iconfont icon-yaoqinghaoyou1"></text>
					</view>
					<view class="item-r">
						<view class="text">邀请好友</view>
						<view class="trip">邀请好友赚奖励</view>
					</view>
				</view>
				<view class="item mb" @click="jumbPath(10)">
					<view class="img">
						<text class="iconfont icon-xingzhuangjiehe"></text>
					</view>
					<view class="item-r">
						<view class="text">等级说明</view>
						<view class="trip">分销等级说明</view>
					</view>
				</view>
				<view class="item" @click="jumbPath(2)">
					<view class="img">
						<text class="iconfont icon-yongjinpaihang1"></text>
					</view>
					<view class="item-r">
						<view class="text">佣金排行</view>
						<view class="trip">您的排名为{{userInfo.position_count}}</view>
					</view>
				</view>
				<view class="item" @click="jumbPath(1)">
					<view class="img">
						<text class="iconfont icon-tuiguangrenpaihang1"></text>
					</view>
					<view class="item-r">
						<view class="text">推广人排行</view>
						<view class="trip">您的排名为{{userInfo.rank_count}}</view>
					</view>
				</view>
			</view>
		</view>
		<task :inv-show="taskShow" :task="task" @inv-close="()=>{taskShow= false}"></task>
	</view>
</template>

<script>
	import {
		getUserInfo,
		agentLevelList,
		agentLevelTaskList,
		moneyList,
		spreadOrder,
		spreadPeople
	} from '@/api/user.js';
	import {
		openExtrctSubscribe
	} from '@/utils/SubscribeMessage.js';
	import {
		toLogin
	} from '@/libs/login.js';
	import commission from './components/commission.vue'
	import order from './components/order.vue'
	import promoter from './components/promoter.vue'
	import task from './components/task.vue'
	import {
		mapGetters
	} from "vuex";
	// #ifdef MP
	import authorize from '@/components/Authorize';
	// #endif
	import home from '@/components/home';
	import colors from '@/mixins/color.js'
	export default {
		components: {
			home,
			commission,
			order,
			promoter,
			task
		},
		mixins: [colors],
		data() {
			return {
				userInfo: {},
				taskShow: false,
				yesterdayPrice: 0.00,
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				distributionLevel: [],
				levelList: [],
				task: [],
				levelInfo: {},
				tabs: [{
					name: '佣金',
				}, {
					name: '订单'
				}, {
					name: '推广人'
				}],
				listData: [],
				sel: 0,
				speedAll: 0,
				headerStatus: false
			};
		},
		computed: mapGetters(['isLogin']),
		watch: {
			isLogin: {
				handler: function(newV, oldV) {
					if (newV) {
						this.getUserInfo();
					}
				},
				deep: true
			}
		},
		onLoad() {
			if (this.isLogin) {
				this.agentLevelList()
				// this.getUserInfo()
				this.clickTab(0);
			} else {
				toLogin();
			}
		},
		methods: {
			agentLevelList() {
				agentLevelList().then(res => {
					const {
						level_info,
						level_list,
						task,
						user
					} = res.data;
					this.levelInfo = level_info;
					this.userInfo = user;
					this.taskInfo = task;
					this.levelList = level_list;
					this.headerStatus = level_list.length ? true : false;
					this.level_id = level_info.id || 0;
					const index = level_list.findIndex((
							grade, v
						) =>
						grade.id === user.agent_level
					);
					console.log(index)
					if (index !== -1) {
						this.swiperIndex = index === -1 ? 0 : (index + 1);
					}
					this.level_id = this.levelList[index === -1 ? 0 : (index + 1)].id || 0;
					this.getTask()
				});
			},
			/**
			 * 获取任务要求
			 */
			getTask() {
				let that = this;
				that.taskNum = 0
				agentLevelTaskList(that.level_id).then(res => {
					that.task = res.data.list
					that.speedAll = res.data.speedAll
				});
			},
			onLoadFun() {
				this.getUserInfo();
			},
			//跳转
			jumbPath(type) {
				let path = [
					'/pages/users/user_cash/index',
					'/pages/users/promoter_rank/index',
					'/pages/users/commission_rank/index',
					'/pages/users/user_spread_code/index',
					'/pages/users/user_vip/index',
					'/pages/extension/invite_friend/index',
					'/pages/users/user_spread_money/index?type=1',
					'/pages/users/user_spread_money/index?type=4',
					'/pages/users/promoter-order/index',
					'/pages/users/promoter-list/index',
					'/pages/users/user_distribution_level/index',
				]

				uni.navigateTo({
					url: path[type]
				})
			},
			// 授权关闭
			authColse(e) {
				this.isShowAuth = e
			},
			openSubscribe(page) {
				uni.showLoading({
					title: '正在加载',
				})
				openExtrctSubscribe().then(res => {
					uni.hideLoading();
					uni.navigateTo({
						url: page,
					});
				}).catch(() => {
					uni.hideLoading();
				});
			},
			/**
			 * 获取个人用户信息
			 */
			getUserInfo() {
				let that = this;
				getUserInfo().then(res => {
					that.$set(that, 'userInfo', res.data);
					if (!res.data.spread_status) {
						that.$util.Tips({
							title: "您目前暂无推广权限"
						}, {
							tab: 4,
							url: '/pages/index/index'
						});
					}
				});
			},
			clickTab(index) {
				this.sel = index
				let mets = [moneyList, spreadOrder, spreadPeople]
				let data = {
					keyword: "",
					start: 0,
					stop: 0,
					page: 1,
					limit: 10
				}
				if (index == 2) {
					data = {
						...data,
						grade: 0,
						sort: '',
					}
				}
				mets[index](data, 3).then(res => {
					this.listData = res.data.list
				})
				// if (index == 0) {} else if (index == 1) {
				// 	this.getRecordOrderList()
				// } else {
				// 	this.userSpreadNewList()
				// }
			},
		}
	}
</script>

<style scoped lang="scss">
	.my-promotion {


		.header {
			background: #212230 url('../static/fxbg.png') no-repeat;
			background-size: 100% 100%;
			padding: 48rpx 30rpx;
			color: #fff;
			position: relative;
			height: 328rpx;

			.user-msg {
				display: flex;
				align-items: flex-start;
				width: 100%;

				.acator {
					width: 90rpx;
					height: 90rpx;
					margin-right: 20rpx;

					image {
						width: 100%;
						height: 100%;
						border-radius: 50%;
					}
				}

				.msg {
					display: flex;
					flex-direction: column;

					.name {
						font-size: 30rpx;
						font-weight: bold;
					}

					.process {
						width: 380rpx;
						height: 6rpx;
						border-radius: 6rpx;
						background: #4D4E59;
						margin: 20rpx 0;

						.fill {
							height: 100%;
							border-radius: 6rpx;
							background-color: #fff;
						}

					}

					.level-info {
						font-size: 20rpx;

						.mr20 {
							margin-right: 40rpx;
						}
					}
				}

				.invite {
					display: flex;
					align-items: center;
					position: absolute;
					right: 0rpx;
					background: rgba(255, 255, 255, 0.14);
					border-radius: 32px 0px 0px 32px;
					color: #FFFFFF;
					padding: 10rpx 16rpx 10rpx 8rpx;

					.poster-in {
						width: 20rpx;
						height: 20rpx;
						display: flex;
						align-items: center;
						margin-right: 8rpx;

						image {
							width: 100%;
							height: 100%;
						}
					}

					.text {
						font-size: 20rpx;
					}
				}
			}

			.tesk {
				position: absolute;
				bottom: 0;
				width: 690rpx;
				height: 128rpx;
				background: linear-gradient(135deg, #FEE8C7 0%, #FFBD6B 100%);
				border-radius: 6px 6px 0px 0px;
				padding: 24rpx 30rpx;
				display: flex;
				justify-content: space-between;
				align-items: center;

				.line {
					width: 1px;
					height: 74rpx;
					background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, #9B5900 52%, rgba(255, 255, 255, 0) 100%);
					opacity: 0.2;
					margin: 0 26rpx;
				}

				.tesk-l {
					display: flex;
					align-items: center;

					.fx-leavel {
						display: flex;
						align-items: center;
						color: #9B5900;
						font-size: 30rpx;
						font-weight: bold;

						.point {
							margin-left: 20rpx;
							width: 0;
							height: 0;
							border-top: 8rpx solid transparent;
							border-left: 8rpx solid #9B5900;
							border-bottom: 8rpx solid transparent;
						}
					}

					.fx-trip {
						color: #9B5900;
						font-size: 24rpx;
						margin-top: 14rpx;
					}

					.upgrade {
						width: 68rpx;
						height: 68rpx;

						image {
							width: 100%;
							height: 100%;
						}
					}
				}

				.tesk-r {
					padding: 10rpx 20rpx;
					color: #9B5900;
					font-size: 24rpx;
					font-weight: 400;
					text-align: center;
					background: #FFFFFF;
					border-radius: 40rpx;
				}
			}
		}

		.price-box.header-height {
			position: relative;
			margin-top: -150rpx;
		}

		.price-box {
			padding: 48rpx 32rpx;
			background-color: #fff;
			margin: 24rpx 30rpx;
			border-radius: 12px;

			.box-top {
				display: flex;
				justify-content: space-between;
				color: #777777;
				font-size: 24rpx;

				.icon-xiangyou {
					font-size: 24rpx;
				}
			}

			.com-count {
				color: #EB0000;
				font-size: 42rpx;
				font-weight: bold;
				margin-top: 16rpx;
			}

			.box-btn {
				display: flex;
				justify-content: space-between;
				margin-top: 44rpx;

				.in {
					display: flex;
					justify-content: center;
					align-items: center;
					flex-direction: column;
				}

				.item {
					width: 100%;

					.item-cn {
						display: flex;
						justify-content: center;
						flex-direction: column;
					}

					.text {
						color: #777777;
						font-size: 24rpx;
						margin-bottom: 20rpx;
					}

					.num {
						color: #333333;
						font-size: 36rpx;
						font-weight: bold;
					}
				}
			}

			.btn {
				display: flex;
				color: #9B5900;
				background: linear-gradient(135deg, #FEE8C7 0%, #FFBD6B 100%);
				border-radius: 46px;
				align-items: center;
				justify-content: center;
				padding: 32rpx 0;
				margin-top: 48rpx;
				font-size: 30rpx;
				line-height: 30rpx;
				font-weight: bold;
				margin: 48rpx 10rpx 0 10rpx;
			}
		}

		.statistics {
			display: flex;
			flex-wrap: wrap;
			background-color: #fff;
			margin: 24rpx 30rpx;
			border-radius: 12px;
			padding: 40rpx 46rpx 40rpx 0;

			.mb {
				margin-bottom: 64rpx;
			}

			.item {
				width: 50%;
				display: flex;
				align-items: center;
				padding-left: 50rpx;

				.img {
					width: 46rpx;
					height: 46rpx;
					margin-right: 22rpx;

					.iconfont {
						font-size: 40rpx !important;
						font-weight: bold;
					}

				}

				.item-r {
					.text {
						font-size: 26rpx;
						color: #333333;
						margin-bottom: 12rpx;
					}

					.trip {
						color: #999999;
						font-size: 22rpx;
					}
				}
			}
		}

		// ---------------------------------------
		.data {
			margin: 28rpx 30rpx;
			background-color: #fff;
			width: 690rpx;
			border-radius: 12rpx;

			.data-num {
				height: 168rpx;
				background: url('../static/data-num.png') no-repeat;
				background-size: 100% 100%;
				display: flex;
				align-items: center;
				justify-content: space-around;
				color: #fff;
				font-size: 24rpx;

				.num {}

				.num-color {
					margin-top: 20rpx;
					font-weight: bold;
					font-size: 36rpx;
				}
			}

			.data-money {
				display: flex;
				justify-content: space-between;
				color: #333;
				padding: 16rpx 30rpx;
				font-size: 24rpx;

				.money {
					display: flex;
					align-items: center;
					color: #333333;
				}

				.money-num {
					color: #E93323;
					font-size: 28rpx;
					font-weight: bold;
					padding-left: 20rpx;

				}

				.btn {
					width: 160rpx;
					background: linear-gradient(135deg, #FEA21F 0%, #FE7A18 100%);
					border-radius: 38rpx;
					color: #fff;
					text-align: center;
					padding: 16rpx 0;
					font-size: 26rpx;
				}
			}
		}

		.invites {
			width: 690rpx;
			margin: 28rpx 30rpx;
			background-color: #fff;
			border-radius: 12rpx;
			font-size: 26rpx;
			color: #333;
			padding: 40rpx 46rpx;

			.invite-list {
				display: flex;

				.item {
					margin-right: 48rpx;
					display: flex;
					flex-direction: column;
					align-items: center;

					.img {
						width: 60rpx;
						height: 60rpx;
						margin-bottom: 24rpx;

						image {
							width: 100%;
							height: 100%;
							border-radius: 50%;
						}
					}
				}
			}
		}

		.list {
			width: 690rpx;
			margin: 28rpx 30rpx;
			background-color: #fff;
			border-radius: 12rpx;
			font-size: 28rpx;

			.tab-list {
				display: flex;
				justify-content: space-between;
				padding: 32rpx 30rpx 0 30rpx;
				color: #999999;

				.tab {
					display: flex;

					.item {
						margin-right: 48rpx;
						transition: all 0.3s;

						.item-text {}

						.line {
							width: 54rpx;
							height: 4rpx;
							margin: 12rpx auto 0 auto;
							border-radius: 4px;
						}

						.line.on {
							background-color: #E93323;
						}
					}

					.item .on {
						font-size: 32rpx;
						font-weight: bold;
						color: #E93323;
					}
				}
			}

			.more {
				display: flex;
				align-items: center;
				font-size: 26rpx;

				.icon-xiangyou {
					font-size: 24rpx;
				}
			}
		}
	}
</style>
