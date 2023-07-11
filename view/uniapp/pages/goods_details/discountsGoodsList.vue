<template>
	<view class="discounts-list" :style="colorStyle">
		<view class="discounts-box" v-for="(item,index) in discountsData" :key="index">
			<view class="discounts-title" @click.stop="changeShow(index)">
				<view class="discounts-name line1">
					套餐{{index + 1}}：{{item.title}}
				</view>
				<view class="right-icon">
					<text class="count-price"
						v-if="selectCountPrice[index].total_pic">￥{{selectCountPrice[index].total_pic}}起</text>
					<text class="count-price" v-else>￥{{selectCountPrice[index].min}}起</text>
					<text v-if="proNum !== index" class="iconfont icon-xiangxia"></text>
					<text v-else class="iconfont icon-xiangshang"></text>
				</view>
			</view>
			<transition name="fade" mode="out-in">
				<view class="discounts-cart" v-if="proNum == index">
					<view class="list">
						<checkbox-group @change="checkboxChange">
							<block v-for="(attr,n) in item.products" :key="attr.id">
								<view class='item acea-row row-between-wrapper'>
									<!-- #ifndef MP -->
									<checkbox v-if="item.type == 1" :disabled="item.type == 1 && n ==0"
										:value="(attr.id).toString()"
										:checked="selectValue.includes(attr.id + '') || (item.type == 1 && n ==0)" />
									<!-- #endif -->
									<!-- #ifdef MP -->
									<checkbox v-if="item.type == 1" :value="attr.id" :disabled="item.type == 1 && n ==0"
										:checked="selectValue.includes(attr.id + '') || (item.type == 1 && n ==0)" />
									<!-- #endif -->
									<view class='pic-txt acea-row row-between-wrapper' @click="selecAttr(index,n)">
										<view class='pictrue'>
											<image v-if="attr.image" :src='attr.image'>
											</image>
										</view>
										<view class='text'>
											<view class='line1' :class="attr.attrStatus?'':'reColor'">
												{{attr.title}}
											</view>
											<view class='infor'>
												<text class="line1">
													属性：{{attrValue[n]}}
												</text>
												<text class="iconfont icon-xiangxia"></text>
											</view>
											<view class="price acea-row row-bottom" v-if="selectAttr[n]">
												<view class='money'>￥{{selectAttr[n].price}}
												</view>
												<view class="y_money">￥{{selectAttr[n].product_price}}</view>
											</view>
										</view>
									</view>
								</view>
							</block>
						</checkbox-group>
					</view>
					<view class="save acea-row row-center-wrapper">省:￥<text class="money">{{saveMoney}}</text></view>
					<view class="btn" @click="subData(index)">
						立即下单
					</view>
				</view>
				<view class="goods-image" v-else>
					<view class="goods-image-box" v-for="(img,imgIndex) in images[index]" :key="imgIndex">
						<view class="add" v-if="imgIndex>0">
							+
						</view>
						<image class="goods-image-sty" :src="img" mode=""></image>
					</view>
				</view>
			</transition>
		</view>
		<productWindow :attr="attr" :isShow="1" :title="selectTitle" :iSplus="1" @myevent="onMyEvent"
			@ChangeAttr="ChangeAttr" @attrVal="attrVal" @iptCartNum="iptCartNum" id="product-window" @goCat="goOrder()">
		</productWindow>
	</view>
</template>

