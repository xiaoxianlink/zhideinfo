<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2014 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace Weixin\Controller;

//use Common\Controller\HomeBaseController;
use Weixin\Controller\ApiController;
use Think\Log;


class IndexController extends ApiController {

	public function index() {
		if (! isset ( $_GET ['echostr'] )) {
			$this->responseMsg ();
		} else {
			$this->valid ();
		}
	}
	// 验证签名
	public function valid() {
		$echoStr = $_GET ["echostr"];
		$signature = $_GET ["signature"];
		$timestamp = $_GET ["timestamp"];
		$nonce = $_GET ["nonce"];
		$token = TOKEN;
		$tmpArr = array (
				$token,
				$timestamp,
				$nonce 
		);
		sort ( $tmpArr, SORT_STRING );
		$tmpStr = implode ( $tmpArr );
		$tmpStr = sha1 ( $tmpStr );
		if ($tmpStr == $signature) {
			echo $echoStr;
			exit ();
		}
	}
	
	// 响应消息
	public function responseMsg() {
		$postStr = $GLOBALS ["HTTP_RAW_POST_DATA"];
		if (! empty ( $postStr )) {
			$log = new Log ();
			// $this->logger ( "R \r\n" . $postStr );
			$postObj = simplexml_load_string ( $postStr, 'SimpleXMLElement', LIBXML_NOCDATA );
			$RX_TYPE = trim ( $postObj->MsgType );
			
			if (($postObj->MsgType == "event") && ($postObj->Event == "subscribe" || $postObj->Event == "unsubscribe")) {
				// 过滤关注和取消关注事件
			} else {
			}
			
			// 消息类型分离
			switch ($RX_TYPE) {
				case "event" :
					$result = $this->receiveEvent ( $postObj );
					break;
				case "text" :
					/* if (strstr ( $postObj->Content, "第三方" )) {
						$result = $this->relayPart3 ( "http://www.fangbei.org/test.php" . '?' . $_SERVER ['QUERY_STRING'], $postStr );
					} else {
						$result = $this->receiveText ( $postObj );
					} */
					$result = $this->receiveText ( $postObj );
					break;
				case "image" :
					$log->write ( "receive image: ". $postObj->MediaId . " from user: " . $postObj->FromUserName, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
					$result = $this->receiveImage ( $postObj );
					//$result = $this->transmitText ( $postObj, "Got it!" );
					break;
				case "location" :
					// $result = $this->receiveLocation ( $postObj );
					break;
				case "voice" :
					// $result = $this->receiveVoice ( $postObj );
					break;
				case "video" :
					// $result = $this->receiveVideo ( $postObj );
					break;
				case "link" :
					// $result = $this->receiveLink ( $postObj );
					break;
				default :
					// $result = "unknown msg type: " . $RX_TYPE;
					break;
			}
			// $this->logger ( "T \r\n" . $result );
			echo $result;
		} else {
			echo "";
			exit ();
		}
	}
	
	// 接收事件消息
	private function receiveEvent($object) {
		$content = "";
		switch ($object->Event) {
			case "subscribe" :
				$content = "只得信息，让微信消息安全传递。发个图片试一下，让你发现更多惊喜。";
				$this->register ( $object->FromUserName );
				// $content .= (! empty ( $object->EventKey )) ? ("\n来自二维码场景 " . str_replace ( "qrscene_", "", $object->EventKey )) : "";
				break;
			case "unsubscribe" :
				$content = "取消关注";
				$this->logout ( $object->FromUserName );
				break;
			case "CLICK" :
				/*
				switch ($object->EventKey) {
					case "scanning" : // 违章扫描
						$content = $this->scanning ( $object->FromUserName );
						if ($content == '1') {
							exit ();
						} else if ($content == '201') {
							$content = "请" . scan_time . "秒后重新查询";
						}
						break;
					default :
						$content = "点击菜单：" . $object->EventKey;
						break;
				}
				*/
				break;
			
			case "VIEW" :
				$content = "跳转链接 " . $object->EventKey;
				break;
			case "SCAN" :
				$content = "扫描场景 " . $object->EventKey;
				$log = new Log ();
				$log->write ( $object->EventKey, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
				break;
			case "LOCATION" :
				//$content = $this->insert_city ( $object );
				break;
			case "scancode_waitmsg" :
				if ($object->ScanCodeInfo->ScanType == "qrcode") {
					$content = "扫码带提示：类型 二维码 结果：" . $object->ScanCodeInfo->ScanResult;
				} else if ($object->ScanCodeInfo->ScanType == "barcode") {
					$codeinfo = explode ( ",", strval ( $object->ScanCodeInfo->ScanResult ) );
					$codeValue = $codeinfo [1];
					$content = "扫码带提示：类型 条形码 结果：" . $codeValue;
				} else {
					$content = "扫码带提示：类型 " . $object->ScanCodeInfo->ScanType . " 结果：" . $object->ScanCodeInfo->ScanResult;
				}
				break;
			case "scancode_push" :
				$content = "扫码推事件";
				break;
			case "pic_sysphoto" :
				$content = "系统拍照";
				break;
			case "pic_weixin" :
				$content = "相册发图：数量 " . $object->SendPicsInfo->Count;
				break;
			case "pic_photo_or_album" :
				$content = "拍照或者相册：数量 " . $object->SendPicsInfo->Count;
				break;
			case "location_select" :
				$content = "发送位置：标签 " . $object->SendLocationInfo->Label;
				break;
			
			default :
				$content = "receive a new event: " . $object->Event;
				break;
		}
		
		if (is_array ( $content )) {
			if (isset ( $content [0] ['PicUrl'] )) {
				$result = $this->transmitNews ( $object, $content );
			} else if (isset ( $content ['MusicUrl'] )) {
				$result = $this->transmitMusic ( $object, $content );
			}
		} else {
			$result = $this->transmitText ( $object, $content );
		}
		return $result;
	}
	
	// 接收文本消息
	private function receiveText($object) {
		$keyword = trim ( $object->Content );
		// 自动回复模式
		if (strstr ( $keyword, "拜年" )) {
			$content = "林仪明@福州给你拜年，祝您身体健康，阖家幸福，事业发达，万事如意";
		} else {
			$content = "仅支持图片消息，试试上传个图片吧";
		}
		
		if (is_array ( $content )) {
			if (isset ( $content [0] )) {
				$result = $this->transmitNews ( $object, $content );
			} else if (isset ( $content ['MusicUrl'] )) {
				$result = $this->transmitMusic ( $object, $content );
			}
		} else {
			$result = $this->transmitText ( $object, $content );
		}
		return $result;
	}
	
	// 接收图片消息
	private function receiveImage($object) {
		$imgPath = $this->downloadImage($object->MediaId);
		$openid = $object->FromUserName;
		$user_model = D ( "User" );
		$data = array ();
		$data ['openid'] = ( string ) $openid;
		$user = $user_model->field ( 'id' )->where ( $data )->find ();
		$msgId = $this->generateMsg($user["id"], $imgPath);
		if($msgId == false){
			$result = $this->transmitText ( $object, "仅支持JPEG/PNG等常见图片格式，请重新选择一张图片吧" );
		}
		else{
			$content = array ();
			$content[0] = array (
						"Title" => "只得消息已经创建成功",
						"Description" => "信息编号#". $msgId,
						"PicUrl" => "http://zhide.xiaoxianlink.com/statics/zhide/images/msg_edit.jpg",
						"Url" => "http://zhide.xiaoxianlink.com/index.php?g=weixin&m=msg&a=index&id=$msgId&op=edit" 
					);
			$result = $this->transmitNews ( $object, $content );
		}
		return $result;
	}
	
	// 接收位置消息
	private function receiveLocation($object) {
		$content = "你发送的是位置，经度为：" . $object->Location_Y . "；纬度为：" . $object->Location_X . "；缩放级别为：" . $object->Scale . "；位置为：" . $object->Label;
		$result = $this->transmitText ( $object, $content );
		return $result;
	}
	
	// 接收语音消息
	private function receiveVoice($object) {
		if (isset ( $object->Recognition ) && ! empty ( $object->Recognition )) {
			$content = "你刚才说的是：" . $object->Recognition;
			$result = $this->transmitText ( $object, $content );
		} else {
			$content = array (
					"MediaId" => $object->MediaId 
			);
			$result = $this->transmitVoice ( $object, $content );
		}
		return $result;
	}
	
	// 接收视频消息
	private function receiveVideo($object) {
		$content = array (
				"MediaId" => $object->MediaId,
				"ThumbMediaId" => $object->ThumbMediaId,
				"Title" => "",
				"Description" => "" 
		);
		$result = $this->transmitVideo ( $object, $content );
		return $result;
	}
	
	// 接收链接消息
	private function receiveLink($object) {
		$content = "你发送的是链接，标题为：" . $object->Title . "；内容为：" . $object->Description . "；链接地址为：" . $object->Url;
		$result = $this->transmitText ( $object, $content );
		return $result;
	}
	
	// 回复文本消息
	private function transmitText($object, $content) {
		if (! isset ( $content ) || empty ( $content )) {
			return "";
		}
		
		$xmlTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[text]]></MsgType>
					<Content><![CDATA[%s]]></Content>
				</xml>";
		$result = sprintf ( $xmlTpl, $object->FromUserName, $object->ToUserName, time (), $content );
		
		return $result;
	}
	
	// 回复图文消息
	private function transmitNews($object, $newsArray) {
		if (! is_array ( $newsArray )) {
			return "";
		}
		$itemTpl = "        <item>
            <Title><![CDATA[%s]]></Title>
            <Description><![CDATA[%s]]></Description>
            <PicUrl><![CDATA[%s]]></PicUrl>
            <Url><![CDATA[%s]]></Url>
        </item>
";
		$item_str = "";
		foreach ( $newsArray as $item ) {
			$item_str .= sprintf ( $itemTpl, $item ['Title'], $item ['Description'], $item ['PicUrl'], $item ['Url'] );
		}
		$xmlTpl = "<xml>
		<ToUserName><![CDATA[%s]]></ToUserName>
		<FromUserName><![CDATA[%s]]></FromUserName>
		<CreateTime>%s</CreateTime>
		<MsgType><![CDATA[news]]></MsgType>
		<ArticleCount>%s</ArticleCount>
		<Articles>
		$item_str    </Articles>
		</xml>";
		
		$result = sprintf ( $xmlTpl, $object->FromUserName, $object->ToUserName, time (), count ( $newsArray ) );
		return $result;
	}
	
	// 回复音乐消息
	private function transmitMusic($object, $musicArray) {
		if (! is_array ( $musicArray )) {
			return "";
		}
		$itemTpl = "<Music>
						<Title><![CDATA[%s]]></Title>
						<Description><![CDATA[%s]]></Description>
						<MusicUrl><![CDATA[%s]]></MusicUrl>
						<HQMusicUrl><![CDATA[%s]]></HQMusicUrl>
					</Music>";
		
		$item_str = sprintf ( $itemTpl, $musicArray ['Title'], $musicArray ['Description'], $musicArray ['MusicUrl'], $musicArray ['HQMusicUrl'] );
		
		$xmlTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[music]]></MsgType>
					$item_str
					</xml>";
		
		$result = sprintf ( $xmlTpl, $object->FromUserName, $object->ToUserName, time () );
		return $result;
	}
	
