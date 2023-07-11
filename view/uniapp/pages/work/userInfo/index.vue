<template>
	<view class="workInfo">
		<!-- #ifdef H5 -->
		<view class="pad30" v-show="isShow">
			<view class='default acea-row row-middle borderRadius15'>
				<image :src="userInfo.avatar" mode="aspectFit" class="avatar"></image>
				<view class="acea-row row-middle">
					<view class="nick_name">{{userInfo.name}}</view>
				</view>
			</view>
			<view class='list acea-row row-middle borderRadius15'>
				<view class='item acea-row row-between-wrapper'>
					<view class='left_name'>手机号</view>
					<view class='right_name' v-if="userInfo.userInfo.phone">{{userInfo.userInfo.phone}}</view>
					<view class='right_name' v-else>暂无</view>
				</view>
				<view class='item acea-row row-between-wrapper'>
					<view class='left_name'>分组</view>
					<view class='right_name' v-if="userInfo.userInfo.userGroup">{{userInfo.userInfo.userGroup.group_name}}</view>
					<view class='right_name' v-else>暂无</view>
				</view>
				<view class='item acea-row row-between'>
					<view class='left_name'>用户标签</view>
					<view class='label acea-row' v-if="userInfo.userInfo.label && userInfo.userInfo.label.length">
						<view class="label_bdg acea-row row-middle row-center" 
						v-for="(item,index) in userInfo.userInfo.label" :key="index">{{item.label_name}}</view>
					</view>
					<view class='right_name' v-else>暂无</view>
				</view>
			</view>
			<view class='list acea-row row-middle borderRadius15'>
				<view class='item acea-row row-between-wrapper'>
					<view class='left_name'>会员等级</view>
					<view class='right_name' v-if="userInfo.userInfo.level">{{userInfo.userInfo.level}}</view>
					<view class='right_name' v-else>暂无</view>
				</view>
				<view class='item acea-row row-between-wrapper'>
					<view class='left_name'>推荐人</view>
					<view class='right_name' v-if="userInfo.userInfo.spreadUser">{{userInfo.userInfo.spreadUser.nickname}}</view>
					<view class='right_name' v-else>暂无</view>
				</view>
				<view class='item acea-row row-between-wrapper'>
					<view class='left_name'>用户类型</view>
					<view class='right_name' v-if="userInfo.userInfo.user_type">{{userInfo.userInfo.user_type | user_type}}</view>
					<view class='right_name' v-else>暂无</view>
				</view>
				<view class='item acea-row row-between-wrapper'>
					<view class='left_name'>余额</view>
					<view class='right_name' v-if="userInfo.userInfo.now_money">{{userInfo.userInfo.now_money}}</view>
					<view class='right_name' v-else>暂无</view>
				</view>
				<view class='item acea-row row-between-wrapper'>
					<view class='left_name'>推广员</view>
					<view class='right_name'>{{userInfo.userInfo.spread_open ? '是' : '否'}}</view>
				</view>
				<view class='item acea-row row-between-wrapper'>
					<view class='left_name'>生日</view>
					<view class='right_name' v-if="userInfo.userInfo.birthday">{{userInfo.userInfo.birthday}}</view>
					<view class='right_name' v-else>暂无</view>
				</view>
			</view>
			<view style="height: 100px;"></view>
			<tNav :active="0"></tNav>
			<view class="tui-fab-box tui-fab-right" @click="groupBack()" v-if="backGroup">
				<text class="iconfont icon-fanhui3"></text>
			</view>
		</view>
		<!-- #endif -->
	</view>
