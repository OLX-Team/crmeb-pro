<template>
	<view class='wrapper card' v-if="customForm && customForm.length && isShow">
		<view class='item acea-row row-between' v-for="(item,index) in customForm" :key="index" v-if="item.value">
			<view>{{item.title}}：</view>
			<view v-if="item.label == 'img'" class='conter'>
				<view class='pictrue' v-for="(img,indexn) in item.value" :key="indexn">
					<image :src='img' mode="aspectFill" @click='getCustomForm(index,indexn)'></image>
				</view>
			</view>
			<view v-if="item.label != 'img'" class='conter'>{{item.value}}</view>
		</view>
	</view>
</template>

<script>
	export default {
		name: 'customForm',
		props: {
			customForm:{
				type: Array
			}
		},
		data() {
			return {
				isShow:0
			};
		},
		watch: {
			customForm (value) {
				if(value && value.length){
					value.forEach((item)=>{
						if(item.value){
							return this.isShow = 1
						}
					})
				}
			}
		},
		created() {},
		mounted() {},
		methods: {
			getCustomForm: function(index,indexn) {
				uni.previewImage({
					urls: this.customForm[index].value,
					current: this.customForm[index].value[indexn]
				});
			},
		}
	};
</script>

<style lang="scss">
	.wrapper{
		    background-color: #fff;
		    margin-top: 6px;
		    padding: 15px;
	}
	.wrapper .item {
		font-size: 28rpx;
		color: #282828;
	}
	
	.wrapper .item~.item {
		margin-top: 20rpx;
		white-space: normal;
		word-break: break-all;
		word-wrap: break-word;
	}
	
	.wrapper .item .conter {
		color: #868686;
		width: 460rpx;
		display: flex;
		flex-wrap: nowrap;
		justify-content: flex-end;
		text-align: right;
		.pictrue{
			width: 80rpx;
			height: 80rpx;
			margin-left: 6rpx;
			image{
				width: 100%;
				height: 100%;
				border-radius: 6rpx;
			}
		}
	}
</style>
