(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-users-user_goods_collection-index"],{"16a8":function(t,e,o){"use strict";var i=o("4ea4");Object.defineProperty(e,"__esModule",{value:!0}),e.getProductDetail=a,e.getProductCode=r,e.collectAdd=c,e.collectDel=s,e.postCartAdd=u,e.getCategoryList=d,e.getProductslist=l,e.getProductHot=f,e.collectAll=v,e.getGroomList=p,e.getCollectUserList=g,e.getVisitList=m,e.deleteVisitList=h,e.getReplyList=_,e.getReplyConfig=y,e.getReplyPraise=b,e.getUnReplyPraise=w,e.getReplyInfo=C,e.getReplyComment=x,e.postReplyPraise=P,e.postUnReplyPraise=L,e.replyComment=A,e.getSearchKeyword=S,e.storeListApi=k,e.storeDiscountsList=T,e.postCartNum=$,e.getAttr=M,e.storeCardApi=O,e.brand=z;var n=i(o("c431"));function a(t,e){return n.default.get("product/detail/"+t,e,{noAuth:!0})}function r(t){return n.default.get("product/code/"+t,{})}function c(t,e){return n.default.post("collect/add",{id:t,category:void 0===e?"product":e})}function s(t,e){return n.default.post("collect/del",{id:t,category:void 0===e?"product":e})}function u(t){return n.default.post("cart/add",t)}function d(){return n.default.get("category",{},{noAuth:!0})}function l(t){return n.default.get("products",t,{noAuth:!0})}function f(t,e){return n.default.get("product/hot",{page:void 0===t?1:t,limit:void 0===e?4:e},{noAuth:!0})}function v(t,e){return n.default.post("collect/all",{id:t,category:void 0===e?"product":e})}function p(t,e){return n.default.get("groom/list/"+t,e,{noAuth:!0})}function g(t){return n.default.get("collect/user",t)}function m(t){return n.default.get("user/visit_list",t)}function h(t){return n.default.delete("user/visit",t)}function _(t,e){return n.default.get("v2/reply/list/"+t,e,{noAuth:!0})}function y(t){return n.default.get("reply/config/"+t,{},{noAuth:!0})}function b(t){return n.default.post("reply/reply_praise/"+t)}function w(t){return n.default.post("reply/un_reply_praise/"+t)}function C(t){return n.default.get("reply/info/"+t)}function x(t,e){return n.default.get("reply/comment/"+t,e)}function P(t){return n.default.post("reply/praise/"+t)}function L(t){return n.default.post("reply/un_praise/"+t)}function A(t,e){return n.default.post("reply/comment/"+t,e)}function S(){return n.default.get("search/keyword",{},{noAuth:!0})}function k(t){return n.default.get("store_list",t,{noAuth:!0})}function T(t){return n.default.get("store_discounts/list/"+t,{},{noAuth:!0})}function $(t){return n.default.post("v2/set_cart_num",t)}function M(t,e){return n.default.get("v2/get_attr/"+t+"/"+e)}function O(){return n.default.get("store/staff/card/code")}function z(t){return n.default.get("brand",t,{noAuth:!0})}},"1f0d":function(t,e,o){"use strict";o.r(e);var i=o("84e5"),n=o("2a3c");for(var a in n)"default"!==a&&function(t){o.d(e,t,(function(){return n[t]}))}(a);o("892d");var r,c=o("f0c5"),s=Object(c["a"])(n["default"],i["b"],i["c"],!1,null,"3b1732d2",null,!1,i["a"],r);e["default"]=s.exports},2261:function(t,e,o){"use strict";var i=o("4ea4");o("99af"),o("a434"),Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var n=o("16a8"),a=o("26cb"),r=o("fa9e"),c=i(o("34f7")),s=i(o("f176")),u=i(o("d0ca")),d={components:{recommend:c.default,home:s.default},mixins:[u.default],data:function(){return{hostProduct:[],loadTitle:"加载更多",loading:!1,loadend:!1,collectProductList:[],limit:15,page:1,isAuto:!1,isShowAuth:!1,hotScroll:!1,hotPage:1,hotLimit:10}},computed:(0,a.mapGetters)(["isLogin"]),onLoad:function(){this.isLogin?(this.loadend=!1,this.page=1,this.collectProductList=[],this.get_user_collect_product(),this.get_host_product()):(0,r.toLogin)()},onShow:function(){this.loadend=!1,this.page=1,this.$set(this,"collectProductList",[]),this.get_user_collect_product()},methods:{goDetails:function(t){0!=t.is_show&&uni.navigateTo({url:"/pages/goods_details/index?id="+t.pid})},onLoadFun:function(){this.loadend=!1,this.page=1,this.$set(this,"collectProductList",[]),this.get_user_collect_product(),this.get_host_product()},authColse:function(t){this.isShowAuth=t},get_user_collect_product:function(){var t=this;this.loading||this.loadend||(t.loading=!0,t.loadTitle="",(0,n.getCollectUserList)({page:t.page,limit:t.limit}).then((function(e){var o=e.data,i=o.length<t.limit;t.collectProductList=t.$util.SplitArray(o,t.collectProductList),t.$set(t,"collectProductList",t.collectProductList),t.loadend=i,t.loadTitle=i?"没有更多内容啦~":"加载更多",t.page=t.page+1,t.loading=!1})).catch((function(e){t.loading=!1,t.loadTitle="加载更多"})))},delCollection:function(t,e){var o=this;(0,n.collectDel)(t).then((function(t){return o.$util.Tips({title:"取消收藏成功",icon:"success"},(function(){o.collectProductList.splice(e,1),o.$set(o,"collectProductList",o.collectProductList)}))}))},get_host_product:function(){var t=this;t.hotScroll||(0,n.getProductHot)(t.hotPage,t.hotLimit).then((function(e){t.hotPage++,t.hotScroll=e.data.length<t.hotLimit,t.hostProduct=t.hostProduct.concat(e.data)}))}},onReachBottom:function(){this.collectProductList.length?this.get_user_collect_product():this.get_host_product()}};e.default=d},"2a3c":function(t,e,o){"use strict";o.r(e);var i=o("2261"),n=o.n(i);for(var a in i)"default"!==a&&function(t){o.d(e,t,(function(){return i[t]}))}(a);e["default"]=n.a},"2b49":function(t,e,o){"use strict";var i=o("4ea4");Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var n=o("26cb"),a=i(o("d0ca")),r=o("12b8"),c={name:"Home",props:{},mixins:[a.default],data:function(){return{top:"545",imgHost:r.HTTP_REQUEST_URL}},computed:(0,n.mapGetters)(["homeActive"]),methods:{setTouchMove:function(t){var e=this;t.touches[0].clientY<545&&t.touches[0].clientY>66&&(e.top=t.touches[0].clientY)},open:function(){this.homeActive?this.$store.commit("CLOSE_HOME"):this.$store.commit("OPEN_HOME")}},created:function(){},beforeDestroy:function(){this.$store.commit("CLOSE_HOME")}};e.default=c},"2dd2":function(t,e,o){"use strict";o.r(e);var i=o("7afd"),n=o.n(i);for(var a in i)"default"!==a&&function(t){o.d(e,t,(function(){return i[t]}))}(a);e["default"]=n.a},"34f7":function(t,e,o){"use strict";o.r(e);var i=o("5392"),n=o("2dd2");for(var a in n)"default"!==a&&function(t){o.d(e,t,(function(){return n[t]}))}(a);o("a1b6");var r,c=o("f0c5"),s=Object(c["a"])(n["default"],i["b"],i["c"],!1,null,"9ed18956",null,!1,i["a"],r);e["default"]=s.exports},4899:function(t,e,o){var i=o("24fb");e=i(!1),e.push([t.i,".pictrueBox[data-v-59354ea8]{width:%?130?%;height:%?120?%}\n\n/*返回主页按钮*/.home[data-v-59354ea8]{position:fixed;color:#fff;text-align:center;z-index:9999;right:%?15?%;display:flex}.home .homeCon[data-v-59354ea8]{border-radius:%?50?%;opacity:0;height:0;color:var(--view-theme);width:0}.home .homeCon.on[data-v-59354ea8]{opacity:1;-webkit-animation:bounceInRight .5s cubic-bezier(.215,.61,.355,1);animation:bounceInRight .5s cubic-bezier(.215,.61,.355,1);width:%?300?%;height:%?86?%;margin-bottom:%?20?%;display:flex;justify-content:center;align-items:center;background:var(--view-theme)!important}.home .homeCon .iconfont[data-v-59354ea8]{font-size:%?48?%;color:#fff;display:inline-block;margin:0 auto}.home .pictrue[data-v-59354ea8]{width:%?86?%;height:%?86?%;border-radius:50%;margin:0 auto;background-color:var(--view-theme)}.home .pictrue .image[data-v-59354ea8]{width:100%;height:100%;border-radius:50%;-webkit-transform:rotate(90deg);transform:rotate(90deg);ms-transform:rotate(90deg);moz-transform:rotate(90deg);webkit-transform:rotate(90deg);o-transform:rotate(90deg)}",""]),t.exports=e},4913:function(t,e,o){"use strict";o("99af"),o("d3b7"),Object.defineProperty(e,"__esModule",{value:!0}),e.goShopDetail=i,e.goPage=n;getApp();function i(t,e){return new Promise((function(o){t.activity&&"1"===t.activity.type?uni.navigateTo({url:"/pages/activity/goods_seckill_details/index?id=".concat(t.activity.id,"&time=").concat(t.activity.time,"&status=1")}):t.activity&&"2"===t.activity.type?uni.navigateTo({url:"/pages/activity/goods_bargain_details/index?id=".concat(t.activity.id,"&bargain=").concat(e)}):t.activity&&"3"===t.activity.type?uni.navigateTo({url:"/pages/activity/goods_combination_details/index?id=".concat(t.activity.id)}):o(t)}))}function n(){return new Promise((function(t){t(!0)}))}},5392:function(t,e,o){"use strict";var i;o.d(e,"b",(function(){return n})),o.d(e,"c",(function(){return a})),o.d(e,"a",(function(){return i}));var n=function(){var t=this,e=t.$createElement,o=t._self._c||e;return o("v-uni-view",{staticClass:"recommend",style:t.colorStyle},[o("v-uni-view",{staticClass:"title acea-row row-center-wrapper"},[o("v-uni-text",{staticClass:"iconfont icon-zhuangshixian"}),o("v-uni-text",{staticClass:"name"},[t._v("热门推荐")]),o("v-uni-text",{staticClass:"iconfont icon-zhuangshixian lefticon"})],1),o("v-uni-view",{staticClass:"recommendList acea-row row-between-wrapper"},t._l(t.hostProduct,(function(e,i){return o("v-uni-view",{key:i,staticClass:"item",attrs:{"hover-class":"none"},on:{click:function(o){arguments[0]=o=t.$handleEvent(o),t.goDetail(e)}}},[o("v-uni-view",{staticClass:"pictrue"},[o("v-uni-image",{attrs:{src:e.image}}),e.activity&&"1"===e.activity.type?o("span",{staticClass:"pictrue_log_big pictrue_log_class"},[t._v("秒杀")]):t._e(),e.activity&&"2"===e.activity.type?o("span",{staticClass:"pictrue_log_big pictrue_log_class"},[t._v("砍价")]):t._e(),e.activity&&"3"===e.activity.type?o("span",{staticClass:"pictrue_log_big pictrue_log_class"},[t._v("拼团")]):t._e()],1),o("v-uni-view",{staticClass:"name line1"},[t._v(t._s(e.store_name))]),o("v-uni-view",{staticClass:"money font-color"},[t._v("￥"),o("v-uni-text",{staticClass:"num"},[t._v(t._s(e.price))])],1)],1)})),1)],1)},a=[]},"65a8":function(t,e,o){var i=o("4899");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=o("4f065").default;n("0114b9c6",i,!0,{sourceMap:!1,shadowMode:!1})},"77b7":function(t,e,o){"use strict";o.r(e);var i=o("2b49"),n=o.n(i);for(var a in i)"default"!==a&&function(t){o.d(e,t,(function(){return i[t]}))}(a);e["default"]=n.a},"7afd":function(t,e,o){"use strict";var i=o("4ea4");Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var n=o("26cb"),a=o("4913"),r=i(o("d0ca")),c={computed:(0,n.mapGetters)(["uid"]),props:{hostProduct:{type:Array,default:function(){return[]}}},mixins:[r.default],data:function(){return{}},methods:{goDetail:function(t){(0,a.goShopDetail)(t,this.uid).then((function(e){uni.navigateTo({url:"/pages/goods_details/index?id=".concat(t.id)})}))}}};e.default=c},"84e5":function(t,e,o){"use strict";var i;o.d(e,"b",(function(){return n})),o.d(e,"c",(function(){return a})),o.d(e,"a",(function(){return i}));var n=function(){var t=this,e=t.$createElement,i=t._self._c||e;return i("v-uni-view",[t.collectProductList.length?i("v-uni-view",{staticClass:"collectionGoods"},[t._l(t.collectProductList,(function(e,o){return i("v-uni-view",{key:o,staticClass:"item acea-row row-between-wrapper",on:{click:function(o){arguments[0]=o=t.$handleEvent(o),t.goDetails(e)}}},[i("v-uni-view",{staticClass:"pictrue"},[i("v-uni-image",{attrs:{src:e.image}})],1),i("v-uni-view",{staticClass:"text acea-row row-column-between"},[i("v-uni-view",{staticClass:"name line1",class:0==e.is_show?"on":""},[t._v(t._s(e.store_name))]),i("v-uni-view",{staticClass:"acea-row row-between-wrapper"},[1==e.is_show?i("v-uni-view",{staticClass:"money font-color"},[t._v("￥"+t._s(e.price))]):i("v-uni-view",{staticClass:"money on"},[t._v("该商品已下架")]),i("v-uni-view",{staticClass:"delete",on:{click:function(i){i.stopPropagation(),arguments[0]=i=t.$handleEvent(i),t.delCollection(e.pid,o)}}},[t._v("删除")])],1)],1)],1)})),i("v-uni-view",{staticClass:"loadingicon acea-row row-center-wrapper"},[i("v-uni-text",{staticClass:"loading iconfont icon-jiazai",attrs:{hidden:0==t.loading}}),t._v(t._s(t.loadTitle))],1)],2):!t.collectProductList.length&&t.page>1?i("v-uni-view",{staticClass:"noCommodity"},[i("v-uni-view",{staticClass:"pictrue"},[i("v-uni-image",{attrs:{src:o("94c4")}})],1),i("recommend",{attrs:{hostProduct:t.hostProduct}})],1):t._e(),t.navigation?i("home"):t._e()],1)},a=[]},"86a9":function(t,e,o){var i=o("c793");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=o("4f065").default;n("f2c4ffaa",i,!0,{sourceMap:!1,shadowMode:!1})},"892d":function(t,e,o){"use strict";var i=o("86a9"),n=o.n(i);n.a},"94c4":function(t,e,o){t.exports=o.p+"static/img/noCollection.ad7c1108.png"},"9aab":function(t,e,o){var i=o("c2b8");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=o("4f065").default;n("688d9360",i,!0,{sourceMap:!1,shadowMode:!1})},a1b6:function(t,e,o){"use strict";var i=o("9aab"),n=o.n(i);n.a},c2b8:function(t,e,o){var i=o("24fb");e=i(!1),e.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* crmeb颜色变量 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.recommend[data-v-9ed18956]{background-color:#fff}.recommend .title[data-v-9ed18956]{height:%?135?%;font-size:%?28?%;color:#282828}.recommend .title .name[data-v-9ed18956]{margin:0 %?28?%}.recommend .title .iconfont[data-v-9ed18956]{font-size:%?170?%;color:#454545}.recommend .title .iconfont.lefticon[data-v-9ed18956]{-webkit-transform:rotate(180deg);transform:rotate(180deg)}.recommend .recommendList[data-v-9ed18956]{padding:0 %?30?%}.recommend .recommendList .item[data-v-9ed18956]{width:%?335?%;margin-bottom:%?30?%}.recommend .recommendList .item .pictrue[data-v-9ed18956]{position:relative;width:100%;height:%?335?%}.recommend .recommendList .item .pictrue uni-image[data-v-9ed18956]{width:100%;height:100%;border-radius:%?20?%}.recommend .recommendList .item .name[data-v-9ed18956]{font-size:%?28?%;color:#282828;margin-top:%?20?%}.recommend .recommendList .item .money[data-v-9ed18956]{font-size:%?20?%;margin-top:%?8?%}.recommend .recommendList .item .money .num[data-v-9ed18956]{font-size:%?28?%}',""]),t.exports=e},c793:function(t,e,o){var i=o("24fb");e=i(!1),e.push([t.i,'@charset "UTF-8";\r\n/**\r\n * 这里是uni-app内置的常用样式变量\r\n *\r\n * uni-app 官方扩展插件及插件市场（https://ext.dcloud.net.cn）上很多三方插件均使用了这些样式变量\r\n * 如果你是插件开发者，建议你使用scss预处理，并在插件代码中直接使用这些变量（无需 import 这个文件），方便用户通过搭积木的方式开发整体风格一致的App\r\n *\r\n */\r\n/**\r\n * 如果你是App开发者（插件使用者），你可以通过修改这些变量来定制自己的插件主题，实现自定义主题功能\r\n *\r\n * 如果你的项目同样使用了scss预处理，你也可以直接在你的 scss 代码中使用如下变量，同时无需 import 这个文件\r\n */\r\n/* 颜色变量 */\r\n/* 行为相关颜色 */\r\n/* crmeb颜色变量 */\r\n/* 背景颜色 */\r\n/* 边框颜色 */\r\n/* 尺寸变量 */\r\n/* 文字尺寸 */\r\n/* 图片尺寸 */\r\n/* Border Radius */\r\n/* 水平间距 */\r\n/* 垂直间距 */\r\n/* 透明度 */\r\n/* 文章场景相关 */.collectionGoods[data-v-3b1732d2]{background-color:#fff;border-top:%?1?% solid #eee}.collectionGoods .item[data-v-3b1732d2]{margin-left:%?30?%;padding-right:%?30?%;border-bottom:%?1?% solid #eee;height:%?180?%}.collectionGoods .item .pictrue[data-v-3b1732d2]{width:%?130?%;height:%?130?%}.collectionGoods .item .pictrue uni-image[data-v-3b1732d2]{width:100%;height:100%;border-radius:%?6?%}.collectionGoods .item .text[data-v-3b1732d2]{width:%?535?%;height:%?130?%;font-size:%?28?%;color:#282828}.collectionGoods .item .text .name[data-v-3b1732d2]{width:100%}.collectionGoods .item .text .name.on[data-v-3b1732d2]{color:#ccc}.collectionGoods .item .text .money[data-v-3b1732d2]{font-size:%?26?%}.collectionGoods .item .text .money.on[data-v-3b1732d2]{color:#ccc}.collectionGoods .item .text .delete[data-v-3b1732d2]{font-size:%?26?%;color:#282828;width:%?144?%;height:%?46?%;border:1px solid #bbb;border-radius:%?4?%;text-align:center;line-height:%?46?%}.noCommodity[data-v-3b1732d2]{background-color:#fff;padding-top:%?1?%;border-top:0}',""]),t.exports=e},d0ca:function(t,e,o){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var i={data:function(){return{colorStyle:"",navigation:0,colorNum:0}},created:function(){var t=this;this.colorStyle=uni.getStorageSync("viewColor"),uni.$on("ok",(function(e){t.colorStyle=e})),this.navigation=uni.getStorageSync("navigation"),uni.$on("navOk",(function(e){t.navigation=e}))},methods:{colorData:function(){var t=this;this.colorNum=uni.getStorageSync("statusColor"),uni.$on("colorOk",(function(e){t.colorNum=e}))}}};e.default=i},da19:function(t,e,o){"use strict";var i;o.d(e,"b",(function(){return n})),o.d(e,"c",(function(){return a})),o.d(e,"a",(function(){return i}));var n=function(){var t=this,e=t.$createElement,o=t._self._c||e;return o("v-uni-view",{style:t.colorStyle},[o("v-uni-view",{staticStyle:{"touch-action":"none"}},[o("v-uni-view",{staticClass:"home",staticStyle:{position:"fixed"},style:{top:t.top+"px"},attrs:{id:"right-nav"},on:{touchmove:function(e){e.stopPropagation(),e.preventDefault(),arguments[0]=e=t.$handleEvent(e),t.setTouchMove.apply(void 0,arguments)}}},[t.homeActive?o("v-uni-view",{staticClass:"homeCon bg-color",class:!0===t.homeActive?"on":""},[o("v-uni-navigator",{staticClass:"iconfont icon-shouye-xianxing",attrs:{"hover-class":"none","open-type":"switchTab",url:"/pages/index/index"}}),o("v-uni-navigator",{staticClass:"iconfont icon-caigou-xianxing",attrs:{"hover-class":"none","open-type":"switchTab",url:"/pages/order_addcart/order_addcart"}}),o("v-uni-navigator",{staticClass:"iconfont icon-yonghu1",attrs:{"hover-class":"none","open-type":"switchTab",url:"/pages/user/index"}})],1):t._e(),o("v-uni-view",{staticClass:"pictrueBox",on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.open.apply(void 0,arguments)}}},[o("v-uni-view",{staticClass:"pictrue"},[o("v-uni-image",{staticClass:"image",attrs:{src:!0===t.homeActive?t.imgHost+"/statics/images/close.gif":t.imgHost+"/statics/images/open.gif"}})],1)],1)],1)],1)],1)},a=[]},eb30:function(t,e,o){"use strict";var i=o("65a8"),n=o.n(i);n.a},f176:function(t,e,o){"use strict";o.r(e);var i=o("da19"),n=o("77b7");for(var a in n)"default"!==a&&function(t){o.d(e,t,(function(){return n[t]}))}(a);o("eb30");var r,c=o("f0c5"),s=Object(c["a"])(n["default"],i["b"],i["c"],!1,null,"59354ea8",null,!1,i["a"],r);e["default"]=s.exports}}]);