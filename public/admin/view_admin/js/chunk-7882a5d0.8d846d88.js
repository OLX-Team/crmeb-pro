(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-7882a5d0"],{"0b65":function(t,e,a){"use strict";e["a"]={shortcuts:[{text:"今天",value:function(){var t=new Date,e=new Date;return e.setTime(new Date((new Date).getFullYear(),(new Date).getMonth(),(new Date).getDate())),[e,t]}},{text:"昨天",value:function(){var t=new Date,e=new Date;return e.setTime(e.setTime(new Date((new Date).getFullYear(),(new Date).getMonth(),(new Date).getDate()-1))),t.setTime(t.setTime(new Date((new Date).getFullYear(),(new Date).getMonth(),(new Date).getDate()-1))),[e,t]}},{text:"最近7天",value:function(){var t=new Date,e=new Date;return e.setTime(e.setTime(new Date((new Date).getFullYear(),(new Date).getMonth(),(new Date).getDate()-6))),[e,t]}},{text:"最近30天",value:function(){var t=new Date,e=new Date;return e.setTime(e.setTime(new Date((new Date).getFullYear(),(new Date).getMonth(),(new Date).getDate()-29))),[e,t]}},{text:"本月",value:function(){var t=new Date,e=new Date;return e.setTime(e.setTime(new Date((new Date).getFullYear(),(new Date).getMonth(),1))),[e,t]}},{text:"本年",value:function(){var t=new Date,e=new Date;return e.setTime(e.setTime(new Date((new Date).getFullYear(),0,1))),[e,t]}}]}},"0e64":function(t,e,a){},"5fe3":function(t,e,a){},"73f5":function(t,e,a){"use strict";a.d(e,"u",(function(){return n})),a.d(e,"n",(function(){return o})),a.d(e,"j",(function(){return i})),a.d(e,"m",(function(){return s})),a.d(e,"o",(function(){return c})),a.d(e,"p",(function(){return u})),a.d(e,"f",(function(){return l})),a.d(e,"e",(function(){return d})),a.d(e,"K",(function(){return m})),a.d(e,"d",(function(){return f})),a.d(e,"r",(function(){return p})),a.d(e,"q",(function(){return h})),a.d(e,"s",(function(){return v})),a.d(e,"l",(function(){return g})),a.d(e,"B",(function(){return _})),a.d(e,"C",(function(){return b})),a.d(e,"z",(function(){return w})),a.d(e,"A",(function(){return y})),a.d(e,"c",(function(){return x})),a.d(e,"w",(function(){return k})),a.d(e,"x",(function(){return O})),a.d(e,"y",(function(){return j})),a.d(e,"J",(function(){return D})),a.d(e,"E",(function(){return V})),a.d(e,"D",(function(){return C})),a.d(e,"a",(function(){return I})),a.d(e,"I",(function(){return F})),a.d(e,"h",(function(){return T})),a.d(e,"F",(function(){return L})),a.d(e,"G",(function(){return S})),a.d(e,"H",(function(){return M})),a.d(e,"g",(function(){return P})),a.d(e,"k",(function(){return z})),a.d(e,"v",(function(){return $})),a.d(e,"i",(function(){return W})),a.d(e,"t",(function(){return B})),a.d(e,"b",(function(){return N}));var r=a("b6bd");function n(t){return Object(r["a"])({url:"merchant/store_list",method:"get",params:t})}function o(t){return Object(r["a"])({url:"store/order/list",method:"get",params:t})}function i(t){return Object(r["a"])({url:"store/order/chart",method:"get",params:t})}function s(t){return Object(r["a"])({url:"store/order/header",method:"get"})}function c(t){return Object(r["a"])({url:"store/recharge",method:"get",params:t})}function u(t){return Object(r["a"])({url:"store/vip_order",method:"get",params:t})}function l(t){return Object(r["a"])({url:"store/order/edit/".concat(t),method:"get"})}function d(t){return Object(r["a"])({url:"store/order/distribution/".concat(t),method:"get"})}function m(t){return Object(r["a"])({url:"store/order/write_update/".concat(t),method:"put"})}function f(t){return Object(r["a"])({url:"store/order/info/".concat(t),method:"get"})}function p(t){return Object(r["a"])({url:"order/remark/".concat(t.id),method:"put",data:t.remark})}function h(t){return Object(r["a"])({url:"store/recharge/remark/".concat(t.id),method:"put",data:t.remark})}function v(t){return Object(r["a"])({url:"store/vip/remark/".concat(t.id),method:"put",data:t.remark})}function g(t,e){return Object(r["a"])({url:"store/order/export/".concat(e),method:"post",data:t})}function _(t){return Object(r["a"])({url:"store/finance_flow/list",method:"get",params:t})}function b(t,e){return Object(r["a"])({url:"store/finance_flow/mark/".concat(t),method:"put",params:e})}function w(t){return Object(r["a"])({url:"store/finance_flow/fund_record",method:"get",params:t})}function y(t){return Object(r["a"])({url:"store/finance_flow/fund_record_info",method:"get",params:t})}function x(t){return Object(r["a"])({url:"/export/storeFinanceRecord",method:"get",params:t})}function k(t){return Object(r["a"])({url:"/store/extract/list",method:"get",params:t})}function O(t,e){return Object(r["a"])({url:"store/extract/mark/".concat(t),method:"post",data:e})}function j(t,e){return Object(r["a"])({url:"store/extract/verify/".concat(t),method:"post",data:e})}function D(t){return Object(r["a"])({url:"store/extract/transfer/".concat(t),method:"get"})}function V(t){return Object(r["a"])({url:"store/store",method:"get",params:t})}function C(t){return Object(r["a"])({url:"store/store/get_info/".concat(t),method:"get"})}function I(t){return Object(r["a"])({url:"city",method:"get",params:t})}function F(t,e){return Object(r["a"])({url:"store/store/".concat(t),method:"post",params:e})}function T(){return Object(r["a"])({url:"store/store/address",method:"get"})}function L(t){return Object(r["a"])({url:"store/store/login/".concat(t),method:"get"})}function S(t,e){return Object(r["a"])({url:"store/store/set_show/".concat(t,"/").concat(e),method:"put"})}function M(t){return Object(r["a"])({url:"store/share/order",method:"post",params:t})}function P(t){return Object(r["a"])({url:"store/home/header",method:"get",params:t})}function z(t){return Object(r["a"])({url:"store/home/orderChart",method:"get",params:t})}function $(t){return Object(r["a"])({url:"store/home/store",method:"get",params:t})}function W(t){return Object(r["a"])({url:"store/home/operate",method:"get",params:t})}function B(t){return Object(r["a"])({url:"store/store/reset_admin/".concat(t),method:"get"})}function N(t,e,a){return Object(r["a"])({url:"export/storeFlowExport?store_id=".concat(t,"&keyword=").concat(e,"&data=").concat(a),method:"get"})}},7600:function(t,e,a){"use strict";var r=a("5fe3"),n=a.n(r);n.a},"94dd":function(t,e,a){"use strict";var r=a("0e64"),n=a.n(r);n.a},a584:function(t,e,a){"use strict";var r=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",[a("Row",{staticClass:"ivu-mt",attrs:{type:"flex",align:"middle",gutter:16}},t._l(t.cardLists,(function(e,r){return a("Col",{key:r,staticClass:"ivu-mb",attrs:{xl:e.col,lg:6,md:12,sm:24,xs:24}},[a("Card",{staticClass:"card_cent",attrs:{shadow:"",padding:0}},[a("div",{staticClass:"card_box"},[a("div",{staticClass:"card_box_cir",class:{one:r%5==0,two:r%5==1,three:r%5==2,four:r%5==3,five:r%5==4}},[a("div",{staticClass:"card_box_cir1",class:{one1:r%5==0,two1:r%5==1,three1:r%5==2,four1:r%5==3,five1:r%5==4}},[e.type?a("span",{staticClass:"iconfont",class:e.className}):a("Icon",{attrs:{type:e.className}})],1)]),a("div",{staticClass:"card_box_txt"},[a("span",{staticClass:"sp1",domProps:{textContent:t._s(e.count||0)}}),a("span",{staticClass:"sp2",domProps:{textContent:t._s(e.name)}})])])])],1)})),1)],1)},n=[],o={name:"cards",data:function(){return{}},props:{cardLists:Array},methods:{},created:function(){}},i=o,s=(a("94dd"),a("2877")),c=Object(s["a"])(i,r,n,!1,null,"286c7c6e",null);e["a"]=c.exports},b57e:function(t,e,a){"use strict";a.r(e);var r=function(){var t=this,e=t.$createElement,a=t._self._c||e;return a("div",[a("Card",{staticClass:"ivu-mt",attrs:{bordered:!1,"dis-hover":"",padding:0}},[a("div",{staticClass:"new_card_pd"},[a("Form",{ref:"formValidate",attrs:{inline:"",model:t.formValidate,"label-width":t.labelWidth,"label-position":t.labelPosition},nativeOn:{submit:function(t){t.preventDefault()}}},[a("FormItem",{attrs:{label:"选择门店："}},[a("Select",{staticStyle:{width:"250px"},attrs:{placeholder:"请选择",clearable:"",filterable:""},model:{value:t.formValidate.store_id,callback:function(e){t.$set(t.formValidate,"store_id",e)},expression:"formValidate.store_id"}},t._l(t.staff,(function(e,r){return a("Option",{key:r,attrs:{value:e.id}},[t._v(t._s(e.name))])})),1)],1),a("FormItem",{attrs:{label:"创建时间："}},[a("DatePicker",{staticStyle:{width:"250px"},attrs:{editable:!1,value:t.timeVal,format:"yyyy/MM/dd",type:"datetimerange",placement:"bottom-start",placeholder:"自定义时间",options:t.options},on:{"on-change":t.onchangeTime}})],1),a("FormItem",{attrs:{label:"审核状态："}},[a("Select",{staticStyle:{width:"250px"},model:{value:t.formValidate.status,callback:function(e){t.$set(t.formValidate,"status",e)},expression:"formValidate.status"}},t._l(t.fromLists.status,(function(e,r){return a("Option",{key:r,attrs:{value:e.val}},[t._v(t._s(e.text))])})),1)],1),a("FormItem",{attrs:{label:"收款方式：","label-for":"status1"}},[a("Select",{staticStyle:{width:"250px"},attrs:{placeholder:"请选择",clearable:"","element-id":"status1"},model:{value:t.formValidate.extract_type,callback:function(e){t.$set(t.formValidate,"extract_type",e)},expression:"formValidate.extract_type"}},[a("Option",{attrs:{value:"bank"}},[t._v("银行卡")]),a("Option",{attrs:{value:"weixin"}},[t._v("微信")]),a("Option",{attrs:{value:"alipay"}},[t._v("支付宝")])],1)],1),a("FormItem",{attrs:{label:"转账状态：","label-for":"status1"}},[a("Select",{staticStyle:{width:"250px","margin-right":"14px"},attrs:{placeholder:"请选择",clearable:"","element-id":"status1"},model:{value:t.formValidate.pay_status,callback:function(e){t.$set(t.formValidate,"pay_status",e)},expression:"formValidate.pay_status"}},[a("Option",{attrs:{value:"0"}},[t._v("未转账")]),a("Option",{attrs:{value:"1"}},[t._v("已转账")])],1),a("Button",{attrs:{type:"primary"},on:{click:function(e){return t.searchs()}}},[t._v("查询")])],1),a("FormItem")],1)],1)]),t.cardLists.length>=0?a("cards-data",{attrs:{cardLists:t.cardLists}}):t._e(),a("Card",{staticClass:"ive-mt",attrs:{bordered:!1,"dis-hover":""}},[a("div",{staticClass:"table"},[a("Table",{ref:"table",attrs:{columns:t.columns,data:t.orderList,loading:t.loading,"highlight-row":"","no-userFrom-text":"暂无数据","no-filtered-userFrom-text":"暂无筛选结果"},scopedSlots:t._u([{key:"action",fn:function(e){var r=e.row;return[0==r.status?a("a",{on:{click:function(e){return t.examine(r)}}},[t._v("审核")]):t._e(),0==r.status?a("Divider",{attrs:{type:"vertical"}}):t._e(),1==r.status&&0==r.pay_status?a("a",{on:{click:function(e){return t.paying(r)}}},[t._v("转账")]):t._e(),1==r.status&&0==r.pay_status?a("Divider",{attrs:{type:"vertical"}}):t._e(),a("a",{on:{click:function(e){return t.remark(r)}}},[t._v("备注")])]}},{key:"admin_id",fn:function(t){var e=t.row;return[a("div",{staticClass:"tabBox_img"},[a("img",{directives:[{name:"lazy",rawName:"v-lazy",value:e.store.image,expression:"row.store.image"}]})])]}},{key:"store",fn:function(e){var r=e.row;return[a("div",[t._v("门店名称："+t._s(r.store.name))]),a("viewer")]}},{key:"extract_type",fn:function(e){var r=e.row;return["bank"==r.extract_type?a("span",[t._v("银行卡")]):t._e(),"weixin"==r.extract_type?a("span",[t._v("微信")]):t._e(),"alipay"==r.extract_type?a("span",[t._v("支付宝")]):t._e()]}},{key:"type",fn:function(e){var r=e.row;return["bank"==r.extract_type?a("div",[a("div",[t._v("开户地址："+t._s(r.bank_address))]),a("div",[t._v("银行卡："+t._s(r.bank_code))])]):t._e(),"weixin"==r.extract_type?a("div",[a("div",[t._v("微信号："+t._s(r.wechat))]),a("viewer",[a("div",{staticClass:"tabBox_img"},[a("img",{directives:[{name:"lazy",rawName:"v-lazy",value:r.qrcode_url,expression:"row.qrcode_url"}]})])])],1):t._e(),"alipay"==r.extract_type?a("div",[a("div",[t._v("支付宝账号："+t._s(r.alipay_account))]),a("viewer",[a("div",{staticClass:"tabBox_img"},[a("img",{directives:[{name:"lazy",rawName:"v-lazy",value:r.qrcode_url,expression:"row.qrcode_url"}]})])])],1):t._e()]}},{key:"status",fn:function(e){var r=e.row;return[0==r.status?a("Tag",{attrs:{color:"orange",size:"medium"}},[t._v("未审核")]):t._e(),1==r.status?a("Tag",{attrs:{color:"green",size:"medium"}},[t._v("已通过")]):t._e(),a("Tooltip",{attrs:{placement:"top",content:r.fail_msg,delay:500}},[-1==r.status?a("Tag",{attrs:{color:"red",size:"medium"}},[t._v("未通过")]):t._e()],1)]}},{key:"pay_status",fn:function(e){var r=e.row;return[1==r.pay_status?a("Tag",{attrs:{color:"green"}},[t._v("已转账")]):t._e(),a("Tooltip",{attrs:{placement:"top",delay:500}},[a("template",{slot:"content"},[a("div",[t._v("转账说明："+t._s(r.voucher_title))]),r.voucher_image?a("viewer",[a("div",{staticClass:"tabBox_img"},[a("img",{directives:[{name:"lazy",rawName:"v-lazy",value:r.voucher_image,expression:"row.voucher_image"}]})])]):t._e()],1),0==r.pay_status?a("Tag",{attrs:{color:"red",size:"medium"}},[t._v("未转账")]):t._e()],2)]}}])}),a("div",{staticClass:"acea-row row-right page"},[a("Page",{attrs:{total:t.total,current:t.formValidate.page,"show-elevator":"","show-total":"","page-size":t.formValidate.limit},on:{"on-change":t.pageChange}})],1)],1)]),a("Modal",{staticClass:"order_box",attrs:{scrollable:"",title:"请修改内容",closable:!1,"mask-closable":!1},model:{value:t.modalmark,callback:function(e){t.modalmark=e},expression:"modalmark"}},[a("Form",{ref:"remarks",attrs:{model:t.remarks,"label-width":80},nativeOn:{submit:function(t){t.preventDefault()}}},[a("FormItem",{attrs:{label:"备注："}},[a("Input",{staticStyle:{width:"100%"},attrs:{maxlength:"200","show-word-limit":"",type:"textarea",placeholder:"请填写备注~"},model:{value:t.remarks.mark,callback:function(e){t.$set(t.remarks,"mark",e)},expression:"remarks.mark"}})],1)],1),a("div",{attrs:{slot:"footer"},slot:"footer"},[a("Button",{attrs:{type:"primary"},on:{click:function(e){return t.putRemark()}}},[t._v("提交")]),a("Button",{on:{click:function(e){return t.cancel()}}},[t._v("取消")])],1)],1),a("Modal",{attrs:{title:"审核","mask-closable":!1},model:{value:t.modal,callback:function(e){t.modal=e},expression:"modal"}},[a("Form",{attrs:{model:t.formItem,"label-width":80}},[a("FormItem",{attrs:{label:"审核状态："}},[a("RadioGroup",{model:{value:t.formItem.type,callback:function(e){t.$set(t.formItem,"type",e)},expression:"formItem.type"}},[a("Radio",{attrs:{label:"1"}},[t._v("通过")]),a("Radio",{attrs:{label:"2"}},[t._v("拒绝")])],1)],1),2==t.formItem.type?a("FormItem",{attrs:{label:"拒绝原因："}},[a("Input",{attrs:{type:"textarea",autosize:{minRows:2,maxRows:5},placeholder:"请输入拒绝原因"},model:{value:t.formItem.message,callback:function(e){t.$set(t.formItem,"message",e)},expression:"formItem.message"}})],1):t._e()],1),a("div",{attrs:{slot:"footer"},slot:"footer"},[a("Button",{attrs:{type:"primary"},on:{click:t.ok}},[t._v("提交")]),a("Button",{on:{click:t.cancel}},[t._v("取消")])],1)],1)],1)},n=[],o=a("2f62"),i=a("73f5"),s=a("a584"),c=a("0b65");function u(t,e){var a=Object.keys(t);if(Object.getOwnPropertySymbols){var r=Object.getOwnPropertySymbols(t);e&&(r=r.filter((function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable}))),a.push.apply(a,r)}return a}function l(t){for(var e=1;e<arguments.length;e++){var a=null!=arguments[e]?arguments[e]:{};e%2?u(a,!0).forEach((function(e){d(t,e,a[e])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(a)):u(a).forEach((function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(a,e))}))}return t}function d(t,e,a){return e in t?Object.defineProperty(t,e,{value:a,enumerable:!0,configurable:!0,writable:!0}):t[e]=a,t}var m={name:"bill",components:{cardsData:s["a"]},data:function(){return{id:0,modal:!1,modalmark:!1,formItem:{type:"1",message:""},remarkId:0,remarks:{mark:""},total:0,cardLists:[],extractStatistics:[],staff:[],loading:!1,columns:[{title:"ID",key:"id",width:60},{title:"图片",slot:"admin_id",width:60},{title:"门店",slot:"store",minWidth:120},{title:"申请金额",key:"extract_price",minWidth:80},{title:"申请时间",key:"add_time",minWidth:150},{title:"收款方式",slot:"extract_type",minWidth:80},{title:"收款信息",slot:"type",minWidth:200},{title:"审核状态",slot:"status",minWidth:150},{title:"转账状态",slot:"pay_status",minWidth:180},{title:"管理员",key:"admin_name",minWidth:80},{title:"备注",key:"store_mark",minWidth:150},{title:"操作",slot:"action",fixed:"right",minWidth:120,align:"center"}],orderList:[],formValidate:{pay_status:"",extract_type:"",data:"",status:"",store_id:"",page:1,limit:15},timeVal:[],options:c["a"],fromLists:{custom:!0,status:[{text:"全部",val:""},{text:"待审核",val:"0"},{text:"已通过",val:"1"},{text:"未通过",val:"-1"}]}}},computed:l({},Object(o["e"])("admin/layout",["isMobile"]),{labelWidth:function(){return this.isMobile?void 0:96},labelPosition:function(){return this.isMobile?"top":"right"}}),mounted:function(){this.staffApi(),this.getList()},methods:{getList:function(){var t=this;this.loading=!0,Object(i["w"])(this.formValidate).then((function(e){t.orderList=e.data.list.list,t.total=e.data.list.count,t.extractStatistics=e.data.extract_statistics,t.cardLists=[{col:6,count:t.extractStatistics.unPayPrice,name:"待转账金额",className:"md-basket"},{col:6,count:t.extractStatistics.price,name:"待审核金额",className:"md-cash"},{col:6,count:t.extractStatistics.price_not,name:"可提现金额",className:"ios-cash"},{col:6,count:t.extractStatistics.paidPrice,name:"累计提现金额",className:"ios-cash"}],t.loading=!1}))},staffApi:function(){var t=this;Object(i["u"])().then((function(e){t.staff=e.data}))},searchs:function(){this.formValidate.page=1,this.getList()},selectChange:function(t){this.formValidate.page=1,this.formValidate.data=t,this.timeVal=[],this.getList()},payStatus:function(t){this.formValidate.page=1,this.formValidate.status=t,this.getList()},onchangeTime:function(t){this.timeVal=t,this.formValidate.data=this.timeVal[0]?this.timeVal.join("-"):"",this.formValidate.page=1,this.getList()},pageChange:function(t){this.formValidate.page=t,this.getList()},remark:function(t){this.remarkId=t.id,this.remarks.mark=t.store_mark,this.modalmark=!0},examine:function(t){this.modal=!0,this.id=t.id},paying:function(t){var e=this;this.$modalForm(Object(i["J"])(t.id)).then((function(){return e.getList()}))},ok:function(){var t=this;2!=this.formItem.type||""!=this.formItem.message?Object(i["y"])(this.id,this.formItem).then((function(e){t.$Message.success(e.msg),t.formItem={type:"1",message:""},t.modal=!1,t.getList()})).catch((function(e){t.$Message.error(e.msg),t.modal=!1})):this.$Message.error("请填写拒绝原因")},cancel:function(){this.formItem={type:"1",message:""},this.remarks.mark="",this.modal=!1,this.modalmark=!1},putRemark:function(){var t=this;Object(i["x"])(this.remarkId,this.remarks).then((function(e){t.$Message.success(e.msg),t.modalmark=!1,t.getList()})).catch((function(e){t.$Message.error(e.msg)}))}}},f=m,p=(a("7600"),a("2877")),h=Object(p["a"])(f,r,n,!1,null,"2d573761",null);e["default"]=h.exports}}]);