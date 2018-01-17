<?php
namespace app\mobile\controller;
use app\home\logic\UsersLogic;
use app\home\model\Message;
use think\AjaxPage;
use think\Page;
use think\Request;
use think\Verify;
use think\db;
class Distribut extends MobileBase {
	public function index()
	{
		$where='';
		$where.='w.user_id='.$_SESSION['user']['user_id'];
		$type = I('get.type');
		switch ($type) {
			case 'all':
				//所有的分销
				break;
			case 'already':
				//已经兑换过的
				$where .= ' and w.buy_num>=g.share_num and w.exchange_statue=1'; 
				break;
			case 'never':
				//还没有兑换过的
				$where .= ' and w.buy_num>=g.share_num and w.exchange_statue=0'; 
				break;	
			default:
				# code...
				break;
		}
        // $where .= ' and w.buy_num>=g.share_num and w.exchange_statue=0'; 
    
        $count = M('wxshare')->alias('w')->join('__USERS__ u','u.user_id = w.user_id')->join('__GOODS__ g','g.goods_id = w.goods_id')->where($where)->count();
        $Page = new Page($count, 10);
        $order_list = $data = M('wxshare')->alias('w')->join('__USERS__ u','u.user_id = w.user_id')->join('__GOODS__ g','g.goods_id = w.goods_id')->order($order_str)->where($where)->limit($Page->firstRow, $Page->listRows)->select();

        $this->assign('lists',$order_list);
        $this->assign('type',$type);
        if (I('get.is_ajax')) {
        	return $this->fetch('ajax_distribut');
        }

		return $this->fetch();
	}

	public function exchange_goods()
	{
		$user_id = $_SESSION['user']['user_id'];
		$user = M('Users')->where(array('user_id'=>$user_id))->find();
		$is_distribut = $user['is_distribut'];
		// if($is_distribut!=1) {
		// 	$this->assign('user',$user);
		// 	return $this->fetch('apply');
		// }
		$id = I('get.id');
		$old = M('Wxshare')->where(array('id'=>$id))->find();
		if(!$old){
			$this->error('该分享不存在');
		}
		if ($old['exchange_statue']==1) {
			$this->error('已经兑换过');
		}
	   $goods = M('goods')->where(array('goods_id'=>$old['goods_id']))->find();
	   if(!$goods){
          $this->error('分享失效');
	   }
	   $share_money = $goods['commission'];
	   $share_goods = M('Goods')->where(array('goods_id'=>$goods['share_gid']))->find();
	   if (is_array($share_goods)) {
	   	$this->assign('goods_status','1');
	   }else{
	   	 $this->assign('goods_status','0');
	   }
	   $address = M('UserAddress')->where(array('user_id'=>$user_id,'is_default'=>'1'))->find();
	   $this->assign('address',$address);
	   $this->assign('share_goods',$share_goods);
	   $this->assign('share_money',$share_money);
	   $this->assign('id',$id);
	   $this->assign('old',$old);
	   // if ($old['exchange_statue']==1) {
       //      return $this->fetch('show_exchange');
	   // }
	   return $this->fetch();
	}


	public function show_exchange()
	{

		$id = I('get.id');
		$old = M('Wxshare')->where(array('id'=>$id))->find();
		if(!$old){
			$this->error('该分享不存在');
		}

	   $goods = M('goods')->where(array('goods_id'=>$old['goods_id']))->find();
	   if(!$goods){
          $this->error('分享失效');
	   }
	   $share_money = $goods['commission'];
	   $share_goods = M('Goods')->where(array('goods_id'=>$goods['share_gid']))->find();
	   if (is_array($share_goods)) {
	   	$this->assign('goods_status','1');
	   }else{
	   	 $this->assign('goods_status','0');
	   }

	   $this->assign('share_goods',$share_goods);
	   $this->assign('share_money',$share_money);

	   $this->assign('old',$old);
	  
	   return $this->fetch();
	}

	public function perfect_info()
	{
		$user_id = $_SESSION['user']['user_id'];
		$userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($user_id); // 获取用户信息
        $user_info = $user_info['result'];
        if (IS_POST) {
        	I('post.id_card') ? $post['id_card'] = I('post.id_card') : false; //手机
        	I('post.real_name') ? $post['real_name'] = I('post.real_name') : false; //手机
        	I('post.mobile') ? $post['mobile'] = I('post.mobile') : false; //手机
            $mobile = I('post.mobile');
            $code = I('post.mobile_code', '');
            $scene = I('post.scene', 6);
            if (!$code) $this->error('请输入验证码');
             $check_code = $userLogic->check_validate_code($code, $mobile, 'phone', $this->session_id, $scene);
             $post['is_distribut'] =2;
             $post['mobile_validated'] =1;
               if (!$userLogic->update_info($user_id, $post))
                $this->error("保存失败");
            $this->success("操作成功");
            exit;
        }
		  
	}

