(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-goods_list-index"],{"02cf":function(t,i,e){"use strict";var a=e("adb0"),s=e.n(a);s.a},"1e58":function(t,i,e){"use strict";e.r(i);var a=e("236b1"),s=e("2817");for(var o in s)"default"!==o&&function(t){e.d(i,t,(function(){return s[t]}))}(o);e("e68a");var r,n=e("f0c5"),c=Object(n["a"])(s["default"],a["b"],a["c"],!1,null,"0f3f9d0a",null,!1,a["a"],r);i["default"]=c.exports},"236b1":function(t,i,e){"use strict";var a;e.d(i,"b",(function(){return s})),e.d(i,"c",(function(){return o})),e.d(i,"a",(function(){return a}));var s=function(){var t=this,i=t.$createElement,a=t._self._c||i;return a("v-uni-view",{style:t.colorStyle},[a("v-uni-view",{staticClass:"productList"},[a("v-uni-view",{staticClass:"search bg-color acea-row row-between-wrapper"},[t.promotions_type?a("v-uni-view",{staticClass:"iconfont icon-fanhui2 fanhui",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.goCart.apply(void 0,arguments)}}}):a("v-uni-view",{staticClass:"home acea-row row-center-wrapper",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.moreNav.apply(void 0,arguments)}}},[a("v-uni-view",{staticClass:"iconfont icon-gengduo2"})],1),a("v-uni-view",{staticClass:"input acea-row row-between-wrapper",class:t.promotions_type?"on":""},[a("v-uni-text",{staticClass:"iconfont icon-sousuo"}),a("v-uni-input",{attrs:{placeholder:"搜索商品名称","placeholder-class":"placeholder","confirm-type":"search",name:"search",value:t.where.keyword},on:{confirm:function(i){arguments[0]=i=t.$handleEvent(i),t.searchSubmit.apply(void 0,arguments)}}})],1),t.promotions_type?t._e():a("v-uni-view",{staticClass:"iconfont",class:1==t.is_switch?"icon-pailie":"icon-tupianpailie",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.Changswitch.apply(void 0,arguments)}}})],1),a("v-uni-view",{staticClass:"nav acea-row row-middle row-around"},[a("v-uni-view",{staticClass:"item line1",class:t.title?"font-num":"",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.set_where(1)}}},[t._v(t._s(t.title?t.title:"默认"))]),a("v-uni-view",{staticClass:"item",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.set_where(2)}}},[t._v("价格"),1==t.price?a("v-uni-image",{attrs:{src:e("b1bc")}}):2==t.price?a("v-uni-image",{attrs:{src:e("4cac0")}}):a("v-uni-image",{attrs:{src:e("21bb")}})],1),a("v-uni-view",{staticClass:"item",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.set_where(3)}}},[t._v("销量"),1==t.stock?a("v-uni-image",{attrs:{src:e("b1bc")}}):2==t.stock?a("v-uni-image",{attrs:{src:e("4cac0")}}):a("v-uni-image",{attrs:{src:e("21bb")}})],1),t.brandList.length?a("v-uni-view",{staticClass:"item",class:{clored:t.brandArray.length>0},on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.set_brand.apply(void 0,arguments)}}},[t._v("品牌"),a("v-uni-image",{attrs:{src:e("2ff9")}})],1):t._e(),a("homeList",{attrs:{navH:t.navH,goodList:t.goodList,currentPage:t.currentPage,sysHeight:t.sysHeight,goodsShow:!0}})],1),0==t.is_switch?a("v-uni-view",{staticClass:"list acea-row row-between-wrapper",class:1==t.is_switch?"":"on"},[a("v-uni-view",{staticClass:"title"},[a("v-uni-text",{staticClass:"label"},[t._v(t._s(t.promotionsInfo.title))]),t._v(t._s(t.promotionsInfo.desc))],1),t._l(t.productList,(function(i,e){return a("v-uni-view",{key:e,staticClass:"item",class:1==t.is_switch?"":"on",attrs:{"hover-class":"none"},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.godDetail(i)}}},[a("v-uni-view",{staticClass:"pictrue",class:1==t.is_switch?"":"on"},[a("v-uni-image",{class:1==t.is_switch?"":"on",attrs:{src:i.image}}),i.stock<=0?a("v-uni-view",{staticClass:"masks acea-row row-center-wrapper"},[a("v-uni-view",{staticClass:"bg"},[a("v-uni-view",[t._v("暂时")]),a("v-uni-view",[t._v("售罄")])],1)],1):t._e()],1),a("v-uni-view",{staticClass:"text",class:1==t.is_switch?"":"on"},[a("v-uni-view",{staticClass:"nameCon"},[a("v-uni-view",{staticClass:"name line1"},[t._v(t._s(i.store_name))]),i.activity&&"1"===i.activity.type&&!t.promotions_type?a("v-uni-text",{staticClass:"label"},[t._v("秒杀")]):t._e(),i.activity&&"2"===i.activity.type&&!t.promotions_type?a("v-uni-text",{staticClass:"label"},[t._v("砍价")]):t._e(),i.activity&&"3"===i.activity.type&&!t.promotions_type?a("v-uni-text",{staticClass:"label"},[t._v("拼团")]):t._e(),i.promotions.title?a("v-uni-text",{staticClass:"label"},[t._v(t._s(i.promotions.title))]):t._e()],1),a("v-uni-view",{staticClass:"vip acea-row row-middle on"},[a("v-uni-view",{staticClass:"money font-color",class:1==t.is_switch?"":"on"},[t._v("￥"),a("v-uni-text",{staticClass:"num"},[t._v(t._s(i.price))])],1),i.vip_price&&i.vip_price>0?a("v-uni-view",{staticClass:"vip-money acea-row row-middle"},[a("v-uni-view",[t._v("￥"+t._s(i.vip_price))]),i.price_type&&"member"==i.price_type?a("v-uni-view",{staticClass:"icon on"},[a("v-uni-text",{staticClass:"iconfont icon-huangguan4"}),t._v("SVIP")],1):t._e(),i.price_type&&"level"==i.price_type?a("v-uni-view",{staticClass:"icon"},[a("v-uni-text",{staticClass:"iconfont icon-dengjitubiao"}),t._v(t._s(i.level_name))],1):t._e()],1):t._e()],1),a("v-uni-view",{staticClass:"sales acea-row row-between-wrapper"},[a("v-uni-view",{staticClass:"acea-row"},[a("v-uni-view",[t._v("已售"+t._s(i.sales)+t._s(i.unit_name||"件"))]),a("v-uni-view",{staticClass:"score"},[t._v("评分 "+t._s(i.star))])],1),t.promotions_type&&0==i.product_type&&!i.custom_form.length?a("v-uni-view",{staticClass:"icon acea-row row-center-wrapper",on:{click:function(e){e.stopPropagation(),arguments[0]=e=t.$handleEvent(e),t.joinCart(i)}}},[a("v-uni-text",{staticClass:"iconfont icon-gouwuche"})],1):t._e()],1)],1)],1)})),t.productList.length>0?a("v-uni-view",{staticClass:"loadingicon acea-row row-center-wrapper"},[a("v-uni-text",{staticClass:"loading iconfont icon-jiazai",attrs:{hidden:0==t.loading}}),t._v(t._s(t.loadTitle))],1):t._e(),t.productList.length>3?a("v-uni-view",{staticStyle:{height:"100rpx",width:"100%"}}):t._e(),a("v-uni-view",{staticClass:"footer acea-row row-between-wrapper"},[a("v-uni-view",{staticClass:"acea-row row-middle"},[a("v-uni-view",{staticClass:"icon"},[a("v-uni-text",{staticClass:"iconfont icon-pinzhongqiquan"}),a("v-uni-view",{staticClass:"num acea-row row-center-wrapper"},[t._v(t._s(t.totalNum))])],1),a("v-uni-view",{staticClass:"right"},[a("v-uni-view",{staticClass:"num"},[t._v("小计："),a("v-uni-text",{staticClass:"money"},[t._v("¥"+t._s(t.totalPrice||0))])],1),4!=this.promotions_type?a("v-uni-view",[t.promotionsInfo.differ_threshold>0?a("v-uni-view",[t._v("再买"+t._s(t.promotionsInfo.differ_threshold)+t._s(1==t.promotionsInfo.threshold_type?"元":"件")),t.promotionsInfo.differ_price||t.promotionsInfo.differ_discount?a("v-uni-text",[t._v("，"),t.promotionsInfo.differ_price?a("v-uni-text",[t._v("可减"+t._s(t.promotionsInfo.differ_price)+"元")]):a("v-uni-text",[t._v("可打"+t._s(parseFloat(t.promotionsInfo.differ_discount)/10)+"折")])],1):t._e()],1):a("v-uni-view",[t.promotionsInfo.reach_threshold?a("v-uni-text",[t._v("已购满"+t._s(t.promotionsInfo.reach_threshold)+t._s(1==t.promotionsInfo.threshold_type?"元":"件")+",")]):t._e(),t._v("已减"+t._s(t.promotionsInfo.sum_promotions_price)+"元")],1)],1):t._e()],1)],1),a("v-uni-view",{staticClass:"bnt",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.goCart.apply(void 0,arguments)}}},[t._v("去购物车")])],1)],2):a("v-uni-view",{staticClass:"list waterList"},[a("waterfallsFlow",{ref:"waterfallsFlow",attrs:{list:t.productList},on:{"wapper-lick":function(i){arguments[0]=i=t.$handleEvent(i),t.godDetail.apply(void 0,arguments)}},scopedSlots:t._u([{key:"default",fn:function(i){return[a("v-uni-view",{staticClass:"waterfalls"},[a("v-uni-view",{staticClass:"name line2"},[t._v(t._s(i.store_name))]),i.activity&&"1"===i.activity.type&&!t.promotions_type?a("span",{staticClass:"label"},[t._v("秒杀")]):t._e(),i.activity&&"2"===i.activity.type&&!t.promotions_type?a("span",{staticClass:"label"},[t._v("砍价")]):t._e(),i.activity&&"3"===i.activity.type&&!t.promotions_type?a("span",{staticClass:"label"},[t._v("拼团")]):t._e(),i.promotions.title?a("v-uni-text",{staticClass:"label"},[t._v(t._s(i.promotions.title))]):t._e(),a("v-uni-view",{staticClass:"vip acea-row row-middle"},[a("v-uni-view",{staticClass:"money font-color"},[t._v("￥"),a("v-uni-text",{staticClass:"num"},[t._v(t._s(i.price.toString().split(".")[0]))]),i.price.toString().split(".").length>1?a("v-uni-text",{staticClass:"nums"},[t._v("."+t._s(i.price.toString().split(".")[1]))]):t._e()],1),i.vip_price&&i.vip_price>0?a("v-uni-view",{staticClass:"vip-money acea-row row-middle"},[a("v-uni-view",[t._v("￥"+t._s(i.vip_price))]),i.price_type&&"member"==i.price_type?a("v-uni-view",{staticClass:"icon on"},[a("v-uni-text",{staticClass:"iconfont icon-huangguan4"}),t._v("SVIP")],1):t._e(),i.price_type&&"level"==i.price_type?a("v-uni-view",{staticClass:"icon"},[a("v-uni-text",{staticClass:"iconfont icon-v"}),t._v(t._s(i.level_name))],1):t._e()],1):t._e()],1),a("v-uni-view",{staticClass:"vip acea-row row-between-wrapper"},[a("v-uni-view",[t._v("已售"+t._s(i.sales)+t._s(i.unit_name||"件"))]),a("v-uni-view",[t._v("评分 "+t._s(i.star))])],1)],1)]}}])}),t.productList.length>0?a("v-uni-view",{staticClass:"loadingicon acea-row row-center-wrapper"},[a("v-uni-text",{staticClass:"loading iconfont icon-jiazai",attrs:{hidden:0==t.loading}}),t._v(t._s(t.loadTitle))],1):t._e()],1)],1),0==t.productList.length&&t.where.page>1?a("v-uni-view",{staticClass:"noCommodity"},[a("v-uni-view",{staticClass:"pictrue"},[a("v-uni-image",{attrs:{src:e("be78")}})],1),a("recommend",{attrs:{hostProduct:t.hostProduct}})],1):t._e(),a("productWindow",{attrs:{attr:t.attr,isShow:1,iSplus:1,iScart:1,storeInfo:t.storeInfo,id:"product-window",is_vip:t.is_vip},on:{myevent:function(i){arguments[0]=i=t.$handleEvent(i),t.onMyEvent.apply(void 0,arguments)},ChangeAttr:function(i){arguments[0]=i=t.$handleEvent(i),t.ChangeAttr.apply(void 0,arguments)},ChangeCartNum:function(i){arguments[0]=i=t.$handleEvent(i),t.ChangeCartNum.apply(void 0,arguments)},attrVal:function(i){arguments[0]=i=t.$handleEvent(i),t.attrVal.apply(void 0,arguments)},iptCartNum:function(i){arguments[0]=i=t.$handleEvent(i),t.iptCartNum.apply(void 0,arguments)},goCat:function(i){arguments[0]=i=t.$handleEvent(i),t.goCat.apply(void 0,arguments)}}}),t.navigation?a("home"):t._e(),a("v-uni-view",{staticClass:"mask",attrs:{hidden:!1===t.brandtip},on:{touchmove:function(i){i.preventDefault(),arguments[0]=i=t.$handleEvent(i)},click:function(i){arguments[0]=i=t.$handleEvent(i),t.closeBrand.apply(void 0,arguments)}}}),a("v-uni-view",{staticClass:"selectbrand",class:!0===t.brandtip?"on":""},[a("v-uni-view",{staticClass:"box"},[a("v-uni-view",{staticClass:"selet"},t._l(t.brandList,(function(i,e){return a("v-uni-view",{key:e,staticClass:"seletbox acea-row row-center-wrapper",class:{seleton:-1!=t.brandArray.indexOf(i.id)},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.seletBrand(i.id)}}},[t._v(t._s(i.brand_name))])})),1),a("v-uni-view",{staticClass:"btn"},[a("div",{staticClass:"sambox reset",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.brandReset.apply(void 0,arguments)}}},[t._v("重置")]),a("div",{staticClass:"sambox ok",on:{click:function(i){arguments[0]=i=t.$handleEvent(i),t.brandOk.apply(void 0,arguments)}}},[t._v("确定")])])],1)],1)],1)},o=[]},"25db":function(t,i,e){"use strict";var a=e("4ea4");e("99af"),e("4160"),e("c975"),e("a434"),e("ac1f"),e("1276"),e("159b"),Object.defineProperty(i,"__esModule",{value:!0}),i.default=void 0;var s=a(e("f176")),o=a(e("67fc")),r=a(e("6ab7")),n=a(e("ca28")),c=e("16a8"),d=e("fa9e"),l=a(e("34f7")),u=e("26cb"),f=e("4913"),h=e("21c4"),p=a(e("d0ca")),v={computed:(0,u.mapGetters)(["uid","isLogin"]),components:{recommend:l.default,home:s.default,homeList:o.default,waterfallsFlow:n.default,productWindow:r.default},mixins:[p.default],data:function(){return{id:0,productValue:[],is_vip:0,attr:{cartAttr:!1,productAttr:[],productSelect:{}},attrValue:"",navH:22,sysHeight:0,goodList:!0,currentPage:!1,brandtip:!1,brandArray:[],productList:[],is_switch:!0,where:{sid:0,keyword:"",priceOrder:"",salesOrder:"",news:0,page:1,limit:20,cid:0,brand_id:[],promotions_id:0},price:0,stock:0,nows:!1,loadend:!1,loading:!1,loadTitle:"加载更多",title:"",hostProduct:[],hotPage:1,hotLimit:10,hotScroll:!1,brandList:[],storeInfo:{},promotions_type:0,totalPrice:0,promotionsInfo:{},totalNum:0}},onLoad:function(t){this.where.cid=t.cid||0,this.$set(this.where,"sid",t.sid||0),this.title=t.title||"",this.$set(this.where,"keyword",t.searchValue||""),this.$set(this.where,"productId",t.productId||""),t.promotions_type&&(this.promotions_type=t.promotions_type,this.where.promotions_id=t.promotions_id,t.promotions_type&&(this.is_switch=!1)),this.get_product_list(),this.getBrand()},onShow:function(){!1===this.isLogin?(0,d.toLogin)():this.is_switch||(this.getCartList(),this.getCartNum())},methods:{getCartNum:function(){var t=this;(0,h.getCartCounts)().then((function(i){t.totalNum=i.data.count})).catch((function(i){return t.$util.Tips({title:i})}))},getCartList:function(){var t=this,i=0;(0,h.getCartList)().then((function(e){var a=e.data,s=e.data.valid;s.forEach((function(e){e.promotions.forEach((function(i){t.where.promotions_id==i.id&&(t.promotionsInfo=i)})),e.cart.forEach((function(e){i=t.$util.$h.Add(i,t.$util.$h.Mul(e.truePrice,e.cart_num))}))})),t.totalPrice=t.$util.$h.Sub(i,a.deduction.coupon_price)})).catch((function(i){return t.$util.Tips({title:i})}))},goCart:function(){uni.switchTab({url:"/pages/order_addcart/order_addcart"})},goCat:function(t){var i=this,e=i.productValue[this.attrValue];if(i.attr.productAttr.length&&void 0===e)return i.$util.Tips({title:"产品库存不足，请选择其它属性"});if(i.attr.productSelect.cart_num<=0)return i.attr.productSelect.cart_num=1,i.$util.Tips({title:"请先选择属性"});var a={productId:i.id,cartNum:i.attr.productSelect.cart_num,new:0,uniqueId:void 0!==i.attr.productSelect?i.attr.productSelect.unique:""};(0,c.postCartAdd)(a).then((function(e){i.isOpen=!1,i.attr.cartAttr=!1,t?uni.navigateTo({url:"/pages/users/order_confirm/index?new=1&cartId="+e.data.cartId}):i.$util.Tips({title:"添加购物车成功"}),i.getCartNum(),i.getCartList()})).catch((function(t){return i.isOpen=!1,i.$util.Tips({title:t})}))},joinCart:function(t){!1===this.isLogin?(0,d.toLogin)():(uni.showLoading({title:"加载中"}),this.getAttrs(t.id),this.$set(this,"id",t.id),this.$set(this.attr,"cartAttr",!0))},getAttrs:function(t){var i=this;(0,c.getAttr)(t,0).then((function(t){uni.hideLoading(),i.$set(i.attr,"productAttr",t.data.productAttr),i.$set(i,"productValue",t.data.productValue),i.$set(i,"is_vip",t.data.storeInfo.is_vip),i.$set(i,"storeInfo",t.data.storeInfo),i.DefaultSelect()}))},DefaultSelect:function(){var t=this.attr.productAttr,i=[];for(var e in this.productValue)if(this.productValue[e].stock>0){i=this.attr.productAttr.length?e.split(","):[];break}for(var a=0;a<t.length;a++)this.$set(t[a],"index",i[a]);var s=this.productValue[i.join(",")];s&&t.length?(this.$set(this.attr.productSelect,"store_name",this.storeInfo.store_name),this.$set(this.attr.productSelect,"image",s.image),this.$set(this.attr.productSelect,"price",s.price),this.$set(this.attr.productSelect,"stock",s.stock),this.$set(this.attr.productSelect,"unique",s.unique),this.$set(this.attr.productSelect,"cart_num",1),this.$set(this,"attrValue",i.join(",")),this.$set(this.attr.productSelect,"vip_price",s.vip_price)):!s&&t.length?(this.$set(this.attr.productSelect,"store_name",this.storeInfo.store_name),this.$set(this.attr.productSelect,"image",this.storeInfo.image),this.$set(this.attr.productSelect,"price",this.storeInfo.price),this.$set(this.attr.productSelect,"stock",0),this.$set(this.attr.productSelect,"unique",""),this.$set(this.attr.productSelect,"cart_num",0),this.$set(this.attr.productSelect,"vip_price",this.storeInfo.vip_price),this.$set(this,"attrValue","")):s||t.length||(this.$set(this.attr.productSelect,"store_name",this.storeInfo.store_name),this.$set(this.attr.productSelect,"image",this.storeInfo.image),this.$set(this.attr.productSelect,"price",this.storeInfo.price),this.$set(this.attr.productSelect,"stock",this.storeInfo.stock),this.$set(this.attr.productSelect,"unique",this.storeInfo.unique||""),this.$set(this.attr.productSelect,"cart_num",1),this.$set(this.attr.productSelect,"vip_price",this.storeInfo.vip_price),this.$set(this,"attrValue",""))},iptCartNum:function(t){this.$set(this.attr.productSelect,"cart_num",t)},attrVal:function(t){this.$set(this.attr.productAttr[t.indexw],"index",this.attr.productAttr[t.indexw].attr_values[t.indexn])},onMyEvent:function(){this.$set(this.attr,"cartAttr",!1)},ChangeAttr:function(t){var i=this.productValue[t];i&&i.stock>0?(this.$set(this.attr.productSelect,"image",i.image),this.$set(this.attr.productSelect,"price",i.price),this.$set(this.attr.productSelect,"stock",i.stock),this.$set(this.attr.productSelect,"unique",i.unique),this.$set(this.attr.productSelect,"vip_price",i.vip_price),this.$set(this.attr.productSelect,"cart_num",1),this.$set(this,"attrValue",t)):i&&0==i.stock?(this.$set(this.attr.productSelect,"image",i.image),this.$set(this.attr.productSelect,"price",i.price),this.$set(this.attr.productSelect,"stock",0),this.$set(this.attr.productSelect,"unique",""),this.$set(this.attr.productSelect,"vip_price",i.vip_price),this.$set(this.attr.productSelect,"cart_num",0),this.$set(this,"attrValue","")):(this.$set(this.attr.productSelect,"image",this.storeInfo.image),this.$set(this.attr.productSelect,"price",this.storeInfo.price),this.$set(this.attr.productSelect,"stock",0),this.$set(this.attr.productSelect,"unique",""),this.$set(this.attr.productSelect,"vip_price",this.storeInfo.vip_price),this.$set(this.attr.productSelect,"cart_num",0),this.$set(this,"attrValue",""))},ChangeCartNum:function(t){var i=this.productValue[this.attrValue];if(void 0!==i||this.attr.productAttr.length||(i=this.attr.productSelect),void 0!==i){var e=i.stock||0,a=this.attr.productSelect;t?(a.cart_num++,a.cart_num>e&&(this.$set(this.attr.productSelect,"cart_num",e||1),this.$set(this,"cart_num",e||1))):(a.cart_num--,a.cart_num<1&&(this.$set(this.attr.productSelect,"cart_num",1),this.$set(this,"cart_num",1)))}},moreNav:function(){this.currentPage=!this.currentPage},getBrand:function(){var t=this;(0,c.brand)(this.where).then((function(i){t.brandList=i.data})).catch((function(i){return t.$util.Tips({title:i.msg})}))},set_brand:function(){this.brandtip=!0,this.currentPage=!1},closeBrand:function(){this.brandtip=!1},seletBrand:function(t){-1==this.brandArray.indexOf(t)?this.brandArray.push(t):this.brandArray.splice(this.brandArray.indexOf(t),1)},brandReset:function(){this.brandArray=[],this.brandOk()},brandOk:function(){this.brandtip=!1,this.loadend=!1,this.$set(this.where,"page",1),this.get_product_list(!0)},godDetail:function(t){this.currentPage=!1,this.promotions_type?uni.navigateTo({url:"/pages/goods_details/index?id=".concat(t.id)}):(0,f.goShopDetail)(t,this.uid).then((function(i){uni.navigateTo({url:"/pages/goods_details/index?id=".concat(t.id)})}))},Changswitch:function(){var t=this;this.currentPage=!1,t.is_switch=!t.is_switch},searchSubmit:function(t){var i=this;this.currentPage=!1,i.$set(i.where,"keyword",t.detail.value),i.loadend=!1,i.$set(i.where,"page",1),this.get_product_list(!0)},get_host_product:function(){var t=this;t.hotScroll||(0,c.getProductHot)(t.hotPage,t.hotLimit).then((function(i){t.hotPage++,t.hotScroll=i.data.length<t.hotLimit,t.hostProduct=t.hostProduct.concat(i.data)}))},set_where:function(t){switch(this.currentPage=!1,t){case 1:return history.back();case 2:0==this.price?this.price=1:1==this.price?this.price=2:2==this.price&&(this.price=0),this.stock=0;break;case 3:0==this.stock?this.stock=1:1==this.stock?this.stock=2:2==this.stock&&(this.stock=0),this.price=0;break;case 4:this.nows=!this.nows;break}this.loadend=!1,this.$set(this.where,"page",1),this.get_product_list(!0)},setWhere:function(){0==this.price?this.where.priceOrder="":1==this.price?this.where.priceOrder="asc":2==this.price&&(this.where.priceOrder="desc"),0==this.stock?this.where.salesOrder="":1==this.stock?this.where.salesOrder="asc":2==this.stock&&(this.where.salesOrder="desc"),this.where.news=this.nows?1:0},get_product_list:function(t){var i=this,e=this;e.setWhere(),e.loadend||e.loading||(!0===t&&(this.is_switch&&e.$refs.waterfallsFlow.refresh(),e.$set(e,"productList",[])),e.loading=!0,e.loadTitle="",e.$set(e.where,"brand_id",e.brandArray.join(",")),(0,c.getProductslist)(e.where).then((function(t){var a=t.data,s=e.$util.SplitArray(a,e.productList),o=a.length<e.where.limit;e.loadend=o,e.loading=!1,e.loadTitle=o?"没有更多内容啦~":"加载更多",e.$set(e,"productList",s),e.$set(e.where,"page",e.where.page+1),e.productList.length||i.get_host_product()})).catch((function(t){e.loading=!1,e.loadTitle="加载更多"})))}},onPullDownRefresh:function(){},onPageScroll:function(t){this.currentPage=!1},onReachBottom:function(){this.productList.length>0?this.get_product_list():this.get_host_product()}};i.default=v},2817:function(t,i,e){"use strict";e.r(i);var a=e("25db"),s=e.n(a);for(var o in a)"default"!==o&&function(t){e.d(i,t,(function(){return a[t]}))}(o);i["default"]=s.a},"2ff9":function(t,i){t.exports="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAATCAYAAABPwleqAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyVpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMTQ4IDc5LjE2NDAzNiwgMjAxOS8wOC8xMy0wMTowNjo1NyAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIDIxLjAgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6MTQ2ODNBNzkyRjEwMTFFQzk4NkE4NEFCRDVEMDlEMzQiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6MTQ2ODNBN0EyRjEwMTFFQzk4NkE4NEFCRDVEMDlEMzQiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDoxNDY4M0E3NzJGMTAxMUVDOTg2QTg0QUJENUQwOUQzNCIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDoxNDY4M0E3ODJGMTAxMUVDOTg2QTg0QUJENUQwOUQzNCIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/PiRpwdsAAAC+SURBVHjaYvz//z8DuYCJgQIwcJopAoyzZ8+2+PPnDwepGllYWH6wAENbhJGRcT0QsxCrEajnDxAHMqWlpW0B8uOAnH9EagSpiwPpAwdYRkbGciCVTaTF2VD1iNAGCswAUpUENFZC1WFGVXp6egfQWZ04nNsJkkcJbWwKZ8yYMR0YgBlIGmcAbcwkKpG8ePEiG6hhOVTjchAfmzpmbIIHDhz4b2JispGXl/cXUGNJQ0PDX6yJZATmKoo0AwQYADiLWEb/UttHAAAAAElFTkSuQmCC"},"3d8d":function(t,i,e){var a=e("24fb");i=a(!1),i.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* crmeb颜色变量 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.waterfalls-box[data-v-ec430f38]{position:relative;width:100%;overflow:hidden}.waterfalls-box .waterfalls-list[data-v-ec430f38]{width:calc((100% - var(--offset) * (var(--cols) - 1)) / var(--cols));position:absolute;background-color:#fff;border-radius:10px;left:calc(-50% - var(--offset))}.waterfalls-box .waterfalls-list .pictrue[data-v-ec430f38]{position:relative}.waterfalls-box .waterfalls-list .pictrue .masks[data-v-ec430f38]{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.2);border-radius:%?20?% %?20?% 0 0}.waterfalls-box .waterfalls-list .pictrue .masks .bg[data-v-ec430f38]{width:%?152?%;height:%?152?%;background:#000;opacity:.6;color:#fff;font-size:%?32?%;border-radius:50%;padding:%?34?% 0;text-align:center}.waterfalls-box .waterfalls-list .waterfalls-list-image[data-v-ec430f38]{width:100%;will-change:transform;border-radius:10px 10px 0 0;display:block}.waterfalls-box .waterfalls-list .waterfalls-list-image.single[data-v-ec430f38]{border-radius:10px}',""]),t.exports=i},"956c":function(t,i,e){"use strict";var a;e.d(i,"b",(function(){return s})),e.d(i,"c",(function(){return o})),e.d(i,"a",(function(){return a}));var s=function(){var t=this,i=t.$createElement,e=t._self._c||i;return e("v-uni-view",{staticClass:"waterfalls-box",style:{height:t.height+"px"}},t._l(t.list,(function(i,a){return e("v-uni-view",{key:i[t.idKey],ref:"waterfalls-list-id-"+i[t.idKey],refInFor:!0,staticClass:"waterfalls-list",style:Object.assign({},{"--offset":t.offset+"px","--cols":t.cols},t.listStyle,t.allPositionArr[a]||{}),attrs:{id:"waterfalls-list-id-"+i[t.idKey]},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.$emit("wapper-lick",i)}}},[e("v-uni-view",{staticClass:"pictrue"},[e("v-uni-image",{staticClass:"waterfalls-list-image",class:{single:t.single},style:t.imageStyle,attrs:{mode:"widthFix",src:i[t.imageSrcKey]||" "},on:{load:function(i){arguments[0]=i=t.$handleEvent(i),t.imageLoadHandle(a)},error:function(i){arguments[0]=i=t.$handleEvent(i),t.imageLoadHandle(a)},click:function(e){arguments[0]=e=t.$handleEvent(e),t.$emit("image-click",i)}}}),i.stock<=0?e("v-uni-view",{staticClass:"masks acea-row row-center-wrapper"},[e("v-uni-view",{staticClass:"bg"},[e("v-uni-view",[t._v("暂时")]),e("v-uni-view",[t._v("售罄")])],1)],1):t._e()],1),t._t("default",null,null,i)],2)})),1)},o=[]},"9cf3":function(t,i,e){"use strict";var a=e("4ea4");e("c975"),e("4e82"),e("a9e3"),e("ac1f"),Object.defineProperty(i,"__esModule",{value:!0}),i.default=void 0;var s=a(e("2909")),o={props:{list:{type:Array,required:!0},offset:{type:Number,default:10},idKey:{type:String,default:"id"},imageSrcKey:{type:String,default:"image"},cols:{type:Number,default:2,validator:function(t){return t>=2}},imageStyle:{type:Object},single:{type:Boolean,default:!1},listStyle:{type:Object}},data:function(){return{topArr:[],allPositionArr:[],allHeightArr:[],height:0,oldNum:0,num:0}},created:function(){this.refresh()},methods:{imageLoadHandle:function(t){var i=this;if(this.list.length){var e="waterfalls-list-id-"+this.list[t][this.idKey],a=uni.createSelectorQuery().in(this);a.select("#"+e).fields({size:!0},(function(e){if(i.num++,i.$set(i.allHeightArr,t,e.height),i.num===i.list.length){for(var a=i.oldNum;a<i.num;a++){var o=function(){var t=(0,s.default)(i.topArr).sort((function(t,i){return t-i}));return{shorterIndex:i.topArr.indexOf(t[0]),shorterValue:t[0],longerIndex:i.topArr.indexOf(t[i.cols-1]),longerValue:t[i.cols-1]}},r=o(),n=r.shorterIndex,c=r.shorterValue,d={top:c+"px",left:(e.width+i.offset)*n+"px"};i.$set(i.allPositionArr,a,d),i.topArr[n]=c+i.allHeightArr[a]+i.offset,i.height=o().longerValue-i.offset}i.oldNum=i.num,i.$emit("image-load")}})).exec()}},refresh:function(){for(var t=[],i=0;i<this.cols;i++)t.push(0);this.topArr=t,this.num=0,this.oldNum=0,this.height=0}}};i.default=o},adb0:function(t,i,e){var a=e("3d8d");"string"===typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var s=e("4f065").default;s("894db3f8",a,!0,{sourceMap:!1,shadowMode:!1})},b129:function(t,i,e){"use strict";e.r(i);var a=e("9cf3"),s=e.n(a);for(var o in a)"default"!==o&&function(t){e.d(i,t,(function(){return a[t]}))}(o);i["default"]=s.a},c84f:function(t,i,e){var a=e("fbe4");"string"===typeof a&&(a=[[t.i,a,""]]),a.locals&&(t.exports=a.locals);var s=e("4f065").default;s("26a785a9",a,!0,{sourceMap:!1,shadowMode:!1})},ca28:function(t,i,e){"use strict";e.r(i);var a=e("956c"),s=e("b129");for(var o in s)"default"!==o&&function(t){e.d(i,t,(function(){return s[t]}))}(o);e("02cf");var r,n=e("f0c5"),c=Object(n["a"])(s["default"],a["b"],a["c"],!1,null,"ec430f38",null,!1,a["a"],r);i["default"]=c.exports},e68a:function(t,i,e){"use strict";var a=e("c84f"),s=e.n(a);s.a},fbe4:function(t,i,e){var a=e("24fb");i=a(!1),i.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* crmeb颜色变量 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.home[data-v-0f3f9d0a]{color:#333;width:%?56?%;height:%?56?%;background:#fff;border:1px solid rgba(0,0,0,.1);border-radius:%?40?%;font-size:%?33?%}.home.on[data-v-0f3f9d0a]{background:unset;color:#333}.home.homeIndex[data-v-0f3f9d0a]{width:%?98?%}.home .iconfont[data-v-0f3f9d0a]{width:%?60?%;text-align:center;font-size:%?28?%;font-weight:700}.home .line[data-v-0f3f9d0a]{width:%?1?%;height:%?34?%;background:#b3b3b3}.home .icon-xiangzuo[data-v-0f3f9d0a]{font-size:%?28?%}.clored[data-v-0f3f9d0a]{color:var(--view-theme);font-weight:600}.clored .icon-gou[data-v-0f3f9d0a]{font-weight:400!important}.selectbrand[data-v-0f3f9d0a]{position:fixed;background-color:#fff;z-index:8;width:100%;top:%?170?%;left:0;max-height:%?860?%;overflow:hidden;overflow-y:auto;border-radius:0 0 %?24?% %?24?%;-webkit-transform:translate3d(0,-100%,0);transform:translate3d(0,-100%,0);transition:all .2s cubic-bezier(.9,.5,.5,.25)}.selectbrand .box .selet[data-v-0f3f9d0a]{display:flex;flex-wrap:wrap;padding:%?20?% %?20?% 0 %?20?%;max-height:%?520?%;overflow-x:hidden;overflow-y:auto}.selectbrand .box .selet .seletbox[data-v-0f3f9d0a]{width:%?216?%;height:%?68?%;border-radius:%?34?%;background-color:#f5f5f5;margin-bottom:%?30?%;text-align:center;font-size:%?24?%;margin-right:%?28?%}.selectbrand .box .selet .seletbox[data-v-0f3f9d0a]:nth-child(3n){margin-right:0}.selectbrand .box .selet .seleton[data-v-0f3f9d0a]{border:1px solid var(--view-theme);background:var(--view-minorColorT);color:var(--view-theme)}.selectbrand .box .btn[data-v-0f3f9d0a]{display:flex;justify-content:space-between;text-align:center;padding:%?30?% %?20?% %?40?%}.selectbrand .box .btn .sambox[data-v-0f3f9d0a]{width:%?328?%;height:%?64?%;border-radius:%?38?%;border:%?2?% solid var(--view-theme);color:#fff;line-height:%?64?%}.selectbrand .box .btn .reset[data-v-0f3f9d0a]{color:var(--view-theme)}.selectbrand .box .btn .ok[data-v-0f3f9d0a]{background:var(--view-theme)}.selectbrand .box .tlebox[data-v-0f3f9d0a]{width:100%;height:%?88?%;line-height:%?88?%;display:flex;justify-content:space-between;padding:0 %?40?% 0 %?36?%;margin-left:%?20?%;border-bottom:%?2?% solid #eee}.selectbrand .box .tlebox[data-v-0f3f9d0a]:last-child{border-bottom:0}.selectbrand.on[data-v-0f3f9d0a]{-webkit-transform:translateZ(0);transform:translateZ(0)}.productList .search[data-v-0f3f9d0a]{width:100%;height:%?86?%;padding-left:%?23?%;box-sizing:border-box;position:fixed;left:0;top:0;z-index:9}.productList .search .fanhui[data-v-0f3f9d0a]{color:#fff}.productList .search .input[data-v-0f3f9d0a]{width:%?560?%;height:%?60?%;background-color:#fff;border-radius:%?50?%;padding:0 %?20?%;box-sizing:border-box}.productList .search .input.on[data-v-0f3f9d0a]{width:%?652?%;margin-right:%?30?%}.productList .search .input.on uni-input[data-v-0f3f9d0a]{width:%?560?%}.productList .search .input uni-input[data-v-0f3f9d0a]{width:%?466?%;height:100%;font-size:%?26?%}.productList .search .input .placeholder[data-v-0f3f9d0a]{color:#999}.productList .search .input .iconfont[data-v-0f3f9d0a]{font-size:%?35?%;color:#555}.productList .search .icon-pailie[data-v-0f3f9d0a],\r\n.productList .search .icon-tupianpailie[data-v-0f3f9d0a]{color:#fff;width:%?62?%;font-size:%?40?%;height:%?86?%;line-height:%?86?%}.productList .nav[data-v-0f3f9d0a]{height:%?86?%;color:#454545;position:fixed;left:0;width:100%;font-size:%?28?%;background-color:#fff;margin-top:%?86?%;top:0;z-index:9}.productList .nav .item[data-v-0f3f9d0a]{width:25%;text-align:center}.productList .nav .item.font-color[data-v-0f3f9d0a]{font-weight:700}.productList .nav .item uni-image[data-v-0f3f9d0a]{width:%?15?%;height:%?19?%;margin-left:%?10?%}.productList .list[data-v-0f3f9d0a]{padding:0 %?20?%;margin-top:%?172?%}.productList .list.waterList[data-v-0f3f9d0a]{margin-top:%?192?%}.productList .list.on[data-v-0f3f9d0a]{background-color:#fff;border-top:1px solid #f6f6f6}.productList .list.on .title[data-v-0f3f9d0a]{font-size:%?22?%;color:#333;margin-top:%?30?%}.productList .list.on .title .label[data-v-0f3f9d0a]{border-radius:%?4?%;padding:%?2?% %?8?%;background-color:var(--view-minorColorT);font-size:%?20?%;color:var(--view-theme);margin-right:%?8?%}.productList .list.on .footer[data-v-0f3f9d0a]{width:100%;height:%?96?%;background-color:#fff;position:fixed;bottom:0;left:0;box-shadow:0 %?-4?% %?32?% 0 rgba(0,0,0,.08);padding:0 %?20?% 0 %?32?%;box-sizing:border-box;bottom:calc(0rpx+ constant(safe-area-inset-bottom));bottom:calc(%?0?% + env(safe-area-inset-bottom))}.productList .list.on .footer .right[data-v-0f3f9d0a]{color:#888;font-size:%?20?%;margin-left:%?32?%}.productList .list.on .footer .right .num[data-v-0f3f9d0a]{font-size:%?28?%;color:#333;font-weight:400}.productList .list.on .footer .right .num .money[data-v-0f3f9d0a]{font-weight:500}.productList .list.on .footer .icon[data-v-0f3f9d0a]{width:%?72?%;height:%?72?%;background:#f5f5f5;border-radius:50%;text-align:center;line-height:%?72?%;position:relative}.productList .list.on .footer .icon .iconfont[data-v-0f3f9d0a]{font-size:%?38?%}.productList .list.on .footer .icon .num[data-v-0f3f9d0a]{min-width:%?32?%;background-color:#fff;color:var(--view-theme);border-radius:15px;position:absolute;right:%?-14?%;top:0;font-size:10px;padding:0 %?8?%;height:%?34?%;line-height:%?31?%;border:1px solid var(--view-theme)}.productList .list.on .footer .bnt[data-v-0f3f9d0a]{width:%?192?%;height:%?64?%;background:var(--view-theme);border-radius:%?40?%;color:#fff;font-size:%?28?%;text-align:center;line-height:%?64?%}.productList .list .item[data-v-0f3f9d0a]{width:%?345?%;margin-top:%?20?%;background-color:#fff;border-radius:%?20?%}.productList .list .item .text.on .nameCon[data-v-0f3f9d0a]{height:%?84?%}.productList .list .item .text.on .name[data-v-0f3f9d0a]{margin-bottom:%?4?%}.productList .list .item .text.on .label[data-v-0f3f9d0a]{font-size:%?20?%;color:var(--view-theme);border-radius:%?4?%;border:1px solid var(--view-theme);padding:0 %?6?%;margin-right:%?10?%}.productList .list .item .text.on .sales[data-v-0f3f9d0a]{color:#999;font-size:%?22?%;margin-top:%?10?%}.productList .list .item .text.on .sales .score[data-v-0f3f9d0a]{margin-left:%?24?%}.productList .list .item .text.on .sales .icon[data-v-0f3f9d0a]{width:%?48?%;height:%?48?%;border-radius:50%;border:1px solid var(--view-theme);font-size:%?20?%;color:var(--view-theme)}.productList .list .item.on[data-v-0f3f9d0a]{width:100%;display:flex;border-bottom:%?1?% solid #f6f6f6;padding:%?30?% 0;margin:0}.productList .list .item .pictrue[data-v-0f3f9d0a]{position:relative;width:100%;height:%?345?%}.productList .list .item .pictrue.on[data-v-0f3f9d0a]{width:%?180?%;height:%?180?%;position:relative}.productList .list .item .pictrue.on .masks[data-v-0f3f9d0a]{position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.2);border-radius:%?10?%}.productList .list .item .pictrue.on .masks .bg[data-v-0f3f9d0a]{width:%?110?%;height:%?110?%;background:#000;opacity:.6;color:#fff;font-size:%?22?%;border-radius:50%;padding:%?22?% 0;text-align:center}.productList .list .item .pictrue uni-image[data-v-0f3f9d0a]{width:100%;height:100%;border-radius:%?20?% %?20?% 0 0}.productList .list .item .pictrue uni-image.on[data-v-0f3f9d0a]{border-radius:%?6?%}.productList .list .item .text[data-v-0f3f9d0a]{padding:%?20?% %?17?% %?26?% %?17?%;font-size:%?30?%;color:#222}.productList .list .item .text.on[data-v-0f3f9d0a]{width:%?508?%;padding:0 0 0 %?22?%}.productList .list .item .text .money[data-v-0f3f9d0a]{font-size:%?26?%;font-weight:700;margin-top:%?8?%}.productList .list .item .text .money.on[data-v-0f3f9d0a]{margin-top:0;margin-right:%?5?%}.productList .list .item .text .money .num[data-v-0f3f9d0a]{font-size:%?34?%}.productList .list .item .text .vip[data-v-0f3f9d0a]{font-size:%?22?%;color:#aaa;margin-top:%?7?%}.productList .list .item .text .vip.on[data-v-0f3f9d0a]{margin-top:%?12?%}.productList .list .item .text .vip .vip-money[data-v-0f3f9d0a]{font-size:%?24?%;color:#282828;font-weight:600}.productList .list .vip .vip-money .icon[data-v-0f3f9d0a]{font-size:%?15?%;background:#ff9500;color:#fff;border-radius:%?18?%;padding:%?1?% %?6?%;margin-left:%?10?%;min-width:%?60?%}.productList .list .vip .vip-money .icon .iconfont[data-v-0f3f9d0a]{font-size:%?15?%;margin-right:%?5?%}.productList .list .vip .vip-money .icon.on[data-v-0f3f9d0a]{background:#333;color:#fddaa4;min-width:unset}.productList .list .item .text .vip .vip-money uni-image[data-v-0f3f9d0a]{width:%?46?%;height:%?21?%;margin-left:%?4?%}.noCommodity[data-v-0f3f9d0a]{background-color:#fff;padding-bottom:%?30?%}.waterfalls[data-v-0f3f9d0a]{padding:%?10?% %?16?% %?16?% %?16?%;color:#222}.waterfalls .name[data-v-0f3f9d0a]{font-size:%?28?%}.waterfalls .label[data-v-0f3f9d0a]{font-size:%?20?%;color:var(--view-theme);border-radius:%?4?%;border:1px solid var(--view-theme);padding:0 %?6?%;display:inline-block;margin-top:%?10?%;margin-right:%?10?%}.waterfalls .money[data-v-0f3f9d0a]{font-size:%?26?%;font-weight:700}.waterfalls .money .num[data-v-0f3f9d0a]{font-size:%?34?%}.waterfalls .money .nums[data-v-0f3f9d0a]{font-size:%?28?%}.waterfalls .vip[data-v-0f3f9d0a]{font-size:%?22?%;color:#aaa;margin-top:%?6?%}.waterfalls .vip .vip-money[data-v-0f3f9d0a]{font-size:%?24?%;color:#282828;font-weight:700}.waterfalls .vip .vip-money uni-image[data-v-0f3f9d0a]{width:%?46?%;height:%?21?%;margin-left:%?4?%}',""]),t.exports=i}}]);