(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/users/message_center/messageDetail"],{"19d2":function(t,e,n){"use strict";(function(t){Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var a=n("d5e6"),u={data:function(){return{msgData:{}}},onLoad:function(t){this.getMsgDetails(t.id)},methods:{getMsgDetails:function(e){var n=this;t.showLoading({title:"获取详情中"}),(0,a.getMsgDetails)(e).then((function(e){t.hideLoading(),n.msgData=e.data})).catch((function(e){return t.hideLoading(),n.$util.Tips({title:e})}))}}};e.default=u}).call(this,n("543d")["default"])},3723:function(t,e,n){"use strict";(function(t){n("22b3");a(n("66fd"));var e=a(n("5b07"));function a(t){return t&&t.__esModule?t:{default:t}}wx.__webpack_require_UNI_MP_PLUGIN__=n,t(e.default)}).call(this,n("543d")["createPage"])},"58e9":function(t,e,n){"use strict";n.r(e);var a=n("19d2"),u=n.n(a);for(var c in a)"default"!==c&&function(t){n.d(e,t,(function(){return a[t]}))}(c);e["default"]=u.a},"5b07":function(t,e,n){"use strict";n.r(e);var a=n("7a5f"),u=n("58e9");for(var c in u)"default"!==c&&function(t){n.d(e,t,(function(){return u[t]}))}(c);n("c7c4");var i,r=n("f0c5"),o=Object(r["a"])(u["default"],a["b"],a["c"],!1,null,"6ca5c48a",null,!1,a["a"],i);e["default"]=o.exports},"6c94":function(t,e,n){},"7a5f":function(t,e,n){"use strict";var a;n.d(e,"b",(function(){return u})),n.d(e,"c",(function(){return c})),n.d(e,"a",(function(){return a}));var u=function(){var t=this,e=t.$createElement;t._self._c},c=[]},c7c4:function(t,e,n){"use strict";var a=n("6c94"),u=n.n(a);u.a}},[["3723","common/runtime","common/vendor"]]]);