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
use app\home\logic\CartLogic;
use app\home\logic\GoodsLogic;
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
class Goods extends Base {
    public function index(){      
        return $this->fetch();
    }


   /**
    * 商品详情页
    */ 
    public function goodsInfo(){
      // $result = shunfuRefund();
      // var_dump($result);die();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
        }
        $goodsLogic = new \app\home\logic\GoodsLogic();
        $goods_id = I("get.id/d");
        $where['is_vip'] = I('get.is_vip',0);
        $where['del_status']=0;
        $where['goods_id'] = $goods_id;
         if ($where['is_vip']) {
            $levelsCount = M('GoodsLevel')->where(array('goods_id'=>$goods_id,'level_id'=>$user['level']))->count();
          
            if ($levelsCount<1) {
               $this->error('没有权限查看此商品');
            }
        }
        $goods = M('Goods')->where($where)->find();
        
        if(empty($goods) || ($goods['is_on_sale'] == 0)){
        	$this->error('该商品已经下架',U('Index/index'));
        }
     
        if($goods['brand_id']){
            $brnad = M('brand')->where("id",$goods['brand_id'])->find();
            $goods['brand_name'] = $brnad['name'];
        }  
        $goods_images_list = M('GoodsImages')->where("goods_id", $goods_id)->select(); // 商品 图册
        // $goods_attribute = M('GoodsAttribute')->getField('attr_id,attr_name'); // 查询属性
        // $goods_attr_list = M('GoodsAttr')->where("goods_id", $goods_id)->select(); // 查询商品属性表
	    $filter_spec = $goodsLogic->get_spec($goods_id);
             
        //商品是否正在促销中        
        if($goods['prom_type'] == 1)
        {
            $goods['flash_sale'] = get_goods_promotion($goods['goods_id']);                        
            $flash_sale = M('flash_sale')->where("id", $goods['prom_id'])->find();
            $this->assign('flash_sale',$flash_sale);
        }
       
        $freight_free = tpCache('shopping.freight_free'); // 全场满多少免运费
        $spec_goods_price  = M('spec_goods_price')->where("goods_id", $goods_id)->getField("key,price,store_count"); // 规格 对应 价格 库存表

        M('Goods')->where("goods_id", $goods_id)->save(array('click_count'=>$goods['click_count']+1 )); //统计点击数

        $commentStatistics = $goodsLogic->commentStatistics($goods_id);// 获取某个商品的评论统计
        $point_rate = tpCache('shopping.point_rate');
        $Programme = getProgramme('',$goods_id);
        $Programme['goods_id'] = $goods_id;
        $cases = M('GoodsProgramme')->where($Programme)->select();

         $cat_id = $goods['cat_id'];
          $goodsAttribute =M('Attribute')->alias('a')
          ->field('a.*,av.id AS attr_val_id,av.attr_val ')
          ->join('__ATTR_VAL__ av','av.attribute_id = a.id','LEFT')
          ->where(array('a.cat_id'=>$cat_id,'del_status'=>0,'av.goods_id'=>$goods_id))
          ->select();

        $this->assign('goods_attributes', $goodsAttribute);// 商品属性和值
        $this->assign('cases',$cases);//购车方案
        $this->assign('freight_free', $freight_free);// 全场满多少免运费
        $this->assign('spec_goods_price', json_encode($spec_goods_price,true)); // 规格 对应 价格 库存表
        $this->assign('navigate_goods',navigate_goods($goods_id,1));// 面包屑导航
        $this->assign('commentStatistics',$commentStatistics);//评论概览
        // $this->assign('goods_attribute',$goods_attribute);//属性值     
        // $this->assign('goods_attr_list',$goods_attr_list);//属性列表
        $this->assign('filter_spec',$filter_spec);//规格参数
        $this->assign('goods_images_list',$goods_images_list);//商品缩略图
        $this->assign('siblings_cate',$goodsLogic->get_siblings_cate($goods['cat_id']));//相关分类
        $this->assign('look_see',$goodsLogic->get_look_see($goods));//看了又看      
        $this->assign('goods',$goods);
        $this->assign('point_rate',$point_rate);
 
