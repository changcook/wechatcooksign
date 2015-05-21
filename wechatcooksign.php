<?php

class Wechatcooksign {
	private $app_id;
	private $app_secret;
	private $lib_path;

	public function __construct($appid, $appsecret,$libpath='./'){
		$this->app_id = $appid;
		$this->app_secret = $appsecret;
		$this->lib_path = dirname(__FILE__) . '/'	;
		// $this->nonce_str = NONCE_STR;

		$this->logit($this->app_id);
	}

	private function httpGet($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 500);
		curl_setopt($curl, CURLOPT_URL, $url);

		$res = curl_exec($curl);
		curl_close($curl);

		return $res;
	}

	
	//签名算法 
	public function get_sign(){
		$jsapi_ticket = $this->get_jsapi_ticket();
		$url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$timestamp = time();
		$nonceStr = $this->get_rand_str();
		 // 这里参数的顺序要按照 key 值 ASCII 码升序排序
		$string = "jsapi_ticket=$jsapi_ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

		$signature = sha1($string);

		$signPackage = array(
			"appId"     => $this->app_id,
			"nonceStr"  => $nonceStr,
			"timestamp" => $timestamp,
			"url"       => $url,
			"signature" => $signature,
			"rawString" => $string,
			'jsapi_ticket'=>$jsapi_ticket,
			'string'	=> $string,

		);
		return $signPackage; 
	}

	public function get_access_token(){
		//获取本地存储数据。如果已经过期，重新读取
		$this->logit('get_access_token');

		$store_file = $this->lib_path .'access_token.json';
		$data = json_decode( file_get_contents($store_file) );
		if( $data->expire_time < time() ){
			$this->logit('---------begin----get_access_token----------');
			$url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->app_id.'&secret='.$this->app_secret;
			$this->logit($url);


			// $res = json_decode($this->httpGet($url));
			$res = json_decode(file_get_contents($url), true);
			$this->logit('---------resssss----get_access_token----------');
			$this->logit($res['access_token']);
			$this->logit('---------getit----get_access_token----------');

			$access_token = $res['access_token'];
			$this->logit('---------'.$access_token.'----get_access_token----------');
			if($access_token){
				$data->expire_time = time()+ intval($res['expires_in']);
				$data->access_token = $access_token;
				file_put_contents($store_file, json_encode($data));
				$this->logit('---------finish write----get_access_token----------');
			}else{
				//未成功获取到
			}
	
		}else{
			$this->logit('---------no----get_access_token----------');
			$access_token = $data->access_token;
		}
		return $access_token ;	
		
	}

	public function get_jsapi_ticket(){
		$access_token = $this->get_access_token();

		$store_file = $this->lib_path.'jsapi_ticket.json';
		$data = json_decode( file_get_contents($store_file) );
		if( $data->expire_time < time() ){
			$url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$access_token.'&type=jsapi';
			$res = json_decode( $this->httpGet($url) );
			$jsapi_ticket = $res->ticket;
			if($jsapi_ticket){
				$data->ticket = $jsapi_ticket;
				$data->expire_time = time() + intval($res->expires_in);

				file_put_contents($store_file, json_encode($data));
			}
		}else{
			$jsapi_ticket = $data->ticket;
		}

		return $jsapi_ticket;		

	}

	public function get_rand_str($length=16){
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
	    $str = "";
	    for ($i = 0; $i < $length; $i++) {
	      $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
	    }
	    return $str;
	}


	function logit($str){
		$path = $this->libpath.date('mdH').'.log';
		$str .= "\t". date('H:i:s')."\n";
		file_put_contents($path, $str, FILE_APPEND);
	}
}

