<template>
	<view class="tab_nav">
		<view class="tab_list acea-row row-between">
			<view class="tab_nav_item acea-row row-center row-column row-middle"
			:class="active == 0 ? 'on' : ''"  @click="switchTab(0)">
				<text class="iconfont" :class="active == 0 ? 'icon-kehu-xuanzhong' : 'icon-kehu'" ></text>
				<span>客户</span>
			</view>
			<view class="tab_nav_item acea-row row-center row-column row-middle" 
			:class="active == 1 ? 'on' : ''" @click="switchTab(1)">
				<text class="iconfont " :class="active == 1 ? 'icon-dingdan-xuanzhong' : 'icon-dingdan1'"></text>
				<span>订单</span>
			</view>
			<view class="tab_nav_item acea-row row-center row-column row-middle" 
			:class="active == 2 ? 'on' : ''" @click="switchTab(2)">
				<text class="iconfont" :class="active == 2 ? 'icon-zuji-xuanzhong' : 'icon-zuji'"></text>
				<span>足迹</span>
			</view>
		</view>
	</view>
</template>
<script>
	export default{
		data() {
			return {
				active1:this.active
			}
		},
		props:{
			active:{
				type:Number,
				default:0
			}
		},
		methods:{
			switchTab(index){
				// this.active = index;
				let userid = this.$Cache.get('work_user_id')
				if(index == 0){
					uni.reLaunch({
						url:'/pages/work/userInfo/index?userid=' + userid
					})
				}else if(index == 1){
					uni.reLaunch({
						url:'/pages/work/orderList/index'
					})
				}else if(index == 2){
					uni.reLaunch({
						url:'/pages/work/record/index'
					})
				}
			}
		}
	}
</script>
<style lang="scss">
	.tab_nav{
		width: 100%;
		position: fixed;
		bottom: 0;
		left: 0;
		
		
	}
	.tab_nav_item{
		width: 33.3%;
		height: 100rpx;
		background: #fff;
		.title{
			color: #1A1A1A;
			font-size: 24rpx;
		}
		.iconfont{
			font-size: 36rpx;
		}
	}
	.icon-kehu-xuanzhong,.icon-dingdan-xuanzhong,.icon-jilu-xuanzhong{
		color: #1890FF;
	}
	.on{
		color: #1890FF;
	}
</style>
