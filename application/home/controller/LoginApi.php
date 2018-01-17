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
 * 微信交互类
 */
namespace app\home\controller;
use app\home\logic\UsersLogic;
use app\home\logic\CartLogic;
use think\Request;
class LoginApi extends Base {
    public $config;
    public $oauth;
    public $class_obj;

    public function __construct(){
        parent::__construct();
//        unset($_GET['oauth']);   // 删除掉 以免被进入签名
//        unset($_REQUEST['oauth']);// 删除掉 以免被进入签名
        
        $this->oauth = I('get.oauth');
        //获取配置
        $data = M('Plugin')->where("code",$this->oauth)->where("type","login")->find();
        $this->config = unserialize($data['config_value']); // 配置反序列化
        if(!$this->oauth)
            $this->error('非法操作',U('User/login'));
        include_once  "plugins/login/{$this->oauth}/{$this->oauth}.class.php";
        $class = '\\'.$this->oauth; //
        $this->class_obj = new $class($this->config); //实例化对应的登陆插件
    }

    public function login(){
        if(!$this->oauth)
            $this->error('非法操作',U('User/login'));
        include_once  "plugins/login/{$this->oauth}/{$this->oauth}.class.php";
        $this->class_obj->login();
    }
    
    public function callback(){
          $data = $this->class_obj->respon();

        $logic = new UsersLogic();
        if(session('?user')){
            $res = $logic->oauth_bind($data);//已有账号绑定第三方账号
            if($res['status'] == 1){
                $this->success('绑定成功',U('Index/index'));
            }else{
                $this->error('绑定失败',U('User/index'));
            }
        }
        $data = $logic->thirdLogin($data);
        if($data['status'] != 1)
            $this->error($data['msg']);
        session('user',$data['result']);
        setcookie('user_id',$data['result']['user_id'],null,'/');
        setcookie('is_distribut',$data['result']['is_distribut'],null,'/');
        $nickname = empty($data['result']['nickname']) ? '第三方用户' : $data['result']['nickname'];
        setcookie('uname',urlencode($nickname),null,'/');
        setcookie('cn',0,time()-3600,'/');
        // 登录后将购物车的商品的 user_id 改为当前登录的id            
        $cartLogic = new CartLogic();
        $cartLogic->login_cart_handle($this->session_id,$data['result']['user_id']);  //用户登录后 需要对购物车 一些操作
        $jump_url = $_GET['url'];
        
        if (!empty($jump_url)) {
            $jump_url = str_replace("-","/",$jump_url);
            $jump_url = "http://".$_SERVER['HTTP_HOST'].'/'.$jump_url;
            Header("Location: $jump_url"); die();
           // $this->success('登陆成功',U($jump_url));
        }
        if(isMobile())
            $this->success('登陆成功',U('Mobile/User/index'));
        else
            $this->success('登陆成功',U('Index/index'));
    }
}