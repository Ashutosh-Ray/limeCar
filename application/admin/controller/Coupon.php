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
namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Db;

class Coupon extends Base {
    /**----------------------------------------------*/
     /*                优惠券控制器                  */
    /**----------------------------------------------*/
    /*
     * 优惠券类型列表
     */
    public function index(){
        //获取优惠券列表
        $where = array('del_status'=>0);
        if (!empty($_REQUEST['keywords'])) {
            $this->assign('keywords', I('keywords'));
            $where['name'] = array('like','%'.I('keywords').'%');
        }
    	$count =  M('coupon')->where($where)->count();
    	$Page = new Page($count,10);
        $show = $Page->show();
        $lists = M('coupon')->where($where)->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('lists',$lists);
        $this->assign('pager',$Page);// 赋值分页输出
        $this->assign('page',$show);// 赋值分页输出   
        $this->assign('coupons',C('COUPON_TYPE'));
        return $this->fetch();
    }

    /*
     * 添加编辑一个优惠券类型
     */
    public function coupon_info(){
        function insert($array1,$cid,$table)
        {
            if(!is_array($array1)) return false;
            foreach ($array1 as $key => $value) {
                 if ($table=='CouponTag') {
                     M($table)->add(array('tag_id'=>$value,'cid'=>$cid)); 
                 }else{
                    M($table)->add(array('shop_id'=>$value,'cid'=>$cid));
                 }
               
            }
           
        }

        if(IS_POST){
        	$data = I('post.');
            $data['use_end_time'] = strtotime($data['use_end_time'].' 23:59:59');
            if(empty($data['id'])){
            	$data['add_time'] = time();
                $data['code'] = time();
            	$row = M('coupon')->add($data);
                if ($row) {
                    $url = "http://".$_SERVER['HTTP_HOST'].U('/Mobile/Coupon/drawcoupon',array('couponid'=>md5('cid'.$row)));
                    $qr = make_qr_code($url,'coupon/'.date('Y-m-d'),$row.'.jpg');
                   M('coupon')->where(array('id'=>$row))->save(array('code'=>md5('cid'.$row),'qrcode'=>$qr));
                   insert($data['tags'],$row,'CouponTag');
                   insert($data['shops'],$row,'CouponShop');
                }
            }else{
            	$row =  M('coupon')->where(array('id'=>$data['id']))->save($data);
                M('CouponTag')->where(array('cid'=>$data['id']))->delete();
                M('CouponShop')->where(array('cid'=>$data['id']))->delete();
                insert($data['tags'],$data['id'],'CouponTag');
                insert($data['shops'],$data['id'],'CouponShop');
            }
            if(!$row)
                $this->error('编辑代金券失败');
            $this->success('编辑代金券成功',U('Admin/Coupon/index'));
            exit;
        }
        $cid = I('get.id/d');
        $allShop = M('Admin')->where(array('type'=>1,'status'=>1,'check_status'=>1,'del_status'=>0))->field('admin_id,shop_name')->select();
        $tags = M('Tag')->where(array('del_status'=>0))->select();
        if($cid){
        	$coupon = M('coupon')->where(array('id'=>$cid))->find();
            $ctags = M('CouponTag')->where(array('cid'=>$cid))->select();
            $cshops = M('CouponShop')->where(array('cid'=>$cid))->select();
            $ctag = array();
            foreach ($ctags as $key => $tag) {
                $ctag[] = $tag['tag_id'];
            }
            $cshop = array();
            foreach ($cshops as $key => $shop) {
                $cshop[] = $shop['shop_id'];
            }
            $this->assign('ctags',$ctag);
            $this->assign('cshops',$cshop);
        	$this->assign('coupon',$coupon);
        }
        $this->assign('allShop',$allShop);
        $this->assign('tags',$tags);      
        return $this->fetch();
    }

