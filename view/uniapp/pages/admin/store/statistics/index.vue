<template>
	<div class="statistical-page" ref="container">
		<div class="navs">
			<div class="list">
				<div class="item" :class="time == 'today' ? 'on' : ''" @click="setTime('today')">今天</div>
				<div class="item" :class="time == 'yesterday' ? 'on' : ''" @click="setTime('yesterday')">昨天</div>
				<div class="item" :class="time == 'seven' ? 'on' : ''" @click="setTime('seven')">最近7天</div>
				<div class="item" :class="time == 'month' ? 'on' : ''" @click="setTime('month')">本月</div>
				<div class="item" :class="time == 'date' ? 'on' : ''" @click="dateTitle">自定义</div>
			</div>
		</div>
		<div class="wrapper" v-if="datalist">
			<div class="title">
				<span v-if="type == 1">{{ time == 'date'?'':title }}配送订单（元）</span>
				<span v-if="type == 2">{{ time == 'date'?'':title }}配送订单（单）</span>
				<span v-if="type == 3">{{ time == 'date'?'':title }}退款订单（元）</span>
				<span v-if="type == 4">{{ time == 'date'?'':title }}收银订单（元）</span>
				<span v-if="type == 5">{{ time == 'date'?'':title }}核销订单（元）</span>
				<span v-if="type == 6">{{ time == 'date'?'':title }}付费会员（元）</span>
				<span v-if="type == 7">{{ time == 'date'?'':title }}充值订单（元）</span>
				<span v-if="type == 8">{{ time == 'date'?'':title }}推广用户量（人）</span>
				<span v-if="type == 9">{{ time == 'date'?'':title }}激活会员卡（张）</span>
				<!-- {{ time == 'date'?'':title }}{{ this.where.type == 1 ? "营业额（元）" : "订单量（份）" }} -->
			</div>
			<div class="money">{{cardData.data.now}}</div>
			<div class="increase acea-row row-between-wrapper">
				<div>
					{{ time == 'date'?'':title }}增长率：<span :class="cardData.data.increase_time_status === 1 ? 'red' : 'green'">{{ cardData.data.growth }}%
						<span class="iconfont" :class="
                cardData.data.increase_time_status === 1
                  ? 'icon-xiangshang1'
                  : 'icon-xiangxia2'
              "></span></span>
				</div>
				<div>
					{{ time == 'date'?'':title }}增长：<span :class="cardData.data.increase_time_status === 1 ? 'red' : 'green'">{{ cardData.data.differ }}
						<span class="iconfont" :class="
                cardData.data.increase_time_status === 1
                  ? 'icon-xiangshang1'
                  : 'icon-xiangxia2'
              "></span></span>
				</div>
			</div>
		</div>
		<div class="public-wrapper">
			<div class="conter">
				<div class="item" v-for="(item, index) in  cardData.list" :key="index">
					<div v-if="type == 8">
						<div class="datas">
							<div class="number_leftbox">
								<image :src="item.avatar" mode=""></image>
								<div class="number_font">
									<div >{{ item.nickname }}</div>
									<div class="times">加入时间：{{ item.spread_time }}</div>
								</div>
							</div>
							<div class="number_rightbox">
								<div>{{ item.order_count }} 单</div>
								<div>{{ item.order_price }} 元</div>
							</div>
						</div>
					</div>
					<div v-else-if="type == 9">
						<div class="data">
							<div class="leftbox">
								<image :src="item.avatar" mode=""></image>
								<span>{{ item.nickname }}</span>
							</div>
							<div >微信会员卡</div>
						</div>
						<div class="times">激活时间：{{ item.add_time }}</div>
					</div>
					<div v-else>
						<div class="data">
							<div class="leftbox">
								<image :src="item.uid?item.avatar:'../../static/yonghu.png'" mode=""></image>
								<span>{{ item.uid?item.nickname:'游客' }}</span>
							</div>
							<div v-if="type == 7">{{ item.price }} 元</div>
							<div v-else>{{ item.pay_price }} 元</div>
						</div>
						<div class="times">订单编号66：{{ item.order_id }}</div>
						<div class="times">下单时间：{{ item.add_time }}</div>
					</div>
					<!-- <div v-else-if="where.type == 6"> -->
					
				</div>
			</div>
		</div>
		<!-- #ifdef H5 || APP-PLUS -->
		<uni-calendar ref="calendar" :date="info.date" :insert="info.insert" :lunar="info.lunar" :startDate="info.startDate" :endDate="info.endDate" :range="info.range" @confirm="confirm" :showMonth="info.showMonth" />
		<div class="mask" @touchmove.prevent v-show="current === true" @click="close"></div>
		<!-- #endif -->
		<Loading :loaded="loaded" :loading="loading"></Loading>
	</div>
