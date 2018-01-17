<?php
/**
 * ThinkPHP [ WE CAN DO IT JUST THINK ]
 +----------------------------------------------------------------------
 * * Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
 * 
 +----------------------------------------------------------------------
 * 
 * 
 +----------------------------------------------------------------------
 * $Author: Alince 2015-08-10 $
 */
namespace app\mobile\controller;
use app\home\logic\UsersLogic;
use think\Controller;
use think\Session;
use think\Db;

class MobileBase extends Controller {
    public $session_id;
    public $weixin_config;
    public $cateTrre = array();
    
    /*
     * 初始化操作
     */
    public function _initialize() {

        Session::start();
        header("Cache-control: private");  // history.back返回后输入框值丢失问题 参考文章 http://www.imshop.com/article_id_1465.html  http://blog.csdn.net/qinchaoguang123456/article/details/29852881
        $this->session_id = session_id(); // 当前的 session_id
        define('SESSION_ID',$this->session_id); //将当前的session_id保存为常量，供其它方法调用
        // 判断当前用户是否手机 
                  
        if(isMobile())
            cookie('is_mobile','1',3600); 
        else 
            cookie('is_mobile','0',3600);
         $this->weixin_config = M('wx_user')->find(); //获取微信配置
        //微信浏览器
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') && empty($_SESSION['openid'])){
           
            $this->assign('wechat_config', $this->weixin_config); 
            if(is_array($this->weixin_config) && $this->weixin_config['wait_access'] == 1){
                $wxuser = $this->GetOpenid(); //授权获取openid以及微信用户信息
                session('subscribe', $wxuser['subscribe']);// 当前这个用户是否关注了微信公众号
                //微信自动登录                             
                $logic = new UsersLogic();
                $wxuser['type']=1;
                $data = $logic->thirdLogin($wxuser);                                
               
                if($data['status'] == 1){
                    $data['result']['partner'] = M('Partner')->where(array('user_id'=>$data['result']['user_id'],'partner_status'=>1))->find();
                    session('user',$data['result']);
                    $this->goodsInfoScan($_SESSION['user']['user_id']);
                    setcookie('user_id',$data['result']['user_id'],null,'/');
                    setcookie('is_distribut',$data['result']['is_distribut'],null,'/');
                    setcookie('uname',$data['result']['nickname'],null,'/');                    
                    // 登录后将购物车的商品的 user_id 改为当前登录的id
                    M('cart')->where("session_id", $this->session_id)->save(array('user_id'=>$data['result']['user_id']));
                    $cartLogic = new \app\home\logic\CartLogic();
                    $cartLogic->login_cart_handle($this->session_id,$data['result']['user_id']);  //用户登录后 需要对购物车 一些操作
                    
                }
            }
           
            // 微信Jssdk 操作类 用分享朋友圈 JS            
            $jssdk = new \app\mobile\logic\Jssdk($this->weixin_config['appid'], $this->weixin_config['appsecret']);
            $signPackage = $jssdk->GetSignPackage();            
            $this->assign('signPackage', $signPackage);
        }