	public function do_exchange()
	{
		$post = I('post.');
		$user_id = $_SESSION['user']['user_id'];
		// if($user_id!=2589){
  //         $this->error('系统调试过程中');
		// }

		$id = $post['share_id'];
		if(empty($post['type'])) $this->error('选择兑换类型');
		if(empty($id)) $this->error('该分享不存在');
		$share = M('Wxshare')->where(array('id'=>$id))->find();
		if ($share['exchange_statue']==1) {
			$this->error('已经兑换过了');
		}
		$scale=1;
		if(!empty($share['first_share'])&&is_numeric($share['first_share'])){
           $shop_scale = M('DistributeScale')->where(array('shop_id'=>$share['shop_id']))->find();
           if (!is_array($shop_scale)) {
           	$shop_scale = M('DistributeScale')->where(array('shop_id'=>0))->find();
           }
           
           $scale = sprintf("%.2f",substr(sprintf("%.3f", (10-$shop_scale['scale'])/10), 0, -2)); 
          
		}
		$goods = M('Goods')->where(array('goods_id'=>$share['goods_id']))->find();
		if(!is_array($share)) $this->error('该分享不存在');
		$order_sn = '';
		if ($post['type']=='goods') {
			
			if(!is_array($goods)) $this->error('兑换不存在');
           $order_sn = $this->do_exchange_goods($goods['share_gid'],$post['address_id'],$user_id);

           if(!$order_sn) $this->error('兑换失败');
           M('ExchangeLog')->add(array('share_id'=>$id,'shop_id'=>$share['shop_id'],'order_sn'=>$order_sn['order_sn'],'type'=>1,'add_time'=>time()));
           M('Wxshare')->where(array('id'=>$id))->save(array('exchange_type'=>1,'exchange_statue'=>1,'exchange_time'=>time()));
            $this->success('兑换成功',U('/mobile/Distribut/index'));
		}
		if ($post['type']=='money') {
			$user = M('Users')->where(array('user_id'=>$user_id))->find();
			$money = $user['user_money']+$goods['commission']*$scale;
			
			$res = M('Users')->where(array('user_id'=>$user_id))->save(array('user_money'=>$money));

			if(!$res) $this->error('兑换失败');
			$data['order_sn']='recharge'.get_rand_str(10,0,1);
			$data['pay_code']="share";
			$data['pay_name']='分享奖金';
			$data['account'] = $goods['commission']*$scale;
			$data['pay_status']=1;
			$data['pay_time'] = time();
			$data['ctime'] = time();
			$data['nickname'] = $user['nickname'];
			$data['user_id'] = $user['user_id'];
			$result = M('recharge')->add($data);
			if($result) $order_sn = $data['order_sn'];
			if(!$order_sn) $this->error('兑换失败');
           M('ExchangeLog')->add(array('user_id'=>$user['user_id'],'share_id'=>$id,'shop_id'=>$share['shop_id'],'exchange_money'=>$goods['commission'],'get_money'=>$goods['commission']*$scale,'level'=>2,'order_sn'=>$data['order_sn'],'type'=>2,'add_time'=>time()));
          

           if ($scale<1) {
           	$user = M('Users')->where(array('user_id'=>$share['first_share']))->find();
			$money = $user['user_money']+$goods['commission']*(1-$scale);
			$res = M('Users')->where(array('user_id'=>$share['first_share']))->save(array('user_money'=>$money));
			if(!$res) $this->error('兑换失败');
			$data['order_sn']='recharge'.get_rand_str(10,0,1);
			$data['pay_code']="share";
			$data['pay_name']='分销奖金';
			$data['account'] = $goods['commission']*(1-$scale);
			$data['pay_status']=1;
			$data['pay_time'] = time();
			$data['ctime'] = time();
			$data['nickname'] = $user['nickname'];
			$data['user_id'] = $user['user_id'];
			$result = M('recharge')->add($data);
			if($result) $order_sn = $data['order_sn'];
			if(!$order_sn) $this->error('兑换失败');
           M('ExchangeLog')->add(array('user_id'=>$user['user_id'],'share_id'=>$id,'shop_id'=>$share['shop_id'],'exchange_money'=>$goods['commission'],'get_money'=>$goods['commission']*(1-$scale),'level'=>1,'order_sn'=>$data['order_sn'],'scale'=>$scale,'type'=>2,'add_time'=>time()));
           }
            M('Wxshare')->where(array('id'=>$id))->save(array('exchange_type'=>1,'exchange_statue'=>1,'exchange_time'=>time()));
           $this->success('兑换成功',U('/mobile/Distribut/index'));
 		}


	}


