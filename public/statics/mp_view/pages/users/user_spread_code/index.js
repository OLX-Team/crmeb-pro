(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/users/user_spread_code/index"],{"0504":function(e,t,n){"use strict";n.d(t,"b",(function(){return r})),n.d(t,"c",(function(){return i})),n.d(t,"a",(function(){return a}));var a={zbCode:function(){return Promise.all([n.e("common/vendor"),n.e("components/zb-code/zb-code")]).then(n.bind(null,"7322"))}},r=function(){var e=this,t=e.$createElement;e._self._c},i=[]},"0a4c":function(e,t,n){"use strict";(function(e){Object.defineProperty(t,"__esModule",{value:!0}),t.default=void 0;var a=c(n("a34a")),r=n("d5e6"),i=n("fa9e"),s=n("26cb"),o=n("12b8"),u=c(n("d0ca"));function c(e){return e&&e.__esModule?e:{default:e}}function d(e,t,n,a,r,i,s){try{var o=e[i](s),u=o.value}catch(c){return void n(c)}o.done?t(u):Promise.resolve(u).then(a,r)}function f(e){return function(){var t=this,n=arguments;return new Promise((function(a,r){var i=e.apply(t,n);function s(e){d(i,a,r,s,o,"next",e)}function o(e){d(i,a,r,s,o,"throw",e)}s(void 0)}))}}var l=function(){Promise.all([n.e("common/vendor"),n.e("components/zb-code/zb-code")]).then(function(){return resolve(n("7322"))}.bind(null,n)).catch(n.oe)},h=function(){n.e("components/Authorize").then(function(){return resolve(n("1702"))}.bind(null,n)).catch(n.oe)},p=function(){Promise.all([n.e("common/vendor"),n.e("components/home/index")]).then(function(){return resolve(n("f176"))}.bind(null,n)).catch(n.oe)},m={components:{authorize:h,home:p,zbCode:l},mixins:[u.default],data:function(){return{imgUrls:[],indicatorDots:!1,posterImageStatus:!0,circular:!1,autoplay:!1,interval:3e3,duration:500,swiperIndex:0,spreadList:[],userInfo:{},poster:"",isAuto:!1,isShowAuth:!1,spreadData:[{}],nickName:"",siteName:"",mpUrl:"",canvasImageUrl:"",posterImage:[],codeShow:!1,cid:"1",ifShow:!0,val:"",size:200,unit:"upx",background:"#FFF",foreground:"#000",pdground:"#000",icon:"",iconsize:40,lv:3,onval:!0,loadMake:!0,src:"",codeSrc:"",wd:0,hg:0,qrcode:""}},computed:(0,s.mapGetters)({isLogin:"isLogin",userData:"userInfo",uid:"uid"}),watch:{isLogin:{handler:function(e,t){e&&this.userSpreadBannerList()},deep:!0},userData:{handler:function(e,t){e&&this.$set(this,"userInfo",e)},deep:!0}},onReady:function(){var e=this;return f(a.default.mark((function t(){return a.default.wrap((function(t){while(1)switch(t.prev=t.next){case 0:if(!e.isLogin){t.next=7;break}return e.val="".concat(o.HTTP_REQUEST_URL,"?spread=").concat(e.uid),t.next=4,e.spreadMsg();case 4:(0,r.getUserInfo)().then((function(t){e.userInfo=t.data})),t.next=8;break;case 7:(0,i.toLogin)();case 8:case"end":return t.stop()}}),t)})))()},onShow:function(){var t=this;this.$nextTick((function(){var n=e.createSelectorQuery().select(".aaa");n.fields({size:!0},(function(e){t.wd=e.width,t.hg=e.height})).exec()}))},onShareAppMessage:function(){return{title:this.userInfo.nickname+"-分销海报",imageUrl:this.spreadList[0],path:"/pages/index/index?spread="+this.userInfo.uid}},methods:{qrR:function(e){var t=this;return f(a.default.mark((function n(){return a.default.wrap((function(n){while(1)switch(n.prev=n.next){case 0:return n.next=2,e;case 2:t.codeSrc=n.sent;case 3:case"end":return n.stop()}}),n)})))()},spreadMsg:function(){var t=this;return f(a.default.mark((function n(){var i,s,o;return a.default.wrap((function(n){while(1)switch(n.prev=n.next){case 0:return n.next=2,(0,r.spreadMsg)();case 2:i=n.sent,t.spreadData=i.data.spread,t.nickName=i.data.nickname,t.siteName=i.data.site_name,e.showLoading({title:"海报生成中",mask:!0}),s=a.default.mark((function e(n){var r,s,o;return a.default.wrap((function(e){while(1)switch(e.prev=e.next){case 0:return r=t,s=[],e.next=4,t.imgToBase(i.data.spread[n].pic);case 4:return o=e.sent,e.next=7,t.imgToBase(i.data.qrcode);case 7:return e.sent,e.next=10,t.routineCode();case 10:return e.next=12,t.downloadFilestoreImage(i.data.spread[n].pic);case 12:o=e.sent,s=[t.mpUrl,o],t.$nextTick((function(){r.$util.userPosterCanvas(s,i.data.nickname,i.data.site_name,n,this.wd,this.hg,(function(e){if(r.$set(r.posterImage,n,e),!r.posterImage.length)return r.$util.Tips({title:"小程序二维码需要发布正式版后才能获取到"})}))}));case 15:case"end":return e.stop()}}),e)})),o=0;case 9:if(!(o<i.data.spread.length)){n.next=14;break}return n.delegateYield(s(o),"t0",11);case 11:o++,n.next=9;break;case 14:e.hideLoading();case 15:case"end":return n.stop()}}),n)})))()},routineCode:function(){var e=this;return f(a.default.mark((function t(){var n;return a.default.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.next=2,(0,r.routineCode)();case 2:return n=t.sent,t.next=5,e.downloadFilestoreImage(n.data.url);case 5:e.mpUrl=t.sent;case 6:case"end":return t.stop()}}),t)})))()},imgToBase:function(e){return f(a.default.mark((function t(){var n;return a.default.wrap((function(t){while(1)switch(t.prev=t.next){case 0:return t.next=2,(0,r.imgToBase)({image:e});case 2:return n=t.sent,t.abrupt("return",n.data.image);case 4:case"end":return t.stop()}}),t)})))()},authColse:function(e){this.isShowAuth=e},bindchange:function(e){this.spreadList;this.swiperIndex=e.detail.current},savePosterPathMp:function(t){var n=this;e.getSetting({success:function(a){a.authSetting["scope.writePhotosAlbum"]?e.saveImageToPhotosAlbum({filePath:t,success:function(e){n.$util.Tips({title:"保存成功",icon:"success"})},fail:function(e){n.$util.Tips({title:"保存失败"})}}):e.authorize({scope:"scope.writePhotosAlbum",success:function(){e.saveImageToPhotosAlbum({filePath:t,success:function(e){n.$util.Tips({title:"保存成功",icon:"success"})},fail:function(e){n.$util.Tips({title:"保存失败"})}})}})}})},downloadFilestoreImage:function(t){var n=this;return new Promise((function(a,r){var i=n;e.downloadFile({url:t,success:function(e){a(e.tempFilePath)},fail:function(){return i.$util.Tips({title:""})}})}))},setShareInfoStatus:function(){var e=this;this.$wechat.isWeixin()&&(this.isLogin?(0,r.getUserInfo)().then((function(t){var n={desc:"分销海报",title:t.data.nickname+"-分销海报",link:"/pages/index/index?spread="+t.data.uid,imgUrl:e.spreadList[0]};e.$wechat.wechatEvevt(["updateAppMessageShareData","updateTimelineShareData"],n)})):(0,i.toLogin)())},userSpreadBannerList:function(){var t=this;e.showLoading({title:"获取中",mask:!0}),(0,r.spreadBanner)().then((function(n){e.hideLoading(),t.$set(t,"spreadList",n.data),t.$set(t,"poster",n.data[0].poster)})).catch((function(t){e.hideLoading()}))}}};t.default=m}).call(this,n("543d")["default"])},"354b":function(e,t,n){},4957:function(e,t,n){"use strict";n.r(t);var a=n("0a4c"),r=n.n(a);for(var i in a)"default"!==i&&function(e){n.d(t,e,(function(){return a[e]}))}(i);t["default"]=r.a},b2ff:function(e,t,n){"use strict";var a=n("354b"),r=n.n(a);r.a},c6f0:function(e,t,n){"use strict";n.r(t);var a=n("0504"),r=n("4957");for(var i in r)"default"!==i&&function(e){n.d(t,e,(function(){return r[e]}))}(i);n("b2ff");var s,o=n("f0c5"),u=Object(o["a"])(r["default"],a["b"],a["c"],!1,null,null,null,!1,a["a"],s);t["default"]=u.exports},fa06:function(e,t,n){"use strict";(function(e){n("22b3");a(n("66fd"));var t=a(n("c6f0"));function a(e){return e&&e.__esModule?e:{default:e}}wx.__webpack_require_UNI_MP_PLUGIN__=n,e(t.default)}).call(this,n("543d")["createPage"])}},[["fa06","common/runtime","common/vendor"]]]);