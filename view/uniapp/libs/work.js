import {getWorkConfig,getWorkAgentConfig} from "@/api/work.js"
// import {wx} from "@/utils/agent.js"
export function initWxConfig() {
    return getTicket;
}

export function initAgentConfig() {
    return agentConfig;
};

//
const getTicket = new Promise((resolve, reject) => {
	getWorkConfig(location.href).then(res=>{
		if (/(iPhone|iPad|iPod|iOS|macintosh|mac os x)/i.test(navigator.userAgent)) {
			wx.config({
				beta: true,// 必须这么写，否则wx.invoke调用形式的jsapi会有问题
				debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
				appId: res.data.appId, // 必填，企业微信的corpID
				timestamp:res.data.timestamp , // 必填，生成签名的时间戳
				nonceStr: res.data.nonceStr, // 必填，生成签名的随机串
				signature: res.data.signature,// 必填，签名，见 附录-JS-SDK使用权限签名算法
				jsApiList: res.data.jsApiList // 必填，需要使用的JS接口列表，凡是要调用的接口都需要传进来
			});
			wx.ready(function() {
				// resolve(wx);
				setTimeout(()=>{
					getWorkAgentConfig(location.href).then(response=>{
						wx.agentConfig({
						    corpid: response.data.corpid, // 必填，企业微信的corpid，必须与当前登录的企业一致
						    agentid: response.data.agentid, // 必填，企业微信的应用id （e.g. 1000247）
						    timestamp: response.data.timestamp, // 必填，生成签名的时间戳
						    nonceStr: response.data.nonceStr, // 必填，生成签名的随机串
						    signature: response.data.signature,// 必填，签名，见附录-JS-SDK使用权限签名算法
						    jsApiList: response.data.jsApiList, //必填，传入需要使用的接口名称
							success: function(data) {
						        resolve(data);
						    },
						    fail: function(err) {
						        if(err.errMsg.indexOf('function not exist') > -1){
									reject('版本过低请升级');
						        }
						    }
						});
					})
				},1000)
			})
		}else{
			// window.wx = window.jWeixin;
			jWeixin.config({
				beta: true,// 必须这么写，否则wx.invoke调用形式的jsapi会有问题
				debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
				appId: res.data.appId, // 必填，企业微信的corpID
				timestamp:res.data.timestamp , // 必填，生成签名的时间戳
				nonceStr: res.data.nonceStr, // 必填，生成签名的随机串
				signature: res.data.signature,// 必填，签名，见 附录-JS-SDK使用权限签名算法
				jsApiList: res.data.jsApiList // 必填，需要使用的JS接口列表，凡是要调用的接口都需要传进来
			});
			jWeixin.ready(function() {
				// resolve(wx);
				getWorkAgentConfig(location.href).then(response=>{
					jWeixin.agentConfig({
						corpid: response.data.corpid, // 必填，企业微信的corpid，必须与当前登录的企业一致
						agentid: response.data.agentid, // 必填，企业微信的应用id （e.g. 1000247）
						timestamp: response.data.timestamp, // 必填，生成签名的时间戳
						nonceStr: response.data.nonceStr, // 必填，生成签名的随机串
						signature: response.data.signature,// 必填，签名，见附录-JS-SDK使用权限签名算法
						jsApiList: response.data.jsApiList, //必填，传入需要使用的接口名称
						success: function(data) {
							resolve(data);
						},
						fail: function(err) {
							if(err.errMsg.indexOf('function not exist') > -1){
								reject('版本过低请升级');
							}
						}
					});
				})
			})
		}
		
	}).catch(err=>{
		reject(err);
	})
})

const agentConfig = new Promise((resolve, reject)=>{
	getWorkAgentConfig(location.href).then(res=>{
		wx.agentConfig({
		    corpid: res.data.corpid, // 必填，企业微信的corpid，必须与当前登录的企业一致
		    agentid: res.data.agentid, // 必填，企业微信的应用id （e.g. 1000247）
		    timestamp: res.data.timestamp, // 必填，生成签名的时间戳
		    nonceStr: res.data.nonceStr, // 必填，生成签名的随机串
		    signature: res.data.signature,// 必填，签名，见附录-JS-SDK使用权限签名算法
		    jsApiList: res.data.jsApiList, //必填，传入需要使用的接口名称
			success: function(res) {
		        resolve(res);
		    },
		    fail: function(res) {
		        if(res.errMsg.indexOf('function not exist') > -1){
					reject('版本过低请升级');
		        }
		    }
		});
	})
})






 

