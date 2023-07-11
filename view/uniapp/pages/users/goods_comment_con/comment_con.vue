<template>
	<view class="evaluateWtapper" :style="colorStyle">
		<view class="scroll-box">
			<scroll-view scroll-y="true" style="height: 100%;" :scroll-top="scrollTop">
				<view id='tops'>
					<view class="goods acea-row row-middle" v-if="replyCon.product" @click="details(replyCon.product.id)">
						<view class="pictrue">
							<image :src="replyCon.product.image"></image>
						</view>
						<view class="text line2">{{replyCon.product.store_name}}</view>
						<!-- <view class="cart acea-row row-center-wrapper" @click="details(replyCon.product.id)">
							<text class="iconfont icon-gouwuche7"></text>
						</view> -->
					</view>
					<view class="evaluateItem" v-if="replyCon.reply">
						<view class="pic-text acea-row row-between-wrapper">
							<view class="acea-row row-middle">
								<view class="pictrue">
									<image :src="replyCon.reply.avatar" mode="aspectFill"></image>
								</view>
								<view class="acea-row row-middle">
									<view class="acea-row row-middle" style="margin-right: 15rpx;">
										<view class="name line1">{{ replyCon.reply.nickname }}</view>
										<view class="vipImg" v-if="replyCon.user.is_money_level>0"><image src="../../../static/images/vip.png"></image></view>
									</view>
								</view>
							</view>
							<view class="start" :class="'star' + replyCon.star"></view>
						</view>
						<view class="time">{{ replyCon.reply.add_time }} {{ replyCon.reply.suk }}</view>
						<view class="evaluate-infor">{{ replyCon.reply.comment }}</view>
						<view class="imgList">
							<view class="pictrue" v-for="(item, index) in replyCon.reply.pics" :key="index">
								<image :src="item" class="image" @click='getpreviewImage(index)' mode="widthFix">
								</image>
							</view>
						</view>
						<view class="census acea-row row-between-wrapper">
							<view>浏览{{replyCon.reply.views_num}}次</view>
						</view>
					</view>
				</view>
				<view class="list" v-if="replyList.length">
					<view class="title">{{replyNum}}条回复</view>
					<view class="item" v-for="(item,index) in replyList" :key="index">
						<view class="info acea-row row-between-wrapper">
							<view class="picTxt acea-row row-middle">
								<view class="pictrue">
									<image :src="item.user.avatar" v-if="item.uid && item.user"></image>
									<image src="../static/store.png" v-if="!item.uid"></image>
								</view>
								<view class="text">
									<view class="acea-row row-middle">
										<view class="acea-row row-middle">
											<view class="name line1" :class="!item.uid?'on':''">{{item.user?item.user.nickname:'用户'}}</view>
											<view class="store" v-if="!item.uid">商家</view>
										</view>
										<view class="vipImg" v-if="item.uid && item.user && item.user.is_money_level>0"><image src="../../../static/images/vip.png"></image></view>
									</view>
									<view class="time">{{item.create_time}}</view>
								</view>
							</view>
							<view @click="praise(item)"><text class="iconfont" :class="item.is_praise?'icon-weizan font-num':'icon-zan'"></text>{{item.praise}}</view>
						</view>
						<view class="conter">{{item.content}}</view>
						<view class="item items" v-if="item.children">
							<view class="info acea-row row-between-wrapper">
								<view class="picTxt acea-row row-middle">
									<view class="pictrue">
										<image src="../static/store.png"></image>
									</view>
									<view class="text">
										<view class="acea-row row-middle">
											<view class="name line1">{{item.children.user.nickname}}</view>
											<view class="store">商家</view>
										</view>
										<view class="time">{{item.children.create_time}}</view>
									</view>
								</view>
								<view @click="praise(item.children)"><text class="iconfont" :class="item.children.is_praise?'icon-weizan font-num':'icon-zan'"></text>{{item.children.praise}}</view>
							</view>
							<view class="conter">{{item.children.content}}</view>
						</view>
					</view>
				</view>
			</scroll-view>	
		</view>
		<view class="footer-box">
			<view class="input-box">
				<input type="text" placeholder="说点什么呗~" v-model="con" confirm-type="send" @confirm="sendText" />
			</view>
			<view class="icons acea-row row-middle">
				<view class="item"><text class="iconfont icon-pinglun1"></text>{{replyNum}}</view>
				<view class="item" @click="tapPraise"><text class="iconfont" :class="replyCon.is_praise?'icon-weizan font-num':'icon-zan'"></text>{{replyCon.reply?replyCon.reply.praise:0}}</view>
			</view>
		</view>
		<home v-if="navigation"></home>
	</view>
