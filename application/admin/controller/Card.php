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
 *   
 * Date: 2015-09-09
 */
namespace app\admin\controller;

use think\Page;
use think\Db;
/**
* 	
*/
class Card extends Base
{
	
	public function index()
	{   $map = array();
		$map = array_filter($_POST);
		$map['del_status'] = 0;
		$count = D('Card')->where($map)->count();
   	    $page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $page->show();
        $data = M('Card')->where($map)->limit($page->firstRow.','.$page->listRows)->select();
        $cates = M('CardType')->select();

        $this->assign('cates',$cates);
        $this->assign('list',$data);
    	$this->assign('page',$show);
    	$this->assign('type',$_SESSION['type']);

		return $this->fetch();
	}
/***********************商家对会员卡的操作start*************************/
	//验证手机号新版本中
	public function check_card()
	{

		if (IS_AJAX) {
				$data = I('post.');

				$map = array();
		  		$map['mobile'] = $data['phone'];
		  		$map['session_id'] = session_id();
		  		$map['status']=1;
          		$old = M('sms_log')->where($map)->order('id desc')->find();
          		$time = time()-$old['add_time'];
        if ($old&&$time>3600)  exit(json_encode(array('status'=>0,'msg'=>'验证码已超时')));
        if ($old['code']!=$data['code']) exit(json_encode(array('status'=>0,'msg'=>'验证码有误')));

        $card = M('Card')->where(array('card_no'=>$data['card_no'],'del_status'=>0))->find();

        if (!is_array($card)) exit(json_encode(array('status'=>0,'msg'=>'会员卡不存在，请重新输入')));

        if ($card['host_phone']!=$data['phone']) exit(json_encode(array('status'=>0,'msg'=>'该手机号与会员卡绑定手机号不相符，请核实并重新输入，有疑问请拨打电话：400-877-8063')));

        	//读取会员卡所有
        	$data = M('CardMeal')->where(array('card_id'=>$card['id'],'del_status'=>0))->select();
        	exit(json_encode(array('status'=>1,'data'=>$data)));
		}else{
			return $this->fetch();
		}
		
	}

	//新版本中会员卡消费
	public function new_consume()
	{
		if (IS_AJAX) {
			$id = I('id');
			if (empty($id)||!is_numeric($id)) exit(json_encode(array('status'=>0,'msg'=>'传参有误')));

			$data =  M('CardMeal')->alias('cm')->join('__CARD__ c','c.id=cm.card_id')->field('cm.id,cm.name,c.card_no,cm.card_id')->where(array('cm.id'=>$id,'cm.del_status'=>0,'c.del_status'=>0))->find();
			exit(json_encode(array('status'=>1,'msg'=>'请求成功','data'=>$data)));
		}else{
			exit(json_encode(array('status'=>0,'msg'=>'请求方法有误')));
		}
	}

	//确认消费
	public function new_do_consume()
	{
	    if(IS_AJAX&&IS_POST){
          if(empty($_POST['id'])) exit(json_encode(array('status'=>0,'msg'=>'参数有误')));
           $_POST['time']=1;
           $_POST['add_time']=time();
           $_POST['op_id']=$_SESSION['admin_id'];
           $data = M('CardMeal')->where(array('id'=>$_POST['id'],'del_status'=>0))->find();

           $time = $data['time'];
           $consume_num = $data['consume_num']+$_POST['time'];
           if($consume_num>$time)  exit(json_encode(array('status'=>0,'msg'=>'该项目所剩次数不足')));
           if($consume_num==$time) {
           	M('CardMeal')->where(array('id'=>$_POST['id']))->save(array('consume_num'=>$time,'status'=>1));
           	$num = M('CardMeal')->where(array('card_id'=>$_POST['card_id'],'status'=>0))->count();
           	if($num==0) M('Card')->where(array('id'=>$_POST['card_id']))->save(array('status'=>1));
           }else{
           	 
           $result = M('CardMeal')->where(array('id'=>$_POST['id']))->save(array('consume_num'=>$consume_num));

           }
           $log = array();
           $log['card_meal_id']=$data['id'];
           $log['name']=$data['name'];
           $log['card_id']=$data['card_id'];
           $log['time']=$_POST['time'];
           $log['op_id']=$_POST['op_id'];
           $log['add_time']=time();
           $res = M('CardLog')->add($log);
           if ($res) {
           	exit(json_encode(array('status'=>1,'msg'=>'消费成功')));
           }else{
           	exit(json_encode(array('status'=>0,'msg'=>'消费失败')));
           }

	    }
	}
/*****************************商家对会员卡的操作end*******************************/

