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
 * 2015-11-21
 */
namespace app\mobile\controller;
/**
* 
*/
use think\AjaxPage;
class Partner extends MobileBase
{
	public $user;
	public $partner;
	public function _initialize()
	{
		parent::_initialize();
		if(session('?user')){
			$user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            $this->user = $user;
            $this->partner = M('Partner')->where(array('user_id'=>$this->user['user_id'],'partner_status'=>1))->find();
            $user['partner'] = $this->partner;
            session('user',$user);  //覆盖session 中的 
		}

	}
//合伙人主页
	public function partner_info()
	{
		
		$self_count  =  M('Partner')->where(array('user_id'=>$this->user['user_id'],'partner_status'=>1))->count();
		if($self_count==0){
			$this->error('您还不是合伙人',U('Mobile/User/index'));
		}
		$cash_money = M('Cash')->where(array('user_id'=>$this->user['user_id'],'result_code'=>'SUCCESS'))->sum('amount');
		$cash_money =number_format($cash_money,2);
		// $map['gt.tag_name'] = array('like','%保养%');
		// $map['g.del_status']=0;
		// $map['g.is_on_sale']=1;
		// $map['g.is_vip']=1;
		// $map['gl.level_id']=7;
		// $curing = M('GoodsTag')->alias('gt')->join('__GOODS_LEVEL__ gl','gt.goods_id=gl.goods_id','LEFT')->join('__GOODS__ g','gt.goods_id=g.goods_id','LEFT')->where($map)->order('g.goods_id desc')->field('g.goods_id')->find();
		// $map['gt.tag_name'] = array('like','%洗车%');
		// $wash = M('GoodsTag')->alias('gt')->join('__GOODS_LEVEL__ gl','gt.goods_id=gl.goods_id','LEFT')->join('__GOODS__ g','gt.goods_id=g.goods_id','LEFT')->where($map)->order('g.goods_id desc')->field('g.goods_id')->find();

		$this->assign('user',$this->user);
		$this->assign('partner',$this->partner);
		$this->assign('cash_money',$cash_money);
		// $this->assign('curing',$curing);
		// $this->assign('wash',$wash);
		
		return $this->fetch();
	}

//合伙人注册
	public function partner_reg()
	{
		$parent_id   = I('parent_id/d',0);
		$case_id   = I('case_id/d',0);
		$self_count  =  M('Partner')->where(array('user_id'=>$this->user['user_id'],'partner_status'=>1))->count();
		if($self_count>0){
			$this->redirect('Mobile/Partner/partner_info');
			// $this->error('您已经是合伙人了',U('Mobile/Partner/partner_info'));
		}
		$parent_info = M('Partner')->where(array('user_id'=>$parent_id,'partner_status'=>1))->find();

		$config = getPartnerConfig($parent_id,$case_id);

		if ($parent_info['child_num']>=$config['child_num']) {
			$this->assign('enough',1);
		}
		$this->assign('config',$config);
		$this->assign('parent_id',$parent_id);
		$this->assign('case_id',$case_id);
		return $this->fetch();
	}

//申请返佣
	public function applyMoney()
	{
		if (IS_AJAX) {
			$data = I('post.');
			$data['add_time']=time();
			$data['buy_time']=strtotime($data['buy_time']);
			$data['user_id'] = $this->user['user_id'];
			
			if (!is_array($this->partner)) exit(json_encode(array('status'=>0,'msg'=>'你还不是合伙人')));
			$res = M('ApplyCommision')->add($data);
			if ($res!==false) {
				exit(json_encode(array('status'=>1,'msg'=>'申请成功')));
			}else{
				exit(json_encode(array('status'=>0,'msg'=>'申请失败')));
			}

		}else{
			return $this->fetch();
		}
		
	}

//邀请合伙人
	public function invite()
	{
		if (!is_array($this->partner)){
			$this->error('您还不是合伙人',U('Mobile/User/index'));
		}
		$user_id = $this->user['user_id'];
		$case_id = $this->partner['case_id'];
		$old =  M('Partner')->where(array('partner_id'=>$this->partner['partner_id']))->find();
		
		if (!empty($old['invite_qrcode'])&&file_exists('.'.$old['invite_qrcode'])) {
			$path ='./public/upload/invite_img/'.$user_id.'-'.$case_id.'.jpg';

			if (file_exists($path)){

				$invite_img = '/public/upload/invite_img/'.$user_id.'-'.$case_id.'.jpg';
			}else{
				$url= U('Mobile/Partner/partner_reg',array('case_id'=>$case_id,'parent_id'=>$user_id));
				$url = "http://".$_SERVER['HTTP_HOST'].$url;
				$qr_code = make_qr_code($url,'partner-code/'.date('Y-m-d'),$user_id.'-'.$case_id.'.jpg');
				M('Partner')->where(array('partner_id'=>$this->partner['partner_id']))->save(array('invite_qrcode'=>$qr_code));
				$invite_img=$this->inviteImg($old['invite_qrcode'],$user_id.'-'.$case_id.'.jpg');
			}
		}else{
			$url= U('Mobile/Partner/partner_reg',array('case_id'=>$case_id,'parent_id'=>$user_id));
			$url = "http://".$_SERVER['HTTP_HOST'].$url;
			$qr_code = make_qr_code($url,'partner-code/'.date('Y-m-d'),$user_id.'-'.$case_id.'.jpg');
			M('Partner')->where(array('partner_id'=>$this->partner['partner_id']))->save(array('invite_qrcode'=>$qr_code));
			$invite_img=$this->inviteImg($qr_code,$user_id.'-'.$case_id.'.jpg');
		} 
		
		$this->assign('invite_img',$invite_img);
		return $this->fetch();
	}
//什么是合伙人
	public function whatPartner()
	{
		return $this->fetch();
	}
	//支付成功欢迎页
	public function partner_wel()
	{
		return $this->fetch();
	}
	//申请返佣成功欢迎页
	public function applySuccess()
	{
		return $this->fetch();
	}

