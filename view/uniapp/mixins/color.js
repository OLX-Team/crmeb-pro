// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

export default {
	data() {
		return {
			colorStyle: '',
			navigation: 0,
			colorNum: 0
		};
	},
	created() {
		this.colorStyle = uni.getStorageSync('viewColor')
		uni.$on('ok', data => {
			this.colorStyle = data
		})
		this.navigation = uni.getStorageSync('navigation')
		uni.$on('navOk', data => {
			this.navigation = data
		})
	},
	methods: {
		colorData(){
			this.colorNum = uni.getStorageSync('statusColor')
			uni.$on('colorOk', data => {
				this.colorNum = data
			})
		}
	}
};