</template>
<script>
	import Loading from '@/components/Loading/index.vue'
	import uniCalendar from '@/components/uni-calendar/uni-calendar.vue'
	var canvaLineA = null;
	
	import {
		getStatisticsListApi
	} from "@/api/admin";
	const year = new Date().getFullYear();
	const month = new Date().getMonth() + 1;
	const day = new Date().getDate();
	export default {
		name: "Statistics",
		components: {
			uniCalendar,
			Loading
		},
		props: {},
		data: function() {
			return {
				cardData: {
					data:[],
					list:[]
				},
				datalist: false,
				tip: 1,
				value: [
					[year, month, day - 1],
					[year, month, day]
				],
				isrange: true,
				weekSwitch: false,
				ismulti: false,
				monFirst: true,
				clean: false, //简洁模式
				lunar: false, //显示农历
				renderValues: [],
				monthRange: [],
				current: false,
				where: {
					start: "",
					stop: "",
					is_manager: "",
					page: 1,
					limit: 15,
				},
				types: "", //类型|order=订单数|price=营业额
				time: "today", //时间|today=今天|yesterday=昨天|month=本月
				title: "", //时间|today=今天|yesterday=昨天|month=本月
				loaded: false,
				loading: false,
				list: [],
				// charts
				cWidth: '',
				cHeight: '',
				"LineA": {
					"categories": ["2012", "2013", "2014", "2015", "2016", "2017"],
					"series": [{
						"data": [35, 8, 25, 37, 4, 20]
					}]
				},
				info: {
					startDate: '',
					endDate: '',
					lunar: false,
					range: true,
					insert: false,
					selected: [],
					showMonth:false
				},
				type: '',
				before: '',
				after: ''
			};
		},
		// watch: {
		// 	"$route.params": function(newVal) {
		// 		var that = this;
		// 		if (newVal != undefined) {
		// 			that.setType(newVal.type);
		// 		}
		// 	}
		// },
		onLoad: function(options) {
			this.type = options.type;
			if (options.before) {
				this.before = options.before;
			}
			if (options.after) {
				this.after = options.after;
			}
			if(options.manager){
				this.where.is_manager = options.manager
			}
			this.cWidth = uni.upx2px(690);
			this.cHeight = uni.upx2px(500);
			this.setTime(options.time);
		},
		methods: {
			getList(){
				this.loading = true;
				// this.where.is_manager = uni.getStorageSync('SETMANAGER')
				getStatisticsListApi(this.type,this.where).then(res=>{
					this.cardData.data = res.data.data
					this.datalist = true
					this.where.page += 1;
					this.loading = false;
					this.cardData.list = this.cardData.list.concat(res.data.list);
					if(res.data.list.length < this.where.limit){
						this.tip = 2
					}
				})
			},
			setTime: function(time) {
				let self = this
				this.time = time;
				var year = new Date().getFullYear(),
					month = new Date().getMonth() + 1,
					day = new Date().getDate();
				this.list = [];
				this.tip = 1,
				this.cardData = {
					data:[],
					list:[]
				}
				this.where.page = 1;
				this.loaded = false;
				this.loading = false;
				switch (time) {
					case "today":
						this.where.start =
							new Date(Date.parse(year + "/" + month + "/" + day)).getTime() /
							1000;
						this.where.stop =
							new Date(Date.parse(year + "/" + month + "/" + day)).getTime() /
							1000 +
							24 * 60 * 60 -
							1;
						this.title = "今日";
						this.getList()
						break;
					case "yesterday":
						this.where.start =
							new Date(Date.parse(year + "/" + month + "/" + day)).getTime() /
							1000 -
							24 * 60 * 60;
						this.where.stop =
							new Date(Date.parse(year + "/" + month + "/" + day)).getTime() /
							1000 -
							1;
						this.title = "昨日";
						this.getList()
						break;
					case "month":
						this.where.start =
							new Date(year, new Date().getMonth(), 1).getTime() / 1000;
						this.where.stop = new Date(year, month, 1).getTime() / 1000 - 1;
						this.title = "本月";
						this.getList()
						break;
					case "seven":
						this.where.start =
							new Date(Date.parse(year + "/" + month + "/" + day)).getTime() /
							1000 +
							24 * 60 * 60 -
							7 * 3600 * 24;
						this.where.stop =
							new Date(Date.parse(year + "/" + month + "/" + day)).getTime() /
							1000 +
							24 * 60 * 60 -
							1;
						this.title = "七日";
						this.getList()
						break;
					// #ifdef MP
					case "date":
						let star = new Date(self.before).getTime()/1000
						let stop = new Date(self.after).getTime()/1000
						self.where.start = star
						self.where.stop = stop
						Promise.all([self.getList()]);
						break;
					// #endif
				}
			},
			
			dateTitle: function() {
				// #ifdef H5 || APP-PLUS
				this.$refs.calendar.open()
				this.time = 'date'
				// #endif
				// #ifdef MP
				uni.navigateTo({
					url: '/pages/admin/store/custom_date/index?type=' + this.type
				});
				// #endif
				// this.current = true;
			},
			close: function() {
				this.current = false;
			},
			// 日历确定
			confirm(e) {
				let self = this
				let star,stop;
				if(e.range.after && e.range.before){
					if(e.range.before>e.range.after){
						 star = new Date(e.range.after+' 00:00:00').getTime()/1000
						 stop = new Date(e.range.before+' 23:59:59').getTime()/1000
					}else{
						 star = new Date(e.range.before+' 00:00:00').getTime()/1000
						 stop = new Date(e.range.after+' 23:59:59').getTime()/1000
					}
					self.where.start = star
					self.where.stop = stop
					self.list = [];
					self.cardData.list = [];
					// self.tip =1
					self.where.page = 1;
					self.loaded = false;
					self.loading = false;
					Promise.all([self.getList()]);
				}
			},
		},
		onReachBottom() {
			if(this.tip == 1){
				this.getList()
			}
		}
	};
