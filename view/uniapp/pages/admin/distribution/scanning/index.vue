<template>
	<view>
		<view class="scan">
			<view class="header" :style="{backgroundImage:'url('+imgHost+'/statics/images/banner.png'+')'}">
				请选择当前核销订单
			</view>
			<view class="box">
				<view class="content" v-for="(item,index) in list" :key="index" @click="jump(item.id)">
					<view  class="content_box">
						<image :src="item.image" mode=""></image>
						<view class="content_box_title">
							<p class="textbox">订单号：{{ item.order_id }}</p>
							<p class="attribute mar">下单时间：{{ item.add_time }}</p>
							<view class="txt">
								<p class="attribute">订单实付：¥{{ item.pay_price }}</p>
								<p class="orange" v-if="(item._status == 4 || item._status == 12) && item.status == 5">部分核销</p>
								<p class="attributes blue" v-if="item._status == 4 && item.status == 1">未核销</p>
								<p class="attributes blue" v-if="item._status == 11">未核销</p>
								<p class="attributes blue" v-if="item._status == 5">已核销</p>
							</view>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import { orderWriteoffInfo } from '@/api/admin'
	import {HTTP_REQUEST_URL} from '@/config/app';
	export default {
		name: 'scanning',
		data() {
			return {
				verify_code:'',
				list: [],
				imgHost:HTTP_REQUEST_URL,
			}
		},
		onLoad: function(options) {
			this.verify_code = options.code
		},
		onShow(options){
			this.getList()
		},
		methods:{
			getList:function() {
				orderWriteoffInfo(2,{verify_code:this.verify_code,code_type:2}).then(res=>{
					this.list = res.data;
					if(this.list.length == 1){
						uni.redirectTo({
							url:'./detail/index?id='+this.list[0].id+'&let='+this.list.length+'&code='+this.verify_code
						})
					}
				}).catch(err=>{
					this.$util.Tips({
						title: err
					}); 
				})
			},
			jump:function(id) {
				uni.navigateTo({
					url:'./detail/index?id='+id+'&let='+this.list.length+'&code='+this.verify_code
				})
			}
		}
	}
</script>

<style lang="scss" scoped>
	.scan{
		padding-bottom: 160upx;
		 .header {
			 width: 100%;
			 height: 220upx;
			 // background-image: url(../../../../static/images/banner.png);
			 background-repeat: no-repeat;   //不重复
			 background-size: 100% 100%;
			 color: #FFFFFF;
			 font-size: 32upx;
			 text-align: center;
			 line-height: 160upx;
			 margin: 0 auto;
		 
		 }
		 .box{
			 margin: -64upx auto 0 auto;
		 }
		 .content{
			 margin: 16upx auto 16upx auto;
			 width: 694upx;
			 // height: 428upx;
			 padding: 28upx 24upx 32upx;
			 background: #FFFFFF;
			 border-radius: 12upx;
			 .pad{padding: 20upx 20upx 22upx;}
			 .content_box{
				 height: 70px;
				 border-radius: 8upx;
				 padding-right: 22upx;
				 display: flex;
				 justify-content: start;
				 align-items: center;
				 image{
					 width: 140upx;
					 height: 140upx;
					 border-radius: 8upx;
				 }
				 .content_box_title{
					 flex: 1;
					 margin-left: 18upx;
					 font-size: 20upx;
					 font-weight: 400;
					 .textbox{
						 white-space: nowrap;
						 text-overflow: ellipsis;
						 overflow: hidden;
						 word-break: break-all;
						 width: 466upx;
						 font-size: 30upx;
						 font-weight: bold;
						 line-height: 21px;
					 }
					 .mar{margin: 16upx 0upx;}
					 .attribute{
						 color: #999999;
						 // margin: 4upx 0upx 10upx;
					 }
					 .txt{
						 display: flex;
						 justify-content: space-between;
						 font-size: 24upx;
						.orange{color: #FF7E00;}
						.blue{color: #1890FF;}
					 }
				 }
			 }
			 .content_bottom{
				 display: flex;
				 justify-content: space-between;
				 font-size: 22upx;
				 padding: 0upx 20upx;
				 color: #666666;
				 .money{
					 font-size: 26upx;
					 color: #F5222D;
				 }
			 }
		 }
	}
</style>
