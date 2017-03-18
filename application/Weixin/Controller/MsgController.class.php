<?php

namespace Weixin\Controller;
//use Common\Controller\HomeBaseController;
use Weixin\Controller\ApiController;
use Think\Log;

class MsgController extends ApiController {

	public function index() {
		
		if(! $this->is_weixin()){
			echo "please open in wechat";
			exit;
		}
		
		$log = new Log ();
		
		$is_author = false;
		$op = 0;
		if(isset($_GET ['op'])){
			if($_GET ['op'] == "edit"){
				$op = 1;
			}
		}
		if (! isset ( $_GET ['id'] )){
			echo "bad request";
		}
		$msg_id = $_GET ['id'];
		$msg_model = M ( "msg" );
		$msg = $msg_model->where ( "id = %d", $msg_id)->find ();
		if(!empty($msg) ){
			if (! isset ( $_GET ['code'] ) && $this->is_weixin()) {
				$redirect_uri = $this->curPageURL();
				//$scope = 'snsapi_base';
				$scope = 'snsapi_userinfo';
				$log->write ( "sub请求: ". $redirect_uri, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
				$this->oauth ( $redirect_uri, $scope );
			} else {
				$code = ( string ) $_GET ['code'];
				//$open_id = $this->get_oauth_openid ( $code );
				$userinfo = $this->get_oauth_userinfo($code);
				if($userinfo == false){
					$redirect_uri = $this->curPageURL();
					$redirect_uri = str_replace("&code=".$code, "", $redirect_uri);
					$scope = 'snsapi_userinfo';
					$log->write ( "sub请求: ". $redirect_uri, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
					$this->oauth ( $redirect_uri, $scope );
				}
				$open_id = $userinfo["openid"];
				$log->write ( "sub微信回调", 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
				
			}
			
			$user_model = D ( "User" );
			$user = $user_model->field ( 'id' )->where ( "openid = '%s'", $open_id )->find ();
			
			if(!empty($user)){
				if($user["id"] == $msg["author_id"]){
					$is_author = true;
				}
			}
			if($is_author){
				if($msg["status"] == 0){
					if($op == 1){
						//echo "setting";
						$this->assign("open_id", $open_id);
						$this->assign("msg_id", $msg["id"]);
						$this->assign("msg_title", $msg["title"]);
						$this->assign("mode", $msg["mode"]);
						$this->assign("timeout", $msg["timeout"]);
						$this->assign("img_path", $msg["image_path"]);
						
						$signPackage = $this->getSignPackage();
						$this->assign("appId", $signPackage["appId"]);
						$this->assign("timestamp", $signPackage["timestamp"]);
						$this->assign("nonceStr", $signPackage["nonceStr"]);
						$this->assign("signature", $signPackage["signature"]);
						
						$this->display ( "setting" );
					}
					else{
						//echo "preview";
						$this->assign("open_id", $open_id);
						$this->assign("msg_id", $msg["id"]);
						$this->assign("timeout", $msg["timeout"]);
						$this->assign("img_path", $msg["image_path"]);
						$this->assign("img_width", $msg["image_width"]);
						$this->assign("img_height", $msg["image_height"]);
						$this->display ( "preview" );
					}
				}
				if($msg["status"] == 1){
					//echo "stats";
					$reader_model = M ( "msg_reader" );
					$readers = $reader_model->where( "msg_id = %d", $msg_id )->order("id desc")->select();
					$this->assign("readers", $readers);
					$this->display ( "stats" );
				}
			}
			else{
				$readed = false;
				if($msg["status"] == 1 && $msg["mode"] == 1){
					$readed = true;
				}
				if($msg["status"] == 1 && $msg["mode"] == 2){
					$reader_model = M ( "msg_reader" );
					$reader = $reader_model->where("reader_id = '%s' and msg_id = %d", array($open_id, $msg_id))->find();
					if(!empty($reader)){
						$readed = true;
					}
				}
				if($readed){
					//echo "destory";
					$this->display ( "destory" );
				}
				else{
					//echo "show";
					$this->assign("open_id", $open_id);
					$this->assign("nickname", $userinfo['nickname']);
					$this->assign("headimgurl", $userinfo['headimgurl']);
					$this->assign("msg_id", $msg["id"]);
					$this->assign("timeout", $msg["timeout"]);
					$this->assign("img_path", $msg["image_path"]);
					$this->assign("img_width", $msg["image_width"]);
					$this->assign("img_height", $msg["image_height"]);
					$this->display ( "show" );
				}
			}
		}
		else{
			echo "Oops! The article you want to seem gone!";
		}
	}
	
	public function read() {
		$is_author = false;
		if (! isset ( $_GET ['id'] )){
			echo "bad request";
		}
		if (! isset ( $_GET ['open_id'] )){
			echo "bad request";
		}
		$open_id = $_GET ['open_id'];
		$nickname = $_GET ['nickname'];
		$headimgurl = $_GET ['headimgurl'];
		$msg_id = $_GET ['id'];
		$msg_model = M ( "msg" );
		$msg = $msg_model->where ( "id = %d", $msg_id )->find ();
		if(!empty($msg) ){
			$user_model = D ( "User" );
			$user = $user_model->field ( 'id' )->where ( "openid = '%s'", $open_id )->find ();
			if(!empty($user)){
				if($user["id"] == $msg["author_id"]){
					$is_author = true;
				}
			}
			$result = array (
				'status' => 0 // 成功
				);
			header('Content-Type:application/json; charset=utf-8');
			if($is_author){
				if($msg["status"] == 1){
					$result['status'] = 1;// 失败
				}
			}
			else{
				if($msg["status"] == 0){
					$data = array(
							"status" => 1,
							"utime" => time()
						);
					$msg_model->where ( "id = %d", $msg_id )->save($data);
				}
				$reader_model = M("msg_reader");
				$reader = $reader_model->where ( "msg_id = %d and reader_id = '%s'" , $msg_id, $open_id )->find ();
				if(empty($reader)){
					$data = array(
							"msg_id" => $msg_id,
							"reader_id" => $open_id,
							"nickname" => $nickname,
							"headimgurl" => $headimgurl,
							"ctime" => time()
						);
					
					$reader_model->add($data);
					
					$author = $user_model->field ( 'openid' )->where ( "id = %d", $msg["author_id"] )->find ();
					$msg_mode = "只得一次(图片)";
					if($msg["mode"] == 2){
						$msg_mode = "每人一次(图片)";
					}
					$this->notify_author($author["openid"], $msg_id, $msg_mode, $msg["title"], $nickname);
				}
			}
			exit(json_encode($result));
		}
	}
	
	public function edit() {
		$is_author = false;
		if (! isset ( $_GET ['id'] )){
			echo "bad request";
		}
		if (! isset ( $_GET ['open_id'] )){
			echo "bad request";
		}
		$open_id = $_GET ['open_id'];
		$msg_id = $_GET ['id'];
		$msg_model = M ( "msg" );
		$msg = $msg_model->where ( "id = $msg_id" )->find ();
		if(!empty($msg) ){
			$user_model = D ( "User" );
			$user = $user_model->field ( 'id' )->where ( "openid = '%s'", $open_id )->find ();
			if(!empty($user)){
				if($user["id"] == $msg["author_id"]){
					$is_author = true;
				}
			}
			$result = array (
				'status' => 0 // 成功
				);
			header('Content-Type:application/json; charset=utf-8');
			if($is_author){
				if($msg["status"] == 1){
					$result['status'] = 1; // 失败
				}
				else{
					if (isset ( $_GET ['timeout'] )){
						$data = array (
								"timeout" => $_GET ['timeout']
							);
						$msg_model->where ( "id = %d", $msg_id )->save ($data);
					}
					if (isset ( $_GET ['mode'] )){
						$data = array (
								"mode" => $_GET ['mode']
							);
						$msg_model->where ( "id = %d", $msg_id )->save ($data);
					}
					if (isset ( $_GET ['title'] )){
						$data = array (
								"title" => $_GET ['title']
							);
						$msg_model->where ( "id = %d", $msg_id )->save ($data);
					}
				}
			}
			else{
				$result['status'] = 1; // 失败
			}
			exit(json_encode($result));
		}
	}
	
	function notify_author($open_id, $msg_id, $msg_mode, $msg_title, $reader){
		$url = "http://zhide.xiaoxianlink.com/index.php?g=weixin&m=msg&a=index&id=$msg_id";
		date_default_timezone_set('Asia/Chongqing');
		$now = date("Y\年m\月d\日 H:i:s");
		$remark = "信息标题：" . $msg_title ."\\n" . "信息编号：" . $msg_id . "\\n" . "该信息已在" . $reader . "查看后自动销毁";
		$data = array(
			'first' => array (
					'value' => urlencode ( "你发送的消息已被查看" ),
					'color' => "#000000" 
			),
			'keyword1' => array (
					'value' => urlencode ( $msg_mode ),
					'color' => '#000000' 
			),
			'keyword2' => array (
					'value' => urlencode ( $now ),
					'color' => '#000000' 
			),
			'remark' => array (
					'value' => urlencode ( $remark ),
					'color' => '#000000' 
			) 
		);
		$this->sendWeiXin($open_id, "nqdkzTfdUn81QHXXmhvZwxIbijcy22NCk-0328RgCRQ", $url, $data);
	}
	
}