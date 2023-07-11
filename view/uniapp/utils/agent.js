function checkMac(){
	if (/(iPhone|iPad|iPod|iOS|macintosh|mac os x)/i.test(navigator.userAgent)) {  
	    return true
	}
}

export const wx  = checkMac() ? wx : jWeixin;