	public function add_card()
	{
		$id = $_GET['id'];
		$data = M('Card')->where(array('id'=>$id))->find();
		$types = M('CardType')->select();
        $meals = M('CardType')->where(array('id'=>$data['card_type']))->find();
        $array = json_decode($meals['packages']);
		$info = array();
		if(is_array($array)){
		foreach ($array as $key => $value) {
			$info[] = M('SetMeal')->where(array('id'=>$value))->find();
		}}
		$car_type = M('CarType')->where(array('parent_id'=>0))->select();
		$this->assign('info',$info);
		$this->assign('types',$types);
		$this->assign('car_types',$car_type);
		$this->assign('data',$data);
		return $this->fetch();
	}
	public function del_card()
	{
		$id  = $_GET['id'];
		$res = M('Card')->where(array('id'=>$id))->save(array('del_status'=>1));
		if($res){
			M('CardMeal')->where(array('card_id'=>$id))->save(array('del_status'=>1));
            $this->success('删除成功');
		}else{
            $this->error('删除失败');
		}
	}
	public function do_add_card()
	{

		if(IS_POST){
          $id = $_POST['id'];
        
          if (empty($id)) {
          	  $old = M('Card')->where(array('card_no'=>$_POST['card_no']))->find();
          if(is_array($old)) exit(json_encode(array('status'=>'0','msg'=>'该会员卡已被绑定')));
          $old = M('Card')->where(array('car_no'=>$_POST['car_no']))->find();
          if(is_array($old)) exit(json_encode(array('status'=>'0','msg'=>'该车牌号已经存在')));
          if (empty($_POST['card_type'])) exit(json_encode(array('status'=>'0','msg'=>'请选择套餐')));
          	$_POST['add_time'] = time();
          	$set_meal = M('SetMeal')->where(array('id'=>$_POST['set_meal']))->find();
          	$_POST['content']=$set_meal['content'];
          	$res =M('Card')->add($_POST);
          	$arr = json_decode($_POST['content']);

          	if(is_array($arr)){


          	foreach ($arr as $key => $array) {
          	    $meal['price'] = $array[1];
          		$meal['name'] = $array[0];
          		$meal['time'] = $array[2];
          		$meal['card_id'] = $res;

          		$result = M('CardMeal')->add($meal);

          	}
          	
          	}
          	if($res){

               exit(json_encode(array('status'=>'1','msg'=>'添加成功')));
          	}else{
          		exit(json_encode(array('status'=>'0','msg'=>'添加失败')));
          	}
          }else{
          	unset($_POST['id']);
          	$count = M('CardMeal')->where(array('card_id'=>$id,'consume_num'=>array('neq','0')))->count();
          	if($count!=0) exit(json_encode(array('status'=>'0','msg'=>'已经开始消费，不能修改')));
          	
          	$_POST['edit_time'] = time();
          		$set_meal = M('SetMeal')->where(array('id'=>$_POST['set_meal']))->find();
          	$_POST['content']=$set_meal['content'];
          	
          	$res =M('Card')->where(array('id'=>$id))->save($_POST);
          	M('CardMeal')->where(array('card_id'=>$id))->delete();
          	$arr = json_decode($_POST['content']);
          	if(is_array($arr)){


          	foreach ($arr as $key => $array) {
          	    $meal['price'] = $array[1];
          		$meal['name'] = $array[0];
          		$meal['time'] = $array[2];
          		$meal['card_id'] = $id;
    
          		$result = M('CardMeal')->add($meal);

          	}
          	
          	}
          	if ($res!==false) {
          		exit(json_encode(array('status'=>'1','msg'=>'更新成功')));
          	}else{
          		exit(json_encode(array('status'=>'0','msg'=>'更新失败')));
          	}
          }
		}
	}
	//获取套餐
	public function get_meal()
	{
		$id = $_POST['id'];

		if(empty($id)) exit(json_encode(array('status'=>0,'msg'=>'获取套餐失败')));
		$data = M('CardType')->where(array('id'=>$id))->find();
		$data = json_decode($data['packages']);
		$info = array();
		foreach ($data as $key => $value) {
			$info[] = M('SetMeal')->where(array('id'=>$value))->find();
		}
		exit(json_encode(array('status'=>1,'msg'=>'success','info'=>$info)));
	}

//会员卡可选设置
	public function attribute()
	{
		$map = array();
		$map['del_status']=0;
		$count = D('CardAttribute')->where($map)->count();
   	    $page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $page->show();
        $data = M('CardAttribute')->where($map)->limit($page->firstRow.','.$page->listRows)->select();

        $this->assign('list',$data);
    	$this->assign('page',$show);
		return $this->fetch();
	}
	public function add_attribute()
	{
	
		if (IS_POST) {
			if(empty($_POST['attribute'])){echo(json_encode(array('msg'=>'名称不能为空')));die();} 
			if(!is_numeric($_POST['price'])) {echo(json_encode(array('msg'=>'价格只能填数字')));die();}
			if(empty($_POST['id'])){
              $_POST['add_time'] = time();
              $_POST['shop_id'] =$_SESSION['type']==1? $_SESSION['admin_id']:0;
           
              if (M('CardAttribute')->add($_POST)) {
              	echo(json_encode(array('msg'=>'添加成功')));die();
              	
              }else{
              	echo(json_encode(array('msg'=>'添加失败')));die();
              
              }
			}else{
				$id = $_POST['id'];
				$_POST['edit_time'] = time();
				unset($_POST['id']);
				$res = M('CardAttribute')->where(array('id'=>$id))->save($_POST);
				if ($res!==false) {
					echo(json_encode(array('msg'=>'编辑成功')));die();
				}else{
						echo(json_encode(array('msg'=>'编辑成功')));die();
				}
			}
		}else{
			$id = I('get.id','0');
			if(!empty($id)&&is_numeric($id)) $data = M('CardAttribute')->where(array('id'=>$id))->find();
			$this->assign('data',$data);

		}
		return $this->fetch();
	}


