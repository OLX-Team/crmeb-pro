(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["pages/index/components/customerService"],{"00a8":function(t,n,o){"use strict";var e=o("2845"),a=o.n(e);a.a},"0269":function(t,n,o){"use strict";o.r(n);var e=o("80a2"),a=o("518b");for(var i in a)"default"!==i&&function(t){o.d(n,t,(function(){return a[t]}))}(i);o("00a8");var u,c=o("f0c5"),r=Object(c["a"])(a["default"],e["b"],e["c"],!1,null,null,null,!1,e["a"],u);n["default"]=r.exports},2845:function(t,n,o){},4255:function(t,n,o){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var e={name:"customerService",props:{dataConfig:{type:Object,default:function(){}},isSortType:{type:String|Number,default:0}},data:function(){return{routineContact:this.dataConfig.routine_contact_type,logoConfig:this.dataConfig.logoConfig.url,topConfig:this.dataConfig.topConfig.val?this.dataConfig.topConfig.val>=80?"80%":this.dataConfig.topConfig.val+"%":"30%"}},created:function(){},methods:{setTouchMove:function(t){var n=this;t.touches[0].clientY<545&&t.touches[0].clientY>66&&(n.topConfig=t.touches[0].clientY+"px")}}};n.default=e},"518b":function(t,n,o){"use strict";o.r(n);var e=o("4255"),a=o.n(e);for(var i in e)"default"!==i&&function(t){o.d(n,t,(function(){return e[t]}))}(i);n["default"]=a.a},"80a2":function(t,n,o){"use strict";var e;o.d(n,"b",(function(){return a})),o.d(n,"c",(function(){return i})),o.d(n,"a",(function(){return e}));var a=function(){var t=this,n=t.$createElement;t._self._c},i=[]}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'pages/index/components/customerService-create-component',
    {
        'pages/index/components/customerService-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("0269"))
        })
    },
    [['pages/index/components/customerService-create-component']]
]);
