<template>
	<view class="group_info">
		<!-- #ifdef H5 -->
		<view class="top_bg"></view>
		<view class="header_card acea-row row-middle">
			<view class="pic">
				<text class="iconfont icon-qunliao"></text>
			</view>
			<view class="name">
				<view class="group_name">{{chatInfo.name}}</view>
				<view class="desc">创建时间：{{chatInfo.group_create_time}}</view>
			</view>
		</view>
		<view class="static acea-row row-around row-middle">
			<view class="item">
				<view class="num">{{chatInfo.member_num}}</view>
				<view class="title">当前群成员</view>
			</view>
			<view class="item v_line">
				<view class="num">{{chatInfo.todaySum}}</view>
				<view class="title">今日入群</view>
			</view>
			<view class="item v_line">
				<view class="num">{{chatInfo.retreat_group_num}}</view>
				<view class="title">累计退群</view>
			</view>
		</view>
		<view class="list">
			<view class="search">
				<input type="text" v-model="name" 
				class="search" placeholder="点击搜索客户名称" 
				placeholder-class="pla_name" @blur="search()">
			</view>
			<view class="item" v-for="(item,index) in userList" :key="index" @click="toInfo(item)">
				<view class="item-info acea-row row-between row-top" >
					<view class="pictrue">
						<image v-if="item.type == 1 && item.member" :src="item.member.avatar"></image>
						<image v-if="item.type == 2 && item.client" :src="item.client.avatar"></image>
					</view>
					<view class="conten">
						<view class="name line1" v-if="item.type == 1 && item.member">{{item.member.name}}
							<text class="iconfont" :class="item.member.gender == 1 ? 'icon-xingbie-nan' : 'icon-xingbie-nv'"></text> 
						</view>
						<view class="name line1" v-if="item.type == 2 && item.client">{{item.client.name}}
							<text class="iconfont" :class="item.client.gender == 1 ? 'icon-xingbie-nan' : 'icon-xingbie-nv'"></text>
						</view>
						<text class="label" v-if="item.type == 1">{{item.userid == chatInfo.owner ? "群主" : "成员"}}</text>
						<text class="label" v-if="item.type == 2">客户</text>
						<text class="label_qita">其他所在群{{item.group_chat_num}}个</text>
					</view>
					<view class="time">{{item.join_time}}</view>
				</view>
				<view class="desc line1" v-if="item.tags && item.tags.length">
					标签：<text v-for="(item1,index1) in item.tags" :key="index1">{{item1}},</text>
				</view>
				<view class="desc line1" v-else>
					标签：暂无
				</view>
			</view>
			<Loading :loaded="loaded" :loading="loading"></Loading>
		</view>
		<!-- #endif -->
	</view>
</template>

<script>
	// #ifdef H5
	import { initWxConfig,initAgentConfig } from "@/libs/work.js";
	import {getWorkGroupInfo,getWorkGroupMember} from "@/api/work.js"
	import Loading from '@/components/Loading/index.vue';
	// import {wx} from "@/utils/agent.js"
	export default{
		data() {
			return {
				// chat_id:"wrPuqMEwAARnzYsua0WJgATVYu4b3iUg",
				chat_id:"",
				chatInfo:{},
				loaded: false,
				loading: false, //是否加载中
				loadend: false, //是否加载完毕
				loadTitle: '加载更多', //提示语
				userList: [], //数组
				page: 1,
				limit: 20,
				name:"",
			}
		},
		components:{Loading},
		onLoad(e) {
			if(e.back){
				this.chat_id = this.$Cache.get('chatId');
				if(this.$Cache.get('chatId')){
					this.getInfo();
				}else{
					uni.navigateBack();
					return this.$util.Tips({
						title: "缺少参数"
					});
					
				}
				
			}else{
				initWxConfig().then((jWeixin) => {
					// initAgentConfig().then(res=>{
						this.getChatID();
					// })
				}).catch((err) => {
				    return that.$util.Tips({
				    	title: err
				    });
				});
			}
			// this.getInfo();
		},
		methods:{
			getChatID(){
				if (/(iPhone|iPad|iPod|iOS|macintosh|mac os x)/i.test(navigator.userAgent)){
					wx.invoke('getContext', {}, (res)=>{
						if(res.err_msg == "getContext:ok"){
							let entry  = res.entry ; 
							//返回进入H5页面的入口类型，
							//目前有normal、contact_profile、single_chat_tools、group_chat_tools、chat_attachment
							wx.invoke('getCurExternalChat', {entry}, (response)=>{
								if(response.err_msg == "getCurExternalChat:ok"){
									this.chat_id = response.chatId;
									this.getInfo();
								}
							});
						}
					});
				}else{
					jWeixin.invoke('getContext', {}, (res)=>{
						if(res.err_msg == "getContext:ok"){
							let entry  = res.entry ; 
							//返回进入H5页面的入口类型，
							//目前有normal、contact_profile、single_chat_tools、group_chat_tools、chat_attachment
							jWeixin.invoke('getCurExternalChat', {entry}, (response)=>{
								if(response.err_msg == "getCurExternalChat:ok"){
									this.chat_id = response.chatId;
									this.getInfo();
								}
							});
						}
					});
				}
				
			},
			getInfo(){
				getWorkGroupInfo({chat_id:this.chat_id}).then(res=>{
					this.chatInfo = res.data;
					this.getList();
				}).catch(err=>{
					return this.$util.Tips({
						title: err
					});
				})
			},
			getList(){
				let that = this;
				if (that.loadend) return;
				if (that.loading) return;
				that.loading = true;
				that.loadTitle = '加载更多';
				getWorkGroupMember(that.chatInfo.id,{
					page:this.page,
					limit:this.limit,
					name:this.name
				}).then(res=>{
					let list = res.data.list || [];
					let loadend = list.length < that.limit;
					that.userList = that.$util.SplitArray(list, that.userList);
					that.$set(that, 'userList', that.userList);
					that.loadend = loadend;
					that.loading = false;
					that.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
					that.page = that.page + 1;
				}).catch(err=>{
					return that.$util.Tips({
						title: err
					});
				})
			},
			search(){
				if(this.loading) return
				this.loadend = false;
				this.page = 1;
				this.$set(this, 'userList', []);
				this.getList();
			},
			toInfo(item){
				if(item.type == 2){
					this.$Cache.set('chatId',this.chat_id);
					uni.navigateTo({
						url:"/pages/work/userInfo/index?userid=" + item.userid
					})
				}
			}
		},
		onReachBottom: function() {
			this.getList();
		}
	}
	// #endif