    public function select_goods()
    {

        $cat_id = 165;
        $cat_id_arr = getCatGrandson($cat_id);
        $where['cat_id'] = array('in',implode(',', $cat_id_arr));
       if (!empty($_REQUEST['keywords'])) {
            $this->assign('keywords', I('keywords'));
            $where['goods_name'] = array('like','%'.I('keywords').'%');
        }
         
        $count = M('goods')->where($where)->count();
        $Page = new Page($count, 10);
        $goodsList = M('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $show = $Page->show();//分页显示输出
        $this->assign('page', $show);//赋值分页输出
        $this->assign('goodsList', $goodsList);
        $this->assign('pager', $Page);//赋值分页输出
        
        return $this->fetch('Promotion/select_goods');
    }

    /*
    * 优惠券发放
    */
    public function make_coupon(){
        //获取优惠券ID
        $cid = I('get.id/d');
        $type = I('get.type');
        //查询是否存在优惠券
        $data = M('coupon')->where(array('id'=>$cid))->find();
        $remain = $data['createnum'] - $data['send_num'];//剩余派发量
    	if($remain<=0) $this->error($data['name'].'已经发放完了');
        if(!$data) $this->error("优惠券类型不存在");
        if($type != 4) $this->error("该优惠券类型不支持发放");
        if(IS_POST){
            $num  = I('post.num/d');
            if($num>$remain) $this->error($data['name'].'发放量不够了');
            if(!$num > 0) $this->error("发放数量不能小于0");
            $add['cid'] = $cid;
            $add['type'] = $type;
            $add['send_time'] = time();
            for($i=0;$i<$num; $i++){
                do{
                    $code = get_rand_str(8,0,1);//获取随机8位字符串
                    $check_exist = M('coupon_list')->where(array('code'=>$code))->find();
                }while($check_exist);
                $add['code'] = $code;
                M('coupon_list')->add($add);
            }
            M('coupon')->where("id",$cid)->setInc('send_num',$num);
            adminLog("发放".$num.'张'.$data['name']);
            $this->success("发放成功",U('Admin/Coupon/index'));
            exit;
        }
        $this->assign('coupon',$data);
        return $this->fetch();
    }
    
    public function ajax_get_user(){
    	//搜索条件
    	$condition = array();
    	I('mobile') ? $condition['mobile'] = I('mobile') : false;
    	I('email') ? $condition['email'] = I('email') : false;
    	$nickname = I('nickname');
    	if(!empty($nickname)){
    		$condition['nickname'] = array('like',"%$nickname%");
    	}
    	$model = M('users');
    	$count = $model->where($condition)->count();
    	$Page  = new AjaxPage($count,10);
    	foreach($condition as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$show = $Page->show();
    	$userList = $model->where($condition)->order("user_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $user_level = M('user_level')->getField('level_id,level_name',true);       
        $this->assign('user_level',$user_level);
    	$this->assign('userList',$userList);
    	$this->assign('page',$show);
        $this->assign('pager',$Page);
    	return $this->fetch();
    }
    
    public function send_coupon(){
    	$cid = I('cid/d');
    	if(IS_POST){
    		$level_id = I('level_id');
    		$user_id = I('user_id/a');
    		$insert = '';
    		$coupon = M('coupon')->where("id",$cid)->find();
    		if($coupon['createnum']>0){
    			$remain = $coupon['createnum'] - $coupon['send_num'];//剩余派发量
    			if($remain<=0) $this->error($coupon['name'].'已经发放完了');
    		}
    		if(empty($user_id) && $level_id>=0){
    			if($level_id==0){
    				$user = M('users')->where("is_lock",0)->select();
    			}else{
    				$user = M('users')->where("is_lock",0)->where('level', $level_id)->select();
    			}
    			if($user){
    				$able = count($user);//本次发送量
    				if($coupon['createnum']>0 && $remain<$able){
    					$this->error($coupon['name'].'派发量只剩'.$remain.'张');
    				}
    				foreach ($user as $k=>$val){
    					$time = time();
                        $insert[] = ['cid' => $cid, 'type' => 1, 'uid' => $val['user_id'], 'send_time' => $time];
    				}
    			}
    		}else{
    			$able = count($user_id);//本次发送量
    			if($coupon['createnum']>0 && $remain<$able){
    				$this->error($coupon['name'].'派发量只剩'.$remain.'张');
    			}
    			foreach ($user_id as $k=>$v){
    				$time = time();
                    $insert[] = ['cid' => $cid, 'type' => 1, 'uid' => $v, 'send_time' => $time];
    			}
    		}
			DB::name('coupon_list')->insertAll($insert);
			M('coupon')->where("id",$cid)->setInc('send_num',$able);
			adminLog("发放".$able.'张'.$coupon['name']);
			$this->success("发放成功");
			exit;
    	}
    	$level = M('user_level')->select();
    	$this->assign('level',$level);
    	$this->assign('cid',$cid);
    	return $this->fetch();
    }
    
    public function send_cancel(){
    	
    }

    /*
     * 删除优惠券类型
     */
    public function del_coupon(){
        //获取优惠券ID
        $cid = I('get.id/d');
        //查询是否存在优惠券
        // $old =M('coupon')->where(array('id'=>$cid))->find();
        $row = M('coupon')->where(array('id'=>$cid))->save(array('del_status'=>1));
        if($row){
            //删除此类型下的优惠券
            // M('coupon_list')->where(array('cid'=>$cid))->delete();
           exit(json_encode(1));
        }else{
            exit(json_encode("删除失败"));
        }
    }


    /*
     * 优惠券详细查看
     */
    public function coupon_list(){
        //获取优惠券ID
        $cid = I('get.id/d');
        //查询是否存在优惠券
        $check_coupon = M('coupon')->field('id,type')->where(array('id'=>$cid))->find();
        if(!$check_coupon['id'] > 0)
            $this->error('不存在该类型优惠券');
       
        //查询该优惠券的列表的数量
        $sql = "SELECT count(1) as c FROM __PREFIX__coupon_list  l ".
                "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid ". //联合优惠券表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = :cid";    //联合用户表去查询用户名
        
        $count = DB::query($sql,['cid' => $cid]);
        $count = $count[0]['c'];
    	$Page = new Page($count,10);
    	$show = $Page->show();
        
        //查询该优惠券的列表
        $sql = "SELECT l.*,c.name,o.order_sn,u.nickname FROM __PREFIX__coupon_list  l ".
                "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid ". //联合优惠券表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = :cid".    //联合用户表去查询用户名
                " limit {$Page->firstRow} , {$Page->listRows}";
        $coupon_list = DB::query($sql,['cid' => $cid]);
        $this->assign('coupon_type',C('COUPON_TYPE'));
        $this->assign('type',$check_coupon['type']);       
        $this->assign('lists',$coupon_list);            	
    	$this->assign('page',$show);
        $this->assign('pager',$Page);
        return $this->fetch();
    }
    
    /*
     * 删除一张优惠券
     */
    public function coupon_list_del(){
        //获取优惠券ID
        $cid = I('get.id');
        if(!$cid)
            $this->error("缺少参数值");
        //查询是否存在优惠券
         $row = M('coupon_list')->where(array('id'=>$cid))->delete();
        if(!$row)
            $this->error('删除失败');
        $this->success('删除成功');
    }
}