</script>
<style lang="scss" scoped>
	/*交易额统计*/
	.statistical-page .navs {
		width: 100%;
		height: 96upx;
		background-color: #fff;
		overflow: hidden;
		line-height: 96upx;
		position: fixed;
		top: 0;
		left: 0;
		z-index: 9;
	}

	.statistical-page .navs .list {
		overflow-y: hidden;
		overflow-x: auto;
		white-space: nowrap;
		-webkit-overflow-scrolling: touch;
		width: 100%;
	}

	.statistical-page .navs .item {
		font-size: 32upx;
		color: #282828;
		margin-left: 60upx;
		display: inline-block;
	}

	.statistical-page .navs .item.on {
		color: #2291f8;
	}

	.statistical-page .navs .item .iconfont {
		font-size: 25upx;
		margin-left: 13upx;
	}

	.statistical-page .wrapper {
		// width: 740upx;
		background-color: #fff;
		border-radius: 10upx;
		margin: 119upx 30upx 0 30upx;
		padding: 50upx 30upx;
	}

	.statistical-page .wrapper .title {
		font-size: 30upx;
		color: #999;
		text-align: center;
	}

	.statistical-page .wrapper .money {
		font-size: 72upx;
		color: #1890FF;
		text-align: center;
		margin-top: 10upx;
	}

	.statistical-page .wrapper .increase {
		font-size: 28upx;
		color: #999;
		margin-top: 20upx;
	}

	.statistical-page .wrapper .increase .red {
		color: #ff6969;
	}

	.statistical-page .wrapper .increase .green {
		color: #1abb1d;
	}

	.statistical-page .wrapper .increase .iconfont {
		font-size: 23upx;
		margin-left: 15upx;
	}

	.statistical-page .chart {
		width: 690upx;
		background-color: #fff;
		border-radius: 10upx;
		margin: 23upx auto 0 auto;
		/* padding: 25upx 22upx 0 22upx; */
	}
	
	.statistical-page .chart .chart-title{
		padding:20upx 20upx 10upx;
		font-size: 26upx;
		color: #999;
	}

	.statistical-page .chart canvas {
		width: 100%;
		height: 530rpx;
	}

	.statistical-page .chart .company {
		font-size: 26upx;
		color: #999;
	}

	.yd-confirm {
		background-color: #fff;
		font-size: unset;
		width: 540upx;
		height: 250upx;
		border-radius: 40upx;
	}

	.yd-confirm-hd {
		text-align: center;
	}

	.yd-confirm-title {
		color: #030303;
		font-weight: bold;
		font-size: 36upx;
	}

	.yd-confirm-bd {
		text-align: center;
		font-size: 28upx;
		color: #333333;
	}

	.yd-confirm-ft {
		line-height: 90upx;
		margin-top: 14px;
		border-top: 1upx solid #eee;
	}

	.yd-confirm-ft>a {
		color: #e93323;
	}

	.yd-confirm-ft>a.primary {
		border-left: 1upx solid #eee;
		color: #e93323;
	}

	.calendar-wrapper {
		position: fixed;
		bottom: 0;
		left: 0;
		width: 100%;
		z-index: 777;
		transform: translate3d(0, 100%, 0);
		transition: all 0.3s cubic-bezier(0.25, 0.5, 0.5, 0.9);
	}

	.calendar-wrapper.on {
		transform: translate3d(0, 0, 0);
	}

	.statistical-page .wrapper .increase {
		font-size: 24upx;
	}

	.statistical-page .wrapper .increase .iconfont {
		margin-left: 0;
	}

	.public-wrapper .title {
		font-size: 30upx;
		color: #282828;
		padding: 0 30upx;
		margin-bottom: 20upx;
	}

	.public-wrapper .title .iconfont {
		color: #2291f8;
		font-size: 40upx;
		margin-right: 13upx;
		vertical-align: middle;
	}

	.public-wrapper {
		margin: 18upx auto 0 auto;
		width: 690upx;
		background-color: #fff;
		border-radius: 10upx;
		// padding-top: 25upx;
	}

	

	.public-wrapper .conter {
		padding: 0 30upx;
	}

	.public-wrapper .conter .item {
		border-bottom: 2upx solid #f7f7f7;
		// height: 194upx;
		font-size: 24upx;
		display: flex;
		flex-direction: column;
		padding: 30upx 0upx;
		.times{
			color: #999999;
			margin-bottom: 8upx;
		}
		.data{
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 18upx;
			.leftbox{
				display: flex;
				align-items: center;
				font-weight: 400;
				color: #000000;
				image{
					width: 46upx;
					height: 46upx;
					border-radius: 50%;
					margin-right: 20upx;
				}
			}
		}
		.datas{
			display: flex;
			justify-content: space-between;
			align-items: center;
			.number_leftbox{
				display: flex;
				justify-content: start;
				image{
					width: 96upx;
					height: 96upx;
					border-radius: 50%;
					margin-right: 20upx;
				}
				.number_font{
					display: flex;
					flex-direction: column;
					font-size: 30upx;
					padding-top: 6upx;
					.times{
						color: #999999;
						font-size: 24upx;
						margin-top: 10upx;
					}
				}
			}
			.number_rightbox{
				text-align: right;
				line-height: 46upx;
			}
		}
	}
</style>
