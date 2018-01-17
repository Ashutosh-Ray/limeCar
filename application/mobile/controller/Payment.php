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
use think\Request;
class Payment extends MobileBase {
    
    public $payment; //  具体的支付类
    public $pay_code; //  具体的支付code
 
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();      
        // imshop 订单支付提交
        $pay_radio = $_REQUEST['pay_radio'];
        if(!empty($pay_radio)) 
        {                         
            $pay_radio = parse_url_param($pay_radio);
            $this->pay_code = $pay_radio['pay_code']; // 支付 code
        }
        else // 第三方 支付商返回
        {            
            //$_GET = I('get.');            
            //file_put_contents('./a.html',$_GET,FILE_APPEND);    
            $this->pay_code = I('get.pay_code');
            unset($_GET['pay_code']); // 用完之后删除, 以免进入签名判断里面去 导致错误
        }                        
        //获取通知的数据
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];    
        if(empty($this->pay_code)) $this->error('请选择支付方式');
            // exit('pay_code 不能为空');        
        // 导入具体的支付类文件                
        include_once  "plugins/payment/{$this->pay_code}/{$this->pay_code}.class.php"; // D:\wamp\www\svn_imshop\www\plugins\payment\alipay\alipayPayment.class.php                       
        $code = '\\'.$this->pay_code; // \alipay
        $this->payment = new $code();
    }
   
    /**
     * ThinkPHP [ WE CAN DO IT JUST THINK ] 提交支付方式
     */
    public function getCode(){     
        
            //C('TOKEN_ON',false); // 关闭 TOKEN_ON
            header("Content-type:text/html;charset=utf-8");            
            $order_id = I('order_id/d'); // 订单id
            // 修改订单的支付方式
            $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");                        
            M('order')->where("order_id", $order_id)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
            $order = M('order')->where("order_id", $order_id)->find();
            if($order['pay_status'] == 1){
              $this->error('此订单，已完成支付!');
            }
            //imshop 订单支付提交
            $pay_radio = $_REQUEST['pay_radio'];
            $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
           // 微信JS支付
           if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
               $code_str = $this->payment->getJSAPI($order);
               exit($code_str);
           }else{
             $code_str = $this->payment->get_code($order,$config_value);
           }
      
           // // 微信JS支付
           // if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
           //  $openid = $_SESSION['openid'];

           //     $result = shunfuPay($order,'0505',$openid);
            
           //     if ($result['result']=='SUCCESS') {
           //       // $result['data']['appId'] = 'wx2f1940c3c352e570';
           //        $result['data']['package'] = $result['data']['packageData'];
           //        $result['data']['paySign'] = $result['data']['sign'];
           //        unset($result['data']['packageData']);
           //        unset($result['data']['sign']);
           //        $code_str = json_encode($result['data']);
           //        // echo "$code_str";die();
           //        $code_str = $this->jsPay($order,$code_str);
           //        exit($code_str);
           //    }else{
           //      // var_dump($order);die();
           //      $this->error($result['msg']);
           //    }
           //     exit($code_str);
           // }elseif ($this->pay_code == 'weixin' && !$_SESSION['openid'] && !strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')) {
           //    $result = shunfuPay($order,'0503');
           //    if ($result['result']=='SUCCESS') {
           //        $code_str = $result['data']['qrCodeImg'];
           //    }else{
           //      $this->error($result['msg']);
           //    }
             
           // }
           // else{
          
           //  $result = shunfuPay($order,'0501');
           //  if ($result['result']=='SUCCESS') {
           //        $code_str = $result['data']['qrCodeImg'];
           //    }else{
           //      $this->error($result['msg']);
           //    }
           // }
            $this->assign('code_str', $code_str); 
            $this->assign('order_id', $order_id); 
            return $this->fetch('payment');  // 分跳转 和不 跳转
    }

    public function jsPay($order,$jsApiParameters)
    {
      if(stripos($order['order_sn'],'recharge') !== false){
            $go_url = U('Mobile/User/points',array('type'=>'recharge'));
            $back_url = U('Mobile/User/recharge',array('order_id'=>$order['order_id']));
        }elseif (stripos($order['order_sn'],'partnerP') !== false) {
            $go_url = U('Mobile/Partner/partner_wel');
            $back_url = U('Mobile/Partner/partner_reg',array('parent_id'=>$order['parent_id']));
        }else{
            $go_url = U('Mobile/User/order_detail',array('id'=>$order['order_id']));
            $goods_id=M('OrderGoods')->where(array('order_id'=>$order['order_id']))->find();
            // $back_url = U('Mobile/Cart/cart4',array('order_id'=>$order['order_id']));
            $back_url = U('Mobile/Goods/goodsInfo',array('id'=>$goods_id['goods_id']));
        }

         $html = <<<EOF
    <script type="text/javascript">
    //调用微信JS api 支付
    function jsApiCall()
    {
        WeixinJSBridge.invoke(
            'getBrandWCPayRequest',$jsApiParameters,
            function(res){
                //WeixinJSBridge.log(res.err_msg);
                 if(res.err_msg == "get_brand_wcpay_request:ok") {
                    location.href='$go_url';
                 }else{
                    alert(res.err_code+res.err_desc+res.err_msg);
                    location.href='$back_url';
                 }
            }
        );
    }

    function callpay()
    {
        if (typeof WeixinJSBridge == "undefined"){
            if( document.addEventListener ){
                document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
            }else if (document.attachEvent){
                document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
            }
        }else{
            jsApiCall();
        }
    }
    callpay();
    </script>
EOF;
        
    return $html;
    }

    public function getPay(){
      //手机端在线充值
        //C('TOKEN_ON',false); // 关闭 TOKEN_ON 
        header("Content-type:text/html;charset=utf-8");
        $order_id = I('order_id/d'); //订单id
        $user = session('user');
        $data['account'] = I('account');
        if($order_id>0){
          M('recharge')->where(array('order_id'=>$order_id,'user_id'=>$user['user_id']))->save($data);
        }else{
          $data['user_id'] = $user['user_id'];
          $data['nickname'] = $user['nickname'];
          $data['order_sn'] = 'recharge'.get_rand_str(10,0,1);
          $data['ctime'] = time();
          $order_id = M('recharge')->add($data);
        }
        if($order_id){
          $order = M('recharge')->where("order_id", $order_id)->find();
          if(is_array($order) && $order['pay_status']==0){
            $order['order_amount'] = $order['account'];
            $pay_radio = $_REQUEST['pay_radio'];
            $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
            $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
            M('recharge')->where("order_id", $order_id)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
            //微信JS支付
            // if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            //   $code_str = $this->payment->getJSAPI($order);
            //   exit($code_str);
            // }else{
            //   $code_str = $this->payment->get_code($order,$config_value);
            // }

           // 微信JS支付
           if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $openid = $_SESSION['openid'];

              $order['total_amount']=$order['account'];
         
               $result = shunfuPay($order,'0505',$openid);
   
               if ($result['result']=='SUCCESS') {
                 // $result['data']['appId'] = 'wx2f1940c3c352e570';
                  $result['data']['package'] = $result['data']['packageData'];
                  $result['data']['paySign'] = $result['data']['sign'];
                  unset($result['data']['packageData']);
                  unset($result['data']['sign']);
                  $code_str = json_encode($result['data']);
                  // echo "$code_str";die();
                  $code_str = $this->jsPay($order,$code_str);
                  exit($code_str);
              }else{
                // var_dump($result);die();
                $this->error($result['msg']);
              }
               exit($code_str);
           }elseif ($this->pay_code == 'weixin' && !$_SESSION['openid'] && !strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')) {
              $result = shunfuPay($order,'0503');
              if ($result['result']=='SUCCESS') {
                  $code_str = $result['data']['qrCodeImg'];
              }else{
                $this->error($result['msg']);
              }
             
           }else{
          
            $result = shunfuPay($order,'0501');
            if ($result['result']=='SUCCESS') {
                  $code_str = $result['data']['qrCodeImg'];
              }else{
                $this->error($result['msg']);
              }
           }
           $this->assign('code_str', $code_str); 
            $this->assign('order_id', $order_id); 
            return $this->fetch('payment');
          }else{
            $this->error('此充值订单，已完成支付!');
          }
        }else{
          $this->error('提交失败,参数有误!');
        }
        $this->assign('code_str', $code_str); 
        $this->assign('order_id', $order_id); 
      return $this->fetch('recharge'); //分跳转 和不 跳转
    }

    //用户申请成为合伙人
    public function getApplyPay(){
      //手机端在线充值
        //C('TOKEN_ON',false); // 关闭 TOKEN_ON 
        header("Content-type:text/html;charset=utf-8");
          $data = I('post.');
          // var_dump($data);die();
          //验证码
          $map['mobile'] = $data['apply_phone'];
          $map['session_id'] = session_id();
          $map['status']=1;
          $old = M('sms_log')->where($map)->order('id desc')->find();
          $time = time()-$old['add_time'];
          if ($old&&$time>3600){
            $this->error('验证码已超时');
          }

          if($old['code']!=$data['code']){
            $this->error('验证码有误');
          }


          $user = session('user');
          $data['user_id'] = $user['user_id'];
          if (!empty($data['case_id'])&&is_numeric($data['case_id'])) {
            $config = M('PartnerConfig')->where(array('id'=>$data['case_id'],'use_status'=>1))->find();
          }
          if (!is_array($config)) {
             $config = getPartnerConfig($data['parent_id']);//上级合伙人id
          }
          
          $data['case_id'] = $config['id'];//方案id
          $data['in_cid'] = $config['id'];//进来时方案id
          $data['add_time'] = time();
          $apply_id = M('Partner')->add($data);
          unset($data['aplly_name']);
          unset($data['apply_phone']);
          $data['order_sn'] = 'partnerP'.get_rand_str(10,0,1);
          $data['account']  =$config['initial_fee'];
          $data['apply_id']  =$apply_id;
          $order_id = M('PartnerPay')->add($data);
  
        if($order_id){
          $order = M('PartnerPay')->where("order_id", $order_id)->find();
          if(is_array($order) && $order['pay_status']==0){
            $order['order_amount'] = $order['account'];
            $pay_radio = $_REQUEST['pay_radio'];
            $config_value = parse_url_param($pay_radio); // 类似于 pay_code=alipay&bank_code=CCB-DEBIT 参数
            $payment_arr = M('Plugin')->where("`type` = 'payment'")->getField("code,name");
            M('PartnerPay')->where("order_id", $order_id)->save(array('pay_code'=>$this->pay_code,'pay_name'=>$payment_arr[$this->pay_code]));
                $order['total_amount']=$order['account'];
                //旧的支付
                if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
                 
                   $code_str = $this->payment->getJSAPI($order);
                   exit($code_str);
                 }else{
                   $code_str = $this->payment->get_code($order,$config_value);
                 }
           // 微信JS支付
           // if($this->pay_code == 'weixin' && $_SESSION['openid'] && strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
           //  $openid = $_SESSION['openid'];
               // $result = shunfuPay($order,'0505',$openid);
   
               // if ($result['result']=='SUCCESS') {
               //   // $result['data']['appId'] = 'wx2f1940c3c352e570';
               //    $result['data']['package'] = $result['data']['packageData'];
               //    $result['data']['paySign'] = $result['data']['sign'];
               //    unset($result['data']['packageData']);
               //    unset($result['data']['sign']);
               //    $code_str = json_encode($result['data']);
               //    // echo "$code_str";die();
               //    $code_str = $this->jsPay($order,$code_str);
               //    exit($code_str);
              // }else{
              //   // var_dump($result);die();
              //   $this->error($result['msg']);
              // }
           //     exit($code_str);
           // }elseif ($this->pay_code == 'weixin' && !$_SESSION['openid'] && !strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')) {
           //    $result = shunfuPay($order,'0503');
           //    if ($result['result']=='SUCCESS') {
           //        $code_str = $result['data']['qrCodeImg'];
           //    }else{
           //      $this->error($result['msg']);
           //    }
             
           // }else{
          
           //  $result = shunfuPay($order,'0501');
           //  if ($result['result']=='SUCCESS') {
           //        $code_str = $result['data']['qrCodeImg'];
           //    }else{
           //      $this->error($result['msg']);
           //    }
           // }
           $this->assign('code_str', $code_str); 
            $this->assign('order_id', $order_id); 
            return $this->fetch('payment');
          }else{
            $this->error('此充值订单，已完成支付!');
          }
        }else{
          $this->error('提交失败,参数有误!');
        }
        $this->assign('code_str', $code_str); 
        $this->assign('order_id', $order_id); 
      return $this->fetch('recharge'); //分跳转 和不 跳转
    }
        // 服务器点对点 // http://www.imshop.com/index.php/Home/Payment/notifyUrl        
        public function notifyUrl(){            
            $this->payment->response();            
            exit();
        }

        // 页面跳转 // http://www.imshop.com/index.php/Home/Payment/returnUrl        
        public function returnUrl(){
            $result = $this->payment->respond2(); // $result['order_sn'] = '201512241425288593';  
            if(stripos($result['order_sn'],'recharge') !== false)
            {
              $order = M('recharge')->where("order_sn", $result['order_sn'])->find();
              $this->assign('order', $order);
              if($result['status'] == 1)
                return $this->fetch('recharge_success');
              else
                return $this->fetch('recharge_error');
              exit();
            }          
            $order = M('order')->where("order_sn", $result['order_sn'])->find();
            $this->assign('order', $order);
            if($result['status'] == 1)
                return $this->fetch('success');
            else
                return $this->fetch('error');
        }                
}