        if (strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') ) {
             $jssdk = new \app\mobile\logic\Jssdk($this->weixin_config['appid'], $this->weixin_config['appsecret']);
            $signPackage = $jssdk->GetSignPackage();            
            $this->assign('signPackage', $signPackage);
            // var_dump($this->weixin_config['appid']);die();
        }
         $this->difference(); 
        $this->public_assign();
    }
    private function goodsInfoScan($uid)
     {
          $str =  strtolower(MODULE_NAME).strtolower(CONTROLLER_NAME).strtolower(ACTION_NAME);
          if ($str=='mobilegoodsgoodsinfo') {
             $goods_id = I("get.id/d");
             $shop_id = I("get.shop_id/d");
             $goods = M('Goods')->where("goods_id", $goods_id)->find();
             if(empty($goods)){
                 $this->error('此商品不存在或者已下架');
             }
             //判断是不是分销的商品
             $share_uid = I('get.share_uid');
             $share = M('Wxshare')->where(array('user_id'=>$share_uid,'goods_id'=>$goods_id,'shop_id'=>$shop_id))->find();
             $scan['share_id'] = $share['id'];
             $scan['uid'] = $user_id;
             $scanData = M('scan_share')->where($scan)->find();
             if($scanData){
                $num = $scanData['scan_num']+1;
                M('scan_share')->where(array('id'=>$scanData['id']))->save(array('scan_num'=>$num));
           
          }else{
               $scan['add_time'] = time();
               if(!is_null($scan['uid'])){
                M('scan_share')->add($scan);
               }
             
          }
      }
     }
    private function difference(){
          function have($admin_info){
              
               session('shop_id',$admin_info['admin_id']);
               session('shop_type',$admin_info['type']);
               session('shop_name',$admin_info['shop_name']);
               session('shop_logo',$admin_info['shop_logo']);
               session('admin_name',$admin_info['user_name']);
              
               
          }
          function nosession(){
   
            session('shop_id',null);
            session('shop_type',null);
            session('shop_name',null);
            session('admin_name',null);
             session('shop_logo',null);
          }
        //区别是不是分商城  
           $str =  strtolower(MODULE_NAME).strtolower(CONTROLLER_NAME).strtolower(ACTION_NAME);
          
           if ($str=='mobileindexindex') {
               $name = I('get.shop');

               if($name) {
                 $admin_info = M('admin')->where(array('user_name'=>$name,'check_status'=>1))->find();
                if(!is_array($admin_info)&&is_numeric($name)){
                    $admin_info = M('admin')->where(array('admin_id'=>$name,'check_status'=>1))->find();
                }
              
                if(is_array($admin_info)){
                
                   have($admin_info);
                  }else{
                   nosession();
                  } 
               }else{
                   nosession();
               }
             }
             // var_dump($_SESSION);die();
             // if ($str=='mobilegoodsgoodsinfo'||$str=='homegoodsgoodsinfo') {

             //     $goods_id = I('get.id');

             //     if($goods_id) {
             //    $goods_info = M('Goods')->where(array('goods_id'=>$goods_id))->find();
             //     $admin_info = M('admin')->where(array('admin_id'=>$goods_info['shop_id'],'check_status'=>1))->find();

             //    if(is_array($admin_info)){
             //       have($admin_info);
                   
             //      }else{
             //        nosession();
             //      } 
             //   }else{
             //       nosession();
             //   }
             // }
             // if ($str=='mobilegoodsgoodslist'||$str=='homegoodsgoodslist') {

             //     $goods_id = I('get.id');

             //     if($goods_id) {
             //    $goods_info = M('GoodsCategory')->where(array('id'=>$goods_id))->find();
             //     $admin_info = M('admin')->where(array('admin_id'=>$goods_info['shop_id'],'check_status'=>1))->find();

             //    if(is_array($admin_info)){
             //       have($admin_info);
                   
             //      }else{
             //        nosession();
             //      } 
             //   }else{
             //       nosession();
             //   }
             // }
         if (session('shop_id')) {

           $this->assign('shop_id',session('shop_id'));
           $this->assign('shop_name',session('shop_name'));
         }else{
           
            $imshop_config = array();
            $tp_config = M('config')->cache(true,TPSHOP_CACHE_TIME)->select();
            foreach($tp_config as $k => $v)
             {
                if($v['name'] == 'hot_keywords'){
                   $imshop_config['hot_keywords'] = explode('|', $v['value']);
                }           
                $imshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
             } 
           
            $this->assign('shop_id',0);
           $this->assign('shop_name', $imshop_config['shop_info_store_title']);
         } 
        $this->assign('shop_type',session('shop_type'));
        $this->assign('admin_name',session('admin_name'));

    }

    private function  distinction_session($admin_info)
    {
       
    }
    /**
     * 保存公告变量到 smarty中 比如 导航 
     */   
    public function public_assign()
    {
        
       $imshop_config = array();
       $tp_config = M('config')->cache(true,TPSHOP_CACHE_TIME)->select();       
       foreach($tp_config as $k => $v)
       {
       	  if($v['name'] == 'hot_keywords'){
       	  	 $imshop_config['hot_keywords'] = explode('|', $v['value']);
       	  }       	  
          $imshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
       }                        
       
       $goods_category_tree = get_goods_category_tree();
       $this->cateTrre = $goods_category_tree;
       $this->assign('goods_category_tree', $goods_category_tree);                     
       $brand_list = M('brand')->cache(true,TPSHOP_CACHE_TIME)->field('id,cat_id,logo,is_hot')->where("cat_id>0")->select();              
       $this->assign('brand_list', $brand_list);
       $this->assign('imshop_config', $imshop_config);
    }      

    // 网页授权登录获取 OpendId
    public function GetOpenid()
    {
        if($_SESSION['openid'])
            return $_SESSION['openid'];
        //通过code获得openid
        if (!isset($_GET['code'])){
            //触发微信返回code码
            //$baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']);
            $baseUrl = urlencode($this->get_url());
            $url = $this->__CreateOauthUrlForCode($baseUrl); // 获取 code地址
            Header("Location: $url"); // 跳转到微信授权页面 需要用户确认登录的页面
            exit();
        } else {
            //上面获取到code后这里跳转回来
            $code = $_GET['code'];
            $data = $this->getOpenidFromMp($code);//获取网页授权access_token和用户openid
            $data2 = $this->GetUserInfo($data['access_token'],$data['openid']);//获取微信用户信息
            $data['nickname'] = empty($data2['nickname']) ? '微信用户' : trim($data2['nickname']);
            $data['sex'] = $data2['sex'];
            $data['head_pic'] = $data2['headimgurl']; 
            $data['subscribe'] = $data2['subscribe'];                         
            $_SESSION['openid'] = $data['openid'];

            $data['oauth'] = 'weixin';
            if(isset($data2['unionid'])){
            	$data['unionid'] = $data2['unionid'];
            }
            return $data;
        }
    }

    /**
     * 获取当前的url 地址
     * @return type
     */
    private function get_url() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
    }    
    
    /**
     *
     * 通过code从工作平台获取openid机器access_token
     * @param string $code 微信跳转回来带上的code
     *
     * @return openid
     */
    public function GetOpenidFromMp($code)
    {
        //通过code获取网页授权access_token 和 openid 。网页授权access_token是一次性的，而基础支持的access_token的是有时间限制的：7200s。
    	//1、微信网页授权是通过OAuth2.0机制实现的，在用户授权给公众号后，公众号可以获取到一个网页授权特有的接口调用凭证（网页授权access_token），通过网页授权access_token可以进行授权后接口调用，如获取用户基本信息；
    	//2、其他微信接口，需要通过基础支持中的“获取access_token”接口来获取到的普通access_token调用。
        $url = $this->__CreateOauthUrlForOpenid($code);       
        $ch = curl_init();//初始化curl        
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);         
        $res = curl_exec($ch);//运行curl，结果以jason形式返回            
        $data = json_decode($res,true);         
        curl_close($ch);
        return $data;
    }
    
    
        /**
     *
     * 通过access_token openid 从工作平台获取UserInfo      
     * @return openid
     */
    public function GetUserInfo($access_token,$openid)
    {         
        // 获取用户 信息
        $url = $this->__CreateOauthUrlForUserinfo($access_token,$openid);
        $ch = curl_init();//初始化curl        
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);//设置超时
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);         
        $res = curl_exec($ch);//运行curl，结果以jason形式返回            
        $data = json_decode($res,true);            
        curl_close($ch);
        //获取用户是否关注了微信公众号， 再来判断是否提示用户 关注
        // if(!isset($data['unionid'])){
        	$access_token2 = $this->get_access_token();//获取基础支持的access_token
        	$url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token2&openid=$openid";
        	$subscribe_info = httpRequest($url,'GET');
        	$subscribe_info = json_decode($subscribe_info,true);
        	$data['subscribe'] = $subscribe_info['subscribe'];
        // }                
        return $data;
    }
    
    
    public function get_access_token(){
        //判断是否过了缓存期
        $expire_time = $this->weixin_config['web_expires'];
        if($expire_time > time()){
           return $this->weixin_config['web_access_token'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->weixin_config[appid]}&secret={$this->weixin_config[appsecret]}";
        $return = httpRequest($url,'GET');
        $return = json_decode($return,1);
        $web_expires = time() + 7140; // 提前60秒过期
        M('wx_user')->where(array('id'=>$this->weixin_config['id']))->save(array('web_access_token'=>$return['access_token'],'web_expires'=>$web_expires));
        return $return['access_token'];
    }    

    /**
     *
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->weixin_config['appid'];
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
//        $urlObj["scope"] = "snsapi_base";
        $urlObj["scope"] = "snsapi_userinfo";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }

    /**
     *
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     *
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->weixin_config['appid'];
        $urlObj["secret"] = $this->weixin_config['appsecret'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }

    /**
     *
     * 构造获取拉取用户信息(需scope为 snsapi_userinfo)的url地址     
     * @return 请求的url
     */
    private function __CreateOauthUrlForUserinfo($access_token,$openid)
    {
        $urlObj["access_token"] = $access_token;
        $urlObj["openid"] = $openid;
        $urlObj["lang"] = 'zh_CN';        
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/userinfo?".$bizString;                    
    }    
    
    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return 返回已经拼接好的字符串
     */
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
    public function ajaxReturn($data){
        exit(json_encode($data));
    }

}