</template>
<script>
	import {
		getReplyInfo,
		getReplyComment,
		postReplyPraise,
		replyComment,
		postUnReplyPraise,
		getReplyPraise,
		getUnReplyPraise
	} from '@/api/store.js';
	import home from '@/components/home';
	import colors from '@/mixins/color.js';
	export default {
		components: {
			home
		},
		mixins: [colors],
		data: function() {
			return {
				id: 0,
				page:1,
				limit:200,
				replyCon: {},
				replyList: [],
				con:'',
				scrollTop:0,
				replyNum:0
			};
		},
		onLoad(options) {
			this.id = options.id
			this.getInfo();
			this.getList();
		},
		methods: {
			tapPraise(){
				if (this.replyCon.is_praise) {
					getUnReplyPraise(this.id).then(res => {
						this.replyCon.is_praise = !this.replyCon.is_praise
						this.replyCon.reply.praise = this.replyCon.reply.praise - 1
						return this.$util.Tips({
							title: res.msg
						});
					});
				} else {
					getReplyPraise(this.id).then(res => {
						this.replyCon.is_praise = !this.replyCon.is_praise
						this.replyCon.reply.praise = this.replyCon.reply.praise + 1
						return this.$util.Tips({
							title: res.msg
						});
					});
				}
			},
			// 设置页面滚动位置
			setPageScrollTo() {
				let view = uni
					.createSelectorQuery()
					.in(this)
					.select('#tops');
				view.boundingClientRect(res => {
					this.scrollTop = parseFloat(res.height);
				}).exec();
			},
			sendText(){
				if(!this.con.trim()){
					return this.$util.Tips({
						title: '说点什么呗'
					});
				}
				replyComment(this.id,{content:this.con}).then(res=>{
					let that = this;
					this.con = '';
					this.replyNum = this.replyNum+1;
					this.getList();
					this.$nextTick(() => {
						setTimeout(function(){
							that.setPageScrollTo();
						},100)
					});
					this.$util.Tips({
						title: res.msg
					});
				})
			},
			details(id){
				uni.navigateTo({
					url: '/pages/goods_details/index?id=' + id
				})
			},
			getInfo() {
				getReplyInfo(this.id).then(res => {
					this.replyCon = res.data;
					this.replyNum = this.replyCon.reply.comment_sum;
				})
			},
			getList(){
				getReplyComment(this.id,{
					page: this.page,
					limit: this.limit
				}).then(res=>{
					this.replyList = res.data
				}).catch(err=>{
					return this.$util.Tips({
						title: err.msg
					});
				})
			},
			getpreviewImage: function(index) {
				uni.previewImage({
					urls: this.replyCon.reply.pics,
					current: this.replyCon.reply.pics[index]
				});
			},
			praise(item) {
				if (item.is_praise) {
					postUnReplyPraise(item.id).then(res => {
						item.is_praise = !item.is_praise
						item.praise = item.praise - 1
						return this.$util.Tips({
							title: res.msg
						});
					});
				} else {
					postReplyPraise(item.id).then(res => {
						item.is_praise = !item.is_praise
						item.praise = item.praise + 1
						return this.$util.Tips({
							title: res.msg
						});
					});
				}
			}
		}
	}
