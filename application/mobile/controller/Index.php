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
use think\AjaxPage;
class Index extends MobileBase {

    public function index(){
        $where = array();
        if($_SESSION['shop_type']==1){
             $where['shop_id'] = $_SESSION['shop_id'];
        }
        $where['is_on_sale']=1;
        $where['del_status']=0;
        $where['is_vip']=0;
        //新车
        $cat_id_arr = getCatGrandson (138);
        $where['is_recommend']=0;
        $where['cat_id']=array('in',implode(',', $cat_id_arr));
        $newCars = M('Goods')->where($where)->limit(4)->order('sort DESC,goods_id DESC')->select();

        //二手车
        $cat_id_arr = getCatGrandson (164);
        $where['cat_id']=array('in',implode(',', $cat_id_arr));
        $usedCars = M('Goods')->where($where)->limit(4)->order('sort DESC,goods_id DESC')->select();

        //养护用品
        $cat_id_arr = getCatGrandson (165);
        $where['cat_id']=array('in',implode(',', $cat_id_arr));
        $where['is_recommend']=0;
        $curings = M('Goods')->where($where)->limit(4)->order('sort DESC,goods_id DESC')->select();
       
       //推荐的养护
        $where['is_recommend']=1;
        $cat_id_arr = getCatGrandson (165);
        $where['cat_id']=array('in',implode(',', $cat_id_arr));
        $rcurings = M('Goods')->where($where)->limit(2)->order('sort DESC,goods_id DESC')->select();

        //推荐的新车
        $cat_id_arr = getCatGrandson (138);
        $where['cat_id']=array('in',implode(',', $cat_id_arr));
        $where['is_recommend']=1;
        $rnewCars = M('Goods')->where($where)->limit(1)->order('sort DESC,goods_id DESC')->select();

        //轮播
        $banners = M('Ad')->where(array('pid'=>1,'media_type'=>0,'position_type'=>1))->order('orderby DESC')->select();

       $this->assign('banners',$banners);
       $this->assign('newCars',$newCars);
       $this->assign('usedCars',$usedCars);
       $this->assign('curings',$curings);
       $this->assign('rcurings',$rcurings);
       $this->assign('rnewCars',$rnewCars);
        return $this->fetch();
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
    *青柠合伙人页面
    */
    public function limecar_partner()
    {
        if(session('?user')){
            $user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            $user = $user;
            $partner = M('Partner')->where(array('user_id'=>$user['user_id'],'partner_status'=>1))->find();
            if (is_array($partner)) {
                $this->redirect('Mobile/Partner/partner_info');
            }
        }
       return $this->fetch();
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


    //活动页面
    public function activity(){
        return $this->fetch();
    }

    //车险
    public function auto_insurance()
    {
        return $this->fetch();
    }

    //养护
    public function curings()
    {
        $type = I('get.type');
        $like = array();
        $tag_ids = array();
        if ($type=='curing') {
            $like[] = '%保养%';
        }elseif ($type=='wash') {
            $like[] = '%洗车%';
            $like[] = '%美容%';
        }
        foreach ($like as $key => $value) {
            $map['name'] = array('like',$value);
            $tag_id = M('Tag')->where($map)->getField('id');
            if (!empty($tag_id)&&is_numeric($tag_id)) $tag_ids[] = $tag_id;
        }
        
        if (count($tag_ids)>0) {
            //养护用品
            $cat_id_arr = getCatGrandson (165);
            $where['g.cat_id']=array('in',implode(',', $cat_id_arr));
            $where['gt.tag_id'] = array('in',implode(',', $tag_ids));
            $where['g.del_status']=0;
            $where['g.is_on_sale']=1;
            $where['g.is_vip']=0;
            $count =  M('GoodsTag')->alias('gt')->join('__GOODS_LEVEL__ gl','gt.goods_id=gl.goods_id','LEFT')->join('__GOODS__ g','gt.goods_id=g.goods_id','LEFT')->where($where)->count();
            $page = new AjaxPage($count,10);
            $curing = M('GoodsTag')->alias('gt')->join('__GOODS_LEVEL__ gl','gt.goods_id=gl.goods_id','LEFT')->join('__GOODS__ g','gt.goods_id=g.goods_id','LEFT')->where($where)->order('g.shop_price asc')->limit($page->firstRow.','.$page->listRows)->select();
        }
        $this->assign('goods_list',$curing);
       if (IS_AJAX) {
          return $this->fetch('ajax_curings');
       }else{
            return $this->fetch();
       }
       
    }


}