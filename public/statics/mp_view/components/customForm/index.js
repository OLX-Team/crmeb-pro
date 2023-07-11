(global["webpackJsonp"]=global["webpackJsonp"]||[]).push([["components/customForm/index"],{"34b2":function(t,n,u){"use strict";(function(t){Object.defineProperty(n,"__esModule",{value:!0}),n.default=void 0;var u={name:"customForm",props:{customForm:{type:Array}},data:function(){return{isShow:0}},watch:{customForm:function(t){var n=this;t&&t.length&&t.forEach((function(t){if(t.value)return n.isShow=1}))}},created:function(){},mounted:function(){},methods:{getCustomForm:function(n,u){t.previewImage({urls:this.customForm[n].value,current:this.customForm[n].value[u]})}}};n.default=u}).call(this,u("543d")["default"])},"3c07":function(t,n,u){"use strict";u.r(n);var r=u("b090"),e=u("75fd");for(var o in e)"default"!==o&&function(t){u.d(n,t,(function(){return e[t]}))}(o);u("743d");var c,a=u("f0c5"),f=Object(a["a"])(e["default"],r["b"],r["c"],!1,null,null,null,!1,r["a"],c);n["default"]=f.exports},"743d":function(t,n,u){"use strict";var r=u("f438"),e=u.n(r);e.a},"75fd":function(t,n,u){"use strict";u.r(n);var r=u("34b2"),e=u.n(r);for(var o in r)"default"!==o&&function(t){u.d(n,t,(function(){return r[t]}))}(o);n["default"]=e.a},b090:function(t,n,u){"use strict";var r;u.d(n,"b",(function(){return e})),u.d(n,"c",(function(){return o})),u.d(n,"a",(function(){return r}));var e=function(){var t=this,n=t.$createElement;t._self._c},o=[]},f438:function(t,n,u){}}]);
;(global["webpackJsonp"] = global["webpackJsonp"] || []).push([
    'components/customForm/index-create-component',
    {
        'components/customForm/index-create-component':(function(module, exports, __webpack_require__){
            __webpack_require__('543d')['createComponent'](__webpack_require__("3c07"))
        })
    },
    [['components/customForm/index-create-component']]
]);
