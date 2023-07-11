<template>
    <view @longtap.stop="longtap">
		<canvas 
			:width="destWidth" 
			:height="destHeight" 
			:canvas-id="item.id" 
			:id="item.id" 
			:style="{width:width,height: height}" 
			v-for="(item,index) in listCode" 
			:key="item.id" 
			@error="handleError"></canvas>
	</view>
</template>
<script>
	import { BarCode, GetCodeImg,GetPixelRatio,GetPx } from '../../js_sdk';
	import { getUUid, deepClone ,platform} from '../../common/helper.js'
	export default {
		name: 'WBarcode',
		props:{
			options:{
				type: Object,
				required: true,
				default: () =>{
					return {
						
					}
				}
			}
		},
		data () {
			return {
				destHeight: 0,
				destWidth: 0,
				width: 0,
				height: 0,
				listCode: [],
				id: getUUid()
			}
		},
		mounted() {
			this.height = GetPx(this.options.height) + 'px';
			this.width = GetPx(this.options.width) + 'px';
			this.destHeight = GetPx(this.options.height) * GetPixelRatio() + 'px';
			this.destWidth = GetPx(this.options.width) * GetPixelRatio() + 'px';
			this.SpecialTreatment(this.options)
			this.$nextTick(()=>{
				this.generateCode();
			})
		},
		watch: {
			options:{
				deep: true,
				handler (val) {
					this.height = GetPx(val.height) + 'px';
					this.width = GetPx(val.width) + 'px';
					this.destHeight = GetPx(this.options.height) * GetPixelRatio() + 'px';
					this.destWidth = GetPx(this.options.width) * GetPixelRatio() + 'px';
					this.SpecialTreatment(val)
					setTimeout(()=>{// h5平台动态改变canvas大小
						this.generateCode();
					},50)
				}
			}
		},
		methods: {
			longtap (e){
				this.$emit('press',e)
			},
			handleError (e) {//当发生错误时触发 error 事件，字节跳动小程序与飞书小程序不支持
				this.$emit('error',e.detail)
			},
			SpecialTreatment (val) {//微信小程序渲染多个canvas特殊处理
					let obj = deepClone(val);
					obj.id = this.id;
					this.listCode = [obj]
			},
			generateCode () {
				try{
					const parameter = {...this.options,source: platform(),id: this.id,ctx: this};
					BarCode(parameter,(res)=>{
						this.$emit('generate',res)
					})
				}catch(err){}
			},
			async GetCodeImg (){
				try{
					return  await GetCodeImg({id: this.id,width: this.options.width,height: this.options.height,ctx: this});
				}catch(e){}
			}
		}
	}
</script>