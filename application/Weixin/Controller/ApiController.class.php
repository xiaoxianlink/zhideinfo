<?php

namespace Weixin\Controller;
use Common\Controller\HomeBaseController;
//use Weixin\Controller\IndexController;
use Think\Log;

class ApiController extends HomeBaseController {
	
	function index() {
		$this->display();
	}
	
	// 获取Access Token
	function get_access_token() {
		$log = new Log ();
		$wxt_model = M ( "wx_token" ); 
		$wxt = $wxt_model->find (); 
		if (empty ( $wxt )) { 
			$token = $this->__get_access_token (); 
			$log->write ( "token: " . $token, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
			$data = array ( 
				"token" => $token, 
				"c_time" => time () 
			); 
			$wxt_model->add ( $data ); 
			return $token;
		} else {
			if ($wxt ['token'] == '' || $wxt ['token'] == null || $wxt ['c_time'] < (time () - 3600 * 2)) {
				$token = $this->__get_access_token ();
				$log->write ( "token: " . $token, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
				$data = array ( 
					"token" => $token, 
					"c_time" => time () 
				); 
				$wxt_model->where ( "id={$wxt['id']}" )->save ( $data );
			}else {
				$token = $wxt['token'];
				$log->write ( "wxtoken: " . $token, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
			}
			return $token;
		}
	}
	
	function __get_access_token() {
		$appid = APPID;
		$appsecret = APPSECRET;
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
		
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$output = curl_exec ( $ch );
		curl_close ( $ch );
		$jsoninfo = json_decode ( $output, true );
		$access_token = $jsoninfo ["access_token"];
		return $access_token;
	}
	
	function getJsApiTicket() {
		$log = new Log ();
		$wxt_model = M ( "wx_ticket" ); 
		$wxt = $wxt_model->find (); 
		if (empty ( $wxt )) { 
			$ticket = $this->__getJsApiTicket ();
			$log->write ( "ticket: " . $ticket, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
			$data = array ( 
				"ticket" => $ticket, 
				"c_time" => time () 
			); 
			$wxt_model->add ( $data ); 
			return $ticket;
		} else {
			if ($wxt ['ticket'] == '' || $wxt ['ticket'] == null || $wxt ['c_time'] < (time () - 3600 * 2)) {
				$ticket = $this->__getJsApiTicket (); 
				$log->write ( "ticket: " . $ticket, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
				$data = array ( 
					"ticket" => $ticket, 
					"c_time" => time () 
				); 
				$wxt_model->where ( "id={$wxt['id']}" )->save ( $data );
			}else {
				$ticket = $wxt['ticket'];
				$log->write ( "wxticket: " . $ticket, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
			}
			return $ticket;
		}
	}
	
	function __getJsApiTicket() {
		$log = new Log ();
		$accessToken = $this->get_access_token ();
		$url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
		$res = $this->getJson($url);
		$log->write ( "__getJsApiTicket: " . json_encode($res), 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
		$ticket = $res["ticket"];
		return $ticket;
	}
	
	function createNonceStr($length = 16) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}
		return $str;
	}
	
	public function getSignPackage() {
		$jsapiTicket = $this->getJsApiTicket();
		$url = $this->curPageURL();
		$timestamp = time();
		$nonceStr = $this->createNonceStr();

		// 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

		$signature = sha1($string);

		$signPackage = array(
			"appId"     => APPID,
			"nonceStr"  => $nonceStr,
			"timestamp" => $timestamp,
			"url"       => $url,
			"signature" => $signature,
			"rawString" => $string
		);
		return $signPackage;
	}
	
	// 获取用户信息
	function get_user_info($openid) {
		$appid = APPID;
		$appsecret = APPSECRET;
		$access_token = $this->get_access_token ();
		$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token&openid=$openid&lang=zh_CN";
		
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$output = curl_exec ( $ch );
		curl_close ( $ch );
		$jsoninfo = json_decode ( $output, true );
		return $jsoninfo;
	}
	
	// 发送自定义的模板消息
	public function doSend($id, $endorsement, $touser, $template_id, $url, $data, $topcolor = '#7B68EE') {
		/*
		 * $data = array ( 'first' => array ( 'value' => urlencode ( "您好,您已购买成功" ), 'color' => "#743A3A" ), 'name' => array ( 'value' => urlencode ( "商品信息:微时代电影票" ), 'color' => '#EEEEEE' ), 'remark' => array ( 'value' => urlencode ( '永久有效!密码为:1231313' ), 'color' => '#FFFFFF' ) );
		 */
		$log = new Log ();
		$template = array (
				'touser' => $touser,
				'template_id' => $template_id,
				'url' => $url,
				'topcolor' => $topcolor,
				'data' => $data 
		);
		$json_template = json_encode ( $template );
		$url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $this->get_access_token ();
		$dataRes = $this->request_post ( $url, urldecode ( $json_template ) );
		if ($id != 0) {
			$data = array (
					"from_userid" => 0,
					"openid" => $touser,
					"msg_type" => 1,
					"tar_id" => $id,
					"create_time" => time (),
					"nums" => $endorsement ['nums'],
					"all_points" => $endorsement ['all_points'],
					"all_money" => $endorsement ['all_money'] 
			);
			$model = M ( "Message" );
			$model->add ( $data );
		}
		$log->write ( serialize ( $dataRes ), 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
		if ($dataRes ['errcode'] == 0) {
			return true;
		} else {
			return false;
		}
	}
	// 网页授权
	function oauth($redirect_uri, $scope, $state = '') {
		$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . APPID . '&redirect_uri=' . urlencode ( $redirect_uri ) . '&response_type=code&scope=' . $scope . '&state=' . $state . '#wechat_redirect';
		header ( "Location:" . $url );
	}
	
	function getJson($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$output = curl_exec($ch);
		curl_close($ch);
		return json_decode($output, true);
	}
	
	// 网页授权获取openid
	function get_oauth($code) {
		$log = new Log ();
		$get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . APPID . '&secret=' . APPSECRET . '&code=' . $code . '&grant_type=authorization_code';
		$json_obj = $this->getJson ($get_token_url);
		$log->write ( "get_oauth: ". $get_token_url . ", result: " . json_encode($json_obj) , 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
		return $json_obj;
	}
	
	function get_oauth_openid($code) {
		$oauth = $this->get_oauth($code);
		return $oauth ['openid'];
	}
	
	function get_oauth_userinfo($code){
		$oauth = $this->get_oauth($code);
		if($oauth["errcode"]){
			return false;
		}
		//第二步:根据全局access_token和openid查询用户信息  
		$access_token = $oauth["access_token"];  
		$openid = $oauth['openid'];  
		$get_user_info_url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
		$userinfo = $this->getJson($get_user_info_url);
		return $userinfo;
	}
	/**
	 * 发送post请求
	 *
	 * @param string $url        	
	 * @param string $param        	
	 * @return bool mixed
	 */
	function request_post($url = '', $param = '') {
		if (empty ( $url ) || empty ( $param )) {
			return false;
		}
		$postUrl = $url;
		$curlPost = $param;
		$ch = curl_init (); // 初始化curl
		curl_setopt ( $ch, CURLOPT_URL, $postUrl ); // 抓取指定网页
		curl_setopt ( $ch, CURLOPT_HEADER, 0 ); // 设置header
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 ); // 要求结果为字符串且输出到屏幕上
		curl_setopt ( $ch, CURLOPT_POST, 1 ); // post提交方式
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $curlPost );
		$data = curl_exec ( $ch ); // 运行curl
		curl_close ( $ch );
		return $data;
	}
	
	// 发送自定义的模板消息
	public function sendWeiXin($touser, $template_id, $url, $data, $topcolor = '#7B68EE') {
		$log = new Log ();
		$template = array (
				'touser' => $touser,
				'template_id' => $template_id,
				'url' => $url,
				'topcolor' => $topcolor,
				'data' => $data 
		);
		$json_template = json_encode ( $template );
		$url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $this->get_access_token ();
		$dataRes = $this->request_post ( $url, urldecode ( $json_template ) );
		$log->write ( "sendWeixin: " . $json_template, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
		$log->write ( "sendWeixin: " . serialize ( $dataRes ), 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
		if ($dataRes ['errcode'] == 0) {
			return true;
		} else {
			return false;
		}
	}

	function is_weixin(){
		if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false ) {
			return true;
		}	
		return false;
	}

	function curPageURL() {
		$pageURL = 'http';
		if ($_SERVER["HTTPS"] == "on"){
			$pageURL .= "s";
		}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} 
		else {
			$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}
	
}