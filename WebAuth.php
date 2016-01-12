<?php
class WebAuth{
	// 获取 code 的 url
	const URL_GET_CODE='https://open.weixin.qq.com/connect/qrconnect?';

	// 通过 code 换取 access_token
	const URL_GET_ACCESS_TOKEN='https://api.weixin.qq.com/sns/oauth2/access_token?';

	// 刷新  access_token
	const URL_REFRESH_ACCESS_TOKEN='https://api.weixin.qq.com/sns/oauth2/refresh_token?';

	// 检测 access_token 是否有效
	const URL_CHECK_ACCESS_TOKEN='https://api.weixin.qq.com/sns/auth?';

	// 获取用户信息
	const URL_GET_USER_INFO='https://api.weixin.qq.com/sns/userinfo?';

	// refresh_token 的生命周期
	// const REFRESH_TOKEN_MAX_LIFE=2592000; // 3600*24*30 -> 30 天

	public $appId; // appId
	public $appSecret; // app Secret

	// access_token 的缓存目录
	public $accessTokenCacheDir='./';
	

	private static $_weixin=array();

	public static function init($appId=null,$appSecret=null,$config=array(),$class=__CLASS__){

		// 
		if(isset(self::$_weixin[$class])) return self::config(self::$_weixin[$class],$config);

		if(null===$appId || trim($appId)=='') throw new Exception('请配置 appid');

		if(null===$appSecret || trim($appSecret)=='') throw new Exception('请配置 appsecret');


		$obj=new $class;

		$config['appId']=$appId;
		$config['appSecret']=$appSecret;

		return self::$_weixin[$class]=self::config($obj,$config);
	}

	protected function __construct(){}

	/**
	 * 获取 code
	 * @param  [type] $redirectUri [description]
	 * @param  [type] $state       [description]
	 * @return [type]              [description]
	 */
	public function getCodeUrl($redirectUri=null,$state='state'){

		return self::URL_GET_CODE.http_build_query(array(
			'appid'=>$this->appId,
			'redirect_uri'=>null===$redirectUri?self::getCurrentUrl():$redirectUri,
			'response_type'=>'code',
			'scope'=>'snsapi_login',
			'state'=>$state,
		));
	}

	/**
	 * 通过 code 获取 access token 和 openid
	 * @param  [type] $code [description]
	 * @return [type]       [description]
	 */
	public function getAccessTokenAndOpenId($code=null){
		
		$returns=self::send(self::URL_GET_ACCESS_TOKEN.http_build_query(array(
			'appid'=>$this->appId,
			'secret'=>$this->appSecret,
			'code'=>$code,
			'grant_type'=>'authorization_code',
		)));

		return isset($returns['openid'])?array($returns['access_token'],$returns['openid']):null;
	}

	/**
	 * 通过 code 获取用户信息
	 * @param  [type] $code [description]
	 * @return [type]       [description]
	 */
	public function getUserInfoByCode($code=null){

		if(null===$code && (!isset($_GET['code']) || ''==($code=(string)$_GET['code']) )) return null;


		return ( $returns=$this->getAccessTokenAndOpenId($code) ) ? $this->getUserInfo($returns[0],$returns[1]) : null ;

	}

	/**
	 * 获取用户个人信息
	 * @param  [type] $accessToken [description]
	 * @param  [type] $openId      [description]
	 * @return [type]              [description]
	 */
	public function getUserInfo($accessToken,$openId){
		$returns=self::send(self::URL_GET_USER_INFO.'access_token='.$accessToken.'&openid='.$openId);

		return isset($returns['openid'])?$returns:null;
	}

	/**
	 * 配置
	 * @param  [type] $obj    [description]
	 * @param  array  $config [description]
	 * @return [type]         [description]
	 */
	protected static function config($obj,array $config){
		foreach($config as $attr=>$val){
			$obj->$attr=$val;
		}
		return $obj;
	}

	/**
	 * 返回当前的 url
	 * @return [type] [description]
	 */
	public static function getCurrentUrl(){
		
		return ('HTTP'==rtrim(substr($_SERVER['SERVER_PROTOCOL'],0,5),'/')?'http://':'https://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	}

	/**
	 * 请求
	 * @param  [type] $url    [description]
	 * @param  array  $params [description]
	 * @return [type]         [description]
	 */
	public static function send($url,$params=array(),$encode=true){
		$curl=curl_init();

		curl_setopt($curl,CURLOPT_TIMEOUT,0);
		curl_setopt($curl,CURLOPT_URL,$url);
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,false);
		curl_setopt($curl,CURLOPT_HEADER,false);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		
		// 如果是 post
		if($params){
			curl_setopt($curl,CURLOPT_POST,true);
			curl_setopt($curl,CURLOPT_POSTFIELDS,$encode?json_encode($params):$params);
		}
		
		$returnRes=curl_exec($curl);
		curl_close($curl);

		return json_decode($returnRes,true);
	}




	// --------------------------------------------------

	/**
	 * 获取 access_token 的缓存文件
	 * @return [type] [description]
	 */
	public function getAccessTokenCacheFile(){
		return $this->accessTokenCacheDir.'/cache_user_access_token.php';
	}

	/**
	 * 检测 access_token 是否有效
	 * @param  [type] $accessToken [description]
	 * @param  [type] $openId      [description]
	 * @return [type]              [description]
	 */
	public function checkAccessTokenIsActive($accessToken,$openId){

	}
}