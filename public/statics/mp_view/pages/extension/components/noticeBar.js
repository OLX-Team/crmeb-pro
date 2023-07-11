(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/extension/components/noticeBar"],{"0667":function(t,n,e){"use strict";e.r(n);var a=e("275c"),i=e("634f");for(var r in i)"default"!==r&&function(t){e.d(n,t,(function(){return i[t]}))}(r);e("0dc9");var c,u=e("f0c5"),s=Object(u["a"])(i["default"],a["b"],a["c"],!1,null,"29b52cd5",null,!1,a["a"],c);n["default"]=s.exports},"0dc9":function(t,n,e){"use strict";var a=e("6dca"),i=e.n(a);i.a},1046:function(t,n,e){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var a={name:"noticeBar",data:function(){return{animateUp:!1,listData:JSON.parse(JSON.stringify(this.showMsg)),timer:null}},props:{showMsg:{type:Array}},mounted:function(){this.timer=setInterval(this.scrollAnimate,2500)},methods:{scrollAnimate:function(){var t=this;this.animateUp=!0,setTimeout((function(){t.listData.push(t.listData[0]),t.listData.shift(),t.animateUp=!1}),500)}},destroyed:function(){clearInterval(this.timer)}};n.default=a},"275c":function(t,n,e){"use strict";var a;e.d(n,"b",(function(){return i})),e.d(n,"c",(function(){return r})),e.d(n,"a",(function(){return a}));var i=function(){var t=this,n=t.$createElement;t._self._c},r=[]},"634f":function(t,n,e){"use strict";e.r(n);var a=e("1046"),i=e.n(a);for(var r in a)"default"!==r&&function(t){e.d(n,t,(function(){return a[t]}))}(r);n["default"]=i.a},"6dca":function(t,n,e){}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/extension/components/noticeBar-create-component',
    {
        'pages/extension/components/noticeBar-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("0667"))
        })
    },
    [['pages/extension/components/noticeBar-create-component']]
]);