   private function do_exchange_goods($goods_id,$address_id,$user_id)
   {
   	 $goods = M('Goods')->where(array('goods_id'=>$goods_id))->find();

   	 $address = M('UserAddress')->where(array('user_id'=>$user_id,'address_id'=>$address_id))->find();

        $data = array(
                'order_sn'         => date('YmdHis').rand(1000,9999), // 订单编号
                'user_id'          =>$user_id, // 用户id
                'consignee'        =>$address['consignee'], // 收货人
                'province'         =>$address['province'],//'省份id',
                'city'             =>$address['city'],//'城市id',
                'district'         =>$address['district'],//'县',
                'twon'             =>$address['twon'],// '街道',
                'address'          =>$address['address'],//'详细地址',
                'mobile'           =>$address['mobile'],//'手机',
                'zipcode'          =>$address['zipcode'],//'邮编',            
                'email'            =>$address['email'],//'邮箱',

         
        );
         $data['order_amount']=0;
         $_SESSION['shop_id']?$data['shop_id']=$_SESSION['shop_id']:$data['shop_id']=0;
         $data['order_id'] = $order_id = M("Order")->insertGetId($data);

           $data2['order_id']           = $order_id; // 订单id
           $data2['goods_id']           = $goods['goods_id']; // 商品id
           $data2['goods_name']         = $goods['goods_name']; // 商品名称
           $data2['goods_sn']           = $goods['goods_sn']; // 商品货号
           $data2['goods_num']          = 1; // 购买数量
           $data2['market_price']       = $goods['market_price']; // 市场价
           $data2['goods_price']        = $goods['shop_price']; // 商品价               为照顾新手开发者们能看懂代码，此处每个字段加于详细注释
           // $data2['spec_key']           = $goods['spec_key']; // 商品规格
           // $data2['spec_key_name']      = $goods['spec_key_name']; // 商品规格名称
           $data2['member_goods_price'] = $goods['member_goods_price']; // 会员折扣价
           $data2['cost_price']         = $goods['cost_price']; // 成本价
           $data2['give_integral']      = $goods['give_integral']; // 购买商品赠送积分         
           $data2['prom_type']          = $goods['prom_type']; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
           $data2['prom_id']            = $goods['prom_id']; // 活动id
           $order_goods_id              = M("OrderGoods")->insertGetId($data2);

        if($data['order_amount'] == 0)
        {                        
            update_pay_status($data['order_sn']);
        }
   return array('order_sn'=>$data['order_sn'],'goods_name'=>$goods['goods_name']);
   }

    public function apply()
    {
    	   $user = $_SESSION['user'];
    	   $this->assign('user',$user);
			return $this->fetch('apply');
    }

    public function see_info()
    {
    	$id = $_GET['id'];
    
    	$where = 'w.share_id='.$id;
    	 $count = M('scan_share')->alias('w')->join('__USERS__ u','u.user_id = w.uid')->where($where)->count();

    


        $Page = new AjaxPage($count,10);
        $show = $Page->show();
        $data = M('scan_share')->alias('w')->join('__USERS__ u','u.user_id = w.uid')->where($where)->limit($Page->firstRow, $Page->listRows)->select();
        $show = $Page->show();
        $this->assign('data',$data);
        $this->assign('id',$id);
        if(IS_AJAX){
           return $this->fetch('ajax_see_info');
        }else{
        	return $this->fetch();
        }
        
        
    }

        public function buy_info()
    {
    	$id = $_GET['id'];
    
    	$where = 'w.share_id='.$id;
    	 $count = M('buy_share')->alias('w')->join('__USERS__ u','u.user_id = w.uid')->where($where)->count();

    


        $Page = new AjaxPage($count,10);
        $show = $Page->show();
        $data = M('buy_share')->alias('w')->join('__USERS__ u','u.user_id = w.uid')->where($where)->limit($Page->firstRow, $Page->listRows)->select();
        $show = $Page->show();
        $this->assign('data',$data);
        $this->assign('id',$id);
        if(IS_AJAX){
           return $this->fetch('buy_see_info');
        }else{
        	return $this->fetch();
        }
        
        
    }


}