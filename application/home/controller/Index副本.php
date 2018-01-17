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
class Index extends Base {
    
    public function index(){      

        // 如果是手机跳转到 手机模块
        if(true == isMobile()){
            header("Location: ".U('Mobile/Index/index'));
        }
        
       
        $hot_goods = $hot_cate = $cateList = array();
        if ($_SESSION['shop_type']==1) {
       $sql = "select a.goods_name,a.goods_id,a.shop_price,a.shop_id,a.is_hot,a.market_price,a.sort,a.cat_id,a.is_new,b.parent_id_path,b.name from ".C('database.prefix')."goods as a left join ";
        $sql .= C('database.prefix')."goods_category as b on a.cat_id=b.id where a.is_hot=1 and a.is_on_sale=1 and b.shop_id=".$_SESSION['shop_id']." and a.shop_id=".$_SESSION['shop_id']." order by a.sort desc limit 6";//二级分类下热卖商品   
        }else{
        $sql = "select a.goods_name,a.goods_id,a.shop_id,a.is_hot,a.shop_price,a.sort,a.market_price,a.cat_id,a.is_new,b.parent_id_path,b.name from ".C('database.prefix')."goods as a left join ";
        $sql .= C('database.prefix')."goods_category as b on a.cat_id=b.id where a.is_hot=1 and a.is_on_sale=1 order by a.sort desc limit 6";//二级分类下热卖商品   
        }   

         // $index_hot_goods = S('index_hot_goods');
    
        if(empty($index_hot_goods))
        {
           
            $index_hot_goods = Db::query($sql);//首页热卖商品
            S('index_hot_goods',$index_hot_goods,TPSHOP_CACHE_TIME);
        }
       
        if($index_hot_goods){
                foreach($index_hot_goods as $val){
                        $cat_path = explode('_', $val['parent_id_path']);
                        $hot_goods[$cat_path[1]][] = $val;
                }
        }
        $hot_category = M('goods_category')->where("is_hot=1 and level=3 and is_show=1")->cache(true,TPSHOP_CACHE_TIME)->select();//热门三级分类
        foreach ($hot_category as $v){
        	$cat_path = explode('_', $v['parent_id_path']);
        	$hot_cate[$cat_path[1]][] = $v;
        }
        
        foreach ($this->cateTrre as $k=>$v){
            if($v['is_hot']==1){
        		$v['hot_goods'] = empty($hot_goods[$k]) ? '' : $hot_goods[$k];
        		$v['hot_cate'] = empty($hot_cate[$k]) ? '' : $hot_cate[$k];
        		$cateList[] = $v;
        	}
        }
       $data = DB::query('select * from `tb_goods_category` where is_show = 1 and `level` = 1 AND id>10 limit 7');
       // var_dump($index_hot_goods);die();
       $this->assign('hot_goods',$index_hot_goods);
        $this->assign('cateList',$cateList);
        $_SESSION['shop_type']==1?$where=' and shop_id='.$_SESSION['shop_id']:false;
        // var_dump($where);die();
        $this->assign('where',$where);
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