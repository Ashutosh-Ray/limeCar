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
use app\admin\logic\GoodsLogic;
use think\AjaxPage;
use think\Page;
use think\Db;

class Distribut extends Base {


    //分销商品列表
	public function goods_list()
	{
		$GoodsLogic = new GoodsLogic();
    
         if ($_SESSION['type']==0)
         {
            $brandList = $GoodsLogic->getSortBrands();
            $categoryList = $GoodsLogic->getSortCategory();
         }else{
            $brandList = $GoodsLogic->getSortBrands("shop_id=".$_SESSION['admin_id']);
            $categoryList = $GoodsLogic->getSortCategory("shop_id=".$_SESSION['admin_id']);
          
         }         
        
        $this->assign('categoryList',$categoryList);
        $this->assign('brandList',$brandList);
		return $this->fetch();
	}
    public function ajaxGoodsList()
    {
    	$where = ' 1 = 1 and share_num > 0'; // 搜索条件                
        I('intro')    && $where = "$where and ".I('intro')." = 1" ;        
        I('brand_id') && $where = "$where and brand_id = ".I('brand_id') ;
        (I('is_on_sale') !== '') && $where = "$where and is_on_sale = ".I('is_on_sale') ;                
        $cat_id = I('cat_id');
        // 关键词搜索               
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%' or goods_sn like '%$key_word%')" ;
        }
        
        if($cat_id > 0)
        {
            $grandson_ids = getCatGrandson($cat_id); 
            $where .= " and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }
        
        if ($_SESSION['type']==0)
         {
          
         }else{
            $where = $where.' AND shop_id='.$_SESSION['admin_id'];
          
         } 

        $model = M('Goods');
        $count = $model->where($where)->count();
        $Page  = new AjaxPage($count,10);

        /**  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
        */
        $show = $Page->show();
        $order_str = "`{$_POST['orderby1']}` {$_POST['orderby2']}";
        $goodsList = $model->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();
       
        $catList = D('goods_category')->select();
        $catList = convert_arr_key($catList, 'id');
        $this->assign('catList',$catList);
        $this->assign('goodsList',$goodsList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }
    //分销商列表
	public function distributor_list()
	{
		return $this->fetch();
	}
    public function ajaxDistributor()
    {
        $where='';
        $_SESSION['type']==1?$where.='w.shop_id='.$_SESSION['admin_id']:false;
    
        $count = M('wxshare')->alias('w')->join('__USERS__ u','u.user_id = w.user_id')->join('__GOODS__ g','g.goods_id = w.goods_id')->where($where)->count();

     


        $Page = new AjaxPage($count,10);
        $show = $Page->show();
        $data = M('wxshare')->alias('w')->join('__USERS__ u','u.user_id = w.user_id')->join('__GOODS__ g','g.goods_id = w.goods_id')->where($where)->limit($Page->firstRow, $Page->listRows)->select();
        $show = $Page->show();
       $this->assign('page',$show);
       $this->assign('distributorList',$data);
     
       return $this->fetch();
    }
    public function distributor_apply()
    {
        return $this->fetch();
    }
     public function ajax_apply(){
     // 搜索条件
     $condition = array();
     I('mobile') ? $condition['mobile'] = I('mobile') : false;
     I('email') ? $condition['email'] = I('email') : false;
     $sort_order = I('order_by','user_id').' '.I('sort','desc');
     $condition['is_distribut'] = array('gt',0);       
     $model = M('users');
     $count = $model->where($condition)->count();

     $Page  = new AjaxPage($count,10);
     //  搜索条件下 分页赋值
     // foreach($condition as $key=>$val) {
     //     $Page->parameter[$key]   =   urlencode($val);
     // }
     
     $userList = $model->where($condition)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
             
     $user_id_arr = get_arr_column($userList, 'user_id');
     if(!empty($user_id_arr))
     {
         $first_leader = DB::query("select first_leader,count(1) as count  from __PREFIX__users where first_leader in(".  implode(',', $user_id_arr).")  group by first_leader");
         $first_leader = convert_arr_key($first_leader,'first_leader');
         
         $second_leader = DB::query("select second_leader,count(1) as count  from __PREFIX__users where second_leader in(".  implode(',', $user_id_arr).")  group by second_leader");
         $second_leader = convert_arr_key($second_leader,'second_leader');            
         
         $third_leader = DB::query("select third_leader,count(1) as count  from __PREFIX__users where third_leader in(".  implode(',', $user_id_arr).")  group by third_leader");
         $third_leader = convert_arr_key($third_leader,'third_leader');            
     }
     $this->assign('first_leader',$first_leader);
     $this->assign('second_leader',$second_leader);
     $this->assign('third_leader',$third_leader);                                
     $show = $Page->show();
     $this->assign('userList',$userList);
     $this->assign('level',M('user_level')->getField('level_id,level_name'));
     $this->assign('page',$show);// 赋值分页输出
     $this->assign('pager',$Page);
     return $this->fetch();
    }


	//分销关系
	public function tree()
	{
		
	}

	//分销设置
	public function set()
	{
       
        $_SESSION['type']==1? $shop_id = $_SESSION['admin_id']: $shop_id = 0;
        $data = M('DistributeScale')->where(array('shop_id'=>$shop_id))->find();

        if (IS_AJAX) {
          
            if (!is_array($data)) {
             $result =  M('DistributeScale')->add(array('shop_id'=>$shop_id,'scale'=>$_POST['scale'],'add_time'=>time()));
            
            }else{
              $result =  M('DistributeScale')->where(array('shop_id'=>$shop_id))->save(array('scale'=>$_POST['scale']));
            }
            if ($result!==false) {
               exit(json_encode(array('msg'=>'操作成功')));
            }else{
               exit(json_encode(array('msg'=>'操作失败')));
            }
        }else{
            $this->assign('data',$data);
           return $this->fetch(); 
        }
		
	}

	//分成日志
	public function rebate_log()
	{
		# code...
	}


    public function scaninfo()
    {
        $id = $_GET['id'];
        if (empty($id)||!is_numeric($id)) $this->error('该分销不存在');
        $where = 'w.share_id='.$id;
        $count = M('scan_share')->alias('w')->join('__USERS__ u','u.user_id = w.uid')->where($where)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $data = M('scan_share')->alias('w')->join('__USERS__ u','u.user_id = w.uid')->where($where)->limit($Page->firstRow, $Page->listRows)->select();
        $show = $Page->show();
        $this->assign('data',$data);
        $this->assign('Page',$Page);
      
        return $this->fetch();
    }
    public function buyinfo()
    {
        $id = $_GET['id'];
        if (empty($id)||!is_numeric($id)) $this->error('该分销不存在');
        $where = 'w.share_id='.$id;
        $count = M('buy_share')->alias('w')->join('__USERS__ u','u.user_id = w.uid')->where($where)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $data = M('buy_share')->alias('w')->join('__USERS__ u','u.user_id = w.uid')->where($where)->limit($Page->firstRow, $Page->listRows)->select();
        $show = $Page->show();
        $this->assign('data',$data);
        $this->assign('Page',$Page);
        return $this->fetch();
    }


}