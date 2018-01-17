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
 * $Author: 当燃 2016-01-09
 */
namespace app\mobile\controller;
use app\home\logic\UsersLogic;
use Think\Db;
class Index extends MobileBase {

    public function index(){
        /*
            //获取微信配置
            $wechat_list = M('wx_user')->select();
            $wechat_config = $wechat_list[0];
            $this->weixin_config = $wechat_config;        
            // 微信Jssdk 操作类 用分享朋友圈 JS            
            $jssdk = new \Mobile\Logic\Jssdk($this->weixin_config['appid'], $this->weixin_config['appsecret']);
            $signPackage = $jssdk->GetSignPackage();              
            print_r($signPackage);
        */
       // $time = date('Y-m-d:H:i:s',1500369892);
       // echo $time;die();
     
       // 热销新商品
       // $where = array('is_hot'=>1,'is_on_sale'=>1);
        $where = array('is_on_sale'=>1);
       if($_SESSION['shop_type']==1) $where['shop_id'] = $_SESSION['shop_id'];
       $aCate = M('goods_category')->where(array('parent_id'=>138))->field('id')->select();
       $aCate = array_column($aCate, 'id');
       $aCate[]=138;
       $where['cat_id'] = array('in',$aCate);
        $hot_new_goods = M('goods')->where($where)->order('sort desc,is_hot desc')->limit(9)->cache(true,TPSHOP_CACHE_TIME)->select();//首页热卖商品
        if(count($hot_new_goods)==0){
            unset($where['is_hot']);
            $hot_new_goods = M('goods')->where($where)->order('sort desc')->limit(9)->cache(true,TPSHOP_CACHE_TIME)->select();
        }

        // 热销二手车
       // $where = array('is_hot'=>1,'is_on_sale'=>1);
        $where = array('is_on_sale'=>1);
       if($_SESSION['shop_type']==1) $where['shop_id'] = $_SESSION['shop_id'];
       $cates =  M('goods_category')->where(array('parent_id'=>138))->field('id')->select();
       $cates = array_column($cates, 'id');
       $cates[]=164;
       $where['cat_id'] = array('in',$cates);
        $hot_er_goods = M('goods')->where($where)->order('sort desc,is_hot desc')->limit(9)->cache(true,TPSHOP_CACHE_TIME)->select();//首页热卖商品
        if(count($hot_new_goods)==0){
            unset($where['is_hot']);
            $hot_er_goods = M('goods')->where($where)->order('sort desc')->limit(9)->cache(true,TPSHOP_CACHE_TIME)->select();
        }
        
        unset($where['cat_id']);
        //新品上市
        unset($where['is_hot']);
        $where['is_new']=1;
        $new_goods = M('goods')->where($where)->order('sort desc')->limit(9)->cache(true,TPSHOP_CACHE_TIME)->select();//首页新品商品
        if(count($new_goods)==0){
            unset($where['is_new']);
            $new_goods = M('goods')->where($where)->order('sort desc')->limit(9)->cache(true,TPSHOP_CACHE_TIME)->select();
        }
        $thems = M('goods_category')->where('level=1')->order('sort_order')->limit(9)->cache(true,TPSHOP_CACHE_TIME)->select();
     
        //select * from __PREFIX__goods where shop_id=$shop_id and is_recommend=1 order by sort limit 9  精品推荐
        unset($where['is_new']);
        $where['is_recommend']=1;
        $recommend_goods = M('goods')->where($where)->order('sort desc')->limit(9)->cache(true,TPSHOP_CACHE_TIME)->select();//精品推荐
        $thems = M('goods_category')->where('level=1')->order('sort_order')->limit(9)->cache(true,TPSHOP_CACHE_TIME)->select();

        //sql="select * from __PREFIX__goods as g inner join __PREFIX__flash_sale as f on g.goods_id = f.goods_id where g.shop_id=$shop_id limit 3"  促销商品
        //M('wxshare')->alias('w')->join('__USERS__ u','u.user_id = w.user_id')->join('__GOODS__ g','g.goods_id = w.goods_id')->where($where)->limit($Page->firstRow, $Page->listRows)->select();
        $condition = '';
        if($_SESSION['shop_type']==1) $condition = 'g.shop_id='.$_SESSION['shop_id'];
        $sales_goods = M('goods')->alias('g')->join('__FLASH_SALE__ f','g.goods_id=f.goods_id')->where($condition)->limit(3)->select();

        //获取首页菜单 <imshop sql="SELECT * FROM `__PREFIX__navigation` where shop_id=$shop_id and is_show = 1 and type=2 ORDER BY `sort` DESC limit 4" key="k" item='v'>
        $navwhere = array();
        $navwhere['is_show']=1;
        $navwhere['type']=2;
        if($_SESSION['shop_type']==1){
            $navwhere['shop_id'] = $_SESSION['shop_id'];
            $navigations = M('navigation')->where($navwhere)->order('sort desc')->limit(4)->select();
            if(count($navigations)==0) {
                $navwhere['shop_id'] = 0;
                
            }
        }else{
            $navwhere['shop_id'] = 0;
        }
        $navigations = M('navigation')->where($navwhere)->order('sort desc')->limit(4)->select();

        //首页轮播图<adv pid ="2" limit="5" item="v" sid="$shop_id" position="1">
         $barwhere = array();
        $barwhere['pid']=2;
        $barwhere['position_type']=1;
        if($_SESSION['shop_type']==1){
            $barwhere['shop_id'] = $_SESSION['shop_id'];
            $banners = M('ad')->where($barwhere)->limit(5)->select();
            if(count($banners)==0) {
                $barwhere['shop_id'] = 0;
                
            }
        }else{
            $barwhere['shop_id'] = 0;
        }
        $banners = M('ad')->where($barwhere)->limit(5)->select();

        $this->assign('banners',$banners);
        $this->assign('navigations',$navigations);
        $this->assign('thems',$thems);
        $this->assign('hot_new_goods',$hot_new_goods);
        $this->assign('hot_er_goods',$hot_er_goods);
        $this->assign('new_goods',$new_goods);
        $this->assign('recommend_goods',$recommend_goods);
        $this->assign('sales_goods',$sales_goods);
//         $favourite_goods = M('goods')->where("is_recommend=1 and is_on_sale=1")->order('goods_id DESC')->limit(20)->cache(true,TPSHOP_CACHE_TIME)->select();//首页推荐商品

//         //秒杀商品
//         $now_time = time();  //当前时间
//         if(is_int($now_time/7200)){      //双整点时间，如：10:00, 12:00
//             $start_time = $now_time;
//         }else{
//             $start_time = floor($now_time/7200)*7200; //取得前一个双整点时间
//         }
//         $end_time = $start_time+7200;   //结束时间
//         $seckill_list=DB::query("select * from __PREFIX__goods as g inner join __PREFIX__flash_sale as f on g.goods_id = f.goods_id where start_time = $start_time and end_time = $end_time limit 3");     //获取秒杀商品
// // dump($seckill_list);die;
//         $this->assign('seckill_list',$seckill_list);
//         $this->assign('start_time',$start_time);
//         $this->assign('end_time',$end_time);
//         $this->assign('favourite_goods',$favourite_goods);
        return $this->fetch();
    }

