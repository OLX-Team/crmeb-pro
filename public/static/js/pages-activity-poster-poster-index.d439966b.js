(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-activity-poster-poster-index"],{"0285":function(t,e,n){var i=n("24fb");e=i(!1),e.push([t.i,".posterCon[data-v-f147cb98]{position:fixed;top:0;width:100%;left:0;height:100%;background-color:var(--view-theme);bottom:0;overflow-y:auto}.poster-poster .tip[data-v-f147cb98]{height:%?80?%;font-size:%?26?%;color:#e8c787;text-align:center;line-height:%?80?%;-webkit-user-select:none;user-select:none}.poster-poster .tip .iconfont[data-v-f147cb98]{font-size:%?36?%;vertical-align:%?-4?%;margin-right:%?18?%}.canvas[data-v-f147cb98]{width:100%;height:%?1100?%}.poster-poster .pictrue[data-v-f147cb98]{width:%?700?%;\n\t/* height: 100%; */margin:0 auto %?50?% auto;display:flex;justify-content:center}.poster-poster .pictrue uni-image[data-v-f147cb98]{width:100%\n\t/* height: 100%; */}",""]),t.exports=e},"2cc1":function(t,e,n){"use strict";var i=n("4ea4");n("c975"),n("d3b7"),n("ac1f"),n("25f0"),n("5319"),Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0,n("96cf");var a=i(n("1da1")),r=i(n("7322")),o=n("866c"),u=n("d5e6"),s=i(n("d0ca")),c={components:{zbCode:r.default},mixins:[s.default],data:function(){return{canvasStatus:!0,posterImage:"",parameter:{navbar:"1",return:"1",title:"拼团海报",color:!0,class:"0"},type:0,id:0,bargain:0,image:"",from:"",uid:"",codeShow:!1,cid:"1",ifShow:!0,val:"",size:200,unit:"upx",background:"#FFF",foreground:"#000",pdground:"#000",icon:"",iconsize:40,lv:3,onval:!0,loadMake:!0,src:"",codeSrc:"",wd:0,hg:0,posterBag:"../static/posterBag.png",mpUrl:""}},onLoad:function(t){this.from="wechat";if(!t.hasOwnProperty("type")||!t.hasOwnProperty("id"))return app.Tips({title:"参数错误",icon:"none"},{tab:3,url:1});this.type=t.type,this.id=t.id,1==t.type?(this.bargain=t.bargain,uni.setNavigationBarTitle({title:"砍价海报"})):uni.setNavigationBarTitle({title:"拼团海报"})},onReady:function(){var t=this;1==this.type?this.val=window.location.origin+"/pages/activity/goods_bargain_details/index?id="+this.id+"&bargain="+this.$store.state.app.uid:2==this.type&&(this.val=window.location.origin+"/pages/activity/goods_combination_status/index?id="+this.id+"&pid="+this.$store.state.app.uid),setTimeout((function(e){t.getPosterInfo()}),200),this.$nextTick((function(){var t=this,e=uni.createSelectorQuery().select(".pictrue");e.fields({size:!0},(function(e){t.wd=e.width,t.hg=e.height})).exec()}))},methods:{getPosterInfo:function(){var t=this;return(0,a.default)(regeneratorRuntime.mark((function e(){var n,i,a,r,s,c;return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return n=t,"",{id:n.id,from:n.from},e.next=4,(0,u.getUserInfo)();case 4:if(i=e.sent,t.uid=i.data.uid,uni.showLoading({title:"海报生成中",mask:!0}),1!=n.type){e.next=12;break}return e.next=10,(0,o.getBargainPosterData)(n.id).then((function(t){s=t.data})).catch((function(t){n.$util.Tips({title:"海报图片获取失败"})}));case 10:e.next=14;break;case 12:return e.next=14,(0,o.getCombinationPosterData)(n.id).then((function(t){s=t.data})).catch((function(t){n.$util.Tips({title:"海报图片获取失败"})}));case 14:return e.next=16,t.imgToBase(s.image);case 16:return a=e.sent,e.next=19,t.imgToBase(s.url);case 19:r=e.sent,c=[t.posterBag,a,r||t.codeSrc],t.$nextTick((function(e){n.$util.bargainPosterCanvas(c,s.title,s.label,s.msg,s.price,t.wd,t.hg,(function(e){t.posterImage=e}))}));case 22:case"end":return e.stop()}}),e)})))()},downloadFilestoreImage:function(t){var e=this;return t=this.setDomain(t),new Promise((function(n,i){var a=e;uni.downloadFile({url:t,success:function(t){n(t.tempFilePath)},fail:function(){return a.$util.Tips({title:""})}})}))},setDomain:function(t){return t=t?t.toString():"",t.indexOf("https://")>-1?t:t.replace("http://","https://")},imgToBase:function(t){return(0,a.default)(regeneratorRuntime.mark((function e(){var n;return regeneratorRuntime.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return e.next=2,(0,u.imgToBase)({image:t});case 2:return n=e.sent,e.abrupt("return",n.data.image);case 4:case"end":return e.stop()}}),e)})))()},downloadImg:function(){},savePosterPathMp:function(t){var e=document.createElement("a");e.download="海报",e.href=t,document.body.appendChild(e),e.click()},qrR:function(t){this.codeSrc=t}}};e.default=c},3225:function(t,e,n){"use strict";var i=n("34b0"),a=n.n(i);a.a},"34b0":function(t,e,n){var i=n("0285");"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var a=n("4f065").default;a("c8ccb0bc",i,!0,{sourceMap:!1,shadowMode:!1})},"811e":function(t,e,n){"use strict";n.r(e);var i=n("2cc1"),a=n.n(i);for(var r in i)"default"!==r&&function(t){n.d(e,t,(function(){return i[t]}))}(r);e["default"]=a.a},"866c":function(t,e,n){"use strict";var i=n("4ea4");Object.defineProperty(e,"__esModule",{value:!0}),e.getCombinationList=r,e.getCombinationDetail=o,e.getCombinationPink=u,e.postCombinationRemove=s,e.getBargainList=c,e.getCombinationBannerList=l,e.getPink=d,e.getBargainUserList=f,e.getBargainDetail=g,e.postBargainStartUser=p,e.postBargainStart=v,e.postBargainHelp=h,e.postBargainHelpPrice=m,e.postBargainHelpList=b,e.postBargainHelpCount=w,e.postBargainShare=k,e.getSeckillIndexTime=y,e.getSeckillList=C,e.getSeckillDetail=x,e.getBargainPoster=S,e.getCombinationPoster=_,e.getBargainUserCancel=B,e.seckillCode=P,e.scombinationCode=I,e.getCombinationPosterData=T,e.getBargainPosterData=D,e.integralOrderConfirm=L,e.integralOrderCreate=$,e.integralOrderDetails=O,e.getIntegralProductDetail=z,e.getStoreIntegralList=M,e.getIntegralOrderList=A,e.getLogisticsDetails=R,e.orderTake=F,e.orderDel=j,e.getAdvancellList=N,e.promotionsList=U,e.giveInfo=E;var a=i(n("c431"));function r(t){return a.default.get("combination/list",t,{noAuth:!0})}function o(t){return a.default.get("combination/detail/"+t)}function u(t){return a.default.get("combination/pink/"+t)}function s(t){return a.default.post("combination/remove",t)}function c(t){return a.default.get("bargain/list",t,{noAuth:!0})}function l(t){return a.default.get("combination/banner_list",t,{noAuth:!0})}function d(t){return a.default.get("pink",t,{noAuth:!0})}function f(t){return a.default.get("bargain/user/list",t)}function g(t){return a.default.get("bargain/detail/"+t)}function p(t){return a.default.post("bargain/start/user",t)}function v(t){return a.default.post("bargain/start",{bargainId:t})}function h(t){return a.default.post("bargain/help",t)}function m(t){return a.default.post("bargain/help/price",t)}function b(t){return a.default.post("bargain/help/list",t)}function w(t){return a.default.post("bargain/help/count",t)}function k(t){return a.default.post("bargain/share",{bargainId:t})}function y(){return a.default.get("seckill/index",{},{noAuth:!0})}function C(t,e){return a.default.get("seckill/list/"+t,e,{noAuth:!0})}function x(t,e){return a.default.get("seckill/detail/"+t)}function S(t){return a.default.post("bargain/poster",t)}function _(t){return a.default.post("combination/poster",t)}function B(t){return a.default.post("bargain/user/cancel",t)}function P(t,e){return a.default.get("seckill/code/"+t,e)}function I(t){return a.default.get("combination/code/"+t)}function T(t){return a.default.get("combination/poster_info/"+t)}function D(t){return a.default.get("bargain/poster_info/"+t)}function L(t){return a.default.post("store_integral/order/confirm",t)}function $(t){return a.default.post("store_integral/order/create",t)}function O(t){return a.default.get("store_integral/order/detail/".concat(t))}function z(t){return a.default.get("store_integral/detail/"+t,{},{noAuth:!0})}function M(t){return a.default.get("store_integral/list",t)}function A(t){return a.default.get("store_integral/order/list",t)}function R(t){return a.default.get("store_integral/order/express/".concat(t))}function F(t){return a.default.post("store_integral/order/take",t)}function j(t){return a.default.post("store_integral/order/del",t)}function N(t){return a.default.get("presale/list",t)}function U(t,e){return a.default.get("v2/promotions/productList/"+t,e)}function E(t){return a.default.get("v2/promotions/give_info/"+t)}},d0ca:function(t,e,n){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var i={data:function(){return{colorStyle:"",navigation:0,colorNum:0}},created:function(){var t=this;this.colorStyle=uni.getStorageSync("viewColor"),uni.$on("ok",(function(e){t.colorStyle=e})),this.navigation=uni.getStorageSync("navigation"),uni.$on("navOk",(function(e){t.navigation=e}))},methods:{colorData:function(){var t=this;this.colorNum=uni.getStorageSync("statusColor"),uni.$on("colorOk",(function(e){t.colorNum=e}))}}};e.default=i},e656:function(t,e,n){"use strict";n.r(e);var i=n("f461"),a=n("811e");for(var r in a)"default"!==r&&function(t){n.d(e,t,(function(){return a[t]}))}(r);n("3225");var o,u=n("f0c5"),s=Object(u["a"])(a["default"],i["b"],i["c"],!1,null,"f147cb98",null,!1,i["a"],o);e["default"]=s.exports},f461:function(t,e,n){"use strict";n.d(e,"b",(function(){return a})),n.d(e,"c",(function(){return r})),n.d(e,"a",(function(){return i}));var i={zbCode:n("7322").default},a=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("v-uni-view",{staticClass:"posterCon",style:t.colorStyle},[n("v-uni-view",{staticClass:"poster-poster"},[n("v-uni-view",{staticClass:"tip"},[n("v-uni-text",{staticClass:"iconfont icon-shuoming"}),t._v("提示：点击图片即可保存至手机相册")],1),n("v-uni-view",{staticClass:"pictrue"},[t.posterImage?n("v-uni-image",{staticClass:"canvas",attrs:{src:t.posterImage},on:{click:function(e){arguments[0]=e=t.$handleEvent(e),t.savePosterPathMp(t.posterImage)}}}):n("v-uni-canvas",{staticClass:"canvas",attrs:{"canvas-id":"myCanvas"}})],1)],1),n("zb-code",{ref:"qrcode",attrs:{show:t.codeShow,cid:t.cid,val:t.val,size:t.size,unit:t.unit,background:t.background,foreground:t.foreground,pdground:t.pdground,icon:t.icon,iconSize:t.iconsize,onval:t.onval,loadMake:t.loadMake},on:{result:function(e){arguments[0]=e=t.$handleEvent(e),t.qrR.apply(void 0,arguments)}}})],1)},r=[]}}]);