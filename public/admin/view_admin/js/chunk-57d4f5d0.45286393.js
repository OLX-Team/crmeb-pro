(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-57d4f5d0"],{"0ebd":function(e,t,r){},"0f7c":function(e,t,r){},3793:function(e,t,r){"use strict";r.r(t);var n=function(){var e=this,t=e.$createElement,r=e._self._c||t;return r("div",[r("div",{staticClass:"i-layout-page-header"},[r("PageHeader",{staticClass:"product_tabs",attrs:{title:"文件管理","hidden-breadcrumb":""}})],1),r("Card",{staticClass:"ivu-mt",attrs:{bordered:!1,"dis-hover":""}},[r("div",{staticClass:"backs",on:{click:e.goBack}},[r("Icon",{staticClass:"mr5",attrs:{type:"ios-folder-outline"}}),r("span",[e._v("返回上级")])],1),r("Table",{ref:"selection",staticClass:"mt20",attrs:{columns:e.columns4,data:e.tabList,loading:e.loading,"no-data-text":"暂无数据","highlight-row":"","no-filtered-data-text":"暂无筛选结果"},on:{"on-current-change":e.currentChange},scopedSlots:e._u([{key:"filename",fn:function(t){var n=t.row;return[n.isDir?r("Icon",{staticClass:"mr5",attrs:{type:"ios-folder-outline"}}):r("Icon",{staticClass:"mr5",attrs:{type:"ios-document-outline"}}),r("span",[e._v(e._s(n.filename))])]}},{key:"isWritable",fn:function(t){var n=t.row;return[r("span",{domProps:{textContent:e._s(n.isWritable?"是":"否")}})]}},{key:"action",fn:function(t){var n=t.row;t.index;return[n.isDir?r("a",{on:{click:function(t){return e.open(n)}}},[e._v("打开")]):r("a",{on:{click:function(t){return e.edit(n)}}},[e._v("编辑")])]}}])})],1),r("Modal",{attrs:{scrollable:"","footer-hide":"",closable:"",title:e.title,"mask-closable":!1,width:"900"},model:{value:e.modals,callback:function(t){e.modals=t},expression:"modals"}},[r("Button",{staticClass:"mr5 mb15",attrs:{type:"primary",id:"savefile"},on:{click:e.savefile}},[e._v("保存")]),r("Button",{staticClass:"mr5 mb15",attrs:{id:"undo"},on:{click:e.undofile}},[e._v("撤销")]),r("textarea",{directives:[{name:"model",rawName:"v-model",value:e.code,expression:"code"}],ref:"mycode",staticClass:"codesql public_text",domProps:{value:e.code},on:{input:function(t){t.target.composing||(e.code=t.target.value)}}}),e.spinShow?r("Spin",{attrs:{size:"large",fix:""}}):e._e()],1)],1)},a=[],i=r("a34a"),o=r.n(i),s=r("8593"),c=r("56b3"),u=r.n(c);r("0f7c");function l(e,t,r,n,a,i,o){try{var s=e[i](o),c=s.value}catch(u){return void r(u)}s.done?t(c):Promise.resolve(c).then(n,a)}function f(e){return function(){var t=this,r=arguments;return new Promise((function(n,a){var i=e.apply(t,r);function o(e){l(i,n,a,o,s,"next",e)}function s(e){l(i,n,a,o,s,"throw",e)}o(void 0)}))}}r("f9d4");var d={name:"opendir",data:function(){return{code:"",modals:!1,spinShow:!1,loading:!1,tabList:[],columns4:[{title:"文件/文件夹名",slot:"filename",minWidth:150,back:"返回上级"},{title:"文件/文件夹路径",key:"real_path",minWidth:150},{title:"文件/文件夹大小",key:"size",minWidth:100},{title:"是否可写",slot:"isWritable",minWidth:100},{title:"更新时间",key:"mtime",minWidth:150},{title:"Action",slot:"action",minWidth:150}],formItem:{dir:"",superior:0,filedir:""},rows:{},pathname:"",title:""}},mounted:function(){this.editor=u.a.fromTextArea(this.$refs.mycode,{value:"http://www.crmeb.com",mode:"text/javascript",theme:"ambiance",indentUnit:4,smartIndent:!0,tabSize:4,readOnly:!1,showCursorWhenSelecting:!0,lineNumbers:!0})},created:function(){this.getList()},methods:{currentChange:function(e){e.isDir?this.open(e):this.edit(e)},getList:function(){var e=this;this.loading=!0,Object(s["P"])(this.formItem).then(function(){var t=f(o.a.mark((function t(r){var n;return o.a.wrap((function(t){while(1)switch(t.prev=t.next){case 0:n=r.data,e.tabList=n.list,e.dir=n.dir,e.loading=!1;case 4:case"end":return t.stop()}}),t)})));return function(e){return t.apply(this,arguments)}}()).catch((function(t){e.loading=!1,e.$Message.error(t.msg)}))},goBack:function(){this.formItem={dir:this.dir,superior:1,filedir:""},this.getList()},open:function(e){this.rows=e,this.formItem={dir:e.path,superior:0,filedir:e.filename},this.getList()},edit:function(e){var t=this;this.modals=!0,this.spinShow=!0,this.pathname=e.pathname,this.title=e.filename,Object(s["Q"])(e.pathname).then(function(){var e=f(o.a.mark((function e(r){var n;return o.a.wrap((function(e){while(1)switch(e.prev=e.next){case 0:n=r.data,t.code=n.content,t.editor.setValue(t.code),t.spinShow=!1;case 4:case"end":return e.stop()}}),e)})));return function(t){return e.apply(this,arguments)}}()).catch((function(e){t.spinShow=!1,t.$Message.error(e.msg)}))},savefile:function(){var e=this,t={comment:this.editor.getValue(),filepath:this.pathname};Object(s["V"])(t).then(function(){var t=f(o.a.mark((function t(r){return o.a.wrap((function(t){while(1)switch(t.prev=t.next){case 0:e.$Message.success(r.msg),e.modals=!1;case 2:case"end":return t.stop()}}),t)})));return function(e){return t.apply(this,arguments)}}()).catch((function(t){e.$Message.error(t.msg)}))},undofile:function(){this.editor.undo()},redofile:function(){this.editor.redo()},refreshfile:function(){this.editor.refresh()}}},m=d,p=(r("5b05"),r("2877")),v=Object(p["a"])(m,n,a,!1,null,"3ab07172",null);t["default"]=v.exports},"5b05":function(e,t,r){"use strict";var n=r("0ebd"),a=r.n(n);a.a},f9d4:function(e,t,r){(function(e){e(r("56b3"))})((function(e){"use strict";e.defineMode("javascript",(function(t,r){var n,a,i=t.indentUnit,o=r.statementIndent,s=r.jsonld,c=r.json||s,u=r.typescript,l=r.wordCharacters||/[\w$\xa1-\uffff]/,f=function(){function e(e){return{type:e,style:"keyword"}}var t=e("keyword a"),r=e("keyword b"),n=e("keyword c"),a=e("keyword d"),i=e("operator"),o={type:"atom",style:"atom"};return{if:e("if"),while:t,with:t,else:r,do:r,try:r,finally:r,return:a,break:a,continue:a,new:e("new"),delete:n,void:n,throw:n,debugger:e("debugger"),var:e("var"),const:e("var"),let:e("var"),function:e("function"),catch:e("catch"),for:e("for"),switch:e("switch"),case:e("case"),default:e("default"),in:i,typeof:i,instanceof:i,true:o,false:o,null:o,undefined:o,NaN:o,Infinity:o,this:e("this"),class:e("class"),super:e("atom"),yield:n,export:e("export"),import:e("import"),extends:n,await:n}}(),d=/[+\-*&%=<>!?|~^@]/,m=/^@(context|id|value|language|type|container|list|set|reverse|index|base|vocab|graph)"/;function p(e){var t,r=!1,n=!1;while(null!=(t=e.next())){if(!r){if("/"==t&&!n)return;"["==t?n=!0:n&&"]"==t&&(n=!1)}r=!r&&"\\"==t}}function v(e,t,r){return n=e,a=r,t}function h(e,t){var r=e.next();if('"'==r||"'"==r)return t.tokenize=k(r),t.tokenize(e,t);if("."==r&&e.match(/^\d[\d_]*(?:[eE][+\-]?[\d_]+)?/))return v("number","number");if("."==r&&e.match(".."))return v("spread","meta");if(/[\[\]{}\(\),;\:\.]/.test(r))return v(r);if("="==r&&e.eat(">"))return v("=>","operator");if("0"==r&&e.match(/^(?:x[\dA-Fa-f_]+|o[0-7_]+|b[01_]+)n?/))return v("number","number");if(/\d/.test(r))return e.match(/^[\d_]*(?:n|(?:\.[\d_]*)?(?:[eE][+\-]?[\d_]+)?)?/),v("number","number");if("/"==r)return e.eat("*")?(t.tokenize=y,y(e,t)):e.eat("/")?(e.skipToEnd(),v("comment","comment")):et(e,t,1)?(p(e),e.match(/^\b(([gimyus])(?![gimyus]*\2))+\b/),v("regexp","string-2")):(e.eat("="),v("operator","operator",e.current()));if("`"==r)return t.tokenize=w,w(e,t);if("#"==r)return e.skipToEnd(),v("error","error");if("<"==r&&e.match("!--")||"-"==r&&e.match("->"))return e.skipToEnd(),v("comment","comment");if(d.test(r))return">"==r&&t.lexical&&">"==t.lexical.type||(e.eat("=")?"!"!=r&&"="!=r||e.eat("="):/[<>*+\-]/.test(r)&&(e.eat(r),">"==r&&e.eat(r))),v("operator","operator",e.current());if(l.test(r)){e.eatWhile(l);var n=e.current();if("."!=t.lastType){if(f.propertyIsEnumerable(n)){var a=f[n];return v(a.type,a.style,n)}if("async"==n&&e.match(/^(\s|\/\*.*?\*\/)*[\[\(\w]/,!1))return v("async","keyword",n)}return v("variable","variable",n)}}function k(e){return function(t,r){var n,a=!1;if(s&&"@"==t.peek()&&t.match(m))return r.tokenize=h,v("jsonld-keyword","meta");while(null!=(n=t.next())){if(n==e&&!a)break;a=!a&&"\\"==n}return a||(r.tokenize=h),v("string","string")}}function y(e,t){var r,n=!1;while(r=e.next()){if("/"==r&&n){t.tokenize=h;break}n="*"==r}return v("comment","comment")}function w(e,t){var r,n=!1;while(null!=(r=e.next())){if(!n&&("`"==r||"$"==r&&e.eat("{"))){t.tokenize=h;break}n=!n&&"\\"==r}return v("quasi","string-2",e.current())}var b="([{}])";function g(e,t){t.fatArrowAt&&(t.fatArrowAt=null);var r=e.string.indexOf("=>",e.start);if(!(r<0)){if(u){var n=/:\s*(?:\w+(?:<[^>]*>|\[\])?|\{[^}]*\})\s*$/.exec(e.string.slice(e.start,r));n&&(r=n.index)}for(var a=0,i=!1,o=r-1;o>=0;--o){var s=e.string.charAt(o),c=b.indexOf(s);if(c>=0&&c<3){if(!a){++o;break}if(0==--a){"("==s&&(i=!0);break}}else if(c>=3&&c<6)++a;else if(l.test(s))i=!0;else if(/["'\/`]/.test(s))for(;;--o){if(0==o)return;var f=e.string.charAt(o-1);if(f==s&&"\\"!=e.string.charAt(o-2)){o--;break}}else if(i&&!a){++o;break}}i&&!a&&(t.fatArrowAt=o)}}var x={atom:!0,number:!0,variable:!0,string:!0,regexp:!0,this:!0,"jsonld-keyword":!0};function j(e,t,r,n,a,i){this.indented=e,this.column=t,this.type=r,this.prev=a,this.info=i,null!=n&&(this.align=n)}function M(e,t){for(var r=e.localVars;r;r=r.next)if(r.name==t)return!0;for(var n=e.context;n;n=n.prev)for(r=n.vars;r;r=r.next)if(r.name==t)return!0}function C(e,t,r,n,a){var i=e.cc;I.state=e,I.stream=a,I.marked=null,I.cc=i,I.style=t,e.lexical.hasOwnProperty("align")||(e.lexical.align=!0);while(1){var o=i.length?i.pop():c?H:D;if(o(r,n)){while(i.length&&i[i.length-1].lex)i.pop()();return I.marked?I.marked:"variable"==r&&M(e,n)?"variable-2":t}}}var I={state:null,column:null,marked:null,cc:null};function V(){for(var e=arguments.length-1;e>=0;e--)I.cc.push(arguments[e])}function _(){return V.apply(null,arguments),!0}function A(e,t){for(var r=t;r;r=r.next)if(r.name==e)return!0;return!1}function E(e){var t=I.state;if(I.marked="def",t.context)if("var"==t.lexical.info&&t.context&&t.context.block){var n=z(e,t.context);if(null!=n)return void(t.context=n)}else if(!A(e,t.localVars))return void(t.localVars=new S(e,t.localVars));r.globalVars&&!A(e,t.globalVars)&&(t.globalVars=new S(e,t.globalVars))}function z(e,t){if(t){if(t.block){var r=z(e,t.prev);return r?r==t.prev?t:new $(r,t.vars,!0):null}return A(e,t.vars)?t:new $(t.prev,new S(e,t.vars),!1)}return null}function T(e){return"public"==e||"private"==e||"protected"==e||"abstract"==e||"readonly"==e}function $(e,t,r){this.prev=e,this.vars=t,this.block=r}function S(e,t){this.name=e,this.next=t}var O=new S("this",new S("arguments",null));function W(){I.state.context=new $(I.state.context,I.state.localVars,!1),I.state.localVars=O}function P(){I.state.context=new $(I.state.context,I.state.localVars,!0),I.state.localVars=null}function L(){I.state.localVars=I.state.context.vars,I.state.context=I.state.context.prev}function q(e,t){var r=function(){var r=I.state,n=r.indented;if("stat"==r.lexical.type)n=r.lexical.indented;else for(var a=r.lexical;a&&")"==a.type&&a.align;a=a.prev)n=a.indented;r.lexical=new j(n,I.stream.column(),e,null,r.lexical,t)};return r.lex=!0,r}function B(){var e=I.state;e.lexical.prev&&(")"==e.lexical.type&&(e.indented=e.lexical.indented),e.lexical=e.lexical.prev)}function N(e){function t(r){return r==e?_():";"==e||"}"==r||")"==r||"]"==r?V():_(t)}return t}function D(e,t){return"var"==e?_(q("vardef",t),Me,N(";"),B):"keyword a"==e?_(q("form"),F,D,B):"keyword b"==e?_(q("form"),D,B):"keyword d"==e?I.stream.match(/^\s*$/,!1)?_():_(q("stat"),G,N(";"),B):"debugger"==e?_(N(";")):"{"==e?_(q("}"),P,fe,B,L):";"==e?_():"if"==e?("else"==I.state.lexical.info&&I.state.cc[I.state.cc.length-1]==B&&I.state.cc.pop()(),_(q("form"),F,D,B,Ee)):"function"==e?_(Se):"for"==e?_(q("form"),ze,D,B):"class"==e||u&&"interface"==t?(I.marked="keyword",_(q("form","class"==e?e:t),qe,B)):"variable"==e?u&&"declare"==t?(I.marked="keyword",_(D)):u&&("module"==t||"enum"==t||"type"==t)&&I.stream.match(/^\s*\w/,!1)?(I.marked="keyword","enum"==t?_(Xe):"type"==t?_(We,N("operator"),he,N(";")):_(q("form"),Ce,N("{"),q("}"),fe,B,B)):u&&"namespace"==t?(I.marked="keyword",_(q("form"),H,D,B)):u&&"abstract"==t?(I.marked="keyword",_(D)):_(q("stat"),ae):"switch"==e?_(q("form"),F,N("{"),q("}","switch"),P,fe,B,B,L):"case"==e?_(H,N(":")):"default"==e?_(N(":")):"catch"==e?_(q("form"),W,U,D,B,L):"export"==e?_(q("stat"),Ue,B):"import"==e?_(q("stat"),Je,B):"async"==e?_(D):"@"==t?_(H,D):V(q("stat"),H,N(";"),B)}function U(e){if("("==e)return _(Pe,N(")"))}function H(e,t){return Q(e,t,!1)}function J(e,t){return Q(e,t,!0)}function F(e){return"("!=e?V():_(q(")"),H,N(")"),B)}function Q(e,t,r){if(I.state.fatArrowAt==I.stream.start){var n=r?ee:Z;if("("==e)return _(W,q(")"),ue(Pe,")"),B,N("=>"),n,L);if("variable"==e)return V(W,Ce,N("=>"),n,L)}var a=r?R:K;return x.hasOwnProperty(e)?_(a):"function"==e?_(Se,a):"class"==e||u&&"interface"==t?(I.marked="keyword",_(q("form"),Le,B)):"keyword c"==e||"async"==e?_(r?J:H):"("==e?_(q(")"),G,N(")"),B,a):"operator"==e||"spread"==e?_(r?J:H):"["==e?_(q("]"),Re,B,a):"{"==e?le(oe,"}",null,a):"quasi"==e?V(X,a):"new"==e?_(te(r)):"import"==e?_(H):_()}function G(e){return e.match(/[;\}\)\],]/)?V():V(H)}function K(e,t){return","==e?_(H):R(e,t,!1)}function R(e,t,r){var n=0==r?K:R,a=0==r?H:J;return"=>"==e?_(W,r?ee:Z,L):"operator"==e?/\+\+|--/.test(t)||u&&"!"==t?_(n):u&&"<"==t&&I.stream.match(/^([^>]|<.*?>)*>\s*\(/,!1)?_(q(">"),ue(he,">"),B,n):"?"==t?_(H,N(":"),a):_(a):"quasi"==e?V(X,n):";"!=e?"("==e?le(J,")","call",n):"."==e?_(ie,n):"["==e?_(q("]"),G,N("]"),B,n):u&&"as"==t?(I.marked="keyword",_(he,n)):"regexp"==e?(I.state.lastType=I.marked="operator",I.stream.backUp(I.stream.pos-I.stream.start-1),_(a)):void 0:void 0}function X(e,t){return"quasi"!=e?V():"${"!=t.slice(t.length-2)?_(X):_(H,Y)}function Y(e){if("}"==e)return I.marked="string-2",I.state.tokenize=w,_(X)}function Z(e){return g(I.stream,I.state),V("{"==e?D:H)}function ee(e){return g(I.stream,I.state),V("{"==e?D:J)}function te(e){return function(t){return"."==t?_(e?ne:re):"variable"==t&&u?_(ge,e?R:K):V(e?J:H)}}function re(e,t){if("target"==t)return I.marked="keyword",_(K)}function ne(e,t){if("target"==t)return I.marked="keyword",_(R)}function ae(e){return":"==e?_(B,D):V(K,N(";"),B)}function ie(e){if("variable"==e)return I.marked="property",_()}function oe(e,t){return"async"==e?(I.marked="property",_(oe)):"variable"==e||"keyword"==I.style?(I.marked="property","get"==t||"set"==t?_(se):(u&&I.state.fatArrowAt==I.stream.start&&(r=I.stream.match(/^\s*:\s*/,!1))&&(I.state.fatArrowAt=I.stream.pos+r[0].length),_(ce))):"number"==e||"string"==e?(I.marked=s?"property":I.style+" property",_(ce)):"jsonld-keyword"==e?_(ce):u&&T(t)?(I.marked="keyword",_(oe)):"["==e?_(H,de,N("]"),ce):"spread"==e?_(J,ce):"*"==t?(I.marked="keyword",_(oe)):":"==e?V(ce):void 0;var r}function se(e){return"variable"!=e?V(ce):(I.marked="property",_(Se))}function ce(e){return":"==e?_(J):"("==e?V(Se):void 0}function ue(e,t,r){function n(a,i){if(r?r.indexOf(a)>-1:","==a){var o=I.state.lexical;return"call"==o.info&&(o.pos=(o.pos||0)+1),_((function(r,n){return r==t||n==t?V():V(e)}),n)}return a==t||i==t?_():r&&r.indexOf(";")>-1?V(e):_(N(t))}return function(r,a){return r==t||a==t?_():V(e,n)}}function le(e,t,r){for(var n=3;n<arguments.length;n++)I.cc.push(arguments[n]);return _(q(t,r),ue(e,t),B)}function fe(e){return"}"==e?_():V(D,fe)}function de(e,t){if(u){if(":"==e)return _(he);if("?"==t)return _(de)}}function me(e,t){if(u&&(":"==e||"in"==t))return _(he)}function pe(e){if(u&&":"==e)return I.stream.match(/^\s*\w+\s+is\b/,!1)?_(H,ve,he):_(he)}function ve(e,t){if("is"==t)return I.marked="keyword",_()}function he(e,t){return"keyof"==t||"typeof"==t||"infer"==t?(I.marked="keyword",_("typeof"==t?J:he)):"variable"==e||"void"==t?(I.marked="type",_(be)):"|"==t||"&"==t?_(he):"string"==e||"number"==e||"atom"==e?_(be):"["==e?_(q("]"),ue(he,"]",","),B,be):"{"==e?_(q("}"),ue(ye,"}",",;"),B,be):"("==e?_(ue(we,")"),ke,be):"<"==e?_(ue(he,">"),he):void 0}function ke(e){if("=>"==e)return _(he)}function ye(e,t){return"variable"==e||"keyword"==I.style?(I.marked="property",_(ye)):"?"==t||"number"==e||"string"==e?_(ye):":"==e?_(he):"["==e?_(N("variable"),me,N("]"),ye):"("==e?V(Oe,ye):void 0}function we(e,t){return"variable"==e&&I.stream.match(/^\s*[?:]/,!1)||"?"==t?_(we):":"==e?_(he):"spread"==e?_(we):V(he)}function be(e,t){return"<"==t?_(q(">"),ue(he,">"),B,be):"|"==t||"."==e||"&"==t?_(he):"["==e?_(he,N("]"),be):"extends"==t||"implements"==t?(I.marked="keyword",_(he)):"?"==t?_(he,N(":"),he):void 0}function ge(e,t){if("<"==t)return _(q(">"),ue(he,">"),B,be)}function xe(){return V(he,je)}function je(e,t){if("="==t)return _(he)}function Me(e,t){return"enum"==t?(I.marked="keyword",_(Xe)):V(Ce,de,_e,Ae)}function Ce(e,t){return u&&T(t)?(I.marked="keyword",_(Ce)):"variable"==e?(E(t),_()):"spread"==e?_(Ce):"["==e?le(Ve,"]"):"{"==e?le(Ie,"}"):void 0}function Ie(e,t){return"variable"!=e||I.stream.match(/^\s*:/,!1)?("variable"==e&&(I.marked="property"),"spread"==e?_(Ce):"}"==e?V():"["==e?_(H,N("]"),N(":"),Ie):_(N(":"),Ce,_e)):(E(t),_(_e))}function Ve(){return V(Ce,_e)}function _e(e,t){if("="==t)return _(J)}function Ae(e){if(","==e)return _(Me)}function Ee(e,t){if("keyword b"==e&&"else"==t)return _(q("form","else"),D,B)}function ze(e,t){return"await"==t?_(ze):"("==e?_(q(")"),Te,B):void 0}function Te(e){return"var"==e?_(Me,$e):"variable"==e?_($e):V($e)}function $e(e,t){return")"==e?_():";"==e?_($e):"in"==t||"of"==t?(I.marked="keyword",_(H,$e)):V(H,$e)}function Se(e,t){return"*"==t?(I.marked="keyword",_(Se)):"variable"==e?(E(t),_(Se)):"("==e?_(W,q(")"),ue(Pe,")"),B,pe,D,L):u&&"<"==t?_(q(">"),ue(xe,">"),B,Se):void 0}function Oe(e,t){return"*"==t?(I.marked="keyword",_(Oe)):"variable"==e?(E(t),_(Oe)):"("==e?_(W,q(")"),ue(Pe,")"),B,pe,L):u&&"<"==t?_(q(">"),ue(xe,">"),B,Oe):void 0}function We(e,t){return"keyword"==e||"variable"==e?(I.marked="type",_(We)):"<"==t?_(q(">"),ue(xe,">"),B):void 0}function Pe(e,t){return"@"==t&&_(H,Pe),"spread"==e?_(Pe):u&&T(t)?(I.marked="keyword",_(Pe)):u&&"this"==e?_(de,_e):V(Ce,de,_e)}function Le(e,t){return"variable"==e?qe(e,t):Be(e,t)}function qe(e,t){if("variable"==e)return E(t),_(Be)}function Be(e,t){return"<"==t?_(q(">"),ue(xe,">"),B,Be):"extends"==t||"implements"==t||u&&","==e?("implements"==t&&(I.marked="keyword"),_(u?he:H,Be)):"{"==e?_(q("}"),Ne,B):void 0}function Ne(e,t){return"async"==e||"variable"==e&&("static"==t||"get"==t||"set"==t||u&&T(t))&&I.stream.match(/^\s+[\w$\xa1-\uffff]/,!1)?(I.marked="keyword",_(Ne)):"variable"==e||"keyword"==I.style?(I.marked="property",_(u?De:Se,Ne)):"number"==e||"string"==e?_(u?De:Se,Ne):"["==e?_(H,de,N("]"),u?De:Se,Ne):"*"==t?(I.marked="keyword",_(Ne)):u&&"("==e?V(Oe,Ne):";"==e||","==e?_(Ne):"}"==e?_():"@"==t?_(H,Ne):void 0}function De(e,t){if("?"==t)return _(De);if(":"==e)return _(he,_e);if("="==t)return _(J);var r=I.state.lexical.prev,n=r&&"interface"==r.info;return V(n?Oe:Se)}function Ue(e,t){return"*"==t?(I.marked="keyword",_(Ke,N(";"))):"default"==t?(I.marked="keyword",_(H,N(";"))):"{"==e?_(ue(He,"}"),Ke,N(";")):V(D)}function He(e,t){return"as"==t?(I.marked="keyword",_(N("variable"))):"variable"==e?V(J,He):void 0}function Je(e){return"string"==e?_():"("==e?V(H):V(Fe,Qe,Ke)}function Fe(e,t){return"{"==e?le(Fe,"}"):("variable"==e&&E(t),"*"==t&&(I.marked="keyword"),_(Ge))}function Qe(e){if(","==e)return _(Fe,Qe)}function Ge(e,t){if("as"==t)return I.marked="keyword",_(Fe)}function Ke(e,t){if("from"==t)return I.marked="keyword",_(H)}function Re(e){return"]"==e?_():V(ue(J,"]"))}function Xe(){return V(q("form"),Ce,N("{"),q("}"),ue(Ye,"}"),B,B)}function Ye(){return V(Ce,_e)}function Ze(e,t){return"operator"==e.lastType||","==e.lastType||d.test(t.charAt(0))||/[,.]/.test(t.charAt(0))}function et(e,t,r){return t.tokenize==h&&/^(?:operator|sof|keyword [bcd]|case|new|export|default|spread|[\[{}\(,;:]|=>)$/.test(t.lastType)||"quasi"==t.lastType&&/\{\s*$/.test(e.string.slice(0,e.pos-(r||0)))}return L.lex=!0,B.lex=!0,{startState:function(e){var t={tokenize:h,lastType:"sof",cc:[],lexical:new j((e||0)-i,0,"block",!1),localVars:r.localVars,context:r.localVars&&new $(null,null,!1),indented:e||0};return r.globalVars&&"object"==typeof r.globalVars&&(t.globalVars=r.globalVars),t},token:function(e,t){if(e.sol()&&(t.lexical.hasOwnProperty("align")||(t.lexical.align=!1),t.indented=e.indentation(),g(e,t)),t.tokenize!=y&&e.eatSpace())return null;var r=t.tokenize(e,t);return"comment"==n?r:(t.lastType="operator"!=n||"++"!=a&&"--"!=a?n:"incdec",C(t,r,n,a,e))},indent:function(t,n){if(t.tokenize==y)return e.Pass;if(t.tokenize!=h)return 0;var a,s=n&&n.charAt(0),c=t.lexical;if(!/^\s*else\b/.test(n))for(var u=t.cc.length-1;u>=0;--u){var l=t.cc[u];if(l==B)c=c.prev;else if(l!=Ee)break}while(("stat"==c.type||"form"==c.type)&&("}"==s||(a=t.cc[t.cc.length-1])&&(a==K||a==R)&&!/^[,\.=+\-*:?[\(]/.test(n)))c=c.prev;o&&")"==c.type&&"stat"==c.prev.type&&(c=c.prev);var f=c.type,d=s==f;return"vardef"==f?c.indented+("operator"==t.lastType||","==t.lastType?c.info.length+1:0):"form"==f&&"{"==s?c.indented:"form"==f?c.indented+i:"stat"==f?c.indented+(Ze(t,n)?o||i:0):"switch"!=c.info||d||0==r.doubleIndentSwitch?c.align?c.column+(d?0:1):c.indented+(d?0:i):c.indented+(/^(?:case|default)\b/.test(n)?i:2*i)},electricInput:/^\s*(?:case .*?:|default:|\{|\})$/,blockCommentStart:c?null:"/*",blockCommentEnd:c?null:"*/",blockCommentContinue:c?null:" * ",lineComment:c?null:"//",fold:"brace",closeBrackets:"()[]{}''\"\"``",helperType:c?"json":"javascript",jsonldMode:s,jsonMode:c,expressionAllowed:et,skipExpression:function(e){var t=e.cc[e.cc.length-1];t!=H&&t!=J||e.cc.pop()}}})),e.registerHelper("wordChars","javascript",/[\w$]/),e.defineMIME("text/javascript","javascript"),e.defineMIME("text/ecmascript","javascript"),e.defineMIME("application/javascript","javascript"),e.defineMIME("application/x-javascript","javascript"),e.defineMIME("application/ecmascript","javascript"),e.defineMIME("application/json",{name:"javascript",json:!0}),e.defineMIME("application/x-json",{name:"javascript",json:!0}),e.defineMIME("application/ld+json",{name:"javascript",jsonld:!0}),e.defineMIME("text/typescript",{name:"javascript",typescript:!0}),e.defineMIME("application/typescript",{name:"javascript",typescript:!0})}))}}]);