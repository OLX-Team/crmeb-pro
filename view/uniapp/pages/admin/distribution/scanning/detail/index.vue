<template>
	<view>
		<view class='shoppingCart copy-data'>
			<view class='nav acea-row row-between-wrapper'>
				<view>订单号：<text class='num'>{{ list.order_id }}</text></view>
					<view v-if="lets != 1" class='administrate acea-row row-center-wrapper' @click="switchs">切换</view>
			</view>
			<view class="content">
				<view class="list_top">共<span class="bluefont">{{ list.total_num }}</span>件<span class="garyfont">（已核销 {{ list.writeoff_count }} 件）</span>
				</view>
				<view class='list'>
					<view>
						<block v-for="(item,index) in list.cart_info" :key="index">
							<view v-if="item.is_writeoff == 1" class='item acea-row row-between-wrapper writeoff'>
								<div class="xuan q" type="checkbox" v-model="checkModel" :value="item.id">
									<view class="iconfont icon-duihao dui"></view>
								</div>
								<view class='picTxt acea-row row-between-wrapper'>
									<view class='pictrue'>
										<image :src="item.cart_info.productInfo.image" mode=""></image>
									</view>
									<view class='text'>
										<view class="title">
											<view class='line1 top' :class="item.attrStatus?'':'reColor'">
												{{ item.cart_info.productInfo.store_name }}
											</view>
											<view v-if="item.is_writeoff == 1" class="txt">已核销{{parseInt(lists.cart_info[index].cart_num-lists.cart_info[index].surplus_num)}}件</view>
										</view>
										<view class='infor line1'>
											属性：{{ item.cart_info.productInfo.attrInfo.suk }}</view>
										<view class='money he'>
											<view>￥{{ item.cart_info.productInfo.price }}</view>
											<view v-if="item.is_writeoff == 1" class="txt">
												<view class='carnum acea-row row-center-wrapper'>
													<view class="reduce">-</view>
													<view class='num0'>0</view>
													<view class="plus">+</view>
												</view>
											</view>
										</view>
									</view>
								</view>
							</view>
							<view v-else class='item acea-row row-between-wrapper'>
								<!-- <input type="checkbox" v-model="checkModel" :value="item.id" :value="(item.id).toString()" :checked="item.checked" color="#007AFF"/> -->
								<div  class="xuan" :class="item.checked?'q':''" type="checkbox" v-model="checkModel" :value="item.id" @click="dan(item.cart_id,index)">
									<view :class="item.checked?'iconfont icon-duihao dui':''"></view>
								</div>
								<view class='picTxt acea-row row-between-wrapper'>
									<view class='pictrue'>
										<image :src="item.cart_info.productInfo.image" mode=""></image>
									</view>
									<view class='text'>
										<view class="title">
											<view class='line1' :class="item.attrStatus?'':'reColor'">
												{{ item.cart_info.productInfo.store_name }}
											</view>
											<view v-if="item.is_writeoff == 1" class="txt">已核销</view>
											<view v-if="item.is_writeoff == 0 && lists.cart_info[index].surplus_num == lists.cart_info[index].cart_num" class="txt bluecol">未核销</view>
											<view v-if="item.is_writeoff == 0 && lists.cart_info[index].surplus_num != lists.cart_info[index].cart_num" class="txt orangcol">已核销{{parseInt(lists.cart_info[index].cart_num-lists.cart_info[index].surplus_num)}}件</view>
										</view>
										<view class='infor line1'>
											属性：{{ item.cart_info.productInfo.attrInfo.suk }}</view>
										<view class='money he'>
											<view>￥{{ item.cart_info.productInfo.price }}</view>
											<view v-if="item.is_writeoff == 1" class="txt">
												<view class='carnum acea-row row-center-wrapper'>
													<view class="reduce">-</view>
													<view class='num0'>0</view>
													<view class="plus">+</view>
												</view>
											</view>
											<view v-else class="txt">
												<view class='carnum acea-row row-center-wrapper'>
													<view v-if="item.surplus_num == 1" class="reduce bggary">-</view>
													<view v-else class="reduce" @click.stop='subCart(item,index)'>-</view>
													<view class='nums'>{{parseInt(item.surplus_num)}}</view>
													<view v-if="item.surplus_num == item.surplus_num+nums[index].num" class="plus bggary">+</view>
													<view v-else class="plus" @click.stop='addCart(item,index)'>+</view>
												</view>
											</view>
										</view>
									</view>
								</view>
							</view>
						</block>
					</view>
				</view>
			</view>
			<view class='footer acea-row row-between-wrapper'>
				<view>
					<view style="display: flex;" @change="checkboxAllChange">
						<!-- <input type="checkbox" @click="checkboxAllChange" v-model="isAllSelect"> -->
						<div class="xuan" :class="checked?'q':''"  @click="checkAll" v-model="checked">
							<view class="iconfont icon-duihao dui"></view>
						</div>
						<!-- <div v-if="lengt == 0" class="xuan noxuan ">
							<view class="iconfont icon-jingyanzhi dui"></view>
						</div> -->
						
						<text class='checkAll'>全选</text>
					</view>
				</view>
				<view >
						<button class='money' type="primary" @click="verification">立即核销</button>
				</view>
			</view>
		</view>
		<view v-if="box">
			<view class="box">
				<view class="small_box">
					<image src="../../../static/decorate.png" mode=""></image>
					<view class="content">
						<view class="font">核销成功</view>
						<view v-if="list.total_num == parseInt(list.writeoff_count)+writeOffNum" class="small_font">当前订单已完成核销</view>
						<view v-else class="small_font">当前订单仍有未核销商品</view>
					</view>
					<view v-if="lets == 1 && list.total_num == parseInt(list.writeoff_count)+writeOffNum" class="btn" @click="ok(1)">好的</view>
					<view v-if="list.total_num != parseInt(list.writeoff_count)+writeOffNum" class="btn" @click="ok(0)">继续核销其他商品</view>
					<navigator v-if="lets > 1 && list.total_num == parseInt(list.writeoff_count)+writeOffNum" :url='"/pages/admin/distribution/scanning/index?code="+attr.code' open-type="redirect" hover-class='none' class="btn">核销其他订单</navigator>
					<navigator v-if="lets > 1 && list.total_num != parseInt(list.writeoff_count)+writeOffNum" :url='"/pages/admin/distribution/scanning/index?code="+attr.code' hover-class='none' open-type="redirect" class="btn_no">返回列表</navigator>
					<navigator v-if="(lets > 1 && list.total_num == parseInt(list.writeoff_count)+writeOffNum)||(lets==1&&list.total_num != parseInt(list.writeoff_count)+writeOffNum)" url="/pages/admin/distribution/index" hover-class='none' open-type="redirect" class="btn_no">返回首页</navigator>		
				</view>
			</view>
		</view>
		<writeOffSwitching ref="writeOff" :attr="attr"  :isShow='1' :iSplus='1' :iScart='1' @dataId = "onDataId" @myevent="onMyEvent" id='product-window'></writeOffSwitching>
	</view>