	public function del_attr()
	{
		$id = I('get.id');
		if (empty($id)||!is_numeric($id)) {
			$this->error('删除失败');
		}
		$res = M('CardAttribute')->where(array('id'=>$id))->save(array('del_status'=>1));
		if ($res) {
			$this->success('删除成功');
			// echo(json_encode(array('status'=>'1')));die();
		}else{
			$this->error('删除失败');
				// echo(json_encode(array('status'=>'0','msg'=>'删除失败')));die();
		}
	}

//套餐设置
	public function set_meal()
	{
		$map = array();
		$map['del_status']=0;
		$count = D('SetMeal')->where($map)->count();
   	    $page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $page->show();
        $data = M('SetMeal')->where($map)->limit($page->firstRow.','.$page->listRows)->select();
        $this->assign('list',$data);
    	$this->assign('page',$show);
		return $this->fetch();
	}

   //添加编辑会员卡套餐
    public function add_set_meal()
	{
		if (IS_POST) {
			$allData['name'] = $_POST['name'];
			$allData['add_time'] = time();
			$data = array();
			$rights = array_filter($_POST['right']);
			foreach ($rights as $key => $value) {
				$data[] = explode('|',$value);
			}
            $allData['content'] = json_encode($data);
            $id = $_POST['id'];
			if (empty($id)) {

				$res = M('SetMeal')->add($allData);
				if($res){
                   echo(json_encode(array('msg'=>'添加成功')));die();
				}else{
					echo(json_encode(array('msg'=>'添加失败')));die();
				}
			}else{
				$old = M('SetMeal')->where(array('id'=>$id))->find();
				unset($allData['add_time']);
				$allData['edit_time'] = time();
		
				if(!$old) {echo(json_encode(array('msg'=>'套餐不存在')));die();}
				$res = M('SetMeal')->where(array('id'=>$id))->save($allData);
				if($res!==false){
					echo(json_encode(array('msg'=>'编辑成功')));die();
				}else{
					echo(json_encode(array('msg'=>'编辑失败')));die();
				}
			}
			
		}else{
			$id = I('get.id');
			if(!empty($id)) {$data = M('SetMeal')->where(array('id'=>$id))->find();

            $data['content'] = json_decode($data['content']);
			$this->assign('info',$data);
		   }
		}
		$attributes = M('CardAttribute')->select();
		$this->assign('attributes',$attributes);
		return $this->fetch();
	}
     
