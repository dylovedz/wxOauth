<?php
header('Cache-Control:no-cache,must-revalidate');   
header('Pragma:no-cache');   
header("Expires:0");
ini_set('date.timezone','Asia/Shanghai');
error_reporting(E_ERROR);
class OAuthWeixin
{
	//绑定支付的APPID（必须配置）
	protected $appid;
	//公众帐号secert（仅JSAPI支付的时候需要配置）
	protected $appsecret;
	
	public $scope;
	
	public function __construct(){
		$this->appid = 'wxa0a08ab82ec58f6c';
		$this->appsecret = 'b48609d8300e14bfd9fc98125a13aefc';
		
		$this->scope = isset($_GET['scope']) ? $_GET['scope'] : 'snsapi_base';
		$openid =  $this->GetOpenid();
		if(isset($_GET['callBack']) && !empty($openid)){
			$callBack = urldecode($_GET['callBack']);
			if (strpos ( $callBack, '?' ) === false) {
				$callBack .= '?';
			} else {
				$callBack .= '&';
			}
			$callBack = $callBack . 'openid=' . $openid;
			Header("Location: $callBack");
		}
	}
	
	public function GetOpenid()
	{
		//通过code获得openid
		if (!isset($_GET['code'])){
			//触发微信返回code码
			$baseUrl = urlencode($_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
			$url = $this->__CreateOauthUrlForCode($baseUrl);
			Header("Location: $url");
			exit();
		} else {
			//获取code码，以获取openid
		    $code = $_GET['code'];
			$openid = $this->GetOpenidFromMp($code);
			if($this->scope == 'snsapi_userinfo'){ //获取用户信息
				$infourl = 'https://api.weixin.qq.com/sns/userinfo?access_token='.$openid['access_token'].'&openid='.$openid ['openid'].'&lang=zh_CN';
				$userInfo = $this->GetUserInfoFromMp($infourl);
				if(isset($userInfo['openid'])){
					$openid = $userInfo;
				}
			}else{
			    return $openid['openid'];
			}
			return json_encode($openid);
		}
	}
	
	public function GetUserInfoFromMp($url)
	{
		//初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 500);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        
        //运行curl，结果以json形式返回
        $res = curl_exec($ch);
        curl_close($ch);
		//取出openid
		$openid = json_decode($res,true);
		return $openid;
	}
	
	public function GetOpenidFromMp($code)
	{
		$url = $this->__CreateOauthUrlForOpenid($code);
		//初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 500);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        
        //运行curl，结果以json形式返回
        $res = curl_exec($ch);
        curl_close($ch);
		//取出openid
		$openid = json_decode($res,true);
		return $openid;
	}
	
	private function ToUrlParams($urlObj)
	{
		$buff = "";
		foreach ($urlObj as $k => $v)
		{
			if($k != "sign"){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}
	
	private function __CreateOauthUrlForCode($redirectUrl)
	{
		$urlObj["appid"] = $this->appid;
		$urlObj["redirect_uri"] = "$redirectUrl";
		$urlObj["response_type"] = "code";
		$urlObj["scope"] = $this->scope;
		$urlObj["state"] = "bjcz"."#wechat_redirect";
		$bizString = $this->ToUrlParams($urlObj);
		return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
	}
	
	private function __CreateOauthUrlForOpenid($code)
	{
		$urlObj["appid"] = $this->appid;
		$urlObj["secret"] = $this->appsecret;
		$urlObj["code"] = $code;
		$urlObj["grant_type"] = "authorization_code";
		$bizString = $this->ToUrlParams($urlObj);
		return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
	}
}
new OAuthWeixin();