<script>
	import {
		postCartAdd
	} from '@/api/store.js';
	import {
		storeDiscountsList
	} from '@/api/store.js';
	import productWindow from './productWindow.vue'
	import colors from "@/mixins/color";
	export default {
		components: {
			productWindow
		},
		mixins: [colors],
		data() {
			return {
				productId: "",
				discountsData: [],
				attr: {
					cartAttr: false,
					productAttr: [],
					productSelect: {},
				},
				productValue: [], //系统属性
				isOpen: false, //是否打开属性组件
				attrValue: [],
				attrTxt: "",
				cartList: {
					valid: [],
					invalid: []
				},
				selectAttr: [],
				selectValue: [], //选中的ID
				proNum: 0,
				images: [],
				selectCountPrice: [],
				selectTitle: "",
				saveMoney: 0
			}
		},
		onLoad(options) {
			this.productId = options.id
			this.getData(0)
		},
		methods: {
			getData(index) {
				storeDiscountsList(this.productId).then(res => {
					this.discountsData = res.data
					if (!this.discountsData.length)
						return uni.navigateBack({
							delta: 1
						});
					res.data[index].products.map((v, i) => {
						this.seleNum = i
						this.attr.productAttr = res.data[index].products[i].productAttr
						this.productValue = res.data[index].products[i].productValue
						this.$set(this.selectAttr, [i], res.data[index].products[i].productValue);
						// this.$set(this.selectValue, [i], res.data[index].products[i].id);
						this.selectValue.push(res.data[index].products[i].id + "")
						this.DefaultSelect()
					})
					if (!this.images.length) {
						for (let i = 0; i < res.data.length; i++) {
							this.selectCountPrice.push({
								min: res.data[i].min_price,
								total_pic: 0
							})
							this.images.push([])
							for (let j = 0; j < res.data[i].products.length; j++) {
								this.images[i].push(res.data[i].products[j].image)
							}
						}
					}
					console.log(this.images)
					this.switchSelect()
				}).catch(err=>{
					uni.showToast({
						title: err,
						icon: 'none',
						duration: 2000
					});
					setTimeout(e=>{
						uni.navigateTo({
							url:'/pages/goods_details/index?id=' + this.productId
						})
					},1001)
				})
			},
			getList(index) {
				this.selectValue = []
				this.discountsData[index].products.map((v, i) => {
					this.seleNum = i
					// this.selectValue.push(this.discountsData[index].products[i].id + "")
					this.attr.productAttr = this.discountsData[index].products[i].productAttr
					this.productValue = this.discountsData[index].products[i].productValue
					this.$set(this.selectAttr, [i], this.discountsData[index].products[i].productValue);
					this.$set(this.selectValue, [i], this.discountsData[index].products[i].id + '');
					console.log(this.discountsData[index].products[i].productAttr, )
					this.DefaultSelect()
				})
				if (!this.images.length) {
					for (let i = 0; i < this.discountsData.length; i++) {
						this.selectCountPrice.push({
							min: this.discountsData[i].min_price,
							total_pic: 0
						})
						this.images.push([])
						for (let j = 0; j < this.discountsData[i].products.length; j++) {
							this.images[i].push(this.discountsData[i].products[j].image)
						}
					}
				}
				this.switchSelect()
				this.proNum = index
			},
			// getList(index) {

			// },
			attrVal(val) {
				this.$set(this.attr.productAttr[val.indexw], 'index', this.attr.productAttr[val.indexw]
					.attr_values[val
						.indexn]);
			},
			/**
			 * 属性变动赋值
			 *
			 */
			ChangeAttr: function(res) {
				let productSelect = this.productValue[res];
				if (productSelect && productSelect.stock > 0) {
					this.$set(this.attr.productSelect, 'image', productSelect.image);
					this.$set(this.attr.productSelect, 'price', productSelect.price);
					this.$set(this.attr.productSelect, 'stock', productSelect.stock);
					this.$set(this.attr.productSelect, 'product_stock', productSelect.product_stock);
					this.$set(this.attr.productSelect, 'unique', productSelect.unique);
					this.$set(this.attr.productSelect, 'cart_num', 1);
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					// this.$set(this, 'attrValue', res);
					this.$set(this, 'attrTxt', '已选择');
					this.attrValue[this.seleNum] = res || ""
				} else {
					this.$set(this.attr.productSelect, 'image', productSelect.image);
					this.$set(this.attr.productSelect, 'price', productSelect.price);
					this.$set(this.attr.productSelect, 'product_stock', productSelect.product_stock);
					this.$set(this.attr.productSelect, 'stock', 0);
					this.$set(this.attr.productSelect, 'unique', '');
					this.$set(this.attr.productSelect, 'cart_num', 0);
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					// this.$set(this, 'attrValue', '');
					this.attrValue[this.seleNum] = res || ""
					this.$set(this, 'attrTxt', '请选择');
				}
				this.$set(this.selectAttr, [this.seleNum], productSelect);
				// this.selectAttr[this.seleNum] = this.attr.productSelect
				this.switchSelect();
			},

			/**
			 * 默认选中属性
			 *
			 */
			DefaultSelect: function() {
				let productAttr = this.attr.productAttr;
				let value = [];
				let arrPrice = []
				for (var key in this.productValue) {
					arrPrice.push(this.productValue[key].price)
				}
				let min = Math.min.apply(null, arrPrice);
				for (var key in this.productValue) {
					if (this.productValue[key].product_stock > 0 && this.productValue[key].price == min) {
						value = this.attr.productAttr.length ? key.split(',') : [];
						break;
					}
				}
				for (let i = 0; i < productAttr.length; i++) {
					this.$set(productAttr[i], 'index', value[i]);
				}
				//sort();排序函数:数字-英文-汉字；
				let productSelect = this.productValue[value.join(',')];
				if (productSelect && productAttr.length) {
					this.$set(this.attr.productSelect, 'store_name', productSelect.title);
					this.$set(this.attr.productSelect, 'image', productSelect.image);
					this.$set(this.attr.productSelect, 'price', productSelect.price);
					this.$set(this.attr.productSelect, 'stock', productSelect.stock);
					this.$set(this.attr.productSelect, 'product_stock', productSelect.product_stock);
					this.$set(this.attr.productSelect, 'unique', productSelect.unique);
					this.$set(this.attr.productSelect, 'cart_num', 1);
					// this.$set(this, 'attrValue', value.join(','));
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					this.attrValue[this.seleNum] = value.join(',')
				} else if (!productSelect && productAttr.length) {
					this.$set(this.attr.productSelect, 'store_name', productSelect.store_name);
					this.$set(this.attr.productSelect, 'image', productSelect.image);
					this.$set(this.attr.productSelect, 'price', productSelect.price);
					this.$set(this.attr.productSelect, 'product_stock', productSelect.product_stock);
					this.$set(this.attr.productSelect, 'stock', 0);
					this.$set(this.attr.productSelect, 'unique', '');
					this.$set(this.attr.productSelect, 'cart_num', 0);
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					// this.$set(this, 'attrValue', '');
					this.attrValue[this.seleNum] = ''
				} else if (!productSelect && !productAttr.length) {
					this.$set(this.attr.productSelect, 'store_name', productSelect.store_name);
					this.$set(this.attr.productSelect, 'image', productSelect.image);
					this.$set(this.attr.productSelect, 'price', productSelect.price);
					this.$set(this.attr.productSelect, 'stock', productSelect.stock);
					this.$set(this.attr.productSelect, 'product_stock', productSelect.product_stock);
					this.$set(this.attr.productSelect, 'unique', productSelect.unique || '');
					this.$set(this.attr.productSelect, 'cart_num', 1);
					this.$set(this.attr.productSelect, 'vip_price', productSelect.vip_price);
					this.$set(this, 'attrValue', '');
					this.attrValue[this.seleNum] = ''
					this.$set(this, 'attrTxt', '请选择');
				}
				this.$set(this.selectAttr, [this.seleNum], productSelect);
			},
			/**
			 * 打开属性插件
			 */
			selecAttr: function(index, n) {
				this.proNum = index
				this.seleNum = n
				this.selectTitle = this.discountsData[index].products[n].title
				this.attr.productAttr = this.discountsData[index].products[n].productAttr
				this.productValue = this.discountsData[index].products[n].productValue
				this.DefaultSelect()
				this.$nextTick((e) => {
					this.$set(this.attr, 'cartAttr', true);
					this.$set(this, 'isOpen', true);
				})
			},
			onMyEvent: function() {
				this.$set(this.attr, 'cartAttr', false);
				this.$set(this, 'isOpen', false);
				this.switchSelect();
			},
			setAllSelectValue: function(status) {
				let that = this;
				let selectValue = [];
				let valid = that.cartList.valid;
				if (valid.length > 0) {
					let newValid = valid.map(item => {
						if (status) {
							if (that.footerswitch) {
								if (item.attrStatus) {
									item.checked = true;
									selectValue.push(item.id);
								} else {
									item.checked = false;
								}
							} else {
								item.checked = true;
								selectValue.push(item.id);
							}
							that.isAllSelect = true;
						} else {
							item.checked = false;
							that.isAllSelect = false;
						}
						return item;
					});
					that.$set(that.cartList, 'valid', newValid);
					that.selectValue = selectValue;
					that.switchSelect();
				}
			},
			checkboxChange: function(event) {
				let that = this;
				let value = event.detail.value;
				let valid = that.cartList.valid;
				console.log(value, valid)
				console.log(this.selectAttr)

				let arr1 = [];
				let arr2 = [];
				let arr3 = [];
				let newValid = valid.map(item => {
					if (that.inArray(item.id, value)) {
						if (that.footerswitch) {
							if (item.attrStatus) {
								item.checked = true;
								arr1.push(item);
							} else {
								item.checked = false;
							}
						} else {
							item.checked = true;
							arr1.push(item);
						}
					} else {
						item.checked = false;
						arr2.push(item);
					}
					return item;
				});
				if (that.footerswitch) {
					arr3 = arr2.filter(item => !item.attrStatus);
				}
				that.$set(that.cartList, 'valid', newValid);
				// let newArr = that.cartList.valid.filter(item => item.attrStatus);
				that.isAllSelect = newValid.length === arr1.length + arr3.length;
				that.selectValue = value;
				that.switchSelect();
			},
			switchSelect: function() {
				let that = this;
				let selectCountPrice = 0.00;
				let originalPrice = 0.00;
				for (let index in this.selectAttr) {
					if (this.selectValue.includes(this.selectAttr[
							index].product_id + '')) {
						selectCountPrice = selectCountPrice + Number(this.selectAttr[
							index].price)
						originalPrice = originalPrice + Number(this.selectAttr[
							index].product_price)
					}
				}
				this.selectCountPrice[this.proNum].total_pic = selectCountPrice
				this.saveMoney = Number(originalPrice - selectCountPrice).toFixed(2) || 0;
			},
			changeShow(index) {
				this.selectValue = []
				if (index === this.proNum) {
					this.proNum = -1
					this.selectCountPrice[index].total_pic = 0
				} else {
					this.proNum = index
					this.getList(index)
				}
			},
			subData(index) {
				let data = []
				let reqData = {
					new: 1,
					discountId: this.discountsData[index].id,
					discountInfos: []
				}
				console.log(this.selectValue, this.discountsData[index].products[0].id)
				console.log(this.selectValue.includes(this.discountsData[index].products[0].id + ''))
				if (this.discountsData[index].type == 0) {

					this.selectValue = []
					this.discountsData[index].products.map(v => {
						this.selectValue.push(v.id + '')
					})
				} else {
					if (this.selectValue
						.length <
						2) {
						this.selectValue = []
						return this.$util.Tips({
							title: '请先选择套餐商品'
						});

					}
				}

				for (let i = 0; i < this.discountsData[index].products.length; i++) {
					for (let j = 0; j < this.selectValue.length; j++) {
						if (this.discountsData[index].products[i].id == this.selectValue[j]) {
							data.push(this.selectAttr[i])
							reqData.discountInfos.push({
								id: this.discountsData[index].products[i].id,
								unique: this.selectAttr[i].unique,
								product_id: this.discountsData[index].products[i].product_id
							})
						}
					}
				}
				postCartAdd(reqData)
					.then(function(res) {
						uni.navigateTo({
							url: '/pages/users/order_confirm/index?new=1&noCoupon=1&cartId=' + res
								.data
								.cartId
								.join(',')
						});
					})
					.catch(err => {
						this.selectValue = []
						return this.$util.Tips({
							title: err
						});
					});
			},
			goOrder() {
				this.$set(this, 'isOpen', false);
				this.$set(this.attr, 'cartAttr', false);
			}
		}
	}