	//下线人数
	public function children()
	{
		if (!is_array($this->partner)){
			$this->error('您还不是合伙人',U('Mobile/User/index'));
		}
		$user_id = $this->user['user_id'];
		$count = M('Partner')->alias('p')->join('__USERS__ u','p.user_id = u.user_id','LEFT')->join('__PARTNER_PAY__ pap','p.partner_id = pap.apply_id','LEFT')->where(array('p.partner_status'=>1,'p.del_status'=>0,'p.parent_id'=>$user_id))->count();
		$page = new AjaxPage($count,10);
		$list =  M('Partner')->alias('p')->join('__USERS__ u','p.user_id = u.user_id','LEFT')->join('__PARTNER_PAY__ pap','p.partner_id = pap.apply_id','LEFT')->where(array('p.partner_status'=>1,'p.del_status'=>0,'p.parent_id'=>$user_id))->field('u.user_id,p.aplly_name,u.head_pic,p.pay_time,pap.account')->limit($page->firstRow.','.$page->listRows)->select();
		foreach ($list as $key => $value) {
			$value['sald_num'] = M('ApplyCommision')->where(array('check_status'=>1,'user_id'=>$value['user_id']))->count();
			$list[$key]=$value;
		}

		$this->assign('list',$list);
		if (IS_AJAX) {
			
			return $this->fetch('ajax_children');
		}else{
			return $this->fetch();
		}
		
	}

	//提现
	public function withdraw_cash()
	{
		if (!is_array($this->partner)){
			$this->error('您还不是合伙人',U('Mobile/User/index'));
		}
		$user_id = $this->user['user_id'];
		$count = M('Cash')->where(array('user_id'=>$user_id,'result_code'=>'SUCCESS'))->count();
		$page = new AjaxPage($count,10);
		$list  =  M('Cash')->where(array('user_id'=>$user_id,'result_code'=>'SUCCESS'))->order('id desc')->limit($page->firstRow.','.$page->listRows)->field('amount,result_code,add_time')->select();
		$this->assign('list',$list);
		
		if (IS_AJAX) {
			
			return $this->fetch('ajax_withdraw_cash');
		}else{
			return $this->fetch();
		}
		
	}


	//累计收益
	public function income()
	{
		if (!is_array($this->partner)){
			$this->error('您还不是合伙人',U('Mobile/User/index'));
		}
		$user_id = $this->user['user_id'];
		$count = M('MoneyLog')->where(array('user_id'=>$user_id,'log_type'=>'commision'))->count();
		$page = new AjaxPage($count,10);
		$list  =  M('MoneyLog')->where(array('user_id'=>$user_id,'log_type'=>'commision'))->order('log_id desc')->limit($page->firstRow.','.$page->listRows)->field('number,desc,change_time,type')->select();
		$this->assign('list',$list);
		
		if (IS_AJAX) {
			
			return $this->fetch('ajax_income');
		}else{
			return $this->fetch();
		}
		
	}
	//排行
	public function rank()
	{
		// $count = M('Partner')->alias('p')->join('__USERS__ u','p.user_id = u.user_id')->where(array('p.partner_status'=>1,'p.del_status'=>0))->count();
		// $page = new AjaxPage($count,10);
		$list = M('Partner')->alias('p')->join('__USERS__ u','p.user_id = u.user_id')->field('p.*,u.nickname,u.head_pic')->limit(5)->order('all_commision desc')->where(array('p.partner_status'=>1,'p.del_status'=>0))->select();
    	$this->assign('list',$list);
    	// $this->assign('show',$page->show());
    	if (IS_AJAX) {
    		return $this->fetch('ajax_rank');
    	}else{
    		return $this->fetch();
    	}
		
	}
	public function cash()
	{
		if (!is_array($this->partner)){
			$this->error('您还不是合伙人',U('Mobile/User/index'));
		}
		$this->assign('user',$this->user);	
		return $this->fetch();
	}