        //可用门店
        $ip = getIP();
        $content = file_get_contents("http://api.map.baidu.com/location/ip?ak=NjnIbhfMbZ0weXzGXbpBbqB78ozUKM8f&ip={$ip}&coor=bd09ll");
         $json = json_decode($content);
         $arr = array('lon'=>$json->{'content'}->{'point'}->{'x'},'lat'=>$json->{'content'}->{'point'}->{'y'}, $json->{'content'}->{'address'});
        $lat = $arr['lat'];
        $lon =$arr['lon'];
        $sql ="SELECT shop_name FROM `tb_admin` WHERE type=1";
        if (!empty($lat)&&!empty($lon)) {
            $sql = 'SELECT shop_name,shop_lon,distance_um,shop_lat,ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(('.$lat.' * PI() / 180 - shop_lat * PI() / 180) / 2),2) + COS('.$lat.' * PI() / 180) * COS(shop_lat * PI() / 180) * POW( SIN(('.$lon.' * PI() / 180 - shop_lon * PI() / 180) / 2),2))) * 1000) AS distance_um FROM tb_admin WHERE type=1 ORDER BY distance_um ASC ';
        }

        
        $cid=$goods['cat_id'];       
        $shops = DB::query($sql);
        $this->assign('shops',$shops);  
           
