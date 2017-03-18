<?php
if($_SERVER ['SERVER_NAME'] == "zhide.xiaoxianlink.com"){
	define ( 'runEnv', "production" );
}
else{
	define ( 'runEnv', "dev" );
}
if(runEnv == "production"){
	define ( "APPID", "wx2a38c59eef8274b4" );
	define ( "APPSECRET", "d19e283fbc32f7edbbdf2c53540163b1" );
}
define ( "versions", 'v1.0' ); // 版本号
define ( "TOKEN", "weixin" );
define ( "MCHID", "1293235801" ); // 商户id
define ( "KEY", "54cbf62f9e8ef2da4b9df54ca8d3920e" ); // 微信支付秘钥
