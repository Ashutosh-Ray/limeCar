<?php
/**
 * ThinkPHP [ WE CAN DO IT JUST THINK ]
 +----------------------------------------------------------------------
 * Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
 * 
 +----------------------------------------------------------------------
 * 
 * 
 +----------------------------------------------------------------------
 * Date: 2015-12-11
 */
namespace app\mobile\controller;
use think\AjaxPage;
use think\Page;
use think\Db;

class Coupon extends MobileBase {
	public function _initialize()
    {
        parent::_initialize();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            $user['partner'] = M('Partner')->where(array('user_id'=>$user['user_id'],'partner_status'=>1))->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息
        }
         if (!$this->user_id ) {
            header("location:" . U('Mobile/User/login'));
            exit;
        }

    }
    //领取优惠卷
    public function drawcoupon()
    {
    	$code = I('couponid');
    	$where = array();
    	$where['del_status']=0;
    	$where['is_use']=1;
    	$where['code'] =$code;
        $where['use_end_time'] =array('gt',time());
    	
    	$coupon = M('Coupon')->where($where)->find();
    	if (!is_array($coupon)) {
    		 $this->showMsg('优惠券已失效'); 
    		// exit();
    	}
    	if($coupon['createnum']<=$coupon['send_num']) exit(json_encode(array('status'=>0,'msg'=>'优惠券被领完啦！')));
    	$count = M('CouponList')->where(array('uid'=>$this->user_id,'cid'=>$coupon['id']))->count();

    	if ($count>0) {
    		$this->showMsg('您已经领取过了');  
    	}
    	$this->assign('coupon',$code);
    	return $this->fetch();

       
    }


    public function do_draw()
    {
    	if (IS_AJAX) 
    	{
    		$data = $_POST;
    		if (empty($data['code'])) exit(json_encode(array('status'=>0,'msg'=>'验证码不能为空')));
    		if (empty($data['phone'])) exit(json_encode(array('status'=>0,'msg'=>'手机号不能为空')));
    			$map = array();
				$map['mobile'] = $data['phone'];
				$map['session_id'] = session_id();
				$map['status']=1;
        		$old = M('sms_log')->where($map)->order('id desc')->find();
        		  		$time = time()-$old['add_time'];
        		if ($old&&$time>3600)  exit(json_encode(array('status'=>0,'msg'=>'验证码已超时')));
        		if ($old['code']!=$data['code']) exit(json_encode(array('status'=>0,'msg'=>'验证码有误')));

        		$where = array();
    			$where['del_status']=0;
    			$where['is_use']=1;
    			$where['code'] =$data['coupon'];
                $where['use_end_time'] =array('gt',time());
        		$coupon = M('Coupon')->where($where)->field('id,name,type,money,use_end_time,is_appoint,code,goods_name,goods_id,createnum,send_num')->find();
    			if (!is_array($coupon)) exit(json_encode(array('status'=>0,'msg'=>'优惠券已失效')));

    			$count = M('CouponList')->where(array('uid'=>$this->user_id,'cid'=>$coupon['id']))->count();
    			if ($count>0) exit(json_encode(array('status'=>0,'msg'=>'您已经领取过了')));
    			if($coupon['createnum']<=$coupon['send_num']) exit(json_encode(array('status'=>0,'msg'=>'优惠券被领完啦！')));
    			$add = $coupon;
    			unset($add['id']);
    			$add['send_time'] = time();
    			$add['uid'] = $this->user_id;
    			$add['cid'] = $coupon['id'];
    			$res = M('CouponList')->add($add);
    			if ($res) {
    				 M('Coupon')->where(array('id'=>$coupon['id']))->setInc('send_num');
    				 $ctags = M('CouponTag')->where(array('cid'=>$coupon['id']))->field('tag_id')->select(); 
    				 $cshops = M('CouponShop')->where(array('cid'=>$coupon['id']))->field('shop_id')->select(); 
    				 if (is_array($ctags)) {
    				 	foreach ($ctags as $key => $tag) {
    				 		$tag['clid'] = $res;
    				 		M('UcouponTag')->add($tag);
    				 	}
    				 }
    				 if (is_array($cshops)) {
    				 	foreach ($cshops as $key => $shop) {
    				 		$shop['clid'] = $res;
    				 		M('UcouponShop')->add($shop);
    				 	}
    				 }
    				exit(json_encode(array('status'=>1,'msg'=>'领取成功')));	 
    			}else{
    				exit(json_encode(array('status'=>0,'msg'=>'领取失败')));
    			}

    	}else{
    		exit(json_encode(array('status'=>0,'msg'=>'请求错误')));
    	}
    }


    //用户删除自己的优惠券
    public function del_coupon()
    {
        if (IS_POST) {
           $ids = $_POST['ids'];
           $where = array();
           $where['uid'] = $this->user_id;
           if (is_array($ids)) {
              $where['id'] = array('in',$ids);
           }
            $res = M('CouponList')->where($where)->save(array('user_del'=>1));
            if ($res) {
                exit(json_encode(1));
            }
        }
    }


    



	private function showMsg($msg)
    {
    	$this->assign('msg',$msg);
    	
    	echo $this->fetch('showMsg');
    	die();

    }

 
}