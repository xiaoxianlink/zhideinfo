function onBridgeReady(){
		WeixinJSBridge.call('hideOptionMenu');
}

function isWeixinBrowser(){
  //����2014��2��12��,����������ܲ���windows phone�е�΢�������
  return (/MicroMessenger/i).test(window.navigator.userAgent);
}
if(!isWeixinBrowser()){
	alert("please open in Weixin");
	window.opener = null;
	window.open(location, '_self').close();
}
if (typeof WeixinJSBridge == "undefined"){
	if( document.addEventListener ){
		document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
	}else if (document.attachEvent){
		document.attachEvent('WeixinJSBridgeReady', onBridgeReady); 
		document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
	}
}else{
	onBridgeReady();
}