</template>

<script>
	import writeOffSwitching from '@/components/writeOffSwitching';
	import { orderCartInfo, orderWriteoff } from '@/api/admin'
	export default {
		components: {
			writeOffSwitching
		},
		data() {
			return {
				nums:[],
				newList:[],
				reduce_show:-1,
				plus_show:-1,
				ids: [],//选定需要核销的id
				lets: 0, //判断订单的数量
				listlet: 0, //判断订单商品的数量
				attr: { //切换组件传值
					cartAttr: false,
					id: [],
					code: '',
					type: 0
				},
				id: 0, //订单ID
				list: [],
				lists: [],
				lengt: 0,
				box: false,
				checked:  false,
				checkModel: [],
				isAllSelect: false,
				data:[{id:'1',value:'aaa'},{id:'2',value:'bbb'},{id:'3',value:'ccc'}],
				writeOffNum:0 //每次核销商品数量
			};
		},
		watch:{
		　　checkModel(){
		　　　　if(this.lengt==this.checkModel.length){
		　　　　　　this.checked=true;
		　　　　}else{
		　　　　　　this.checked=false;
		　　　　}
		　　}
		},
		onLoad: function(options) {
			this.id = options.id
			this.attr.code = options.code
			this.lets = options.let
			this.getList(this.id)
		},
		onShow: function() {
		},
		methods: {
			//处理每一条数据的最大值
			num(){
				 for (let index = 0; index < this.lists.cart_info.length; index++) {
								this.nums.push({ num: 0 });
							}
			},
			subCart(item,index){
				if(item.surplus_num == 1) {
					 this.reduce_show = index;
				}else{
					this.nums[index].num --
					item.surplus_num--
				}
			},
			addCart(item,index){
				if(item.surplus_num == item.surplus_num+this.nums[index].num) {
					 this.plus_show = index;
				}
				else{
					item.surplus_num++
					this.nums[index].num ++
				}
			},
			checkAll(){
				var items = this.list.cart_info,
				data = [];
			　　　　if(this.checked){
			　　　　　　this.checkModel=[];
						for (var i = 0, lenI = this.list.cart_info.length; i < lenI; ++i) {
							const item = items[i]
								this.$set(item,'checked',false)
						}
			　　　　}else{
						this.checkModel=[];
						for (var i = 0, lenI = this.list.cart_info.length; i < lenI; ++i) {
							const item = items[i]
						
								this.$set(item,'checked',true)
								if(item.is_writeoff == 1){
									this.checkModel =this.checkModel.filter(item => item != item.cart_id)
								}else{
									this.checkModel.push(item.cart_id)
								}
						}
						this.lengt = this.checkModel.length
			　　　　}
			　　},
				dan(id,index){
					if(this.checkModel.indexOf(id) == -1){
						this.$set(this.list.cart_info[index],'checked',true)
						this.checkModel.push(id)
					}
					else{
						this.$set(this.list.cart_info[index],'checked',false)
						this.checkModel =this.checkModel.filter(item => item != id)
					}
			},
			getList:function(id) {
				orderCartInfo(2,{oid:id}).then(res=>{
					this.list = res.data
					this.lists = JSON.parse(JSON.stringify(res.data))
					this.listlet = res.data.cart_info.length
					this.$set(this.attr, 'id',this.list.id);
				this.checkAll()
				this.num()
				})
			},
			onDataId: function(id) {
				this.nums.forEach((item)=>{
					item.num = 0
				})
				this.id = id;
				this.getList(id)
			},
			switchs(){
				this.attr.cartAttr = true;
				this.$refs.writeOff.getList(2)
			},
			onMyEvent(){
				this.attr.cartAttr = false;
			},
			verification(){
				let that = this
				let obj = {};
				// 将数组转化为对象
				for (let key in this.checkModel) {
						 obj[key] = this.checkModel[key];
				 };
				let newObj = Object.keys(obj).map(val => ({
								cart_id: obj[val],
				 }));
				 //处理列表内对应的核销数的数值
				 for(var i =0 ;i<newObj.length;i++){
					 for(var j = 0; j< this.list.cart_info.length;j++){
						 if(newObj[i].cart_id == this.list.cart_info[j].cart_id){
							 newObj[i].cart_num=  this.list.cart_info[j].surplus_num;
						 }
					 }
				 }
				 this.newList = newObj
				if(that.checkModel.length == 0){
					that.$util.Tips({
						title: '请选择商品'
					});
				}else{
					uni.showLoading({
					    title: '加载中',
					});
					let num = 0;
					newObj.forEach((item)=>{
						num = num+ parseInt(item.cart_num) 
					})
					this.writeOffNum = num;
					setTimeout(function () {
					    orderWriteoff(2,{oid:that.id,cart_ids:that.newList}).then(res=>{
					    	uni.hideLoading();
							that.box = true
					    }).catch(err=>{
							that.$util.Tips({
								title: err
							});
							uni.hideLoading();
						})
					}, 1000);
				}
			},
			// 所有订单核销完成
			ok(type){
				this.box = false
				this.nums.forEach((item)=>{
					item.num = 0
				})
				this.getList(this.id)
				if(type){
					uni.redirectTo({
						url:'/pages/admin/distribution/index'
					})
				}
			}
		}
	}