     public function del_setmeal()
	{
		$id = I('get.id');
		if (empty($id)||!is_numeric($id)) {
			$this->error('删除失败');
		}
		$res = M('SetMeal')->where(array('id'=>$id))->save(array('del_status'=>1));
		if ($res) {
			$this->success('删除成功');
			// echo(json_encode(array('status'=>'1')));die();
		}else{
			$this->error('删除失败');
				// echo(json_encode(array('status'=>'0','msg'=>'删除失败')));die();
		}
	}

	//分类设置
	public function card_type()
	{

        $map = array();
        $map['del_status']=0;
		$count = D('CardType')->where($map)->count();
   	    $page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $page->show();
        $data = M('CardType')->where($map)->limit($page->firstRow.','.$page->listRows)->select();

        $this->assign('list',$data);
    	$this->assign('page',$show);
		return $this->fetch();
	}
	public function add_card_type()
	{
		if (IS_POST) {
			$allData['card_name'] = $_POST['data']['card_name'];
			$allData['money'] = $_POST['data']['money'];
			$allData['type_desc'] = $_POST['data']['type_desc'];
			$allData['packages'] = json_encode($_POST['right']);
			
            $id = $_POST['id'];
			if (empty($id)) {
               $allData['add_time'] = time();
    
				$res = M('CardType')->add($allData);
				if($res){
                   echo(json_encode(array('msg'=>'添加成功')));die();
				}else{
					echo(json_encode(array('msg'=>'添加失败')));die();
				}
			}else{
				$old = M('CardType')->where(array('id'=>$id))->find();
				$allData['edit_time'] = time();
		
				if(!$old) {echo(json_encode(array('msg'=>'套餐不存在')));die();}
				$res = M('CardType')->where(array('id'=>$id))->save($allData);
				if($res!==false){
					echo(json_encode(array('msg'=>'编辑成功')));die();
				}else{
					echo(json_encode(array('msg'=>'编辑失败')));die();
				}
			}
			
		}else{
			$id = I('get.id');
			if(!empty($id)) {$data = M('CardType')->where(array('id'=>$id))->find();

            $data['packages'] = json_decode($data['packages']);
			$this->assign('info',$data);
		   }
		}
		$set_meals = M('SetMeal')->where(array('del_status'=>0))->select();
		
		$this->assign('set_meals',$set_meals);
		return $this->fetch('addCardType');
	}


//消费
	public function  consume()
	{
		$card_id = $_GET['id'];
		$old = M('Card')->where(array('id'=>$card_id))->find();
        if($old['status']==1) $this->error('该会员卡已消费完',U('Admin/Card/index'));
        $result= $this->send_msg($old['host_phone']);
		$data = M('CardMeal')->where(array('card_id'=>$card_id))->select();
		$this->assign('types',$data);
		$this->assign('card_id',$card_id);
		$code = session('code'.$card_id);
		if ($code&&!$result) {
			$this->assign('codeshow','1');
		}
		return $this->fetch();
	}

