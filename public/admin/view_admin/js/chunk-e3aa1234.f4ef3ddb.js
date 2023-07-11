(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-e3aa1234"],{d7d0:function(t,e,i){"use strict";i.r(e);var o=function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("div",[i("PageHeader",{staticClass:"product_tabs",attrs:{title:t.$route.meta.title,"hidden-breadcrumb":""}},[i("div",{attrs:{slot:"title"},slot:"title"},[i("router-link",{attrs:{to:{path:"/admin/marketing/live/live_goods"}}},[i("div",{staticClass:"font-sm after-line"},[i("span",{staticClass:"iconfont iconfanhui"}),i("span",{staticClass:"pl10"},[t._v("返回")])])]),i("span",{staticClass:"mr20 ml16",domProps:{textContent:t._s(t.$route.meta.title)}})],1)]),i("Card",{staticClass:"ivu-mt",attrs:{bordered:!1,"dis-hover":""}},[i("Form",{ref:"formValidate",staticClass:"tabform",attrs:{model:t.formValidate,"label-width":t.labelWidth,"label-position":t.labelPosition},nativeOn:{submit:function(t){t.preventDefault()}}},[i("Row",{attrs:{gutter:24,type:"flex"}},[i("Col",{attrs:{span:"24"}},[i("FormItem",{attrs:{label:"选择商品："}},[i("div",{staticClass:"box"},[t._l(t.goodsList,(function(e,o){return i("div",{key:o,staticClass:"box-item"},[i("img",{attrs:{src:e.image,alt:""}}),i("Icon",{attrs:{type:"ios-close-circle",size:"20"},on:{click:function(e){return t.bindDelete(o)}}})],1)})),i("div",{staticClass:"upload-box",on:{click:function(e){t.modals=!0}}},[i("Icon",{attrs:{type:"ios-camera-outline",size:"36"}})],1)],2)])],1)],1)],1),t.goodsList.length>0?i("div",{staticClass:"active-btn"},[i("Button",{attrs:{type:"success"},on:{click:t.liveGoods}},[t._v("生成直播商品")])],1):t._e(),t.isShowBox?i("div",{staticClass:"table-box"},[i("Table",{ref:"table",staticClass:"mt25",attrs:{columns:t.columns1,data:t.tabList,loading:t.loading,"no-userFrom-text":"暂无数据","no-filtered-userFrom-text":"暂无筛选结果"},scopedSlots:t._u([{key:"img",fn:function(e){var o=e.row;e.index;return[i("div",{staticClass:"product_box"},[i("img",{attrs:{src:o.image,alt:""}}),i("Tooltip",{attrs:{theme:"dark","max-width":"300",delay:600,content:o.store_name}},[i("span",{staticClass:"goods_title line1"},[t._v(t._s(o.store_name))])])],1)]}},{key:"action",fn:function(e){var o=e.row,s=e.index;return[i("a",{on:{click:function(e){return t.del(o,s)}}},[t._v("删除")])]}}],null,!1,4243220451)})],1):t._e(),i("Card",{staticClass:"fixed-card",attrs:{bordered:!1,"dis-hover":""}},[i("div",{staticClass:"acea-row row-center"},[i("Button",{staticStyle:{width:"8%"},attrs:{type:"primary"},on:{click:t.bindSub}},[t._v("提交")])],1)])],1),i("Modal",{staticClass:"paymentFooter",attrs:{title:"商品列表",scrollable:"",width:"900","footer-hide":!0},model:{value:t.modals,callback:function(e){t.modals=e},expression:"modals"}},[t.modals?i("goods-list",{ref:"goodslist",attrs:{ischeckbox:!0,isLive:!0},on:{getProductId:t.getProductId}}):t._e()],1)],1)},s=[],n=i("2f62"),a=i("c4ad"),r=i("47c5");function c(t,e){var i=Object.keys(t);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(t);e&&(o=o.filter((function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable}))),i.push.apply(i,o)}return i}function l(t){for(var e=1;e<arguments.length;e++){var i=null!=arguments[e]?arguments[e]:{};e%2?c(i,!0).forEach((function(e){d(t,e,i[e])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(i)):c(i).forEach((function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(i,e))}))}return t}function d(t,e,i){return e in t?Object.defineProperty(t,e,{value:i,enumerable:!0,configurable:!0,writable:!0}):t[e]=i,t}var u={name:"add_goods",components:{goodsList:a["default"]},computed:l({},Object(n["e"])("admin/layout",["isMobile"]),{labelWidth:function(){return this.isMobile?void 0:100},labelPosition:function(){return this.isMobile?"top":"right"}}),data:function(){var t=this;return{isShowBox:!1,loading:!1,modals:!1,goodsList:[],tempGoods:{},formValidate:{},columns1:[{key:"id",title:"商品ID"},{slot:"img",title:"商品信息",minWidth:250},{key:"price",title:"直播售价",render:function(e,i){return e("Input",{props:{type:"number",value:i.row.price},on:{input:function(e){t.tabList[i.index].price=e}}})}},{key:"cost_price",title:"直播原价",render:function(e,i){return e("Input",{props:{type:"number",value:i.row.cost_price},on:{input:function(e){t.tabList[i.index].cost_price=e}}})}},{key:"stock",title:"库存"},{slot:"action",fixed:"right",title:"操作"}],tabList:[]}},methods:{liveGoods:function(){var t=this,e=[];this.goodsList.map((function(t){e.push(t.product_id)})),Object(r["g"])({product_id:e}).then((function(e){t.tabList=e.data,t.isShowBox=!0})).catch((function(e){t.$Message.error(e.msg)}))},getProductId:function(t){var e=this;this.goodsList=this.goodsList.concat(t),this.$nextTick((function(t){setTimeout((function(){e.modals=!1}),300)}))},bindDelete:function(t){this.goodsList.splice(t,1)},del:function(t,e){this.tabList.splice(e,1)},bindSub:function(){var t=this;Object(r["f"])({goods_info:this.tabList}).then((function(e){t.$Message.success("添加成功"),setTimeout((function(){t.$router.push({path:"/admin/marketing/live/live_goods"})}),500)})).catch((function(e){t.$Message.error(e.msg)}))}}},f=u,p=(i("f183"),i("2877")),m=Object(p["a"])(f,o,s,!1,null,"d09cc9d2",null);e["default"]=m.exports},e20e:function(t,e,i){},f183:function(t,e,i){"use strict";var o=i("e20e"),s=i.n(o);s.a}}]);