</script>

<style lang="scss">
	/* #ifdef H5 */
	.group_info{
		.top_bg{
			width: 750rpx;
			height: 150rpx;
			background: #1890FF;
		}
		.header_card{
			width: 710rpx;
			height: 168rpx;
			margin: -126rpx auto 0;
			background: #FFFFFF;
			border-radius: 12rpx;
			padding: 28rpx 24rpx;
			.pic{
				width: 112rpx;
				height: 112rpx;
				border-radius: 50%;
				background: #E1F1FF;
				text-align: center;
				line-height: 112rpx;
				.iconfont{
					font-size: 50rpx;
					color: #1890FF;
				}
			}
			.name{
				margin-left:20rpx;
				.group_name{
					height: 44rpx;
					font-size: 32rpx;
					font-weight: 500;
					color: rgba(0, 0, 0, 0.85);
					line-height: 44rpx;
				}
				.desc{
					height: 32rpx;
					font-size: 22rpx;
					font-weight: 400;
					color: rgba(102, 102, 102, 0.85);
					line-height: 32rpx;
					margin-top: 6rpx;
				}
			}
		}
		.static{
			width: 710rpx;
			height: 172rpx;
			background: #FFFFFF;
			border-radius: 12rpx;
			margin: 20rpx auto 0;
			.item{
				text-align: center;
				width: 33.3%;
				.num{
					height: 56rpx;
					font-size: 40rpx;
					font-family: PingFangSC-Medium, PingFang SC;
					font-weight: 500;
					color: rgba(0, 0, 0, 0.85);
					line-height: 56rpx;
				}
				.title{
					height: 32rpx;
					font-size: 22rpx;
					font-family: PingFangSC-Regular, PingFang SC;
					font-weight: 400;
					color: rgba(102, 102, 102, 0.85);
					line-height: 32rpx;
					margin-top: 12rpx;
				}
			}
			.v_line{
				position: relative;
				&::before{
					content: '';
					position: absolute;
					left: 0;
					top:23rpx;
					width: 1px;
					height: 80rpx;
					background-color: #eee;
				}
			}
		}
		.list{
			width: 710rpx;
			margin: 20rpx auto 0;
			background: #FFFFFF;
			border-radius: 12rpx;
			padding: 30rpx 24rpx;
			.search{
				width: 642rpx;
				height: 64rpx;
				margin:0 auto 0;
				line-height: 64rpx;
				background: #F5F6F9;
				border-radius: 32rpx;
				padding-left:20rpx ;
				font-size: 28rpx;
			}
			.pla_name{
				font-size: 28rpx;
				text-align: center;
				font-weight: 400;
				color: #CCCCCC;
			}
			.item {
				margin-top: 32rpx;
				padding-bottom: 28rpx;
				border-bottom: 1px solid #eee;
			}
			
			.item-info .pictrue {
				width: 100rpx;
				height: 100rpx;
			}
			
			.item-info .pictrue image {
				width: 100%;
				height: 100%;
				border-radius: 8rpx;
			}
			.conten{
				width: 296rpx;
				margin-left:22rpx;
				.name{
					height: 42rpx;
					font-size: 30rpx;
					font-weight: 600;
					color: #000;
					line-height: 42rpx;
				}
				.iconfont{
					font-size: 26rpx;
					font-weight: 400;
					display: inline-block;
					margin-left: 8rpx;
				}
				.icon-xingbie-nan{
					color: #1890FF;
				}
				.icon-xingbie-nv{
					color: #E369A2;
				}
				.label{
					display: inline-block;
					margin:10rpx 12rpx 0 0;
					height: 38rpx;
					padding: 0 12rpx 0;
					background: rgba(24, 144, 255, 0.1);
					border-radius: 4rpx;
					border: 1px solid #1890FF;
					// line-height: 44rpx;
					text-align: center;
					font-size: 24rpx;
					color: #1890FF;
				}
				.label_qita{
					display: inline-block;
					margin:10rpx 12rpx 0 0;
					height: 38rpx;
					padding: 0 12rpx 0;
					border-radius: 4rpx;
					border: 1rpx solid #ccc;
					// line-height: 44rpx;
					text-align: center;
					font-size: 24rpx;
					color: #999;
				}
			}
			.time{
				font-size: 24rpx;
			}
			.desc{
				width: 460rpx;
				height: 34rpx;
				font-size: 24rpx;
				font-family: PingFangSC-Regular, PingFang SC;
				font-weight: 400;
				color: rgba(0, 0, 0, 0.85);
				line-height: 34rpx;
				margin-top: 24rpx;
			}
		}
	}
	/* #endif */
</style>
