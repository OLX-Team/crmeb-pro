<template>
	<view class="evaluateWtapper">
		<view class="evaluateItem" v-for="(item, indexw) in reply" :key="indexw" @click.stop="details(item)">
			<view class="pic-text acea-row row-between-wrapper">
				<view class="acea-row row-middle">
					<view class="pictrue">
						<image :src="item.avatar" mode="aspectFill"></image>
					</view>
					<view class="acea-row row-middle">
						<view class="acea-row row-middle" style="margin-right: 15rpx;">
							<view class="name line1">{{ item.nickname }}</view>
							<view class="vipImg" v-if="item.is_money_level>0"><image src="../../static/images/vip.png"></image></view>
						</view>
					</view>
				</view>
				<view class="start" :class="'star' + item.star"></view>
			</view>
			<view class="time">{{ item.add_time }} {{ item.suk }}</view>
			<view class="evaluate-infor">{{ item.comment }}</view>
			<view class="imgList acea-row">
				<view class="pictrue" :class="item.pics.length==1?'one':item.pics.length==2?'two':item.pics.length==3?'three':''" v-for="(itemn, indexn) in item.pics" :key="indexn">
					<image :src="itemn" class="image" @click.stop='getpreviewImage(indexw, indexn)' mode="aspectFill"></image>
				</view>
			</view>
			<view class="census acea-row row-between-wrapper" v-if="!fromTo">
				<view>浏览{{item.views_num}}次</view>
				<view class="icons acea-row row-middle">
					<view>
						<text class="iconfont icon-pinglun1"></text>{{item.replyComment?item.replyComment.sum:0}}
					</view>
					<view @click.stop="praise(item)">
						<text class="icon iconfont" :class="item.is_praise?'icon-weizan font-num':'icon-zan'"></text>{{item.praise}}</view>
					</view>
			</view>
			<view class="reply" v-if="item.replyComment && !fromTo">
				<text :class="item.replyComment.uid?'':'font-num'">{{item.replyComment.user?item.replyComment.user.nickname:''}}</text><text class="store" v-if="!item.replyComment.uid">商家</text>：{{
          item.replyComment.content
        }}
			</view>
		</view>
	</view>
</template>
<script>
	import {
		getReplyPraise,
		getUnReplyPraise
	} from '@/api/store.js';
	export default {
		props: {
			reply: {
				type: Array,
				default: () => []
			},
			fromTo: {
				type: Number,
				default: 0
			}
		},
		data: function() {
			return {};
		},
		methods: {
			details(item){
				uni.navigateTo({
					url: '/pages/users/goods_comment_con/comment_con?id=' + item.id
				})
			},
			getpreviewImage: function(indexw, indexn) {
				uni.previewImage({
					urls: this.reply[indexw].pics,
					current: this.reply[indexw].pics[indexn]
				});
			},
			praise(item){
				if (item.is_praise) {
					getUnReplyPraise(item.id).then(res => {
						item.is_praise = !item.is_praise
						item.praise = item.praise - 1
						return this.$util.Tips({
							title: res.msg
						});
					});
				} else {
					getReplyPraise(item.id).then(res => {
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
	.vipImg{
		width: 56rpx;
		height: 20rpx;
		margin-left: 10rpx;
		image{
			width: 100%;
			height: 100%;
			display: block;
		}
	}
	.evaluateWtapper .census{
		padding: 0 20rpx;
		font-size: 22rpx;
		color: #999;
	}
	.evaluateWtapper .census .iconfont{
		margin-right: 6rpx;
	}
	.evaluateWtapper .census .icons{
		color: #333;
	}
	.evaluateWtapper .census .icon{
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
		word-break: break-all;
	}

	.evaluateWtapper .evaluateItem .imgList {
		padding: 0 20rpx 0 6rpx;
		margin-top: 25rpx;
	}

	.evaluateWtapper .evaluateItem .imgList .pictrue {
		width: 156rpx;
		height: 156rpx;
		margin: 0 0 15rpx 15rpx;
		border-radius: 12rpx;
	}
	
	.evaluateWtapper .evaluateItem .imgList .pictrue.one{
		width: 400rpx;
		height: 400rpx;
	}
	
	.evaluateWtapper .evaluateItem .imgList .pictrue.two{
		width: 324rpx;
		height: 324rpx;
	}
	
	.evaluateWtapper .evaluateItem .imgList .pictrue.three{
		width: 214rpx;
		height: 214rpx;
	}

	.evaluateWtapper .evaluateItem .imgList .pictrue image {
		width: 100%;
		height: 100%;
		background-color: #f7f7f7;
		border-radius: 12rpx;
	}

	.evaluateWtapper .evaluateItem .reply {
		font-size: 26rpx;
		color: #454545;
		background-color: #f7f7f7;
		border-radius: 5rpx;
		margin: 20rpx 30rpx 0 30rpx;
		padding: 20rpx;
		position: relative;
		word-break: break-all;
		.store{
			background-color: var(--view-theme);
			font-size: 12rpx;
			color: #fff;
			border-radius: 15rpx;
			padding: 2rpx 5rpx;
			margin-left: 10rpx;
		}
	}

	.evaluateWtapper .evaluateItem .reply::before {
		content: "";
		width: 0;
		height: 0;
		border-left: 20rpx solid transparent;
		border-right: 20rpx solid transparent;
		border-bottom: 30rpx solid #f7f7f7;
		position: absolute;
		top: -14rpx;
		left: 40rpx;
	}
</style>