</script>

<style scoped lang="scss">
	/deep/checkbox .uni-checkbox-input.uni-checkbox-input-checked{
		    border: 1px solid #007aff !important;
		    background-color: #007aff !important;
	}
	.bggary{
			background-color: #dfdfdf;
	}
	.page-footer {
		position: fixed;
		bottom: 0;
		z-index: 30;
		display: flex;
		align-items: center;
		justify-content: space-around;
		width: 100%;
		height: calc(98upx+ constant(safe-area-inset-bottom)); ///兼容 IOS<11.2/
		height: calc(98upx + env(safe-area-inset-bottom)); ///兼容 IOS>11.2/
		box-sizing: border-box;
		border-top: solid 1upx #F3F3F3;
		background-color: #fff;
		box-shadow: 0px 0px 17upx 1upx rgba(206, 206, 206, 0.32);
		padding-bottom: constant(safe-area-inset-bottom); ///兼容 IOS<11.2/
		padding-bottom: env(safe-area-inset-bottom); ///兼容 IOS>11.2/
	
		.foot-item {
			display: flex;
			width: max-content;
			align-items: center;
			justify-content: center;
			flex-direction: column;
			position: relative;
	
			.count-num {
				position: absolute;
				display: flex;
				justify-content: center;
				align-items: center;
				width: 40upx;
				height: 40upx;
				top: 0upx;
				right: -15upx;
				color: #fff;
				font-size: 20upx;
				background-color: #FD502F;
				border-radius: 50%;
				padding: 4upx;
			}
		}
	
		.foot-item image {
			height: 50upx;
			width: 50upx;
			text-align: center;
			margin: 0 auto;
		}
	
		.foot-item .txt {
			font-size: 24upx;
	
	
			&.active {}
		}
	}
	.shoppingCart {
		/* #ifdef H5 */
		// padding-bottom: 0;
		// padding-bottom: constant(safe-area-inset-bottom);  
		// padding-bottom: env(safe-area-inset-bottom);
		/* #endif */
	}

	.shoppingCart .labelNav {
		height: 76upx;
		padding: 0 30upx;
		font-size: 22upx;
		color: #8c8c8c;
		position: fixed;
		left: 0;
		width: 100%;
		box-sizing: border-box;
		background-color: #f5f5f5;
		z-index: 5;
		top: 0;
	}

	.shoppingCart .labelNav .item .iconfont {
		font-size: 25upx;
		margin-right: 10upx;
	}

	.shoppingCart .nav {
		// width: 100%;
		background-color: #fff;
		margin: 20upx 28upx;
		padding: 30upx 24upx;
		font-size: 28upx;
		color: #282828;
		z-index: 5;
		border-radius: 12upx;
	}

	.shoppingCart .nav .num {
		margin-left: 12upx;
	}

	.shoppingCart .nav .administrate {
		font-size: 26upx;
		color: #282828;
		width: 110upx;
		height: 46upx;
		border-radius: 24upx;
		border: 1px solid #CCCCCC;
	}

	.shoppingCart .noCart {
		margin-top: 171upx;
		background-color: #fff;
		padding-top: 0.1upx;
	}

	.shoppingCart .noCart .pictrue {
		width: 414upx;
		height: 336upx;
		margin: 78upx auto 56upx auto;
	}

	.shoppingCart .noCart .pictrue image {
		width: 100%;
		height: 100%;
	}

	.shoppingCart .list {
		// margin: 20upx 28upx;
	}

	.shoppingCart .list .item {
		padding: 25upx 30upx;
		background-color: #fff;
		// margin-bottom: 15upx;
	}

	.shoppingCart .list .item .picTxt {
		// width: 627upx;
		position: relative;
	}

	.shoppingCart .list .item .picTxt .pictrue {
		width: 132upx;
		height: 132upx;
	}

	.shoppingCart .list .item .picTxt .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 6upx;
	}

	.shoppingCart .list .item .picTxt .text {
		width: 420upx;
		font-size: 28upx;
		color: #282828;
		margin-left: 20upx;
	}

	.shoppingCart .list .item .picTxt .text .reColor {
		color: #999;
		width: 60%;
	}
	.shoppingCart .list .item .picTxt .text .title {
			display: flex;
			justify-content: space-between;
			font-size: 20upx;
			font-weight: 600;
			.bluecol{
				color: #1890FF;
			}
			.orangcol{
				color: #FF7E00;
			}
			.graycol{
				color: #CCCCCC;
			}
		}
		.shoppingCart .list .item .picTxt .text .title .top {
			width: 70%;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
			font-size: 28upx;
			font-weight: 400;
		}
	.shoppingCart .list .item .picTxt .text .reElection {
		margin-top: 20upx;
	}

	.shoppingCart .list .item .picTxt .text .reElection .title {
		font-size: 24upx;
	}

	.shoppingCart .list .item .picTxt .text .reElection .reBnt {
		width: 120upx;
		height: 46upx;
		border-radius: 23upx;
		font-size: 26upx;
	}

	.shoppingCart .list .item .picTxt .text .infor {
		font-size: 24upx;
		color: #868686;
		margin-top: 8upx;
	}

	.shoppingCart .list .item .picTxt .text .money {
		font-size: 30upx;
		color: #282828;
		margin-top: 20upx;
		font-weight: 600;
		font-family: PingFangSC-Semibold, PingFang SC;
	}

	.shoppingCart .list .item .picTxt .carnum {
		height: 47upx;
		position: absolute;
		bottom: 7upx;
		right: 0;
	}

	.shoppingCart .list .item .picTxt .carnum view {
		border: 1upx solid #a4a4a4;
		width: 66upx;
		text-align: center;
		height: 100%;
		line-height: 40upx;
		font-size: 28upx;
		color: #a4a4a4;
	}

	.shoppingCart .list .item .picTxt .carnum .reduce {
		border-right: 0;
		border-radius: 30upx 0 0 30upx;
	}

	.shoppingCart .list .item .picTxt .carnum .reduce.on {
		border-color: #e3e3e3;
		color: #dedede;
	}

	.shoppingCart .list .item .picTxt .carnum .plus {
		border-left: 0;
		border-radius: 0 30upx 30upx 0;
	}

	.shoppingCart .list .item .picTxt .carnum .num {
		color: #282828;
	}
	.shoppingCart .list .item .picTxt .carnum .num0 {
		color: #a4a4a4;
	}
	.shoppingCart .list .item .picTxt .carnum .nums {
		color: #282828;
	}

	.shoppingCart .invalidGoods {
		background-color: #fff;
	}

	.shoppingCart .invalidGoods .goodsNav {
		width: 100%;
		height: 66upx;
		padding: 0 30upx;
		box-sizing: border-box;
		font-size: 28upx;
		color: #282828;
	}

	.shoppingCart .invalidGoods .goodsNav .iconfont {
		color: #424242;
		font-size: 28upx;
		margin-right: 17upx;
	}

	.shoppingCart .invalidGoods .goodsNav .del {
		font-size: 26upx;
		color: #999;
	}

	.shoppingCart .invalidGoods .goodsNav .del .icon-shanchu1 {
		color: #999;
		font-size: 33upx;
		vertical-align: -2upx;
		margin-right: 8upx;
	}

	.shoppingCart .invalidGoods .goodsList .item {
		padding: 20upx 30upx;
		border-top: 1upx solid #f5f5f5;
	}

	.shoppingCart .invalidGoods .goodsList .item .invalid {
		font-size: 22upx;
		color: #fff;
		width: 70upx;
		height: 36upx;
		background-color: #aaa;
		border-radius: 3upx;
		text-align: center;
		line-height: 36upx;
	}

	.shoppingCart .invalidGoods .goodsList .item .pictrue {
		width: 140upx;
		height: 140upx;
	}

	.shoppingCart .invalidGoods .goodsList .item .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 6upx;
	}

	.shoppingCart .invalidGoods .goodsList .item .text {
		width: 433upx;
		font-size: 28upx;
		color: #999;
		height: 140upx;
	}

	.shoppingCart .invalidGoods .goodsList .item .text .name {
		width: 100%;
	}

	.shoppingCart .invalidGoods .goodsList .item .text .infor {
		font-size: 24upx;
	}

	.shoppingCart .invalidGoods .goodsList .item .text .end {
		font-size: 26upx;
		color: #bbb;
	}

	.shoppingCart .footer {
		// z-index: 999;
		width: 100%;
		height: 100upx;
		background-color: #fafafa;
		position: fixed;
		padding: 0 30upx;
		box-sizing: border-box;
		border-top: 1upx solid #eee;
		bottom: 0upx;
	}

	.shoppingCart .footer.on {
		// #ifndef H5
		bottom: 0upx;
		// #endif
	}

	.shoppingCart .footer .checkAll {
		font-size: 28upx;
		color: #282828;
		margin-left: 16upx;
	}

	// .shoppingCart .footer checkbox .wx-checkbox-input{background-color:#fafafa;}
	.shoppingCart .footer .money {
		font-size: 30upx;
		width: 214upx;
		height: 70upx;
		background: #1890FF;
		border-radius: 50upx;
		font-weight: 400;
		color: #FFFFFF;
		line-height: 70upx;
	}

	.shoppingCart .footer .placeOrder {
		color: #fff;
		font-size: 30upx;
		width: 226upx;
		height: 70upx;
		border-radius: 50upx;
		text-align: center;
		line-height: 70upx;
		margin-left: 22upx;
	}

	.shoppingCart .footer .button .bnt {
		font-size: 28upx;
		color: #999;
		border-radius: 50upx;
		border: 1px solid #999;
		width: 160upx;
		height: 60upx;
		text-align: center;
		line-height: 60upx;
	}

	.shoppingCart .footer .button form~form {
		margin-left: 17upx;
	}

	.uni-p-b-96 {
		height: 96upx;
	}