</template>
<script>
	// #ifdef H5
	import { initWxConfig,initAgentConfig } from "@/libs/work.js";
	import {getWorkAgentInfo} from "@/api/work.js";
	import tNav from '../components/tabNav.vue';
	// import {wx} from "@/utils/agent.js"
	export default{
		data() {
			return {
				userId:"",
				isShow:false,
				userInfo:{
					userInfo:{
						real_name:"",
						level:"",
						user_type:"",
						now_money:"",
						spread_open:"",
						birthday:"",
						userGroup:{},
						label:[],
					}
				},
				backGroup:false
			}
		},
		filters:{
			user_type(val){
				if(val =='wechat'){
					return '公众号'
				}else if(val == 'routine'){
					return '小程序'
				}else if(val == 'h5'){
					return 'H5'
				}else if(val == 'app'){
					return 'APP'
				}
			}
		},
		components:{tNav},
		onLoad(e) {
			this.$Cache.clear('work_user_id')
			if(e.userid){
				this.userId = e.userid;
				this.backGroup = true;
				this.$Cache.set('work_user_id',e.userid)
				this.getInfo();
			}else{
				initWxConfig().then((jWeixin) => {
					this.getUserID();
				}).catch((err) => {
					console.log(err);
					return this.$util.Tips({
						title: err
					});
				});
			}
			// this.getInfo();
		},
		methods:{
			getUserID(){
				if (/(iPhone|iPad|iPod|iOS|macintosh|mac os x)/i.test(navigator.userAgent)){
					wx.invoke('getContext', {}, (res)=>{
						if(res.err_msg == "getContext:ok" && res.entry == "single_chat_tools"){
							let entry  = res.entry ; 
							//返回进入H5页面的入口类型，
							//目前有normal、contact_profile、single_chat_tools、group_chat_tools、chat_attachment
							wx.invoke('getCurExternalContact', {entry}, (response)=>{
								if(response.err_msg == "getCurExternalContact:ok"){
									//返回当前外部联系人userId
									this.userId = response.userId;
									this.$Cache.set('work_user_id',response.userId)
									this.getInfo();
								}
							});
						}else if(res.err_msg == "getContext:ok" && res.entry == "group_chat_tools"){
							uni.reLaunch({
								url:"/pages/work/groupInfo/index"
							})
						}
					});
				}else{
					jWeixin.invoke('getContext', {}, (res)=>{
						if(res.err_msg == "getContext:ok" && res.entry == "single_chat_tools"){
							let entry  = res.entry ; 
							//返回进入H5页面的入口类型，
							//目前有normal、contact_profile、single_chat_tools、group_chat_tools、chat_attachment
							jWeixin.invoke('getCurExternalContact', {entry}, (response)=>{
								if(response.err_msg == "getCurExternalContact:ok"){
									//返回当前外部联系人userId
									this.userId = response.userId;
									this.$Cache.set('work_user_id',response.userId)
									this.getInfo();
								}
							});
						}else if(res.err_msg == "getContext:ok" && res.entry == "group_chat_tools"){
							uni.reLaunch({
								url:"/pages/work/groupInfo/index"
							})
						}
					});
				}
				
			},
			getInfo(){
				getWorkAgentInfo({
					userid:this.userId,
				}).then(res=>{
					this.isShow = true;
					this.userInfo = res.data;
				}).catch(err=>{
					return this.$util.Tips({
						title: err
					});
				})
			},
			groupBack(){
				uni.navigateTo({
					url:"/pages/work/groupInfo/index?back=1"
				})
			}
		}
	}
	// #endif
</script>
<style lang="scss">
	/* #ifdef H5 */
	.workInfo{
		
	}
	.default {
		padding: 0 24rpx;
		height: 154rpx;
		background-color: #fff;
		margin-top: 24rpx;
	}
	.list {
		background-color: #fff;
		margin-top: 24rpx;
		.item{
			width: 100%;
			padding: 30rpx;
			.left_name{
				color: #666666;
				font-size: 30rpx;
			}
			.right_name{
				color: #333333;
				font-size: 30rpx;
			}
			.label{
				width: 440rpx;
			}
			.label_bdg{
				height: 44rpx;
				padding:4rpx 8rpx;
				background: rgba(24, 144, 255, 0.1);
				border-radius: 2px;
				margin-left: 16rpx;
				margin-bottom: 16rpx;
				font-size: 24rpx;
				font-family: PingFangSC-Regular, PingFang SC;
				font-weight: 400;
				color: #1890FF;
			}
		}
	}
	.avatar{
		width: 84rpx;
		height: 84rpx;
		border-radius: 50%;
	}
	.nick_name{
		font-size: 32rpx;
		font-weight: 500;
		margin: 0 12rpx 0;
		color: rgba(0, 0, 0, 0.65);
	}
	.badge{
		width: 56rpx;
		height: 28rpx;
		background: rgba(100, 64, 194, 0.16);
		border-radius: 4rpx;
		font-size: 20rpx;
		font-family: PingFangSC-Regular, PingFang SC;
		font-weight: 400;
		color: #6440C2;
		line-height: 20rpx;
	}
	.tui-fab-box {
		display: flex;
		justify-content: center;
		flex-direction: column;
		position: fixed;
		right:40px;
		bottom:100px;
		z-index: 99997;
		width: 64rpx;
		height: 64rpx;
		background: #FFFFFF;
		border-radius: 50%;
		box-shadow: 0px 0px 28rpx 0px rgba(0, 0, 0, 0.08);
	}
	
	.tui-fab-right {
		align-items: flex-end;
	}
	.iconfont{
		font-size: 40rpx;
	}
	/* #endif */
</style>