</script>

<style lang="scss" scoped>
	.discounts-list {
		background-color: #F5F5F5;
	}

	.discounts-box {
		padding: 15rpx 0 30rpx 0;
		background-color: #fff;
		margin-bottom: 20rpx;
	}

	.discounts-title {
		display: flex;
		justify-content: space-between;
		margin: 15rpx 20rpx 0 20rpx;
		padding-bottom: 15rpx;
		border-bottom: 1px solid #EEEEEE;
		font-size: 26rpx;
		font-weight: bold;
		
		.discounts-name{
			width: 540rpx;
		}

		.right-icon {
			display: flex;
			align-items: center;

			.iconfont {
				font-size: 25rpx;
			}
		}
	}

	.discounts-cart .noCart .pictrue image {
		width: 100%;
		height: 100%;
	}

	.discounts-cart .list {}

	.discounts-cart .list .item {
		padding: 25rpx 30rpx;
		background-color: #fff;
		margin-bottom: 15rpx;
	}

	.discounts-cart .list .item .pic-txt {
		width: 627rpx;
		position: relative;
	}

	.discounts-cart .list .item .pic-txt .pictrue {
		width: 160rpx;
		height: 160rpx;
	}

	.discounts-cart .list .item .pic-txt .pictrue image {
		width: 100%;
		height: 100%;
		border-radius: 6rpx;
	}

	.discounts-cart .list .item .pic-txt .text {
		width: 444rpx;
		font-size: 28rpx;
		color: #282828;
	}

	.discounts-cart .list .item .pic-txt .text .reColor {
		color: #333;
	}

	.discounts-cart .list .item .pic-txt .text .reElection {
		margin-top: 20rpx;
	}

	.discounts-cart .list .item .pic-txt .text .reElection .title {
		font-size: 24rpx;
	}

	.discounts-cart .list .item .pic-txt .text .reElection .reBnt {
		width: 120rpx;
		height: 46rpx;
		border-radius: 23rpx;
		font-size: 26rpx;
	}

	.discounts-cart .list .item .pic-txt .text .infor {
		display: flex;
		align-items: center;
		justify-content: space-between;
		font-size: 24rpx;
		color: #868686;
		margin-top: 16rpx;
		background-color: #F5F5F5;
		padding: 6rpx 10rpx;
		border-radius: 16rpx;

		.icon-xiangxia {
			font-size: 16rpx;
		}
	}

	.discounts-cart .list .item .pic-txt .text .price {
		margin-top: 28rpx;
	}

	.discounts-cart .list .item .pic-txt .text .money {
		font-size: 32rpx;
		color: #282828;
	}

	.discounts-cart .list .item .pic-txt .text .y_money {
		font-size: 22rpx;
		color: #999;
		text-decoration: line-through;
		margin-left: 10rpx;
	}

	.discounts-cart .list .item .pic-txt .carnum {
		height: 47rpx;
		position: absolute;
		bottom: 7rpx;
		right: 0;
	}

	.discounts-cart .list .item .pic-txt .carnum view {
		border: 1rpx solid #a4a4a4;
		width: 66rpx;
		text-align: center;
		height: 100%;
		line-height: 40rpx;
		font-size: 28rpx;
		color: #a4a4a4;
	}

	.discounts-cart .list .item .pic-txt .carnum .reduce {
		border-right: 0;
		border-radius: 3rpx 0 0 3rpx;
	}

	.discounts-cart .list .item .pic-txt .carnum .reduce.on {
		border-color: #e3e3e3;
		color: #dedede;
	}

	.discounts-cart .list .item .pic-txt .carnum .plus {
		border-left: 0;
		border-radius: 0 3rpx 3rpx 0;
	}

	.discounts-cart .list .item .pic-txt .carnum .num {
		color: #282828;
	}

	.save {
		margin-bottom: 20rpx;
		color: var(--view-priceColor);

		.money {
			font-size: 35rpx;
			font-weight: bold;
		}
	}

	.btn {
		text-align: center;
		color: #fff;
		padding: 15rpx 0;
		margin: 0 30rpx;
		background: var(--view-theme);
		box-shadow: 0px 0px 8px var(--view-minorColor);
		opacity: 1;
		border-radius: 16px;
	}

	.goods-image {
		display: flex;
		align-items: center;
		padding: 30rpx;
		width: 100%;
		overflow-x: scroll;

		.goods-image-box {
			display: flex;
			align-items: center;

			.add {
				font-size: 26rpx;
				color: #666666;
				padding: 0 20rpx;
			}

			.goods-image-sty {
				width: 180rpx;
				height: 180rpx;
				border-radius: 6rpx;
			}
		}
	}

	.count-price {
		margin-right: 10rpx;
		color: var(--view-priceColor);
	}

	.fade-enter-active,
	.fade-leave-active {
		transition: all 0.2s;
	}

	.fade-enter,
	.fade-leave-to

	/* .fade-leave-active below version 2.1.8 */
		{
		opacity: 0;
		transform: translateY(-30px);
	}
</style>