</style>
<style scoped lang="scss">
	.writeoff{
		opacity: 0.6;
	}
	.he{
		display: flex;
		justify-content: space-between;
		.txt{
			font-size: 22upx;
			
		}
	}
	.xuan{
		width: 40upx; 
		height: 40upx; 
		border: 2upx solid #CCCCCC;
		border-radius: 30upx;
	}
	.noxuan{
		cursor: not-allowed;
	}
	.q{background-color: #4276F6;line-height: 40upx;color: #FFF; font-size:24upx;text-align: center;}
	.dui{line-height: 40upx;color: #FFF; font-size:24upx;text-align: center;}
	.box{
		position: fixed;
		top: 0;
		bottom: 0;
		width: 100%;
		height: 100%;
		z-index: 10;
		background-color: #78797a;
		.small_box{
			padding-bottom: 46upx;
			width: 480upx;
			background: #FFFFFF;
			border-radius: 12upx;
			margin-top: 360upx;
			margin-left: 136upx;
			image{width: 100%;height: 228upx;}
			.content{
				// height: 200upx;
				text-align: center;
				.font{
					margin-top: 52upx;
					margin-bottom: 8upx;
					font-size: 36upx;
					font-weight: 600;
					color: #333333;
				}
				.small_font{
					font-size: 28upx;
					font-weight: 400;
					color: #666666;
					margin-bottom: 50upx;
				}
			}
			.btn{
				width: 398upx;
				margin: 0 auto;
				text-align: center;
				height: 80upx;
				line-height: 80upx;
				font-size: 30upx;
				color: #FFFFFF;
				background: linear-gradient(270deg, #4276F6 0%, #00ACF8 100%);
				border-radius: 20px;
			}
			.btn_no{
				color: #999999;
				margin-top: 28upx;
				text-align: center;
				font-size: 30upx;
			}
		}
	}
	.shoppingCart .content{
		margin: 20upx 28upx;
		border-radius: 12upx;
		margin-bottom: 200upx;
		.list_top{
			background-color: #FFFFFF;
			font-size: 28upx;
			color: #333333;
			height: 90upx;
			line-height: 90upx;
			padding-left: 24upx;
			border-bottom: 2upx solid #EEEEEE;
			.bluefont{color: #1890FF;display: inline-block;margin: 0upx 10upx;}
			.garyfont{color: #999999;}
		}
	}
</style>