         $url = $this->jumpType($cid);
        if (!empty($url)) {
          
            return $this->fetch($url.'Info');
          }else{
           return $this->error('页面不存在');
          }         
    }

    
    public function goodsInfo2(){
        $this->fetch();
    }
    
    /**
     * 获取可发货地址
     */
    public function getRegion()
    {
        $goodsLogic = new GoodsLogic();
        $region_list = $goodsLogic->getRegionList();//获取配送地址列表
        $region_list['status'] = 1;
        $this->ajaxReturn($region_list);
    }
    
    /**
     * 商品列表页
     */
    public function goodsList(){ 
        
        $key = md5($_SERVER['REQUEST_URI'].I('start_price').'_'.I('end_price'));
        // $html = S($key);
        if(!empty($html))
        {
            return $html;
        }
        
        $filter_param = array(); // 帅选数组                        
        $id = I('get.id/d',1); // 当前分类id
        $brand_id = I('get.brand_id/d',0);
        $spec = I('get.spec',0); // 规格 
        $attr = I('get.attr',''); // 属性        
        $sort = I('get.sort','goods_id'); // 排序
        $sort_asc = I('get.sort_asc','asc'); // 排序
        $price = I('get.price',''); // 价钱
        $start_price = trim(I('post.start_price','0')); // 输入框价钱
        $end_price = trim(I('post.end_price','0')); // 输入框价钱        
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
     
        $filter_param['id'] = $id; //加入帅选条件中                       
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中
        $spec  && ($filter_param['spec'] = $spec); //加入帅选条件中
        $attr  && ($filter_param['attr'] = $attr); //加入帅选条件中
        $price  && ($filter_param['price'] = $price); //加入帅选条件中
                
        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
        
        // 分类菜单显示
        $goodsCate = M('GoodsCategory')->where("id", $id)->find();// 当前分类
        //($goodsCate['level'] == 1) && header('Location:'.U('Home/Channel/index',array('cat_id'=>$id))); //一级分类跳转至大分类馆        
        $cateArr = $goodsLogic->get_goods_cate($goodsCate);

        // 帅选 品牌 规格 属性 价格
        $cat_id_arr = getCatGrandson ($id);
        $filter_goods_id = M('goods')->where(['del_status'=>0,'is_on_sale'=>1,'cat_id'=>['in',implode(',', $cat_id_arr)]])->cache(true)->getField("goods_id",true);
        // 过滤帅选的结果集里面找商品        
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id    
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个帅选条件的结果 的交集
        }
        if($spec)// 规格
        {
            $goods_id_2 = $goodsLogic->getGoodsIdBySpec($spec); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_2); // 获取多个帅选条件的结果 的交集
        }
        if($attr)// 属性
        {
            $goods_id_3 = $goodsLogic->getGoodsIdByAttr($attr); // 根据 规格 查找当所有商品id
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_3); // 获取多个帅选条件的结果 的交集
        }        
           
        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'goodsList'); // 获取显示的帅选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'goodsList'); // 帅选的价格期间         
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的帅选品牌        
        $filter_spec  = $goodsLogic->get_filter_spec($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的帅选规格        
        $filter_attr  = $goodsLogic->get_filter_attr($filter_goods_id,$filter_param,'goodsList',1); // 获取指定分类下的帅选属性        
                                
        $count = count($filter_goods_id);
        $page = new Page($count,12);

        if($count > 0)
        {

            $where['goods_id'] = array('in',implode(',', $filter_goods_id));
            if($_SESSION['shop_type']==1) $where['shop_id'] = $_SESSION['shop_id'];
            $where['is_vip']=0;
            $goods_list = M('goods')->where($where)->order("sort desc")->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if($filter_goods_id2)
            $goods_images = M('goods_images')->where("goods_id", "in", implode(',', $filter_goods_id2))->cache(true)->select();
        }
        // print_r($filter_menu);         
        $goods_category = M('goods_category')->where('is_show=1')->cache(true)->getField('id,name,parent_id,level'); // 键值分类数组
        $navigate_cat = navigate_goods($id); // 面包屑导航         
        $this->assign('goods_list',$goods_list);
        $this->assign('navigate_cat',$navigate_cat);
        $this->assign('goods_category',$goods_category);                
        $this->assign('goods_images',$goods_images);  // 相册图片
        $this->assign('filter_menu',$filter_menu);  // 帅选菜单
        $this->assign('filter_spec',$filter_spec);  // 帅选规格
        $this->assign('filter_attr',$filter_attr);  // 帅选属性
        $this->assign('filter_brand',$filter_brand);  // 列表页帅选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 帅选的价格期间
        $this->assign('goodsCate',$goodsCate);
        $this->assign('cateArr',$cateArr);
        $this->assign('filter_param',$filter_param); // 帅选条件
        $this->assign('cat_id',$id);
        $this->assign('page',$page);// 赋值分页输出 
        $url = $this->jumpType($id);
   
        if (!empty($url)) {
          
            $html = $this->fetch($url.'List');
          }else{
            $this->error('页面不存在');
          }  
                
        S($key,$html);
        return $html;
    } 

    private function jumpType($cat_id){
       
        $cateRules = array('138'=>'goods','164'=>'usedCar','165'=>'curingCar');
        $GoodsLogic = new GoodsLogic();
        $data = $GoodsLogic->find_parent_cat($cat_id);
        foreach ($cateRules as $key => $value) {
            if (in_array($key, $data)) {
                return $value;
            }
        }


    }   

    /**
     *  查询配送地址，并执行回调函数
     */
    public function region()
    {
        $fid = I('fid/d');
        $callback = I('callback');
        $parent_region = M('region')->field('id,name')->where(array('parent_id'=>$fid))->cache(true)->select();
        echo $callback.'('.json_encode($parent_region).')';
        exit;
    }

    /**
     * 商品物流配送和运费
     */
    public function dispatching()
    {        
        $goods_id = I('goods_id/d');//143
        $region_id = I('region_id/d');//28242
        $goods_logic = new GoodsLogic();
        $dispatching_data = $goods_logic->getGoodsDispatching($goods_id,$region_id);
        $this->ajaxReturn($dispatching_data);
    }

    /**
     * 商品搜索列表页
     */
    public function search()
    {
       //C('URL_MODEL',0);
        $filter_param = array(); // 帅选数组                        
        $id = I('get.id/d',0); // 当前分类id 
        $brand_id = I('brand_id/d',0);         
        $sort = I('sort','goods_id'); // 排序
        $sort_asc = I('sort_asc','asc'); // 排序
        $price = I('price',''); // 价钱
        $start_price = trim(I('start_price','0')); // 输入框价钱
        $end_price = trim(I('end_price','0')); // 输入框价钱
        if($start_price && $end_price) $price = $start_price.'-'.$end_price; // 如果输入框有价钱 则使用输入框的价钱
        $q = urldecode(trim(I('q',''))); // 关键字搜索
        // empty($q) && $this->error('请输入搜索词');
        $type = I('type'); //1代表是商家
       
        // $type = 1;
        $id && ($filter_param['id'] = $id); //加入帅选条件中                       
        $brand_id  && ($filter_param['brand_id'] = $brand_id); //加入帅选条件中        
        $price  && ($filter_param['price'] = $price); //加入帅选条件中
        $q  && ($_GET['q'] = $filter_param['q'] = $q); //加入帅选条件中
        
        $goodsLogic = new GoodsLogic(); // 前台商品操作逻辑类
               
        $where  = array(
            'is_on_sale' => 1,
            'is_vip'=>0
        );
        if($_SESSION['shop_type']==1) $where['shop_id'] = $_SESSION['shop_id'];
        if($type==1){
          $where = array('g.is_on_sale'=>1,'g.is_vip'=>0);  
          
        } 
        if($_SESSION['shop_type']==1) $where['g.shop_id'] = $_SESSION['shop_id'];
        //引入
        if(file_exists(PLUGIN_PATH.'coreseek/sphinxapi.php'))
        {
            require_once(PLUGIN_PATH.'coreseek/sphinxapi.php');
            $cl = new \SphinxClient();
            $cl->SetServer(C('SPHINX_HOST').'', intval(C('SPHINX_PORT')));
            $cl->SetConnectTimeout(10);
            $cl->SetArrayResult(true);
            $cl->SetMatchMode(SPH_MATCH_ANY);
            $res = $cl->Query($q, "mysql");
            if($res){
                $goods_id_array = array();
                foreach ($res['matches'] as $key => $value) {
                    $goods_id_array[] = $value['id'];
                }
                if(!empty($goods_id_array)){
                    $where['goods_id'] = array('in',$goods_id_array);
                    if($type==1) $where['g.goods_id'] = array('in',$goods_id_array);
                }else{
                    $where['goods_id'] = 0;
                    if($type==1) $where['g.goods_id'] = 0;
                }
            }else{
               if($type!=1) $where['goods_name'] = array('like','%'.$q.'%');
                if($type==1) $where['a.user_name'] = array('like','%'.$q.'%');
            }
        }else{
           if($type!=1) $where['goods_name'] = array('like','%'.$q.'%');
             if($type==1) $where['a.user_name'] = array('like','%'.$q.'%');
        }


        if($id)
        {
            $cat_id_arr = getCatGrandson ($id);
            $where['cat_id'] = array('in',implode(',', $cat_id_arr));
        }
        // $_SESSION['shop_id']?$where['shop_id']=$_SESSION['shop_id']:$where['shop_id']=0;
       if($type!=1) $search_goods = M('goods')->where($where)->getField('goods_id,cat_id');

        if($type==1) $search_goods =  M('goods')->alias('g')->join('__ADMIN__ a','g.shop_id=a.admin_id')->where($where)->getField('g.goods_id,g.cat_id');
        
 
        $filter_goods_id = array_keys($search_goods);
        $filter_cat_id = array_unique($search_goods); // 分类需要去重
        if($filter_cat_id)        
        {
            $cateArr = M('goods_category')->where("id","in",implode(',', $filter_cat_id))->select();
            $tmp = $filter_param;
            foreach($cateArr as $k => $v)            
            {
                $tmp['id'] = $v['id'];
                $cateArr[$k]['href'] = U("/Home/Goods/search",$tmp);                            
            }                
        }                        
        // 过滤帅选的结果集里面找商品        
        if($brand_id || $price)// 品牌或者价格
        {
            $goods_id_1 = $goodsLogic->getGoodsIdByBrandPrice($brand_id,$price); // 根据 品牌 或者 价格范围 查找所有商品id    
            $filter_goods_id = array_intersect($filter_goods_id,$goods_id_1); // 获取多个帅选条件的结果 的交集
        }
        
        $filter_menu  = $goodsLogic->get_filter_menu($filter_param,'search'); // 获取显示的帅选菜单
        $filter_price = $goodsLogic->get_filter_price($filter_goods_id,$filter_param,'search'); // 帅选的价格期间         
        $filter_brand = $goodsLogic->get_filter_brand($filter_goods_id,$filter_param,'search',1); // 获取指定分类下的帅选品牌        
                                
        $count = count($filter_goods_id);
        $page = new Page($count,12);
        

        if($count > 0)
        {
           
            
           if($type!=1) $goods_list = M('goods')->where(['is_on_sale'=>1,'goods_id'=>['in',implode(',', $filter_goods_id)]])->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
           if($type==1) $goods_list = M('goods')->alias('g')->join('__ADMIN__ a','g.shop_id=a.admin_id')->where(['g.is_on_sale'=>1,'g.goods_id'=>['in',implode(',', $filter_goods_id)],'a.user_name'=>['like',"%$q%"]])->order("$sort $sort_asc")->limit($page->firstRow.','.$page->listRows)->select();
            $filter_goods_id2 = get_arr_column($goods_list, 'goods_id');
            if($filter_goods_id2)
            $goods_images = M('goods_images')->where("goods_id", "in",implode(',', $filter_goods_id2))->select();
        }    

        $this->assign('goods_list',$goods_list);  
        $this->assign('goods_images',$goods_images);  // 相册图片
        $this->assign('filter_menu',$filter_menu);  // 帅选菜单
        $this->assign('filter_brand',$filter_brand);  // 列表页帅选属性 - 商品品牌
        $this->assign('filter_price',$filter_price);// 帅选的价格期间
        $this->assign('cateArr',$cateArr);
        $this->assign('filter_param',$filter_param); // 帅选条件
        $this->assign('cat_id',$id);
        $this->assign('page',$page);// 赋值分页输出
        $this->assign('q',I('q'));
        C('TOKEN_ON',false);
        // return $this->fetch();
        $url = $this->jumpType($id);
        if (!empty($url)) {
          
            return $this->fetch($url.'List');
          }else{
           return $this->error('页面不存在');
          }  
    }
    
    /**
     * 商品咨询ajax分页
     */
    public function ajax_consult(){        
        $goods_id = I("goods_id/d", 0);
        $consult_type = I('consult_type','0'); // 0全部咨询  1 商品咨询 2 支付咨询 3 配送 4 售后
                 
        $where  = ['is_show'=>1,'parent_id'=>0,'goods_id'=>$goods_id];
        if($consult_type > 0){
            $where['consult_type'] = $consult_type;
        }
        $count = M('GoodsConsult')->where($where)->count();
        $page = new AjaxPage($count,5);
        $show = $page->show();        
        $list = M('GoodsConsult')->where($where)->order("id desc")->limit($page->firstRow.','.$page->listRows)->select();
        $replyList = M('GoodsConsult')->where("parent_id > 0")->order("id desc")->select();
        
        $this->assign('consultCount',$count);// 商品咨询数量
        $this->assign('consultList',$list);// 商品咨询
        $this->assign('replyList',$replyList); // 管理员回复
        $this->assign('page',$show);// 赋值分页输出        
        return $this->fetch();        
    }
    
    /**
     * 商品评论ajax分页
     */
    public function ajaxComment(){        
        $goods_id = I("goods_id/d",'0');        
        $commentType = I('commentType','1'); // 1 全部 2好评 3 中评 4差评
        $where = ['is_show'=>1,'goods_id'=>$goods_id,'parent_id'=>0];
        if($commentType==5){
            $where['img'] = ['<>',''];
        }else{
        	$typeArr = array('1'=>'0,1,2,3,4,5','2'=>'4,5','3'=>'3','4'=>'0,1,2');
            $where['ceil((deliver_rank + goods_rank + service_rank) / 3)'] = ['in',$typeArr[$commentType]];
        }
        $count = M('Comment')->where($where)->count();                
        
        $page = new AjaxPage($count,2);
        $show = $page->show();   
       
        $list = M('Comment')->alias('c')->join('__USERS__ u','u.user_id = c.user_id','LEFT')->where($where)->order("add_time desc")->limit($page->firstRow.','.$page->listRows)->select();
         
        $replyList = M('Comment')->where(['is_show'=>1,'goods_id'=>$goods_id,'parent_id'=>['>',0]])->order("add_time desc")->select();
        
        foreach($list as $k => $v){
            $list[$k]['img'] = unserialize($v['img']); // 晒单图片            
        }        
        $this->assign('commentlist',$list);// 商品评论
        $this->assign('replyList',$replyList); // 管理员回复
        $this->assign('page',$show);// 赋值分页输出        
        return $this->fetch();        
    }    
    
    /**
     *  商品咨询
     */
    public function goodsConsult(){
        //  form表单提交
        C('TOKEN_ON',true);        
        $goods_id = I("goods_id/d",'0'); // 商品id
        $consult_type = I("consult_type",'1'); // 商品咨询类型
        $username = I("username",'IMSHOP用户'); // 网友咨询
        $content = I("content"); // 咨询内容
        
        $verify = new Verify();
        if (!$verify->check(I('post.verify_code'),'consult')) {
            $this->error("验证码错误");
        }
        
        
        $result = $this->validate(input('post.'),['__token__'=>'require|token'],['__token__'=>'你已经提交过了']);
        if (true !== $result) {
            $this->error($result, U('/Home/Goods/goodsInfo',array('id'=>$goods_id)));             
            exit;
        }                
       
        $goodsConsult = M('goodsConsult');       
        $data = array(
            'goods_id'=>$goods_id,
            'consult_type'=>$consult_type,
            'username'=>$username,
            'content'=>$content,
            'add_time'=>time(),
        );        
        $goodsConsult->add($data);        
        $this->success('咨询已提交!', U('/Home/Goods/goodsInfo',array('id'=>$goods_id))); 
    }
    
    /**
     * 用户收藏某一件商品
     * @param type $goods_id
     */
    public function collect_goods()
    {
        $goods_id = I('goods_id/d');
        $goodsLogic = new GoodsLogic();        
        $result = $goodsLogic->collect_goods(cookie('user_id'),$goods_id);
        exit(json_encode($result));
    }
    
    /**
     * 加入购物车弹出
     */
    public function open_add_cart()
    {        
         return $this->fetch();
    }

    /**
     * 积分商城
     */
    public function integralMall()
    {
        $cat_id = I('get.id/d');
        $minValue = I('get.minValue');
        $maxValue = I('get.maxValue');
        $brandType = I('get.brandType');
        $point_rate = tpCache('shopping.point_rate');
        $is_new = I('get.is_new',0);
        $exchange = I('get.exchange',0);
        $goods_where = array(
            'is_on_sale' => 1,  //是否上架
        );
        //积分兑换筛选
        $exchange_integral_where_array = array(array('gt',0));
        // 分类id
        if (!empty($cat_id)) {
            $goods_where['cat_id'] = array('in', getCatGrandson($cat_id));
        }
        //积分截止范围
        if (!empty($maxValue)) {
            array_push($exchange_integral_where_array, array('elt', $maxValue));
        }
        //积分起始范围
        if (!empty($minValue)) {
            array_push($exchange_integral_where_array, array('egt', $minValue));
        }
        //积分+金额
        if ($brandType == 1) {
            array_push($exchange_integral_where_array, array('exp', ' < shop_price* ' . $point_rate));
        }
        //全部积分
        if ($brandType == 2) {
            array_push($exchange_integral_where_array, array('exp', ' = shop_price* ' . $point_rate));
        }
        //新品
        if($is_new == 1){
            $goods_where['is_new'] = $is_new;
        }
        //我能兑换
        $user_id = cookie('user_id');
        if ($exchange == 1 && !empty($user_id)) {
            $user_pay_points = intval(M('users')->where(array('user_id' => $user_id))->getField('pay_points'));
            if ($user_pay_points !== false) {
                array_push($exchange_integral_where_array, array('lt', $user_pay_points));
            }
        }

        $goods_where['exchange_integral'] =  $exchange_integral_where_array;
        $goods_list_count = M('goods')->where($goods_where)->count();   //总页数
        $page = new Page($goods_list_count, 15);
        $goods_list = M('goods')->where($goods_where)->limit($page->firstRow . ',' . $page->listRows)->select();
        $goods_category = M('goods_category')->where(array('level' => 1))->select();

        $this->assign('goods_list', $goods_list);
        $this->assign('page', $page->show());
        $this->assign('goods_list_count',$goods_list_count);
        $this->assign('goods_category', $goods_category);//商品1级分类
        $this->assign('point_rate', $point_rate);//兑换率
        $this->assign('nowPage',$page->nowPage);// 当前页
        $this->assign('totalPages',$page->totalPages);//总页数
        return $this->fetch();
    }


    /**
    *
    *客户预约到店
    */
    public function ajax_subscribe()
    {
        
        $data = I('post.');
        $map['mobile'] = $data['phone'];
        $map['session_id'] = session_id();
        $map['status']=1;
        $old = M('sms_log')->where($map)->order('id desc')->find();
        $time = time()-$old['add_time'];
        if ($old&&$time>3600)  exit(json_encode(array('status'=>0,'msg'=>'验证码已超时')));

        if($old['code']!=$data['code'])  exit(json_encode(array('status'=>0,'msg'=>'验证码有误')));

        $count = M('Subscribe')->where(array('phone'=>$data['phone'],'goods_id'=>$data['goods_id']))->count();
        if ($count>0)  exit(json_encode(array('status'=>0,'msg'=>'您已经预约过了')));
        $needdata=$this->send_data($data);
        send_template_msg('Xmvnth64cHiXnT3g21eDnlAnN9GWMgveiQOsvN4HbJc','ot1H9jktE7q1y2T7NWphCNgcuCcc',$needdata);
        // $wx_user = M('wx_user')->find();
        // $jssdk = new \app\mobile\logic\Jssdk($wx_user['appid'],$wx_user['appsecret']);
        // $needdata=$this->send_data($data);
        // $jssdk->template_msg('oghVc02c5D86i92th7fKQ1iz-2jo',$needdata);//测试清宁养车
        // $jssdk->template_msg('ot1H9jktE7q1y2T7NWphCNgcuCcc',$needdata);//真的清宁养车
        $data['time'] = strtotime($data['time']);
        $data['add_time'] = time();
        $res =  M('Subscribe')->add($data);

        if ($res) {
             exit(json_encode(array('status'=>1,'msg'=>'预约成功')));
        }else{
            exit(json_encode(array('status'=>0,'msg'=>'预约失败')));
        }
      
    }


    private function send_data($needdata){
      $data = array();
      $first['value'] = '您有新的客户啦！';
      $first['color'] = '#ff9000';
      $data['data']['first'] = $first;

      $keyword1['value'] = $needdata['user_name'];
      // $keyword1['color'] = '#22DD48';
      $data['data']['keyword1'] = $keyword1;

      $keyword2['value'] = $needdata['phone'];
      // $keyword2['color'] = '#ff9933';
      $data['data']['keyword2'] = $keyword2;
      
      $keyword3['value'] = $needdata['time'];
      // $keyword3['color'] = '#ff0066';
      $data['data']['keyword3'] = $keyword3;

      $keyword4['value'] = M('Goods')->where(array('goods_id'=>$needdata['goods_id']))->getField('goods_name');
      // $keyword4['color'] = '#ff0066';
      $data['data']['keyword4'] = $keyword4;

      $keyword5['value'] = '苏州市工业园区若水路1号（职业技术学院西门）';
      // $keyword4['color'] = '#ff0066';
      $data['data']['keyword5'] = $keyword5;

      $remark['value'] = '请在24小时内联系该客户';
      // $remark['color'] = '#2783c9';
      $data['data']['remark'] = $remark;
      return $data;
    }


    /**
    *发送预约验证码
    *
    */
    public function sendCode()
    {
        $num = I('post.phone');
        $map = array();
        $map['mobile'] = $num;
        $map['session_id'] = session_id();
        $map['status']=1;
        $old = M('sms_log')->where($map)->order('id desc')->find();
        $time = time()-$old['add_time'];
        if ($old&&$time<3600) exit(json_encode(array('status'=>0,'msg'=>'一小时内发送一次')));
    
        $code = rand(1000, 9999);
       $tpl_value ='#code#='.$code;
       $res=sendAllMsg($num,$tpl_value,$code,'46982');

       if ($res['status']==1) {
           exit(json_encode(array('status'=>1,'msg'=>'发送成功请查收')));
       }else{
         exit(json_encode(array('status'=>0,'msg'=>$res['msg'])));
       }
      
    
    }


    public function test()
    {
        // buysucess_template('');
        consume_template();
    }
    
}