export function onNetworkStatusChange(onlineFun, offlineFun) {
	uni.onNetworkStatusChange(res => {
		if(res.networkType !== 'none') { 
			onlineFun && onlineFun(res);
		} else{
			offlineFun && offlineFun(res);
		}
	});
}