</script>
<style scoped lang='scss'>
	.vipImg {
		width: 56rpx;
		height: 20rpx;
		margin-left: 10rpx;

		image {
			width: 100%;
			height: 100%;
			display: block;
		}
	}

	.evaluateWtapper {
		display: flex;
		flex-direction: column;
		height: 100vh;
		.scroll-box{
			flex: 1;
			overflow: hidden;
		}
		.goods {
			margin: 0 20rpx;
			padding: 24rpx 0;
			position: relative;
			.pictrue {
				width: 108rpx;
				height: 108rpx;
				border-radius: 12rpx;
				margin-right: 24rpx;

				image{
					width: 100%;
					height: 100%;
					border-radius: 12rpx;
				}
			}
			.text{
				width: 560rpx;
				font-size: 26rpx;
				color: #333;
			}
			.cart{
				width: 60rpx;
				height: 60rpx;
				border-radius: 50%;
				background-color: #fff;
				position: absolute;
				right: 20rpx;
				top:50%;
				margin-top: -30rpx;
			}
			.iconfont{
				font-size: 40rpx;
				color: var(--view-theme);
			}
		}
		.list{
			margin: 0 20rpx 30rpx 20rpx;
			padding: 28rpx 20rpx 0 20rpx;
			background-color: #fff;
			border-radius: 12rpx;
			.title{
				font-size: 30rpx;
				color: #333;
			}
			.item{
				padding: 28rpx 0 30rpx 0;
				.store{
					background-color: var(--view-theme);
					font-size: 18rpx;
					color: #fff;
					border-radius: 8rpx;
					padding: 2rpx 5rpx;
					margin-left: 10rpx;
				}
				&~.item{
					border-top: 1rpx solid #eee;
				}
				&.items{
					background-color: #F5F5F5;
					border-radius: 8rpx;
					margin-top: 22rpx;
					padding: 24rpx;
					.info .picTxt .text .name{
						max-width: 230rpx;
						color: var(--view-theme);
					}
				}
				.info{
					.picTxt{
						.pictrue{
							width: 56rpx;
							height: 56rpx;
							border-radius: 50%;
							margin-right: 20rpx;
							image{
								width: 100%;
								height: 100%;
								border-radius: 50%;
							}
						}
						.text{
							font-size: 20rpx;
							color: #999999;
							.name{
								max-width: 330rpx;
								margin-right: 4rpx;
								color: #333;
								&.on{
									color: var(--view-theme);
								}
							}
						}
					}
					.iconfont{
						margin-right: 6rpx;
					}
				}
				.conter{
					margin-top: 24rpx;
					word-break: break-all;
				}
			}
		}
		.footer-box{
			display: flex;
			align-items: center;
			padding: 0 20rpx;
			color: rgba(0, 0, 0, 0.8);
			background: #fff;
			height: 96rpx;
			height: calc(96rpx+ constant(safe-area-inset-bottom)); ///兼容 IOS<11.2/
			height: calc(96rpx + env(safe-area-inset-bottom)); ///兼容 IOS>11.2/
			.icons{
				width: 217rpx;
				.item{
					margin-left: 42rpx;
					.iconfont{
						margin-right: 6rpx;
					}
				}
			}
			.input-box {
				display: flex;
				align-items: center;
				flex: 1;
				height: 64rpx;
				padding-right: 5rpx;
				background-color: #eee;
				border-radius: 32rpx;
			
				input {
					flex: 1;
					padding-left: 20rpx;
					height: 100%;
					font-size: 28rpx;
					font-weight: normal;
				}
			
				.icon-fasong {
					font-size: 50rpx;
					color: #ccc;
					font-weight: normal;
				}
			}
		}
	}

	.evaluateWtapper .census {
		padding: 0 20rpx;
		font-size: 22rpx;
		color: #999;
	}

	.evaluateWtapper .census .iconfont {
		font-size: 26rpx;
		margin-right: 6rpx;
	}

	.evaluateWtapper .census .icons {
		color: #333;
	}

	.evaluateWtapper .census .icon {
		margin-left: 40rpx;
	}

	.evaluateWtapper .evaluateItem {
		background-color: #fff;
		padding-bottom: 25rpx;
		margin: 0 20rpx 20rpx 20rpx;
		border-radius: 12rpx;
	}

	.evaluateWtapper .evaluateItem~.evaluateItem {
		/* border-top: 1rpx solid #f5f5f5; */
	}

	.evaluateWtapper .evaluateItem .pic-text {
		font-size: 26rpx;
		color: #282828;
		height: 95rpx;
		padding: 0 20rpx;
	}

	.evaluateWtapper .evaluateItem .pic-text .pictrue {
		width: 56rpx;
		height: 56rpx;
		margin-right: 20rpx;
	}

	.evaluateWtapper .evaluateItem .pic-text .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 50%;
	}

	.evaluateWtapper .evaluateItem .pic-text .name {
		max-width: 450rpx;
	}

	.evaluateWtapper .evaluateItem .time {
		font-size: 24rpx;
		color: #82848f;
		padding: 0 20rpx;
	}

	.evaluateWtapper .evaluateItem .evaluate-infor {
		font-size: 28rpx;
		color: #282828;
		margin-top: 19rpx;
		padding: 0 20rpx;
	}

	.evaluateWtapper .evaluateItem .imgList {
		padding: 0 20rpx 0 6rpx;
		margin-top: 25rpx;
	}

	.evaluateWtapper .evaluateItem .imgList .pictrue {
		width: 100%;
		margin: 0 0 15rpx 15rpx;
		border-radius: 12rpx;
	}

	.evaluateWtapper .evaluateItem .imgList .pictrue image {
		width: 100%;
		height: 100%;
		background-color: #f7f7f7;
		border-radius: 12rpx;
	}
</style>
