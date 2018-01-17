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
 * $Author: IT宇宙人 2015-08-10 $
 */ 
namespace app\home\controller;
use think\Controller;
use think\Db;
use think\response\Json;
use think\Session;

class Base extends Controller {
    public $session_id;
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
        //区别是不是分商城  
          $this->difference();
          
        $this->public_assign();

        //判断是否登录
         if(session('?user'))
        {
          $user = session('user');
                $user = M('users')->where("user_id", $user['user_id'])->find();
                session('user',$user);  //覆盖session 中的 user
         
          $this->assign('user',$user);
          $this->assign('is_login',1);
        }

       
    }
        private function difference(){
          function have($admin_info){
              
               session('shop_id',$admin_info['admin_id']);
               session('shop_type',$admin_info['type']);
               session('shop_name',$admin_info['shop_name']);
               session('admin_name',$admin_info['user_name']);
               
          }
          function nosession(){
   
            session('shop_id',null);
            session('shop_type',null);
            session('shop_name',null);
            session('admin_name',null);
          }
        //区别是不是分商城  
           $str =  strtolower(MODULE_NAME).strtolower(CONTROLLER_NAME).strtolower(ACTION_NAME);
          
           if ($str=='homeindexindex') {
               $name = I('get.shop');

               if($name) {
                $admin_info = M('admin')->where(array('user_name'=>$name,'check_status'=>1))->find();
                if(is_array($admin_info)){
                
                   have($admin_info);
                  }else{
                   nosession();
                  } 
               }else{
                   nosession();
               }
             }
         
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
         }else{
           $this->assign('shop_id',0);
         } 
        $this->assign('shop_type',session('shop_type'));
        $this->assign('admin_name',session('admin_name'));
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
       $brand_list = M('brand')->cache(true,TPSHOP_CACHE_TIME)->field('id,parent_cat_id,logo,is_hot')->where("parent_cat_id>0")->select();              
       $this->assign('brand_list', $brand_list);
       $this->assign('imshop_config', $imshop_config);
    }
    /*
     * 
     */
    public function ajaxReturn($data){                        
            exit(json_encode($data)); 
    }

}