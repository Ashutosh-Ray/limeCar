<?php
/**
 * ThinkPHP [ WE CAN DO IT JUST THINK ]
 +----------------------------------------------------------------------
 * * Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
 * 网站地址: http://www.imshop.cn
 +----------------------------------------------------------------------
 * 
 * 
 +----------------------------------------------------------------------
 * $Author: IT宇宙人 2015-08-10 $
 *
 */ 
namespace app\home\controller; 
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
use app\home\logic\GoodsLogic;
class Index extends Base {
    
    public function index(){      

                // 如果是手机跳转到 手机模块
        if(true == isMobile()){
            header("Location: ".U('Mobile/Index/index'));
        }
         
        $where = array();
        if($_SESSION['shop_type']==1){
             $where['shop_id'] = $_SESSION['shop_id'];
        }
        $where['is_on_sale']=1;
         $where['is_vip']=0;
        //新车
        $cat_id_arr = getCatGrandson (138);
        $where['is_recommend']=0;
        $where['cat_id']=array('in',implode(',', $cat_id_arr));
        $where['del_status']=0;
        $newCars = M('Goods')->where($where)->limit(4)->order('sort DESC,goods_id DESC')->select();

        //二手车
        $cat_id_arr = getCatGrandson (164);
        $where['cat_id']=array('in',implode(',', $cat_id_arr));
        $usedCars = M('Goods')->where($where)->limit(4)->order('sort DESC,goods_id DESC')->select();

        //养护用品
        $cat_id_arr = getCatGrandson (165);
        $where['is_recommend']=0;
        $where['cat_id']=array('in',implode(',', $cat_id_arr));
        $curings = M('Goods')->where($where)->limit(6)->order('sort DESC,goods_id DESC')->select();
    
        //轮播
        $banners = M('Ad')->where(array('pid'=>1,'media_type'=>0,'position_type'=>0))->order('orderby DESC')->select();
       
       
       $this->assign('banners',$banners);
       $this->assign('newCars',$newCars);
       $this->assign('usedCars',$usedCars);
       $this->assign('curings',$curings);
        return $this->fetch();
    }
 
    /**
     *  公告详情页
     */
    public function notice(){
        return $this->fetch();
    }
    
    // 二维码
    public function qr_code(){        
        // 导入Vendor类库包 Library/Vendor/Zend/Server.class.php
        //http://www.imshop.com/Home/Index/erweima/data/www.sanpinche.com
         //require_once 'vendor/phpqrcode/phpqrcode.php';
         vendor('phpqrcode.phpqrcode'); 
          //import('Vendor.phpqrcode.phpqrcode');
            error_reporting(E_ERROR);            
            $url = urldecode($_GET["data"]);
            $url = urldecode('http://www.sanpinche.com/');
            \QRcode::png($url);
			exit;        
    }
    
    // 验证码
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : '';
        $fontSize = I('get.fontSize') ? I('get.fontSize') : '40';
        $length = I('get.length') ? I('get.length') : '4';
        
        $config = array(
            'fontSize' => $fontSize,
            'length' => $length,
            'useCurve' => true,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);        
    }
    
    // 促销活动页面
    public function promoteList()
    {
        $goodsList = DB::query("select * from __PREFIX__goods as g inner join __PREFIX__flash_sale as f on g.goods_id = f.goods_id   where ".time()." > start_time  and ".time()." < end_time");
        $brandList = M('brand')->getField("id,name,logo");
        $this->assign('brandList',$brandList);
        $this->assign('goodsList',$goodsList);
        return $this->fetch();
    }
    
    function truncate_tables (){
        $tables = DB::query("show tables");
        $table = array('tp_admin','tp_config','tp_region','tp_system_module','tp_admin_role','tp_system_menu','tp_article_cat','tp_wx_user');
        foreach($tables as $key => $val)
        {                                    
            if(!in_array($val['Tables_in_imshop'], $table))                             
                echo "truncate table ".$val['Tables_in_imshop'].' ; ';
                echo "<br/>";         
        }                
    }

    /**
     * 猜你喜欢
     * @author lxl
     * @time 17-2-15
     */
    public function ajax_favorite(){
        $p = I('p/d',1);
        $i = I('i',5); //显示条数
        $favourite_goods = M('goods')->where("is_recommend=1 and is_on_sale=1")->order('goods_id DESC')->page($p,$i)->cache(true,TPSHOP_CACHE_TIME)->select();//首页推荐商品
        $this->assign('favourite_goods',$favourite_goods);
        return $this->fetch();
    }
}