	public function  do_consume()
	{
	
	    if(IS_POST){
          if(empty($_POST['card_meal_id'])) $this->error('请选择项目');
          if(empty($_POST['time'])) $this->error('请填写次数');
	      $old = M('Card')->where(array('id'=>$_POST['card_id'],'del_status'=>0))->field('host_phone')->find();
	      $map = array();
		  $map['mobile'] = $old['host_phone'];
		  $map['session_id'] = session_id();
		  $map['status']=1;
          $old = M('sms_log')->where($map)->order('id desc')->find();
          $time = time()-$old['add_time'];
        if ($old&&$time>3600)  $this->error('验证码已超时');
        $code = $_SESSION['code'.$_POST['card_id']];
   
        $post_code = $_POST['code'];

        if (!$code||empty($code)) {

        	if(empty($post_code)) $this->error('验证码为空');
        	// var_dump($old);die();
        	if($post_code!=$old['code']){
        	 $this->error('验证码有误');
        	}else{
        		$_SESSION['code'.$_POST['card_id']]=$post_code;
        	}
        }
           $_POST['add_time']=time();
           $_POST['op_id']=$_SESSION['admin_id'];
           $data = M('CardMeal')->where(array('id'=>$_POST['card_meal_id'],'del_status'=>0))->find();
           $time = $data['time'];
           $consume_num = $data['consume_num']+$_POST['time'];
           if($consume_num>$time)  $this->error('该项目所剩次数不足');
           if($consume_num==$time) {
           	M('CardMeal')->where(array('id'=>$_POST['card_meal_id']))->save(array('consume_num'=>$time,'status'=>1));
           	$num = M('CardMeal')->where(array('card_id'=>$_POST['card_id'],'status'=>0))->count();
           	if($num==0) M('Card')->where(array('id'=>$_POST['card_id']))->save(array('status'=>1));
           }else{
           	 
           $result = M('CardMeal')->where(array('id'=>$_POST['card_meal_id']))->save(array('consume_num'=>$consume_num));

           }

           $res = M('CardLog')->add($_POST);
           if ($res) {
           $this->success('消费成功');
           }else{
            $this->error('消费失败');
           }

	    }
	}

//每张会员卡的消费记录
	public function consume_log()
	{
		$card_id = $_GET['id'];
		if(empty($card_id)||!is_numeric($card_id)) $this->error('该会员卡不存在');
		$map = array();
        $map['card_id'] = $card_id;
		$count = D('CardLog')->where($map)->count();
   	    $page = new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数
        $show = $page->show();
        $data = M('CardLog')->where($map)->limit($page->firstRow.','.$page->listRows)->select();

        $this->assign('list',$data);
    	$this->assign('page',$show);
		return $this->fetch();

	}

	private function send_msg($num)
	{
		$map = array();
		$map['mobile'] = $num;
		$map['session_id'] = session_id();
		$map['status']=1;
        $old = M('sms_log')->where($map)->order('id desc')->find();
        $time = time()-$old['add_time'];
        if ($old&&$time<3600) return false;
	
		$code = rand(1000, 9999);
		$tpl_value ='#code#='.$code;
		
		sendAllMsg($num,$tpl_value,$code,'46984');
		return true;

	}

	public function del_type()
	{
		$id = I('get.id');
		if (empty($id)||!is_numeric($id)) {
			$this->error('删除失败');
		}
		$res = M('CardType')->where(array('id'=>$id))->save(array('del_status'=>1));
		if ($res) {
			$this->success('删除成功');
			// echo(json_encode(array('status'=>'1')));die();
		}else{
			var_dump($res);die();
			$this->error('删除失败');
				// echo(json_encode(array('status'=>'0','msg'=>'删除失败')));die();
		}
	}


	public function ajax_car_type()
	{
	  
	   if (IS_AJAX) {
	   	 $parent_id =	$_POST['parent_id'] ;
	   	 $types = M('CarType')->where(array('parent_id'=>$parent_id,'del_status'=>0))->select();

        exit(json_encode(array('data'=>$types)));
	   }
	}

}