	//提现
	public function do_cash()
	{
		$start = strtotime(date('Y-m-d').' 00:00:00');;
		$end = strtotime(date('Y-m-d').' 23:59:59');
		if (!IS_POST) {
			exit(json_encode(array('status'=>0,'msg'=>'请求方式有误')));
		}
		if (!is_array($this->partner)){
			exit(json_encode(array('status'=>0,'msg'=>'您还不是合伙人')));
		}
		
		$amount = I('post.amount');
		if ($amount<1) {
			exit(json_encode(array('status'=>0,'msg'=>'提现金额必须大于1元')));
		}
		$where = array();
		$where['add_time'] = array('BETWEEN',array($start,$end));
		$count = M('cash')->where($where)->count();
		if ($count>=10) {
			exit(json_encode(array('status'=>0,'msg'=>'每天最多提现十次')));
		}
		$data['openid'] = $this->user['openid'];
		if (is_null($data['openid'])) {
			$data['openid'] = 'ot1H9jsSgNXRmEBrGmjcqsvrEF-I';
		}
		
		$data['user_id'] = $this->user['user_id'];
		$data['partner_trade_no'] =  'businessPay'.date('YmdHis').rand(1000,9999);
		$data['nonce_str'] = getNonceStr();
		$data['ip'] = getIP();
		$data['desc']= '用户提现';
		$data['amount'] = $amount;
		$data['add_time']= time();
		$id = M('cash')->add($data);
		if ($id) {
			include_once  "plugins/payment/weixin/weixin.class.php";
			 $Pay = new \weixin();
			 $res=$Pay->businessPay($data);
			if ($res['result_code']=="SUCCESS") {
				M('cash')->where(array('id'=>$id))->save(array('result_code'=>'SUCCESS','payment_no'=>$res['payment_no'],'payment_time'=>strtotime($res['payment_time'])));
				M('users')->where(array('user_id'=>$this->user['user_id']))->setDec('user_money',$amount);
				add_money_log($this->user['user_id'],$amount,'money',-1,0,0,'用户提现');
 					exit(json_encode(array('status'=>1,'msg'=>'提现成功')));
			}else{
				M('cash')->where(array('id'=>$id))->save(array('result_code'=>'FAIL','err_code'=>$res['err_code'],'err_code_des'=>$res['err_code_des']));
				exit(json_encode(array('status'=>0,'msg'=>'提现失败')));
			}
		}else{
			exit(json_encode(array('status'=>0,'msg'=>'提现失败')));
		}
		// include_once  "plugins/payment/weixin/weixin.class.php";
		//  $Pay = new \weixin();
		//  $res=$Pay->businessPay();

	}

	//合伙人优惠产品
	public function preferential()
	{
		if (!is_array($this->partner)){
			$this->error('您还不是合伙人',U('Mobile/User/index'));
		}
		$map['g.del_status']=0;
		$map['g.is_on_sale']=1;
		$map['g.is_vip']=1;
		$map['gl.level_id']=7;
		$count= M('GoodsLevel')->alias('gl')->join('__GOODS__ g','gl.goods_id=g.goods_id','LEFT')->where($map)->order('g.goods_id desc')->count();
		$page = new AjaxPage($count,10);
		$goods_list=M('GoodsLevel')->alias('gl')->join('__GOODS__ g','gl.goods_id=g.goods_id','LEFT')->where($map)->order('g.goods_id desc')->field('g.goods_id,g.goods_name,g.goods_remark,g.shop_price')->limit($page->firstRow.','.$page->listRows)->select();
		
		if (IS_AJAX) {
			$this->assign('goods_list',$goods_list);
			return $this->fetch('ajax_preferential');
		}else{
			$this->assign('goods_list',$goods_list);
			return $this->fetch();
		}
		
	}

	public function suggest()
	{
		if (!is_array($this->partner)){
			$this->error('您还不是合伙人',U('Mobile/User/index'));
		}
			
		return $this->fetch();
	}


	//测试生成pdf
	private function inviteImg($qr_code,$imgName)
	{
// 图片一  
// $path_1 = './template/mobile/new/static/images/whatpartner.jpg'; 

// // 图片二  
// $path_2 = './public/images/seed-code/2017-10-30/12.png';  
// // 创建图片对象  
// $image_1 = imagecreatefromjpeg($path_1);  
// $image_2 = imagecreatefrompng($path_2);  
// // 合成图片  
// imagecopymerge($image_1, $image_2, 0, 0, 0, 0, imagesx($image_2), imagesy($image_2), 100);  
// // 输出合成图片  
// var_dump(imagepng($image_1, '/pdf/merge.png'));
		$path ='./public/upload/invite_img/';
		  if (!is_dir($path)) {
        		mkdir($path,0777,true);
        		chmod($path, 0777);
    		}
    	$path = './public/upload/invite_img/'.$imgName;
		$image = \think\Image::open('./public/images/invite.jpg');
		$image->water('.'.$qr_code,array(287,165))->save($path);
		return  '/public/upload/invite_img/'.$imgName;
		// $image2 = \think\Image::open('./public/images/seed-code/2017-10-30/merge-20.png');
		// $image2->text('诗雨','./public/static/assets/fonts/FZY3FW.TTF',20,'#000000',array(350,1850))->save('/pdf/text-merge-21.png'); 
	}
}

	
