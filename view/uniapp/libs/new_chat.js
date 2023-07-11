// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

import $store from "@/store";
import wechat from "@/libs/wechat";
import {
	HTTP_REQUEST_URL,
	VUE_APP_WS_URL
} from "@/config/app.js";
import {
	getServerType
} from '@/api/api.js';
import {
	onNetworkStatusChange
} from '@/libs/network.js';


function wss(wsSocketUrl) {
	if (wsSocketUrl.indexOf('wss:') !== -1) {
		return wsSocketUrl;
	}
	// #ifdef H5
	let ishttps = document.location.protocol == 'https:';
	if (ishttps) {
		return wsSocketUrl.replace('ws:', 'wss:');
	} else {
		return wsSocketUrl.replace('wss:', 'ws:');
	}
	// #endif
	// #ifndef H5
	return wsSocketUrl.replace('ws:', 'wss:');
	//#endif
}

class Socket {
	constructor() {
		this.socketTask = null; //socket 任务
		this.timer = null; //心跳定时器
		this.connectStatus = false; //连接状态
		this.wsUrl = ''; //ws地址
		this.reconnectTimer = 2000; //重连
		this.handClse = false; //手动关闭
		this.reconnetime = null; //重连 定时器
		this.networkStatus = true;
		this.connectLing = false; //连接是否进行中
		this.defaultEvenv(); //执行默认事件
		this.networkEvent();
	}
	//网络状态变化监听
	networkEvent() {
		onNetworkStatusChange((res) => {
			console.log('有网了',res)
			this.networkStatus = true;
			if (this.socketTask) {
				this.socketTask.close();	
			}
			uni.$on('timeout', this.timeoutEvent.bind(this))
		}, () => {
			console.log('断网了')
			this.networkStatus = false;
			this.connectStatus = false;
			clearInterval(this.timer);
			this.timer = null;
			uni.$off('timeout', this.timeoutEvent)
		});
	}
	//开始连接
	startConnect() {
		console.log('开始链接')
		this.handClse = false;
		if (!this.connectStatus) {
			this.init();
			this.connect();
		}
	}
	//默认事件
	defaultEvenv() {

		uni.$off('success', this.successEvent);
		uni.$off('timeout', this.timeoutEvent);

		uni.$on('success', this.successEvent.bind(this));
		uni.$on('timeout', this.timeoutEvent.bind(this));
	}

	timeoutEvent() {
		console.log('timeoutEvent')
		this.reconne();
	}
	successEvent() {
		console.log('success默认事件');
		// this.changOnline();
	}
	//发送用户状态
	changOnline() {
		let online = cache.get('kefu_online')
		if (online !== undefined && online !== '') {
			this.send({
				data: {
					online: online
				},
				type: 'online'
			});
		}
	}

	//连接websocket
	connect() {
		this.connectLing = true;
		this.socketTask = uni.connectSocket({
			url: this.wsUrl,
			complete: () => {}
		});

		this.socketTask.onOpen(this.onOpen.bind(this))
		this.socketTask.onError(this.onError.bind(this));
		this.socketTask.onMessage(this.onMessage.bind(this))
		this.socketTask.onClose(this.onClose.bind(this));

	}

	init() {
		let wsUrl = wss(`${VUE_APP_WS_URL}?type=user`),
			form_type = 3;

		//#ifdef MP || APP-PLUS
		form_type = 2
		//#endif
		//#ifdef H5
		form_type = wechat.isWeixin() ? 1 : 3
		//#endif
		this.wsUrl = `${wsUrl}&token=${$store.state.app.token}&form_type=${form_type}`
	}

	//断线重连
	reconne() {
		if (this.reconnetime || this.connectStatus) {
			return;
		}
		this.reconnetime = setInterval(() => {
			if (this.connectStatus) {
				return;
			}
			this.connectLing || this.connect();
		}, this.reconnectTimer);
	}

	onOpen() {
		clearInterval(this.reconnetime);
		this.reconnetime = null;
		this.connectLing = false;
		this.connectStatus = true;
		this.ping();
	}

	onError(error) {
		console.log('连接发生错误', error)
		this.connectStatus = false;
		this.connectLing = false;
		this.reconne();
	}

	onClose(err) {
		console.log(this.socketTask, err, '关闭连接')
		uni.$emit('close');
		//手动关闭不用重新连接
		if (this.handClse) {
			return;
		}
		clearInterval(this.timer);
		this.timer = null;
		this.connectStatus = false;
		this.connectLing = false;
		this.reconne();
	}

	ping() {
		this.timer = setInterval(() => {
			this.send({
				type: 'ping'
			})
		}, 10000)
	}

	onMessage(response) {
		let {
			type,
			data
		} = JSON.parse(response.data);
		uni.$emit(type, data);
	}


	send(data) {
		let that = this;
		//没有网络,或者没有连接
		if (!this.connectStatus || !this.networkStatus) {
			this.reconne();
		}
		return new Promise((reslove, reject) => {
			this.socketTask.send({
				data: JSON.stringify(data),
				success() {
					reslove();
				},
				fail(res) {
					console.log(res)
					if (res.errMsg ==
						'sendSocketMessage:fail WebSocket is not connected' ||
						res.errMsg ==
						'sendSocketMessage:fail Error: SocketTask.readyState is not OPEN'
					) {
						that.reconne();
					}
					reject(res);
				},
				complete(res) {
					console.log(res)
				}
			})
		});
	}

	guid() {
		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
			var r = Math.random() * 16 | 0,
				v = c == 'x' ? r : (r & 0x3 | 0x8);
			return v.toString(16);
		});
	}

	clearPing() {
		clearInterval(this.timer);
		this.timer = null;
		if (this.connectStatus) {
			this.socketTask.close();
		}
		this.handClse = true;
		this.connectStatus = false;
		this.connectLing = false;
	}

	setBadgeNumber(count) {
		//#ifdef APP-PLUS
		plus.runtime.setBadgeNumber(Number(count));
		//#endif
	}

}


export default Socket;