	// 回复图片消息
	private function transmitImage($object, $imageArray) {
		$itemTpl = "<Image>
						<MediaId><![CDATA[%s]]></MediaId>
					</Image>";
		
		$item_str = sprintf ( $itemTpl, $imageArray ['MediaId'] );
		
		$xmlTpl = "<xml>
				<ToUserName><![CDATA[%s]]></ToUserName>
				<FromUserName><![CDATA[%s]]></FromUserName>
				<CreateTime>%s</CreateTime>
				<MsgType><![CDATA[image]]></MsgType>
				$item_str
				</xml>";
		$result = sprintf ( $xmlTpl, $object->FromUserName, $object->ToUserName, time () );
		return $result;
	}
	
	// 回复语音消息
	private function transmitVoice($object, $voiceArray) {
		$itemTpl = "<Voice>
					<MediaId><![CDATA[%s]]></MediaId>
					</Voice>";
		
		$item_str = sprintf ( $itemTpl, $voiceArray ['MediaId'] );
		$xmlTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[voice]]></MsgType>
					$item_str
					</xml>";
		
		$result = sprintf ( $xmlTpl, $object->FromUserName, $object->ToUserName, time () );
		return $result;
	}
	
	// 回复视频消息
	private function transmitVideo($object, $videoArray) {
		$itemTpl = "<Video>
					<MediaId><![CDATA[%s]]></MediaId>
					<ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
					<Title><![CDATA[%s]]></Title>
					<Description><![CDATA[%s]]></Description>
					</Video>";
		
		$item_str = sprintf ( $itemTpl, $videoArray ['MediaId'], $videoArray ['ThumbMediaId'], $videoArray ['Title'], $videoArray ['Description'] );
		
		$xmlTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[video]]></MsgType>
					$item_str
					</xml>";
		
		$result = sprintf ( $xmlTpl, $object->FromUserName, $object->ToUserName, time () );
		return $result;
	}
	
	// 回复多客服消息
	private function transmitService($object) {
		$xmlTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[transfer_customer_service]]></MsgType>
					</xml>";
		$result = sprintf ( $xmlTpl, $object->FromUserName, $object->ToUserName, time () );
		return $result;
	}
	
	// 回复第三方接口消息
	private function relayPart3($url, $rawData) {
		$headers = array (
				"Content-Type: text/xml; charset=utf-8" 
		);
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $rawData );
		$output = curl_exec ( $ch );
		curl_close ( $ch );
		return $output;
	}
	
	// 字节转Emoji表情
	function bytes_to_emoji($cp) {
		if ($cp > 0x10000) { // 4 bytes
			return chr ( 0xF0 | (($cp & 0x1C0000) >> 18) ) . chr ( 0x80 | (($cp & 0x3F000) >> 12) ) . chr ( 0x80 | (($cp & 0xFC0) >> 6) ) . chr ( 0x80 | ($cp & 0x3F) );
		} else if ($cp > 0x800) { // 3 bytes
			return chr ( 0xE0 | (($cp & 0xF000) >> 12) ) . chr ( 0x80 | (($cp & 0xFC0) >> 6) ) . chr ( 0x80 | ($cp & 0x3F) );
		} else if ($cp > 0x80) { // 2 bytes
			return chr ( 0xC0 | (($cp & 0x7C0) >> 6) ) . chr ( 0x80 | ($cp & 0x3F) );
		} else { // 1 byte
			return chr ( $cp );
		}
	}
	
	// 日志记录
	/*
	 * private function logger($log_content) { if (isset ( $_SERVER ['HTTP_APPNAME'] )) { // SAE sae_set_display_errors ( false ); sae_debug ( $log_content ); sae_set_display_errors ( true ); } else if ($_SERVER ['REMOTE_ADDR'] != "127.0.0.1") { // LOCAL $max_size = 1000000; $log_filename = "log.xml"; if (file_exists ( $log_filename ) and (abs ( filesize ( $log_filename ) ) > $max_size)) { unlink ( $log_filename ); } file_put_contents ( $log_filename, date ( 'Y-m-d H:i:s' ) . " " . $log_content . "\r\n", FILE_APPEND ); } }
	 */
	
	/**
	 * 以下数据库操作 *
	 */
	// 关注
	function register($openid) {
		$user_model = D ( "User" );
		$data = array ();
		$data ['openid'] = ( string ) $openid;
		$user = $user_model->field ( 'id' )->where ( $data )->find ();
		
		if (! empty ( $user )) {
			$data1 ['is_att'] = 0;
			$user_model->where ( $data )->save ( $data1 );
		} else {
			$user_info = $this->get_user_info ( $openid );
			$data ['group_id'] = $user_info ['groupid'];
			$data ['unionid'] = ( string ) $user_info ['unionid'];
			$data ['username'] = $user_info ['nickname'];
			$data ['nickname'] = $user_info ['nickname'];
			$data ['is_att'] = 0;
			$data ['create_time'] = time ();
			$data ['channel'] = 0;
			$user_model->add ( $data );
		}
	}
	// 取消关注
	function logout($openid) {
		$user_model = M ( "User" );
		$data = array ();
		$data ['openid'] = ( string ) $openid;
		$user = $user_model->field ( 'id' )->where ( $data )->find ();
		$log = new Log ();
		$log->write ( $data ['open_id'], 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
		if (! empty ( $user )) {
			$data1 ['is_att'] = 1;
			$user_model->where ( $data )->save ( $data1 );
		}
	}
	// 保存用户现在的地理位置
	function insert_city($object) {
		$open_id = $object->FromUserName;
		$lat = $object->Latitude;
		$lng = $object->Longitude;
		$url = "http://api.map.baidu.com/ag/coord/convert?from=2&to=4&x=$lng&y=$lat";
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$output = curl_exec ( $ch );
		curl_close ( $ch );
		$jsoninfo = json_decode ( $output, true );
		$lat = base64_decode ( $jsoninfo ['y'] );
		$lng = base64_decode ( $jsoninfo ['x'] );
		
		$url = "http://api.map.baidu.com/geocoder?location=$lat,$lng&output=json&key=" . bdkey;
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$output = curl_exec ( $ch );
		curl_close ( $ch );
		$jsoninfo = json_decode ( $output, true );
		$city = $jsoninfo ['result'] ['addressComponent'] ['city'];
		$user_model = M ( "User" );
		$data = array (
				"city" => substr ( $city, 0, - 1 ) 
		);
		$user_model->where ( "openid='$open_id'" )->save ( $data );
	}
	
	public function fixOrientation($filename) {
        $image = imagecreatefromjpeg($filename);
        $exif = exif_read_data($filename);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }
        }
        imagejpeg($image, $filename);
    }
	
	function downloadImage($MediaId){
		$log = new Log();
		$accessToken = $this->get_access_token();
		$url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=$accessToken&media_id=$MediaId";
		$log->write ( "wx image url: ". $url, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt	( $ch, CURLOPT_HEADER, 0);
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_BINARYTRANSFER, 1 );
		$output = curl_exec ( $ch );
		curl_close ( $ch );
		//$log->write ( "wx image result: ". serialize($output), 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
		$savedir = dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/upload/Weixin/';
		$prefix = date ( 'y_m_d' ) . '/';
		mkdir($savedir . $prefix);
		$saveto = $savedir. $prefix. $MediaId . '.jpg';
		$log->write ( "wx image local: ". $saveto, 'DEBUG', '', dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/Logs/Weixin/' . date ( 'y_m_d' ) . '.log' );
		if(file_exists($saveto)){
			unlink($saveto);
		}
		$fp = fopen($saveto, 'x');
		fwrite($fp, $output);
		//fflush($fp);
		fclose($fp);
		$this->fixOrientation($saveto);
		return $prefix. $MediaId . '.jpg';
	}
	
	function generateMsg($userId, $imgPath){
		$savedir = dirname ( $_SERVER ['SCRIPT_FILENAME'] ) . '/upload/Weixin/';
		$size = getimagesize($savedir . $imgPath);
		if($size == false){
			return false;
		}
		$msg_model = M("msg");
		$data = array(
				"title" => "这是一个只得消息",
				"image_path" => $imgPath,
				"image_width" => $size[0],
				"image_height" => $size[1],
				"mode" => 1,
				"timeout" => 10,
				"status" => 0,
				"author_id" => $userId,
				"ctime" => time(),
				"utime" => time()
			);
		$msg_id = $msg_model->add($data);
		return $msg_id;
	}
	
}
