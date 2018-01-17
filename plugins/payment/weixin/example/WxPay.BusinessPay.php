<?php

require_once dirname(dirname(__FILE__))."/lib/WxPay.Config.php";
require_once dirname(dirname(__FILE__))."/lib/WxPay.Exception.php";
/**
* 
*/
class BusinessPay 
{
	// public $key="edfhsb4543rfer1e56tews1fw6e54123";
	// public $key=WxPayConfig::$key;
	public $values = array();
	public function setValues($order=array())
	{
		$this->values = $order;
		$this->values['mch_appid'] = WxPayConfig::$appid;;
		$this->values['mchid']  = WxPayConfig::$mchid ;
		// $this->values['mch_appid'] = "wx2f1940c3c352e570";
		// $this->values['mchid']  = "1241369502";
		// $this->values['nonce_str']  =$this->getNonceStr();
		// $this->values['partner_trade_no']  = 'BusinessPay'.get_rand_str(10,0,1);
		// $this->values['openid']  = 'ot1H9jsSgNXRmEBrGmjcqsvrEF-I';
		$this->values['check_name']  ='NO_CHECK';
		// $this->values['amount'] = 100;
		// $this->values['desc']  ='用户提现';
		// $this->values['spbill_create_ip']  =getIP();
		$sign = $this->MakeSign();
		$this->values['sign']  =$sign;
		// print_r($this->values);die();
		
	}

	// public function setSign()
	// {
	// 	$sign = $this->MakeSign();
	// 	$this->values['sign']  =$sign;
	// }

	public static function getNonceStr($length = 32) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		} 
		return $str;
	}

	/**
	 * 生成签名
	 * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
	 */
	public function MakeSign()
	{
		//签名步骤一：按字典序排序参数
		ksort($this->values);
		$string = $this->ToUrlParams();
		//签名步骤二：在string后加入KEY
		$string = $string . "&key=".WxPayConfig::$key;
		//签名步骤三：MD5加密
		$string = md5($string);
		//签名步骤四：所有字符转为大写
		$result = strtoupper($string);
		return $result;
	}


	/**
	 * 输出xml字符
	 * @throws WxPayException
	**/
	public function ToXml()
	{
		if(!is_array($this->values) 
			|| count($this->values) <= 0)
		{
    		throw new WxPayException("数组数据异常！");
    	}
    	
    	$xml = "<xml>";
    	foreach ($this->values as $key=>$val)
    	{
    		if (is_numeric($val)){
    			$xml.="<".$key.">".$val."</".$key.">";
    		}else{
    			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
    		}
        }
        $xml.="</xml>";

        return $xml; 
	}
	
    /**
     * 将xml转为array
     * @param string $xml
     * @throws WxPayException
     */
	public function FromXml($xml)
	{	
		if(!$xml){
			throw new WxPayException("xml数据异常！");
		}
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
		return $this->values;
	}
	
	/**
	 * 格式化参数格式化成url参数
	 */
	public function ToUrlParams()
	{
		$buff = "";
		foreach ($this->values as $k => $v)
		{
			if($k != "sign" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}


	public	function curl_post_ssl($aHeader=array())
	{
		$url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
		$vars = $this->ToXml();

		$ch = curl_init();
		//超时时间
		curl_setopt($ch,CURLOPT_TIMEOUT,30);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		//这里设置代理，如果有的话
		//curl_setopt($ch,CURLOPT_PROXY, '10.206.30.98');
		//curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
		
		//以下两种方式需选择一种
		
		//第一种方法，cert 与 key 分别属于两个.pem文件
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/plugins/payment/weixin/cert/apiclient_cert.pem');
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLKEY,getcwd().'/plugins/payment/weixin/cert/apiclient_key.pem');
		curl_setopt($ch, CURLOPT_CAINFO, getcwd().'/plugins/payment/weixin/cert/rootca.pem');
		//第二种方式，两个文件合成一个.pem文件
		// curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/all.pem');
	 
		if( count($aHeader) >= 1 ){
			curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
		}
	 
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
		$data = curl_exec($ch);
		
		if($data){
			curl_close($ch);
			$data = $this->FromXml($data);
			return $data;
		}
		else { 
			$error = curl_errno($ch);
			echo "call faild, errorCode:$error\n"; 
			curl_close($ch);
			return false;
		}
	}
	
}