    /**
     * 分类列表显示
     */
    public function categoryList(){
        return $this->fetch();
    }

    /**
     * 模板列表
     */
    public function mobanlist(){
        $arr = glob("D:/wamp/www/svn_imshop/mobile--html/*.html");
        foreach($arr as $key => $val)
        {
            $html = end(explode('/', $val));
            echo "<a href='http://www.php.com/svn_imshop/mobile--html/{$html}' target='_blank'>{$html}</a> <br/>";            
        }        
    }
    
    /**
     * 商品列表页
     */
    public function goodsList(){
        $id = I('get.id/d',0); // 当前分类id
        $lists = getCatGrandson($id);
        $this->assign('lists',$lists);
        return $this->fetch();
    }
    
    public function ajaxGetMore(){
    	$p = I('p/d',1);
        $where['is_recommend']=1;
        $where['is_on_sale']=1;
        $_SESSION['shop_id']?$where['shop_id']=$_SESSION['shop_id']:$where['shop_id']=0;
    	$favourite_goods = M('goods')->where($where)->order('goods_id DESC')->page($p,10)->cache(true,TPSHOP_CACHE_TIME)->select();//首页推荐商品
    	$this->assign('favourite_goods',$favourite_goods);
    	return $this->fetch();
    }

    public function test()
    {
        return $this->fetch();
    }

    public function pop()
    {
      return $this->fetch();
    }
}